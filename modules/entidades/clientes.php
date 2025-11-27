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
    'CL' => [
        'nombre_documento' => 'RUT',
        'formato' => 'XX.XXX.XXX-X',
        'validacion' => 'modulo_11',
        'tiene_dv' => true,
        'longitud_min' => 8,
        'longitud_max' => 9
    ],
    'AR' => [
        'nombre_documento' => 'CUIT',
        'formato' => 'XX-XXXXXXXX-X',
        'validacion' => 'modulo_11',
        'tiene_dv' => true,
        'longitud_min' => 11,
        'longitud_max' => 11
    ],
    'PE' => [
        'nombre_documento' => 'RUC',
        'formato' => 'XXXXXXXXXXX',
        'validacion' => 'suma_ponderada',
        'tiene_dv' => false,
        'longitud_min' => 11,
        'longitud_max' => 11
    ],
    'MX' => [
        'nombre_documento' => 'RFC',
        'formato' => 'XXXX-XXXXXX-XXX',
        'validacion' => 'regex',
        'tiene_dv' => false,
        'longitud_min' => 12,
        'longitud_max' => 13
    ],
    'US' => [
        'nombre_documento' => 'EIN',
        'formato' => 'XX-XXXXXXX',
        'validacion' => 'ninguna',
        'tiene_dv' => false,
        'longitud_min' => 9,
        'longitud_max' => 9
    ],
    'BR' => [
        'nombre_documento' => 'CNPJ',
        'formato' => 'XX.XXX.XXX/XXXX-XX',
        'validacion' => 'doble_dv',
        'tiene_dv' => true,
        'longitud_min' => 14,
        'longitud_max' => 14
    ]
];

// Obtener configuracion del pais actual
$stmt_pais = $pdo->prepare("SELECT codigo_pais, nombre, moneda_default FROM cat_paises WHERE id = ?");
$stmt_pais->execute([$pais_id]);
$pais_config = $stmt_pais->fetch(PDO::FETCH_ASSOC);
$codigo_pais = $pais_config['codigo_pais'] ?? 'CL';
$reglas_documento = $reglas_pais[$codigo_pais] ?? $reglas_pais['CL'];

$mensaje = '';
$cliente_edit = null;

// Procesar formulario INSERT RAPIDO de catalogos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_add'])) {
    // LISTA BLANCA DE TABLAS PERMITIDAS - SEGURIDAD
    $tablas_permitidas = [
        'cat_tipos_cliente',
        'cat_categorias_cliente',
        'cat_grupos_cliente',
        'cat_condiciones_pago',
        'cat_listas_precio'
    ];

    $tabla = $_POST['tabla'] ?? '';
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $nombre = trim($_POST['nombre'] ?? '');

    // Validar que la tabla este en la lista blanca
    if (!in_array($tabla, $tablas_permitidas)) {
        echo json_encode(['success' => false, 'error' => 'Tabla no permitida']);
        exit;
    }

    // Validar campos requeridos
    if (empty($codigo) || empty($nombre)) {
        echo json_encode(['success' => false, 'error' => 'Codigo y nombre son requeridos']);
        exit;
    }

    try {
        // Verificar si ya existe el codigo
        $sql_check = "SELECT id FROM $tabla WHERE codigo = ? AND company_id = ? AND pais_id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$codigo, $company_id, $pais_id]);

        if ($stmt_check->fetch()) {
            echo json_encode(['success' => false, 'error' => 'El codigo ya existe']);
            exit;
        }

        // Insertar nuevo registro
        $sql = "INSERT INTO $tabla (codigo, nombre, company_id, pais_id, created_at)
                VALUES (:codigo, :nombre, :company_id, :pais_id, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':codigo' => $codigo,
            ':nombre' => $nombre,
            ':company_id' => $company_id,
            ':pais_id' => $pais_id
        ]);

        $new_id = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'id' => $new_id,
            'codigo' => $codigo,
            'nombre' => $nombre
        ]);
        exit;

    } catch (PDOException $e) {
        // Manejar error de duplicado u otros errores de BD
        $error_msg = 'Error al guardar el registro';
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $error_msg = 'El registro ya existe (codigo duplicado)';
        }
        echo json_encode(['success' => false, 'error' => $error_msg, 'details' => $e->getMessage()]);
        exit;
    }
}

