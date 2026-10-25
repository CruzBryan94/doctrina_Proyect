(() => {
    const table = document.getElementById('workflow-matrix');
    if (!table) return;
    const queues = new WeakMap();
    const editionValues = cell => Object.fromEntries([...cell.querySelectorAll('.edition-field')].map(input => [input.dataset.field, input.value || null]));
    const cellDirty = cell => cell.querySelector('.completion-toggle').checked !== (cell.dataset.saved === '1') || (cell.querySelector('.completion-toggle').checked && cell.querySelector('.completion-date').value !== cell.dataset.date) || (cell.classList.contains('edition-cell') && JSON.stringify(editionValues(cell)) !== cell.dataset.fields);
    const rowDirty = row => [...row.querySelectorAll('.matrix-cell')].some(cellDirty);
    table.querySelectorAll('.edition-cell').forEach(cell => cell.dataset.fields = JSON.stringify(editionValues(cell)));
    const updateSummary = (row, data) => {
        const inView = data.phase === table.dataset.phase && data.published === (new URL(location.href).searchParams.get('published') === '1');
        const before = {total:1, development:Number(row.dataset.summarySubstate === 'development'), validation:Number(row.dataset.summarySubstate === 'validation'), ready:Number(row.dataset.summaryReady || 0)};
        const after = {total:Number(inView), development:Number(inView && data.substate?.code === 'development'), validation:Number(inView && data.substate?.code === 'validation'), ready:Number(inView && !data.published && data.progress >= 100)};
        document.querySelectorAll('[data-matrix-count]').forEach(counter => {
            const key = counter.dataset.matrixCount;
            counter.textContent = Number(counter.textContent) + after[key] - before[key];
        });
        row.dataset.summarySubstate = inView ? data.substate?.code || '' : '';
        row.dataset.summaryReady = after.ready;
    };
    const notice = (message, error = false) => {
        return window.ArtemisAlerts[error ? 'error' : 'info'](message);
    };
    const confirmAdvance = async row => {
        if (row.dataset.confirming || row.dataset.moved || row.dataset.conflict || row.dataset.busy || rowDirty(row) || row.querySelector('.advance').disabled) return;
        row.dataset.confirming = '1';
        const title = row.querySelector('.manual-title').textContent.trim();
        const published = table.dataset.phase === 'cgft';
        try {
            if (await window.ArtemisAlerts.confirm(published
                ? `Se publicará el manual «${title}». ¿Deseas continuar?`
                : `El manual «${title}» pasará de ${table.dataset.phaseName} a ${table.dataset.nextPhase}. ¿Deseas continuar?`,
                published ? 'Confirmar publicación' : 'Confirmar cambio de fase', published ? 'Publicar manual' : 'Cambiar fase')) {
                save(row, {action:'advance', confirmed:true});
            }
        } finally { row.dataset.confirming = ''; }
    };
    const showCell = (cell, completed, date, label) => {
        cell.querySelector('.completion-toggle').checked = completed;
        const input = cell.querySelector('.completion-date');
        input.value = date || ''; input.disabled = !completed;
        cell.classList.toggle('done', completed);
        cell.querySelector('.cell-state').textContent = label;
    };
    const save = (row, payload, cell = null) => {
        row.dataset.queued = Number(row.dataset.queued || 0) + 1;
        row.querySelector('.advance').disabled = true;
        const task = async () => {
            if (!row.isConnected || row.dataset.conflict || row.dataset.moved) { row.dataset.queued--; return; }
            if (typeof payload === 'function') payload = payload();
            const revision = cell?.dataset.revision || '0';
            row.dataset.busy = '1';
            row.querySelector('.advance').disabled = true;
            if (cell) { cell.classList.add('saving'); cell.classList.remove('error'); cell.querySelector('.cell-state').textContent = 'Guardando…'; }
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 20000);
            try {
                const response = await fetch(row.dataset.url, {
                    method: 'PATCH', signal: controller.signal,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ ...payload, version: Number(row.dataset.version) })
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    if (response.status === 409) row.dataset.conflict = '1';
                    throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'No se pudo guardar. Comprueba la conexión.');
                }
                row.dataset.version = data.version;
                updateSummary(row, data);
                if (cell) {
                    cell.dataset.saved = payload.is_completed ? '1' : '0';
                    cell.dataset.date = payload.is_completed ? payload.completation_date : '';
                    if (cell.classList.contains('edition-cell')) {
                        const saved = data.activity_state;
                        if ((cell.dataset.revision || '0') === revision) cell.querySelectorAll('.edition-field').forEach(input => input.value = saved?.[input.dataset.field] ?? '');
                        cell.dataset.fields = JSON.stringify(Object.fromEntries([...cell.querySelectorAll('.edition-field')].map(input => [input.dataset.field, saved?.[input.dataset.field] == null ? null : String(saved[input.dataset.field])])));
                        cell.querySelector('.deadline-state').textContent = saved?.deadline_status || '';
                    }
                    if ((cell.dataset.revision || '0') === revision) showCell(cell, !!payload.is_completed, cell.dataset.date, 'Guardado');
                    else cell.querySelector('.cell-state').textContent = 'Sin guardar';
                    cell.querySelector('.retry-save')?.remove();
                }
                const oldPhase = table.dataset.phase;
                if (data.phase !== oldPhase || data.published !== (new URL(location.href).searchParams.get('published') === '1')) {
                    const title = row.querySelector('.manual-title').textContent.trim();
                    notice(title + ': ' + (data.published ? 'publicado correctamente.' : 'ahora está en ' + data.phase_name + '.'));
                    row.dataset.moved = '1';
                    const originProgress = data.phase !== oldPhase ? (payload.action === 'retreat' ? 0 : 100) : data.progress;
                    row.querySelector('.progress-label').textContent = originProgress.toLocaleString('es-EC', {minimumFractionDigits:2,maximumFractionDigits:2}) + ' %';
                    row.querySelector('.progress-track span').style.width = originProgress + '%';
                    row.style.opacity = '.45';
                    row.querySelectorAll('input,button,select,textarea').forEach(control => control.disabled = true);
                    setTimeout(() => row.remove(), 3000);
                } else {
                    row.querySelector('.progress-label').textContent = Number(data.progress).toLocaleString('es-EC', {minimumFractionDigits:2,maximumFractionDigits:2}) + ' %';
                    row.querySelector('.progress-track span').style.width = data.progress + '%';
                    row.querySelector('.progress-track').setAttribute('aria-valuenow', data.progress);
                    row.querySelector('.row-activity').textContent = data.activity;
                    const substate = row.querySelector('.manual-substate');
                    substate.hidden = !data.substate;
                    substate.textContent = data.substate?.label || '';
                    substate.className = 'manual-substate' + (['development', 'validation'].includes(data.substate?.code) ? ' substate-' + data.substate.code : '');
                    substate.title = data.substate ? `${data.substate.label} de ${data.phase_name} · Desde ${data.substate.started_on}` : '';
                    row.querySelector('.phase-days').textContent = data.timing.phase_days === null ? 'Sin iniciar' : data.timing.phase_days + ' días';
                    row.querySelector('.total-days').textContent = data.timing.total_days + ' días';
                    row.querySelector('.timing-cell').classList.toggle('overdue', !!data.timing.phase_overdue);
                    row.querySelector('.phase-alert').hidden = !data.timing.phase_overdue;
                    row.querySelector('.research-alert').hidden = !data.timing.research_overdue;
                    row.querySelector('.advance').disabled = data.progress < 100 || data.published;
                    if (!cell) notice('Cambios guardados.');
                }
            } catch (error) {
                const message = error.name === 'AbortError' ? 'La conexión tardó demasiado. Recarga para comprobar si se guardó antes de reintentar.' : error.message;
                if (cell) {
                    cell.classList.add('error');
                    showCell(cell, cell.dataset.saved === '1', cell.dataset.date, 'No guardado');
                    if (!row.dataset.conflict && !cell.querySelector('.retry-save')) {
                        const retry = document.createElement('button'); retry.type = 'button'; retry.className = 'btn btn-sm btn-link retry-save'; retry.textContent = 'Reintentar';
                        retry.addEventListener('click', () => { retry.remove(); save(row, payload, cell); }); cell.append(retry);
                    }
                }
                if (row.dataset.conflict) {
                    row.querySelectorAll('input,button,select,textarea').forEach(control => control.disabled = true);
                    notice(message + ' Recarga la página.', true);
                } else notice(message, true);
            } finally {
                clearTimeout(timeout); row.dataset.busy = ''; row.dataset.queued--; cell?.classList.remove('saving');
                if (!row.dataset.conflict && !row.dataset.moved) {
                    row.querySelector('.advance').disabled = Number(row.dataset.queued) > 0 || rowDirty(row) || Number(row.querySelector('.progress-track').getAttribute('aria-valuenow')) < 100 || new URL(location.href).searchParams.get('published') === '1';
                }
            }
        };
        queues.set(row, (queues.get(row) || Promise.resolve()).then(task));
    };
    table.addEventListener('input', event => {
        const cell = event.target.closest('.edition-cell');
        if (!cell || !event.target.matches('.edition-field,.completion-date,.completion-toggle')) return;
        cell.dataset.revision = Number(cell.dataset.revision || 0) + 1;
        cell.querySelector('.cell-state').textContent = 'Sin guardar';
        cell.closest('tr').querySelector('.advance').disabled = true;
    });
    table.addEventListener('change', event => {
        const cell = event.target.closest('.matrix-cell');
        if (!cell || event.target.matches('.responsible-query')) return;
        const row = cell.closest('tr');
        const checkbox = cell.querySelector('.completion-toggle');
        const date = cell.querySelector('.completion-date');
        if (event.target === checkbox) {
            if (checkbox.checked) {
                date.disabled = false;
                if (!date.value) { cell.querySelector('.cell-state').textContent = 'Fecha obligatoria'; date.focus(); return; }
            } else if (cell.dataset.saved === '0' && !cell.classList.contains('edition-cell')) {
                showCell(cell, false, '', 'Pendiente'); return;
            }
        }
        if (checkbox.checked && !date.value) { cell.querySelector('.cell-state').textContent = 'Fecha obligatoria'; return; }
        if (checkbox.checked && !date.checkValidity()) { notice('Ingresa una fecha de cumplimiento válida, sin superar la fecha actual.', true); return; }
        const payload = () => ({action:'activity', activity_id:Number(cell.dataset.activity), is_completed:checkbox.checked, completation_date:checkbox.checked ? date.value : null, ...(cell.classList.contains('edition-cell') ? editionValues(cell) : {})});
        save(row, cell.classList.contains('edition-cell') ? payload : payload(), cell);
    });
    table.addEventListener('click', async event => {
        const row = event.target.closest('tr[data-manual]');
        if (!row) return;
        if (event.target.closest('.save-start')) {
            const date = row.querySelector('.phase-start');
            if (!date.value || !date.checkValidity()) { notice('Registra una fecha de inicio válida, sin superar la fecha actual.', true); return; }
            save(row, {action:'start', started_on:date.value});
        }
        if (event.target.closest('.retreat')) {
            if (row.dataset.confirming) return;
            row.dataset.confirming = '1';
            try {
                const title = row.querySelector('.manual-title').textContent.trim();
                if (await window.ArtemisAlerts.confirm(`El manual «${title}» retrocederá de ${table.dataset.phaseName} a ${table.dataset.previousPhase}. Solo se permite si todas las actividades actuales están pendientes. ¿Deseas continuar?`)) {
                    save(row, {action:'retreat', confirmed:true});
                }
            } finally { row.dataset.confirming = ''; }
        }
        if (event.target.closest('.advance')) confirmAdvance(row);
    });
    const pendingChanges = () => {
        return [...table.querySelectorAll('tr[data-manual]')].some(row => Number(row.dataset.queued || 0) > 0 || rowDirty(row));
    };
    document.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (!link || link.getAttribute('href').startsWith('#') || !pendingChanges()) return;
        event.preventDefault();
        notice('Hay cambios pendientes. Registra las fechas obligatorias y espera a que termine el guardado antes de salir de la matriz.', true);
    });
    document.addEventListener('submit', event => {
        if (pendingChanges()) {
            event.preventDefault();
            notice('Hay cambios pendientes. Completa las fechas y espera el guardado antes de continuar.', true);
        }
    });
})();

