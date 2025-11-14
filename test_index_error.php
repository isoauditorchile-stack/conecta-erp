<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. Cargando config.php...<br>";
require_once __DIR__ . '/includes/config.php';
echo "✓ config.php cargado<br>";

echo "2. Cargando i18n.php...<br>";
require_once __DIR__ . '/includes/i18n.php';
echo "✓ i18n.php cargado<br>";

echo "3. Cargando seed_nueva_empresa.php...<br>";
require_once __DIR__ . '/includes/seed_nueva_empresa.php';
echo "✓ seed_nueva_empresa.php cargado<br>";

echo "4. Inicializando sesión...<br>";
initSession();
echo "✓ sesión inicializada<br>";

echo "<br><strong>TODO OK - No hay error 500</strong>";
