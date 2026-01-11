<?php
/**
 * INGClean - Registro de Cliente
 */
require_once 'includes/init.php';

// Si ya está logueado, redirigir
if (auth()->isLoggedIn()) {
    redirect('/client/');
}

$error = '';
$errors = [];

// Procesar registro
if (isPost()) {
    validateCsrf();
    
    $data = [
        'name' => post('name'),
        'email' => post('email'),
        'phone' => post('phone'),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
        'address' => post('address'),
        'latitude' => post('latitude'),
        'longitude' => post('longitude')
    ];
    
    // Validaciones
    if (empty($data['name'])) {
        $errors['name'] = 'El nombre es requerido';
    }
    
    if (empty($data['email'])) {
        $errors['email'] = 'El email es requerido';
    } elseif (!isValidEmail($data['email'])) {
        $errors['email'] = 'Email inválido';
    }
    
    if (empty($data['phone'])) {
        $errors['phone'] = 'El teléfono es requerido';
    }
    
    if (empty($data['password'])) {
        $errors['password'] = 'La contraseña es requerida';
    } elseif (strlen($data['password']) < 6) {
        $errors['password'] = 'Mínimo 6 caracteres';
    }
    
    if ($data['password'] !== $data['password_confirm']) {
        $errors['password_confirm'] = 'Las contraseñas no coinciden';
    }
    
    // Si no hay errores, registrar
    if (empty($errors)) {
        $result = auth()->registerClient($data);
        
        if ($result['success']) {
            redirect('/client/');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Cliente - <?= APP_NAME ?></title>
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
            min-height: 100vh;
            background: linear-gradient(135deg, #0a1628 0%, #1a3a5c 50%, #0d2847 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 480px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px 30px;
            border: 1px solid rgba(0, 180, 216, 0.2);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .logo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.1);
            padding: 5px;
        }
        
        .brand-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #ffffff;
            margin-top: 10px;
        }
        
        .brand-name span {
            color: #00b4d8;
        }
        
        h2 {
            color: #ffffff;
            font-size: 1.2rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 5px;
        }
        
        .subtitle {
            color: #90e0ef;
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 25px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #fca5a5;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            color: #caf0f8;
            font-size: 0.85rem;
            margin-bottom: 6px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 10px;
            border: 2px solid rgba(0, 180, 216, 0.2);
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #00b4d8;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .form-group input::placeholder {
            color: rgba(144, 224, 239, 0.5);
        }
        
        .form-group input.error {
            border-color: #ef4444;
        }
        
        .form-group .error-text {
            color: #fca5a5;
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .location-group {
            position: relative;
        }
        
        .btn-location {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-location:hover {
            opacity: 0.9;
        }
        
        .btn-location.loading {
            opacity: 0.7;
            cursor: wait;
        }
        
        .location-status {
            font-size: 0.8rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .location-status.success {
            color: #86efac;
        }
        
        .location-status.error {
            color: #fca5a5;
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: inherit;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 180, 216, 0.3);
        }
        
        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(0, 180, 216, 0.2);
        }
        
        .links a {
            color: #00b4d8;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .links p {
            color: #90e0ef;
            font-size: 0.85rem;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #90e0ef;
            text-decoration: none;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .back-link:hover {
            color: #00b4d8;
        }
        
        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver al inicio
        </a>
        
        <div class="card">
            <div class="logo-section">
                <img src="assets/img/logo.png" alt="INGClean" class="logo">
                <h1 class="brand-name">ING<span>Clean</span></h1>
            </div>
            
            <h2>Crear Cuenta de Cliente</h2>
            <p class="subtitle">Solicita servicios de limpieza en minutos</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <?= csrfField() ?>
                
                <div class="form-group">
                    <label for="name">Nombre Completo *</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        placeholder="Tu nombre completo"
                        value="<?= e(post('name', '')) ?>"
                        class="<?= isset($errors['name']) ? 'error' : '' ?>"
                        required
                    >
                    <?php if (isset($errors['name'])): ?>
                        <div class="error-text"><?= $errors['name'] ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Correo Electrónico *</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="tu@email.com"
                        value="<?= e(post('email', '')) ?>"
                        class="<?= isset($errors['email']) ? 'error' : '' ?>"
                        required
                    >
                    <?php if (isset($errors['email'])): ?>
                        <div class="error-text"><?= $errors['email'] ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="phone">Teléfono *</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        placeholder="+1 (555) 123-4567"
                        value="<?= e(post('phone', '')) ?>"
                        class="<?= isset($errors['phone']) ? 'error' : '' ?>"
                        required
                    >
                    <?php if (isset($errors['phone'])): ?>
                        <div class="error-text"><?= $errors['phone'] ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group location-group">
                    <label for="address">Dirección</label>
                    <input 
                        type="text" 
                        id="address" 
                        name="address" 
                        placeholder="Tu dirección"
                        value="<?= e(post('address', '')) ?>"
                        style="padding-right: 100px;"
                    >
                    <button type="button" class="btn-location" id="btnLocation">
                        📍 Ubicar
                    </button>
                    <div id="locationStatus" class="location-status"></div>
                </div>
                
                <input type="hidden" name="latitude" id="latitude" value="<?= e(post('latitude', '')) ?>">
                <input type="hidden" name="longitude" id="longitude" value="<?= e(post('longitude', '')) ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Contraseña *</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Mínimo 6 caracteres"
                            class="<?= isset($errors['password']) ? 'error' : '' ?>"
                            required
                        >
                        <?php if (isset($errors['password'])): ?>
                            <div class="error-text"><?= $errors['password'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm">Confirmar *</label>
                        <input 
                            type="password" 
                            id="password_confirm" 
                            name="password_confirm" 
                            placeholder="Repetir contraseña"
                            class="<?= isset($errors['password_confirm']) ? 'error' : '' ?>"
                            required
                        >
                        <?php if (isset($errors['password_confirm'])): ?>
                            <div class="error-text"><?= $errors['password_confirm'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Crear mi cuenta</button>
            </form>
            
            <div class="links">
                <p>¿Ya tienes cuenta? <a href="login.php">Iniciar Sesión</a></p>
                <p style="margin-top: 10px;">¿Quieres ser partner? <a href="register-partner.php">Regístrate aquí</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Obtener ubicación del usuario
        const btnLocation = document.getElementById('btnLocation');
        const locationStatus = document.getElementById('locationStatus');
        const addressInput = document.getElementById('address');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        
        btnLocation.addEventListener('click', function() {
            if (!navigator.geolocation) {
                locationStatus.innerHTML = '❌ Geolocalización no soportada';
                locationStatus.className = 'location-status error';
                return;
            }
            
            btnLocation.classList.add('loading');
            btnLocation.textContent = '⏳ Buscando...';
            locationStatus.innerHTML = '';
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    latitudeInput.value = lat;
                    longitudeInput.value = lng;
                    
                    // Intentar obtener dirección con Google Maps Geocoding (si hay API key)
                    locationStatus.innerHTML = '✓ Ubicación obtenida';
                    locationStatus.className = 'location-status success';
                    
                    btnLocation.classList.remove('loading');
                    btnLocation.innerHTML = '✓ Listo';
                    
                    // Mostrar coordenadas en el campo si no hay dirección
                    if (!addressInput.value) {
                        addressInput.value = `Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`;
                    }
                },
                function(error) {
                    let errorMsg = 'Error desconocido';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg = 'Permiso denegado';
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
                    
                    btnLocation.classList.remove('loading');
                    btnLocation.innerHTML = '📍 Ubicar';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        });
    </script>
</body>
</html>
