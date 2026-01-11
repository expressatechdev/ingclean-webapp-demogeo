<?php
/**
 * INGClean API - Actualizar ubicación del partner
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
$latitude = $input['latitude'] ?? null;
$longitude = $input['longitude'] ?? null;
$orderId = $input['order_id'] ?? null;
$accuracy = $input['accuracy'] ?? null;
$speed = $input['speed'] ?? null;
$heading = $input['heading'] ?? null;

if (!$latitude || !$longitude) {
    jsonResponse(false, 'Coordenadas requeridas');
}

$db = Database::getInstance();
$partnerId = auth()->getUserId();

try {
    // Actualizar ubicación en la tabla de partners
    $db->update(
        'partners',
        [
            'latitude' => $latitude,
            'longitude' => $longitude
        ],
        'id = :id',
        ['id' => $partnerId]
    );
    
    // Si hay una orden activa, guardar en el historial de ubicaciones
    if ($orderId) {
        $db->insert('partner_locations', [
            'partner_id' => $partnerId,
            'order_id' => $orderId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'speed' => $speed,
            'heading' => $heading
        ]);
    }
    
    jsonResponse(true, 'Ubicación actualizada', [
        'latitude' => $latitude,
        'longitude' => $longitude
    ]);
    
} catch (Exception $e) {
    appLog("Error actualizando ubicación: " . $e->getMessage(), 'error');
    jsonResponse(false, 'Error al actualizar ubicación');
}
