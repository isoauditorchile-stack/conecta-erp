<?php
/**
 * SERVICIOS EXTERNOS - SII (Servicio de Impuestos Internos Chile)
 * Integración con sistema de facturación electrónica SII
 * - Envío de DTEs (Documentos Tributarios Electrónicos)
 * - Consulta de estado
 * - Validación de RUT
 */

class SIIClient {
    private $ambiente; // 'certificacion' o 'produccion'
    private $rut_emisor;
    private $certificado_digital;
    private $clave_certificado;

    const URL_CERTIFICACION = 'https://maullin.sii.cl/DTEWS/';
    const URL_PRODUCCION = 'https://palena.sii.cl/DTEWS/';

    /**
     * Constructor
     */
    public function __construct($config) {
        $this->ambiente = $config['ambiente'] ?? 'certificacion';
        $this->rut_emisor = $config['rut_emisor'];
        $this->certificado_digital = $config['certificado_digital'];
        $this->clave_certificado = $config['clave_certificado'];
    }

    /**
     * Obtener URL base según ambiente
     */
    private function getBaseURL() {
        return $this->ambiente === 'produccion' ? self::URL_PRODUCCION : self::URL_CERTIFICACION;
    }

    /**
     * Validar RUT chileno
     */
    public function validarRUT($rut) {
        // Limpiar RUT
        $rut = preg_replace('/[^0-9kK]/', '', strtoupper($rut));

        if (strlen($rut) < 2) {
            return false;
        }

        $dv = substr($rut, -1);
        $numero = substr($rut, 0, -1);

        // Calcular dígito verificador
        $suma = 0;
        $multiplo = 2;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $suma += $numero[$i] * $multiplo;
            $multiplo = $multiplo < 7 ? $multiplo + 1 : 2;
        }

        $resto = $suma % 11;
        $dv_calculado = 11 - $resto;

        if ($dv_calculado == 11) {
            $dv_calculado = '0';
        } elseif ($dv_calculado == 10) {
            $dv_calculado = 'K';
        }

