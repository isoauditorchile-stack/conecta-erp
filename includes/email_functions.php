<?php
/**
 * SISTEMA DE EMAILS - CONECTA ERP
 * Funciones para envío de correos electrónicos con plantillas HTML
 *
 * Soporta:
 * - Plantillas HTML personalizadas
 * - Envío vía SMTP o mail() nativo
 * - Variables dinámicas en plantillas
 * - Logs de envío
 * - Cola de emails (opcional)
 */

/**
 * Configuración de email desde BD
 * @param mysqli $conn - Conexión a BD
 * @return array - Configuración de email
 */
function getEmailConfig($conn) {
    $config = [];

    $result = $conn->query("SELECT clave, valor FROM configuracion WHERE grupo = 'email'");
    while ($row = $result->fetch_assoc()) {
        $config[$row['clave']] = $row['valor'];
    }

    return $config;
}

/**
 * Enviar email usando PHPMailer o mail() nativo
 * @param mysqli $conn - Conexión a BD
 * @param string $to - Email destino
 * @param string $subject - Asunto
 * @param string $htmlBody - Cuerpo HTML
 * @param string $textBody - Cuerpo texto plano (opcional)
 * @param array $attachments - Archivos adjuntos (opcional)
 * @return bool - true si se envió correctamente
 */
function sendEmail($conn, $to, $subject, $htmlBody, $textBody = '', $attachments = []) {
    $config = getEmailConfig($conn);

    // Validar que el email esté habilitado
    if (!isset($config['smtp_habilitado']) || $config['smtp_habilitado'] != '1') {
        error_log("Email deshabilitado en configuración");
        return false;
    }

    // Usar PHPMailer si está disponible, sino mail() nativo
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return sendEmailSMTP($config, $to, $subject, $htmlBody, $textBody, $attachments);
    } else {
        return sendEmailNative($config, $to, $subject, $htmlBody);
    }
}

/**
 * Enviar email vía SMTP usando PHPMailer
 * @param array $config - Configuración SMTP
 * @param string $to - Email destino
 * @param string $subject - Asunto
 * @param string $htmlBody - Cuerpo HTML
 * @param string $textBody - Cuerpo texto plano
 * @param array $attachments - Archivos adjuntos
 * @return bool - true si se envió correctamente
 */
