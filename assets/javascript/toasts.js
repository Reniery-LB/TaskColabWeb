// assets/javascript/toasts.js
document.addEventListener('DOMContentLoaded', () => {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  function createToast({ type = 'info', message = '', timeout = 5000 } = {}) {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;

    container.appendChild(toast);

    const t = setTimeout(() => closeToast(toast), timeout);
    toast.addEventListener('mouseenter', () => clearTimeout(t));
    toast.addEventListener('click', () => closeToast(toast));
    return toast;
  }

  function closeToast(toast) {
    if (!toast) return;
    toast.classList.add('closing');
    toast.addEventListener('animationend', () => {
      if (toast && toast.parentNode) toast.parentNode.removeChild(toast);
    });
  }

  const params = new URLSearchParams(window.location.search);
  if (params.has('errors')) {
    const raw = params.get('errors') || '';
    const parts = decodeURIComponent(raw).split('||').filter(Boolean);
    parts.forEach(msg => createToast({ type: 'error', message: msg, timeout: 6000 }));
    removeQueryParam('errors');
  }

  if (params.has('success')) {
    const s = params.get('success');
    const text = s === '1' || s === 'registered' ? 'Registro exitoso. Inicia sesión.' : decodeURIComponent(s);
    createToast({ type: 'success', message: text, timeout: 5000 });
    removeQueryParam('success');
  }

  if (params.has('error')) {
    createToast({ type: 'error', message: decodeURIComponent(params.get('error')), timeout: 6000 });
    removeQueryParam('error');
  }

  function removeQueryParam(key) {
    const url = new URL(window.location);
    url.searchParams.delete(key);
    window.history.replaceState({}, document.title, url.toString());
  }
});
