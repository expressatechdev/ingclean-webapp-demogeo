<?php
/**
 * INGClean API - Estado de Orden
 */
define('INGCLEAN_APP', true);
require_once '../../includes/init.php';

header('Content-Type: application/json');

if (!auth()->isLoggedIn()) {
    jsonResponse(false, 'No autorizado', null, 401);
}

$orderId = get('order');

if (!$orderId) {
    jsonResponse(false, 'Order ID requerido');
}

$db = Database::getInstance();
$userId = auth()->getUserId();
$userType = auth()->getUserType();

// Construir query según tipo de usuario
if ($userType === 'client') {
    $whereClause = "o.id = :id AND o.client_id = :user_id";
} elseif ($userType === 'partner') {
    $whereClause = "o.id = :id AND o.partner_id = :user_id";
} else {
    $whereClause = "o.id = :id"; // Admin ve todo
}

$order = $db->fetchOne(
    "SELECT o.*, s.name as service_name, s.price,
            c.name as client_name, c.phone as client_phone,
            p.name as partner_name, p.phone as partner_phone
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     JOIN clients c ON o.client_id = c.id
     LEFT JOIN partners p ON o.partner_id = p.id
     WHERE {$whereClause}",
    ['id' => $orderId, 'user_id' => $userId]
);

if (!$order) {
    jsonResponse(false, 'Orden no encontrada');
}

jsonResponse(true, 'Estado de orden', [
    'id' => $order['id'],
    'order_code' => $order['order_code'],
    'status' => $order['status'],
    'service_name' => $order['service_name'],
    'price' => $order['price'],
    'client_name' => $order['client_name'],
    'partner_name' => $order['partner_name'],
    'client_latitude' => $order['client_latitude'],
    'client_longitude' => $order['client_longitude'],
    'estimated_time' => $order['estimated_time'],
    'created_at' => $order['created_at'],
    'accepted_at' => $order['accepted_at'],
    'paid_at' => $order['paid_at']
]);
