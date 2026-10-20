(() => {
    const table = document.getElementById('workflow-matrix');
    if (!table) return;
    const queues = new WeakMap();
    const notice = (message, error = false) => {
        const box = document.getElementById('live-status');
        box.textContent = message; box.classList.toggle('error', error); box.hidden = false;
        clearTimeout(box.timer); box.timer = setTimeout(() => box.hidden = true, error ? 12000 : 6500);
    };
    const showCell = (cell, completed, date, label) => {
        cell.querySelector('.completion-toggle').checked = completed;
        const input = cell.querySelector('.completion-date');
        input.value = date || ''; input.disabled = !completed;
        cell.classList.toggle('done', completed);
        cell.querySelector('.cell-state').textContent = label;
    };
    const save = (row, payload, cell = null) => {
        const task = async () => {
            if (!row.isConnected || row.dataset.conflict || row.dataset.moved) return;
            row.dataset.busy = '1';
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
                if (cell) {
                    cell.dataset.saved = payload.is_completed ? '1' : '0';
                    cell.dataset.date = payload.is_completed ? payload.completation_date : '';
                    showCell(cell, !!payload.is_completed, cell.dataset.date, 'Guardado');
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
                    row.querySelectorAll('input,button').forEach(control => control.disabled = true);
                    setTimeout(() => row.remove(), 3000);
                } else {
                    row.querySelector('.progress-label').textContent = Number(data.progress).toLocaleString('es-EC', {minimumFractionDigits:2,maximumFractionDigits:2}) + ' %';
                    row.querySelector('.progress-track span').style.width = data.progress + '%';
                    row.querySelector('.progress-track').setAttribute('aria-valuenow', data.progress);
                    row.querySelector('.row-activity').textContent = data.activity;
                    row.querySelector('.phase-days').textContent = data.timing.phase_days === null ? 'Sin iniciar' : data.timing.phase_days + ' días';
                    row.querySelector('.total-days').textContent = data.timing.total_days + ' días';
                    row.querySelector('.timing-cell').classList.toggle('overdue', !!data.timing.phase_overdue);
                    row.querySelector('.phase-alert').hidden = !data.timing.phase_overdue;
                    row.querySelector('.research-alert').hidden = !data.timing.research_overdue;
                    row.querySelector('.advance').hidden = data.progress < 100 || data.phase === 'cgft';
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
                    row.querySelectorAll('input,button').forEach(control => control.disabled = true);
                    notice(message + ' Recarga la página.', true);
                } else notice(message, true);
            } finally {
                clearTimeout(timeout); row.dataset.busy = ''; cell?.classList.remove('saving');
            }
        };
        queues.set(row, (queues.get(row) || Promise.resolve()).then(task));
    };
    table.addEventListener('change', event => {
        const cell = event.target.closest('.matrix-cell');
        if (!cell) return;
        const row = cell.closest('tr');
        const checkbox = cell.querySelector('.completion-toggle');
        const date = cell.querySelector('.completion-date');
        if (event.target === checkbox) {
            if (checkbox.checked) {
                date.disabled = false;
                if (!date.value) { cell.querySelector('.cell-state').textContent = 'Fecha obligatoria'; date.focus(); return; }
            } else if (cell.dataset.saved === '0') {
                showCell(cell, false, '', 'Pendiente'); return;
            }
        }
        if (checkbox.checked && !date.value) { cell.querySelector('.cell-state').textContent = 'Fecha obligatoria'; return; }
        if (checkbox.checked && !date.checkValidity()) { date.reportValidity(); return; }
        let reason;
        if (!checkbox.checked && cell.dataset.saved === '1') {
            reason = prompt('Motivo para retirar el cumplimiento de esta actividad:');
            if (!reason?.trim()) { showCell(cell, true, cell.dataset.date, 'Guardado'); return; }
        }
        save(row, {action:'activity', activity_id:Number(cell.dataset.activity), is_completed:checkbox.checked, completation_date:checkbox.checked ? date.value : null, reason}, cell);
    });
    table.addEventListener('click', event => {
        const row = event.target.closest('tr[data-manual]');
        if (!row) return;
        if (event.target.closest('.save-start')) {
            const date = row.querySelector('.phase-start');
            if (!date.value || !date.checkValidity()) { date.required = true; date.reportValidity(); return; }
            save(row, {action:'start', started_on:date.value});
        }
        if (event.target.closest('.retreat')) {
            const reason = prompt('Retroceder requiere todas las actividades pendientes. Indica el motivo (cancelar conserva la fase):');
            if (reason?.trim()) save(row, {action:'retreat', reason});
        }
        if (event.target.closest('.advance')) save(row, {action:'advance'});
    });
    window.addEventListener('beforeunload', event => {
        if (table.querySelector('[data-busy="1"]') || [...table.querySelectorAll('.matrix-cell')].some(cell => cell.querySelector('.completion-toggle').checked !== (cell.dataset.saved === '1') || (cell.querySelector('.completion-toggle').checked && cell.querySelector('.completion-date').value !== cell.dataset.date))) {
            event.preventDefault(); event.returnValue = '';
        }
    });
})();

