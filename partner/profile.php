<?php
/**
 * INGClean - Perfil del Partner
 */
require_once '../includes/init.php';

auth()->requireLogin(['partner']);

$user = auth()->getCurrentUser();
$db = Database::getInstance();

// Obtener estadísticas del partner (consulta simplificada)
try {
    $completedOrders = $db->fetchOne(
        "SELECT COUNT(*) as total FROM orders WHERE partner_id = :partner_id AND status = 'completed'",
        ['partner_id' => $user['id']]
    );
    
    $totalServices = $completedOrders['total'] ?? 0;
    
    // Obtener ganancias totales
    $earningsResult = $db->fetchOne(
        "SELECT COALESCE(SUM(p.partner_amount), 0) as total 
         FROM payments p 
         JOIN orders o ON p.order_id = o.id 
         WHERE o.partner_id = :partner_id AND p.status = 'completed'",
        ['partner_id' => $user['id']]
    );
    
    $totalEarnings = $earningsResult['total'] ?? 0;
} catch (Exception $e) {
    $totalServices = 0;
    $totalEarnings = 0;
}

$error = '';
$success = '';

// Procesar actualización de perfil
if (isPost()) {
    validateCsrf();
    
    $action = post('action') ?? '';
    
    if ($action === 'update_profile') {
        $name = post('name') ?? '';
        $phone = post('phone') ?? '';
        
        if (empty($name) || empty($phone)) {
            $error = 'Nombre y teléfono son requeridos';
        } else {
            // Procesar foto si se subió una nueva
            $photoPath = $user['photo'] ?? '';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/partners/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = 'partner_' . $user['id'] . '_' . time() . '.' . $ext;
                $targetPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                    $photoPath = 'uploads/partners/' . $filename;
                }
            }
            
            $db->update(
                'partners',
                [
                    'name' => $name,
                    'phone' => $phone,
                    'photo' => $photoPath
                ],
                'id = :id',
                ['id' => $user['id']]
            );
            
            $success = 'Perfil actualizado correctamente';
            
            // Refrescar datos del usuario
            $user = $db->fetchOne("SELECT * FROM partners WHERE id = :id", ['id' => $user['id']]);
        }
    } elseif ($action === 'toggle_availability') {
        $newStatus = ($user['is_available'] ?? 0) ? 0 : 1;
        $db->update('partners', ['is_available' => $newStatus], 'id = :id', ['id' => $user['id']]);
        $user['is_available'] = $newStatus;
        $success = $newStatus ? '¡Ahora estás disponible!' : 'Te has marcado como no disponible';
    }
}

