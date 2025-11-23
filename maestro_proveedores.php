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
        $codigo_proveedor = sanitize($_POST['codigo_proveedor'] ?? '');
        $nombre_legal = sanitize($_POST['nombre_legal'] ?? '');
        $identificacion_fiscal = sanitize($_POST['identificacion_fiscal'] ?? '');
        $clasificacion = sanitize($_POST['clasificacion'] ?? '');
        $nivel_riesgo = sanitize($_POST['nivel_riesgo'] ?? 'bajo');
        $evaluacion_anual = intval($_POST['evaluacion_anual'] ?? 0);
        $certificaciones = sanitize($_POST['certificaciones'] ?? '');
        $pais_id = intval($_POST['pais_id'] ?? 0);

        // Datos Comerciales
        $condicion_compra = sanitize($_POST['condicion_compra'] ?? '');
        $forma_pago_id = intval($_POST['forma_pago_id'] ?? 0);
        $plazo_pago = intval($_POST['plazo_pago'] ?? 0);
        $moneda_id = intval($_POST['moneda_id'] ?? 0);
        $lista_precios_proveedor = sanitize($_POST['lista_precios_proveedor'] ?? '');
        $convenios_marco = sanitize($_POST['convenios_marco'] ?? '');
        $contratos_activos = sanitize($_POST['contratos_activos'] ?? '');

        // Datos Logísticos
        $plazo_entrega_estimado = intval($_POST['plazo_entrega_estimado'] ?? 0);
        $lote_minimo_compra = floatval($_POST['lote_minimo_compra'] ?? 0);
        $politica_devolucion = sanitize($_POST['politica_devolucion'] ?? '');
        $incoterms = sanitize($_POST['incoterms'] ?? '');
        $lead_time = intval($_POST['lead_time'] ?? 0);
        $metodo_envio = sanitize($_POST['metodo_envio'] ?? '');

        // Datos Bancarios
        $banco_nombre = sanitize($_POST['banco_nombre'] ?? '');
        $cuenta_bancaria = sanitize($_POST['cuenta_bancaria'] ?? '');
        $tipo_cuenta = sanitize($_POST['tipo_cuenta'] ?? '');
        $swift = sanitize($_POST['swift'] ?? '');
        $beneficiario = sanitize($_POST['beneficiario'] ?? '');

        // Evaluación de Proveedores
        $calidad_entregas = intval($_POST['calidad_entregas'] ?? 0);
        $puntualidad = intval($_POST['puntualidad'] ?? 0);
        $cumplimiento_especificaciones = intval($_POST['cumplimiento_especificaciones'] ?? 0);
        $ranking_global = sanitize($_POST['ranking_global'] ?? '');
        $riesgo_operativo = sanitize($_POST['riesgo_operativo'] ?? 'bajo');

        // Contacto
        $contacto_nombre = sanitize($_POST['contacto_nombre'] ?? '');
        $contacto_cargo = sanitize($_POST['contacto_cargo'] ?? '');
        $contacto_email = sanitize($_POST['contacto_email'] ?? '');
        $contacto_telefono = sanitize($_POST['contacto_telefono'] ?? '');
        $contacto_movil = sanitize($_POST['contacto_movil'] ?? '');

        // Dirección
        $direccion_principal = sanitize($_POST['direccion_principal'] ?? '');
        $ciudad = sanitize($_POST['ciudad'] ?? '');
        $region = sanitize($_POST['region'] ?? '');
        $codigo_postal = sanitize($_POST['codigo_postal'] ?? '');

        // Estado
        $estado = sanitize($_POST['estado'] ?? 'activo');
        $observaciones = sanitize($_POST['observaciones'] ?? '');

        if ($accion === 'crear') {
            $stmt = $conn->prepare("INSERT INTO proveedores (
                codigo_proveedor, nombre_legal, identificacion_fiscal, clasificacion, nivel_riesgo,
                evaluacion_anual, certificaciones, pais_id,
                condicion_compra, forma_pago_id, plazo_pago, moneda_id, lista_precios_proveedor,
                convenios_marco, contratos_activos,
                plazo_entrega_estimado, lote_minimo_compra, politica_devolucion, incoterms, lead_time, metodo_envio,
                banco_nombre, cuenta_bancaria, tipo_cuenta, swift, beneficiario,
                calidad_entregas, puntualidad, cumplimiento_especificaciones, ranking_global, riesgo_operativo,
                contacto_nombre, contacto_cargo, contacto_email, contacto_telefono, contacto_movil,
                direccion_principal, ciudad, region, codigo_postal,
                estado, observaciones, fecha_registro
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            $stmt->bind_param("sssssisisiisissidsssisssiiissssssssssss",
                $codigo_proveedor, $nombre_legal, $identificacion_fiscal, $clasificacion, $nivel_riesgo,
                $evaluacion_anual, $certificaciones, $pais_id,
                $condicion_compra, $forma_pago_id, $plazo_pago, $moneda_id, $lista_precios_proveedor,
                $convenios_marco, $contratos_activos,
                $plazo_entrega_estimado, $lote_minimo_compra, $politica_devolucion, $incoterms, $lead_time, $metodo_envio,
                $banco_nombre, $cuenta_bancaria, $tipo_cuenta, $swift, $beneficiario,
                $calidad_entregas, $puntualidad, $cumplimiento_especificaciones, $ranking_global, $riesgo_operativo,
                $contacto_nombre, $contacto_cargo, $contacto_email, $contacto_telefono, $contacto_movil,
                $direccion_principal, $ciudad, $region, $codigo_postal,
                $estado, $observaciones
            );

            if ($stmt->execute()) {
                $proveedor_id = $conn->insert_id;
                logAuditoria('crear', 'proveedores', $proveedor_id, null, json_encode($_POST), 'Proveedor creado: ' . $nombre_legal);
                $mensaje = 'Proveedor creado exitosamente';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al crear proveedor: ' . $stmt->error;
                $tipo_mensaje = 'error';
            }
        } else {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE proveedores SET
                codigo_proveedor=?, nombre_legal=?, identificacion_fiscal=?, clasificacion=?, nivel_riesgo=?,
                evaluacion_anual=?, certificaciones=?, pais_id=?,
                condicion_compra=?, forma_pago_id=?, plazo_pago=?, moneda_id=?, lista_precios_proveedor=?,
                convenios_marco=?, contratos_activos=?,
                plazo_entrega_estimado=?, lote_minimo_compra=?, politica_devolucion=?, incoterms=?, lead_time=?, metodo_envio=?,
                banco_nombre=?, cuenta_bancaria=?, tipo_cuenta=?, swift=?, beneficiario=?,
                calidad_entregas=?, puntualidad=?, cumplimiento_especificaciones=?, ranking_global=?, riesgo_operativo=?,
                contacto_nombre=?, contacto_cargo=?, contacto_email=?, contacto_telefono=?, contacto_movil=?,
                direccion_principal=?, ciudad=?, region=?, codigo_postal=?,
                estado=?, observaciones=?, fecha_modificacion=NOW()
                WHERE id=?");

            $stmt->bind_param("sssssisisiisissidsssisssiiisssssssssssi",
                $codigo_proveedor, $nombre_legal, $identificacion_fiscal, $clasificacion, $nivel_riesgo,
                $evaluacion_anual, $certificaciones, $pais_id,
                $condicion_compra, $forma_pago_id, $plazo_pago, $moneda_id, $lista_precios_proveedor,
                $convenios_marco, $contratos_activos,
                $plazo_entrega_estimado, $lote_minimo_compra, $politica_devolucion, $incoterms, $lead_time, $metodo_envio,
                $banco_nombre, $cuenta_bancaria, $tipo_cuenta, $swift, $beneficiario,
                $calidad_entregas, $puntualidad, $cumplimiento_especificaciones, $ranking_global, $riesgo_operativo,
                $contacto_nombre, $contacto_cargo, $contacto_email, $contacto_telefono, $contacto_movil,
                $direccion_principal, $ciudad, $region, $codigo_postal,
                $estado, $observaciones, $id
            );

            if ($stmt->execute()) {
                logAuditoria('editar', 'proveedores', $id, null, json_encode($_POST), 'Proveedor actualizado: ' . $nombre_legal);
                $mensaje = 'Proveedor actualizado exitosamente';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al actualizar proveedor: ' . $stmt->error;
                $tipo_mensaje = 'error';
            }
        }
    } elseif ($accion === 'eliminar') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE proveedores SET estado='eliminado', fecha_eliminacion=NOW() WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            logAuditoria('eliminar', 'proveedores', $id, null, null, 'Proveedor eliminado');
            $mensaje = 'Proveedor eliminado exitosamente';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al eliminar proveedor';
            $tipo_mensaje = 'error';
        }
    }
}