// Procesar formulario principal CLIENTES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $action = $_POST['action'];

        if ($action === 'create' || $action === 'update') {
            // Datos Generales
            $codigo = strtoupper(trim($_POST['codigo']));
            $rut = trim($_POST['rut']);
            $razon_social = trim($_POST['razon_social']);
            $nombre_comercial = trim($_POST['nombre_comercial']);
            $nombre_fantasia = trim($_POST['nombre_fantasia']);
            $giro = trim($_POST['giro']);
            $tipo_cliente = $_POST['tipo_cliente'];
            $categoria_cliente = $_POST['categoria_cliente'];
            $grupo_cliente = $_POST['grupo_cliente'];

            // Direccion
            $direccion = trim($_POST['direccion']);
            $numero = trim($_POST['numero']);
            $departamento = trim($_POST['departamento']);
            $region = $_POST['region'];
            $comuna = trim($_POST['comuna']);
            $ciudad = trim($_POST['ciudad']);
            $codigo_postal = trim($_POST['codigo_postal']);
            $pais = $_POST['pais'];

            // Contacto
            $telefono1 = trim($_POST['telefono1']);
            $telefono2 = trim($_POST['telefono2']);
            $email1 = trim($_POST['email1']);
            $email2 = trim($_POST['email2']);
            $sitio_web = trim($_POST['sitio_web']);
            $contacto_principal = trim($_POST['contacto_principal']);
            $cargo_contacto = trim($_POST['cargo_contacto']);
            $telefono_contacto = trim($_POST['telefono_contacto']);
            $email_contacto = trim($_POST['email_contacto']);

            // Datos Comerciales
            $vendedor_asignado = $_POST['vendedor_asignado'] ?: NULL;
            $condicion_pago_id = $_POST['condicion_pago_id'] ?: NULL;
            $dias_credito = intval($_POST['dias_credito']);
            $limite_credito = floatval($_POST['limite_credito']);
            $descuento_general = floatval($_POST['descuento_general']);
            $lista_precio_id = $_POST['lista_precio_id'] ?: NULL;
            $bloqueo_credito = isset($_POST['bloqueo_credito']) ? 1 : 0;
            $motivo_bloqueo = trim($_POST['motivo_bloqueo']);

            // Datos Fiscales SII
            $actividad_economica = trim($_POST['actividad_economica']);
            $codigo_actividad = trim($_POST['codigo_actividad']);
            $sucursal_sii = trim($_POST['sucursal_sii']);
            $resolucion_sii = trim($_POST['resolucion_sii']);
            $fecha_resolucion = $_POST['fecha_resolucion'] ?: NULL;
            $email_dte = trim($_POST['email_dte']);
            $contribuyente = $_POST['contribuyente'];

            // Otros
            $observaciones = trim($_POST['observaciones']);
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($action === 'create') {
                $sql = "INSERT INTO cat_clientes (
                    company_id, pais_id, idioma_id, centro_gestion_id, user_id,
                    codigo, rut, razon_social, nombre_comercial, nombre_fantasia, giro, tipo_cliente, categoria_cliente, grupo_cliente,
                    direccion, numero, departamento, region, comuna, ciudad, codigo_postal, pais,
                    telefono1, telefono2, email1, email2, sitio_web,
                    contacto_principal, cargo_contacto, telefono_contacto, email_contacto,
                    vendedor_asignado, condicion_pago_id, dias_credito, limite_credito, descuento_general, lista_precio_id, bloqueo_credito, motivo_bloqueo,
                    actividad_economica, codigo_actividad, sucursal_sii, resolucion_sii, fecha_resolucion, email_dte, contribuyente,
                    observaciones, activo
                ) VALUES (
                    :company_id, :pais_id, :idioma_id, :centro_gestion_id, :user_id,
                    :codigo, :rut, :razon_social, :nombre_comercial, :nombre_fantasia, :giro, :tipo_cliente, :categoria_cliente, :grupo_cliente,
                    :direccion, :numero, :departamento, :region, :comuna, :ciudad, :codigo_postal, :pais,
                    :telefono1, :telefono2, :email1, :email2, :sitio_web,
                    :contacto_principal, :cargo_contacto, :telefono_contacto, :email_contacto,
                    :vendedor_asignado, :condicion_pago_id, :dias_credito, :limite_credito, :descuento_general, :lista_precio_id, :bloqueo_credito, :motivo_bloqueo,
                    :actividad_economica, :codigo_actividad, :sucursal_sii, :resolucion_sii, :fecha_resolucion, :email_dte, :contribuyente,
                    :observaciones, :activo
                )";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':company_id' => $company_id,
                    ':pais_id' => $pais_id,
                    ':idioma_id' => $idioma_id,
                    ':centro_gestion_id' => $centro_gestion_id,
                    ':user_id' => $user_id,
                    ':codigo' => $codigo,
                    ':rut' => $rut,
                    ':razon_social' => $razon_social,
                    ':nombre_comercial' => $nombre_comercial,
                    ':nombre_fantasia' => $nombre_fantasia,
                    ':giro' => $giro,
                    ':tipo_cliente' => $tipo_cliente,
                    ':categoria_cliente' => $categoria_cliente,
                    ':grupo_cliente' => $grupo_cliente,
                    ':direccion' => $direccion,
                    ':numero' => $numero,
                    ':departamento' => $departamento,
                    ':region' => $region,
                    ':comuna' => $comuna,
                    ':ciudad' => $ciudad,
                    ':codigo_postal' => $codigo_postal,
                    ':pais' => $pais,
                    ':telefono1' => $telefono1,
                    ':telefono2' => $telefono2,
                    ':email1' => $email1,
                    ':email2' => $email2,
                    ':sitio_web' => $sitio_web,
                    ':contacto_principal' => $contacto_principal,
                    ':cargo_contacto' => $cargo_contacto,
                    ':telefono_contacto' => $telefono_contacto,
                    ':email_contacto' => $email_contacto,
                    ':vendedor_asignado' => $vendedor_asignado,
                    ':condicion_pago_id' => $condicion_pago_id,
                    ':dias_credito' => $dias_credito,
                    ':limite_credito' => $limite_credito,
                    ':descuento_general' => $descuento_general,
                    ':lista_precio_id' => $lista_precio_id,
                    ':bloqueo_credito' => $bloqueo_credito,
                    ':motivo_bloqueo' => $motivo_bloqueo,
                    ':actividad_economica' => $actividad_economica,
                    ':codigo_actividad' => $codigo_actividad,
                    ':sucursal_sii' => $sucursal_sii,
                    ':resolucion_sii' => $resolucion_sii,
                    ':fecha_resolucion' => $fecha_resolucion,
                    ':email_dte' => $email_dte,
                    ':contribuyente' => $contribuyente,
                    ':observaciones' => $observaciones,
                    ':activo' => $activo
                ]);

                $mensaje = "Cliente creado exitosamente";
            } else {
                $id = intval($_POST['id']);
                $sql = "UPDATE cat_clientes SET
                    codigo = :codigo, rut = :rut, razon_social = :razon_social, nombre_comercial = :nombre_comercial,
                    nombre_fantasia = :nombre_fantasia, giro = :giro, tipo_cliente = :tipo_cliente, categoria_cliente = :categoria_cliente, grupo_cliente = :grupo_cliente,
                    direccion = :direccion, numero = :numero, departamento = :departamento, region = :region, comuna = :comuna, ciudad = :ciudad, codigo_postal = :codigo_postal, pais = :pais,
                    telefono1 = :telefono1, telefono2 = :telefono2, email1 = :email1, email2 = :email2, sitio_web = :sitio_web,
                    contacto_principal = :contacto_principal, cargo_contacto = :cargo_contacto, telefono_contacto = :telefono_contacto, email_contacto = :email_contacto,
                    vendedor_asignado = :vendedor_asignado, condicion_pago_id = :condicion_pago_id, dias_credito = :dias_credito,
                    limite_credito = :limite_credito, descuento_general = :descuento_general, lista_precio_id = :lista_precio_id,
                    bloqueo_credito = :bloqueo_credito, motivo_bloqueo = :motivo_bloqueo,
                    actividad_economica = :actividad_economica, codigo_actividad = :codigo_actividad,
                    sucursal_sii = :sucursal_sii, resolucion_sii = :resolucion_sii, fecha_resolucion = :fecha_resolucion,
                    email_dte = :email_dte, contribuyente = :contribuyente,
                    observaciones = :observaciones, activo = :activo, updated_at = NOW()
                WHERE id = :id AND company_id = :company_id";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $id,
                    ':company_id' => $company_id,
                    ':codigo' => $codigo,
                    ':rut' => $rut,
                    ':razon_social' => $razon_social,
                    ':nombre_comercial' => $nombre_comercial,
                    ':nombre_fantasia' => $nombre_fantasia,
                    ':giro' => $giro,
                    ':tipo_cliente' => $tipo_cliente,
                    ':categoria_cliente' => $categoria_cliente,
                    ':grupo_cliente' => $grupo_cliente,
                    ':direccion' => $direccion,
                    ':numero' => $numero,
                    ':departamento' => $departamento,
                    ':region' => $region,
                    ':comuna' => $comuna,
                    ':ciudad' => $ciudad,
                    ':codigo_postal' => $codigo_postal,
                    ':pais' => $pais,
                    ':telefono1' => $telefono1,
                    ':telefono2' => $telefono2,
                    ':email1' => $email1,
                    ':email2' => $email2,
                    ':sitio_web' => $sitio_web,
                    ':contacto_principal' => $contacto_principal,
                    ':cargo_contacto' => $cargo_contacto,
                    ':telefono_contacto' => $telefono_contacto,
                    ':email_contacto' => $email_contacto,
                    ':vendedor_asignado' => $vendedor_asignado,
                    ':condicion_pago_id' => $condicion_pago_id,
                    ':dias_credito' => $dias_credito,
                    ':limite_credito' => $limite_credito,
                    ':descuento_general' => $descuento_general,
                    ':lista_precio_id' => $lista_precio_id,
                    ':bloqueo_credito' => $bloqueo_credito,
                    ':motivo_bloqueo' => $motivo_bloqueo,
                    ':actividad_economica' => $actividad_economica,
                    ':codigo_actividad' => $codigo_actividad,
                    ':sucursal_sii' => $sucursal_sii,
                    ':resolucion_sii' => $resolucion_sii,
                    ':fecha_resolucion' => $fecha_resolucion,
                    ':email_dte' => $email_dte,
                    ':contribuyente' => $contribuyente,
                    ':observaciones' => $observaciones,
                    ':activo' => $activo
                ]);

                $mensaje = "Cliente actualizado exitosamente";
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("DELETE FROM cat_clientes WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $company_id]);
            $mensaje = "Cliente eliminado exitosamente";
        }
    } catch (PDOException $e) {
        $mensaje = "Error: " . $e->getMessage();
    }
}

