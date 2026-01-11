<?php
/**
 * INGClean - Servicio Activo del Partner
 */
require_once '../includes/init.php';

auth()->requireLogin(['partner']);

$user = auth()->getCurrentUser();
$db = Database::getInstance();

$orderId = get('order');

if (!$orderId) {
    redirect('/partner/');
}

// Obtener orden activa
$order = $db->fetchOne(
    "SELECT o.*, s.name as service_name, s.price, s.description as service_description,
            c.name as client_name, c.phone as client_phone, c.email as client_email,
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicio Activo - <?= APP_NAME ?></title>
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
        
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-accepted { background: #fef3c7; color: #92400e; }
        .status-paid { background: #dbeafe; color: #1d4ed8; }
        .status-in_transit { background: #cffafe; color: #0891b2; }
        .status-in_progress { background: #dcfce7; color: #16a34a; }
        
        /* Map */
        .map-container {
            flex: 1;
            min-height: 350px;
            position: relative;
        }
        
        #map {
            width: 100%;
            height: 100%;
        }
        
        .map-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 20px;
        }
        
        .map-placeholder-icon {
            font-size: 4rem;
        }
        
        .btn-open-maps {
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Bottom Panel */
        .bottom-panel {
            background: white;
            border-radius: 24px 24px 0 0;
            padding: 25px 20px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
        }
        
        /* Client Section */
        .client-section {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .client-avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .client-details {
            flex: 1;
        }
        
        .client-details h3 {
            font-size: 1.1rem;
            color: #1e3a5f;
            margin-bottom: 3px;
        }
        
        .client-details p {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .client-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            text-decoration: none;
        }
        
        .btn-call { background: #dcfce7; color: #16a34a; }
        .btn-message { background: #dbeafe; color: #1d4ed8; }
        
        /* Service Info */
        .service-info {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .service-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        
        .service-row .label {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .service-row .value {
            color: #1e3a5f;
            font-weight: 500;
        }
        
        .service-row.earnings .value {
            color: #16a34a;
            font-size: 1.2rem;
            font-weight: 700;
        }
        
        /* Address */
        .address-box {
            background: #eff6ff;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: start;
            gap: 10px;
        }
        
        .address-box .icon {
            font-size: 1.5rem;
        }
        
        .address-box .text {
            flex: 1;
        }
        
        .address-box h4 {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 3px;
        }
        
        .address-box p {
            font-size: 0.95rem;
            color: #1e3a5f;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .btn-primary-action {
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-navigate {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
        
        .btn-arrived {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .btn-start {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
        }
        
        .btn-complete {
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
        }
        
        .btn-primary-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .btn-primary-action:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-secondary-action {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #64748b;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
        }
        
        /* Notes */
        .notes-box {
            background: #fef3c7;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .notes-box h4 {
            font-size: 0.85rem;
            color: #92400e;
            margin-bottom: 5px;
        }
        
        .notes-box p {
            font-size: 0.9rem;
            color: #78350f;
        }
        
        /* Confirmation Modal */
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
            padding: 30px;
            max-width: 350px;
            width: 100%;
            text-align: center;
        }
        
        .modal-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        
        .modal-content h2 {
            font-size: 1.3rem;
            color: #1e3a5f;
            margin-bottom: 10px;
        }
        
        .modal-content p {
            color: #64748b;
            margin-bottom: 20px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        
        .btn-modal-cancel {
            background: #f1f5f9;
            border: none;
            color: #64748b;
        }
        
        .btn-modal-confirm {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border: none;
            color: white;
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
        <span class="status-badge status-<?= $order['status'] ?>">
            <?php
            $statusLabels = [
                'accepted' => '⏳ Esperando pago',
                'paid' => '💳 Pagado',
                'in_transit' => '🚗 En camino',
                'in_progress' => '🧹 En progreso'
            ];
            echo $statusLabels[$order['status']] ?? $order['status'];
            ?>
        </span>
    </header>
    
    <!-- Map -->
    <div class="map-container">
        <?php
        $hasApiKey = defined('GOOGLE_MAPS_API_KEY') && GOOGLE_MAPS_API_KEY !== 'AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
        $mapsUrl = "https://www.google.com/maps/dir/?api=1&destination={$order['client_lat']},{$order['client_lng']}&travelmode=driving";
        ?>
        
        <?php if ($hasApiKey): ?>
            <div id="map"></div>
        <?php else: ?>
            <div class="map-placeholder">
                <div class="map-placeholder-icon">🗺️</div>
                <p style="color: #64748b; text-align: center;">
                    Navega hacia el cliente usando Google Maps
                </p>
                <a href="<?= $mapsUrl ?>" target="_blank" class="btn-open-maps">
                    🧭 Abrir en Google Maps
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Bottom Panel -->
    <div class="bottom-panel">
        <!-- Client Info -->
        <div class="client-section">
            <div class="client-avatar">
                <?= strtoupper(substr($order['client_name'], 0, 1)) ?>
            </div>
            <div class="client-details">
                <h3><?= e($order['client_name']) ?></h3>
                <p>Cliente</p>
            </div>
            <div class="client-actions">
                <a href="tel:<?= e($order['client_phone']) ?>" class="btn-icon btn-call">📞</a>
                <button class="btn-icon btn-message">💬</button>
            </div>
        </div>
        
        <!-- Address -->
        <div class="address-box">
            <span class="icon">📍</span>
            <div class="text">
                <h4>Dirección del servicio</h4>
                <p><?= e($order['client_address'] ?: "Lat: {$order['client_lat']}, Lng: {$order['client_lng']}") ?></p>
            </div>
        </div>
        
        <!-- Notes -->
        <?php if (!empty($order['notes'])): ?>
            <div class="notes-box">
                <h4>📝 Notas del cliente</h4>
                <p><?= e($order['notes']) ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Service Info -->
        <div class="service-info">
            <div class="service-row">
                <span class="label">Servicio</span>
                <span class="value"><?= e($order['service_name']) ?></span>
            </div>
            <div class="service-row">
                <span class="label">Precio total</span>
                <span class="value">$<?= number_format($order['price'], 2) ?></span>
            </div>
            <div class="service-row earnings">
                <span class="label">Tu ganancia (65%)</span>
                <span class="value">$<?= number_format($partnerEarnings, 2) ?></span>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <?php if ($order['status'] === 'accepted'): ?>
                <button class="btn-primary-action" disabled style="background: #94a3b8;">
                    ⏳ Esperando que el cliente pague...
                </button>
            <?php elseif ($order['status'] === 'paid'): ?>
                <a href="<?= $mapsUrl ?>" target="_blank" class="btn-primary-action btn-navigate">
                    🧭 Iniciar Navegación
                </a>
                <button class="btn-primary-action btn-arrived" onclick="showArrivedModal()">
                    📍 Ya llegué al destino
                </button>
            <?php elseif ($order['status'] === 'in_transit'): ?>
                <button class="btn-primary-action btn-start" onclick="startService()">
                    🧹 Iniciar Limpieza
                </button>
                <a href="<?= $mapsUrl ?>" target="_blank" class="btn-secondary-action">
                    🧭 Ver navegación
                </a>
            <?php elseif ($order['status'] === 'in_progress'): ?>
                <button class="btn-primary-action btn-complete" onclick="showCompleteModal()">
                    ✅ Completar Servicio
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Arrived Modal -->
    <div class="modal-overlay" id="arrivedModal">
        <div class="modal-content">
            <div class="modal-icon">📍</div>
            <h2>¿Llegaste al destino?</h2>
            <p>Confirma que estás en la ubicación del cliente para iniciar el servicio.</p>
            <div class="modal-buttons">
                <button class="btn-modal-cancel" onclick="closeModal('arrivedModal')">Cancelar</button>
                <button class="btn-modal-confirm" onclick="confirmArrived()">Sí, llegué</button>
            </div>
        </div>
    </div>
    
    <!-- Complete Modal -->
    <div class="modal-overlay" id="completeModal">
        <div class="modal-content">
            <div class="modal-icon">🎉</div>
            <h2>¿Completar servicio?</h2>
            <p>Confirma que has terminado la limpieza. Recibirás $<?= number_format($partnerEarnings, 2) ?> por este servicio.</p>
            <div class="modal-buttons">
                <button class="btn-modal-cancel" onclick="closeModal('completeModal')">Cancelar</button>
                <button class="btn-modal-confirm" onclick="confirmComplete()">Completar</button>
            </div>
        </div>
    </div>

    <script>
        const orderId = <?= $order['id'] ?>;
        let watchId = null;
        
        // Tracking de ubicación mientras está en tránsito
        <?php if (in_array($order['status'], ['paid', 'in_transit'])): ?>
        if (navigator.geolocation) {
            watchId = navigator.geolocation.watchPosition(
                function(pos) {
                    updateLocation(pos.coords.latitude, pos.coords.longitude);
                },
                function(err) {
                    console.log('Error GPS:', err);
                },
                { enableHighAccuracy: true, maximumAge: 5000 }
            );
        }
        <?php endif; ?>
        
        function updateLocation(lat, lng) {
            fetch('../api/location/update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    latitude: lat, 
                    longitude: lng,
                    order_id: orderId
                })
            });
        }
        
        function showArrivedModal() {
            document.getElementById('arrivedModal').classList.add('show');
        }
        
        function showCompleteModal() {
            document.getElementById('completeModal').classList.add('show');
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }
        
        function confirmArrived() {
            closeModal('arrivedModal');
            updateOrderStatus('arrive');
        }
        
        function startService() {
            updateOrderStatus('start');
        }
        
        function confirmComplete() {
            closeModal('completeModal');
            updateOrderStatus('complete');
        }
        
        function updateOrderStatus(action) {
            const btn = document.querySelector('.btn-primary-action:not([disabled])');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '⏳ Procesando...';
            }
            
            fetch('../api/orders/' + action + '.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (action === 'complete') {
                        // Redirigir al dashboard con mensaje de éxito
                        window.location.href = 'index.php?completed=1';
                    } else {
                        location.reload();
                    }
                } else {
                    alert(data.message || 'Error al actualizar');
                    if (btn) {
                        btn.disabled = false;
                    }
                    location.reload();
                }
            })
            .catch(err => {
                alert('Error de conexión');
                location.reload();
            });
        }
        
        // Detener tracking al salir
        window.addEventListener('beforeunload', function() {
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
            }
        });
    </script>
    
    <?php if ($hasApiKey): ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>"></script>
    <script>
        function initMap() {
            const clientPos = { lat: <?= $order['client_lat'] ?>, lng: <?= $order['client_lng'] ?> };
            
            const map = new google.maps.Map(document.getElementById('map'), {
                center: clientPos,
                zoom: 15,
                disableDefaultUI: true,
                zoomControl: true
            });
            
            new google.maps.Marker({
                position: clientPos,
                map: map,
                title: 'Cliente',
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
        }
        
        window.onload = initMap;
    </script>
    <?php endif; ?>
</body>
</html>
