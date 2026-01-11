<?php
/**
 * INGClean - Ganancias del Partner
 */
require_once '../includes/init.php';

auth()->requireLogin(['partner']);

$user = auth()->getCurrentUser();
$db = Database::getInstance();

// Ganancias de hoy
$todayEarnings = $db->fetchOne(
    "SELECT COALESCE(SUM(p.partner_amount), 0) as earnings, COUNT(*) as count
     FROM payments p
     JOIN orders o ON p.order_id = o.id
     WHERE o.partner_id = :partner_id 
     AND p.status = 'completed'
     AND DATE(p.completed_at) = CURDATE()",
    ['partner_id' => $user['id']]
);

// Ganancias de esta semana
$weekEarnings = $db->fetchOne(
    "SELECT COALESCE(SUM(p.partner_amount), 0) as earnings, COUNT(*) as count
     FROM payments p
     JOIN orders o ON p.order_id = o.id
     WHERE o.partner_id = :partner_id 
     AND p.status = 'completed'
     AND YEARWEEK(p.completed_at) = YEARWEEK(CURDATE())",
    ['partner_id' => $user['id']]
);

// Ganancias de este mes
$monthEarnings = $db->fetchOne(
    "SELECT COALESCE(SUM(p.partner_amount), 0) as earnings, COUNT(*) as count
     FROM payments p
     JOIN orders o ON p.order_id = o.id
     WHERE o.partner_id = :partner_id 
     AND p.status = 'completed'
     AND MONTH(p.completed_at) = MONTH(CURDATE())
     AND YEAR(p.completed_at) = YEAR(CURDATE())",
    ['partner_id' => $user['id']]
);

// Historial de pagos (últimos 20)
$payments = $db->fetchAll(
    "SELECT p.*, o.order_code, o.completed_at as service_date, s.name as service_name
     FROM payments p
     JOIN orders o ON p.order_id = o.id
     JOIN services s ON o.service_id = s.id
     WHERE o.partner_id = :partner_id 
     AND p.status = 'completed'
     ORDER BY p.completed_at DESC
     LIMIT 20",
    ['partner_id' => $user['id']]
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Ganancias - <?= APP_NAME ?></title>
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
        
        .total-earnings {
            text-align: center;
            padding: 20px 0;
        }
        
        .total-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .total-amount {
            font-size: 3rem;
            font-weight: 700;
        }
        
        .main-content {
            padding: 20px;
            margin-top: -20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0077b6;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 3px;
        }
        
        .stat-count {
            font-size: 0.7rem;
            color: #94a3b8;
        }
        
        .section-title {
            font-size: 1rem;
            color: #1e3a5f;
            margin-bottom: 15px;
        }
        
        .payments-list {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .payment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .payment-item:last-child {
            border-bottom: none;
        }
        
        .payment-info h4 {
            font-size: 0.9rem;
            color: #1e3a5f;
            margin-bottom: 3px;
        }
        
        .payment-info p {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .payment-amount {
            text-align: right;
        }
        
        .payment-amount .amount {
            font-size: 1.1rem;
            font-weight: 600;
            color: #16a34a;
        }
        
        .payment-amount .date {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
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
    </style>
</head>
<body>
    <header class="header">
        <div class="header-top">
            <a href="index.php" class="back-btn">←</a>
            <h1>💰 Mis Ganancias</h1>
        </div>
        <div class="total-earnings">
            <div class="total-label">Total acumulado</div>
            <div class="total-amount">$<?= number_format($user['total_earnings'], 2) ?></div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">$<?= number_format($todayEarnings['earnings'], 2) ?></div>
                <div class="stat-label">Hoy</div>
                <div class="stat-count"><?= $todayEarnings['count'] ?> servicios</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$<?= number_format($weekEarnings['earnings'], 2) ?></div>
                <div class="stat-label">Esta semana</div>
                <div class="stat-count"><?= $weekEarnings['count'] ?> servicios</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$<?= number_format($monthEarnings['earnings'], 2) ?></div>
                <div class="stat-label">Este mes</div>
                <div class="stat-count"><?= $monthEarnings['count'] ?> servicios</div>
            </div>
        </div>
        
        <h2 class="section-title">📋 Historial de Pagos</h2>
        
        <div class="payments-list">
            <?php if (empty($payments)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <p>Aún no tienes pagos registrados</p>
                </div>
            <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                    <div class="payment-item">
                        <div class="payment-info">
                            <h4><?= e($payment['service_name']) ?></h4>
                            <p><?= e($payment['order_code']) ?></p>
                        </div>
                        <div class="payment-amount">
                            <div class="amount">+$<?= number_format($payment['partner_amount'], 2) ?></div>
                            <div class="date"><?= formatDate($payment['completed_at'], 'd M Y') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item">
            <span class="nav-icon">🏠</span>
            Inicio
        </a>
        <a href="earnings.php" class="nav-item active">
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
</body>
</html>
