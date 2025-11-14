<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 TEST DEL PANEL DE ADMINISTRACIÓN</h2>";
echo "<pre>";

echo "1. Cargando config.php...\n";
try {
    require_once __DIR__ . '/includes/config.php';
    echo "✓ config.php cargado correctamente\n\n";
} catch (Exception $e) {
    echo "❌ ERROR en config.php: " . $e->getMessage() . "\n";
    die();
}

echo "2. Iniciando sesión...\n";
try {
    initSession();
    echo "✓ Sesión iniciada\n\n";
} catch (Exception $e) {
    echo "❌ ERROR al iniciar sesión: " . $e->getMessage() . "\n";
    die();
}

echo "3. Simulando sesión de super admin...\n";
$_SESSION['user_id'] = 1;
$_SESSION['email'] = 'auditorexchile@gmail.com';
$_SESSION['is_admin'] = 1;
$_SESSION['username'] = 'auditorexchile@gmail.com';
echo "✓ Sesión simulada\n\n";

echo "4. Verificando acceso de super admin...\n";
if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'auditorexchile@gmail.com') {
    echo "❌ ERROR: No es super admin\n";
    die();
}
echo "✓ Usuario es super admin\n\n";

echo "5. Conectando a base de datos...\n";
try {
    $db = Database::getInstance();
    echo "✓ Conexión establecida\n\n";
} catch (Exception $e) {
    echo "❌ ERROR de conexión: " . $e->getMessage() . "\n";
    die();
}

echo "6. Verificando tabla 'planes'...\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'planes'");
    $result = $stmt->fetchAll();
    if (count($result) > 0) {
        echo "✓ Tabla 'planes' existe\n";

        // Verificar estructura
        $columns = $db->query("SHOW COLUMNS FROM planes")->fetchAll();
        echo "  Columnas encontradas:\n";
        foreach ($columns as $col) {
            echo "    - {$col['Field']} ({$col['Type']})\n";
        }

        // Contar registros
        $count = $db->query("SELECT COUNT(*) as c FROM planes")->fetch()['c'];
        echo "  Total de registros: $count\n\n";
    } else {
        echo "❌ Tabla 'planes' NO existe\n\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR al verificar planes: " . $e->getMessage() . "\n\n";
}

echo "7. Verificando tabla 'suscripciones'...\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'suscripciones'");
    $result = $stmt->fetchAll();
    if (count($result) > 0) {
        echo "✓ Tabla 'suscripciones' existe\n";
        $count = $db->query("SELECT COUNT(*) as c FROM suscripciones")->fetch()['c'];
        echo "  Total de registros: $count\n\n";
    } else {
        echo "❌ Tabla 'suscripciones' NO existe\n\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR al verificar suscripciones: " . $e->getMessage() . "\n\n";
}

echo "8. Verificando tabla 'pagos'...\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'pagos'");
    $result = $stmt->fetchAll();
    if (count($result) > 0) {
        echo "✓ Tabla 'pagos' existe\n";
        $count = $db->query("SELECT COUNT(*) as c FROM pagos")->fetch()['c'];
        echo "  Total de registros: $count\n\n";
    } else {
        echo "❌ Tabla 'pagos' NO existe\n\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR al verificar pagos: " . $e->getMessage() . "\n\n";
}

echo "9. Verificando tabla 'aprobaciones_usuario'...\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'aprobaciones_usuario'");
    $result = $stmt->fetchAll();
    if (count($result) > 0) {
        echo "✓ Tabla 'aprobaciones_usuario' existe\n";
        $count = $db->query("SELECT COUNT(*) as c FROM aprobaciones_usuario")->fetch()['c'];
        echo "  Total de registros: $count\n\n";
    } else {
        echo "❌ Tabla 'aprobaciones_usuario' NO existe\n\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR al verificar aprobaciones_usuario: " . $e->getMessage() . "\n\n";
}

