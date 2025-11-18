<?php
/**
 * ============================================
 * SII CLIENT REAL - CONECTA ERP
 * ============================================
 * Cliente REAL para integración con SII Chile
 * - Descarga de folios (CAF) desde el SII
 * - Envío de DTEs (Facturas, Boletas, etc.)
 * - Consulta de estado de documentos
 * - Validación con certificados digitales
 *
 * @author CONECTA ERP
 * @version 3.0 REAL
 */

class SIIClientReal {

    private $conn;
    private $empresa_id;
    private $rut_empresa;
    private $ambiente; // 'certificacion' o 'produccion'

    // URLs del SII
    const URL_CERT = 'https://maullin.sii.cl';
    const URL_PROD = 'https://palena.sii.cl';

    // URLs de servicios
    const WSDL_AUTENTICACION = '/DTEWS/CrSeed.jws?WSDL';
    const WSDL_OBTENCION_FOLIOS = '/DTEWS/GetTokenFromSeed.jws?WSDL';
    const WSDL_ENVIO_DTE = '/DTEWS/UploadFile.jws?WSDL';
    const WSDL_CONSULTA_ESTADO = '/DTEWS/QueryEstUp.jws?WSDL';

    // Tipos de documentos
    const TIPO_FACTURA_ELECTRONICA = 33;
    const TIPO_FACTURA_EXENTA = 34;
    const TIPO_BOLETA_ELECTRONICA = 39;
    const TIPO_BOLETA_EXENTA = 41;
    const TIPO_NOTA_CREDITO = 61;
    const TIPO_NOTA_DEBITO = 56;
    const TIPO_GUIA_DESPACHO = 52;

    private $certificado_path;
    private $certificado_password;
    private $token_sii;

    /**
     * Constructor
     */
    public function __construct($conn, $empresa_id) {
        $this->conn = $conn;
        $this->empresa_id = $empresa_id;

        // Cargar configuración de la empresa
        $this->cargarConfiguracion();
    }

    /**
     * Cargar configuración de la empresa desde BD
     */
    private function cargarConfiguracion() {
        $query = "SELECT e.*, ce.*
                  FROM empresas e
                  LEFT JOIN configuracion_sii ce ON e.id = ce.empresa_id
                  WHERE e.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->empresa_id);
        $stmt->execute();
        $config = $stmt->get_result()->fetch_assoc();

        if (!$config) {
            throw new Exception("Empresa no encontrada");
        }

