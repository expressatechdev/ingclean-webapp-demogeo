<?php
/**
 * INGClean - Dashboard del Cliente
 */
require_once '../includes/init.php';

// Requerir login de cliente
auth()->requireLogin(['client']);

$user = auth()->getCurrentUser();
$db = Database::getInstance();

// Obtener servicios disponibles
$services = $db->fetchAll("SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC");

// Obtener orden activa del cliente (si existe)
$activeOrder = $db->fetchOne(
    "SELECT o.*, s.name as service_name, s.price, p.name as partner_name, p.phone as partner_phone, p.photo as partner_photo
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     LEFT JOIN partners p ON o.partner_id = p.id
     WHERE o.client_id = :client_id 
     AND o.status NOT IN ('completed', 'cancelled')
     ORDER BY o.created_at DESC 
     LIMIT 1",
    ['client_id' => $user['id']]
);

// Obtener últimos 5 servicios completados
$recentOrders = $db->fetchAll(
    "SELECT o.*, s.name as service_name, s.price, p.name as partner_name
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     LEFT JOIN partners p ON o.partner_id = p.id
     WHERE o.client_id = :client_id 
     AND o.status IN ('completed', 'cancelled')
     ORDER BY o.created_at DESC 
     LIMIT 5",
    ['client_id' => $user['id']]
);

