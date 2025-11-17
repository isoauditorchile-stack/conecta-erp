<?php
/**
 * EMAIL MANAGER - CONECTA ERP
 * Gestión de envío automático de emails para documentos
 * Incluye: facturas, cotizaciones, pedidos, recordatorios
 *
 * Soporta:
 * - SMTP (Gmail, Outlook, servidores propios)
 * - Plantillas HTML
 * - Adjuntos PDF
 * - Cola de envíos
 * - Tracking de apertura
 */

class EmailManager {
    private $conn;
    private $empresa_id;
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_pass;
    private $from_email;
    private $from_name;
    private $use_smtp = true;

    public function __construct($conn, $empresa_id) {
        $this->conn = $conn;
        $this->empresa_id = $empresa_id;

        // Cargar configuración SMTP
        $this->cargarConfiguracion();
    }

    /**
     * Cargar configuración de email desde BD
     */
    private function cargarConfiguracion() {
        $stmt = $this->conn->prepare("SELECT * FROM configuracion_empresa WHERE empresa_id = ?");
        $stmt->bind_param("i", $this->empresa_id);
        $stmt->execute();
        $config = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($config) {
            $this->smtp_host = $config['smtp_host'] ?? 'smtp.gmail.com';
            $this->smtp_port = $config['smtp_port'] ?? 587;
            $this->smtp_user = $config['smtp_user'] ?? $config['email'];
            $this->smtp_pass = $config['smtp_pass'] ?? '';
            $this->from_email = $config['email'] ?? 'noreply@conectaerp.cl';
            $this->from_name = $config['razon_social'] ?? 'CONECTA ERP';
        }
    }

