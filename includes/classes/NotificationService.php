<?php
/**
 * INGClean - Servicio de Notificaciones Push (OneSignal)
 * 
 * Uso:
 *   $notif = new NotificationService();
 *   $notif->sendToPartners('Nueva orden disponible', 'Limpieza Básica - $1.00', ['order_id' => 123]);
 *   $notif->sendToUser('client', $userId, 'Partner en camino', 'Tu partner llegará en 5 minutos');
 */

class NotificationService {
    
    private $appId;
    private $apiKey;
    private $apiUrl = 'https://onesignal.com/api/v1/notifications';
    private $enabled;
    
    public function __construct() {
        $this->appId = ONESIGNAL_APP_ID;
        $this->apiKey = ONESIGNAL_REST_API_KEY;
        $this->enabled = defined('ONESIGNAL_ENABLED') ? ONESIGNAL_ENABLED : false;
    }
    
    /**
     * Enviar notificación a todos los partners disponibles
     */
    public function sendToPartners($title, $message, $data = [], $url = null) {
        return $this->send([
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'url' => $url,
            'filters' => [
                ['field' => 'tag', 'key' => 'user_type', 'relation' => '=', 'value' => 'partner'],
                ['operator' => 'AND'],
                ['field' => 'tag', 'key' => 'is_available', 'relation' => '=', 'value' => '1']
            ]
        ]);
    }
    
    /**
     * Enviar notificación a todos los partners (disponibles o no)
     */
    public function sendToAllPartners($title, $message, $data = [], $url = null) {
        return $this->send([
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'url' => $url,
            'filters' => [
                ['field' => 'tag', 'key' => 'user_type', 'relation' => '=', 'value' => 'partner']
            ]
        ]);
    }
    
    /**
     * Enviar notificación a un usuario específico por su external_id
     * @param string $userType - 'client' o 'partner'
     * @param int $userId - ID del usuario en la BD
     */
    public function sendToUser($userType, $userId, $title, $message, $data = [], $url = null) {
        $externalId = $userType . '_' . $userId;
        
        return $this->send([
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'url' => $url,
            'include_aliases' => [
                'external_id' => [$externalId]
            ],
            'target_channel' => 'push'
        ]);
    }
    
    /**
     * Enviar notificación a múltiples usuarios
     * @param array $externalIds - Array de external_ids ['client_1', 'partner_5', ...]
     */
    public function sendToUsers($externalIds, $title, $message, $data = [], $url = null) {
        return $this->send([
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'url' => $url,
            'include_aliases' => [
                'external_id' => $externalIds
            ],
            'target_channel' => 'push'
        ]);
    }
    
    /**
     * Enviar notificación a todos los suscriptores
     */
    public function sendToAll($title, $message, $data = [], $url = null) {
        return $this->send([
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'url' => $url,
            'included_segments' => ['All']
        ]);
    }
    
    /**
     * Método principal para enviar notificación
     */
    private function send($options) {
        if (!$this->enabled) {
            appLog('OneSignal desactivado, notificación no enviada: ' . $options['title'], 'info');
            return ['success' => false, 'message' => 'OneSignal desactivado'];
        }
        
        $payload = [
            'app_id' => $this->appId,
            'headings' => ['en' => $options['title']],
            'contents' => ['en' => $options['message']],
            'chrome_web_icon' => APP_URL . '/assets/img/logo.png',
            'firefox_icon' => APP_URL . '/assets/img/logo.png'
        ];
        
        // Agregar datos adicionales
        if (!empty($options['data'])) {
            $payload['data'] = $options['data'];
        }
        
        // Agregar URL de destino
        if (!empty($options['url'])) {
            $payload['url'] = $options['url'];
            $payload['web_url'] = $options['url'];
        }
        
        // Configurar destinatarios
        if (isset($options['included_segments'])) {
            $payload['included_segments'] = $options['included_segments'];
        } elseif (isset($options['include_aliases'])) {
            $payload['include_aliases'] = $options['include_aliases'];
            $payload['target_channel'] = $options['target_channel'] ?? 'push';
        } elseif (isset($options['filters'])) {
            $payload['filters'] = $options['filters'];
        }
        
        // Configuración adicional
        $payload['priority'] = 10; // Alta prioridad
        $payload['ttl'] = 86400;   // 24 horas de vida
        
        // Sonido personalizado (opcional)
        $payload['android_sound'] = 'notification';
        $payload['ios_sound'] = 'notification.wav';
        
        return $this->makeRequest($payload);
    }
    
