<?php
/**
 * INGClean - Panel de Administración
 */
require_once '../includes/init.php';

auth()->requireLogin(['admin']);

$user = auth()->getCurrentUser();
$db = Database::getInstance();

// Estadísticas generales
$stats = [
    'total_clients' => $db->count('clients'),
    'total_partners' => $db->count('partners'),
    'pending_partners' => $db->count('partners', "status = 'pending'"),
    'approved_partners' => $db->count('partners', "status = 'approved'"),
    'total_orders' => $db->count('orders'),
    'pending_orders' => $db->count('orders', "status = 'pending'"),
    'active_orders' => $db->count('orders', "status IN ('accepted', 'paid', 'in_transit', 'in_progress')"),
    'completed_orders' => $db->count('orders', "status = 'completed'"),
];

// Ingresos
$earnings = $db->fetchOne(
    "SELECT 
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(SUM(platform_fee), 0) as platform_earnings,
        COALESCE(SUM(partner_amount), 0) as partner_payouts
     FROM payments WHERE status = 'completed'"
);

// Ingresos de hoy
$todayEarnings = $db->fetchOne(
    "SELECT COALESCE(SUM(platform_fee), 0) as today
     FROM payments 
     WHERE status = 'completed' AND DATE(completed_at) = CURDATE()"
);

// Últimas órdenes
$recentOrders = $db->fetchAll(
    "SELECT o.*, s.name as service_name, c.name as client_name, p.name as partner_name
     FROM orders o
     JOIN services s ON o.service_id = s.id
     JOIN clients c ON o.client_id = c.id
     LEFT JOIN partners p ON o.partner_id = p.id
     ORDER BY o.created_at DESC
     LIMIT 10"
);

