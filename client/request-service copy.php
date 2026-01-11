<?php
/**
 * INGClean - Solicitar Servicio
 */
require_once '../includes/init.php';

auth()->requireLogin(['client']);

$user = auth()->getCurrentUser();
$db = Database::getInstance();

// Verificar si ya tiene una orden activa
$activeOrder = $db->fetchOne(
    "SELECT id FROM orders WHERE client_id = :client_id AND status NOT IN ('completed', 'cancelled') LIMIT 1",
    ['client_id' => $user['id']]
);

if ($activeOrder) {
    setFlash('warning', 'Ya tienes un servicio en curso. Espera a que termine para solicitar otro.');
    redirect('/client/');
}

// Obtener servicio seleccionado
$serviceId = get('service');
$service = null;

if ($serviceId) {
    $service = $db->fetchOne("SELECT * FROM services WHERE id = :id AND is_active = 1", ['id' => $serviceId]);
}

// Si no hay servicio válido, mostrar todos
$services = $db->fetchAll("SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC");

$error = '';

// Procesar solicitud
if (isPost()) {
    validateCsrf();
    
    $serviceId = post('service_id');
    $latitude = post('latitude');
    $longitude = post('longitude');
    $address = post('address');
    $notes = post('notes');
    
    // Validaciones
    if (empty($serviceId)) {
        $error = 'Selecciona un tipo de servicio';
    } elseif (empty($latitude) || empty($longitude)) {
        $error = 'Necesitamos tu ubicación para enviar un partner';
    } elseif (empty($address)) {
        $error = 'Ingresa tu dirección';
    } else {
        // Verificar servicio
        $service = $db->fetchOne("SELECT * FROM services WHERE id = :id AND is_active = 1", ['id' => $serviceId]);
        
        if (!$service) {
            $error = 'Servicio no válido';
        } else {
            try {
                // Crear la orden
                $orderId = $db->insert('orders', [
                    'order_code' => '', // Se genera automáticamente por trigger
                    'client_id' => $user['id'],
                    'service_id' => $serviceId,
                    'status' => 'pending',
                    'client_latitude' => $latitude,
                    'client_longitude' => $longitude,
                    'client_address' => $address,
                    'notes' => $notes
                ]);
                
                // Actualizar ubicación del cliente
                auth()->updateLocation($latitude, $longitude);
                
                // Obtener datos completos de la orden para notificación
                $orderData = $db->fetchOne(
                    "SELECT o.*, s.name as service_name, s.price 
                     FROM orders o 
                     JOIN services s ON o.service_id = s.id 
                     WHERE o.id = :id",
                    ['id' => $orderId]
                );
                $orderData['client_address'] = $address;
                
                // Enviar push notification a partners disponibles
                try {
                    $notificationService = new NotificationService();
                    $notificationService->notifyNewOrder($orderData);
                } catch (Exception $e) {
                    appLog("Error enviando push a partners: " . $e->getMessage(), 'warning');
                }
                
                setFlash('success', '¡Solicitud enviada! Buscando un partner cerca de ti...');
                redirect('/client/');
                
            } catch (Exception $e) {
                appLog("Error creando orden: " . $e->getMessage(), 'error');
                $error = 'Error al crear la solicitud. Intenta de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Servicio - <?= APP_NAME ?></title>
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
        }
        
        /* Header */
        .header {
            background: white;
            padding: 15px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
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
        
        .header h1 {
            font-size: 1.1rem;
            color: #1e3a5f;
        }
        
        /* Main */
        .main-content {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        /* Service Selection */
        .step-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .step-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .step-header h2 {
            font-size: 1rem;
            color: #1e3a5f;
        }
        
        .service-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .service-option {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .service-option:hover {
            border-color: #00b4d8;
        }
        
        .service-option.selected {
            border-color: #0077b6;
            background: #f0f9ff;
        }
        
        .service-option input {
            display: none;
        }
        
        .service-radio {
            width: 22px;
            height: 22px;
            border: 2px solid #cbd5e1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .service-option.selected .service-radio {
            border-color: #0077b6;
            background: #0077b6;
        }
        
        .service-radio::after {
            content: '';
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .service-option.selected .service-radio::after {
            opacity: 1;
        }
        
        .service-details {
            flex: 1;
        }
        
        .service-details h3 {
            font-size: 0.95rem;
            color: #1e3a5f;
            margin-bottom: 3px;
        }
        
        .service-details p {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .service-price-tag {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0077b6;
        }
        
        /* Location */
        .location-input-group {
            position: relative;
        }
        
        .location-input {
            width: 100%;
            padding: 14px 50px 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .location-input:focus {
            outline: none;
            border-color: #00b4d8;
        }
        
        .btn-locate {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        
        .btn-locate:hover {
            opacity: 0.9;
        }
        
        .btn-locate.loading {
            opacity: 0.7;
            cursor: wait;
        }
        
        .location-status {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            font-size: 0.85rem;
        }
        
        .location-status.success {
            color: #16a34a;
        }
        
        .location-status.error {
            color: #dc2626;
        }
        
        /* Map Preview */
        .map-preview {
            height: 200px;
            border-radius: 12px;
            margin-top: 15px;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            overflow: hidden;
        }
        
        .map-preview.loaded {
            background: none;
        }
        
        .map-preview iframe {
            width: 100%;
            height: 100%;
            border: 0;
            border-radius: 12px;
        }
        
        /* Notes */
        .notes-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: inherit;
            resize: none;
            min-height: 100px;
            transition: all 0.3s;
        }
        
        .notes-input:focus {
            outline: none;
            border-color: #00b4d8;
        }
        
        /* Summary */
        .summary-section {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            border-radius: 16px;
            padding: 20px;
            color: white;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .summary-row:last-child {
            border-bottom: none;
        }
        
        .summary-row.total {
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 10px;
            padding-top: 15px;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: white;
            color: #0077b6;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            margin-top: 20px;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        @media (max-width: 480px) {
            .main-content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <a href="index.php" class="back-btn">←</a>
        <h1>Solicitar Servicio</h1>
    </header>
    
    <!-- Main -->
    <main class="main-content">
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="requestForm">
            <?= csrfField() ?>
            
            <!-- Step 1: Service Selection -->
            <section class="step-section">
                <div class="step-header">
                    <div class="step-number">1</div>
                    <h2>Tipo de Limpieza</h2>
                </div>
                
                <div class="service-options">
                    <?php foreach ($services as $s): ?>
                        <label class="service-option <?= ($service && $service['id'] == $s['id']) ? 'selected' : '' ?>">
                            <input 
                                type="radio" 
                                name="service_id" 
                                value="<?= $s['id'] ?>"
                                data-price="<?= $s['price'] ?>"
                                data-name="<?= e($s['name']) ?>"
                                <?= ($service && $service['id'] == $s['id']) ? 'checked' : '' ?>
                                required
                            >
                            <div class="service-radio"></div>
                            <div class="service-details">
                                <h3><?= e($s['name']) ?></h3>
                                <p><?= e($s['description']) ?></p>
                            </div>
                            <div class="service-price-tag">$<?= number_format($s['price'], 2) ?></div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>
            
            <!-- Step 2: Location -->
            <section class="step-section">
                <div class="step-header">
                    <div class="step-number">2</div>
                    <h2>Tu Ubicación</h2>
                </div>
                
                <div class="location-input-group">
                    <input 
                        type="text" 
                        id="address" 
                        name="address" 
                        class="location-input"
                        placeholder="Ingresa tu dirección"
                        value="<?= e($user['address'] ?? '') ?>"
                        required
                    >
                    <button type="button" class="btn-locate" id="btnLocate">📍 GPS</button>
                </div>
                
                <div id="locationStatus" class="location-status"></div>
                
                <input type="hidden" name="latitude" id="latitude" value="<?= e($user['latitude'] ?? '') ?>">
                <input type="hidden" name="longitude" id="longitude" value="<?= e($user['longitude'] ?? '') ?>">
                
                <div class="map-preview" id="mapPreview">
                    <span>📍 Tu ubicación aparecerá aquí</span>
                </div>
            </section>
            
            <!-- Step 3: Notes -->
            <section class="step-section">
                <div class="step-header">
                    <div class="step-number">3</div>
                    <h2>Notas Adicionales (Opcional)</h2>
                </div>
                
                <textarea 
                    name="notes" 
                    class="notes-input"
                    placeholder="Ej: Tengo mascotas, necesito énfasis en la cocina, la puerta es azul..."
                ></textarea>
            </section>
            
            <!-- Summary & Submit -->
            <section class="summary-section">
                <h3 style="margin-bottom: 15px;">📋 Resumen del Pedido</h3>
                
                <div class="summary-row">
                    <span>Servicio</span>
                    <span id="summaryService"><?= $service ? e($service['name']) : 'No seleccionado' ?></span>
                </div>
                <div class="summary-row">
                    <span>Comisión plataforma</span>
                    <span>Incluida</span>
                </div>
                <div class="summary-row total">
                    <span>Total a Pagar</span>
                    <span id="summaryTotal">$<?= $service ? number_format($service['price'], 2) : '0.00' ?></span>
                </div>
                
                <button type="submit" class="btn-submit" id="btnSubmit">
                    🧹 Solicitar Limpieza
                </button>
                
                <p style="text-align: center; font-size: 0.8rem; margin-top: 15px; opacity: 0.8;">
                    El pago se realizará después de que un partner acepte tu solicitud
                </p>
            </section>
        </form>
    </main>
    
    <script>
        // Service selection
        document.querySelectorAll('.service-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.service-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                
                const input = this.querySelector('input');
                const price = input.dataset.price;
                const name = input.dataset.name;
                
                document.getElementById('summaryService').textContent = name;
                document.getElementById('summaryTotal').textContent = '$' + parseFloat(price).toFixed(2);
            });
        });
        
        // Geolocation
        const btnLocate = document.getElementById('btnLocate');
        const locationStatus = document.getElementById('locationStatus');
        const addressInput = document.getElementById('address');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const mapPreview = document.getElementById('mapPreview');
        
        function updateMapPreview(lat, lng) {
            const apiKey = '<?= GOOGLE_MAPS_API_KEY ?>';
            if (apiKey && apiKey !== 'AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX') {
                mapPreview.innerHTML = `<iframe src="https://www.google.com/maps/embed/v1/place?key=${apiKey}&q=${lat},${lng}&zoom=16"></iframe>`;
                mapPreview.classList.add('loaded');
            } else {
                // Sin API key, mostrar link a Google Maps
                mapPreview.innerHTML = `
                    <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" style="color: #0077b6; text-decoration: none;">
                        📍 Ver ubicación en Google Maps
                    </a>
                `;
            }
        }
        
        // Si ya hay coordenadas, mostrar mapa
        if (latitudeInput.value && longitudeInput.value) {
            updateMapPreview(latitudeInput.value, longitudeInput.value);
            locationStatus.innerHTML = '✓ Ubicación guardada';
            locationStatus.className = 'location-status success';
        }
        
        btnLocate.addEventListener('click', function() {
            if (!navigator.geolocation) {
                locationStatus.innerHTML = '❌ Geolocalización no soportada';
                locationStatus.className = 'location-status error';
                return;
            }
            
            btnLocate.classList.add('loading');
            btnLocate.textContent = '⏳';
            locationStatus.innerHTML = 'Obteniendo ubicación...';
            locationStatus.className = 'location-status';
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    latitudeInput.value = lat;
                    longitudeInput.value = lng;
                    
                    locationStatus.innerHTML = '✓ Ubicación obtenida correctamente';
                    locationStatus.className = 'location-status success';
                    
                    btnLocate.classList.remove('loading');
                    btnLocate.textContent = '✓';
                    
                    updateMapPreview(lat, lng);
                    
                    // Intentar obtener dirección con Geocoding
                    reverseGeocode(lat, lng);
                },
                function(error) {
                    let errorMsg = 'Error desconocido';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg = 'Permiso denegado. Habilita la ubicación en tu navegador.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg = 'Ubicación no disponible';
                            break;
                        case error.TIMEOUT:
                            errorMsg = 'Tiempo agotado';
                            break;
                    }
                    
                    locationStatus.innerHTML = '❌ ' + errorMsg;
                    locationStatus.className = 'location-status error';
                    
                    btnLocate.classList.remove('loading');
                    btnLocate.textContent = '📍 GPS';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                }
            );
        });
        
        // Reverse Geocoding (obtener dirección desde coordenadas)
        function reverseGeocode(lat, lng) {
            const apiKey = '<?= GOOGLE_MAPS_API_KEY ?>';
            if (!apiKey || apiKey === 'AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX') {
                // Sin API key, poner coordenadas
                if (!addressInput.value) {
                    addressInput.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                }
                return;
            }
            
            fetch(`https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${apiKey}`)
                .then(r => r.json())
                .then(data => {
                    if (data.results && data.results[0]) {
                        addressInput.value = data.results[0].formatted_address;
                    }
                })
                .catch(err => {
                    console.log('Geocoding error:', err);
                    if (!addressInput.value) {
                        addressInput.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    }
                });
        }
        
        // Form validation
        document.getElementById('requestForm').addEventListener('submit', function(e) {
            const lat = latitudeInput.value;
            const lng = longitudeInput.value;
            const service = document.querySelector('input[name="service_id"]:checked');
            
            if (!service) {
                e.preventDefault();
                alert('Por favor selecciona un tipo de servicio');
                return;
            }
            
            if (!lat || !lng) {
                e.preventDefault();
                alert('Por favor habilita tu ubicación haciendo clic en el botón GPS');
                return;
            }
            
            document.getElementById('btnSubmit').disabled = true;
            document.getElementById('btnSubmit').textContent = '⏳ Enviando...';
        });
    </script>
</body>
</html>
