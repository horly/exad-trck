(() => {
    document.querySelectorAll('[data-profile-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.parentElement.querySelector('input');
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button.querySelector('i').className = visible ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
        });
    });

    const passwordForm = document.querySelector('[data-profile-password-form]');
    const password = passwordForm?.querySelector('[data-profile-new-password]');
    const confirmation = passwordForm?.querySelector('[data-profile-password-confirmation]');
    const passwordRules = Object.fromEntries(
        [...(passwordForm?.querySelectorAll('[data-password-rule]') || [])]
            .map((rule) => [rule.dataset.passwordRule, rule]),
    );

    const setValidationState = (element, valid, dirty) => {
        element?.classList.toggle('is-valid', dirty && valid);
        element?.classList.toggle('is-invalid', dirty && !valid);
    };

    const updatePasswordRules = () => {
        if (!password || !confirmation) return;

        const value = password.value;
        const confirmationValue = confirmation.value;
        const passwordDirty = value.length > 0;
        const confirmationDirty = confirmationValue.length > 0;
        const states = {
            length: value.length >= 12,
            case: /[a-z]/.test(value) && /[A-Z]/.test(value),
            number: /[A-Za-z]/.test(value) && /\d/.test(value),
            symbol: /[^A-Za-z0-9]/.test(value),
        };
        const passwordValid = Object.values(states).every(Boolean);
        const confirmationValid = confirmationDirty && value === confirmationValue;

        Object.entries(states).forEach(([rule, valid]) => {
            setValidationState(passwordRules[rule], valid, passwordDirty);
        });
        setValidationState(passwordRules.match, confirmationValid, confirmationDirty);
        setValidationState(password, passwordValid, passwordDirty);
        setValidationState(confirmation, confirmationValid, confirmationDirty);
    };
    password?.addEventListener('input', updatePasswordRules);
    confirmation?.addEventListener('input', updatePasswordRules);

    const sourceInput = document.querySelector('[data-profile-photo-source]');
    const outputInput = document.querySelector('[data-profile-photo-output]');
    const cropImage = document.querySelector('[data-profile-crop-image]');
    const cropModalElement = document.getElementById('profileCropModal');
    const cropModal = cropModalElement ? bootstrap.Modal.getOrCreateInstance(cropModalElement) : null;
    let cropper = null;
    let objectUrl = null;

    document.querySelector('[data-choose-profile-photo]')?.addEventListener('click', () => sourceInput.click());
    sourceInput?.addEventListener('change', () => {
        const file = sourceInput.files?.[0];
        if (!file || !file.type.startsWith('image/')) return;
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);
        cropImage.src = objectUrl;
        cropModal.show();
    });

    cropModalElement?.addEventListener('shown.bs.modal', () => {
        cropper?.destroy();
        cropper = new Cropper(cropImage, {
            aspectRatio: 1,
            autoCropArea: 0.92,
            background: false,
            preview: '[data-profile-crop-preview]',
            responsive: true,
            viewMode: 1,
        });
    });
    cropModalElement?.addEventListener('hidden.bs.modal', () => {
        cropper?.destroy();
        cropper = null;
        sourceInput.value = '';
    });

    document.querySelectorAll('[data-crop-action]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!cropper) return;
            const actions = {
                'zoom-in': () => cropper.zoom(0.1),
                'zoom-out': () => cropper.zoom(-0.1),
                'rotate-left': () => cropper.rotate(-90),
                'rotate-right': () => cropper.rotate(90),
                reset: () => cropper.reset(),
            };
            actions[button.dataset.cropAction]?.();
        });
    });

    document.querySelector('[data-save-profile-crop]')?.addEventListener('click', () => {
        if (!cropper) return;
        const button = document.querySelector('[data-save-profile-crop]');
        button.disabled = true;
        cropper.getCroppedCanvas({ width: 512, height: 512, imageSmoothingEnabled: true, imageSmoothingQuality: 'high' })
            .toBlob((blob) => {
                if (!blob) { button.disabled = false; return; }
                const transfer = new DataTransfer();
                transfer.items.add(new File([blob], 'profile-photo.webp', { type: 'image/webp' }));
                outputInput.files = transfer.files;
                document.querySelector('[data-profile-photo-form]').requestSubmit();
            }, 'image/webp', 0.9);
    });

    const toast = document.querySelector('[data-app-toast]');
    toast?.querySelector('[data-app-toast-close]')?.addEventListener('click', () => toast.classList.add('is-hiding'));
    if (toast) setTimeout(() => toast.classList.add('is-hiding'), 5200);
})();