// Partners pendientes de aprobación
$pendingPartners = $db->fetchAll(
    "SELECT * FROM partners WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 250px;
            background: linear-gradient(180deg, #0a1628 0%, #1a3a5c 100%);
            padding: 20px;
            z-index: 100;
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 25px;
        }
        
        .sidebar-logo img {
            width: 45px;
            height: 45px;
            border-radius: 12px;
        }
        
        .sidebar-logo span {
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .sidebar-nav {
            list-style: none;
        }
        
        .sidebar-nav li {
            margin-bottom: 5px;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-nav a.active {
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
        }
        
        .sidebar-nav .icon {
            font-size: 1.2rem;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            background: #00b4d8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .admin-details {
            flex: 1;
        }
        
        .admin-details h4 {
            color: white;
            font-size: 0.85rem;
        }
        
        .admin-details p {
            color: #94a3b8;
            font-size: 0.75rem;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 1.5rem;
            color: #1e3a5f;
            margin-bottom: 5px;
        }
        
        .page-header p {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-icon.green { background: #dcfce7; }
        .stat-icon.blue { background: #dbeafe; }
        .stat-icon.yellow { background: #fef3c7; }
        .stat-icon.purple { background: #f3e8ff; }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e3a5f;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.85rem;
        }
        
        .stat-change {
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 5px;
        }
        
        .stat-change.positive {
            background: #dcfce7;
            color: #16a34a;
        }
        
        /* Revenue Cards */
        .revenue-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .revenue-card {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            border-radius: 16px;
            padding: 25px;
            color: white;
        }
        
        .revenue-card.secondary {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
        }
        
        .revenue-card.tertiary {
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
        }
        
        .revenue-label {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .revenue-value {
            font-size: 2rem;
            font-weight: 700;
        }
        
        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .card-header h3 {
            font-size: 1rem;
            color: #1e3a5f;
        }
        
        .card-header a {
            color: #0077b6;
            font-size: 0.85rem;
            text-decoration: none;
        }
        
        .card-body {
            padding: 0;
        }
        
        /* Orders Table */
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th {
            text-align: left;
            padding: 12px 20px;
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            background: #f8fafc;
            font-weight: 600;
        }
        
        .orders-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
        }
        
        .orders-table tr:last-child td {
            border-bottom: none;
        }
        
        .order-code {
            font-weight: 600;
            color: #0077b6;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
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
        
        /* Pending Partners */
        .partner-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .partner-item:last-child {
            border-bottom: none;
        }
        
        .partner-photo {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-size: 1.2rem;
        }
        
        .partner-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .partner-info {
            flex: 1;
        }
        
        .partner-info h4 {
            font-size: 0.9rem;
            color: #1e3a5f;
        }
        
        .partner-info p {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .partner-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-approve, .btn-reject {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            font-size: 0.8rem;
            cursor: pointer;
            font-family: inherit;
        }
        
        .btn-approve {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .btn-reject {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .empty-state {
            padding: 30px;
            text-align: center;
            color: #64748b;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0;
            }
            .stats-grid, .revenue-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="../assets/img/logo.png" alt="INGClean">
            <span>INGClean Admin</span>
        </div>
        
        <ul class="sidebar-nav">
            <li><a href="index.php" class="active"><span class="icon">📊</span> Dashboard</a></li>
            <li><a href="partners.php"><span class="icon">👥</span> Partners</a></li>
            <li><a href="orders.php"><span class="icon">📋</span> Órdenes</a></li>
            <li><a href="payments.php"><span class="icon">💳</span> Pagos</a></li>
            <li><a href="clients.php"><span class="icon">👤</span> Clientes</a></li>
            <li><a href="settings.php"><span class="icon">⚙️</span> Configuración</a></li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="admin-info">
                <div class="admin-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                <div class="admin-details">
                    <h4><?= e($user['name']) ?></h4>
                    <p>Administrador</p>
                </div>
            </div>
            <a href="../logout.php" style="display: block; text-align: center; color: #94a3b8; margin-top: 15px; font-size: 0.85rem; text-decoration: none;">
                🚪 Cerrar Sesión
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Bienvenido de vuelta, <?= e(explode(' ', $user['name'])[0]) ?>. Aquí está el resumen de hoy.</p>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-icon blue">👥</div>
                </div>
                <div class="stat-value"><?= $stats['total_clients'] ?></div>
                <div class="stat-label">Clientes Registrados</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-icon green">👷</div>
                    <?php if ($stats['pending_partners'] > 0): ?>
                        <span class="stat-change positive"><?= $stats['pending_partners'] ?> pendientes</span>
                    <?php endif; ?>
                </div>
                <div class="stat-value"><?= $stats['approved_partners'] ?></div>
                <div class="stat-label">Partners Activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-icon yellow">📋</div>
                </div>
                <div class="stat-value"><?= $stats['completed_orders'] ?></div>
                <div class="stat-label">Servicios Completados</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-icon purple">⏳</div>
                </div>
                <div class="stat-value"><?= $stats['active_orders'] ?></div>
                <div class="stat-label">Servicios Activos</div>
            </div>
        </div>
        
        <!-- Revenue -->
        <div class="revenue-grid">
            <div class="revenue-card">
                <div class="revenue-label">💰 Ingresos de Hoy (35%)</div>
                <div class="revenue-value">$<?= number_format($todayEarnings['today'], 2) ?></div>
            </div>
            <div class="revenue-card secondary">
                <div class="revenue-label">📈 Ingresos Totales (35%)</div>
                <div class="revenue-value">$<?= number_format($earnings['platform_earnings'], 2) ?></div>
            </div>
            <div class="revenue-card tertiary">
                <div class="revenue-label">💵 Pagado a Partners (65%)</div>
                <div class="revenue-value">$<?= number_format($earnings['partner_payouts'], 2) ?></div>
            </div>
        </div>
        
        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Orders -->
            <div class="card">
                <div class="card-header">
                    <h3>📋 Órdenes Recientes</h3>
                    <a href="orders.php">Ver todas →</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentOrders)): ?>
                        <div class="empty-state">No hay órdenes aún</div>
                    <?php else: ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Servicio</th>
                                    <th>Cliente</th>
                                    <th>Partner</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><span class="order-code"><?= e($order['order_code']) ?></span></td>
                                        <td><?= e($order['service_name']) ?></td>
                                        <td><?= e($order['client_name']) ?></td>
                                        <td><?= e($order['partner_name'] ?? '—') ?></td>
                                        <td><span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Pending Partners -->
            <div class="card">
                <div class="card-header">
                    <h3>⏳ Partners Pendientes</h3>
                    <a href="partners.php">Ver todos →</a>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingPartners)): ?>
                        <div class="empty-state">No hay partners pendientes</div>
                    <?php else: ?>
                        <?php foreach ($pendingPartners as $partner): ?>
                            <div class="partner-item">
                                <div class="partner-photo">
                                    <?php if ($partner['photo']): ?>
                                        <img src="../<?= e($partner['photo']) ?>" alt="">
                                    <?php else: ?>
                                        👤
                                    <?php endif; ?>
                                </div>
                                <div class="partner-info">
                                    <h4><?= e($partner['name']) ?></h4>
                                    <p><?= e($partner['email']) ?></p>
                                </div>
                                <div class="partner-actions">
                                    <button class="btn-approve" onclick="approvePartner(<?= $partner['id'] ?>)">✓</button>
                                    <button class="btn-reject" onclick="rejectPartner(<?= $partner['id'] ?>)">✗</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        function approvePartner(id) {
            if (!confirm('¿Aprobar este partner?')) return;
            updatePartnerStatus(id, 'approved');
        }
        
        function rejectPartner(id) {
            if (!confirm('¿Rechazar este partner?')) return;
            updatePartnerStatus(id, 'rejected');
        }
        
        function updatePartnerStatus(id, status) {
            fetch('../api/admin/update-partner-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ partner_id: id, status: status })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error');
                }
            });
        }
    </script>
</body>
</html>
