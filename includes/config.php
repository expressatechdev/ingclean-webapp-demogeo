<?php
/**
 * =====================================================
 * INGClean - Archivo de Configuración Principal
 * =====================================================
 * Version: 1.0 (Prototype)
 * Domain: demogeo.expressatech.net
 * =====================================================
 */

// Evitar acceso directo al archivo
if (!defined('INGCLEAN_APP')) {
    die('Acceso directo no permitido');
}

// =====================================================
// MODO DE LA APLICACIÓN
// =====================================================
define('APP_ENV', 'development'); // 'development' o 'production'
define('APP_DEBUG', true);        // true para ver errores, false en producción
define('APP_NAME', 'INGClean');
define('APP_TAGLINE', 'Revolutionary Cleaning Services');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://demogeo.expressatech.net');

// =====================================================
// CONFIGURACIÓN DE BASE DE DATOS (HOSTINGER)
// =====================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'u367875829_ingclean_demo');
define('DB_USER', 'u367875829_ingclean_demo');
define('DB_PASS', 'GustaBivi.1');
define('DB_CHARSET', 'utf8mb4');

// =====================================================
// CONFIGURACIÓN DE STRIPE
// =====================================================
// Modo: 'test' para pruebas, 'live' para producción
define('STRIPE_MODE', 'test');

// API Keys de TEST (para desarrollo)
// Obtener en: https://dashboard.stripe.com/test/apikeys
define('STRIPE_TEST_PUBLIC_KEY', 'pk_test_51SEAwfDNyy0iJ2zis3znCZM946YOHjomGU5qvOwwuQXCuLKYI0QCn9rQkwqlo3ja9SW7TZMo8EEmmCrEnHx37SEg00oyNlZx7f');
define('STRIPE_TEST_SECRET_KEY', 'sk_test_51SEAwfDNyy0iJ2zidjOlptZtqKxuZLaDtOc78DDqohG08fntOVHlvzVv6Q0ooAIwCwtNB3BfbEqxJWoB1YSHlgW700E4zjCFe6');

// API Keys de PRODUCCIÓN (cuando esté listo)
define('STRIPE_LIVE_PUBLIC_KEY', 'pk_live_XXXXXXXXXXXXXXXXXXXXXXXXX');
define('STRIPE_LIVE_SECRET_KEY', 'sk_live_XXXXXXXXXXXXXXXXXXXXXXXXX');

// Webhook Secret (para verificar webhooks de Stripe)
// Obtener en: https://dashboard.stripe.com/test/webhooks
define('STRIPE_WEBHOOK_SECRET', 'whsec_XXXXXXXXXXXXXXXXXXXXXXXXX');

// Función helper para obtener las keys según el modo
function getStripePublicKey() {
    return STRIPE_MODE === 'live' ? STRIPE_LIVE_PUBLIC_KEY : STRIPE_TEST_PUBLIC_KEY;
}

function getStripeSecretKey() {
    return STRIPE_MODE === 'live' ? STRIPE_LIVE_SECRET_KEY : STRIPE_TEST_SECRET_KEY;
}

// =====================================================
// CONFIGURACIÓN DE STRIPE CONNECT (Marketplace)
// =====================================================
// Porcentaje de comisión para INGClean
define('PLATFORM_FEE_PERCENT', 35);  // 35% para INGClean
define('PARTNER_PERCENT', 65);        // 65% para el Partner

// Moneda por defecto
define('STRIPE_CURRENCY', 'usd');

// =====================================================
// CONFIGURACIÓN DE GOOGLE MAPS
// =====================================================
// Obtener en: https://console.cloud.google.com/apis/credentials
define('GOOGLE_MAPS_API_KEY', 'AIzaSyAsEEMHGAnC2bXEOPeihlpBBgcHEYuNoO0');

// APIs habilitadas requeridas:
// - Maps JavaScript API
// - Geolocation API
// - Directions API
// - Distance Matrix API

// Configuración del mapa
define('GOOGLE_MAPS_DEFAULT_LAT', 25.7617);   // Miami, FL (cambiar según ubicación)
define('GOOGLE_MAPS_DEFAULT_LNG', -80.1918);
define('GOOGLE_MAPS_DEFAULT_ZOOM', 14);

// Radio máximo de búsqueda de partners (en km)
define('MAX_SEARCH_RADIUS_KM', 50);

// =====================================================
// CONFIGURACIÓN DE ONESIGNAL (Push Notifications)
// =====================================================
// Obtener en: https://app.onesignal.com/
define('ONESIGNAL_APP_ID', 'fe7b968c-e925-4d85-a479-f9f4ec4a5971');
define('ONESIGNAL_REST_API_KEY', 'os_v2_app_7z5zndhjevgyljdz7h2oysszofl6rz2eojauizv5e35ftd7a2qjqoqixav4xa2xptflcnqi2tdhgzmzpn2yjlhzg22xznn2tgmcfsui');

