<?php
/**
 * INGClean API - Obtener Ubicación del Partner
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

// Verificar que el usuario tiene acceso a esta orden
if ($userType === 'client') {
    $order = $db->fetchOne(
        "SELECT o.*, p.current_latitude as partner_lat, p.current_longitude as partner_lng, 
                p.latitude, p.longitude, p.name as partner_name
         FROM orders o 
         LEFT JOIN partners p ON o.partner_id = p.id
         WHERE o.id = :id AND o.client_id = :client_id",
        ['id' => $orderId, 'client_id' => $userId]
    );
} elseif ($userType === 'partner') {
    $order = $db->fetchOne(
        "SELECT o.*, c.latitude, c.longitude, c.name as client_name
         FROM orders o 
         JOIN clients c ON o.client_id = c.id
         WHERE o.id = :id AND o.partner_id = :partner_id",
        ['id' => $orderId, 'partner_id' => $userId]
    );
} else {
    jsonResponse(false, 'No autorizado', null, 401);
}

if (!$order) {
    jsonResponse(false, 'Orden no encontrada');
}

// Obtener última ubicación registrada del partner (más precisa)
$latestLocation = null;
if ($userType === 'client' && $order['partner_id']) {
    $latestLocation = $db->fetchOne(
        "SELECT latitude, longitude, recorded_at 
         FROM partner_locations 
         WHERE partner_id = :partner_id 
         ORDER BY recorded_at DESC 
         LIMIT 1",
        ['partner_id' => $order['partner_id']]
    );
}

$responseData = [
    'order_id' => $order['id'],
    'order_status' => $order['status'],
    'latitude' => $latestLocation['latitude'] ?? $order['partner_lat'] ?? $order['latitude'],
    'longitude' => $latestLocation['longitude'] ?? $order['partner_lng'] ?? $order['longitude'],
    'updated_at' => $latestLocation['recorded_at'] ?? null
];

// Si es partner, devolver ubicación del cliente
if ($userType === 'partner') {
    $responseData['client_latitude'] = $order['client_latitude'];
    $responseData['client_longitude'] = $order['client_longitude'];
    $responseData['client_name'] = $order['client_name'];
}

jsonResponse(true, 'Ubicación obtenida', $responseData);
