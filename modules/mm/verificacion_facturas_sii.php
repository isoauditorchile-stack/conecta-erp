<?php
/**
 * MODULO: Verificacion de Facturas SII Chile
 * Sistema completo de verificacion y conciliacion con SII
 * Sin dependencias - Sin AJAX - Sin sidebar - UTF-8
 */

$db_host = 'localhost';
$db_name = 'conectae_conectaerpbd';
$db_user = 'conectae_conectaerpuser';
$db_pass = 'pt125824caraud';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('Error de conexion: ' . $e->getMessage());
}

session_start();

// ============================================================================
// CLASE SII CHILE - CONEXION REAL CON API SII
// ============================================================================
class SIIChile {
    private $pdo;
    private $ambiente;
    private $rutEmpresa;
    private $certificado;
    private $claveCertificado;
    private $token;

    // URLs SII
    private $urls = [
        'certificacion' => [
            'seed' => 'https://maullin.sii.cl/DTEWS/CrSeed.jws?WSDL',
            'token' => 'https://maullin.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL',
            'rcv' => 'https://www4c.sii.cl/registrocompaboraboraliloaboraborRcvws/reaborLiRcvWS'
        ],
        'produccion' => [
            'seed' => 'https://palena.sii.cl/DTEWS/CrSeed.jws?WSDL',
            'token' => 'https://palena.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL',
            'rcv' => 'https://www.sii.cl/cgi_rcv/RCVConsulta.cgi'
        ]
    ];

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->cargarConfiguracion();
    }

    private function cargarConfiguracion() {
        $stmt = $this->pdo->query("SELECT * FROM sii_parametros WHERE activo=1 LIMIT 1");
        $config = $stmt->fetch();
        if ($config) {
            $this->ambiente = $config['ambiente_sii'] ?: 'certificacion';
            $this->rutEmpresa = $config['rut_empresa'] . '-' . $config['dv_empresa'];
            $this->certificado = $config['certificado_digital'];
            $this->claveCertificado = $config['clave_certificado'];
        }
    }

    public function getSeed() {
        $url = $this->urls[$this->ambiente]['seed'];
        $soap = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <getSeed/>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = $this->curlRequest($url, $soap, ['Content-Type: text/xml; charset=utf-8', 'SOAPAction: ""'], 'POST');

        if ($response && preg_match('/<SEMILLA>([^<]+)<\/SEMILLA>/', $response, $matches)) {
            return $matches[1];
        }
        return false;
    }

    public function getToken() {
        $seed = $this->getSeed();
        if (!$seed) {
            $this->log('error', '', 'error', 'No se pudo obtener semilla del SII');
            return false;
        }

        // Crear XML con semilla firmada
        $xmlToSign = '<?xml version="1.0" encoding="UTF-8"?>
        <getToken>
            <item>
                <Semilla>' . $seed . '</Semilla>
            </item>
        </getToken>';

        // Firmar XML con certificado digital
        $signedXml = $this->firmarXML($xmlToSign);
        if (!$signedXml) {
            $this->log('error', '', 'error', 'Error al firmar XML con certificado');
            return false;
        }

        $url = $this->urls[$this->ambiente]['token'];
        $soap = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <getToken>
                    <pszXml><![CDATA[' . $signedXml . ']]></pszXml>
                </getToken>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = $this->curlRequest($url, $soap, ['Content-Type: text/xml; charset=utf-8', 'SOAPAction: ""'], 'POST');

        if ($response && preg_match('/<TOKEN>([^<]+)<\/TOKEN>/', $response, $matches)) {
            $this->token = $matches[1];
            $this->log('conexion', '', 'conectado', 'Token obtenido exitosamente');
            return $this->token;
        }

        $this->log('error', '', 'error', 'No se pudo obtener token del SII');
        return false;
    }

    private function firmarXML($xml) {
        if (empty($this->certificado) || empty($this->claveCertificado)) {
            return false;
        }

        // Decodificar certificado base64
        $certData = base64_decode($this->certificado);
        if (!$certData) {
            return false;
        }

        // Cargar certificado PKCS12
        $certs = [];
        if (!openssl_pkcs12_read($certData, $certs, $this->claveCertificado)) {
            return false;
        }

        $privateKey = $certs['pkey'];
        $certificate = $certs['cert'];

        // Crear firma
        $digest = base64_encode(hash('sha1', $xml, true));

        // Firmar
        openssl_sign($xml, $signature, $privateKey, OPENSSL_ALGO_SHA1);
        $signatureB64 = base64_encode($signature);

        // Obtener info del certificado
        $certInfo = openssl_x509_parse($certificate);
        $serialNumber = $certInfo['serialNumber'] ?? '';

        // Construir XML firmado
        $signedXml = '<?xml version="1.0" encoding="UTF-8"?>
        <getToken>
            <item>
                <Semilla>' . preg_match('/<Semilla>([^<]+)<\/Semilla>/', $xml, $m) ? $m[1] : '' . '</Semilla>
            </item>
            <Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
                <SignedInfo>
                    <CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
                    <SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
                    <Reference URI="">
                        <Transforms>
                            <Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
                        </Transforms>
                        <DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
                        <DigestValue>' . $digest . '</DigestValue>
                    </Reference>
                </SignedInfo>
                <SignatureValue>' . $signatureB64 . '</SignatureValue>
                <KeyInfo>
                    <X509Data>
                        <X509Certificate>' . base64_encode($certificate) . '</X509Certificate>
                    </X509Data>
                </KeyInfo>
            </Signature>
        </getToken>';

        return $signedXml;
    }

    public function consultarRCV($periodo, $tipoDte = null) {
        if (!$this->token) {
            if (!$this->getToken()) {
                return ['error' => 'No se pudo autenticar con SII'];
            }
        }

        $rut = explode('-', $this->rutEmpresa);
        $rutNum = $rut[0];
        $dv = $rut[1] ?? '';

        $url = $this->urls[$this->ambiente]['rcv'];

        // Parametros para consulta RCV
        $params = [
            'TOKEN' => $this->token,
            'RUT_RECEPTOR' => $rutNum,
            'DV_RECEPTOR' => $dv,
            'PERIODO' => $periodo,
            'ESTADO' => 'REGISTRO',
            'OPERACION' => 'CONSULTAR'
        ];

        if ($tipoDte) {
            $params['TIPO_DTE'] = $tipoDte;
        }

        $response = $this->curlRequest($url, http_build_query($params), ['Content-Type: application/x-www-form-urlencoded', 'Cookie: TOKEN=' . $this->token], 'POST');

        return $this->parseRCVResponse($response);
    }

    private function parseRCVResponse($response) {
        $result = ['dtes' => [], 'total' => 0, 'error' => null];

        if (!$response) {
            $result['error'] = 'Sin respuesta del SII';
            return $result;
        }

        // Intentar parsear como JSON
        $json = json_decode($response, true);
        if ($json && isset($json['data'])) {
            foreach ($json['data'] as $doc) {
                $result['dtes'][] = [
                    'folio' => $doc['folio'] ?? '',
                    'tipo_dte' => $doc['tipo_dte'] ?? 33,
                    'rut_emisor' => $doc['rut_emisor'] ?? '',
                    'razon_social' => $doc['razon_social'] ?? '',
                    'fecha_emision' => $doc['fecha_emision'] ?? '',
                    'monto_neto' => $doc['monto_neto'] ?? 0,
                    'monto_iva' => $doc['monto_iva'] ?? 0,
                    'monto_total' => $doc['monto_total'] ?? 0,
                    'estado' => $doc['estado'] ?? 'REGISTRO'
                ];
            }
            $result['total'] = count($result['dtes']);
        }
        // Intentar parsear como XML
        elseif (strpos($response, '<?xml') !== false) {
            $xml = @simplexml_load_string($response);
            if ($xml) {
                foreach ($xml->xpath('//DTE') as $dte) {
                    $result['dtes'][] = [
                        'folio' => (string)$dte->Folio,
                        'tipo_dte' => (int)$dte->TipoDTE,
                        'rut_emisor' => (string)$dte->RutEmisor,
                        'razon_social' => (string)$dte->RznSoc,
                        'fecha_emision' => (string)$dte->FchEmis,
                        'monto_neto' => (float)$dte->MntNeto,
                        'monto_iva' => (float)$dte->MntIVA,
                        'monto_total' => (float)$dte->MntTotal,
                        'estado' => (string)$dte->Estado
                    ];
                }
                $result['total'] = count($result['dtes']);
            }
        }

        return $result;
    }

    public function sincronizarPeriodo($periodoDesde, $periodoHasta, $descargaId) {
        $tiposDte = [33, 34, 43, 46, 52, 56, 61];
        $totalDescargados = 0;
        $totalNuevos = 0;
        $errores = [];

        // Convertir periodos a formato YYYYMM
        $desde = str_replace('-', '', substr($periodoDesde, 0, 7));
        $hasta = str_replace('-', '', substr($periodoHasta, 0, 7));

        $periodoActual = $desde;
        while ($periodoActual <= $hasta) {
            foreach ($tiposDte as $tipo) {
                $resultado = $this->consultarRCV($periodoActual, $tipo);

                if (isset($resultado['error'])) {
                    $errores[] = "Periodo $periodoActual Tipo $tipo: " . $resultado['error'];
                    continue;
                }

                foreach ($resultado['dtes'] as $dte) {
                    $guardado = $this->guardarDTE($dte, $descargaId);
                    if ($guardado === 'nuevo') {
                        $totalNuevos++;
                    }
                    $totalDescargados++;
                }
            }

            // Siguiente periodo
            $year = substr($periodoActual, 0, 4);
            $month = (int)substr($periodoActual, 4, 2) + 1;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
            $periodoActual = $year . str_pad($month, 2, '0', STR_PAD_LEFT);
        }

        return [
            'descargados' => $totalDescargados,
            'nuevos' => $totalNuevos,
            'errores' => $errores
        ];
    }

    private function guardarDTE($dte, $descargaId) {
        // Verificar si ya existe
        $stmt = $this->pdo->prepare("SELECT id FROM sii_dte_compras WHERE folio_dte=? AND tipo_dte=? AND rut_proveedor=?");
        $stmt->execute([$dte['folio'], $dte['tipo_dte'], $dte['rut_emisor']]);

        if ($stmt->fetch()) {
            return 'existente';
        }

        $tiposNombre = [
            33 => 'Factura Electronica', 34 => 'Factura No Afecta o Exenta',
            43 => 'Liquidacion Factura', 46 => 'Factura de Compra',
            52 => 'Guia de Despacho', 56 => 'Nota de Debito', 61 => 'Nota de Credito'
        ];

        $rutParts = explode('-', $dte['rut_emisor']);

        $stmt = $this->pdo->prepare("INSERT INTO sii_dte_compras
            (folio_dte, tipo_dte, tipo_dte_nombre, rut_proveedor, dv_proveedor, razon_social_proveedor,
             fecha_emision, monto_neto, monto_iva, monto_total, estado_dte_sii, descarga_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aceptado', ?)");

        $stmt->execute([
            $dte['folio'],
            $dte['tipo_dte'],
            $tiposNombre[$dte['tipo_dte']] ?? 'Otro',
            $rutParts[0] ?? $dte['rut_emisor'],
            $rutParts[1] ?? '',
            $dte['razon_social'],
            $dte['fecha_emision'],
            $dte['monto_neto'],
            $dte['monto_iva'],
            $dte['monto_total'],
            $descargaId
        ]);

        $dteId = $this->pdo->lastInsertId();

        // Crear registros relacionados
        $this->pdo->exec("INSERT INTO sii_verificacion (dte_sii_id, existe_en_sii, estado_verificacion) VALUES ($dteId, 1, 'pendiente')");
        $this->pdo->exec("INSERT INTO sii_vinculacion_erp (dte_sii_id, estado_vinculacion) VALUES ($dteId, 'sin_vincular')");

        return 'nuevo';
    }

    private function curlRequest($url, $data, $headers, $method = 'POST') {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => 'CONECTA-ERP/1.0'
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            $this->log('curl_error', '', 'error', "Error CURL: $error");
            return false;
        }

        return $response;
    }

    private function log($tipo, $periodo, $estado, $mensaje) {
        $stmt = $this->pdo->prepare("INSERT INTO sii_conexion_log (estado_conexion, fecha_conexion, periodo_sincronizado, detalle_log) VALUES (?, NOW(), ?, ?)");
        $stmt->execute([$estado, $periodo, $mensaje]);
    }

    public function probarConexion() {
        $seed = $this->getSeed();
        if ($seed) {
            $this->log('test', '', 'conectado', 'Prueba de conexion exitosa - Seed obtenido: ' . substr($seed, 0, 10) . '...');
            return ['success' => true, 'message' => 'Conexion exitosa con SII'];
        }
        return ['success' => false, 'message' => 'No se pudo conectar con SII'];
    }
}
// ============================================================================
// FIN CLASE SII CHILE
// ============================================================================

