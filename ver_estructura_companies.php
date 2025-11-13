<?php
$host = 'localhost';
$dbname = 'conectae_conectaerpbd';
$user = 'conectae_conectaerpuser';
$pass = 'pt125824caraud';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h2>📋 Estructura de la tabla 'companies'</h2>";
    echo "<pre>";

    $stmt = $pdo->query("DESCRIBE companies");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    printf("%-20s %-30s %-10s %-10s %-20s\n", "Campo", "Tipo", "Null", "Key", "Default");
    echo str_repeat("=", 100) . "\n";

    foreach ($columns as $col) {
        printf("%-20s %-30s %-10s %-10s %-20s\n",
            $col['Field'],
            $col['Type'],
            $col['Null'],
            $col['Key'],
            $col['Default'] ?? 'NULL'
        );
    }

    echo "</pre>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