// Cargar cliente para edicion
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM cat_clientes WHERE id = ? AND company_id = ?");
    $stmt->execute([$id, $company_id]);
    $cliente_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Listar clientes
$search = $_GET['search'] ?? '';
$filtro_activo = $_GET['filtro_activo'] ?? '';

$sql = "SELECT * FROM cat_clientes WHERE company_id = ?";
$params = [$company_id];

if ($search) {
    $sql .= " AND (codigo LIKE ? OR rut LIKE ? OR razon_social LIKE ? OR nombre_comercial LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($filtro_activo !== '') {
    $sql .= " AND activo = ?";
    $params[] = intval($filtro_activo);
}

$sql .= " ORDER BY codigo ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar catalogos necesarios
$tipos_cliente = $pdo->query("SELECT * FROM cat_tipos_cliente WHERE company_id = $company_id AND pais_id = $pais_id AND activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$categorias_cliente = $pdo->query("SELECT * FROM cat_categorias_cliente WHERE company_id = $company_id AND pais_id = $pais_id AND activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$grupos_cliente = $pdo->query("SELECT * FROM cat_grupos_cliente WHERE company_id = $company_id AND pais_id = $pais_id AND activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$condiciones_pago = $pdo->query("SELECT * FROM cat_condiciones_pago WHERE company_id = $company_id AND pais_id = $pais_id ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$listas_precio = $pdo->query("SELECT * FROM cat_listas_precio WHERE company_id = $company_id AND pais_id = $pais_id ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$vendedores = $pdo->query("SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo FROM rrhh_empleados WHERE company_id = $company_id AND cargo = 'Vendedor' AND activo = 1 ORDER BY nombres")->fetchAll(PDO::FETCH_ASSOC);
$paises = $pdo->query("SELECT * FROM cat_paises ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Regiones de Chile
$regiones_chile = [
    'XV' => 'Arica y Parinacota',
    'I' => 'Tarapaca',
    'II' => 'Antofagasta',
    'III' => 'Atacama',
    'IV' => 'Coquimbo',
    'V' => 'Valparaiso',
    'RM' => 'Region Metropolitana',
    'VI' => 'Libertador General Bernardo OHiggins',
    'VII' => 'Maule',
    'XVI' => 'Nuble',
    'VIII' => 'Biobio',
    'IX' => 'La Araucania',
    'XIV' => 'Los Rios',
    'X' => 'Los Lagos',
    'XI' => 'Aysen del General Carlos Ibanez del Campo',
    'XII' => 'Magallanes y de la Antartica Chilena'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maestro de Clientes - CONECTA ERP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
        }

        .header-info {
            text-align: right;
            font-size: 13px;
        }

        .content {
            padding: 40px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }

        .form-section {
            background: #f9fafb;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border: 2px solid #e5e7eb;
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
            font-size: 14px;
        }

        label.required::after {
            content: " *";
            color: #ef4444;
        }

        input[type="text"],
        input[type="email"],
        input[type="number"],
        input[type="date"],
        textarea,
        select {
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }

        .search-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .search-bar input {
            flex: 1;
            min-width: 250px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .rut-feedback {
            display: none;
            font-size: 12px;
            margin-top: 5px;
            font-weight: 600;
        }

        .rut-feedback.valid {
            color: #10b981;
            display: block;
        }

        .rut-feedback.invalid {
            color: #ef4444;
            display: block;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .quick-add {
            display: none;
            margin-top: 10px;
            padding: 15px;
            background: #f3f4f6;
            border-radius: 8px;
            border: 2px dashed #667eea;
        }

        .quick-add.active {
            display: block;
        }

        .quick-add-grid {
            display: grid;
            grid-template-columns: 1fr 2fr auto auto;
            gap: 10px;
            align-items: end;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #e5e7eb;
        }

        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border: none;
            background: none;
            font-weight: 600;
            color: #6b7280;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .btn,
            .search-bar,
            .form-actions,
            .header-info {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MAESTRO DE CLIENTES</h1>
            <div class="header-info">
                <div>MULTIEMPRESA | MULTIPAIS | MULTIIDIOMA | MULTIGESTION</div>
                <div>Empresa: <?php echo $company_id; ?> | Pais: <?php echo $codigo_pais; ?> | Usuario: <?php echo $user_id; ?></div>
                <div>Documento: <?php echo $reglas_documento['nombre_documento']; ?> | Formato: <?php echo $reglas_documento['formato']; ?></div>
            </div>
        </div>

        <div class="content">
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <div class="tabs">
                <button class="tab active" onclick="switchTab('form')">FORMULARIO</button>
                <button class="tab" onclick="switchTab('list')">LISTADO</button>
            </div>

            <div id="tab-form" class="tab-content active">
                <form method="POST" id="formCliente">
                    <input type="hidden" name="action" value="<?php echo $cliente_edit ? 'update' : 'create'; ?>">
                    <?php if ($cliente_edit): ?>
                        <input type="hidden" name="id" value="<?php echo $cliente_edit['id']; ?>">
                    <?php endif; ?>

                    <div class="form-section">
                        <h3 class="form-section-title">DATOS GENERALES</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="required">Codigo Cliente</label>
                                <input type="text" name="codigo" value="<?php echo $cliente_edit['codigo'] ?? ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="required"><?php echo $reglas_documento['nombre_documento']; ?></label>
                                <input type="text" name="rut" id="rutInput" value="<?php echo $cliente_edit['rut'] ?? ''; ?>" required onblur="validarDocumento()">
                                <div id="rutError" class="rut-feedback invalid">Documento invalido</div>
                                <div id="rutValid" class="rut-feedback valid">Documento valido</div>
                            </div>

                            <div class="form-group">
                                <label class="required">Razon Social</label>
                                <input type="text" name="razon_social" value="<?php echo $cliente_edit['razon_social'] ?? ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Nombre Comercial</label>
                                <input type="text" name="nombre_comercial" value="<?php echo $cliente_edit['nombre_comercial'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Nombre Fantasia</label>
                                <input type="text" name="nombre_fantasia" value="<?php echo $cliente_edit['nombre_fantasia'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Giro</label>
                                <input type="text" name="giro" value="<?php echo $cliente_edit['giro'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label class="required">Tipo Cliente</label>
                                <select name="tipo_cliente" id="tipo_cliente_select" required onchange="toggleQuickAdd(this, 'tipo_cliente_quick')">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($tipos_cliente as $tc): ?>
                                        <option value="<?php echo htmlspecialchars($tc['codigo']); ?>" <?php echo ($cliente_edit['tipo_cliente'] ?? '') === $tc['codigo'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tc['codigo'] . ' - ' . $tc['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="__NUEVO__">AGREGAR NUEVO...</option>
                                </select>
                                <div id="tipo_cliente_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_tipo_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_tipo_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_tipos_cliente', 'tipo_cliente_select', 'tipo_cliente_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('tipo_cliente_quick', 'tipo_cliente_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Categoria Cliente</label>
                                <select name="categoria_cliente" id="categoria_select" onchange="toggleQuickAdd(this, 'categoria_quick')">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($categorias_cliente as $cc): ?>
                                        <option value="<?php echo htmlspecialchars($cc['codigo']); ?>" <?php echo ($cliente_edit['categoria_cliente'] ?? '') === $cc['codigo'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cc['codigo'] . ' - ' . $cc['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="__NUEVO__">AGREGAR NUEVO...</option>
                                </select>
                                <div id="categoria_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_cat_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_cat_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_categorias_cliente', 'categoria_select', 'categoria_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('categoria_quick', 'categoria_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Grupo Cliente</label>
                                <select name="grupo_cliente" id="grupo_select" onchange="toggleQuickAdd(this, 'grupo_quick')">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($grupos_cliente as $gc): ?>
                                        <option value="<?php echo htmlspecialchars($gc['codigo']); ?>" <?php echo ($cliente_edit['grupo_cliente'] ?? '') === $gc['codigo'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($gc['codigo'] . ' - ' . $gc['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="__NUEVO__">AGREGAR NUEVO...</option>
                                </select>
                                <div id="grupo_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_grupo_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_grupo_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_grupos_cliente', 'grupo_select', 'grupo_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('grupo_quick', 'grupo_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">DIRECCION</h3>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>Direccion</label>
                                <input type="text" name="direccion" value="<?php echo $cliente_edit['direccion'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Numero</label>
                                <input type="text" name="numero" value="<?php echo $cliente_edit['numero'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Depto/Oficina</label>
                                <input type="text" name="departamento" value="<?php echo $cliente_edit['departamento'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Region</label>
                                <select name="region">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($regiones_chile as $codigo => $nombre): ?>
                                        <option value="<?php echo $codigo; ?>" <?php echo ($cliente_edit['region'] ?? '') === $codigo ? 'selected' : ''; ?>>
                                            <?php echo $nombre; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Comuna</label>
                                <input type="text" name="comuna" value="<?php echo $cliente_edit['comuna'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Ciudad</label>
                                <input type="text" name="ciudad" value="<?php echo $cliente_edit['ciudad'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Codigo Postal</label>
                                <input type="text" name="codigo_postal" value="<?php echo $cliente_edit['codigo_postal'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label class="required">Pais</label>
                                <select name="pais" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($paises as $p): ?>
                                        <option value="<?php echo $p['codigo_pais']; ?>" <?php echo ($cliente_edit['pais'] ?? $codigo_pais) === $p['codigo_pais'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">CONTACTO</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Telefono 1</label>
                                <input type="text" name="telefono1" value="<?php echo $cliente_edit['telefono1'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Telefono 2</label>
                                <input type="text" name="telefono2" value="<?php echo $cliente_edit['telefono2'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Email 1</label>
                                <input type="email" name="email1" value="<?php echo $cliente_edit['email1'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Email 2</label>
                                <input type="email" name="email2" value="<?php echo $cliente_edit['email2'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Sitio Web</label>
                                <input type="text" name="sitio_web" value="<?php echo $cliente_edit['sitio_web'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Contacto Principal</label>
                                <input type="text" name="contacto_principal" value="<?php echo $cliente_edit['contacto_principal'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Cargo Contacto</label>
                                <input type="text" name="cargo_contacto" value="<?php echo $cliente_edit['cargo_contacto'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Telefono Contacto</label>
                                <input type="text" name="telefono_contacto" value="<?php echo $cliente_edit['telefono_contacto'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Email Contacto</label>
                                <input type="email" name="email_contacto" value="<?php echo $cliente_edit['email_contacto'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">DATOS COMERCIALES</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Vendedor Asignado</label>
                                <select name="vendedor_asignado">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($vendedores as $v): ?>
                                        <option value="<?php echo $v['id']; ?>" <?php echo ($cliente_edit['vendedor_asignado'] ?? '') == $v['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($v['nombre_completo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Condicion de Pago</label>
                                <select name="condicion_pago_id" id="condicion_pago_select" onchange="toggleQuickAdd(this, 'condicion_pago_quick')">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($condiciones_pago as $cp): ?>
                                        <option value="<?php echo $cp['id']; ?>" <?php echo ($cliente_edit['condicion_pago_id'] ?? '') == $cp['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cp['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="__NUEVO__">AGREGAR NUEVO...</option>
                                </select>
                                <div id="condicion_pago_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_condicion_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_condicion_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_condiciones_pago', 'condicion_pago_select', 'condicion_pago_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('condicion_pago_quick', 'condicion_pago_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Dias Credito</label>
                                <input type="number" name="dias_credito" value="<?php echo $cliente_edit['dias_credito'] ?? '0'; ?>" min="0">
                            </div>

                            <div class="form-group">
                                <label>Limite Credito</label>
                                <input type="number" step="0.01" name="limite_credito" value="<?php echo $cliente_edit['limite_credito'] ?? '0'; ?>" min="0">
                            </div>

                            <div class="form-group">
                                <label>Descuento General (%)</label>
                                <input type="number" step="0.01" name="descuento_general" value="<?php echo $cliente_edit['descuento_general'] ?? '0'; ?>" min="0" max="100">
                            </div>

                            <div class="form-group">
                                <label>Lista de Precios</label>
                                <select name="lista_precio_id" id="lista_precio_select" onchange="toggleQuickAdd(this, 'lista_precio_quick')">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($listas_precio as $lp): ?>
                                        <option value="<?php echo $lp['id']; ?>" <?php echo ($cliente_edit['lista_precio_id'] ?? '') == $lp['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($lp['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="__NUEVO__">AGREGAR NUEVO...</option>
                                </select>
                                <div id="lista_precio_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_lista_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_lista_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_listas_precio', 'lista_precio_select', 'lista_precio_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('lista_precio_quick', 'lista_precio_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Bloqueo Credito</label>
                                <div class="checkbox-group">
                                    <input type="checkbox" name="bloqueo_credito" <?php echo ($cliente_edit['bloqueo_credito'] ?? 0) ? 'checked' : ''; ?>>
                                    <span>Cliente bloqueado para credito</span>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label>Motivo Bloqueo</label>
                                <textarea name="motivo_bloqueo"><?php echo $cliente_edit['motivo_bloqueo'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">DATOS FISCALES SII (CHILE)</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Actividad Economica</label>
                                <input type="text" name="actividad_economica" value="<?php echo $cliente_edit['actividad_economica'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Codigo Actividad</label>
                                <input type="text" name="codigo_actividad" value="<?php echo $cliente_edit['codigo_actividad'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Sucursal SII</label>
                                <input type="text" name="sucursal_sii" value="<?php echo $cliente_edit['sucursal_sii'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Resolucion SII</label>
                                <input type="text" name="resolucion_sii" value="<?php echo $cliente_edit['resolucion_sii'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Fecha Resolucion</label>
                                <input type="date" name="fecha_resolucion" value="<?php echo $cliente_edit['fecha_resolucion'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Email DTE</label>
                                <input type="email" name="email_dte" value="<?php echo $cliente_edit['email_dte'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Tipo Contribuyente</label>
                                <select name="contribuyente">
                                    <option value="">Seleccione...</option>
                                    <option value="IVA_AFECTO" <?php echo ($cliente_edit['contribuyente'] ?? '') === 'IVA_AFECTO' ? 'selected' : ''; ?>>IVA Afecto</option>
                                    <option value="IVA_EXENTO" <?php echo ($cliente_edit['contribuyente'] ?? '') === 'IVA_EXENTO' ? 'selected' : ''; ?>>IVA Exento</option>
                                    <option value="NO_CONTRIBUYENTE" <?php echo ($cliente_edit['contribuyente'] ?? '') === 'NO_CONTRIBUYENTE' ? 'selected' : ''; ?>>No Contribuyente</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">OBSERVACIONES</h3>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>Observaciones</label>
                                <textarea name="observaciones"><?php echo $cliente_edit['observaciones'] ?? ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Estado</label>
                                <div class="checkbox-group">
                                    <input type="checkbox" name="activo" <?php echo ($cliente_edit['activo'] ?? 1) ? 'checked' : ''; ?>>
                                    <span>Cliente Activo</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="limpiarFormulario()">Limpiar</button>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $cliente_edit ? 'Actualizar Cliente' : 'Crear Cliente'; ?>
                        </button>
                    </div>
                </form>
            </div>

            <div id="tab-list" class="tab-content">
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Buscar por codigo, RUT, razon social..." value="<?php echo htmlspecialchars($search); ?>">
                    <select id="filtroActivo" onchange="filtrar()">
                        <option value="">Todos</option>
                        <option value="1" <?php echo $filtro_activo === '1' ? 'selected' : ''; ?>>Activos</option>
                        <option value="0" <?php echo $filtro_activo === '0' ? 'selected' : ''; ?>>Inactivos</option>
                    </select>
                    <button class="btn btn-primary" onclick="buscar()">Buscar</button>
                    <button class="btn btn-secondary" onclick="limpiarBusqueda()">Limpiar</button>
                    <button class="btn btn-success" onclick="window.print()">Imprimir</button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th><?php echo $reglas_documento['nombre_documento']; ?></th>
                                <th>Razon Social</th>
                                <th>Nombre Comercial</th>
                                <th>Ciudad</th>
                                <th>Telefono</th>
                                <th>Email</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($clientes)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px;">No hay clientes registrados</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($clientes as $cliente): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($cliente['codigo']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($cliente['rut']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['razon_social']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['nombre_comercial']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['ciudad']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['telefono1']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['email1']); ?></td>
                                        <td>
                                            <?php if ($cliente['activo']): ?>
                                                <span class="badge badge-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactivo</span>
                                            <?php endif; ?>
                                            <?php if ($cliente['bloqueo_credito']): ?>
                                                <span class="badge badge-warning">Bloq. Credito</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?edit=<?php echo $cliente['id']; ?>" class="btn btn-secondary btn-sm" onclick="switchTab('form')">Editar</a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar cliente?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $cliente['id']; ?>">
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
        const reglasDocumento = <?php echo json_encode($reglas_documento); ?>;
        const codigoPais = '<?php echo $codigo_pais; ?>';

        function validarDocumento() {
            const input = document.getElementById('rutInput');
            const errorDiv = document.getElementById('rutError');
            const validDiv = document.getElementById('rutValid');
            const valor = input.value.replace(/[^0-9kK]/g, '');

            if (codigoPais === 'CL') {
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
                    input.value = formatearDocumento(valor);
                } else {
                    errorDiv.style.display = 'block';
                    validDiv.style.display = 'none';
                }
            } else {
                validDiv.style.display = 'block';
                errorDiv.style.display = 'none';
            }
        }

        function formatearDocumento(doc) {
            doc = doc.replace(/[^0-9kK]/g, '');

            if (codigoPais === 'CL') {
                const cuerpo = doc.slice(0, -1);
                const dv = doc.slice(-1);
                return cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, ".") + '-' + dv;
            } else if (codigoPais === 'AR') {
                return doc.replace(/(\d{2})(\d{8})(\d{1})/, '$1-$2-$3');
            } else if (codigoPais === 'BR') {
                return doc.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
            }

            return doc;
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
                alert('Por favor complete codigo y nombre');
                return;
            }

            const formData = new FormData();
            formData.append('quick_add', '1');
            formData.append('tabla', tabla);
            formData.append('codigo', codigo);
            formData.append('nombre', nombre);

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }

                const result = await response.json();

                if (result.success) {
                    const select = document.getElementById(selectId);

                    // Crear nueva opcion con el formato correcto
                    // Para tipo_cliente, categoria y grupo usamos codigo como value
                    // Para condicion_pago y lista_precio usamos id como value
                    const useId = (tabla === 'cat_condiciones_pago' || tabla === 'cat_listas_precio');
                    const optionValue = useId ? result.id : result.codigo;

                    const newOption = new Option(
                        result.codigo + ' - ' + result.nombre,
                        optionValue,
                        true,
                        true
                    );

                    // Agregar antes de la opcion "__NUEVO__"
                    const nuevoIndex = select.options.length - 1;
                    select.add(newOption, nuevoIndex);
                    select.value = optionValue;

                    // Limpiar formulario quick add
                    container.classList.remove('active');
                    inputs[0].value = '';
                    inputs[1].value = '';

                    alert('✓ Registro agregado exitosamente');
                } else {
                    // Mostrar mensaje de error especifico del servidor
                    const errorMsg = result.error || 'Error desconocido al agregar registro';
                    alert('❌ ERROR: ' + errorMsg);
                    console.error('Error del servidor:', result);
                }
            } catch (error) {
                console.error('Error de red o procesamiento:', error);
                alert('❌ ERROR: No se pudo conectar con el servidor. Verifique su conexion.');
            }
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
            if (confirm('Limpiar todos los campos del formulario?')) {
                document.getElementById('formCliente').reset();
                window.location.href = '?';
            }
        }

        function buscar() {
            const search = document.getElementById('searchInput').value;
            const filtro = document.getElementById('filtroActivo').value;
            window.location.href = '?search=' + encodeURIComponent(search) + '&filtro_activo=' + filtro;
        }

        function filtrar() {
            buscar();
        }

        function limpiarBusqueda() {
            window.location.href = '?';
        }

        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                buscar();
            }
        });
    </script>
</body>
</html>
