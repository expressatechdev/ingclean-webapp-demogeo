<?php
/**
 * INGClean API - Toggle Disponibilidad Partner
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
$isAvailable = isset($input['is_available']) ? (bool)$input['is_available'] : false;

$db = Database::getInstance();
$partnerId = auth()->getUserId();

try {
    $db->update(
        'partners',
        ['is_available' => $isAvailable ? 1 : 0],
        'id = :id',
        ['id' => $partnerId]
    );
    
    jsonResponse(true, $isAvailable ? 'Ahora estás disponible' : 'Ya no estás disponible', [
        'is_available' => $isAvailable
    ]);
    
} catch (Exception $e) {
    appLog("Error toggle availability: " . $e->getMessage(), 'error');
    jsonResponse(false, 'Error al actualizar disponibilidad');
}
