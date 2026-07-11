document.addEventListener('DOMContentLoaded', () => {

    // ── Password visibility toggle ────────────────────────────────────────────
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput  = document.getElementById('password');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            if (type === 'password') {
                togglePassword.innerHTML = '<svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>';
                togglePassword.title = 'Show password';
            } else {
                togglePassword.innerHTML = '<svg id="eyeOffIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>';
                togglePassword.title = 'Hide password';
            }
        });
    }
    
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) submitBtn.disabled = false;

    // ── File size guard on submit (certificate is optional) ──────────────────
    const form      = document.getElementById('registrationForm');
    const fileInput = document.getElementById('certificate');

    if (form && fileInput) {
        form.addEventListener('submit', (e) => {
            const file = fileInput.files[0];
            if (file && file.size > 5 * 1024 * 1024) {
                e.preventDefault();
                alert('File is too large. Please upload a file smaller than 5 MB.');
            }
        });
    }

});
