<?php
/**
 * INGClean - Servicio de Notificaciones FCM v1 (Firebase Cloud Messaging)
 * Usa el método moderno con OAuth 2.0 y Service Account
 * CORREGIDO: Todos los valores en data son strings
 */

class FCMService {
    
    private $projectId = 'ingclean-2adb7';
    private $serviceAccountFile;
    private $fcmUrl;
    private $accessToken = null;
    private $tokenExpiry = 0;
    
    // Credenciales del Service Account (embebidas)
    private $serviceAccount = [
        'type' => 'service_account',
        'project_id' => 'ingclean-2adb7',
        'private_key_id' => 'f0569e7fb04e39d754c8482c816f2e6f0c41493b',
        'private_key' => '-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDBEj99HNYW6vVX
Ik+zCBPtTqHSw3BUKBnuRqsiTbhxOEbK0T5WYOoTrUulQTpz/7Lfw5l3DFFLg93B
3WpS0J+2lWlbFowdY2wAjh0ROfesFj/Hvp9GiSOQUZN1rcBFwUNqPgaZxbOrkbvO
5Uk/aeSnJTHx9I//b96SZ1wrbD8VC46ho5Cew6+Kq0J0lPg3aTAXsL5wwzeRYaxC
Ax2BDaga8kqhugvar/rFEWksI2A1r2ivkiZ5ZUMrCjTQ539bf9CcHoM2OSVUaIvF
YqF09zlX0odNW7u7czlzPVGWEtU/kOTXvIx/vpxF8tlFXJy2Fd2QROqn7PCJuqrC
+gsay+Y1AgMBAAECggEAGQQPxNq1JKAJ7OHNuNE9n7xV/FSFW+ocPgLbDqVKViUo
CwRkktWYzrbf+8gBVmFVoGecHVAzwliSJZnWOme8ofAnB/3aZr5okoPXYVGE9T+m
wO9CfOWs/XiMZi1+oasiXIQv38G9f2fxESQlQvmp/jw0BfkdpUet64NH7npmjAfK
9V6FYPh1EjF/+CK6fOJrj3E+xW/RsCNAjYNm7ijIjA855ETOgmejd/kG7Jh1KdbE
hcCZb71eFztiZknkGVBgAlMlvTfTyJfcgdCIvlLmFXkpa+oPlXekU3XgT+sw6qgG
dAw3XLd4m2tqlQV8nTvqUmsGix5YZ/7TtFAlrH3COwKBgQDz+FaWaiO50XjMTeMw
QdrYoIbJEd3UmlZXyJnQfqmZp0U8LpvHCJ6uVntGP72kGIsIeHxh+CL4pHL/jsoz
MpVjwCaQKqqIeRwVIdzsDldVV4OmlYSGWry8NmQRH9XixD2m/H2UsIJhzC8QerQa
yZAPW2VJMLqo6UcBHNd+afEm+wKBgQDKl2iZsAovMhZvKvgwAssue9PdcWOU3GU5
lumNHeTumv7dLFr6ZPVPZe8moL69Bj82KIzUo4r7iRf6+jQEar6FMpQVOML+lUas
Ck9ZshCtItXDIDKhumVOs5Ri0ktVKPpsREp1ABCMsSVEZkOVbEIL8G12UdyZWN+4
sEEHhhtgjwKBgFPrVWVx5w4Q1rt4AzDjRjMDrLlXMvXhjNevQfFs0EvxNKiJ472n
4mVXjBnS7RmX86MbRrWwU98xOflcFYNc3/Qq8VjfxD8jYZyHRGXSXQoXC0ru3WIV
rhwTnYIicEELfaWF7nCJ8p9PS9UgT/ly4eHWb1WotFxLucfRMvLh4DinAoGAAodV
ROXPmrszUHvm0SKXyqK9CDyME6WUld1uWNaQrvG8UKJnGEz+Stlo3MCQ4OcdDt3+
tAC+kVkqtXU+BPgYHK1+76zfsjHGygru5p20W5TduivCtgYPkaXoKjuZuRxj08oe
a8tuaXMkle/40/qf8Go1044+BKP5VuLMTP29hb0CgYBWMMXL9YL42DBbT5oBlhsm
Pel/fXs9W9D6fbPUIT/T5M8d8rRtvkUABK1raM2kRA1Y8OINkPouMGmyUVOep4Iw
o8Avlv/zueCdDesA3rewx5AGmGu63KX0pIGxHUxBUND1bffYyY7sN2OQtJxA2E44
QFOb1hsSX3UfU3rIrGCUIw==
-----END PRIVATE KEY-----',
        'client_email' => 'firebase-adminsdk-fbsvc@ingclean-2adb7.iam.gserviceaccount.com',
        'client_id' => '105584491221763052446',
        'token_uri' => 'https://oauth2.googleapis.com/token'
    ];
    
