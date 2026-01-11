<?php
/**
 * INGClean - OneSignal SDK Include
 * Incluir este archivo en las páginas donde quieras habilitar push notifications
 * 
 * Uso: <?php include 'includes/onesignal.php'; ?>
 * 
 * Variables que puedes definir antes de incluir:
 * - $onesignal_user_type: 'client' o 'partner'
 * - $onesignal_user_id: ID del usuario logueado
 */

// Solo cargar si OneSignal está habilitado
if (!defined('ONESIGNAL_ENABLED') || !ONESIGNAL_ENABLED) {
    return;
}

// Obtener datos del usuario si está logueado
$os_user_type = $onesignal_user_type ?? (auth()->isLoggedIn() ? auth()->getUserType() : null);
$os_user_id = $onesignal_user_id ?? (auth()->isLoggedIn() ? auth()->getUserId() : null);
$os_external_id = ($os_user_type && $os_user_id) ? "{$os_user_type}_{$os_user_id}" : null;

// Para partners, obtener si está disponible
$os_is_available = '0';
if ($os_user_type === 'partner' && auth()->isLoggedIn()) {
    $user = auth()->getCurrentUser();
    $os_is_available = ($user['is_available'] ?? 0) ? '1' : '0';
}
?>

<!-- OneSignal SDK -->
<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
<script>
window.OneSignalDeferred = window.OneSignalDeferred || [];
OneSignalDeferred.push(async function(OneSignal) {
    await OneSignal.init({
        appId: "<?= ONESIGNAL_APP_ID ?>",
        safari_web_id: null,
        notifyButton: {
            enable: false // Usaremos nuestro propio UI
        },
        promptOptions: {
            slidedown: {
                prompts: [{
                    type: "push",
                    autoPrompt: false, // No mostrar automáticamente
                    text: {
                        actionMessage: "¿Quieres recibir notificaciones de INGClean?",
                        acceptButton: "Sí, permitir",
                        cancelButton: "Ahora no"
                    },
                    delay: {
                        pageViews: 1,
                        timeDelay: 5
                    }
                }]
            }
        },
        welcomeNotification: {
            title: "¡Bienvenido a INGClean!",
            message: "Recibirás notificaciones de tus servicios aquí."
        }
    });
    
    <?php if ($os_external_id): ?>
    // Registrar usuario con external_id
    await OneSignal.login("<?= $os_external_id ?>");
    
    // Agregar tags para segmentación
    await OneSignal.User.addTags({
        user_type: "<?= $os_user_type ?>",
        user_id: "<?= $os_user_id ?>",
        <?php if ($os_user_type === 'partner'): ?>
        is_available: "<?= $os_is_available ?>"
        <?php endif; ?>
    });
    
    console.log('OneSignal: Usuario registrado como <?= $os_external_id ?>');
    <?php endif; ?>
    
    // Listener para cuando el usuario da permiso
    OneSignal.Notifications.addEventListener('permissionChange', function(permission) {
        console.log('OneSignal: Permiso cambiado a', permission);
        if (permission) {
            // Mostrar mensaje de éxito
            showNotificationSuccess();
        }
    });
});

// Función para solicitar permiso de notificaciones (llamar desde UI)
function requestNotificationPermission() {
    OneSignalDeferred.push(async function(OneSignal) {
        const permission = await OneSignal.Notifications.permission;
        
        if (permission) {
            // Ya tiene permiso
            showNotificationSuccess();
            return;
        }
        
        // Pedir permiso
        await OneSignal.Slidedown.promptPush();
    });
}

// Función para verificar si tiene permisos
function hasNotificationPermission() {
    return new Promise((resolve) => {
        OneSignalDeferred.push(async function(OneSignal) {
            const permission = await OneSignal.Notifications.permission;
            resolve(permission);
        });
    });
}

// Actualizar tag de disponibilidad del partner
function updatePartnerAvailability(isAvailable) {
    OneSignalDeferred.push(async function(OneSignal) {
        await OneSignal.User.addTag('is_available', isAvailable ? '1' : '0');
        console.log('OneSignal: Disponibilidad actualizada a', isAvailable);
    });
}

// Mostrar mensaje de éxito
function showNotificationSuccess() {
    // Puedes personalizar esto
    if (typeof Toastify !== 'undefined') {
        Toastify({
            text: "🔔 ¡Notificaciones activadas!",
            duration: 3000,
            gravity: "top",
            position: "center",
            style: { background: "#22c55e" }
        }).showToast();
    } else {
        console.log('Notificaciones activadas');
    }
}
</script>

<?php if ($os_user_type): ?>
<!-- Banner para pedir permiso de notificaciones (si no lo tiene) -->
<style>
.notification-banner {
    display: none;
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
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from { transform: translateY(100px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.notification-banner-content {
    display: flex;
    align-items: center;
    gap: 14px;
}

.notification-banner-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.notification-banner-text h4 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 4px;
}

.notification-banner-text p {
    font-size: 0.85rem;
    opacity: 0.9;
}

.notification-banner-actions {
    display: flex;
    gap: 10px;
    margin-top: 14px;
}

.notification-banner-btn {
    flex: 1;
    padding: 10px 16px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    font-family: inherit;
}

.notification-banner-btn.allow {
    background: white;
    color: #0077b6;
}

.notification-banner-btn.later {
    background: rgba(255,255,255,0.2);
    color: white;
}

.notification-banner-close {
    position: absolute;
    top: 8px;
    right: 12px;
    background: none;
    border: none;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    opacity: 0.7;
}
</style>

<div class="notification-banner" id="notificationBanner">
    <button class="notification-banner-close" onclick="hideNotificationBanner()">&times;</button>
    <div class="notification-banner-content">
        <div class="notification-banner-icon">🔔</div>
        <div class="notification-banner-text">
            <h4>Activa las notificaciones</h4>
            <p>Recibe alertas de <?= $os_user_type === 'partner' ? 'nuevas órdenes' : 'tu servicio' ?> en tiempo real</p>
        </div>
    </div>
    <div class="notification-banner-actions">
        <button class="notification-banner-btn allow" onclick="allowNotifications()">Activar</button>
        <button class="notification-banner-btn later" onclick="hideNotificationBanner()">Después</button>
    </div>
</div>

<script>
// Verificar y mostrar banner si no tiene permiso
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(async function() {
        // Verificar si ya descartó el banner recientemente
        const dismissed = localStorage.getItem('notif_banner_dismissed');
        if (dismissed && (Date.now() - parseInt(dismissed)) < 86400000) { // 24 horas
            return;
        }
        
        // Verificar permiso actual
        const hasPermission = await hasNotificationPermission();
        if (!hasPermission) {
            document.getElementById('notificationBanner').style.display = 'block';
        }
    }, 3000); // Mostrar después de 3 segundos
});

function allowNotifications() {
    hideNotificationBanner();
    requestNotificationPermission();
}

function hideNotificationBanner() {
    document.getElementById('notificationBanner').style.display = 'none';
    localStorage.setItem('notif_banner_dismissed', Date.now().toString());
}
</script>
<?php endif; ?>
