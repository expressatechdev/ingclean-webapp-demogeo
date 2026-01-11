<?php
/**
 * INGClean Admin - Gestión de Partners
 */
require_once '../includes/init.php';

auth()->requireLogin(['admin']);

$user = auth()->getCurrentUser();
$db = Database::getInstance();

// Filtros
$statusFilter = get('status', 'all');
$searchQuery = get('search', '');

// Construir query
$where = "1=1";
$params = [];

if ($statusFilter !== 'all') {
    $where .= " AND status = :status";
    $params['status'] = $statusFilter;
}

if ($searchQuery) {
    $where .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params['search'] = "%{$searchQuery}%";
}

$partners = $db->fetchAll(
    "SELECT * FROM partners WHERE {$where} ORDER BY created_at DESC",
    $params
);

// Contadores
$counts = [
    'all' => $db->count('partners'),
    'pending' => $db->count('partners', "status = 'pending'"),
    'approved' => $db->count('partners', "status = 'approved'"),
    'rejected' => $db->count('partners', "status = 'rejected'")
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partners - Admin <?= APP_NAME ?></title>
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
        
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .page-header h1 {
            font-size: 1.5rem;
            color: #1e3a5f;
        }
        
        /* Filters */
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
        
        .filter-tabs {
            display: flex;
            gap: 10px;
        }
        
        .filter-tab {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            color: #64748b;
            background: #f1f5f9;
            transition: all 0.3s;
        }
        
        .filter-tab:hover {
            background: #e2e8f0;
        }
        
        .filter-tab.active {
            background: #0077b6;
            color: white;
        }
        
        .filter-tab .count {
            background: rgba(0,0,0,0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-left: 5px;
        }
        
        .filter-tab.active .count {
            background: rgba(255,255,255,0.2);
        }
        
        .search-box {
            flex: 1;
            max-width: 300px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: inherit;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #0077b6;
        }
        
        /* Partners Table */
        .partners-table {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
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
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .partner-cell {
            display: flex;
            align-items: center;
            gap: 12px;
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
        
        .partner-name {
            font-weight: 500;
            color: #1e3a5f;
        }
        
        .partner-email {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #dcfce7; color: #16a34a; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        
        .availability {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
        }
        
        .availability.online { color: #16a34a; }
        .availability.offline { color: #94a3b8; }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            font-size: 0.8rem;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .btn-approve { background: #dcfce7; color: #16a34a; }
        .btn-reject { background: #fee2e2; color: #dc2626; }
        .btn-view { background: #dbeafe; color: #1d4ed8; }
        
        .btn-action:hover {
            opacity: 0.8;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #64748b;
        }
        
        .stats-row {
            display: flex;
            gap: 8px;
        }
        
        .stat-mini {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .stat-mini strong {
            color: #1e3a5f;
        }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .filters { flex-direction: column; }
            .filter-tabs { flex-wrap: wrap; }
            .search-box { max-width: 100%; }
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
            <li><a href="index.php"><span class="icon">📊</span> Dashboard</a></li>
            <li><a href="partners.php" class="active"><span class="icon">👥</span> Partners</a></li>
            <li><a href="orders.php"><span class="icon">📋</span> Órdenes</a></li>
            <li><a href="payments.php"><span class="icon">💳</span> Pagos</a></li>
            <li><a href="clients.php"><span class="icon">👤</span> Clientes</a></li>
            <li><a href="settings.php"><span class="icon">⚙️</span> Configuración</a></li>
        </ul>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1>👥 Gestión de Partners</h1>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <div class="filter-tabs">
                <a href="?status=all" class="filter-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">
                    Todos <span class="count"><?= $counts['all'] ?></span>
                </a>
                <a href="?status=pending" class="filter-tab <?= $statusFilter === 'pending' ? 'active' : '' ?>">
                    Pendientes <span class="count"><?= $counts['pending'] ?></span>
                </a>
                <a href="?status=approved" class="filter-tab <?= $statusFilter === 'approved' ? 'active' : '' ?>">
                    Aprobados <span class="count"><?= $counts['approved'] ?></span>
                </a>
                <a href="?status=rejected" class="filter-tab <?= $statusFilter === 'rejected' ? 'active' : '' ?>">
                    Rechazados <span class="count"><?= $counts['rejected'] ?></span>
                </a>
            </div>
            
            <form class="search-box" method="GET">
                <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                <input type="text" name="search" placeholder="🔍 Buscar por nombre, email o teléfono..." value="<?= e($searchQuery) ?>">
            </form>
        </div>
        
        <!-- Partners Table -->
        <div class="partners-table">
            <?php if (empty($partners)): ?>
                <div class="empty-state">
                    <p>No se encontraron partners</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Partner</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Disponibilidad</th>
                            <th>Estadísticas</th>
                            <th>Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($partners as $partner): ?>
                            <tr>
                                <td>
                                    <div class="partner-cell">
                                        <div class="partner-photo">
                                            <?php if ($partner['photo']): ?>
                                                <img src="../<?= e($partner['photo']) ?>" alt="">
                                            <?php else: ?>
                                                👤
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="partner-name"><?= e($partner['name']) ?></div>
                                            <div class="partner-email"><?= e($partner['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e($partner['phone']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $partner['status'] ?>">
                                        <?= ucfirst($partner['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="availability <?= $partner['is_available'] ? 'online' : 'offline' ?>">
                                        <?= $partner['is_available'] ? '🟢 Online' : '⚫ Offline' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="stats-row">
                                        <span class="stat-mini"><strong><?= $partner['total_services'] ?></strong> servicios</span>
                                        <span class="stat-mini">• <strong>$<?= number_format($partner['total_earnings'], 2) ?></strong></span>
                                    </div>
                                </td>
                                <td><?= formatDate($partner['created_at'], 'd M Y') ?></td>
                                <td>
                                    <div class="actions">
                                        <?php if ($partner['status'] === 'pending'): ?>
                                            <button class="btn-action btn-approve" onclick="updateStatus(<?= $partner['id'] ?>, 'approved')">✓ Aprobar</button>
                                            <button class="btn-action btn-reject" onclick="updateStatus(<?= $partner['id'] ?>, 'rejected')">✗ Rechazar</button>
                                        <?php elseif ($partner['status'] === 'approved'): ?>
                                            <button class="btn-action btn-reject" onclick="updateStatus(<?= $partner['id'] ?>, 'rejected')">Suspender</button>
                                        <?php else: ?>
                                            <button class="btn-action btn-approve" onclick="updateStatus(<?= $partner['id'] ?>, 'approved')">Reactivar</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
    
    <script>
        function updateStatus(id, status) {
            const action = status === 'approved' ? 'aprobar' : (status === 'rejected' ? 'rechazar' : 'actualizar');
            if (!confirm(`¿${action.charAt(0).toUpperCase() + action.slice(1)} este partner?`)) return;
            
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
                    alert(data.message || 'Error al actualizar');
                }
            });
        }
    </script>
</body>
</html>
