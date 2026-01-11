<?php
/**
 * INGClean - Tracking en Tiempo Real
 */
require_once '../includes/init.php';

auth()->requireLogin(['client']);

$user = auth()->getCurrentUser();
$db = Database::getInstance();

$orderId = get('order');

if (!$orderId) {
    redirect('/client/');
}

// Obtener orden con datos del partner
$order = $db->fetchOne(
    "SELECT o.*, s.name as service_name, s.price, 
            p.name as partner_name, p.phone as partner_phone, p.photo as partner_photo,
            p.latitude as partner_lat, p.longitude as partner_lng
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     LEFT JOIN partners p ON o.partner_id = p.id
     WHERE o.id = :order_id AND o.client_id = :client_id",
    ['order_id' => $orderId, 'client_id' => $user['id']]
);

if (!$order) {
    setFlash('error', 'Orden no encontrada');
    redirect('/client/');
}

// Si la orden está completada o cancelada, redirigir
if (in_array($order['status'], ['completed', 'cancelled'])) {
    redirect('/client/');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f9ff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header */
        .header {
            background: white;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 100;
        }
        
        .back-btn {
            background: #f1f5f9;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #64748b;
            text-decoration: none;
        }
        
        .header-info {
            flex: 1;
        }
        
        .header-info h1 {
            font-size: 1rem;
            color: #1e3a5f;
        }
        
        .header-info p {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .order-status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-paid, .status-in_transit {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .status-in_progress {
            background: #dcfce7;
            color: #16a34a;
        }
        
        /* Map Container */
        .map-container {
            flex: 1;
            position: relative;
            min-height: 400px;
        }
        
        #map {
            width: 100%;
            height: 100%;
            min-height: 400px;
        }
        
        /* Map Placeholder (si no hay API key) */
        .map-placeholder {
            width: 100%;
            height: 100%;
            min-height: 400px;
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 20px;
            padding: 20px;
        }
        
        .map-placeholder-icon {
            font-size: 4rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .map-placeholder h3 {
            color: #0077b6;
            font-size: 1.2rem;
        }
        
        .map-placeholder p {
            color: #64748b;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .coordinates-display {
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .coordinates-display p {
            font-size: 0.85rem;
            margin: 5px 0;
        }
        
        .coordinates-display strong {
            color: #0077b6;
        }
        
        /* ETA Card */
        .eta-card {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            background: white;
            border-radius: 16px;
            padding: 15px 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 10;
        }
        
        .eta-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .eta-info h3 {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 400;
        }
        
        .eta-time {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e3a5f;
        }
        
        .eta-distance {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        /* Bottom Panel */
        .bottom-panel {
            background: white;
            border-radius: 24px 24px 0 0;
            padding: 25px 20px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
        }
        
        .partner-section {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .partner-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            overflow: hidden;
        }
        
        .partner-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .partner-details {
            flex: 1;
        }
        
        .partner-details h3 {
            font-size: 1.1rem;
            color: #1e3a5f;
            margin-bottom: 3px;
        }
        
        .partner-details p {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .partner-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s;
        }
        
        .btn-call {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .btn-message {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .btn-action:hover {
            transform: scale(1.1);
        }
        
        /* Order Summary */
        .order-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-service h4 {
            font-size: 0.95rem;
            color: #1e3a5f;
            margin-bottom: 3px;
        }
        
        .order-service p {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .order-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0077b6;
        }
        
        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }
        
        .progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 60%;
            width: 80%;
            height: 3px;
            background: #e2e8f0;
        }
        
        .progress-step.completed:not(:last-child)::after {
            background: #0077b6;
        }
        
        .step-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            z-index: 1;
            margin-bottom: 8px;
        }
        
        .progress-step.completed .step-icon {
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
        }
        
        .progress-step.active .step-icon {
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            box-shadow: 0 0 0 4px rgba(0, 180, 216, 0.3);
            animation: pulse-ring 2s infinite;
        }
        
        @keyframes pulse-ring {
            0%, 100% { box-shadow: 0 0 0 4px rgba(0, 180, 216, 0.3); }
            50% { box-shadow: 0 0 0 8px rgba(0, 180, 216, 0.1); }
        }
        
        .step-label {
            font-size: 0.7rem;
            color: #64748b;
            text-align: center;
        }
        
        .progress-step.completed .step-label,
        .progress-step.active .step-label {
            color: #0077b6;
            font-weight: 500;
        }
        
        @media (max-width: 480px) {
            .eta-card {
                left: 10px;
                right: 10px;
            }
            
            .step-label {
                font-size: 0.65rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <a href="index.php" class="back-btn">←</a>
        <div class="header-info">
            <h1><?= e($order['service_name']) ?></h1>
            <p><?= e($order['order_code']) ?></p>
        </div>
        <span class="order-status-badge status-<?= $order['status'] ?>">
            <?php
            $statusLabels = [
                'paid' => 'Pagado',
                'in_transit' => 'En Camino',
                'in_progress' => 'En Progreso'
            ];
            echo $statusLabels[$order['status']] ?? $order['status'];
            ?>
        </span>
    </header>
    
    <!-- Map -->
    <div class="map-container">
        <?php
        $hasApiKey = defined('GOOGLE_MAPS_API_KEY') && GOOGLE_MAPS_API_KEY !== 'AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
        
        if ($hasApiKey):
        ?>
            <div id="map"></div>
            
            <!-- ETA Card -->
            <div class="eta-card">
                <div class="eta-icon">🚗</div>
                <div class="eta-info">
                    <h3><?= $order['status'] === 'in_progress' ? 'Servicio en curso' : 'Tiempo estimado de llegada' ?></h3>
                    <div class="eta-time" id="etaTime">
                        <?= $order['status'] === 'in_progress' ? 'Limpiando...' : 'Calculando...' ?>
                    </div>
                    <div class="eta-distance" id="etaDistance"></div>
                </div>
            </div>
        <?php else: ?>
            <!-- Placeholder sin API key -->
            <div class="map-placeholder">
                <div class="map-placeholder-icon">
                    <?= $order['status'] === 'in_progress' ? '🧹' : '🚗' ?>
                </div>
                <h3>
                    <?= $order['status'] === 'in_progress' ? 'Servicio en Progreso' : 'Partner en Camino' ?>
                </h3>
                <p>
                    <?= $order['status'] === 'in_progress' 
                        ? 'Tu partner está realizando la limpieza' 
                        : 'Tu partner se dirige a tu ubicación' 
                    ?>
                </p>
                
                <div class="coordinates-display">
                    <p><strong>Tu ubicación:</strong> <?= $order['client_latitude'] ?>, <?= $order['client_longitude'] ?></p>
                    <?php if ($order['partner_lat'] && $order['partner_lng']): ?>
                        <p><strong>Partner:</strong> <?= $order['partner_lat'] ?>, <?= $order['partner_lng'] ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Bottom Panel -->
    <div class="bottom-panel">
        <!-- Partner Info -->
        <?php if ($order['partner_name']): ?>
            <div class="partner-section">
                <div class="partner-photo">
                    <?php if ($order['partner_photo']): ?>
                        <img src="../<?= e($order['partner_photo']) ?>" alt="Partner">
                    <?php else: ?>
                        👤
                    <?php endif; ?>
                </div>
                <div class="partner-details">
                    <h3><?= e($order['partner_name']) ?></h3>
                    <p>Tu profesional de limpieza</p>
                </div>
                <div class="partner-actions">
                    <?php if ($order['partner_phone']): ?>
                        <a href="tel:<?= e($order['partner_phone']) ?>" class="btn-action btn-call">📞</a>
                    <?php endif; ?>
                    <button class="btn-action btn-message">💬</button>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Order Summary -->
        <div class="order-summary">
            <div class="order-service">
                <h4><?= e($order['service_name']) ?></h4>
                <p><?= e($order['client_address']) ?></p>
            </div>
            <div class="order-price">$<?= number_format($order['price'], 2) ?></div>
        </div>
        
        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="progress-step completed">
                <div class="step-icon">✓</div>
                <span class="step-label">Solicitado</span>
            </div>
            <div class="progress-step completed">
                <div class="step-icon">✓</div>
                <span class="step-label">Aceptado</span>
            </div>
            <div class="progress-step completed">
                <div class="step-icon">✓</div>
                <span class="step-label">Pagado</span>
            </div>
            <div class="progress-step <?= in_array($order['status'], ['in_transit', 'in_progress']) ? 'completed' : '' ?> <?= $order['status'] === 'in_transit' ? 'active' : '' ?>">
                <div class="step-icon"><?= in_array($order['status'], ['in_transit', 'in_progress']) ? '✓' : '🚗' ?></div>
                <span class="step-label">En Camino</span>
            </div>
            <div class="progress-step <?= $order['status'] === 'in_progress' ? 'active' : '' ?>">
                <div class="step-icon">🧹</div>
                <span class="step-label">Limpiando</span>
            </div>
        </div>
    </div>
    
    <?php if ($hasApiKey): ?>
    <!-- Google Maps Script -->
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=geometry"></script>
    <script>
        let map, partnerMarker, clientMarker, directionsRenderer;
        const clientLat = <?= $order['client_latitude'] ?>;
        const clientLng = <?= $order['client_longitude'] ?>;
        let partnerLat = <?= $order['partner_lat'] ?? 'null' ?>;
        let partnerLng = <?= $order['partner_lng'] ?? 'null' ?>;
        
        function initMap() {
            // Crear mapa centrado en el cliente
            map = new google.maps.Map(document.getElementById('map'), {
                center: { lat: clientLat, lng: clientLng },
                zoom: 15,
                disableDefaultUI: true,
                zoomControl: true,
                styles: [
                    {
                        featureType: "poi",
                        elementType: "labels",
                        stylers: [{ visibility: "off" }]
                    }
                ]
            });
            
            // Marcador del cliente (destino)
            clientMarker = new google.maps.Marker({
                position: { lat: clientLat, lng: clientLng },
                map: map,
                title: 'Tu ubicación',
                icon: {
                    url: 'data:image/svg+xml,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40">
                            <circle cx="20" cy="20" r="18" fill="#0077b6" stroke="white" stroke-width="3"/>
                            <text x="20" y="26" text-anchor="middle" fill="white" font-size="16">🏠</text>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(40, 40)
                }
            });
            
            // Directions renderer para la ruta
            directionsRenderer = new google.maps.DirectionsRenderer({
                map: map,
                suppressMarkers: true,
                polylineOptions: {
                    strokeColor: '#0077b6',
                    strokeWeight: 5,
                    strokeOpacity: 0.8
                }
            });
            
            // Si hay ubicación del partner, mostrarla
            if (partnerLat && partnerLng) {
                updatePartnerLocation(partnerLat, partnerLng);
            }
            
            // Actualizar ubicación cada 5 segundos
            setInterval(fetchPartnerLocation, 5000);
        }
        
        function updatePartnerLocation(lat, lng) {
            const position = { lat: parseFloat(lat), lng: parseFloat(lng) };
            
            if (!partnerMarker) {
                partnerMarker = new google.maps.Marker({
                    position: position,
                    map: map,
                    title: 'Partner',
                    icon: {
                        url: 'data:image/svg+xml,' + encodeURIComponent(`
                            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50">
                                <circle cx="25" cy="25" r="22" fill="#00b4d8" stroke="white" stroke-width="3"/>
                                <text x="25" y="32" text-anchor="middle" fill="white" font-size="20">🚗</text>
                            </svg>
                        `),
                        scaledSize: new google.maps.Size(50, 50),
                        anchor: new google.maps.Point(25, 25)
                    },
                    zIndex: 100
                });
                
                // Calcular y mostrar ruta
                calculateRoute(position);
            } else {
                // Animar movimiento del marcador
                animateMarker(partnerMarker, partnerMarker.getPosition(), position);
            }
            
            partnerLat = lat;
            partnerLng = lng;
        }
        
        function animateMarker(marker, start, end) {
            const frames = 30;
            let frame = 0;
            
            const deltaLat = (end.lat - start.lat()) / frames;
            const deltaLng = (end.lng - start.lng()) / frames;
            
            const interval = setInterval(() => {
                frame++;
                const lat = start.lat() + deltaLat * frame;
                const lng = start.lng() + deltaLng * frame;
                marker.setPosition({ lat, lng });
                
                if (frame >= frames) {
                    clearInterval(interval);
                    calculateRoute({ lat: end.lat, lng: end.lng });
                }
            }, 50);
        }
        
        function calculateRoute(origin) {
            const directionsService = new google.maps.DirectionsService();
            
            directionsService.route({
                origin: origin,
                destination: { lat: clientLat, lng: clientLng },
                travelMode: google.maps.TravelMode.DRIVING
            }, (result, status) => {
                if (status === 'OK') {
                    directionsRenderer.setDirections(result);
                    
                    const route = result.routes[0].legs[0];
                    document.getElementById('etaTime').textContent = route.duration.text;
                    document.getElementById('etaDistance').textContent = route.distance.text;
                    
                    // Ajustar zoom para ver ambos puntos
                    const bounds = new google.maps.LatLngBounds();
                    bounds.extend(origin);
                    bounds.extend({ lat: clientLat, lng: clientLng });
                    map.fitBounds(bounds, { padding: 100 });
                }
            });
        }
        
        function fetchPartnerLocation() {
            fetch('../api/location/get-partner.php?order=<?= $order['id'] ?>')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.data.latitude && data.data.longitude) {
                        updatePartnerLocation(data.data.latitude, data.data.longitude);
                    }
                    
                    // Verificar si el estado cambió
                    if (data.data.order_status && data.data.order_status !== '<?= $order['status'] ?>') {
                        location.reload();
                    }
                })
                .catch(err => console.log('Error fetching location:', err));
        }
        
        // Inicializar mapa cuando cargue la página
        window.onload = initMap;
    </script>
    <?php else: ?>
    <script>
        // Sin API key, solo actualizar estado
        setInterval(function() {
            fetch('../api/orders/status.php?order=<?= $order['id'] ?>')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.data.status !== '<?= $order['status'] ?>') {
                        location.reload();
                    }
                });
        }, 10000);
    </script>
    <?php endif; ?>
</body>
</html>