// Obtener datos para el formulario
$paises = $conn->query("SELECT * FROM paises ORDER BY nombre");
$monedas = $conn->query("SELECT * FROM monedas ORDER BY codigo");
$formas_pago = $conn->query("SELECT * FROM formas_pago ORDER BY nombre");

// Filtros de búsqueda
$buscar = sanitize($_GET['buscar'] ?? '');
$filtro_clasificacion = sanitize($_GET['filtro_clasificacion'] ?? '');
$filtro_estado = sanitize($_GET['filtro_estado'] ?? '');
$filtro_ranking = sanitize($_GET['filtro_ranking'] ?? '');

// Consulta de proveedores
$where = ["estado != 'eliminado'"];
$params = [];
$types = '';

if ($buscar) {
    $where[] = "(codigo_proveedor LIKE ? OR nombre_legal LIKE ? OR identificacion_fiscal LIKE ?)";
    $buscar_param = "%$buscar%";
    $params[] = &$buscar_param;
    $params[] = &$buscar_param;
    $params[] = &$buscar_param;
    $types .= 'sss';
}

if ($filtro_clasificacion) {
    $where[] = "clasificacion = ?";
    $params[] = &$filtro_clasificacion;
    $types .= 's';
}

if ($filtro_estado) {
    $where[] = "estado = ?";
    $params[] = &$filtro_estado;
    $types .= 's';
}