// Activar/desactivar notificaciones push
define('ONESIGNAL_ENABLED', true);

// =====================================================
// CONFIGURACIÓN DE SESIONES
// =====================================================
define('SESSION_NAME', 'ingclean_session');
define('SESSION_LIFETIME', 86400);      // 24 horas en segundos
define('SESSION_PATH', '/');
define('SESSION_SECURE', true);         // true si usas HTTPS
define('SESSION_HTTPONLY', true);

// =====================================================
// CONFIGURACIÓN DE SEGURIDAD
// =====================================================
// Clave secreta para tokens y encriptación
define('APP_SECRET_KEY', 'ING_CL34N_S3CR3T_K3Y_2024_PR0T0TYP3');

// Algoritmo de hash para contraseñas
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_OPTIONS', ['cost' => 10]);

// Tiempo de expiración de tokens (en segundos)
define('TOKEN_EXPIRY', 3600); // 1 hora

// =====================================================
// CONFIGURACIÓN DE ÓRDENES
// =====================================================
// Tiempo máximo para que un partner acepte (en segundos)
define('ORDER_ACCEPT_TIMEOUT', 60);  // 1 minuto

// Tiempo máximo para que un partner acepte antes de cancelar automáticamente
define('ORDER_EXPIRE_TIMEOUT', 600); // 10 minutos

// Intervalo de actualización de ubicación del partner (en segundos)
define('LOCATION_UPDATE_INTERVAL', 5);

// Distancia mínima para considerar que el partner "llegó" (en metros)
define('ARRIVAL_DISTANCE_THRESHOLD', 50);

// =====================================================
// CONFIGURACIÓN DE ARCHIVOS Y UPLOADS
// =====================================================
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// =====================================================
// CONFIGURACIÓN DE PRECIOS DE SERVICIOS
// =====================================================
// Estos valores se cargan desde la BD, pero sirven como fallback
define('SERVICE_PRICES', [
    'basic'  => 1.00,  // Limpieza Básica
    'medium' => 3.00,  // Limpieza Media
    'deep'   => 5.00   // Limpieza Profunda
]);

// =====================================================
// CONFIGURACIÓN DE EMAILS (Opcional - para notificaciones por email)
// =====================================================
define('MAIL_ENABLED', false);  // Cambiar a true si quieres enviar emails
define('MAIL_HOST', 'smtp.hostinger.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'noreply@ingclean.com');
define('MAIL_PASSWORD', '');
define('MAIL_FROM_NAME', 'INGClean');
define('MAIL_FROM_EMAIL', 'noreply@ingclean.com');

// =====================================================
// CONFIGURACIÓN DE ZONA HORARIA
// =====================================================
define('APP_TIMEZONE', 'America/New_York'); // Miami timezone
date_default_timezone_set(APP_TIMEZONE);

// =====================================================
// CONFIGURACIÓN DE LOGS
// =====================================================
define('LOG_ENABLED', true);
define('LOG_PATH', __DIR__ . '/../logs/');
define('LOG_LEVEL', 'debug'); // 'debug', 'info', 'warning', 'error'

// =====================================================
// MANEJO DE ERRORES
// =====================================================
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// =====================================================
// HEADERS DE SEGURIDAD
// =====================================================
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// =====================================================
// CONSTANTES DE RUTAS
// =====================================================
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDES_PATH', __DIR__ . '/');
define('API_PATH', ROOT_PATH . 'api/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');

// =====================================================
// AUTOLOAD DE CLASES (si se necesita)
// =====================================================
spl_autoload_register(function ($class) {
    $file = INCLUDES_PATH . 'classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// =====================================================
// FUNCIONES HELPER GLOBALES
// =====================================================

/**
 * Genera una respuesta JSON estándar
 */
function jsonResponse($success, $message = '', $data = null, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Genera un token seguro
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Sanitiza input del usuario
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Formatea precio
 */
function formatPrice($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Calcula la comisión de la plataforma
 */
function calculatePlatformFee($amount) {
    return round($amount * (PLATFORM_FEE_PERCENT / 100), 2);
}

/**
 * Calcula el monto para el partner
 */
function calculatePartnerAmount($amount) {
    return round($amount * (PARTNER_PERCENT / 100), 2);
}

/**
 * Log personalizado
 */
function appLog($message, $level = 'info', $context = []) {
    if (!LOG_ENABLED) return;
    
    $logFile = LOG_PATH . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
    $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}
