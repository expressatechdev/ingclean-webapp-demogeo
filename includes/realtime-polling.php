<?php
/**
 * INGClean - Sistema de Polling en Tiempo Real
 * 
 * Incluir en páginas donde se necesite actualización automática de estado.
 * 
 * Variables requeridas antes de incluir:
 * - $polling_order_id: ID de la orden a monitorear
 * - $polling_interval: (opcional) Intervalo en ms, default 5000
 * - $polling_redirect_on_status: (opcional) Array de status => url para redireccionar
 */

$polling_order_id = $polling_order_id ?? null;
$polling_interval = $polling_interval ?? 5000; // 5 segundos por defecto
$polling_redirect_on_status = $polling_redirect_on_status ?? [];

if (!$polling_order_id) {
    return; // No hay orden que monitorear
}
?>

<!-- Toast Notifications CSS -->
<style>
/* Toast Container */
.toast-container {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-width: 90%;
    width: 400px;
    pointer-events: none;
}

/* Individual Toast */
.toast {
    background: white;
    border-radius: 16px;
    padding: 16px 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: flex-start;
    gap: 14px;
    animation: toastSlideIn 0.4s cubic-bezier(0.21, 1.02, 0.73, 1);
    pointer-events: auto;
    border-left: 4px solid #00b4d8;
}

.toast.toast-success {
    border-left-color: #22c55e;
}

.toast.toast-warning {
    border-left-color: #f59e0b;
}

.toast.toast-error {
    border-left-color: #ef4444;
}

.toast.toast-info {
    border-left-color: #00b4d8;
}

.toast.toast-hide {
    animation: toastSlideOut 0.3s ease-in forwards;
}

