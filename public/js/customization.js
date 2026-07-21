(() => {
    const form = document.querySelector('[data-customization-form]');
    if (!form) return;

    const colorInputs = [...form.querySelectorAll('[data-theme-color]')];
    const applyColor = (input) => {
        const value = input.value.toUpperCase();
        document.documentElement.style.setProperty(input.dataset.themeColor, value);
        input.closest('.customization-color-control')?.querySelector('[data-color-value]')?.replaceChildren(value);
    };

    colorInputs.forEach((input) => {
        input.addEventListener('input', () => applyColor(input));
        applyColor(input);
    });

    form.querySelector('[data-reset-colors]')?.addEventListener('click', () => {
        colorInputs.forEach((input) => {
            input.value = input.dataset.defaultColor;
            applyColor(input);
        });
    });

    form.querySelectorAll('[data-image-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            const preview = form.querySelector(input.dataset.previewTarget);
            if (!file || !preview) return;

            const objectUrl = URL.createObjectURL(file);
            preview.addEventListener('load', () => URL.revokeObjectURL(objectUrl), { once: true });
            preview.src = objectUrl;
        });
    });

    const toast = document.querySelector('[data-app-toast]');
    toast?.querySelector('[data-app-toast-close]')?.addEventListener('click', () => toast.classList.add('is-hiding'));
    if (toast) setTimeout(() => toast.classList.add('is-hiding'), 5200);
})();
