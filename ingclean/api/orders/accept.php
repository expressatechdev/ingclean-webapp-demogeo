<?php
/**
 * INGClean API - Aceptar Orden
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
$partner = auth()->getCurrentUser();

// Verificar que el partner está aprobado y disponible
if ($partner['status'] !== 'approved') {
    jsonResponse(false, 'Tu cuenta no está aprobada');
}

// Verificar que el partner no tiene otra orden activa
$activeOrder = $db->fetchOne(
    "SELECT id FROM orders WHERE partner_id = :partner_id AND status IN ('accepted', 'paid', 'in_transit', 'in_progress') LIMIT 1",
    ['partner_id' => $partnerId]
);

if ($activeOrder) {
    jsonResponse(false, 'Ya tienes un servicio activo. Complétalo primero.');
}

try {
    $db->beginTransaction();
    
    // Intentar tomar la orden (solo si está pending y sin partner)
    $result = $db->query(
        "UPDATE orders SET partner_id = :partner_id, status = 'accepted', accepted_at = NOW() 
         WHERE id = :order_id AND status = 'pending' AND partner_id IS NULL",
        ['partner_id' => $partnerId, 'order_id' => $orderId]
    );
    
    if ($result->rowCount() === 0) {
        $db->rollback();
        jsonResponse(false, 'Esta orden ya fue tomada por otro partner');
    }
    
    // Obtener datos de la orden para la notificación
    $order = $db->fetchOne(
        "SELECT o.*, c.name as client_name, c.onesignal_id as client_onesignal
         FROM orders o
         JOIN clients c ON o.client_id = c.id
         WHERE o.id = :id",
        ['id' => $orderId]
    );
    
    // Notificar al cliente
    $db->insert('notifications', [
        'user_type' => 'client',
        'user_id' => $order['client_id'],
        'order_id' => $orderId,
        'title' => '✅ Partner Asignado',
        'message' => "{$partner['name']} aceptó tu solicitud. Por favor realiza el pago para que pueda ir a tu ubicación.",
        'type' => 'order_accepted'
    ]);
    
    // TODO: Enviar push notification con OneSignal
    
    $db->commit();
    
    jsonResponse(true, '¡Orden aceptada! Esperando pago del cliente.', [
        'order_id' => $orderId,
        'order_code' => $order['order_code']
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    appLog("Error aceptando orden: " . $e->getMessage(), 'error');
    jsonResponse(false, 'Error al aceptar la orden');
}
