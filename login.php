<?php
/**
 * INGClean - Login
 * AMPLIADO - Ocupa 80-85% de la pantalla
 */
require_once 'includes/init.php';

if (auth()->isLoggedIn()) {
    $userType = auth()->getUserType();
    if ($userType === 'client') redirect('/client/');
    elseif ($userType === 'partner') redirect('/partner/');
    elseif ($userType === 'admin') redirect('/admin/');
}

$error = '';
$success = '';

if (isPost()) {
    $email = post('email');
    $password = post('password');
    $userType = post('user_type', 'client');
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor completa todos los campos';
    } else {
        $result = auth()->login($email, $password, $userType);
        
        if ($result['success']) {
            if ($userType === 'client') redirect('/client/');
            elseif ($userType === 'partner') redirect('/partner/');
            elseif ($userType === 'admin') redirect('/admin/');
        } else {
            $error = $result['message'];
        }
    }
}

if (get('registered') === 'client') {
    $success = '¡Registro exitoso! Ya puedes iniciar sesión.';
}
if (get('registered') === 'partner') {
    $success = '¡Registro exitoso! Tu cuenta está pendiente de aprobación.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#0a1628">
    <meta name="apple-mobile-web-app-capable" content="yes">
    
    <title>Iniciar Sesión - <?= APP_NAME ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        html, body {
            height: 100%;
        }
        
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            min-height: 100dvh;
            background: linear-gradient(135deg, #0a1628 0%, #1a3a5c 50%, #0d2847 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            padding-top: max(16px, env(safe-area-inset-top));
            padding-bottom: max(16px, env(safe-area-inset-bottom));
        }
        
        .container {
            width: 100%;
            max-width: 480px;
            min-height: 85vh;
            min-height: 85dvh;
            display: flex;
            flex-direction: column;
        }
        
        /* Link volver */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #90e0ef;
            text-decoration: none;
            font-size: 1.1rem;
            padding: 10px 0;
            margin-bottom: 16px;
        }
        
        .back-link svg {
            width: 22px;
            height: 22px;
        }
        
        /* Card GRANDE */
        .card {
            flex: 1;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 28px;
            padding: 32px 28px;
            border: 1px solid rgba(0, 180, 216, 0.2);
            display: flex;
            flex-direction: column;
        }
        
        /* Logo section */
        .logo-section {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .logo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.1);
            padding: 6px;
            margin: 0 auto 14px;
            box-shadow: 0 0 30px rgba(0, 180, 216, 0.4);
        }
        
        .brand-name {
            font-size: 2rem;
            font-weight: 700;
            color: #ffffff;
        }
        
        .brand-name span {
            color: #00b4d8;
        }
        
        h2 {
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 24px;
        }
        
        /* Alerts */
        .alert {
            padding: 16px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-size: 1.05rem;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #fca5a5;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.5);
            color: #86efac;
        }
        
        /* Form container */
        .form-container {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        /* User Type Tabs GRANDES */
        .user-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 28px;
        }
        
        .user-tab {
            flex: 1;
            min-height: 54px;
            padding: 14px 10px;
            border-radius: 14px;
            border: 2px solid rgba(0, 180, 216, 0.3);
            background: transparent;
            color: #90e0ef;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            -webkit-appearance: none;
        }
        
        .user-tab:active {
            transform: scale(0.98);
        }
        
        .user-tab.active {
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            border-color: transparent;
            color: white;
        }
        
        /* Form GRANDE */
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            color: #caf0f8;
            font-size: 1.15rem;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            min-height: 60px;
            padding: 16px 18px;
            border-radius: 16px;
            border: 2px solid rgba(0, 180, 216, 0.2);
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            font-size: 16px;
            font-family: inherit;
            -webkit-appearance: none;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #00b4d8;
            background: rgba(255, 255, 255, 0.12);
        }
        
        .form-group input::placeholder {
            color: rgba(144, 224, 239, 0.5);
        }
        
        /* Spacer */
        .spacer {
            flex: 1;
            min-height: 20px;
        }
        
        /* Submit Button GRANDE */
        .btn-submit {
            width: 100%;
            min-height: 64px;
            padding: 18px;
            border-radius: 16px;
            border: none;
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            -webkit-appearance: none;
            box-shadow: 0 8px 30px rgba(0, 180, 216, 0.35);
        }
        
        .btn-submit:active {
            transform: scale(0.98);
        }
        
        /* Links */
        .links {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 180, 216, 0.2);
        }
        
        .links a {
            color: #00b4d8;
            text-decoration: none;
            font-size: 1.1rem;
            padding: 10px;
            display: inline-block;
            font-weight: 500;
        }
        
        .links p {
            color: #90e0ef;
            font-size: 1.1rem;
            margin-top: 12px;
        }
        
        /* Pantallas pequeñas */
        @media (max-height: 650px) {
            .card {
                padding: 24px 22px;
            }
            .logo {
                width: 70px;
                height: 70px;
            }
            .brand-name {
                font-size: 1.6rem;
            }
            h2 {
                font-size: 1.3rem;
                margin-bottom: 18px;
            }
            .form-group {
                margin-bottom: 18px;
            }
            .form-group input {
                min-height: 52px;
            }
            .btn-submit {
                min-height: 56px;
                font-size: 1.1rem;
            }
        }
        
        /* Pantallas más grandes */
        @media (min-height: 800px) {
            .card {
                padding: 40px 32px;
            }
            .logo {
                width: 100px;
                height: 100px;
            }
            .brand-name {
                font-size: 2.2rem;
            }
            .form-group input {
                min-height: 64px;
                font-size: 17px;
            }
            .btn-submit {
                min-height: 68px;
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Volver al inicio
        </a>
        
        <div class="card">
            <div class="logo-section">
                <img src="assets/img/logo.png" alt="<?= APP_NAME ?>" class="logo">
                <h1 class="brand-name">ING<span>Clean</span></h1>
            </div>
            
            <h2>Iniciar Sesión</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="form-container">
                <?= csrfField() ?>
                
                <div class="user-tabs">
                    <button type="button" class="user-tab active" data-type="client">Cliente</button>
                    <button type="button" class="user-tab" data-type="partner">Partner</button>
                    <button type="button" class="user-tab" data-type="admin">Admin</button>
                </div>
                
                <input type="hidden" name="user_type" id="user_type" value="client">
                
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="tu@email.com"
                        value="<?= e(post('email') ?? '') ?>"
                        required
                        autocomplete="email"
                        inputmode="email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <div class="spacer"></div>
                
                <button type="submit" class="btn-submit">Iniciar Sesión</button>
            </form>
            
            <div class="links">
                <a href="#">¿Olvidaste tu contraseña?</a>
                <p>¿No tienes cuenta? <a href="register-client.php">Regístrate</a></p>
            </div>
        </div>
    </div>
    
    <script>
        document.querySelectorAll('.user-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.user-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('user_type').value = this.dataset.type;
            });
        });
    </script>
</body>
</html>
