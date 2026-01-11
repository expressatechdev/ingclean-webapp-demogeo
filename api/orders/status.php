<?php
/**
 * INGClean - API para obtener estado de una orden específica
 * Usado para polling en tiempo real
 */
require_once '../../includes/init.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!auth()->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$orderId = get('order_id') ?: get('order');

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'ID de orden requerido']);
    exit;
}

$user = auth()->getCurrentUser();
$userType = auth()->getUserType();
$db = Database::getInstance();

// Verificar que el usuario tiene acceso a esta orden
if ($userType === 'client') {
    $order = $db->fetchOne(
        "SELECT o.id, o.status, o.partner_id FROM orders o WHERE o.id = :order_id AND o.client_id = :user_id",
        ['order_id' => $orderId, 'user_id' => $user['id']]
    );
} elseif ($userType === 'partner') {
    $order = $db->fetchOne(
        "SELECT o.id, o.status, o.client_id FROM orders o WHERE o.id = :order_id AND o.partner_id = :user_id",
        ['order_id' => $orderId, 'user_id' => $user['id']]
    );
} else {
    echo json_encode(['success' => false, 'message' => 'Tipo de usuario no válido']);
    exit;
}

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Orden no encontrada']);
    exit;
}

echo json_encode([
    'success' => true,
    'order_id' => $order['id'],
    'status' => $order['status']
]);
