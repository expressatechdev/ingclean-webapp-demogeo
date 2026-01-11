<?php
/**
 * INGClean - Tracking del Cliente
 * Mapa GRANDE - Ocupa toda la pantalla
 */
require_once '../includes/init.php';

auth()->requireLogin(['client']);

$user = auth()->getCurrentUser();
$db = Database::getInstance();

$orderId = get('order');

if (!$orderId) {
    redirect('/client/');
}

$order = $db->fetchOne(
    "SELECT o.*, s.name as service_name, s.price,
            p.name as partner_name, p.phone as partner_phone, p.photo as partner_photo,
            p.latitude as partner_lat, p.longitude as partner_lng,
            c.latitude as client_latitude, c.longitude as client_longitude, c.address as client_address
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     JOIN clients c ON o.client_id = c.id
     LEFT JOIN partners p ON o.partner_id = p.id
     WHERE o.id = :order_id AND o.client_id = :client_id
     AND o.status NOT IN ('completed', 'cancelled')",
    ['order_id' => $orderId, 'client_id' => $user['id']]
);

if (!$order) {
    setFlash('error', 'Orden no encontrada');
    redirect('/client/');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script src="/assets/js/capacitor-push.js" defer></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#0077b6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    
    <title>Seguimiento - <?= APP_NAME ?></title>
    
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
            background: #f0f9ff;
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh;
        }
        
        /* ===== HEADER COMPACTO ===== */
        .header {
            background: white;
            padding: 10px 16px;
            padding-top: max(10px, env(safe-area-inset-top));
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 100;
            flex-shrink: 0;
        }
        
        .back-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f1f5f9;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #64748b;
            text-decoration: none;
            flex-shrink: 0;
        }
        
        .header-info {
            flex: 1;
            min-width: 0;
        }
        
        .header-info h1 {
            font-size: 1.1rem;
            color: #1e3a5f;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .header-info p {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .status-paid { background: #dbeafe; color: #1d4ed8; }
        .status-in_transit { background: #dcfce7; color: #16a34a; }
        .status-in_progress { background: #fef3c7; color: #92400e; }
        
        /* ===== MAPA - OCUPA TODO EL ESPACIO DISPONIBLE ===== */
        .map-container {
            flex: 1;
            position: relative;
            min-height: 0;
        }
        
        #map {
            width: 100%;
            height: 100%;
        }
        
        /* ETA Card flotante en el mapa */
        .eta-card {
            position: absolute;
            top: 16px;
            left: 16px;
            right: 16px;
            background: white;
            border-radius: 16px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            z-index: 10;
        }
        
        .eta-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .eta-info {
            flex: 1;
        }
        
        .eta-info h3 {
            font-size: 1.3rem;
            color: #1e3a5f;
            font-weight: 700;
        }
        
        .eta-info p {
            font-size: 1rem;
            color: #64748b;
        }
        
        /* Botón recentrar */
        .btn-recenter {
            position: absolute;
            bottom: 20px;
            right: 16px;
            width: 50px;
            height: 50px;
            background: white;
            border: none;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            cursor: pointer;
            font-size: 1.4rem;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* ===== PANEL INFERIOR COMPACTO ===== */
        .bottom-panel {
            background: white;
            border-radius: 24px 24px 0 0;
            padding: 20px;
            padding-bottom: max(20px, env(safe-area-inset-bottom));
            box-shadow: 0 -4px 30px rgba(0,0,0,0.15);
            flex-shrink: 0;
            z-index: 50;
        }
        
        /* Partner info compacto */
        .partner-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 16px;
        }
        
        .partner-photo {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: white;
            font-weight: 700;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .partner-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .partner-details {
            flex: 1;
            min-width: 0;
        }
        
        .partner-details h3 {
            font-size: 1.15rem;
            color: #1e3a5f;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .partner-details p {
            font-size: 0.95rem;
            color: #64748b;
        }
        
        .partner-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        
        .btn-call {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .btn-message {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        /* Progress steps horizontal compacto */
        .progress-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-top: 1px solid #f1f5f9;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            position: relative;
            flex: 1;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 14px;
            left: calc(50% + 16px);
            width: calc(100% - 32px);
            height: 3px;
            background: #e2e8f0;
        }
        
        .step.completed:not(:last-child)::after {
            background: #22c55e;
        }
        
        .step-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            position: relative;
            z-index: 1;
        }
        
        .step.completed .step-icon {
            background: #22c55e;
            color: white;
        }
        
        .step.active .step-icon {
            background: #0077b6;
            color: white;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(0, 119, 182, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(0, 119, 182, 0); }
        }
        
        .step-label {
            font-size: 0.75rem;
            color: #64748b;
            text-align: center;
        }
        
        .step.completed .step-label,
        .step.active .step-label {
            color: #1e3a5f;
            font-weight: 600;
        }
        
        /* Info row compacta */
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 1rem;
        }
        
        .info-row .label {
            color: #64748b;
        }
        
        .info-row .value {
            color: #1e3a5f;
            font-weight: 600;
        }
        
        /* Botón cancelar */
        .btn-cancel {
            width: 100%;
            padding: 14px;
            min-height: 52px;
            background: #fee2e2;
            color: #dc2626;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            margin-top: 12px;
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
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
            border-radius: 24px;
            padding: 28px;
            max-width: 360px;
            width: 100%;
            text-align: center;
        }
        
        .modal-icon {
            font-size: 3.5rem;
            margin-bottom: 16px;
        }
        
        .modal-content h2 {
            font-size: 1.3rem;
            color: #1e3a5f;
            margin-bottom: 12px;
        }
        
        .modal-content p {
            font-size: 1rem;
            color: #64748b;
            margin-bottom: 8px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 14px;
            min-height: 52px;
            border-radius: 14px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            font-family: inherit;
            border: none;
        }
        
        .btn-modal-back {
            background: #f1f5f9;
            color: #64748b;
        }
        
        .btn-modal-confirm {
            background: #dc2626;
            color: white;
        }
        
        /* Desktop - hacer el panel más ancho pero el mapa sigue siendo grande */
        @media (min-width: 768px) {
            .bottom-panel {
                max-width: 500px;
                margin: 0 auto;
                border-radius: 24px 24px 0 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header Compacto -->
    <header class="header">
        <a href="index.php" class="back-btn">←</a>
        <div class="header-info">
            <h1>📍 Seguimiento en Vivo</h1>
            <p><?= e($order['order_code'] ?? 'ORD-'.$order['id']) ?></p>
        </div>
        <span class="status-badge status-<?= $order['status'] ?>">
            <?php
            $statusLabels = [
                'paid' => '💳 Pagado',
                'in_transit' => '🚗 En camino',
                'in_progress' => '🧹 Limpiando'
            ];
            echo $statusLabels[$order['status']] ?? $order['status'];
            ?>
        </span>
    </header>
    
    <!-- Mapa GRANDE -->
    <div class="map-container">
        <div id="map"></div>
        
        <!-- ETA flotante simplificado -->
        <div class="eta-card">
            <div class="eta-icon">
                <?php if ($order['status'] === 'in_transit'): ?>
                    🚗
                <?php elseif ($order['status'] === 'in_progress'): ?>
                    🧹
                <?php else: ?>
                    📍
                <?php endif; ?>
            </div>
            <div class="eta-info">
                <h3 id="etaTime">Calculando...</h3>
                <p id="etaDistance">Obteniendo ubicación del partner</p>
            </div>
        </div>
        
        <!-- Botón recentrar -->
        <button class="btn-recenter" onclick="recenterMap()" title="Ver ruta completa">🎯</button>
    </div>
    
    <!-- Panel Inferior Compacto -->
    <div class="bottom-panel">
        <!-- Partner Info -->
        <?php if (!empty($order['partner_name'])): ?>
            <div class="partner-row">
                <div class="partner-photo">
                    <?php if (!empty($order['partner_photo'])): ?>
                        <img src="../<?= e($order['partner_photo']) ?>" alt="">
                    <?php else: ?>
                        <?= strtoupper(substr($order['partner_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div class="partner-details">
                    <h3><?= e($order['partner_name']) ?></h3>
                    <p>Tu profesional de limpieza</p>
                </div>
                <div class="partner-actions">
                    <a href="tel:<?= e($order['partner_phone'] ?? '') ?>" class="btn-icon btn-call">📞</a>
                    <button class="btn-icon btn-message">💬</button>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Progress Steps -->
        <div class="progress-row">
            <div class="step <?= in_array($order['status'], ['paid', 'in_transit', 'in_progress']) ? 'completed' : '' ?>">
                <div class="step-icon">💳</div>
                <span class="step-label">Pagado</span>
            </div>
            <div class="step <?= $order['status'] === 'in_transit' ? 'active' : (in_array($order['status'], ['in_progress']) ? 'completed' : '') ?>">
                <div class="step-icon">🚗</div>
                <span class="step-label">En Camino</span>
            </div>
            <div class="step <?= $order['status'] === 'in_progress' ? 'active' : '' ?>">
                <div class="step-icon">🧹</div>
                <span class="step-label">Limpiando</span>
            </div>
            <div class="step">
                <div class="step-icon">✓</div>
                <span class="step-label">Listo</span>
            </div>
        </div>
        
        <!-- Info compacta -->
        <div class="info-row">
            <span class="label">Servicio</span>
            <span class="value"><?= e($order['service_name'] ?? '') ?></span>
        </div>
        <div class="info-row">
            <span class="label">Total</span>
            <span class="value" style="color: #16a34a;">$<?= number_format($order['price'] ?? 0, 2) ?></span>
        </div>
        
        <?php if (in_array($order['status'], ['paid', 'in_transit'])): ?>
            <button class="btn-cancel" onclick="showCancelModal()">❌ Cancelar Servicio</button>
        <?php endif; ?>
    </div>
    
    <!-- Cancel Modal -->
    <div class="modal-overlay" id="cancelModal">
        <div class="modal-content">
            <div class="modal-icon">⚠️</div>
            <h2>¿Cancelar el servicio?</h2>
            <p>Para reembolso contacta a:</p>
            <p style="font-size: 0.9rem; color: #0077b6;">📧 finanzas@ingclean.com</p>
            <div class="modal-buttons">
                <button class="btn-modal-back" onclick="closeCancelModal()">Volver</button>
                <button class="btn-modal-confirm" onclick="confirmCancel()">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&callback=initMap" async defer></script>
    
    <script>
        let map, clientMarker, partnerMarker, directionsRenderer, directionsService;
        const orderId = <?= $order['id'] ?>;
        const clientLat = <?= $order['client_latitude'] ?? 0 ?>;
        const clientLng = <?= $order['client_longitude'] ?? 0 ?>;
        let currentPartnerLat = <?= !empty($order['partner_lat']) ? $order['partner_lat'] : 'null' ?>;
        let currentPartnerLng = <?= !empty($order['partner_lng']) ? $order['partner_lng'] : 'null' ?>;
        let hasInitialFit = false;
        
        // Wake Lock - Mantener pantalla encendida
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
        
        function initMap() {
            const clientPos = { lat: clientLat, lng: clientLng };
            
            map = new google.maps.Map(document.getElementById('map'), {
                center: clientPos,
                zoom: 15,
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
            
            // Marcador del cliente (casa)
            clientMarker = new google.maps.Marker({
                position: clientPos,
                map: map,
                title: 'Tu ubicación',
                icon: {
                    url: 'data:image/svg+xml,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56">
                            <circle cx="28" cy="28" r="24" fill="#0077b6" stroke="white" stroke-width="4"/>
                            <text x="28" y="35" text-anchor="middle" fill="white" font-size="22">🏠</text>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(56, 56),
                    anchor: new google.maps.Point(28, 28)
                }
            });
            
            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                map: map,
                suppressMarkers: true,
                polylineOptions: {
                    strokeColor: '#0077b6',
                    strokeWeight: 6,
                    strokeOpacity: 0.9
                }
            });
            
            if (currentPartnerLat && currentPartnerLng) {
                updatePartnerLocation(currentPartnerLat, currentPartnerLng);
            }
            
            startTracking();
        }
        
        function updatePartnerLocation(lat, lng) {
            const partnerPos = { lat: parseFloat(lat), lng: parseFloat(lng) };
            
            if (!partnerMarker) {
                partnerMarker = new google.maps.Marker({
                    position: partnerPos,
                    map: map,
                    title: 'Partner en camino',
                    icon: {
                        url: 'data:image/svg+xml,' + encodeURIComponent(`
                            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56">
                                <circle cx="28" cy="28" r="24" fill="#22c55e" stroke="white" stroke-width="4"/>
                                <text x="28" y="35" text-anchor="middle" fill="white" font-size="20">🚗</text>
                            </svg>
                        `),
                        scaledSize: new google.maps.Size(56, 56),
                        anchor: new google.maps.Point(28, 28)
                    }
                });
                
                if (!hasInitialFit) {
                    recenterMap();
                    hasInitialFit = true;
                }
            } else {
                partnerMarker.setPosition(partnerPos);
            }
            
            calculateRoute(partnerPos);
        }
        
        function calculateRoute(partnerPos) {
            directionsService.route({
                origin: partnerPos,
                destination: { lat: clientLat, lng: clientLng },
                travelMode: google.maps.TravelMode.DRIVING
            }, function(result, status) {
                if (status === 'OK') {
                    directionsRenderer.setDirections(result);
                    const route = result.routes[0].legs[0];
                    document.getElementById('etaTime').textContent = '⏱️ ' + route.duration.text;
                    document.getElementById('etaDistance').textContent = '📍 ' + route.distance.text;
                } else {
                    // Si no hay ruta por carretera, calcular distancia aproximada
                    console.log('Directions API error:', status);
                    showApproximateDistance(partnerPos);
                }
            });
        }
        
        // Calcular distancia aproximada cuando no hay ruta por carretera
        function showApproximateDistance(partnerPos) {
            const dist = haversineDistance(partnerPos.lat, partnerPos.lng, clientLat, clientLng);
            
            if (dist > 100) {
                // Más de 100km - probablemente en diferente ciudad/país
                document.getElementById('etaTime').textContent = '📍 ~' + dist.toFixed(1) + ' km';
                document.getElementById('etaDistance').textContent = 'Distancia aproximada (sin ruta)';
            } else {
                // Menos de 100km - calcular tiempo aproximado
                const timeMin = Math.round(dist / 0.5); // ~30 km/h promedio ciudad
                document.getElementById('etaTime').textContent = '⏱️ ~' + timeMin + ' min';
                document.getElementById('etaDistance').textContent = '📍 ~' + dist.toFixed(1) + ' km aprox.';
            }
            
            // Dibujar línea recta en el mapa
            if (window.directLine) window.directLine.setMap(null);
            window.directLine = new google.maps.Polyline({
                path: [partnerPos, { lat: clientLat, lng: clientLng }],
                geodesic: true,
                strokeColor: '#22c55e',
                strokeOpacity: 0.8,
                strokeWeight: 4,
                map: map
            });
        }
        
        // Fórmula Haversine para distancia entre dos puntos
        function haversineDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radio de la Tierra en km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }
        
        function recenterMap() {
            if (!clientMarker) return;
            const bounds = new google.maps.LatLngBounds();
            bounds.extend(clientMarker.getPosition());
            if (partnerMarker) bounds.extend(partnerMarker.getPosition());
            map.fitBounds(bounds, { padding: 80 });
        }
        
        // Sonido de notificación
        const notificationSound = new Audio('/assets/sounds/notification.mp3');
        let audioUnlocked = false;
        let lastOrderStatus = '<?= $order['status'] ?>';
        
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
        
        function startTracking() {
            setInterval(fetchPartnerLocation, 5000);
        }
        
        function fetchPartnerLocation() {
            fetch('../api/location/get-partner.php?order=' + orderId)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.data) {
                        const { latitude, longitude, order_status } = data.data;
                        
                        // Detectar cambio de estado y reproducir sonido
                        if (order_status !== lastOrderStatus) {
                            playNotificationSound();
                            lastOrderStatus = order_status;
                            
                            // Actualizar badge de estado en el header
                            const statusLabels = {
                                'paid': '💳 Pagado',
                                'in_transit': '🚗 En camino',
                                'in_progress': '🧹 Limpiando'
                            };
                            const badge = document.querySelector('.status-badge');
                            if (badge && statusLabels[order_status]) {
                                badge.textContent = statusLabels[order_status];
                                badge.className = 'status-badge status-' + order_status;
                            }
                        }
                        
                        if (order_status === 'completed' || order_status === 'cancelled') {
                            playNotificationSound();
                            setTimeout(() => window.location.href = 'index.php', 1000);
                            return;
                        }
                        if (latitude && longitude) {
                            updatePartnerLocation(latitude, longitude);
                        }
                    }
                })
                .catch(err => console.log('Error:', err));
        }
        
        function showCancelModal() {
            document.getElementById('cancelModal').classList.add('show');
        }
        
        function closeCancelModal() {
            document.getElementById('cancelModal').classList.remove('show');
        }
        
        function confirmCancel() {
            closeCancelModal();
            fetch('../api/orders/cancel-client.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Servicio cancelado. Contacta a finanzas@ingclean.com');
                    window.location.href = 'index.php';
                } else {
                    alert(data.message || 'Error');
                }
            });
        }
        
        // Listener para actualizaciones de ubicación del partner
        window.addEventListener('partnerLocationUpdate', function(e) {
            if (e.detail.lat && e.detail.lng) {
                updatePartnerLocation(e.detail.lat, e.detail.lng);
            }
        });
    </script>
</body>
</html>