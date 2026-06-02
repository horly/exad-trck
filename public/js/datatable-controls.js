let datatableSearchTimeout;

const datatableContainer = document.querySelector('[data-datatable-container]');

async function loadDatatable(url) {
    if (!datatableContainer) {
        return;
    }

    const activeSearch = document.activeElement?.matches('[data-datatable-search]')
        ? {
            value: document.activeElement.value,
            start: document.activeElement.selectionStart,
            end: document.activeElement.selectionEnd,
        }
        : null;

    datatableContainer.classList.add('is-loading');

    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        window.location.href = url;
        return;
    }

    const payload = await response.json();
    datatableContainer.innerHTML = payload.html;
    window.exadLoginHistories = payload.loginHistories || {};
    if (payload.stats) {
        document.dispatchEvent(new CustomEvent('exad:datatable-stats', { detail: payload.stats }));
    }
    datatableContainer.classList.remove('is-loading');

    if (activeSearch) {
        const nextSearch = datatableContainer.querySelector('[data-datatable-search]');

        if (nextSearch) {
            nextSearch.focus({ preventScroll: true });
            nextSearch.setSelectionRange(activeSearch.start ?? activeSearch.value.length, activeSearch.end ?? activeSearch.value.length);
        }
    }

}

document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-datatable-search-form]');

    if (!form) {
        return;
    }

    event.preventDefault();

    const params = new URLSearchParams(new FormData(form));
    const url = `${form.action}?${params.toString()}`;

    loadDatatable(url);
});

document.addEventListener('input', (event) => {
    const input = event.target.closest('[data-datatable-search]');

    if (!input) {
        return;
    }

    const form = input.closest('[data-datatable-search-form]');

    window.clearTimeout(datatableSearchTimeout);
    datatableSearchTimeout = window.setTimeout(() => {
        form?.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    }, 350);
});

document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-datatable-sort], [data-datatable-pagination] a');

    if (!link) {
        return;
    }

    event.preventDefault();
    loadDatatable(link.href);
});
