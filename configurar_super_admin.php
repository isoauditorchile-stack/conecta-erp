<?php
/**
 * SCRIPT: Configurar auditorexchile@gmail.com como Super Admin
 * Este script asegura que solo auditorexchile@gmail.com tenga acceso total al sistema
 */

define('ACCESO_PERMITIDO', true);
require_once __DIR__ . '/incluir/configuracion.php';
require_once RUTA_INCLUIR . '/base_datos.php';

echo "<h1>Configuración de Super Admin</h1>";
echo "<pre>";

try {
    $bd = BaseDatos::obtener_instancia()->obtener_conexion();

    echo "✅ Conectado a la base de datos\n\n";

    // 1. Verificar si el usuario existe
    echo "1️⃣ Verificando usuario " . SUPER_ADMIN_EMAIL . "...\n";
    $stmt = $bd->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([SUPER_ADMIN_EMAIL]);
    $admin_user = $stmt->fetch();

    if (!$admin_user) {
        echo "   ❌ ERROR: El usuario " . SUPER_ADMIN_EMAIL . " NO EXISTE en la base de datos\n";
        echo "   📝 Debes registrarte primero en el sistema\n";
        echo "   🌐 URL de registro: " . URL_APP . "/index.php\n\n";
        exit;
    }

    echo "   ✅ Usuario encontrado (ID: {$admin_user['id']})\n";
    echo "   📧 Email: {$admin_user['email']}\n";
    echo "   👤 Nombre: {$admin_user['firstname']} {$admin_user['lastname']}\n\n";

    // 2. Verificar estructura de tabla users
    echo "2️⃣ Verificando estructura de la tabla users...\n";
    $columns = $bd->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

    $has_is_admin = in_array('is_admin', $columns);
    $has_is_super_admin = in_array('is_super_admin', $columns);

    if (!$has_is_admin && !$has_is_super_admin) {
        echo "   ❌ ERROR: La tabla users no tiene columnas is_admin ni is_super_admin\n";
        echo "   🔧 Agregando columna is_admin...\n";
        $bd->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0");
        echo "   ✅ Columna is_admin agregada\n";
        $has_is_admin = true;
    }

    if (!$has_is_super_admin) {
        echo "   🔧 Agregando columna is_super_admin...\n";
        $bd->exec("ALTER TABLE users ADD COLUMN is_super_admin TINYINT(1) DEFAULT 0");
        echo "   ✅ Columna is_super_admin agregada\n";
        $has_is_super_admin = true;
    }

    echo "   ✅ Estructura de tabla verificada\n\n";

    // 3. Configurar como super admin
    echo "3️⃣ Configurando permisos de Super Admin...\n";

    $updates = [];
    $updates[] = "is_admin = 1";
    if ($has_is_super_admin) {
        $updates[] = "is_super_admin = 1";
    }
    $updates[] = "status = 'active'";

    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE email = ?";
    $stmt = $bd->prepare($sql);
    $stmt->execute([SUPER_ADMIN_EMAIL]);

    echo "   ✅ Permisos actualizados exitosamente\n";
    echo "   ✅ is_admin = 1\n";
    if ($has_is_super_admin) {
        echo "   ✅ is_super_admin = 1\n";
    }
    echo "   ✅ status = 'active'\n\n";

    // 4. Eliminar período de trial (opcional)
    echo "4️⃣ Eliminando restricciones de trial...\n";
    $bd->prepare("UPDATE users SET trial_ends_at = NULL WHERE email = ?")->execute([SUPER_ADMIN_EMAIL]);
    echo "   ✅ Restricciones de trial eliminadas\n\n";

    // 5. Verificar estado final
    echo "5️⃣ Verificando estado final...\n";
    $stmt = $bd->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([SUPER_ADMIN_EMAIL]);
    $final_user = $stmt->fetch();

    echo "   📧 Email: {$final_user['email']}\n";
    echo "   is_admin: " . ($final_user['is_admin'] ? '✅ SÍ' : '❌ NO') . "\n";
    if ($has_is_super_admin) {
        echo "   is_super_admin: " . ($final_user['is_super_admin'] ? '✅ SÍ' : '❌ NO') . "\n";
    }
    echo "   status: {$final_user['status']} " . ($final_user['status'] == 'active' ? '✅' : '⚠️') . "\n";
    echo "   trial_ends_at: " . ($final_user['trial_ends_at'] ?: 'N/A (sin restricciones)') . "\n\n";

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    if ($final_user['is_admin'] == 1 && $final_user['status'] == 'active') {
        echo "✅ ¡SUPER ADMIN CONFIGURADO CORRECTAMENTE!\n\n";
        echo "🔑 " . SUPER_ADMIN_EMAIL . " ahora tiene:\n";
        echo "   • Acceso total a todos los módulos ISO (30 normas)\n";
        echo "   • Panel de administración completo\n";
        echo "   • Sin restricciones de trial\n";
        echo "   • Sin límites de acceso\n\n";

        echo "🌐 Accesos:\n";
        echo "   Panel Usuario: " . URL_APP . "/usuario/inicio.php\n";
        if ($es_admin) {
            echo "   Panel Admin: " . URL_APP . "/administrador/inicio.php\n";
        }
        echo "\n";

        echo "⚠️  IMPORTANTE: Si ya tenías sesión abierta, cierra sesión y vuelve a iniciar sesión\n";
        echo "   para que los cambios surtan efecto.\n\n";

        echo "🔒 SEGURIDAD:\n";
        echo "   • Solo " . SUPER_ADMIN_EMAIL . " tiene acceso total\n";
        echo "   • Los demás usuarios tienen acceso limitado según su plan\n";
        echo "   • El acceso está controlado en usuario/inicio.php\n";
    } else {
        echo "⚠️  ATENCIÓN: Hay problemas con la configuración\n";
        echo "   Verifica manualmente en la base de datos\n";
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
