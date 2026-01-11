<?php
/**
 * INGClean - Registro de Partner
 */
require_once 'includes/init.php';

// Si ya está logueado, redirigir
if (auth()->isLoggedIn()) {
    redirect('/partner/');
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
        'password_confirm' => $_POST['password_confirm'] ?? ''
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
    
    // Procesar foto si se subió
    $photoPath = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            $errors['photo'] = 'Solo imágenes JPG, PNG, GIF o WEBP';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors['photo'] = 'Máximo 5MB';
        } else {
            // Crear directorio si no existe
            $uploadDir = 'uploads/partners/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generar nombre único
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'partner_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $photoPath = $uploadDir . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $photoPath)) {
                $errors['photo'] = 'Error al subir la imagen';
                $photoPath = null;
            }
        }
    }
    
    // Si no hay errores, registrar
    if (empty($errors)) {
        $data['photo'] = $photoPath;
        $result = auth()->registerPartner($data);
        
        if ($result['success']) {
            redirect('/login.php?registered=partner');
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
    <title>Registro Partner - <?= APP_NAME ?></title>
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
        
        .alert-info {
            background: rgba(0, 180, 216, 0.2);
            border: 1px solid rgba(0, 180, 216, 0.5);
            color: #90e0ef;
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
        
        /* Photo upload styles */
        .photo-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        
        .photo-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: 3px dashed rgba(0, 180, 216, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .photo-preview:hover {
            border-color: #00b4d8;
            background: rgba(0, 180, 216, 0.1);
        }
        
        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-preview .placeholder {
            color: #90e0ef;
            font-size: 2.5rem;
        }
        
        .photo-upload input[type="file"] {
            display: none;
        }
        
        .photo-label {
            color: #90e0ef;
            font-size: 0.85rem;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .photo-label:hover {
            color: #00b4d8;
        }
        
        .benefits {
            background: rgba(0, 180, 216, 0.1);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .benefits h4 {
            color: #00b4d8;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .benefits ul {
            list-style: none;
            color: #caf0f8;
            font-size: 0.85rem;
        }
        
        .benefits li {
            padding: 5px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .benefits li::before {
            content: '✓';
            color: #00b4d8;
            font-weight: bold;
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
            
            <h2>Únete como Partner</h2>
            <p class="subtitle">Genera ingresos con servicios de limpieza</p>
            
            <div class="benefits">
                <h4>💼 Beneficios de ser Partner</h4>
                <ul>
                    <li>Recibe el 65% de cada servicio</li>
                    <li>Horarios flexibles</li>
                    <li>Pagos semanales garantizados</li>
                    <li>Soporte 24/7</li>
                </ul>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            
            <div class="alert alert-info">
                ⏳ Tu cuenta será revisada por nuestro equipo antes de activarse.
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data" id="registerForm">
                <?= csrfField() ?>
                
                <!-- Photo Upload -->
                <div class="form-group">
                    <label>Foto de Perfil</label>
                    <div class="photo-upload">
                        <label for="photo" class="photo-preview" id="photoPreview">
                            <span class="placeholder">👤</span>
                        </label>
                        <input type="file" id="photo" name="photo" accept="image/*">
                        <label for="photo" class="photo-label">Click para subir foto</label>
                    </div>
                    <?php if (isset($errors['photo'])): ?>
                        <div class="error-text" style="text-align: center;"><?= $errors['photo'] ?></div>
                    <?php endif; ?>
                </div>
                
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
                
                <button type="submit" class="btn-submit">Enviar Solicitud</button>
            </form>
            
            <div class="links">
                <p>¿Ya tienes cuenta? <a href="login.php">Iniciar Sesión</a></p>
                <p style="margin-top: 10px;">¿Eres cliente? <a href="register-client.php">Regístrate aquí</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Preview de imagen
        const photoInput = document.getElementById('photo');
        const photoPreview = document.getElementById('photoPreview');
        
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
