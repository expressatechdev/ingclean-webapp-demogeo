<?php
/**
 * INGClean API - Cambiar Contraseña (Admin)
 * Permite al admin cambiar la contraseña de clientes y partners
 */
require_once '../../includes/init.php';

header('Content-Type: application/json');

// Solo admins pueden cambiar contraseñas
if (!auth()->isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);

$userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
$userType = isset($input['user_type']) ? $input['user_type'] : '';
$newPassword = isset($input['new_password']) ? $input['new_password'] : '';

// Validaciones
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
    exit;
}

if (!in_array($userType, ['client', 'partner'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo de usuario inválido']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
    exit;
}

// Determinar tabla
$table = $userType === 'client' ? 'clients' : 'partners';

$db = Database::getInstance();

// Verificar que el usuario existe
$user = $db->fetchOne("SELECT id, name, email FROM {$table} WHERE id = :id", ['id' => $userId]);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

// Hashear nueva contraseña
$hashedPassword = password_hash($newPassword, PASSWORD_ALGO, PASSWORD_OPTIONS);

// Actualizar contraseña
try {
    $updated = $db->update(
        $table,
        ['password' => $hashedPassword],
        'id = :id',
        ['id' => $userId]
    );
    
    if ($updated) {
        // Log de la acción (opcional)
        appLog("Admin cambió contraseña de {$userType} ID:{$userId} ({$user['email']})", 'info');
        
        echo json_encode([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la contraseña']);
    }
    
} catch (Exception $e) {
    appLog("Error cambiando contraseña: " . $e->getMessage(), 'error');
    echo json_encode(['success' => false, 'message' => 'Error al actualizar la contraseña']);
}