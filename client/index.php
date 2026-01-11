<?php
/**
 * INGClean - Dashboard del Cliente
 * Mobile First con textos GRANDES
 */
require_once '../includes/init.php';

auth()->requireLogin(['client']);

$user = auth()->getCurrentUser();
$db = Database::getInstance();

$services = $db->fetchAll("SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC");

$activeOrder = $db->fetchOne(
    "SELECT o.*, s.name as service_name, s.price, p.name as partner_name, p.phone as partner_phone, p.photo as partner_photo
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     LEFT JOIN partners p ON o.partner_id = p.id
     WHERE o.client_id = :client_id 
     AND o.status NOT IN ('completed', 'cancelled')
     ORDER BY o.created_at DESC LIMIT 1",
    ['client_id' => $user['id']]
);

$recentOrders = $db->fetchAll(
    "SELECT o.*, s.name as service_name, s.price, p.name as partner_name
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     LEFT JOIN partners p ON o.partner_id = p.id
     WHERE o.client_id = :client_id 
     AND o.status IN ('completed', 'cancelled')
     ORDER BY o.created_at DESC LIMIT 5",
    ['client_id' => $user['id']]
);

$userName = $user['name'] ?? 'Cliente';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <script src="/assets/js/capacitor-push.js" defer></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#0077b6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    
    <title>Mi Dashboard - <?= APP_NAME ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        html {
            font-size: 18px;
            -webkit-text-size-adjust: 100%;
        }
        
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f9ff;
            min-height: 100vh;
            padding-bottom: 100px;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            padding: 16px 20px;
            padding-top: max(16px, env(safe-area-inset-top));
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 800px;
            margin: 0 auto;
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
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .user-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 1.1rem;
            text-decoration: none;
        }
        
        /* Main */
        .main {
            max-width: 800px;
            margin: 0 auto;
            padding: 24px 20px;
        }
        
        /* Welcome */
        .welcome {
            margin-bottom: 28px;
        }
        
        .welcome h1 {
            font-size: 1.7rem;
            color: #1e3a5f;
            margin-bottom: 6px;
        }
        
        .welcome p {
            color: #64748b;
            font-size: 1.1rem;
        }
        
        /* Active Order */
        .active-order {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            border-radius: 20px;
            padding: 24px;
            color: white;
            margin-bottom: 28px;
            box-shadow: 0 10px 30px rgba(0, 119, 182, 0.3);
        }
        
        .active-order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 18px;
            gap: 12px;
        }
        
        .active-order h3 {
            font-size: 1.15rem;
            margin-bottom: 4px;
        }
        
        .order-code {
            font-size: 0.95rem;
            opacity: 0.9;
        }
        
        .order-status {
            background: rgba(255,255,255,0.25);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .partner-info {
            display: flex;
            align-items: center;
            gap: 14px;
            background: rgba(255,255,255,0.15);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 18px;
        }
        
        .partner-photo {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .partner-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .partner-details h4 {
            font-size: 1.1rem;
            margin-bottom: 2px;
        }
        
        .partner-details p {
            font-size: 0.95rem;
            opacity: 0.9;
        }
        
        .active-order-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn-track {
            flex: 1;
            background: white;
            color: #0077b6;
            border: none;
            padding: 16px;
            min-height: 56px;
            border-radius: 14px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            font-size: 1.05rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-call {
            background: rgba(255,255,255,0.25);
            color: white;
            border: none;
            padding: 16px;
            min-height: 56px;
            border-radius: 14px;
            font-size: 1.3rem;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        /* Section Title */
        .section-title {
            font-size: 1.25rem;
            color: #1e3a5f;
            margin-bottom: 18px;
        }
        
        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            margin-bottom: 32px;
        }
        
        .service-card {
            background: white;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }
        
        .service-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
        }
        
        .service-icon {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
        }
        
        .service-icon.basic { background: #dbeafe; }
        .service-icon.medium { background: #dcfce7; }
        .service-icon.deep { background: #fef3c7; }
        
        .service-card h3 {
            font-size: 1.15rem;
            color: #1e3a5f;
        }
        
        .service-card p {
            font-size: 1rem;
            color: #64748b;
            margin-bottom: 16px;
            line-height: 1.5;
        }
        
        .service-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .price {
            font-size: 1.6rem;
            font-weight: 700;
            color: #0077b6;
        }
        
        .btn-request {
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            border: none;
            padding: 14px 22px;
            min-height: 52px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            text-decoration: none;
        }
        
        .btn-request:active {
            transform: scale(0.98);
        }
        
        /* Recent Orders */
        .recent-orders {
            background: white;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .order-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .order-item:first-child {
            padding-top: 0;
        }
        
        .order-info h4 {
            font-size: 1.05rem;
            color: #1e3a5f;
            margin-bottom: 4px;
        }
        
        .order-info p {
            font-size: 0.95rem;
            color: #64748b;
        }
        
        .order-meta {
            text-align: right;
        }
        
        .order-price {
            font-weight: 700;
            color: #0077b6;
            font-size: 1.1rem;
            margin-bottom: 4px;
        }
        
        .order-status-badge {
            font-size: 0.85rem;
            padding: 5px 12px;
            border-radius: 12px;
            font-weight: 600;
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
            padding: 40px 24px;
            color: #64748b;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 12px;
        }
        
        .empty-state p {
            font-size: 1.05rem;
        }
        
        /* Bottom Nav */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 12px 20px;
            padding-bottom: max(12px, env(safe-area-inset-bottom));
            box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-around;
            z-index: 100;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.85rem;
            padding: 10px 18px;
            border-radius: 12px;
            gap: 4px;
        }
        
        .nav-item:active {
            background: #f1f5f9;
        }
        
        .nav-item.active {
            color: #0077b6;
        }
        
        .nav-icon {
            font-size: 1.5rem;
        }
        
        /* Tablet */
        @media (min-width: 640px) {
            .services-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Desktop */
        @media (min-width: 1024px) {
            .services-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            /* Bottom nav visible en desktop, centrado */
            .bottom-nav {
                max-width: 500px;
                left: 50%;
                transform: translateX(-50%);
                border-radius: 20px 20px 0 0;
                box-shadow: 0 -4px 30px rgba(0,0,0,0.15);
            }
            
            .nav-item {
                padding: 12px 24px;
            }
            
            .nav-item:hover {
                background: #f1f5f9;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo-section">
                <img src="../assets/img/logo.png" alt="<?= APP_NAME ?>">
                <span>INGClean</span>
            </div>
            <a href="../logout.php" class="user-btn" title="Cerrar sesión">
                <?= strtoupper(substr($userName, 0, 1)) ?>
            </a>
        </div>
    </header>
    
    <main class="main">
        <div class="welcome">
            <h1>Hola, <?= e(explode(' ', $userName)[0]) ?>! 👋</h1>
            <p>¿Qué servicio necesitas hoy?</p>
        </div>
        
        <?php if ($activeOrder): ?>
            <div class="active-order">
                <div class="active-order-header">
                    <div>
                        <h3>🧹 <?= e($activeOrder['service_name']) ?></h3>
                        <span class="order-code"><?= e($activeOrder['order_code'] ?? 'ORD-'.$activeOrder['id']) ?></span>
                    </div>
                    <span class="order-status">
                        <?php
                        $statusLabels = [
                            'pending' => '⏳ Pendiente',
                            'accepted' => '✅ Aceptado',
                            'paid' => '💳 Pagado',
                            'in_transit' => '🚗 En camino',
                            'in_progress' => '🧹 Limpiando'
                        ];
                        echo $statusLabels[$activeOrder['status']] ?? $activeOrder['status'];
                        ?>
                    </span>
                </div>
                
                <?php if (!empty($activeOrder['partner_name'])): ?>
                    <div class="partner-info">
                        <div class="partner-photo">
                            <?php if (!empty($activeOrder['partner_photo'])): ?>
                                <img src="../<?= e($activeOrder['partner_photo']) ?>" alt="">
                            <?php else: ?>
                                <?= strtoupper(substr($activeOrder['partner_name'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="partner-details">
                            <h4><?= e($activeOrder['partner_name']) ?></h4>
                            <p>Tu profesional de limpieza</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="active-order-actions">
                    <?php if (in_array($activeOrder['status'], ['accepted'])): ?>
                        <a href="payment.php?order=<?= $activeOrder['id'] ?>" class="btn-track">💳 Pagar Ahora</a>
                    <?php elseif (in_array($activeOrder['status'], ['paid', 'in_transit', 'in_progress'])): ?>
                        <a href="tracking.php?order=<?= $activeOrder['id'] ?>" class="btn-track">📍 Ver en Mapa</a>
                    <?php else: ?>
                        <span class="btn-track">⏳ Esperando partner...</span>
                    <?php endif; ?>
                    
                    <?php if (!empty($activeOrder['partner_phone'])): ?>
                        <a href="tel:<?= e($activeOrder['partner_phone']) ?>" class="btn-call">📞</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <h2 class="section-title">🧹 Servicios Disponibles</h2>
        <div class="services-grid">
            <?php 
            $icons = ['🧹', '✨', '🌟'];
            $colors = ['basic', 'medium', 'deep'];
            $i = 0;
            foreach ($services as $service): 
            ?>
                <div class="service-card">
                    <div class="service-header">
                        <div class="service-icon <?= $colors[$i % 3] ?>">
                            <?= $icons[$i % 3] ?>
                        </div>
                        <h3><?= e($service['name']) ?></h3>
                    </div>
                    <p><?= e($service['description'] ?? 'Servicio de limpieza profesional') ?></p>
                    <div class="service-footer">
                        <span class="price">$<?= number_format($service['price'], 0) ?></span>
                        <a href="request-service.php?service=<?= $service['id'] ?>" class="btn-request">Solicitar</a>
                    </div>
                </div>
            <?php $i++; endforeach; ?>
        </div>
        
        <?php if (!empty($recentOrders)): ?>
            <h2 class="section-title">📋 Historial Reciente</h2>
            <div class="recent-orders">
                <?php foreach ($recentOrders as $order): ?>
                    <div class="order-item">
                        <div class="order-info">
                            <h4><?= e($order['service_name']) ?></h4>
                            <p><?= !empty($order['partner_name']) ? e($order['partner_name']) : 'Sin asignar' ?> • <?= date('d/m/Y', strtotime($order['created_at'])) ?></p>
                        </div>
                        <div class="order-meta">
                            <div class="order-price">$<?= number_format($order['price'], 0) ?></div>
                            <span class="order-status-badge status-<?= $order['status'] ?>">
                                <?= $order['status'] === 'completed' ? '✓ Completado' : '✗ Cancelado' ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item active">
            <span class="nav-icon">🏠</span>
            <span>Inicio</span>
        </a>
        <a href="history.php" class="nav-item">
            <span class="nav-icon">📋</span>
            <span>Historial</span>
        </a>
        <a href="profile.php" class="nav-item">
            <span class="nav-icon">👤</span>
            <span>Perfil</span>
        </a>
    </nav>
    
    <?php include '../includes/onesignal.php'; ?>
    
    <script>
        // Sonido de notificación
        const notificationSound = new Audio('/assets/sounds/notification.mp3');
        let audioUnlocked = false;
        
        // Desbloquear audio con primera interacción
        document.addEventListener('click', function() {
            if (!audioUnlocked) {
                notificationSound.play().then(() => {
                    notificationSound.pause();
                    notificationSound.currentTime = 0;
                    audioUnlocked = true;
                }).catch(() => {});
            }
        }, { once: false });
        
        // Reproducir sonido
        function playNotificationSound() {
            if (audioUnlocked) {
                notificationSound.currentTime = 0;
                notificationSound.play().catch(() => {});
            }
        }
        
        <?php if ($activeOrder): ?>
        // Estado actual para detectar cambios
        let currentStatus = '<?= $activeOrder['status'] ?>';
        let currentPartnerId = <?= $activeOrder['partner_id'] ? $activeOrder['partner_id'] : 'null' ?>;
        
        // Polling cada 5 segundos usando API
        function checkOrderStatus() {
            fetch('../api/orders/client-status.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.has_active_order && data.order) {
                        const order = data.order;
                        
                        // Detectar si el partner aceptó (cambió de null a un ID)
                        if (currentPartnerId === null && order.partner_id !== null) {
                            playNotificationSound();
                            location.reload();
                            return;
                        }
                        
                        // Detectar cambio de estado
                        if (order.status !== currentStatus) {
                            playNotificationSound();
                            currentStatus = order.status;
                            location.reload();
                            return;
                        }
                        
                        currentPartnerId = order.partner_id;
                    } else if (data.success && !data.has_active_order) {
                        // La orden ya no existe (completada o cancelada)
                        location.reload();
                    }
                })
                .catch(err => console.log('Error polling:', err));
        }
        
        // Iniciar polling
        setInterval(checkOrderStatus, 5000);
        <?php endif; ?>
    </script>
</body>
</html>