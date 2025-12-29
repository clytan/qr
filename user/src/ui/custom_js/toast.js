/**
 * Global Toast Notification Utility
 * Usage: showToast('Message', 'success') or showToast('Error message', 'error')
 * Types: 'success' (green), 'error' (red), 'info' (blue), 'warning' (orange)
 */

function showToast(message, type = 'success') {
    // Remove any existing toasts
    document.querySelectorAll('.toast-notification').forEach(toast => toast.remove());

    // Create new toast
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.textContent = message;

    // Color gradients based on type
    const gradients = {
        success: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
        error: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
        info: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
        warning: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'
    };

    // Apply styles directly
    Object.assign(toast.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '15px 20px',
        background: gradients[type] || gradients.success,
        color: 'white',
        borderRadius: '8px',
        boxShadow: '0 8px 25px rgba(0, 0, 0, 0.4)',
        zIndex: '2147483647',
        maxWidth: '350px',
        fontWeight: '500',
        opacity: '0',
        transform: 'translateX(400px)',
        transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
        border: '1px solid rgba(255, 255, 255, 0.2)',
        pointerEvents: 'none',
        display: 'block',
        visibility: 'visible',
        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
        fontSize: '14px',
        lineHeight: '1.5'
    });

    // Append to body
    document.body.appendChild(toast);

    // Force reflow and show
    toast.offsetHeight;
    toast.style.opacity = '1';
    toast.style.transform = 'translateX(0)';

    // Auto-hide after 4 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(400px)';
        setTimeout(() => {
            if (toast && toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 4000);
}

// Make it globally available
window.showToast = showToast;
