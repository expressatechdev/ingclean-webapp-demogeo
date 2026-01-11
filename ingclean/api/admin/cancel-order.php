<?php
/**
 * INGClean API - Cancelar Orden (Admin)
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
$orderId = $input['order_id'] ?? null;

if (!$orderId) {
    jsonResponse(false, 'Order ID requerido');
}

$db = Database::getInstance();

try {
    $db->beginTransaction();
    
    // Obtener orden
    $order = $db->fetchOne(
        "SELECT * FROM orders WHERE id = :id AND status NOT IN ('completed', 'cancelled')",
        ['id' => $orderId]
    );
    
    if (!$order) {
        jsonResponse(false, 'Orden no encontrada o ya finalizada');
    }
    
    // Cancelar orden
    $db->update(
        'orders',
        [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
            'notes' => ($order['notes'] ? $order['notes'] . "\n" : '') . "[Cancelada por administrador]"
        ],
        'id = :id',
        ['id' => $orderId]
    );
    
    // Notificar al cliente
    $db->insert('notifications', [
        'user_type' => 'client',
        'user_id' => $order['client_id'],
        'order_id' => $orderId,
        'title' => '❌ Orden Cancelada',
        'message' => 'Tu orden ha sido cancelada por el administrador.',
        'type' => 'order_cancelled'
    ]);
    
    // Notificar al partner si hay uno asignado
    if ($order['partner_id']) {
        $db->insert('notifications', [
            'user_type' => 'partner',
            'user_id' => $order['partner_id'],
            'order_id' => $orderId,
            'title' => '❌ Orden Cancelada',
            'message' => 'La orden ha sido cancelada por el administrador.',
            'type' => 'order_cancelled'
        ]);
    }
    
    // Si ya había un pago, marcarlo como refunded (en producción se haría el refund real en Stripe)
    $db->update(
        'payments',
        ['status' => 'refunded'],
        'order_id = :order_id',
        ['order_id' => $orderId]
    );
    
    $db->commit();
    
    jsonResponse(true, 'Orden cancelada exitosamente', [
        'order_id' => $orderId,
        'status' => 'cancelled'
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    appLog("Error cancelando orden: " . $e->getMessage(), 'error');
    jsonResponse(false, 'Error al cancelar la orden');
}
