<?php
/**
 * INGClean API - Partner llegó al destino
 * Envía notificación push al cliente cuando el partner llega
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
        "SELECT o.*, s.name as service_name, s.price
         FROM orders o
         JOIN services s ON o.service_id = s.id
         WHERE o.id = :id AND o.partner_id = :partner_id AND o.status IN ('paid', 'in_transit')",
        ['id' => $orderId, 'partner_id' => $partnerId]
    );
    
    if (!$order) {
        jsonResponse(false, 'Orden no encontrada o no está en estado válido');
    }
    
    // Actualizar estado a "en tránsito completado" (llegó)
    $db->update(
        'orders',
        ['status' => 'in_transit', 'arrived_at' => date('Y-m-d H:i:s')],
        'id = :id',
        ['id' => $orderId]
    );
    
    // Insertar notificación en BD
    $db->insert('notifications', [
        'user_type' => 'client',
        'user_id' => $order['client_id'],
        'order_id' => $orderId,
        'title' => '🏠 Partner llegó',
        'message' => 'Tu profesional de limpieza ha llegado a tu ubicación.',
        'type' => 'partner_arrived'
    ]);
    
    // ========== ENVIAR PUSH NOTIFICATION ==========
    try {
        require_once INCLUDES_PATH . 'NotificationService.php';
        
        $notificationService = new NotificationService();
        $notificationService->notifyPartnerArrived($order);
        
        appLog("Push enviado al cliente {$order['client_id']} - Partner llegó", 'info');
        
    } catch (Exception $e) {
        appLog("Error enviando push de llegada: " . $e->getMessage(), 'warning');
    }
    
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
