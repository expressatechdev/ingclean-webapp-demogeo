<?php
/**
 * INGClean - Dashboard del Partner
 * Mobile First - Textos GRANDES - Bottom Nav siempre visible
 */
require_once '../includes/init.php';

auth()->requireLogin(['partner']);

$user = auth()->getCurrentUser();
$db = Database::getInstance();

// Verificar aprobación
if (($user['status'] ?? '') !== 'approved') {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0a1628">
    <title>Cuenta Pendiente - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { font-size: 18px; }
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0a1628 0%, #1a3a5c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            padding-top: max(24px, env(safe-area-inset-top));
        }
        .card {
            background: white;
            border-radius: 24px;
            padding: 40px 28px;
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        .icon { font-size: 4rem; margin-bottom: 20px; }
        h1 { font-size: 1.6rem; color: #1e3a5f; margin-bottom: 12px; }
        p { color: #64748b; margin-bottom: 24px; font-size: 1.1rem; line-height: 1.6; }
        .status {
            display: inline-block;
            padding: 12px 28px;
            border-radius: 24px;
            font-size: 1.05rem;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        .btn {
            display: inline-block;
            margin-top: 24px;
            padding: 16px 36px;
            min-height: 56px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            text-decoration: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1.15rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon"><?= ($user['status'] ?? '') === 'pending' ? '⏳' : '❌' ?></div>
        <h1><?= ($user['status'] ?? '') === 'pending' ? 'Cuenta en Revisión' : 'Cuenta Rechazada' ?></h1>
        <p><?= ($user['status'] ?? '') === 'pending' 
            ? 'Tu solicitud está siendo revisada. Te notificaremos cuando sea aprobada.' 
            : 'Lo sentimos, tu solicitud no fue aprobada. Contacta a soporte.' 
        ?></p>
        <span class="status status-<?= $user['status'] ?? 'pending' ?>">
            <?= ($user['status'] ?? '') === 'pending' ? 'Pendiente' : 'Rechazada' ?>
        </span>
        <br>
        <a href="../logout.php" class="btn">Cerrar Sesión</a>
    </div>
</body>
</html>
<?php
    exit;
}

$activeOrder = $db->fetchOne(
    "SELECT o.*, s.name as service_name, s.price, 
            c.name as client_name, c.phone as client_phone, c.address as client_address
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     JOIN clients c ON o.client_id = c.id
     WHERE o.partner_id = :partner_id 
     AND o.status IN ('accepted', 'paid', 'in_transit', 'in_progress')
     ORDER BY o.created_at DESC LIMIT 1",
    ['partner_id' => $user['id']]
);

$pendingOrders = $db->fetchAll(
    "SELECT o.*, s.name as service_name, s.price, c.name as client_name, c.address as client_address
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     JOIN clients c ON o.client_id = c.id
     WHERE o.status = 'pending' AND o.partner_id IS NULL
     ORDER BY o.created_at DESC LIMIT 10"
);

$stats = $db->fetchOne(
    "SELECT COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders
     FROM orders WHERE partner_id = :partner_id",
    ['partner_id' => $user['id']]
);

$totalEarnings = $db->fetchOne(
    "SELECT COALESCE(SUM(p.partner_amount), 0) as total
     FROM payments p JOIN orders o ON p.order_id = o.id
     WHERE o.partner_id = :partner_id AND p.status = 'completed'",
    ['partner_id' => $user['id']]
);

$todayEarnings = $db->fetchOne(
    "SELECT COALESCE(SUM(p.partner_amount), 0) as earnings
     FROM payments p JOIN orders o ON p.order_id = o.id
     WHERE o.partner_id = :partner_id AND p.status = 'completed'
     AND DATE(p.completed_at) = CURDATE()",
    ['partner_id' => $user['id']]
);

$userName = $user['name'] ?? 'Partner';
$isAvailable = (int)($user['is_available'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#16a34a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    
    <title>Dashboard Partner - <?= APP_NAME ?></title>
    
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
            background: #f0fdf4;
            min-height: 100vh;
            min-height: 100dvh;
            padding-bottom: 100px; /* Espacio para bottom nav */
        }
        
        /* ===== HEADER ===== */
        .header {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            padding: 16px 20px;
            padding-top: max(16px, env(safe-area-inset-top));
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(34, 197, 94, 0.3);
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
            gap: 12px;
        }
        
        .logo-section img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
        }
        
        .logo-section span {
            color: white;
            font-weight: 700;
            font-size: 1.3rem;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 1.2rem;
            text-decoration: none;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        /* ===== MAIN ===== */
        .main {
            max-width: 800px;
            margin: 0 auto;
            padding: 24px 20px;
        }
        
        /* ===== WELCOME ===== */
        .welcome {
            margin-bottom: 28px;
        }
        
        .welcome h1 {
            font-size: 1.9rem;
            color: #1e3a5f;
            margin-bottom: 8px;
        }
        
        .welcome p {
            color: #64748b;
            font-size: 1.15rem;
        }
        
        /* ===== AVAILABILITY CARD ===== */
        .availability-card {
            background: white;
            border-radius: 20px;
            padding: 22px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        
        .availability-info h3 {
            font-size: 1.25rem;
            color: #1e3a5f;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .availability-info p {
            font-size: 1rem;
            color: #64748b;
        }
        
        /* Toggle Switch Grande */
        .toggle-switch {
            position: relative;
            width: 80px;
            height: 44px;
            flex-shrink: 0;
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
            background-color: #cbd5e1;
            transition: 0.3s;
            border-radius: 44px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 36px;
            width: 36px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .toggle-switch input:checked + .toggle-slider {
            background-color: #22c55e;
        }
        
        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(36px);
        }
        
        /* ===== STATS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 28px;
        }
        
        .stat-card {
            background: white;
            border-radius: 18px;
            padding: 20px 14px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .stat-icon {
            font-size: 1.8rem;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 1.7rem;
            font-weight: 700;
            color: #1e3a5f;
        }
        
        .stat-value.earnings {
            color: #16a34a;
        }
        
        .stat-label {
            font-size: 0.95rem;
            color: #64748b;
            margin-top: 4px;
        }
        
        /* ===== ACTIVE ORDER ===== */
        .active-order {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-radius: 22px;
            padding: 26px;
            color: white;
            margin-bottom: 28px;
            box-shadow: 0 10px 35px rgba(34, 197, 94, 0.35);
        }
        
        .active-order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .active-order h3 {
            font-size: 1.3rem;
            margin-bottom: 6px;
        }
        
        .order-code {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .order-status {
            background: rgba(255,255,255,0.25);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.95rem;
            font-weight: 600;
        }
        
        .client-info {
            background: rgba(255,255,255,0.15);
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 20px;
        }
        
        .client-info h4 {
            font-size: 1.2rem;
            margin-bottom: 8px;
        }
        
        .client-info p {
            font-size: 1rem;
            opacity: 0.95;
        }
        
        .active-order-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn-action {
            flex: 1;
            background: white;
            color: #16a34a;
            border: none;
            padding: 16px;
            min-height: 58px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-action:active {
            transform: scale(0.98);
        }
        
        .btn-call {
            flex: 0;
            width: 58px;
            background: rgba(255,255,255,0.25);
            color: white;
            border: none;
            padding: 0;
            min-height: 58px;
            border-radius: 14px;
            font-size: 1.5rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* ===== SECTION TITLE ===== */
        .section-title {
            font-size: 1.4rem;
            color: #1e3a5f;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* ===== PENDING ORDERS ===== */
        .pending-orders {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        
        .order-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .order-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            gap: 12px;
        }
        
        .order-card h3 {
            font-size: 1.25rem;
            color: #1e3a5f;
            margin-bottom: 6px;
        }
        
        .order-card .address {
            font-size: 1rem;
            color: #64748b;
            line-height: 1.4;
        }
        
        .order-price-box {
            text-align: right;
            flex-shrink: 0;
        }
        
        .order-earnings-main {
            font-size: 1.5rem;
            font-weight: 700;
            color: #16a34a;
        }
        
        .order-earnings-label {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 2px;
        }
        
        .btn-accept {
            width: 100%;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            border: none;
            padding: 18px;
            min-height: 60px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1.15rem;
            cursor: pointer;
            font-family: inherit;
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
            -webkit-appearance: none;
        }
        
        .btn-accept:active {
            transform: scale(0.98);
        }
        
        .btn-accept:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 24px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 16px;
        }
        
        .empty-state h3 {
            font-size: 1.3rem;
            color: #1e3a5f;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            font-size: 1.1rem;
            color: #64748b;
            line-height: 1.6;
        }
        
        /* ===== BOTTOM NAV - SIEMPRE VISIBLE ===== */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 12px 20px;
            padding-bottom: max(12px, env(safe-area-inset-bottom));
            box-shadow: 0 -4px 25px rgba(0,0,0,0.12);
            display: flex;
            justify-content: space-around;
            z-index: 1000; /* Asegurar que esté siempre visible */
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 14px;
            min-width: 70px;
            gap: 4px;
            transition: all 0.2s;
        }
        
        .nav-item:active {
            background: #f0fdf4;
        }
        
        .nav-item.active {
            color: #16a34a;
            background: #f0fdf4;
        }
        
        .nav-icon {
            font-size: 1.6rem;
            line-height: 1;
        }
        
        .nav-label {
            font-size: 0.85rem;
        }
        
        /* ===== LOADING OVERLAY ===== */
        .loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255,255,255,0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            flex-direction: column;
            gap: 16px;
        }
        
        .loading-overlay.show {
            display: flex;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e2e8f0;
            border-top-color: #22c55e;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .loading-text {
            font-size: 1.1rem;
            color: #64748b;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (min-width: 640px) {
            .stats-grid {
                gap: 18px;
            }
            
            .stat-card {
                padding: 24px 18px;
            }
            
            .stat-value {
                font-size: 2rem;
            }
        }
        
        @media (min-width: 1024px) {
            .bottom-nav {
                max-width: 500px;
                left: 50%;
                transform: translateX(-50%);
                border-radius: 20px 20px 0 0;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">Actualizando...</div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo-section">
                <img src="../assets/img/logo.png" alt="<?= APP_NAME ?>">
                <span>Partner</span>
            </div>
            <div class="header-actions">
                <a href="../logout.php" class="logout-btn">
                    🚪 Salir
                </a>
            </div>
        </div>
    </header>
    
    <!-- Main -->
    <main class="main">
        <!-- Welcome -->
        <div class="welcome">
            <h1>Hola, <?= e(explode(' ', $userName)[0]) ?>! 💪</h1>
            <p>Listo para ganar dinero hoy</p>
        </div>
        
        <!-- Availability Card con Toggle Grande -->
        <div class="availability-card">
            <div class="availability-info">
                <h3 id="availabilityText">
                    <?= $isAvailable ? '🟢 Disponible' : '🔴 No disponible' ?>
                </h3>
                <p id="availabilityDesc"><?= $isAvailable ? 'Puedes recibir nuevas órdenes' : 'No recibirás órdenes nuevas' ?></p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="availabilityToggle" <?= $isAvailable ? 'checked' : '' ?> onchange="toggleAvailability(this)">
                <span class="toggle-slider"></span>
            </label>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?= number_format($stats['completed_orders'] ?? 0) ?></div>
                <div class="stat-label">Completados</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💵</div>
                <div class="stat-value earnings">$<?= number_format($todayEarnings['earnings'] ?? 0, 0) ?></div>
                <div class="stat-label">Ganado Hoy</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value earnings">$<?= number_format($totalEarnings['total'] ?? 0, 0) ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>
        
        <!-- Active Order -->
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
                            'accepted' => '✅ Aceptado',
                            'paid' => '💳 Pagado',
                            'in_transit' => '🚗 En camino',
                            'in_progress' => '🧹 Limpiando'
                        ];
                        echo $statusLabels[$activeOrder['status']] ?? $activeOrder['status'];
                        ?>
                    </span>
                </div>
                
                <div class="client-info">
                    <h4>👤 <?= e($activeOrder['client_name']) ?></h4>
                    <p>📍 <?= e($activeOrder['client_address'] ?: 'Ver ubicación en el mapa') ?></p>
                </div>
                
                <div class="active-order-actions">
                    <a href="active-service.php?order=<?= $activeOrder['id'] ?>" class="btn-action">
                        📍 Ver Servicio
                    </a>
                    <?php if (!empty($activeOrder['client_phone'])): ?>
                        <a href="tel:<?= e($activeOrder['client_phone']) ?>" class="btn-call">📞</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Pending Orders -->
        <h2 class="section-title">📋 Órdenes Disponibles</h2>
        
        <?php if (empty($pendingOrders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3>No hay órdenes disponibles</h3>
                <p>Mantente activo y disponible.<br>Te notificaremos cuando llegue una orden.</p>
            </div>
        <?php else: ?>
            <div class="pending-orders">
                <?php foreach ($pendingOrders as $order): 
                    $earnings = calculatePartnerAmount($order['price']);
                ?>
                    <div class="order-card" id="order-<?= $order['id'] ?>">
                        <div class="order-card-header">
                            <div>
                                <h3>🧹 <?= e($order['service_name']) ?></h3>
                                <p class="address">📍 <?= e($order['client_address'] ?: 'Ubicación disponible al aceptar') ?></p>
                            </div>
                            <div class="order-price-box">
                                <div class="order-earnings-main">💰 $<?= number_format($earnings, 2) ?></div>
                                <div class="order-earnings-label">Tu ganancia</div>
                            </div>
                        </div>
                        <button class="btn-accept" onclick="acceptOrder(<?= $order['id'] ?>, this)">
                            ✅ Aceptar Orden
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <!-- Bottom Nav - SIEMPRE VISIBLE -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item active">
            <span class="nav-icon">🏠</span>
            <span class="nav-label">Inicio</span>
        </a>
        <a href="earnings.php" class="nav-item">
            <span class="nav-icon">💰</span>
            <span class="nav-label">Ganancias</span>
        </a>
        <a href="profile.php" class="nav-item">
            <span class="nav-icon">👤</span>
            <span class="nav-label">Perfil</span>
        </a>
    </nav>
    
    <script>
        // Wake Lock - Mantener pantalla encendida
        let wakeLock = null;
        async function requestWakeLock() {
            try {
                if ('wakeLock' in navigator) {
                    wakeLock = await navigator.wakeLock.request('screen');
                }
            } catch (e) {}
        }
        requestWakeLock();
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') requestWakeLock();
        });
        
        // Toggle Availability - CORREGIDO
        function toggleAvailability(checkbox) {
            const isAvailable = checkbox.checked;
            const textEl = document.getElementById('availabilityText');
            const descEl = document.getElementById('availabilityDesc');
            
            // Mostrar loading
            document.getElementById('loadingOverlay').classList.add('show');
            
            fetch('../api/partner/toggle-availability.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ is_available: isAvailable })
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('loadingOverlay').classList.remove('show');
                
                if (data.success) {
                    // Actualizar UI
                    if (isAvailable) {
                        textEl.innerHTML = '🟢 Disponible';
                        descEl.textContent = 'Puedes recibir nuevas órdenes';
                    } else {
                        textEl.innerHTML = '🔴 No disponible';
                        descEl.textContent = 'No recibirás órdenes nuevas';
                    }
                    
                    // Actualizar tag de OneSignal para recibir/no recibir notificaciones de nuevas órdenes
                    if (typeof updatePartnerAvailability === 'function') {
                        updatePartnerAvailability(isAvailable);
                    }
                } else {
                    // Revertir checkbox si hay error
                    checkbox.checked = !isAvailable;
                    alert(data.message || 'Error al actualizar');
                }
            })
            .catch(err => {
                document.getElementById('loadingOverlay').classList.remove('show');
                checkbox.checked = !isAvailable;
                alert('Error de conexión');
            });
        }
        
        // Accept Order - Con activación automática de GPS
        function acceptOrder(orderId, btn) {
            if (!confirm('¿Aceptar esta orden?\n\nUna vez aceptada, deberás completar el servicio.')) return;
            
            btn.disabled = true;
            btn.innerHTML = '⏳ Aceptando...';
            
            fetch('../api/orders/accept.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    btn.innerHTML = '✅ ¡Aceptada!';
                    btn.style.background = '#16a34a';
                    
                    // Activar GPS automáticamente antes de redirigir
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            () => {
                                // GPS activado, redirigir al servicio
                                window.location.href = 'active-service.php?order=' + orderId;
                            },
                            () => {
                                // GPS denegado o error, redirigir igual
                                window.location.href = 'active-service.php?order=' + orderId;
                            },
                            { enableHighAccuracy: true, timeout: 5000 }
                        );
                    } else {
                        // Sin GPS disponible, redirigir igual
                        window.location.href = 'active-service.php?order=' + orderId;
                    }
                } else {
                    alert(data.message || 'Error al aceptar la orden');
                    btn.disabled = false;
                    btn.innerHTML = '✅ Aceptar Orden';
                }
            })
            .catch(err => {
                alert('Error de conexión');
                btn.disabled = false;
                btn.innerHTML = '✅ Aceptar Orden';
            });
        }
        
        // Auto-refresh órdenes pendientes cada 5 segundos (AJAX sin reload visible)
        <?php if (!$activeOrder): ?>
        function refreshPendingOrders() {
            fetch(window.location.href)
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Actualizar sección de órdenes pendientes
                    const newOrders = doc.querySelector('.pending-orders, .empty-state');
                    const currentOrders = document.querySelector('.pending-orders, .empty-state');
                    
                    if (newOrders && currentOrders) {
                        currentOrders.parentNode.replaceChild(
                            newOrders.cloneNode(true), 
                            currentOrders
                        );
                        
                        // Re-agregar event listeners a los nuevos botones
                        document.querySelectorAll('.btn-accept').forEach(btn => {
                            const orderId = btn.getAttribute('onclick').match(/\d+/)[0];
                            btn.onclick = function() { acceptOrder(orderId, this); };
                        });
                    }
                    
                    // Actualizar estadísticas
                    const newStats = doc.querySelectorAll('.stat-value');
                    const currentStats = document.querySelectorAll('.stat-value');
                    newStats.forEach((stat, i) => {
                        if (currentStats[i]) {
                            currentStats[i].textContent = stat.textContent;
                        }
                    });
                })
                .catch(err => console.log('Error refreshing:', err));
        }
        
        setInterval(refreshPendingOrders, 5000);
        <?php endif; ?>
    </script>
    
    <?php include '../includes/onesignal.php'; ?>
    
    <?php if ($activeOrder): ?>
    <?php 
    // Sistema de polling en tiempo real para orden activa
    $polling_order_id = $activeOrder['id'];
    $polling_interval = 5000; // 5 segundos
    $polling_redirect_on_status = [
        'paid' => '/partner/active-service.php?order=' . $activeOrder['id'],
        'cancelled' => '/partner/'
    ];
    include '../includes/realtime-polling.php'; 
    ?>
    <?php endif; ?>
</body>
</html>