(() => {
    const form = document.querySelector('[data-garage-form]');
    if (!form) return;
    const modal = document.getElementById('garageModal');
    const field = (name) => form.querySelector(`[data-field="${name}"]`);
    const title = form.querySelector('[data-garage-title]');
    const submit = form.querySelector('[data-garage-submit]');
    const storeAction = form.action;
    const clearValidationErrors = () => {
        form.querySelectorAll('.is-invalid').forEach((element) => element.classList.remove('is-invalid'));
        form.querySelectorAll('[data-field-error]').forEach((element) => element.remove());
        form.removeAttribute('data-garage-validation-errors');
    };
    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-garage-create]')) {
            clearValidationErrors();
            form.reset(); form.action = storeAction; field('method').value = 'POST';
            title.textContent = title.dataset.createLabel; submit.textContent = submit.dataset.createLabel;
            return;
        }
        const button = event.target.closest('[data-garage-edit]');
        if (!button) return;
        clearValidationErrors();
        const garage = JSON.parse(button.dataset.garage);
        form.reset(); form.action = button.dataset.action; field('method').value = 'PUT';
        Object.entries(garage).forEach(([key, value]) => { const input = field(key); if (input) input.value = Array.isArray(value) ? value.join(', ') : (value ?? ''); });
        title.textContent = garage.name; submit.textContent = submit.dataset.saveLabel;
    });

    const input = form.querySelector('[data-garage-address]');
    const results = form.querySelector('[data-address-results]');
    let timer;
    input.addEventListener('input', () => {
        field('latitude').value = ''; field('longitude').value = ''; clearTimeout(timer);
        if (input.value.trim().length < 3) { results.hidden = true; return; }
        timer = setTimeout(async () => {
            try {
            const response = await fetch(`${form.dataset.addressSearchUrl}?query=${encodeURIComponent(input.value.trim())}`, {headers: {'Accept': 'application/json'}});
            if (!response.ok) return;
            const data = await response.json(); results.replaceChildren();
            data.results.forEach((item) => {
                const button = document.createElement('button'); button.type = 'button'; button.textContent = item.address;
                button.addEventListener('click', () => { input.value = item.address; field('latitude').value = item.latitude; field('longitude').value = item.longitude; results.hidden = true; });
                results.append(button);
            });
            results.hidden = data.results.length === 0;
            } catch (_) { results.hidden = true; }
        }, 350);
    });
    document.addEventListener('click', (event) => { if (!event.target.closest('.maintenance-address-field')) results.hidden = true; });
    if (form.hasAttribute('data-garage-validation-errors')) bootstrap.Modal.getOrCreateInstance(modal).show();
    const toast = document.querySelector('[data-app-toast]');
    toast?.querySelector('[data-app-toast-close]')?.addEventListener('click', () => toast.classList.add('is-hiding'));
    if (toast) setTimeout(() => toast.classList.add('is-hiding'), 5200);
})();
