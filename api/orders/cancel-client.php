<?php
/**
 * INGClean API - Cancelar Orden (Cliente)
 * El cliente debe contactar a finanzas para reembolso
 * ACTUALIZADO: Incluye notificación push FCM al partner
 */
define('INGCLEAN_APP', true);
require_once '../../includes/init.php';

header('Content-Type: application/json');

if (!isPost()) {
    jsonResponse(false, 'Método no permitido', null, 405);
}

if (!auth()->isLoggedIn() || !auth()->isClient()) {
    jsonResponse(false, 'No autorizado', null, 401);
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['order_id'] ?? null;

if (!$orderId) {
    jsonResponse(false, 'Order ID requerido');
}

$db = Database::getInstance();
$clientId = auth()->getUserId();

try {
    $db->beginTransaction();
    
    // Obtener orden (solo el cliente dueño puede cancelar)
    $order = $db->fetchOne(
        "SELECT o.*, s.name as service_name, s.price 
         FROM orders o 
         JOIN services s ON o.service_id = s.id
         WHERE o.id = :id AND o.client_id = :client_id AND o.status IN ('paid', 'in_transit')",
        ['id' => $orderId, 'client_id' => $clientId]
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
            'cancelled_by' => 'client',
            'notes' => ($order['notes'] ? $order['notes'] . "\n" : '') . "[Cancelado por el cliente - Pendiente reembolso]"
        ],
        'id = :id',
        ['id' => $orderId]
    );
    
    // Notificar al partner (en BD)
    if ($order['partner_id']) {
        $db->insert('notifications', [
            'user_type' => 'partner',
            'user_id' => $order['partner_id'],
            'order_id' => $orderId,
            'title' => '❌ Servicio Cancelado',
            'message' => 'El cliente ha cancelado el servicio. No recibirás recompensa por esta orden.',
            'type' => 'order_cancelled'
        ]);
        
        // ========== ENVIAR PUSH NOTIFICATION FCM ==========
        try {
            require_once INCLUDES_PATH . 'NotificationService.php';
            
            $notificationService = new NotificationService();
            $notificationService->notifyOrderCancelled($order, 'client');
            
            appLog("Push enviado al partner {$order['partner_id']} - Cliente canceló orden", 'info');
            
        } catch (Exception $e) {
            appLog("Error enviando push de cancelación: " . $e->getMessage(), 'warning');
        }
        // ========== FIN PUSH NOTIFICATION ==========
    }
    
    // Marcar el pago como pendiente de reembolso
    $db->update(
        'payments',
        ['status' => 'refund_pending'],
        'order_id = :order_id',
        ['order_id' => $orderId]
    );
    
    $db->commit();
    
    jsonResponse(true, 'Servicio cancelado. Contacta a finanzas@ingclean.com para tu reembolso.', [
        'order_id' => $orderId,
        'status' => 'cancelled',
        'refund_contact' => 'finanzas@ingclean.com'
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    appLog("Error cancelando orden (cliente): " . $e->getMessage(), 'error');
    jsonResponse(false, 'Error al cancelar el servicio');
}