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
$proveedor_edit = null;

// Procesar formulario INSERT RAPIDO de catalogos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_add'])) {
    $tabla = $_POST['tabla'];
    $codigo = strtoupper(trim($_POST['codigo']));
    $nombre = trim($_POST['nombre']);

    $sql = "INSERT INTO $tabla (codigo, nombre, company_id, pais_id) VALUES (:codigo, :nombre, :company_id, :pais_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':codigo' => $codigo,
        ':nombre' => $nombre,
        ':company_id' => $company_id,
        ':pais_id' => $pais_id
    ]);

    $new_id = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'id' => $new_id, 'codigo' => $codigo, 'nombre' => $nombre]);
    exit;
}

// Procesar formulario principal PROVEEDORES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $action = $_POST['action'];

        if ($action === 'create' || $action === 'update') {
            // Datos Generales
            $codigo = strtoupper(trim($_POST['codigo']));
            $rut = trim($_POST['rut']);
            $razon_social = trim($_POST['razon_social']);
            $nombre_comercial = trim($_POST['nombre_comercial']);
            $giro = trim($_POST['giro']);
            $tipo_proveedor = $_POST['tipo_proveedor'];
            $categoria_proveedor = $_POST['categoria_proveedor'];
            $grupo_proveedor = $_POST['grupo_proveedor'];
            $rubro = trim($_POST['rubro']);

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
            $comprador_asignado = $_POST['comprador_asignado'] ?: NULL;
            $condicion_pago_id = $_POST['condicion_pago_id'] ?: NULL;
            $dias_pago = intval($_POST['dias_pago']);
            $forma_pago = $_POST['forma_pago'];
            $moneda = $_POST['moneda'];
            $descuento_proveedor = floatval($_POST['descuento_proveedor']);
            $plazo_entrega_dias = intval($_POST['plazo_entrega_dias']);
            $calificacion = $_POST['calificacion'] ?: NULL;

            // Datos Bancarios
            $banco = trim($_POST['banco']);
            $tipo_cuenta = $_POST['tipo_cuenta'];
            $numero_cuenta = trim($_POST['numero_cuenta']);
            $titular_cuenta = trim($_POST['titular_cuenta']);
            $rut_titular = trim($_POST['rut_titular']);
            $email_transferencia = trim($_POST['email_transferencia']);

            // Datos Fiscales SII
            $actividad_economica = trim($_POST['actividad_economica']);
            $codigo_actividad = trim($_POST['codigo_actividad']);
            $resolucion_sii = trim($_POST['resolucion_sii']);
            $fecha_resolucion = $_POST['fecha_resolucion'] ?: NULL;
            $email_dte = trim($_POST['email_dte']);
            $contribuyente = $_POST['contribuyente'];
            $retencion_impuesto = isset($_POST['retencion_impuesto']) ? 1 : 0;
            $porcentaje_retencion = floatval($_POST['porcentaje_retencion']);

            // Otros
            $observaciones = trim($_POST['observaciones']);
            $activo = isset($_POST['activo']) ? 1 : 0;
            $bloqueado = isset($_POST['bloqueado']) ? 1 : 0;
            $motivo_bloqueo = trim($_POST['motivo_bloqueo']);

            if ($action === 'create') {
                $sql = "INSERT INTO cat_proveedores (
                    company_id, pais_id, idioma_id, centro_gestion_id, user_id,
                    codigo, rut, razon_social, nombre_comercial, giro, tipo_proveedor, categoria_proveedor, grupo_proveedor, rubro,
                    direccion, numero, departamento, region, comuna, ciudad, codigo_postal, pais,
                    telefono1, telefono2, email1, email2, sitio_web,
                    contacto_principal, cargo_contacto, telefono_contacto, email_contacto,
                    comprador_asignado, condicion_pago_id, dias_pago, forma_pago, moneda, descuento_proveedor, plazo_entrega_dias, calificacion,
                    banco, tipo_cuenta, numero_cuenta, titular_cuenta, rut_titular, email_transferencia,
                    actividad_economica, codigo_actividad, resolucion_sii, fecha_resolucion, email_dte, contribuyente, retencion_impuesto, porcentaje_retencion,
                    observaciones, activo, bloqueado, motivo_bloqueo
                ) VALUES (
                    :company_id, :pais_id, :idioma_id, :centro_gestion_id, :user_id,
                    :codigo, :rut, :razon_social, :nombre_comercial, :giro, :tipo_proveedor, :categoria_proveedor, :grupo_proveedor, :rubro,
                    :direccion, :numero, :departamento, :region, :comuna, :ciudad, :codigo_postal, :pais,
                    :telefono1, :telefono2, :email1, :email2, :sitio_web,
                    :contacto_principal, :cargo_contacto, :telefono_contacto, :email_contacto,
                    :comprador_asignado, :condicion_pago_id, :dias_pago, :forma_pago, :moneda, :descuento_proveedor, :plazo_entrega_dias, :calificacion,
                    :banco, :tipo_cuenta, :numero_cuenta, :titular_cuenta, :rut_titular, :email_transferencia,
                    :actividad_economica, :codigo_actividad, :resolucion_sii, :fecha_resolucion, :email_dte, :contribuyente, :retencion_impuesto, :porcentaje_retencion,
                    :observaciones, :activo, :bloqueado, :motivo_bloqueo
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
                    ':giro' => $giro,
                    ':tipo_proveedor' => $tipo_proveedor,
                    ':categoria_proveedor' => $categoria_proveedor,
                    ':grupo_proveedor' => $grupo_proveedor,
                    ':rubro' => $rubro,
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
                    ':comprador_asignado' => $comprador_asignado,
                    ':condicion_pago_id' => $condicion_pago_id,
                    ':dias_pago' => $dias_pago,
                    ':forma_pago' => $forma_pago,
                    ':moneda' => $moneda,
                    ':descuento_proveedor' => $descuento_proveedor,
                    ':plazo_entrega_dias' => $plazo_entrega_dias,
                    ':calificacion' => $calificacion,
                    ':banco' => $banco,
                    ':tipo_cuenta' => $tipo_cuenta,
                    ':numero_cuenta' => $numero_cuenta,
                    ':titular_cuenta' => $titular_cuenta,
                    ':rut_titular' => $rut_titular,
                    ':email_transferencia' => $email_transferencia,
                    ':actividad_economica' => $actividad_economica,
                    ':codigo_actividad' => $codigo_actividad,
                    ':resolucion_sii' => $resolucion_sii,
                    ':fecha_resolucion' => $fecha_resolucion,
                    ':email_dte' => $email_dte,
                    ':contribuyente' => $contribuyente,
                    ':retencion_impuesto' => $retencion_impuesto,
                    ':porcentaje_retencion' => $porcentaje_retencion,
                    ':observaciones' => $observaciones,
                    ':activo' => $activo,
                    ':bloqueado' => $bloqueado,
                    ':motivo_bloqueo' => $motivo_bloqueo
                ]);

                $mensaje = "Proveedor creado exitosamente";
            } else {
                $id = intval($_POST['id']);
                $sql = "UPDATE cat_proveedores SET
                    codigo = :codigo, rut = :rut, razon_social = :razon_social, nombre_comercial = :nombre_comercial,
                    giro = :giro, tipo_proveedor = :tipo_proveedor, categoria_proveedor = :categoria_proveedor, grupo_proveedor = :grupo_proveedor, rubro = :rubro,
                    direccion = :direccion, numero = :numero, departamento = :departamento, region = :region, comuna = :comuna, ciudad = :ciudad, codigo_postal = :codigo_postal, pais = :pais,
                    telefono1 = :telefono1, telefono2 = :telefono2, email1 = :email1, email2 = :email2, sitio_web = :sitio_web,
                    contacto_principal = :contacto_principal, cargo_contacto = :cargo_contacto, telefono_contacto = :telefono_contacto, email_contacto = :email_contacto,
                    comprador_asignado = :comprador_asignado, condicion_pago_id = :condicion_pago_id, dias_pago = :dias_pago,
                    forma_pago = :forma_pago, moneda = :moneda, descuento_proveedor = :descuento_proveedor, plazo_entrega_dias = :plazo_entrega_dias, calificacion = :calificacion,
                    banco = :banco, tipo_cuenta = :tipo_cuenta, numero_cuenta = :numero_cuenta, titular_cuenta = :titular_cuenta, rut_titular = :rut_titular, email_transferencia = :email_transferencia,
                    actividad_economica = :actividad_economica, codigo_actividad = :codigo_actividad,
                    resolucion_sii = :resolucion_sii, fecha_resolucion = :fecha_resolucion, email_dte = :email_dte, contribuyente = :contribuyente,
                    retencion_impuesto = :retencion_impuesto, porcentaje_retencion = :porcentaje_retencion,
                    observaciones = :observaciones, activo = :activo, bloqueado = :bloqueado, motivo_bloqueo = :motivo_bloqueo, updated_at = NOW()
                WHERE id = :id AND company_id = :company_id";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $id,
                    ':company_id' => $company_id,
                    ':codigo' => $codigo,
                    ':rut' => $rut,
                    ':razon_social' => $razon_social,
                    ':nombre_comercial' => $nombre_comercial,
                    ':giro' => $giro,
                    ':tipo_proveedor' => $tipo_proveedor,
                    ':categoria_proveedor' => $categoria_proveedor,
                    ':grupo_proveedor' => $grupo_proveedor,
                    ':rubro' => $rubro,
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
                    ':comprador_asignado' => $comprador_asignado,
                    ':condicion_pago_id' => $condicion_pago_id,
                    ':dias_pago' => $dias_pago,
                    ':forma_pago' => $forma_pago,
                    ':moneda' => $moneda,
                    ':descuento_proveedor' => $descuento_proveedor,
                    ':plazo_entrega_dias' => $plazo_entrega_dias,
                    ':calificacion' => $calificacion,
                    ':banco' => $banco,
                    ':tipo_cuenta' => $tipo_cuenta,
                    ':numero_cuenta' => $numero_cuenta,
                    ':titular_cuenta' => $titular_cuenta,
                    ':rut_titular' => $rut_titular,
                    ':email_transferencia' => $email_transferencia,
                    ':actividad_economica' => $actividad_economica,
                    ':codigo_actividad' => $codigo_actividad,
                    ':resolucion_sii' => $resolucion_sii,
                    ':fecha_resolucion' => $fecha_resolucion,
                    ':email_dte' => $email_dte,
                    ':contribuyente' => $contribuyente,
                    ':retencion_impuesto' => $retencion_impuesto,
                    ':porcentaje_retencion' => $porcentaje_retencion,
                    ':observaciones' => $observaciones,
                    ':activo' => $activo,
                    ':bloqueado' => $bloqueado,
                    ':motivo_bloqueo' => $motivo_bloqueo
                ]);

                $mensaje = "Proveedor actualizado exitosamente";
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("DELETE FROM cat_proveedores WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $company_id]);
            $mensaje = "Proveedor eliminado exitosamente";
        }
    } catch (PDOException $e) {
        $mensaje = "Error: " . $e->getMessage();
    }
}

