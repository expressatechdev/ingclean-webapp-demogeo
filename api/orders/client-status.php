<?php
/**
 * INGClean - API para obtener estado de orden activa del cliente
 * Usado para polling en tiempo real
 */
require_once '../../includes/init.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!auth()->isLoggedIn() || auth()->getUserType() !== 'client') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = auth()->getCurrentUser();
$db = Database::getInstance();

// Obtener orden activa del cliente
$activeOrder = $db->fetchOne(
    "SELECT o.id, o.order_code, o.status, o.partner_id,
            s.name as service_name, s.price,
            p.name as partner_name, p.phone as partner_phone, p.photo as partner_photo
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     LEFT JOIN partners p ON o.partner_id = p.id
     WHERE o.client_id = :client_id 
     AND o.status NOT IN ('completed', 'cancelled')
     ORDER BY o.created_at DESC LIMIT 1",
    ['client_id' => $user['id']]
);

if ($activeOrder) {
    echo json_encode([
        'success' => true,
        'has_active_order' => true,
        'order' => [
            'id' => $activeOrder['id'],
            'order_code' => $activeOrder['order_code'] ?? 'ORD-'.$activeOrder['id'],
            'status' => $activeOrder['status'],
            'service_name' => $activeOrder['service_name'],
            'price' => $activeOrder['price'],
            'partner_id' => $activeOrder['partner_id'],
            'partner_name' => $activeOrder['partner_name'],
            'partner_phone' => $activeOrder['partner_phone'],
            'partner_photo' => $activeOrder['partner_photo']
        ]
    ]);
} else {
    echo json_encode([
        'success' => true,
        'has_active_order' => false,
        'order' => null
    ]);
}