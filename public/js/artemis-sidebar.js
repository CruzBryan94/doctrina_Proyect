(() => {
    const sidebar = document.getElementById('app-sidebar');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');

    if (!sidebar || !toggle || !backdrop) return;

    const body = document.body;
    const mobileViewport = window.matchMedia('(max-width: 700px)');
    const collapsedKey = 'artemis.sidebar.collapsed';
    let mobileOpen = false;

    const readCollapsed = () => {
        try {
            return window.sessionStorage.getItem(collapsedKey) === 'true';
        } catch (_) {
            return false;
        }
    };

    const saveCollapsed = (collapsed) => {
        try {
            window.sessionStorage.setItem(collapsedKey, String(collapsed));
        } catch (_) {
            // The sidebar remains usable when browser storage is unavailable.
        }
    };

    const setMobileOpen = (open, returnFocus = false) => {
        mobileOpen = open;
        body.classList.toggle('app-sidebar-open', open);
        sidebar.classList.toggle('app-sidebar-mobile-open', open);
        sidebar.inert = !open;
        sidebar.setAttribute('aria-hidden', String(!open));
        backdrop.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
        toggle.title = open ? 'Cerrar menú' : 'Abrir menú';

        if (open) {
            sidebar.querySelector('nav a')?.focus();
        } else if (returnFocus) {
            toggle.focus();
        }
    };

    const syncViewport = () => {
        body.classList.add('artemis-sidebar-ready');

        if (mobileViewport.matches) {
            body.classList.remove('app-sidebar-collapsed');
            setMobileOpen(false);
            return;
        }

        mobileOpen = false;
        body.classList.remove('app-sidebar-open');
        sidebar.classList.remove('app-sidebar-mobile-open');
        backdrop.hidden = true;
        sidebar.inert = false;
        sidebar.setAttribute('aria-hidden', 'false');

        const collapsed = readCollapsed();
        body.classList.toggle('app-sidebar-collapsed', collapsed);
        toggle.setAttribute('aria-expanded', String(!collapsed));
        toggle.setAttribute('aria-label', collapsed ? 'Expandir menú' : 'Contraer menú');
        toggle.title = collapsed ? 'Expandir menú' : 'Contraer menú';
    };

    toggle.addEventListener('click', () => {
        if (mobileViewport.matches) {
            setMobileOpen(!mobileOpen, mobileOpen);
            return;
        }

        const collapsed = !body.classList.contains('app-sidebar-collapsed');
        body.classList.toggle('app-sidebar-collapsed', collapsed);
        toggle.setAttribute('aria-expanded', String(!collapsed));
        toggle.setAttribute('aria-label', collapsed ? 'Expandir menú' : 'Contraer menú');
        toggle.title = collapsed ? 'Expandir menú' : 'Contraer menú';
        saveCollapsed(collapsed);
    });

    backdrop.addEventListener('click', () => setMobileOpen(false, true));
    sidebar.querySelectorAll('nav a').forEach((link) => {
        link.addEventListener('click', () => {
            if (mobileViewport.matches) setMobileOpen(false);
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobileViewport.matches && mobileOpen) {
            setMobileOpen(false, true);
        }
    });

    mobileViewport.addEventListener('change', syncViewport);
    syncViewport();
})();
