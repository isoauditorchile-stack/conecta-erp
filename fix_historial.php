<?php
$host = 'localhost';
$dbname = 'conectae_conectaerpbd';
$user = 'conectae_conectaerpuser';
$pass = 'pt125824caraud';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "CREATE TABLE IF NOT EXISTS `historial_suscripciones` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `suscripcion_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `accion` VARCHAR(100) NOT NULL,
  `plan_anterior_id` INT(11) UNSIGNED NULL,
  `plan_nuevo_id` INT(11) UNSIGNED NULL,
  `estado_anterior` VARCHAR(50) NULL,
  `estado_nuevo` VARCHAR(50) NULL,
  `realizado_por` INT(11) UNSIGNED NULL,
  `es_automatico` TINYINT(1) DEFAULT 0,
  `detalles` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones`(`id`) ON DELETE CASCADE,
  INDEX `idx_suscripcion` (`suscripcion_id`),
  INDEX `idx_fecha` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "✅ Tabla historial_suscripciones creada<br>";
    echo "<a href='/index.php'>Ir a Index</a> | <a href='/logout.php'>Cerrar Sesión</a>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
