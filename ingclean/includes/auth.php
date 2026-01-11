<?php
/**
 * =====================================================
 * INGClean - Sistema de Autenticación
 * ACTUALIZADO: Validaciones de email y teléfono cruzadas
 * =====================================================
 */

// Evitar acceso directo
if (!defined('INGCLEAN_APP')) {
    die('Acceso directo no permitido');
}

class Auth {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Iniciar sesión
     */
    public function login($email, $password, $userType = 'client') {
        // Validar tipo de usuario
        $validTypes = ['client', 'partner', 'admin'];
        if (!in_array($userType, $validTypes)) {
            return ['success' => false, 'message' => 'Tipo de usuario inválido'];
        }
        
        // Determinar tabla
        $table = $userType === 'client' ? 'clients' : ($userType === 'partner' ? 'partners' : 'admins');
        
        // Buscar usuario
        $user = $this->db->fetchOne(
            "SELECT * FROM {$table} WHERE email = :email LIMIT 1",
            ['email' => $email]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'Credenciales incorrectas'];
        }
        
        // Verificar contraseña
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Credenciales incorrectas'];
        }
        
        // Verificar estado del usuario
        if ($userType === 'partner' && $user['status'] !== 'approved') {
            if ($user['status'] === 'pending') {
                return ['success' => false, 'message' => 'Tu cuenta está pendiente de aprobación'];
            } else {
                return ['success' => false, 'message' => 'Tu cuenta ha sido rechazada'];
            }
        }
        
        // Verificar si está activo (clients y admins)
        if (isset($user['is_active']) && !$user['is_active']) {
            return ['success' => false, 'message' => 'Tu cuenta está desactivada'];
        }
        
        // Crear sesión
        $this->createSession($user, $userType);
        
        // Actualizar último login (solo admins)
        if ($userType === 'admin') {
            $this->db->update('admins', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
        }
        
        // Remover password del array
        unset($user['password']);
        
        return [
            'success' => true,
            'message' => 'Login exitoso',
            'user' => $user,
            'user_type' => $userType
        ];
    }
    
    /**
     * Registrar cliente
     */
    public function registerClient($data) {
        // Validar campos requeridos
        $required = ['name', 'email', 'phone', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "El campo {$field} es requerido"];
            }
        }
        
        // Validar email
        if (!isValidEmail($data['email'])) {
            return ['success' => false, 'message' => 'Email inválido'];
        }
        
        // =====================================================
        // VALIDACIÓN CRUZADA DE EMAIL
        // Verificar que el email NO exista en clients NI en partners
        // =====================================================
        if ($this->emailExistsAnywhere($data['email'])) {
            return ['success' => false, 'message' => 'Este email ya está registrado en el sistema'];
        }
        
        // =====================================================
        // VALIDACIÓN CRUZADA DE TELÉFONO
        // Verificar que el teléfono NO exista en clients NI en partners
        // =====================================================
        $phone = $this->normalizePhone($data['phone']);
        if ($this->phoneExistsAnywhere($phone)) {
            return ['success' => false, 'message' => 'Este número de teléfono ya está registrado en el sistema'];
        }
        
        // Validar contraseña (mínimo 6 caracteres)
        if (strlen($data['password']) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
        }
        
        // Preparar datos
        $clientData = [
            'name' => sanitize($data['name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => $phone,
            'password' => password_hash($data['password'], PASSWORD_ALGO, PASSWORD_OPTIONS),
            'address' => isset($data['address']) ? sanitize($data['address']) : null,
            'latitude' => isset($data['latitude']) ? $data['latitude'] : null,
            'longitude' => isset($data['longitude']) ? $data['longitude'] : null
        ];
        
        try {
            $clientId = $this->db->insert('clients', $clientData);
            
            // Auto-login después del registro
            $this->login($data['email'], $data['password'], 'client');
            
            return [
                'success' => true,
                'message' => 'Registro exitoso',
                'client_id' => $clientId
            ];
            
        } catch (Exception $e) {
            appLog("Error en registro cliente: " . $e->getMessage(), 'error');
            return ['success' => false, 'message' => 'Error al registrar. Intenta de nuevo.'];
        }
    }
    
    /**
     * Registrar partner
     */
    public function registerPartner($data) {
        // Validar campos requeridos
        $required = ['name', 'email', 'phone', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "El campo {$field} es requerido"];
            }
        }
        
        // Validar email
        if (!isValidEmail($data['email'])) {
            return ['success' => false, 'message' => 'Email inválido'];
        }
        
        // =====================================================
        // VALIDACIÓN CRUZADA DE EMAIL
        // Verificar que el email NO exista en clients NI en partners
        // =====================================================
        if ($this->emailExistsAnywhere($data['email'])) {
            return ['success' => false, 'message' => 'Este email ya está registrado en el sistema'];
        }
        
        // =====================================================
        // VALIDACIÓN CRUZADA DE TELÉFONO
        // Verificar que el teléfono NO exista en clients NI en partners
        // =====================================================
        $phone = $this->normalizePhone($data['phone']);
        if ($this->phoneExistsAnywhere($phone)) {
            return ['success' => false, 'message' => 'Este número de teléfono ya está registrado en el sistema'];
        }
        
        // Validar contraseña
        if (strlen($data['password']) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
        }
        
        // Preparar datos
        $partnerData = [
            'name' => sanitize($data['name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => $phone,
            'password' => password_hash($data['password'], PASSWORD_ALGO, PASSWORD_OPTIONS),
            'photo' => isset($data['photo']) ? $data['photo'] : null,
            'status' => 'pending' // Requiere aprobación
        ];
        
        try {
            $partnerId = $this->db->insert('partners', $partnerData);
            
            // Notificar al admin (opcional)
            $this->notifyAdminNewPartner($partnerId, $partnerData['name']);
            
            return [
                'success' => true,
                'message' => 'Registro exitoso. Tu cuenta está pendiente de aprobación.',
                'partner_id' => $partnerId
            ];
            
        } catch (Exception $e) {
            appLog("Error en registro partner: " . $e->getMessage(), 'error');
            return ['success' => false, 'message' => 'Error al registrar. Intenta de nuevo.'];
        }
    }
    
    /**
     * Crear sesión de usuario
     */
    private function createSession($user, $userType) {
        // Regenerar ID de sesión por seguridad
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $userType;
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Guardar en tabla sessions
        $sessionData = [
            'id' => session_id(),
            'user_type' => $userType,
            'user_id' => $user['id'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'payload' => json_encode($_SESSION),
            'last_activity' => time()
        ];
        
        // Eliminar sesiones anteriores del mismo usuario
        $this->db->delete('sessions', 'user_type = :type AND user_id = :uid', [
            'type' => $userType,
            'uid' => $user['id']
        ]);
        
        $this->db->insert('sessions', $sessionData);
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            // Eliminar de tabla sessions
            $this->db->delete('sessions', 'id = :id', ['id' => session_id()]);
        }
        
        // Destruir sesión
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
        
        return ['success' => true, 'message' => 'Sesión cerrada'];
    }
    
    /**
     * Verificar si hay sesión activa
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Obtener usuario actual
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $userType = $_SESSION['user_type'];
        $userId = $_SESSION['user_id'];
        $table = $userType === 'client' ? 'clients' : ($userType === 'partner' ? 'partners' : 'admins');
        
        $user = $this->db->fetchOne(
            "SELECT * FROM {$table} WHERE id = :id",
            ['id' => $userId]
        );
        
        if ($user) {
            unset($user['password']);
            $user['user_type'] = $userType;
        }
        
        return $user;
    }
    
    /**
     * Obtener tipo de usuario actual
     */
    public function getUserType() {
        return $_SESSION['user_type'] ?? null;
    }
    
    /**
     * Obtener ID de usuario actual
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Verificar si es admin
     */
    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['user_type'] === 'admin';
    }
    
    /**
     * Verificar si es partner
     */
    public function isPartner() {
        return $this->isLoggedIn() && $_SESSION['user_type'] === 'partner';
    }
    
    /**
     * Verificar si es cliente
     */
    public function isClient() {
        return $this->isLoggedIn() && $_SESSION['user_type'] === 'client';
    }
    
    /**
     * Requerir login (redirige si no está logueado)
     */
    public function requireLogin($allowedTypes = ['client', 'partner', 'admin']) {
        if (!$this->isLoggedIn()) {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
        
        if (!in_array($_SESSION['user_type'], $allowedTypes)) {
            header('Location: ' . APP_URL . '/login.php?error=unauthorized');
            exit;
        }
    }
    
    /**
     * Requerir admin
     */
    public function requireAdmin() {
        $this->requireLogin(['admin']);
    }
    
    /**
     * Verificar si email existe en una tabla específica
     */
    public function emailExists($email, $table = 'clients') {
        return $this->db->exists($table, 'email = :email', ['email' => strtolower(trim($email))]);
    }
    
    /**
     * =====================================================
     * NUEVA FUNCIÓN: Verificar si email existe en CUALQUIER tabla
     * Busca en clients Y partners
     * =====================================================
     */
    public function emailExistsAnywhere($email) {
        $email = strtolower(trim($email));
        
        // Verificar en clients
        if ($this->db->exists('clients', 'email = :email', ['email' => $email])) {
            return true;
        }
        
        // Verificar en partners
        if ($this->db->exists('partners', 'email = :email', ['email' => $email])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * =====================================================
     * NUEVA FUNCIÓN: Verificar si teléfono existe en una tabla
     * =====================================================
     */
    public function phoneExists($phone, $table = 'clients') {
        $phone = $this->normalizePhone($phone);
        return $this->db->exists($table, 'phone = :phone', ['phone' => $phone]);
    }
    
    /**
     * =====================================================
     * NUEVA FUNCIÓN: Verificar si teléfono existe en CUALQUIER tabla
     * Busca en clients Y partners
     * =====================================================
     */
    public function phoneExistsAnywhere($phone) {
        $phone = $this->normalizePhone($phone);
        
        // Verificar en clients
        if ($this->db->exists('clients', 'phone = :phone', ['phone' => $phone])) {
            return true;
        }
        
        // Verificar en partners
        if ($this->db->exists('partners', 'phone = :phone', ['phone' => $phone])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * =====================================================
     * NUEVA FUNCIÓN: Normalizar número de teléfono
     * Elimina espacios, guiones, paréntesis, etc.
     * =====================================================
     */
    public function normalizePhone($phone) {
        // Eliminar todo excepto números y el signo +
        $normalized = preg_replace('/[^0-9+]/', '', $phone);
        
        // Si empieza con 1 y tiene 11 dígitos (USA), agregar +
        if (strlen($normalized) === 11 && substr($normalized, 0, 1) === '1') {
            $normalized = '+' . $normalized;
        }
        
        // Si tiene 10 dígitos (USA sin código de país), agregar +1
        if (strlen($normalized) === 10) {
            $normalized = '+1' . $normalized;
        }
        
        return $normalized;
    }
    
    /**
     * Actualizar OneSignal ID
     */
    public function updateOneSignalId($playerId) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userType = $_SESSION['user_type'];
        $userId = $_SESSION['user_id'];
        $table = $userType === 'client' ? 'clients' : ($userType === 'partner' ? 'partners' : 'admins');
        
        return $this->db->update(
            $table,
            ['onesignal_id' => $playerId],
            'id = :id',
            ['id' => $userId]
        );
    }
    
    /**
     * Actualizar ubicación (para partners y clients)
     */
    public function updateLocation($latitude, $longitude) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userType = $_SESSION['user_type'];
        $userId = $_SESSION['user_id'];
        
        if ($userType === 'admin') {
            return false;
        }
        
        $table = $userType === 'client' ? 'clients' : 'partners';
        
        return $this->db->update(
            $table,
            ['latitude' => $latitude, 'longitude' => $longitude],
            'id = :id',
            ['id' => $userId]
        );
    }
    
    /**
     * Notificar admin de nuevo partner
     */
    private function notifyAdminNewPartner($partnerId, $partnerName) {
        try {
            $this->db->insert('notifications', [
                'user_type' => 'admin',
                'user_id' => 1, // Admin principal
                'title' => 'Nuevo Partner Registrado',
                'message' => "El partner {$partnerName} se ha registrado y espera aprobación.",
                'type' => 'system',
                'data' => json_encode(['partner_id' => $partnerId])
            ]);
        } catch (Exception $e) {
            appLog("Error notificando admin: " . $e->getMessage(), 'error');
        }
    }
}

/**
 * Función helper para obtener instancia de Auth
 */
function auth() {
    static $auth = null;
    if ($auth === null) {
        $auth = new Auth();
    }
    return $auth;
}