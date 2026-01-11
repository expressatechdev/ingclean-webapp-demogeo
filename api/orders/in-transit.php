<?php
/**
 * INGClean API - Partner va en camino
 * Cambia estado de 'paid' a 'in_transit'
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
    
    // Verificar que la orden pertenece al partner y está pagada
    $order = $db->fetchOne(
        "SELECT * FROM orders WHERE id = :id AND partner_id = :partner_id AND status = 'paid'",
        ['id' => $orderId, 'partner_id' => $partnerId]
    );
    
    if (!$order) {
        jsonResponse(false, 'Orden no encontrada o no está pagada');
    }
    
    // Actualizar estado a "en tránsito"
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
        'title' => '🚗 Partner en camino',
        'message' => 'Tu profesional de limpieza está en camino.',
        'type' => 'partner_in_transit'
    ]);
    
    // Enviar push notification al cliente
    try {
        $partner = auth()->getCurrentUser();
        $notificationService = new NotificationService();
        $notificationService->notifyPartnerInTransit($order, $partner);
    } catch (Exception $e) {
        appLog("Error enviando push de en camino: " . $e->getMessage(), 'warning');
    }
    
    $db->commit();
    
    jsonResponse(true, 'En camino', [
        'order_id' => $orderId,
        'status' => 'in_transit'
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    appLog("Error cambiando a in_transit: " . $e->getMessage(), 'error');
    jsonResponse(false, 'Error al actualizar estado');
}
