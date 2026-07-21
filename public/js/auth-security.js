(() => {
    document.querySelector('[data-auth-password-toggle]')?.addEventListener('click', (event) => {
        const button = event.currentTarget;
        const input = button.parentElement.querySelector('input');
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        button.querySelector('i').className = visible ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
    });

    document.querySelector('[data-auth-recovery-toggle]')?.addEventListener('click', (event) => {
        const button = event.currentTarget;
        const codePanel = document.querySelector('[data-auth-code-panel]');
        const recoveryPanel = document.querySelector('[data-auth-recovery-panel]');
        const showingRecovery = !recoveryPanel.hidden;
        recoveryPanel.hidden = showingRecovery;
        codePanel.hidden = !showingRecovery;
        button.textContent = showingRecovery ? button.dataset.recoveryLabel : button.dataset.codeLabel;
        (showingRecovery ? codePanel : recoveryPanel).querySelector('input').focus();
    });
})();
