<?php
/**
 * MODULO: Verificacion de Facturas SII Chile
 * CONEXION REAL AL SII - APIs Oficiales
 * Sin dependencias externas - Sin AJAX - Sin sidebar
 */

$db_host = 'localhost';
$db_name = 'conectae_conectaerpbd';
$db_user = 'conectae_conectaerpuser';
$db_pass = 'pt125824caraud';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    die('Error de conexion BD: ' . $e->getMessage());
}

session_start();

$pdo->exec("
CREATE TABLE IF NOT EXISTS sii_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rut_empresa VARCHAR(20) NOT NULL,
    dv_empresa VARCHAR(1) NOT NULL,
    razon_social VARCHAR(255) NOT NULL,
    ambiente ENUM('certificacion','produccion') DEFAULT 'certificacion',
    cert_path VARCHAR(500),
    cert_pass VARCHAR(255),
    token_actual TEXT,
    token_expira DATETIME,
    ultimo_periodo_sync VARCHAR(10),
    ultima_sync DATETIME,
    activo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_dte_recibidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rut_emisor VARCHAR(20) NOT NULL,
    dv_emisor VARCHAR(1),
    razon_social_emisor VARCHAR(255),
    tipo_dte INT NOT NULL,
    folio INT NOT NULL,
    fecha_emision DATE,
    fecha_recepcion DATE,
    monto_exento DECIMAL(15,2) DEFAULT 0,
    monto_neto DECIMAL(15,2) DEFAULT 0,
    monto_iva DECIMAL(15,2) DEFAULT 0,
    monto_total DECIMAL(15,2) DEFAULT 0,
    estado_sii VARCHAR(50),
    periodo VARCHAR(10),
    sincronizado_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_dte (rut_emisor, tipo_dte, folio),
    INDEX idx_periodo (periodo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_operacion VARCHAR(50),
    periodo VARCHAR(10),
    estado ENUM('iniciado','exitoso','error','parcial') DEFAULT 'iniciado',
    registros_nuevos INT DEFAULT 0,
    registros_actualizados INT DEFAULT 0,
    mensaje TEXT,
    response_data LONGTEXT,
    duracion_ms INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_facturas_erp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_doc INT,
    folio VARCHAR(50),
    rut_proveedor VARCHAR(20),
    razon_social VARCHAR(255),
    fecha_emision DATE,
    monto_neto DECIMAL(15,2) DEFAULT 0,
    monto_iva DECIMAL(15,2) DEFAULT 0,
    monto_total DECIMAL(15,2) DEFAULT 0,
    orden_compra VARCHAR(50),
    estado ENUM('pendiente','verificada','rechazada') DEFAULT 'pendiente',
    dte_sii_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sii_verificacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dte_sii_id INT,
    factura_erp_id INT,
    estado ENUM('coincide','diferencia','solo_sii','solo_erp','pendiente') DEFAULT 'pendiente',
    diferencia_total DECIMAL(15,2) DEFAULT 0,
    observacion TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

class SIIChile {
    private $pdo;
    private $config;
    private $baseUrl;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->config = $this->pdo->query("SELECT * FROM sii_config WHERE activo = 1 LIMIT 1")->fetch();
        $this->baseUrl = ($this->config && $this->config['ambiente'] === 'produccion')
            ? 'https://palena.sii.cl' : 'https://maullin.sii.cl';
    }

    public function getSeed() {
        $url = $this->baseUrl . '/DTEWS/CrSeed.jws?wsdl';
        $xml = '<?xml version="1.0"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Body><getSeed/></soapenv:Body></soapenv:Envelope>';

        $response = $this->curlRequest($url, $xml, ['Content-Type: text/xml; charset=utf-8', 'SOAPAction: "getSeed"']);

        if ($response && preg_match('/<SEMILLA>(\d+)<\/SEMILLA>/', $response, $m)) {
            return $m[1];
        }
        return false;
    }

    public function getToken() {
        if ($this->config && $this->config['token_actual'] && strtotime($this->config['token_expira']) > time()) {
            return $this->config['token_actual'];
        }

        $seed = $this->getSeed();
        if (!$seed) {
            $this->log('get_token', '', 'error', 'No se pudo obtener semilla');
            return false;
        }

        if (!$this->config || !$this->config['cert_path'] || !file_exists($this->config['cert_path'])) {
            $this->log('get_token', '', 'error', 'Certificado no configurado o no existe');
            return false;
        }

        $certContent = file_get_contents($this->config['cert_path']);
        $certs = [];
        if (!openssl_pkcs12_read($certContent, $certs, $this->config['cert_pass'])) {
            $this->log('get_token', '', 'error', 'Error al leer certificado. Verifique contrasena.');
            return false;
        }

        $privateKey = $certs['pkey'];
        $xmlToSign = '<getToken><item><Semilla>' . $seed . '</Semilla></item></getToken>';

        $signature = '';
        openssl_sign($xmlToSign, $signature, $privateKey, OPENSSL_ALGO_SHA1);

        $signedXml = '<?xml version="1.0"?><getToken><item><Semilla>' . $seed . '</Semilla></item>
        <Signature xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo>
        <CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
        <SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
        <Reference URI=""><Transforms><Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/></Transforms>
        <DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
        <DigestValue>' . base64_encode(sha1($xmlToSign, true)) . '</DigestValue></Reference></SignedInfo>
        <SignatureValue>' . base64_encode($signature) . '</SignatureValue>
        <KeyInfo><X509Data><X509Certificate>' . base64_encode($certs['cert']) . '</X509Certificate></X509Data></KeyInfo>
        </Signature></getToken>';

        $soapXml = '<?xml version="1.0"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
        <soapenv:Body><getToken><pszXml><![CDATA[' . $signedXml . ']]></pszXml></getToken></soapenv:Body></soapenv:Envelope>';

        $url = $this->baseUrl . '/DTEWS/GetTokenFromSeed.jws?wsdl';
        $response = $this->curlRequest($url, $soapXml, ['Content-Type: text/xml; charset=utf-8', 'SOAPAction: "getToken"']);

        if ($response && preg_match('/<TOKEN>([^<]+)<\/TOKEN>/', $response, $m)) {
            $token = $m[1];
            $expira = date('Y-m-d H:i:s', strtotime('+2 hours'));
            $this->pdo->prepare("UPDATE sii_config SET token_actual=?, token_expira=? WHERE id=?")->execute([$token, $expira, $this->config['id']]);
            $this->log('get_token', '', 'exitoso', 'Token obtenido');
            return $token;
        }

        $this->log('get_token', '', 'error', 'No se pudo obtener token: ' . substr($response, 0, 300));
        return false;
    }

    public function consultarRCV($periodo, $tipo = 0) {
        $token = $this->getToken();
        if (!$token) return ['error' => 'Sin autenticacion'];

        $rut = $this->config['rut_empresa'];
        $dv = $this->config['dv_empresa'];

        $url = ($this->config['ambiente'] === 'produccion' ? 'https://www.sii.cl' : 'https://www4c.sii.cl')
            . '/cgi_rcv/RCVConsulta.cgi?' . http_build_query([
                'RutEmpresa' => $rut, 'DvEmpresa' => $dv,
                'Periodo' => $periodo, 'TipoDoc' => $tipo,
                'Estado' => 'REGISTRO', 'Token' => $token
            ]);

        $start = microtime(true);
        $response = $this->curlRequest($url, null, ['Cookie: TOKEN=' . $token], 'GET');
        $duracion = round((microtime(true) - $start) * 1000);

        if (!$response) {
            $this->log('consulta_rcv', $periodo, 'error', 'Sin respuesta', null, $duracion);
            return ['error' => 'Sin respuesta del SII'];
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = $this->parseXML($response);
        }

        $this->log('consulta_rcv', $periodo, isset($data['error']) ? 'error' : 'exitoso',
            isset($data['error']) ? $data['error'] : 'OK', $response, $duracion);

        return $data;
    }

    public function sincronizarPeriodo($periodo) {
        $tipos = [33, 34, 46, 56, 61];
        $nuevos = 0; $actualizados = 0; $errores = [];

        foreach ($tipos as $tipo) {
            $resultado = $this->consultarRCV($periodo, $tipo);
            if (isset($resultado['error'])) {
                $errores[] = "Tipo $tipo: " . $resultado['error'];
                continue;
            }

            $docs = $resultado['documentos'] ?? $resultado['data'] ?? [];
            if (!is_array($docs)) continue;

            foreach ($docs as $doc) {
                $r = $this->guardarDTE($doc, $periodo);
                if ($r === 'nuevo') $nuevos++;
                if ($r === 'actualizado') $actualizados++;
            }
        }

        $this->pdo->prepare("UPDATE sii_config SET ultimo_periodo_sync=?, ultima_sync=NOW() WHERE id=?")
            ->execute([$periodo, $this->config['id']]);

        $estado = empty($errores) ? 'exitoso' : (($nuevos + $actualizados) > 0 ? 'parcial' : 'error');
        $this->log('sync_periodo', $periodo, $estado, "Nuevos:$nuevos, Actualizados:$actualizados" .
            (empty($errores) ? '' : '. Errores: ' . implode('; ', $errores)), null, 0, $nuevos, $actualizados);

        return ['estado' => $estado, 'nuevos' => $nuevos, 'actualizados' => $actualizados, 'errores' => $errores];
    }

    private function guardarDTE($doc, $periodo) {
        $rut = $doc['RutEmisor'] ?? $doc['rut_emisor'] ?? '';
        $tipo = $doc['TipoDoc'] ?? $doc['tipo_dte'] ?? 0;
        $folio = $doc['Folio'] ?? $doc['folio'] ?? 0;

        $existe = $this->pdo->prepare("SELECT id FROM sii_dte_recibidos WHERE rut_emisor=? AND tipo_dte=? AND folio=?");
        $existe->execute([$rut, $tipo, $folio]);

        $data = [
            $rut,
            $doc['DvEmisor'] ?? $doc['dv_emisor'] ?? '',
            $doc['RazonSocial'] ?? $doc['razon_social'] ?? '',
            $tipo, $folio,
            $doc['FechaEmision'] ?? $doc['fecha_emision'] ?? null,
            $doc['FechaRecepcion'] ?? $doc['fecha_recepcion'] ?? null,
            $doc['MontoExento'] ?? $doc['monto_exento'] ?? 0,
            $doc['MontoNeto'] ?? $doc['monto_neto'] ?? 0,
            $doc['MontoIva'] ?? $doc['monto_iva'] ?? 0,
            $doc['MontoTotal'] ?? $doc['monto_total'] ?? 0,
            $doc['EstadoSII'] ?? $doc['estado'] ?? '',
            $periodo
        ];

        if ($row = $existe->fetch()) {
            $this->pdo->prepare("UPDATE sii_dte_recibidos SET razon_social_emisor=?, monto_exento=?, monto_neto=?, monto_iva=?, monto_total=?, estado_sii=?, sincronizado_at=NOW() WHERE id=?")
                ->execute([$data[2], $data[7], $data[8], $data[9], $data[10], $data[11], $row['id']]);
            return 'actualizado';
        }

        $this->pdo->prepare("INSERT INTO sii_dte_recibidos (rut_emisor,dv_emisor,razon_social_emisor,tipo_dte,folio,fecha_emision,fecha_recepcion,monto_exento,monto_neto,monto_iva,monto_total,estado_sii,periodo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute($data);
        return 'nuevo';
    }

    public function testConexion() {
        $seed = $this->getSeed();
        return $seed ? ['estado' => 'ok', 'mensaje' => 'Conexion OK. Semilla: ' . $seed]
                     : ['estado' => 'error', 'mensaje' => 'No se pudo conectar con SII'];
    }

    private function curlRequest($url, $data = null, $headers = [], $method = 'POST') {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers
        ]);
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        if ($this->config && $this->config['cert_path'] && file_exists($this->config['cert_path'])) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->config['cert_path']);
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->config['cert_pass']);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    private function parseXML($xml) {
        libxml_use_internal_errors(true);
        $doc = @simplexml_load_string($xml);
        return $doc ? json_decode(json_encode($doc), true) : ['error' => 'XML invalido'];
    }

    private function log($tipo, $periodo, $estado, $mensaje, $response = null, $duracion = 0, $nuevos = 0, $actualizados = 0) {
        $this->pdo->prepare("INSERT INTO sii_sync_log (tipo_operacion,periodo,estado,mensaje,response_data,duracion_ms,registros_nuevos,registros_actualizados) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$tipo, $periodo, $estado, $mensaje, $response, $duracion, $nuevos, $actualizados]);
    }

    public function getConfig() { return $this->config; }
}

