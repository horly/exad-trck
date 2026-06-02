const themeToggle = document.querySelector('[data-theme-toggle]');
const themeIcon = document.querySelector('[data-theme-icon]');
const fullscreenToggle = document.querySelector('[data-fullscreen-toggle]');
const fullscreenIcon = document.querySelector('[data-fullscreen-icon]');
const storedTheme = localStorage.getItem('exad-theme');

function applyTheme(theme) {
    document.body.classList.toggle('dashboard-dark', theme === 'dark');

    if (themeIcon) {
        themeIcon.className = theme === 'dark' ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
    }

    localStorage.setItem('exad-theme', theme);
}

applyTheme(storedTheme === 'dark' ? 'dark' : 'light');

themeToggle?.addEventListener('click', () => {
    applyTheme(document.body.classList.contains('dashboard-dark') ? 'light' : 'dark');
});

function syncFullscreenIcon() {
    if (!fullscreenIcon) {
        return;
    }

    fullscreenIcon.className = document.fullscreenElement
        ? 'fa-solid fa-compress'
        : 'fa-solid fa-expand';
}

fullscreenToggle?.addEventListener('click', async () => {
    if (document.fullscreenElement) {
        await document.exitFullscreen();
    } else {
        await document.documentElement.requestFullscreen();
    }

    syncFullscreenIcon();
});

document.addEventListener('fullscreenchange', syncFullscreenIcon);
