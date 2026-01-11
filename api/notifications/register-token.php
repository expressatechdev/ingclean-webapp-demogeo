<?php
/**
 * INGClean API - Registrar Token FCM
 * Guarda el token de notificaciones push de la app nativa
 * FIXED: Detecta correctamente el tipo de usuario (client/partner)
 */
define('INGCLEAN_APP', true);
require_once '../../includes/init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$token = $data['token'] ?? '';
$platform = $data['platform'] ?? 'android';
$type = $data['type'] ?? 'fcm';
$clientUserType = $data['user_type'] ?? null; // Tipo enviado desde el JS

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Token requerido']);
    exit;
}

// Verificar autenticación
if (!auth()->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$user = auth()->getCurrentUser();
$userId = auth()->getUserId();

// Detectar tipo de usuario de múltiples formas
$userType = detectUserType($userId, $clientUserType);

$db = Database::getInstance();

try {
    // Primero, eliminar tokens antiguos del mismo usuario para evitar duplicados
    // (un dispositivo = un token, si cambia de cuenta se actualiza)
    $db->delete(
        'push_tokens',
        'token = :token AND (user_id != :user_id OR user_type != :user_type)',
        ['token' => $token, 'user_id' => $userId, 'user_type' => $userType]
    );
    
    // Verificar si ya existe el token para este usuario
    $existing = $db->fetchOne(
        "SELECT id FROM push_tokens WHERE token = :token AND user_id = :user_id AND user_type = :user_type",
        ['token' => $token, 'user_id' => $userId, 'user_type' => $userType]
    );
    
    if ($existing) {
        // Actualizar token existente
        $db->update(
            'push_tokens',
            [
                'platform' => $platform,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'id = :id',
            ['id' => $existing['id']]
        );
        
        appLog("Token FCM actualizado para {$userType}_{$userId}", 'info');
    } else {
        // Verificar si existe un token anterior para este usuario+tipo y eliminarlo
        $db->delete(
            'push_tokens',
            'user_id = :user_id AND user_type = :user_type',
            ['user_id' => $userId, 'user_type' => $userType]
        );
        
        // Insertar nuevo token
        $db->insert('push_tokens', [
            'user_id' => $userId,
            'user_type' => $userType,
            'token' => $token,
            'platform' => $platform,
            'type' => $type,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        appLog("Token FCM registrado para {$userType}_{$userId}", 'info');
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Token registrado',
        'user_type' => $userType,
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    appLog("Error registrando token FCM: " . $e->getMessage(), 'error');
    echo json_encode(['success' => false, 'message' => 'Error guardando token']);
}

/**
 * Detectar el tipo de usuario de forma robusta
 */
function detectUserType($userId, $clientUserType = null) {
    $db = Database::getInstance();
    
    // Método 1: Usar el tipo que envía el cliente JS (basado en la URL)
    if ($clientUserType === 'partner' || $clientUserType === 'client') {
        // Verificar que el usuario realmente existe en esa tabla
        if ($clientUserType === 'partner') {
            $partner = $db->fetchOne(
                "SELECT id FROM partners WHERE id = :id",
                ['id' => $userId]
            );
            if ($partner) {
                return 'partner';
            }
        } else {
            $client = $db->fetchOne(
                "SELECT id FROM clients WHERE id = :id",
                ['id' => $userId]
            );
            if ($client) {
                return 'client';
            }
        }
    }
    
    // Método 2: Verificar sesión de partner
    if (isset($_SESSION['partner_id']) && $_SESSION['partner_id'] == $userId) {
        return 'partner';
    }
    
    // Método 3: Verificar sesión de cliente
    if (isset($_SESSION['client_id']) && $_SESSION['client_id'] == $userId) {
        return 'client';
    }
    
    // Método 4: Buscar en ambas tablas
    $partner = $db->fetchOne(
        "SELECT id FROM partners WHERE id = :id",
        ['id' => $userId]
    );
    
    if ($partner && isset($_SESSION['partner_id'])) {
        return 'partner';
    }
    
    // Método 5: Usar auth()->getUserType() como fallback
    $authType = auth()->getUserType();
    if ($authType === 'partner' || $authType === 'client') {
        return $authType;
    }
    
    // Default
    return 'client';
}