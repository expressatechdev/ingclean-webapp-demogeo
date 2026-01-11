<?php
/**
 * INGClean - Servicio Activo del Partner
 * CORREGIDO: GPS activo en todos los estados
 */
require_once '../includes/init.php';

auth()->requireLogin(['partner']);

$user = auth()->getCurrentUser();
$db = Database::getInstance();

$orderId = get('order');

if (!$orderId) {
    redirect('/partner/');
}

$order = $db->fetchOne(
    "SELECT o.*, s.name as service_name, s.price,
            c.name as client_name, c.phone as client_phone,
            c.address as client_address, c.latitude as client_lat, c.longitude as client_lng
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     JOIN clients c ON o.client_id = c.id
     WHERE o.id = :order_id AND o.partner_id = :partner_id
     AND o.status IN ('accepted', 'paid', 'in_transit', 'in_progress')",
    ['order_id' => $orderId, 'partner_id' => $user['id']]
);

if (!$order) {
    setFlash('error', 'Orden no encontrada');
    redirect('/partner/');
}

$partnerEarnings = calculatePartnerAmount($order['price']);
$orderStatus = $order['status'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#16a34a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    
    <title>Servicio Activo - <?= APP_NAME ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        html, body {
            height: 100%;
            overflow: hidden;
        }
        
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0fdf4;
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            padding: 12px 16px;
            padding-top: max(12px, env(safe-area-inset-top));
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.15);
            z-index: 100;
            flex-shrink: 0;
        }
        
        .back-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
            text-decoration: none;
            flex-shrink: 0;
        }
        
        .header-info {
            flex: 1;
            min-width: 0;
            color: white;
        }
        
        .header-info h1 {
            font-size: 1.15rem;
            font-weight: 600;
        }
        
        .header-info p {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .status-badge {
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
            background: rgba(255,255,255,0.25);
            color: white;
        }
        
        /* Mapa - Tamaño reducido para mejor visibilidad de botones */
        .map-container {
            position: relative;
            min-height: 0;
            height: 40vh;
            max-height: 300px;
            flex-shrink: 0;
        }
        
        #map {
            width: 100%;
            height: 100%;
        }
        
        /* ETA Card flotante */
        .eta-card {
            position: absolute;
            top: 16px;
            left: 16px;
            right: 16px;
            background: white;
            border-radius: 18px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.15);
            z-index: 10;
        }
        
        .eta-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            flex-shrink: 0;
        }
        
        .eta-info h3 {
            font-size: 1.5rem;
            color: #1e3a5f;
            font-weight: 700;
        }
        
        .eta-info p {
            font-size: 1.1rem;
            color: #64748b;
        }
        
        /* Botones del mapa */
        .map-buttons {
            position: absolute;
            bottom: 24px;
            right: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            z-index: 10;
        }
        
        .btn-map {
            width: 56px;
            height: 56px;
            background: white;
            border: none;
            border-radius: 50%;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            cursor: pointer;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-navigate {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
        }
        
        /* Panel de Navegación Turn-by-Turn */
        .nav-panel {
            display: none;
            position: absolute;
            top: 80px;
            left: 10px;
            right: 10px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            z-index: 100;
            max-height: 50%;
            overflow: hidden;
        }
        
        .nav-panel.show {
            display: block;
        }
        
        .nav-panel-header {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-panel-header h3 {
            font-size: 1rem;
            font-weight: 600;
        }
        
        .nav-panel-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
        }
        
        .nav-current-step {
            background: #f0fdf4;
            padding: 16px;
            border-bottom: 1px solid #dcfce7;
        }
        
        .nav-current-step .step-icon {
            font-size: 2rem;
            margin-bottom: 8px;
        }
        
        .nav-current-step .step-instruction {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e3a5f;
        }
        
        .nav-current-step .step-distance {
            font-size: 0.9rem;
            color: #64748b;
            margin-top: 4px;
        }
        
        .nav-steps-list {
            max-height: 150px;
            overflow-y: auto;
            padding: 8px 0;
        }
        
        .nav-step-item {
            padding: 10px 16px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .nav-step-item .step-num {
            background: #e2e8f0;
            color: #64748b;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .nav-step-item .step-text {
            font-size: 0.85rem;
            color: #374151;
            flex: 1;
        }
        
        .nav-step-item .step-dist {
            font-size: 0.8rem;
            color: #94a3b8;
            flex-shrink: 0;
        }
        
        /* Botón de navegación activo */
        .btn-navigate.active {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.3);
        }

        /* Panel Inferior - Más espacio para botones */
        .bottom-panel {
            background: white;
            border-radius: 28px 28px 0 0;
            padding: 20px 20px;
            padding-bottom: max(24px, env(safe-area-inset-bottom));
            box-shadow: 0 -4px 35px rgba(0,0,0,0.15);
            flex: 1;
            overflow-y: auto;
            z-index: 50;
            min-height: 0;
        }
        
        /* Cliente info - Compacto */
        .client-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .client-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .client-details {
            flex: 1;
            min-width: 0;
        }
        
        .client-details h3 {
            font-size: 1.2rem;
            color: #1e3a5f;
            margin-bottom: 4px;
        }
        
        .client-details p {
            font-size: 0.95rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .btn-call {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            background: #dcfce7;
            color: #16a34a;
            border: none;
            font-size: 1.4rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        /* Info compacta - Más pequeña */
        .info-compact {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 14px;
        }
        
        .info-item {
            text-align: center;
            flex: 1;
        }
        
        .info-item .label {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 2px;
        }
        
        .info-item .value {
            font-size: 1rem;
            font-weight: 700;
            color: #1e3a5f;
        }
        
        .info-item .value.earnings {
            color: #16a34a;
        }
        
        /* Botones de acción - MÁS GRANDES Y VISIBLES */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 14px;
            padding-top: 10px;
        }
        
        .btn-action {
            width: 100%;
            padding: 20px;
            min-height: 70px;
            border-radius: 18px;
            border: none;
            font-size: 1.25rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            -webkit-appearance: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-green {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
        }
        
        .btn-blue {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(0, 119, 182, 0.4);
        }
        
        .btn-orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }
        
        .btn-cancel {
            background: #fee2e2;
            color: #dc2626;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.15);
        }
        
        .btn-action:active {
            transform: scale(0.98);
        }
        
        .btn-action:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Nota de estado */
        .status-note {
            background: #fef3c7;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            color: #92400e;
        }
        
        .status-note-icon {
            font-size: 1.3rem;
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }
        
        .modal-overlay.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 28px;
            padding: 32px;
            max-width: 380px;
            width: 100%;
            text-align: center;
        }
        
        .modal-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .modal-content h2 {
            font-size: 1.4rem;
            color: #1e3a5f;
            margin-bottom: 12px;
        }
        
        .modal-content p {
            font-size: 1.1rem;
            color: #64748b;
        }
        
        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 28px;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 16px;
            min-height: 56px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1.05rem;
            cursor: pointer;
            font-family: inherit;
            border: none;
        }
        
        .btn-modal-cancel {
            background: #f1f5f9;
            color: #64748b;
        }
        
        .btn-modal-green {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
        }
        
        .btn-modal-red {
            background: #dc2626;
            color: white;
        }
        
        @media (min-width: 768px) {
            .bottom-panel {
                max-width: 500px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <a href="index.php" class="back-btn">←</a>
        <div class="header-info">
            <h1>🚗 Servicio Activo</h1>
            <p><?= e($order['order_code'] ?? 'ORD-'.$order['id']) ?></p>
        </div>
        <span class="status-badge">
            <?php
            $statusLabels = [
                'accepted' => '✅ Aceptado',
                'paid' => '💳 Pagado',
                'in_transit' => '🚗 En camino',
                'in_progress' => '🧹 Limpiando'
            ];
            echo $statusLabels[$orderStatus] ?? $orderStatus;
            ?>
        </span>
    </header>
    
    <!-- Mapa GRANDE -->
    <div class="map-container">
        <div id="map"></div>
        
        <!-- ETA -->
        <div class="eta-card">
            <div class="eta-icon">🏠</div>
            <div class="eta-info">
                <h3 id="etaTime">Calculando...</h3>
                <p id="etaDistance">Obteniendo tu ubicación...</p>
            </div>
        </div>
        
        <!-- Botones del mapa -->
        <div class="map-buttons">
            <button class="btn-map btn-navigate" onclick="toggleNavigation()" id="btnNav" title="Ver instrucciones de navegación">🧭</button>
            <button class="btn-map" onclick="openGoogleMaps()" title="Abrir en Google Maps">📍</button>
            <button class="btn-map" onclick="recenterMap()" title="Ver ruta completa">🎯</button>
        </div>
        
        <!-- Panel de Navegación Turn-by-Turn -->
        <div class="nav-panel" id="navPanel">
            <div class="nav-panel-header">
                <h3>🧭 Instrucciones de Navegación</h3>
                <button class="nav-panel-close" onclick="toggleNavigation()">✕</button>
            </div>
            <div class="nav-current-step" id="navCurrentStep">
                <div class="step-icon">🚗</div>
                <div class="step-instruction">Calculando ruta...</div>
                <div class="step-distance">Espera un momento</div>
            </div>
            <div class="nav-steps-list" id="navStepsList">
                <!-- Se llena dinámicamente -->
            </div>
        </div>
    </div>
    
    <!-- Panel Inferior -->
    <div class="bottom-panel">
        <!-- Nota según estado -->
        <?php if ($orderStatus === 'accepted'): ?>
            <div class="status-note">
                <span class="status-note-icon">⏳</span>
                <span>Esperando que el cliente realice el pago</span>
            </div>
        <?php endif; ?>
        
        <!-- Cliente -->
        <div class="client-row">
            <div class="client-photo">
                <?= strtoupper(substr($order['client_name'] ?? 'C', 0, 1)) ?>
            </div>
            <div class="client-details">
                <h3>👤 <?= e($order['client_name'] ?? 'Cliente') ?></h3>
                <p>📍 <?= e($order['client_address'] ?: 'Ver ubicación en mapa') ?></p>
            </div>
            <?php if (!empty($order['client_phone'])): ?>
                <a href="tel:<?= e($order['client_phone']) ?>" class="btn-call">📞</a>
            <?php endif; ?>
        </div>
        
        <!-- Info compacta -->
        <div class="info-compact">
            <div class="info-item">
                <div class="label">Servicio</div>
                <div class="value"><?= e($order['service_name'] ?? '') ?></div>
            </div>
            <div class="info-item">
                <div class="label">💰 Tu ganancia</div>
                <div class="value earnings">$<?= number_format($partnerEarnings, 2) ?></div>
            </div>
        </div>
        
        <!-- Botones de acción según estado -->
        <div class="action-buttons">
            <?php if ($orderStatus === 'accepted'): ?>
                <!-- Esperando pago - no puede hacer nada aún -->
                <button class="btn-action btn-orange" disabled>
                    ⏳ Esperando pago del cliente
                </button>
            <?php elseif ($orderStatus === 'paid'): ?>
                <!-- Ya pagó - puede ir en camino -->
                <button class="btn-action btn-green" onclick="goInTransit()">
                    🚗 Ir en Camino
                </button>
            <?php elseif ($orderStatus === 'in_transit'): ?>
                <!-- En camino - puede indicar que llegó -->
                <button class="btn-action btn-blue" onclick="showArrivedModal()">
                    🏠 Ya llegué al destino
                </button>
            <?php elseif ($orderStatus === 'in_progress'): ?>
                <!-- Limpiando - puede completar -->
                <button class="btn-action btn-green" onclick="showCompleteModal()">
                    🎉 Completar Servicio
                </button>
            <?php endif; ?>
            
            <?php if (in_array($orderStatus, ['accepted', 'paid', 'in_transit'])): ?>
                <button class="btn-action btn-cancel" onclick="showCancelModal()">
                    ❌ Cancelar Servicio
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modales -->
    <div class="modal-overlay" id="arrivedModal">
        <div class="modal-content">
            <div class="modal-icon">🏠</div>
            <h2>¿Ya llegaste?</h2>
            <p>Confirma que llegaste y comenzarás la limpieza.</p>
            <div class="modal-buttons">
                <button class="btn-modal-cancel" onclick="closeModal('arrivedModal')">No aún</button>
                <button class="btn-modal-green" onclick="startCleaning()">Sí, llegué</button>
            </div>
        </div>
    </div>
    
    <div class="modal-overlay" id="completeModal">
        <div class="modal-content">
            <div class="modal-icon">🎉</div>
            <h2>¿Completar servicio?</h2>
            <p>Recibirás <strong style="color: #16a34a;">$<?= number_format($partnerEarnings, 2) ?></strong></p>
            <div class="modal-buttons">
                <button class="btn-modal-cancel" onclick="closeModal('completeModal')">Cancelar</button>
                <button class="btn-modal-green" onclick="confirmComplete()">✓ Completar</button>
            </div>
        </div>
    </div>
    
    <div class="modal-overlay" id="cancelModal">
        <div class="modal-content">
            <div class="modal-icon">⚠️</div>
            <h2>¿Cancelar el servicio?</h2>
            <p>No recibirás pago y puede afectar tu calificación.</p>
            <div class="modal-buttons">
                <button class="btn-modal-cancel" onclick="closeModal('cancelModal')">Volver</button>
                <button class="btn-modal-red" onclick="confirmCancel()">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Google Maps -->
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&callback=initMap" async defer></script>
    
    <script>
        let map, clientMarker, partnerMarker, directionsRenderer, directionsService;
        const orderId = <?= $order['id'] ?>;
        const clientLat = <?= $order['client_lat'] ?? 0 ?>;
        const clientLng = <?= $order['client_lng'] ?? 0 ?>;
        const orderStatus = '<?= $orderStatus ?>';
        let myLat = null, myLng = null;
        let watchId = null;
        let hasInitialFit = false;
        
        // Wake Lock - Mantener pantalla encendida silenciosamente
        let wakeLock = null;
        async function requestWakeLock() {
            try {
                if ('wakeLock' in navigator) {
                    wakeLock = await navigator.wakeLock.request('screen');
                }
            } catch (e) {}
        }
        requestWakeLock();
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') requestWakeLock();
        });
        
        // Sonido de notificación
        const notificationSound = new Audio('/assets/sounds/notification.mp3');
        let audioUnlocked = false;
        let lastOrderStatus = orderStatus;
        
        // Desbloquear audio con interacción
        document.addEventListener('click', function() {
            if (!audioUnlocked) {
                notificationSound.play().then(() => {
                    notificationSound.pause();
                    notificationSound.currentTime = 0;
                    audioUnlocked = true;
                }).catch(() => {});
            }
        }, { once: false });
        
        function playNotificationSound() {
            if (audioUnlocked) {
                notificationSound.currentTime = 0;
                notificationSound.play().catch(() => {});
            }
        }
        
        function initMap() {
            const clientPos = { lat: clientLat, lng: clientLng };
            
            map = new google.maps.Map(document.getElementById('map'), {
                center: clientPos,
                zoom: 14,
                disableDefaultUI: false,
                zoomControl: true,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
                gestureHandling: 'greedy',
                styles: [
                    { featureType: "poi", stylers: [{ visibility: "off" }] },
                    { featureType: "transit", stylers: [{ visibility: "off" }] }
                ]
            });
            
            // Marcador del cliente (destino)
            clientMarker = new google.maps.Marker({
                position: clientPos,
                map: map,
                title: 'Destino del cliente',
                icon: {
                    url: 'data:image/svg+xml,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60">
                            <circle cx="30" cy="30" r="26" fill="#0077b6" stroke="white" stroke-width="4"/>
                            <text x="30" y="38" text-anchor="middle" fill="white" font-size="24">🏠</text>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(60, 60),
                    anchor: new google.maps.Point(30, 30)
                }
            });
            
            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                map: map,
                suppressMarkers: true,
                polylineOptions: {
                    strokeColor: '#22c55e',
                    strokeWeight: 7,
                    strokeOpacity: 0.9
                }
            });
            
            // ⚠️ iOS Safari requiere interacción del usuario antes de pedir GPS
            // Mostrar botón para activar GPS (NO iniciar automáticamente)
            showGPSActivationButton();
            
            // Detectar cuando vuelve a la página (desde Google Maps u otra app)
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    console.log('📍 Página visible - actualizando ubicación');
                    // Enviar ubicación inmediatamente al volver
                    if (myLat && myLng) {
                        sendLocationToServer(myLat, myLng);
                        updateMyLocation(myLat, myLng);
                    } else {
                        // Si no tiene ubicación, mostrar botón de nuevo
                        showGPSActivationButton();
                    }
                }
            });
            
            // También detectar focus de la ventana
            window.addEventListener('focus', function() {
                console.log('📍 Ventana con foco - actualizando ubicación');
                if (myLat && myLng) {
                    sendLocationToServer(myLat, myLng);
                }
            });
        }
        
        // Botón de activación GPS (necesario para iOS Safari)
        function showGPSActivationButton() {
            // Si ya tiene ubicación, no mostrar
            if (myLat && myLng) return;
            
            // Remover cualquier botón existente
            const oldBtn = document.getElementById('gpsUnifiedBtn');
            if (oldBtn) oldBtn.remove();
            
            let btn = document.getElementById('gpsActivateBtn');
            if (!btn) {
                btn = document.createElement('button');
                btn.id = 'gpsActivateBtn';
                btn.innerHTML = '📍 Toca para activar GPS';
                btn.style.cssText = `
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
                    color: white;
                    border: none;
                    padding: 20px 30px;
                    border-radius: 16px;
                    font-size: 1.2rem;
                    font-weight: 600;
                    font-family: inherit;
                    cursor: pointer;
                    z-index: 9999;
                    box-shadow: 0 8px 30px rgba(34, 197, 94, 0.4);
                    text-align: center;
                    max-width: 85%;
                `;
                
                btn.onclick = function() {
                    btn.innerHTML = '⏳ Activando GPS...';
                    btn.disabled = true;
                    btn.style.background = '#94a3b8';
                    
                    // Iniciar GPS con interacción del usuario
                    startMyLocationTracking();
                    
                    // Ocultar después de 3 segundos si tuvo éxito
                    setTimeout(() => {
                        if (myLat && myLng) {
                            btn.remove();
                        }
                    }, 3000);
                };
                
                document.body.appendChild(btn);
            }
            btn.style.display = 'block';
        }
        
        // Función para limpiar todos los botones GPS
        function removeAllGPSButtons() {
            const btns = ['gpsActivateBtn', 'gpsUnifiedBtn', 'gpsRetryBtn'];
            btns.forEach(id => {
                const btn = document.getElementById(id);
                if (btn) btn.remove();
            });
        }
        
        function startMyLocationTracking() {
            if (!navigator.geolocation) {
                showGPSError('Tu navegador no soporta GPS');
                return;
            }
            
            document.getElementById('etaTime').textContent = '📍 Activando GPS...';
            document.getElementById('etaDistance').textContent = 'Esperando permiso...';
            
            // Opciones optimizadas para iOS
            const gpsOptions = {
                enableHighAccuracy: true,
                timeout: 30000,        // 30 segundos para iOS
                maximumAge: 5000
            };
            
            // Primero obtener ubicación actual
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    console.log('✅ GPS obtenido:', position.coords.latitude, position.coords.longitude);
                    myLat = position.coords.latitude;
                    myLng = position.coords.longitude;
                    updateMyLocation(myLat, myLng);
                    sendLocationToServer(myLat, myLng);
                    
                    // Limpiar TODOS los botones de GPS
                    removeAllGPSButtons();
                    
                    // Iniciar seguimiento continuo DESPUÉS del primer éxito
                    startContinuousTracking();
                },
                function(error) {
                    console.log('❌ Error GPS:', error.code, error.message);
                    handleGPSError(error);
                },
                gpsOptions
            );
        }
        
        function startContinuousTracking() {
            // Seguimiento continuo después de obtener permiso
            watchId = navigator.geolocation.watchPosition(
                function(position) {
                    myLat = position.coords.latitude;
                    myLng = position.coords.longitude;
                    updateMyLocation(myLat, myLng);
                    sendLocationToServer(myLat, myLng);
                },
                function(error) {
                    console.log('Error watch GPS:', error.code, error.message);
                },
                { 
                    enableHighAccuracy: true, 
                    maximumAge: 5000, 
                    timeout: 30000 
                }
            );
        }
        
        function handleGPSError(error) {
            // Ocultar botón de activación si existe
            const activateBtn = document.getElementById('gpsActivateBtn');
            if (activateBtn) activateBtn.remove();
            
            let title = '';
            let instructions = '';
            
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    title = '⚠️ GPS Bloqueado';
                    instructions = `
                        <div style="text-align:left; font-size:0.85rem; line-height:1.5;">
                            <strong>Solución:</strong><br>
                            1. Ve a <b>Configuración</b> del iPhone<br>
                            2. <b>Safari</b> → <b>Borrar historial</b><br>
                            3. Vuelve aquí y recarga
                        </div>
                    `;
                    break;
                case error.POSITION_UNAVAILABLE:
                    title = '📍 GPS no disponible';
                    instructions = 'Verifica que el GPS esté activo en tu iPhone';
                    break;
                case error.TIMEOUT:
                    title = '⏳ GPS tardó mucho';
                    instructions = 'Toca el botón para reintentar';
                    break;
                default:
                    title = '❌ Error de GPS';
                    instructions = error.message || 'Intenta de nuevo';
            }
            
            document.getElementById('etaTime').textContent = title;
            document.getElementById('etaDistance').innerHTML = instructions;
            
            // Mostrar botón unificado
            showUnifiedGPSButton(error.code === error.PERMISSION_DENIED);
        }
        
        function showUnifiedGPSButton(isPermissionDenied) {
            // Remover cualquier botón existente
            const oldBtn1 = document.getElementById('gpsRetryBtn');
            const oldBtn2 = document.getElementById('gpsActivateBtn');
            if (oldBtn1) oldBtn1.remove();
            if (oldBtn2) oldBtn2.remove();
            
            const btn = document.createElement('button');
            btn.id = 'gpsUnifiedBtn';
            
            if (isPermissionDenied) {
                btn.innerHTML = '🔄 Ya borré historial - Reintentar';
            } else {
                btn.innerHTML = '📍 Activar GPS';
            }
            
            btn.style.cssText = `
                position: fixed;
                bottom: 180px;
                left: 50%;
                transform: translateX(-50%);
                background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
                color: white;
                border: none;
                padding: 16px 28px;
                border-radius: 30px;
                font-size: 1rem;
                font-weight: 600;
                font-family: inherit;
                cursor: pointer;
                z-index: 9999;
                box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
                white-space: nowrap;
            `;
            
            btn.onclick = function() {
                btn.innerHTML = '⏳ Activando...';
                btn.disabled = true;
                btn.style.background = '#94a3b8';
                
                startMyLocationTracking();
                
                setTimeout(() => {
                    if (!myLat || !myLng) {
                        btn.innerHTML = isPermissionDenied ? '🔄 Reintentar' : '📍 Activar GPS';
                        btn.disabled = false;
                        btn.style.background = 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)';
                    }
                }, 3000);
            };
            
            document.body.appendChild(btn);
        }
        
        function showGPSError(message) {
            document.getElementById('etaTime').textContent = '❌ GPS no disponible';
            document.getElementById('etaDistance').textContent = message;
        }
        
        function updateMyLocation(lat, lng) {
            const myPos = { lat: lat, lng: lng };
            
            if (!partnerMarker) {
                partnerMarker = new google.maps.Marker({
                    position: myPos,
                    map: map,
                    title: 'Mi ubicación',
                    icon: {
                        url: 'data:image/svg+xml,' + encodeURIComponent(`
                            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60">
                                <circle cx="30" cy="30" r="26" fill="#22c55e" stroke="white" stroke-width="4"/>
                                <text x="30" y="38" text-anchor="middle" fill="white" font-size="22">🚗</text>
                            </svg>
                        `),
                        scaledSize: new google.maps.Size(60, 60),
                        anchor: new google.maps.Point(30, 30)
                    }
                });
                
                // Ajustar mapa para mostrar ambos marcadores
                if (!hasInitialFit) {
                    setTimeout(recenterMap, 500);
                    hasInitialFit = true;
                }
            } else {
                partnerMarker.setPosition(myPos);
            }
            
            // Calcular ruta
            calculateRoute(myPos);
            
            // Actualizar panel de navegación si está visible
            updateNavigationIfActive();
        }
        
        function calculateRoute(myPos) {
            directionsService.route({
                origin: myPos,
                destination: { lat: clientLat, lng: clientLng },
                travelMode: google.maps.TravelMode.DRIVING
            }, function(result, status) {
                if (status === 'OK') {
                    directionsRenderer.setDirections(result);
                    const route = result.routes[0].legs[0];
                    document.getElementById('etaTime').textContent = '⏱️ ' + route.duration.text;
                    document.getElementById('etaDistance').textContent = '📍 ' + route.distance.text + ' de distancia';
                } else {
                    // Si falla la ruta, calcular distancia directa
                    const distance = calculateDirectDistance(myPos.lat, myPos.lng, clientLat, clientLng);
                    document.getElementById('etaTime').textContent = '📍 ~' + distance.toFixed(1) + ' km';
                    document.getElementById('etaDistance').textContent = 'Distancia aproximada';
                }
            });
        }
        
        function calculateDirectDistance(lat1, lng1, lat2, lng2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLng/2) * Math.sin(dLng/2);
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        }
        
        function recenterMap() {
            if (!clientMarker) return;
            const bounds = new google.maps.LatLngBounds();
            bounds.extend(clientMarker.getPosition());
            if (partnerMarker) bounds.extend(partnerMarker.getPosition());
            map.fitBounds(bounds, { padding: 100 });
        }
        
        function openGoogleMaps() {
            // Enviar ubicación actual antes de salir
            if (myLat && myLng) {
                sendLocationToServer(myLat, myLng);
            }
            
            // Mostrar recordatorio
            showNavigationReminder();
            
            const url = `https://www.google.com/maps/dir/?api=1&destination=${clientLat},${clientLng}&travelmode=driving`;
            window.open(url, '_blank');
        }
        
        // === NAVEGACIÓN TURN-BY-TURN ===
        let navigationSteps = [];
        let currentStepIndex = 0;
        let navPanelVisible = false;
        
        function toggleNavigation() {
            const panel = document.getElementById('navPanel');
            const btn = document.getElementById('btnNav');
            
            navPanelVisible = !navPanelVisible;
            
            if (navPanelVisible) {
                panel.classList.add('show');
                btn.classList.add('active');
                if (myLat && myLng) {
                    calculateNavigationSteps();
                } else {
                    document.getElementById('navCurrentStep').innerHTML = `
                        <div class="step-icon">📍</div>
                        <div class="step-instruction">Esperando ubicación GPS...</div>
                        <div class="step-distance">Activa tu GPS</div>
                    `;
                }
            } else {
                panel.classList.remove('show');
                btn.classList.remove('active');
            }
        }
        
        function calculateNavigationSteps() {
            if (!myLat || !myLng) return;
            
            const origin = { lat: myLat, lng: myLng };
            const destination = { lat: clientLat, lng: clientLng };
            
            directionsService.route({
                origin: origin,
                destination: destination,
                travelMode: google.maps.TravelMode.DRIVING
            }, function(result, status) {
                if (status === 'OK') {
                    displayNavigationSteps(result);
                } else {
                    document.getElementById('navCurrentStep').innerHTML = `
                        <div class="step-icon">⚠️</div>
                        <div class="step-instruction">No se pudo calcular la ruta</div>
                        <div class="step-distance">Intenta de nuevo</div>
                    `;
                }
            });
        }
        
        function displayNavigationSteps(result) {
            const route = result.routes[0].legs[0];
            const steps = route.steps;
            navigationSteps = steps;
            
            // Mostrar paso actual (primero)
            if (steps.length > 0) {
                updateCurrentStep(0);
            }
            
            // Mostrar lista de pasos
            const listEl = document.getElementById('navStepsList');
            let html = '';
            
            steps.forEach((step, index) => {
                const instruction = step.instructions.replace(/<[^>]*>/g, ''); // Limpiar HTML
                html += `
                    <div class="nav-step-item" onclick="updateCurrentStep(${index})">
                        <div class="step-num">${index + 1}</div>
                        <div class="step-text">${instruction}</div>
                        <div class="step-dist">${step.distance.text}</div>
                    </div>
                `;
            });
            
            // Agregar destino
            html += `
                <div class="nav-step-item">
                    <div class="step-num">🏁</div>
                    <div class="step-text"><strong>Llegaste a tu destino</strong></div>
                    <div class="step-dist">${route.distance.text} total</div>
                </div>
            `;
            
            listEl.innerHTML = html;
        }
        
        function updateCurrentStep(index) {
            if (!navigationSteps[index]) return;
            
            currentStepIndex = index;
            const step = navigationSteps[index];
            const instruction = step.instructions.replace(/<[^>]*>/g, '');
            
            // Icono según la instrucción
            let icon = '➡️';
            const inst = instruction.toLowerCase();
            if (inst.includes('izquierda') || inst.includes('left')) icon = '⬅️';
            else if (inst.includes('derecha') || inst.includes('right')) icon = '➡️';
            else if (inst.includes('continúa') || inst.includes('continue') || inst.includes('sigue')) icon = '⬆️';
            else if (inst.includes('rotonda') || inst.includes('roundabout')) icon = '🔄';
            else if (inst.includes('destino') || inst.includes('destination')) icon = '🏁';
            else if (inst.includes('giro en u') || inst.includes('u-turn')) icon = '↩️';
            
            document.getElementById('navCurrentStep').innerHTML = `
                <div class="step-icon">${icon}</div>
                <div class="step-instruction">${instruction}</div>
                <div class="step-distance">En ${step.distance.text} • Paso ${index + 1} de ${navigationSteps.length}</div>
            `;
        }
        
        // Actualizar navegación cuando cambia la ubicación
        function updateNavigationIfActive() {
            if (navPanelVisible && myLat && myLng) {
                calculateNavigationSteps();
            }
        }
        
        function showNavigationReminder() {
            // Crear toast de recordatorio
            const toast = document.createElement('div');
            toast.innerHTML = `
                <div style="
                    position: fixed;
                    bottom: 100px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: #1e3a5f;
                    color: white;
                    padding: 15px 25px;
                    border-radius: 12px;
                    font-size: 0.9rem;
                    text-align: center;
                    z-index: 99999;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                    max-width: 90%;
                    animation: slideUp 0.3s ease;
                ">
                    <strong>💡 Recuerda</strong><br>
                    Vuelve a esta página para que el cliente<br>vea tu ubicación en tiempo real
                </div>
            `;
            document.body.appendChild(toast);
            
            // Agregar animación
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideUp {
                    from { transform: translateX(-50%) translateY(50px); opacity: 0; }
                    to { transform: translateX(-50%) translateY(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
            
            // Remover después de 5 segundos
            setTimeout(() => toast.remove(), 5000);
        }
        
        function sendLocationToServer(lat, lng) {
            fetch('../api/location/update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ latitude: lat, longitude: lng, order_id: orderId })
            }).catch(err => console.log('Error enviando ubicación:', err));
        }
        
        // Funciones de modales
        function showArrivedModal() { document.getElementById('arrivedModal').classList.add('show'); }
        function showCompleteModal() { document.getElementById('completeModal').classList.add('show'); }
        function showCancelModal() { document.getElementById('cancelModal').classList.add('show'); }
        function closeModal(id) { document.getElementById(id).classList.remove('show'); }
        
        // Acciones
        function goInTransit() {
            updateOrderStatus('in-transit', '🚗 Marcando en camino...');
        }
        
        function startCleaning() {
            closeModal('arrivedModal');
            updateOrderStatus('start', '🧹 Iniciando limpieza...');
        }
        
        function confirmComplete() {
            closeModal('completeModal');
            updateOrderStatus('complete', '🎉 Completando...');
        }
        
        function confirmCancel() {
            closeModal('cancelModal');
            
            fetch('../api/orders/cancel-partner.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Servicio cancelado.');
                    window.location.href = 'index.php';
                } else {
                    alert(data.message || 'Error al cancelar');
                }
            });
        }
        
        function updateOrderStatus(action, loadingText) {
            const buttons = document.querySelectorAll('.btn-action');
            buttons.forEach(btn => {
                btn.disabled = true;
                if (btn.classList.contains('btn-green') || btn.classList.contains('btn-blue')) {
                    btn.innerHTML = loadingText || '⏳ Procesando...';
                }
            });
            
            fetch('../api/orders/' + action + '.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Reproducir sonido de éxito
                    playNotificationSound();
                    
                    if (action === 'complete') {
                        window.location.href = 'index.php?completed=1';
                    } else {
                        location.reload();
                    }
                } else {
                    alert(data.message || 'Error');
                    location.reload();
                }
            })
            .catch(err => {
                alert('Error de conexión');
                location.reload();
            });
        }
        
        // Limpiar GPS al salir
        window.addEventListener('beforeunload', function() {
            if (watchId) navigator.geolocation.clearWatch(watchId);
        });
        
        // Listener para cambios de estado
        window.addEventListener('orderStatusChanged', function(e) {
            console.log('Estado cambiado a:', e.detail.status);
            // Reproducir sonido cuando cambia el estado (ej: cliente pagó)
            playNotificationSound();
        });
        
        // Polling para detectar cambios de estado de la orden
        let pollingOrderStatus = orderStatus;
        
        function pollOrderStatus() {
            fetch('../api/orders/status.php?order_id=' + orderId)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const newStatus = data.status;
                        
                        if (newStatus !== pollingOrderStatus) {
                            playNotificationSound();
                            pollingOrderStatus = newStatus;
                            
                            // Disparar evento de cambio de estado
                            window.dispatchEvent(new CustomEvent('orderStatusChanged', { 
                                detail: { status: newStatus } 
                            }));
                            
                            // Redirigir según el nuevo estado
                            if (newStatus === 'completed') {
                                window.location.href = 'index.php?completed=1';
                            } else if (newStatus === 'cancelled') {
                                window.location.href = 'index.php';
                            } else {
                                // Actualizar UI si es necesario
                                location.reload();
                            }
                        }
                    }
                })
                .catch(err => console.log('Error polling order status:', err));
        }
        
        setInterval(pollOrderStatus, 5000);
    </script>
</body>
</html>