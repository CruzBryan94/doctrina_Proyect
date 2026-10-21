(() => {
    document.querySelectorAll('.assignment-picker').forEach(picker => {
        const query = picker.querySelector('.assignment-query');
        const clear = picker.querySelector('.assignment-clear');
        const selectedBox = picker.querySelector('.assignment-selected');
        const results = picker.querySelector('.assignment-results');
        const state = picker.querySelector('.assignment-state');
        const more = picker.querySelector('.assignment-more');
        const selected = new Map([...selectedBox.querySelectorAll('[data-id]')].map(chip => [String(chip.dataset.id), {id:chip.dataset.id, label:chip.dataset.label}]));
        let items = new Map(), page = 0, hasMore = false, loading = false;
        let timer, controller, sequence = 0;
        const status = () => {
            state.textContent = `${selected.size} ${selected.size === 1 ? 'seleccionado' : 'seleccionados'} · ${loading ? 'Buscando…' : `${items.size} ${items.size === 1 ? 'resultado' : 'resultados'}${hasMore ? ' (hay más)' : ''}`}`;
        };
        const renderSelected = () => {
            selectedBox.replaceChildren();
            selected.forEach(item => {
                const chip = document.createElement('span'); chip.className = 'assignment-chip';
                const input = document.createElement('input'); input.type = 'hidden'; input.name = picker.dataset.field; input.value = item.id;
                const label = document.createElement('span'); label.textContent = item.label;
                const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = '×'; remove.setAttribute('aria-label', 'Retirar ' + item.label);
                remove.addEventListener('click', () => {
                    selected.delete(String(item.id));
                    results.querySelectorAll('input[type="checkbox"]').forEach(box => { if (box.value === String(item.id)) box.checked = false; });
                    renderSelected(); query.focus();
                });
                chip.append(input, label, remove); selectedBox.append(chip);
            });
            status();
        };
        const renderResults = () => {
            results.replaceChildren();
            items.forEach(item => {
                const label = document.createElement('label');
                const checkbox = document.createElement('input'); checkbox.type = 'checkbox'; checkbox.value = item.id; checkbox.checked = selected.has(String(item.id));
                const text = document.createElement('span');
                const name = document.createElement('strong'); name.textContent = item.label; text.append(name);
                if (item.detail) { const detail = document.createElement('small'); detail.textContent = item.detail; text.append(detail); }
                checkbox.addEventListener('change', () => {
                    if (checkbox.checked) selected.set(String(item.id), item); else selected.delete(String(item.id));
                    renderSelected();
                });
                label.append(checkbox, text); results.append(label);
            });
            if (!items.size) {
                const empty = document.createElement('p'); empty.className = 'assignment-empty';
                empty.textContent = 'No se encontraron coincidencias. Prueba con otro dato.';
                results.append(empty);
            }
        };
        const load = async (nextPage = 1) => {
            controller?.abort(); controller = new AbortController();
            const requestController = controller;
            const current = ++sequence;
            const timeout = setTimeout(() => requestController.abort(), 15000);
            loading = true; results.setAttribute('aria-busy', 'true'); more.disabled = true;
            if (nextPage === 1) { items.clear(); results.replaceChildren(); page = 0; hasMore = false; more.hidden = true; }
            status();
            const url = new URL(picker.dataset.url, location.origin);
            url.searchParams.set('kind', picker.dataset.kind); url.searchParams.set('q', query.value.trim()); url.searchParams.set('page', nextPage);
            try {
                const response = await fetch(url, {headers:{Accept:'application/json'}, signal:requestController.signal});
                const data = await response.json();
                if (!response.ok) throw new Error(response.status === 403 ? 'No tienes permiso para consultar las asignaciones de esta fase.' : 'No se pudo realizar la búsqueda. Reintenta o recarga la página.');
                if (current !== sequence) return;
                data.items.forEach(item => items.set(String(item.id), item));
                page = nextPage; hasMore = data.more;
                renderResults(); more.textContent = 'Mostrar más'; more.hidden = !hasMore;
            } catch (error) {
                if (current !== sequence) return;
                more.textContent = 'Reintentar búsqueda'; more.hidden = false;
                window.ArtemisAlerts.error(error.name === 'AbortError' ? 'La búsqueda tardó demasiado. Vuelve a intentarlo.' : error.message);
            } finally {
                clearTimeout(timeout);
                if (current === sequence) { loading = false; results.setAttribute('aria-busy','false'); more.disabled = false; status(); }
            }
        };
        const search = () => {
            clearTimeout(timer); ++sequence; controller?.abort();
            clear.hidden = !query.value;
            more.hidden = true;
            loading = true; status();
            timer = setTimeout(() => load(), 250);
        };
        query.addEventListener('input', search);
        query.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); clearTimeout(timer); load(); } });
        clear.addEventListener('click', () => { query.value = ''; search(); query.focus(); });
        more.addEventListener('click', () => load(page + 1));
        renderSelected(); load();
    });
})();
