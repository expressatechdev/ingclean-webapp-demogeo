<?php
/**
 * INGClean Admin - Gestión de Clientes
 */
require_once '../includes/init.php';

auth()->requireLogin(['admin']);

$db = Database::getInstance();

$searchQuery = get('search', '');

$where = "1=1";
$params = [];

if ($searchQuery) {
    $where .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params['search'] = "%{$searchQuery}%";
}

$clients = $db->fetchAll(
    "SELECT c.*, 
            (SELECT COUNT(*) FROM orders WHERE client_id = c.id) as total_orders,
            (SELECT COUNT(*) FROM orders WHERE client_id = c.id AND status = 'completed') as completed_orders
     FROM clients c
     WHERE {$where}
     ORDER BY c.created_at DESC",
    $params
);

$totalClients = $db->count('clients');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Admin <?= APP_NAME ?></title>
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
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-header h1 { font-size: 1.5rem; color: #1e3a5f; }
        .page-header .count { background: #0077b6; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem; }
        
        .search-box { background: white; border-radius: 12px; padding: 15px 20px; margin-bottom: 25px; }
        .search-box input { width: 100%; max-width: 400px; padding: 10px 15px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; font-family: inherit; }
        .search-box input:focus { outline: none; border-color: #0077b6; }
        
        .clients-table { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px 20px; font-size: 0.75rem; color: #64748b; text-transform: uppercase; background: #f8fafc; font-weight: 600; }
        td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        tr:hover { background: #f8fafc; }
        
        .client-cell { display: flex; align-items: center; gap: 12px; }
        .client-avatar { width: 40px; height: 40px; border-radius: 50%; background: #0077b6; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        .client-name { font-weight: 500; color: #1e3a5f; }
        .client-email { font-size: 0.8rem; color: #64748b; }
        
        .stat-mini { font-size: 0.85rem; color: #64748b; }
        .stat-mini strong { color: #1e3a5f; }
        
        .empty-state { text-align: center; padding: 50px; color: #64748b; }
        
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { margin-left: 0; } }
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
            <li><a href="payments.php"><span>💳</span> Pagos</a></li>
            <li><a href="clients.php" class="active"><span>👤</span> Clientes</a></li>
            <li><a href="settings.php"><span>⚙️</span> Configuración</a></li>
        </ul>
    </aside>
    
    <main class="main-content">
        <div class="page-header">
            <h1>👤 Clientes Registrados</h1>
            <span class="count"><?= $totalClients ?> total</span>
        </div>
        
        <div class="search-box">
            <form method="GET">
                <input type="text" name="search" placeholder="🔍 Buscar por nombre, email o teléfono..." value="<?= e($searchQuery) ?>">
            </form>
        </div>
        
        <div class="clients-table">
            <?php if (empty($clients)): ?>
                <div class="empty-state">No se encontraron clientes</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Teléfono</th>
                            <th>Dirección</th>
                            <th>Servicios</th>
                            <th>Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td>
                                    <div class="client-cell">
                                        <div class="client-avatar"><?= strtoupper(substr($client['name'], 0, 1)) ?></div>
                                        <div>
                                            <div class="client-name"><?= e($client['name']) ?></div>
                                            <div class="client-email"><?= e($client['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e($client['phone']) ?></td>
                                <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= e($client['address'] ?: '—') ?>
                                </td>
                                <td>
                                    <span class="stat-mini">
                                        <strong><?= $client['completed_orders'] ?></strong> completados / 
                                        <strong><?= $client['total_orders'] ?></strong> total
                                    </span>
                                </td>
                                <td><?= formatDate($client['created_at'], 'd M Y') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
