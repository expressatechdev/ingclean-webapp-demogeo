<?php
/**
 * INGClean - Setup Admin
 * Ejecutar una sola vez para crear/resetear el admin
 * ELIMINAR ESTE ARCHIVO DESPUÉS DE USAR
 */

require_once 'includes/init.php';

$db = Database::getInstance();

// Credenciales del admin
$adminEmail = 'admin@ingclean.com';
$adminPassword = 'Admin123!';
$adminName = 'Administrador';

// Generar hash del password
$hashedPassword = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 10]);

echo "<h2>INGClean - Setup Admin</h2>";
echo "<pre>";

// Verificar si existe
$existing = $db->fetchOne("SELECT id FROM admins WHERE email = :email", ['email' => $adminEmail]);

if ($existing) {
    // Actualizar password
    $db->update(
        'admins',
        ['password' => $hashedPassword],
        'email = :email',
        ['email' => $adminEmail]
    );
    echo "✅ Admin actualizado correctamente\n";
} else {
    // Crear nuevo admin
    $db->insert('admins', [
        'name' => $adminName,
        'email' => $adminEmail,
        'password' => $hashedPassword
    ]);
    echo "✅ Admin creado correctamente\n";
}

echo "\n";
echo "📧 Email: {$adminEmail}\n";
echo "🔑 Password: {$adminPassword}\n";
echo "\n";
echo "Hash generado: {$hashedPassword}\n";
echo "\n";
echo "⚠️  ELIMINA ESTE ARCHIVO (setup-admin.php) DESPUÉS DE USAR\n";
echo "</pre>";

echo "<p><a href='login.php'>Ir al Login</a></p>";
?>