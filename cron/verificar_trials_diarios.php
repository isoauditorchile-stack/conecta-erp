<?php
/**
 * CONECTA ERP - Script CRON para Verificar Trials Diarios
 * Ejecutar diariamente a las 9:00 AM
 *
 * Crontab: 0 9 * * * /usr/bin/php /home/user/conecta-erp/cron/verificar_trials_diarios.php
 */

require_once __DIR__ . '/../includes/config.php';

echo "═══════════════════════════════════════════════════════════\n";
echo "  CONECTA ERP - Verificación Diaria de Trials\n";
echo "  Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$db = Database::getInstance();

// Obtener usuarios en trial
$users_trial = $db->fetchAll("
    SELECT u.*, c.company_name,
           DATEDIFF(u.trial_ends_at, NOW()) as dias_restantes,
           s.id as suscripcion_id,
           s.notificacion_dia_13_enviada,
           s.notificacion_dia_7_enviada,
           s.notificacion_dia_1_enviada
    FROM users u
    LEFT JOIN companies c ON u.company_id = c.id
    LEFT JOIN suscripciones s ON u.id = s.user_id AND s.es_trial = 1 AND s.estado = 'trial'
    WHERE u.status = 'trial'
      AND u.trial_ends_at IS NOT NULL
      AND u.trial_ends_at > NOW()
      AND u.email != 'auditorexchile@gmail.com'
    ORDER BY u.trial_ends_at ASC
");

echo "Usuarios en trial encontrados: " . count($users_trial) . "\n\n";

$notificaciones_enviadas = 0;
$trials_vencidos = 0;

foreach ($users_trial as $user) {
    $dias = (int)$user['dias_restantes'];
    $user_id = $user['id'];
    $suscripcion_id = $user['suscripcion_id'];
    $email = $user['email'];
    $nombre = $user['firstname'] . ' ' . $user['lastname'];

    echo "Usuario: {$nombre} ({$email}) - Días restantes: {$dias}\n";

    // NOTIFICACIÓN DÍA 13 (queda 1 día para llegar al día 13)
    if ($dias == 1 && !$user['notificacion_dia_13_enviada']) {
        $mensaje = "¡Hola {$nombre}!\n\n" .
                   "Quedan solo 13 días para que finalice tu período de prueba gratuita de CONECTA ERP.\n\n" .
                   "Para continuar disfrutando de nuestro sistema, por favor selecciona un plan y realiza el pago:\n" .
                   "https://conectaerp.com/planes\n\n" .
                   "Si necesitas ayuda, contáctanos a: auditorexchile@gmail.com\n\n" .
                   "Saludos,\nEquipo CONECTA ERP";

        // Enviar email
        if (enviarEmailNotificacion($email, 'Trial por vencer - 13 días restantes', $mensaje)) {
            // Registrar notificación
            $db->insert(
                "INSERT INTO notificaciones_trial (user_id, suscripcion_id, tipo, dias_restantes, mensaje, enviado, fecha_envio)
                 VALUES (?, ?, 'dia_13', 13, ?, 1, NOW())",
                [$user_id, $suscripcion_id, $mensaje]
            );

            // Actualizar flag
            if ($suscripcion_id) {
                $db->update(
                    "UPDATE suscripciones SET notificacion_dia_13_enviada = 1 WHERE id = ?",
                    [$suscripcion_id]
                );
            }

            echo "  ✓ Notificación día 13 enviada\n";
            $notificaciones_enviadas++;
        }
    }

    // NOTIFICACIÓN DÍA 7
    if ($dias == 7 && !$user['notificacion_dia_7_enviada']) {
        $mensaje = "¡Hola {$nombre}!\n\n" .
                   "Quedan 7 días para que finalice tu período de prueba de CONECTA ERP.\n\n" .
                   "No pierdas acceso a tu información. Selecciona un plan ahora:\n" .
                   "https://conectaerp.com/planes\n\n" .
                   "Métodos de pago:\n" .
                   "• PayPal\n" .
                   "• Transferencia Bancaria\n\n" .
                   "Contacto: auditorexchile@gmail.com\n\n" .
                   "Saludos,\nEquipo CONECTA ERP";

        if (enviarEmailNotificacion($email, 'Trial por vencer - 7 días restantes', $mensaje)) {
            $db->insert(
                "INSERT INTO notificaciones_trial (user_id, suscripcion_id, tipo, dias_restantes, mensaje, enviado, fecha_envio)
                 VALUES (?, ?, 'dia_7', 7, ?, 1, NOW())",
                [$user_id, $suscripcion_id, $mensaje]
            );

            if ($suscripcion_id) {
                $db->update(
                    "UPDATE suscripciones SET notificacion_dia_7_enviada = 1 WHERE id = ?",
                    [$suscripcion_id]
                );
            }

            echo "  ✓ Notificación día 7 enviada\n";
            $notificaciones_enviadas++;
        }
    }

    // NOTIFICACIÓN DÍA 1 (ÚLTIMO DÍA)
    if ($dias == 1 && !$user['notificacion_dia_1_enviada']) {
        $mensaje = "¡URGENTE! Hola {$nombre},\n\n" .
                   "Este es tu ÚLTIMO DÍA de acceso a CONECTA ERP.\n\n" .
                   "Si no realizas el pago hoy, tu cuenta será desactivada mañana y perderás acceso a toda tu información.\n\n" .
                   "Activa tu cuenta AHORA:\n" .
                   "https://conectaerp.com/planes\n\n" .
                   "Contacto urgente: auditorexchile@gmail.com\n" .
                   "WhatsApp: +56 9 XXXX XXXX\n\n" .
                   "Saludos,\nEquipo CONECTA ERP";

        if (enviarEmailNotificacion($email, '🚨 ÚLTIMO DÍA de Trial - Acción Requerida', $mensaje)) {
            $db->insert(
                "INSERT INTO notificaciones_trial (user_id, suscripcion_id, tipo, dias_restantes, mensaje, enviado, fecha_envio)
                 VALUES (?, ?, 'dia_1', 1, ?, 1, NOW())",
                [$user_id, $suscripcion_id, $mensaje]
            );

            if ($suscripcion_id) {
                $db->update(
                    "UPDATE suscripciones SET notificacion_dia_1_enviada = 1 WHERE id = ?",
                    [$suscripcion_id]
                );
            }

            echo "  ✓ Notificación día 1 (ÚLTIMO DÍA) enviada\n";
            $notificaciones_enviadas++;
        }
    }
}

echo "\n";

// Vencer trials expirados
$trials_expirados = $db->fetchAll("
    SELECT u.*, c.company_name
    FROM users u
    LEFT JOIN companies c ON u.company_id = c.id
    WHERE u.status = 'trial'
      AND u.trial_ends_at IS NOT NULL
      AND u.trial_ends_at <= NOW()
      AND u.email != 'auditorexchile@gmail.com'
");

echo "Trials expirados para desactivar: " . count($trials_expirados) . "\n\n";

foreach ($trials_expirados as $user) {
    try {
        // Actualizar estado del usuario
        $db->update(
            "UPDATE users SET status = 'expired' WHERE id = ?",
            [$user['id']]
        );

        // Actualizar suscripción
        $db->update(
            "UPDATE suscripciones SET estado = 'vencida' WHERE user_id = ? AND es_trial = 1",
            [$user['id']]
        );

        // Enviar notificación de vencimiento
        $mensaje = "Hola {$user['firstname']},\n\n" .
                   "Tu período de prueba de CONECTA ERP ha finalizado.\n\n" .
                   "Tu cuenta ha sido desactivada. Para reactivarla y continuar usando el sistema, por favor selecciona un plan:\n" .
                   "https://conectaerp.com/planes\n\n" .
                   "Contacto: auditorexchile@gmail.com\n\n" .
                   "Saludos,\nEquipo CONECTA ERP";

        enviarEmailNotificacion($user['email'], 'Tu trial de CONECTA ERP ha finalizado', $mensaje);

        echo "  ✓ Usuario {$user['email']} desactivado (trial vencido)\n";
        $trials_vencidos++;

        // Log actividad
        logActivity($user['id'], 'trial_expired', 'Trial expirado - Cuenta desactivada');
    } catch (Exception $e) {
        echo "  ✗ Error al desactivar {$user['email']}: " . $e->getMessage() . "\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "  RESUMEN\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  Notificaciones enviadas: $notificaciones_enviadas\n";
echo "  Trials vencidos: $trials_vencidos\n";
echo "═══════════════════════════════════════════════════════════\n\n";

/**
 * Enviar email de notificación
 */
function enviarEmailNotificacion($to, $subject, $mensaje) {
    // TODO: Implementar con PHPMailer o servicio SMTP real
    // Por ahora, usar mail() de PHP

    $headers = "From: CONECTA ERP <noreply@conectaerp.com>\r\n";
    $headers .= "Reply-To: auditorexchile@gmail.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // En producción, usar PHPMailer
    // Por ahora, simular envío exitoso
    error_log("EMAIL ENVIADO a $to: $subject");

    return mail($to, $subject, $mensaje, $headers);
}
