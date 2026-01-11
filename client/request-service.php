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
    $saveAddress = post('save_address'); // Nueva opción para guardar dirección
    
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
                // Si el usuario quiere guardar la nueva dirección
                if ($saveAddress) {
                    $db->update(
                        'clients',
                        [
                            'address' => $address,
                            'latitude' => $latitude,
                            'longitude' => $longitude
                        ],
                        'id = :id',
                        ['id' => $user['id']]
                    );
                }
                
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
    <script src="/assets/js/capacitor-push.js" defer></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Servicio - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=places"></script>
    <!-- GPS Nativo para Capacitor -->
    <script src="/assets/js/capacitor-gps.js"></script>
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
        
        /* Address Options */
        .address-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .address-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .address-option:hover {
            border-color: #00b4d8;
            background: #f0f9ff;
        }
        
        .address-option.selected {
            border-color: #00b4d8;
            background: linear-gradient(135deg, rgba(0, 180, 216, 0.1) 0%, rgba(0, 119, 182, 0.1) 100%);
        }
        
        .address-option input[type="radio"] {
            display: none;
        }
        
        .address-option-radio {
            width: 22px;
            height: 22px;
            border: 2px solid #cbd5e1;
            border-radius: 50%;
            flex-shrink: 0;
            position: relative;
            transition: all 0.3s;
        }
        
        .address-option.selected .address-option-radio {
            border-color: #00b4d8;
        }
        
        .address-option.selected .address-option-radio::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 12px;
            height: 12px;
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            border-radius: 50%;
        }
        
        .address-option-content {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }
        
        .address-option-icon {
            width: 44px;
            height: 44px;
            background: #f1f5f9;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        
        .address-option.selected .address-option-icon {
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
        }
        
        .address-option-text {
            flex: 1;
            min-width: 0;
        }
        
        .address-option-text h4 {
            font-size: 0.95rem;
            color: #1e3a5f;
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .address-option-text p {
            font-size: 0.8rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* New Address Container */
        .new-address-container {
            margin-top: 15px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        
        .new-address-container .location-input-group {
            position: relative;
        }
        
        .new-address-container .location-input {
            padding-right: 45px;
        }
        
        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
            color: #94a3b8;
        }
        
        /* Save Address Checkbox */
        .save-address-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 12px;
            cursor: pointer;
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .save-address-check input[type="checkbox"] {
            display: none;
        }
        
        .save-address-check .checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid #cbd5e1;
            border-radius: 6px;
            flex-shrink: 0;
            position: relative;
            transition: all 0.3s;
        }
        
        .save-address-check input:checked + .checkmark {
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            border-color: #00b4d8;
        }
        
        .save-address-check input:checked + .checkmark::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        /* Google Places Autocomplete Dropdown */
        .pac-container {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-top: 5px;
            font-family: 'Poppins', sans-serif;
        }
        
        .pac-item {
            padding: 12px 15px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .pac-item:hover {
            background: #f0f9ff;
        }
        
        .pac-icon {
            margin-right: 10px;
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
                
                <!-- Opciones de dirección -->
                <div class="address-options">
                    <?php if (!empty($user['address'])): ?>
                    <!-- Opción 1: Dirección guardada -->
                    <label class="address-option selected" data-option="saved">
                        <input type="radio" name="address_type" value="saved" checked>
                        <div class="address-option-radio"></div>
                        <div class="address-option-content">
                            <div class="address-option-icon">🏠</div>
                            <div class="address-option-text">
                                <h4>Mi dirección guardada</h4>
                                <p><?= e($user['address']) ?></p>
                            </div>
                        </div>
                    </label>
                    <?php endif; ?>
                    
                    <!-- Opción 2: Ubicación actual GPS -->
                    <label class="address-option <?= empty($user['address']) ? 'selected' : '' ?>" data-option="gps">
                        <input type="radio" name="address_type" value="gps" <?= empty($user['address']) ? 'checked' : '' ?>>
                        <div class="address-option-radio"></div>
                        <div class="address-option-content">
                            <div class="address-option-icon">📍</div>
                            <div class="address-option-text">
                                <h4>Usar mi ubicación actual</h4>
                                <p id="gpsAddressPreview">Detectar por GPS</p>
                            </div>
                        </div>
                    </label>
                    
                    <!-- Opción 3: Otra dirección -->
                    <label class="address-option" data-option="new">
                        <input type="radio" name="address_type" value="new">
                        <div class="address-option-radio"></div>
                        <div class="address-option-content">
                            <div class="address-option-icon">✏️</div>
                            <div class="address-option-text">
                                <h4>Usar otra dirección</h4>
                                <p>Buscar una dirección diferente</p>
                            </div>
                        </div>
                    </label>
                </div>
                
                <!-- Buscador de nueva dirección (oculto por defecto) -->
                <div class="new-address-container" id="newAddressContainer" style="display: none;">
                    <div class="location-input-group">
                        <input 
                            type="text" 
                            id="addressSearch" 
                            class="location-input"
                            placeholder="Buscar dirección..."
                            autocomplete="off"
                        >
                        <span class="search-icon">🔍</span>
                    </div>
                    
                    <!-- Checkbox para guardar dirección -->
                    <label class="save-address-check" id="saveAddressCheck">
                        <input type="checkbox" name="save_address" value="1">
                        <span class="checkmark"></span>
                        <span>Guardar como mi dirección principal</span>
                    </label>
                </div>
                
                <!-- Campo oculto con la dirección final -->
                <input type="hidden" name="address" id="address" value="<?= e($user['address'] ?? '') ?>">
                <input type="hidden" name="latitude" id="latitude" value="<?= e($user['latitude'] ?? '') ?>">
                <input type="hidden" name="longitude" id="longitude" value="<?= e($user['longitude'] ?? '') ?>">
                
                <div id="locationStatus" class="location-status"></div>
                
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
        // Variables globales
        const addressInput = document.getElementById('address');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const locationStatus = document.getElementById('locationStatus');
        const mapPreview = document.getElementById('mapPreview');
        const newAddressContainer = document.getElementById('newAddressContainer');
        const addressSearch = document.getElementById('addressSearch');
        const gpsAddressPreview = document.getElementById('gpsAddressPreview');
        
        // Datos guardados del usuario
        const savedAddress = '<?= e($user['address'] ?? '') ?>';
        const savedLat = '<?= e($user['latitude'] ?? '') ?>';
        const savedLng = '<?= e($user['longitude'] ?? '') ?>';
        const apiKey = '<?= GOOGLE_MAPS_API_KEY ?>';
        
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
        
        // Address option selection
        document.querySelectorAll('.address-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.address-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                
                const optionType = this.dataset.option;
                handleAddressOption(optionType);
            });
        });
        
        function handleAddressOption(optionType) {
            // Ocultar contenedor de nueva dirección por defecto
            newAddressContainer.style.display = 'none';
            locationStatus.innerHTML = '';
            
            switch(optionType) {
                case 'saved':
                    // Usar dirección guardada
                    addressInput.value = savedAddress;
                    latitudeInput.value = savedLat;
                    longitudeInput.value = savedLng;
                    
                    if (savedLat && savedLng) {
                        updateMapPreview(savedLat, savedLng);
                        locationStatus.innerHTML = '✓ Usando tu dirección guardada';
                        locationStatus.className = 'location-status success';
                    }
                    break;
                    
                case 'gps':
                    // Obtener ubicación actual
                    getGPSLocation();
                    break;
                    
                case 'new':
                    // Mostrar buscador de dirección
                    newAddressContainer.style.display = 'block';
                    addressInput.value = '';
                    latitudeInput.value = '';
                    longitudeInput.value = '';
                    mapPreview.innerHTML = '<span>📍 Busca una dirección</span>';
                    mapPreview.classList.remove('loaded');
                    
                    // Enfocar el input de búsqueda
                    setTimeout(() => addressSearch.focus(), 100);
                    break;
            }
        }
        
        // GPS Location - MODIFICADO PARA CAPACITOR
        const isCapacitorApp = typeof Capacitor !== 'undefined' && Capacitor.isNativePlatform();
        
        function getGPSLocation() {
            locationStatus.innerHTML = '⏳ Obteniendo ubicación...';
            locationStatus.className = 'location-status';
            gpsAddressPreview.textContent = 'Detectando...';
            
            // ========== GPS NATIVO (CAPACITOR) ==========
            if (isCapacitorApp && window.INGCleanGPS) {
                console.log('📱 Usando GPS NATIVO (Capacitor)');
                
                window.INGCleanGPS.init(
                    // Éxito
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        console.log('✅ GPS nativo:', lat, lng);
                        
                        latitudeInput.value = lat;
                        longitudeInput.value = lng;
                        
                        locationStatus.innerHTML = '✓ Ubicación obtenida (GPS Nativo)';
                        locationStatus.className = 'location-status success';
                        
                        updateMapPreview(lat, lng);
                        reverseGeocode(lat, lng);
                    },
                    // Error
                    function(error) {
                        console.log('❌ Error GPS nativo:', error);
                        let errorMsg = error.message || 'Error obteniendo ubicación';
                        
                        if (error.code === 1) {
                            errorMsg = 'Permiso denegado. Ve a Configuración → Apps → INGClean → Permisos → Ubicación';
                        }
                        
                        locationStatus.innerHTML = '❌ ' + errorMsg;
                        locationStatus.className = 'location-status error';
                        gpsAddressPreview.textContent = 'No se pudo detectar';
                    }
                );
                
            // ========== GPS WEB (NAVEGADOR) ==========
            } else {
                console.log('🌐 Usando GPS WEB (navegador)');
                
                if (!navigator.geolocation) {
                    locationStatus.innerHTML = '❌ Geolocalización no soportada';
                    locationStatus.className = 'location-status error';
                    return;
                }
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        latitudeInput.value = lat;
                        longitudeInput.value = lng;
                        
                        locationStatus.innerHTML = '✓ Ubicación obtenida correctamente';
                        locationStatus.className = 'location-status success';
                        
                        updateMapPreview(lat, lng);
                        
                        // Obtener dirección desde coordenadas
                        reverseGeocode(lat, lng);
                    },
                    function(error) {
                        let errorMsg = 'Error desconocido';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMsg = 'Permiso denegado. Habilita la ubicación.';
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
                        gpsAddressPreview.textContent = 'No se pudo detectar';
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );
            }
        }
        
        // Reverse Geocoding
        function reverseGeocode(lat, lng) {
            if (!apiKey) {
                addressInput.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                gpsAddressPreview.textContent = addressInput.value;
                return;
            }
            
            fetch(`https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${apiKey}`)
                .then(r => r.json())
                .then(data => {
                    if (data.results && data.results[0]) {
                        const formattedAddress = data.results[0].formatted_address;
                        addressInput.value = formattedAddress;
                        gpsAddressPreview.textContent = formattedAddress;
                    } else {
                        addressInput.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                        gpsAddressPreview.textContent = addressInput.value;
                    }
                })
                .catch(err => {
                    console.log('Geocoding error:', err);
                    addressInput.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    gpsAddressPreview.textContent = addressInput.value;
                });
        }
        
        // Google Places Autocomplete
        let autocomplete;
        function initAutocomplete() {
            if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
                console.log('Google Places API not loaded');
                return;
            }
            
            autocomplete = new google.maps.places.Autocomplete(addressSearch, {
                types: ['address'],
                fields: ['formatted_address', 'geometry']
            });
            
            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();
                
                if (!place.geometry) {
                    locationStatus.innerHTML = '❌ No se encontró la dirección';
                    locationStatus.className = 'location-status error';
                    return;
                }
                
                const lat = place.geometry.location.lat();
                const lng = place.geometry.location.lng();
                
                addressInput.value = place.formatted_address;
                latitudeInput.value = lat;
                longitudeInput.value = lng;
                
                updateMapPreview(lat, lng);
                
                locationStatus.innerHTML = '✓ Dirección seleccionada';
                locationStatus.className = 'location-status success';
            });
        }
        
        // Map Preview
        function updateMapPreview(lat, lng) {
            if (apiKey) {
                mapPreview.innerHTML = `<iframe src="https://www.google.com/maps/embed/v1/place?key=${apiKey}&q=${lat},${lng}&zoom=16"></iframe>`;
                mapPreview.classList.add('loaded');
            } else {
                mapPreview.innerHTML = `
                    <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" style="color: #0077b6; text-decoration: none;">
                        📍 Ver ubicación en Google Maps
                    </a>
                `;
            }
        }
        
        // Inicializar según opción seleccionada
        document.addEventListener('DOMContentLoaded', function() {
            initAutocomplete();
            
            // Detectar opción inicial
            const selectedOption = document.querySelector('.address-option.selected');
            if (selectedOption) {
                const optionType = selectedOption.dataset.option;
                
                if (optionType === 'saved' && savedLat && savedLng) {
                    updateMapPreview(savedLat, savedLng);
                    locationStatus.innerHTML = '✓ Usando tu dirección guardada';
                    locationStatus.className = 'location-status success';
                } else if (optionType === 'gps') {
                    // Auto-obtener GPS al cargar si no tiene dirección guardada
                    getGPSLocation();
                }
            }
        });
        
        // Form validation
        document.getElementById('requestForm').addEventListener('submit', function(e) {
            const lat = latitudeInput.value;
            const lng = longitudeInput.value;
            const address = addressInput.value;
            const service = document.querySelector('input[name="service_id"]:checked');
            
            if (!service) {
                e.preventDefault();
                alert('Por favor selecciona un tipo de servicio');
                return;
            }
            
            if (!lat || !lng || !address) {
                e.preventDefault();
                alert('Por favor selecciona o ingresa una ubicación válida');
                return;
            }
            
            document.getElementById('btnSubmit').disabled = true;
            document.getElementById('btnSubmit').textContent = '⏳ Enviando...';
        });
    </script>
</body>
</html>