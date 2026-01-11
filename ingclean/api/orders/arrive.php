<?php
/**
 * INGClean API - Partner llegó al destino
 */
define('INGCLEAN_APP', true);
require_once '../../includes/init.php';

header('Content-Type: application/json');

if (!isPost()) {
    jsonResponse(false, 'Método no permitido', null, 405);
}

if (!auth()->isLoggedIn() || !auth()->isPartner()) {
    jsonResponse(false, 'No autorizado', null, 401);
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['order_id'] ?? null;

if (!$orderId) {
    jsonResponse(false, 'Order ID requerido');
}

$db = Database::getInstance();
$partnerId = auth()->getUserId();

try {
    $db->beginTransaction();
    
    // Verificar que la orden pertenece al partner y está en estado correcto
    $order = $db->fetchOne(
        "SELECT * FROM orders WHERE id = :id AND partner_id = :partner_id AND status = 'paid'",
        ['id' => $orderId, 'partner_id' => $partnerId]
    );
    
    if (!$order) {
        jsonResponse(false, 'Orden no encontrada o no está en estado válido');
    }
    
    // Actualizar estado a "en tránsito completado" (llegó)
    $db->update(
        'orders',
        ['status' => 'in_transit'],
        'id = :id',
        ['id' => $orderId]
    );
    
    // Notificar al cliente
    $db->insert('notifications', [
        'user_type' => 'client',
        'user_id' => $order['client_id'],
        'order_id' => $orderId,
        'title' => '📍 Partner llegó',
        'message' => 'Tu profesional de limpieza ha llegado a tu ubicación.',
        'type' => 'partner_arrived'
    ]);
    
    $db->commit();
    
    jsonResponse(true, 'Llegada confirmada', [
        'order_id' => $orderId,
        'status' => 'in_transit'
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    appLog("Error confirmando llegada: " . $e->getMessage(), 'error');
    jsonResponse(false, 'Error al confirmar llegada');
}
