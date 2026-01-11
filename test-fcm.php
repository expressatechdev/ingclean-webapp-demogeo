<?php
/**
 * Test FCM - Prueba de notificaciones push
 * ELIMINAR DESPUÉS DE PROBAR
 */
define('INGCLEAN_APP', true);
require_once 'includes/init.php';
require_once 'includes/FCMService.php';

echo "<h1>Test FCM Push Notifications</h1>";

// Verificar tokens en BD
$db = Database::getInstance();
$tokens = $db->fetchAll("SELECT * FROM push_tokens ORDER BY id DESC");

echo "<h2>Tokens registrados:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>User</th><th>Type</th><th>Token (primeros 30 chars)</th><th>Platform</th></tr>";
foreach ($tokens as $t) {
    echo "<tr>";
    echo "<td>{$t['id']}</td>";
    echo "<td>{$t['user_id']}</td>";
    echo "<td>{$t['user_type']}</td>";
    echo "<td>" . substr($t['token'], 0, 30) . "...</td>";
    echo "<td>{$t['platform']}</td>";
    echo "</tr>";
}
echo "</table>";

// Enviar notificación de prueba
if (isset($_GET['send'])) {
    $userId = $_GET['user_id'] ?? 1;
    $userType = $_GET['user_type'] ?? 'partner';
    
    echo "<h2>Enviando notificación de prueba...</h2>";
    
    $fcm = new FCMService();
    $result = $fcm->sendToUser(
        $userId,
        $userType,
        '🧪 Prueba FCM',
        '¡Las notificaciones push funcionan!',
        ['type' => 'test', 'timestamp' => time()]
    );
    
    echo "<p><strong>Resultado:</strong> " . ($result ? '✅ Enviado' : '❌ Error') . "</p>";
    echo "<p>Revisa el archivo de logs para más detalles.</p>";
}

// Formulario de prueba
echo "<h2>Enviar notificación de prueba:</h2>";
echo "<form method='GET'>";
echo "<input type='hidden' name='send' value='1'>";
echo "<label>User ID: <input type='number' name='user_id' value='1'></label><br><br>";
echo "<label>User Type: 
    <select name='user_type'>
        <option value='partner'>Partner</option>
        <option value='client'>Client</option>
    </select>
</label><br><br>";
echo "<button type='submit' style='padding:10px 20px; font-size:16px;'>🔔 Enviar Push de Prueba</button>";
echo "</form>";

echo "<hr>";
echo "<p><small>Archivo de logs: logs/" . date('Y-m-d') . ".log</small></p>";
?>