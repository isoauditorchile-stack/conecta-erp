<?php
session_start();
require_once '/home/conectae/public_html/includes/config.php';

// Verificar sesión
requireLogin();

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear' || $accion === 'editar') {
        // Datos Generales
        $codigo_cliente = sanitize($_POST['codigo_cliente'] ?? '');
        $nombre_razon_social = sanitize($_POST['nombre_razon_social'] ?? '');
        $rut = sanitize($_POST['rut'] ?? '');
        $tipo_cliente = sanitize($_POST['tipo_cliente'] ?? '');
        $clasificacion_abc = sanitize($_POST['clasificacion_abc'] ?? '');
        $segmento_comercial = sanitize($_POST['segmento_comercial'] ?? '');
        $zona_geografica = sanitize($_POST['zona_geografica'] ?? '');
        $ejecutivo_asignado = sanitize($_POST['ejecutivo_asignado'] ?? '');
        $idioma_id = intval($_POST['idioma_id'] ?? 0);
        $canal_venta = sanitize($_POST['canal_venta'] ?? '');

        // Datos Comerciales
        $condicion_pago_id = intval($_POST['condicion_pago_id'] ?? 0);
        $forma_pago_id = intval($_POST['forma_pago_id'] ?? 0);
        $descuento_comercial = floatval($_POST['descuento_comercial'] ?? 0);
        $lista_precios_id = intval($_POST['lista_precios_id'] ?? 0);
        $plazo_entrega = intval($_POST['plazo_entrega'] ?? 0);
        $politica_devoluciones = sanitize($_POST['politica_devoluciones'] ?? '');
        $acuerdos_comerciales = sanitize($_POST['acuerdos_comerciales'] ?? '');
        $contratos_vigentes = sanitize($_POST['contratos_vigentes'] ?? '');

        // Datos de Crédito
        $limite_credito = floatval($_POST['limite_credito'] ?? 0);
        $riesgo_crediticio = intval($_POST['riesgo_crediticio'] ?? 0);
        $garantias = sanitize($_POST['garantias'] ?? '');
        $documentacion_financiera = sanitize($_POST['documentacion_financiera'] ?? '');
        $nivel_morosidad = sanitize($_POST['nivel_morosidad'] ?? 'bajo');
        $historial_pagos = sanitize($_POST['historial_pagos'] ?? '');
        $alertas_credito = sanitize($_POST['alertas_credito'] ?? 'ninguna');

        // Datos Fiscales
        $tipo_contribuyente = sanitize($_POST['tipo_contribuyente'] ?? '');
        $exenciones = sanitize($_POST['exenciones'] ?? '');
        $impuestos_asociados = sanitize($_POST['impuestos_asociados'] ?? '');
        $certificados_tributarios = sanitize($_POST['certificados_tributarios'] ?? '');
        $facturacion_electronica = isset($_POST['facturacion_electronica']) ? 1 : 0;
        $reglas_retencion = sanitize($_POST['reglas_retencion'] ?? '');

        // Dirección Principal
        $direccion_comercial = sanitize($_POST['direccion_comercial'] ?? '');
        $direccion_despacho = sanitize($_POST['direccion_despacho'] ?? '');
        $direccion_facturacion = sanitize($_POST['direccion_facturacion'] ?? '');
        $latitud = sanitize($_POST['latitud'] ?? '');
        $longitud = sanitize($_POST['longitud'] ?? '');

        // Contacto Principal
        $contacto_nombre = sanitize($_POST['contacto_nombre'] ?? '');
        $contacto_cargo = sanitize($_POST['contacto_cargo'] ?? '');
        $contacto_email = sanitize($_POST['contacto_email'] ?? '');
        $contacto_telefono = sanitize($_POST['contacto_telefono'] ?? '');
        $contacto_rol = sanitize($_POST['contacto_rol'] ?? '');
        $contacto_preferencias = sanitize($_POST['contacto_preferencias'] ?? '');

        // Estado y observaciones
        $estado = sanitize($_POST['estado'] ?? 'activo');
        $observaciones = sanitize($_POST['observaciones'] ?? '');

        if ($accion === 'crear') {
            $stmt = $conn->prepare("INSERT INTO clientes (
                codigo_cliente, nombre_razon_social, rut, tipo_cliente, clasificacion_abc,
                segmento_comercial, zona_geografica, ejecutivo_asignado, idioma_id, canal_venta,
                condicion_pago_id, forma_pago_id, descuento_comercial, lista_precios_id, plazo_entrega,
                politica_devoluciones, acuerdos_comerciales, contratos_vigentes,
                limite_credito, riesgo_crediticio, garantias, documentacion_financiera,
                nivel_morosidad, historial_pagos, alertas_credito,
                tipo_contribuyente, exenciones, impuestos_asociados, certificados_tributarios,
                facturacion_electronica, reglas_retencion,
                direccion_comercial, direccion_despacho, direccion_facturacion, latitud, longitud,
                contacto_nombre, contacto_cargo, contacto_email, contacto_telefono, contacto_rol, contacto_preferencias,
                estado, observaciones, fecha_registro
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            $stmt->bind_param("ssssssssissiidisssidssssssssssissssssssssssss",
                $codigo_cliente, $nombre_razon_social, $rut, $tipo_cliente, $clasificacion_abc,
                $segmento_comercial, $zona_geografica, $ejecutivo_asignado, $idioma_id, $canal_venta,
                $condicion_pago_id, $forma_pago_id, $descuento_comercial, $lista_precios_id, $plazo_entrega,
                $politica_devoluciones, $acuerdos_comerciales, $contratos_vigentes,
                $limite_credito, $riesgo_crediticio, $garantias, $documentacion_financiera,
                $nivel_morosidad, $historial_pagos, $alertas_credito,
                $tipo_contribuyente, $exenciones, $impuestos_asociados, $certificados_tributarios,
                $facturacion_electronica, $reglas_retencion,
                $direccion_comercial, $direccion_despacho, $direccion_facturacion, $latitud, $longitud,
                $contacto_nombre, $contacto_cargo, $contacto_email, $contacto_telefono, $contacto_rol, $contacto_preferencias,
                $estado, $observaciones
            );

            if ($stmt->execute()) {
                $cliente_id = $conn->insert_id;
                logAuditoria('crear', 'clientes', $cliente_id, null, json_encode($_POST), 'Cliente creado: ' . $nombre_razon_social);
                $mensaje = 'Cliente creado exitosamente';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al crear cliente: ' . $stmt->error;
                $tipo_mensaje = 'error';
            }
        } else {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE clientes SET
                codigo_cliente=?, nombre_razon_social=?, rut=?, tipo_cliente=?, clasificacion_abc=?,
                segmento_comercial=?, zona_geografica=?, ejecutivo_asignado=?, idioma_id=?, canal_venta=?,
                condicion_pago_id=?, forma_pago_id=?, descuento_comercial=?, lista_precios_id=?, plazo_entrega=?,
                politica_devoluciones=?, acuerdos_comerciales=?, contratos_vigentes=?,
                limite_credito=?, riesgo_crediticio=?, garantias=?, documentacion_financiera=?,
                nivel_morosidad=?, historial_pagos=?, alertas_credito=?,
                tipo_contribuyente=?, exenciones=?, impuestos_asociados=?, certificados_tributarios=?,
                facturacion_electronica=?, reglas_retencion=?,
                direccion_comercial=?, direccion_despacho=?, direccion_facturacion=?, latitud=?, longitud=?,
                contacto_nombre=?, contacto_cargo=?, contacto_email=?, contacto_telefono=?, contacto_rol=?, contacto_preferencias=?,
                estado=?, observaciones=?, fecha_modificacion=NOW()
                WHERE id=?");

            $stmt->bind_param("ssssssssissiidisssidssssssssssisssssssssssssi",
                $codigo_cliente, $nombre_razon_social, $rut, $tipo_cliente, $clasificacion_abc,
                $segmento_comercial, $zona_geografica, $ejecutivo_asignado, $idioma_id, $canal_venta,
                $condicion_pago_id, $forma_pago_id, $descuento_comercial, $lista_precios_id, $plazo_entrega,
                $politica_devoluciones, $acuerdos_comerciales, $contratos_vigentes,
                $limite_credito, $riesgo_crediticio, $garantias, $documentacion_financiera,
                $nivel_morosidad, $historial_pagos, $alertas_credito,
                $tipo_contribuyente, $exenciones, $impuestos_asociados, $certificados_tributarios,
                $facturacion_electronica, $reglas_retencion,
                $direccion_comercial, $direccion_despacho, $direccion_facturacion, $latitud, $longitud,
                $contacto_nombre, $contacto_cargo, $contacto_email, $contacto_telefono, $contacto_rol, $contacto_preferencias,
                $estado, $observaciones, $id
            );

            if ($stmt->execute()) {
                logAuditoria('editar', 'clientes', $id, null, json_encode($_POST), 'Cliente actualizado: ' . $nombre_razon_social);
                $mensaje = 'Cliente actualizado exitosamente';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al actualizar cliente: ' . $stmt->error;
                $tipo_mensaje = 'error';
            }
        }
    } elseif ($accion === 'eliminar') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE clientes SET estado='eliminado', fecha_eliminacion=NOW() WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            logAuditoria('eliminar', 'clientes', $id, null, null, 'Cliente eliminado');
            $mensaje = 'Cliente eliminado exitosamente';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al eliminar cliente';
            $tipo_mensaje = 'error';
        }
    }
}

