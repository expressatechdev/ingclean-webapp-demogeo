<?php
/**
 * INGClean - Dashboard del Partner
 */
require_once '../includes/init.php';

auth()->requireLogin(['partner']);

$user = auth()->getCurrentUser();
$db = Database::getInstance();

// Verificar si el partner está aprobado
if ($user['status'] !== 'approved') {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cuenta Pendiente - <?= APP_NAME ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Poppins', sans-serif;
                min-height: 100vh;
                background: linear-gradient(135deg, #0a1628 0%, #1a3a5c 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .card {
                background: white;
                border-radius: 24px;
                padding: 40px;
                text-align: center;
                max-width: 400px;
            }
            .icon { font-size: 4rem; margin-bottom: 20px; }
            h1 { font-size: 1.5rem; color: #1e3a5f; margin-bottom: 10px; }
            p { color: #64748b; margin-bottom: 20px; }
            .status {
                display: inline-block;
                padding: 8px 20px;
                border-radius: 20px;
                font-size: 0.9rem;
                font-weight: 500;
            }
            .status-pending { background: #fef3c7; color: #92400e; }
            .status-rejected { background: #fee2e2; color: #dc2626; }
            .btn {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 30px;
                background: #0077b6;
                color: white;
                text-decoration: none;
                border-radius: 10px;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icon"><?= $user['status'] === 'pending' ? '⏳' : '❌' ?></div>
            <h1><?= $user['status'] === 'pending' ? 'Cuenta en Revisión' : 'Cuenta Rechazada' ?></h1>
            <p>
                <?= $user['status'] === 'pending' 
                    ? 'Tu solicitud está siendo revisada por nuestro equipo. Te notificaremos cuando sea aprobada.' 
                    : 'Lo sentimos, tu solicitud no fue aprobada. Contacta a soporte para más información.' 
                ?>
            </p>
            <span class="status status-<?= $user['status'] ?>">
                <?= $user['status'] === 'pending' ? 'Pendiente de Aprobación' : 'Rechazada' ?>
            </span>
            <br>
            <a href="../logout.php" class="btn">Cerrar Sesión</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Obtener orden activa del partner
$activeOrder = $db->fetchOne(
    "SELECT o.*, s.name as service_name, s.price, 
            c.name as client_name, c.phone as client_phone, c.address as client_address,
            c.latitude as client_lat, c.longitude as client_lng
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     JOIN clients c ON o.client_id = c.id
     WHERE o.partner_id = :partner_id 
     AND o.status IN ('accepted', 'paid', 'in_transit', 'in_progress')
     ORDER BY o.created_at DESC 
     LIMIT 1",
    ['partner_id' => $user['id']]
);

// Contar órdenes pendientes (sin partner asignado) cercanas
$pendingOrders = $db->fetchAll(
    "SELECT o.*, s.name as service_name, s.price, c.name as client_name,
            c.latitude as client_lat, c.longitude as client_lng, c.address as client_address
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     JOIN clients c ON o.client_id = c.id
     WHERE o.status = 'pending' AND o.partner_id IS NULL
     ORDER BY o.created_at DESC
     LIMIT 10"
);

// Estadísticas del partner
$stats = $db->fetchOne(
    "SELECT 
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN 
            (SELECT partner_amount FROM payments WHERE order_id = orders.id AND status = 'completed' LIMIT 1) 
        END), 0) as total_earnings
     FROM orders 
     WHERE partner_id = :partner_id",
    ['partner_id' => $user['id']]
);

// Ganancias de hoy
$todayEarnings = $db->fetchOne(
    "SELECT COALESCE(SUM(p.partner_amount), 0) as earnings
     FROM payments p
     JOIN orders o ON p.order_id = o.id
     WHERE o.partner_id = :partner_id 
     AND p.status = 'completed'
     AND DATE(p.completed_at) = CURDATE()",
    ['partner_id' => $user['id']]
);

// Notificaciones no leídas
$unreadNotifications = $db->count(
    'notifications',
    "user_type = 'partner' AND user_id = :user_id AND is_read = 0",
    ['user_id' => $user['id']]
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Partner - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f9ff;
            min-height: 100vh;
            padding-bottom: 80px;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            padding: 20px;
            color: white;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo-section img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        
        .logo-section span {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .notification-btn {
            position: relative;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 0.65rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .user-greeting h2 {
            font-size: 1.3rem;
            margin-bottom: 5px;
        }
        
        .user-greeting p {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        /* Availability Toggle */
        .availability-card {
            background: white;
            margin: -30px 20px 20px;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .availability-info h3 {
            font-size: 1rem;
            color: #1e3a5f;
            margin-bottom: 3px;
        }
        
        .availability-info p {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 32px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #e2e8f0;
            border-radius: 32px;
            transition: 0.3s;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .toggle-switch input:checked + .toggle-slider {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }
        
        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(28px);
        }
        
        /* Main Content */
        .main-content {
            padding: 0 20px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 12px;
        }
        
        .stat-card:nth-child(1) .stat-icon { background: #dcfce7; }
        .stat-card:nth-child(2) .stat-icon { background: #dbeafe; }
        .stat-card:nth-child(3) .stat-icon { background: #fef3c7; }
        .stat-card:nth-child(4) .stat-icon { background: #f3e8ff; }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e3a5f;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        /* Active Order */
        .active-order {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            border-radius: 20px;
            padding: 25px;
            color: white;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 119, 182, 0.3);
        }
        
        .active-order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .active-order h3 {
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .order-code {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        
        .order-status {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .client-info {
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .client-info h4 {
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .client-info p {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .order-price {
            font-size: 1.5rem;
            font-weight: 700;
            text-align: right;
            margin-bottom: 15px;
        }
        
        .order-price span {
            font-size: 0.8rem;
            font-weight: 400;
            opacity: 0.8;
        }
        
        .active-order-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-navigate {
            flex: 1;
            background: white;
            color: #0077b6;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-call {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
        }
        
        /* Pending Orders */
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .section-title h3 {
            font-size: 1.1rem;
            color: #1e3a5f;
        }
        
        .section-title .count {
            background: #ef4444;
            color: white;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .pending-orders {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .order-card {
            background: white;
            border-radius: 16px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #00b4d8;
        }
        
        .order-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .order-card h4 {
            font-size: 0.95rem;
            color: #1e3a5f;
            margin-bottom: 3px;
        }
        
        .order-card .time-ago {
            font-size: 0.75rem;
            color: #64748b;
        }
        
        .order-card .price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0077b6;
        }
        
        .order-card .address {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-accept {
            width: 100%;
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 119, 182, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 10px 20px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-around;
            z-index: 100;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #64748b;
            text-decoration: none;
            font-size: 0.7rem;
            padding: 5px 15px;
            border-radius: 10px;
        }
        
        .nav-item.active {
            color: #0077b6;
            background: #e0f2fe;
        }
        
        .nav-icon {
            font-size: 1.2rem;
            margin-bottom: 3px;
        }
        
        @media (max-width: 380px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-top">
            <div class="logo-section">
                <img src="../assets/img/logo.png" alt="INGClean">
                <span>INGClean Partner</span>
            </div>
            <div class="header-actions">
                <button class="notification-btn">
                    🔔
                    <?php if ($unreadNotifications > 0): ?>
                        <span class="notification-badge"><?= $unreadNotifications ?></span>
                    <?php endif; ?>
                </button>
            </div>
        </div>
        <div class="user-greeting">
            <h2>Hola, <?= e(explode(' ', $user['name'])[0]) ?>! 👋</h2>
            <p><?= $user['is_available'] ? '🟢 Estás disponible para recibir solicitudes' : '🔴 No estás recibiendo solicitudes' ?></p>
        </div>
    </header>
    
    <!-- Availability Toggle -->
    <div class="availability-card">
        <div class="availability-info">
            <h3>Disponibilidad</h3>
            <p id="availabilityText"><?= $user['is_available'] ? 'Recibiendo solicitudes' : 'No disponible' ?></p>
        </div>
        <label class="toggle-switch">
            <input type="checkbox" id="availabilityToggle" <?= $user['is_available'] ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
        </label>
    </div>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value">$<?= number_format($todayEarnings['earnings'] ?? 0, 2) ?></div>
                <div class="stat-label">Ganancias Hoy</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?= $stats['completed_orders'] ?? 0 ?></div>
                <div class="stat-label">Servicios Completados</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-value"><?= $user['average_rating'] ?? 'N/A' ?></div>
                <div class="stat-label">Calificación</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💵</div>
                <div class="stat-value">$<?= number_format($user['total_earnings'] ?? 0, 2) ?></div>
                <div class="stat-label">Total Ganado</div>
            </div>
        </div>
        
        <!-- Active Order -->
        <?php if ($activeOrder): ?>
            <section class="active-order">
                <div class="active-order-header">
                    <div>
                        <h3>🧹 <?= e($activeOrder['service_name']) ?></h3>
                        <div class="order-code"><?= e($activeOrder['order_code']) ?></div>
                    </div>
                    <span class="order-status">
                        <?php
                        $statusText = [
                            'accepted' => '⏳ Esperando pago',
                            'paid' => '💳 Pagado - ¡Ve ahora!',
                            'in_transit' => '🚗 En camino',
                            'in_progress' => '🧹 En progreso'
                        ];
                        echo $statusText[$activeOrder['status']] ?? $activeOrder['status'];
                        ?>
                    </span>
                </div>
                
                <div class="client-info">
                    <h4>👤 <?= e($activeOrder['client_name']) ?></h4>
                    <p>📍 <?= e($activeOrder['client_address']) ?></p>
                </div>
                
                <div class="order-price">
                    $<?= number_format(calculatePartnerAmount($activeOrder['price']), 2) ?>
                    <span>(Tu ganancia)</span>
                </div>
                
                <div class="active-order-actions">
                    <a href="active-service.php?order=<?= $activeOrder['id'] ?>" class="btn-navigate">
                        <?= $activeOrder['status'] === 'in_progress' ? '📋 Ver Servicio' : '🗺️ Navegar' ?>
                    </a>
                    <a href="tel:<?= e($activeOrder['client_phone']) ?>" class="btn-call">📞</a>
                </div>
            </section>
        <?php endif; ?>
        
        <!-- Pending Orders -->
        <?php if (!$activeOrder): ?>
            <div class="section-title">
                <h3>📋 Solicitudes Disponibles</h3>
                <?php if (count($pendingOrders) > 0): ?>
                    <span class="count"><?= count($pendingOrders) ?></span>
                <?php endif; ?>
            </div>
            
            <div class="pending-orders">
                <?php if (empty($pendingOrders)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <p>No hay solicitudes disponibles en este momento</p>
                        <p style="font-size: 0.85rem; margin-top: 10px;">Mantente disponible para recibir nuevas solicitudes</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pendingOrders as $order): ?>
                        <div class="order-card" data-order-id="<?= $order['id'] ?>">
                            <div class="order-card-header">
                                <div>
                                    <h4><?= e($order['service_name']) ?></h4>
                                    <span class="time-ago"><?= timeAgo($order['created_at']) ?></span>
                                </div>
                                <div class="price">$<?= number_format($order['price'], 2) ?></div>
                            </div>
                            <div class="address">
                                📍 <?= e($order['client_address'] ?: 'Ubicación por GPS') ?>
                            </div>
                            <button class="btn-accept" onclick="acceptOrder(<?= $order['id'] ?>)">
                                ✓ Aceptar Servicio
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item active">
            <span class="nav-icon">🏠</span>
            Inicio
        </a>
        <a href="earnings.php" class="nav-item">
            <span class="nav-icon">💰</span>
            Ganancias
        </a>
        <a href="profile.php" class="nav-item">
            <span class="nav-icon">👤</span>
            Perfil
        </a>
        <a href="../logout.php" class="nav-item">
            <span class="nav-icon">🚪</span>
            Salir
        </a>
    </nav>

    <script>
        // Toggle disponibilidad
        const availabilityToggle = document.getElementById('availabilityToggle');
        const availabilityText = document.getElementById('availabilityText');
        
        availabilityToggle.addEventListener('change', function() {
            const isAvailable = this.checked;
            
            fetch('../api/partner/toggle-availability.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ is_available: isAvailable })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    availabilityText.textContent = isAvailable ? 'Recibiendo solicitudes' : 'No disponible';
                    
                    // Si se activa, iniciar tracking de ubicación
                    if (isAvailable) {
                        startLocationTracking();
                    }
                }
            });
        });
        
        // Aceptar orden
        function acceptOrder(orderId) {
            if (!confirm('¿Aceptar este servicio?')) return;
            
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = '⏳ Aceptando...';
            
            fetch('../api/orders/accept.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error al aceptar');
                    btn.disabled = false;
                    btn.textContent = '✓ Aceptar Servicio';
                }
            })
            .catch(err => {
                alert('Error de conexión');
                btn.disabled = false;
                btn.textContent = '✓ Aceptar Servicio';
            });
        }
        
        // Tracking de ubicación
        function startLocationTracking() {
            if (!navigator.geolocation) return;
            
            navigator.geolocation.getCurrentPosition(function(pos) {
                updateLocation(pos.coords.latitude, pos.coords.longitude);
            });
        }
        
        function updateLocation(lat, lng) {
            fetch('../api/location/update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ latitude: lat, longitude: lng })
            });
        }
        
        // Actualizar ubicación al cargar si está disponible
        <?php if ($user['is_available']): ?>
        startLocationTracking();
        <?php endif; ?>
        
        // Actualizar lista de órdenes cada 30 segundos
        <?php if (!$activeOrder): ?>
        setInterval(function() {
            location.reload();
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>
