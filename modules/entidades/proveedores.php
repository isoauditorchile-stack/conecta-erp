<?php
session_start();

// MULTIEMPRESA | MULTIPAIS | MULTIIDIOMA | MULTIUSUARIO | MULTIGESTION
$user_id = $_SESSION['user_id'] ?? 1;
$company_id = $_SESSION['company_id'] ?? 1;
$pais_id = $_SESSION['pais_id'] ?? 1;
$idioma_id = $_SESSION['idioma_id'] ?? 1;
$centro_gestion_id = $_SESSION['centro_gestion_id'] ?? 1;

// Conexion a base de datos
$host = 'localhost';
$dbname = 'conectae_conectaerpbd';
$username = 'conectae_conectaerpuser';
$password = 'pt125824caraud';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexion: " . $e->getMessage());
}

// VALIDADORES POR PAIS - Sistema Global de Identificacion
$reglas_pais = [
    'CL' => ['nombre_documento' => 'RUT', 'formato' => 'XX.XXX.XXX-X', 'validacion' => 'modulo_11', 'tiene_dv' => true],
    'AR' => ['nombre_documento' => 'CUIT', 'formato' => 'XX-XXXXXXXX-X', 'validacion' => 'modulo_11', 'tiene_dv' => true],
    'PE' => ['nombre_documento' => 'RUC', 'formato' => 'XXXXXXXXXXX', 'validacion' => 'suma_ponderada', 'tiene_dv' => false],
    'MX' => ['nombre_documento' => 'RFC', 'formato' => 'XXXX-XXXXXX-XXX', 'validacion' => 'regex', 'tiene_dv' => false],
    'US' => ['nombre_documento' => 'EIN', 'formato' => 'XX-XXXXXXX', 'validacion' => 'ninguna', 'tiene_dv' => false],
    'BR' => ['nombre_documento' => 'CNPJ', 'formato' => 'XX.XXX.XXX/XXXX-XX', 'validacion' => 'doble_dv', 'tiene_dv' => true]
];

$stmt_pais = $pdo->prepare("SELECT codigo_pais FROM cat_paises WHERE id = ?");
$stmt_pais->execute([$pais_id]);
$pais_config = $stmt_pais->fetch(PDO::FETCH_ASSOC);
$codigo_pais = $pais_config['codigo_pais'] ?? 'CL';
$reglas_documento = $reglas_pais[$codigo_pais] ?? $reglas_pais['CL'];

$mensaje = '';
$proveedor_edit = null;

// Procesar INSERT RAPIDO de catalogos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_add'])) {
    $tabla = $_POST['tabla'];
    $codigo = strtoupper(trim($_POST['codigo']));
    $nombre = trim($_POST['nombre']);

    $sql = "INSERT INTO $tabla (codigo, nombre, company_id, pais_id) VALUES (:codigo, :nombre, :company_id, :pais_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':codigo' => $codigo, ':nombre' => $nombre, ':company_id' => $company_id, ':pais_id' => $pais_id]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'codigo' => $codigo, 'nombre' => $nombre]);
    exit;
}