echo "10. Intentando cargar estadísticas como en panel_super_admin.php...\n";
try {
    $stats = [];

    // Usuarios pendientes de aprobación
    echo "  - Consultando usuarios pendientes...\n";
    $stats['usuarios_pendientes'] = $db->fetchOne(
        "SELECT COUNT(*) as total FROM users WHERE status = 'pending_approval'"
    )['total'] ?? 0;
    echo "    ✓ Usuarios pendientes: {$stats['usuarios_pendientes']}\n";

    // Usuarios en trial
    echo "  - Consultando usuarios en trial...\n";
    $stats['usuarios_trial'] = $db->fetchOne(
        "SELECT COUNT(*) as total FROM users WHERE status = 'trial'"
    )['total'] ?? 0;
    echo "    ✓ Usuarios en trial: {$stats['usuarios_trial']}\n";

    // Usuarios activos
    echo "  - Consultando usuarios activos...\n";
    $stats['usuarios_activos'] = $db->fetchOne(
        "SELECT COUNT(*) as total FROM users WHERE status = 'active' AND email != 'auditorexchile@gmail.com'"
    )['total'] ?? 0;
    echo "    ✓ Usuarios activos: {$stats['usuarios_activos']}\n";

    // Pagos pendientes
    echo "  - Consultando pagos pendientes...\n";
    $stats['pagos_pendientes'] = $db->fetchOne(
        "SELECT COUNT(*) as total FROM pagos WHERE estado = 'pendiente'"
    )['total'] ?? 0;
    echo "    ✓ Pagos pendientes: {$stats['pagos_pendientes']}\n\n";

    echo "✓ Todas las estadísticas se cargaron correctamente\n\n";

} catch (Exception $e) {
    echo "❌ ERROR al cargar estadísticas: " . $e->getMessage() . "\n\n";
}

echo "11. Intentando obtener lista de usuarios pendientes...\n";
try {
    $usuarios_pendientes = $db->fetchAll("
        SELECT u.*, c.company_name
        FROM users u
        LEFT JOIN companies c ON u.company_id = c.id
        WHERE u.status = 'pending_approval'
        ORDER BY u.created_at DESC
        LIMIT 10
    ");
    echo "✓ Consulta exitosa. Encontrados: " . count($usuarios_pendientes) . " usuarios\n\n";
} catch (Exception $e) {
    echo "❌ ERROR al obtener usuarios pendientes: " . $e->getMessage() . "\n\n";
}

echo "12. Intentando obtener lista de pagos pendientes...\n";
try {
    $pagos_pendientes = $db->fetchAll("
        SELECT p.*, s.*, u.email, c.company_name, pl.nombre_es as plan_name
        FROM pagos p
        INNER JOIN suscripciones s ON p.suscripcion_id = s.id
        INNER JOIN users u ON s.user_id = u.id
        INNER JOIN companies c ON s.company_id = c.id
        INNER JOIN planes pl ON s.plan_id = pl.id
        WHERE p.estado = 'pendiente'
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    echo "✓ Consulta exitosa. Encontrados: " . count($pagos_pendientes) . " pagos\n\n";
} catch (Exception $e) {
    echo "❌ ERROR al obtener pagos pendientes: " . $e->getMessage() . "\n\n";
    echo "  DETALLE: Este error puede indicar que las tablas tienen estructura diferente\n";
    echo "  SOLUCIÓN: Ejecutar autofix_sistema_completo.php\n\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "RESUMEN DEL TEST\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Si todos los pasos anteriores muestran ✓, el panel debería funcionar.\n";
echo "Si hay errores ❌, revisa el mensaje de error específico.\n\n";

echo "Acciones recomendadas:\n";
echo "1. Si hay errores de tablas faltantes: Ejecutar autofix_sistema_completo.php\n";
echo "2. Si hay errores de columnas: Las tablas tienen estructura antigua, ejecutar autofix\n";
echo "3. Si todo está OK pero panel da error 500: Revisar logs de PHP del servidor\n\n";

echo "</pre>";

echo "<a href='/autofix_sistema_completo.php' style='padding:10px 20px; background:#f59e0b; color:white; text-decoration:none; border-radius:5px; margin-right:10px;'>Ejecutar AutoFix</a>";
echo "<a href='/admin/panel_super_admin.php' style='padding:10px 20px; background:#667eea; color:white; text-decoration:none; border-radius:5px; margin-right:10px;'>Ir al Panel Admin</a>";
echo "<a href='/index.php' style='padding:10px 20px; background:#4CAF50; color:white; text-decoration:none; border-radius:5px;'>Ir al Index</a>";
?>