$userName = $user['name'] ?? 'Partner';
$userEmail = $user['email'] ?? '';
$userPhone = $user['phone'] ?? '';
$userPhoto = $user['photo'] ?? '';
$userStatus = $user['status'] ?? 'pending';
$isAvailable = $user['is_available'] ?? 0;
$createdAt = $user['created_at'] ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f9ff;
            min-height: 100vh;
            padding-bottom: 100px;
        }
        
        .header {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            padding: 20px;
            padding-top: 40px;
            color: white;
            text-align: center;
            border-radius: 0 0 30px 30px;
        }
        
        .header h1 {
            font-size: 1.3rem;
            margin-bottom: 20px;
        }
        
        .avatar-section {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: #16a34a;
            margin-bottom: 15px;
            border: 4px solid rgba(255,255,255,0.3);
            overflow: hidden;
        }
        
        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-name {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .user-email {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: 15px;
            background: rgba(255,255,255,0.2);
        }
        
        .stats-row {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        
        .stat-item { text-align: center; }
        .stat-value { font-size: 1.5rem; font-weight: 700; }
        .stat-label { font-size: 0.75rem; opacity: 0.9; }
        
        .container {
            padding: 20px;
            margin-top: -20px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .alert-error { background: #fee2e2; color: #dc2626; }
        .alert-success { background: #dcfce7; color: #16a34a; }
        
        .availability-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .availability-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .availability-info h3 {
            font-size: 1rem;
            color: #1e3a5f;
            margin-bottom: 3px;
        }
        
        .availability-info p {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .toggle-form { display: inline; }
        
        .toggle-btn {
            width: 60px;
            height: 32px;
            border-radius: 16px;
            border: none;
            cursor: pointer;
            position: relative;
            transition: all 0.3s;
        }
        
        .toggle-btn.active { background: #22c55e; }
        .toggle-btn.inactive { background: #cbd5e1; }
        
        .toggle-btn::after {
            content: '';
            position: absolute;
            width: 26px;
            height: 26px;
            background: white;
            border-radius: 50%;
            top: 3px;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .toggle-btn.active::after { left: 31px; }
        .toggle-btn.inactive::after { left: 3px; }
        
        .card {
            background: white;
            border-radius: 16px;
            padding: 25px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card-title {
            font-size: 1rem;
            color: #1e3a5f;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
        }
        
        .form-group input:disabled {
            background: #f1f5f9;
            color: #64748b;
        }
        
        .photo-upload {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .photo-preview {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #64748b;
            overflow: hidden;
        }
        
        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-upload-btn { flex: 1; }
        .photo-upload-btn input { display: none; }
        
        .photo-upload-btn label {
            display: block;
            padding: 12px;
            background: #f1f5f9;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            font-size: 0.85rem;
            color: #64748b;
            transition: all 0.3s;
        }
        
        .photo-upload-btn label:hover {
            border-color: #22c55e;
            background: #f0fdf4;
        }
        
        .btn-save {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        
        .menu-list { list-style: none; }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
            text-decoration: none;
            color: #1e3a5f;
            gap: 12px;
        }
        
        .menu-item:last-child { border-bottom: none; }
        
        .menu-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .menu-icon.green { background: #dcfce7; }
        .menu-icon.blue { background: #dbeafe; }
        .menu-icon.red { background: #fee2e2; }
        
        .menu-text { flex: 1; }
        .menu-text h4 { font-size: 0.95rem; font-weight: 500; }
        .menu-text p { font-size: 0.8rem; color: #64748b; }
        
        .menu-arrow { color: #cbd5e1; }
        
        .info-text {
            text-align: center;
            color: #94a3b8;
            font-size: 0.8rem;
            margin-top: 20px;
        }
        
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            display: flex;
            justify-content: space-around;
            padding: 12px 0;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
            z-index: 100;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #94a3b8;
            font-size: 0.7rem;
            gap: 4px;
        }
        
        .nav-item.active { color: #22c55e; }
        .nav-item span:first-child { font-size: 1.4rem; }
    </style>
</head>
<body>
    <header class="header">
        <h1>👤 Mi Perfil</h1>
        
        <div class="avatar-section">
            <div class="avatar">
                <?php if ($userPhoto): ?>
                    <img src="../<?= e($userPhoto) ?>" alt="">
                <?php else: ?>
                    <?= strtoupper(substr($userName, 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div class="user-name"><?= e($userName) ?></div>
            <div class="user-email"><?= e($userEmail) ?></div>
            
            <span class="status-badge">
                <?php if ($userStatus === 'approved'): ?>
                    ✅ Partner Verificado
                <?php elseif ($userStatus === 'pending'): ?>
                    ⏳ Pendiente de Aprobación
                <?php else: ?>
                    ❌ <?= ucfirst($userStatus) ?>
                <?php endif; ?>
            </span>
        </div>
        
        <div class="stats-row">
            <div class="stat-item">
                <div class="stat-value"><?= number_format($totalServices) ?></div>
                <div class="stat-label">Servicios</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">$<?= number_format($totalEarnings, 0) ?></div>
                <div class="stat-label">Ganancias</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">⭐ 5.0</div>
                <div class="stat-label">Calificación</div>
            </div>
        </div>
    </header>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= e($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= e($success) ?></div>
        <?php endif; ?>
        
        <!-- Availability Toggle -->
        <div class="availability-card">
            <div class="availability-header">
                <div class="availability-info">
                    <h3><?= $isAvailable ? '🟢 Disponible' : '🔴 No disponible' ?></h3>
                    <p><?= $isAvailable ? 'Puedes recibir nuevas órdenes' : 'No recibirás nuevas órdenes' ?></p>
                </div>
                <form method="POST" class="toggle-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="toggle_availability">
                    <button type="submit" class="toggle-btn <?= $isAvailable ? 'active' : 'inactive' ?>"></button>
                </form>
            </div>
        </div>
        
        <!-- Edit Profile -->
        <div class="card">
            <h3 class="card-title">✏️ Editar Información</h3>
            
            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label>Foto de Perfil</label>
                    <div class="photo-upload">
                        <div class="photo-preview" id="photoPreview">
                            <?php if ($userPhoto): ?>
                                <img src="../<?= e($userPhoto) ?>" alt="">
                            <?php else: ?>
                                📷
                            <?php endif; ?>
                        </div>
                        <div class="photo-upload-btn">
                            <input type="file" name="photo" id="photoInput" accept="image/*">
                            <label for="photoInput">📤 Cambiar foto</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Nombre Completo</label>
                    <input type="text" name="name" value="<?= e($userName) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" value="<?= e($userEmail) ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="tel" name="phone" value="<?= e($userPhone) ?>" required>
                </div>
                
                <button type="submit" class="btn-save">💾 Guardar Cambios</button>
            </form>
        </div>
        
        <!-- Menu -->
        <div class="card">
            <ul class="menu-list">
                <a href="earnings.php" class="menu-item">
                    <div class="menu-icon green">💰</div>
                    <div class="menu-text">
                        <h4>Mis Ganancias</h4>
                        <p>Ver historial de pagos</p>
                    </div>
                    <span class="menu-arrow">→</span>
                </a>
                
                <a href="index.php" class="menu-item">
                    <div class="menu-icon blue">📋</div>
                    <div class="menu-text">
                        <h4>Órdenes</h4>
                        <p>Ver órdenes disponibles</p>
                    </div>
                    <span class="menu-arrow">→</span>
                </a>
                
                <a href="../logout.php" class="menu-item">
                    <div class="menu-icon red">🚪</div>
                    <div class="menu-text">
                        <h4>Cerrar Sesión</h4>
                        <p>Salir de tu cuenta</p>
                    </div>
                    <span class="menu-arrow">→</span>
                </a>
            </ul>
        </div>
        
        <p class="info-text">
            Partner desde <?= date('d/m/Y', strtotime($createdAt)) ?>
        </p>
    </div>
    
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item">
            <span>🏠</span>
            <span>Inicio</span>
        </a>
        <a href="earnings.php" class="nav-item">
            <span>💰</span>
            <span>Ganancias</span>
        </a>
        <a href="profile.php" class="nav-item active">
            <span>👤</span>
            <span>Perfil</span>
        </a>
    </nav>
    
    <script>
        document.getElementById('photoInput').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').innerHTML = 
                        '<img src="' + e.target.result + '" alt="Preview">';
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html>