// Procesar formulario principal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create' || $action === 'update') {
        // Seccion 1: Identificacion
        $pais_proveedor = $_POST['pais_proveedor'];
        $tipo_documento = $_POST['tipo_documento'];
        $numero_documento = trim($_POST['numero_documento']);

        // Seccion 2: Datos Principales
        $razon_social = trim($_POST['razon_social']);
        $nombre_fantasia = trim($_POST['nombre_fantasia']);
        $alias_corto = trim($_POST['alias_corto']);

        // Seccion 3: Datos Empresa
        $empresa_id = $_POST['empresa_id'] ?: $company_id;
        $unidad_negocio = $_POST['unidad_negocio'];

        // Seccion 4: Contacto
        $emails = json_encode($_POST['emails'] ?? []);
        $telefonos = json_encode($_POST['telefonos'] ?? []);
        $sitio_web = trim($_POST['sitio_web']);

        // Seccion 5: Direccion
        $region = $_POST['region'];
        $ciudad = $_POST['ciudad'];
        $comuna = $_POST['comuna'];
        $calle = trim($_POST['calle']);
        $numero = trim($_POST['numero']);
        $depto_oficina = trim($_POST['depto_oficina']);
        $codigo_postal = trim($_POST['codigo_postal']);

        // Seccion 6: Clasificacion
        $tipo_proveedor = $_POST['tipo_proveedor'];
        $rubro_industria = $_POST['rubro_industria'];
        $categoria_financiera = $_POST['categoria_financiera'];

        // Seccion 7: Condiciones Comerciales
        $moneda_principal = $_POST['moneda_principal'];
        $forma_pago = $_POST['forma_pago'];
        $plazo_pago = intval($_POST['plazo_pago']);
        $descuento_comercial = floatval($_POST['descuento_comercial']);
        $condicion_pago_id = $_POST['condicion_pago_id'] ?: NULL;

        // Seccion 8: Datos Bancarios
        $banco = $_POST['banco'];
        $tipo_cuenta = $_POST['tipo_cuenta'];
        $numero_cuenta = trim($_POST['numero_cuenta']);
        $swift_aba_iban = trim($_POST['swift_aba_iban']);
        $titular_cuenta = trim($_POST['titular_cuenta']);
        $email_bancario = trim($_POST['email_bancario']);

        // Seccion 9: Responsabilidad
        $ejecutivo_responsable = $_POST['ejecutivo_responsable'] ?: NULL;
        $estado_proveedor = $_POST['estado_proveedor'];

        // Seccion 10: Documentos (rutas guardadas)
        $documentos = json_encode($_POST['documentos'] ?? []);

        // Seccion 11: Notas
        $notas_internas = trim($_POST['notas_internas']);
        $notas_compras = trim($_POST['notas_compras']);
        $notas_contabilidad = trim($_POST['notas_contabilidad']);

        // Datos adicionales
        $giro = trim($_POST['giro']);
        $actividad_economica = trim($_POST['actividad_economica']);
        $calificacion = $_POST['calificacion'] ?: NULL;
        $activo = isset($_POST['activo']) ? 1 : 0;

        if ($action === 'create') {
            $sql = "INSERT INTO cat_proveedores (
                company_id, pais_id, idioma_id, centro_gestion_id, user_id,
                pais_proveedor, tipo_documento, numero_documento, razon_social, nombre_fantasia, alias_corto,
                empresa_id, unidad_negocio, emails, telefonos, sitio_web,
                region, ciudad, comuna, calle, numero, depto_oficina, codigo_postal,
                tipo_proveedor, rubro_industria, categoria_financiera,
                moneda_principal, forma_pago, plazo_pago, descuento_comercial, condicion_pago_id,
                banco, tipo_cuenta, numero_cuenta, swift_aba_iban, titular_cuenta, email_bancario,
                ejecutivo_responsable, estado_proveedor, documentos,
                notas_internas, notas_compras, notas_contabilidad,
                giro, actividad_economica, calificacion, activo
            ) VALUES (
                :company_id, :pais_id, :idioma_id, :centro_gestion_id, :user_id,
                :pais_proveedor, :tipo_documento, :numero_documento, :razon_social, :nombre_fantasia, :alias_corto,
                :empresa_id, :unidad_negocio, :emails, :telefonos, :sitio_web,
                :region, :ciudad, :comuna, :calle, :numero, :depto_oficina, :codigo_postal,
                :tipo_proveedor, :rubro_industria, :categoria_financiera,
                :moneda_principal, :forma_pago, :plazo_pago, :descuento_comercial, :condicion_pago_id,
                :banco, :tipo_cuenta, :numero_cuenta, :swift_aba_iban, :titular_cuenta, :email_bancario,
                :ejecutivo_responsable, :estado_proveedor, :documentos,
                :notas_internas, :notas_compras, :notas_contabilidad,
                :giro, :actividad_economica, :calificacion, :activo
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':company_id' => $company_id, ':pais_id' => $pais_id, ':idioma_id' => $idioma_id,
                ':centro_gestion_id' => $centro_gestion_id, ':user_id' => $user_id,
                ':pais_proveedor' => $pais_proveedor, ':tipo_documento' => $tipo_documento, ':numero_documento' => $numero_documento,
                ':razon_social' => $razon_social, ':nombre_fantasia' => $nombre_fantasia, ':alias_corto' => $alias_corto,
                ':empresa_id' => $empresa_id, ':unidad_negocio' => $unidad_negocio, ':emails' => $emails,
                ':telefonos' => $telefonos, ':sitio_web' => $sitio_web, ':region' => $region,
                ':ciudad' => $ciudad, ':comuna' => $comuna, ':calle' => $calle, ':numero' => $numero,
                ':depto_oficina' => $depto_oficina, ':codigo_postal' => $codigo_postal,
                ':tipo_proveedor' => $tipo_proveedor, ':rubro_industria' => $rubro_industria,
                ':categoria_financiera' => $categoria_financiera, ':moneda_principal' => $moneda_principal,
                ':forma_pago' => $forma_pago, ':plazo_pago' => $plazo_pago, ':descuento_comercial' => $descuento_comercial,
                ':condicion_pago_id' => $condicion_pago_id, ':banco' => $banco, ':tipo_cuenta' => $tipo_cuenta,
                ':numero_cuenta' => $numero_cuenta, ':swift_aba_iban' => $swift_aba_iban,
                ':titular_cuenta' => $titular_cuenta, ':email_bancario' => $email_bancario,
                ':ejecutivo_responsable' => $ejecutivo_responsable, ':estado_proveedor' => $estado_proveedor,
                ':documentos' => $documentos, ':notas_internas' => $notas_internas,
                ':notas_compras' => $notas_compras, ':notas_contabilidad' => $notas_contabilidad,
                ':giro' => $giro, ':actividad_economica' => $actividad_economica,
                ':calificacion' => $calificacion, ':activo' => $activo
            ]);

            $mensaje = "Proveedor creado exitosamente";

            if (isset($_POST['guardar_y_crear_otro'])) {
                header("Location: ?nuevo=1&msg=" . urlencode($mensaje));
                exit;
            } elseif (isset($_POST['guardar_y_contrato'])) {
                header("Location: contratos.php?proveedor_id=" . $pdo->lastInsertId());
                exit;
            }
        } else {
            $id = intval($_POST['id']);
            $sql = "UPDATE cat_proveedores SET
                pais_proveedor=:pais_proveedor, tipo_documento=:tipo_documento, numero_documento=:numero_documento,
                razon_social=:razon_social, nombre_fantasia=:nombre_fantasia, alias_corto=:alias_corto,
                empresa_id=:empresa_id, unidad_negocio=:unidad_negocio, emails=:emails, telefonos=:telefonos, sitio_web=:sitio_web,
                region=:region, ciudad=:ciudad, comuna=:comuna, calle=:calle, numero=:numero, depto_oficina=:depto_oficina, codigo_postal=:codigo_postal,
                tipo_proveedor=:tipo_proveedor, rubro_industria=:rubro_industria, categoria_financiera=:categoria_financiera,
                moneda_principal=:moneda_principal, forma_pago=:forma_pago, plazo_pago=:plazo_pago, descuento_comercial=:descuento_comercial, condicion_pago_id=:condicion_pago_id,
                banco=:banco, tipo_cuenta=:tipo_cuenta, numero_cuenta=:numero_cuenta, swift_aba_iban=:swift_aba_iban, titular_cuenta=:titular_cuenta, email_bancario=:email_bancario,
                ejecutivo_responsable=:ejecutivo_responsable, estado_proveedor=:estado_proveedor, documentos=:documentos,
                notas_internas=:notas_internas, notas_compras=:notas_compras, notas_contabilidad=:notas_contabilidad,
                giro=:giro, actividad_economica=:actividad_economica, calificacion=:calificacion, activo=:activo, updated_at=NOW()
            WHERE id=:id AND company_id=:company_id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id, ':company_id' => $company_id,
                ':pais_proveedor' => $pais_proveedor, ':tipo_documento' => $tipo_documento, ':numero_documento' => $numero_documento,
                ':razon_social' => $razon_social, ':nombre_fantasia' => $nombre_fantasia, ':alias_corto' => $alias_corto,
                ':empresa_id' => $empresa_id, ':unidad_negocio' => $unidad_negocio, ':emails' => $emails,
                ':telefonos' => $telefonos, ':sitio_web' => $sitio_web, ':region' => $region,
                ':ciudad' => $ciudad, ':comuna' => $comuna, ':calle' => $calle, ':numero' => $numero,
                ':depto_oficina' => $depto_oficina, ':codigo_postal' => $codigo_postal,
                ':tipo_proveedor' => $tipo_proveedor, ':rubro_industria' => $rubro_industria,
                ':categoria_financiera' => $categoria_financiera, ':moneda_principal' => $moneda_principal,
                ':forma_pago' => $forma_pago, ':plazo_pago' => $plazo_pago, ':descuento_comercial' => $descuento_comercial,
                ':condicion_pago_id' => $condicion_pago_id, ':banco' => $banco, ':tipo_cuenta' => $tipo_cuenta,
                ':numero_cuenta' => $numero_cuenta, ':swift_aba_iban' => $swift_aba_iban,
                ':titular_cuenta' => $titular_cuenta, ':email_bancario' => $email_bancario,
                ':ejecutivo_responsable' => $ejecutivo_responsable, ':estado_proveedor' => $estado_proveedor,
                ':documentos' => $documentos, ':notas_internas' => $notas_internas,
                ':notas_compras' => $notas_compras, ':notas_contabilidad' => $notas_contabilidad,
                ':giro' => $giro, ':actividad_economica' => $actividad_economica,
                ':calificacion' => $calificacion, ':activo' => $activo
            ]);

            $mensaje = "Proveedor actualizado exitosamente";
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM cat_proveedores WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, $company_id]);
        $mensaje = "Proveedor eliminado exitosamente";
    }
}

