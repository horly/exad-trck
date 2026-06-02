document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!form.matches('[data-loading-form]')) {
        return;
    }

    if (form.dataset.loading === 'true') {
        event.preventDefault();
        return;
    }

    if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
        return;
    }

    const button = form.querySelector('[data-loading-button], button[type="submit"], input[type="submit"]');

    if (!button) {
        return;
    }

    form.dataset.loading = 'true';
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');

    if (button.tagName === 'INPUT') {
        button.value = button.dataset.loadingText || form.dataset.loadingText || 'Traitement...';
        return;
    }

    const loadingText = button.dataset.loadingText || form.dataset.loadingText || 'Traitement...';
    const spinner = document.createElement('span');
    const label = document.createElement('span');

    spinner.className = 'spinner-border spinner-border-sm';
    spinner.setAttribute('aria-hidden', 'true');
    label.textContent = loadingText;

    button.classList.add('is-loading');
    button.replaceChildren(spinner, label);
});
