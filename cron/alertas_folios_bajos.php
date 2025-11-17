<?php
/**
 * ===============================================
 * CRON JOB - ALERTAS DE FOLIOS BAJOS
 * ===============================================
 * Ejecutar diariamente para notificar sobre folios bajos
 *
 * CRONTAB:
 * 0 8 * * * /usr/bin/php /path/to/cron/alertas_folios_bajos.php
 * (Ejecutar todos los días a las 8:00 AM)
 */

require_once __DIR__ . '/../includes/config.php';

echo "===========================================\n";
echo "CRON: Verificación de Folios Bajos\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

// Obtener todas las empresas activas
$empresas_query = "
    SELECT id, nombre_empresa, rut, email_contacto
    FROM empresas
    WHERE estado = 'activo'
    ORDER BY id
";

$empresas = $conn->query($empresas_query);

$total_empresas = 0;
$total_alertas_enviadas = 0;
$total_folios_criticos = 0;

while ($empresa = $empresas->fetch_assoc()) {
    $total_empresas++;
    $empresa_id = $empresa['id'];
    $empresa_nombre = $empresa['nombre_empresa'];

    echo "Revisando: $empresa_nombre (ID: $empresa_id)\n";

    // Obtener folios en estado crítico o agotado
    $query_folios = "
        SELECT
            t.codigo,
            t.nombre,
            COALESCE(SUM(f.folio_hasta - f.folio_actual), 0) as folios_disponibles,
            COUNT(f.id) as cantidad_cafs,
            CASE
                WHEN SUM(f.folio_hasta - f.folio_actual) = 0 THEN 'agotado'
                WHEN SUM(f.folio_hasta - f.folio_actual) < 50 THEN 'critico'
                WHEN SUM(f.folio_hasta - f.folio_actual) < 100 THEN 'bajo'
                ELSE 'normal'
            END as estado_alerta
        FROM tipos_documentos_sii t
        LEFT JOIN folios_caf f ON t.codigo = f.tipo_documento
            AND f.empresa_id = ?
            AND f.estado = 'activo'
        WHERE t.electronico = 1
          AND t.activo = 1
          AND t.requiere_folio = 1
          AND t.fase_implementacion <= 2  -- Solo fases esenciales e importantes
        GROUP BY t.codigo, t.nombre
        HAVING estado_alerta IN ('agotado', 'critico', 'bajo')
        ORDER BY
            CASE estado_alerta
                WHEN 'agotado' THEN 1
                WHEN 'critico' THEN 2
                WHEN 'bajo' THEN 3
            END
    ";

    $stmt = $conn->prepare($query_folios);
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $folios_criticos = $stmt->get_result();

    $folios_data = [];
    $tiene_agotados = false;
    $tiene_criticos = false;

    while ($folio = $folios_criticos->fetch_assoc()) {
        $folios_data[] = $folio;

        if ($folio['estado_alerta'] === 'agotado') {
            $tiene_agotados = true;
        }
        if ($folio['estado_alerta'] === 'critico') {
            $tiene_criticos = true;
        }

        $total_folios_criticos++;
    }

    if (count($folios_data) > 0) {
        echo "  ⚠️  Encontrados " . count($folios_data) . " tipos de documento con folios bajos\n";

        // Enviar email de alerta
        $email_enviado = enviarEmailAlertaFolios(
            $empresa,
            $folios_data,
            $tiene_agotados,
            $tiene_criticos
        );

        if ($email_enviado) {
            echo "  ✅ Email de alerta enviado\n";
            $total_alertas_enviadas++;

            // Registrar en log
            logAlertaFolios($empresa_id, $folios_data);
        } else {
            echo "  ❌ Error al enviar email\n";
        }
    } else {
        echo "  ✓ Todos los folios en nivel normal\n";
    }

    echo "\n";
}

echo "===========================================\n";
echo "RESUMEN:\n";
echo "Empresas revisadas: $total_empresas\n";
echo "Alertas enviadas: $total_alertas_enviadas\n";
echo "Total folios críticos: $total_folios_criticos\n";
echo "===========================================\n";

/**
 * Enviar email de alerta de folios bajos
 */