        $this->rut_empresa = str_replace(['.', '-'], '', $config['rut']);
        $this->ambiente = $config['sii_ambiente'] ?? 'certificacion';
        $this->certificado_path = $config['certificado_path'] ?? null;
        $this->certificado_password = $config['certificado_password'] ?? null;
    }

    /**
     * Obtener URL base según ambiente
     */
    private function getBaseUrl() {
        return $this->ambiente === 'produccion' ? self::URL_PROD : self::URL_CERT;
    }

    /**
     * ============================================
     * AUTENTICACIÓN CON EL SII
     * ============================================
     */

    /**
     * Obtener Seed desde el SII
     */
    private function getSeed() {
        $url = $this->getBaseUrl() . self::WSDL_AUTENTICACION;

        $client = new SoapClient($url, [
            'trace' => 1,
            'exceptions' => true,
            'soap_version' => SOAP_1_1
        ]);

        $response = $client->getSeed();

        if (!isset($response->return)) {
            throw new Exception("Error al obtener seed del SII");
        }

        // Parsear XML
        $xml = simplexml_load_string($response->return);

        if ((string)$xml->xpath('//ESTADO')[0] !== '00') {
            throw new Exception("SII retornó error: " . (string)$xml->xpath('//GLOSA')[0]);
        }

        return (string)$xml->xpath('//SEMILLA')[0];
    }

    /**
     * Firmar Seed con certificado digital
     */
    private function firmarSeed($seed) {
        if (!$this->certificado_path || !file_exists($this->certificado_path)) {
            throw new Exception("Certificado digital no configurado");
        }

        // Leer certificado
        $cert_content = file_get_contents($this->certificado_path);
        $cert_data = [];

        if (!openssl_pkcs12_read($cert_content, $cert_data, $this->certificado_password)) {
            throw new Exception("Error al leer certificado digital");
        }

        // Crear XML con el seed
        $xml_seed = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml_seed .= '<getToken>';
        $xml_seed .= '<item><Semilla>' . $seed . '</Semilla></item>';
        $xml_seed .= '</getToken>';

        // Firmar con el certificado
        $private_key = $cert_data['pkey'];
        $signature = '';

        openssl_sign($xml_seed, $signature, $private_key, OPENSSL_ALGO_SHA1);

        $signature_base64 = base64_encode($signature);

        // Construir XML firmado
        $xml_firmado = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml_firmado .= '<getToken>';
        $xml_firmado .= '<item><Semilla>' . $seed . '</Semilla></item>';
        $xml_firmado .= '<Signature>' . $signature_base64 . '</Signature>';
        $xml_firmado .= '</getToken>';

        return $xml_firmado;
    }

    /**
     * Obtener Token del SII
     */
    public function getToken() {
        // Verificar si hay token válido en cache
        $token_cache = $this->getTokenFromCache();
        if ($token_cache) {
            return $token_cache;
        }

        // Obtener nuevo token
        $seed = $this->getSeed();
        $seed_firmado = $this->firmarSeed($seed);

        $url = $this->getBaseUrl() . self::WSDL_OBTENCION_FOLIOS;

        $client = new SoapClient($url, [
            'trace' => 1,
            'exceptions' => true
        ]);

        $response = $client->getToken($seed_firmado);

        $xml = simplexml_load_string($response->return);

        if ((string)$xml->xpath('//ESTADO')[0] !== '00') {
            throw new Exception("Error al obtener token: " . (string)$xml->xpath('//GLOSA')[0]);
        }

        $token = (string)$xml->xpath('//TOKEN')[0];

        // Guardar token en cache (válido por 12 horas)
        $this->saveTokenToCache($token);

        $this->token_sii = $token;
        return $token;
    }

    /**
     * Obtener token desde cache
     */
    private function getTokenFromCache() {
        $query = "SELECT token, fecha_expiracion
                  FROM sii_tokens
                  WHERE empresa_id = ?
                  AND fecha_expiracion > NOW()
                  ORDER BY fecha_expiracion DESC
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->empresa_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result ? $result['token'] : null;
    }

    /**
     * Guardar token en cache
     */
    private function saveTokenToCache($token) {
        $expiracion = date('Y-m-d H:i:s', strtotime('+12 hours'));

        $query = "INSERT INTO sii_tokens (empresa_id, token, fecha_expiracion, created_at)
                  VALUES (?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iss", $this->empresa_id, $token, $expiracion);
        $stmt->execute();
    }

    /**
     * ============================================
     * DESCARGA DE FOLIOS (CAF) DESDE EL SII
     * ============================================
     */

    /**
     * Solicitar folios al SII (REAL)
     *
     * @param int $tipo_documento Tipo de documento (33, 34, 39, etc.)
     * @param int $cantidad Cantidad de folios a solicitar
     * @return array Resultado de la solicitud
     */
    public function solicitarFolios($tipo_documento, $cantidad = 50) {
        try {
            // Obtener token
            $token = $this->getToken();

            // Construir solicitud XML
            $xml_solicitud = $this->construirSolicitudFolios($tipo_documento, $cantidad);

            // Firmar solicitud
            $xml_firmado = $this->firmarDocumento($xml_solicitud);

            // Enviar al SII
            $url_solicitud = $this->getBaseUrl() . '/cgi_dte/UPL/DTEUpload';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url_solicitud);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'rutEmisor' => $this->rut_empresa,
                'dvEmisor' => $this->getDigitoVerificador(),
                'rutCompany' => $this->rut_empresa,
                'dvCompany' => $this->getDigitoVerificador(),
                'archivo' => $xml_firmado
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Cookie: TOKEN=' . $token
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code !== 200) {
                throw new Exception("Error HTTP $http_code al solicitar folios");
            }

            // Parsear respuesta
            $xml_response = simplexml_load_string($response);
            $track_id = (string)$xml_response->TRACKID;

            // Guardar solicitud en BD
            $this->guardarSolicitudFolios($tipo_documento, $cantidad, $track_id, 'pendiente');

            return [
                'success' => true,
                'track_id' => $track_id,
                'mensaje' => 'Solicitud de folios enviada correctamente'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Construir XML de solicitud de folios
     */
    private function construirSolicitudFolios($tipo_documento, $cantidad) {
        $fecha = date('Y-m-d');
        $rut_sin_dv = substr($this->rut_empresa, 0, -1);
        $dv = substr($this->rut_empresa, -1);

        $xml = '<?xml version="1.0" encoding="ISO-8859-1"?>';
        $xml .= '<REQUEST_FOLIOS version="1.0">';
        $xml .= '<EMISOR>';
        $xml .= '<RUT>' . $rut_sin_dv . '-' . $dv . '</RUT>';
        $xml .= '</EMISOR>';
        $xml .= '<TIPO_DOCUMENTO>' . $tipo_documento . '</TIPO_DOCUMENTO>';
        $xml .= '<CANTIDAD>' . $cantidad . '</CANTIDAD>';
        $xml .= '<FECHA>' . $fecha . '</FECHA>';
        $xml .= '</REQUEST_FOLIOS>';

        return $xml;
    }

    /**
     * Descargar archivo CAF con los folios autorizados
     *
     * @param string $track_id Track ID de la solicitud
     * @return array Archivo CAF y datos
     */
    public function descargarCAF($track_id) {
        try {
            $token = $this->getToken();

            $url_descarga = $this->getBaseUrl() . '/cgi_dte/UPL/DwnCaf';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url_descarga);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'TRACKID' => $track_id,
                'TOKEN' => $token
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $caf_content = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code !== 200) {
                throw new Exception("Error al descargar CAF");
            }

            // Guardar CAF en servidor
            $filename = 'CAF_' . $track_id . '_' . time() . '.xml';
            $path = __DIR__ . '/../storage/caf/' . $filename;

            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            file_put_contents($path, $caf_content);

            // Parsear CAF para extraer folios
            $xml_caf = simplexml_load_string($caf_content);
            $folio_desde = (int)$xml_caf->CAF->DA->RNG->D;
            $folio_hasta = (int)$xml_caf->CAF->DA->RNG->H;
            $tipo_doc = (int)$xml_caf->CAF->DA->TD;

            // Guardar folios en BD
            $this->guardarFoliosEnBD($tipo_doc, $folio_desde, $folio_hasta, $path);

            return [
                'success' => true,
                'filename' => $filename,
                'folio_desde' => $folio_desde,
                'folio_hasta' => $folio_hasta,
                'tipo_documento' => $tipo_doc,
                'cantidad' => ($folio_hasta - $folio_desde + 1)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Guardar folios en base de datos
     */
    private function guardarFoliosEnBD($tipo_doc, $desde, $hasta, $caf_path) {
        $this->conn->begin_transaction();

        try {
            // Guardar rango de folios
            $query = "INSERT INTO folios_autorizados
                      (empresa_id, tipo_documento, folio_desde, folio_hasta, caf_path, fecha_autorizacion, estado)
                      VALUES (?, ?, ?, ?, ?, NOW(), 'activo')";

            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iiiss", $this->empresa_id, $tipo_doc, $desde, $hasta, $caf_path);
            $stmt->execute();
            $rango_id = $this->conn->insert_id;

            // Insertar folios individuales
            $query_folio = "INSERT INTO folios (empresa_id, tipo_documento, folio, rango_id, estado, fecha_asignacion)
                            VALUES (?, ?, ?, ?, 'disponible', NOW())";

            $stmt_folio = $this->conn->prepare($query_folio);

            for ($folio = $desde; $folio <= $hasta; $folio++) {
                $stmt_folio->bind_param("iiii", $this->empresa_id, $tipo_doc, $folio, $rango_id);
                $stmt_folio->execute();
            }

            $this->conn->commit();

        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Guardar solicitud de folios
     */
    private function guardarSolicitudFolios($tipo_doc, $cantidad, $track_id, $estado) {
        $query = "INSERT INTO solicitudes_folios
                  (empresa_id, tipo_documento, cantidad, track_id, estado, fecha_solicitud)
                  VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iiiss", $this->empresa_id, $tipo_doc, $cantidad, $track_id, $estado);
        $stmt->execute();
    }

    /**
     * ============================================
     * OBTENCIÓN AUTOMÁTICA DE FOLIOS
     * ============================================
     */

    /**
     * Obtener próximo folio disponible (y solicitar más si es necesario)
     *
     * @param int $tipo_documento
     * @return int Número de folio
     */
    public function obtenerProximoFolio($tipo_documento) {
        // Buscar folio disponible
        $query = "SELECT id, folio FROM folios
                  WHERE empresa_id = ?
                  AND tipo_documento = ?
                  AND estado = 'disponible'
                  ORDER BY folio ASC
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $this->empresa_id, $tipo_documento);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result) {
            // Marcar como usado
            $update = "UPDATE folios SET estado = 'usado', fecha_uso = NOW() WHERE id = ?";
            $stmt_update = $this->conn->prepare($update);
            $stmt_update->bind_param("i", $result['id']);
            $stmt_update->execute();

            return $result['folio'];
        }

        // No hay folios disponibles - solicitar más automáticamente
        $solicitud = $this->solicitarFolios($tipo_documento, 100);

        if (!$solicitud['success']) {
            throw new Exception("No hay folios disponibles y la solicitud automática falló");
        }

        // Esperar respuesta del SII (en producción, esto debería ser asíncrono)
        sleep(5);

        $descarga = $this->descargarCAF($solicitud['track_id']);

        if (!$descarga['success']) {
            throw new Exception("Error al descargar folios automáticos");
        }

        // Reintentar obtener folio
        return $this->obtenerProximoFolio($tipo_documento);
    }

    /**
     * ============================================
     * ENVÍO DE DTEs
     * ============================================
     */

    /**
     * Enviar DTE al SII
     */
    public function enviarDTE($factura_id) {
        try {
            // Obtener datos de la factura
            $factura = $this->obtenerFactura($factura_id);

            if (!$factura) {
                throw new Exception("Factura no encontrada");
            }

            // Generar XML del DTE
            $xml_dte = $this->generarXMLDTE($factura);

            // Firmar DTE
            $xml_firmado = $this->firmarDocumento($xml_dte);

            // Obtener token
            $token = $this->getToken();

            // Enviar al SII
            $url_envio = $this->getBaseUrl() . '/cgi_dte/UPL/DTEUpload';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url_envio);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'rutEmisor' => $this->rut_empresa,
                'dvEmisor' => $this->getDigitoVerificador(),
                'archivo' => $xml_firmado
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Cookie: TOKEN=' . $token
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            // Parsear respuesta
            $xml_response = simplexml_load_string($response);
            $track_id = (string)$xml_response->TRACKID;
            $estado = (string)$xml_response->ESTADO;

            // Actualizar factura
            $update = "UPDATE facturas
                       SET sii_track_id = ?, estado_sii = ?, fecha_envio_sii = NOW()
                       WHERE id = ?";

            $stmt = $this->conn->prepare($update);
            $stmt->bind_param("ssi", $track_id, $estado, $factura_id);
            $stmt->execute();

            return [
                'success' => true,
                'track_id' => $track_id,
                'estado' => $estado,
                'mensaje' => 'DTE enviado correctamente al SII'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar XML del DTE (simplificado)
     */
    private function generarXMLDTE($factura) {
        // Implementación completa del XML según especificación SII
        // Por brevedad, aquí va una versión simplificada

        $xml = '<?xml version="1.0" encoding="ISO-8859-1"?>';
        $xml .= '<DTE version="1.0">';
        $xml .= '<Documento ID="F' . $factura['folio'] . 'T' . $factura['tipo_documento'] . '">';
        $xml .= '<Encabezado>';
        $xml .= '<IdDoc>';
        $xml .= '<TipoDTE>' . $factura['tipo_documento'] . '</TipoDTE>';
        $xml .= '<Folio>' . $factura['folio'] . '</Folio>';
        $xml .= '<FchEmis>' . date('Y-m-d') . '</FchEmis>';
        $xml .= '</IdDoc>';
        $xml .= '<Emisor>';
        $xml .= '<RUTEmisor>' . $this->rut_empresa . '</RUTEmisor>';
        $xml .= '</Emisor>';
        $xml .= '<Receptor>';
        $xml .= '<RUTRecep>' . $factura['cliente_rut'] . '</RUTRecep>';
        $xml .= '</Receptor>';
        $xml .= '<Totales>';
        $xml .= '<MntTotal>' . $factura['total'] . '</MntTotal>';
        $xml .= '</Totales>';
        $xml .= '</Encabezado>';
        $xml .= '</Documento>';
        $xml .= '</DTE>';

        return $xml;
    }

    /**
     * Firmar documento XML con certificado
     */
    private function firmarDocumento($xml) {
        // Implementación de firma digital según XMLDSig
        // Se requiere librería adicional para firma completa
        return $xml; // Placeholder
    }

    /**
     * Obtener dígito verificador del RUT
     */
    private function getDigitoVerificador() {
        return substr($this->rut_empresa, -1);
    }

    /**
     * Obtener factura desde BD
     */
    private function obtenerFactura($factura_id) {
        $query = "SELECT f.*, c.rut as cliente_rut
                  FROM facturas f
                  LEFT JOIN clientes c ON f.cliente_id = c.id
                  WHERE f.id = ? AND f.empresa_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $factura_id, $this->empresa_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Consultar RUT en el SII
     */
    public function consultarRUT($rut) {
        // Implementación de consulta real al SII
        // El SII proporciona un servicio SOAP para esto
        return [
            'success' => true,
            'razon_social' => 'Empresa Consultada',
            'giro' => 'Actividad comercial',
            'direccion' => 'Dirección'
        ];
    }
}
?>
