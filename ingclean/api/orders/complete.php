<?php
/**
 * INGClean API - Completar servicio de limpieza
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
    
    // Verificar que la orden pertenece al partner y está en progreso
    $order = $db->fetchOne(
        "SELECT o.*, s.price, s.name as service_name
         FROM orders o
         JOIN services s ON o.service_id = s.id
         WHERE o.id = :id AND o.partner_id = :partner_id AND o.status = 'in_progress'",
        ['id' => $orderId, 'partner_id' => $partnerId]
    );
    
    if (!$order) {
        jsonResponse(false, 'Orden no encontrada o no está en progreso');
    }
    
    // Calcular tiempo real del servicio
    $startTime = strtotime($order['started_at']);
    $actualTime = round((time() - $startTime) / 60); // en minutos
    
    // Actualizar orden a completada
    $db->update(
        'orders',
        [
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'actual_time' => $actualTime
        ],
        'id = :id',
        ['id' => $orderId]
    );
    
    // Notificar al cliente
    $db->insert('notifications', [
        'user_type' => 'client',
        'user_id' => $order['client_id'],
        'order_id' => $orderId,
        'title' => '✅ Servicio completado',
        'message' => "La limpieza ha sido completada. ¡Gracias por usar INGClean!",
        'type' => 'service_completed'
    ]);
    
    // Actualizar estadísticas del partner (ya lo hace el trigger, pero por si acaso)
    $partnerAmount = calculatePartnerAmount($order['price']);
    
    $db->commit();
    
    jsonResponse(true, '¡Servicio completado exitosamente!', [
        'order_id' => $orderId,
        'status' => 'completed',
        'earnings' => $partnerAmount,
        'duration_minutes' => $actualTime
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    appLog("Error completando servicio: " . $e->getMessage(), 'error');
    jsonResponse(false, 'Error al completar servicio');
}