// Cargar para edicion
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM cat_proveedores WHERE id = ? AND company_id = ?");
    $stmt->execute([$id, $company_id]);
    $proveedor_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Listar proveedores
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM cat_proveedores WHERE company_id = ?";
$params = [$company_id];
if ($search) {
    $sql .= " AND (razon_social LIKE ? OR numero_documento LIKE ? OR alias_corto LIKE ?)";
    $s = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s;
}
$sql .= " ORDER BY razon_social ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar catalogos
$paises = $pdo->query("SELECT * FROM cat_paises ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$empresas = $pdo->query("SELECT id, nombre FROM cat_empresas WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$ejecutivos = $pdo->query("SELECT id, CONCAT(nombres, ' ', apellidos) as nombre FROM rrhh_empleados WHERE company_id = $company_id AND activo = 1 ORDER BY nombres")->fetchAll(PDO::FETCH_ASSOC);
$condiciones_pago = $pdo->query("SELECT * FROM cat_condiciones_pago WHERE company_id = $company_id ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

$regiones_chile = [
    'XV'=>'Arica y Parinacota','I'=>'Tarapaca','II'=>'Antofagasta','III'=>'Atacama','IV'=>'Coquimbo',
    'V'=>'Valparaiso','RM'=>'Region Metropolitana','VI'=>'OHiggins','VII'=>'Maule','XVI'=>'Nuble',
    'VIII'=>'Biobio','IX'=>'Araucania','XIV'=>'Los Rios','X'=>'Los Lagos','XI'=>'Aysen','XII'=>'Magallanes'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maestro de Proveedores - CONECTA ERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 40px;
        }
        .header h1 { font-size: 32px; font-weight: 800; margin-bottom: 10px; }
        .header-info { font-size: 13px; opacity: 0.95; }
        .content { padding: 40px; }
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 600;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 2px solid #10b981; }
        .form-section {
            background: #f9fafb;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px solid #e5e7eb;
        }
        .form-section-title {
            font-size: 20px;
            font-weight: 800;
            color: #667eea;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 4px solid #667eea;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: 1 / -1; }
        label {
            font-weight: 700;
            margin-bottom: 10px;
            color: #374151;
            font-size: 14px;
        }
        label.required::after { content: " *"; color: #ef4444; }
        input, textarea, select {
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        textarea { resize: vertical; min-height: 120px; }
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,0.4); }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-sm { padding: 10px 20px; font-size: 13px; }
        .form-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 3px solid #e5e7eb;
        }
        .quick-add {
            display: none;
            margin-top: 12px;
            padding: 20px;
            background: #f3f4f6;
            border-radius: 12px;
            border: 2px dashed #667eea;
        }
        .quick-add.active { display: block; }
        .quick-add-grid {
            display: grid;
            grid-template-columns: 1fr 2fr auto auto;
            gap: 12px;
            align-items: end;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 3px solid #e5e7eb;
        }
        .tab {
            padding: 15px 30px;
            cursor: pointer;
            border: none;
            background: none;
            font-weight: 700;
            color: #6b7280;
            border-bottom: 4px solid transparent;
            transition: all 0.3s;
            font-size: 15px;
        }
        .tab.active { color: #667eea; border-bottom-color: #667eea; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .multi-input-container {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            background: white;
        }
        .multi-input-item {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }
        .multi-input-item input { flex: 1; }
        .table-container {
            overflow-x: auto;
            border-radius: 15px;
            border: 2px solid #e5e7eb;
        }
        table { width: 100%; border-collapse: collapse; }
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        th {
            padding: 18px;
            text-align: left;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        td {
            padding: 18px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        tbody tr:hover { background: #f9fafb; }
        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .search-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .search-bar input { flex: 1; min-width: 300px; }
        .doc-feedback {
            display: none;
            font-size: 13px;
            margin-top: 8px;
            font-weight: 700;
        }
        .doc-feedback.valid { color: #10b981; display: block; }
        .doc-feedback.invalid { color: #ef4444; display: block; }
        .file-upload-area {
            border: 3px dashed #667eea;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            background: #f9fafb;
            cursor: pointer;
        }
        .file-upload-area:hover { background: #f3f4f6; }
        @media print {
            body { background: white; padding: 0; }
            .btn, .search-bar, .form-actions, .tabs { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MAESTRO DE PROVEEDORES</h1>
            <div class="header-info">
                <strong>MULTIEMPRESA | MULTIPAIS | MULTIIDIOMA | MULTIGESTION</strong><br>
                Empresa: <?php echo $company_id; ?> | Pais: <?php echo $codigo_pais; ?> | Usuario: <?php echo $user_id; ?> | Documento: <?php echo $reglas_documento['nombre_documento']; ?>
            </div>
        </div>

        <div class="content">
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <div class="tabs">
                <button class="tab active" onclick="switchTab('form')">FORMULARIO CREAR PROVEEDOR</button>
                <button class="tab" onclick="switchTab('list')">LISTADO DE PROVEEDORES</button>
            </div>

            <div id="tab-form" class="tab-content active">
                <form method="POST" id="formProveedor" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo $proveedor_edit ? 'update' : 'create'; ?>">
                    <?php if ($proveedor_edit): ?>
                        <input type="hidden" name="id" value="<?php echo $proveedor_edit['id']; ?>">
                    <?php endif; ?>

                    <!-- SECCION 1: IDENTIFICACION DEL PROVEEDOR POR PAIS -->
                    <div class="form-section">
                        <h3 class="form-section-title">1. IDENTIFICACION DEL PROVEEDOR POR PAIS</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="required">Pais del Proveedor</label>
                                <select name="pais_proveedor" id="pais_select" required onchange="toggleQuickAdd(this, 'pais_quick')">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($paises as $p): ?>
                                        <option value="<?php echo $p['codigo_pais']; ?>" <?php echo ($proveedor_edit['pais_proveedor'] ?? $codigo_pais) === $p['codigo_pais'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="__NUEVO__">AGREGAR NUEVO PAIS...</option>
                                </select>
                                <div id="pais_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_pais_codigo" placeholder="Codigo (CL, AR, PE)">
                                        <input type="text" name="quick_pais_nombre" placeholder="Nombre del Pais">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_paises', 'pais_select', 'pais_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('pais_quick', 'pais_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="required">Tipo de Documento</label>
                                <select name="tipo_documento" id="tipo_doc_select" required onchange="toggleQuickAdd(this, 'tipo_doc_quick')">
                                    <option value="">Seleccione...</option>
                                    <option value="RUT" <?php echo ($proveedor_edit['tipo_documento'] ?? '') === 'RUT' ? 'selected' : ''; ?>>RUT (Chile)</option>
                                    <option value="CUIT" <?php echo ($proveedor_edit['tipo_documento'] ?? '') === 'CUIT' ? 'selected' : ''; ?>>CUIT (Argentina)</option>
                                    <option value="RUC" <?php echo ($proveedor_edit['tipo_documento'] ?? '') === 'RUC' ? 'selected' : ''; ?>>RUC (Peru)</option>
                                    <option value="RFC" <?php echo ($proveedor_edit['tipo_documento'] ?? '') === 'RFC' ? 'selected' : ''; ?>>RFC (Mexico)</option>
                                    <option value="EIN" <?php echo ($proveedor_edit['tipo_documento'] ?? '') === 'EIN' ? 'selected' : ''; ?>>EIN (USA)</option>
                                    <option value="CNPJ" <?php echo ($proveedor_edit['tipo_documento'] ?? '') === 'CNPJ' ? 'selected' : ''; ?>>CNPJ (Brasil)</option>
                                    <option value="__NUEVO__">CREAR NUEVO TIPO...</option>
                                </select>
                                <div id="tipo_doc_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_tipodoc_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_tipodoc_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_tipos_documento', 'tipo_doc_select', 'tipo_doc_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('tipo_doc_quick', 'tipo_doc_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="required">Numero de Identificacion</label>
                                <input type="text" name="numero_documento" id="docInput" value="<?php echo $proveedor_edit['numero_documento'] ?? ''; ?>" required onblur="validarDocumento()">
                                <div id="docError" class="doc-feedback invalid">Documento invalido</div>
                                <div id="docValid" class="doc-feedback valid">Documento valido</div>
                            </div>
                        </div>
                    </div>

                    <!-- SECCION 2: DATOS PRINCIPALES -->
                    <div class="form-section">
                        <h3 class="form-section-title">2. DATOS PRINCIPALES DEL PROVEEDOR</h3>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label class="required">Razon Social</label>
                                <input type="text" name="razon_social" value="<?php echo $proveedor_edit['razon_social'] ?? ''; ?>" required placeholder="Nombre legal del proveedor">
                            </div>

                            <div class="form-group">
                                <label>Nombre de Fantasia / Comercial</label>
                                <input type="text" name="nombre_fantasia" value="<?php echo $proveedor_edit['nombre_fantasia'] ?? ''; ?>" placeholder="Nombre comercial">
                            </div>

                            <div class="form-group">
                                <label>Alias Corto</label>
                                <input type="text" name="alias_corto" value="<?php echo $proveedor_edit['alias_corto'] ?? ''; ?>" placeholder="Para busquedas rapidas">
                            </div>
                        </div>
                    </div>

                    <!-- SECCION 3: DATOS DE EMPRESA (MULTIEMPRESA) -->
                    <div class="form-section">
                        <h3 class="form-section-title">3. DATOS DE EMPRESA (MULTIEMPRESA)</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="required">Empresa del ERP</label>
                                <select name="empresa_id" id="empresa_select" required onchange="toggleQuickAdd(this, 'empresa_quick')">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($empresas as $emp): ?>
                                        <option value="<?php echo $emp['id']; ?>" <?php echo ($proveedor_edit['empresa_id'] ?? $company_id) == $emp['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($emp['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="__NUEVO__">CREAR NUEVA EMPRESA...</option>
                                </select>
                                <div id="empresa_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_empresa_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_empresa_nombre" placeholder="Nombre Empresa">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_empresas', 'empresa_select', 'empresa_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('empresa_quick', 'empresa_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Unidad de Negocio</label>
                                <select name="unidad_negocio" id="unidad_select" onchange="toggleQuickAdd(this, 'unidad_quick')">
                                    <option value="">Seleccione...</option>
                                    <option value="RETAIL" <?php echo ($proveedor_edit['unidad_negocio'] ?? '') === 'RETAIL' ? 'selected' : ''; ?>>Retail</option>
                                    <option value="TI" <?php echo ($proveedor_edit['unidad_negocio'] ?? '') === 'TI' ? 'selected' : ''; ?>>Tecnologia</option>
                                    <option value="LOGISTICA" <?php echo ($proveedor_edit['unidad_negocio'] ?? '') === 'LOGISTICA' ? 'selected' : ''; ?>>Logistica</option>
                                    <option value="PRODUCCION" <?php echo ($proveedor_edit['unidad_negocio'] ?? '') === 'PRODUCCION' ? 'selected' : ''; ?>>Produccion</option>
                                    <option value="__NUEVO__">CREAR NUEVA UNIDAD...</option>
                                </select>
                                <div id="unidad_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_unidad_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_unidad_nombre" placeholder="Nombre Unidad">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_unidades_negocio', 'unidad_select', 'unidad_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('unidad_quick', 'unidad_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECCION 4: DATOS DE CONTACTO (MULTIPLES) -->
                    <div class="form-section">
                        <h3 class="form-section-title">4. DATOS DE CONTACTO DEL PROVEEDOR</h3>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>Correos Electronicos</label>
                                <div class="multi-input-container" id="emailsContainer">
                                    <div class="multi-input-item">
                                        <input type="email" name="emails[]" placeholder="Email principal">
                                        <button type="button" class="btn btn-sm btn-success" onclick="addEmailField()">+ Agregar Email</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label>Telefonos</label>
                                <div class="multi-input-container" id="telefonosContainer">
                                    <div class="multi-input-item">
                                        <input type="text" name="telefonos[]" placeholder="Telefono principal">
                                        <button type="button" class="btn btn-sm btn-success" onclick="addTelefonoField()">+ Agregar Telefono</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label>Sitio Web</label>
                                <input type="text" name="sitio_web" value="<?php echo $proveedor_edit['sitio_web'] ?? ''; ?>" placeholder="https://www.proveedor.com">
                            </div>
                        </div>
                    </div>

                    <!-- SECCION 5: DIRECCION -->
                    <div class="form-section">
                        <h3 class="form-section-title">5. DIRECCION DEL PROVEEDOR</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Region / Estado</label>
                                <select name="region" id="region_select" onchange="toggleQuickAdd(this, 'region_quick')">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($regiones_chile as $cod => $nom): ?>
                                        <option value="<?php echo $cod; ?>" <?php echo ($proveedor_edit['region'] ?? '') === $cod ? 'selected' : ''; ?>><?php echo $nom; ?></option>
                                    <?php endforeach; ?>
                                    <option value="__NUEVO__">AGREGAR NUEVA REGION...</option>
                                </select>
                                <div id="region_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_region_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_region_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_regiones', 'region_select', 'region_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('region_quick', 'region_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Ciudad</label>
                                <select name="ciudad" id="ciudad_select" onchange="toggleQuickAdd(this, 'ciudad_quick')">
                                    <option value="">Seleccione...</option>
                                    <option value="SANTIAGO" <?php echo ($proveedor_edit['ciudad'] ?? '') === 'SANTIAGO' ? 'selected' : ''; ?>>Santiago</option>
                                    <option value="VALPARAISO" <?php echo ($proveedor_edit['ciudad'] ?? '') === 'VALPARAISO' ? 'selected' : ''; ?>>Valparaiso</option>
                                    <option value="CONCEPCION" <?php echo ($proveedor_edit['ciudad'] ?? '') === 'CONCEPCION' ? 'selected' : ''; ?>>Concepcion</option>
                                    <option value="__NUEVO__">AGREGAR NUEVA CIUDAD...</option>
                                </select>
                                <div id="ciudad_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_ciudad_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_ciudad_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_ciudades', 'ciudad_select', 'ciudad_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('ciudad_quick', 'ciudad_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Comuna / Distrito</label>
                                <select name="comuna" id="comuna_select" onchange="toggleQuickAdd(this, 'comuna_quick')">
                                    <option value="">Seleccione...</option>
                                    <option value="LAS CONDES" <?php echo ($proveedor_edit['comuna'] ?? '') === 'LAS CONDES' ? 'selected' : ''; ?>>Las Condes</option>
                                    <option value="PROVIDENCIA" <?php echo ($proveedor_edit['comuna'] ?? '') === 'PROVIDENCIA' ? 'selected' : ''; ?>>Providencia</option>
                                    <option value="MAIPU" <?php echo ($proveedor_edit['comuna'] ?? '') === 'MAIPU' ? 'selected' : ''; ?>>Maipu</option>
                                    <option value="__NUEVO__">AGREGAR NUEVA COMUNA...</option>
                                </select>
                                <div id="comuna_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_comuna_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_comuna_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_comunas', 'comuna_select', 'comuna_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('comuna_quick', 'comuna_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label>Calle</label>
                                <input type="text" name="calle" value="<?php echo $proveedor_edit['calle'] ?? ''; ?>" placeholder="Nombre de la calle">
                            </div>

                            <div class="form-group">
                                <label>Numero</label>
                                <input type="text" name="numero" value="<?php echo $proveedor_edit['numero'] ?? ''; ?>" placeholder="Numero">
                            </div>

                            <div class="form-group">
                                <label>Depto / Oficina / Bodega</label>
                                <input type="text" name="depto_oficina" value="<?php echo $proveedor_edit['depto_oficina'] ?? ''; ?>" placeholder="Depto/Oficina">
                            </div>

                            <div class="form-group">
                                <label>Codigo Postal</label>
                                <input type="text" name="codigo_postal" value="<?php echo $proveedor_edit['codigo_postal'] ?? ''; ?>" placeholder="Codigo postal">
                            </div>
                        </div>
                    </div>

                    <!-- SECCION 6: CLASIFICACION -->
                    <div class="form-section">
                        <h3 class="form-section-title">6. CLASIFICACION DEL PROVEEDOR</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Tipo de Proveedor</label>
                                <select name="tipo_proveedor" id="tipo_prov_select" onchange="toggleQuickAdd(this, 'tipo_prov_quick')">
                                    <option value="">Seleccione...</option>
                                    <option value="SERVICIOS" <?php echo ($proveedor_edit['tipo_proveedor'] ?? '') === 'SERVICIOS' ? 'selected' : ''; ?>>Servicios</option>
                                    <option value="PRODUCTOS" <?php echo ($proveedor_edit['tipo_proveedor'] ?? '') === 'PRODUCTOS' ? 'selected' : ''; ?>>Productos</option>
                                    <option value="CONSULTORIA" <?php echo ($proveedor_edit['tipo_proveedor'] ?? '') === 'CONSULTORIA' ? 'selected' : ''; ?>>Consultoria</option>
                                    <option value="TRANSPORTE" <?php echo ($proveedor_edit['tipo_proveedor'] ?? '') === 'TRANSPORTE' ? 'selected' : ''; ?>>Transporte</option>
                                    <option value="INSUMOS_TI" <?php echo ($proveedor_edit['tipo_proveedor'] ?? '') === 'INSUMOS_TI' ? 'selected' : ''; ?>>Insumos TI</option>
                                    <option value="__NUEVO__">AGREGAR NUEVO TIPO...</option>
                                </select>
                                <div id="tipo_prov_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_tipoprov_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_tipoprov_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_tipos_proveedor', 'tipo_prov_select', 'tipo_prov_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('tipo_prov_quick', 'tipo_prov_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Rubro / Industria</label>
                                <select name="rubro_industria" id="rubro_select" onchange="toggleQuickAdd(this, 'rubro_quick')">
                                    <option value="">Seleccione...</option>
                                    <option value="TECNOLOGIA" <?php echo ($proveedor_edit['rubro_industria'] ?? '') === 'TECNOLOGIA' ? 'selected' : ''; ?>>Tecnologia</option>
                                    <option value="ALIMENTOS" <?php echo ($proveedor_edit['rubro_industria'] ?? '') === 'ALIMENTOS' ? 'selected' : ''; ?>>Alimentos</option>
                                    <option value="CONSTRUCCION" <?php echo ($proveedor_edit['rubro_industria'] ?? '') === 'CONSTRUCCION' ? 'selected' : ''; ?>>Construccion</option>
                                    <option value="RETAIL" <?php echo ($proveedor_edit['rubro_industria'] ?? '') === 'RETAIL' ? 'selected' : ''; ?>>Retail</option>
                                    <option value="__NUEVO__">AGREGAR NUEVO RUBRO...</option>
                                </select>
                                <div id="rubro_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_rubro_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_rubro_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_rubros', 'rubro_select', 'rubro_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('rubro_quick', 'rubro_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Categoria Financiera</label>
                                <select name="categoria_financiera" id="catfin_select" onchange="toggleQuickAdd(this, 'catfin_quick')">
                                    <option value="">Seleccione...</option>
                                    <option value="NORMAL" <?php echo ($proveedor_edit['categoria_financiera'] ?? '') === 'NORMAL' ? 'selected' : ''; ?>>Normal</option>
                                    <option value="CRITICO" <?php echo ($proveedor_edit['categoria_financiera'] ?? '') === 'CRITICO' ? 'selected' : ''; ?>>Critico</option>
                                    <option value="PREFERENTE" <?php echo ($proveedor_edit['categoria_financiera'] ?? '') === 'PREFERENTE' ? 'selected' : ''; ?>>Preferente</option>
                                    <option value="RESTRINGIDO" <?php echo ($proveedor_edit['categoria_financiera'] ?? '') === 'RESTRINGIDO' ? 'selected' : ''; ?>>Restringido</option>
                                    <option value="__NUEVO__">AGREGAR NUEVA CATEGORIA...</option>
                                </select>
                                <div id="catfin_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_catfin_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_catfin_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_categorias_financieras', 'catfin_select', 'catfin_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('catfin_quick', 'catfin_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECCION 7: CONDICIONES COMERCIALES -->
                    <div class="form-section">
                        <h3 class="form-section-title">7. CONDICIONES COMERCIALES</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Moneda Principal</label>
                                <select name="moneda_principal" id="moneda_select" onchange="toggleQuickAdd(this, 'moneda_quick')">
                                    <option value="">Seleccione...</option>
                                    <option value="CLP" <?php echo ($proveedor_edit['moneda_principal'] ?? 'CLP') === 'CLP' ? 'selected' : ''; ?>>CLP - Peso Chileno</option>
                                    <option value="USD" <?php echo ($proveedor_edit['moneda_principal'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD - Dolar</option>
                                    <option value="EUR" <?php echo ($proveedor_edit['moneda_principal'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                                    <option value="ARS" <?php echo ($proveedor_edit['moneda_principal'] ?? '') === 'ARS' ? 'selected' : ''; ?>>ARS - Peso Argentino</option>
                                    <option value="PEN" <?php echo ($proveedor_edit['moneda_principal'] ?? '') === 'PEN' ? 'selected' : ''; ?>>PEN - Sol Peruano</option>
                                    <option value="MXN" <?php echo ($proveedor_edit['moneda_principal'] ?? '') === 'MXN' ? 'selected' : ''; ?>>MXN - Peso Mexicano</option>
                                    <option value="__NUEVO__">CREAR NUEVA MONEDA...</option>
                                </select>
                                <div id="moneda_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_moneda_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_moneda_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_monedas', 'moneda_select', 'moneda_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('moneda_quick', 'moneda_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Forma de Pago</label>
                                <select name="forma_pago" id="formapago_select" onchange="toggleQuickAdd(this, 'formapago_quick')">
                                    <option value="">Seleccione...</option>
                                    <option value="TRANSFERENCIA" <?php echo ($proveedor_edit['forma_pago'] ?? '') === 'TRANSFERENCIA' ? 'selected' : ''; ?>>Transferencia</option>
                                    <option value="CHEQUE" <?php echo ($proveedor_edit['forma_pago'] ?? '') === 'CHEQUE' ? 'selected' : ''; ?>>Cheque</option>
                                    <option value="TARJETA_CREDITO" <?php echo ($proveedor_edit['forma_pago'] ?? '') === 'TARJETA_CREDITO' ? 'selected' : ''; ?>>Tarjeta de Credito</option>
                                    <option value="PAGO_CONTRA_ENTREGA" <?php echo ($proveedor_edit['forma_pago'] ?? '') === 'PAGO_CONTRA_ENTREGA' ? 'selected' : ''; ?>>Pago contra Entrega</option>
                                    <option value="__NUEVO__">AGREGAR NUEVA FORMA...</option>
                                </select>
                                <div id="formapago_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_formapago_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_formapago_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_formas_pago', 'formapago_select', 'formapago_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('formapago_quick', 'formapago_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Plazo de Pago (dias)</label>
                                <select name="plazo_pago" id="plazo_select" onchange="toggleQuickAdd(this, 'plazo_quick')">
                                    <option value="0" <?php echo ($proveedor_edit['plazo_pago'] ?? 0) == 0 ? 'selected' : ''; ?>>0 dias (Contado)</option>
                                    <option value="15" <?php echo ($proveedor_edit['plazo_pago'] ?? 0) == 15 ? 'selected' : ''; ?>>15 dias</option>
                                    <option value="30" <?php echo ($proveedor_edit['plazo_pago'] ?? 0) == 30 ? 'selected' : ''; ?>>30 dias</option>
                                    <option value="60" <?php echo ($proveedor_edit['plazo_pago'] ?? 0) == 60 ? 'selected' : ''; ?>>60 dias</option>
                                    <option value="90" <?php echo ($proveedor_edit['plazo_pago'] ?? 0) == 90 ? 'selected' : ''; ?>>90 dias</option>
                                    <option value="__NUEVO__">AGREGAR NUEVO PLAZO...</option>
                                </select>
                                <div id="plazo_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="number" name="quick_plazo_dias" placeholder="Dias">
                                        <input type="text" name="quick_plazo_nombre" placeholder="Descripcion">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_plazos_pago', 'plazo_select', 'plazo_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('plazo_quick', 'plazo_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Descuento Comercial (%)</label>
                                <input type="number" step="0.01" name="descuento_comercial" value="<?php echo $proveedor_edit['descuento_comercial'] ?? '0'; ?>" min="0" max="100">
                            </div>

                            <div class="form-group">
                                <label>Condicion de Pago</label>
                                <select name="condicion_pago_id" id="condpago_select" onchange="toggleQuickAdd(this, 'condpago_quick')">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($condiciones_pago as $cp): ?>
                                        <option value="<?php echo $cp['id']; ?>" <?php echo ($proveedor_edit['condicion_pago_id'] ?? '') == $cp['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cp['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="__NUEVO__">CREAR NUEVA CONDICION...</option>
                                </select>
                                <div id="condpago_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_condpago_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_condpago_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_condiciones_pago', 'condpago_select', 'condpago_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('condpago_quick', 'condpago_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECCION 8: DATOS BANCARIOS -->
                    <div class="form-section">
                        <h3 class="form-section-title">8. DATOS BANCARIOS</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Banco</label>
                                <select name="banco" id="banco_select" onchange="toggleQuickAdd(this, 'banco_quick')">
                                    <option value="">Seleccione...</option>
                                    <option value="BANCO DE CHILE" <?php echo ($proveedor_edit['banco'] ?? '') === 'BANCO DE CHILE' ? 'selected' : ''; ?>>Banco de Chile</option>
                                    <option value="BANCO ESTADO" <?php echo ($proveedor_edit['banco'] ?? '') === 'BANCO ESTADO' ? 'selected' : ''; ?>>Banco Estado</option>
                                    <option value="SANTANDER" <?php echo ($proveedor_edit['banco'] ?? '') === 'SANTANDER' ? 'selected' : ''; ?>>Santander</option>
                                    <option value="BCI" <?php echo ($proveedor_edit['banco'] ?? '') === 'BCI' ? 'selected' : ''; ?>>BCI</option>
                                    <option value="INTERNACIONAL" <?php echo ($proveedor_edit['banco'] ?? '') === 'INTERNACIONAL' ? 'selected' : ''; ?>>Banco Internacional</option>
                                    <option value="__NUEVO__">AGREGAR NUEVO BANCO...</option>
                                </select>
                                <div id="banco_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_banco_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_banco_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_bancos', 'banco_select', 'banco_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('banco_quick', 'banco_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Tipo de Cuenta</label>
                                <select name="tipo_cuenta" id="tipocta_select" onchange="toggleQuickAdd(this, 'tipocta_quick')">
                                    <option value="">Seleccione...</option>
                                    <option value="CORRIENTE" <?php echo ($proveedor_edit['tipo_cuenta'] ?? '') === 'CORRIENTE' ? 'selected' : ''; ?>>Cuenta Corriente</option>
                                    <option value="VISTA" <?php echo ($proveedor_edit['tipo_cuenta'] ?? '') === 'VISTA' ? 'selected' : ''; ?>>Cuenta Vista</option>
                                    <option value="AHORRO" <?php echo ($proveedor_edit['tipo_cuenta'] ?? '') === 'AHORRO' ? 'selected' : ''; ?>>Cuenta Ahorro</option>
                                    <option value="INTERNACIONAL" <?php echo ($proveedor_edit['tipo_cuenta'] ?? '') === 'INTERNACIONAL' ? 'selected' : ''; ?>>Internacional</option>
                                    <option value="__NUEVO__">AGREGAR NUEVO TIPO...</option>
                                </select>
                                <div id="tipocta_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_tipocta_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_tipocta_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_tipos_cuenta', 'tipocta_select', 'tipocta_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('tipocta_quick', 'tipocta_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Numero de Cuenta</label>
                                <input type="text" name="numero_cuenta" value="<?php echo $proveedor_edit['numero_cuenta'] ?? ''; ?>" placeholder="Numero de cuenta bancaria">
                            </div>

                            <div class="form-group">
                                <label>SWIFT / ABA / IBAN (segun pais)</label>
                                <input type="text" name="swift_aba_iban" value="<?php echo $proveedor_edit['swift_aba_iban'] ?? ''; ?>" placeholder="SWIFT, ABA o IBAN">
                            </div>

                            <div class="form-group">
                                <label>Titular de la Cuenta</label>
                                <input type="text" name="titular_cuenta" value="<?php echo $proveedor_edit['titular_cuenta'] ?? ''; ?>" placeholder="Nombre del titular">
                            </div>

                            <div class="form-group">
                                <label>Email Bancario</label>
                                <input type="email" name="email_bancario" value="<?php echo $proveedor_edit['email_bancario'] ?? ''; ?>" placeholder="email@banco.com">
                            </div>
                        </div>
                    </div>

                    <!-- SECCION 9: RESPONSABILIDAD (MULTIUSUARIO) -->
                    <div class="form-section">
                        <h3 class="form-section-title">9. DATOS DE RESPONSABILIDAD (MULTIUSUARIO)</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Ejecutivo Responsable Interno</label>
                                <select name="ejecutivo_responsable" id="ejecutivo_select" onchange="toggleQuickAdd(this, 'ejecutivo_quick')">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($ejecutivos as $ejec): ?>
                                        <option value="<?php echo $ejec['id']; ?>" <?php echo ($proveedor_edit['ejecutivo_responsable'] ?? '') == $ejec['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ejec['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="__NUEVO__">CREAR NUEVO USUARIO...</option>
                                </select>
                                <div id="ejecutivo_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_ejecutivo_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_ejecutivo_nombre" placeholder="Nombre Completo">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('rrhh_empleados', 'ejecutivo_select', 'ejecutivo_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('ejecutivo_quick', 'ejecutivo_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Estado del Proveedor</label>
                                <select name="estado_proveedor" id="estado_select" onchange="toggleQuickAdd(this, 'estado_quick')">
                                    <option value="">Seleccione...</option>
                                    <option value="ACTIVO" <?php echo ($proveedor_edit['estado_proveedor'] ?? 'ACTIVO') === 'ACTIVO' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="SUSPENDIDO" <?php echo ($proveedor_edit['estado_proveedor'] ?? '') === 'SUSPENDIDO' ? 'selected' : ''; ?>>Suspendido</option>
                                    <option value="RIESGO" <?php echo ($proveedor_edit['estado_proveedor'] ?? '') === 'RIESGO' ? 'selected' : ''; ?>>En Riesgo</option>
                                    <option value="OBSERVADO" <?php echo ($proveedor_edit['estado_proveedor'] ?? '') === 'OBSERVADO' ? 'selected' : ''; ?>>Observado</option>
                                    <option value="__NUEVO__">AGREGAR NUEVO ESTADO...</option>
                                </select>
                                <div id="estado_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_estado_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_estado_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_estados_proveedor', 'estado_select', 'estado_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('estado_quick', 'estado_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Calificacion General</label>
                                <select name="calificacion">
                                    <option value="">Sin calificar</option>
                                    <option value="5" <?php echo ($proveedor_edit['calificacion'] ?? '') === '5' ? 'selected' : ''; ?>>5 Estrellas - Excelente</option>
                                    <option value="4" <?php echo ($proveedor_edit['calificacion'] ?? '') === '4' ? 'selected' : ''; ?>>4 Estrellas - Muy Bueno</option>
                                    <option value="3" <?php echo ($proveedor_edit['calificacion'] ?? '') === '3' ? 'selected' : ''; ?>>3 Estrellas - Bueno</option>
                                    <option value="2" <?php echo ($proveedor_edit['calificacion'] ?? '') === '2' ? 'selected' : ''; ?>>2 Estrellas - Regular</option>
                                    <option value="1" <?php echo ($proveedor_edit['calificacion'] ?? '') === '1' ? 'selected' : ''; ?>>1 Estrella - Malo</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Giro</label>
                                <input type="text" name="giro" value="<?php echo $proveedor_edit['giro'] ?? ''; ?>" placeholder="Giro comercial">
                            </div>

                            <div class="form-group">
                                <label>Actividad Economica</label>
                                <input type="text" name="actividad_economica" value="<?php echo $proveedor_edit['actividad_economica'] ?? ''; ?>" placeholder="Codigo actividad SII">
                            </div>
                        </div>
                    </div>

                    <!-- SECCION 10: DOCUMENTOS ADJUNTOS -->
                    <div class="form-section">
                        <h3 class="form-section-title">10. DOCUMENTOS ADJUNTOS</h3>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>Certificados Tributarios</label>
                                <div class="file-upload-area">
                                    <input type="file" name="doc_tributario" accept=".pdf,.jpg,.png">
                                    <p>Certificado de situacion tributaria, RUT, etc.</p>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label>Certificados Bancarios</label>
                                <div class="file-upload-area">
                                    <input type="file" name="doc_bancario" accept=".pdf,.jpg,.png">
                                    <p>Certificado bancario, carta de cuenta, etc.</p>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label>Contratos</label>
                                <div class="file-upload-area">
                                    <input type="file" name="doc_contrato" accept=".pdf">
                                    <p>Contrato de servicios, acuerdos comerciales, etc.</p>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label>Certificados de Cumplimiento</label>
                                <div class="file-upload-area">
                                    <input type="file" name="doc_cumplimiento" accept=".pdf,.jpg,.png">
                                    <p>ISO, certificaciones de calidad, etc.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECCION 11: NOTAS INTERNAS -->
                    <div class="form-section">
                        <h3 class="form-section-title">11. NOTAS INTERNAS</h3>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>Notas Internas</label>
                                <textarea name="notas_internas" placeholder="Notas generales sobre el proveedor"><?php echo $proveedor_edit['notas_internas'] ?? ''; ?></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label>Notas de Compras</label>
                                <textarea name="notas_compras" placeholder="Informacion relevante para el area de compras"><?php echo $proveedor_edit['notas_compras'] ?? ''; ?></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label>Notas de Contabilidad y Auditoria</label>
                                <textarea name="notas_contabilidad" placeholder="Informacion para contabilidad y auditoria"><?php echo $proveedor_edit['notas_contabilidad'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Checkbox activo -->
                    <div class="form-section">
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="activo" <?php echo ($proveedor_edit['activo'] ?? 1) ? 'checked' : ''; ?> style="width: 24px; height: 24px;">
                                <span style="font-size: 16px; font-weight: 700;">Proveedor Activo</span>
                            </label>
                        </div>
                    </div>

                    <!-- SECCION 12: BOTONES FINALES -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" name="guardar">GUARDAR PROVEEDOR</button>
                        <button type="submit" class="btn btn-success" name="guardar_y_crear_otro">GUARDAR Y CREAR OTRO</button>
                        <button type="submit" class="btn btn-success" name="guardar_y_contrato">GUARDAR Y AGREGAR CONTRATO</button>
                        <button type="button" class="btn btn-secondary" onclick="limpiarFormulario()">LIMPIAR</button>
                    </div>
                </form>
            </div>

            <div id="tab-list" class="tab-content">
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Buscar proveedor..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" onclick="buscar()">Buscar</button>
                    <button class="btn btn-secondary" onclick="window.location.href='?'">Limpiar</button>
                    <button class="btn btn-success" onclick="window.print()">Imprimir</button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Doc.</th>
                                <th>Razon Social</th>
                                <th>Alias</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Calif.</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($proveedores)): ?>
                                <tr><td colspan="7" style="text-align:center;padding:40px;">No hay proveedores</td></tr>
                            <?php else: ?>
                                <?php foreach ($proveedores as $p): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($p['numero_documento']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($p['razon_social']); ?></td>
                                        <td><?php echo htmlspecialchars($p['alias_corto']); ?></td>
                                        <td><?php echo htmlspecialchars($p['tipo_proveedor']); ?></td>
                                        <td>
                                            <?php if ($p['activo']): ?>
                                                <span class="badge badge-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $p['calificacion'] ? str_repeat('⭐', intval($p['calificacion'])) : '-'; ?></td>
                                        <td>
                                            <a href="?edit=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm" onclick="switchTab('form')">Editar</a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function validarDocumento() {
            const input = document.getElementById('docInput');
            const errorDiv = document.getElementById('docError');
            const validDiv = document.getElementById('docValid');
            const valor = input.value.replace(/[^0-9kK]/g, '');

            if (valor.length < 2) {
                errorDiv.style.display = 'none';
                validDiv.style.display = 'none';
                return;
            }

            const cuerpo = valor.slice(0, -1);
            const dv = valor.slice(-1).toUpperCase();

            if (!/^\d+$/.test(cuerpo)) {
                errorDiv.style.display = 'block';
                validDiv.style.display = 'none';
                return;
            }

            let suma = 0;
            let multiplo = 2;

            for (let i = cuerpo.length - 1; i >= 0; i--) {
                suma += parseInt(cuerpo.charAt(i)) * multiplo;
                multiplo = multiplo < 7 ? multiplo + 1 : 2;
            }

            const dvEsperado = 11 - (suma % 11);
            const dvCalculado = dvEsperado === 11 ? '0' : dvEsperado === 10 ? 'K' : dvEsperado.toString();

            if (dv === dvCalculado) {
                errorDiv.style.display = 'none';
                validDiv.style.display = 'block';
                input.value = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, ".") + '-' + dv;
            } else {
                errorDiv.style.display = 'block';
                validDiv.style.display = 'none';
            }
        }

        function toggleQuickAdd(select, containerId) {
            const container = document.getElementById(containerId);
            if (select.value === '__NUEVO__') {
                container.classList.add('active');
            } else {
                container.classList.remove('active');
            }
        }

        function cancelQuickAdd(containerId, selectId) {
            document.getElementById(containerId).classList.remove('active');
            document.getElementById(selectId).value = '';
        }

        async function quickAdd(tabla, selectId, containerId) {
            const container = document.getElementById(containerId);
            const inputs = container.querySelectorAll('input');
            const codigo = inputs[0].value.trim().toUpperCase();
            const nombre = inputs[1].value.trim();

            if (!codigo || !nombre) {
                alert('Complete codigo y nombre');
                return;
            }

            const formData = new FormData();
            formData.append('quick_add', '1');
            formData.append('tabla', tabla);
            formData.append('codigo', codigo);
            formData.append('nombre', nombre);

            try {
                const response = await fetch('', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    const select = document.getElementById(selectId);
                    const newOption = new Option(codigo + ' - ' + nombre, nombre, true, true);
                    select.add(newOption, select.options.length - 1);
                    select.value = nombre;
                    container.classList.remove('active');
                    inputs[0].value = '';
                    inputs[1].value = '';
                    alert('Registro agregado');
                }
            } catch (error) {
                alert('Error');
            }
        }

        function addEmailField() {
            const container = document.getElementById('emailsContainer');
            const div = document.createElement('div');
            div.className = 'multi-input-item';
            div.innerHTML = `
                <input type="email" name="emails[]" placeholder="Email adicional">
                <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">Eliminar</button>
            `;
            container.appendChild(div);
        }

        function addTelefonoField() {
            const container = document.getElementById('telefonosContainer');
            const div = document.createElement('div');
            div.className = 'multi-input-item';
            div.innerHTML = `
                <input type="text" name="telefonos[]" placeholder="Telefono adicional">
                <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">Eliminar</button>
            `;
            container.appendChild(div);
        }

        function switchTab(tabName) {
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));

            if (tabName === 'form') {
                tabs[0].classList.add('active');
                document.getElementById('tab-form').classList.add('active');
            } else {
                tabs[1].classList.add('active');
                document.getElementById('tab-list').classList.add('active');
            }
        }

        function limpiarFormulario() {
            if (confirm('Limpiar formulario?')) {
                window.location.href = '?';
            }
        }

        function buscar() {
            const search = document.getElementById('searchInput').value;
            window.location.href = '?search=' + encodeURIComponent(search);
        }

        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') buscar();
        });
    </script>
</body>
</html>