@keyframes toastSlideIn {
    from {
        transform: translateY(-100px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes toastSlideOut {
    to {
        transform: translateY(-100px);
        opacity: 0;
    }
}

.toast-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.toast-content {
    flex: 1;
}

.toast-title {
    font-weight: 600;
    color: #1e3a5f;
    font-size: 1rem;
    margin-bottom: 4px;
}

.toast-message {
    color: #64748b;
    font-size: 0.9rem;
    line-height: 1.4;
}

.toast-close {
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 1.2rem;
    padding: 0;
    line-height: 1;
}

.toast-close:hover {
    color: #64748b;
}

/* Toast with action button */
.toast-action {
    margin-top: 10px;
}

.toast-action-btn {
    background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
}

/* Status change notification - more prominent */
.toast.status-change {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border-left-width: 5px;
}

.toast.status-change .toast-title {
    color: #0077b6;
}

/* Vibrate animation for important notifications */
.toast.vibrate {
    animation: toastSlideIn 0.4s cubic-bezier(0.21, 1.02, 0.73, 1), 
               vibrate 0.3s ease-in-out 0.4s;
}

@keyframes vibrate {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-5px); }
    40% { transform: translateX(5px); }
    60% { transform: translateX(-5px); }
    80% { transform: translateX(5px); }
}

/* Sound notification indicator */
.toast-sound-icon {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}
</style>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Notification Sound -->
<audio id="notificationSound" preload="auto">
    <source src="data:audio/mp3;base64,SUQzBAAAAAAAI1RTU0UAAAAPAAADTGF2ZjU4Ljc2LjEwMAAAAAAAAAAAAAAA//tQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWGluZwAAAA8AAAACAAABhgC7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7//////////////////////////////////////////////////////////////////8AAAAATGF2YzU4LjEzAAAAAAAAAAAAAAAAJAAAAAAAAAAAAYYNbRsAAAAAAAD/+9DEAAAIAANIAAAAQoAANIAAAARMQU1FMy4xMDBVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVQ==" type="audio/mp3">
</audio>

<script>
// Polling System
(function() {
    const orderId = <?= json_encode($polling_order_id) ?>;
    const pollingInterval = <?= (int)$polling_interval ?>;
    const redirectOnStatus = <?= json_encode($polling_redirect_on_status) ?>;
    
    let currentStatus = null;
    let pollingTimer = null;
    let isPolling = false;
    
    // Initialize current status from the page if available
    const statusEl = document.querySelector('[data-order-status]');
    if (statusEl) {
        currentStatus = statusEl.dataset.orderStatus;
    }
    
    // Toast functions
    function showToast(options) {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        
        const type = options.type || 'info';
        const isStatusChange = options.statusChange || false;
        
        toast.className = `toast toast-${type}${isStatusChange ? ' status-change vibrate' : ''}`;
        
        toast.innerHTML = `
            <div class="toast-icon">${options.icon || '🔔'}</div>
            <div class="toast-content">
                <div class="toast-title">${options.title}</div>
                <div class="toast-message">${options.message}</div>
                ${options.action ? `
                    <div class="toast-action">
                        <button class="toast-action-btn" onclick="${options.action.onclick}">${options.action.text}</button>
                    </div>
                ` : ''}
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;
        
        container.appendChild(toast);
        
        // Play sound for status changes
        if (isStatusChange && options.playSound !== false) {
            playNotificationSound();
        }
        
        // Auto remove after duration
        const duration = options.duration || (isStatusChange ? 8000 : 5000);
        setTimeout(() => {
            toast.classList.add('toast-hide');
            setTimeout(() => toast.remove(), 300);
        }, duration);
        
        return toast;
    }
    
    // Play notification sound
    function playNotificationSound() {
        try {
            const sound = document.getElementById('notificationSound');
            sound.currentTime = 0;
            sound.play().catch(() => {});
        } catch (e) {}
    }
    
    // Check order status
    async function checkOrderStatus() {
        if (isPolling) return;
        isPolling = true;
        
        try {
            const url = `../api/orders/check-status.php?order_id=${orderId}&current_status=${currentStatus || ''}`;
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success && data.data) {
                const orderData = data.data;
                
                // Status changed!
                if (orderData.status_changed && orderData.notification) {
                    const notif = orderData.notification;
                    
                    showToast({
                        title: notif.title,
                        message: notif.message,
                        icon: notif.icon,
                        type: getToastType(orderData.status),
                        statusChange: true,
                        playSound: true
                    });
                    
                    // Update current status
                    currentStatus = orderData.status;
                    
                    // Update page status indicator if exists
                    if (statusEl) {
                        statusEl.dataset.orderStatus = orderData.status;
                    }
                    
                    // Trigger custom event for page-specific handling
                    window.dispatchEvent(new CustomEvent('orderStatusChanged', {
                        detail: orderData
                    }));
                    
                    // Check if we need to redirect
                    if (redirectOnStatus[orderData.status]) {
                        setTimeout(() => {
                            window.location.href = redirectOnStatus[orderData.status];
                        }, 2000);
                    }
                    
                    // Reload page for significant status changes
                    const reloadStatuses = ['accepted', 'paid', 'completed', 'cancelled'];
                    if (reloadStatuses.includes(orderData.status)) {
                        setTimeout(() => {
                            location.reload();
                        }, 3000);
                    }
                }
                
                // Update partner location if tracking
                if (orderData.order.partner_latitude && orderData.order.partner_longitude) {
                    window.dispatchEvent(new CustomEvent('partnerLocationUpdate', {
                        detail: {
                            lat: parseFloat(orderData.order.partner_latitude),
                            lng: parseFloat(orderData.order.partner_longitude)
                        }
                    }));
                }
            }
        } catch (error) {
            console.error('Polling error:', error);
        } finally {
            isPolling = false;
        }
    }
    
    // Get toast type based on status
    function getToastType(status) {
        switch (status) {
            case 'accepted':
            case 'paid':
            case 'completed':
                return 'success';
            case 'cancelled':
                return 'error';
            case 'in_transit':
            case 'in_progress':
                return 'info';
            default:
                return 'info';
        }
    }
    
    // Start polling
    function startPolling() {
        if (pollingTimer) return;
        
        // Initial check
        setTimeout(checkOrderStatus, 1000);
        
        // Regular polling
        pollingTimer = setInterval(checkOrderStatus, pollingInterval);
        
        console.log(`Polling started for order ${orderId} every ${pollingInterval}ms`);
    }
    
    // Stop polling
    function stopPolling() {
        if (pollingTimer) {
            clearInterval(pollingTimer);
            pollingTimer = null;
            console.log('Polling stopped');
        }
    }
    
    // Pause polling when page is hidden
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopPolling();
        } else {
            startPolling();
        }
    });
    
    // Export functions globally
    window.INGCleanPolling = {
        start: startPolling,
        stop: stopPolling,
        check: checkOrderStatus,
        showToast: showToast
    };
    
    // Auto-start if order ID is set
    if (orderId) {
        startPolling();
    }
})();
</script>
