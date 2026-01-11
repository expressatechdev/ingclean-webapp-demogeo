<?php
/**
 * INGClean API - Verificar estado de orden
 * Usado para polling en tiempo real
 */
define('INGCLEAN_APP', true);
require_once '../../includes/init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

if (!auth()->isLoggedIn()) {
    jsonResponse(false, 'No autorizado', null, 401);
}

$orderId = get('order_id');
$currentStatus = get('current_status'); // Estado actual que tiene el cliente

if (!$orderId) {
    jsonResponse(false, 'Order ID requerido');
}

$db = Database::getInstance();
$userId = auth()->getUserId();
$userType = auth()->getUserType();

// Verificar que la orden pertenece al usuario
$whereClause = $userType === 'client' ? 'client_id = :user_id' : 'partner_id = :user_id';

$order = $db->fetchOne(
    "SELECT o.*, s.name as service_name, s.price,
            c.name as client_name, c.phone as client_phone, c.address as client_address,
            p.name as partner_name, p.phone as partner_phone, p.photo as partner_photo,
            p.current_latitude as partner_lat, p.current_longitude as partner_lng
     FROM orders o
     JOIN services s ON o.service_id = s.id
     JOIN clients c ON o.client_id = c.id
     LEFT JOIN partners p ON o.partner_id = p.id
     WHERE o.id = :order_id AND o.{$whereClause}",
    ['order_id' => $orderId, 'user_id' => $userId]
);

if (!$order) {
    jsonResponse(false, 'Orden no encontrada');
}

// Verificar si el estado cambió
$statusChanged = ($currentStatus && $currentStatus !== $order['status']);

// Mensajes según el cambio de estado - diferente para cliente vs partner
if ($userType === 'client') {
    $statusMessages = [
        'pending' => [
            'title' => '⏳ Buscando Partner',
            'message' => 'Estamos buscando un profesional cerca de ti...',
            'icon' => '🔍'
        ],
        'accepted' => [
            'title' => '✅ ¡Partner Asignado!',
            'message' => $order['partner_name'] . ' aceptó tu solicitud. Procede al pago.',
            'icon' => '👤'
        ],
        'paid' => [
            'title' => '💳 Pago Confirmado',
            'message' => 'Tu pago fue procesado. El partner se dirigirá a tu ubicación.',
            'icon' => '✅'
        ],
        'in_transit' => [
            'title' => '🚗 Partner en Camino',
            'message' => $order['partner_name'] . ' está en camino a tu ubicación.',
            'icon' => '🚗'
        ],
        'in_progress' => [
            'title' => '🧹 Limpieza en Progreso',
            'message' => 'Tu profesional está realizando la limpieza.',
            'icon' => '🧹'
        ],
        'completed' => [
            'title' => '🎉 ¡Servicio Completado!',
            'message' => '¡Gracias por usar INGClean!',
            'icon' => '🎉'
        ],
        'cancelled' => [
            'title' => '❌ Orden Cancelada',
            'message' => 'La orden ha sido cancelada.',
            'icon' => '❌'
        ]
    ];
} else {
    // Mensajes para Partner
    $partnerEarnings = calculatePartnerAmount($order['price']);
    $statusMessages = [
        'accepted' => [
            'title' => '✅ Orden Aceptada',
            'message' => 'Esperando el pago del cliente...',
            'icon' => '⏳'
        ],
        'paid' => [
            'title' => '💳 ¡Pago Recibido!',
            'message' => 'El cliente pagó. Ganas $' . number_format($partnerEarnings, 2) . '. ¡Dirígete al destino!',
            'icon' => '💰'
        ],
        'in_transit' => [
            'title' => '🚗 En Camino',
            'message' => 'Dirígete a la ubicación del cliente.',
            'icon' => '🚗'
        ],
        'in_progress' => [
            'title' => '🧹 Limpieza Iniciada',
            'message' => 'Servicio en progreso.',
            'icon' => '🧹'
        ],
        'completed' => [
            'title' => '🎉 ¡Servicio Completado!',
            'message' => '¡Excelente trabajo! Ganaste $' . number_format($partnerEarnings, 2),
            'icon' => '🎉'
        ],
        'cancelled' => [
            'title' => '❌ Orden Cancelada',
            'message' => 'El cliente canceló la orden.',
            'icon' => '❌'
        ]
    ];
}

$notification = null;
if ($statusChanged && isset($statusMessages[$order['status']])) {
    $notification = $statusMessages[$order['status']];
}

// Preparar datos de la orden según tipo de usuario
$orderData = [
    'order_code' => $order['order_code'],
    'service_name' => $order['service_name'],
    'client_name' => $order['client_name'],
    'client_phone' => $order['client_phone'],
    'client_address' => $order['client_address'],
    'client_latitude' => $order['client_latitude'],
    'client_longitude' => $order['client_longitude'],
    'partner_name' => $order['partner_name'],
    'partner_phone' => $order['partner_phone'],
    'partner_photo' => $order['partner_photo'],
    'partner_latitude' => $order['partner_lat'],
    'partner_longitude' => $order['partner_lng'],
    'created_at' => $order['created_at'],
    'accepted_at' => $order['accepted_at'],
    'started_at' => $order['started_at'],
    'completed_at' => $order['completed_at']
];

// Cliente ve precio total, Partner ve solo su ganancia
if ($userType === 'client') {
    $orderData['price'] = $order['price'];
} else {
    $orderData['earnings'] = calculatePartnerAmount($order['price']);
}

jsonResponse(true, 'OK', [
    'order_id' => $order['id'],
    'status' => $order['status'],
    'status_changed' => $statusChanged,
    'notification' => $notification,
    'order' => $orderData
]);
