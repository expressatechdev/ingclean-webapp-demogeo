<?php
/**
 * INGClean Admin - Configuración
 */
require_once '../includes/init.php';

auth()->requireLogin(['admin']);

$db = Database::getInstance();

// Obtener servicios
$services = $db->fetchAll("SELECT * FROM services ORDER BY sort_order ASC");

// Guardar cambios
if (isPost()) {
    validateCsrf();
    
    $action = post('action');
    
    if ($action === 'update_service') {
        $serviceId = post('service_id');
        $price = post('price');
        $description = post('description');
        $isActive = post('is_active') ? 1 : 0;
        
        $db->update(
            'services',
            [
                'price' => $price,
                'description' => $description,
                'is_active' => $isActive
            ],
            'id = :id',
            ['id' => $serviceId]
        );
        
        setFlash('success', 'Servicio actualizado correctamente');
        redirect('/admin/settings.php');
    }
}

$flash = getFlash('success');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Admin <?= APP_NAME ?></title>
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
        
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #16a34a; }
        
        .settings-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; }
        
        .card { background: white; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
        .card-header { padding: 20px; border-bottom: 1px solid #f1f5f9; }
        .card-header h3 { font-size: 1rem; color: #1e3a5f; }
        .card-body { padding: 20px; }
        
        .service-item { padding: 15px; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 15px; }
        .service-item:last-child { margin-bottom: 0; }
        .service-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .service-name { font-weight: 600; color: #1e3a5f; }
        .service-toggle { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #64748b; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.85rem; color: #64748b; margin-bottom: 5px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 0.9rem; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #0077b6; }
        .form-group textarea { resize: vertical; min-height: 60px; }
        
        .btn-save { background: #0077b6; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 0.85rem; cursor: pointer; font-family: inherit; }
        .btn-save:hover { background: #005f8a; }
        
        .info-box { background: #f0f9ff; border-radius: 10px; padding: 15px; }
        .info-box h4 { font-size: 0.9rem; color: #0077b6; margin-bottom: 10px; }
        .info-box p { font-size: 0.85rem; color: #64748b; margin-bottom: 8px; }
        .info-box code { background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; }
        
        .credentials-list { list-style: none; }
        .credentials-list li { padding: 10px 0; border-bottom: 1px solid #e2e8f0; font-size: 0.85rem; }
        .credentials-list li:last-child { border-bottom: none; }
        .credentials-list .label { color: #64748b; }
        .credentials-list .value { color: #1e3a5f; font-weight: 500; word-break: break-all; }
        
        @media (max-width: 1024px) { .settings-grid { grid-template-columns: 1fr; } }
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
            <li><a href="clients.php"><span>👤</span> Clientes</a></li>
            <li><a href="settings.php" class="active"><span>⚙️</span> Configuración</a></li>
        </ul>
    </aside>
    
    <main class="main-content">
        <div class="page-header">
            <h1>⚙️ Configuración</h1>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-success"><?= e($flash) ?></div>
        <?php endif; ?>
        
        <div class="settings-grid">
            <!-- Servicios -->
            <div class="card">
                <div class="card-header">
                    <h3>🧹 Servicios y Precios</h3>
                </div>
                <div class="card-body">
                    <?php foreach ($services as $service): ?>
                        <form method="POST" class="service-item">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update_service">
                            <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                            
                            <div class="service-header">
                                <span class="service-name"><?= e($service['name']) ?></span>
                                <label class="service-toggle">
                                    <input type="checkbox" name="is_active" <?= $service['is_active'] ? 'checked' : '' ?>>
                                    Activo
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label>Precio ($)</label>
                                <input type="number" name="price" value="<?= $service['price'] ?>" step="0.01" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label>Descripción</label>
                                <textarea name="description"><?= e($service['description']) ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn-save">Guardar Cambios</button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Información del Sistema -->
            <div class="card">
                <div class="card-header">
                    <h3>ℹ️ Información del Sistema</h3>
                </div>
                <div class="card-body">
                    <div class="info-box" style="margin-bottom: 20px;">
                        <h4>📊 Comisiones</h4>
                        <p>Plataforma (INGClean): <strong><?= PLATFORM_FEE_PERCENT ?>%</strong></p>
                        <p>Partners: <strong><?= PARTNER_PERCENT ?>%</strong></p>
                    </div>
                    
                    <div class="info-box" style="margin-bottom: 20px;">
                        <h4>⚙️ Configuración de Stripe</h4>
                        <p>Modo: <code><?= STRIPE_MODE ?></code></p>
                        <p>Moneda: <code><?= STRIPE_CURRENCY ?></code></p>
                    </div>
                    
                    <div class="info-box">
                        <h4>🔑 API Keys (editar en config.php)</h4>
                        <ul class="credentials-list">
                            <li>
                                <div class="label">Stripe Public Key:</div>
                                <div class="value"><?= substr(getStripePublicKey(), 0, 20) ?>...</div>
                            </li>
                            <li>
                                <div class="label">Google Maps API:</div>
                                <div class="value"><?= GOOGLE_MAPS_API_KEY ? substr(GOOGLE_MAPS_API_KEY, 0, 15) . '...' : 'No configurada' ?></div>
                            </li>
                            <li>
                                <div class="label">OneSignal App ID:</div>
                                <div class="value"><?= ONESIGNAL_APP_ID ?: 'No configurada' ?></div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
