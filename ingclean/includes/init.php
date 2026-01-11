<?php
/**
 * =====================================================
 * INGClean - Inicializador Principal
 * =====================================================
 * 
 * Este archivo debe incluirse al inicio de cada página.
 * Carga todas las configuraciones y clases necesarias.
 * 
 * Uso: require_once 'includes/init.php';
 */

// Definir constante para permitir acceso a otros archivos
define('INGCLEAN_APP', true);

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_name('ingclean_session');
    session_start();
}

// Cargar configuración
require_once __DIR__ . '/config.php';

// Crear directorio de logs si no existe
if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

// Crear directorio de uploads si no existe
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

// Cargar clases principales
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

// Verificar conexión a base de datos al inicio
try {
    $db = Database::getInstance();
    appLog("Aplicación iniciada", 'info');
} catch (Exception $e) {
    appLog("Error iniciando aplicación: " . $e->getMessage(), 'error');
    if (APP_DEBUG) {
        die("Error de conexión: " . $e->getMessage());
    } else {
        die("Error del servidor. Por favor intenta más tarde.");
    }
}

/**
 * Función para obtener variable GET sanitizada
 */
function get($key, $default = null) {
    return isset($_GET[$key]) ? sanitize($_GET[$key]) : $default;
}

/**
 * Función para obtener variable POST sanitizada
 */
function post($key, $default = null) {
    return isset($_POST[$key]) ? sanitize($_POST[$key]) : $default;
}

/**
 * Función para redireccionar
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Función para mostrar mensaje flash
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Función para obtener y limpiar mensaje flash
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Función para verificar si es petición AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Función para verificar si es petición POST
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Función para obtener URL actual
 */
function currentUrl() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
           "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

/**
 * Función para cargar vista parcial
 */
function partial($name, $data = []) {
    extract($data);
    include ROOT_PATH . "partials/{$name}.php";
}

/**
 * Función para escapar HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Función para generar URL de asset
 */
function asset($path) {
    return APP_URL . '/assets/' . ltrim($path, '/');
}

/**
 * Función para generar URL
 */
function url($path = '') {
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Función para verificar CSRF token
 */
function csrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Función para generar campo CSRF
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

/**
 * Función para validar CSRF token
 */
function validateCsrf() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        if (isAjax()) {
            jsonResponse(false, 'Token de seguridad inválido', null, 403);
        }
        die('Token de seguridad inválido');
    }
    return true;
}

/**
 * Función para formatear fecha
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

/**
 * Función para tiempo relativo (hace X minutos)
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'Hace un momento';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return "Hace {$mins} " . ($mins == 1 ? 'minuto' : 'minutos');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "Hace {$hours} " . ($hours == 1 ? 'hora' : 'horas');
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "Hace {$days} " . ($days == 1 ? 'día' : 'días');
    } else {
        return formatDate($datetime, 'd/m/Y');
    }
}
