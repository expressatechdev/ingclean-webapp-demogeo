<?php
/**
 * INGClean API - Confirmar Pago
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
$paymentIntentId = $input['payment_intent_id'] ?? null;

if (!$orderId || !$paymentIntentId) {
    jsonResponse(false, 'Datos incompletos');
}

$db = Database::getInstance();
$userId = auth()->getUserId();

try {
    $db->beginTransaction();
    
    // Verificar que la orden pertenece al usuario
    $order = $db->fetchOne(
        "SELECT * FROM orders WHERE id = :id AND client_id = :client_id AND status = 'accepted'",
        ['id' => $orderId, 'client_id' => $userId]
    );
    
    if (!$order) {
        jsonResponse(false, 'Orden no encontrada');
    }
    
    // Actualizar payment
    $db->update(
        'payments',
        [
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s')
        ],
        'order_id = :order_id AND stripe_payment_intent_id = :pi_id',
        ['order_id' => $orderId, 'pi_id' => $paymentIntentId]
    );
    
    // Actualizar orden a pagada
    $db->update(
        'orders',
        [
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s')
        ],
        'id = :id',
        ['id' => $orderId]
    );
    
    // Notificar al partner que el pago fue recibido
    if ($order['partner_id']) {
        $db->insert('notifications', [
            'user_type' => 'partner',
            'user_id' => $order['partner_id'],
            'order_id' => $orderId,
            'title' => '💳 Pago Recibido',
            'message' => 'El cliente ha pagado el servicio. ¡Es hora de ir!',
            'type' => 'payment_completed'
        ]);
        
        // Actualizar estado a "en tránsito"
        $db->update(
            'orders',
            ['status' => 'in_transit'],
            'id = :id',
            ['id' => $orderId]
        );
    }
    
    $db->commit();
    
    jsonResponse(true, 'Pago confirmado exitosamente', [
        'order_id' => $orderId,
        'status' => 'paid'
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    appLog("Error confirmando pago: " . $e->getMessage(), 'error');
    jsonResponse(false, 'Error al confirmar el pago');
}
