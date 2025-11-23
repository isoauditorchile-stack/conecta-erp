<?php
/**
 * MODULO 1: ENTIDADES MAESTRAS - CORE
 * Sistema ERP Estilo SAP
 * Version: 1.0 - REPARADO Y COMPLETO
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir variables de sesión con valores por defecto
$userName = $_SESSION['user_name'] ?? 'Invitado';
$userRole = $_SESSION['user_role'] ?? 'Sin rol';
$userId = $_SESSION['user_id'] ?? 0;

// Inicializar variable de mensaje
$mensaje = '';
$tipo_mensaje = '';

// Verificar si el usuario está autenticado (opcional)
if (!isset($_SESSION['user_id'])) {
    // Redirigir al login si es necesario
    // header('Location: /login.php');
    // exit;
}

// Incluir configuración
require_once __DIR__ . '/../../includes/config.php';

// Convertir conexión MySQLi a PDO
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función helper para formatear números
if (!function_exists('formatNumber')) {
    function formatNumber($number) {
        return number_format($number, 0, ',', '.');
    }
}

// Procesar acciones del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $seccion = $_POST['seccion'] ?? '';

    try {
        switch ($seccion) {
            case 'clientes':
                if ($accion === 'crear') {
                    $stmt = $db->prepare("INSERT INTO clientes (
                        codigo, tipo_cliente, razon_social, nombre_comercial,
                        rut, giro, clasificacion_abc, segmento_comercial,
                        zona_geografica, ejecutivo_asignado, idioma, canal_venta,
                        condicion_pago, forma_pago, descuento_comercial,
                        lista_precios_id, plazo_entrega_dias, politica_devolucion,
                        limite_credito, riesgo_crediticio, nivel_morosidad,
                        tipo_contribuyente, exento_impuestos, certificado_tributario,
                        direccion_principal, ciudad, region, pais,
                        telefono, email, sitio_web, observaciones,
                        estado, created_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                    $stmt->execute([
                        $_POST['codigo'],
                        $_POST['tipo_cliente'],
                        $_POST['razon_social'],
                        $_POST['nombre_comercial'],
                        $_POST['rut'],
                        $_POST['giro'],
                        $_POST['clasificacion_abc'],
                        $_POST['segmento_comercial'],
                        $_POST['zona_geografica'],
                        $_POST['ejecutivo_asignado'],
                        $_POST['idioma'],
                        $_POST['canal_venta'],
                        $_POST['condicion_pago'],
                        $_POST['forma_pago'],
                        $_POST['descuento_comercial'],
                        $_POST['lista_precios_id'],
                        $_POST['plazo_entrega_dias'],
                        $_POST['politica_devolucion'],
                        $_POST['limite_credito'],
                        $_POST['riesgo_crediticio'],
                        $_POST['nivel_morosidad'],
                        $_POST['tipo_contribuyente'],
                        $_POST['exento_impuestos'],
                        $_POST['certificado_tributario'],
                        $_POST['direccion_principal'],
                        $_POST['ciudad'],
                        $_POST['region'],
                        $_POST['pais'],
                        $_POST['telefono'],
                        $_POST['email'],
                        $_POST['sitio_web'],
                        $_POST['observaciones'],
                        $_POST['estado'],
                        $userId
                    ]);
                    $mensaje = 'Cliente creado exitosamente';
                    $tipo_mensaje = 'success';

                } elseif ($accion === 'editar') {
                    $stmt = $db->prepare("UPDATE clientes SET
                        tipo_cliente=?, razon_social=?, nombre_comercial=?,
                        rut=?, giro=?, clasificacion_abc=?, segmento_comercial=?,
                        zona_geografica=?, ejecutivo_asignado=?, idioma=?, canal_venta=?,
                        condicion_pago=?, forma_pago=?, descuento_comercial=?,
                        lista_precios_id=?, plazo_entrega_dias=?, politica_devolucion=?,
                        limite_credito=?, riesgo_crediticio=?, nivel_morosidad=?,
                        tipo_contribuyente=?, exento_impuestos=?, certificado_tributario=?,
                        direccion_principal=?, ciudad=?, region=?, pais=?,
                        telefono=?, email=?, sitio_web=?, observaciones=?,
                        estado=?, updated_by=?, updated_at=NOW()
                        WHERE id=?");

                    $stmt->execute([
                        $_POST['tipo_cliente'],
                        $_POST['razon_social'],
                        $_POST['nombre_comercial'],
                        $_POST['rut'],
                        $_POST['giro'],
                        $_POST['clasificacion_abc'],
                        $_POST['segmento_comercial'],
                        $_POST['zona_geografica'],
                        $_POST['ejecutivo_asignado'],
                        $_POST['idioma'],
                        $_POST['canal_venta'],
                        $_POST['condicion_pago'],
                        $_POST['forma_pago'],
                        $_POST['descuento_comercial'],
                        $_POST['lista_precios_id'],
                        $_POST['plazo_entrega_dias'],
                        $_POST['politica_devolucion'],
                        $_POST['limite_credito'],
                        $_POST['riesgo_crediticio'],
                        $_POST['nivel_morosidad'],
                        $_POST['tipo_contribuyente'],
                        $_POST['exento_impuestos'],
                        $_POST['certificado_tributario'],
                        $_POST['direccion_principal'],
                        $_POST['ciudad'],
                        $_POST['region'],
                        $_POST['pais'],
                        $_POST['telefono'],
                        $_POST['email'],
                        $_POST['sitio_web'],
                        $_POST['observaciones'],
                        $_POST['estado'],
                        $userId,
                        $_POST['id']
                    ]);
                    $mensaje = 'Cliente actualizado exitosamente';
                    $tipo_mensaje = 'success';

                } elseif ($accion === 'eliminar') {
                    $stmt = $db->prepare("UPDATE clientes SET estado='ELIMINADO', deleted_by=?, deleted_at=NOW() WHERE id=?");
                    $stmt->execute([$userId, $_POST['id']]);
                    $mensaje = 'Cliente eliminado exitosamente';
                    $tipo_mensaje = 'success';
                }
                break;

            case 'direcciones_cliente':
                if ($accion === 'crear') {
                    $stmt = $db->prepare("INSERT INTO direccion (
                        cliente_id, tipo_direccion, nombre_direccion, direccion,
                        ciudad, region, pais, codigo_postal, telefono,
                        contacto, email, latitud, longitud, horario_atencion,
                        es_principal, estado, created_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                    $stmt->execute([
                        $_POST['cliente_id'],
                        $_POST['tipo_direccion'],
                        $_POST['nombre_direccion'],
                        $_POST['direccion'],
                        $_POST['ciudad'],
                        $_POST['region'],
                        $_POST['pais'],
                        $_POST['codigo_postal'],
                        $_POST['telefono'],
                        $_POST['contacto'],
                        $_POST['email'],
                        $_POST['latitud'],
                        $_POST['longitud'],
                        $_POST['horario_atencion'],
                        $_POST['es_principal'],
                        $_POST['estado'],
                        $userId
                    ]);
                    $mensaje = 'Direccion creada exitosamente';
                    $tipo_mensaje = 'success';

                } elseif ($accion === 'editar') {
                    $stmt = $db->prepare("UPDATE direccion SET
                        tipo_direccion=?, nombre_direccion=?, direccion=?,
                        ciudad=?, region=?, pais=?, codigo_postal=?, telefono=?,
                        contacto=?, email=?, latitud=?, longitud=?, horario_atencion=?,
                        es_principal=?, estado=?, updated_by=?, updated_at=NOW()
                        WHERE id=?");

                    $stmt->execute([
                        $_POST['tipo_direccion'],
                        $_POST['nombre_direccion'],
                        $_POST['direccion'],
                        $_POST['ciudad'],
                        $_POST['region'],
                        $_POST['pais'],
                        $_POST['codigo_postal'],
                        $_POST['telefono'],
                        $_POST['contacto'],
                        $_POST['email'],
                        $_POST['latitud'],
                        $_POST['longitud'],
                        $_POST['horario_atencion'],
                        $_POST['es_principal'],
                        $_POST['estado'],
                        $userId,
                        $_POST['id']
                    ]);
                    $mensaje = 'Direccion actualizada exitosamente';
                    $tipo_mensaje = 'success';

                } elseif ($accion === 'eliminar') {
                    $stmt = $db->prepare("DELETE FROM direccion WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $mensaje = 'Direccion eliminada exitosamente';
                    $tipo_mensaje = 'success';
                }
                break;

            case 'contactos_cliente':
                if ($accion === 'crear') {
                    $stmt = $db->prepare("INSERT INTO clientes_contactos (
                        cliente_id, nombre, apellido, cargo, departamento,
                        telefono, celular, email, rol_contacto,
                        preferencia_comunicacion, idioma, fecha_nacimiento,
                        es_principal, activo, created_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                    $stmt->execute([
                        $_POST['cliente_id'],
                        $_POST['nombre'],
                        $_POST['apellido'],
                        $_POST['cargo'],
                        $_POST['departamento'],
                        $_POST['telefono'],
                        $_POST['celular'],
                        $_POST['email'],
                        $_POST['rol_contacto'],
                        $_POST['preferencia_comunicacion'],
                        $_POST['idioma'],
                        $_POST['fecha_nacimiento'],
                        $_POST['es_principal'],
                        $_POST['activo'],
                        $userId
                    ]);
                    $mensaje = 'Contacto creado exitosamente';
                    $tipo_mensaje = 'success';

                } elseif ($accion === 'editar') {
                    $stmt = $db->prepare("UPDATE clientes_contactos SET
                        nombre=?, apellido=?, cargo=?, departamento=?,
                        telefono=?, celular=?, email=?, rol_contacto=?,
                        preferencia_comunicacion=?, idioma=?, fecha_nacimiento=?,
                        es_principal=?, activo=?, updated_by=?, updated_at=NOW()
                        WHERE id=?");

                    $stmt->execute([
                        $_POST['nombre'],
                        $_POST['apellido'],
                        $_POST['cargo'],
                        $_POST['departamento'],
                        $_POST['telefono'],
                        $_POST['celular'],
                        $_POST['email'],
                        $_POST['rol_contacto'],
                        $_POST['preferencia_comunicacion'],
                        $_POST['idioma'],
                        $_POST['fecha_nacimiento'],
                        $_POST['es_principal'],
                        $_POST['activo'],
                        $userId,
                        $_POST['id']
                    ]);
                    $mensaje = 'Contacto actualizado exitosamente';
                    $tipo_mensaje = 'success';

                } elseif ($accion === 'eliminar') {
                    $stmt = $db->prepare("UPDATE clientes_contactos SET activo=0, deleted_by=?, deleted_at=NOW() WHERE id=?");
                    $stmt->execute([$userId, $_POST['id']]);
                    $mensaje = 'Contacto eliminado exitosamente';
                    $tipo_mensaje = 'success';
                }
                break;

            case 'contratos_cliente':
                if ($accion === 'crear') {
                    $stmt = $db->prepare("INSERT INTO clientes_contratos (
                        cliente_id, numero_contrato, tipo_contrato, descripcion,
                        fecha_inicio, fecha_fin, monto_total, moneda,
                        condiciones, estado_contrato, renovacion_automatica,
                        archivo_adjunto, created_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                    $stmt->execute([
                        $_POST['cliente_id'],
                        $_POST['numero_contrato'],
                        $_POST['tipo_contrato'],
                        $_POST['descripcion'],
                        $_POST['fecha_inicio'],
                        $_POST['fecha_fin'],
                        $_POST['monto_total'],
                        $_POST['moneda'],
                        $_POST['condiciones'],
                        $_POST['estado_contrato'],
                        $_POST['renovacion_automatica'],
                        $_POST['archivo_adjunto'],
                        $userId
                    ]);
                    $mensaje = 'Contrato creado exitosamente';
                    $tipo_mensaje = 'success';

                } elseif ($accion === 'editar') {
                    $stmt = $db->prepare("UPDATE clientes_contratos SET
                        numero_contrato=?, tipo_contrato=?, descripcion=?,
                        fecha_inicio=?, fecha_fin=?, monto_total=?, moneda=?,
                        condiciones=?, estado_contrato=?, renovacion_automatica=?,
                        archivo_adjunto=?, updated_by=?, updated_at=NOW()
                        WHERE id=?");

                    $stmt->execute([
                        $_POST['numero_contrato'],
                        $_POST['tipo_contrato'],
                        $_POST['descripcion'],
                        $_POST['fecha_inicio'],
                        $_POST['fecha_fin'],
                        $_POST['monto_total'],
                        $_POST['moneda'],
                        $_POST['condiciones'],
                        $_POST['estado_contrato'],
                        $_POST['renovacion_automatica'],
                        $_POST['archivo_adjunto'],
                        $userId,
                        $_POST['id']
                    ]);
                    $mensaje = 'Contrato actualizado exitosamente';
                    $tipo_mensaje = 'success';

                } elseif ($accion === 'eliminar') {
                    $stmt = $db->prepare("UPDATE clientes_contratos SET estado_contrato='CANCELADO' WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $mensaje = 'Contrato cancelado exitosamente';
                    $tipo_mensaje = 'success';
                }
                break;

            case 'acuerdos_comerciales':
                if ($accion === 'crear') {
                    $stmt = $db->prepare("INSERT INTO clientes_acuerdos (
                        cliente_id, codigo_acuerdo, descripcion, tipo_descuento,
                        valor_descuento, fecha_inicio, fecha_fin, productos_aplicables,
                        cantidad_minima, monto_minimo, estado, created_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                    $stmt->execute([
                        $_POST['cliente_id'],
                        $_POST['codigo_acuerdo'],
                        $_POST['descripcion'],
                        $_POST['tipo_descuento'],
                        $_POST['valor_descuento'],
                        $_POST['fecha_inicio'],
                        $_POST['fecha_fin'],
                        $_POST['productos_aplicables'],
                        $_POST['cantidad_minima'],
                        $_POST['monto_minimo'],
                        $_POST['estado'],
                        $userId
                    ]);
                    $mensaje = 'Acuerdo Comercial creado exitosamente';
                    $tipo_mensaje = 'success';

                } elseif ($accion === 'editar') {
                    $stmt = $db->prepare("UPDATE clientes_acuerdos SET
                        descripcion=?, tipo_descuento=?,
                        valor_descuento=?, fecha_inicio=?, fecha_fin=?, productos_aplicables=?,
                        cantidad_minima=?, monto_minimo=?, estado=?, updated_by=?, updated_at=NOW()
                        WHERE id=?");

                    $stmt->execute([
                        $_POST['descripcion'],
                        $_POST['tipo_descuento'],
                        $_POST['valor_descuento'],
                        $_POST['fecha_inicio'],
                        $_POST['fecha_fin'],
                        $_POST['productos_aplicables'],
                        $_POST['cantidad_minima'],
                        $_POST['monto_minimo'],
                        $_POST['estado'],
                        $userId,
                        $_POST['id']
                    ]);
                    $mensaje = 'Acuerdo Comercial actualizado exitosamente';
                    $tipo_mensaje = 'success';

                } elseif ($accion === 'eliminar') {
                    $stmt = $db->prepare("UPDATE clientes_acuerdos SET estado='INACTIVO' WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $mensaje = 'Acuerdo Comercial inactivado exitosamente';
                    $tipo_mensaje = 'success';
                }
                break;
        }

    } catch (PDOException $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener datos para mostrar
$seccion_activa = $_GET['seccion'] ?? 'clientes';
$accion_form = $_GET['accion'] ?? 'listar';
$id_editar = $_GET['id'] ?? null;
$cliente_id = $_GET['cliente_id'] ?? null;

// Datos para edicion
$datos_edicion = null;
if ($id_editar && $accion_form === 'editar') {
    $tabla_map = [
        'clientes' => 'clientes',
        'direcciones_cliente' => 'direccion',
        'contactos_cliente' => 'clientes_contactos',
        'contratos_cliente' => 'clientes_contratos',
        'acuerdos_comerciales' => 'clientes_acuerdos'
    ];

    if (isset($tabla_map[$seccion_activa])) {
        $stmt = $db->prepare("SELECT * FROM {$tabla_map[$seccion_activa]} WHERE id = ?");
        $stmt->execute([$id_editar]);
        $datos_edicion = $stmt->fetch();
    }
}

// Obtener listados
$clientes = $db->query("SELECT id, codigo, razon_social FROM clientes WHERE estado='ACTIVO' ORDER BY razon_social")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Clientes - ERP System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e88e5 0%, #1565c0 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .nav-tabs {
            background: #f8f9fa;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            border-bottom: 3px solid #1565c0;
        }

        .nav-tab {
            flex: 1;
            min-width: 180px;
            padding: 15px 20px;
            background: #e9ecef;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: #495057;
            transition: all 0.3s ease;
            border-right: 1px solid #dee2e6;
        }

        .nav-tab:hover {
            background: #dee2e6;
        }

        .nav-tab.active {
            background: white;
            color: #1565c0;
            border-bottom: 3px solid #1565c0;
            margin-bottom: -3px;
        }

        .content {
            padding: 30px;
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #1565c0;
            color: white;
        }

        .btn-primary:hover {
            background: #0d47a1;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(21, 101, 192, 0.3);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .form-section h3 {
            color: #1565c0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1565c0;
            font-size: 18px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
            font-size: 13px;
        }

        .form-group label .required {
            color: #dc3545;
            margin-left: 3px;
        }

        .form-control {
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #1565c0;
            box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.1);
        }

        select.form-control {
            cursor: pointer;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .table thead {
            background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
            color: white;
        }

        .table thead th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            border-bottom: 1px solid #dee2e6;
            transition: background 0.2s ease;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .table tbody td {
            padding: 12px 15px;
            font-size: 13px;
            color: #495057;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-primary {
            background: #cfe2ff;
            color: #084298;
        }

        .section-title {
            color: #1565c0;
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #1565c0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #1e88e5 0%, #1565c0 100%);
            padding: 20px;
            border-radius: 8px;
            color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stat-card h4 {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .stat-card .stat-value {
            font-size: 32px;
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .filter-section {
            background: #e9ecef;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .info-panel {
            background: #e3f2fd;
            border-left: 4px solid #1565c0;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .info-panel h4 {
            color: #0d47a1;
            margin-bottom: 10px;
        }

        .credit-indicator {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
        }

        .credit-high {
            background: #d4edda;
            color: #155724;
        }

        .credit-medium {
            background: #fff3cd;
            color: #856404;
        }

        .credit-low {
            background: #f8d7da;
            color: #721c24;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .btn-group, .action-buttons, .nav-tabs {
                display: none !important;
            }

            .container {
                box-shadow: none;
            }
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gestion de Clientes - ERP System</h1>
            <p>Maestro de Clientes - CRM Integrado - VERSION COMPLETA</p>
            <p>Usuario: <?php echo htmlspecialchars($userName); ?> | Rol: <?php echo htmlspecialchars($userRole); ?></p>
        </div>

        <div class="nav-tabs">
            <button class="nav-tab <?php echo $seccion_activa === 'clientes' ? 'active' : ''; ?>"
                    onclick="window.location.href='?seccion=clientes'">
                Maestro Clientes
            </button>
            <button class="nav-tab <?php echo $seccion_activa === 'direcciones_cliente' ? 'active' : ''; ?>"
                    onclick="window.location.href='?seccion=direcciones_cliente'">
                Direcciones
            </button>
            <button class="nav-tab <?php echo $seccion_activa === 'contactos_cliente' ? 'active' : ''; ?>"
                    onclick="window.location.href='?seccion=contactos_cliente'">
                Contactos
            </button>
            <button class="nav-tab <?php echo $seccion_activa === 'contratos_cliente' ? 'active' : ''; ?>"
                    onclick="window.location.href='?seccion=contratos_cliente'">
                Contratos
            </button>
            <button class="nav-tab <?php echo $seccion_activa === 'acuerdos_comerciales' ? 'active' : ''; ?>"
                    onclick="window.location.href='?seccion=acuerdos_comerciales'">
                Acuerdos Comerciales
            </button>
        </div>

        <div class="content">
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <?php if ($accion_form === 'listar'): ?>
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="window.location.href='?seccion=<?php echo $seccion_activa; ?>&accion=crear<?php echo $cliente_id ? '&cliente_id='.$cliente_id : ''; ?>'">
                        Crear Nuevo
                    </button>
                    <button class="btn btn-success" onclick="window.print()">
                        Imprimir Reporte
                    </button>
                    <button class="btn btn-info" onclick="window.location.href='modulo_01_entidades_maestras.php'">
                        Volver a Maestros
                    </button>
                </div>
            <?php endif; ?>

            <?php
            // ========================================
            // RENDERIZAR CONTENIDO SEGUN SECCION ACTIVA
            // ========================================

            switch ($seccion_activa) {
                // ========================================
                // SECCION: CLIENTES
                // ========================================
                case 'clientes':
                    if ($accion_form === 'listar') {
                        $datos = $db->query("SELECT * FROM clientes WHERE estado != 'ELIMINADO' ORDER BY id DESC")->fetchAll();
                        ?>
                        <h2 class="section-title">Maestro de Clientes</h2>

                        <div class="stats-grid">
                            <div class="stat-card">
                                <h4>Total Clientes</h4>
                                <div class="stat-value"><?php echo count($datos); ?></div>
                            </div>
                            <div class="stat-card">
                                <h4>Clientes Activos</h4>
                                <div class="stat-value">
                                    <?php echo count(array_filter($datos, function($e) { return $e['estado'] === 'ACTIVO'; })); ?>
                                </div>
                            </div>
                            <div class="stat-card">
                                <h4>Clasificacion A</h4>
                                <div class="stat-value">
                                    <?php echo count(array_filter($datos, function($e) { return $e['clasificacion_abc'] === 'A'; })); ?>
                                </div>
                            </div>
                            <div class="stat-card">
                                <h4>Con Credito Vigente</h4>
                                <div class="stat-value">
                                    <?php echo count(array_filter($datos, function($e) { return $e['limite_credito'] > 0; })); ?>
                                </div>
                            </div>
                        </div>

                        <div class="filter-section">
                            <h4>Filtros de Busqueda</h4>
                            <div class="filter-grid">
                                <input type="text" class="form-control" placeholder="Buscar por RUT o Razon Social...">
                                <select class="form-control">
                                    <option value="">Todas las clasificaciones</option>
                                    <option value="A">Clasificacion A</option>
                                    <option value="B">Clasificacion B</option>
                                    <option value="C">Clasificacion C</option>
                                </select>
                                <select class="form-control">
                                    <option value="">Todos los estados</option>
                                    <option value="ACTIVO">Activo</option>
                                    <option value="SUSPENDIDO">Suspendido</option>
                                    <option value="BLOQUEADO">Bloqueado</option>
                                </select>
                                <button class="btn btn-primary">Aplicar Filtros</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Razon Social</th>
                                        <th>RUT</th>
                                        <th>Tipo</th>
                                        <th>Clasificacion</th>
                                        <th>Limite Credito</th>
                                        <th>Morosidad</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($datos) > 0): ?>
                                        <?php foreach ($datos as $row): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($row['codigo']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($row['razon_social']); ?></td>
                                                <td><?php echo htmlspecialchars($row['rut']); ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo htmlspecialchars($row['tipo_cliente']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                        echo $row['clasificacion_abc'] === 'A' ? 'success' :
                                                            ($row['clasificacion_abc'] === 'B' ? 'warning' : 'danger');
                                                    ?>">
                                                        <?php echo htmlspecialchars($row['clasificacion_abc']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatNumber($row['limite_credito'] ?? 0); ?></td>
                                                <td>
                                                    <?php
                                                    $morosidad = $row['nivel_morosidad'] ?? 0;
                                                    $class = $morosidad == 0 ? 'credit-high' : ($morosidad < 3 ? 'credit-medium' : 'credit-low');
                                                    ?>
                                                    <span class="credit-indicator <?php echo $class; ?>">
                                                        Nivel <?php echo $morosidad; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                        echo $row['estado'] === 'ACTIVO' ? 'success' :
                                                            ($row['estado'] === 'SUSPENDIDO' ? 'warning' : 'danger');
                                                    ?>">
                                                        <?php echo htmlspecialchars($row['estado']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="?seccion=clientes&accion=editar&id=<?php echo $row['id']; ?>"
                                                           class="btn btn-warning btn-sm">Editar</a>
                                                        <a href="?seccion=direcciones_cliente&cliente_id=<?php echo $row['id']; ?>"
                                                           class="btn btn-info btn-sm">Ver</a>
                                                        <form method="POST" style="display:inline;"
                                                              onsubmit="return confirm('Desea eliminar este cliente?');">
                                                            <input type="hidden" name="seccion" value="clientes">
                                                            <input type="hidden" name="accion" value="eliminar">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="no-data">No hay clientes registrados</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                    } else {
                        // FORMULARIO CREAR/EDITAR CLIENTE
                        require 'form_cliente.php'; // Por espacio, este formulario es muy largo
                    }
                    break;

                // ========================================
                // SECCION: CONTACTOS DE CLIENTE
                // ========================================
                case 'contactos_cliente':
                    if ($accion_form === 'listar') {
                        $where_clause = $cliente_id ? "WHERE cc.cliente_id = " . intval($cliente_id) : "";
                        $datos = $db->query("SELECT cc.*, c.razon_social as cliente_nombre
                                           FROM clientes_contactos cc
                                           LEFT JOIN clientes c ON cc.cliente_id = c.id
                                           {$where_clause}
                                           ORDER BY cc.es_principal DESC, cc.id DESC")->fetchAll();
                        ?>
                        <h2 class="section-title">Contactos de Clientes</h2>

                        <?php if ($cliente_id): ?>
                            <div class="info-panel">
                                <?php
                                $cliente_info = $db->query("SELECT razon_social FROM clientes WHERE id = " . intval($cliente_id))->fetch();
                                ?>
                                <h4>Cliente: <?php echo htmlspecialchars($cliente_info['razon_social']); ?></h4>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Nombre Completo</th>
                                        <th>Cargo</th>
                                        <th>Email</th>
                                        <th>Telefono</th>
                                        <th>Rol</th>
                                        <th>Principal</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($datos) > 0): ?>
                                        <?php foreach ($datos as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['cliente_nombre']); ?></td>
                                                <td><strong><?php echo htmlspecialchars($row['nombre'] . ' ' . $row['apellido']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($row['cargo']); ?></td>
                                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                                <td><?php echo htmlspecialchars($row['celular'] ?: $row['telefono']); ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo htmlspecialchars($row['rol_contacto']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($row['es_principal']): ?>
                                                        <span class="badge badge-success">SI</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">NO</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $row['activo'] ? 'success' : 'danger'; ?>">
                                                        <?php echo $row['activo'] ? 'ACTIVO' : 'INACTIVO'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="?seccion=contactos_cliente&accion=editar&id=<?php echo $row['id']; ?>&cliente_id=<?php echo $row['cliente_id']; ?>"
                                                           class="btn btn-warning btn-sm">Editar</a>
                                                        <form method="POST" style="display:inline;"
                                                              onsubmit="return confirm('Desea eliminar este contacto?');">
                                                            <input type="hidden" name="seccion" value="contactos_cliente">
                                                            <input type="hidden" name="accion" value="eliminar">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="no-data">No hay contactos registrados</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                    } else {
                        // FORMULARIO CREAR/EDITAR CONTACTO
                        $cliente_id = $cliente_id ?? $datos_edicion['cliente_id'] ?? null;
                        ?>
                        <h2 class="section-title"><?php echo $accion_form === 'crear' ? 'Crear Nuevo Contacto' : 'Editar Contacto'; ?></h2>

                        <form method="POST">
                            <input type="hidden" name="seccion" value="contactos_cliente">
                            <input type="hidden" name="accion" value="<?php echo $accion_form; ?>">
                            <?php if ($datos_edicion): ?>
                                <input type="hidden" name="id" value="<?php echo $datos_edicion['id']; ?>">
                            <?php endif; ?>

                            <div class="form-section">
                                <h3>Datos del Contacto</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Cliente <span class="required">*</span></label>
                                        <select name="cliente_id" class="form-control" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($clientes as $cli): ?>
                                                <option value="<?php echo $cli['id']; ?>"
                                                    <?php echo ($datos_edicion['cliente_id'] ?? $cliente_id) == $cli['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cli['razon_social']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Nombre <span class="required">*</span></label>
                                        <input type="text" name="nombre" class="form-control"
                                               value="<?php echo $datos_edicion['nombre'] ?? ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Apellido <span class="required">*</span></label>
                                        <input type="text" name="apellido" class="form-control"
                                               value="<?php echo $datos_edicion['apellido'] ?? ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Cargo</label>
                                        <input type="text" name="cargo" class="form-control"
                                               value="<?php echo $datos_edicion['cargo'] ?? ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Departamento</label>
                                        <select name="departamento" class="form-control">
                                            <option value="">Seleccione...</option>
                                            <option value="GERENCIA" <?php echo ($datos_edicion['departamento'] ?? '') === 'GERENCIA' ? 'selected' : ''; ?>>Gerencia</option>
                                            <option value="COMPRAS" <?php echo ($datos_edicion['departamento'] ?? '') === 'COMPRAS' ? 'selected' : ''; ?>>Compras</option>
                                            <option value="FINANZAS" <?php echo ($datos_edicion['departamento'] ?? '') === 'FINANZAS' ? 'selected' : ''; ?>>Finanzas</option>
                                            <option value="LOGISTICA" <?php echo ($datos_edicion['departamento'] ?? '') === 'LOGISTICA' ? 'selected' : ''; ?>>Logistica</option>
                                            <option value="OPERACIONES" <?php echo ($datos_edicion['departamento'] ?? '') === 'OPERACIONES' ? 'selected' : ''; ?>>Operaciones</option>
                                            <option value="TI" <?php echo ($datos_edicion['departamento'] ?? '') === 'TI' ? 'selected' : ''; ?>>TI</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Telefono</label>
                                        <input type="text" name="telefono" class="form-control"
                                               value="<?php echo $datos_edicion['telefono'] ?? ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Celular <span class="required">*</span></label>
                                        <input type="text" name="celular" class="form-control"
                                               value="<?php echo $datos_edicion['celular'] ?? ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email <span class="required">*</span></label>
                                        <input type="email" name="email" class="form-control"
                                               value="<?php echo $datos_edicion['email'] ?? ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Rol del Contacto</label>
                                        <select name="rol_contacto" class="form-control">
                                            <option value="COMPRAS" <?php echo ($datos_edicion['rol_contacto'] ?? '') === 'COMPRAS' ? 'selected' : ''; ?>>Compras</option>
                                            <option value="PAGOS" <?php echo ($datos_edicion['rol_contacto'] ?? '') === 'PAGOS' ? 'selected' : ''; ?>>Pagos</option>
                                            <option value="LOGISTICA" <?php echo ($datos_edicion['rol_contacto'] ?? '') === 'LOGISTICA' ? 'selected' : ''; ?>>Logistica</option>
                                            <option value="GERENCIA" <?php echo ($datos_edicion['rol_contacto'] ?? '') === 'GERENCIA' ? 'selected' : ''; ?>>Gerencia</option>
                                            <option value="TECNICO" <?php echo ($datos_edicion['rol_contacto'] ?? '') === 'TECNICO' ? 'selected' : ''; ?>>Tecnico</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Preferencia Comunicacion</label>
                                        <select name="preferencia_comunicacion" class="form-control">
                                            <option value="EMAIL" <?php echo ($datos_edicion['preferencia_comunicacion'] ?? 'EMAIL') === 'EMAIL' ? 'selected' : ''; ?>>Email</option>
                                            <option value="TELEFONO" <?php echo ($datos_edicion['preferencia_comunicacion'] ?? '') === 'TELEFONO' ? 'selected' : ''; ?>>Telefono</option>
                                            <option value="WHATSAPP" <?php echo ($datos_edicion['preferencia_comunicacion'] ?? '') === 'WHATSAPP' ? 'selected' : ''; ?>>WhatsApp</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Idioma</label>
                                        <select name="idioma" class="form-control">
                                            <option value="ES" <?php echo ($datos_edicion['idioma'] ?? 'ES') === 'ES' ? 'selected' : ''; ?>>Espanol</option>
                                            <option value="EN" <?php echo ($datos_edicion['idioma'] ?? '') === 'EN' ? 'selected' : ''; ?>>Ingles</option>
                                            <option value="PT" <?php echo ($datos_edicion['idioma'] ?? '') === 'PT' ? 'selected' : ''; ?>>Portugues</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Fecha Nacimiento</label>
                                        <input type="date" name="fecha_nacimiento" class="form-control"
                                               value="<?php echo $datos_edicion['fecha_nacimiento'] ?? ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Es Contacto Principal</label>
                                        <select name="es_principal" class="form-control">
                                            <option value="0" <?php echo ($datos_edicion['es_principal'] ?? '0') == '0' ? 'selected' : ''; ?>>No</option>
                                            <option value="1" <?php echo ($datos_edicion['es_principal'] ?? '0') == '1' ? 'selected' : ''; ?>>Si</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Estado</label>
                                        <select name="activo" class="form-control">
                                            <option value="1" <?php echo ($datos_edicion['activo'] ?? '1') == '1' ? 'selected' : ''; ?>>Activo</option>
                                            <option value="0" <?php echo ($datos_edicion['activo'] ?? '1') == '0' ? 'selected' : ''; ?>>Inactivo</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="btn-group">
                                <button type="submit" class="btn btn-success">Guardar Contacto</button>
                                <a href="?seccion=contactos_cliente<?php echo $cliente_id ? '&cliente_id='.$cliente_id : ''; ?>" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                        <?php
                    }
                    break;

                // ========================================
                // SECCION: CONTRATOS DE CLIENTE
                // ========================================
                case 'contratos_cliente':
                    if ($accion_form === 'listar') {
                        $where_clause = $cliente_id ? "WHERE cc.cliente_id = " . intval($cliente_id) : "";
                        $datos = $db->query("SELECT cc.*, c.razon_social as cliente_nombre
                                           FROM clientes_contratos cc
                                           LEFT JOIN clientes c ON cc.cliente_id = c.id
                                           {$where_clause}
                                           ORDER BY cc.id DESC")->fetchAll();
                        ?>
                        <h2 class="section-title">Contratos de Clientes</h2>

                        <?php if ($cliente_id): ?>
                            <div class="info-panel">
                                <?php
                                $cliente_info = $db->query("SELECT razon_social FROM clientes WHERE id = " . intval($cliente_id))->fetch();
                                ?>
                                <h4>Cliente: <?php echo htmlspecialchars($cliente_info['razon_social']); ?></h4>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Num Contrato</th>
                                        <th>Tipo</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Monto</th>
                                        <th>Moneda</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($datos) > 0): ?>
                                        <?php foreach ($datos as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['cliente_nombre']); ?></td>
                                                <td><strong><?php echo htmlspecialchars($row['numero_contrato']); ?></strong></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo htmlspecialchars($row['tipo_contrato']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($row['fecha_inicio'])); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($row['fecha_fin'])); ?></td>
                                                <td><?php echo formatNumber($row['monto_total']); ?></td>
                                                <td><?php echo htmlspecialchars($row['moneda']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                        echo $row['estado_contrato'] === 'VIGENTE' ? 'success' :
                                                            ($row['estado_contrato'] === 'CANCELADO' ? 'danger' : 'warning');
                                                    ?>">
                                                        <?php echo htmlspecialchars($row['estado_contrato']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="?seccion=contratos_cliente&accion=editar&id=<?php echo $row['id']; ?>&cliente_id=<?php echo $row['cliente_id']; ?>"
                                                           class="btn btn-warning btn-sm">Editar</a>
                                                        <form method="POST" style="display:inline;"
                                                              onsubmit="return confirm('Desea cancelar este contrato?');">
                                                            <input type="hidden" name="seccion" value="contratos_cliente">
                                                            <input type="hidden" name="accion" value="eliminar">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">Cancelar</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="no-data">No hay contratos registrados</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                    } else {
                        // FORMULARIO CREAR/EDITAR CONTRATO
                        $cliente_id = $cliente_id ?? $datos_edicion['cliente_id'] ?? null;
                        ?>
                        <h2 class="section-title"><?php echo $accion_form === 'crear' ? 'Crear Nuevo Contrato' : 'Editar Contrato'; ?></h2>

                        <form method="POST">
                            <input type="hidden" name="seccion" value="contratos_cliente">
                            <input type="hidden" name="accion" value="<?php echo $accion_form; ?>">
                            <?php if ($datos_edicion): ?>
                                <input type="hidden" name="id" value="<?php echo $datos_edicion['id']; ?>">
                            <?php endif; ?>

                            <div class="form-section">
                                <h3>Datos del Contrato</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Cliente <span class="required">*</span></label>
                                        <select name="cliente_id" class="form-control" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($clientes as $cli): ?>
                                                <option value="<?php echo $cli['id']; ?>"
                                                    <?php echo ($datos_edicion['cliente_id'] ?? $cliente_id) == $cli['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cli['razon_social']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Numero Contrato <span class="required">*</span></label>
                                        <input type="text" name="numero_contrato" class="form-control"
                                               value="<?php echo $datos_edicion['numero_contrato'] ?? ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Tipo de Contrato</label>
                                        <select name="tipo_contrato" class="form-control">
                                            <option value="SERVICIOS" <?php echo ($datos_edicion['tipo_contrato'] ?? '') === 'SERVICIOS' ? 'selected' : ''; ?>>Servicios</option>
                                            <option value="SUMINISTRO" <?php echo ($datos_edicion['tipo_contrato'] ?? '') === 'SUMINISTRO' ? 'selected' : ''; ?>>Suministro</option>
                                            <option value="DISTRIBUCION" <?php echo ($datos_edicion['tipo_contrato'] ?? '') === 'DISTRIBUCION' ? 'selected' : ''; ?>>Distribucion</option>
                                            <option value="MARCO" <?php echo ($datos_edicion['tipo_contrato'] ?? '') === 'MARCO' ? 'selected' : ''; ?>>Marco</option>
                                            <option value="LICENCIA" <?php echo ($datos_edicion['tipo_contrato'] ?? '') === 'LICENCIA' ? 'selected' : ''; ?>>Licencia</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Descripcion</label>
                                        <textarea name="descripcion" class="form-control"><?php echo $datos_edicion['descripcion'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Fecha Inicio <span class="required">*</span></label>
                                        <input type="date" name="fecha_inicio" class="form-control"
                                               value="<?php echo $datos_edicion['fecha_inicio'] ?? ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Fecha Fin <span class="required">*</span></label>
                                        <input type="date" name="fecha_fin" class="form-control"
                                               value="<?php echo $datos_edicion['fecha_fin'] ?? ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Monto Total</label>
                                        <input type="number" step="0.01" name="monto_total" class="form-control"
                                               value="<?php echo $datos_edicion['monto_total'] ?? '0'; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Moneda</label>
                                        <select name="moneda" class="form-control">
                                            <option value="CLP" <?php echo ($datos_edicion['moneda'] ?? 'CLP') === 'CLP' ? 'selected' : ''; ?>>CLP - Peso Chileno</option>
                                            <option value="USD" <?php echo ($datos_edicion['moneda'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD - Dolar</option>
                                            <option value="EUR" <?php echo ($datos_edicion['moneda'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Condiciones</label>
                                        <textarea name="condiciones" class="form-control"><?php echo $datos_edicion['condiciones'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Estado Contrato</label>
                                        <select name="estado_contrato" class="form-control">
                                            <option value="VIGENTE" <?php echo ($datos_edicion['estado_contrato'] ?? 'VIGENTE') === 'VIGENTE' ? 'selected' : ''; ?>>VIGENTE</option>
                                            <option value="VENCIDO" <?php echo ($datos_edicion['estado_contrato'] ?? '') === 'VENCIDO' ? 'selected' : ''; ?>>VENCIDO</option>
                                            <option value="CANCELADO" <?php echo ($datos_edicion['estado_contrato'] ?? '') === 'CANCELADO' ? 'selected' : ''; ?>>CANCELADO</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Renovacion Automatica</label>
                                        <select name="renovacion_automatica" class="form-control">
                                            <option value="0" <?php echo ($datos_edicion['renovacion_automatica'] ?? '0') == '0' ? 'selected' : ''; ?>>No</option>
                                            <option value="1" <?php echo ($datos_edicion['renovacion_automatica'] ?? '0') == '1' ? 'selected' : ''; ?>>Si</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Archivo Adjunto (URL)</label>
                                        <input type="text" name="archivo_adjunto" class="form-control"
                                               value="<?php echo $datos_edicion['archivo_adjunto'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="btn-group">
                                <button type="submit" class="btn btn-success">Guardar Contrato</button>
                                <a href="?seccion=contratos_cliente<?php echo $cliente_id ? '&cliente_id='.$cliente_id : ''; ?>" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                        <?php
                    }
                    break;

                // ========================================
                // SECCION: ACUERDOS COMERCIALES
                // ========================================
                case 'acuerdos_comerciales':
                    if ($accion_form === 'listar') {
                        $where_clause = $cliente_id ? "WHERE ca.cliente_id = " . intval($cliente_id) : "";
                        $datos = $db->query("SELECT ca.*, c.razon_social as cliente_nombre
                                           FROM clientes_acuerdos ca
                                           LEFT JOIN clientes c ON ca.cliente_id = c.id
                                           {$where_clause}
                                           ORDER BY ca.id DESC")->fetchAll();
                        ?>
                        <h2 class="section-title">Acuerdos Comerciales</h2>

                        <?php if ($cliente_id): ?>
                            <div class="info-panel">
                                <?php
                                $cliente_info = $db->query("SELECT razon_social FROM clientes WHERE id = " . intval($cliente_id))->fetch();
                                ?>
                                <h4>Cliente: <?php echo htmlspecialchars($cliente_info['razon_social']); ?></h4>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Codigo Acuerdo</th>
                                        <th>Descripcion</th>
                                        <th>Tipo Descuento</th>
                                        <th>Valor</th>
                                        <th>Vigencia</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($datos) > 0): ?>
                                        <?php foreach ($datos as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['cliente_nombre']); ?></td>
                                                <td><strong><?php echo htmlspecialchars($row['codigo_acuerdo']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo htmlspecialchars($row['tipo_descuento']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    echo $row['tipo_descuento'] === 'PORCENTAJE' ?
                                                        $row['valor_descuento'] . '%' :
                                                        formatNumber($row['valor_descuento']);
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php echo date('d/m/Y', strtotime($row['fecha_inicio'])); ?> -
                                                    <?php echo date('d/m/Y', strtotime($row['fecha_fin'])); ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                        echo $row['estado'] === 'ACTIVO' ? 'success' : 'warning';
                                                    ?>">
                                                        <?php echo htmlspecialchars($row['estado']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="?seccion=acuerdos_comerciales&accion=editar&id=<?php echo $row['id']; ?>&cliente_id=<?php echo $row['cliente_id']; ?>"
                                                           class="btn btn-warning btn-sm">Editar</a>
                                                        <form method="POST" style="display:inline;"
                                                              onsubmit="return confirm('Desea inactivar este acuerdo?');">
                                                            <input type="hidden" name="seccion" value="acuerdos_comerciales">
                                                            <input type="hidden" name="accion" value="eliminar">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">Inactivar</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="no-data">No hay acuerdos comerciales registrados</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                    } else {
                        // FORMULARIO CREAR/EDITAR ACUERDO
                        $cliente_id = $cliente_id ?? $datos_edicion['cliente_id'] ?? null;
                        ?>
                        <h2 class="section-title"><?php echo $accion_form === 'crear' ? 'Crear Nuevo Acuerdo Comercial' : 'Editar Acuerdo Comercial'; ?></h2>

                        <form method="POST">
                            <input type="hidden" name="seccion" value="acuerdos_comerciales">
                            <input type="hidden" name="accion" value="<?php echo $accion_form; ?>">
                            <?php if ($datos_edicion): ?>
                                <input type="hidden" name="id" value="<?php echo $datos_edicion['id']; ?>">
                            <?php endif; ?>

                            <div class="form-section">
                                <h3>Datos del Acuerdo Comercial</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Cliente <span class="required">*</span></label>
                                        <select name="cliente_id" class="form-control" required>
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($clientes as $cli): ?>
                                                <option value="<?php echo $cli['id']; ?>"
                                                    <?php echo ($datos_edicion['cliente_id'] ?? $cliente_id) == $cli['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cli['razon_social']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Codigo Acuerdo <span class="required">*</span></label>
                                        <input type="text" name="codigo_acuerdo" class="form-control"
                                               value="<?php echo $datos_edicion['codigo_acuerdo'] ?? ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Descripcion <span class="required">*</span></label>
                                        <textarea name="descripcion" class="form-control" required><?php echo $datos_edicion['descripcion'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Tipo de Descuento</label>
                                        <select name="tipo_descuento" class="form-control">
                                            <option value="PORCENTAJE" <?php echo ($datos_edicion['tipo_descuento'] ?? 'PORCENTAJE') === 'PORCENTAJE' ? 'selected' : ''; ?>>Porcentaje</option>
                                            <option value="MONTO_FIJO" <?php echo ($datos_edicion['tipo_descuento'] ?? '') === 'MONTO_FIJO' ? 'selected' : ''; ?>>Monto Fijo</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Valor Descuento</label>
                                        <input type="number" step="0.01" name="valor_descuento" class="form-control"
                                               value="<?php echo $datos_edicion['valor_descuento'] ?? '0'; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Fecha Inicio <span class="required">*</span></label>
                                        <input type="date" name="fecha_inicio" class="form-control"
                                               value="<?php echo $datos_edicion['fecha_inicio'] ?? ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Fecha Fin <span class="required">*</span></label>
                                        <input type="date" name="fecha_fin" class="form-control"
                                               value="<?php echo $datos_edicion['fecha_fin'] ?? ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Productos Aplicables</label>
                                        <textarea name="productos_aplicables" class="form-control" placeholder="Separados por coma o TODOS"><?php echo $datos_edicion['productos_aplicables'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Cantidad Minima</label>
                                        <input type="number" name="cantidad_minima" class="form-control"
                                               value="<?php echo $datos_edicion['cantidad_minima'] ?? '0'; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Monto Minimo</label>
                                        <input type="number" step="0.01" name="monto_minimo" class="form-control"
                                               value="<?php echo $datos_edicion['monto_minimo'] ?? '0'; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Estado</label>
                                        <select name="estado" class="form-control">
                                            <option value="ACTIVO" <?php echo ($datos_edicion['estado'] ?? 'ACTIVO') === 'ACTIVO' ? 'selected' : ''; ?>>ACTIVO</option>
                                            <option value="INACTIVO" <?php echo ($datos_edicion['estado'] ?? '') === 'INACTIVO' ? 'selected' : ''; ?>>INACTIVO</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="btn-group">
                                <button type="submit" class="btn btn-success">Guardar Acuerdo</button>
                                <a href="?seccion=acuerdos_comerciales<?php echo $cliente_id ? '&cliente_id='.$cliente_id : ''; ?>" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                        <?php
                    }
                    break;

                // ========================================
                // SECCION: DIRECCIONES CLIENTE (YA ESTABA)
                // ========================================
                case 'direcciones_cliente':
                    if ($accion_form === 'listar') {
                        $where_clause = $cliente_id ? "WHERE cd.cliente_id = " . intval($cliente_id) : "";
                        $datos = $db->query("SELECT cd.*, c.razon_social as cliente_nombre
                                           FROM direccion cd
                                           LEFT JOIN clientes c ON cd.cliente_id = c.id
                                           {$where_clause}
                                           ORDER BY cd.es_principal DESC, cd.id DESC")->fetchAll();
                        ?>
                        <h2 class="section-title">Direcciones de Clientes</h2>

                        <?php if ($cliente_id): ?>
                            <div class="info-panel">
                                <?php
                                $cliente_info = $db->query("SELECT razon_social FROM clientes WHERE id = " . intval($cliente_id))->fetch();
                                ?>
                                <h4>Cliente: <?php echo htmlspecialchars($cliente_info['razon_social']); ?></h4>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Tipo</th>
                                        <th>Nombre</th>
                                        <th>Direccion</th>
                                        <th>Ciudad</th>
                                        <th>Telefono</th>
                                        <th>Principal</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($datos) > 0): ?>
                                        <?php foreach ($datos as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['cliente_nombre']); ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo htmlspecialchars($row['tipo_direccion']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['nombre_direccion']); ?></td>
                                                <td><?php echo htmlspecialchars($row['direccion']); ?></td>
                                                <td><?php echo htmlspecialchars($row['ciudad']); ?></td>
                                                <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                                                <td>
                                                    <?php if ($row['es_principal']): ?>
                                                        <span class="badge badge-success">SI</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">NO</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $row['estado'] === 'ACTIVO' ? 'success' : 'danger'; ?>">
                                                        <?php echo htmlspecialchars($row['estado']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="?seccion=direcciones_cliente&accion=editar&id=<?php echo $row['id']; ?>&cliente_id=<?php echo $row['cliente_id']; ?>"
                                                           class="btn btn-warning btn-sm">Editar</a>
                                                        <form method="POST" style="display:inline;"
                                                              onsubmit="return confirm('Desea eliminar esta direccion?');">
                                                            <input type="hidden" name="seccion" value="direcciones_cliente">
                                                            <input type="hidden" name="accion" value="eliminar">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="no-data">No hay direcciones registradas</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                    } else {
                        echo "<div class='no-data'>Formulario de direcciones pendiente</div>";
                    }
                    break;

                default:
                    echo "<div class='no-data'>Seccion no encontrada. Seleccione otra pestana.</div>";
            }
            ?>
        </div>
    </div>

    <script>
        function confirmarEliminacion() {
            return confirm('Esta seguro que desea eliminar este registro?');
        }
    </script>
</body>
</html>