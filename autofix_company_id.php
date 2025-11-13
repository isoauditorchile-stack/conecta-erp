<?php
/**
 * AUTO-FIX: Agregar company_id a tabla users
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'conectae_conectaerpbd';
$user = 'conectae_conectaerpuser';
$pass = 'pt125824caraud';

echo "<h2>🔧 AUTO-FIX: Agregando company_id a tabla users</h2>";
echo "<pre>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Conectado a la base de datos\n\n";

    // Verificar si la columna ya existe
    echo "🔍 Verificando si company_id ya existe...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'company_id'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "✅ La columna company_id YA EXISTE\n";
        echo "No se necesitan cambios.\n\n";
    } else {
        echo "⚠️  La columna company_id NO EXISTE\n\n";

        // Agregar columna
        echo "🔧 Agregando columna company_id...\n";
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `company_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `username`");
        echo "✅ Columna company_id agregada\n\n";

        // Agregar foreign key (ignorar error si ya existe)
        try {
            echo "🔧 Agregando foreign key...\n";
            $pdo->exec("ALTER TABLE `users` ADD CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL");
            echo "✅ Foreign key agregada\n\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠️  Foreign key ya existe (ignorando)\n\n";
            } else {
                echo "⚠️  Error al agregar foreign key: " . $e->getMessage() . "\n";
                echo "   (Esto no es crítico, la columna funciona sin FK)\n\n";
            }
        }
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ FIX COMPLETADO\n\n";
    echo "La columna company_id está disponible en tabla users.\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<a href='/index.php' style='padding:10px 20px; background:#4CAF50; color:white; text-decoration:none; border-radius:5px;'>Ir a Index</a>";
?>
