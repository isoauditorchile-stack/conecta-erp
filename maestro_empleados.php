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
        // Datos Personales
        $rut = sanitize($_POST['rut'] ?? '');
        $nombre_completo = sanitize($_POST['nombre_completo'] ?? '');
        $fecha_nacimiento = sanitize($_POST['fecha_nacimiento'] ?? '');
        $nacionalidad = sanitize($_POST['nacionalidad'] ?? '');
        $estado_civil = sanitize($_POST['estado_civil'] ?? '');
        $genero = sanitize($_POST['genero'] ?? '');
        $email_personal = sanitize($_POST['email_personal'] ?? '');
        $telefono_personal = sanitize($_POST['telefono_personal'] ?? '');
        $telefono_movil = sanitize($_POST['telefono_movil'] ?? '');
        $contacto_emergencia_nombre = sanitize($_POST['contacto_emergencia_nombre'] ?? '');
        $contacto_emergencia_telefono = sanitize($_POST['contacto_emergencia_telefono'] ?? '');
        $contacto_emergencia_relacion = sanitize($_POST['contacto_emergencia_relacion'] ?? '');

        // Datos Laborales
        $codigo_empleado = sanitize($_POST['codigo_empleado'] ?? '');
        $cargo = sanitize($_POST['cargo'] ?? '');
        $departamento = sanitize($_POST['departamento'] ?? '');
        $gerencia = sanitize($_POST['gerencia'] ?? '');
        $centro_costo_id = intval($_POST['centro_costo_id'] ?? 0);
        $fecha_ingreso = sanitize($_POST['fecha_ingreso'] ?? '');
        $tipo_contrato = sanitize($_POST['tipo_contrato'] ?? '');
        $jornada = sanitize($_POST['jornada'] ?? '');
        $supervisor_directo = sanitize($_POST['supervisor_directo'] ?? '');
        $rol_erp = sanitize($_POST['rol_erp'] ?? '');
        $email_corporativo = sanitize($_POST['email_corporativo'] ?? '');
        $extension = sanitize($_POST['extension'] ?? '');

        // Datos de Pago
        $banco_id = sanitize($_POST['banco_id'] ?? '');
        $cuenta_bancaria = sanitize($_POST['cuenta_bancaria'] ?? '');
        $tipo_cuenta = sanitize($_POST['tipo_cuenta'] ?? '');
        $tipo_pago = sanitize($_POST['tipo_pago'] ?? '');
        $moneda_salario_id = intval($_POST['moneda_salario_id'] ?? 0);
        $salario_base = floatval($_POST['salario_base'] ?? 0);
        $afp = sanitize($_POST['afp'] ?? '');
        $prevision_salud = sanitize($_POST['prevision_salud'] ?? '');
        $apv = sanitize($_POST['apv'] ?? '');

        // Dirección
        $direccion = sanitize($_POST['direccion'] ?? '');
        $ciudad = sanitize($_POST['ciudad'] ?? '');
        $region = sanitize($_POST['region'] ?? '');
        $codigo_postal = sanitize($_POST['codigo_postal'] ?? '');
        $pais_id = intval($_POST['pais_id'] ?? 0);

        // Estado
        $estado = sanitize($_POST['estado'] ?? 'activo');
        $observaciones = sanitize($_POST['observaciones'] ?? '');

        if ($accion === 'crear') {
            $stmt = $conn->prepare("INSERT INTO empleados (
                rut, nombre_completo, fecha_nacimiento, nacionalidad, estado_civil, genero,
                email_personal, telefono_personal, telefono_movil,
                contacto_emergencia_nombre, contacto_emergencia_telefono, contacto_emergencia_relacion,
                codigo_empleado, cargo, departamento, gerencia, centro_costo_id, fecha_ingreso,
                tipo_contrato, jornada, supervisor_directo, rol_erp, email_corporativo, extension,
                banco_id, cuenta_bancaria, tipo_cuenta, tipo_pago, moneda_salario_id, salario_base,
                afp, prevision_salud, apv,
                direccion, ciudad, region, codigo_postal, pais_id,
                estado, observaciones, fecha_registro
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            $stmt->bind_param("ssssssssssssssssississsssssssidssssssiss",
                $rut, $nombre_completo, $fecha_nacimiento, $nacionalidad, $estado_civil, $genero,
                $email_personal, $telefono_personal, $telefono_movil,
                $contacto_emergencia_nombre, $contacto_emergencia_telefono, $contacto_emergencia_relacion,
                $codigo_empleado, $cargo, $departamento, $gerencia, $centro_costo_id, $fecha_ingreso,
                $tipo_contrato, $jornada, $supervisor_directo, $rol_erp, $email_corporativo, $extension,
                $banco_id, $cuenta_bancaria, $tipo_cuenta, $tipo_pago, $moneda_salario_id, $salario_base,
                $afp, $prevision_salud, $apv,
                $direccion, $ciudad, $region, $codigo_postal, $pais_id,
                $estado, $observaciones
            );

            if ($stmt->execute()) {
                $empleado_id = $conn->insert_id;
                logAuditoria('crear', 'empleados', $empleado_id, null, json_encode($_POST), 'Empleado creado: ' . $nombre_completo);
                $mensaje = 'Empleado creado exitosamente';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al crear empleado: ' . $stmt->error;
                $tipo_mensaje = 'error';
            }
        } else {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE empleados SET
                rut=?, nombre_completo=?, fecha_nacimiento=?, nacionalidad=?, estado_civil=?, genero=?,
                email_personal=?, telefono_personal=?, telefono_movil=?,
                contacto_emergencia_nombre=?, contacto_emergencia_telefono=?, contacto_emergencia_relacion=?,
                codigo_empleado=?, cargo=?, departamento=?, gerencia=?, centro_costo_id=?, fecha_ingreso=?,
                tipo_contrato=?, jornada=?, supervisor_directo=?, rol_erp=?, email_corporativo=?, extension=?,
                banco_id=?, cuenta_bancaria=?, tipo_cuenta=?, tipo_pago=?, moneda_salario_id=?, salario_base=?,
                afp=?, prevision_salud=?, apv=?,
                direccion=?, ciudad=?, region=?, codigo_postal=?, pais_id=?,
                estado=?, observaciones=?, fecha_modificacion=NOW()
                WHERE id=?");

            $stmt->bind_param("ssssssssssssssssississsssssssidssssssissi",
                $rut, $nombre_completo, $fecha_nacimiento, $nacionalidad, $estado_civil, $genero,
                $email_personal, $telefono_personal, $telefono_movil,
                $contacto_emergencia_nombre, $contacto_emergencia_telefono, $contacto_emergencia_relacion,
                $codigo_empleado, $cargo, $departamento, $gerencia, $centro_costo_id, $fecha_ingreso,
                $tipo_contrato, $jornada, $supervisor_directo, $rol_erp, $email_corporativo, $extension,
                $banco_id, $cuenta_bancaria, $tipo_cuenta, $tipo_pago, $moneda_salario_id, $salario_base,
                $afp, $prevision_salud, $apv,
                $direccion, $ciudad, $region, $codigo_postal, $pais_id,
                $estado, $observaciones, $id
            );

            if ($stmt->execute()) {
                logAuditoria('editar', 'empleados', $id, null, json_encode($_POST), 'Empleado actualizado: ' . $nombre_completo);
                $mensaje = 'Empleado actualizado exitosamente';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al actualizar empleado: ' . $stmt->error;
                $tipo_mensaje = 'error';
            }
        }
    } elseif ($accion === 'eliminar') {
        $id = intval($_POST['id']);
        $fecha_baja = date('Y-m-d');
        $stmt = $conn->prepare("UPDATE empleados SET estado='inactivo', fecha_baja=?, fecha_eliminacion=NOW() WHERE id=?");
        $stmt->bind_param("si", $fecha_baja, $id);

        if ($stmt->execute()) {
            logAuditoria('eliminar', 'empleados', $id, null, null, 'Empleado dado de baja');
            $mensaje = 'Empleado dado de baja exitosamente';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al dar de baja empleado';
            $tipo_mensaje = 'error';
        }
    }
}

