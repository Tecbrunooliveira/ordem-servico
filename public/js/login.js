document.addEventListener('DOMContentLoaded', () => {
    const passwordInput = document.getElementById('password');
    const toggleButton = document.getElementById('toggle-password');

    if (! passwordInput || ! toggleButton) {
        return;
    }

    const eyeOpen = toggleButton.querySelector('[data-eye-open]');
    const eyeClosed = toggleButton.querySelector('[data-eye-closed]');

    toggleButton.addEventListener('click', () => {
        const showPassword = passwordInput.type === 'password';

        passwordInput.type = showPassword ? 'text' : 'password';
        eyeOpen?.classList.toggle('hidden', ! showPassword);
        eyeClosed?.classList.toggle('hidden', showPassword);
    });
});