$sii = new SIIChile($pdo);
$vista = $_GET['vista'] ?? 'dashboard';
$mensaje = ''; $tipo_mensaje = '';

$tiposDTE = [33=>'Factura Electronica',34=>'Factura Exenta',46=>'Factura Compra',56=>'Nota Debito',61=>'Nota Credito'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar_config') {
        $certPath = '';
        if (isset($_FILES['certificado']) && $_FILES['certificado']['error'] === UPLOAD_ERR_OK) {
            $dir = __DIR__ . '/../../uploads/certs/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $certPath = $dir . 'cert_' . $_POST['rut_empresa'] . '.pfx';
            move_uploaded_file($_FILES['certificado']['tmp_name'], $certPath);
        } else {
            $ex = $pdo->query("SELECT cert_path FROM sii_config LIMIT 1")->fetch();
            $certPath = $ex ? $ex['cert_path'] : '';
        }

        $existe = $pdo->query("SELECT id FROM sii_config LIMIT 1")->fetch();
        if ($existe) {
            $pdo->prepare("UPDATE sii_config SET rut_empresa=?,dv_empresa=?,razon_social=?,ambiente=?,cert_path=?,cert_pass=? WHERE id=?")
                ->execute([$_POST['rut_empresa'],$_POST['dv_empresa'],$_POST['razon_social'],$_POST['ambiente'],$certPath,$_POST['cert_pass'],$existe['id']]);
        } else {
            $pdo->prepare("INSERT INTO sii_config (rut_empresa,dv_empresa,razon_social,ambiente,cert_path,cert_pass) VALUES (?,?,?,?,?,?)")
                ->execute([$_POST['rut_empresa'],$_POST['dv_empresa'],$_POST['razon_social'],$_POST['ambiente'],$certPath,$_POST['cert_pass']]);
        }
        $mensaje = 'Configuracion guardada'; $tipo_mensaje = 'success';
    }

    if ($accion === 'test_conexion') {
        $r = $sii->testConexion();
        $mensaje = $r['mensaje'];
        $tipo_mensaje = $r['estado'] === 'ok' ? 'success' : 'error';
    }

    if ($accion === 'sincronizar') {
        $r = $sii->sincronizarPeriodo($_POST['periodo']);
        $mensaje = $r['estado'] === 'error' ? 'Error: ' . implode(', ', $r['errores'])
            : "Sincronizado. Nuevos: {$r['nuevos']}, Actualizados: {$r['actualizados']}";
        $tipo_mensaje = $r['estado'] === 'error' ? 'error' : 'success';
    }

    if ($accion === 'registrar_factura') {
        $pdo->prepare("INSERT INTO sii_facturas_erp (tipo_doc,folio,rut_proveedor,razon_social,fecha_emision,monto_neto,monto_iva,monto_total,orden_compra) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$_POST['tipo_doc'],$_POST['folio'],$_POST['rut_proveedor'],$_POST['razon_social'],$_POST['fecha_emision'],$_POST['monto_neto'],$_POST['monto_iva'],$_POST['monto_total'],$_POST['orden_compra']]);
        $mensaje = 'Factura registrada'; $tipo_mensaje = 'success';
    }

    if ($accion === 'verificar') {
        $dtes = $pdo->prepare("SELECT * FROM sii_dte_recibidos WHERE periodo=?"); $dtes->execute([$_POST['periodo']]);
        $ok = 0; $dif = 0; $solo = 0;
        foreach ($dtes->fetchAll() as $d) {
            $erp = $pdo->prepare("SELECT * FROM sii_facturas_erp WHERE tipo_doc=? AND folio=? AND rut_proveedor=?");
            $erp->execute([$d['tipo_dte'], $d['folio'], $d['rut_emisor']]);
            $f = $erp->fetch();
            if ($f) {
                $diff = abs($d['monto_total'] - $f['monto_total']);
                $estado = $diff <= 1 ? 'coincide' : 'diferencia';
                $pdo->prepare("INSERT INTO sii_verificacion (dte_sii_id,factura_erp_id,estado,diferencia_total) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE estado=VALUES(estado),diferencia_total=VALUES(diferencia_total)")
                    ->execute([$d['id'], $f['id'], $estado, $diff]);
                $estado === 'coincide' ? $ok++ : $dif++;
            } else {
                $pdo->prepare("INSERT INTO sii_verificacion (dte_sii_id,estado) VALUES (?,'solo_sii') ON DUPLICATE KEY UPDATE estado='solo_sii'")->execute([$d['id']]);
                $solo++;
            }
        }
        $mensaje = "Verificado. Coinciden: $ok, Diferencias: $dif, Solo SII: $solo"; $tipo_mensaje = 'success';
    }

    header("Location: verificacion_facturas_sii.php?vista=$vista&msg=" . urlencode($mensaje) . "&tipo=$tipo_mensaje");
    exit;
}