// Contar notificaciones no leídas
$unreadNotifications = $db->count(
    'notifications',
    "user_type = 'client' AND user_id = :user_id AND is_read = 0",
    ['user_id' => $user['id']]
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Dashboard - <?= APP_NAME ?></title>
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
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            padding: 15px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 119, 182, 0.3);
        }
        
        .header-content {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
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
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        /* Main Content */
        .main-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Welcome Section */
        .welcome-section {
            margin-bottom: 25px;
        }
        
        .welcome-section h1 {
            font-size: 1.5rem;
            color: #1e3a5f;
            margin-bottom: 5px;
        }
        
        .welcome-section p {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        /* Active Order Card */
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
            margin-bottom: 20px;
        }
        
        .active-order h3 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .order-code {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .order-status {
            background: rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .partner-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255,255,255,0.15);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .partner-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            overflow: hidden;
        }
        
        .partner-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .partner-details h4 {
            font-size: 1rem;
            margin-bottom: 3px;
        }
        
        .partner-details p {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .active-order-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-track {
            flex: 1;
            background: white;
            color: #0077b6;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .btn-track:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-call {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Services Section */
        .section-title {
            font-size: 1.1rem;
            color: #1e3a5f;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .service-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .service-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 119, 182, 0.15);
            border-color: #00b4d8;
        }
        
        .service-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .service-basic .service-icon {
            background: #e0f2fe;
        }
        
        .service-medium .service-icon {
            background: #dbeafe;
        }
        
        .service-deep .service-icon {
            background: #c7d2fe;
        }
        
        .service-card h3 {
            font-size: 1rem;
            color: #1e3a5f;
            margin-bottom: 5px;
        }
        
        .service-card p {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .service-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0077b6;
        }
        
        .btn-request {
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .btn-request:hover {
            transform: scale(1.05);
        }
        
        /* Recent Orders */
        .recent-orders {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-info h4 {
            font-size: 0.95rem;
            color: #1e3a5f;
            margin-bottom: 3px;
        }
        
        .order-info p {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .order-meta {
            text-align: right;
        }
        
        .order-price {
            font-weight: 600;
            color: #0077b6;
            margin-bottom: 3px;
        }
        
        .order-status-badge {
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 20px;
        }
        
        .status-completed {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #64748b;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 10px;
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
            font-size: 0.75rem;
            padding: 5px 15px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .nav-item.active {
            color: #0077b6;
            background: #e0f2fe;
        }
        
        .nav-icon {
            font-size: 1.3rem;
            margin-bottom: 3px;
        }
        
        /* Responsive padding for bottom nav */
        .main-content {
            padding-bottom: 100px;
        }
        
        @media (max-width: 480px) {
            .services-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-section h1 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo-section">
                <img src="../assets/img/logo.png" alt="INGClean">
                <span>INGClean</span>
            </div>
            <div class="header-actions">
                <button class="notification-btn">
                    🔔
                    <?php if ($unreadNotifications > 0): ?>
                        <span class="notification-badge"><?= $unreadNotifications ?></span>
                    <?php endif; ?>
                </button>
                <a href="../logout.php" class="user-menu" title="Cerrar sesión">
                    <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                </a>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Welcome -->
        <section class="welcome-section">
            <h1>Hola, <?= e(explode(' ', $user['name'])[0]) ?>! 👋</h1>
            <p>¿Qué tipo de limpieza necesitas hoy?</p>
        </section>
        
        <!-- Active Order -->
        <?php if ($activeOrder): ?>
            <section class="active-order">
                <div class="active-order-header">
                    <div>
                        <h3>🧹 <?= e($activeOrder['service_name']) ?></h3>
                        <div class="order-code"><?= e($activeOrder['order_code']) ?></div>
                    </div>
                    <div class="order-status">
                        <?php
                        $statusText = [
                            'pending' => '⏳ Buscando partner...',
                            'accepted' => '✅ Partner asignado',
                            'paid' => '💳 Pagado',
                            'in_transit' => '🚗 Partner en camino',
                            'in_progress' => '🧹 En progreso'
                        ];
                        echo $statusText[$activeOrder['status']] ?? $activeOrder['status'];
                        ?>
                    </div>
                </div>
                
                <?php if ($activeOrder['partner_id']): ?>
                    <div class="partner-info">
                        <div class="partner-photo">
                            <?php if ($activeOrder['partner_photo']): ?>
                                <img src="../<?= e($activeOrder['partner_photo']) ?>" alt="Partner">
                            <?php else: ?>
                                👤
                            <?php endif; ?>
                        </div>
                        <div class="partner-details">
                            <h4><?= e($activeOrder['partner_name']) ?></h4>
                            <p>Tu profesional de limpieza</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="active-order-actions">
                    <?php if (in_array($activeOrder['status'], ['paid', 'in_transit', 'in_progress'])): ?>
                        <a href="tracking.php?order=<?= $activeOrder['id'] ?>" class="btn-track">
                            📍 Ver en Mapa
                        </a>
                    <?php elseif ($activeOrder['status'] === 'accepted'): ?>
                        <a href="payment.php?order=<?= $activeOrder['id'] ?>" class="btn-track">
                            💳 Pagar Servicio
                        </a>
                    <?php else: ?>
                        <button class="btn-track" disabled>⏳ Esperando...</button>
                    <?php endif; ?>
                    
                    <?php if ($activeOrder['partner_phone']): ?>
                        <a href="tel:<?= e($activeOrder['partner_phone']) ?>" class="btn-call">
                            📞 Llamar
                        </a>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
        
        <!-- Services -->
        <section>
            <h2 class="section-title">🧹 Nuestros Servicios</h2>
            <div class="services-grid">
                <?php 
                $icons = ['🧽', '🧹', '✨'];
                $classes = ['service-basic', 'service-medium', 'service-deep'];
                foreach ($services as $index => $service): 
                ?>
                    <div class="service-card <?= $classes[$index] ?? '' ?>">
                        <div class="service-icon"><?= $icons[$index] ?? '🧹' ?></div>
                        <h3><?= e($service['name']) ?></h3>
                        <p><?= e($service['description']) ?></p>
                        <div class="service-price">
                            <span class="price">$<?= number_format($service['price'], 2) ?></span>
                            <?php if (!$activeOrder): ?>
                                <a href="request-service.php?service=<?= $service['id'] ?>" class="btn-request">
                                    Solicitar
                                </a>
                            <?php else: ?>
                                <button class="btn-request" disabled style="opacity: 0.5;">
                                    Ocupado
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        
        <!-- Recent Orders -->
        <section class="recent-orders">
            <h2 class="section-title">📋 Historial Reciente</h2>
            
            <?php if (empty($recentOrders)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <p>Aún no tienes servicios completados</p>
                </div>
            <?php else: ?>
                <?php foreach ($recentOrders as $order): ?>
                    <div class="order-item">
                        <div class="order-info">
                            <h4><?= e($order['service_name']) ?></h4>
                            <p><?= e($order['partner_name'] ?? 'Sin asignar') ?> • <?= formatDate($order['created_at'], 'd M Y') ?></p>
                        </div>
                        <div class="order-meta">
                            <div class="order-price">$<?= number_format($order['price'], 2) ?></div>
                            <span class="order-status-badge status-<?= $order['status'] ?>">
                                <?= $order['status'] === 'completed' ? 'Completado' : 'Cancelado' ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
    
    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item active">
            <span class="nav-icon">🏠</span>
            Inicio
        </a>
        <a href="history.php" class="nav-item">
            <span class="nav-icon">📋</span>
            Historial
        </a>
        <a href="profile.php" class="nav-item">
            <span class="nav-icon">👤</span>
            Perfil
        </a>
    </nav>

    <script>
        // Actualizar estado de la orden cada 10 segundos si hay orden activa
        <?php if ($activeOrder): ?>
        setInterval(function() {
            fetch('../api/orders/status.php?order=<?= $activeOrder['id'] ?>')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.data.status !== '<?= $activeOrder['status'] ?>') {
                        location.reload();
                    }
                });
        }, 10000);
        <?php endif; ?>
    </script>
</body>
</html>
