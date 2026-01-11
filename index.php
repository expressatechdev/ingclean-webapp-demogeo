<?php
/**
 * INGClean - Landing Page
 * AMPLIADA - Ocupa 80-85% de la pantalla
 */
require_once 'includes/init.php';

if (auth()->isLoggedIn()) {
    $userType = auth()->getUserType();
    if ($userType === 'client') redirect('/client/');
    elseif ($userType === 'partner') redirect('/partner/');
    elseif ($userType === 'admin') redirect('/admin/');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#0a1628">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/android-launchericon-192-192.png">
    
    <title><?= APP_NAME ?> - <?= APP_TAGLINE ?></title>
    
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
            max-width: 500px;
            min-height: 85vh;
            min-height: 85dvh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 20px;
        }
        
        /* Logo GRANDE */
        .logo {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            margin-bottom: 20px;
            box-shadow: 0 0 50px rgba(0, 180, 216, 0.5);
        }
        
        .brand-name {
            font-size: 3rem;
            font-weight: 700;
            color: #ffffff;
            text-shadow: 0 0 25px rgba(0, 180, 216, 0.5);
        }
        
        .brand-name span {
            color: #00b4d8;
        }
        
        .tagline {
            font-size: 1.15rem;
            color: #90e0ef;
            margin-top: 8px;
            font-weight: 400;
        }
        
        .welcome-text {
            color: #caf0f8;
            font-size: 1.2rem;
            margin: 32px 0;
            line-height: 1.8;
            max-width: 380px;
        }
        
        /* Buttons GRANDES */
        .buttons {
            display: flex;
            flex-direction: column;
            gap: 16px;
            width: 100%;
            max-width: 340px;
            margin-top: 10px;
        }
        
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            width: 100%;
            min-height: 64px;
            padding: 18px 28px;
            border-radius: 18px;
            font-size: 1.2rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: transform 0.2s;
            -webkit-appearance: none;
        }
        
        .btn:active {
            transform: scale(0.98);
        }
        
        .btn-client {
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            box-shadow: 0 10px 35px rgba(0, 180, 216, 0.45);
        }
        
        .btn-partner {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 2px solid rgba(0, 180, 216, 0.5);
        }
        
        .btn-icon {
            width: 28px;
            height: 28px;
        }
        
        .login-link {
            margin-top: 28px;
            color: #90e0ef;
            font-size: 1.15rem;
        }
        
        .login-link a {
            color: #00b4d8;
            text-decoration: none;
            font-weight: 600;
            padding: 10px;
            display: inline-block;
        }
        
        /* Features GRANDES */
        .features {
            display: flex;
            justify-content: space-around;
            width: 100%;
            max-width: 340px;
            margin-top: 36px;
            padding-top: 28px;
            border-top: 1px solid rgba(0, 180, 216, 0.25);
        }
        
        .feature {
            text-align: center;
        }
        
        .feature-icon {
            font-size: 2.2rem;
            margin-bottom: 8px;
        }
        
        .feature-text {
            color: #caf0f8;
            font-size: 1.05rem;
            font-weight: 500;
        }
        
        /* Pantallas pequeñas - ajustar un poco */
        @media (max-height: 650px) {
            .container {
                min-height: 90vh;
            }
            .logo {
                width: 110px;
                height: 110px;
            }
            .brand-name {
                font-size: 2.4rem;
            }
            .welcome-text {
                margin: 20px 0;
                font-size: 1.05rem;
            }
            .btn {
                min-height: 56px;
                font-size: 1.1rem;
            }
            .features {
                margin-top: 24px;
            }
        }
        
        /* Pantallas más grandes */
        @media (min-height: 750px) {
            .logo {
                width: 150px;
                height: 150px;
            }
            .brand-name {
                font-size: 3.2rem;
            }
            .welcome-text {
                font-size: 1.25rem;
                margin: 40px 0;
            }
            .btn {
                min-height: 68px;
                font-size: 1.25rem;
            }
        }
        
        /* Desktop */
        @media (min-width: 768px) {
            .logo { 
                width: 160px; 
                height: 160px; 
            }
            .brand-name { 
                font-size: 3.5rem; 
            }
            .btn { 
                min-height: 72px; 
                font-size: 1.3rem;
                max-width: 380px;
            }
            .buttons {
                max-width: 380px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="assets/img/logo.png" alt="<?= APP_NAME ?>" class="logo">
        <h1 class="brand-name">ING<span>Clean</span></h1>
        <p class="tagline">Servicios de Limpieza Profesional</p>
        
        <p class="welcome-text">
            Conectamos clientes con profesionales de limpieza de confianza. Servicio rápido, seguro y de calidad.
        </p>
        
        <div class="buttons">
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
                <div class="feature-text">Rápido</div>
            </div>
            <div class="feature">
                <div class="feature-icon">🔒</div>
                <div class="feature-text">Seguro</div>
            </div>
            <div class="feature">
                <div class="feature-icon">📍</div>
                <div class="feature-text">GPS Vivo</div>
            </div>
        </div>
    </div>
    
    <!-- Service Worker Registration -->
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/service-worker.js')
                .then(function(registration) {
                    console.log('ServiceWorker registrado:', registration.scope);
                })
                .catch(function(error) {
                    console.log('Error registrando ServiceWorker:', error);
                });
        });
    }
    </script>
</body>
</html>