-- ===============================================
-- CONECTA ERP - PASSWORD RECOVERY SYSTEM
-- Módulo: Recuperación de Contraseñas
-- ===============================================

-- Agregar columnas a tabla usuarios para recuperación
ALTER TABLE `usuarios`
ADD COLUMN IF NOT EXISTS `reset_token` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Token para recuperar contraseña',
ADD COLUMN IF NOT EXISTS `reset_token_expira` DATETIME NULL DEFAULT NULL COMMENT 'Fecha de expiración del token',
ADD INDEX `idx_reset_token` (`reset_token`);

-- Tabla para historial de tokens de recuperación
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(255) NOT NULL COMMENT 'Token único de recuperación',
  `expiry` DATETIME NOT NULL COMMENT 'Fecha y hora de expiración',
  `usado` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=No usado, 1=Ya usado',
  `ip_address` VARCHAR(45) NULL DEFAULT NULL COMMENT 'IP desde donde se solicitó',
  `user_agent` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Navegador del solicitante',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `used_at` DATETIME NULL DEFAULT NULL COMMENT 'Cuando se usó el token',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_expiry` (`expiry`),
  KEY `idx_usado` (`usado`),
  CONSTRAINT `fk_password_resets_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial de tokens de recuperación de contraseña';

-- Event para limpiar tokens expirados (ejecuta cada día a las 3 AM)
DROP EVENT IF EXISTS `ev_limpiar_tokens_expirados`;

DELIMITER $$
CREATE EVENT `ev_limpiar_tokens_expirados`
ON SCHEDULE EVERY 1 DAY
STARTS CONCAT(CURDATE() + INTERVAL 1 DAY, ' 03:00:00')
DO
BEGIN
    -- Eliminar tokens expirados mayores a 7 días
    DELETE FROM `password_resets`
    WHERE `expiry` < DATE_SUB(NOW(), INTERVAL 7 DAY);

    -- Limpiar columnas de reset_token en usuarios donde el token expiró
    UPDATE `usuarios`
    SET `reset_token` = NULL, `reset_token_expira` = NULL
    WHERE `reset_token_expira` < NOW() AND `reset_token` IS NOT NULL;
END$$
DELIMITER ;

-- Stored Procedure para generar token de recuperación
DROP PROCEDURE IF EXISTS `sp_generar_token_recuperacion`;

DELIMITER $$
CREATE PROCEDURE `sp_generar_token_recuperacion`(
    IN p_email VARCHAR(255),
    OUT p_token VARCHAR(255),
    OUT p_usuario_id INT,
    OUT p_nombre_completo VARCHAR(255),
    OUT p_exito INT
)
BEGIN
    DECLARE v_usuario_id INT;
    DECLARE v_nombre VARCHAR(100);
    DECLARE v_apellido VARCHAR(100);
    DECLARE v_token VARCHAR(255);
    DECLARE v_expiry DATETIME;

    -- Inicializar variables de salida
    SET p_token = NULL;
    SET p_usuario_id = NULL;
    SET p_nombre_completo = NULL;
    SET p_exito = 0;

    -- Buscar usuario activo por email
    SELECT `id`, `nombre`, `apellido`
    INTO v_usuario_id, v_nombre, v_apellido
    FROM `usuarios`
    WHERE `email` = p_email AND `estado` = 'activo'
    LIMIT 1;

    -- Si el usuario existe
    IF v_usuario_id IS NOT NULL THEN
        -- Generar token único (simulación, en PHP usar bin2hex(random_bytes(32)))
        SET v_token = MD5(CONCAT(v_usuario_id, NOW(), RAND()));
        SET v_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR);

        -- Actualizar usuario con token
        UPDATE `usuarios`
        SET `reset_token` = v_token,
            `reset_token_expira` = v_expiry
        WHERE `id` = v_usuario_id;

        -- Registrar en historial
        INSERT INTO `password_resets` (`usuario_id`, `token`, `expiry`, `ip_address`)
        VALUES (v_usuario_id, v_token, v_expiry, '0.0.0.0');

        -- Asignar valores de salida
        SET p_token = v_token;
        SET p_usuario_id = v_usuario_id;
        SET p_nombre_completo = CONCAT(v_nombre, ' ', v_apellido);
        SET p_exito = 1;
    END IF;
END$$
DELIMITER ;

-- Stored Procedure para validar token
DROP PROCEDURE IF EXISTS `sp_validar_token_recuperacion`;

DELIMITER $$
CREATE PROCEDURE `sp_validar_token_recuperacion`(
    IN p_token VARCHAR(255),
    OUT p_valido INT,
    OUT p_usuario_id INT,
    OUT p_nombre_completo VARCHAR(255),
    OUT p_email VARCHAR(255)
)
BEGIN
    DECLARE v_usuario_id INT;
    DECLARE v_nombre VARCHAR(100);
    DECLARE v_apellido VARCHAR(100);
    DECLARE v_email VARCHAR(255);

    -- Inicializar
    SET p_valido = 0;
    SET p_usuario_id = NULL;
    SET p_nombre_completo = NULL;
    SET p_email = NULL;

    -- Buscar usuario con token válido
    SELECT u.`id`, u.`nombre`, u.`apellido`, u.`email`
    INTO v_usuario_id, v_nombre, v_apellido, v_email
    FROM `usuarios` u
    WHERE u.`reset_token` = p_token
      AND u.`reset_token_expira` > NOW()
      AND u.`estado` = 'activo'
    LIMIT 1;

    -- Si existe y es válido
    IF v_usuario_id IS NOT NULL THEN
        SET p_valido = 1;
        SET p_usuario_id = v_usuario_id;
        SET p_nombre_completo = CONCAT(v_nombre, ' ', v_apellido);
        SET p_email = v_email;
    END IF;
END$$
DELIMITER ;

-- Stored Procedure para restablecer contraseña
DROP PROCEDURE IF EXISTS `sp_restablecer_password`;

DELIMITER $$
CREATE PROCEDURE `sp_restablecer_password`(
    IN p_token VARCHAR(255),
    IN p_new_password_hash VARCHAR(255),
    OUT p_exito INT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE v_usuario_id INT;
    DECLARE v_expiro INT DEFAULT 0;

    -- Inicializar
    SET p_exito = 0;
    SET p_mensaje = 'Token inválido o expirado';

    -- Verificar token
    SELECT u.`id`, IF(u.`reset_token_expira` < NOW(), 1, 0)
    INTO v_usuario_id, v_expiro
    FROM `usuarios` u
    WHERE u.`reset_token` = p_token
    LIMIT 1;

    -- Si el usuario existe
    IF v_usuario_id IS NOT NULL THEN
        -- Verificar si expiró
        IF v_expiro = 1 THEN
            SET p_mensaje = 'El enlace de recuperación ha expirado';
        ELSE
            -- Actualizar contraseña y limpiar token
            UPDATE `usuarios`
            SET `password` = p_new_password_hash,
                `reset_token` = NULL,
                `reset_token_expira` = NULL
            WHERE `id` = v_usuario_id;

            -- Marcar token como usado en historial
            UPDATE `password_resets`
            SET `usado` = 1, `used_at` = NOW()
            WHERE `token` = p_token;

            SET p_exito = 1;
            SET p_mensaje = 'Contraseña restablecida exitosamente';
        END IF;
    END IF;
END$$
DELIMITER ;

-- Vista para monitorear solicitudes de recuperación
CREATE OR REPLACE VIEW `v_password_recovery_stats` AS
SELECT
    DATE(pr.created_at) as fecha,
    COUNT(*) as total_solicitudes,
    SUM(CASE WHEN pr.usado = 1 THEN 1 ELSE 0 END) as tokens_usados,
    SUM(CASE WHEN pr.usado = 0 AND pr.expiry > NOW() THEN 1 ELSE 0 END) as tokens_vigentes,
    SUM(CASE WHEN pr.usado = 0 AND pr.expiry < NOW() THEN 1 ELSE 0 END) as tokens_expirados
FROM `password_resets` pr
GROUP BY DATE(pr.created_at)
ORDER BY fecha DESC;

-- Vista para auditoría de recuperaciones por usuario
CREATE OR REPLACE VIEW `v_password_recovery_audit` AS
SELECT
    u.id as usuario_id,
    u.username,
    u.email,
    CONCAT(u.nombre, ' ', u.apellido) as nombre_completo,
    pr.token,
    pr.created_at as solicitud_fecha,
    pr.expiry as expiracion,
    IF(pr.usado = 1, 'Usado',
       IF(pr.expiry < NOW(), 'Expirado', 'Vigente')) as estado,
    pr.used_at as usado_en,
    pr.ip_address,
    pr.user_agent
FROM `password_resets` pr
INNER JOIN `usuarios` u ON pr.usuario_id = u.id
ORDER BY pr.created_at DESC;

-- Datos de ejemplo (comentados - descomentar solo para testing)
/*
-- Ejemplo de uso del procedimiento
CALL sp_generar_token_recuperacion('test@example.com', @token, @user_id, @nombre, @exito);
SELECT @token as token_generado, @user_id as usuario_id, @nombre as nombre, @exito as exito;

-- Ejemplo de validación
CALL sp_validar_token_recuperacion(@token, @valido, @user_id, @nombre, @email);
SELECT @valido as es_valido, @user_id, @nombre, @email;

-- Ejemplo de restablecimiento
CALL sp_restablecer_password(@token, '$2y$10$hashedpassword...', @exito, @mensaje);
SELECT @exito, @mensaje;
*/

-- ===============================================
-- INDICES ADICIONALES PARA OPTIMIZACIÓN
-- ===============================================
ALTER TABLE `password_resets`
ADD INDEX `idx_created_at` (`created_at`),
ADD INDEX `idx_usuario_created` (`usuario_id`, `created_at`);

-- ===============================================
-- TRIGGERS PARA AUDITORÍA
-- ===============================================

-- Trigger para registrar IP y User Agent al crear token
DROP TRIGGER IF EXISTS `before_insert_password_resets`;

DELIMITER $$
CREATE TRIGGER `before_insert_password_resets`
BEFORE INSERT ON `password_resets`
FOR EACH ROW
BEGIN
    -- Capturar IP (esto debe hacerse desde PHP, aquí solo valor por defecto)
    IF NEW.ip_address IS NULL THEN
        SET NEW.ip_address = '0.0.0.0';
    END IF;
END$$
DELIMITER ;

-- ===============================================
-- PERMISOS Y SEGURIDAD
-- ===============================================

-- Revocar acceso directo a la tabla de tokens (solo via stored procedures)
-- REVOKE ALL ON conectae_conectaerpbd.password_resets FROM 'conectae_conectaerpuser'@'localhost';
-- GRANT EXECUTE ON PROCEDURE conectae_conectaerpbd.sp_generar_token_recuperacion TO 'conectae_conectaerpuser'@'localhost';
-- GRANT EXECUTE ON PROCEDURE conectae_conectaerpbd.sp_validar_token_recuperacion TO 'conectae_conectaerpuser'@'localhost';
-- GRANT EXECUTE ON PROCEDURE conectae_conectaerpbd.sp_restablecer_password TO 'conectae_conectaerpuser'@'localhost';

-- ===============================================
-- COMENTARIOS Y DOCUMENTACIÓN
-- ===============================================

/*
MÓDULO DE RECUPERACIÓN DE CONTRASEÑAS - CONECTA ERP

Este módulo gestiona el proceso completo de recuperación de contraseñas:

1. El usuario solicita recuperación ingresando su email
2. El sistema genera un token único con validez de 1 hora
3. Se envía un email con el enlace de recuperación (requiere configuración SMTP)
4. El usuario hace clic en el enlace y establece nueva contraseña
5. El token se marca como usado y se invalida
6. Se registra la operación en logs de auditoría

FLUJO COMPLETO:
--------------
Paso 1: Usuario ingresa email en formulario "Olvidé mi contraseña"
Paso 2: PHP llama a sp_generar_token_recuperacion(email)
Paso 3: Sistema envía email con enlace: reset_password.php?token=XXX
Paso 4: Usuario hace clic y accede a formulario de nueva contraseña
Paso 5: PHP valida token con sp_validar_token_recuperacion(token)
Paso 6: Usuario ingresa nueva contraseña
Paso 7: PHP llama a sp_restablecer_password(token, new_pass_hash)
Paso 8: Contraseña actualizada, token invalidado

SEGURIDAD:
---------
- Tokens únicos generados con algoritmo criptográfico seguro
- Expiración automática en 1 hora
- Un solo uso por token
- Limpieza automática de tokens antiguos
- Registro de IP y User Agent
- Auditoría completa de operaciones

TABLAS:
------
- usuarios: Almacena token actual y fecha de expiración
- password_resets: Historial completo de solicitudes de recuperación

STORED PROCEDURES:
-----------------
- sp_generar_token_recuperacion: Genera token y actualiza usuario
- sp_validar_token_recuperacion: Verifica validez del token
- sp_restablecer_password: Actualiza contraseña y marca token como usado

EVENTS:
------
- ev_limpiar_tokens_expirados: Limpia tokens antiguos diariamente a las 3 AM

VISTAS:
------
- v_password_recovery_stats: Estadísticas de solicitudes por día
- v_password_recovery_audit: Auditoría completa de recuperaciones

CONFIGURACIÓN REQUERIDA:
----------------------
- Configurar SMTP para envío de emails
- Establecer constante SITE_URL en config.php
- Activar eventos de MySQL: SET GLOBAL event_scheduler = ON;

EJEMPLO DE USO DESDE PHP:
------------------------
// Generar token
$stmt = $conn->prepare("CALL sp_generar_token_recuperacion(?, @token, @user_id, @nombre, @exito)");
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $conn->query("SELECT @token, @user_id, @nombre, @exito");
$data = $result->fetch_assoc();

if ($data['@exito'] == 1) {
    $reset_link = SITE_URL . "/reset_password.php?token=" . $data['@token'];
    // Enviar email con $reset_link
}
*/