function enviarEmailAlertaFolios($empresa, $folios_data, $tiene_agotados, $tiene_criticos) {
    $to = $empresa['email_contacto'] ?? 'admin@empresa.cl';
    $empresa_nombre = $empresa['nombre_empresa'];

    // Determinar nivel de urgencia
    if ($tiene_agotados) {
        $urgencia = 'URGENTE';
        $color = '#dc3545';
    } elseif ($tiene_criticos) {
        $urgencia = 'ALTA PRIORIDAD';
        $color = '#ffc107';
    } else {
        $urgencia = 'ATENCIÓN';
        $color = '#17a2b8';
    }

    $subject = "[$urgencia] Folios Bajos - $empresa_nombre";

    // Construir tabla HTML de folios
    $tabla_folios = '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
    $tabla_folios .= '<thead><tr style="background: #f8f9fa;">
        <th style="padding: 12px; border: 1px solid #dee2e6; text-align: left;">Tipo Documento</th>
        <th style="padding: 12px; border: 1px solid #dee2e6; text-align: center;">Código</th>
        <th style="padding: 12px; border: 1px solid #dee2e6; text-align: center;">Folios Disponibles</th>
        <th style="padding: 12px; border: 1px solid #dee2e6; text-align: center;">Estado</th>
    </tr></thead><tbody>';

    foreach ($folios_data as $folio) {
        $estado_color = match($folio['estado_alerta']) {
            'agotado' => '#343a40',
            'critico' => '#dc3545',
            'bajo' => '#ffc107',
            default => '#28a745'
        };

        $estado_texto = match($folio['estado_alerta']) {
            'agotado' => 'AGOTADO',
            'critico' => 'CRÍTICO',
            'bajo' => 'BAJO',
            default => 'NORMAL'
        };

        $tabla_folios .= "<tr>
            <td style='padding: 12px; border: 1px solid #dee2e6;'>{$folio['nombre']}</td>
            <td style='padding: 12px; border: 1px solid #dee2e6; text-align: center;'>{$folio['codigo']}</td>
            <td style='padding: 12px; border: 1px solid #dee2e6; text-align: center; font-weight: bold;'>" . number_format($folio['folios_disponibles']) . "</td>
            <td style='padding: 12px; border: 1px solid #dee2e6; text-align: center;'>
                <span style='background: $estado_color; color: white; padding: 5px 10px; border-radius: 5px; font-weight: 600;'>$estado_texto</span>
            </td>
        </tr>";
    }

    $tabla_folios .= '</tbody></table>';

    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: white; padding: 30px; border: 1px solid #e0e0e0; }
            .alert-box { background: $color; color: white; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 0.9rem; color: #6c757d; }
            .btn { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🚨 ALERTA DE FOLIOS</h1>
                <p>$empresa_nombre</p>
            </div>

            <div class='content'>
                <div class='alert-box'>
                    <h2>$urgencia</h2>
                    <p>Se han detectado tipos de documentos con folios bajos o agotados</p>
                </div>

                <h3>Folios que Requieren Atención:</h3>

                $tabla_folios

                <h3>Acción Requerida:</h3>
                <ul>
                    <li><strong>Inmediato:</strong> Solicitar folios al SII para documentos AGOTADOS o CRÍTICOS</li>
                    <li><strong>Esta semana:</strong> Planificar solicitud para documentos en nivel BAJO</li>
                </ul>

                <h3>¿Cómo Solicitar Folios?</h3>
                <ol>
                    <li>Ingresa al <a href='https://www4.sii.cl/registrosc/'>Portal SII</a></li>
                    <li>Ve a Documentos Tributarios Electrónicos → Solicitar Folios</li>
                    <li>Descarga el archivo CAF</li>
                    <li>Cárgalo en CONECTA ERP</li>
                </ol>

                <div style='text-align: center;'>
                    <a href='http://conectaerp.cl/modulos/facturacion/folios.php' class='btn'>
                        Ver Dashboard de Folios
                    </a>
                </div>
            </div>

            <div class='footer'>
                <p>Este es un mensaje automático de CONECTA ERP</p>
                <p>Enviado el " . date('d/m/Y H:i:s') . "</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: CONECTA ERP <noreply@conectaerp.cl>" . "\r\n";

    return mail($to, $subject, $message, $headers);
}

/**
 * Registrar alerta en log
 */
function logAlertaFolios($empresa_id, $folios_data) {
    global $conn;

    $detalle = json_encode($folios_data, JSON_UNESCAPED_UNICODE);

    $stmt = $conn->prepare("
        INSERT INTO logs_sistema (
            empresa_id,
            tipo,
            modulo,
            accion,
            detalle,
            fecha_hora
        ) VALUES (?, 'alerta', 'facturacion', 'folios_bajos', ?, NOW())
    ");

    $stmt->bind_param("is", $empresa_id, $detalle);
    $stmt->execute();
    $stmt->close();
}
?>
