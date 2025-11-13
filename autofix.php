<?php
/**
 * AUTO-FIX: Arregla la tabla companies automáticamente
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'conectae_conectaerpbd';
$user = 'conectae_conectaerpuser';
$pass = 'pt125824caraud';

echo "<h2>🔧 AUTO-FIX: Arreglando tabla companies</h2>";
echo "<pre>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Conectado a la base de datos\n\n";

    // Hacer owner_user_id nullable
    echo "🔧 Modificando columna owner_user_id...\n";
    $pdo->exec("ALTER TABLE `companies` MODIFY COLUMN `owner_user_id` INT(11) UNSIGNED NULL DEFAULT NULL");
    echo "✅ Columna owner_user_id ahora es NULLABLE\n\n";

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ FIX COMPLETADO\n\n";
    echo "Ahora puedes registrarte sin problemas.\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<a href='/index.php' style='padding:10px 20px; background:#4CAF50; color:white; text-decoration:none; border-radius:5px;'>Ir a Registrarse</a>";
?>