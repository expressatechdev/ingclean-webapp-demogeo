<?php
/**
 * INGClean API - Cancelar Orden (Partner)
 * El partner no recibe recompensa si cancela
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
    
    // Obtener orden (solo el partner asignado puede cancelar)
    $order = $db->fetchOne(
        "SELECT * FROM orders WHERE id = :id AND partner_id = :partner_id AND status IN ('paid', 'in_transit')",
        ['id' => $orderId, 'partner_id' => $partnerId]
    );
    
    if (!$order) {
        jsonResponse(false, 'Orden no encontrada o no se puede cancelar en este estado');
    }
    
    // Cancelar orden
    $db->update(
        'orders',
        [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancelled_by' => 'partner',
            'notes' => ($order['notes'] ? $order['notes'] . "\n" : '') . "[Cancelado por el partner - Cliente requiere reembolso]"
        ],
        'id = :id',
        ['id' => $orderId]
    );
    
    // Notificar al cliente
    $db->insert('notifications', [
        'user_type' => 'client',
        'user_id' => $order['client_id'],
        'order_id' => $orderId,
        'title' => '❌ Servicio Cancelado',
        'message' => 'El profesional ha cancelado el servicio. Contacta a finanzas@ingclean.com para la devolución de tu dinero.',
        'type' => 'order_cancelled'
    ]);
    
    // Marcar el pago como pendiente de reembolso
    $db->update(
        'payments',
        ['status' => 'refund_pending'],
        'order_id = :order_id',
        ['order_id' => $orderId]
    );
    
    $db->commit();
    
    jsonResponse(true, 'Servicio cancelado. No recibirás recompensa por este servicio.', [
        'order_id' => $orderId,
        'status' => 'cancelled'
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    appLog("Error cancelando orden (partner): " . $e->getMessage(), 'error');
    jsonResponse(false, 'Error al cancelar el servicio');
}