function sendEmailSMTP($config, $to, $subject, $htmlBody, $textBody = '', $attachments = []) {
    require_once __DIR__ . '/../vendor/autoload.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_usuario'] ?? '';
        $mail->Password = $config['smtp_password'] ?? '';
        $mail->SMTPSecure = $config['smtp_seguridad'] ?? 'tls';
        $mail->Port = $config['smtp_puerto'] ?? 587;
        $mail->CharSet = 'UTF-8';

        // Remitente
        $mail->setFrom(
            $config['email_from'] ?? 'noreply@conectaerp.com',
            $config['email_from_nombre'] ?? 'CONECTA ERP'
        );

        // Destinatario
        $mail->addAddress($to);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags($htmlBody);

        // Archivos adjuntos
        foreach ($attachments as $attachment) {
            if (file_exists($attachment)) {
                $mail->addAttachment($attachment);
            }
        }

        $mail->send();

        // Log de envío exitoso
        error_log("Email enviado a: $to - Asunto: $subject");

        return true;

    } catch (Exception $e) {
        error_log("Error al enviar email: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Enviar email vía mail() nativo de PHP
 * @param array $config - Configuración
 * @param string $to - Email destino
 * @param string $subject - Asunto
 * @param string $htmlBody - Cuerpo HTML
 * @return bool - true si se envió correctamente
 */
function sendEmailNative($config, $to, $subject, $htmlBody) {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . ($config['email_from_nombre'] ?? 'CONECTA ERP') . ' <' . ($config['email_from'] ?? 'noreply@conectaerp.com') . '>',
        'Reply-To: ' . ($config['email_from'] ?? 'noreply@conectaerp.com'),
        'X-Mailer: PHP/' . phpversion()
    ];

    $success = mail($to, $subject, $htmlBody, implode("\r\n", $headers));

    if ($success) {
        error_log("Email enviado (nativo) a: $to - Asunto: $subject");
    } else {
        error_log("Error al enviar email (nativo) a: $to");
    }

    return $success;
}

/**
 * Cargar y renderizar plantilla de email
 * @param string $template - Nombre de la plantilla (sin .html)
 * @param array $variables - Variables para reemplazar en la plantilla
 * @return string - HTML renderizado
 */
function renderEmailTemplate($template, $variables = []) {
    $templatePath = __DIR__ . '/../emails/templates/' . $template . '.html';

    if (!file_exists($templatePath)) {
        error_log("Plantilla de email no encontrada: $templatePath");
        return '';
    }

    $html = file_get_contents($templatePath);

    // Reemplazar variables {{variable}}
    foreach ($variables as $key => $value) {
        $html = str_replace('{{' . $key . '}}', $value, $html);
    }

    return $html;
}

/**
 * EMAIL: Bienvenida a nuevo usuario
 * @param mysqli $conn - Conexión a BD
 * @param array $usuario - Datos del usuario
 * @return bool - true si se envió correctamente
 */
function enviarEmailBienvenida($conn, $usuario) {
    $variables = [
        'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
        'email' => $usuario['email'],
        'dias_trial' => 14,
        'fecha_fin_trial' => date('d/m/Y', strtotime($usuario['fecha_fin_trial'] ?? '+14 days')),
        'url_login' => SITE_URL . '/login.php',
        'url_dashboard' => SITE_URL . '/user/dashboard.php',
        'anio' => date('Y')
    ];

    $html = renderEmailTemplate('bienvenida', $variables);
    $subject = '¡Bienvenido a CONECTA ERP! 🎉';

    $success = sendEmail($conn, $usuario['email'], $subject, $html);

    // Registrar en auditoría
    if ($success) {
        logAuditoria('envio_email', 'usuarios', $usuario['id'] ?? null, null, null,
            "Email de bienvenida enviado a {$usuario['email']}");
    }

    return $success;
}

/**
 * EMAIL: Recordatorio de trial próximo a expirar
 * @param mysqli $conn - Conexión a BD
 * @param array $usuario - Datos del usuario
 * @param int $diasRestantes - Días restantes de trial
 * @return bool - true si se envió correctamente
 */
function enviarEmailTrialExpirando($conn, $usuario, $diasRestantes) {
    $variables = [
        'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
        'dias_restantes' => $diasRestantes,
        'fecha_expiracion' => date('d/m/Y', strtotime($usuario['fecha_fin_trial'])),
        'url_planes' => SITE_URL . '/index.php#planes',
        'url_dashboard' => SITE_URL . '/user/dashboard.php',
        'anio' => date('Y')
    ];

    $html = renderEmailTemplate('trial_expirando', $variables);
    $subject = "⏰ Tu periodo de prueba vence en $diasRestantes días";

    $success = sendEmail($conn, $usuario['email'], $subject, $html);

    if ($success) {
        logAuditoria('envio_email', 'usuarios', $usuario['id'], null, null,
            "Email trial expirando enviado ($diasRestantes días)");
    }

    return $success;
}

/**
 * EMAIL: Trial expirado
 * @param mysqli $conn - Conexión a BD
 * @param array $usuario - Datos del usuario
 * @return bool - true si se envió correctamente
 */
function enviarEmailTrialExpirado($conn, $usuario) {
    $variables = [
        'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
        'fecha_expiracion' => date('d/m/Y', strtotime($usuario['fecha_fin_trial'])),
        'url_planes' => SITE_URL . '/index.php#planes',
        'url_contacto' => SITE_URL . '/contacto.php',
        'anio' => date('Y')
    ];

    $html = renderEmailTemplate('trial_expirado', $variables);
    $subject = '⚠️ Tu periodo de prueba ha expirado';

    return sendEmail($conn, $usuario['email'], $subject, $html);
}

/**
 * EMAIL: Pago aprobado
 * @param mysqli $conn - Conexión a BD
 * @param array $pago - Datos del pago
 * @param array $usuario - Datos del usuario
 * @return bool - true si se envió correctamente
 */
function enviarEmailPagoAprobado($conn, $pago, $usuario) {
    $variables = [
        'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
        'monto' => '$' . number_format($pago['monto'], 0, ',', '.'),
        'plan' => $pago['plan_nombre'] ?? 'Plan',
        'fecha_pago' => date('d/m/Y', strtotime($pago['fecha_pago'])),
        'metodo_pago' => ucfirst($pago['metodo_pago'] ?? 'Transferencia'),
        'orden_id' => $pago['orden_id'] ?? $pago['id'],
        'url_factura' => SITE_URL . "/user/factura.php?id={$pago['id']}",
        'url_dashboard' => SITE_URL . '/user/dashboard.php',
        'anio' => date('Y')
    ];

    $html = renderEmailTemplate('pago_aprobado', $variables);
    $subject = '✅ Pago recibido - CONECTA ERP';

    $success = sendEmail($conn, $usuario['email'], $subject, $html);

    if ($success) {
        logAuditoria('envio_email', 'pagos', $pago['id'], null, null,
            "Email pago aprobado enviado");
    }

    return $success;
}

/**
 * EMAIL: Pago rechazado
 * @param mysqli $conn - Conexión a BD
 * @param array $pago - Datos del pago
 * @param array $usuario - Datos del usuario
 * @param string $motivo - Motivo del rechazo
 * @return bool - true si se envió correctamente
 */
function enviarEmailPagoRechazado($conn, $pago, $usuario, $motivo = '') {
    $variables = [
        'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
        'monto' => '$' . number_format($pago['monto'], 0, ',', '.'),
        'motivo' => $motivo ?: 'No especificado',
        'fecha_intento' => date('d/m/Y H:i'),
        'url_reintentar' => SITE_URL . '/user/pago.php',
        'url_soporte' => SITE_URL . '/user/soporte.php',
        'anio' => date('Y')
    ];

    $html = renderEmailTemplate('pago_rechazado', $variables);
    $subject = '❌ Pago rechazado - CONECTA ERP';

    return sendEmail($conn, $usuario['email'], $subject, $html);
}

/**
 * EMAIL: Cambio de plan
 * @param mysqli $conn - Conexión a BD
 * @param array $usuario - Datos del usuario
 * @param string $planAnterior - Nombre del plan anterior
 * @param string $planNuevo - Nombre del plan nuevo
 * @return bool - true si se envió correctamente
 */
function enviarEmailCambioPlan($conn, $usuario, $planAnterior, $planNuevo) {
    $variables = [
        'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
        'plan_anterior' => $planAnterior,
        'plan_nuevo' => $planNuevo,
        'fecha_cambio' => date('d/m/Y'),
        'url_dashboard' => SITE_URL . '/user/dashboard.php',
        'anio' => date('Y')
    ];

    $html = renderEmailTemplate('cambio_plan', $variables);
    $subject = "🔄 Cambio de plan confirmado - Ahora estás en $planNuevo";

    return sendEmail($conn, $usuario['email'], $subject, $html);
}

/**
 * EMAIL: Recuperar contraseña
 * @param mysqli $conn - Conexión a BD
 * @param array $usuario - Datos del usuario
 * @param string $token - Token de recuperación
 * @return bool - true si se envió correctamente
 */
function enviarEmailRecuperarPassword($conn, $usuario, $token) {
    $variables = [
        'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
        'url_reset' => SITE_URL . "/reset_password.php?token=$token",
        'validez_horas' => 24,
        'fecha_solicitud' => date('d/m/Y H:i'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Desconocida',
        'url_soporte' => SITE_URL . '/user/soporte.php',
        'anio' => date('Y')
    ];

    $html = renderEmailTemplate('recuperar_password', $variables);
    $subject = '🔐 Recuperación de contraseña - CONECTA ERP';

    $success = sendEmail($conn, $usuario['email'], $subject, $html);

    if ($success) {
        logAuditoria('envio_email', 'usuarios', $usuario['id'], null, null,
            "Email recuperación contraseña enviado");
    }

    return $success;
}

/**
 * EMAIL: Confirmación de email (verificación)
 * @param mysqli $conn - Conexión a BD
 * @param array $usuario - Datos del usuario
 * @param string $token - Token de verificación
 * @return bool - true si se envió correctamente
 */
function enviarEmailVerificacion($conn, $usuario, $token) {
    $variables = [
        'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
        'url_verificar' => SITE_URL . "/verificar_email.php?token=$token",
        'anio' => date('Y')
    ];

    $html = renderEmailTemplate('verificar_email', $variables);
    $subject = '📧 Verifica tu email - CONECTA ERP';

    return sendEmail($conn, $usuario['email'], $subject, $html);
}

/**
 * EMAIL: Notificación a super admin
 * @param mysqli $conn - Conexión a BD
 * @param string $asunto - Asunto del email
 * @param string $mensaje - Mensaje
 * @param array $datos - Datos adicionales
 * @return bool - true si se envió correctamente
 */
function enviarEmailSuperAdmin($conn, $asunto, $mensaje, $datos = []) {
    // Obtener email del super admin
    $result = $conn->query("SELECT email FROM usuarios WHERE es_super_admin = 1 LIMIT 1");

    if ($result->num_rows === 0) {
        return false;
    }

    $superAdmin = $result->fetch_assoc();

    $variables = [
        'asunto' => $asunto,
        'mensaje' => $mensaje,
        'datos' => json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        'fecha' => date('d/m/Y H:i:s'),
        'url_admin' => SITE_URL . '/admin/dashboard_admin.php',
        'anio' => date('Y')
    ];

    $html = renderEmailTemplate('notificacion_admin', $variables);

    return sendEmail($conn, $superAdmin['email'], "[ADMIN] $asunto", $html);
}

/**
 * CRON: Enviar emails de trial próximos a expirar
 * Ejecutar diariamente para notificar a usuarios con trial expirando en 3 días
 * @param mysqli $conn - Conexión a BD
 * @return int - Cantidad de emails enviados
 */
function cronEnviarRecordatoriosTrial($conn) {
    $enviados = 0;

    // Usuarios con trial expirando en 3 días
    $query = "SELECT u.*, p.nombre as plan_nombre
              FROM usuarios u
              LEFT JOIN planes p ON u.plan_id = p.id
              WHERE u.en_periodo_prueba = 1
              AND DATEDIFF(u.fecha_fin_trial, NOW()) = 3
              AND u.activo = 1";

    $result = $conn->query($query);

    while ($usuario = $result->fetch_assoc()) {
        if (enviarEmailTrialExpirando($conn, $usuario, 3)) {
            $enviados++;
        }
    }

    return $enviados;
}

/**
 * CRON: Desactivar usuarios con trial expirado
 * Ejecutar diariamente para desactivar y notificar
 * @param mysqli $conn - Conexión a BD
 * @return int - Cantidad de usuarios desactivados
 */
function cronDesactivarTrialExpirados($conn) {
    $desactivados = 0;

    // Usuarios con trial expirado
    $query = "SELECT * FROM usuarios
              WHERE en_periodo_prueba = 1
              AND fecha_fin_trial < NOW()
              AND activo = 1";

    $result = $conn->query($query);

    while ($usuario = $result->fetch_assoc()) {
        // Desactivar usuario
        $stmt = $conn->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
        $stmt->bind_param("i", $usuario['id']);

        if ($stmt->execute()) {
            // Enviar email de notificación
            enviarEmailTrialExpirado($conn, $usuario);

            // Log de auditoría
            logAuditoria('trial_expirado', 'usuarios', $usuario['id'], null, null,
                "Trial expirado - Usuario desactivado automáticamente");

            $desactivados++;
        }

        $stmt->close();
    }

    return $desactivados;
}