// Obtener datos para el formulario
$idiomas = $conn->query("SELECT * FROM idiomas ORDER BY nombre");
$condiciones_pago = $conn->query("SELECT * FROM condiciones_pago ORDER BY nombre");
$formas_pago = $conn->query("SELECT * FROM formas_pago ORDER BY nombre");
$listas_precios = $conn->query("SELECT * FROM listas_precios WHERE estado='activo' ORDER BY nombre");

// Filtros de búsqueda
$buscar = sanitize($_GET['buscar'] ?? '');
$filtro_tipo = sanitize($_GET['filtro_tipo'] ?? '');
$filtro_estado = sanitize($_GET['filtro_estado'] ?? '');
$filtro_clasificacion = sanitize($_GET['filtro_clasificacion'] ?? '');

// Consulta de clientes
$where = ["estado != 'eliminado'"];
$params = [];
$types = '';

if ($buscar) {
    $where[] = "(codigo_cliente LIKE ? OR nombre_razon_social LIKE ? OR rut LIKE ?)";
    $buscar_param = "%$buscar%";
    $params[] = &$buscar_param;
    $params[] = &$buscar_param;
    $params[] = &$buscar_param;
    $types .= 'sss';
}

if ($filtro_tipo) {
    $where[] = "tipo_cliente = ?";
    $params[] = &$filtro_tipo;
    $types .= 's';
}

