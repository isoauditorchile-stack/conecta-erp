<?php
// TEST DE CONEXIÓN A BASE DE DATOS

// Configuración
$host = 'localhost';
$user = 'conectae_conectaerpuser';
$pass = 'pt125824caraud';
$db = 'conectae_conectaerpbd';

echo "<h1>Test de Conexión - CONECTA ERP</h1>";
echo "<hr>";

// Test 1: Conectar a MySQL
echo "<h2>1. Probando conexión a MySQL...</h2>";
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    echo "<p style='color:red;'>❌ ERROR: No se puede conectar a MySQL<br>";
    echo "Error: " . $conn->connect_error . "</p>";
    exit;
} else {
    echo "<p style='color:green;'>✅ Conexión a MySQL exitosa</p>";
}

// Test 2: Verificar base de datos
echo "<h2>2. Verificando base de datos '$db'...</h2>";
if (!$conn->select_db($db)) {
    echo "<p style='color:red;'>❌ ERROR: La base de datos '$db' NO EXISTE<br>";
    echo "Error: " . $conn->error . "</p>";
    echo "<p><strong>Solución:</strong> Debes crear la base de datos primero o ejecutar el instalador.</p>";
    exit;
} else {
    echo "<p style='color:green;'>✅ Base de datos '$db' existe y es accesible</p>";
}

// Test 3: Verificar charset
echo "<h2>3. Configurando charset utf8mb4...</h2>";
if (!$conn->set_charset('utf8mb4')) {
    echo "<p style='color:orange;'>⚠️ ADVERTENCIA: No se pudo establecer charset utf8mb4<br>";
    echo "Error: " . $conn->error . "</p>";
} else {
    echo "<p style='color:green;'>✅ Charset configurado correctamente</p>";
}

// Test 4: Verificar tablas necesarias
echo "<h2>4. Verificando tablas necesarias...</h2>";
$tablas_requeridas = ['paises', 'idiomas', 'planes', 'empresas', 'usuarios', 'roles', 'suscripciones'];
$tablas_existentes = [];
$tablas_faltantes = [];

$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_array()) {
        $tablas_existentes[] = $row[0];
    }

    foreach ($tablas_requeridas as $tabla) {
        if (in_array($tabla, $tablas_existentes)) {
            echo "<p style='color:green;'>✅ Tabla '$tabla' existe</p>";
        } else {
            echo "<p style='color:red;'>❌ Tabla '$tabla' NO EXISTE</p>";
            $tablas_faltantes[] = $tabla;
        }
    }
} else {
    echo "<p style='color:red;'>❌ ERROR al verificar tablas: " . $conn->error . "</p>";
}

// Test 5: Verificar datos básicos
if (empty($tablas_faltantes)) {
    echo "<h2>5. Verificando datos básicos...</h2>";

    // Verificar países
    $result = $conn->query("SELECT COUNT(*) as total FROM paises");
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row['total'] > 0) {
            echo "<p style='color:green;'>✅ Tabla 'paises' tiene {$row['total']} registros</p>";
        } else {
            echo "<p style='color:red;'>❌ Tabla 'paises' está VACÍA</p>";
        }
    }

    // Verificar idiomas
    $result = $conn->query("SELECT COUNT(*) as total FROM idiomas");
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row['total'] > 0) {
            echo "<p style='color:green;'>✅ Tabla 'idiomas' tiene {$row['total']} registros</p>";
        } else {
            echo "<p style='color:red;'>❌ Tabla 'idiomas' está VACÍA</p>";
        }
    }

    // Verificar super admin
    $result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE es_super_admin = 1");
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row['total'] > 0) {
            echo "<p style='color:green;'>✅ Super Admin creado</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ No hay Super Admin en el sistema</p>";
        }
    }
}

// Resumen
echo "<hr>";
echo "<h2>RESUMEN</h2>";

if (!empty($tablas_faltantes)) {
    echo "<div style='background:#ffebee; padding:20px; border-left:5px solid #f44336;'>";
    echo "<h3 style='color:#d32f2f;'>❌ SISTEMA NO INSTALADO</h3>";
    echo "<p><strong>Faltan las siguientes tablas:</strong></p>";
    echo "<ul>";
    foreach ($tablas_faltantes as $tabla) {
        echo "<li>$tabla</li>";
    }
    echo "</ul>";
    echo "<p><strong>Solución:</strong></p>";
    echo "<ol>";
    echo "<li>Ve a <a href='install.php'>install.php</a> para instalar el sistema</li>";
    echo "<li>O ejecuta manualmente los archivos SQL en phpMyAdmin</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background:#e8f5e9; padding:20px; border-left:5px solid #4caf50;'>";
    echo "<h3 style='color:#388e3c;'>✅ SISTEMA INSTALADO CORRECTAMENTE</h3>";
    echo "<p>La base de datos y todas las tablas necesarias están presentes.</p>";
    echo "<p><strong>Puedes:</strong></p>";
    echo "<ul>";
    echo "<li><a href='index.php'>Ir al Index</a> para registrarte o hacer login</li>";
    echo "<li><a href='login.php'>Ir al Login directo</a></li>";
    echo "<li>Login Super Admin: <strong>auditorexchile@gmail.com</strong> / <strong>password</strong></li>";
    echo "</ul>";
    echo "</div>";
}

$conn->close();
?>
