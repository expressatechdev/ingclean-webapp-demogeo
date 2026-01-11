<?php
/**
 * INGClean API - Eliminar Partner (Admin)
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
$partnerId = $input['partner_id'] ?? null;
$forceDelete = $input['force'] ?? false;

if (!$partnerId) {
    jsonResponse(false, 'ID de partner requerido');
}

$db = Database::getInstance();

// Verificar que el partner existe
$partner = $db->fetchOne("SELECT * FROM partners WHERE id = :id", ['id' => $partnerId]);

if (!$partner) {
    jsonResponse(false, 'Partner no encontrado');
}

// Verificar órdenes activas
$activeOrders = $db->fetchOne(
    "SELECT COUNT(*) as count FROM orders 
     WHERE partner_id = :partner_id AND status NOT IN ('completed', 'cancelled')",
    ['partner_id' => $partnerId]
);

if ($activeOrders['count'] > 0 && !$forceDelete) {
    jsonResponse(false, 'El partner tiene ' . $activeOrders['count'] . ' orden(es) activa(s). No se puede eliminar.', [
        'active_orders' => $activeOrders['count'],
        'can_force' => false
    ]);
}

try {
    $db->beginTransaction();
    
    // Contar estadísticas antes de eliminar
    $stats = $db->fetchOne(
        "SELECT COUNT(*) as total_orders, COALESCE(SUM(total_earnings), 0) as earnings 
         FROM partners WHERE id = :id",
        ['id' => $partnerId]
    );
    
    $orderCount = $db->fetchOne(
        "SELECT COUNT(*) as count FROM orders WHERE partner_id = :id",
        ['id' => $partnerId]
    );
    
    // Eliminar notificaciones del partner
    $db->query("DELETE FROM notifications WHERE user_type = 'partner' AND user_id = :id", ['id' => $partnerId]);
    
    // Desasociar órdenes completadas/canceladas (ponerlas sin partner)
    $db->query(
        "UPDATE orders SET partner_id = NULL WHERE partner_id = :id AND status IN ('completed', 'cancelled')",
        ['id' => $partnerId]
    );
    
    // Eliminar el partner
    $db->query("DELETE FROM partners WHERE id = :id", ['id' => $partnerId]);
    
    $db->commit();
    
    appLog("Partner eliminado: {$partner['email']} (ID: {$partnerId}) por admin", 'info');
    
    jsonResponse(true, 'Partner eliminado correctamente', [
        'deleted_partner' => $partner['name'],
        'total_services' => $partner['total_services'],
        'total_earnings' => $partner['total_earnings']
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    appLog("Error eliminando partner: " . $e->getMessage(), 'error');
    jsonResponse(false, 'Error al eliminar el partner');
}
