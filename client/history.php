<?php
/**
 * INGClean - Historial de Pedidos del Cliente
 */
require_once '../includes/init.php';

auth()->requireLogin(['client']);

$user = auth()->getCurrentUser();
$db = Database::getInstance();

// Obtener todas las órdenes del cliente
$orders = $db->fetchAll(
    "SELECT o.*, s.name as service_name, s.price, 
            p.name as partner_name, p.phone as partner_phone, p.photo as partner_photo
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     LEFT JOIN partners p ON o.partner_id = p.id
     WHERE o.client_id = :client_id 
     ORDER BY o.created_at DESC",
    ['client_id' => $user['id']]
);

// Estadísticas
$stats = $db->fetchOne(
    "SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN (SELECT price FROM services WHERE id = orders.service_id) END), 0) as total_spent
     FROM orders 
     WHERE client_id = :client_id",
    ['client_id' => $user['id']]
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Historial - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
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
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .back-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header h1 {
            font-size: 1.2rem;
        }
        
        /* Stats */
        .stats-row {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .stat-item {
            flex: 1;
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 12px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.3rem;
            font-weight: 700;
        }
        
        .stat-label {
            font-size: 0.7rem;
            opacity: 0.9;
        }
        
        /* Main */
        .main-content {
            padding: 20px;
        }
        
        .section-title {
            font-size: 1rem;
            color: #1e3a5f;
            margin-bottom: 15px;
        }
        
        /* Order Cards */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .order-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .order-service {
            font-weight: 600;
            color: #1e3a5f;
            font-size: 1rem;
            margin-bottom: 3px;
        }
        
        .order-code {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .order-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0077b6;
        }
        
        .order-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #f1f5f9;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
        }
        
        .detail-label {
            color: #64748b;
        }
        
        .detail-value {
            color: #1e3a5f;
        }
        
        .partner-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            border-radius: 10px;
            padding: 10px;
            margin-top: 10px;
        }
        
        .partner-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-size: 1rem;
        }
        
        .partner-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .partner-name {
            font-size: 0.9rem;
            color: #1e3a5f;
            font-weight: 500;
        }
        
        .partner-role {
            font-size: 0.75rem;
            color: #64748b;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-accepted { background: #dbeafe; color: #1d4ed8; }
        .status-paid { background: #cffafe; color: #0891b2; }
        .status-in_transit { background: #e0e7ff; color: #4338ca; }
        .status-in_progress { background: #fce7f3; color: #be185d; }
        .status-completed { background: #dcfce7; color: #16a34a; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #64748b;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            margin-bottom: 20px;
        }
        
        .btn-request {
            display: inline-block;
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
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
        }
        
        .nav-item.active {
            color: #0077b6;
            background: #e0f2fe;
        }
        
        .nav-icon {
            font-size: 1.3rem;
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-top">
            <a href="index.php" class="back-btn">←</a>
            <h1>📋 Mi Historial</h1>
        </div>
        
        <div class="stats-row">
            <div class="stat-item">
                <div class="stat-value"><?= $stats['total_orders'] ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $stats['completed'] ?></div>
                <div class="stat-label">Completados</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">$<?= number_format($stats['total_spent'], 2) ?></div>
                <div class="stat-label">Gastado</div>
            </div>
        </div>
    </header>
    
    <!-- Main -->
    <main class="main-content">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p>Aún no has solicitado ningún servicio</p>
                <a href="index.php" class="btn-request">Solicitar Limpieza</a>
            </div>
        <?php else: ?>
            <h2 class="section-title">Todos los Pedidos (<?= count($orders) ?>)</h2>
            
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-service">🧹 <?= e($order['service_name']) ?></div>
                                <div class="order-code"><?= e($order['order_code']) ?></div>
                            </div>
                            <div class="order-price">$<?= number_format($order['price'], 2) ?></div>
                        </div>
                        
                        <span class="status-badge status-<?= $order['status'] ?>">
                            <?php
                            $statusLabels = [
                                'pending' => '⏳ Buscando partner',
                                'accepted' => '✅ Aceptado',
                                'paid' => '💳 Pagado',
                                'in_transit' => '🚗 En camino',
                                'in_progress' => '🧹 En progreso',
                                'completed' => '✓ Completado',
                                'cancelled' => '✗ Cancelado'
                            ];
                            echo $statusLabels[$order['status']] ?? $order['status'];
                            ?>
                        </span>
                        
                        <div class="order-details">
                            <div class="detail-row">
                                <span class="detail-label">Fecha solicitud</span>
                                <span class="detail-value"><?= formatDate($order['created_at'], 'd M Y, H:i') ?></span>
                            </div>
                            
                            <?php if ($order['completed_at']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Fecha completado</span>
                                    <span class="detail-value"><?= formatDate($order['completed_at'], 'd M Y, H:i') ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($order['actual_time']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Duración</span>
                                    <span class="detail-value"><?= $order['actual_time'] ?> minutos</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="detail-row">
                                <span class="detail-label">Dirección</span>
                                <span class="detail-value"><?= e($order['client_address'] ?: 'GPS') ?></span>
                            </div>
                        </div>
                        
                        <?php if ($order['partner_name']): ?>
                            <div class="partner-info">
                                <div class="partner-photo">
                                    <?php if ($order['partner_photo']): ?>
                                        <img src="../<?= e($order['partner_photo']) ?>" alt="">
                                    <?php else: ?>
                                        👤
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="partner-name"><?= e($order['partner_name']) ?></div>
                                    <div class="partner-role">Profesional de limpieza</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item">
            <span class="nav-icon">🏠</span>
            Inicio
        </a>
        <a href="history.php" class="nav-item active">
            <span class="nav-icon">📋</span>
            Historial
        </a>
        <a href="profile.php" class="nav-item">
            <span class="nav-icon">👤</span>
            Perfil
        </a>
    </nav>
</body>
</html>