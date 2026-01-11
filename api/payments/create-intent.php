<?php
/**
 * INGClean API - Crear Payment Intent
 */
define('INGCLEAN_APP', true);
require_once '../../includes/init.php';

header('Content-Type: application/json');

// Solo POST
if (!isPost()) {
    jsonResponse(false, 'Método no permitido', null, 405);
}

// Verificar autenticación
if (!auth()->isLoggedIn() || !auth()->isClient()) {
    jsonResponse(false, 'No autorizado', null, 401);
}

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['order_id'] ?? null;

if (!$orderId) {
    jsonResponse(false, 'Order ID requerido');
}

$db = Database::getInstance();
$userId = auth()->getUserId();

// Obtener orden
$order = $db->fetchOne(
    "SELECT o.*, s.price 
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     WHERE o.id = :order_id AND o.client_id = :client_id AND o.status = 'accepted'",
    ['order_id' => $orderId, 'client_id' => $userId]
);

if (!$order) {
    jsonResponse(false, 'Orden no encontrada o no está pendiente de pago');
}

// Calcular montos
$totalAmount = $order['price'];
$amountInCents = (int)($totalAmount * 100); // Stripe usa centavos

try {
    // Incluir SDK de Stripe (versión simplificada usando cURL)
    $stripeSecretKey = getStripeSecretKey();
    
    // Crear Payment Intent usando cURL
    $ch = curl_init('https://api.stripe.com/v1/payment_intents');
    
    $postData = http_build_query([
        'amount' => $amountInCents,
        'currency' => STRIPE_CURRENCY,
        'metadata[order_id]' => $orderId,
        'metadata[order_code]' => $order['order_code'],
        'description' => "INGClean - Orden {$order['order_code']}"
    ]);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $stripeSecretKey,
            'Content-Type: application/x-www-form-urlencoded'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $paymentIntent = json_decode($response, true);
    
    if ($httpCode !== 200 || isset($paymentIntent['error'])) {
        $errorMsg = $paymentIntent['error']['message'] ?? 'Error al crear Payment Intent';
        appLog("Stripe error: " . $errorMsg, 'error');
        jsonResponse(false, $errorMsg);
    }
    
    // Guardar Payment Intent ID en la base de datos
    $db->insert('payments', [
        'order_id' => $orderId,
        'total_amount' => $totalAmount,
        'platform_fee' => calculatePlatformFee($totalAmount),
        'partner_amount' => calculatePartnerAmount($totalAmount),
        'stripe_payment_intent_id' => $paymentIntent['id'],
        'status' => 'pending'
    ]);
    
    jsonResponse(true, 'Payment Intent creado', [
        'client_secret' => $paymentIntent['client_secret'],
        'payment_intent_id' => $paymentIntent['id']
    ]);
    
} catch (Exception $e) {
    appLog("Error creando Payment Intent: " . $e->getMessage(), 'error');
    jsonResponse(false, 'Error al procesar el pago');
}
