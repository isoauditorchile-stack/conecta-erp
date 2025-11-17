<?php
/**
 * ============================================
 * SIIClient - Cliente SOAP para Servicio de Impuestos Internos
 * ============================================
 * Conexión directa con servicios web del SII de Chile
 * Soporta envío de DTE, consulta de estados, obtención de CAF
 *
 * @author CONECTA ERP
 * @version 2.0
 */

class SIIClient {

    private $conn;
    private $empresa_id;
    private $ambiente; // 'certificacion' o 'produccion'

    // URLs de los servicios SII
    const SII_URL_CERTIFICACION = 'https://maullin.sii.cl/DTEWS/';
    const SII_URL_PRODUCCION = 'https://palena.sii.cl/DTEWS/';

    // URLs específicas de servicios
    const SII_UPLOAD_DTE = 'upload';
    const SII_QUERY_STATUS = 'queryEstUp';
    const SII_TOKEN = 'getToken';

    private $certificado_path;
    private $certificado_password;

    /**
     * Constructor
     */
    public function __construct($conn, $empresa_id) {
        $this->conn = $conn;
        $this->empresa_id = $empresa_id;

        // Cargar configuración
        $this->cargarConfiguracion();
    }

    /**
     * Cargar configuración desde BD
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

            if ($config['certificado_digital']) {
                $temp_file = tempnam(sys_get_temp_dir(), 'cert_');
                file_put_contents($temp_file, $config['certificado_digital']);
                $this->certificado_path = $temp_file;
            }
        }
    }

    /**
     * Obtener URL base según ambiente
     */
    private function getBaseURL() {
        return $this->ambiente == 'produccion'
            ? self::SII_URL_PRODUCCION
            : self::SII_URL_CERTIFICACION;
    }

    /**
     * Obtener token de autenticación del SII
     */
    public function obtenerToken() {
        $url = $this->getBaseURL() . self::SII_TOKEN;

        // Obtener semilla
        $semilla = $this->obtenerSemilla($url);

        // Firmar semilla con certificado
        $semilla_firmada = $this->firmarSemilla($semilla);

        // Solicitar token
        $token = $this->solicitarToken($url, $semilla_firmada);

        return $token;
    }

    /**
     * Obtener semilla del SII
     */
    private function obtenerSemilla($url) {
        $client = new SoapClient($url . '?wsdl', [
            'trace' => 1,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE
        ]);

        try {
            $response = $client->getSeed();

            if (!isset($response->SEMILLA)) {
                throw new Exception("No se pudo obtener semilla del SII");
            }

            return $response->SEMILLA;

        } catch (SoapFault $e) {
            throw new Exception("Error al obtener semilla: " . $e->getMessage());
        }
    }

    /**
     * Firmar semilla con certificado digital
     */
    private function firmarSemilla($semilla) {
        // Crear XML de semilla
        $xml = new DOMDocument('1.0', 'ISO-8859-1');
        $xml->formatOutput = false;

        $getToken = $xml->createElement('getToken');
        $xml->appendChild($getToken);

        $item = $xml->createElement('item');
        $semilla_node = $xml->createElement('Semilla', $semilla);
        $item->appendChild($semilla_node);
        $getToken->appendChild($item);

        $xml_string = $xml->saveXML();

        // Firmar con certificado
        if (!$this->certificado_path) {
            throw new Exception("No hay certificado digital configurado");
        }

        $cert_content = file_get_contents($this->certificado_path);
        openssl_pkcs12_read($cert_content, $cert_data, $this->certificado_password);

        if (!$cert_data) {
            throw new Exception("Error al leer certificado digital");
        }

        // Firmar XML
        $firma = '';
        openssl_sign($xml_string, $firma, $cert_data['pkey'], OPENSSL_ALGO_SHA1);

        // Codificar en base64
        $firma_b64 = base64_encode($firma);

        // Agregar firma al XML
        $signature = $xml->createElement('Signature', $firma_b64);
        $getToken->appendChild($signature);

        return $xml->saveXML();
    }

