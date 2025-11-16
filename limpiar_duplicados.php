<?php
/**
 * SCRIPT DE LIMPIEZA - Eliminar usuarios duplicados
 * Este script elimina usuarios duplicados del super admin
 */

require_once 'includes/config.php';

echo "<h1>Script de Limpieza - Usuarios Duplicados</h1>";
echo "<hr>";

// Buscar usuarios con el email del super admin
$email_super_admin = SUPER_ADMIN_EMAIL;

$query = "SELECT id, nombre, apellido, email, username, es_super_admin, fecha_registro
          FROM usuarios
          WHERE email = ? OR username = ?
          ORDER BY id ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $email_super_admin, SUPER_ADMIN_USERNAME);
$stmt->execute();
$result = $stmt->get_result();

$usuarios_encontrados = [];
while ($row = $result->fetch_assoc()) {
    $usuarios_encontrados[] = $row;
}
$stmt->close();

if (count($usuarios_encontrados) === 0) {
    echo "<p style='color:green;'>✅ No hay usuarios duplicados con el email/username del super admin</p>";
    echo "<p><a href='index.php'>Volver al inicio</a></p>";
    exit;
}

echo "<h2>Usuarios encontrados con el email/username del super admin:</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
echo "<tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Email</th>
        <th>Username</th>
        <th>Es Super Admin</th>
        <th>Fecha Registro</th>
        <th>Acción</th>
      </tr>";

foreach ($usuarios_encontrados as $usuario) {
    $es_super = $usuario['es_super_admin'] == 1 ? 'SÍ' : 'NO';
    $color = $usuario['es_super_admin'] == 1 ? '#d4edda' : '#fff3cd';

    echo "<tr style='background: $color;'>";
    echo "<td>{$usuario['id']}</td>";
    echo "<td>" . htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) . "</td>";
    echo "<td>" . htmlspecialchars($usuario['email']) . "</td>";
    echo "<td>" . htmlspecialchars($usuario['username']) . "</td>";
    echo "<td><strong>$es_super</strong></td>";
    echo "<td>" . $usuario['fecha_registro'] . "</td>";

    if ($usuario['es_super_admin'] == 1) {
        echo "<td><strong style='color:green;'>✅ MANTENER (Super Admin Original)</strong></td>";
    } else {
        echo "<td><strong style='color:red;'>❌ ELIMINAR (Duplicado)</strong></td>";
    }

    echo "</tr>";
}

echo "</table>";

// Formulario de confirmación
if (count($usuarios_encontrados) > 1) {
    echo "<hr>";
    echo "<h2>Acción Requerida</h2>";
    echo "<p style='background:#fff3cd; padding:15px; border-left:5px solid #ffc107;'>";
    echo "<strong>⚠️ ADVERTENCIA:</strong> Se encontraron " . count($usuarios_encontrados) . " usuarios con el email/username del super admin.<br>";
    echo "Solo debe existir 1 usuario (el super admin original con es_super_admin = 1).<br>";
    echo "Los duplicados (es_super_admin = 0) serán eliminados.";
    echo "</p>";

    echo "<form method='POST' action='' onsubmit='return confirm(\"¿Estás seguro de eliminar los usuarios duplicados?\");'>";
    echo "<button type='submit' name='limpiar' style='background:#dc3545; color:white; padding:15px 30px; border:none; border-radius:5px; font-size:16px; cursor:pointer;'>";
    echo "🗑️ ELIMINAR USUARIOS DUPLICADOS";
    echo "</button>";
    echo "</form>";
} else {
    echo "<hr>";
    echo "<p style='color:green;'>✅ Solo hay 1 usuario con este email/username (correcto)</p>";
}

// Procesar eliminación
if (isset($_POST['limpiar'])) {
    echo "<hr>";
    echo "<h2>Procesando Limpieza...</h2>";

    $conn->begin_transaction();

    try {
        $eliminados = 0;
        $mantenidos = 0;

        foreach ($usuarios_encontrados as $usuario) {
            if ($usuario['es_super_admin'] == 0) {
                // Eliminar este usuario (es un duplicado)
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->bind_param("i", $usuario['id']);
                $stmt->execute();
                $stmt->close();

                echo "<p style='color:red;'>❌ Eliminado: ID {$usuario['id']} - " . htmlspecialchars($usuario['email']) . "</p>";
                $eliminados++;
            } else {
                // Mantener el super admin original
                echo "<p style='color:green;'>✅ Mantenido: ID {$usuario['id']} - SUPER ADMIN</p>";
                $mantenidos++;
            }
        }

        $conn->commit();

        echo "<hr>";
        echo "<div style='background:#d4edda; padding:20px; border-left:5px solid #28a745; margin:20px 0;'>";
        echo "<h3 style='color:#155724;'>✅ LIMPIEZA COMPLETADA</h3>";
        echo "<p><strong>Usuarios eliminados:</strong> $eliminados</p>";
        echo "<p><strong>Usuarios mantenidos:</strong> $mantenidos</p>";
        echo "</div>";

        echo "<div style='background:#d1ecf1; padding:20px; border-left:5px solid #0c5460; margin:20px 0;'>";
        echo "<h3>📋 Instrucciones para iniciar sesión:</h3>";
        echo "<ol>";
        echo "<li>Ve a <a href='login.php'>login.php</a></li>";
        echo "<li>Usa las credenciales del Super Admin:</li>";
        echo "<ul>";
        echo "<li><strong>Email:</strong> " . SUPER_ADMIN_EMAIL . "</li>";
        echo "<li><strong>Username:</strong> " . SUPER_ADMIN_USERNAME . "</li>";
        echo "<li><strong>Password:</strong> password</li>";
        echo "</ul>";
        echo "<li>Serás redirigido automáticamente al Dashboard de Administración</li>";
        echo "</ol>";
        echo "</div>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color:red;'>❌ ERROR: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<p><a href='index.php'>← Volver al inicio</a> | <a href='login.php'>Ir al Login</a> | <a href='test_conexion.php'>Test de Conexión</a></p>";

$conn->close();
?>
