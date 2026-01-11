<?php
/**
 * INGClean Admin - Gestión de Órdenes
 */
require_once '../includes/init.php';

auth()->requireLogin(['admin']);

$db = Database::getInstance();

// Filtros
$statusFilter = get('status', 'all');
$searchQuery = get('search', '');

// Construir query
$where = "1=1";
$params = [];

if ($statusFilter !== 'all') {
    $where .= " AND o.status = :status";
    $params['status'] = $statusFilter;
}

if ($searchQuery) {
    $where .= " AND (o.order_code LIKE :search OR c.name LIKE :search OR p.name LIKE :search)";
    $params['search'] = "%{$searchQuery}%";
}

$orders = $db->fetchAll(
    "SELECT o.*, s.name as service_name, s.price,
            c.name as client_name, c.phone as client_phone,
            p.name as partner_name, p.phone as partner_phone
     FROM orders o
     JOIN services s ON o.service_id = s.id
     JOIN clients c ON o.client_id = c.id
     LEFT JOIN partners p ON o.partner_id = p.id
     WHERE {$where}
     ORDER BY o.created_at DESC
     LIMIT 100",
    $params
);

// Contadores
$counts = [
    'all' => $db->count('orders'),
    'pending' => $db->count('orders', "status = 'pending'"),
    'active' => $db->count('orders', "status IN ('accepted', 'paid', 'in_transit', 'in_progress')"),
    'completed' => $db->count('orders', "status = 'completed'"),
    'cancelled' => $db->count('orders', "status = 'cancelled'")
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Órdenes - Admin <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
        }
        
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
        
        .sidebar-logo img { width: 45px; height: 45px; border-radius: 12px; }
        .sidebar-logo span { color: white; font-weight: 600; font-size: 1.1rem; }
        
        .sidebar-nav { list-style: none; }
        .sidebar-nav li { margin-bottom: 5px; }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 10px;
            font-size: 0.9rem;
        }
        .sidebar-nav a:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar-nav a.active { background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%); color: white; }
        
        .main-content { margin-left: 250px; padding: 30px; }
        
        .page-header { margin-bottom: 25px; }
        .page-header h1 { font-size: 1.5rem; color: #1e3a5f; }
        
        .filters {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-tabs { display: flex; gap: 10px; flex-wrap: wrap; }
        
        .filter-tab {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            color: #64748b;
            background: #f1f5f9;
        }
        .filter-tab:hover { background: #e2e8f0; }
        .filter-tab.active { background: #0077b6; color: white; }
        .filter-tab .count {
            background: rgba(0,0,0,0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-left: 5px;
        }
        .filter-tab.active .count { background: rgba(255,255,255,0.2); }
        
        .search-box { flex: 1; max-width: 300px; }
        .search-box input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: inherit;
        }
        .search-box input:focus { outline: none; border-color: #0077b6; }
        
        .orders-table {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        table { width: 100%; border-collapse: collapse; }
        
        th {
            text-align: left;
            padding: 15px 20px;
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            background: #f8fafc;
            font-weight: 600;
        }
        
        td {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
        }
        
        tr:hover { background: #f8fafc; }
        
        .order-code { font-weight: 600; color: #0077b6; }
        
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
        
        .price { font-weight: 600; color: #16a34a; }
        
        .btn-cancel {
            padding: 5px 10px;
            border-radius: 5px;
            border: none;
            background: #fee2e2;
            color: #dc2626;
            font-size: 0.8rem;
            cursor: pointer;
        }
        
        .empty-state { text-align: center; padding: 50px; color: #64748b; }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
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
            <li><a href="orders.php" class="active"><span>📋</span> Órdenes</a></li>
            <li><a href="payments.php"><span>💳</span> Pagos</a></li>
            <li><a href="clients.php"><span>👤</span> Clientes</a></li>
            <li><a href="settings.php"><span>⚙️</span> Configuración</a></li>
        </ul>
    </aside>
    
    <main class="main-content">
        <div class="page-header">
            <h1>📋 Gestión de Órdenes</h1>
        </div>
        
        <div class="filters">
            <div class="filter-tabs">
                <a href="?status=all" class="filter-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">
                    Todas <span class="count"><?= $counts['all'] ?></span>
                </a>
                <a href="?status=pending" class="filter-tab <?= $statusFilter === 'pending' ? 'active' : '' ?>">
                    Pendientes <span class="count"><?= $counts['pending'] ?></span>
                </a>
                <a href="?status=active" class="filter-tab <?= $statusFilter === 'active' ? 'active' : '' ?>">
                    Activas <span class="count"><?= $counts['active'] ?></span>
                </a>
                <a href="?status=completed" class="filter-tab <?= $statusFilter === 'completed' ? 'active' : '' ?>">
                    Completadas <span class="count"><?= $counts['completed'] ?></span>
                </a>
                <a href="?status=cancelled" class="filter-tab <?= $statusFilter === 'cancelled' ? 'active' : '' ?>">
                    Canceladas <span class="count"><?= $counts['cancelled'] ?></span>
                </a>
            </div>
            
            <form class="search-box" method="GET">
                <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                <input type="text" name="search" placeholder="🔍 Buscar por código, cliente o partner..." value="<?= e($searchQuery) ?>">
            </form>
        </div>
        
        <div class="orders-table">
            <?php if (empty($orders)): ?>
                <div class="empty-state">No se encontraron órdenes</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Servicio</th>
                            <th>Cliente</th>
                            <th>Partner</th>
                            <th>Estado</th>
                            <th>Precio</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><span class="order-code"><?= e($order['order_code']) ?></span></td>
                                <td><?= e($order['service_name']) ?></td>
                                <td>
                                    <div><?= e($order['client_name']) ?></div>
                                    <div style="font-size: 0.8rem; color: #64748b;"><?= e($order['client_phone']) ?></div>
                                </td>
                                <td>
                                    <?php if ($order['partner_name']): ?>
                                        <div><?= e($order['partner_name']) ?></div>
                                        <div style="font-size: 0.8rem; color: #64748b;"><?= e($order['partner_phone']) ?></div>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst(str_replace('_', ' ', $order['status'])) ?></span></td>
                                <td><span class="price">$<?= number_format($order['price'], 2) ?></span></td>
                                <td><?= formatDate($order['created_at'], 'd M Y H:i') ?></td>
                                <td>
                                    <?php if (!in_array($order['status'], ['completed', 'cancelled'])): ?>
                                        <button class="btn-cancel" onclick="cancelOrder(<?= $order['id'] ?>)">Cancelar</button>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
    
    <script>
        function cancelOrder(id) {
            if (!confirm('¿Cancelar esta orden?')) return;
            
            fetch('../api/admin/cancel-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: id })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
                else alert(data.message || 'Error');
            });
        }
    </script>
</body>
</html>