    /**
     * Solicitar token con semilla firmada
     */
    private function solicitarToken($url, $semilla_firmada) {
        $client = new SoapClient($url . '?wsdl', [
            'trace' => 1,
            'exceptions' => true
        ]);

        try {
            $response = $client->getToken(['pszXml' => $semilla_firmada]);

            if (!isset($response->TOKEN)) {
                throw new Exception("No se pudo obtener token del SII");
            }

            return $response->TOKEN;

        } catch (SoapFault $e) {
            throw new Exception("Error al obtener token: " . $e->getMessage());
        }
    }

    /**
     * Enviar DTE al SII
     *
     * @param string $xml_dte XML del DTE firmado
     * @param int $tipo_documento Código del tipo de documento
     * @return array Resultado con track_id
     */
    public function enviarDTE($xml_dte, $tipo_documento) {
        $url = $this->getBaseURL() . self::SII_UPLOAD_DTE;

        // Obtener token
        $token = $this->obtenerToken();

        // Obtener RUT de la empresa
        $query = "SELECT rut FROM empresas WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->empresa_id);
        $stmt->execute();
        $empresa = $stmt->get_result()->fetch_assoc();
        $rut_empresa = str_replace(['.', '-'], '', $empresa['rut']);

        // Crear EnvioDTE
        $envio_dte = $this->crearEnvioDTE($xml_dte, $rut_empresa);

        // Crear cliente SOAP
        $client = new SoapClient($url . '?wsdl', [
            'trace' => 1,
            'exceptions' => true,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ])
        ]);

        try {
            $response = $client->uploadDTE([
                'rutSender' => $rut_empresa,
                'dvSender' => $this->obtenerDV($rut_empresa),
                'rutCompany' => $rut_empresa,
                'dvCompany' => $this->obtenerDV($rut_empresa),
                'archivo' => base64_encode($envio_dte),
                'token' => $token
            ]);

            if (!isset($response->TRACKID)) {
                throw new Exception("No se recibió track ID del SII");
            }

            return [
                'track_id' => $response->TRACKID,
                'estado' => 'enviado',
                'fecha_envio' => date('Y-m-d H:i:s')
            ];

        } catch (SoapFault $e) {
            throw new Exception("Error al enviar DTE al SII: " . $e->getMessage());
        }
    }

    /**
     * Crear EnvioDTE (sobre que contiene uno o más DTE)
     */
    private function crearEnvioDTE($xml_dte, $rut_empresa) {
        $xml = new DOMDocument('1.0', 'ISO-8859-1');
        $xml->formatOutput = false;

        // Raíz
        $envioDTE = $xml->createElement('EnvioDTE');
        $envioDTE->setAttribute('xmlns', 'http://www.sii.cl/SiiDte');
        $envioDTE->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $envioDTE->setAttribute('xsi:schemaLocation', 'http://www.sii.cl/SiiDte EnvioDTE_v10.xsd');
        $envioDTE->setAttribute('version', '1.0');
        $xml->appendChild($envioDTE);

        // SetDTE
        $setDTE = $xml->createElement('SetDTE');
        $setDTE->setAttribute('ID', 'SetDTE');
        $envioDTE->appendChild($setDTE);

        // Caratula
        $caratula = $xml->createElement('Caratula');
        $caratula->appendChild($xml->createElement('RutEmisor', $rut_empresa));
        $caratula->appendChild($xml->createElement('RutEnvia', $rut_empresa));
        $caratula->appendChild($xml->createElement('RutReceptor', '60803000-K')); // RUT SII
        $caratula->appendChild($xml->createElement('FchResol', date('Y-m-d')));
        $caratula->appendChild($xml->createElement('NroResol', '0'));
        $caratula->appendChild($xml->createElement('TmstFirmaEnv', date('Y-m-d\TH:i:s')));
        $caratula->appendChild($xml->createElement('SubTotDTE'));
        $setDTE->appendChild($caratula);

        // Agregar DTE
        $dte_dom = new DOMDocument();
        $dte_dom->loadXML($xml_dte);
        $dte_node = $xml->importNode($dte_dom->documentElement, true);
        $setDTE->appendChild($dte_node);

        return $xml->saveXML();
    }

    /**
     * Consultar estado de DTE en SII
     *
     * @param string $track_id Track ID retornado al enviar
     * @return array Estado del DTE
     */
    public function consultarEstadoDTE($track_id) {
        $url = $this->getBaseURL() . self::SII_QUERY_STATUS;

        // Obtener token
        $token = $this->obtenerToken();

        // Obtener RUT de la empresa
        $query = "SELECT rut FROM empresas WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->empresa_id);
        $stmt->execute();
        $empresa = $stmt->get_result()->fetch_assoc();
        $rut_empresa = str_replace(['.', '-'], '', $empresa['rut']);

        // Crear cliente SOAP
        $client = new SoapClient($url . '?wsdl', [
            'trace' => 1,
            'exceptions' => true,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ])
        ]);

        try {
            $response = $client->queryEstUp([
                'RutConsultante' => $rut_empresa,
                'DvConsultante' => $this->obtenerDV($rut_empresa),
                'trackId' => $track_id,
                'token' => $token
            ]);

            // Parsear respuesta
            $estado = $this->parsearEstadoDTE($response);

            return $estado;

        } catch (SoapFault $e) {
            throw new Exception("Error al consultar estado DTE: " . $e->getMessage());
        }
    }

    /**
     * Parsear respuesta de estado DTE
     */
    private function parsearEstadoDTE($response) {
        // Extraer estado de la respuesta XML
        $xml = simplexml_load_string($response);

        $estado_codigo = (string)$xml->xpath('//ESTADO')[0];
        $glosa = (string)$xml->xpath('//GLOSA')[0];

        // Mapear código de estado
        $estado_map = [
            'EPR' => 'aceptado',      // Envío procesado
            'REC' => 'rechazado',     // Rechazado
            'RCT' => 'rechazado',     // Rechazado con reparos
            'RDC' => 'rechazado',     // Rechazado por DTE con errores
            'PRD' => 'aceptado',      // Procesado
            'SOK' => 'aceptado',      // Envío OK
        ];

        $estado = $estado_map[$estado_codigo] ?? 'reparo';

        return [
            'estado' => $estado,
            'codigo' => $estado_codigo,
            'glosa' => $glosa,
            'fecha_consulta' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Obtener dígito verificador de un RUT
     */
    private function obtenerDV($rut) {
        $rut = preg_replace('/[^0-9]/', '', $rut);
        $suma = 0;
        $multiplo = 2;

        for ($i = strlen($rut) - 1; $i >= 0; $i--) {
            $suma += $rut[$i] * $multiplo;
            $multiplo = $multiplo < 7 ? $multiplo + 1 : 2;
        }

        $dv = 11 - ($suma % 11);
        if ($dv == 11) return '0';
        if ($dv == 10) return 'K';
        return (string)$dv;
    }

    /**
     * Validar respuesta del SII
     */
    private function validarRespuestaSII($response) {
        if (isset($response->ESTADO) && $response->ESTADO == 'ERROR') {
            throw new Exception("Error del SII: " . ($response->GLOSA ?? 'Error desconocido'));
        }

        return true;
    }

    /**
     * Obtener CAF desde el SII (para implementar)
     * Requiere acceso al portal MIPYME del SII
     */
    public function obtenerCAFDesdePortal($tipo_documento, $cantidad_folios) {
        throw new Exception("Función no implementada. Los CAF deben descargarse desde el portal SII manualmente.");
        // En el futuro se puede implementar scraping o API oficial si el SII la habilita
    }

    /**
     * Registrar log de comunicación con SII
     */
    private function registrarLog($accion, $request, $response, $exito) {
        $query = "INSERT INTO logs_sii (empresa_id, accion, request, response, exito, fecha)
                  VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "isssi",
            $this->empresa_id,
            $accion,
            json_encode($request),
            json_encode($response),
            $exito
        );
        $stmt->execute();
    }

    /**
     * Destructor
     */
    public function __destruct() {
        if ($this->certificado_path && file_exists($this->certificado_path)) {
            unlink($this->certificado_path);
        }
    }
}
