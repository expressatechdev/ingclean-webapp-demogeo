<?php
/**
 * INGClean - Login
 */
require_once 'includes/init.php';

// Si ya está logueado, redirigir
if (auth()->isLoggedIn()) {
    $userType = auth()->getUserType();
    if ($userType === 'client') {
        redirect('/client/');
    } elseif ($userType === 'partner') {
        redirect('/partner/');
    } elseif ($userType === 'admin') {
        redirect('/admin/');
    }
}

$error = '';
$success = '';

// Procesar login
if (isPost()) {
    $email = post('email');
    $password = post('password');
    $userType = post('user_type', 'client');
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor completa todos los campos';
    } else {
        $result = auth()->login($email, $password, $userType);
        
        if ($result['success']) {
            // Redirigir según tipo de usuario
            if ($userType === 'client') {
                redirect('/client/');
            } elseif ($userType === 'partner') {
                redirect('/partner/');
            } elseif ($userType === 'admin') {
                redirect('/admin/');
            }
        } else {
            $error = $result['message'];
        }
    }
}

// Mensaje de registro exitoso
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?= APP_NAME ?></title>
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
            max-width: 420px;
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
            margin-bottom: 30px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.1);
            padding: 5px;
        }
        
        .brand-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            margin-top: 10px;
        }
        
        .brand-name span {
            color: #00b4d8;
        }
        
        h2 {
            color: #ffffff;
            font-size: 1.3rem;
            font-weight: 600;
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
        
        .alert-success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.5);
            color: #86efac;
        }
        
        /* User Type Tabs */
        .user-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .user-tab {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            border: 2px solid rgba(0, 180, 216, 0.3);
            background: transparent;
            color: #90e0ef;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .user-tab:hover {
            border-color: rgba(0, 180, 216, 0.5);
        }
        
        .user-tab.active {
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            border-color: transparent;
            color: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #caf0f8;
            font-size: 0.9rem;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 12px;
            border: 2px solid rgba(0, 180, 216, 0.2);
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            font-size: 1rem;
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
        
        .btn-submit {
            width: 100%;
            padding: 16px;
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
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 180, 216, 0.2);
        }
        
        .links a {
            color: #00b4d8;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        
        .links a:hover {
            color: #48cae4;
            text-decoration: underline;
        }
        
        .links p {
            color: #90e0ef;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #90e0ef;
            text-decoration: none;
            font-size: 0.9rem;
            margin-bottom: 20px;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #00b4d8;
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
            
            <h2>Iniciar Sesión</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
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
                        value="<?= e(post('email', '')) ?>"
                        required
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
                    >
                </div>
                
                <button type="submit" class="btn-submit">Iniciar Sesión</button>
            </form>
            
            <div class="links">
                <a href="#">¿Olvidaste tu contraseña?</a>
                <p>¿No tienes cuenta? <a href="register-client.php">Regístrate aquí</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Manejo de tabs de tipo de usuario
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