// Obtener datos para el formulario
$centros_costo = $conn->query("SELECT * FROM centros_costo ORDER BY nombre");
$monedas = $conn->query("SELECT * FROM monedas ORDER BY codigo");
$paises = $conn->query("SELECT * FROM paises ORDER BY nombre");

// Filtros de búsqueda
$buscar = sanitize($_GET['buscar'] ?? '');
$filtro_departamento = sanitize($_GET['filtro_departamento'] ?? '');
$filtro_estado = sanitize($_GET['filtro_estado'] ?? '');
$filtro_tipo_contrato = sanitize($_GET['filtro_tipo_contrato'] ?? '');

// Consulta de empleados
$where = ["1=1"];
$params = [];
$types = '';

if ($buscar) {
    $where[] = "(codigo_empleado LIKE ? OR nombre_completo LIKE ? OR rut LIKE ?)";
    $buscar_param = "%$buscar%";
    $params[] = &$buscar_param;
    $params[] = &$buscar_param;
    $params[] = &$buscar_param;
    $types .= 'sss';
}

if ($filtro_departamento) {
    $where[] = "departamento = ?";
    $params[] = &$filtro_departamento;
    $types .= 's';
}

if ($filtro_estado) {
    $where[] = "estado = ?";
    $params[] = &$filtro_estado;
    $types .= 's';
}

