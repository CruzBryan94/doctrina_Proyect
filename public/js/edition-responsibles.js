(() => {
    document.querySelectorAll('.responsible-query').forEach(input => {
        if (input.disabled) return;
        const cell = input.closest('.edition-cell');
        const select = cell.querySelector('.responsible-select');
        const status = cell.querySelector('.responsible-state');
        let timer, controller, version = 0;
        const search = async () => {
            controller?.abort(); controller = new AbortController();
            const request = controller, current = ++version;
            const timeout = setTimeout(() => request.abort(), 15000);
            status.textContent = 'Buscando usuarios…';
            try {
                const url = new URL(input.dataset.url); url.searchParams.set('q', input.value.trim());
                const response = await fetch(url, {headers:{Accept:'application/json'},signal:request.signal});
                if (!response.ok) throw new Error('No se pudieron consultar los responsables. Reintenta la búsqueda.');
                const data = await response.json();
                if (current !== version) return;
                const selected = select.value;
                const retained = select.selectedOptions[0]?.cloneNode(true);
                select.replaceChildren(new Option('Sin asignar', ''));
                if (selected && retained) select.append(retained);
                data.items.forEach(user => { if (String(user.id) !== selected) select.append(new Option(user.label, user.id)); });
                select.value = selected;
                status.textContent = data.items.length ? `${data.items.length} resultados. Selecciona el responsable; afina la búsqueda si no aparece.` : 'Sin coincidencias. La asignación actual se conserva.';
            } catch (error) {
                if (current !== version) return;
                status.textContent = 'No se pudo buscar. Escribe para reintentar.';
                window.ArtemisAlerts.error(error.name === 'AbortError' ? 'La búsqueda tardó demasiado. Reintenta.' : error.message);
            } finally { clearTimeout(timeout); }
        };
        input.addEventListener('input', () => { ++version; controller?.abort(); clearTimeout(timer); timer = setTimeout(search,250); });
        input.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); clearTimeout(timer); search(); } });
        search();
    });
})();
