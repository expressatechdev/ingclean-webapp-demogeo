<?php
/**
 * INGClean Admin - Gestión de Partners
 * ACTUALIZADO: Botón para cambiar contraseña
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
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-approved {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .availability {
            font-size: 0.85rem;
        }
        
        .online { color: #16a34a; }
        .offline { color: #64748b; }
        
        .stats-row {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .stat-mini {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .stat-mini strong {
            color: #1e3a5f;
        }
        
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .btn-approve {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .btn-approve:hover {
            background: #bbf7d0;
        }
        
        .btn-reject {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .btn-reject:hover {
            background: #fecaca;
        }
        
        .btn-password {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .btn-password:hover {
            background: #bfdbfe;
        }
        
        .btn-delete {
            background: #f1f5f9;
            color: #64748b;
        }
        
        .btn-delete:hover {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #64748b;
        }
        
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.show {
            display: flex;
        }
        
        .modal {
            background: white;
            border-radius: 16px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        
        .modal h3 {
            color: #1e3a5f;
            margin-bottom: 15px;
        }
        
        .modal h3.delete {
            color: #dc2626;
        }
        
        .modal p {
            color: #64748b;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        
        .modal .stats {
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .modal-btn {
            padding: 10px 24px;
            border-radius: 8px;
            border: none;
            font-size: 0.9rem;
            cursor: pointer;
            font-family: inherit;
        }
        
        .modal-btn-cancel {
            background: #e2e8f0;
            color: #64748b;
        }
        
        .modal-btn-delete {
            background: #dc2626;
            color: white;
        }
        
        .modal-btn-save {
            background: #0077b6;
            color: white;
        }
        
        .modal-btn-save:hover {
            background: #005a8c;
        }
        
        /* Input en modal */
        .modal-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            margin-bottom: 15px;
        }
        
        .modal-input:focus {
            outline: none;
            border-color: #0077b6;
        }
        
        .modal-label {
            display: block;
            text-align: left;
            color: #1e3a5f;
            font-size: 0.85rem;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .password-hint {
            font-size: 0.8rem;
            color: #64748b;
            text-align: left;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .filters { flex-direction: column; align-items: stretch; }
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
                            <tr id="partner-row-<?= $partner['id'] ?>">
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
                                        <button class="btn-action btn-password" onclick="openPasswordModal(<?= $partner['id'] ?>, '<?= e(addslashes($partner['name'])) ?>', 'partner')">🔑</button>
                                        <button class="btn-action btn-delete" onclick="confirmDelete(<?= $partner['id'] ?>, '<?= e(addslashes($partner['name'])) ?>', <?= $partner['total_services'] ?>, <?= $partner['total_earnings'] ?>)">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Modal de Confirmación de Eliminación -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <h3 class="delete">🗑️ Eliminar Partner</h3>
            <p>¿Estás seguro de eliminar a <strong id="partnerName"></strong>?</p>
            <div class="stats" id="partnerStats"></div>
            <p style="color: #f59e0b; font-size: 0.85rem;">⚠️ Esta acción no se puede deshacer</p>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">Cancelar</button>
                <button class="modal-btn modal-btn-delete" id="deleteBtn" onclick="deletePartner()">Eliminar</button>
            </div>
        </div>
    </div>
    
    <!-- Modal de Cambiar Contraseña -->
    <div class="modal-overlay" id="passwordModal">
        <div class="modal">
            <h3>🔑 Cambiar Contraseña</h3>
            <p>Cambiar contraseña de: <strong id="passwordUserName"></strong></p>
            
            <label class="modal-label">Nueva Contraseña</label>
            <input type="password" id="newPassword" class="modal-input" placeholder="Ingresa la nueva contraseña">
            <div class="password-hint">Mínimo 6 caracteres</div>
            
            <label class="modal-label">Confirmar Contraseña</label>
            <input type="password" id="confirmPassword" class="modal-input" placeholder="Repite la contraseña">
            
            <div class="modal-actions">
                <button class="modal-btn modal-btn-cancel" onclick="closePasswordModal()">Cancelar</button>
                <button class="modal-btn modal-btn-save" id="savePasswordBtn" onclick="savePassword()">Guardar</button>
            </div>
        </div>
    </div>
    
    <script>
        // =====================================================
        // ACTUALIZAR ESTADO
        // =====================================================
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
        
        // =====================================================
        // ELIMINAR PARTNER
        // =====================================================
        let partnerToDelete = null;
        
        function confirmDelete(id, name, services, earnings) {
            partnerToDelete = id;
            document.getElementById('partnerName').textContent = name;
            document.getElementById('partnerStats').innerHTML = `
                <strong>${services}</strong> servicios realizados<br>
                <strong>$${parseFloat(earnings).toFixed(2)}</strong> ganancias totales
            `;
            document.getElementById('deleteModal').classList.add('show');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            partnerToDelete = null;
        }
        
        function deletePartner() {
            if (!partnerToDelete) return;
            
            const btn = document.getElementById('deleteBtn');
            btn.disabled = true;
            btn.textContent = 'Eliminando...';
            
            fetch('../api/admin/delete-partner.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ partner_id: partnerToDelete })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('partner-row-' + partnerToDelete);
                    if (row) {
                        row.style.transition = 'opacity 0.3s';
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 300);
                    }
                    closeDeleteModal();
                    setTimeout(() => location.reload(), 500);
                } else {
                    alert(data.message || 'Error al eliminar');
                    btn.disabled = false;
                    btn.textContent = 'Eliminar';
                }
            })
            .catch(err => {
                alert('Error de conexión');
                btn.disabled = false;
                btn.textContent = 'Eliminar';
            });
        }
        
        // =====================================================
        // CAMBIAR CONTRASEÑA
        // =====================================================
        let passwordUserId = null;
        let passwordUserType = null;
        
        function openPasswordModal(id, name, type) {
            passwordUserId = id;
            passwordUserType = type;
            document.getElementById('passwordUserName').textContent = name;
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            document.getElementById('passwordModal').classList.add('show');
            document.getElementById('newPassword').focus();
        }
        
        function closePasswordModal() {
            document.getElementById('passwordModal').classList.remove('show');
            passwordUserId = null;
            passwordUserType = null;
        }
        
        function savePassword() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            // Validaciones
            if (!newPassword) {
                alert('Ingresa una contraseña');
                return;
            }
            
            if (newPassword.length < 6) {
                alert('La contraseña debe tener al menos 6 caracteres');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                alert('Las contraseñas no coinciden');
                return;
            }
            
            const btn = document.getElementById('savePasswordBtn');
            btn.disabled = true;
            btn.textContent = 'Guardando...';
            
            fetch('../api/admin/change-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: passwordUserId,
                    user_type: passwordUserType,
                    new_password: newPassword
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Contraseña actualizada correctamente');
                    closePasswordModal();
                } else {
                    alert(data.message || 'Error al cambiar contraseña');
                }
                btn.disabled = false;
                btn.textContent = 'Guardar';
            })
            .catch(err => {
                alert('Error de conexión');
                btn.disabled = false;
                btn.textContent = 'Guardar';
            });
        }
        
        // =====================================================
        // EVENTOS GENERALES
        // =====================================================
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
                closePasswordModal();
            }
        });
        
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
        
        document.getElementById('passwordModal').addEventListener('click', function(e) {
            if (e.target === this) closePasswordModal();
        });
        
        // Enter para guardar contraseña
        document.getElementById('confirmPassword').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') savePassword();
        });
    </script>
</body>
</html>
