(() => {
    const form = document.querySelector('[data-driver-form]');
    const root = form?.querySelector('[data-driver-address-search]');
    const input = root?.querySelector('[data-driver-address-input]');
    const latitude = root?.querySelector('[data-driver-address-latitude]');
    const longitude = root?.querySelector('[data-driver-address-longitude]');
    const suggestions = root?.querySelector('[data-driver-address-suggestions]');
    const spinner = root?.querySelector('[data-driver-address-spinner]');

    if (!form || !root || !input || !latitude || !longitude || !suggestions || !spinner) {
        return;
    }

    let debounceTimer;
    let requestController;

    const hideSuggestions = () => {
        suggestions.hidden = true;
        suggestions.innerHTML = '';
    };

    const showMessage = (message) => {
        suggestions.innerHTML = '';
        const element = document.createElement('div');
        element.className = 'driver-address-message';
        element.textContent = message;
        suggestions.appendChild(element);
        suggestions.hidden = false;
    };

    const selectAddress = (result) => {
        input.value = result.address;
        latitude.value = result.latitude;
        longitude.value = result.longitude;
        hideSuggestions();
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const renderResults = (results) => {
        suggestions.innerHTML = '';

        if (!results.length) {
            showMessage(root.dataset.emptyText);
            return;
        }

        results.forEach((result) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'driver-address-suggestion';
            button.setAttribute('role', 'option');
            button.innerHTML = '<i class="fa-solid fa-location-dot" aria-hidden="true"></i><span></span>';
            button.querySelector('span').textContent = result.address;
            button.addEventListener('click', () => selectAddress(result));
            suggestions.appendChild(button);
        });

        suggestions.hidden = false;
    };

    const search = async () => {
        const query = input.value.trim();

        if (query.length < 3) {
            hideSuggestions();
            return;
        }

        requestController?.abort();
        requestController = new AbortController();
        spinner.hidden = false;

        try {
            const url = new URL(form.dataset.addressSearchUrl, window.location.origin);
            url.searchParams.set('query', query);
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: requestController.signal,
            });

            if (!response.ok) {
                throw new Error('Address search failed');
            }

            const payload = await response.json();
            renderResults(Array.isArray(payload.results) ? payload.results : []);
        } catch (error) {
            if (error.name !== 'AbortError') {
                showMessage(root.dataset.errorText);
            }
        } finally {
            spinner.hidden = true;
        }
    };

    input.addEventListener('input', () => {
        latitude.value = '';
        longitude.value = '';
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(search, 350);
    });

    input.addEventListener('focus', () => {
        if (suggestions.childElementCount > 0) {
            suggestions.hidden = false;
        }
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) {
            hideSuggestions();
        }
    });

    form.addEventListener('reset', hideSuggestions);
})();
