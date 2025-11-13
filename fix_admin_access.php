<?php
/**
 * FIX: Configurar auditorexchile@gmail.com como super admin con acceso completo
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'conectae_conectaerpbd';
$user = 'conectae_conectaerpuser';
$pass = 'pt125824caraud';

echo "<h2>🔧 FIX: Configurar Super Admin</h2>";
echo "<pre>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Conectado a la base de datos\n\n";

    // 1. Verificar si auditorexchile@gmail.com existe
    echo "1️⃣ Verificando usuario auditorexchile@gmail.com...\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['auditorexchile@gmail.com']);
    $admin_user = $stmt->fetch();

    if (!$admin_user) {
        echo "   ⚠️  El usuario auditorexchile@gmail.com NO EXISTE\n";
        echo "   📝 Debes registrarte primero en: http://tu-dominio/index.php\n\n";
    } else {
        echo "   ✅ Usuario encontrado (ID: {$admin_user['id']})\n\n";

        // 2. Configurar como admin
        echo "2️⃣ Configurando permisos de super admin...\n";
        $updates = [];

        if ($admin_user['is_admin'] != 1) {
            $updates[] = 'is_admin = 1';
            echo "   🔧 Activando is_admin\n";
        }

        if ($admin_user['status'] != 'active') {
            $updates[] = "status = 'active'";
            echo "   🔧 Cambiando status a 'active'\n";
        }

        if (!empty($updates)) {
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$admin_user['id']]);
            echo "   ✅ Permisos actualizados\n\n";
        } else {
            echo "   ✅ Ya está configurado correctamente\n\n";
        }

        // 3. Verificar módulos en la base de datos
        echo "3️⃣ Verificando módulos del sistema...\n";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM modules WHERE is_active = 1");
        $modules_count = $stmt->fetch()['total'];

        if ($modules_count == 0) {
            echo "   ❌ NO HAY MÓDULOS en la base de datos\n";
            echo "   🔧 Los módulos deben instalarse. Verifica que existan en la tabla 'modules'\n\n";
        } else {
            echo "   ✅ $modules_count módulos disponibles en el sistema\n\n";

            // Mostrar módulos
            $stmt = $pdo->query("SELECT code, name, is_active FROM modules ORDER BY sort_order");
            $modules = $stmt->fetchAll();
            foreach ($modules as $mod) {
                $status = $mod['is_active'] ? '✅' : '❌';
                echo "      $status {$mod['code']} - {$mod['name']}\n";
            }
            echo "\n";
        }

        // 4. Verificar estado final
        echo "4️⃣ Estado final del usuario:\n";
        $stmt = $pdo->prepare("SELECT id, email, is_admin, status, trial_ends_at FROM users WHERE email = ?");
        $stmt->execute(['auditorexchile@gmail.com']);
        $final_user = $stmt->fetch();

        echo "   • Email: {$final_user['email']}\n";
        echo "   • is_admin: " . ($final_user['is_admin'] ? 'SÍ ✅' : 'NO ❌') . "\n";
        echo "   • status: {$final_user['status']} " . ($final_user['status'] == 'active' ? '✅' : '⚠️') . "\n";
        echo "   • trial_ends_at: " . ($final_user['trial_ends_at'] ?: 'N/A (no necesita trial)') . "\n\n";

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        if ($final_user['is_admin'] == 1 && $final_user['status'] == 'active') {
            echo "✅ SUPER ADMIN CONFIGURADO CORRECTAMENTE\n\n";
            echo "Accede al panel de super admin:\n";
            echo "<a href='/admin/panel_super_admin.php' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>Panel Super Admin</a>\n\n";
            echo "NOTA: Si ya tenías sesión abierta, cierra sesión y vuelve a iniciar sesión:\n";
            echo "<a href='/logout.php' style='padding:10px 20px;background:#ef4444;color:white;text-decoration:none;border-radius:5px;'>Cerrar Sesión</a>\n";
        } else {
            echo "⚠️  AÚN HAY PROBLEMAS\n";
            echo "Verifica manualmente en la base de datos\n";
        }
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
