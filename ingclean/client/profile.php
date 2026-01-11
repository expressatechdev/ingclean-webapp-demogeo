<?php
/**
 * INGClean - Perfil del Cliente
 */
require_once '../includes/init.php';

auth()->requireLogin(['client']);

$user = auth()->getCurrentUser();
$db = Database::getInstance();

$error = '';
$success = '';

// Procesar actualización
if (isPost()) {
    validateCsrf();
    
    $name = post('name');
    $phone = post('phone');
    $address = post('address');
    
    if (empty($name)) {
        $error = 'El nombre es requerido';
    } elseif (empty($phone)) {
        $error = 'El teléfono es requerido';
    } else {
        try {
            $db->update(
                'clients',
                [
                    'name' => $name,
                    'phone' => $phone,
                    'address' => $address
                ],
                'id = :id',
                ['id' => $user['id']]
            );
            
            $success = 'Perfil actualizado correctamente';
            
            // Refrescar datos del usuario
            $user = $db->fetchOne("SELECT * FROM clients WHERE id = :id", ['id' => $user['id']]);
            
        } catch (Exception $e) {
            $error = 'Error al actualizar el perfil';
        }
    }
}

// Estadísticas del usuario
$stats = $db->fetchOne(
    "SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
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
            padding-bottom: 80px;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            padding: 30px 20px;
            color: white;
            text-align: center;
        }
        
        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
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
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            color: white;
            font-size: 0.85rem;
            cursor: pointer;
            text-decoration: none;
        }
        
        .avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 15px;
            border: 4px solid rgba(255,255,255,0.5);
        }
        
        .user-name {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .user-email {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .user-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
        }
        
        .user-stat {
            text-align: center;
        }
        
        .user-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .user-stat-label {
            font-size: 0.75rem;
            opacity: 0.9;
        }
        
        /* Main */
        .main-content {
            padding: 20px;
            margin-top: -20px;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 1rem;
            color: #1e3a5f;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #00b4d8;
        }
        
        .form-group input:disabled {
            background: #f8fafc;
            color: #94a3b8;
        }
        
        .btn-save {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 119, 182, 0.3);
        }
        
        /* Menu Options */
        .menu-list {
            list-style: none;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        
        .menu-item:last-child {
            border-bottom: none;
        }
        
        .menu-item-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .menu-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .menu-icon.blue { background: #dbeafe; }
        .menu-icon.green { background: #dcfce7; }
        .menu-icon.red { background: #fee2e2; }
        
        .menu-item-text h4 {
            font-size: 0.95rem;
            color: #1e3a5f;
            font-weight: 500;
        }
        
        .menu-item-text p {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .menu-arrow {
            color: #94a3b8;
        }
        
        /* Member Since */
        .member-since {
            text-align: center;
            color: #94a3b8;
            font-size: 0.8rem;
            margin-top: 10px;
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
            <a href="../logout.php" class="logout-btn">Cerrar Sesión</a>
        </div>
        
        <div class="avatar">
            <?= strtoupper(substr($user['name'], 0, 1)) ?>
        </div>
        <div class="user-name"><?= e($user['name']) ?></div>
        <div class="user-email"><?= e($user['email']) ?></div>
        
        <div class="user-stats">
            <div class="user-stat">
                <div class="user-stat-value"><?= $stats['total_orders'] ?></div>
                <div class="user-stat-label">Pedidos</div>
            </div>
            <div class="user-stat">
                <div class="user-stat-value"><?= $stats['completed'] ?></div>
                <div class="user-stat-label">Completados</div>
            </div>
        </div>
    </header>
    
    <!-- Main -->
    <main class="main-content">
        <!-- Edit Profile -->
        <div class="card">
            <h3 class="card-title">✏️ Editar Información</h3>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <?= csrfField() ?>
                
                <div class="form-group">
                    <label>Nombre Completo</label>
                    <input type="text" name="name" value="<?= e($user['name']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" value="<?= e($user['email']) ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="tel" name="phone" value="<?= e($user['phone']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="address" value="<?= e($user['address']) ?>" placeholder="Tu dirección predeterminada">
                </div>
                
                <button type="submit" class="btn-save">Guardar Cambios</button>
            </form>
        </div>
        
        <!-- Menu Options -->
        <div class="card">
            <ul class="menu-list">
                <a href="history.php" class="menu-item">
                    <div class="menu-item-left">
                        <div class="menu-icon blue">📋</div>
                        <div class="menu-item-text">
                            <h4>Historial de Pedidos</h4>
                            <p>Ver todos tus servicios</p>
                        </div>
                    </div>
                    <span class="menu-arrow">→</span>
                </a>
                
                <div class="menu-item">
                    <div class="menu-item-left">
                        <div class="menu-icon green">🔔</div>
                        <div class="menu-item-text">
                            <h4>Notificaciones</h4>
                            <p>Configurar alertas</p>
                        </div>
                    </div>
                    <span class="menu-arrow">→</span>
                </div>
                
                <a href="../logout.php" class="menu-item">
                    <div class="menu-item-left">
                        <div class="menu-icon red">🚪</div>
                        <div class="menu-item-text">
                            <h4>Cerrar Sesión</h4>
                            <p>Salir de tu cuenta</p>
                        </div>
                    </div>
                    <span class="menu-arrow">→</span>
                </a>
            </ul>
        </div>
        
        <p class="member-since">
            Miembro desde <?= formatDate($user['created_at'], 'd M Y') ?>
        </p>
    </main>
    
    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item">
            <span class="nav-icon">🏠</span>
            Inicio
        </a>
        <a href="history.php" class="nav-item">
            <span class="nav-icon">📋</span>
            Historial
        </a>
        <a href="profile.php" class="nav-item active">
            <span class="nav-icon">👤</span>
            Perfil
        </a>
    </nav>
</body>
</html>