if ($filtro_ranking) {
    $where[] = "ranking_global = ?";
    $params[] = &$filtro_ranking;
    $types .= 's';
}

$sql = "SELECT * FROM proveedores WHERE " . implode(" AND ", $where) . " ORDER BY nombre_legal";
$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$proveedores = $stmt->get_result();

// Modo edición
$editar = null;
if (isset($_GET['editar'])) {
    $id = intval($_GET['editar']);
    $stmt = $conn->prepare("SELECT * FROM proveedores WHERE id=?");
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
    <title>Maestro de Proveedores - CONECTA ERP</title>
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            background: #f5576c;
            color: white;
        }

        .btn-primary:hover {
            background: #e04558;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(245, 87, 108, 0.3);
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
            border-color: #f5576c;
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
            border-color: #f5576c;
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
            color: #f5576c;
            border-bottom-color: #f5576c;
        }

        .tab:hover {
            color: #f5576c;
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
            border-left: 4px solid #f5576c;
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

        .info-box {
            background: #fff5f7;
            border: 1px solid #feb2b2;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #742a2a;
        }

        .rating-stars {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .star {
            color: #fbbf24;
            font-size: 18px;
        }

        .star.empty {
            color: #e2e8f0;
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
        <h1>MAESTRO DE PROVEEDORES</h1>
        <p>Gestion Integral de Proveedores - Compras / CxP / Calidad</p>
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
            $stats_total = $conn->query("SELECT COUNT(*) as total FROM proveedores WHERE estado != 'eliminado'")->fetch_assoc()['total'];
            $stats_activos = $conn->query("SELECT COUNT(*) as total FROM proveedores WHERE estado = 'activo'")->fetch_assoc()['total'];
            $stats_calidad_a = $conn->query("SELECT COUNT(*) as total FROM proveedores WHERE ranking_global = 'A'")->fetch_assoc()['total'];
            $stats_riesgo_alto = $conn->query("SELECT COUNT(*) as total FROM proveedores WHERE nivel_riesgo = 'alto'")->fetch_assoc()['total'];
            ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Proveedores</h3>
                    <div class="value"><?php echo number_format($stats_total); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Activos</h3>
                    <div class="value"><?php echo number_format($stats_activos); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Clase A Calidad</h3>
                    <div class="value"><?php echo number_format($stats_calidad_a); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Riesgo Alto</h3>
                    <div class="value"><?php echo number_format($stats_riesgo_alto); ?></div>
                </div>
            </div>

            <!-- BARRA DE HERRAMIENTAS -->
            <form method="GET" class="toolbar">
                <a href="?nuevo=1" class="btn btn-primary">+ Nuevo Proveedor</a>

                <div class="search-box">
                    <input type="text" name="buscar" placeholder="Buscar por codigo, nombre o identificacion..." value="<?php echo htmlspecialchars($buscar); ?>">
                </div>

                <select name="filtro_clasificacion">
                    <option value="">Todas las clasificaciones</option>
                    <option value="local" <?php echo $filtro_clasificacion === 'local' ? 'selected' : ''; ?>>Local</option>
                    <option value="internacional" <?php echo $filtro_clasificacion === 'internacional' ? 'selected' : ''; ?>>Internacional</option>
                    <option value="servicios" <?php echo $filtro_clasificacion === 'servicios' ? 'selected' : ''; ?>>Servicios</option>
                    <option value="insumos" <?php echo $filtro_clasificacion === 'insumos' ? 'selected' : ''; ?>>Insumos</option>
                    <option value="transporte" <?php echo $filtro_clasificacion === 'transporte' ? 'selected' : ''; ?>>Transporte</option>
                </select>

                <select name="filtro_ranking">
                    <option value="">Todos los rankings</option>
                    <option value="A" <?php echo $filtro_ranking === 'A' ? 'selected' : ''; ?>>Ranking A</option>
                    <option value="B" <?php echo $filtro_ranking === 'B' ? 'selected' : ''; ?>>Ranking B</option>
                    <option value="C" <?php echo $filtro_ranking === 'C' ? 'selected' : ''; ?>>Ranking C</option>
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

            <!-- TABLA DE PROVEEDORES -->
            <div class="card">
                <div class="table-container">
                    <?php if ($proveedores->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Proveedor</th>
                                    <th>Identificacion</th>
                                    <th>Clasificacion</th>
                                    <th>Ranking</th>
                                    <th>Evaluacion</th>
                                    <th>Riesgo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($proveedor = $proveedores->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($proveedor['codigo_proveedor']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($proveedor['nombre_legal']); ?></td>
                                        <td><?php echo htmlspecialchars($proveedor['identificacion_fiscal']); ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo ucfirst($proveedor['clasificacion']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php
                                                echo $proveedor['ranking_global'] === 'A' ? 'success' :
                                                    ($proveedor['ranking_global'] === 'B' ? 'warning' : 'secondary');
                                            ?>">
                                                Clase <?php echo $proveedor['ranking_global']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="rating-stars">
                                                <?php
                                                $rating = floor($proveedor['evaluacion_anual'] / 20);
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo $i <= $rating ? '<span class="star">★</span>' : '<span class="star empty">★</span>';
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php
                                                echo $proveedor['nivel_riesgo'] === 'bajo' ? 'success' :
                                                    ($proveedor['nivel_riesgo'] === 'medio' ? 'warning' : 'danger');
                                            ?>">
                                                <?php echo ucfirst($proveedor['nivel_riesgo']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php
                                                echo $proveedor['estado'] === 'activo' ? 'success' :
                                                    ($proveedor['estado'] === 'suspendido' ? 'danger' : 'warning');
                                            ?>">
                                                <?php echo ucfirst($proveedor['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?editar=<?php echo $proveedor['id']; ?>" class="btn btn-info btn-sm">Editar</a>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Esta seguro de eliminar este proveedor?');">
                                                    <input type="hidden" name="accion" value="eliminar">
                                                    <input type="hidden" name="id" value="<?php echo $proveedor['id']; ?>">
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
                            <h3>No se encontraron proveedores</h3>
                            <p>Intenta ajustar los filtros o crea un nuevo proveedor</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- FORMULARIO DE PROVEEDOR -->
            <div class="card">
                <form method="POST" style="padding: 30px;">
                    <input type="hidden" name="accion" value="<?php echo $editar ? 'editar' : 'crear'; ?>">
                    <?php if ($editar): ?>
                        <input type="hidden" name="id" value="<?php echo $editar['id']; ?>">
                    <?php endif; ?>

                    <div class="info-box">
                        Complete todos los campos marcados con asterisco como obligatorios
                    </div>

                    <!-- TABS -->
                    <div class="tabs">
                        <button type="button" class="tab active" onclick="showTab(0)">Datos Generales</button>
                        <button type="button" class="tab" onclick="showTab(1)">Datos Comerciales</button>
                        <button type="button" class="tab" onclick="showTab(2)">Datos Logisticos</button>
                        <button type="button" class="tab" onclick="showTab(3)">Datos Bancarios</button>
                        <button type="button" class="tab" onclick="showTab(4)">Evaluacion</button>
                        <button type="button" class="tab" onclick="showTab(5)">Contacto y Direccion</button>
                    </div>

                    <!-- TAB 1: DATOS GENERALES -->
                    <div class="tab-content active">
                        <div class="form-title">DATOS GENERALES</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Codigo Proveedor <span class="required">*</span></label>
                                <input type="text" name="codigo_proveedor" required
                                       value="<?php echo $editar['codigo_proveedor'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Nombre Legal <span class="required">*</span></label>
                                <input type="text" name="nombre_legal" required
                                       value="<?php echo $editar['nombre_legal'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Identificacion Fiscal <span class="required">*</span></label>
                                <input type="text" name="identificacion_fiscal" required
                                       value="<?php echo $editar['identificacion_fiscal'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Clasificacion <span class="required">*</span></label>
                                <select name="clasificacion" required>
                                    <option value="">Seleccione...</option>
                                    <option value="local" <?php echo ($editar['clasificacion'] ?? '') === 'local' ? 'selected' : ''; ?>>Local</option>
                                    <option value="internacional" <?php echo ($editar['clasificacion'] ?? '') === 'internacional' ? 'selected' : ''; ?>>Internacional</option>
                                    <option value="servicios" <?php echo ($editar['clasificacion'] ?? '') === 'servicios' ? 'selected' : ''; ?>>Servicios</option>
                                    <option value="insumos" <?php echo ($editar['clasificacion'] ?? '') === 'insumos' ? 'selected' : ''; ?>>Insumos</option>
                                    <option value="ti" <?php echo ($editar['clasificacion'] ?? '') === 'ti' ? 'selected' : ''; ?>>TI</option>
                                    <option value="transporte" <?php echo ($editar['clasificacion'] ?? '') === 'transporte' ? 'selected' : ''; ?>>Transporte</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Nivel de Riesgo</label>
                                <select name="nivel_riesgo">
                                    <option value="bajo" <?php echo ($editar['nivel_riesgo'] ?? 'bajo') === 'bajo' ? 'selected' : ''; ?>>Bajo</option>
                                    <option value="medio" <?php echo ($editar['nivel_riesgo'] ?? '') === 'medio' ? 'selected' : ''; ?>>Medio</option>
                                    <option value="alto" <?php echo ($editar['nivel_riesgo'] ?? '') === 'alto' ? 'selected' : ''; ?>>Alto</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Evaluacion Anual (0-100)</label>
                                <input type="number" min="0" max="100" name="evaluacion_anual"
                                       value="<?php echo $editar['evaluacion_anual'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Pais</label>
                                <select name="pais_id">
                                    <option value="0">Seleccione...</option>
                                    <?php
                                    $paises->data_seek(0);
                                    while ($pais = $paises->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $pais['id']; ?>"
                                                <?php echo ($editar['pais_id'] ?? 0) == $pais['id'] ? 'selected' : ''; ?>>
                                            <?php echo $pais['nombre']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Certificaciones Obligatorias</label>
                            <textarea name="certificaciones"><?php echo $editar['certificaciones'] ?? ''; ?></textarea>
                        </div>
                    </div>

                    <!-- TAB 2: DATOS COMERCIALES -->
                    <div class="tab-content">
                        <div class="form-title">DATOS COMERCIALES</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Condicion de Compra</label>
                                <input type="text" name="condicion_compra"
                                       value="<?php echo $editar['condicion_compra'] ?? ''; ?>">
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
                                <label>Plazo de Pago (dias)</label>
                                <input type="number" name="plazo_pago"
                                       value="<?php echo $editar['plazo_pago'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Moneda Principal</label>
                                <select name="moneda_id">
                                    <option value="0">Seleccione...</option>
                                    <?php
                                    $monedas->data_seek(0);
                                    while ($moneda = $monedas->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $moneda['id']; ?>"
                                                <?php echo ($editar['moneda_id'] ?? 0) == $moneda['id'] ? 'selected' : ''; ?>>
                                            <?php echo $moneda['codigo']; ?> - <?php echo $moneda['nombre']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Lista de Precios del Proveedor</label>
                                <input type="text" name="lista_precios_proveedor"
                                       value="<?php echo $editar['lista_precios_proveedor'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Convenios Marco</label>
                                <textarea name="convenios_marco"><?php echo $editar['convenios_marco'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Contratos Activos</label>
                                <textarea name="contratos_activos"><?php echo $editar['contratos_activos'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: DATOS LOGISTICOS -->
                    <div class="tab-content">
                        <div class="form-title">DATOS LOGISTICOS</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Plazo Entrega Estimado (dias)</label>
                                <input type="number" name="plazo_entrega_estimado"
                                       value="<?php echo $editar['plazo_entrega_estimado'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Lote Minimo de Compra</label>
                                <input type="number" step="0.01" name="lote_minimo_compra"
                                       value="<?php echo $editar['lote_minimo_compra'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Lead Time (dias)</label>
                                <input type="number" name="lead_time"
                                       value="<?php echo $editar['lead_time'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Incoterms</label>
                                <select name="incoterms">
                                    <option value="">Seleccione...</option>
                                    <option value="EXW" <?php echo ($editar['incoterms'] ?? '') === 'EXW' ? 'selected' : ''; ?>>EXW - Ex Works</option>
                                    <option value="FCA" <?php echo ($editar['incoterms'] ?? '') === 'FCA' ? 'selected' : ''; ?>>FCA - Free Carrier</option>
                                    <option value="CPT" <?php echo ($editar['incoterms'] ?? '') === 'CPT' ? 'selected' : ''; ?>>CPT - Carriage Paid To</option>
                                    <option value="CIP" <?php echo ($editar['incoterms'] ?? '') === 'CIP' ? 'selected' : ''; ?>>CIP - Carriage Insurance Paid</option>
                                    <option value="DAP" <?php echo ($editar['incoterms'] ?? '') === 'DAP' ? 'selected' : ''; ?>>DAP - Delivered At Place</option>
                                    <option value="DPU" <?php echo ($editar['incoterms'] ?? '') === 'DPU' ? 'selected' : ''; ?>>DPU - Delivered Place Unloaded</option>
                                    <option value="DDP" <?php echo ($editar['incoterms'] ?? '') === 'DDP' ? 'selected' : ''; ?>>DDP - Delivered Duty Paid</option>
                                    <option value="FAS" <?php echo ($editar['incoterms'] ?? '') === 'FAS' ? 'selected' : ''; ?>>FAS - Free Alongside Ship</option>
                                    <option value="FOB" <?php echo ($editar['incoterms'] ?? '') === 'FOB' ? 'selected' : ''; ?>>FOB - Free On Board</option>
                                    <option value="CFR" <?php echo ($editar['incoterms'] ?? '') === 'CFR' ? 'selected' : ''; ?>>CFR - Cost and Freight</option>
                                    <option value="CIF" <?php echo ($editar['incoterms'] ?? '') === 'CIF' ? 'selected' : ''; ?>>CIF - Cost Insurance Freight</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Metodo de Envio Habitual</label>
                                <select name="metodo_envio">
                                    <option value="">Seleccione...</option>
                                    <option value="terrestre" <?php echo ($editar['metodo_envio'] ?? '') === 'terrestre' ? 'selected' : ''; ?>>Terrestre</option>
                                    <option value="aereo" <?php echo ($editar['metodo_envio'] ?? '') === 'aereo' ? 'selected' : ''; ?>>Aereo</option>
                                    <option value="maritimo" <?php echo ($editar['metodo_envio'] ?? '') === 'maritimo' ? 'selected' : ''; ?>>Maritimo</option>
                                    <option value="multimodal" <?php echo ($editar['metodo_envio'] ?? '') === 'multimodal' ? 'selected' : ''; ?>>Multimodal</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Politica de Devolucion</label>
                            <textarea name="politica_devolucion"><?php echo $editar['politica_devolucion'] ?? ''; ?></textarea>
                        </div>
                    </div>

                    <!-- TAB 4: DATOS BANCARIOS -->
                    <div class="tab-content">
                        <div class="form-title">DATOS BANCARIOS</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nombre del Banco</label>
                                <input type="text" name="banco_nombre"
                                       value="<?php echo $editar['banco_nombre'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Cuenta Bancaria</label>
                                <input type="text" name="cuenta_bancaria"
                                       value="<?php echo $editar['cuenta_bancaria'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Tipo de Cuenta</label>
                                <select name="tipo_cuenta">
                                    <option value="">Seleccione...</option>
                                    <option value="corriente" <?php echo ($editar['tipo_cuenta'] ?? '') === 'corriente' ? 'selected' : ''; ?>>Cuenta Corriente</option>
                                    <option value="ahorro" <?php echo ($editar['tipo_cuenta'] ?? '') === 'ahorro' ? 'selected' : ''; ?>>Cuenta de Ahorro</option>
                                    <option value="vista" <?php echo ($editar['tipo_cuenta'] ?? '') === 'vista' ? 'selected' : ''; ?>>Cuenta Vista</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>SWIFT / BIC</label>
                                <input type="text" name="swift"
                                       value="<?php echo $editar['swift'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Beneficiario</label>
                                <input type="text" name="beneficiario"
                                       value="<?php echo $editar['beneficiario'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: EVALUACION -->
                    <div class="tab-content">
                        <div class="form-title">EVALUACION DE PROVEEDORES</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Calidad de Entregas (0-100)</label>
                                <input type="number" min="0" max="100" name="calidad_entregas"
                                       value="<?php echo $editar['calidad_entregas'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Puntualidad (0-100)</label>
                                <input type="number" min="0" max="100" name="puntualidad"
                                       value="<?php echo $editar['puntualidad'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Cumplimiento Especificaciones (0-100)</label>
                                <input type="number" min="0" max="100" name="cumplimiento_especificaciones"
                                       value="<?php echo $editar['cumplimiento_especificaciones'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Ranking Global</label>
                                <select name="ranking_global">
                                    <option value="">Sin ranking</option>
                                    <option value="A" <?php echo ($editar['ranking_global'] ?? '') === 'A' ? 'selected' : ''; ?>>A - Excelente</option>
                                    <option value="B" <?php echo ($editar['ranking_global'] ?? '') === 'B' ? 'selected' : ''; ?>>B - Bueno</option>
                                    <option value="C" <?php echo ($editar['ranking_global'] ?? '') === 'C' ? 'selected' : ''; ?>>C - Regular</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Riesgo Operativo</label>
                                <select name="riesgo_operativo">
                                    <option value="bajo" <?php echo ($editar['riesgo_operativo'] ?? 'bajo') === 'bajo' ? 'selected' : ''; ?>>Bajo</option>
                                    <option value="medio" <?php echo ($editar['riesgo_operativo'] ?? '') === 'medio' ? 'selected' : ''; ?>>Medio</option>
                                    <option value="alto" <?php echo ($editar['riesgo_operativo'] ?? '') === 'alto' ? 'selected' : ''; ?>>Alto</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 6: CONTACTO Y DIRECCION -->
                    <div class="tab-content">
                        <div class="form-title">CONTACTO PRINCIPAL</div>
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
                                <label>Movil</label>
                                <input type="tel" name="contacto_movil"
                                       value="<?php echo $editar['contacto_movil'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="form-title">DIRECCION PRINCIPAL</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Direccion</label>
                                <textarea name="direccion_principal"><?php echo $editar['direccion_principal'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Ciudad</label>
                                <input type="text" name="ciudad"
                                       value="<?php echo $editar['ciudad'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Region / Estado</label>
                                <input type="text" name="region"
                                       value="<?php echo $editar['region'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Codigo Postal</label>
                                <input type="text" name="codigo_postal"
                                       value="<?php echo $editar['codigo_postal'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="form-title">ESTADO Y OBSERVACIONES</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Estado del Proveedor</label>
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
                            <?php echo $editar ? 'Actualizar Proveedor' : 'Crear Proveedor'; ?>
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
