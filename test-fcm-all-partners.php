<?php
/**
 * INGClean - Test FCM Push a TODOS los Partners
 */
define('INGCLEAN_APP', true);
require_once 'includes/init.php';
require_once 'includes/FCMService.php';

$db = Database::getInstance();
$fcm = new FCMService();
$result = null;
$tokens = [];

// Obtener todos los tokens de partners
$tokens = $db->fetchAll(
    "SELECT pt.*, p.name as partner_name 
     FROM push_tokens pt 
     LEFT JOIN partners p ON pt.user_id = p.id 
     WHERE pt.user_type = 'partner' 
     ORDER BY pt.updated_at DESC"
);

// Enviar notificación a todos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_all'])) {
    $title = $_POST['title'] ?? '🔔 Notificación de Prueba';
    $body = $_POST['body'] ?? 'Este es un mensaje de prueba para todos los partners.';
    
    $results = [];
    $success_count = 0;
    $error_count = 0;
    
    foreach ($tokens as $token) {
        $sendResult = $fcm->sendToUser(
            $token['user_id'],
            'partner',
            $title,
            $body,
            ['type' => 'test', 'timestamp' => time()]
        );
        
        if ($sendResult) {
            $success_count++;
            $results[] = [
                'user_id' => $token['user_id'],
                'name' => $token['partner_name'] ?? 'Partner ' . $token['user_id'],
                'status' => '✅ Enviado'
            ];
        } else {
            $error_count++;
            $results[] = [
                'user_id' => $token['user_id'],
                'name' => $token['partner_name'] ?? 'Partner ' . $token['user_id'],
                'status' => '❌ Error'
            ];
        }
    }
    
    $result = [
        'total' => count($tokens),
        'success' => $success_count,
        'errors' => $error_count,
        'details' => $results
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test FCM - Todos los Partners</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #0a1628 100%);
            min-height: 100vh;
            padding: 20px;
            color: white;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            color: #1e3a5f;
        }
        .card h2 {
            margin-bottom: 20px;
            color: #0077b6;
            font-size: 1.2rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #64748b;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .btn {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(34, 197, 94, 0.4);
        }
        .result-box {
            background: #f0fdf4;
            border: 2px solid #22c55e;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .result-box.error {
            background: #fef2f2;
            border-color: #ef4444;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .stat {
            flex: 1;
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 10px;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #64748b;
        }
        .stat.success .stat-value { color: #22c55e; }
        .stat.error .stat-value { color: #ef4444; }
        .stat.total .stat-value { color: #0077b6; }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: white;
            text-decoration: none;
            opacity: 0.8;
        }
        .back-link:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="test-fcm.php" class="back-link">← Volver a prueba individual</a>
        <h1>🔔 Enviar a TODOS los Partners</h1>
        
        <?php if ($result): ?>
        <div class="card">
            <h2>📊 Resultado del Envío</h2>
            <div class="stats">
                <div class="stat total">
                    <div class="stat-value"><?= $result['total'] ?></div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat success">
                    <div class="stat-value"><?= $result['success'] ?></div>
                    <div class="stat-label">Exitosos</div>
                </div>
                <div class="stat error">
                    <div class="stat-value"><?= $result['errors'] ?></div>
                    <div class="stat-label">Errores</div>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Partner</th>
                        <th>User ID</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['details'] as $detail): ?>
                    <tr>
                        <td><?= htmlspecialchars($detail['name']) ?></td>
                        <td><?= $detail['user_id'] ?></td>
                        <td><?= $detail['status'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>📱 Partners con Token FCM (<?= count($tokens) ?>)</h2>
            
            <?php if (empty($tokens)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p>No hay partners con tokens registrados</p>
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Partner</th>
                        <th>User ID</th>
                        <th>Actualizado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tokens as $token): ?>
                    <tr>
                        <td><?= $token['id'] ?></td>
                        <td><?= htmlspecialchars($token['partner_name'] ?? 'Partner ' . $token['user_id']) ?></td>
                        <td><?= $token['user_id'] ?></td>
                        <td><?= $token['updated_at'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <form method="POST">
                <h2 style="margin-top: 30px;">✉️ Enviar Notificación</h2>
                
                <div class="form-group">
                    <label>Título:</label>
                    <input type="text" name="title" value="🔔 Notificación de Prueba" required>
                </div>
                
                <div class="form-group">
                    <label>Mensaje:</label>
                    <textarea name="body" required>¡Hola Partners! Este es un mensaje de prueba enviado a todos.</textarea>
                </div>
                
                <button type="submit" name="send_all" value="1" class="btn">
                    📤 Enviar a <?= count($tokens) ?> Partner<?= count($tokens) > 1 ? 's' : '' ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>📋 Ver Logs</h2>
            <p>Archivo: <code>logs/<?= date('Y-m-d') ?>.log</code></p>
        </div>
    </div>
</body>
</html>