    public function __construct() {
        $this->fcmUrl = 'https://fcm.googleapis.com/v1/projects/' . $this->projectId . '/messages:send';
    }
    
    /**
     * Convertir todos los valores de un array a strings (requerido por FCM)
     */
    private function arrayValuesToString($array) {
        $result = [];
        foreach ($array as $key => $value) {
            $result[$key] = strval($value);
        }
        return $result;
    }
    
    /**
     * Obtener Access Token usando JWT
     */
    private function getAccessToken() {
        // Si el token aún es válido, reutilizarlo
        if ($this->accessToken && time() < $this->tokenExpiry - 60) {
            return $this->accessToken;
        }
        
        try {
            // Crear JWT Header
            $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
            
            // Crear JWT Payload
            $now = time();
            $payload = json_encode([
                'iss' => $this->serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $this->serviceAccount['token_uri'],
                'iat' => $now,
                'exp' => $now + 3600
            ]);
            
            // Codificar en Base64URL
            $base64Header = $this->base64UrlEncode($header);
            $base64Payload = $this->base64UrlEncode($payload);
            
            // Firmar con la clave privada
            $signatureInput = $base64Header . '.' . $base64Payload;
            $privateKey = openssl_pkey_get_private($this->serviceAccount['private_key']);
            
            if (!$privateKey) {
                appLog('FCM: Error al cargar clave privada', 'error');
                return null;
            }
            
            openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            $base64Signature = $this->base64UrlEncode($signature);
            
            $jwt = $signatureInput . '.' . $base64Signature;
            
            // Intercambiar JWT por Access Token
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->serviceAccount['token_uri'],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                appLog('FCM: Error obteniendo token - HTTP ' . $httpCode . ' - ' . $response, 'error');
                return null;
            }
            
            $tokenData = json_decode($response, true);
            
            if (isset($tokenData['access_token'])) {
                $this->accessToken = $tokenData['access_token'];
                $this->tokenExpiry = time() + ($tokenData['expires_in'] ?? 3600);
                appLog('FCM: Access token obtenido exitosamente', 'info');
                return $this->accessToken;
            }
            
            appLog('FCM: Respuesta sin access_token - ' . $response, 'error');
            return null;
            
        } catch (Exception $e) {
            appLog('FCM: Excepción obteniendo token - ' . $e->getMessage(), 'error');
            return null;
        }
    }
    
    /**
     * Codificar en Base64 URL-safe
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Enviar notificación a un usuario específico
     */
    public function sendToUser($userId, $userType, $title, $body, $data = []) {
        $db = Database::getInstance();
        
        $tokens = $db->fetchAll(
            "SELECT token FROM push_tokens WHERE user_id = :user_id AND user_type = :user_type",
            ['user_id' => $userId, 'user_type' => $userType]
        );
        
        if (empty($tokens)) {
            appLog("FCM: No hay tokens para user {$userType}_{$userId}", 'info');
            return false;
        }
        
        $sent = 0;
        foreach ($tokens as $t) {
            $result = $this->send($t['token'], $title, $body, $data);
            if ($result) {
                $sent++;
            }
        }
        
        return $sent > 0;
    }
    
    /**
     * Enviar notificación a todos los partners
     */
    public function sendToAllPartners($title, $body, $data = []) {
        $db = Database::getInstance();
        
        $tokens = $db->fetchAll(
            "SELECT token FROM push_tokens WHERE user_type = 'partner'"
        );
        
        if (empty($tokens)) {
            appLog("FCM: No hay tokens de partners registrados", 'info');
            return 0;
        }
        
        $sent = 0;
        foreach ($tokens as $t) {
            $result = $this->send($t['token'], $title, $body, $data);
            if ($result) {
                $sent++;
            }
        }
        
        appLog("FCM: Enviado a {$sent}/" . count($tokens) . " partners", 'info');
        return $sent;
    }
    
    /**
     * Enviar notificación a todos los partners disponibles
     */
    public function sendToAvailablePartners($title, $body, $data = []) {
        $db = Database::getInstance();
        
        $tokens = $db->fetchAll(
            "SELECT pt.token 
             FROM push_tokens pt
             JOIN partners p ON pt.user_id = p.id AND pt.user_type = 'partner'
             WHERE p.is_available = 1 AND p.status = 'approved'"
        );
        
        if (empty($tokens)) {
            appLog("FCM: No hay partners disponibles con tokens", 'info');
            return 0;
        }
        
        $sent = 0;
        foreach ($tokens as $t) {
            $result = $this->send($t['token'], $title, $body, $data);
            if ($result) {
                $sent++;
            }
        }
        
        appLog("FCM: Enviado a {$sent}/" . count($tokens) . " partners disponibles", 'info');
        return $sent;
    }
    
    /**
     * Enviar notificación FCM v1
     */
    private function send($token, $title, $body, $data = []) {
        $accessToken = $this->getAccessToken();
        
        if (!$accessToken) {
            appLog('FCM: No se pudo obtener access token', 'error');
            return false;
        }
        
        // IMPORTANTE: Todos los valores en data deben ser strings
        $dataPayload = $this->arrayValuesToString(array_merge($data, [
            'title' => $title,
            'body' => $body,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
        ]));
        
        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'channel_id' => 'ingclean_notifications',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                    ]
                ],
                'data' => $dataPayload
            ]
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->fcmUrl,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($message),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            appLog('FCM cURL error: ' . $error, 'error');
            return false;
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode === 200) {
            appLog("FCM: Notificación enviada exitosamente", 'info');
            return true;
        } else {
            // Si el token es inválido, eliminarlo
            if ($httpCode === 404 || ($httpCode === 400 && isset($result['error']))) {
                $errorCode = $result['error']['details'][0]['errorCode'] ?? '';
                if (in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
                    $this->removeInvalidToken($token);
                }
            }
            appLog("FCM HTTP Error: {$httpCode} - {$response}", 'error');
            return false;
        }
    }
    
    /**
     * Eliminar token inválido
     */
    private function removeInvalidToken($token) {
        $db = Database::getInstance();
        $db->delete('push_tokens', 'token = :token', ['token' => $token]);
        appLog("FCM: Token inválido eliminado", 'info');
    }
    
    // =====================================================
    // MÉTODOS HELPER PARA CASOS ESPECÍFICOS DE INGCLEAN
    // =====================================================
    
    /**
     * Notificar nueva orden a partners disponibles
     */
    public function notifyNewOrder($order) {
        $serviceName = $order['service_name'] ?? 'Servicio de limpieza';
        $partnerEarnings = isset($order['price']) ? calculatePartnerAmount($order['price']) : 0;
        $address = $order['client_address'] ?? 'Ubicación del cliente';
        
        return $this->sendToAvailablePartners(
            '🧹 ¡Nueva orden disponible!',
            "{$serviceName} - Ganas: " . formatPrice($partnerEarnings),
            [
                'type' => 'new_order',
                'order_id' => isset($order['id']) ? strval($order['id']) : '',
                'url' => '/partner/'
            ]
        );
    }
    
    /**
     * Notificar al cliente que un partner aceptó su orden
     */
    public function notifyOrderAccepted($order, $partner) {
        $partnerName = $partner['name'] ?? 'Tu partner';
        
        return $this->sendToUser(
            $order['client_id'],
            'client',
            '✅ ¡Partner asignado!',
            "{$partnerName} ha aceptado tu solicitud. Procede al pago.",
            [
                'type' => 'order_accepted',
                'order_id' => strval($order['id']),
                'url' => '/client/payment.php?order=' . $order['id']
            ]
        );
    }
    
    /**
     * Notificar al partner que el cliente pagó
     */
    public function notifyPaymentReceived($order) {
        $partnerEarnings = isset($order['price']) ? calculatePartnerAmount($order['price']) : 0;
        
        return $this->sendToUser(
            $order['partner_id'],
            'partner',
            '💳 ¡Pago recibido!',
            'El cliente ha pagado. Ganas ' . formatPrice($partnerEarnings) . '. ¡Dirígete al destino!',
            [
                'type' => 'payment_received',
                'order_id' => strval($order['id']),
                'url' => '/partner/active-service.php?order=' . $order['id']
            ]
        );
    }
    
    /**
     * Notificar al cliente que el partner va en camino
     */
    public function notifyPartnerInTransit($order, $partner) {
        $partnerName = $partner['name'] ?? 'Tu partner';
        
        return $this->sendToUser(
            $order['client_id'],
            'client',
            '🚗 Partner en camino',
            "{$partnerName} se dirige a tu ubicación.",
            [
                'type' => 'partner_in_transit',
                'order_id' => strval($order['id']),
                'url' => '/client/tracking.php?order=' . $order['id']
            ]
        );
    }
    
    /**
     * Notificar al cliente que el partner llegó
     */
    public function notifyPartnerArrived($order) {
        return $this->sendToUser(
            $order['client_id'],
            'client',
            '🏠 ¡Tu partner llegó!',
            'Tu profesional de limpieza ha llegado a tu ubicación.',
            [
                'type' => 'partner_arrived',
                'order_id' => strval($order['id']),
                'url' => '/client/tracking.php?order=' . $order['id']
            ]
        );
    }
    
    /**
     * Notificar al cliente que el servicio inició
     */
    public function notifyServiceStarted($order) {
        return $this->sendToUser(
            $order['client_id'],
            'client',
            '🧹 Limpieza iniciada',
            'Tu profesional ha comenzado el servicio de limpieza.',
            [
                'type' => 'service_started',
                'order_id' => strval($order['id']),
                'url' => '/client/tracking.php?order=' . $order['id']
            ]
        );
    }
    
    /**
     * Notificar que el servicio se completó
     */
    public function notifyServiceCompleted($order, $partnerEarnings = null) {
        // Al cliente
        $this->sendToUser(
            $order['client_id'],
            'client',
            '🎉 ¡Servicio completado!',
            'Tu limpieza ha sido completada. ¡Gracias por usar INGClean!',
            [
                'type' => 'service_completed',
                'order_id' => strval($order['id']),
                'url' => '/client/'
            ]
        );
        
        // Al partner
        $earningsMsg = $partnerEarnings ? formatPrice($partnerEarnings) . ' ganados' : '';
        return $this->sendToUser(
            $order['partner_id'],
            'partner',
            '🎉 ¡Servicio completado!',
            "Excelente trabajo. {$earningsMsg}",
            [
                'type' => 'service_completed',
                'order_id' => strval($order['id']),
                'url' => '/partner/'
            ]
        );
    }
    
    /**
     * Notificar cancelación de orden
     */
    public function notifyOrderCancelled($order, $cancelledBy = 'client') {
        if ($cancelledBy === 'client' && !empty($order['partner_id'])) {
            return $this->sendToUser(
                $order['partner_id'],
                'partner',
                '❌ Orden cancelada',
                'El cliente ha cancelado la orden.',
                [
                    'type' => 'order_cancelled',
                    'order_id' => strval($order['id']),
                    'url' => '/partner/'
                ]
            );
        } elseif ($cancelledBy === 'partner') {
            return $this->sendToUser(
                $order['client_id'],
                'client',
                '❌ Orden cancelada',
                'El partner ha cancelado. Contacta a finanzas@ingclean.com para reembolso.',
                [
                    'type' => 'order_cancelled',
                    'order_id' => strval($order['id']),
                    'url' => '/client/'
                ]
            );
        }
        return false;
    }
}