if ($filtro_tipo_contrato) {
    $where[] = "tipo_contrato = ?";
    $params[] = &$filtro_tipo_contrato;
    $types .= 's';
}

$sql = "SELECT * FROM empleados WHERE " . implode(" AND ", $where) . " ORDER BY nombre_completo";
$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$empleados = $stmt->get_result();

// Modo edición
$editar = null;
if (isset($_GET['editar'])) {
    $id = intval($_GET['editar']);
    $stmt = $conn->prepare("SELECT * FROM empleados WHERE id=?");
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
    <title>Maestro de Empleados - CONECTA ERP</title>
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
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
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
            background: #fa709a;
            color: white;
        }

        .btn-primary:hover {
            background: #e85d87;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(250, 112, 154, 0.3);
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
            border-color: #fa709a;
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
            border-color: #fa709a;
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
            color: #fa709a;
            border-bottom-color: #fa709a;
        }

        .tab:hover {
            color: #fa709a;
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
            border-left: 4px solid #fa709a;
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
            background: #fffaf0;
            border: 1px solid #fbd38d;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #744210;
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
        <h1>MAESTRO DE EMPLEADOS</h1>
        <p>Gestion de Recursos Humanos - RRHH / Nomina / Control</p>
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
            $stats_total = $conn->query("SELECT COUNT(*) as total FROM empleados WHERE estado != 'eliminado'")->fetch_assoc()['total'];
            $stats_activos = $conn->query("SELECT COUNT(*) as total FROM empleados WHERE estado = 'activo'")->fetch_assoc()['total'];
            $stats_inactivos = $conn->query("SELECT COUNT(*) as total FROM empleados WHERE estado = 'inactivo'")->fetch_assoc()['total'];
            $stats_indefinidos = $conn->query("SELECT COUNT(*) as total FROM empleados WHERE tipo_contrato = 'indefinido'")->fetch_assoc()['total'];
            ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Empleados</h3>
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
                    <h3>Contrato Indefinido</h3>
                    <div class="value"><?php echo number_format($stats_indefinidos); ?></div>
                </div>
            </div>

            <!-- BARRA DE HERRAMIENTAS -->
            <form method="GET" class="toolbar">
                <a href="?nuevo=1" class="btn btn-primary">+ Nuevo Empleado</a>

                <div class="search-box">
                    <input type="text" name="buscar" placeholder="Buscar por codigo, nombre o RUT..." value="<?php echo htmlspecialchars($buscar); ?>">
                </div>

                <select name="filtro_departamento">
                    <option value="">Todos los departamentos</option>
                    <option value="Gerencia" <?php echo $filtro_departamento === 'Gerencia' ? 'selected' : ''; ?>>Gerencia</option>
                    <option value="Finanzas" <?php echo $filtro_departamento === 'Finanzas' ? 'selected' : ''; ?>>Finanzas</option>
                    <option value="Ventas" <?php echo $filtro_departamento === 'Ventas' ? 'selected' : ''; ?>>Ventas</option>
                    <option value="Produccion" <?php echo $filtro_departamento === 'Produccion' ? 'selected' : ''; ?>>Produccion</option>
                    <option value="Logistica" <?php echo $filtro_departamento === 'Logistica' ? 'selected' : ''; ?>>Logistica</option>
                    <option value="TI" <?php echo $filtro_departamento === 'TI' ? 'selected' : ''; ?>>TI</option>
                    <option value="RRHH" <?php echo $filtro_departamento === 'RRHH' ? 'selected' : ''; ?>>RRHH</option>
                </select>

                <select name="filtro_tipo_contrato">
                    <option value="">Todos los contratos</option>
                    <option value="indefinido" <?php echo $filtro_tipo_contrato === 'indefinido' ? 'selected' : ''; ?>>Indefinido</option>
                    <option value="plazo_fijo" <?php echo $filtro_tipo_contrato === 'plazo_fijo' ? 'selected' : ''; ?>>Plazo Fijo</option>
                    <option value="honorarios" <?php echo $filtro_tipo_contrato === 'honorarios' ? 'selected' : ''; ?>>Honorarios</option>
                    <option value="obra" <?php echo $filtro_tipo_contrato === 'obra' ? 'selected' : ''; ?>>Por Obra</option>
                </select>

                <select name="filtro_estado">
                    <option value="">Todos los estados</option>
                    <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                    <option value="inactivo" <?php echo $filtro_estado === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                    <option value="vacaciones" <?php echo $filtro_estado === 'vacaciones' ? 'selected' : ''; ?>>Vacaciones</option>
                    <option value="licencia" <?php echo $filtro_estado === 'licencia' ? 'selected' : ''; ?>>Licencia</option>
                </select>

                <button type="submit" class="btn btn-secondary">Buscar</button>
                <button type="button" onclick="window.print()" class="btn btn-info">Imprimir</button>
            </form>

            <!-- TABLA DE EMPLEADOS -->
            <div class="card">
                <div class="table-container">
                    <?php if ($empleados->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Nombre</th>
                                    <th>RUT</th>
                                    <th>Cargo</th>
                                    <th>Departamento</th>
                                    <th>Tipo Contrato</th>
                                    <th>Fecha Ingreso</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($empleado = $empleados->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($empleado['codigo_empleado']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($empleado['nombre_completo']); ?></td>
                                        <td><?php echo formatRUT($empleado['rut']); ?></td>
                                        <td><?php echo htmlspecialchars($empleado['cargo']); ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo htmlspecialchars($empleado['departamento']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php
                                                echo $empleado['tipo_contrato'] === 'indefinido' ? 'success' :
                                                    ($empleado['tipo_contrato'] === 'plazo_fijo' ? 'warning' : 'secondary');
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $empleado['tipo_contrato'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($empleado['fecha_ingreso']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php
                                                echo $empleado['estado'] === 'activo' ? 'success' :
                                                    ($empleado['estado'] === 'vacaciones' ? 'info' : 'warning');
                                            ?>">
                                                <?php echo ucfirst($empleado['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?editar=<?php echo $empleado['id']; ?>" class="btn btn-info btn-sm">Editar</a>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Esta seguro de dar de baja este empleado?');">
                                                    <input type="hidden" name="accion" value="eliminar">
                                                    <input type="hidden" name="id" value="<?php echo $empleado['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Dar de Baja</button>
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <h3>No se encontraron empleados</h3>
                            <p>Intenta ajustar los filtros o crea un nuevo empleado</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- FORMULARIO DE EMPLEADO -->
            <div class="card">
                <form method="POST" style="padding: 30px;">
                    <input type="hidden" name="accion" value="<?php echo $editar ? 'editar' : 'crear'; ?>">
                    <?php if ($editar): ?>
                        <input type="hidden" name="id" value="<?php echo $editar['id']; ?>">
                    <?php endif; ?>

                    <div class="info-box">
                        Complete todos los campos marcados con asterisco como obligatorios. Los datos sensibles estan protegidos.
                    </div>

                    <!-- TABS -->
                    <div class="tabs">
                        <button type="button" class="tab active" onclick="showTab(0)">Datos Personales</button>
                        <button type="button" class="tab" onclick="showTab(1)">Datos Laborales</button>
                        <button type="button" class="tab" onclick="showTab(2)">Datos de Pago</button>
                        <button type="button" class="tab" onclick="showTab(3)">Direccion</button>
                        <button type="button" class="tab" onclick="showTab(4)">Contacto de Emergencia</button>
                    </div>

                    <!-- TAB 1: DATOS PERSONALES -->
                    <div class="tab-content active">
                        <div class="form-title">DATOS PERSONALES</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>RUT / DNI <span class="required">*</span></label>
                                <input type="text" name="rut" required
                                       value="<?php echo $editar['rut'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Nombre Completo <span class="required">*</span></label>
                                <input type="text" name="nombre_completo" required
                                       value="<?php echo $editar['nombre_completo'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Fecha de Nacimiento <span class="required">*</span></label>
                                <input type="date" name="fecha_nacimiento" required
                                       value="<?php echo $editar['fecha_nacimiento'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Genero</label>
                                <select name="genero">
                                    <option value="">Seleccione...</option>
                                    <option value="masculino" <?php echo ($editar['genero'] ?? '') === 'masculino' ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="femenino" <?php echo ($editar['genero'] ?? '') === 'femenino' ? 'selected' : ''; ?>>Femenino</option>
                                    <option value="otro" <?php echo ($editar['genero'] ?? '') === 'otro' ? 'selected' : ''; ?>>Otro</option>
                                    <option value="prefiero_no_decir" <?php echo ($editar['genero'] ?? '') === 'prefiero_no_decir' ? 'selected' : ''; ?>>Prefiero no decir</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Nacionalidad</label>
                                <input type="text" name="nacionalidad"
                                       value="<?php echo $editar['nacionalidad'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Estado Civil</label>
                                <select name="estado_civil">
                                    <option value="">Seleccione...</option>
                                    <option value="soltero" <?php echo ($editar['estado_civil'] ?? '') === 'soltero' ? 'selected' : ''; ?>>Soltero/a</option>
                                    <option value="casado" <?php echo ($editar['estado_civil'] ?? '') === 'casado' ? 'selected' : ''; ?>>Casado/a</option>
                                    <option value="divorciado" <?php echo ($editar['estado_civil'] ?? '') === 'divorciado' ? 'selected' : ''; ?>>Divorciado/a</option>
                                    <option value="viudo" <?php echo ($editar['estado_civil'] ?? '') === 'viudo' ? 'selected' : ''; ?>>Viudo/a</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Email Personal</label>
                                <input type="email" name="email_personal"
                                       value="<?php echo $editar['email_personal'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Telefono Personal</label>
                                <input type="tel" name="telefono_personal"
                                       value="<?php echo $editar['telefono_personal'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Telefono Movil</label>
                                <input type="tel" name="telefono_movil"
                                       value="<?php echo $editar['telefono_movil'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: DATOS LABORALES -->
                    <div class="tab-content">
                        <div class="form-title">DATOS LABORALES</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Codigo Empleado <span class="required">*</span></label>
                                <input type="text" name="codigo_empleado" required
                                       value="<?php echo $editar['codigo_empleado'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Cargo <span class="required">*</span></label>
                                <input type="text" name="cargo" required
                                       value="<?php echo $editar['cargo'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Departamento <span class="required">*</span></label>
                                <select name="departamento" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Gerencia" <?php echo ($editar['departamento'] ?? '') === 'Gerencia' ? 'selected' : ''; ?>>Gerencia</option>
                                    <option value="Finanzas" <?php echo ($editar['departamento'] ?? '') === 'Finanzas' ? 'selected' : ''; ?>>Finanzas</option>
                                    <option value="Ventas" <?php echo ($editar['departamento'] ?? '') === 'Ventas' ? 'selected' : ''; ?>>Ventas</option>
                                    <option value="Produccion" <?php echo ($editar['departamento'] ?? '') === 'Produccion' ? 'selected' : ''; ?>>Produccion</option>
                                    <option value="Logistica" <?php echo ($editar['departamento'] ?? '') === 'Logistica' ? 'selected' : ''; ?>>Logistica</option>
                                    <option value="TI" <?php echo ($editar['departamento'] ?? '') === 'TI' ? 'selected' : ''; ?>>TI</option>
                                    <option value="RRHH" <?php echo ($editar['departamento'] ?? '') === 'RRHH' ? 'selected' : ''; ?>>RRHH</option>
                                    <option value="Compras" <?php echo ($editar['departamento'] ?? '') === 'Compras' ? 'selected' : ''; ?>>Compras</option>
                                    <option value="Calidad" <?php echo ($editar['departamento'] ?? '') === 'Calidad' ? 'selected' : ''; ?>>Calidad</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Gerencia</label>
                                <input type="text" name="gerencia"
                                       value="<?php echo $editar['gerencia'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Centro de Costo</label>
                                <select name="centro_costo_id">
                                    <option value="0">Seleccione...</option>
                                    <?php
                                    $centros_costo->data_seek(0);
                                    while ($centro = $centros_costo->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $centro['id']; ?>"
                                                <?php echo ($editar['centro_costo_id'] ?? 0) == $centro['id'] ? 'selected' : ''; ?>>
                                            <?php echo $centro['codigo']; ?> - <?php echo $centro['nombre']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Fecha de Ingreso <span class="required">*</span></label>
                                <input type="date" name="fecha_ingreso" required
                                       value="<?php echo $editar['fecha_ingreso'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Tipo de Contrato <span class="required">*</span></label>
                                <select name="tipo_contrato" required>
                                    <option value="">Seleccione...</option>
                                    <option value="indefinido" <?php echo ($editar['tipo_contrato'] ?? '') === 'indefinido' ? 'selected' : ''; ?>>Indefinido</option>
                                    <option value="plazo_fijo" <?php echo ($editar['tipo_contrato'] ?? '') === 'plazo_fijo' ? 'selected' : ''; ?>>Plazo Fijo</option>
                                    <option value="honorarios" <?php echo ($editar['tipo_contrato'] ?? '') === 'honorarios' ? 'selected' : ''; ?>>Honorarios</option>
                                    <option value="obra" <?php echo ($editar['tipo_contrato'] ?? '') === 'obra' ? 'selected' : ''; ?>>Por Obra</option>
                                    <option value="temporal" <?php echo ($editar['tipo_contrato'] ?? '') === 'temporal' ? 'selected' : ''; ?>>Temporal</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Jornada</label>
                                <select name="jornada">
                                    <option value="">Seleccione...</option>
                                    <option value="completa" <?php echo ($editar['jornada'] ?? '') === 'completa' ? 'selected' : ''; ?>>Completa</option>
                                    <option value="media" <?php echo ($editar['jornada'] ?? '') === 'media' ? 'selected' : ''; ?>>Media Jornada</option>
                                    <option value="parcial" <?php echo ($editar['jornada'] ?? '') === 'parcial' ? 'selected' : ''; ?>>Parcial</option>
                                    <option value="turnos" <?php echo ($editar['jornada'] ?? '') === 'turnos' ? 'selected' : ''; ?>>Por Turnos</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Supervisor Directo</label>
                                <input type="text" name="supervisor_directo"
                                       value="<?php echo $editar['supervisor_directo'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Rol en el ERP</label>
                                <input type="text" name="rol_erp"
                                       value="<?php echo $editar['rol_erp'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Email Corporativo</label>
                                <input type="email" name="email_corporativo"
                                       value="<?php echo $editar['email_corporativo'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Extension</label>
                                <input type="text" name="extension"
                                       value="<?php echo $editar['extension'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: DATOS DE PAGO -->
                    <div class="tab-content">
                        <div class="form-title">DATOS DE PAGO Y PREVISION</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Banco</label>
                                <input type="text" name="banco_id"
                                       value="<?php echo $editar['banco_id'] ?? ''; ?>">
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
                                    <option value="vista" <?php echo ($editar['tipo_cuenta'] ?? '') === 'vista' ? 'selected' : ''; ?>>Cuenta Vista</option>
                                    <option value="ahorro" <?php echo ($editar['tipo_cuenta'] ?? '') === 'ahorro' ? 'selected' : ''; ?>>Cuenta de Ahorro</option>
                                    <option value="rut" <?php echo ($editar['tipo_cuenta'] ?? '') === 'rut' ? 'selected' : ''; ?>>Cuenta RUT</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tipo de Pago</label>
                                <select name="tipo_pago">
                                    <option value="">Seleccione...</option>
                                    <option value="transferencia" <?php echo ($editar['tipo_pago'] ?? '') === 'transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                                    <option value="cheque" <?php echo ($editar['tipo_pago'] ?? '') === 'cheque' ? 'selected' : ''; ?>>Cheque</option>
                                    <option value="efectivo" <?php echo ($editar['tipo_pago'] ?? '') === 'efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Moneda Salario</label>
                                <select name="moneda_salario_id">
                                    <option value="0">Seleccione...</option>
                                    <?php
                                    $monedas->data_seek(0);
                                    while ($moneda = $monedas->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $moneda['id']; ?>"
                                                <?php echo ($editar['moneda_salario_id'] ?? 0) == $moneda['id'] ? 'selected' : ''; ?>>
                                            <?php echo $moneda['codigo']; ?> - <?php echo $moneda['nombre']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Salario Base</label>
                                <input type="number" step="0.01" name="salario_base"
                                       value="<?php echo $editar['salario_base'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>AFP / Seguridad Social</label>
                                <input type="text" name="afp"
                                       value="<?php echo $editar['afp'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Prevision de Salud</label>
                                <input type="text" name="prevision_salud"
                                       value="<?php echo $editar['prevision_salud'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>APV / Ahorro Voluntario</label>
                                <input type="text" name="apv"
                                       value="<?php echo $editar['apv'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: DIRECCION -->
                    <div class="tab-content">
                        <div class="form-title">DIRECCION</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Direccion</label>
                                <textarea name="direccion"><?php echo $editar['direccion'] ?? ''; ?></textarea>
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
                    </div>

                    <!-- TAB 5: CONTACTO DE EMERGENCIA -->
                    <div class="tab-content">
                        <div class="form-title">CONTACTO DE EMERGENCIA</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nombre Contacto</label>
                                <input type="text" name="contacto_emergencia_nombre"
                                       value="<?php echo $editar['contacto_emergencia_nombre'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Telefono Contacto</label>
                                <input type="tel" name="contacto_emergencia_telefono"
                                       value="<?php echo $editar['contacto_emergencia_telefono'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Relacion</label>
                                <select name="contacto_emergencia_relacion">
                                    <option value="">Seleccione...</option>
                                    <option value="esposo" <?php echo ($editar['contacto_emergencia_relacion'] ?? '') === 'esposo' ? 'selected' : ''; ?>>Esposo/a</option>
                                    <option value="padre" <?php echo ($editar['contacto_emergencia_relacion'] ?? '') === 'padre' ? 'selected' : ''; ?>>Padre/Madre</option>
                                    <option value="hijo" <?php echo ($editar['contacto_emergencia_relacion'] ?? '') === 'hijo' ? 'selected' : ''; ?>>Hijo/a</option>
                                    <option value="hermano" <?php echo ($editar['contacto_emergencia_relacion'] ?? '') === 'hermano' ? 'selected' : ''; ?>>Hermano/a</option>
                                    <option value="otro" <?php echo ($editar['contacto_emergencia_relacion'] ?? '') === 'otro' ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-title">ESTADO Y OBSERVACIONES</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Estado del Empleado</label>
                                <select name="estado">
                                    <option value="activo" <?php echo ($editar['estado'] ?? 'activo') === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="inactivo" <?php echo ($editar['estado'] ?? '') === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                    <option value="vacaciones" <?php echo ($editar['estado'] ?? '') === 'vacaciones' ? 'selected' : ''; ?>>Vacaciones</option>
                                    <option value="licencia" <?php echo ($editar['estado'] ?? '') === 'licencia' ? 'selected' : ''; ?>>Licencia</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Observaciones</label>
                            <textarea name="observaciones" rows="6"><?php echo $editar['observaciones'] ?? ''; ?></textarea>
                        </div>
                    </div>

                    <!-- BOTONES DE ACCION -->
                    <div class="form-actions">
                        <a href="?" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">
                            <?php echo $editar ? 'Actualizar Empleado' : 'Crear Empleado'; ?>
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
