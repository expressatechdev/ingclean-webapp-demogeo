<?php
/**
 * INGClean API - Eliminar Cliente (Admin)
 */
define('INGCLEAN_APP', true);
require_once '../../includes/init.php';

header('Content-Type: application/json');

if (!isPost()) {
    jsonResponse(false, 'Método no permitido', null, 405);
}

if (!auth()->isLoggedIn() || !auth()->isAdmin()) {
    jsonResponse(false, 'No autorizado', null, 401);
}

$input = json_decode(file_get_contents('php://input'), true);
$clientId = $input['client_id'] ?? null;
$forceDelete = $input['force'] ?? false;

if (!$clientId) {
    jsonResponse(false, 'ID de cliente requerido');
}

$db = Database::getInstance();

// Verificar que el cliente existe
$client = $db->fetchOne("SELECT * FROM clients WHERE id = :id", ['id' => $clientId]);

if (!$client) {
    jsonResponse(false, 'Cliente no encontrado');
}

// Verificar órdenes activas
$activeOrders = $db->fetchOne(
    "SELECT COUNT(*) as count FROM orders 
     WHERE client_id = :client_id AND status NOT IN ('completed', 'cancelled')",
    ['client_id' => $clientId]
);

if ($activeOrders['count'] > 0 && !$forceDelete) {
    jsonResponse(false, 'El cliente tiene ' . $activeOrders['count'] . ' orden(es) activa(s). No se puede eliminar.', [
        'active_orders' => $activeOrders['count'],
        'can_force' => false
    ]);
}

try {
    $db->beginTransaction();
    
    // Contar estadísticas antes de eliminar
    $stats = $db->fetchOne(
        "SELECT COUNT(*) as total_orders FROM orders WHERE client_id = :id",
        ['id' => $clientId]
    );
    
    // Eliminar notificaciones del cliente
    $db->query("DELETE FROM notifications WHERE user_type = 'client' AND user_id = :id", ['id' => $clientId]);
    
    // Eliminar órdenes del cliente (solo completadas y canceladas)
    $db->query("DELETE FROM orders WHERE client_id = :id AND status IN ('completed', 'cancelled')", ['id' => $clientId]);
    
    // Eliminar el cliente
    $db->query("DELETE FROM clients WHERE id = :id", ['id' => $clientId]);
    
    $db->commit();
    
    appLog("Cliente eliminado: {$client['email']} (ID: {$clientId}) por admin", 'info');
    
    jsonResponse(true, 'Cliente eliminado correctamente', [
        'deleted_client' => $client['name'],
        'deleted_orders' => $stats['total_orders']
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    appLog("Error eliminando cliente: " . $e->getMessage(), 'error');
    jsonResponse(false, 'Error al eliminar el cliente');
}
