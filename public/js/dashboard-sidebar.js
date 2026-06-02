const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
const sidebarToggleIcon = document.querySelector('[data-sidebar-toggle-icon]');
const sidebarState = localStorage.getItem('exad-sidebar');

function applySidebarState(state) {
    const collapsed = state === 'collapsed';

    document.body.classList.toggle('sidebar-collapsed', collapsed);

    if (sidebarToggleIcon) {
        sidebarToggleIcon.className = collapsed ? 'fa-solid fa-chevron-right' : 'fa-solid fa-chevron-left';
    }

    localStorage.setItem('exad-sidebar', collapsed ? 'collapsed' : 'expanded');
}

applySidebarState(sidebarState === 'collapsed' ? 'collapsed' : 'expanded');

sidebarToggle?.addEventListener('click', () => {
    applySidebarState(document.body.classList.contains('sidebar-collapsed') ? 'expanded' : 'collapsed');
});