    /**
     * Hacer la petición HTTP a OneSignal
     */
    private function makeRequest($payload) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Authorization: Basic ' . $this->apiKey
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            appLog('OneSignal cURL error: ' . $error, 'error');
            return ['success' => false, 'message' => 'Error de conexión', 'error' => $error];
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            appLog('OneSignal notificación enviada: ' . $payload['headings']['en'], 'info', [
                'recipients' => $result['recipients'] ?? 0,
                'id' => $result['id'] ?? null
            ]);
            return [
                'success' => true,
                'message' => 'Notificación enviada',
                'data' => $result
            ];
        } else {
            appLog('OneSignal error: ' . $response, 'error', ['http_code' => $httpCode]);
            return [
                'success' => false,
                'message' => 'Error al enviar notificación',
                'error' => $result
            ];
        }
    }
    
    // =====================================================
    // MÉTODOS HELPER PARA CASOS ESPECÍFICOS DE INGCLEAN
    // =====================================================
    
    /**
     * Notificar nueva orden a partners disponibles
     */
    public function notifyNewOrder($order) {
        $serviceName = $order['service_name'] ?? 'Servicio de limpieza';
        // Mostrar solo la ganancia del partner, NO el precio total
        $partnerEarnings = isset($order['price']) ? calculatePartnerAmount($order['price']) : 0;
        $earningsText = formatPrice($partnerEarnings);
        $address = $order['client_address'] ?? '';
        
        return $this->sendToPartners(
            '🧹 ¡Nueva orden disponible!',
            "{$serviceName} - Ganas: {$earningsText}\n📍 {$address}",
            [
                'type' => 'new_order',
                'order_id' => $order['id'] ?? null
            ],
            APP_URL . '/partner/'
        );
    }
    
    /**
     * Notificar al cliente que un partner aceptó su orden
     */
    public function notifyOrderAccepted($order, $partner) {
        $partnerName = $partner['name'] ?? 'Tu partner';
        
        return $this->sendToUser(
            'client',
            $order['client_id'],
            '✅ ¡Partner asignado!',
            "{$partnerName} ha aceptado tu solicitud. Procede al pago.",
            [
                'type' => 'order_accepted',
                'order_id' => $order['id'],
                'partner_id' => $partner['id'] ?? null
            ],
            APP_URL . '/client/payment.php?order=' . $order['id']
        );
    }
    
    /**
     * Notificar al partner que el cliente pagó
     */
    public function notifyPaymentReceived($order) {
        return $this->sendToUser(
            'partner',
            $order['partner_id'],
            '💳 ¡Pago recibido!',
            'El cliente ha pagado. Puedes dirigirte al destino.',
            [
                'type' => 'payment_received',
                'order_id' => $order['id']
            ],
            APP_URL . '/partner/active-service.php?order=' . $order['id']
        );
    }
    
    /**
     * Notificar al cliente que el partner va en camino
     */
    public function notifyPartnerInTransit($order, $partner) {
        $partnerName = $partner['name'] ?? 'Tu partner';
        
        return $this->sendToUser(
            'client',
            $order['client_id'],
            '🚗 Partner en camino',
            "{$partnerName} se dirige a tu ubicación.",
            [
                'type' => 'partner_in_transit',
                'order_id' => $order['id']
            ],
            APP_URL . '/client/tracking.php?order=' . $order['id']
        );
    }
    
    /**
     * Notificar al cliente que el partner llegó
     */
    public function notifyPartnerArrived($order) {
        return $this->sendToUser(
            'client',
            $order['client_id'],
            '📍 ¡Partner llegó!',
            'Tu profesional de limpieza ha llegado.',
            [
                'type' => 'partner_arrived',
                'order_id' => $order['id']
            ],
            APP_URL . '/client/tracking.php?order=' . $order['id']
        );
    }
    
    /**
     * Notificar al cliente que el servicio inició
     */
    public function notifyServiceStarted($order) {
        return $this->sendToUser(
            'client',
            $order['client_id'],
            '🧹 Limpieza iniciada',
            'Tu profesional ha comenzado el servicio.',
            [
                'type' => 'service_started',
                'order_id' => $order['id']
            ],
            APP_URL . '/client/tracking.php?order=' . $order['id']
        );
    }
    
    /**
     * Notificar que el servicio se completó
     */
    public function notifyServiceCompleted($order, $partnerEarnings = null) {
        // Al cliente
        $this->sendToUser(
            'client',
            $order['client_id'],
            '🎉 ¡Servicio completado!',
            'Tu limpieza ha sido completada. ¡Gracias por usar INGClean!',
            [
                'type' => 'service_completed',
                'order_id' => $order['id']
            ],
            APP_URL . '/client/'
        );
        
        // Al partner
        $earningsMsg = $partnerEarnings ? formatPrice($partnerEarnings) . ' ganados' : '';
        return $this->sendToUser(
            'partner',
            $order['partner_id'],
            '🎉 ¡Servicio completado!',
            "Excelente trabajo. {$earningsMsg}",
            [
                'type' => 'service_completed',
                'order_id' => $order['id']
            ],
            APP_URL . '/partner/'
        );
    }
    
    /**
     * Notificar cancelación de orden
     */
    public function notifyOrderCancelled($order, $cancelledBy = 'client') {
        if ($cancelledBy === 'client' && !empty($order['partner_id'])) {
            // Notificar al partner
            return $this->sendToUser(
                'partner',
                $order['partner_id'],
                '❌ Orden cancelada',
                'El cliente ha cancelado la orden.',
                ['type' => 'order_cancelled', 'order_id' => $order['id']],
                APP_URL . '/partner/'
            );
        } elseif ($cancelledBy === 'partner') {
            // Notificar al cliente
            return $this->sendToUser(
                'client',
                $order['client_id'],
                '❌ Orden cancelada',
                'El partner ha cancelado la orden. Buscaremos otro para ti.',
                ['type' => 'order_cancelled', 'order_id' => $order['id']],
                APP_URL . '/client/'
            );
        }
    }
}
