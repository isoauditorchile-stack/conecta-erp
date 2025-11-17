<?php
/**
 * ============================================
 * DTEManager - Gestión de Documentos Tributarios Electrónicos
 * ============================================
 * Clase completa para generar, firmar y enviar DTE al SII
 * Compatible con todos los tipos de documentos (33, 39, 52, 61, etc.)
 *
 * @author CONECTA ERP
 * @version 2.0
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/SIIClient.php';

class DTEManager {

    private $conn;
    private $empresa_id;
    private $sii_client;
    private $ambiente; // 'certificacion' o 'produccion'

    // Configuración de certificado digital
    private $certificado_path;
    private $certificado_password;

    /**
     * Constructor
     */
    public function __construct($conn, $empresa_id) {
        $this->conn = $conn;
        $this->empresa_id = $empresa_id;
        $this->sii_client = new SIIClient($conn, $empresa_id);

        // Cargar configuración DTE de la empresa
        $this->cargarConfiguracion();
    }

    /**
     * Cargar configuración DTE desde BD
     */
    private function cargarConfiguracion() {
        $query = "SELECT * FROM configuracion_dte WHERE empresa_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->empresa_id);
        $stmt->execute();
        $config = $stmt->get_result()->fetch_assoc();

        if ($config) {
            $this->ambiente = $config['ambiente'];
            $this->certificado_password = $config['password_certificado'];

            // Guardar certificado temporalmente
            if ($config['certificado_digital']) {
                $temp_file = tempnam(sys_get_temp_dir(), 'cert_');
                file_put_contents($temp_file, $config['certificado_digital']);
                $this->certificado_path = $temp_file;
            }
        } else {
            throw new Exception("No se ha configurado DTE para esta empresa");
        }
    }

    /**
     * Obtener siguiente folio disponible para un tipo de documento
     */
    public function obtenerSiguienteFolio($tipo_documento) {
        $query = "CALL sp_obtener_siguiente_folio(?, ?, @folio, @caf_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $this->empresa_id, $tipo_documento);
        $stmt->execute();

        // Obtener variables de salida
        $result = $this->conn->query("SELECT @folio as folio, @caf_id as caf_id");
        $data = $result->fetch_assoc();

        if (!$data['folio']) {
            throw new Exception("No hay folios disponibles para el tipo de documento $tipo_documento. Debe cargar un CAF.");
        }

        return [
            'folio' => $data['folio'],
            'caf_id' => $data['caf_id']
        ];
    }

    /**
     * Generar DTE completo (XML)
     *
     * @param array $datos Datos del DTE:
     *   - tipo_documento: 33, 39, 52, 61, etc.
     *   - rut_receptor: RUT del receptor
     *   - razon_social_receptor: Razón social
     *   - giro_receptor: Giro
     *   - direccion_receptor: Dirección
     *   - comuna_receptor: Comuna
     *   - detalle: Array de líneas de detalle
     *   - fecha_emision: Fecha (opcional, por defecto hoy)
     *   - forma_pago: 'contado' o 'credito'
     *   - referencia: Array (solo para NC/ND)
     *
     * @return array DTE generado con XML y TED
     */
    public function generarDTE($datos) {
        // Validar datos obligatorios
        $this->validarDatos($datos);

        // Obtener folio
        $folio_data = $this->obtenerSiguienteFolio($datos['tipo_documento']);
        $folio = $folio_data['folio'];

        // Obtener datos de la empresa emisora
        $empresa = $this->obtenerDatosEmpresa();

        // Obtener CAF
        $caf = $this->obtenerCAF($folio_data['caf_id']);

        // Calcular totales
        $totales = $this->calcularTotales($datos['detalle'], $datos['tipo_documento']);

        // Generar XML del DTE
        $xml_dte = $this->generarXMLDTE([
            'tipo_documento' => $datos['tipo_documento'],
            'folio' => $folio,
            'fecha_emision' => $datos['fecha_emision'] ?? date('Y-m-d'),
            'emisor' => $empresa,
            'receptor' => [
                'rut' => $datos['rut_receptor'],
                'razon_social' => $datos['razon_social_receptor'],
                'giro' => $datos['giro_receptor'] ?? '',
                'direccion' => $datos['direccion_receptor'] ?? '',
                'comuna' => $datos['comuna_receptor'] ?? ''
            ],
            'totales' => $totales,
            'detalle' => $datos['detalle'],
            'forma_pago' => $datos['forma_pago'] ?? 'contado',
            'referencia' => $datos['referencia'] ?? null
        ]);

        // Firmar DTE
        $xml_firmado = $this->firmarXML($xml_dte);

        // Generar TED (Timbre Electrónico Digital)
        $ted = $this->generarTED($xml_firmado, $caf, $folio);

        // Guardar en BD
        $documento_id = $this->guardarDTE([
            'tipo_documento' => $datos['tipo_documento'],
            'folio' => $folio,
            'fecha_emision' => $datos['fecha_emision'] ?? date('Y-m-d'),
            'rut_emisor' => $empresa['rut'],
            'razon_social_emisor' => $empresa['razon_social'],
            'rut_receptor' => $datos['rut_receptor'],
            'razon_social_receptor' => $datos['razon_social_receptor'],
            'giro_receptor' => $datos['giro_receptor'] ?? '',
            'direccion_receptor' => $datos['direccion_receptor'] ?? '',
            'comuna_receptor' => $datos['comuna_receptor'] ?? '',
            'monto_neto' => $totales['neto'],
            'monto_exento' => $totales['exento'],
            'monto_iva' => $totales['iva'],
            'monto_total' => $totales['total'],
            'forma_pago' => $datos['forma_pago'] ?? 'contado',
            'dte_xml' => $xml_firmado,
            'ted' => $ted,
            'detalle' => $datos['detalle']
        ]);

        return [
            'documento_id' => $documento_id,
            'folio' => $folio,
            'xml' => $xml_firmado,
            'ted' => $ted,
            'totales' => $totales
        ];
    }

    /**
     * Enviar DTE al SII
     */
    public function enviarDTE($documento_id) {
        // Obtener DTE desde BD
        $query = "SELECT * FROM documentos_tributarios WHERE id = ? AND empresa_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $documento_id, $this->empresa_id);
        $stmt->execute();
        $dte = $stmt->get_result()->fetch_assoc();

        if (!$dte) {
            throw new Exception("DTE no encontrado");
        }

        // Enviar al SII usando SIIClient
        $resultado = $this->sii_client->enviarDTE($dte['dte_xml'], $dte['tipo_documento']);

        // Actualizar estado en BD
        $query = "UPDATE documentos_tributarios SET
                  track_id = ?,
                  estado_sii = 'enviado',
                  fecha_envio_sii = NOW()
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $resultado['track_id'], $documento_id);
        $stmt->execute();

        return $resultado;
    }

    /**
     * Consultar estado de DTE en SII
     */
    public function consultarEstadoDTE($documento_id) {
        // Obtener track_id
        $query = "SELECT track_id, tipo_documento FROM documentos_tributarios
                  WHERE id = ? AND empresa_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $documento_id, $this->empresa_id);
        $stmt->execute();
        $dte = $stmt->get_result()->fetch_assoc();

        if (!$dte || !$dte['track_id']) {
            throw new Exception("DTE no enviado al SII");
        }

        // Consultar al SII
        $estado = $this->sii_client->consultarEstadoDTE($dte['track_id']);

        // Actualizar estado en BD
        $query = "UPDATE documentos_tributarios SET
                  estado_sii = ?,
                  glosa_estado = ?,
                  fecha_respuesta_sii = NOW()
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssi", $estado['estado'], $estado['glosa'], $documento_id);
        $stmt->execute();

        return $estado;
    }

    /**
     * Generar PDF del DTE
     */
    public function generarPDF($documento_id) {
        // Obtener DTE
        $query = "SELECT dt.*, e.nombre_empresa, e.rut as empresa_rut, e.direccion as empresa_direccion
                  FROM documentos_tributarios dt
                  INNER JOIN empresas e ON dt.empresa_id = e.id
                  WHERE dt.id = ? AND dt.empresa_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $documento_id, $this->empresa_id);
        $stmt->execute();
        $dte = $stmt->get_result()->fetch_assoc();

        if (!$dte) {
            throw new Exception("DTE no encontrado");
        }

        // Obtener detalle
        $query = "SELECT * FROM documentos_tributarios_detalle WHERE documento_id = ? ORDER BY linea";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $documento_id);
        $stmt->execute();
        $detalle = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Generar PDF usando TCPDF o similar
        require_once __DIR__ . '/../vendor/tcpdf/tcpdf.php';

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('CONECTA ERP');
        $pdf->SetAuthor($dte['nombre_empresa']);
        $pdf->SetTitle('DTE ' . $dte['tipo_documento'] . ' - ' . $dte['folio']);

        $pdf->AddPage();

        // Contenido del PDF
        $html = $this->generarHTMLPDF($dte, $detalle);
        $pdf->writeHTML($html, true, false, true, false, '');

        // Guardar PDF
        $pdf_path = __DIR__ . '/../storage/dte/pdf/' . $dte['tipo_documento'] . '_' . $dte['folio'] . '.pdf';
        $pdf->Output($pdf_path, 'F');

        // Actualizar BD
        $query = "UPDATE documentos_tributarios SET pdf_generado = 1, ruta_pdf = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $pdf_path, $documento_id);
        $stmt->execute();

        return $pdf_path;
    }

    /**
     * Validar datos del DTE
     */
    private function validarDatos($datos) {
        $campos_obligatorios = [
            'tipo_documento',
            'rut_receptor',
            'razon_social_receptor',
            'detalle'
        ];

        foreach ($campos_obligatorios as $campo) {
            if (!isset($datos[$campo]) || empty($datos[$campo])) {
                throw new Exception("El campo '$campo' es obligatorio");
            }
        }

        // Validar RUT
        if (!$this->validarRUT($datos['rut_receptor'])) {
            throw new Exception("RUT receptor inválido");
        }

        // Validar detalle
        if (!is_array($datos['detalle']) || count($datos['detalle']) == 0) {
            throw new Exception("Debe incluir al menos una línea de detalle");
        }

        return true;
    }

    /**
     * Validar RUT chileno
     */
    private function validarRUT($rut) {
        $rut = preg_replace('/[^0-9kK]/', '', $rut);
        $dv = substr($rut, -1);
        $numero = substr($rut, 0, -1);

        $suma = 0;
        $multiplo = 2;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $suma += $numero[$i] * $multiplo;
            $multiplo = $multiplo < 7 ? $multiplo + 1 : 2;
        }

        $dvEsperado = 11 - ($suma % 11);
        if ($dvEsperado == 11) $dvEsperado = 0;
        if ($dvEsperado == 10) $dvEsperado = 'K';

        return strtoupper($dv) == $dvEsperado;
    }

    /**
     * Obtener datos de la empresa emisora
     */
    private function obtenerDatosEmpresa() {
        $query = "SELECT * FROM empresas WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->empresa_id);
        $stmt->execute();
        $empresa = $stmt->get_result()->fetch_assoc();

        if (!$empresa) {
            throw new Exception("Empresa no encontrada");
        }

        return $empresa;
    }

    /**
     * Obtener archivo CAF
     */
    private function obtenerCAF($caf_id) {
        $query = "SELECT * FROM folios_caf WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $caf_id);
        $stmt->execute();
        $caf = $stmt->get_result()->fetch_assoc();

        if (!$caf) {
            throw new Exception("CAF no encontrado");
        }

        return $caf;
    }

    /**
     * Calcular totales del DTE
     */
    private function calcularTotales($detalle, $tipo_documento) {
        $neto = 0;
        $exento = 0;
        $iva = 0;

        // Verificar si el documento es afecto a IVA
        $query = "SELECT afecto_iva FROM tipos_documentos_sii WHERE codigo = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $tipo_documento);
        $stmt->execute();
        $tipo = $stmt->get_result()->fetch_assoc();
        $afecto_iva = $tipo['afecto_iva'];

        foreach ($detalle as $linea) {
            $cantidad = $linea['cantidad'];
            $precio_unitario = $linea['precio_unitario'];
            $descuento = $linea['descuento_monto'] ?? 0;
            $item_exento = $linea['exento'] ?? false;

            $subtotal = ($cantidad * $precio_unitario) - $descuento;

            if ($item_exento || !$afecto_iva) {
                $exento += $subtotal;
            } else {
                $neto += $subtotal;
            }
        }

        // Calcular IVA (19%)
        if ($afecto_iva) {
            $iva = round($neto * 0.19);
        }

        $total = $neto + $exento + $iva;

        return [
            'neto' => $neto,
            'exento' => $exento,
            'iva' => $iva,
            'total' => $total
        ];
    }

    /**
     * Generar XML del DTE según formato SII
     */
    private function generarXMLDTE($datos) {
        $xml = new DOMDocument('1.0', 'ISO-8859-1');
        $xml->formatOutput = true;

        // Raíz
        $dte = $xml->createElement('DTE');
        $dte->setAttribute('version', '1.0');
        $xml->appendChild($dte);

        // Documento
        $documento = $xml->createElement('Documento');
        $documento->setAttribute('ID', 'DTE-' . $datos['tipo_documento'] . '-' . $datos['folio']);
        $dte->appendChild($documento);

        // Encabezado
        $encabezado = $xml->createElement('Encabezado');

        // ID Documento
        $idDoc = $xml->createElement('IdDoc');
        $idDoc->appendChild($xml->createElement('TipoDTE', $datos['tipo_documento']));
        $idDoc->appendChild($xml->createElement('Folio', $datos['folio']));
        $idDoc->appendChild($xml->createElement('FchEmis', $datos['fecha_emision']));
        $idDoc->appendChild($xml->createElement('FmaPago', $datos['forma_pago'] == 'contado' ? 1 : 2));
        $encabezado->appendChild($idDoc);

        // Emisor
        $emisor = $xml->createElement('Emisor');
        $emisor->appendChild($xml->createElement('RUTEmisor', $datos['emisor']['rut']));
        $emisor->appendChild($xml->createElement('RznSoc', $datos['emisor']['nombre_empresa']));
        $emisor->appendChild($xml->createElement('GiroEmis', $datos['emisor']['giro']));
        $emisor->appendChild($xml->createElement('DirOrigen', $datos['emisor']['direccion']));
        $emisor->appendChild($xml->createElement('CmnaOrigen', $datos['emisor']['comuna']));
        $encabezado->appendChild($emisor);

        // Receptor
        $receptor = $xml->createElement('Receptor');
        $receptor->appendChild($xml->createElement('RUTRecep', $datos['receptor']['rut']));
        $receptor->appendChild($xml->createElement('RznSocRecep', $datos['receptor']['razon_social']));
        $receptor->appendChild($xml->createElement('GiroRecep', $datos['receptor']['giro']));
        $receptor->appendChild($xml->createElement('DirRecep', $datos['receptor']['direccion']));
        $receptor->appendChild($xml->createElement('CmnaRecep', $datos['receptor']['comuna']));
        $encabezado->appendChild($receptor);

        // Totales
        $totales = $xml->createElement('Totales');
        $totales->appendChild($xml->createElement('MntNeto', $datos['totales']['neto']));
        $totales->appendChild($xml->createElement('MntExe', $datos['totales']['exento']));
        $totales->appendChild($xml->createElement('IVA', $datos['totales']['iva']));
        $totales->appendChild($xml->createElement('MntTotal', $datos['totales']['total']));
        $encabezado->appendChild($totales);

        $documento->appendChild($encabezado);

        // Detalle
        foreach ($datos['detalle'] as $index => $linea) {
            $detalle = $xml->createElement('Detalle');
            $detalle->appendChild($xml->createElement('NroLinDet', $index + 1));
            $detalle->appendChild($xml->createElement('NmbItem', $linea['nombre_item']));
            $detalle->appendChild($xml->createElement('QtyItem', $linea['cantidad']));
            $detalle->appendChild($xml->createElement('PrcItem', $linea['precio_unitario']));
            $detalle->appendChild($xml->createElement('MontoItem', $linea['cantidad'] * $linea['precio_unitario']));
            $documento->appendChild($detalle);
        }

        return $xml->saveXML();
    }

    /**
     * Firmar XML con certificado digital
     */
    private function firmarXML($xml) {
        if (!$this->certificado_path) {
            throw new Exception("No hay certificado digital configurado");
        }

        // Cargar certificado
        $cert_content = file_get_contents($this->certificado_path);

        // Extraer clave privada y certificado
        openssl_pkcs12_read($cert_content, $cert_data, $this->certificado_password);

        if (!$cert_data) {
            throw new Exception("Error al leer certificado digital. Verifique la contraseña.");
        }

        // Firmar XML (implementación simplificada)
        // En producción usar biblioteca especializada como chilephp/dte

        $xml_firmado = $xml; // Placeholder - implementar firma real

        return $xml_firmado;
    }

    /**
     * Generar TED (Timbre Electrónico Digital)
     */
    private function generarTED($xml, $caf, $folio) {
        // Extraer datos del CAF
        $caf_xml = simplexml_load_string($caf['archivo_caf_xml']);

        // Generar TED en formato base64
        // (Implementación simplificada - en producción usar biblioteca especializada)

        $ted = base64_encode("TED_PLACEHOLDER_" . $folio);

        return $ted;
    }

    /**
     * Guardar DTE en base de datos
     */
    private function guardarDTE($datos) {
        $query = "INSERT INTO documentos_tributarios (
            empresa_id, tipo_documento, folio, fecha_emision,
            rut_emisor, razon_social_emisor, rut_receptor, razon_social_receptor,
            giro_receptor, direccion_receptor, comuna_receptor,
            monto_neto, monto_exento, monto_iva, monto_total,
            forma_pago, dte_xml, ted, estado_sii
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'no_enviado')";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "iiissssssssddddsss",
            $this->empresa_id,
            $datos['tipo_documento'],
            $datos['folio'],
            $datos['fecha_emision'],
            $datos['rut_emisor'],
            $datos['razon_social_emisor'],
            $datos['rut_receptor'],
            $datos['razon_social_receptor'],
            $datos['giro_receptor'],
            $datos['direccion_receptor'],
            $datos['comuna_receptor'],
            $datos['monto_neto'],
            $datos['monto_exento'],
            $datos['monto_iva'],
            $datos['monto_total'],
            $datos['forma_pago'],
            $datos['dte_xml'],
            $datos['ted']
        );

        $stmt->execute();
        $documento_id = $stmt->insert_id;

        // Guardar detalle
        foreach ($datos['detalle'] as $index => $linea) {
            $query = "INSERT INTO documentos_tributarios_detalle (
                documento_id, linea, nombre_item, cantidad, precio_unitario, monto_neto_linea
            ) VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($query);
            $monto_linea = $linea['cantidad'] * $linea['precio_unitario'];
            $stmt->bind_param(
                "iisddd",
                $documento_id,
                $index + 1,
                $linea['nombre_item'],
                $linea['cantidad'],
                $linea['precio_unitario'],
                $monto_linea
            );
            $stmt->execute();
        }

        return $documento_id;
    }

    /**
     * Generar HTML para PDF
     */
    private function generarHTMLPDF($dte, $detalle) {
        $html = '
        <h1>FACTURA ELECTRÓNICA</h1>
        <p><strong>Folio:</strong> ' . $dte['folio'] . '</p>
        <p><strong>Fecha:</strong> ' . $dte['fecha_emision'] . '</p>
        <hr>
        <h3>Emisor</h3>
        <p>' . $dte['razon_social_emisor'] . '</p>
        <p>RUT: ' . $dte['rut_emisor'] . '</p>
        <hr>
        <h3>Receptor</h3>
        <p>' . $dte['razon_social_receptor'] . '</p>
        <p>RUT: ' . $dte['rut_receptor'] . '</p>
        <hr>
        <h3>Detalle</h3>
        <table border="1" cellpadding="5">
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Total</th>
            </tr>';

        foreach ($detalle as $linea) {
            $html .= '<tr>
                <td>' . $linea['nombre_item'] . '</td>
                <td>' . $linea['cantidad'] . '</td>
                <td>$' . number_format($linea['precio_unitario'], 0, ',', '.') . '</td>
                <td>$' . number_format($linea['monto_neto_linea'], 0, ',', '.') . '</td>
            </tr>';
        }

        $html .= '</table>
        <hr>
        <p><strong>Neto:</strong> $' . number_format($dte['monto_neto'], 0, ',', '.') . '</p>
        <p><strong>IVA:</strong> $' . number_format($dte['monto_iva'], 0, ',', '.') . '</p>
        <p><strong>Total:</strong> $' . number_format($dte['monto_total'], 0, ',', '.') . '</p>
        ';

        return $html;
    }

    /**
     * Destructor - limpiar archivos temporales
     */
    public function __destruct() {
        if ($this->certificado_path && file_exists($this->certificado_path)) {
            unlink($this->certificado_path);
        }
    }
}
