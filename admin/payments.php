<?php
/**
 * INGClean Admin - Gestión de Pagos
 */
require_once '../includes/init.php';

auth()->requireLogin(['admin']);

$db = Database::getInstance();

// Estadísticas
$stats = $db->fetchOne(
    "SELECT 
        COUNT(*) as total_payments,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(SUM(platform_fee), 0) as platform_total,
        COALESCE(SUM(partner_amount), 0) as partner_total
     FROM payments WHERE status = 'completed'"
);

// Pagos recientes
$payments = $db->fetchAll(
    "SELECT p.*, o.order_code, s.name as service_name,
            c.name as client_name, pr.name as partner_name
     FROM payments p
     JOIN orders o ON p.order_id = o.id
     JOIN services s ON o.service_id = s.id
     JOIN clients c ON o.client_id = c.id
     LEFT JOIN partners pr ON o.partner_id = pr.id
     ORDER BY p.created_at DESC
     LIMIT 100"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagos - Admin <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f1f5f9; min-height: 100vh; }
        
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0; width: 250px;
            background: linear-gradient(180deg, #0a1628 0%, #1a3a5c 100%);
            padding: 20px; z-index: 100;
        }
        .sidebar-logo { display: flex; align-items: center; gap: 12px; padding-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 25px; }
        .sidebar-logo img { width: 45px; height: 45px; border-radius: 12px; }
        .sidebar-logo span { color: white; font-weight: 600; font-size: 1.1rem; }
        .sidebar-nav { list-style: none; }
        .sidebar-nav li { margin-bottom: 5px; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: #94a3b8; text-decoration: none; border-radius: 10px; font-size: 0.9rem; }
        .sidebar-nav a:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar-nav a.active { background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%); color: white; }
        
        .main-content { margin-left: 250px; padding: 30px; }
        .page-header { margin-bottom: 25px; }
        .page-header h1 { font-size: 1.5rem; color: #1e3a5f; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; border-radius: 16px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-label { font-size: 0.85rem; color: #64748b; margin-bottom: 8px; }
        .stat-value { font-size: 1.8rem; font-weight: 700; color: #1e3a5f; }
        .stat-card.primary { background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%); }
        .stat-card.primary .stat-label, .stat-card.primary .stat-value { color: white; }
        
        .payments-table { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px 20px; font-size: 0.75rem; color: #64748b; text-transform: uppercase; background: #f8fafc; font-weight: 600; }
        td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        tr:hover { background: #f8fafc; }
        
        .order-code { font-weight: 600; color: #0077b6; }
        .amount { font-weight: 600; }
        .amount.total { color: #1e3a5f; }
        .amount.platform { color: #0077b6; }
        .amount.partner { color: #16a34a; }
        
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
        .status-completed { background: #dcfce7; color: #16a34a; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-refunded { background: #fee2e2; color: #dc2626; }
        
        .empty-state { text-align: center; padding: 50px; color: #64748b; }
        
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { margin-left: 0; } .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="../assets/img/logo.png" alt="INGClean">
            <span>INGClean Admin</span>
        </div>
        <ul class="sidebar-nav">
            <li><a href="index.php"><span>📊</span> Dashboard</a></li>
            <li><a href="partners.php"><span>👥</span> Partners</a></li>
            <li><a href="orders.php"><span>📋</span> Órdenes</a></li>
            <li><a href="payments.php" class="active"><span>💳</span> Pagos</a></li>
            <li><a href="clients.php"><span>👤</span> Clientes</a></li>
            <li><a href="settings.php"><span>⚙️</span> Configuración</a></li>
        </ul>
    </aside>
    
    <main class="main-content">
        <div class="page-header">
            <h1>💳 Gestión de Pagos</h1>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-label">💰 Ingresos Totales</div>
                <div class="stat-value">$<?= number_format($stats['total_revenue'], 2) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">🏢 Comisión INGClean (35%)</div>
                <div class="stat-value">$<?= number_format($stats['platform_total'], 2) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">👷 Pagado a Partners (65%)</div>
                <div class="stat-value">$<?= number_format($stats['partner_total'], 2) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">📊 Total Transacciones</div>
                <div class="stat-value"><?= $stats['total_payments'] ?></div>
            </div>
        </div>
        
        <div class="payments-table">
            <?php if (empty($payments)): ?>
                <div class="empty-state">No hay pagos registrados</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Servicio</th>
                            <th>Cliente</th>
                            <th>Partner</th>
                            <th>Total</th>
                            <th>Comisión</th>
                            <th>Partner</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><span class="order-code"><?= e($payment['order_code']) ?></span></td>
                                <td><?= e($payment['service_name']) ?></td>
                                <td><?= e($payment['client_name']) ?></td>
                                <td><?= e($payment['partner_name'] ?? '—') ?></td>
                                <td><span class="amount total">$<?= number_format($payment['total_amount'], 2) ?></span></td>
                                <td><span class="amount platform">$<?= number_format($payment['platform_fee'], 2) ?></span></td>
                                <td><span class="amount partner">$<?= number_format($payment['partner_amount'], 2) ?></span></td>
                                <td><span class="status-badge status-<?= $payment['status'] ?>"><?= ucfirst($payment['status']) ?></span></td>
                                <td><?= $payment['completed_at'] ? formatDate($payment['completed_at'], 'd M Y H:i') : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
