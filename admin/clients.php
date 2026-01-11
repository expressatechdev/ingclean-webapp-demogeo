<?php
/**
 * INGClean Admin - Gestión de Clientes
 * ACTUALIZADO: Botón para cambiar contraseña
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
        
        /* Botones de acción */
        .actions { display: flex; gap: 8px; }
        
        .btn-password {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            font-size: 0.8rem;
            cursor: pointer;
            font-family: inherit;
            background: #dbeafe;
            color: #1d4ed8;
            transition: all 0.3s;
        }
        .btn-password:hover { background: #bfdbfe; }
        
        .btn-delete {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            font-size: 0.8rem;
            cursor: pointer;
            font-family: inherit;
            background: #fee2e2;
            color: #dc2626;
            transition: all 0.3s;
        }
        .btn-delete:hover { background: #fecaca; }
        
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.show { display: flex; }
        .modal {
            background: white;
            border-radius: 16px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        .modal h3 { color: #1e3a5f; margin-bottom: 15px; }
        .modal h3.delete { color: #dc2626; }
        .modal p { color: #64748b; margin-bottom: 20px; font-size: 0.95rem; }
        .modal-actions { display: flex; gap: 10px; justify-content: center; }
        .modal-btn {
            padding: 10px 24px;
            border-radius: 8px;
            border: none;
            font-size: 0.9rem;
            cursor: pointer;
            font-family: inherit;
        }
        .modal-btn-cancel { background: #e2e8f0; color: #64748b; }
        .modal-btn-delete { background: #dc2626; color: white; }
        .modal-btn-save { background: #0077b6; color: white; }
        .modal-btn-save:hover { background: #005a8c; }
        
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
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr id="client-row-<?= $client['id'] ?>">
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
                                <td>
                                    <div class="actions">
                                        <button class="btn-password" onclick="openPasswordModal(<?= $client['id'] ?>, '<?= e(addslashes($client['name'])) ?>', 'client')">
                                            🔑 Contraseña
                                        </button>
                                        <button class="btn-delete" onclick="confirmDelete(<?= $client['id'] ?>, '<?= e(addslashes($client['name'])) ?>', <?= $client['total_orders'] ?>)">
                                            🗑️ Eliminar
                                        </button>
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
            <h3 class="delete">🗑️ Eliminar Cliente</h3>
            <p>¿Estás seguro de eliminar a <strong id="clientName"></strong>?</p>
            <p id="orderWarning" style="color: #f59e0b; font-size: 0.85rem;"></p>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">Cancelar</button>
                <button class="modal-btn modal-btn-delete" id="deleteBtn" onclick="deleteClient()">Eliminar</button>
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
        // ELIMINAR CLIENTE
        // =====================================================
        let clientToDelete = null;
        
        function confirmDelete(id, name, orderCount) {
            clientToDelete = id;
            document.getElementById('clientName').textContent = name;
            
            if (orderCount > 0) {
                document.getElementById('orderWarning').textContent = 
                    `⚠️ Este cliente tiene ${orderCount} orden(es) que también serán eliminadas.`;
            } else {
                document.getElementById('orderWarning').textContent = '';
            }
            
            document.getElementById('deleteModal').classList.add('show');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            clientToDelete = null;
        }
        
        function deleteClient() {
            if (!clientToDelete) return;
            
            const btn = document.getElementById('deleteBtn');
            btn.disabled = true;
            btn.textContent = 'Eliminando...';
            
            fetch('../api/admin/delete-client.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ client_id: clientToDelete })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('client-row-' + clientToDelete);
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