    /**
     * Enviar email con plantilla
     *
     * @param string $to Email destinatario
     * @param string $subject Asunto
     * @param string $template Nombre de plantilla
     * @param array $data Datos para la plantilla
     * @param array $attachments Archivos adjuntos
     * @return array ['success' => bool, 'message' => string, 'error' => string]
     */
    public function enviarEmail($to, $subject, $template, $data = [], $attachments = []) {
        try {
            // Generar HTML desde plantilla
            $html = $this->generarHTML($template, $data);

            // Preparar headers
            $headers = $this->prepararHeaders($attachments);

            // Enviar
            if ($this->use_smtp) {
                $resultado = $this->enviarSMTP($to, $subject, $html, $attachments);
            } else {
                $resultado = mail($to, $subject, $html, implode("\r\n", $headers));
            }

            // Registrar envío
            $this->registrarEnvio($to, $subject, $template, $resultado ? 'enviado' : 'fallido');

            return [
                'success' => $resultado,
                'message' => $resultado ? 'Email enviado exitosamente' : 'Error al enviar email'
            ];

        } catch (Exception $e) {
            $this->registrarEnvio($to, $subject, $template, 'error');

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar factura por email
     *
     * @param int $factura_id ID de la factura
     * @return array ['success' => bool, 'message' => string]
     */
    public function enviarFactura($factura_id) {
        // Obtener datos de la factura
        $stmt = $this->conn->prepare("SELECT f.*, c.razon_social, c.email, c.rut as cliente_rut
            FROM facturas f
            LEFT JOIN clientes c ON f.cliente_id = c.id
            WHERE f.id = ? AND f.empresa_id = ?");
        $stmt->bind_param("ii", $factura_id, $this->empresa_id);
        $stmt->execute();
        $factura = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$factura || !$factura['email']) {
            return [
                'success' => false,
                'error' => 'Factura no encontrada o sin email de cliente'
            ];
        }

        // Generar PDF (asumir que existe una función para esto)
        $pdf_path = $this->generarPDFFactura($factura_id);

        // Datos para la plantilla
        $data = [
            'cliente_nombre' => $factura['razon_social'],
            'tipo_documento' => $this->getNombreTipoDocumento($factura['tipo_documento']),
            'folio' => $factura['folio'],
            'fecha' => date('d-m-Y', strtotime($factura['fecha_emision'])),
            'total' => '$' . number_format($factura['total'], 0, ',', '.'),
            'empresa_nombre' => $this->from_name
        ];

        $subject = "Factura Electrónica N° {$factura['folio']} - {$this->from_name}";

        $attachments = [];
        if ($pdf_path && file_exists($pdf_path)) {
            $attachments[] = [
                'path' => $pdf_path,
                'name' => "Factura_{$factura['folio']}.pdf",
                'type' => 'application/pdf'
            ];
        }

        return $this->enviarEmail($factura['email'], $subject, 'factura', $data, $attachments);
    }

    /**
     * Enviar cotización por email
     */
    public function enviarCotizacion($cotizacion_id) {
        $stmt = $this->conn->prepare("SELECT cot.*, c.razon_social, c.email
            FROM cotizaciones cot
            LEFT JOIN clientes c ON cot.cliente_id = c.id
            WHERE cot.id = ? AND cot.empresa_id = ?");
        $stmt->bind_param("ii", $cotizacion_id, $this->empresa_id);
        $stmt->execute();
        $cotizacion = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$cotizacion || !$cotizacion['email']) {
            return ['success' => false, 'error' => 'Cotización no encontrada o sin email'];
        }

        $data = [
            'cliente_nombre' => $cotizacion['razon_social'],
            'numero' => $cotizacion['numero'],
            'fecha' => date('d-m-Y', strtotime($cotizacion['fecha'])),
            'total' => '$' . number_format($cotizacion['total'], 0, ',', '.'),
            'validez' => $cotizacion['dias_validez'] . ' días',
            'empresa_nombre' => $this->from_name
        ];

        $subject = "Cotización N° {$cotizacion['numero']} - {$this->from_name}";

        return $this->enviarEmail($cotizacion['email'], $subject, 'cotizacion', $data);
    }

    /**
     * Enviar recordatorio de pago
     */
    public function enviarRecordatorioPago($factura_id) {
        $stmt = $this->conn->prepare("SELECT f.*, c.razon_social, c.email,
            DATEDIFF(CURDATE(), f.fecha_vencimiento) as dias_vencido
            FROM facturas f
            LEFT JOIN clientes c ON f.cliente_id = c.id
            WHERE f.id = ? AND f.empresa_id = ? AND f.saldo_pendiente > 0");
        $stmt->bind_param("ii", $factura_id, $this->empresa_id);
        $stmt->execute();
        $factura = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$factura || !$factura['email']) {
            return ['success' => false, 'error' => 'Factura no encontrada o sin email'];
        }

        $data = [
            'cliente_nombre' => $factura['razon_social'],
            'folio' => $factura['folio'],
            'total' => '$' . number_format($factura['total'], 0, ',', '.'),
            'saldo' => '$' . number_format($factura['saldo_pendiente'], 0, ',', '.'),
            'fecha_vencimiento' => date('d-m-Y', strtotime($factura['fecha_vencimiento'])),
            'dias_vencido' => $factura['dias_vencido'],
            'estado' => $factura['dias_vencido'] > 0 ? 'VENCIDA' : 'POR VENCER',
            'empresa_nombre' => $this->from_name
        ];

        $subject = "Recordatorio de Pago - Factura N° {$factura['folio']}";

        return $this->enviarEmail($factura['email'], $subject, 'recordatorio_pago', $data);
    }

    /**
     * Generar HTML desde plantilla
     */
    private function generarHTML($template, $data) {
        // Plantillas HTML básicas
        $templates = [
            'factura' => '
                <html>
                <body style="font-family: Arial, sans-serif; padding: 20px;">
                    <h2 style="color: #333;">Estimado/a {cliente_nombre}</h2>
                    <p>Adjuntamos {tipo_documento} Electrónica N° <strong>{folio}</strong></p>
                    <table style="border-collapse: collapse; margin: 20px 0;">
                        <tr><td style="padding: 5px;"><strong>Fecha:</strong></td><td>{fecha}</td></tr>
                        <tr><td style="padding: 5px;"><strong>Total:</strong></td><td>{total}</td></tr>
                    </table>
                    <p>Gracias por su preferencia.</p>
                    <hr>
                    <p style="color: #666; font-size: 12px;">
                        {empresa_nombre}<br>
                        Este es un email automático, por favor no responder.
                    </p>
                </body>
                </html>
            ',
            'cotizacion' => '
                <html>
                <body style="font-family: Arial, sans-serif; padding: 20px;">
                    <h2 style="color: #333;">Estimado/a {cliente_nombre}</h2>
                    <p>Adjuntamos cotización N° <strong>{numero}</strong></p>
                    <table style="border-collapse: collapse; margin: 20px 0;">
                        <tr><td style="padding: 5px;"><strong>Fecha:</strong></td><td>{fecha}</td></tr>
                        <tr><td style="padding: 5px;"><strong>Total:</strong></td><td>{total}</td></tr>
                        <tr><td style="padding: 5px;"><strong>Validez:</strong></td><td>{validez}</td></tr>
                    </table>
                    <p>Quedamos atentos a sus comentarios.</p>
                    <hr>
                    <p style="color: #666; font-size: 12px;">{empresa_nombre}</p>
                </body>
                </html>
            ',
            'recordatorio_pago' => '
                <html>
                <body style="font-family: Arial, sans-serif; padding: 20px;">
                    <h2 style="color: #d32f2f;">Recordatorio de Pago</h2>
                    <p>Estimado/a {cliente_nombre}</p>
                    <p>Le recordamos que la factura N° <strong>{folio}</strong> se encuentra {estado}.</p>
                    <table style="border-collapse: collapse; margin: 20px 0;">
                        <tr><td style="padding: 5px;"><strong>Total:</strong></td><td>{total}</td></tr>
                        <tr><td style="padding: 5px;"><strong>Saldo Pendiente:</strong></td><td style="color: #d32f2f;">{saldo}</td></tr>
                        <tr><td style="padding: 5px;"><strong>Fecha Vencimiento:</strong></td><td>{fecha_vencimiento}</td></tr>
                    </table>
                    <p>Agradecemos regularizar esta situación a la brevedad.</p>
                    <hr>
                    <p style="color: #666; font-size: 12px;">{empresa_nombre}</p>
                </body>
                </html>
            '
        ];

        $html = $templates[$template] ?? '<html><body>Plantilla no encontrada</body></html>';

        // Reemplazar variables
        foreach ($data as $key => $value) {
            $html = str_replace('{' . $key . '}', $value, $html);
        }

        return $html;
    }

    /**
     * Enviar via SMTP
     */
    private function enviarSMTP($to, $subject, $html, $attachments = []) {
        require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';
        require_once __DIR__ . '/../libs/PHPMailer/Exception.php';

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_user;
            $mail->Password = $this->smtp_pass;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;
            $mail->CharSet = 'UTF-8';

            // Remitente y destinatario
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($to);

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;

            // Adjuntos
            foreach ($attachments as $file) {
                if (file_exists($file['path'])) {
                    $mail->addAttachment($file['path'], $file['name']);
                }
            }

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Error al enviar email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Preparar headers para mail()
     */
    private function prepararHeaders($attachments = []) {
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=UTF-8";
        $headers[] = "From: {$this->from_name} <{$this->from_email}>";

        return $headers;
    }

    /**
     * Registrar envío en BD
     */
    private function registrarEnvio($to, $subject, $template, $estado) {
        $stmt = $this->conn->prepare("INSERT INTO emails_enviados (empresa_id, destinatario, asunto, template, estado, fecha_envio) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issss", $this->empresa_id, $to, $subject, $template, $estado);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Generar PDF de factura (placeholder)
     */
    private function generarPDFFactura($factura_id) {
        // En producción, esto generaría un PDF real
        return null;
    }

    /**
     * Obtener nombre de tipo de documento
     */
    private function getNombreTipoDocumento($tipo) {
        $tipos = [
            '33' => 'Factura',
            '34' => 'Factura Exenta',
            '39' => 'Boleta',
            '52' => 'Guía de Despacho',
            '56' => 'Nota de Débito',
            '61' => 'Nota de Crédito'
        ];

        return $tipos[$tipo] ?? 'Documento';
    }

    /**
     * Enviar emails masivos (cola)
     */
    public function enviarEmailsMasivos($emails, $subject, $template, $data) {
        $resultados = [
            'enviados' => 0,
            'fallidos' => 0,
            'errores' => []
        ];

        foreach ($emails as $email) {
            $resultado = $this->enviarEmail($email, $subject, $template, $data);

            if ($resultado['success']) {
                $resultados['enviados']++;
            } else {
                $resultados['fallidos']++;
                $resultados['errores'][] = $email . ': ' . ($resultado['error'] ?? 'Error desconocido');
            }

            // Esperar para no saturar el servidor SMTP
            usleep(500000); // 0.5 segundos
        }

        return $resultados;
    }
}
?>
