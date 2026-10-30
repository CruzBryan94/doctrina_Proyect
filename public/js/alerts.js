(() => {
    if (window.ArtemisAlerts) return;
    const dialog = document.getElementById('artemis-alert');
    const title = dialog.querySelector('h2');
    const message = dialog.querySelector('p');
    const accept = dialog.querySelector('[data-alert-accept]');
    const cancel = dialog.querySelector('[data-alert-cancel]');
    const queue = [];
    let active = null;
    const next = () => {
        if (active || !queue.length) return;
        active = queue.shift();
        active.focus = document.activeElement;
        title.textContent = active.title;
        message.textContent = active.message;
        dialog.dataset.kind = active.kind;
        dialog.querySelector('.artemis-alert-mark').textContent = active.kind === 'info' ? 'i' : '!';
        cancel.hidden = active.kind !== 'confirm';
        accept.textContent = active.acceptLabel || 'Aceptar';
        dialog.showModal();
        (active.kind === 'confirm' ? cancel : accept).focus();
    };
    const finish = value => {
        if (!active) return;
        const previous = active;
        active = null;
        dialog.close();
        if (previous.focus?.isConnected) previous.focus.focus();
        previous.resolve(value);
        next();
    };
    const open = (text, options) => new Promise(resolve => {
        queue.push({message: String(text), ...options, resolve});
        next();
    });
    accept.addEventListener('click', () => finish(true));
    cancel.addEventListener('click', () => finish(false));
    dialog.addEventListener('cancel', event => { event.preventDefault(); finish(false); });
    window.ArtemisAlerts = {
        info: (text, title = 'Información') => open(text, {title, kind:'info'}),
        error: (text, title = 'No se pudo completar la acción') => open(text, {title, kind:'error'}),
        confirm: (text, title = 'Confirmar cambio de fase', acceptLabel = 'Confirmar cambio') => open(text, {title, kind:'confirm', acceptLabel})
    };
    document.addEventListener('DOMContentLoaded', () => {
        const messages = [...document.querySelectorAll('[data-alert-message]')];
        // Legacy AdminLTE screens use the same modal for their flash messages.
        document.querySelectorAll('.alert[role="alert"], .alert[role="status"]').forEach(box => {
            if (box.classList.contains('d-none') || !box.getClientRects().length) return;
            box.querySelectorAll('button').forEach(button => button.remove());
            if (!messages.length) window.ArtemisAlerts[box.classList.contains('alert-danger') ? 'error' : 'info'](box.textContent.trim());
            box.hidden = true;
        });
        if (messages.length) {
            const isError = messages.some(box => box.dataset.alertKind === 'error');
            window.ArtemisAlerts[isError ? 'error' : 'info'](messages.map(box => box.textContent.trim()).join('\n'));
        }
    });
    // Display form validation in the same accessible modal instead of browser bubbles.
    let invalidForm = null;
    document.addEventListener('invalid', event => {
        event.preventDefault();
        const form = event.target.form;
        if (!form || invalidForm) return;
        invalidForm = form;
        requestAnimationFrame(async () => {
            const fields = [...form.querySelectorAll(':invalid')];
            const errors = fields.map(field => {
                const label = field.labels?.[0]?.textContent.trim() || field.name || 'Campo';
                return `${label}: ${field.validationMessage}`;
            });
            await window.ArtemisAlerts.error(errors.join('\n'), 'Revisa los datos');
            fields[0]?.focus();
            invalidForm = null;
        });
    }, true);
})();