if ($filtro_estado) {
    $where[] = "estado = ?";
    $params[] = &$filtro_estado;
    $types .= 's';
}

if ($filtro_clasificacion) {
    $where[] = "clasificacion_abc = ?";
    $params[] = &$filtro_clasificacion;
    $types .= 's';
}

$sql = "SELECT * FROM clientes WHERE " . implode(" AND ", $where) . " ORDER BY nombre_razon_social";
$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$clientes = $stmt->get_result();

// Modo edición
$editar = null;
if (isset($_GET['editar'])) {
    $id = intval($_GET['editar']);
    $stmt = $conn->prepare("SELECT * FROM clientes WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }

        .toolbar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-success:hover {
            background: #38a169;
        }

        .btn-danger {
            background: #f56565;
            color: white;
        }

        .btn-danger:hover {
            background: #e53e3e;
        }

        .btn-secondary {
            background: #718096;
            color: white;
        }

        .btn-secondary:hover {
            background: #4a5568;
        }

        .btn-info {
            background: #4299e1;
            color: white;
        }

        .btn-info:hover {
            background: #3182ce;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            transition: border 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-group {
            display: flex;
            gap: 10px;
        }

        select {
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f7fafc;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }

        tr:hover {
            background: #f7fafc;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .badge-success {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge-warning {
            background: #feebc8;
            color: #744210;
        }

        .badge-danger {
            background: #fed7d7;
            color: #742a2a;
        }

        .badge-info {
            background: #bee3f8;
            color: #2c5282;
        }

        .badge-secondary {
            background: #e2e8f0;
            color: #2d3748;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .form-group label .required {
            color: #f56565;
            margin-left: 3px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            transition: border 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .tabs {
            display: flex;
            gap: 5px;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #718096;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab:hover {
            color: #667eea;
            background: #f7fafc;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #48bb78;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #f56565;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
        }

        .stat-card h3 {
            font-size: 14px;
            color: #718096;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .info-box {
            background: #ebf8ff;
            border: 1px solid #90cdf4;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #2c5282;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media print {
            .toolbar, .btn, .action-buttons {
                display: none !important;
            }

            body {
                background: white;
            }

            .container {
                padding: 0;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
            }

            .container {
                padding: 15px;
            }

            .toolbar {
                flex-direction: column;
            }

            .search-box {
                width: 100%;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>MAESTRO DE CLIENTES</h1>
        <p>Gestion Integral de Clientes - CRM / CxC / Ventas</p>
    </div>

    <div class="container">
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($_GET['nuevo']) && !isset($_GET['editar'])): ?>
            <!-- ESTADISTICAS -->
            <?php
            $stats_total = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE estado != 'eliminado'")->fetch_assoc()['total'];
            $stats_activos = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE estado = 'activo'")->fetch_assoc()['total'];
            $stats_inactivos = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE estado = 'inactivo'")->fetch_assoc()['total'];
            $stats_bloqueados = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE alertas_credito != 'ninguna'")->fetch_assoc()['total'];
            ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Clientes</h3>
                    <div class="value"><?php echo number_format($stats_total); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Activos</h3>
                    <div class="value"><?php echo number_format($stats_activos); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Inactivos</h3>
                    <div class="value"><?php echo number_format($stats_inactivos); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Con Alertas</h3>
                    <div class="value"><?php echo number_format($stats_bloqueados); ?></div>
                </div>
            </div>

            <!-- BARRA DE HERRAMIENTAS -->
            <form method="GET" class="toolbar">
                <a href="?nuevo=1" class="btn btn-primary">+ Nuevo Cliente</a>

                <div class="search-box">
                    <input type="text" name="buscar" placeholder="Buscar por codigo, nombre o RUT..." value="<?php echo htmlspecialchars($buscar); ?>">
                </div>

                <select name="filtro_tipo">
                    <option value="">Todos los tipos</option>
                    <option value="minorista" <?php echo $filtro_tipo === 'minorista' ? 'selected' : ''; ?>>Minorista</option>
                    <option value="mayorista" <?php echo $filtro_tipo === 'mayorista' ? 'selected' : ''; ?>>Mayorista</option>
                    <option value="distribuidor" <?php echo $filtro_tipo === 'distribuidor' ? 'selected' : ''; ?>>Distribuidor</option>
                    <option value="exportacion" <?php echo $filtro_tipo === 'exportacion' ? 'selected' : ''; ?>>Exportacion</option>
                </select>

                <select name="filtro_clasificacion">
                    <option value="">Todas las clasificaciones</option>
                    <option value="A" <?php echo $filtro_clasificacion === 'A' ? 'selected' : ''; ?>>Clase A</option>
                    <option value="B" <?php echo $filtro_clasificacion === 'B' ? 'selected' : ''; ?>>Clase B</option>
                    <option value="C" <?php echo $filtro_clasificacion === 'C' ? 'selected' : ''; ?>>Clase C</option>
                </select>

                <select name="filtro_estado">
                    <option value="">Todos los estados</option>
                    <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                    <option value="inactivo" <?php echo $filtro_estado === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                    <option value="suspendido" <?php echo $filtro_estado === 'suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                </select>

                <button type="submit" class="btn btn-secondary">Buscar</button>
                <button type="button" onclick="window.print()" class="btn btn-info">Imprimir</button>
            </form>

            <!-- TABLA DE CLIENTES -->
            <div class="card">
                <div class="table-container">
                    <?php if ($clientes->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Cliente</th>
                                    <th>RUT</th>
                                    <th>Tipo</th>
                                    <th>Clasificacion</th>
                                    <th>Limite Credito</th>
                                    <th>Estado</th>
                                    <th>Ejecutivo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($cliente = $clientes->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($cliente['codigo_cliente']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($cliente['nombre_razon_social']); ?></td>
                                        <td><?php echo formatRUT($cliente['rut']); ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo ucfirst($cliente['tipo_cliente']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php
                                                echo $cliente['clasificacion_abc'] === 'A' ? 'success' :
                                                    ($cliente['clasificacion_abc'] === 'B' ? 'warning' : 'secondary');
                                            ?>">
                                                Clase <?php echo $cliente['clasificacion_abc']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatCurrency($cliente['limite_credito']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php
                                                echo $cliente['estado'] === 'activo' ? 'success' :
                                                    ($cliente['estado'] === 'suspendido' ? 'danger' : 'warning');
                                            ?>">
                                                <?php echo ucfirst($cliente['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($cliente['ejecutivo_asignado']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?editar=<?php echo $cliente['id']; ?>" class="btn btn-info btn-sm">Editar</a>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Esta seguro de eliminar este cliente?');">
                                                    <input type="hidden" name="accion" value="eliminar">
                                                    <input type="hidden" name="id" value="<?php echo $cliente['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <h3>No se encontraron clientes</h3>
                            <p>Intenta ajustar los filtros o crea un nuevo cliente</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- FORMULARIO DE CLIENTE -->
            <div class="card">
                <form method="POST" style="padding: 30px;">
                    <input type="hidden" name="accion" value="<?php echo $editar ? 'editar' : 'crear'; ?>">
                    <?php if ($editar): ?>
                        <input type="hidden" name="id" value="<?php echo $editar['id']; ?>">
                    <?php endif; ?>

                    <div class="info-box">
                        Complete todos los campos marcados con asterisco (*) como obligatorios
                    </div>

                    <!-- TABS -->
                    <div class="tabs">
                        <button type="button" class="tab active" onclick="showTab(0)">Datos Generales</button>
                        <button type="button" class="tab" onclick="showTab(1)">Datos Comerciales</button>
                        <button type="button" class="tab" onclick="showTab(2)">Datos de Credito</button>
                        <button type="button" class="tab" onclick="showTab(3)">Datos Fiscales</button>
                        <button type="button" class="tab" onclick="showTab(4)">Direcciones</button>
                        <button type="button" class="tab" onclick="showTab(5)">Contactos</button>
                        <button type="button" class="tab" onclick="showTab(6)">Estado y Observaciones</button>
                    </div>

                    <!-- TAB 1: DATOS GENERALES -->
                    <div class="tab-content active">
                        <div class="form-title">DATOS GENERALES</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Codigo Cliente <span class="required">*</span></label>
                                <input type="text" name="codigo_cliente" required
                                       value="<?php echo $editar['codigo_cliente'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Nombre / Razon Social <span class="required">*</span></label>
                                <input type="text" name="nombre_razon_social" required
                                       value="<?php echo $editar['nombre_razon_social'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>RUT / NIF <span class="required">*</span></label>
                                <input type="text" name="rut" required
                                       value="<?php echo $editar['rut'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Tipo de Cliente <span class="required">*</span></label>
                                <select name="tipo_cliente" required>
                                    <option value="">Seleccione...</option>
                                    <option value="minorista" <?php echo ($editar['tipo_cliente'] ?? '') === 'minorista' ? 'selected' : ''; ?>>Minorista</option>
                                    <option value="mayorista" <?php echo ($editar['tipo_cliente'] ?? '') === 'mayorista' ? 'selected' : ''; ?>>Mayorista</option>
                                    <option value="distribuidor" <?php echo ($editar['tipo_cliente'] ?? '') === 'distribuidor' ? 'selected' : ''; ?>>Distribuidor</option>
                                    <option value="exportacion" <?php echo ($editar['tipo_cliente'] ?? '') === 'exportacion' ? 'selected' : ''; ?>>Exportacion</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Clasificacion ABC</label>
                                <select name="clasificacion_abc">
                                    <option value="">Seleccione...</option>
                                    <option value="A" <?php echo ($editar['clasificacion_abc'] ?? '') === 'A' ? 'selected' : ''; ?>>Clase A - Alto Volumen</option>
                                    <option value="B" <?php echo ($editar['clasificacion_abc'] ?? '') === 'B' ? 'selected' : ''; ?>>Clase B - Medio Volumen</option>
                                    <option value="C" <?php echo ($editar['clasificacion_abc'] ?? '') === 'C' ? 'selected' : ''; ?>>Clase C - Bajo Volumen</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Segmento Comercial</label>
                                <select name="segmento_comercial">
                                    <option value="">Seleccione...</option>
                                    <option value="corporativo" <?php echo ($editar['segmento_comercial'] ?? '') === 'corporativo' ? 'selected' : ''; ?>>Corporativo</option>
                                    <option value="pyme" <?php echo ($editar['segmento_comercial'] ?? '') === 'pyme' ? 'selected' : ''; ?>>PYME</option>
                                    <option value="empresarial" <?php echo ($editar['segmento_comercial'] ?? '') === 'empresarial' ? 'selected' : ''; ?>>Empresarial</option>
                                    <option value="retail" <?php echo ($editar['segmento_comercial'] ?? '') === 'retail' ? 'selected' : ''; ?>>Retail</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Zona Geografica</label>
                                <input type="text" name="zona_geografica"
                                       value="<?php echo $editar['zona_geografica'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Ejecutivo Asignado</label>
                                <input type="text" name="ejecutivo_asignado"
                                       value="<?php echo $editar['ejecutivo_asignado'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Idioma</label>
                                <select name="idioma_id">
                                    <option value="0">Seleccione...</option>
                                    <?php
                                    $idiomas->data_seek(0);
                                    while ($idioma = $idiomas->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $idioma['id']; ?>"
                                                <?php echo ($editar['idioma_id'] ?? 0) == $idioma['id'] ? 'selected' : ''; ?>>
                                            <?php echo $idioma['nombre']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Canal de Venta</label>
                                <select name="canal_venta">
                                    <option value="">Seleccione...</option>
                                    <option value="directo" <?php echo ($editar['canal_venta'] ?? '') === 'directo' ? 'selected' : ''; ?>>Venta Directa</option>
                                    <option value="distribuidor" <?php echo ($editar['canal_venta'] ?? '') === 'distribuidor' ? 'selected' : ''; ?>>Distribuidor</option>
                                    <option value="online" <?php echo ($editar['canal_venta'] ?? '') === 'online' ? 'selected' : ''; ?>>Online</option>
                                    <option value="telefono" <?php echo ($editar['canal_venta'] ?? '') === 'telefono' ? 'selected' : ''; ?>>Telefono</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: DATOS COMERCIALES -->
                    <div class="tab-content">
                        <div class="form-title">DATOS COMERCIALES</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Condicion de Pago</label>
                                <select name="condicion_pago_id">
                                    <option value="0">Seleccione...</option>
                                    <?php
                                    $condiciones_pago->data_seek(0);
                                    while ($condicion = $condiciones_pago->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $condicion['id']; ?>"
                                                <?php echo ($editar['condicion_pago_id'] ?? 0) == $condicion['id'] ? 'selected' : ''; ?>>
                                            <?php echo $condicion['nombre']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Forma de Pago</label>
                                <select name="forma_pago_id">
                                    <option value="0">Seleccione...</option>
                                    <?php
                                    $formas_pago->data_seek(0);
                                    while ($forma = $formas_pago->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $forma['id']; ?>"
                                                <?php echo ($editar['forma_pago_id'] ?? 0) == $forma['id'] ? 'selected' : ''; ?>>
                                            <?php echo $forma['nombre']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Descuento Comercial (%)</label>
                                <input type="number" step="0.01" name="descuento_comercial"
                                       value="<?php echo $editar['descuento_comercial'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Lista de Precios</label>
                                <select name="lista_precios_id">
                                    <option value="0">Seleccione...</option>
                                    <?php
                                    $listas_precios->data_seek(0);
                                    while ($lista = $listas_precios->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $lista['id']; ?>"
                                                <?php echo ($editar['lista_precios_id'] ?? 0) == $lista['id'] ? 'selected' : ''; ?>>
                                            <?php echo $lista['nombre']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Plazo de Entrega (dias)</label>
                                <input type="number" name="plazo_entrega"
                                       value="<?php echo $editar['plazo_entrega'] ?? '0'; ?>">
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Politica de Devoluciones</label>
                                <textarea name="politica_devoluciones"><?php echo $editar['politica_devoluciones'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Acuerdos Comerciales</label>
                                <textarea name="acuerdos_comerciales"><?php echo $editar['acuerdos_comerciales'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Contratos Vigentes</label>
                                <textarea name="contratos_vigentes"><?php echo $editar['contratos_vigentes'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: DATOS DE CREDITO -->
                    <div class="tab-content">
                        <div class="form-title">DATOS DE CREDITO</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Limite de Credito</label>
                                <input type="number" step="0.01" name="limite_credito"
                                       value="<?php echo $editar['limite_credito'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Riesgo Crediticio (Score 0-100)</label>
                                <input type="number" min="0" max="100" name="riesgo_crediticio"
                                       value="<?php echo $editar['riesgo_crediticio'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Nivel de Morosidad</label>
                                <select name="nivel_morosidad">
                                    <option value="bajo" <?php echo ($editar['nivel_morosidad'] ?? 'bajo') === 'bajo' ? 'selected' : ''; ?>>Bajo</option>
                                    <option value="medio" <?php echo ($editar['nivel_morosidad'] ?? '') === 'medio' ? 'selected' : ''; ?>>Medio</option>
                                    <option value="alto" <?php echo ($editar['nivel_morosidad'] ?? '') === 'alto' ? 'selected' : ''; ?>>Alto</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Alertas de Credito</label>
                                <select name="alertas_credito">
                                    <option value="ninguna" <?php echo ($editar['alertas_credito'] ?? 'ninguna') === 'ninguna' ? 'selected' : ''; ?>>Ninguna</option>
                                    <option value="revision" <?php echo ($editar['alertas_credito'] ?? '') === 'revision' ? 'selected' : ''; ?>>En Revision</option>
                                    <option value="limite_superado" <?php echo ($editar['alertas_credito'] ?? '') === 'limite_superado' ? 'selected' : ''; ?>>Limite Superado</option>
                                    <option value="suspendido" <?php echo ($editar['alertas_credito'] ?? '') === 'suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Garantias</label>
                                <textarea name="garantias"><?php echo $editar['garantias'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Documentacion Financiera</label>
                                <textarea name="documentacion_financiera"><?php echo $editar['documentacion_financiera'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Historial de Pagos</label>
                                <textarea name="historial_pagos"><?php echo $editar['historial_pagos'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: DATOS FISCALES -->
                    <div class="tab-content">
                        <div class="form-title">DATOS FISCALES / TRIBUTARIOS</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Tipo de Contribuyente</label>
                                <select name="tipo_contribuyente">
                                    <option value="">Seleccione...</option>
                                    <option value="primera_categoria" <?php echo ($editar['tipo_contribuyente'] ?? '') === 'primera_categoria' ? 'selected' : ''; ?>>Primera Categoria</option>
                                    <option value="segunda_categoria" <?php echo ($editar['tipo_contribuyente'] ?? '') === 'segunda_categoria' ? 'selected' : ''; ?>>Segunda Categoria</option>
                                    <option value="exento" <?php echo ($editar['tipo_contribuyente'] ?? '') === 'exento' ? 'selected' : ''; ?>>Exento</option>
                                    <option value="no_contribuyente" <?php echo ($editar['tipo_contribuyente'] ?? '') === 'no_contribuyente' ? 'selected' : ''; ?>>No Contribuyente</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Facturacion Electronica (DTE)</label>
                                <div class="checkbox-group">
                                    <input type="checkbox" name="facturacion_electronica" value="1"
                                           <?php echo ($editar['facturacion_electronica'] ?? 0) ? 'checked' : ''; ?>>
                                    <span>Habilitado</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Exenciones</label>
                                <textarea name="exenciones"><?php echo $editar['exenciones'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Impuestos Asociados</label>
                                <textarea name="impuestos_asociados"><?php echo $editar['impuestos_asociados'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Certificados Tributarios</label>
                                <textarea name="certificados_tributarios"><?php echo $editar['certificados_tributarios'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Reglas de Retencion</label>
                                <textarea name="reglas_retencion"><?php echo $editar['reglas_retencion'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: DIRECCIONES -->
                    <div class="tab-content">
                        <div class="form-title">DIRECCIONES Y SUCURSALES</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Direccion Comercial</label>
                                <textarea name="direccion_comercial"><?php echo $editar['direccion_comercial'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Direccion de Despacho</label>
                                <textarea name="direccion_despacho"><?php echo $editar['direccion_despacho'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Direccion de Facturacion</label>
                                <textarea name="direccion_facturacion"><?php echo $editar['direccion_facturacion'] ?? ''; ?></textarea>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Latitud (GPS)</label>
                                <input type="text" name="latitud" placeholder="-33.4489"
                                       value="<?php echo $editar['latitud'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Longitud (GPS)</label>
                                <input type="text" name="longitud" placeholder="-70.6693"
                                       value="<?php echo $editar['longitud'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 6: CONTACTOS -->
                    <div class="tab-content">
                        <div class="form-title">CONTACTOS</div>
                        <div class="info-box">
                            Ingrese los datos del contacto principal. Podra agregar contactos adicionales despues de guardar el cliente.
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nombre Contacto</label>
                                <input type="text" name="contacto_nombre"
                                       value="<?php echo $editar['contacto_nombre'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Cargo</label>
                                <input type="text" name="contacto_cargo"
                                       value="<?php echo $editar['contacto_cargo'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="contacto_email"
                                       value="<?php echo $editar['contacto_email'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Telefono</label>
                                <input type="tel" name="contacto_telefono"
                                       value="<?php echo $editar['contacto_telefono'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Rol</label>
                                <select name="contacto_rol">
                                    <option value="">Seleccione...</option>
                                    <option value="compras" <?php echo ($editar['contacto_rol'] ?? '') === 'compras' ? 'selected' : ''; ?>>Compras</option>
                                    <option value="pagos" <?php echo ($editar['contacto_rol'] ?? '') === 'pagos' ? 'selected' : ''; ?>>Pagos</option>
                                    <option value="logistica" <?php echo ($editar['contacto_rol'] ?? '') === 'logistica' ? 'selected' : ''; ?>>Logistica</option>
                                    <option value="gerencia" <?php echo ($editar['contacto_rol'] ?? '') === 'gerencia' ? 'selected' : ''; ?>>Gerencia</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Preferencias de Comunicacion</label>
                                <select name="contacto_preferencias">
                                    <option value="email" <?php echo ($editar['contacto_preferencias'] ?? 'email') === 'email' ? 'selected' : ''; ?>>Email</option>
                                    <option value="telefono" <?php echo ($editar['contacto_preferencias'] ?? '') === 'telefono' ? 'selected' : ''; ?>>Telefono</option>
                                    <option value="whatsapp" <?php echo ($editar['contacto_preferencias'] ?? '') === 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
                                    <option value="ambos" <?php echo ($editar['contacto_preferencias'] ?? '') === 'ambos' ? 'selected' : ''; ?>>Ambos</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 7: ESTADO Y OBSERVACIONES -->
                    <div class="tab-content">
                        <div class="form-title">ESTADO Y OBSERVACIONES</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Estado del Cliente</label>
                                <select name="estado">
                                    <option value="activo" <?php echo ($editar['estado'] ?? 'activo') === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="inactivo" <?php echo ($editar['estado'] ?? '') === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                    <option value="suspendido" <?php echo ($editar['estado'] ?? '') === 'suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Observaciones Generales</label>
                            <textarea name="observaciones" rows="6"><?php echo $editar['observaciones'] ?? ''; ?></textarea>
                        </div>
                    </div>

                    <!-- BOTONES DE ACCION -->
                    <div class="form-actions">
                        <a href="?" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">
                            <?php echo $editar ? 'Actualizar Cliente' : 'Crear Cliente'; ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function showTab(index) {
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach((tab, i) => {
                if (i === index) {
                    tab.classList.add('active');
                    contents[i].classList.add('active');
                } else {
                    tab.classList.remove('active');
                    contents[i].classList.remove('active');
                }
            });
        }
    </script>
</body>
</html>
