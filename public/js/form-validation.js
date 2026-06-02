function exadFieldLabel(field) {
    const form = field.form;
    const label = form?.querySelector(`label[for="${CSS.escape(field.id)}"]`);

    return (label?.textContent || field.name || '')
        .replace('*', '')
        .trim()
        .toLowerCase();
}

function exadFeedbackAnchor(field) {
    return field.closest('.field-shell, .password-field') || field;
}

function exadClearFieldError(field) {
    field.classList.remove('is-invalid');
    field.closest('.field-shell')?.classList.remove('is-invalid');
    exadFeedbackAnchor(field).parentElement?.querySelectorAll(`[data-client-feedback-for="${field.name}"]`).forEach((feedback) => {
        feedback.remove();
    });
}

function exadShowFieldError(field, message) {
    exadClearFieldError(field);

    field.classList.add('is-invalid');
    field.closest('.field-shell')?.classList.add('is-invalid');

    const feedback = document.createElement('div');
    feedback.className = 'invalid-feedback d-block client-invalid-feedback';
    feedback.dataset.clientFeedbackFor = field.name;
    feedback.textContent = message;

    exadFeedbackAnchor(field).insertAdjacentElement('afterend', feedback);
}

function exadFieldError(field, form) {
    if (field.disabled || field.type === 'hidden' || field.type === 'button' || field.type === 'submit') {
        return '';
    }

    const label = exadFieldLabel(field);
    const requiredMessage = form.dataset.requiredMessage || 'Le champ :attribute est obligatoire.';
    const emailMessage = form.dataset.emailMessage || 'Le champ :attribute doit être une adresse email valide.';

    if (field.required) {
        const isEmptyCheckbox = field.type === 'checkbox' && !field.checked;
        const isEmptyValue = field.type !== 'checkbox' && String(field.value || '').trim() === '';

        if (isEmptyCheckbox || isEmptyValue) {
            return requiredMessage.replace(':attribute', label);
        }
    }

    if (field.type === 'email' && field.value.trim() !== '' && !field.validity.valid) {
        return emailMessage.replace(':attribute', label);
    }

    return '';
}

document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-validate-form]');

    if (!form) {
        return;
    }

    const fields = Array.from(form.querySelectorAll('input, select, textarea'));
    let firstInvalidField = null;

    fields.forEach((field) => {
        exadClearFieldError(field);

        const error = exadFieldError(field, form);
        if (!error) {
            return;
        }

        exadShowFieldError(field, error);
        firstInvalidField ??= field;
    });

    if (!firstInvalidField) {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
    firstInvalidField.focus({ preventScroll: true });
}, true);

document.addEventListener('input', (event) => {
    const field = event.target.closest('[data-validate-form] input, [data-validate-form] textarea');

    if (!field) {
        return;
    }

    exadClearFieldError(field);
});

document.addEventListener('change', (event) => {
    const field = event.target.closest('[data-validate-form] select, [data-validate-form] input[type="checkbox"]');

    if (!field) {
        return;
    }

    exadClearFieldError(field);
});
