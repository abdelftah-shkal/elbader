/**
 * Generic Frontend Helpers & Toast Notifications
 */

export function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

export function debounce(func, wait = 350) {
    let timeout = null;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(this, args);
        }, wait);
    };
}

export function setButtonLoading(button, loading, loadingText = 'Loading...') {
    if (!button) return;

    if (loading) {
        button.dataset.originalText = button.textContent;
        button.disabled = true;
        button.textContent = loadingText;
        return;
    }

    button.disabled = false;
    button.textContent = button.dataset.originalText || button.textContent;
}

export function showToast(message, type = 'success') {
    const container = document.querySelector('#toast-container');

    const toast = document.createElement('div');
    const isSuccess = type === 'success';

    toast.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl border shadow-lg text-sm font-medium transition-all duration-300 transform translate-y-2 opacity-0 ${
        isSuccess
            ? 'bg-slate-900 text-white border-slate-800'
            : 'bg-rose-950 text-white border-rose-800'
    }`;

    toast.innerHTML = `
        <span class="${isSuccess ? 'text-emerald-400' : 'text-rose-400'} font-bold">
            ${isSuccess ? '✓' : '✕'}
        </span>
        <span class="flex-1">${escapeHtml(message)}</span>
    `;

    if (container) {
        container.appendChild(toast);
        requestAnimationFrame(() => {
            toast.classList.remove('translate-y-2', 'opacity-0');
        });

        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    } else {
        alert(message);
    }
}

export function showSuccess(message) {
    showToast(message, 'success');
}

export function showError(message) {
    showToast(message, 'error');
}

export function clearValidationErrors() {
    const errors = document.querySelectorAll('.validation-error');
    errors.forEach((element) => {
        element.textContent = '';
        element.classList.add('hidden');
    });

    const categoryName = document.querySelector('#category-name');
    const categoryParent = document.querySelector('#category-parent');

    categoryName?.classList.remove('border-rose-500', 'ring-2', 'ring-rose-500/20');
    categoryParent?.classList.remove('border-rose-500', 'ring-2', 'ring-rose-500/20');
}

export function showValidationError(field, message) {
    const errorElement = document.querySelector(`#${field}-error`);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.remove('hidden');
    }

    const inputElement = document.querySelector(`#category-${field}`);
    if (inputElement) {
        inputElement.classList.add('border-rose-500', 'ring-2', 'ring-rose-500/20');
    }
}

export function handleValidationErrors(error) {
    clearValidationErrors();

    if (error?.errors && typeof error.errors === 'object') {
        Object.entries(error.errors).forEach(([field, messages]) => {
            showValidationError(field, Array.isArray(messages) ? messages[0] : messages);
        });
        return;
    }

    showError(error?.message || 'Something went wrong.');
}
