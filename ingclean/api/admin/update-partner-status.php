<?php
/**
 * INGClean API - Actualizar estado de Partner (Admin)
 */
define('INGCLEAN_APP', true);
require_once '../../includes/init.php';

header('Content-Type: application/json');

if (!isPost()) {
    jsonResponse(false, 'Método no permitido', null, 405);
}

if (!auth()->isLoggedIn() || !auth()->isAdmin()) {
    jsonResponse(false, 'No autorizado', null, 401);
}

$input = json_decode(file_get_contents('php://input'), true);
$partnerId = $input['partner_id'] ?? null;
$status = $input['status'] ?? null;

if (!$partnerId || !$status) {
    jsonResponse(false, 'Datos incompletos');
}

if (!in_array($status, ['pending', 'approved', 'rejected'])) {
    jsonResponse(false, 'Estado no válido');
}

$db = Database::getInstance();

try {
    // Verificar que existe el partner
    $partner = $db->fetchOne("SELECT * FROM partners WHERE id = :id", ['id' => $partnerId]);
    
    if (!$partner) {
        jsonResponse(false, 'Partner no encontrado');
    }
    
    // Actualizar estado
    $db->update(
        'partners',
        ['status' => $status],
        'id = :id',
        ['id' => $partnerId]
    );
    
    // Notificar al partner
    $titles = [
        'approved' => '🎉 ¡Cuenta Aprobada!',
        'rejected' => '❌ Solicitud Rechazada'
    ];
    
    $messages = [
        'approved' => '¡Felicidades! Tu cuenta ha sido aprobada. Ya puedes comenzar a recibir solicitudes de servicio.',
        'rejected' => 'Lo sentimos, tu solicitud no fue aprobada. Contacta a soporte para más información.'
    ];
    
    if (isset($titles[$status])) {
        $db->insert('notifications', [
            'user_type' => 'partner',
            'user_id' => $partnerId,
            'title' => $titles[$status],
            'message' => $messages[$status],
            'type' => 'account_status'
        ]);
    }
    
    $statusLabels = [
        'approved' => 'aprobado',
        'rejected' => 'rechazado',
        'pending' => 'pendiente'
    ];
    
    jsonResponse(true, "Partner {$statusLabels[$status]} exitosamente", [
        'partner_id' => $partnerId,
        'status' => $status
    ]);
    
} catch (Exception $e) {
    appLog("Error actualizando partner: " . $e->getMessage(), 'error');
    jsonResponse(false, 'Error al actualizar el partner');
}
