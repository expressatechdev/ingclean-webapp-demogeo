/**
 * INGClean - Capacitor Push Notifications Handler
 * Maneja notificaciones push nativas en la app Android
 * FIXED: Detecta correctamente el tipo de usuario (client/partner)
 */

(function() {
    'use strict';
    
    // Verificar si estamos en Capacitor
    const isCapacitor = typeof Capacitor !== 'undefined' && Capacitor.isNativePlatform();
    
    if (!isCapacitor) {
        console.log('Push: No es app nativa, usando OneSignal web');
        return;
    }
    
    console.log('Push: Inicializando FCM nativo...');
    
    // Esperar a que Capacitor esté listo
    document.addEventListener('DOMContentLoaded', async function() {
        await initPushNotifications();
    });
    
    /**
     * Detectar tipo de usuario basándose en la URL y variables disponibles
     */
    function detectUserType() {
        // Método 1: Verificar la URL
        const path = window.location.pathname.toLowerCase();
        
        if (path.includes('/partner/') || path.includes('/partner')) {
            console.log('Push: Detectado como PARTNER (por URL)');
            return 'partner';
        }
        
        if (path.includes('/client/') || path.includes('/client')) {
            console.log('Push: Detectado como CLIENT (por URL)');
            return 'client';
        }
        
        // Método 2: Verificar variables JavaScript globales
        if (typeof window.currentPartnerId !== 'undefined' && window.currentPartnerId) {
            console.log('Push: Detectado como PARTNER (por currentPartnerId)');
            return 'partner';
        }
        
        if (typeof window.isPartner !== 'undefined' && window.isPartner === true) {
            console.log('Push: Detectado como PARTNER (por isPartner)');
            return 'partner';
        }
        
        if (typeof window.userType !== 'undefined') {
            console.log('Push: Detectado como', window.userType, '(por userType)');
            return window.userType;
        }
        
        // Método 3: Verificar elemento HTML con data attribute
        const userTypeElement = document.querySelector('[data-user-type]');
        if (userTypeElement) {
            const type = userTypeElement.getAttribute('data-user-type');
            console.log('Push: Detectado como', type, '(por data-user-type)');
            return type;
        }
        
        // Método 4: Verificar meta tag
        const metaUserType = document.querySelector('meta[name="user-type"]');
        if (metaUserType) {
            const type = metaUserType.getAttribute('content');
            console.log('Push: Detectado como', type, '(por meta tag)');
            return type;
        }
        
        // Default: client
        console.log('Push: No se pudo detectar tipo, usando CLIENT por defecto');
        return 'client';
    }
    
    async function initPushNotifications() {
        try {
            const { PushNotifications } = Capacitor.Plugins;
            
            if (!PushNotifications) {
                console.error('Push: Plugin PushNotifications no disponible');
                return;
            }
            
            // Solicitar permisos
            let permStatus = await PushNotifications.checkPermissions();
            console.log('Push: Estado de permisos:', permStatus.receive);
            
            if (permStatus.receive === 'prompt') {
                permStatus = await PushNotifications.requestPermissions();
            }
            
            if (permStatus.receive !== 'granted') {
                console.warn('Push: Permisos no otorgados');
                showPermissionBanner();
                return;
            }
            
            // Registrar para recibir push
            await PushNotifications.register();
            console.log('Push: Registro iniciado');
            
            // Listener: Token recibido
            PushNotifications.addListener('registration', async (token) => {
                console.log('Push: Token FCM recibido:', token.value.substring(0, 20) + '...');
                await sendTokenToServer(token.value);
            });
            
            // Listener: Error de registro
            PushNotifications.addListener('registrationError', (error) => {
                console.error('Push: Error de registro:', error);
            });
            
            // Listener: Notificación recibida (app en primer plano)
            PushNotifications.addListener('pushNotificationReceived', (notification) => {
                console.log('Push: Notificación recibida en primer plano:', notification);
                showInAppNotification(notification);
            });
            
            // Listener: Usuario tocó la notificación
            PushNotifications.addListener('pushNotificationActionPerformed', (action) => {
                console.log('Push: Usuario tocó notificación:', action);
                handleNotificationAction(action.notification);
            });
            
            console.log('Push: FCM inicializado correctamente');
            
        } catch (error) {
            console.error('Push: Error inicializando:', error);
        }
    }
    
    /**
     * Enviar token FCM al servidor
     */
    async function sendTokenToServer(token) {
        try {
            // Detectar tipo de usuario
            const userType = detectUserType();
            
            console.log('Push: Enviando token al servidor como', userType);
            
            const response = await fetch('/api/notifications/register-token.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token: token,
                    platform: 'android',
                    type: 'fcm',
                    user_type: userType  // ← NUEVO: Enviar tipo de usuario
                }),
                credentials: 'include'
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log('Push: Token registrado en servidor para', data.user_type, data.user_id);
                localStorage.setItem('fcm_token_registered', 'true');
                localStorage.setItem('fcm_user_type', data.user_type);
            } else {
                console.error('Push: Error registrando token:', data.message);
            }
        } catch (error) {
            console.error('Push: Error enviando token al servidor:', error);
        }
    }
    
    /**
     * Mostrar notificación dentro de la app (cuando está en primer plano)
     */
    function showInAppNotification(notification) {
        const title = notification.title || 'INGClean';
        const body = notification.body || '';
        const data = notification.data || {};
        
        // Reproducir sonido
        playNotificationSound();
        
        // Vibrar
        if (navigator.vibrate) {
            navigator.vibrate([200, 100, 200]);
        }
        
        // Crear toast/banner de notificación
        const toast = document.createElement('div');
        toast.className = 'fcm-toast';
        toast.innerHTML = `
            <div class="fcm-toast-content">
                <div class="fcm-toast-icon">🔔</div>
                <div class="fcm-toast-text">
                    <strong>${escapeHtml(title)}</strong>
                    <p>${escapeHtml(body)}</p>
                </div>
                <button class="fcm-toast-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        // Agregar estilos si no existen
        if (!document.getElementById('fcm-toast-styles')) {
            const styles = document.createElement('style');
            styles.id = 'fcm-toast-styles';
            styles.textContent = `
                .fcm-toast {
                    position: fixed;
                    top: 20px;
                    left: 16px;
                    right: 16px;
                    max-width: 400px;
                    margin: 0 auto;
                    background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
                    color: white;
                    border-radius: 16px;
                    box-shadow: 0 10px 40px rgba(0, 119, 182, 0.4);
                    z-index: 999999;
                    animation: fcmSlideIn 0.3s ease;
                    cursor: pointer;
                }
                @keyframes fcmSlideIn {
                    from { transform: translateY(-100px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                .fcm-toast-content {
                    display: flex;
                    align-items: center;
                    padding: 16px;
                    gap: 12px;
                }
                .fcm-toast-icon {
                    font-size: 1.8rem;
                    flex-shrink: 0;
                }
                .fcm-toast-text {
                    flex: 1;
                }
                .fcm-toast-text strong {
                    display: block;
                    font-size: 1rem;
                    margin-bottom: 4px;
                }
                .fcm-toast-text p {
                    font-size: 0.9rem;
                    opacity: 0.9;
                    margin: 0;
                }
                .fcm-toast-close {
                    background: rgba(255,255,255,0.2);
                    border: none;
                    color: white;
                    width: 28px;
                    height: 28px;
                    border-radius: 50%;
                    font-size: 1.2rem;
                    cursor: pointer;
                    flex-shrink: 0;
                }
            `;
            document.head.appendChild(styles);
        }
        
        document.body.appendChild(toast);
        
        // Click para manejar acción
        toast.addEventListener('click', (e) => {
            if (!e.target.classList.contains('fcm-toast-close')) {
                handleNotificationAction(notification);
                toast.remove();
            }
        });
        
        // Auto-remover después de 6 segundos
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.animation = 'fcmSlideIn 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            }
        }, 6000);
    }
    
    /**
     * Manejar acción cuando el usuario toca la notificación
     */
    function handleNotificationAction(notification) {
        const data = notification.data || {};
        
        console.log('Push: Manejando acción, tipo:', data.type);
        
        // Navegar según el tipo de notificación
        if (data.url) {
            window.location.href = data.url;
            return;
        }
        
        switch (data.type) {
            case 'new_order':
                window.location.href = '/partner/';
                break;
            case 'order_accepted':
                window.location.href = '/client/payment.php?order=' + data.order_id;
                break;
            case 'payment_received':
                window.location.href = '/partner/active-service.php?order=' + data.order_id;
                break;
            case 'partner_in_transit':
            case 'partner_arrived':
            case 'service_started':
                window.location.href = '/client/tracking.php?order=' + data.order_id;
                break;
            case 'service_completed':
            case 'order_cancelled':
                // Recargar página actual
                window.location.reload();
                break;
            default:
                console.log('Push: Tipo de notificación no reconocido');
        }
    }
    
    /**
     * Reproducir sonido de notificación
     */
    function playNotificationSound() {
        try {
            // Sonido base64 simple (beep)
            const audioData = 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdH2Onp6agHBkXW57jZ2hoZV/bWFdcIGVo6WfjHlpX19eleOnJqQgG1hYGZwfYqWmZWJeWxnaGxzeYSNk5KLgHRtbW9ydHmAiI2MiIB3cXBwcXN2e4GGh4WBe3d0c3N0dXh8gIOEgoB8eHZ1dXV2eHt+gIGAf3x5d3Z2dnd4ent9fn59fHp4d3d3d3h5e3x9fX18e3l4d3d3eHl6e3x8fHt6eXh4eHh4eXp7e3t7enl4eHh4eXl6ent7e3p5eXh4eXl5enp6enp6eXl5eXl5eXp6enp6enl5eXl5eXl5enp6enl5eXl5eXl5eXp6enp5eXl5eXl5eXl5eXl5eXl5eXl5eXl5eXl5eXl5eXl5eQ==';
            const audio = new Audio(audioData);
            audio.volume = 0.5;
            audio.play().catch(() => {});
        } catch (e) {
            console.log('Push: No se pudo reproducir sonido');
        }
    }
    
    /**
     * Mostrar banner para solicitar permisos
     */
    function showPermissionBanner() {
        // Solo mostrar si no se ha descartado recientemente
        const dismissed = localStorage.getItem('push_banner_dismissed');
        if (dismissed && (Date.now() - parseInt(dismissed)) < 86400000) {
            return;
        }
        
        const banner = document.createElement('div');
        banner.id = 'push-permission-banner';
        banner.innerHTML = `
            <div style="
                position: fixed;
                bottom: 80px;
                left: 16px;
                right: 16px;
                max-width: 400px;
                margin: 0 auto;
                background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
                color: white;
                padding: 16px 20px;
                border-radius: 16px;
                box-shadow: 0 10px 40px rgba(0, 119, 182, 0.4);
                z-index: 9999;
            ">
                <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 14px;">
                    <div style="font-size: 2rem;">🔔</div>
                    <div>
                        <strong style="display: block; margin-bottom: 4px;">Activa las notificaciones</strong>
                        <span style="font-size: 0.85rem; opacity: 0.9;">Recibe alertas de tu servicio en tiempo real</span>
                    </div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button onclick="window.requestPushPermission()" style="
                        flex: 1;
                        padding: 10px 16px;
                        border-radius: 10px;
                        border: none;
                        background: white;
                        color: #0077b6;
                        font-weight: 600;
                        font-size: 0.9rem;
                        cursor: pointer;
                    ">Activar</button>
                    <button onclick="window.dismissPushBanner()" style="
                        flex: 1;
                        padding: 10px 16px;
                        border-radius: 10px;
                        border: none;
                        background: rgba(255,255,255,0.2);
                        color: white;
                        font-weight: 600;
                        font-size: 0.9rem;
                        cursor: pointer;
                    ">Después</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(banner);
    }
    
    /**
     * Solicitar permisos manualmente
     */
    window.requestPushPermission = async function() {
        const banner = document.getElementById('push-permission-banner');
        if (banner) banner.remove();
        
        try {
            const { PushNotifications } = Capacitor.Plugins;
            const permStatus = await PushNotifications.requestPermissions();
            
            if (permStatus.receive === 'granted') {
                await PushNotifications.register();
            }
        } catch (error) {
            console.error('Push: Error solicitando permisos:', error);
        }
    };
    
    /**
     * Descartar banner
     */
    window.dismissPushBanner = function() {
        const banner = document.getElementById('push-permission-banner');
        if (banner) banner.remove();
        localStorage.setItem('push_banner_dismissed', Date.now().toString());
    };
    
    /**
     * Escapar HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
})();