        return $dv == $dv_calculado;
    }

    /**
     * Firmar documento XML con certificado digital
     */
    private function firmarXML($xml) {
        // Cargar certificado
        if (!openssl_pkcs12_read($this->certificado_digital, $certs, $this->clave_certificado)) {
            throw new Exception('Error al leer certificado digital');
        }

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        // Crear firma XML (XMLDSig)
        // Aquí iría la implementación completa de firma digital XML según estándar SII
        // Por simplicidad, se muestra la estructura básica

        $signature = $dom->createElement('Signature');
        $signature->setAttribute('xmlns', 'http://www.w3.org/2000/09/xmldsig#');

        // SignedInfo
        $signedInfo = $dom->createElement('SignedInfo');
        // ... agregar elementos de firma

        // Firmar con clave privada
        $private_key = $certs['pkey'];
        $data_to_sign = $dom->saveXML($signedInfo);

        openssl_sign($data_to_sign, $signature_value, $private_key, OPENSSL_ALGO_SHA1);

        // Agregar SignatureValue
        $signatureValue = $dom->createElement('SignatureValue', base64_encode($signature_value));
        $signature->appendChild($signatureValue);

        // Agregar KeyInfo con certificado
        $keyInfo = $dom->createElement('KeyInfo');
        $x509Data = $dom->createElement('X509Data');
        $x509Certificate = $dom->createElement('X509Certificate', base64_encode($certs['cert']));
        $x509Data->appendChild($x509Certificate);
        $keyInfo->appendChild($x509Data);
        $signature->appendChild($keyInfo);

        $dom->documentElement->appendChild($signature);

        return $dom->saveXML();
    }

    /**
     * Generar DTE (Documento Tributario Electrónico)
     */
    public function generarDTE($tipo_dte, $datos_factura) {
        // Tipos de DTE:
        // 33: Factura Electrónica
        // 34: Factura Exenta
        // 39: Boleta Electrónica
        // 41: Boleta Exenta
        // 52: Guía de Despacho
        // 56: Nota de Débito
        // 61: Nota de Crédito

        $xml = '<?xml version="1.0" encoding="ISO-8859-1"?>';
        $xml .= '<DTE version="1.0">';
        $xml .= '<Documento ID="DOC' . $datos_factura['folio'] . '">';

        // Encabezado
        $xml .= '<Encabezado>';
        $xml .= '<IdDoc>';
        $xml .= '<TipoDTE>' . $tipo_dte . '</TipoDTE>';
        $xml .= '<Folio>' . $datos_factura['folio'] . '</Folio>';
        $xml .= '<FchEmis>' . $datos_factura['fecha_emision'] . '</FchEmis>';
        $xml .= '</IdDoc>';

        // Emisor
        $xml .= '<Emisor>';
        $xml .= '<RUTEmisor>' . $this->rut_emisor . '</RUTEmisor>';
        $xml .= '<RznSoc>' . htmlspecialchars($datos_factura['razon_social_emisor']) . '</RznSoc>';
        $xml .= '<GiroEmis>' . htmlspecialchars($datos_factura['giro_emisor']) . '</GiroEmis>';
        $xml .= '<Acteco>' . $datos_factura['actividad_economica'] . '</Acteco>';
        $xml .= '<DirOrigen>' . htmlspecialchars($datos_factura['direccion_emisor']) . '</DirOrigen>';
        $xml .= '<CmnaOrigen>' . htmlspecialchars($datos_factura['comuna_emisor']) . '</CmnaOrigen>';
        $xml .= '</Emisor>';

        // Receptor
        $xml .= '<Receptor>';
        $xml .= '<RUTRecep>' . $datos_factura['rut_receptor'] . '</RUTRecep>';
        $xml .= '<RznSocRecep>' . htmlspecialchars($datos_factura['razon_social_receptor']) . '</RznSocRecep>';
        $xml .= '<DirRecep>' . htmlspecialchars($datos_factura['direccion_receptor']) . '</DirRecep>';
        $xml .= '<CmnaRecep>' . htmlspecialchars($datos_factura['comuna_receptor']) . '</CmnaRecep>';
        $xml .= '</Receptor>';

        // Totales
        $xml .= '<Totales>';
        $xml .= '<MntNeto>' . number_format($datos_factura['neto'], 0, '', '') . '</MntNeto>';
        $xml .= '<TasaIVA>' . $datos_factura['tasa_iva'] . '</TasaIVA>';
        $xml .= '<IVA>' . number_format($datos_factura['iva'], 0, '', '') . '</IVA>';
        $xml .= '<MntTotal>' . number_format($datos_factura['total'], 0, '', '') . '</MntTotal>';
        $xml .= '</Totales>';

        $xml .= '</Encabezado>';

        // Detalle (items)
        foreach ($datos_factura['items'] as $idx => $item) {
            $xml .= '<Detalle>';
            $xml .= '<NroLinDet>' . ($idx + 1) . '</NroLinDet>';
            $xml .= '<NmbItem>' . htmlspecialchars($item['nombre']) . '</NmbItem>';
            $xml .= '<QtyItem>' . $item['cantidad'] . '</QtyItem>';
            $xml .= '<PrcItem>' . number_format($item['precio_unitario'], 0, '', '') . '</PrcItem>';
            $xml .= '<MontoItem>' . number_format($item['monto_total'], 0, '', '') . '</MontoItem>';
            $xml .= '</Detalle>';
        }

        $xml .= '</Documento>';
        $xml .= '</DTE>';

        // Firmar XML
        $xml_firmado = $this->firmarXML($xml);

        return $xml_firmado;
    }

    /**
     * Enviar DTE al SII
     */
    public function enviarDTE($xml_dte, $rut_empresa, $rut_enviador) {
        $url = $this->getBaseURL() . 'services/wsRPETCEnvioDTE';

        // Crear sobre (EnvioDTE)
        $xml_sobre = '<?xml version="1.0" encoding="ISO-8859-1"?>';
        $xml_sobre .= '<EnvioDTE xmlns="http://www.sii.cl/SiiDte" version="1.0">';
        $xml_sobre .= '<SetDTE ID="SET' . time() . '">';
        $xml_sobre .= $xml_dte;
        $xml_sobre .= '</SetDTE>';
        $xml_sobre .= '</EnvioDTE>';

        // Firmar sobre
        $xml_sobre_firmado = $this->firmarXML($xml_sobre);

        // Enviar vía SOAP
        $client = new SoapClient($url . '?wsdl', ['trace' => 1]);

        try {
            $response = $client->enviarDTE([
                'rutEmisor' => $rut_empresa,
                'rutEnvia' => $rut_enviador,
                'dvEmisor' => substr($rut_empresa, -1),
                'dvEnvia' => substr($rut_enviador, -1),
                'archivo' => base64_encode($xml_sobre_firmado)
            ]);

            return [
                'success' => true,
                'track_id' => $response->TRACKID,
                'estado' => $response->ESTADO,
                'mensaje' => $response->GLOSA
            ];

        } catch (SoapFault $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Consultar estado de DTE
     */
    public function consultarEstadoDTE($track_id) {
        $url = $this->getBaseURL() . 'services/QueryEstDte';

        $client = new SoapClient($url . '?wsdl');

        try {
            $response = $client->getEstDte([
                'RutConsultante' => $this->rut_emisor,
                'DvConsultante' => substr($this->rut_emisor, -1),
                'TrackId' => $track_id
            ]);

            return [
                'success' => true,
                'estado' => $response->ESTADO,
                'glosa' => $response->GLOSA_ESTADO,
                'numero_atencion' => $response->NUM_ATENCION ?? null
            ];

        } catch (SoapFault $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener token de autenticación SII
     */
    public function obtenerToken() {
        $url = $this->getBaseURL() . 'GetTokenFromSeed';

        // Obtener semilla
        $client = new SoapClient($url . '?wsdl');
        $seed_response = $client->getSeed();
        $seed = $seed_response->SEMILLA;

        // Firmar semilla
        if (!openssl_pkcs12_read($this->certificado_digital, $certs, $this->clave_certificado)) {
            throw new Exception('Error al leer certificado digital');
        }

        $seed_firmada = '';
        openssl_sign($seed, $seed_firmada, $certs['pkey'], OPENSSL_ALGO_SHA1);

        // Obtener token
        $token_response = $client->getToken([
            'Semilla' => base64_encode($seed_firmada)
        ]);

        return $token_response->TOKEN;
    }
}
