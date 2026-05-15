// assets/javascript/messages.js
(function () {
    const params = new URLSearchParams(window.location.search);
    const container = document.getElementById('messageContainer') || document.getElementById('loginMessage');
    if (!container) return;

    const errors = params.get('errors');
    const success = params.get('success');

    if (errors) {
        const arr = decodeURIComponent(errors).split('||').filter(Boolean);
        container.innerHTML = arr.map(e => `<div class="form-error" style="color:#EF4444">${e}</div>`).join('');
    } else if (success) {
        container.innerHTML = `<div class="form-success" style="color:#16A34A">Registro exitoso. Ahora puedes iniciar sesión.</div>`;
    } else if (params.get('error')) {
        container.innerHTML = `<div class="form-error" style="color:#EF4444">${decodeURIComponent(params.get('error'))}</div>`;
    }
})();
