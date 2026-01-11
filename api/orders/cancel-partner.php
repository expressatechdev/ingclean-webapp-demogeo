<?php
/**
 * INGClean API - Cancelar Orden (Partner)
 * El partner no recibe recompensa si cancela
 * ACTUALIZADO: Incluye notificación push FCM al cliente
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
        "SELECT o.*, s.name as service_name, s.price 
         FROM orders o 
         JOIN services s ON o.service_id = s.id
         WHERE o.id = :id AND o.partner_id = :partner_id AND o.status IN ('paid', 'in_transit')",
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
    
    // Notificar al cliente (en BD)
    $db->insert('notifications', [
        'user_type' => 'client',
        'user_id' => $order['client_id'],
        'order_id' => $orderId,
        'title' => '❌ Servicio Cancelado',
        'message' => 'El profesional ha cancelado el servicio. Contacta a finanzas@ingclean.com para la devolución de tu dinero.',
        'type' => 'order_cancelled'
    ]);
    
    // ========== ENVIAR PUSH NOTIFICATION FCM ==========
    try {
        require_once INCLUDES_PATH . 'NotificationService.php';
        
        $notificationService = new NotificationService();
        $notificationService->notifyOrderCancelled($order, 'partner');
        
        appLog("Push enviado al cliente {$order['client_id']} - Partner canceló orden", 'info');
        
    } catch (Exception $e) {
        appLog("Error enviando push de cancelación: " . $e->getMessage(), 'warning');
    }
    // ========== FIN PUSH NOTIFICATION ==========
    
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