if (isset($_GET['msg'])) { $mensaje = $_GET['msg']; $tipo_mensaje = $_GET['tipo'] ?? 'success'; }

$config = $sii->getConfig();
$totalDTE = $pdo->query("SELECT COUNT(*) FROM sii_dte_recibidos")->fetchColumn();
$totalERP = $pdo->query("SELECT COUNT(*) FROM sii_facturas_erp")->fetchColumn();
$totalOK = $pdo->query("SELECT COUNT(*) FROM sii_verificacion WHERE estado='coincide'")->fetchColumn();
$totalDif = $pdo->query("SELECT COUNT(*) FROM sii_verificacion WHERE estado='diferencia'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificacion Facturas SII</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',sans-serif;background:#f8fafc;color:#1e293b;min-height:100vh}
        .header{background:linear-gradient(135deg,#dc2626,#ef4444);padding:20px 40px;display:flex;justify-content:space-between;align-items:center;color:white}
        .header h1{font-size:22px;display:flex;align-items:center;gap:10px}
        .header a{color:white;text-decoration:none;padding:10px 20px;background:rgba(255,255,255,.2);border-radius:8px}
        .nav-tabs{background:#fff;padding:0 40px;display:flex;gap:5px;border-bottom:2px solid #e2e8f0;overflow-x:auto}
        .nav-tab{padding:15px 18px;color:#64748b;text-decoration:none;border-bottom:3px solid transparent;font-size:13px;font-weight:500;white-space:nowrap}
        .nav-tab:hover,.nav-tab.active{color:#dc2626;border-bottom-color:#dc2626;background:rgba(220,38,38,.05)}
        .container{max-width:1600px;margin:0 auto;padding:30px 40px}
        .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:30px}
        .stat-card{background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.1);border-left:4px solid #dc2626}
        .stat-card.verde{border-left-color:#10b981}.stat-card.naranja{border-left-color:#f59e0b}.stat-card.azul{border-left-color:#3b82f6}
        .stat-card h3{font-size:32px;margin:8px 0}.stat-card p{color:#64748b;font-size:12px;text-transform:uppercase}
        .card{background:#fff;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.1);border:1px solid #e2e8f0}
        .card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid #e2e8f0}
        .card-title{font-size:18px;font-weight:600}
        .btn{padding:10px 20px;border:none;border-radius:8px;cursor:pointer;font-size:14px;font-weight:500;display:inline-flex;align-items:center;gap:8px;text-decoration:none}
        .btn-primary{background:#dc2626;color:white}.btn-primary:hover{background:#b91c1c}
        .btn-success{background:#10b981;color:white}.btn-info{background:#3b82f6;color:white}
        .btn-secondary{background:#64748b;color:white}.btn-sm{padding:6px 12px;font-size:12px}
        .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px}
        .form-group{margin-bottom:15px}
        .form-group label{display:block;margin-bottom:8px;color:#475569;font-size:14px;font-weight:500}
        .form-group input,.form-group select{width:100%;padding:12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px}
        .form-group input:focus,.form-group select:focus{outline:none;border-color:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.1)}
        table{width:100%;border-collapse:collapse;font-size:13px}
        th,td{padding:10px 12px;text-align:left;border-bottom:1px solid #e2e8f0}
        th{background:#f8fafc;color:#64748b;font-size:11px;text-transform:uppercase}
        tr:hover{background:#fef2f2}
        .badge{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600}
        .badge-success{background:#dcfce7;color:#166534}.badge-warning{background:#fef3c7;color:#92400e}
        .badge-danger{background:#fee2e2;color:#991b1b}.badge-info{background:#dbeafe;color:#1e40af}
        .alert{padding:15px 20px;border-radius:8px;margin-bottom:20px}
        .alert-success{background:#dcfce7;border:1px solid #86efac;color:#166534}
        .alert-error{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
        .info-box{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:15px;margin-bottom:20px}
        .info-box p{color:#1e40af;font-size:13px}
        .monto{font-family:monospace;text-align:right}
        .sii-badge{background:#dc2626;color:white;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:bold}
        .estado-ok{color:#10b981}.estado-error{color:#ef4444}
        @media(max-width:768px){.stats-grid{grid-template-columns:repeat(2,1fr)}}
    </style>
</head>
<body>
<div class="header">
    <h1><span class="sii-badge">SII</span> Verificacion Facturas SII Chile</h1>
    <a href="index.php"><i class="fas fa-arrow-left"></i> Volver</a>
</div>
<div class="nav-tabs">
    <a href="?vista=dashboard" class="nav-tab <?php echo $vista==='dashboard'?'active':'';?>">Dashboard</a>
    <a href="?vista=config" class="nav-tab <?php echo $vista==='config'?'active':'';?>">Configuracion</a>
    <a href="?vista=sync" class="nav-tab <?php echo $vista==='sync'?'active':'';?>">Sincronizar</a>
    <a href="?vista=dte" class="nav-tab <?php echo $vista==='dte'?'active':'';?>">DTEs SII</a>
    <a href="?vista=erp" class="nav-tab <?php echo $vista==='erp'?'active':'';?>">Facturas ERP</a>
    <a href="?vista=verificar" class="nav-tab <?php echo $vista==='verificar'?'active':'';?>">Verificar</a>
    <a href="?vista=resultados" class="nav-tab <?php echo $vista==='resultados'?'active':'';?>">Resultados</a>
    <a href="?vista=log" class="nav-tab <?php echo $vista==='log'?'active':'';?>">Log</a>
</div>
<div class="container">
<?php if($mensaje):?><div class="alert alert-<?php echo $tipo_mensaje==='success'?'success':'error';?>"><?php echo htmlspecialchars($mensaje);?></div><?php endif;?>

<?php if($vista==='dashboard'):?>
<div class="info-box"><p><strong>Estado:</strong>
<?php if($config):?><span class="estado-ok"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($config['razon_social']);?> (<?php echo $config['rut_empresa'];?>-<?php echo $config['dv_empresa'];?>) - <?php echo ucfirst($config['ambiente']);?></span>
<?php if($config['ultimo_periodo_sync']):?> | Ultima sync: <?php echo $config['ultimo_periodo_sync'];?><?php endif;?>
<?php else:?><span class="estado-error"><i class="fas fa-times-circle"></i> No configurado</span><?php endif;?></p></div>

<div class="stats-grid">
    <div class="stat-card"><p>DTEs SII</p><h3><?php echo number_format($totalDTE);?></h3></div>
    <div class="stat-card azul"><p>Facturas ERP</p><h3><?php echo number_format($totalERP);?></h3></div>
    <div class="stat-card verde"><p>Verificadas OK</p><h3><?php echo number_format($totalOK);?></h3></div>
    <div class="stat-card naranja"><p>Diferencias</p><h3><?php echo number_format($totalDif);?></h3></div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Ultimos DTEs del SII</span><a href="?vista=sync" class="btn btn-primary btn-sm"><i class="fas fa-sync"></i> Sincronizar</a></div>
    <table><thead><tr><th>Tipo</th><th>Folio</th><th>Emisor</th><th>RUT</th><th>Fecha</th><th>Neto</th><th>IVA</th><th>Total</th><th>Estado</th></tr></thead><tbody>
    <?php $dtes=$pdo->query("SELECT * FROM sii_dte_recibidos ORDER BY sincronizado_at DESC LIMIT 15")->fetchAll();
    foreach($dtes as $d):?>
    <tr><td><?php echo $d['tipo_dte'];?></td><td><strong><?php echo $d['folio'];?></strong></td><td><?php echo htmlspecialchars(substr($d['razon_social_emisor'],0,25));?></td><td><?php echo $d['rut_emisor'];?>-<?php echo $d['dv_emisor'];?></td><td><?php echo $d['fecha_emision'];?></td><td class="monto">$<?php echo number_format($d['monto_neto'],0,',','.');?></td><td class="monto">$<?php echo number_format($d['monto_iva'],0,',','.');?></td><td class="monto"><strong>$<?php echo number_format($d['monto_total'],0,',','.');?></strong></td><td><span class="badge badge-info"><?php echo $d['estado_sii']?:'N/A';?></span></td></tr>
    <?php endforeach;?>
    <?php if(empty($dtes)):?><tr><td colspan="9" style="text-align:center;padding:40px;color:#64748b">No hay DTEs. Sincronice con el SII.</td></tr><?php endif;?>
    </tbody></table>
</div>

<?php elseif($vista==='config'):?>
<div class="card">
    <div class="card-header"><span class="card-title"><i class="fas fa-cog"></i> Configuracion SII</span>
    <form method="POST" style="display:inline"><input type="hidden" name="accion" value="test_conexion"><button class="btn btn-info btn-sm"><i class="fas fa-plug"></i> Probar Conexion</button></form></div>
    <form method="POST" enctype="multipart/form-data"><input type="hidden" name="accion" value="guardar_config">
    <div class="form-grid">
        <div class="form-group"><label>RUT Empresa</label><input type="text" name="rut_empresa" value="<?php echo $config['rut_empresa']??'';?>" placeholder="12345678" required></div>
        <div class="form-group"><label>DV</label><input type="text" name="dv_empresa" value="<?php echo $config['dv_empresa']??'';?>" maxlength="1" required></div>
        <div class="form-group"><label>Razon Social</label><input type="text" name="razon_social" value="<?php echo $config['razon_social']??'';?>" required></div>
        <div class="form-group"><label>Ambiente</label><select name="ambiente"><option value="certificacion" <?php echo ($config['ambiente']??'')==='certificacion'?'selected':'';?>>Certificacion</option><option value="produccion" <?php echo ($config['ambiente']??'')==='produccion'?'selected':'';?>>Produccion</option></select></div>
        <div class="form-group"><label>Certificado (.pfx/.p12)</label><input type="file" name="certificado" accept=".pfx,.p12"><?php if(!empty($config['cert_path'])):?><small style="color:#10b981"><i class="fas fa-check"></i> Cargado</small><?php endif;?></div>
        <div class="form-group"><label>Contrasena Certificado</label><input type="password" name="cert_pass" value="<?php echo $config['cert_pass']??'';?>"></div>
    </div>
    <div class="info-box"><p><strong>Requisitos:</strong> Certificado digital vigente (.pfx) y empresa habilitada en SII.</p></div>
    <button class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button></form>
</div>

<?php elseif($vista==='sync'):?>
<div class="card">
    <div class="card-header"><span class="card-title"><span class="sii-badge">SII</span> Sincronizar Registro de Compras</span></div>
    <?php if(!$config):?><div class="alert alert-error">Configure los parametros SII primero. <a href="?vista=config">Ir a Configuracion</a></div>
    <?php else:?>
    <form method="POST"><input type="hidden" name="accion" value="sincronizar">
    <div class="form-group" style="max-width:300px"><label>Periodo (AAAAMM)</label><input type="text" name="periodo" value="<?php echo date('Ym');?>" pattern="\d{6}" required><small>Ej: <?php echo date('Ym');?> para <?php echo date('F Y');?></small></div>
    <button class="btn btn-primary"><i class="fas fa-cloud-download-alt"></i> Sincronizar</button></form>
    <div style="margin-top:20px"><h4>Periodos rapidos:</h4><div style="display:flex;gap:10px;margin-top:10px;flex-wrap:wrap">
    <?php for($i=0;$i<6;$i++):$p=date('Ym',strtotime("-$i months"));$l=date('M Y',strtotime("-$i months"));?>
    <form method="POST" style="display:inline"><input type="hidden" name="accion" value="sincronizar"><input type="hidden" name="periodo" value="<?php echo $p;?>"><button class="btn btn-secondary btn-sm"><?php echo $l;?></button></form>
    <?php endfor;?></div></div><?php endif;?>
</div>

<?php elseif($vista==='dte'):?>
<div class="card">
    <div class="card-header"><span class="card-title"><span class="sii-badge">SII</span> DTEs Recibidos</span></div>
    <table><thead><tr><th>Periodo</th><th>Tipo</th><th>Folio</th><th>Emisor</th><th>RUT</th><th>Fecha</th><th>Neto</th><th>IVA</th><th>Total</th></tr></thead><tbody>
    <?php $dtes=$pdo->query("SELECT * FROM sii_dte_recibidos ORDER BY fecha_emision DESC LIMIT 100")->fetchAll();foreach($dtes as $d):?>
    <tr><td><?php echo $d['periodo'];?></td><td><?php echo $tiposDTE[$d['tipo_dte']]??$d['tipo_dte'];?></td><td><strong><?php echo $d['folio'];?></strong></td><td><?php echo htmlspecialchars(substr($d['razon_social_emisor'],0,25));?></td><td><?php echo $d['rut_emisor'];?></td><td><?php echo $d['fecha_emision'];?></td><td class="monto">$<?php echo number_format($d['monto_neto'],0,',','.');?></td><td class="monto">$<?php echo number_format($d['monto_iva'],0,',','.');?></td><td class="monto"><strong>$<?php echo number_format($d['monto_total'],0,',','.');?></strong></td></tr>
    <?php endforeach;?></tbody></table>
</div>

<?php elseif($vista==='erp'):?>
<div class="card">
    <div class="card-header"><span class="card-title"><i class="fas fa-file-invoice"></i> Facturas ERP</span><button class="btn btn-primary btn-sm" onclick="document.getElementById('modal').style.display='flex'"><i class="fas fa-plus"></i> Nueva</button></div>
    <table><thead><tr><th>Tipo</th><th>Folio</th><th>Proveedor</th><th>RUT</th><th>Fecha</th><th>Neto</th><th>IVA</th><th>Total</th><th>OC</th><th>Estado</th></tr></thead><tbody>
    <?php $f=$pdo->query("SELECT * FROM sii_facturas_erp ORDER BY created_at DESC")->fetchAll();foreach($f as $r):?>
    <tr><td><?php echo $r['tipo_doc'];?></td><td><strong><?php echo $r['folio'];?></strong></td><td><?php echo htmlspecialchars($r['razon_social']);?></td><td><?php echo $r['rut_proveedor'];?></td><td><?php echo $r['fecha_emision'];?></td><td class="monto">$<?php echo number_format($r['monto_neto'],0,',','.');?></td><td class="monto">$<?php echo number_format($r['monto_iva'],0,',','.');?></td><td class="monto"><strong>$<?php echo number_format($r['monto_total'],0,',','.');?></strong></td><td><?php echo $r['orden_compra'];?></td><td><span class="badge badge-<?php echo $r['estado']==='verificada'?'success':($r['estado']==='rechazada'?'danger':'warning');?>"><?php echo ucfirst($r['estado']);?></span></td></tr>
    <?php endforeach;?></tbody></table>
</div>
<div id="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);align-items:center;justify-content:center;z-index:1000">
<div style="background:white;border-radius:12px;width:100%;max-width:600px;padding:25px"><h3 style="margin-bottom:20px">Nueva Factura ERP</h3>
<form method="POST"><input type="hidden" name="accion" value="registrar_factura">
<div class="form-grid">
<div class="form-group"><label>Tipo</label><select name="tipo_doc"><?php foreach($tiposDTE as $c=>$n):?><option value="<?php echo $c;?>"><?php echo $c;?> - <?php echo $n;?></option><?php endforeach;?></select></div>
<div class="form-group"><label>Folio</label><input type="text" name="folio" required></div>
<div class="form-group"><label>RUT Proveedor</label><input type="text" name="rut_proveedor" required></div>
<div class="form-group"><label>Razon Social</label><input type="text" name="razon_social" required></div>
<div class="form-group"><label>Fecha</label><input type="date" name="fecha_emision" value="<?php echo date('Y-m-d');?>" required></div>
<div class="form-group"><label>OC</label><input type="text" name="orden_compra"></div>
<div class="form-group"><label>Neto</label><input type="number" name="monto_neto" required></div>
<div class="form-group"><label>IVA</label><input type="number" name="monto_iva" required></div>
<div class="form-group"><label>Total</label><input type="number" name="monto_total" required></div>
</div>
<div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px"><button type="button" class="btn btn-secondary" onclick="document.getElementById('modal').style.display='none'">Cancelar</button><button class="btn btn-primary">Guardar</button></div>
</form></div></div>

<?php elseif($vista==='verificar'):?>
<div class="card">
    <div class="card-header"><span class="card-title"><i class="fas fa-check-double"></i> Ejecutar Verificacion</span></div>
    <form method="POST"><input type="hidden" name="accion" value="verificar">
    <div class="form-group" style="max-width:300px"><label>Periodo</label><input type="text" name="periodo" value="<?php echo date('Ym');?>" required></div>
    <p style="margin-bottom:15px;color:#64748b">Compara DTEs del SII con facturas del ERP.</p>
    <button class="btn btn-success"><i class="fas fa-search"></i> Verificar</button></form>
</div>

<?php elseif($vista==='resultados'):?>
<div class="stats-grid">
    <div class="stat-card verde"><p>Coinciden</p><h3><?php echo $pdo->query("SELECT COUNT(*) FROM sii_verificacion WHERE estado='coincide'")->fetchColumn();?></h3></div>
    <div class="stat-card naranja"><p>Diferencias</p><h3><?php echo $pdo->query("SELECT COUNT(*) FROM sii_verificacion WHERE estado='diferencia'")->fetchColumn();?></h3></div>
    <div class="stat-card"><p>Solo SII</p><h3><?php echo $pdo->query("SELECT COUNT(*) FROM sii_verificacion WHERE estado='solo_sii'")->fetchColumn();?></h3></div>
    <div class="stat-card azul"><p>Solo ERP</p><h3><?php echo $pdo->query("SELECT COUNT(*) FROM sii_verificacion WHERE estado='solo_erp'")->fetchColumn();?></h3></div>
</div>
<div class="card">
    <div class="card-header"><span class="card-title">Resultados</span></div>
    <table><thead><tr><th>Tipo</th><th>Folio</th><th>Emisor</th><th>Total SII</th><th>Total ERP</th><th>Diferencia</th><th>Estado</th></tr></thead><tbody>
    <?php $r=$pdo->query("SELECT v.*,d.tipo_dte,d.folio,d.razon_social_emisor,d.monto_total as t_sii,e.monto_total as t_erp FROM sii_verificacion v LEFT JOIN sii_dte_recibidos d ON v.dte_sii_id=d.id LEFT JOIN sii_facturas_erp e ON v.factura_erp_id=e.id ORDER BY v.created_at DESC LIMIT 100")->fetchAll();foreach($r as $v):?>
    <tr><td><?php echo $v['tipo_dte'];?></td><td><strong><?php echo $v['folio'];?></strong></td><td><?php echo htmlspecialchars(substr($v['razon_social_emisor'],0,25));?></td><td class="monto">$<?php echo number_format($v['t_sii'],0,',','.');?></td><td class="monto">$<?php echo number_format($v['t_erp'],0,',','.');?></td><td class="monto" style="color:<?php echo $v['diferencia_total']>0?'#ef4444':'#10b981';?>">$<?php echo number_format($v['diferencia_total'],0,',','.');?></td><td><span class="badge badge-<?php echo $v['estado']==='coincide'?'success':($v['estado']==='solo_sii'?'warning':'danger');?>"><?php echo ucfirst(str_replace('_',' ',$v['estado']));?></span></td></tr>
    <?php endforeach;?></tbody></table>
</div>

<?php elseif($vista==='log'):?>
<div class="card">
    <div class="card-header"><span class="card-title"><i class="fas fa-history"></i> Log Sincronizacion</span></div>
    <table><thead><tr><th>Fecha</th><th>Operacion</th><th>Periodo</th><th>Estado</th><th>Nuevos</th><th>Actualizados</th><th>Duracion</th><th>Mensaje</th></tr></thead><tbody>
    <?php $l=$pdo->query("SELECT * FROM sii_sync_log ORDER BY created_at DESC LIMIT 50")->fetchAll();foreach($l as $r):?>
    <tr><td><?php echo date('d/m/Y H:i',strtotime($r['created_at']));?></td><td><?php echo $r['tipo_operacion'];?></td><td><?php echo $r['periodo'];?></td><td><span class="badge badge-<?php echo $r['estado']==='exitoso'?'success':($r['estado']==='error'?'danger':'warning');?>"><?php echo ucfirst($r['estado']);?></span></td><td><?php echo $r['registros_nuevos'];?></td><td><?php echo $r['registros_actualizados'];?></td><td><?php echo $r['duracion_ms'];?>ms</td><td><?php echo htmlspecialchars(substr($r['mensaje'],0,60));?></td></tr>
    <?php endforeach;?></tbody></table>
</div>
<?php endif;?>
</div>
</body>
</html>
