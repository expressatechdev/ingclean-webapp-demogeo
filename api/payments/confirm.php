<?php
/**
 * INGClean API - Confirmar Pago
 * Envía notificación push al partner cuando el cliente paga
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
        "SELECT o.*, s.price, s.name as service_name 
         FROM orders o 
         JOIN services s ON o.service_id = s.id
         WHERE o.id = :id AND o.client_id = :client_id AND o.status = 'accepted'",
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
        // Insertar notificación en BD
        $db->insert('notifications', [
            'user_type' => 'partner',
            'user_id' => $order['partner_id'],
            'order_id' => $orderId,
            'title' => '💳 Pago Recibido',
            'message' => 'El cliente ha pagado el servicio. ¡Es hora de ir!',
            'type' => 'payment_completed'
        ]);
        
        // ========== ENVIAR PUSH NOTIFICATION ==========
        try {
            // Cargar servicios de notificación
            require_once INCLUDES_PATH . 'NotificationService.php';
            
            // Enviar por OneSignal + FCM
            $notificationService = new NotificationService();
            $notificationService->notifyPaymentReceived($order);
            
            appLog("Push enviado al partner {$order['partner_id']} - Pago recibido", 'info');
            
        } catch (Exception $e) {
            appLog("Error enviando push de pago: " . $e->getMessage(), 'warning');
        }
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