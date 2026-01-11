<?php
/**
 * INGClean - Landing Page
 */
require_once 'includes/init.php';

// Si ya está logueado, redirigir al dashboard correspondiente
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - <?= APP_TAGLINE ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
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
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Efecto de partículas/grid de fondo */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                linear-gradient(rgba(0, 180, 216, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 180, 216, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
        }
        
        /* Efecto de brillo */
        .glow-effect {
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(0, 180, 216, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            top: -100px;
            right: -100px;
            pointer-events: none;
        }
        
        .glow-effect-2 {
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(0, 119, 182, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -50px;
            left: -50px;
            pointer-events: none;
        }
        
        .container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        
        .logo-container {
            margin-bottom: 30px;
        }
        
        .logo {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            box-shadow: 
                0 0 30px rgba(0, 180, 216, 0.3),
                0 0 60px rgba(0, 180, 216, 0.1);
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 30px rgba(0, 180, 216, 0.3), 0 0 60px rgba(0, 180, 216, 0.1); }
            50% { box-shadow: 0 0 40px rgba(0, 180, 216, 0.5), 0 0 80px rgba(0, 180, 216, 0.2); }
        }
        
        .brand-name {
            font-size: 2.5rem;
            font-weight: 700;
            color: #ffffff;
            margin-top: 20px;
            text-shadow: 0 0 20px rgba(0, 180, 216, 0.5);
        }
        
        .brand-name span {
            color: #00b4d8;
        }
        
        .tagline {
            font-size: 1rem;
            color: #90e0ef;
            margin-top: 5px;
            font-weight: 300;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .welcome-text {
            color: #caf0f8;
            font-size: 1.1rem;
            margin: 30px 0;
            line-height: 1.6;
        }
        
        .buttons-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 18px 30px;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        
        .btn-client {
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            box-shadow: 0 10px 30px rgba(0, 180, 216, 0.3);
        }
        
        .btn-client:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 180, 216, 0.4);
        }
        
        .btn-partner {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 2px solid rgba(0, 180, 216, 0.5);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .btn-partner:hover {
            background: rgba(0, 180, 216, 0.2);
            border-color: #00b4d8;
            transform: translateY(-3px);
        }
        
        .btn-icon {
            width: 24px;
            height: 24px;
        }
        
        .login-link {
            margin-top: 30px;
            color: #90e0ef;
            font-size: 0.95rem;
        }
        
        .login-link a {
            color: #00b4d8;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .login-link a:hover {
            color: #48cae4;
            text-decoration: underline;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid rgba(0, 180, 216, 0.2);
        }
        
        .feature {
            text-align: center;
        }
        
        .feature-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .feature-text {
            color: #caf0f8;
            font-size: 0.85rem;
        }
        
        @media (max-width: 480px) {
            .brand-name {
                font-size: 2rem;
            }
            
            .logo {
                width: 140px;
                height: 140px;
            }
            
            .features {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="glow-effect"></div>
    <div class="glow-effect-2"></div>
    
    <div class="container">
        <div class="logo-container">
            <img src="assets/img/logo.png" alt="INGClean Logo" class="logo">
            <h1 class="brand-name">ING<span>Clean</span></h1>
            <p class="tagline">Revolutionary Cleaning Services</p>
        </div>
        
        <p class="welcome-text">
            Conectamos clientes con profesionales de limpieza de confianza. 
            Servicio rápido, seguro y de calidad.
        </p>
        
        <div class="buttons-container">
            <a href="register-client.php" class="btn btn-client">
                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Necesito Limpieza
            </a>
            
            <a href="register-partner.php" class="btn btn-partner">
                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Quiero ser Partner
            </a>
        </div>
        
        <p class="login-link">
            ¿Ya tienes cuenta? <a href="login.php">Iniciar Sesión</a>
        </p>
        
        <div class="features">
            <div class="feature">
                <div class="feature-icon">⚡</div>
                <div class="feature-text">Servicio Rápido</div>
            </div>
            <div class="feature">
                <div class="feature-icon">🔒</div>
                <div class="feature-text">Pago Seguro</div>
            </div>
            <div class="feature">
                <div class="feature-icon">📍</div>
                <div class="feature-text">GPS en Vivo</div>
            </div>
        </div>
    </div>
</body>
</html>
