<?php
/**
 * INGClean - API para obtener estado del partner
 * Usado para polling en tiempo real
 */
require_once '../../includes/init.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!auth()->isLoggedIn() || auth()->getUserType() !== 'partner') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = auth()->getCurrentUser();
$db = Database::getInstance();

// Obtener orden activa del partner
$activeOrder = $db->fetchOne(
    "SELECT o.id, o.order_code, o.status,
            s.name as service_name, s.price,
            c.name as client_name, c.phone as client_phone, c.address as client_address
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     JOIN clients c ON o.client_id = c.id
     WHERE o.partner_id = :partner_id 
     AND o.status IN ('accepted', 'paid', 'in_transit', 'in_progress')
     ORDER BY o.created_at DESC LIMIT 1",
    ['partner_id' => $user['id']]
);

// Obtener órdenes pendientes (disponibles para aceptar)
$pendingOrders = $db->fetchAll(
    "SELECT o.id, o.order_code, o.created_at,
            s.name as service_name, s.price,
            c.name as client_name, c.address as client_address
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     JOIN clients c ON o.client_id = c.id
     WHERE o.status = 'pending' AND o.partner_id IS NULL
     ORDER BY o.created_at DESC LIMIT 10"
);

// Calcular ganancias para cada orden pendiente
foreach ($pendingOrders as &$order) {
    $order['partner_earnings'] = calculatePartnerAmount($order['price']);
}

echo json_encode([
    'success' => true,
    'active_order' => $activeOrder ? [
        'id' => $activeOrder['id'],
        'order_code' => $activeOrder['order_code'] ?? 'ORD-'.$activeOrder['id'],
        'status' => $activeOrder['status'],
        'service_name' => $activeOrder['service_name'],
        'price' => $activeOrder['price'],
        'client_name' => $activeOrder['client_name'],
        'client_phone' => $activeOrder['client_phone'],
        'client_address' => $activeOrder['client_address']
    ] : null,
    'pending_orders' => $pendingOrders,
    'pending_count' => count($pendingOrders)
]);