// Cargar proveedor para edicion
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM cat_proveedores WHERE id = ? AND company_id = ?");
    $stmt->execute([$id, $company_id]);
    $proveedor_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Listar proveedores
$search = $_GET['search'] ?? '';
$filtro_activo = $_GET['filtro_activo'] ?? '';

$sql = "SELECT * FROM cat_proveedores WHERE company_id = ?";
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
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar catalogos necesarios
$condiciones_pago = $pdo->query("SELECT * FROM cat_condiciones_pago WHERE company_id = $company_id AND pais_id = $pais_id ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$compradores = $pdo->query("SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo FROM rrhh_empleados WHERE company_id = $company_id AND cargo = 'Comprador' AND activo = 1 ORDER BY nombres")->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Maestro de Proveedores - CONECTA ERP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            color: #f5576c;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #f5576c;
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
            border-color: #f5576c;
            box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.1);
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(245, 87, 108, 0.3);
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
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
            border: 2px dashed #f5576c;
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
            color: #f5576c;
            border-bottom-color: #f5576c;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .rating-stars {
            display: flex;
            gap: 5px;
            font-size: 20px;
        }

        .rating-stars label {
            cursor: pointer;
            color: #d1d5db;
        }

        .rating-stars input[type="radio"]:checked ~ label {
            color: #fbbf24;
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
            <h1>MAESTRO DE PROVEEDORES</h1>
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
                <form method="POST" id="formProveedor">
                    <input type="hidden" name="action" value="<?php echo $proveedor_edit ? 'update' : 'create'; ?>">
                    <?php if ($proveedor_edit): ?>
                        <input type="hidden" name="id" value="<?php echo $proveedor_edit['id']; ?>">
                    <?php endif; ?>

                    <div class="form-section">
                        <h3 class="form-section-title">DATOS GENERALES</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="required">Codigo Proveedor</label>
                                <input type="text" name="codigo" value="<?php echo $proveedor_edit['codigo'] ?? ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="required"><?php echo $reglas_documento['nombre_documento']; ?></label>
                                <input type="text" name="rut" id="rutInput" value="<?php echo $proveedor_edit['rut'] ?? ''; ?>" required onblur="validarDocumento()">
                                <div id="rutError" class="rut-feedback invalid">Documento invalido</div>
                                <div id="rutValid" class="rut-feedback valid">Documento valido</div>
                            </div>

                            <div class="form-group">
                                <label class="required">Razon Social</label>
                                <input type="text" name="razon_social" value="<?php echo $proveedor_edit['razon_social'] ?? ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Nombre Comercial</label>
                                <input type="text" name="nombre_comercial" value="<?php echo $proveedor_edit['nombre_comercial'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Giro</label>
                                <input type="text" name="giro" value="<?php echo $proveedor_edit['giro'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Rubro</label>
                                <select name="rubro" id="rubro_select" onchange="toggleQuickAdd(this, 'rubro_quick')">
                                    <option value="">Seleccione...</option>
                                    <option value="ALIMENTOS" <?php echo ($proveedor_edit['rubro'] ?? '') === 'ALIMENTOS' ? 'selected' : ''; ?>>Alimentos</option>
                                    <option value="TECNOLOGIA" <?php echo ($proveedor_edit['rubro'] ?? '') === 'TECNOLOGIA' ? 'selected' : ''; ?>>Tecnologia</option>
                                    <option value="CONSTRUCCION" <?php echo ($proveedor_edit['rubro'] ?? '') === 'CONSTRUCCION' ? 'selected' : ''; ?>>Construccion</option>
                                    <option value="SERVICIOS" <?php echo ($proveedor_edit['rubro'] ?? '') === 'SERVICIOS' ? 'selected' : ''; ?>>Servicios</option>
                                    <option value="__NUEVO__">AGREGAR NUEVO...</option>
                                </select>
                                <div id="rubro_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_rubro_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_rubro_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_rubros_proveedor', 'rubro_select', 'rubro_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('rubro_quick', 'rubro_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="required">Tipo Proveedor</label>
                                <select name="tipo_proveedor" required>
                                    <option value="">Seleccione...</option>
                                    <option value="NACIONAL" <?php echo ($proveedor_edit['tipo_proveedor'] ?? 'NACIONAL') === 'NACIONAL' ? 'selected' : ''; ?>>Nacional</option>
                                    <option value="INTERNACIONAL" <?php echo ($proveedor_edit['tipo_proveedor'] ?? '') === 'INTERNACIONAL' ? 'selected' : ''; ?>>Internacional</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Categoria Proveedor</label>
                                <select name="categoria_proveedor" id="categoria_select" onchange="toggleQuickAdd(this, 'categoria_quick')">
                                    <option value="">Seleccione...</option>
                                    <option value="ESTRATEGICO" <?php echo ($proveedor_edit['categoria_proveedor'] ?? '') === 'ESTRATEGICO' ? 'selected' : ''; ?>>Estrategico</option>
                                    <option value="PREFERIDO" <?php echo ($proveedor_edit['categoria_proveedor'] ?? '') === 'PREFERIDO' ? 'selected' : ''; ?>>Preferido</option>
                                    <option value="ESTANDAR" <?php echo ($proveedor_edit['categoria_proveedor'] ?? '') === 'ESTANDAR' ? 'selected' : ''; ?>>Estandar</option>
                                    <option value="__NUEVO__">AGREGAR NUEVO...</option>
                                </select>
                                <div id="categoria_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_cat_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_cat_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_categorias_proveedor', 'categoria_select', 'categoria_quick')">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelQuickAdd('categoria_quick', 'categoria_select')">Cancelar</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Grupo Proveedor</label>
                                <select name="grupo_proveedor" id="grupo_select" onchange="toggleQuickAdd(this, 'grupo_quick')">
                                    <option value="">Seleccione...</option>
                                    <option value="MATERIAS_PRIMAS" <?php echo ($proveedor_edit['grupo_proveedor'] ?? '') === 'MATERIAS_PRIMAS' ? 'selected' : ''; ?>>Materias Primas</option>
                                    <option value="INSUMOS" <?php echo ($proveedor_edit['grupo_proveedor'] ?? '') === 'INSUMOS' ? 'selected' : ''; ?>>Insumos</option>
                                    <option value="SERVICIOS" <?php echo ($proveedor_edit['grupo_proveedor'] ?? '') === 'SERVICIOS' ? 'selected' : ''; ?>>Servicios</option>
                                    <option value="__NUEVO__">AGREGAR NUEVO...</option>
                                </select>
                                <div id="grupo_quick" class="quick-add">
                                    <div class="quick-add-grid">
                                        <input type="text" name="quick_grupo_codigo" placeholder="Codigo">
                                        <input type="text" name="quick_grupo_nombre" placeholder="Nombre">
                                        <button type="button" class="btn btn-success btn-sm" onclick="quickAdd('cat_grupos_proveedor', 'grupo_select', 'grupo_quick')">Guardar</button>
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
                                <input type="text" name="direccion" value="<?php echo $proveedor_edit['direccion'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Numero</label>
                                <input type="text" name="numero" value="<?php echo $proveedor_edit['numero'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Depto/Oficina</label>
                                <input type="text" name="departamento" value="<?php echo $proveedor_edit['departamento'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Region</label>
                                <select name="region">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($regiones_chile as $codigo => $nombre): ?>
                                        <option value="<?php echo $codigo; ?>" <?php echo ($proveedor_edit['region'] ?? '') === $codigo ? 'selected' : ''; ?>>
                                            <?php echo $nombre; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Comuna</label>
                                <input type="text" name="comuna" value="<?php echo $proveedor_edit['comuna'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Ciudad</label>
                                <input type="text" name="ciudad" value="<?php echo $proveedor_edit['ciudad'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Codigo Postal</label>
                                <input type="text" name="codigo_postal" value="<?php echo $proveedor_edit['codigo_postal'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label class="required">Pais</label>
                                <select name="pais" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($paises as $p): ?>
                                        <option value="<?php echo $p['codigo_pais']; ?>" <?php echo ($proveedor_edit['pais'] ?? $codigo_pais) === $p['codigo_pais'] ? 'selected' : ''; ?>>
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
                                <input type="text" name="telefono1" value="<?php echo $proveedor_edit['telefono1'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Telefono 2</label>
                                <input type="text" name="telefono2" value="<?php echo $proveedor_edit['telefono2'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Email 1</label>
                                <input type="email" name="email1" value="<?php echo $proveedor_edit['email1'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Email 2</label>
                                <input type="email" name="email2" value="<?php echo $proveedor_edit['email2'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Sitio Web</label>
                                <input type="text" name="sitio_web" value="<?php echo $proveedor_edit['sitio_web'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Contacto Principal</label>
                                <input type="text" name="contacto_principal" value="<?php echo $proveedor_edit['contacto_principal'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Cargo Contacto</label>
                                <input type="text" name="cargo_contacto" value="<?php echo $proveedor_edit['cargo_contacto'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Telefono Contacto</label>
                                <input type="text" name="telefono_contacto" value="<?php echo $proveedor_edit['telefono_contacto'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Email Contacto</label>
                                <input type="email" name="email_contacto" value="<?php echo $proveedor_edit['email_contacto'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">DATOS COMERCIALES</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Comprador Asignado</label>
                                <select name="comprador_asignado">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($compradores as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo ($proveedor_edit['comprador_asignado'] ?? '') == $c['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['nombre_completo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Condicion de Pago</label>
                                <select name="condicion_pago_id" id="condicion_pago_select" onchange="toggleQuickAdd(this, 'condicion_pago_quick')">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($condiciones_pago as $cp): ?>
                                        <option value="<?php echo $cp['id']; ?>" <?php echo ($proveedor_edit['condicion_pago_id'] ?? '') == $cp['id'] ? 'selected' : ''; ?>>
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
                                <label>Dias Pago</label>
                                <input type="number" name="dias_pago" value="<?php echo $proveedor_edit['dias_pago'] ?? '0'; ?>" min="0">
                            </div>

                            <div class="form-group">
                                <label>Forma de Pago</label>
                                <select name="forma_pago">
                                    <option value="">Seleccione...</option>
                                    <option value="TRANSFERENCIA" <?php echo ($proveedor_edit['forma_pago'] ?? '') === 'TRANSFERENCIA' ? 'selected' : ''; ?>>Transferencia</option>
                                    <option value="CHEQUE" <?php echo ($proveedor_edit['forma_pago'] ?? '') === 'CHEQUE' ? 'selected' : ''; ?>>Cheque</option>
                                    <option value="EFECTIVO" <?php echo ($proveedor_edit['forma_pago'] ?? '') === 'EFECTIVO' ? 'selected' : ''; ?>>Efectivo</option>
                                    <option value="CREDITO" <?php echo ($proveedor_edit['forma_pago'] ?? '') === 'CREDITO' ? 'selected' : ''; ?>>Credito</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Moneda</label>
                                <select name="moneda">
                                    <option value="">Seleccione...</option>
                                    <option value="CLP" <?php echo ($proveedor_edit['moneda'] ?? 'CLP') === 'CLP' ? 'selected' : ''; ?>>CLP - Peso Chileno</option>
                                    <option value="USD" <?php echo ($proveedor_edit['moneda'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD - Dolar</option>
                                    <option value="EUR" <?php echo ($proveedor_edit['moneda'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Descuento Proveedor (%)</label>
                                <input type="number" step="0.01" name="descuento_proveedor" value="<?php echo $proveedor_edit['descuento_proveedor'] ?? '0'; ?>" min="0" max="100">
                            </div>

                            <div class="form-group">
                                <label>Plazo Entrega (dias)</label>
                                <input type="number" name="plazo_entrega_dias" value="<?php echo $proveedor_edit['plazo_entrega_dias'] ?? '0'; ?>" min="0">
                            </div>

                            <div class="form-group">
                                <label>Calificacion</label>
                                <select name="calificacion">
                                    <option value="">Sin calificar</option>
                                    <option value="5" <?php echo ($proveedor_edit['calificacion'] ?? '') === '5' ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ Excelente</option>
                                    <option value="4" <?php echo ($proveedor_edit['calificacion'] ?? '') === '4' ? 'selected' : ''; ?>>⭐⭐⭐⭐ Muy Bueno</option>
                                    <option value="3" <?php echo ($proveedor_edit['calificacion'] ?? '') === '3' ? 'selected' : ''; ?>>⭐⭐⭐ Bueno</option>
                                    <option value="2" <?php echo ($proveedor_edit['calificacion'] ?? '') === '2' ? 'selected' : ''; ?>>⭐⭐ Regular</option>
                                    <option value="1" <?php echo ($proveedor_edit['calificacion'] ?? '') === '1' ? 'selected' : ''; ?>>⭐ Malo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">DATOS BANCARIOS</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Banco</label>
                                <select name="banco" id="banco_select" onchange="toggleQuickAdd(this, 'banco_quick')">
                                    <option value="">Seleccione...</option>
                                    <option value="BANCO DE CHILE" <?php echo ($proveedor_edit['banco'] ?? '') === 'BANCO DE CHILE' ? 'selected' : ''; ?>>Banco de Chile</option>
                                    <option value="BANCO ESTADO" <?php echo ($proveedor_edit['banco'] ?? '') === 'BANCO ESTADO' ? 'selected' : ''; ?>>Banco Estado</option>
                                    <option value="SANTANDER" <?php echo ($proveedor_edit['banco'] ?? '') === 'SANTANDER' ? 'selected' : ''; ?>>Santander</option>
                                    <option value="BCI" <?php echo ($proveedor_edit['banco'] ?? '') === 'BCI' ? 'selected' : ''; ?>>BCI</option>
                                    <option value="SCOTIABANK" <?php echo ($proveedor_edit['banco'] ?? '') === 'SCOTIABANK' ? 'selected' : ''; ?>>Scotiabank</option>
                                    <option value="__NUEVO__">AGREGAR NUEVO...</option>
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
                                <label>Tipo Cuenta</label>
                                <select name="tipo_cuenta">
                                    <option value="">Seleccione...</option>
                                    <option value="CORRIENTE" <?php echo ($proveedor_edit['tipo_cuenta'] ?? '') === 'CORRIENTE' ? 'selected' : ''; ?>>Cuenta Corriente</option>
                                    <option value="VISTA" <?php echo ($proveedor_edit['tipo_cuenta'] ?? '') === 'VISTA' ? 'selected' : ''; ?>>Cuenta Vista</option>
                                    <option value="AHORRO" <?php echo ($proveedor_edit['tipo_cuenta'] ?? '') === 'AHORRO' ? 'selected' : ''; ?>>Cuenta Ahorro</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Numero Cuenta</label>
                                <input type="text" name="numero_cuenta" value="<?php echo $proveedor_edit['numero_cuenta'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Titular Cuenta</label>
                                <input type="text" name="titular_cuenta" value="<?php echo $proveedor_edit['titular_cuenta'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>RUT Titular</label>
                                <input type="text" name="rut_titular" value="<?php echo $proveedor_edit['rut_titular'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Email Transferencia</label>
                                <input type="email" name="email_transferencia" value="<?php echo $proveedor_edit['email_transferencia'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">DATOS FISCALES SII (CHILE)</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Actividad Economica</label>
                                <input type="text" name="actividad_economica" value="<?php echo $proveedor_edit['actividad_economica'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Codigo Actividad</label>
                                <input type="text" name="codigo_actividad" value="<?php echo $proveedor_edit['codigo_actividad'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Resolucion SII</label>
                                <input type="text" name="resolucion_sii" value="<?php echo $proveedor_edit['resolucion_sii'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Fecha Resolucion</label>
                                <input type="date" name="fecha_resolucion" value="<?php echo $proveedor_edit['fecha_resolucion'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Email DTE</label>
                                <input type="email" name="email_dte" value="<?php echo $proveedor_edit['email_dte'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Tipo Contribuyente</label>
                                <select name="contribuyente">
                                    <option value="">Seleccione...</option>
                                    <option value="IVA_AFECTO" <?php echo ($proveedor_edit['contribuyente'] ?? '') === 'IVA_AFECTO' ? 'selected' : ''; ?>>IVA Afecto</option>
                                    <option value="IVA_EXENTO" <?php echo ($proveedor_edit['contribuyente'] ?? '') === 'IVA_EXENTO' ? 'selected' : ''; ?>>IVA Exento</option>
                                    <option value="NO_CONTRIBUYENTE" <?php echo ($proveedor_edit['contribuyente'] ?? '') === 'NO_CONTRIBUYENTE' ? 'selected' : ''; ?>>No Contribuyente</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Retencion Impuesto</label>
                                <div class="checkbox-group">
                                    <input type="checkbox" name="retencion_impuesto" <?php echo ($proveedor_edit['retencion_impuesto'] ?? 0) ? 'checked' : ''; ?>>
                                    <span>Aplica retencion</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Porcentaje Retencion (%)</label>
                                <input type="number" step="0.01" name="porcentaje_retencion" value="<?php echo $proveedor_edit['porcentaje_retencion'] ?? '0'; ?>" min="0" max="100">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">OBSERVACIONES Y ESTADO</h3>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>Observaciones</label>
                                <textarea name="observaciones"><?php echo $proveedor_edit['observaciones'] ?? ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Estado</label>
                                <div class="checkbox-group">
                                    <input type="checkbox" name="activo" <?php echo ($proveedor_edit['activo'] ?? 1) ? 'checked' : ''; ?>>
                                    <span>Proveedor Activo</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Bloqueo</label>
                                <div class="checkbox-group">
                                    <input type="checkbox" name="bloqueado" <?php echo ($proveedor_edit['bloqueado'] ?? 0) ? 'checked' : ''; ?>>
                                    <span>Proveedor Bloqueado</span>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label>Motivo Bloqueo</label>
                                <textarea name="motivo_bloqueo"><?php echo $proveedor_edit['motivo_bloqueo'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="limpiarFormulario()">Limpiar</button>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $proveedor_edit ? 'Actualizar Proveedor' : 'Crear Proveedor'; ?>
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
                                <th>Rubro</th>
                                <th>Ciudad</th>
                                <th>Telefono</th>
                                <th>Calificacion</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($proveedores)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px;">No hay proveedores registrados</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($proveedores as $proveedor): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($proveedor['codigo']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($proveedor['rut']); ?></td>
                                        <td><?php echo htmlspecialchars($proveedor['razon_social']); ?></td>
                                        <td><?php echo htmlspecialchars($proveedor['rubro']); ?></td>
                                        <td><?php echo htmlspecialchars($proveedor['ciudad']); ?></td>
                                        <td><?php echo htmlspecialchars($proveedor['telefono1']); ?></td>
                                        <td>
                                            <?php
                                            $calif = $proveedor['calificacion'];
                                            if ($calif) {
                                                echo str_repeat('⭐', intval($calif));
                                            } else {
                                                echo '<span class="badge badge-info">Sin calificar</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($proveedor['activo']): ?>
                                                <span class="badge badge-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactivo</span>
                                            <?php endif; ?>
                                            <?php if ($proveedor['bloqueado']): ?>
                                                <span class="badge badge-warning">Bloqueado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?edit=<?php echo $proveedor['id']; ?>" class="btn btn-secondary btn-sm" onclick="switchTab('form')">Editar</a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar proveedor?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $proveedor['id']; ?>">
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
                alert('Complete codigo y nombre');
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

                const result = await response.json();

                if (result.success) {
                    const select = document.getElementById(selectId);
                    const newOption = new Option(codigo + ' - ' + nombre, nombre, true, true);
                    select.add(newOption, select.options.length - 1);
                    select.value = nombre;

                    container.classList.remove('active');
                    inputs[0].value = '';
                    inputs[1].value = '';

                    alert('Registro agregado exitosamente');
                } else {
                    alert('Error al agregar registro');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al procesar solicitud');
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
                document.getElementById('formProveedor').reset();
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
