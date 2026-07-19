const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
const sidebarToggleIcon = document.querySelector('[data-sidebar-toggle-icon]');
const sidebarState = localStorage.getItem('exad-sidebar');
const compactSidebarQuery = window.matchMedia('(max-width: 1366px)');

function applySidebarState(state) {
    const collapsed = state === 'collapsed';

    document.body.classList.toggle('sidebar-collapsed', collapsed);

    if (sidebarToggleIcon) {
        sidebarToggleIcon.className = collapsed ? 'fa-solid fa-chevron-right' : 'fa-solid fa-chevron-left';
    }

    if (sidebarToggle) {
        sidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    }

    localStorage.setItem('exad-sidebar', collapsed ? 'collapsed' : 'expanded');
}

function getInitialSidebarState() {
    if (compactSidebarQuery.matches) {
        return 'collapsed';
    }

    return sidebarState || 'expanded';
}

applySidebarState(getInitialSidebarState());

sidebarToggle?.addEventListener('click', () => {
    applySidebarState(document.body.classList.contains('sidebar-collapsed') ? 'expanded' : 'collapsed');
});

compactSidebarQuery.addEventListener?.('change', (event) => {
    if (event.matches) {
        applySidebarState('collapsed');
    }
});

document.querySelectorAll('[data-sidebar-menu]').forEach((menu) => {
    const toggle = menu.querySelector('[data-sidebar-menu-toggle]');

        toggle?.addEventListener('click', () => {
            const wasCollapsed = document.body.classList.contains('sidebar-collapsed');

            if (wasCollapsed) {
                applySidebarState('expanded');
                menu.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');

                return;
            }

            const isOpen = menu.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
});
