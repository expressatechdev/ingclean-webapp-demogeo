<?php
/**
 * INGClean API - Iniciar servicio de limpieza
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
        "SELECT * FROM orders WHERE id = :id AND partner_id = :partner_id AND status = 'in_transit'",
        ['id' => $orderId, 'partner_id' => $partnerId]
    );
    
    if (!$order) {
        jsonResponse(false, 'Orden no encontrada o no está en estado válido');
    }
    
    // Actualizar estado a "en progreso"
    $db->update(
        'orders',
        [
            'status' => 'in_progress',
            'started_at' => date('Y-m-d H:i:s')
        ],
        'id = :id',
        ['id' => $orderId]
    );
    
    // Notificar al cliente
    $db->insert('notifications', [
        'user_type' => 'client',
        'user_id' => $order['client_id'],
        'order_id' => $orderId,
        'title' => '🧹 Limpieza iniciada',
        'message' => 'Tu profesional ha comenzado el servicio de limpieza.',
        'type' => 'service_started'
    ]);
    
    $db->commit();
    
    jsonResponse(true, 'Servicio iniciado', [
        'order_id' => $orderId,
        'status' => 'in_progress'
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    appLog("Error iniciando servicio: " . $e->getMessage(), 'error');
    jsonResponse(false, 'Error al iniciar servicio');
}
