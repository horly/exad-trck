document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!form.matches('[data-confirm-delete]') || form.dataset.confirmed === 'true') {
        return;
    }

    event.preventDefault();

    const title = form.dataset.confirmTitle || 'Supprimer cet element ?';
    const message = form.dataset.confirmMessage || 'Cette action est irreversible.';
    const cancelText = form.dataset.confirmCancel || 'Annuler';
    const confirmText = form.dataset.confirmSubmit || 'Oui, supprimer';
    const processingText = form.dataset.confirmProcessing || 'Traitement...';
    const overlay = document.createElement('div');

    overlay.className = 'sweet-confirm-overlay';
    overlay.innerHTML = `
        <div class="sweet-confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="delete-confirm-title" aria-describedby="delete-confirm-message">
            <div class="sweet-confirm-icon" aria-hidden="true">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h2 id="delete-confirm-title"></h2>
            <p id="delete-confirm-message"></p>
            <div class="sweet-confirm-actions">
                <button type="button" class="sweet-confirm-cancel"></button>
                <button type="button" class="sweet-confirm-submit"></button>
            </div>
        </div>
    `;

    overlay.querySelector('#delete-confirm-title').textContent = title;
    overlay.querySelector('#delete-confirm-message').textContent = message;
    overlay.querySelector('.sweet-confirm-cancel').textContent = cancelText;
    overlay.querySelector('.sweet-confirm-submit').textContent = confirmText;

    const close = () => overlay.remove();
    const confirm = () => {
        const submitButton = overlay.querySelector('.sweet-confirm-submit');
        const cancelButton = overlay.querySelector('.sweet-confirm-cancel');

        cancelButton.disabled = true;
        submitButton.disabled = true;
        submitButton.classList.add('is-loading');
        submitButton.innerHTML = `
            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
            <span></span>
        `;
        submitButton.querySelector('span:last-child').textContent = processingText;

        form.dataset.confirmed = 'true';
        form.submit();
    };

    overlay.addEventListener('click', (clickEvent) => {
        if (clickEvent.target === overlay) {
            close();
        }
    });
    overlay.querySelector('.sweet-confirm-cancel').addEventListener('click', close);
    overlay.querySelector('.sweet-confirm-submit').addEventListener('click', confirm);

    document.body.appendChild(overlay);
    overlay.querySelector('.sweet-confirm-cancel').focus();
});
