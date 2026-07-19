(() => {
    document.querySelectorAll('select[data-searchable-database]:not([data-searchable-select-native])').forEach((select) => {
        const root = document.createElement('div');
        const toggle = document.createElement('button');
        const label = document.createElement('span');
        const chevron = document.createElement('i');
        const panel = document.createElement('div');
        const searchLabel = document.createElement('label');
        const searchIcon = document.createElement('i');
        const search = document.createElement('input');
        const options = document.createElement('div');
        const empty = document.createElement('p');

        root.className = `searchable-select${select.classList.contains('is-invalid') ? ' is-invalid' : ''}`;
        root.dataset.searchableSelect = '';
        root.dataset.noResults = select.dataset.noResults || '';
        select.parentNode.insertBefore(root, select);
        root.append(select);
        select.dataset.searchableSelectNative = '';
        select.classList.add('searchable-select-native');

        toggle.type = 'button';
        toggle.className = 'searchable-select-toggle';
        toggle.disabled = select.disabled;
        toggle.dataset.searchableSelectToggle = '';
        toggle.setAttribute('aria-expanded', 'false');
        label.dataset.searchableSelectLabel = '';
        chevron.className = 'fa-solid fa-chevron-down';
        toggle.append(label, chevron);

        panel.className = 'searchable-select-panel';
        panel.dataset.searchableSelectPanel = '';
        panel.hidden = true;
        searchLabel.className = 'searchable-select-search';
        searchIcon.className = 'fa-solid fa-magnifying-glass';
        search.type = 'search';
        search.placeholder = select.dataset.searchPlaceholder || '';
        search.dataset.searchableSelectSearch = '';
        searchLabel.append(searchIcon, search);
        options.className = 'searchable-select-options';
        options.dataset.searchableSelectOptions = '';
        empty.className = 'searchable-select-empty';
        empty.dataset.searchableSelectEmpty = '';
        empty.hidden = true;
        empty.textContent = select.dataset.noResults || '';
        panel.append(searchLabel, options, empty);
        root.append(toggle, panel);
    });

    const normalize = (value) => String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase()
        .trim();

    document.querySelectorAll('[data-searchable-select]').forEach((root) => {
        const select = root.querySelector('[data-searchable-select-native]');
        const toggle = root.querySelector('[data-searchable-select-toggle]');
        const label = root.querySelector('[data-searchable-select-label]');
        const panel = root.querySelector('[data-searchable-select-panel]');
        const search = root.querySelector('[data-searchable-select-search]');
        const options = root.querySelector('[data-searchable-select-options]');
        const empty = root.querySelector('[data-searchable-select-empty]');

        if (!select || !toggle || !panel || !search || !options) return;

        const availableOptions = () => [...select.options].filter((option) => option.value && !option.disabled && !option.hidden);

        const updateLabel = () => {
            const selected = select.selectedOptions[0];
            label.textContent = selected?.value ? selected.textContent.trim() : select.options[0]?.textContent.trim();
            toggle.classList.toggle('has-value', Boolean(selected?.value));
        };

        const render = () => {
            const query = normalize(search.value);
            const matches = availableOptions().filter((option) => normalize(`${option.textContent} ${option.dataset.search || ''}`).includes(query));
            options.replaceChildren();

            matches.forEach((option) => {
                const button = document.createElement('button');
                const identity = document.createElement('span');
                const name = document.createElement('strong');
                const detail = document.createElement('small');
                const parts = option.textContent.split(/\s*(?:\u00b7|\u00c2\u00b7)\s*/).map((part) => part.trim());

                button.type = 'button';
                button.className = 'searchable-select-option';
                button.classList.toggle('is-selected', option.value === select.value);
                button.innerHTML = `<span class="searchable-select-option-icon"><i class="fa-solid ${select.dataset.optionIcon || 'fa-list'}"></i></span>`;
                name.textContent = parts[0] || option.textContent.trim();
                detail.textContent = parts.slice(1).join(' \u00b7 ');
                identity.append(name, detail);
                button.append(identity);
                if (option.value === select.value) {
                    const check = document.createElement('i');
                    check.className = 'fa-solid fa-check searchable-select-option-check';
                    button.append(check);
                }
                button.addEventListener('click', () => {
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    close();
                    toggle.focus();
                });
                options.append(button);
            });

            empty.hidden = matches.length !== 0;
        };

        const open = () => {
            document.querySelectorAll('[data-searchable-select].is-open').forEach((other) => {
                if (other !== root) other.dispatchEvent(new CustomEvent('searchable-select:close'));
            });
            root.classList.add('is-open');
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            search.value = '';
            render();
            search.focus();
        };

        const close = () => {
            root.classList.remove('is-open');
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        };

        toggle.addEventListener('click', () => panel.hidden ? open() : close());
        search.addEventListener('input', render);
        select.addEventListener('change', () => {
            updateLabel();
            root.classList.toggle('is-invalid', select.required && !select.value);
        });
        select.addEventListener('searchable-select:refresh', () => {
            toggle.disabled = select.disabled;
            updateLabel();
            if (!panel.hidden) render();
        });
        select.addEventListener('invalid', (event) => {
            event.preventDefault();
            root.classList.add('is-invalid');
            open();
        });
        root.addEventListener('searchable-select:close', close);
        root.closest('form')?.addEventListener('reset', () => setTimeout(() => {
            updateLabel();
            root.classList.remove('is-invalid');
        }));
        document.addEventListener('click', (event) => {
            if (!root.contains(event.target)) close();
        });
        root.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close();
                toggle.focus();
            }
        });
        if (select.id) {
            document.querySelector(`label[for="${CSS.escape(select.id)}"]`)?.addEventListener('click', (event) => {
                event.preventDefault();
                open();
            });
        }

        updateLabel();
    });
})();
