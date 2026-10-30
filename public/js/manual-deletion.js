(() => {
    document.addEventListener('submit', async event => {
        const form = event.target.closest('form[data-confirm-action]');
        if (!form || form.dataset.confirmed === 'true') return;

        event.preventDefault();
        const confirmed = await window.ArtemisAlerts.confirm(
            form.dataset.confirmMessage || '¿Confirmas esta acción?',
            form.action.includes('/restore') ? 'Recuperar manual' : 'Eliminar manual',
            form.action.includes('/restore') ? 'Recuperar' : 'Enviar a papelera'
        );
        if (confirmed) {
            form.dataset.confirmed = 'true';
            form.requestSubmit();
        }
    });
})();
