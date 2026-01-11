<?php
/**
 * INGClean - Registro de Cliente
 * Con GPS mejorado y Autocompletado de Google Places enfocado en Florida
 */
define('INGCLEAN_APP', true);
require_once 'includes/init.php';

// Si ya está logueado, redirigir
if (auth()->isLoggedIn()) {
    redirect('/client/');
}

$error = '';
$success = '';

if (isPost()) {
    validateCsrf();
    
    $data = [
        'name' => post('name'),
        'email' => post('email'),
        'phone' => post('phone'),
        'password' => post('password'),
        'address' => post('address'),
        'latitude' => post('latitude'),
        'longitude' => post('longitude')
    ];
    
    $confirmPassword = post('confirm_password');
    
    // Validaciones
    if (empty($data['name']) || empty($data['email']) || empty($data['phone']) || empty($data['password'])) {
        $error = 'Todos los campos son requeridos';
    } elseif (!isValidEmail($data['email'])) {
        $error = 'Email inválido';
    } elseif (strlen($data['password']) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($data['password'] !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden';
    } elseif (empty($data['address'])) {
        $error = 'Debes ingresar tu dirección';
    } elseif (empty($data['latitude']) || empty($data['longitude'])) {
        $error = 'Por favor selecciona una dirección válida de la lista o usa el botón GPS';
    } else {
        $result = auth()->registerClient($data);
        
        if ($result['success']) {
            setFlash('success', '¡Registro exitoso! Ahora puedes iniciar sesión');
            redirect('/login.php?registered=client');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#0a1628">
    <title>Registro de Cliente - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0a1628 0%, #1a3a5c 50%, #0077b6 100%);
            padding: 20px;
            padding-top: max(20px, env(safe-area-inset-top));
            padding-bottom: max(20px, env(safe-area-inset-bottom));
        }
        
        .container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            padding: 24px 0;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px;
            margin: 0 auto 12px;
            box-shadow: 0 0 30px rgba(0, 180, 216, 0.4);
        }
        
        .header h1 {
            color: white;
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .header h1 span { color: #00b4d8; }
        
        .header p {
            color: rgba(255,255,255,0.8);
            font-size: 0.95rem;
        }
        
        .form-card {
            background: white;
            border-radius: 24px;
            padding: 30px 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .form-title {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .form-title h2 {
            color: #1e3a5f;
            font-size: 1.3rem;
            margin-bottom: 5px;
        }
        
        .form-title p {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            color: #1e3a5f;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .form-group label .required {
            color: #ef4444;
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon span {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
            z-index: 1;
            pointer-events: none;
        }
        
        .input-icon input {
            padding-left: 48px !important;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            font-family: inherit;
            transition: all 0.3s;
            -webkit-appearance: none;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #00b4d8;
            box-shadow: 0 0 0 4px rgba(0, 180, 216, 0.1);
        }
        
        /* Location Section */
        .location-section {
            background: #f0f9ff;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid #e0f2fe;
        }
        
        .location-section h3 {
            font-size: 1rem;
            color: #1e3a5f;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .location-section .hint {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 16px;
        }
        
        .gps-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .gps-btn:disabled {
            opacity: 0.7;
            cursor: wait;
        }
        
        .gps-btn.success {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }
        
        .divider {
            text-align: center;
            color: #94a3b8;
            font-size: 0.85rem;
            margin: 16px 0;
            position: relative;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 25%;
            height: 1px;
            background: #cbd5e1;
        }
        
        .divider::before { left: 5%; }
        .divider::after { right: 5%; }
        
        .address-input-wrapper {
            position: relative;
        }
        
        .address-input-wrapper input {
            width: 100%;
            padding: 14px 16px;
            padding-right: 50px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            font-family: inherit;
            -webkit-appearance: none;
        }
        
        .address-input-wrapper input:focus {
            outline: none;
            border-color: #00b4d8;
            box-shadow: 0 0 0 4px rgba(0, 180, 216, 0.1);
        }
        
        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            background: #0077b6;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .address-hint {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 8px;
            padding-left: 4px;
        }
        
        .location-preview {
            display: none;
            margin-top: 16px;
            padding: 14px;
            background: #dcfce7;
            border-radius: 12px;
            border: 2px solid #86efac;
        }
        
        .location-preview.show {
            display: block;
        }
        
        .location-preview p {
            color: #16a34a;
            font-weight: 500;
            font-size: 0.95rem;
            margin-bottom: 6px;
        }
        
        .location-preview .coords {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 10px;
        }
        
        .location-preview .map-preview {
            border-radius: 10px;
            overflow: hidden;
            height: 120px;
        }
        
        .location-preview .map-preview iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .btn-register {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1.15rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            box-shadow: 0 8px 25px rgba(0, 180, 216, 0.35);
            margin-top: 10px;
        }
        
        .btn-register:active {
            transform: scale(0.98);
        }
        
        .btn-register:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #64748b;
            font-size: 0.95rem;
        }
        
        .login-link a {
            color: #0077b6;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-home {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 0.95rem;
        }
        
        /* Google Places Autocomplete styling */
        .pac-container {
            border-radius: 12px;
            margin-top: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            font-family: 'Poppins', sans-serif;
            border: none;
            z-index: 10000 !important;
        }
        
        .pac-item {
            padding: 12px 16px;
            cursor: pointer;
            font-size: 0.9rem;
            border-top: 1px solid #f1f5f9;
        }
        
        .pac-item:first-child {
            border-top: none;
        }
        
        .pac-item:hover {
            background: #f0f9ff;
        }
        
        .pac-item-query {
            font-size: 0.95rem;
            color: #1e3a5f;
        }
        
        .pac-matched {
            font-weight: 600;
        }
        
        /* Loading spinner for geocoding */
        .geocoding-spinner {
            display: none;
            position: absolute;
            right: 52px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .geocoding-spinner.show {
            display: block;
        }
        
        .geocoding-spinner::after {
            content: '';
            display: block;
            width: 20px;
            height: 20px;
            border: 2px solid #e2e8f0;
            border-top-color: #0077b6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="assets/img/logo.png" alt="INGClean" class="logo">
            <h1>ING<span>Clean</span></h1>
            <p>Servicios de limpieza a tu puerta</p>
        </div>
        
        <div class="form-card">
            <div class="form-title">
                <h2>👤 Crear Cuenta de Cliente</h2>
                <p>Completa tus datos para comenzar</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?= e($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <?= csrfField() ?>
                
                <div class="form-group">
                    <label>Nombre Completo <span class="required">*</span></label>
                    <div class="input-icon">
                        <span>👤</span>
                        <input type="text" name="name" placeholder="Tu nombre completo" value="<?= e(post('name') ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Correo Electrónico <span class="required">*</span></label>
                    <div class="input-icon">
                        <span>📧</span>
                        <input type="email" name="email" placeholder="tu@email.com" value="<?= e(post('email') ?? '') ?>" required inputmode="email">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Teléfono <span class="required">*</span></label>
                    <div class="input-icon">
                        <span>📱</span>
                        <input type="tel" name="phone" placeholder="+1 (305) 123-4567" value="<?= e(post('phone') ?? '') ?>" required inputmode="tel">
                    </div>
                </div>
                
                <!-- Location Section -->
                <div class="location-section">
                    <h3>📍 Tu Ubicación</h3>
                    <p class="hint">Esta será la dirección donde recibirás los servicios de limpieza</p>
                    
                    <button type="button" class="gps-btn" id="gpsBtn" onclick="getGPSLocation()">
                        📍 Usar mi ubicación actual
                    </button>
                    
                    <div class="divider">— o escribe tu dirección —</div>
                    
                    <div class="address-input-wrapper">
                        <input 
                            type="text" 
                            id="addressInput" 
                            name="address" 
                            placeholder="Ej: 9619 Fontainebleau Blvd, Miami FL"
                            value="<?= e(post('address') ?? '') ?>"
                            autocomplete="off"
                        >
                        <div class="geocoding-spinner" id="geocodingSpinner"></div>
                        <button type="button" class="search-btn" onclick="geocodeAddress()" title="Buscar dirección">🔍</button>
                    </div>
                    <p class="address-hint">💡 Escribe una dirección de Florida y selecciona de la lista</p>
                    
                    <div class="location-preview" id="locationPreview">
                        <p>✅ <span id="previewAddress">Ubicación detectada</span></p>
                        <div class="coords" id="previewCoords"></div>
                        <div class="map-preview" id="mapPreview"></div>
                    </div>
                    
                    <!-- Hidden fields for coordinates -->
                    <input type="hidden" name="latitude" id="latitude" value="<?= e(post('latitude') ?? '') ?>">
                    <input type="hidden" name="longitude" id="longitude" value="<?= e(post('longitude') ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Contraseña <span class="required">*</span></label>
                    <div class="input-icon">
                        <span>🔒</span>
                        <input type="password" name="password" placeholder="Mínimo 6 caracteres" required minlength="6">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Confirmar Contraseña <span class="required">*</span></label>
                    <div class="input-icon">
                        <span>🔒</span>
                        <input type="password" name="confirm_password" placeholder="Repite tu contraseña" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-register" id="submitBtn">
                    Crear Mi Cuenta
                </button>
            </form>
            
            <div class="login-link">
                ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
            </div>
        </div>
        
        <a href="index.php" class="back-home">← Volver al inicio</a>
    </div>
    
    <!-- Google Maps JavaScript API with Places Library -->
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=places&callback=initAutocomplete" async defer></script>
    
    <script>
        let autocomplete;
        let geocoder;
        
        function initAutocomplete() {
            const addressInput = document.getElementById('addressInput');
            geocoder = new google.maps.Geocoder();
            
            // Configurar autocomplete enfocado en Florida/USA
            autocomplete = new google.maps.places.Autocomplete(addressInput, {
                types: ['address'],
                componentRestrictions: { country: 'us' }, // Solo USA
                fields: ['formatted_address', 'geometry', 'address_components']
            });
            
            // Establecer bias hacia Miami/Florida
            const floridaBounds = new google.maps.LatLngBounds(
                new google.maps.LatLng(24.396308, -87.634896), // SW Florida
                new google.maps.LatLng(31.000968, -79.974306)  // NE Florida
            );
            autocomplete.setBounds(floridaBounds);
            
            // Listener cuando selecciona una dirección
            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();
                
                if (place.geometry) {
                    const lat = place.geometry.location.lat();
                    const lng = place.geometry.location.lng();
                    setLocation(lat, lng, place.formatted_address);
                }
            });
            
            // También permitir Enter para buscar
            addressInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    geocodeAddress();
                }
            });
        }
        
        function getGPSLocation() {
            const btn = document.getElementById('gpsBtn');
            btn.disabled = true;
            btn.innerHTML = '⏳ Obteniendo ubicación...';
            
            if (!navigator.geolocation) {
                alert('Tu navegador no soporta geolocalización. Por favor escribe tu dirección.');
                btn.disabled = false;
                btn.innerHTML = '📍 Usar mi ubicación actual';
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    // Reverse geocoding to get address
                    reverseGeocode(lat, lng);
                    
                    btn.innerHTML = '✅ Ubicación obtenida';
                    btn.classList.add('success');
                },
                function(error) {
                    let msg = 'No se pudo obtener tu ubicación. Por favor escribe tu dirección manualmente.';
                    
                    if (error.code === 1) msg = 'Permiso de ubicación denegado. Escribe tu dirección manualmente.';
                    if (error.code === 2) msg = 'Ubicación no disponible. Escribe tu dirección manualmente.';
                    if (error.code === 3) msg = 'Tiempo agotado. Escribe tu dirección manualmente.';
                    
                    alert(msg);
                    btn.disabled = false;
                    btn.innerHTML = '📍 Usar mi ubicación actual';
                    
                    // Enfocar en el campo de dirección
                    document.getElementById('addressInput').focus();
                },
                {
                    enableHighAccuracy: true,
                    timeout: 30000,  // 30 segundos de timeout
                    maximumAge: 60000 // Cache de 1 minuto
                }
            );
        }
        
        function reverseGeocode(lat, lng) {
            const latlng = { lat: lat, lng: lng };
            
            geocoder.geocode({ location: latlng }, function(results, status) {
                if (status === 'OK' && results[0]) {
                    setLocation(lat, lng, results[0].formatted_address);
                } else {
                    setLocation(lat, lng, `Coordenadas: ${lat.toFixed(6)}, ${lng.toFixed(6)}`);
                }
            });
        }
        
        // Geocodificar dirección escrita manualmente
        function geocodeAddress() {
            const address = document.getElementById('addressInput').value.trim();
            
            if (!address) {
                alert('Por favor escribe una dirección');
                return;
            }
            
            // Mostrar spinner
            document.getElementById('geocodingSpinner').classList.add('show');
            
            // Agregar "Florida" si no está incluido para mejorar resultados
            let searchAddress = address;
            if (!address.toLowerCase().includes('florida') && !address.toLowerCase().includes(', fl')) {
                searchAddress = address + ', Florida, USA';
            }
            
            geocoder.geocode({ 
                address: searchAddress,
                componentRestrictions: { country: 'us' }
            }, function(results, status) {
                document.getElementById('geocodingSpinner').classList.remove('show');
                
                if (status === 'OK' && results[0]) {
                    const lat = results[0].geometry.location.lat();
                    const lng = results[0].geometry.location.lng();
                    setLocation(lat, lng, results[0].formatted_address);
                } else {
                    alert('No se encontró la dirección. Por favor verifica e intenta de nuevo.');
                }
            });
        }
        
        function setLocation(lat, lng, address) {
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            document.getElementById('addressInput').value = address;
            
            // Show preview
            const preview = document.getElementById('locationPreview');
            preview.classList.add('show');
            document.getElementById('previewAddress').textContent = address;
            document.getElementById('previewCoords').textContent = `Coordenadas: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            
            // Show mini map
            const mapPreview = document.getElementById('mapPreview');
            mapPreview.innerHTML = `<iframe src="https://www.google.com/maps?q=${lat},${lng}&z=17&output=embed" loading="lazy"></iframe>`;
            
            // Scroll to show preview
            preview.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const lat = document.getElementById('latitude').value;
            const lng = document.getElementById('longitude').value;
            const address = document.getElementById('addressInput').value.trim();
            
            if (!lat || !lng) {
                e.preventDefault();
                
                if (address) {
                    // Si hay dirección pero no coordenadas, intentar geocodificar
                    alert('Por favor presiona el botón 🔍 para validar tu dirección, o selecciona una opción de la lista.');
                } else {
                    alert('Por favor ingresa tu dirección o usa el botón GPS');
                }
                return false;
            }
        });
    </script>
</body>
</html>