// Crear tablas
$pdo->exec("
CREATE TABLE IF NOT EXISTS sii_parametros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rut_empresa VARCHAR(20) NOT NULL,
    dv_empresa VARCHAR(1),
    razon_social VARCHAR(255),
    ambiente_sii ENUM('certificacion','produccion') DEFAULT 'certificacion',
    certificado_digital TEXT,
    clave_certificado VARCHAR(255),
    usuario_sii VARCHAR(100),
    clave_sii VARCHAR(255),
    correo_notificaciones VARCHAR(255),
    horario_sincronizacion VARCHAR(50),
    sincronizacion_manual TINYINT(1) DEFAULT 1,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_conexion_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estado_conexion ENUM('conectado','desconectado','error') DEFAULT 'desconectado',
    fecha_conexion DATETIME,
    periodo_sincronizado VARCHAR(20),
    reintentos INT DEFAULT 0,
    tiempo_respuesta INT DEFAULT 0,
    mensaje_error TEXT,
    detalle_log TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_descargas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    periodo_desde VARCHAR(10),
    periodo_hasta VARCHAR(10),
    tipo_descarga ENUM('pendientes','todas','nuevas') DEFAULT 'todas',
    facturas_nuevas INT DEFAULT 0,
    facturas_descargadas INT DEFAULT 0,
    facturas_modificadas INT DEFAULT 0,
    estado_descarga ENUM('exitoso','con_errores','parcial','en_proceso') DEFAULT 'en_proceso',
    usuario_descarga INT,
    fecha_inicio DATETIME,
    fecha_fin DATETIME,
    errores TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_dte_compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio_dte VARCHAR(50) NOT NULL,
    tipo_dte INT NOT NULL,
    tipo_dte_nombre VARCHAR(100),
    rut_proveedor VARCHAR(20) NOT NULL,
    dv_proveedor VARCHAR(1),
    razon_social_proveedor VARCHAR(255),
    giro_proveedor VARCHAR(255),
    fecha_emision DATE,
    fecha_recepcion_sii DATE,
    monto_neto DECIMAL(15,2) DEFAULT 0,
    monto_exento DECIMAL(15,2) DEFAULT 0,
    monto_iva DECIMAL(15,2) DEFAULT 0,
    otros_impuestos DECIMAL(15,2) DEFAULT 0,
    monto_total DECIMAL(15,2) DEFAULT 0,
    moneda VARCHAR(10) DEFAULT 'CLP',
    estado_dte_sii ENUM('aceptado','rechazado','pendiente','no_informado') DEFAULT 'pendiente',
    xml_dte_original LONGTEXT,
    pdf_dte LONGTEXT,
    descarga_id INT,
    fecha_descarga DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_folio_tipo_rut (folio_dte, tipo_dte, rut_proveedor),
    INDEX idx_periodo (fecha_emision),
    INDEX idx_proveedor (rut_proveedor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_vinculacion_erp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dte_sii_id INT NOT NULL,
    factura_compra_interna VARCHAR(50),
    proveedor_erp_id INT,
    orden_compra VARCHAR(50),
    recepcion_mercaderia VARCHAR(50),
    centro_costo VARCHAR(50),
    bodega VARCHAR(50),
    cuenta_contable VARCHAR(50),
    condicion_pago VARCHAR(100),
    fecha_registro_erp DATE,
    usuario_registro_erp INT,
    estado_vinculacion ENUM('vinculada','pendiente','sin_vincular') DEFAULT 'sin_vincular',
    fecha_vinculacion DATETIME,
    FOREIGN KEY (dte_sii_id) REFERENCES sii_dte_compras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_verificacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dte_sii_id INT NOT NULL,
    existe_en_erp TINYINT(1) DEFAULT 0,
    existe_en_sii TINYINT(1) DEFAULT 1,
    rut_coincide TINYINT(1) DEFAULT 0,
    monto_total_coincide TINYINT(1) DEFAULT 0,
    monto_iva_coincide TINYINT(1) DEFAULT 0,
    monto_neto_coincide TINYINT(1) DEFAULT 0,
    fecha_coincide TINYINT(1) DEFAULT 0,
    tipo_dte_coincide TINYINT(1) DEFAULT 0,
    folio_duplicado TINYINT(1) DEFAULT 0,
    diferencia_monto DECIMAL(15,2) DEFAULT 0,
    diferencia_iva DECIMAL(15,2) DEFAULT 0,
    tolerancia_monto DECIMAL(15,2) DEFAULT 1.00,
    tolerancia_fecha INT DEFAULT 3,
    estado_verificacion ENUM('conciliada','con_diferencias','solo_sii','solo_erp','pendiente','observada','rechazada') DEFAULT 'pendiente',
    fecha_verificacion DATETIME,
    usuario_verificacion INT,
    FOREIGN KEY (dte_sii_id) REFERENCES sii_dte_compras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_diferencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dte_sii_id INT NOT NULL,
    tipo_diferencia ENUM('monto','iva','fecha','proveedor','folio','no_en_sii','no_en_erp','duplicado','otro') DEFAULT 'otro',
    detalle_diferencia TEXT,
    valor_sii VARCHAR(255),
    valor_erp VARCHAR(255),
    estado_revision ENUM('pendiente','en_revision','resuelta') DEFAULT 'pendiente',
    responsable_revision VARCHAR(255),
    fecha_revision DATE,
    accion_tomada ENUM('ajuste_erp','reclamo_proveedor','nota_credito','rechazo_factura','comentario_interno','sin_accion') DEFAULT 'sin_accion',
    observacion_interna TEXT,
    observacion_proveedor TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dte_sii_id) REFERENCES sii_dte_compras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_acciones_factura (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dte_sii_id INT NOT NULL,
    accion ENUM('conciliar','observar','rechazar','vincular_erp','actualizar_erp','bloquear_pago','desbloquear_pago') NOT NULL,
    estado_anterior VARCHAR(100),
    estado_nuevo VARCHAR(100),
    observacion TEXT,
    usuario_accion INT,
    fecha_accion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dte_sii_id) REFERENCES sii_dte_compras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_facturas_erp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_factura VARCHAR(50) NOT NULL,
    tipo_documento INT,
    rut_proveedor VARCHAR(20),
    razon_social_proveedor VARCHAR(255),
    fecha_emision DATE,
    fecha_registro DATE,
    monto_neto DECIMAL(15,2) DEFAULT 0,
    monto_iva DECIMAL(15,2) DEFAULT 0,
    monto_total DECIMAL(15,2) DEFAULT 0,
    estado ENUM('registrada','pagada','anulada') DEFAULT 'registrada',
    verificada_sii TINYINT(1) DEFAULT 0,
    dte_sii_id INT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_numero (numero_factura),
    INDEX idx_rut (rut_proveedor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabla_origen VARCHAR(100),
    registro_id INT,
    accion VARCHAR(50),
    datos_anteriores TEXT,
    datos_nuevos TEXT,
    usuario_id INT,
    ip_usuario VARCHAR(50),
    fecha_accion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_nombre (nombre_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_permiso VARCHAR(100) NOT NULL,
    nombre_permiso VARCHAR(255) NOT NULL,
    descripcion TEXT,
    modulo VARCHAR(100) DEFAULT 'verificacion_sii',
    activo TINYINT(1) DEFAULT 1,
    UNIQUE KEY idx_codigo (codigo_permiso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_roles_permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_id INT NOT NULL,
    permiso_id INT NOT NULL,
    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES sii_roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permiso_id) REFERENCES sii_permisos(id) ON DELETE CASCADE,
    UNIQUE KEY idx_rol_permiso (rol_id, permiso_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_usuarios_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    rol_id INT NOT NULL,
    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES sii_roles(id) ON DELETE CASCADE,
    UNIQUE KEY idx_usuario_rol (usuario_id, rol_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO sii_roles (nombre_rol, descripcion) VALUES
('administrador', 'Acceso total al modulo'),
('contabilidad', 'Acceso a conciliacion y reportes'),
('compras', 'Acceso a facturas y vinculacion'),
('auditoria', 'Solo lectura y reportes');

INSERT IGNORE INTO sii_permisos (codigo_permiso, nombre_permiso, descripcion) VALUES
('ver_facturas_sii', 'Ver Facturas SII', 'Permite ver facturas descargadas del SII'),
('descargar_sii', 'Descargar desde SII', 'Permite ejecutar descargas desde el SII'),
('modificar_vinculo_erp', 'Modificar Vinculo ERP', 'Permite vincular facturas SII con ERP'),
('marcar_conciliacion', 'Marcar Conciliacion', 'Permite marcar facturas como conciliadas'),
('rechazar_facturas', 'Rechazar Facturas', 'Permite rechazar facturas'),
('gestionar_diferencias', 'Gestionar Diferencias', 'Permite registrar y resolver diferencias'),
('ver_reportes', 'Ver Reportes', 'Permite acceder a reportes'),
('configurar_parametros', 'Configurar Parametros', 'Permite modificar parametros SII'),
('gestionar_permisos', 'Gestionar Permisos', 'Permite administrar roles y permisos');
");

$vista = isset($_GET['vista']) ? $_GET['vista'] : 'dashboard';
$mensaje = '';
$tipo_mensaje = '';

// Tipos DTE Chile
$tipos_dte = [
    33 => 'Factura Electronica',
    34 => 'Factura No Afecta o Exenta',
    43 => 'Liquidacion Factura Electronica',
    46 => 'Factura de Compra Electronica',
    52 => 'Guia de Despacho Electronica',
    56 => 'Nota de Debito Electronica',
    61 => 'Nota de Credito Electronica'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    // Guardar parametros SII
    if ($accion === 'guardar_parametros') {
        $existe = $pdo->query("SELECT id FROM sii_parametros LIMIT 1")->fetch();
        if ($existe) {
            $stmt = $pdo->prepare("UPDATE sii_parametros SET rut_empresa=?, dv_empresa=?, razon_social=?, ambiente_sii=?, usuario_sii=?, clave_sii=?, correo_notificaciones=?, horario_sincronizacion=?, sincronizacion_manual=? WHERE id=?");
            $stmt->execute([$_POST['rut_empresa'], $_POST['dv_empresa'], $_POST['razon_social'], $_POST['ambiente_sii'], $_POST['usuario_sii'], $_POST['clave_sii'], $_POST['correo_notificaciones'], $_POST['horario_sincronizacion'], isset($_POST['sincronizacion_manual']) ? 1 : 0, $existe['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO sii_parametros (rut_empresa, dv_empresa, razon_social, ambiente_sii, usuario_sii, clave_sii, correo_notificaciones, horario_sincronizacion, sincronizacion_manual) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['rut_empresa'], $_POST['dv_empresa'], $_POST['razon_social'], $_POST['ambiente_sii'], $_POST['usuario_sii'], $_POST['clave_sii'], $_POST['correo_notificaciones'], $_POST['horario_sincronizacion'], isset($_POST['sincronizacion_manual']) ? 1 : 0]);
        }
        $mensaje = 'Parametros SII guardados correctamente';
        $tipo_mensaje = 'success';
    }

    // Descarga REAL desde SII usando clase SIIChile
    if ($accion === 'descargar_sii') {
        $periodo_desde = $_POST['periodo_desde'];
        $periodo_hasta = $_POST['periodo_hasta'];

        // Registrar descarga
        $stmt = $pdo->prepare("INSERT INTO sii_descargas (periodo_desde, periodo_hasta, tipo_descarga, estado_descarga, fecha_inicio) VALUES (?, ?, ?, 'en_proceso', NOW())");
        $stmt->execute([$periodo_desde, $periodo_hasta, $_POST['tipo_descarga']]);
        $descarga_id = $pdo->lastInsertId();

        // Usar clase SII real
        $sii = new SIIChile($pdo);
        $resultado = $sii->sincronizarPeriodo($periodo_desde, $periodo_hasta, $descarga_id);

        if (!empty($resultado['errores'])) {
            $erroresTexto = implode('; ', $resultado['errores']);
            $pdo->prepare("UPDATE sii_descargas SET estado_descarga='con_errores', fecha_fin=NOW(), facturas_nuevas=?, facturas_descargadas=?, errores=? WHERE id=?")->execute([$resultado['nuevos'], $resultado['descargados'], $erroresTexto, $descarga_id]);
            $mensaje = 'Descarga completada con errores. Nuevas: ' . $resultado['nuevos'] . ', Total: ' . $resultado['descargados'];
            $tipo_mensaje = 'warning';
        } else {
            $pdo->prepare("UPDATE sii_descargas SET estado_descarga='exitoso', fecha_fin=NOW(), facturas_nuevas=?, facturas_descargadas=? WHERE id=?")->execute([$resultado['nuevos'], $resultado['descargados'], $descarga_id]);
            $mensaje = 'Descarga exitosa desde SII. Facturas nuevas: ' . $resultado['nuevos'] . ', Total procesadas: ' . $resultado['descargados'];
            $tipo_mensaje = 'success';
        }
    }

    // Probar conexion SII
    if ($accion === 'probar_conexion_sii') {
        $sii = new SIIChile($pdo);
        $resultado = $sii->probarConexion();
        $mensaje = $resultado['message'];
        $tipo_mensaje = $resultado['success'] ? 'success' : 'danger';
    }

    // Subir certificado digital
    if ($accion === 'subir_certificado') {
        if (isset($_FILES['certificado_file']) && $_FILES['certificado_file']['error'] === UPLOAD_ERR_OK) {
            $certContent = file_get_contents($_FILES['certificado_file']['tmp_name']);
            $certBase64 = base64_encode($certContent);
            $clave = $_POST['clave_certificado'];

            // Validar que se puede leer el certificado
            $certs = [];
            if (openssl_pkcs12_read($certContent, $certs, $clave)) {
                $stmt = $pdo->prepare("UPDATE sii_parametros SET certificado_digital=?, clave_certificado=? WHERE activo=1");
                $stmt->execute([$certBase64, $clave]);
                $mensaje = 'Certificado digital cargado correctamente';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error: No se pudo leer el certificado. Verifique la clave.';
                $tipo_mensaje = 'danger';
            }
        } else {
            $mensaje = 'Error al subir el archivo de certificado';
            $tipo_mensaje = 'danger';
        }
    }

    // Registrar DTE manualmente (simulando descarga)
    if ($accion === 'registrar_dte') {
        $stmt = $pdo->prepare("INSERT INTO sii_dte_compras (folio_dte, tipo_dte, tipo_dte_nombre, rut_proveedor, dv_proveedor, razon_social_proveedor, giro_proveedor, fecha_emision, monto_neto, monto_exento, monto_iva, monto_total, estado_dte_sii) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $tipo_nombre = isset($tipos_dte[$_POST['tipo_dte']]) ? $tipos_dte[$_POST['tipo_dte']] : 'Otro';
        $stmt->execute([$_POST['folio_dte'], $_POST['tipo_dte'], $tipo_nombre, $_POST['rut_proveedor'], $_POST['dv_proveedor'], $_POST['razon_social_proveedor'], $_POST['giro_proveedor'], $_POST['fecha_emision'], $_POST['monto_neto'], $_POST['monto_exento'], $_POST['monto_iva'], $_POST['monto_total'], $_POST['estado_dte_sii']]);

        $dte_id = $pdo->lastInsertId();

        // Crear registro de verificacion
        $pdo->exec("INSERT INTO sii_verificacion (dte_sii_id, existe_en_sii, estado_verificacion) VALUES ($dte_id, 1, 'pendiente')");

        // Crear vinculacion vacia
        $pdo->exec("INSERT INTO sii_vinculacion_erp (dte_sii_id, estado_vinculacion) VALUES ($dte_id, 'sin_vincular')");

        $mensaje = 'DTE registrado correctamente';
        $tipo_mensaje = 'success';
    }

    // Verificar factura
    if ($accion === 'verificar_factura') {
        $dte_id = $_POST['dte_id'];
        $estado = $_POST['estado_verificacion'];

        $stmt = $pdo->prepare("UPDATE sii_verificacion SET estado_verificacion=?, fecha_verificacion=NOW() WHERE dte_sii_id=?");
        $stmt->execute([$estado, $dte_id]);

        // Registrar accion
        $pdo->prepare("INSERT INTO sii_acciones_factura (dte_sii_id, accion, estado_nuevo, observacion) VALUES (?, 'conciliar', ?, ?)")->execute([$dte_id, $estado, $_POST['observacion']]);

        $mensaje = 'Factura verificada correctamente';
        $tipo_mensaje = 'success';
    }

    // Vincular con ERP
    if ($accion === 'vincular_erp') {
        $stmt = $pdo->prepare("UPDATE sii_vinculacion_erp SET factura_compra_interna=?, orden_compra=?, centro_costo=?, cuenta_contable=?, estado_vinculacion='vinculada', fecha_vinculacion=NOW() WHERE dte_sii_id=?");
        $stmt->execute([$_POST['factura_interna'], $_POST['orden_compra'], $_POST['centro_costo'], $_POST['cuenta_contable'], $_POST['dte_id']]);

        // Actualizar verificacion
        $pdo->prepare("UPDATE sii_verificacion SET existe_en_erp=1 WHERE dte_sii_id=?")->execute([$_POST['dte_id']]);

        $mensaje = 'Factura vinculada con ERP correctamente';
        $tipo_mensaje = 'success';
    }

    // Registrar diferencia
    if ($accion === 'registrar_diferencia') {
        $stmt = $pdo->prepare("INSERT INTO sii_diferencias (dte_sii_id, tipo_diferencia, detalle_diferencia, valor_sii, valor_erp, estado_revision, responsable_revision, observacion_interna) VALUES (?, ?, ?, ?, ?, 'pendiente', ?, ?)");
        $stmt->execute([$_POST['dte_id'], $_POST['tipo_diferencia'], $_POST['detalle_diferencia'], $_POST['valor_sii'], $_POST['valor_erp'], $_POST['responsable'], $_POST['observacion']]);

        // Actualizar estado verificacion
        $pdo->prepare("UPDATE sii_verificacion SET estado_verificacion='con_diferencias' WHERE dte_sii_id=?")->execute([$_POST['dte_id']]);

        $mensaje = 'Diferencia registrada correctamente';
        $tipo_mensaje = 'success';
    }

    // Resolver diferencia
    if ($accion === 'resolver_diferencia') {
        $stmt = $pdo->prepare("UPDATE sii_diferencias SET estado_revision='resuelta', accion_tomada=?, observacion_interna=?, fecha_revision=CURDATE() WHERE id=?");
        $stmt->execute([$_POST['accion_tomada'], $_POST['observacion'], $_POST['diferencia_id']]);
        $mensaje = 'Diferencia resuelta correctamente';
        $tipo_mensaje = 'success';
    }

    // Registrar factura ERP
    if ($accion === 'registrar_factura_erp') {
        $stmt = $pdo->prepare("INSERT INTO sii_facturas_erp (numero_factura, tipo_documento, rut_proveedor, razon_social_proveedor, fecha_emision, fecha_registro, monto_neto, monto_iva, monto_total) VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?, ?)");
        $stmt->execute([$_POST['numero_factura'], $_POST['tipo_documento'], $_POST['rut_proveedor'], $_POST['razon_social'], $_POST['fecha_emision'], $_POST['monto_neto'], $_POST['monto_iva'], $_POST['monto_total']]);
        $mensaje = 'Factura ERP registrada correctamente';
        $tipo_mensaje = 'success';
    }

    // Guardar Rol
    if ($accion === 'guardar_rol') {
        $stmt = $pdo->prepare("INSERT INTO sii_roles (nombre_rol, descripcion) VALUES (?, ?)");
        $stmt->execute([$_POST['nombre_rol'], $_POST['descripcion']]);
        $mensaje = 'Rol creado correctamente';
        $tipo_mensaje = 'success';
    }

    // Asignar Permiso a Rol
    if ($accion === 'asignar_permiso') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO sii_roles_permisos (rol_id, permiso_id) VALUES (?, ?)");
        $stmt->execute([$_POST['rol_id'], $_POST['permiso_id']]);
        $mensaje = 'Permiso asignado correctamente';
        $tipo_mensaje = 'success';
    }

    // Quitar Permiso de Rol
    if ($accion === 'quitar_permiso') {
        $stmt = $pdo->prepare("DELETE FROM sii_roles_permisos WHERE rol_id = ? AND permiso_id = ?");
        $stmt->execute([$_POST['rol_id'], $_POST['permiso_id']]);
        $mensaje = 'Permiso removido correctamente';
        $tipo_mensaje = 'success';
    }

    // Asignar Rol a Usuario
    if ($accion === 'asignar_rol_usuario') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO sii_usuarios_roles (usuario_id, rol_id) VALUES (?, ?)");
        $stmt->execute([$_POST['usuario_id'], $_POST['rol_id']]);
        $mensaje = 'Rol asignado al usuario correctamente';
        $tipo_mensaje = 'success';
    }

    header("Location: verificacion_facturas_sii.php?vista=$vista&msg=" . urlencode($mensaje) . "&tipo=$tipo_mensaje");
    exit;
}

if (isset($_GET['msg'])) {
    $mensaje = $_GET['msg'];
    $tipo_mensaje = isset($_GET['tipo']) ? $_GET['tipo'] : 'success';
}

// KPIs
$total_dte_sii = $pdo->query("SELECT COUNT(*) FROM sii_dte_compras")->fetchColumn();
$total_conciliadas = $pdo->query("SELECT COUNT(*) FROM sii_verificacion WHERE estado_verificacion = 'conciliada'")->fetchColumn();
$total_diferencias = $pdo->query("SELECT COUNT(*) FROM sii_verificacion WHERE estado_verificacion = 'con_diferencias'")->fetchColumn();
$total_pendientes = $pdo->query("SELECT COUNT(*) FROM sii_verificacion WHERE estado_verificacion = 'pendiente'")->fetchColumn();
$total_solo_sii = $pdo->query("SELECT COUNT(*) FROM sii_verificacion WHERE estado_verificacion = 'solo_sii'")->fetchColumn();
$parametros = $pdo->query("SELECT * FROM sii_parametros LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificacion Facturas SII - CONECTA ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; color: #1e293b; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header h1 { font-size: 22px; display: flex; align-items: center; gap: 10px; color: white; }
        .header a { color: white; text-decoration: none; padding: 10px 20px; background: rgba(255,255,255,0.2); border-radius: 8px; }
        .nav-tabs { background: #ffffff; padding: 0 40px; display: flex; gap: 5px; overflow-x: auto; border-bottom: 2px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .nav-tab { padding: 15px 18px; color: #64748b; text-decoration: none; border-bottom: 3px solid transparent; white-space: nowrap; font-size: 13px; font-weight: 500; }
        .nav-tab:hover, .nav-tab.active { color: #dc2626; border-bottom-color: #dc2626; background: rgba(220,38,38,0.05); }
        .container { max-width: 1600px; margin: 0 auto; padding: 30px 40px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #ffffff; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #dc2626; }
        .stat-card.verde { border-left-color: #10b981; }
        .stat-card.naranja { border-left-color: #f59e0b; }
        .stat-card.azul { border-left-color: #3b82f6; }
        .stat-card h3 { font-size: 32px; margin: 8px 0; color: #1e293b; }
        .stat-card p { color: #64748b; font-size: 12px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .card { background: #ffffff; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0; }
        .card-title { font-size: 18px; font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 10px; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #dc2626; color: white; }
        .btn-primary:hover { background: #b91c1c; }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-secondary { background: #64748b; color: white; }
        .btn-info { background: #3b82f6; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; color: #475569; font-size: 14px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px; color: #1e293b; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,0.1); }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase; }
        tr:hover { background: #fef2f2; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-secondary { background: #e2e8f0; color: #475569; }
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; }
        .alert-error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto; }
        .modal.active { display: flex; align-items: flex-start; justify-content: center; padding: 40px 20px; }
        .modal-content { background: #ffffff; border-radius: 12px; width: 100%; max-width: 800px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .modal-header { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 25px; max-height: 70vh; overflow-y: auto; }
        .modal-footer { padding: 20px 25px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; }
        .close-modal { background: none; border: none; color: #64748b; font-size: 24px; cursor: pointer; }
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        .sii-logo { background: #dc2626; color: white; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .monto { font-family: monospace; text-align: right; }
        .conexion-ok { color: #10b981; }
        .conexion-error { color: #ef4444; }
        .info-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .info-box p { color: #1e40af; font-size: 13px; margin: 5px 0; }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .container { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-file-invoice"></i> Verificacion Facturas SII Chile</h1>
        <a href="index.php"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <div class="nav-tabs">
        <a href="?vista=dashboard" class="nav-tab <?php echo $vista === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
        <a href="?vista=parametros" class="nav-tab <?php echo $vista === 'parametros' ? 'active' : ''; ?>">Parametros SII</a>
        <a href="?vista=descargar" class="nav-tab <?php echo $vista === 'descargar' ? 'active' : ''; ?>">Descargar SII</a>
        <a href="?vista=bandeja_sii" class="nav-tab <?php echo $vista === 'bandeja_sii' ? 'active' : ''; ?>">Bandeja SII</a>
        <a href="?vista=facturas_erp" class="nav-tab <?php echo $vista === 'facturas_erp' ? 'active' : ''; ?>">Facturas ERP</a>
        <a href="?vista=verificacion" class="nav-tab <?php echo $vista === 'verificacion' ? 'active' : ''; ?>">Verificacion</a>
        <a href="?vista=diferencias" class="nav-tab <?php echo $vista === 'diferencias' ? 'active' : ''; ?>">Diferencias</a>
        <a href="?vista=conciliadas" class="nav-tab <?php echo $vista === 'conciliadas' ? 'active' : ''; ?>">Conciliadas</a>
        <a href="?vista=reportes" class="nav-tab <?php echo $vista === 'reportes' ? 'active' : ''; ?>">Reportes</a>
        <a href="?vista=log_conexion" class="nav-tab <?php echo $vista === 'log_conexion' ? 'active' : ''; ?>">Log Conexion</a>
        <a href="?vista=permisos" class="nav-tab <?php echo $vista === 'permisos' ? 'active' : ''; ?>">Permisos</a>
    </div>

    <div class="container">
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <?php if ($vista === 'dashboard'): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <p>DTE en SII</p>
                    <h3><?php echo $total_dte_sii; ?></h3>
                </div>
                <div class="stat-card verde">
                    <p>Conciliadas</p>
                    <h3><?php echo $total_conciliadas; ?></h3>
                </div>
                <div class="stat-card naranja">
                    <p>Con Diferencias</p>
                    <h3><?php echo $total_diferencias; ?></h3>
                </div>
                <div class="stat-card azul">
                    <p>Pendientes</p>
                    <h3><?php echo $total_pendientes; ?></h3>
                </div>
            </div>

            <div class="info-box">
                <p><strong><i class="fas fa-info-circle"></i> Estado Conexion SII:</strong>
                <?php if ($parametros): ?>
                    <span class="conexion-ok"><i class="fas fa-check-circle"></i> Configurado - <?php echo htmlspecialchars($parametros['razon_social']); ?> (<?php echo $parametros['rut_empresa']; ?>-<?php echo $parametros['dv_empresa']; ?>)</span>
                <?php else: ?>
                    <span class="conexion-error"><i class="fas fa-times-circle"></i> No configurado - Configure los parametros SII</span>
                <?php endif; ?>
                </p>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title"><span class="sii-logo">SII</span> Ultimas Facturas desde SII</span>
                    <a href="?vista=descargar" class="btn btn-primary btn-sm"><i class="fas fa-download"></i> Descargar</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Tipo DTE</th>
                            <th>Proveedor</th>
                            <th>RUT</th>
                            <th>Fecha</th>
                            <th>Neto</th>
                            <th>IVA</th>
                            <th>Total</th>
                            <th>Estado SII</th>
                            <th>Verificacion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $dtes = $pdo->query("SELECT d.*, v.estado_verificacion FROM sii_dte_compras d LEFT JOIN sii_verificacion v ON d.id = v.dte_sii_id ORDER BY d.fecha_descarga DESC LIMIT 10")->fetchAll();
                        foreach ($dtes as $dte):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dte['folio_dte']); ?></strong></td>
                            <td><?php echo htmlspecialchars($dte['tipo_dte_nombre']); ?></td>
                            <td><?php echo htmlspecialchars(substr($dte['razon_social_proveedor'], 0, 25)); ?></td>
                            <td><?php echo $dte['rut_proveedor']; ?>-<?php echo $dte['dv_proveedor']; ?></td>
                            <td><?php echo $dte['fecha_emision']; ?></td>
                            <td class="monto">$<?php echo number_format($dte['monto_neto'], 0, ',', '.'); ?></td>
                            <td class="monto">$<?php echo number_format($dte['monto_iva'], 0, ',', '.'); ?></td>
                            <td class="monto"><strong>$<?php echo number_format($dte['monto_total'], 0, ',', '.'); ?></strong></td>
                            <td><span class="badge badge-<?php echo $dte['estado_dte_sii'] === 'aceptado' ? 'success' : ($dte['estado_dte_sii'] === 'rechazado' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($dte['estado_dte_sii']); ?></span></td>
                            <td><span class="badge badge-<?php echo $dte['estado_verificacion'] === 'conciliada' ? 'success' : ($dte['estado_verificacion'] === 'con_diferencias' ? 'danger' : ($dte['estado_verificacion'] === 'pendiente' ? 'warning' : 'secondary')); ?>"><?php echo ucfirst(str_replace('_', ' ', $dte['estado_verificacion'] ?: 'pendiente')); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($dtes)): ?>
                        <tr><td colspan="10" class="empty-state">No hay facturas descargadas del SII</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($vista === 'parametros'): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-cog"></i> Parametros de Conexion SII</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="accion" value="guardar_parametros">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>RUT Empresa</label>
                            <input type="text" name="rut_empresa" value="<?php echo $parametros ? htmlspecialchars($parametros['rut_empresa']) : ''; ?>" placeholder="12345678" required>
                        </div>
                        <div class="form-group">
                            <label>DV</label>
                            <input type="text" name="dv_empresa" value="<?php echo $parametros ? htmlspecialchars($parametros['dv_empresa']) : ''; ?>" maxlength="1" placeholder="K" required>
                        </div>
                        <div class="form-group">
                            <label>Razon Social</label>
                            <input type="text" name="razon_social" value="<?php echo $parametros ? htmlspecialchars($parametros['razon_social']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Ambiente SII</label>
                            <select name="ambiente_sii">
                                <option value="certificacion" <?php echo ($parametros && $parametros['ambiente_sii'] === 'certificacion') ? 'selected' : ''; ?>>Certificacion</option>
                                <option value="produccion" <?php echo ($parametros && $parametros['ambiente_sii'] === 'produccion') ? 'selected' : ''; ?>>Produccion</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Usuario SII</label>
                            <input type="text" name="usuario_sii" value="<?php echo $parametros ? htmlspecialchars($parametros['usuario_sii']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Clave SII</label>
                            <input type="password" name="clave_sii" value="<?php echo $parametros ? htmlspecialchars($parametros['clave_sii']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Correo Notificaciones</label>
                            <input type="email" name="correo_notificaciones" value="<?php echo $parametros ? htmlspecialchars($parametros['correo_notificaciones']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Horario Sincronizacion</label>
                            <input type="text" name="horario_sincronizacion" value="<?php echo $parametros ? htmlspecialchars($parametros['horario_sincronizacion']) : ''; ?>" placeholder="08:00, 14:00, 20:00">
                        </div>
                        <div class="form-group">
                            <label><input type="checkbox" name="sincronizacion_manual" <?php echo ($parametros && $parametros['sincronizacion_manual']) ? 'checked' : ''; ?>> Sincronizacion Manual</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Parametros</button>
                </form>
            </div>

        <?php elseif ($vista === 'descargar'): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><span class="sii-logo">SII</span> Descargar Facturas de Compra desde SII</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="accion" value="descargar_sii">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Periodo Desde</label>
                            <input type="month" name="periodo_desde" value="<?php echo date('Y-m'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Periodo Hasta</label>
                            <input type="month" name="periodo_hasta" value="<?php echo date('Y-m'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Tipo Descarga</label>
                            <select name="tipo_descarga">
                                <option value="todas">Todas las Facturas</option>
                                <option value="pendientes">Solo Pendientes</option>
                                <option value="nuevas">Solo Nuevas</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-cloud-download-alt"></i> Iniciar Descarga desde SII</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-plus"></i> Registrar DTE Manualmente</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="accion" value="registrar_dte">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Folio DTE</label>
                            <input type="text" name="folio_dte" required>
                        </div>
                        <div class="form-group">
                            <label>Tipo DTE</label>
                            <select name="tipo_dte">
                                <?php foreach ($tipos_dte as $cod => $nombre): ?>
                                <option value="<?php echo $cod; ?>"><?php echo $cod; ?> - <?php echo $nombre; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>RUT Proveedor</label>
                            <input type="text" name="rut_proveedor" placeholder="12345678" required>
                        </div>
                        <div class="form-group">
                            <label>DV</label>
                            <input type="text" name="dv_proveedor" maxlength="1" required>
                        </div>
                        <div class="form-group">
                            <label>Razon Social Proveedor</label>
                            <input type="text" name="razon_social_proveedor" required>
                        </div>
                        <div class="form-group">
                            <label>Giro</label>
                            <input type="text" name="giro_proveedor">
                        </div>
                        <div class="form-group">
                            <label>Fecha Emision</label>
                            <input type="date" name="fecha_emision" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Monto Neto</label>
                            <input type="number" name="monto_neto" step="1" required>
                        </div>
                        <div class="form-group">
                            <label>Monto Exento</label>
                            <input type="number" name="monto_exento" step="1" value="0">
                        </div>
                        <div class="form-group">
                            <label>Monto IVA</label>
                            <input type="number" name="monto_iva" step="1" required>
                        </div>
                        <div class="form-group">
                            <label>Monto Total</label>
                            <input type="number" name="monto_total" step="1" required>
                        </div>
                        <div class="form-group">
                            <label>Estado DTE SII</label>
                            <select name="estado_dte_sii">
                                <option value="aceptado">Aceptado</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="rechazado">Rechazado</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Registrar DTE</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Historial de Descargas</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Periodo</th>
                            <th>Tipo</th>
                            <th>Nuevas</th>
                            <th>Descargadas</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $descargas = $pdo->query("SELECT * FROM sii_descargas ORDER BY fecha_creacion DESC LIMIT 10")->fetchAll();
                        foreach ($descargas as $d):
                        ?>
                        <tr>
                            <td><?php echo $d['id']; ?></td>
                            <td><?php echo $d['periodo_desde']; ?> a <?php echo $d['periodo_hasta']; ?></td>
                            <td><?php echo ucfirst($d['tipo_descarga']); ?></td>
                            <td><?php echo $d['facturas_nuevas']; ?></td>
                            <td><?php echo $d['facturas_descargadas']; ?></td>
                            <td><span class="badge badge-<?php echo $d['estado_descarga'] === 'exitoso' ? 'success' : ($d['estado_descarga'] === 'con_errores' ? 'danger' : 'warning'); ?>"><?php echo ucfirst(str_replace('_', ' ', $d['estado_descarga'])); ?></span></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($d['fecha_creacion'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($descargas)): ?>
                        <tr><td colspan="7" class="empty-state">No hay descargas registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($vista === 'bandeja_sii'): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><span class="sii-logo">SII</span> Bandeja de Facturas SII</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Tipo</th>
                            <th>Proveedor</th>
                            <th>RUT</th>
                            <th>Fecha</th>
                            <th>Neto</th>
                            <th>IVA</th>
                            <th>Total</th>
                            <th>Estado SII</th>
                            <th>Verificacion</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $dtes = $pdo->query("SELECT d.*, v.estado_verificacion, vi.estado_vinculacion FROM sii_dte_compras d LEFT JOIN sii_verificacion v ON d.id = v.dte_sii_id LEFT JOIN sii_vinculacion_erp vi ON d.id = vi.dte_sii_id ORDER BY d.fecha_emision DESC")->fetchAll();
                        foreach ($dtes as $dte):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dte['folio_dte']); ?></strong></td>
                            <td><small><?php echo $dte['tipo_dte']; ?></small></td>
                            <td><?php echo htmlspecialchars(substr($dte['razon_social_proveedor'], 0, 20)); ?></td>
                            <td><small><?php echo $dte['rut_proveedor']; ?>-<?php echo $dte['dv_proveedor']; ?></small></td>
                            <td><?php echo $dte['fecha_emision']; ?></td>
                            <td class="monto">$<?php echo number_format($dte['monto_neto'], 0, ',', '.'); ?></td>
                            <td class="monto">$<?php echo number_format($dte['monto_iva'], 0, ',', '.'); ?></td>
                            <td class="monto"><strong>$<?php echo number_format($dte['monto_total'], 0, ',', '.'); ?></strong></td>
                            <td><span class="badge badge-<?php echo $dte['estado_dte_sii'] === 'aceptado' ? 'success' : 'warning'; ?>"><?php echo ucfirst($dte['estado_dte_sii']); ?></span></td>
                            <td><span class="badge badge-<?php echo $dte['estado_verificacion'] === 'conciliada' ? 'success' : ($dte['estado_verificacion'] === 'con_diferencias' ? 'danger' : 'secondary'); ?>"><?php echo ucfirst(str_replace('_', ' ', $dte['estado_verificacion'] ?: 'pendiente')); ?></span></td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="document.getElementById('modalVincular<?php echo $dte['id']; ?>').classList.add('active')"><i class="fas fa-link"></i></button>
                                <button class="btn btn-success btn-sm" onclick="document.getElementById('modalVerificar<?php echo $dte['id']; ?>').classList.add('active')"><i class="fas fa-check"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($dtes)): ?>
                        <tr><td colspan="11" class="empty-state">No hay facturas en la bandeja SII</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php foreach ($dtes as $dte): ?>
            <div id="modalVincular<?php echo $dte['id']; ?>" class="modal">
                <div class="modal-content" style="max-width:600px">
                    <div class="modal-header">
                        <h3>Vincular con ERP - Folio <?php echo $dte['folio_dte']; ?></h3>
                        <button class="close-modal" onclick="document.getElementById('modalVincular<?php echo $dte['id']; ?>').classList.remove('active')">&times;</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="vincular_erp">
                            <input type="hidden" name="dte_id" value="<?php echo $dte['id']; ?>">
                            <div class="info-box">
                                <p><strong>Proveedor:</strong> <?php echo htmlspecialchars($dte['razon_social_proveedor']); ?></p>
                                <p><strong>Total:</strong> $<?php echo number_format($dte['monto_total'], 0, ',', '.'); ?></p>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Factura Interna ERP</label>
                                    <input type="text" name="factura_interna">
                                </div>
                                <div class="form-group">
                                    <label>Orden de Compra</label>
                                    <input type="text" name="orden_compra">
                                </div>
                                <div class="form-group">
                                    <label>Centro de Costo</label>
                                    <input type="text" name="centro_costo">
                                </div>
                                <div class="form-group">
                                    <label>Cuenta Contable</label>
                                    <input type="text" name="cuenta_contable">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalVincular<?php echo $dte['id']; ?>').classList.remove('active')">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Vincular</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="modalVerificar<?php echo $dte['id']; ?>" class="modal">
                <div class="modal-content" style="max-width:500px">
                    <div class="modal-header">
                        <h3>Verificar Factura - Folio <?php echo $dte['folio_dte']; ?></h3>
                        <button class="close-modal" onclick="document.getElementById('modalVerificar<?php echo $dte['id']; ?>').classList.remove('active')">&times;</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="verificar_factura">
                            <input type="hidden" name="dte_id" value="<?php echo $dte['id']; ?>">
                            <div class="form-group">
                                <label>Estado Verificacion</label>
                                <select name="estado_verificacion">
                                    <option value="conciliada">Conciliada</option>
                                    <option value="con_diferencias">Con Diferencias</option>
                                    <option value="observada">Observada</option>
                                    <option value="rechazada">Rechazada</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Observacion</label>
                                <textarea name="observacion" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalVerificar<?php echo $dte['id']; ?>').classList.remove('active')">Cancelar</button>
                            <button type="submit" class="btn btn-success">Verificar</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>

        <?php elseif ($vista === 'facturas_erp'): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-file-alt"></i> Facturas Registradas en ERP</span>
                    <button class="btn btn-primary" onclick="document.getElementById('modalFacturaERP').classList.add('active')"><i class="fas fa-plus"></i> Nueva Factura</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Numero</th>
                            <th>Tipo</th>
                            <th>Proveedor</th>
                            <th>RUT</th>
                            <th>Fecha</th>
                            <th>Neto</th>
                            <th>IVA</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Verificada SII</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $facturas_erp = $pdo->query("SELECT * FROM sii_facturas_erp ORDER BY fecha_creacion DESC")->fetchAll();
                        foreach ($facturas_erp as $f):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($f['numero_factura']); ?></strong></td>
                            <td><?php echo $f['tipo_documento']; ?></td>
                            <td><?php echo htmlspecialchars($f['razon_social_proveedor']); ?></td>
                            <td><?php echo $f['rut_proveedor']; ?></td>
                            <td><?php echo $f['fecha_emision']; ?></td>
                            <td class="monto">$<?php echo number_format($f['monto_neto'], 0, ',', '.'); ?></td>
                            <td class="monto">$<?php echo number_format($f['monto_iva'], 0, ',', '.'); ?></td>
                            <td class="monto"><strong>$<?php echo number_format($f['monto_total'], 0, ',', '.'); ?></strong></td>
                            <td><span class="badge badge-<?php echo $f['estado'] === 'pagada' ? 'success' : ($f['estado'] === 'anulada' ? 'danger' : 'info'); ?>"><?php echo ucfirst($f['estado']); ?></span></td>
                            <td><?php echo $f['verificada_sii'] ? '<i class="fas fa-check-circle" style="color:#10b981"></i>' : '<i class="fas fa-times-circle" style="color:#ef4444"></i>'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($facturas_erp)): ?>
                        <tr><td colspan="10" class="empty-state">No hay facturas registradas en ERP</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="modalFacturaERP" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Nueva Factura ERP</h3>
                        <button class="close-modal" onclick="document.getElementById('modalFacturaERP').classList.remove('active')">&times;</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="registrar_factura_erp">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Numero Factura</label>
                                    <input type="text" name="numero_factura" required>
                                </div>
                                <div class="form-group">
                                    <label>Tipo Documento</label>
                                    <select name="tipo_documento">
                                        <?php foreach ($tipos_dte as $cod => $nombre): ?>
                                        <option value="<?php echo $cod; ?>"><?php echo $cod; ?> - <?php echo $nombre; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>RUT Proveedor</label>
                                    <input type="text" name="rut_proveedor" required>
                                </div>
                                <div class="form-group">
                                    <label>Razon Social</label>
                                    <input type="text" name="razon_social" required>
                                </div>
                                <div class="form-group">
                                    <label>Fecha Emision</label>
                                    <input type="date" name="fecha_emision" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Monto Neto</label>
                                    <input type="number" name="monto_neto" step="1" required>
                                </div>
                                <div class="form-group">
                                    <label>Monto IVA</label>
                                    <input type="number" name="monto_iva" step="1" required>
                                </div>
                                <div class="form-group">
                                    <label>Monto Total</label>
                                    <input type="number" name="monto_total" step="1" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalFacturaERP').classList.remove('active')">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($vista === 'verificacion'): ?>
            <div class="stats-grid">
                <div class="stat-card verde">
                    <p>Conciliadas</p>
                    <h3><?php echo $total_conciliadas; ?></h3>
                </div>
                <div class="stat-card naranja">
                    <p>Con Diferencias</p>
                    <h3><?php echo $total_diferencias; ?></h3>
                </div>
                <div class="stat-card azul">
                    <p>Pendientes</p>
                    <h3><?php echo $total_pendientes; ?></h3>
                </div>
                <div class="stat-card">
                    <p>Solo en SII</p>
                    <h3><?php echo $total_solo_sii; ?></h3>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-check-double"></i> Estado de Verificacion</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Proveedor</th>
                            <th>Total SII</th>
                            <th>En ERP</th>
                            <th>RUT OK</th>
                            <th>Monto OK</th>
                            <th>IVA OK</th>
                            <th>Fecha OK</th>
                            <th>Diferencia</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $verificaciones = $pdo->query("SELECT d.*, v.* FROM sii_dte_compras d JOIN sii_verificacion v ON d.id = v.dte_sii_id ORDER BY v.fecha_verificacion DESC")->fetchAll();
                        foreach ($verificaciones as $ver):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($ver['folio_dte']); ?></strong></td>
                            <td><?php echo htmlspecialchars(substr($ver['razon_social_proveedor'], 0, 20)); ?></td>
                            <td class="monto">$<?php echo number_format($ver['monto_total'], 0, ',', '.'); ?></td>
                            <td><?php echo $ver['existe_en_erp'] ? '<i class="fas fa-check" style="color:#10b981"></i>' : '<i class="fas fa-times" style="color:#ef4444"></i>'; ?></td>
                            <td><?php echo $ver['rut_coincide'] ? '<i class="fas fa-check" style="color:#10b981"></i>' : '<i class="fas fa-times" style="color:#ef4444"></i>'; ?></td>
                            <td><?php echo $ver['monto_total_coincide'] ? '<i class="fas fa-check" style="color:#10b981"></i>' : '<i class="fas fa-times" style="color:#ef4444"></i>'; ?></td>
                            <td><?php echo $ver['monto_iva_coincide'] ? '<i class="fas fa-check" style="color:#10b981"></i>' : '<i class="fas fa-times" style="color:#ef4444"></i>'; ?></td>
                            <td><?php echo $ver['fecha_coincide'] ? '<i class="fas fa-check" style="color:#10b981"></i>' : '<i class="fas fa-times" style="color:#ef4444"></i>'; ?></td>
                            <td class="monto"><?php echo $ver['diferencia_monto'] != 0 ? '$' . number_format($ver['diferencia_monto'], 0, ',', '.') : '-'; ?></td>
                            <td><span class="badge badge-<?php echo $ver['estado_verificacion'] === 'conciliada' ? 'success' : ($ver['estado_verificacion'] === 'con_diferencias' ? 'danger' : 'warning'); ?>"><?php echo ucfirst(str_replace('_', ' ', $ver['estado_verificacion'])); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($verificaciones)): ?>
                        <tr><td colspan="10" class="empty-state">No hay verificaciones registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($vista === 'diferencias'): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-exclamation-triangle"></i> Gestion de Diferencias</span>
                    <button class="btn btn-primary" onclick="document.getElementById('modalDiferencia').classList.add('active')"><i class="fas fa-plus"></i> Registrar Diferencia</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Folio DTE</th>
                            <th>Tipo Diferencia</th>
                            <th>Valor SII</th>
                            <th>Valor ERP</th>
                            <th>Estado</th>
                            <th>Responsable</th>
                            <th>Accion</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $diferencias = $pdo->query("SELECT di.*, d.folio_dte FROM sii_diferencias di JOIN sii_dte_compras d ON di.dte_sii_id = d.id ORDER BY di.fecha_creacion DESC")->fetchAll();
                        foreach ($diferencias as $dif):
                        ?>
                        <tr>
                            <td><?php echo $dif['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($dif['folio_dte']); ?></strong></td>
                            <td><span class="badge badge-warning"><?php echo ucfirst(str_replace('_', ' ', $dif['tipo_diferencia'])); ?></span></td>
                            <td><?php echo htmlspecialchars($dif['valor_sii']); ?></td>
                            <td><?php echo htmlspecialchars($dif['valor_erp']); ?></td>
                            <td><span class="badge badge-<?php echo $dif['estado_revision'] === 'resuelta' ? 'success' : ($dif['estado_revision'] === 'en_revision' ? 'info' : 'danger'); ?>"><?php echo ucfirst(str_replace('_', ' ', $dif['estado_revision'])); ?></span></td>
                            <td><?php echo htmlspecialchars($dif['responsable_revision']); ?></td>
                            <td><?php echo $dif['accion_tomada'] !== 'sin_accion' ? ucfirst(str_replace('_', ' ', $dif['accion_tomada'])) : '-'; ?></td>
                            <td>
                                <?php if ($dif['estado_revision'] !== 'resuelta'): ?>
                                <button class="btn btn-success btn-sm" onclick="document.getElementById('modalResolver<?php echo $dif['id']; ?>').classList.add('active')">Resolver</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($diferencias)): ?>
                        <tr><td colspan="9" class="empty-state">No hay diferencias registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php foreach ($diferencias as $dif): ?>
            <?php if ($dif['estado_revision'] !== 'resuelta'): ?>
            <div id="modalResolver<?php echo $dif['id']; ?>" class="modal">
                <div class="modal-content" style="max-width:500px">
                    <div class="modal-header">
                        <h3>Resolver Diferencia</h3>
                        <button class="close-modal" onclick="document.getElementById('modalResolver<?php echo $dif['id']; ?>').classList.remove('active')">&times;</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="resolver_diferencia">
                            <input type="hidden" name="diferencia_id" value="<?php echo $dif['id']; ?>">
                            <div class="form-group">
                                <label>Accion Tomada</label>
                                <select name="accion_tomada">
                                    <option value="ajuste_erp">Ajuste en ERP</option>
                                    <option value="reclamo_proveedor">Reclamo a Proveedor</option>
                                    <option value="nota_credito">Nota de Credito</option>
                                    <option value="rechazo_factura">Rechazo de Factura</option>
                                    <option value="comentario_interno">Solo Comentario Interno</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Observacion</label>
                                <textarea name="observacion" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalResolver<?php echo $dif['id']; ?>').classList.remove('active')">Cancelar</button>
                            <button type="submit" class="btn btn-success">Resolver</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>

            <div id="modalDiferencia" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Registrar Diferencia</h3>
                        <button class="close-modal" onclick="document.getElementById('modalDiferencia').classList.remove('active')">&times;</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="registrar_diferencia">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>DTE SII</label>
                                    <select name="dte_id" required>
                                        <?php
                                        $dtes_list = $pdo->query("SELECT id, folio_dte, razon_social_proveedor FROM sii_dte_compras ORDER BY fecha_descarga DESC")->fetchAll();
                                        foreach ($dtes_list as $d): ?>
                                        <option value="<?php echo $d['id']; ?>"><?php echo $d['folio_dte']; ?> - <?php echo htmlspecialchars(substr($d['razon_social_proveedor'], 0, 30)); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Tipo Diferencia</label>
                                    <select name="tipo_diferencia">
                                        <option value="monto">Monto</option>
                                        <option value="iva">IVA</option>
                                        <option value="fecha">Fecha</option>
                                        <option value="proveedor">Proveedor</option>
                                        <option value="folio">Folio</option>
                                        <option value="no_en_sii">No en SII</option>
                                        <option value="no_en_erp">No en ERP</option>
                                        <option value="duplicado">Duplicado</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Valor SII</label>
                                    <input type="text" name="valor_sii">
                                </div>
                                <div class="form-group">
                                    <label>Valor ERP</label>
                                    <input type="text" name="valor_erp">
                                </div>
                                <div class="form-group">
                                    <label>Responsable</label>
                                    <input type="text" name="responsable">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Detalle Diferencia</label>
                                <textarea name="detalle_diferencia" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Observacion</label>
                                <textarea name="observacion" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalDiferencia').classList.remove('active')">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Registrar</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($vista === 'conciliadas'): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-check-circle"></i> Facturas Conciliadas</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Tipo</th>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Factura ERP</th>
                            <th>Fecha Conciliacion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $conciliadas = $pdo->query("SELECT d.*, v.fecha_verificacion, vi.factura_compra_interna FROM sii_dte_compras d JOIN sii_verificacion v ON d.id = v.dte_sii_id LEFT JOIN sii_vinculacion_erp vi ON d.id = vi.dte_sii_id WHERE v.estado_verificacion = 'conciliada' ORDER BY v.fecha_verificacion DESC")->fetchAll();
                        foreach ($conciliadas as $c):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($c['folio_dte']); ?></strong></td>
                            <td><?php echo $c['tipo_dte_nombre']; ?></td>
                            <td><?php echo htmlspecialchars($c['razon_social_proveedor']); ?></td>
                            <td><?php echo $c['fecha_emision']; ?></td>
                            <td class="monto"><strong>$<?php echo number_format($c['monto_total'], 0, ',', '.'); ?></strong></td>
                            <td><?php echo htmlspecialchars($c['factura_compra_interna']); ?></td>
                            <td><?php echo $c['fecha_verificacion'] ? date('d/m/Y H:i', strtotime($c['fecha_verificacion'])) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($conciliadas)): ?>
                        <tr><td colspan="7" class="empty-state">No hay facturas conciliadas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($vista === 'reportes'): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <p>Total DTE Periodo</p>
                    <h3><?php echo $total_dte_sii; ?></h3>
                </div>
                <div class="stat-card verde">
                    <p>Monto Conciliado</p>
                    <h3>$<?php echo number_format($pdo->query("SELECT COALESCE(SUM(d.monto_total), 0) FROM sii_dte_compras d JOIN sii_verificacion v ON d.id = v.dte_sii_id WHERE v.estado_verificacion = 'conciliada'")->fetchColumn(), 0, ',', '.'); ?></h3>
                </div>
                <div class="stat-card naranja">
                    <p>Monto Diferencias</p>
                    <h3>$<?php echo number_format($pdo->query("SELECT COALESCE(SUM(d.monto_total), 0) FROM sii_dte_compras d JOIN sii_verificacion v ON d.id = v.dte_sii_id WHERE v.estado_verificacion = 'con_diferencias'")->fetchColumn(), 0, ',', '.'); ?></h3>
                </div>
                <div class="stat-card azul">
                    <p>Monto Pendiente</p>
                    <h3>$<?php echo number_format($pdo->query("SELECT COALESCE(SUM(d.monto_total), 0) FROM sii_dte_compras d JOIN sii_verificacion v ON d.id = v.dte_sii_id WHERE v.estado_verificacion = 'pendiente'")->fetchColumn(), 0, ',', '.'); ?></h3>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-chart-bar"></i> Resumen por Proveedor</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>RUT</th>
                            <th>Total DTE</th>
                            <th>Monto Total</th>
                            <th>Conciliadas</th>
                            <th>Con Diferencias</th>
                            <th>Pendientes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $resumen_prov = $pdo->query("
                            SELECT
                                d.razon_social_proveedor,
                                d.rut_proveedor,
                                COUNT(*) as total_dte,
                                SUM(d.monto_total) as monto_total,
                                SUM(CASE WHEN v.estado_verificacion = 'conciliada' THEN 1 ELSE 0 END) as conciliadas,
                                SUM(CASE WHEN v.estado_verificacion = 'con_diferencias' THEN 1 ELSE 0 END) as diferencias,
                                SUM(CASE WHEN v.estado_verificacion = 'pendiente' THEN 1 ELSE 0 END) as pendientes
                            FROM sii_dte_compras d
                            LEFT JOIN sii_verificacion v ON d.id = v.dte_sii_id
                            GROUP BY d.rut_proveedor, d.razon_social_proveedor
                            ORDER BY monto_total DESC
                        ")->fetchAll();
                        foreach ($resumen_prov as $rp):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rp['razon_social_proveedor']); ?></td>
                            <td><?php echo $rp['rut_proveedor']; ?></td>
                            <td><?php echo $rp['total_dte']; ?></td>
                            <td class="monto"><strong>$<?php echo number_format($rp['monto_total'], 0, ',', '.'); ?></strong></td>
                            <td><span class="badge badge-success"><?php echo $rp['conciliadas']; ?></span></td>
                            <td><span class="badge badge-danger"><?php echo $rp['diferencias']; ?></span></td>
                            <td><span class="badge badge-warning"><?php echo $rp['pendientes']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($resumen_prov)): ?>
                        <tr><td colspan="7" class="empty-state">No hay datos para el reporte</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($vista === 'log_conexion'): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-history"></i> Log de Conexion SII</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Estado</th>
                            <th>Fecha Conexion</th>
                            <th>Periodo</th>
                            <th>Tiempo Respuesta</th>
                            <th>Reintentos</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $logs = $pdo->query("SELECT * FROM sii_conexion_log ORDER BY fecha_creacion DESC LIMIT 50")->fetchAll();
                        foreach ($logs as $log):
                        ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td><span class="badge badge-<?php echo $log['estado_conexion'] === 'conectado' ? 'success' : ($log['estado_conexion'] === 'error' ? 'danger' : 'secondary'); ?>"><?php echo ucfirst($log['estado_conexion']); ?></span></td>
                            <td><?php echo $log['fecha_conexion'] ? date('d/m/Y H:i:s', strtotime($log['fecha_conexion'])) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($log['periodo_sincronizado']); ?></td>
                            <td><?php echo $log['tiempo_respuesta']; ?> ms</td>
                            <td><?php echo $log['reintentos']; ?></td>
                            <td><?php echo htmlspecialchars(substr($log['detalle_log'], 0, 50)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                        <tr><td colspan="7" class="empty-state">No hay logs de conexion</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($vista === 'permisos'): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <p>Roles</p>
                    <h3><?php echo $pdo->query("SELECT COUNT(*) FROM sii_roles")->fetchColumn(); ?></h3>
                </div>
                <div class="stat-card verde">
                    <p>Permisos</p>
                    <h3><?php echo $pdo->query("SELECT COUNT(*) FROM sii_permisos")->fetchColumn(); ?></h3>
                </div>
                <div class="stat-card azul">
                    <p>Asignaciones</p>
                    <h3><?php echo $pdo->query("SELECT COUNT(*) FROM sii_roles_permisos")->fetchColumn(); ?></h3>
                </div>
                <div class="stat-card naranja">
                    <p>Usuarios con Rol</p>
                    <h3><?php echo $pdo->query("SELECT COUNT(*) FROM sii_usuarios_roles")->fetchColumn(); ?></h3>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-user-shield"></i> Roles del Sistema</span>
                    <button class="btn btn-primary" onclick="document.getElementById('modalRol').classList.add('active')"><i class="fas fa-plus"></i> Nuevo Rol</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Rol</th>
                            <th>Descripcion</th>
                            <th>Permisos Asignados</th>
                            <th>Activo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $roles = $pdo->query("SELECT r.*, (SELECT COUNT(*) FROM sii_roles_permisos WHERE rol_id = r.id) as total_permisos FROM sii_roles r ORDER BY r.id")->fetchAll();
                        foreach ($roles as $rol):
                        ?>
                        <tr>
                            <td><?php echo $rol['id']; ?></td>
                            <td><strong><?php echo ucfirst(htmlspecialchars($rol['nombre_rol'])); ?></strong></td>
                            <td><?php echo htmlspecialchars($rol['descripcion']); ?></td>
                            <td><span class="badge badge-info"><?php echo $rol['total_permisos']; ?> permisos</span></td>
                            <td><?php echo $rol['activo'] ? '<i class="fas fa-check-circle" style="color:#10b981"></i>' : '<i class="fas fa-times-circle" style="color:#ef4444"></i>'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-key"></i> Permisos Disponibles</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Nombre</th>
                            <th>Descripcion</th>
                            <th>Modulo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $permisos = $pdo->query("SELECT * FROM sii_permisos ORDER BY id")->fetchAll();
                        foreach ($permisos as $p):
                        ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($p['codigo_permiso']); ?></code></td>
                            <td><?php echo htmlspecialchars($p['nombre_permiso']); ?></td>
                            <td><?php echo htmlspecialchars($p['descripcion']); ?></td>
                            <td><span class="badge badge-secondary"><?php echo htmlspecialchars($p['modulo']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-link"></i> Asignar Permiso a Rol</span>
                </div>
                <form method="POST" style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap;">
                    <input type="hidden" name="accion" value="asignar_permiso">
                    <div class="form-group" style="margin:0; flex:1; min-width:200px;">
                        <label>Rol</label>
                        <select name="rol_id" required>
                            <?php foreach ($roles as $rol): ?>
                            <option value="<?php echo $rol['id']; ?>"><?php echo ucfirst($rol['nombre_rol']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0; flex:1; min-width:200px;">
                        <label>Permiso</label>
                        <select name="permiso_id" required>
                            <?php foreach ($permisos as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo $p['nombre_permiso']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Asignar</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-list-check"></i> Permisos por Rol</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Rol</th>
                            <th>Permiso</th>
                            <th>Fecha Asignacion</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $asignaciones = $pdo->query("SELECT rp.*, r.nombre_rol, p.nombre_permiso, p.id as permiso_id FROM sii_roles_permisos rp JOIN sii_roles r ON rp.rol_id = r.id JOIN sii_permisos p ON rp.permiso_id = p.id ORDER BY r.nombre_rol, p.nombre_permiso")->fetchAll();
                        foreach ($asignaciones as $a):
                        ?>
                        <tr>
                            <td><strong><?php echo ucfirst(htmlspecialchars($a['nombre_rol'])); ?></strong></td>
                            <td><?php echo htmlspecialchars($a['nombre_permiso']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($a['fecha_asignacion'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="accion" value="quitar_permiso">
                                    <input type="hidden" name="rol_id" value="<?php echo $a['rol_id']; ?>">
                                    <input type="hidden" name="permiso_id" value="<?php echo $a['permiso_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Quitar este permiso?')"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($asignaciones)): ?>
                        <tr><td colspan="4" class="empty-state">No hay permisos asignados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-user-tag"></i> Asignar Rol a Usuario</span>
                </div>
                <form method="POST" style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap;">
                    <input type="hidden" name="accion" value="asignar_rol_usuario">
                    <div class="form-group" style="margin:0; flex:1; min-width:200px;">
                        <label>ID Usuario</label>
                        <input type="number" name="usuario_id" required placeholder="ID del usuario">
                    </div>
                    <div class="form-group" style="margin:0; flex:1; min-width:200px;">
                        <label>Rol</label>
                        <select name="rol_id" required>
                            <?php foreach ($roles as $rol): ?>
                            <option value="<?php echo $rol['id']; ?>"><?php echo ucfirst($rol['nombre_rol']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-user-plus"></i> Asignar</button>
                </form>
            </div>

            <div id="modalRol" class="modal">
                <div class="modal-content" style="max-width:500px">
                    <div class="modal-header">
                        <h3>Nuevo Rol</h3>
                        <button class="close-modal" onclick="document.getElementById('modalRol').classList.remove('active')">&times;</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="guardar_rol">
                            <div class="form-group">
                                <label>Nombre del Rol</label>
                                <input type="text" name="nombre_rol" required placeholder="ej: supervisor">
                            </div>
                            <div class="form-group">
                                <label>Descripcion</label>
                                <textarea name="descripcion" rows="3" placeholder="Descripcion del rol y sus responsabilidades"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalRol').classList.remove('active')">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>
