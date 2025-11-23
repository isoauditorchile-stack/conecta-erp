<?php
session_start();

// Configuracion de base de datos
$db_host = 'localhost';
$db_name = 'conectae_conectaerpbd';
$db_user = 'conectae_conectaerpuser';
$db_pass = 'pt125824caraud';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexion: " . $e->getMessage());
}

// Procesamiento de acciones
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    switch($accion) {
        case 'crear_modelo_operacional':
            // TODO: Insertar en tabla presupuesto_modelos_operacionales
            $mensaje = 'Modelo Operacional creado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_modelo_inversiones':
            // TODO: Insertar en tabla presupuesto_modelos_inversiones
            $mensaje = 'Modelo de Inversiones creado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_modelo_tesoreria':
            // TODO: Insertar en tabla presupuesto_modelos_tesoreria
            $mensaje = 'Modelo de Tesoreria creado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_modelo_proyectos':
            // TODO: Insertar en tabla presupuesto_modelos_proyectos
            $mensaje = 'Modelo de Proyectos creado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_dimension':
            // TODO: Insertar en tabla presupuesto_dimensiones
            $mensaje = 'Dimension creada exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_version':
            // TODO: Insertar en tabla presupuesto_versiones
            $mensaje = 'Version creada exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_workflow':
            // TODO: Insertar en tabla presupuesto_workflows
            $mensaje = 'Workflow creado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'carga_manual':
            // TODO: Insertar en tabla presupuesto_cargas_manuales
            $mensaje = 'Carga manual registrada exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'importar_excel':
            // TODO: Procesar archivo Excel y cargar en tabla presupuesto_importaciones
            $mensaje = 'Archivo Excel importado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_regla_distribucion':
            // TODO: Insertar en tabla presupuesto_reglas_distribucion
            $mensaje = 'Regla de distribucion creada exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_escenario':
            // TODO: Insertar en tabla presupuesto_escenarios
            $mensaje = 'Escenario creado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_alerta':
            // TODO: Insertar en tabla presupuesto_alertas
            $mensaje = 'Alerta configurada exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'eliminar':
            $id = $_POST['id'] ?? 0;
            $tabla = $_POST['tabla'] ?? '';
            // TODO: Eliminar registro de la tabla correspondiente
            $mensaje = 'Registro eliminado exitosamente';
            $tipo_mensaje = 'success';
            break;
    }
}

// Obtener datos para las grillas (preparado para consultas SQL)
$modelos_operacionales = []; // TODO: SELECT * FROM presupuesto_modelos_operacionales
$modelos_inversiones = []; // TODO: SELECT * FROM presupuesto_modelos_inversiones
$modelos_tesoreria = []; // TODO: SELECT * FROM presupuesto_modelos_tesoreria
$modelos_proyectos = []; // TODO: SELECT * FROM presupuesto_modelos_proyectos
$dimensiones = []; // TODO: SELECT * FROM presupuesto_dimensiones
$versiones = []; // TODO: SELECT * FROM presupuesto_versiones
$workflows = []; // TODO: SELECT * FROM presupuesto_workflows
$cargas = []; // TODO: SELECT * FROM presupuesto_cargas_manuales
$reglas_distribucion = []; // TODO: SELECT * FROM presupuesto_reglas_distribucion
$escenarios = []; // TODO: SELECT * FROM presupuesto_escenarios
$alertas = []; // TODO: SELECT * FROM presupuesto_alertas
$comparaciones = []; // TODO: SELECT con joins entre presupuesto y real
$integraciones = []; // TODO: SELECT * FROM presupuesto_integraciones

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRESUPUESTOS - ConectaERP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 20px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-icon {
            font-size: 32px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }

        .mensaje {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mensaje.success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .mensaje.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .tabs-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .tab-header {
            background: #f8f9fa;
            padding: 18px 25px;
            cursor: pointer;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            user-select: none;
        }

        .tab-header:hover {
            background: #e9ecef;
        }

        .tab-header.active {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
        }

        .tab-header h2 {
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .tab-icon {
            font-size: 18px;
        }

        .tab-arrow {
            font-size: 20px;
            transition: transform 0.3s ease;
        }

        .tab-header.active .tab-arrow {
            transform: rotate(180deg);
        }

        .tab-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease;
            background: white;
        }

        .tab-content.active {
            max-height: 5000px;
            border-bottom: 1px solid #dee2e6;
        }

        .tab-inner {
            padding: 25px;
        }

        .section-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .card h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1e3a8a;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3b82f6;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            color: #495057;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #047857 0%, #10b981 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, #0e7490 0%, #06b6d4 100%);
            color: white;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        thead {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
        }

        th {
            padding: 14px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            font-size: 14px;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            padding: 6px 12px;
            font-size: 12px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .status-activo {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactivo {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-aprobado {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .kpi-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-left: 4px solid #3b82f6;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .kpi-label {
            font-size: 13px;
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .kpi-value {
            font-size: 28px;
            font-weight: 700;
            color: #1e3a8a;
        }

        .kpi-change {
            font-size: 12px;
            margin-top: 8px;
        }

        .kpi-change.positive {
            color: #10b981;
        }

        .kpi-change.negative {
            color: #ef4444;
        }

        .subsection {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .subsection h4 {
            font-size: 16px;
            font-weight: 600;
            color: #1e3a8a;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #dee2e6, transparent);
            margin: 25px 0;
        }

        @media print {
            .btn, .form-group, .action-buttons {
                display: none;
            }

            body {
                background: white;
            }

            .header {
                background: #1e3a8a;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            <span class="header-icon">&#128200;</span>
            MODULO PRESUPUESTOS
        </h1>
    </div>

    <div class="container">
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="tabs-container">

            <!-- TAB 5.1: MODELADO -->
            <div class="tab-header" onclick="toggleTab('tab1')">
                <h2><span class="tab-icon">&#128203;</span> 5.1 Modelado</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab1">
                <div class="tab-inner">

                    <!-- Modelo Operacional -->
                    <div class="subsection">
                        <h4>&#128188; Modelo Operacional (Ingresos/Gastos)</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Crear Modelo Operacional</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_modelo_operacional">

                                    <div class="form-group">
                                        <label>Codigo Modelo</label>
                                        <input type="text" name="codigo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Nombre Modelo</label>
                                        <input type="text" name="nombre" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo</label>
                                        <select name="tipo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="INGRESOS">Ingresos</option>
                                            <option value="GASTOS">Gastos</option>
                                            <option value="MIXTO">Mixto</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo Fiscal</label>
                                        <select name="periodo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="2024">2024</option>
                                            <option value="2025">2025</option>
                                            <option value="2026">2026</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Moneda</label>
                                        <select name="moneda" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CLP">CLP - Peso Chileno</option>
                                            <option value="USD">USD - Dolar</option>
                                            <option value="EUR">EUR - Euro</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Descripcion</label>
                                        <textarea name="descripcion"></textarea>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Modelo</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Periodo</th>
                                        <th>Moneda</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($modelos_operacionales)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay modelos operacionales registrados
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($modelos_operacionales as $modelo): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($modelo['codigo']); ?></td>
                                            <td><?php echo htmlspecialchars($modelo['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($modelo['tipo']); ?></td>
                                            <td><?php echo htmlspecialchars($modelo['periodo']); ?></td>
                                            <td><?php echo htmlspecialchars($modelo['moneda']); ?></td>
                                            <td><span class="status-badge status-<?php echo strtolower($modelo['estado']); ?>"><?php echo htmlspecialchars($modelo['estado']); ?></span></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-warning btn-icon">&#9998; Editar</button>
                                                    <button class="btn btn-danger btn-icon">&#128465; Eliminar</button>
                                                    <button class="btn btn-info btn-icon" onclick="window.print()">&#128424; Imprimir</button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Modelo de Inversiones -->
                    <div class="subsection">
                        <h4>&#128177; Modelo de Inversiones</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Crear Modelo de Inversiones</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_modelo_inversiones">

                                    <div class="form-group">
                                        <label>Codigo Inversion</label>
                                        <input type="text" name="codigo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Nombre Proyecto</label>
                                        <input type="text" name="nombre" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo Inversion</label>
                                        <select name="tipo_inversion" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CAPEX">CAPEX - Gastos de Capital</option>
                                            <option value="ACTIVO_FIJO">Activo Fijo</option>
                                            <option value="INTANGIBLE">Intangible</option>
                                            <option value="INFRAESTRUCTURA">Infraestructura</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Monto Estimado</label>
                                        <input type="number" name="monto" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Fecha Inicio</label>
                                        <input type="date" name="fecha_inicio" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Fecha Fin</label>
                                        <input type="date" name="fecha_fin" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Responsable</label>
                                        <input type="text" name="responsable" required>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Inversion</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Proyecto</th>
                                        <th>Tipo</th>
                                        <th>Monto Estimado</th>
                                        <th>Inicio</th>
                                        <th>Fin</th>
                                        <th>Responsable</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay modelos de inversiones registrados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Modelo de Tesoreria -->
                    <div class="subsection">
                        <h4>&#128181; Modelo de Tesoreria</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Crear Modelo de Tesoreria</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_modelo_tesoreria">

                                    <div class="form-group">
                                        <label>Codigo Flujo</label>
                                        <input type="text" name="codigo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Nombre Flujo</label>
                                        <input type="text" name="nombre" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo Flujo</label>
                                        <select name="tipo_flujo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="OPERACIONAL">Operacional</option>
                                            <option value="INVERSION">Inversion</option>
                                            <option value="FINANCIAMIENTO">Financiamiento</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodicidad</label>
                                        <select name="periodicidad" required>
                                            <option value="">Seleccione...</option>
                                            <option value="DIARIO">Diario</option>
                                            <option value="SEMANAL">Semanal</option>
                                            <option value="MENSUAL">Mensual</option>
                                            <option value="TRIMESTRAL">Trimestral</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Cuenta Bancaria</label>
                                        <input type="text" name="cuenta_bancaria">
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Modelo</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Nombre</th>
                                        <th>Tipo Flujo</th>
                                        <th>Periodicidad</th>
                                        <th>Cuenta</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay modelos de tesoreria registrados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Modelo de Proyectos -->
                    <div class="subsection">
                        <h4>&#128194; Modelo de Proyectos</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Crear Modelo de Proyectos</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_modelo_proyectos">

                                    <div class="form-group">
                                        <label>Codigo Proyecto</label>
                                        <input type="text" name="codigo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Nombre Proyecto</label>
                                        <input type="text" name="nombre" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Area Responsable</label>
                                        <select name="area" required>
                                            <option value="">Seleccione...</option>
                                            <option value="VENTAS">Ventas</option>
                                            <option value="MARKETING">Marketing</option>
                                            <option value="OPERACIONES">Operaciones</option>
                                            <option value="TI">Tecnologia</option>
                                            <option value="RRHH">Recursos Humanos</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Presupuesto Asignado</label>
                                        <input type="number" name="presupuesto" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Director Proyecto</label>
                                        <input type="text" name="director" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Objetivo Proyecto</label>
                                        <textarea name="objetivo"></textarea>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Proyecto</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Proyecto</th>
                                        <th>Area</th>
                                        <th>Presupuesto</th>
                                        <th>Director</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay modelos de proyectos registrados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Dimensiones -->
                    <div class="subsection">
                        <h4>&#128202; Dimensiones Presupuestarias</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Crear Dimension</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_dimension">

                                    <div class="form-group">
                                        <label>Tipo Dimension</label>
                                        <select name="tipo_dimension" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CUENTA_CONTABLE">Cuenta Contable</option>
                                            <option value="CENTRO_COSTO">Centro de Costo</option>
                                            <option value="UNIDAD_NEGOCIO">Unidad de Negocio</option>
                                            <option value="PRODUCTO">Producto</option>
                                            <option value="CANAL">Canal</option>
                                            <option value="PROYECTO">Proyecto</option>
                                            <option value="EMPRESA">Empresa</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Codigo</label>
                                        <input type="text" name="codigo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Nombre</label>
                                        <input type="text" name="nombre" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Jerarquia</label>
                                        <select name="jerarquia">
                                            <option value="">Ninguna (Raiz)</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Descripcion</label>
                                        <textarea name="descripcion"></textarea>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Dimension</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Codigo</th>
                                        <th>Nombre</th>
                                        <th>Jerarquia</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay dimensiones registradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 5.2: CICLO PRESUPUESTARIO -->
            <div class="tab-header" onclick="toggleTab('tab2')">
                <h2><span class="tab-icon">&#128257;</span> 5.2 Ciclo Presupuestario</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab2">
                <div class="tab-inner">

                    <!-- Versiones -->
                    <div class="subsection">
                        <h4>&#128221; Gestion de Versiones</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Crear Version Presupuestaria</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_version">

                                    <div class="form-group">
                                        <label>Tipo Version</label>
                                        <select name="tipo_version" required>
                                            <option value="">Seleccione...</option>
                                            <option value="PRELIMINAR">Version Preliminar</option>
                                            <option value="REVISION_1">Version Revision 1</option>
                                            <option value="REVISION_2">Version Revision 2</option>
                                            <option value="REVISION_3">Version Revision 3</option>
                                            <option value="APROBADO">Version Aprobado</option>
                                            <option value="FORECAST">Version Forecast</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Codigo Version</label>
                                        <input type="text" name="codigo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Nombre Version</label>
                                        <input type="text" name="nombre" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo Fiscal</label>
                                        <input type="number" name="periodo" min="2020" max="2030" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Fecha Cierre</label>
                                        <input type="date" name="fecha_cierre">
                                    </div>

                                    <div class="form-group">
                                        <label>Responsable</label>
                                        <input type="text" name="responsable" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Notas</label>
                                        <textarea name="notas"></textarea>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Version</button>
                                        <button type="button" class="btn btn-success">&#128259; Copiar desde Version Anterior</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Periodo</th>
                                        <th>Fecha Cierre</th>
                                        <th>Responsable</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay versiones registradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Workflows de Aprobacion -->
                    <div class="subsection">
                        <h4>&#128260; Workflows de Aprobacion</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Configurar Workflow</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_workflow">

                                    <div class="form-group">
                                        <label>Tipo Workflow</label>
                                        <select name="tipo_workflow" required>
                                            <option value="">Seleccione...</option>
                                            <option value="POR_AREA">Workflow por Area</option>
                                            <option value="POR_GERENCIA">Workflow por Gerencia</option>
                                            <option value="CORPORATIVO">Workflow Corporativo</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Nombre Workflow</label>
                                        <input type="text" name="nombre" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Area/Gerencia</label>
                                        <select name="area_gerencia" required>
                                            <option value="">Seleccione...</option>
                                            <option value="VENTAS">Ventas</option>
                                            <option value="MARKETING">Marketing</option>
                                            <option value="OPERACIONES">Operaciones</option>
                                            <option value="FINANZAS">Finanzas</option>
                                            <option value="RRHH">RRHH</option>
                                            <option value="TI">Tecnologia</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Nivel 1 - Aprobador</label>
                                        <input type="text" name="nivel_1" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Nivel 2 - Aprobador</label>
                                        <input type="text" name="nivel_2">
                                    </div>

                                    <div class="form-group">
                                        <label>Nivel 3 - Aprobador</label>
                                        <input type="text" name="nivel_3">
                                    </div>

                                    <div class="form-group">
                                        <label>Monto Minimo Aprobacion</label>
                                        <input type="number" name="monto_minimo" step="0.01">
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Workflow</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Area/Gerencia</th>
                                        <th>Nivel 1</th>
                                        <th>Nivel 2</th>
                                        <th>Nivel 3</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay workflows configurados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Captura Descentralizada -->
                    <div class="subsection">
                        <h4>&#128101; Captura Descentralizada (Usuarios de Negocio)</h4>
                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Usuarios Activos</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Capturas Pendientes</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Capturas Completadas</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Avance Total</div>
                                <div class="kpi-value">0%</div>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Area</th>
                                        <th>Centro Costo</th>
                                        <th>Asignado</th>
                                        <th>Capturado</th>
                                        <th>Avance</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay capturas asignadas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Consolidacion Automatica -->
                    <div class="subsection">
                        <h4>&#128200; Consolidacion Automatica</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Ejecutar Consolidacion</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="ejecutar_consolidacion">

                                    <div class="form-group">
                                        <label>Version a Consolidar</label>
                                        <select name="version" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Nivel Consolidacion</label>
                                        <select name="nivel" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CENTRO_COSTO">Por Centro de Costo</option>
                                            <option value="AREA">Por Area</option>
                                            <option value="EMPRESA">Por Empresa</option>
                                            <option value="GRUPO">Por Grupo</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <select name="periodo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="MENSUAL">Mensual</option>
                                            <option value="TRIMESTRAL">Trimestral</option>
                                            <option value="ANUAL">Anual</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-success">&#9654; Ejecutar Consolidacion</button>
                                        <button type="button" class="btn btn-warning">&#128203; Ver Log</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Fecha Ejecucion</th>
                                        <th>Version</th>
                                        <th>Nivel</th>
                                        <th>Periodo</th>
                                        <th>Registros</th>
                                        <th>Duracion</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay consolidaciones ejecutadas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 5.3: CARGA Y DISTRIBUCION -->
            <div class="tab-header" onclick="toggleTab('tab3')">
                <h2><span class="tab-icon">&#128190;</span> 5.3 Carga y Distribucion</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab3">
                <div class="tab-inner">

                    <!-- Carga Manual -->
                    <div class="subsection">
                        <h4>&#9997; Carga Manual por Formulario</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Ingreso Manual de Presupuesto</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="carga_manual">

                                    <div class="form-group">
                                        <label>Version</label>
                                        <select name="version" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Cuenta Contable</label>
                                        <input type="text" name="cuenta_contable" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Centro de Costo</label>
                                        <select name="centro_costo" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Monto</label>
                                        <input type="number" name="monto" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Moneda</label>
                                        <select name="moneda" required>
                                            <option value="CLP">CLP</option>
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Comentarios</label>
                                        <textarea name="comentarios"></textarea>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Guardar</button>
                                        <button type="button" class="btn btn-success">&#10133; Guardar y Nuevo</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Fecha Carga</th>
                                        <th>Version</th>
                                        <th>Cuenta</th>
                                        <th>Centro Costo</th>
                                        <th>Periodo</th>
                                        <th>Monto</th>
                                        <th>Usuario</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay cargas manuales registradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Importacion Excel -->
                    <div class="subsection">
                        <h4>&#128202; Importacion desde Excel</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Importar Archivo Excel</h3>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="accion" value="importar_excel">

                                    <div class="form-group">
                                        <label>Version Destino</label>
                                        <select name="version" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo Importacion</label>
                                        <select name="tipo_importacion" required>
                                            <option value="">Seleccione...</option>
                                            <option value="REEMPLAZAR">Reemplazar Datos Existentes</option>
                                            <option value="ADICIONAR">Adicionar a Datos Existentes</option>
                                            <option value="ACTUALIZAR">Actualizar Solo Diferencias</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Archivo Excel</label>
                                        <input type="file" name="archivo" accept=".xlsx,.xls" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Hoja de Calculo</label>
                                        <input type="text" name="hoja" placeholder="Nombre de la hoja o numero">
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128190; Importar</button>
                                        <button type="button" class="btn btn-success">&#128196; Descargar Plantilla</button>
                                        <button type="button" class="btn btn-warning">&#128269; Validar Archivo</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Archivo</th>
                                        <th>Version</th>
                                        <th>Tipo</th>
                                        <th>Registros</th>
                                        <th>Exitosos</th>
                                        <th>Errores</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay importaciones registradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Reglas de Distribucion -->
                    <div class="subsection">
                        <h4>&#128207; Reglas de Distribucion</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Crear Regla de Distribucion</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_regla_distribucion">

                                    <div class="form-group">
                                        <label>Tipo Regla</label>
                                        <select name="tipo_regla" required>
                                            <option value="">Seleccione...</option>
                                            <option value="PORCENTAJE">Por Porcentaje</option>
                                            <option value="HISTORICO">Por Historico</option>
                                            <option value="VOLUMEN">Por Volumen</option>
                                            <option value="M2">Por M2</option>
                                            <option value="HH">Por Horas Hombre</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Nombre Regla</label>
                                        <input type="text" name="nombre" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Cuenta Origen</label>
                                        <input type="text" name="cuenta_origen" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Dimension Distribucion</label>
                                        <select name="dimension" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CENTRO_COSTO">Centro de Costo</option>
                                            <option value="PRODUCTO">Producto</option>
                                            <option value="CANAL">Canal</option>
                                            <option value="PROYECTO">Proyecto</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo Base (si aplica)</label>
                                        <input type="month" name="periodo_base">
                                    </div>

                                    <div class="form-group">
                                        <label>Formula/Criterio</label>
                                        <textarea name="formula"></textarea>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Regla</button>
                                        <button type="button" class="btn btn-success">&#9654; Ejecutar Distribucion</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Cuenta Origen</th>
                                        <th>Dimension</th>
                                        <th>Ultima Ejecucion</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay reglas de distribucion configuradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Escenarios What-If -->
                    <div class="subsection">
                        <h4>&#128161; Escenarios What-If</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Crear Escenario</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_escenario">

                                    <div class="form-group">
                                        <label>Tipo Escenario</label>
                                        <select name="tipo_escenario" required>
                                            <option value="">Seleccione...</option>
                                            <option value="SUPUESTOS">Con Distintos Supuestos</option>
                                            <option value="VAR_VENTAS">Con Variacion de Ventas</option>
                                            <option value="VAR_TC">Con Variacion de Tipo de Cambio</option>
                                            <option value="MIXTO">Mixto</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Nombre Escenario</label>
                                        <input type="text" name="nombre" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Version Base</label>
                                        <select name="version_base" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Variacion Ventas (%)</label>
                                        <input type="number" name="var_ventas" step="0.01" placeholder="Ej: 10 para +10%, -5 para -5%">
                                    </div>

                                    <div class="form-group">
                                        <label>Variacion Tipo Cambio (%)</label>
                                        <input type="number" name="var_tc" step="0.01" placeholder="Ej: 15 para +15%">
                                    </div>

                                    <div class="form-group">
                                        <label>Otros Supuestos</label>
                                        <textarea name="supuestos" placeholder="Describa otros supuestos del escenario"></textarea>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Escenario</button>
                                        <button type="button" class="btn btn-success">&#128200; Calcular Impacto</button>
                                        <button type="button" class="btn btn-warning">&#128202; Comparar Escenarios</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Version Base</th>
                                        <th>Var Ventas</th>
                                        <th>Var TC</th>
                                        <th>Fecha Creacion</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay escenarios creados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 5.4: CONTROL DE EJECUCION -->
            <div class="tab-header" onclick="toggleTab('tab4')">
                <h2><span class="tab-icon">&#128200;</span> 5.4 Control de Ejecucion</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab4">
                <div class="tab-inner">

                    <!-- Comparaciones -->
                    <div class="subsection">
                        <h4>&#128202; Comparaciones Presupuestarias</h4>

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Presupuesto Total</div>
                                <div class="kpi-value">$0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Real Ejecutado</div>
                                <div class="kpi-value">$0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Variacion</div>
                                <div class="kpi-value">0%</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Forecast</div>
                                <div class="kpi-value">$0</div>
                            </div>
                        </div>

                        <div class="section-grid">
                            <div class="card">
                                <h3>Comparacion Real vs Presupuesto</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="comparacion_real_presupuesto">

                                    <div class="form-group">
                                        <label>Version Presupuesto</label>
                                        <select name="version" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo Desde</label>
                                        <input type="month" name="periodo_desde" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo Hasta</label>
                                        <input type="month" name="periodo_hasta" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Nivel Detalle</label>
                                        <select name="nivel_detalle" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CUENTA">Por Cuenta Contable</option>
                                            <option value="CENTRO_COSTO">Por Centro de Costo</option>
                                            <option value="PROYECTO">Por Proyecto</option>
                                            <option value="TODOS">Todos los Niveles</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Generar Reporte</button>
                                        <button type="button" class="btn btn-success">&#128202; Exportar Excel</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Comparacion Real vs Forecast</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="comparacion_real_forecast">

                                    <div class="form-group">
                                        <label>Version Forecast</label>
                                        <select name="version" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo Desde</label>
                                        <input type="month" name="periodo_desde" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo Hasta</label>
                                        <input type="month" name="periodo_hasta" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Nivel Detalle</label>
                                        <select name="nivel_detalle" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CUENTA">Por Cuenta Contable</option>
                                            <option value="CENTRO_COSTO">Por Centro de Costo</option>
                                            <option value="PROYECTO">Por Proyecto</option>
                                            <option value="TODOS">Todos los Niveles</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Generar Reporte</button>
                                        <button type="button" class="btn btn-success">&#128202; Exportar Excel</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Comparacion Presupuesto vs Forecast</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="comparacion_presupuesto_forecast">

                                    <div class="form-group">
                                        <label>Version Presupuesto</label>
                                        <select name="version_presupuesto" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Version Forecast</label>
                                        <select name="version_forecast" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo Analisis</label>
                                        <select name="tipo_analisis" required>
                                            <option value="">Seleccione...</option>
                                            <option value="VARIACION">Variacion Absoluta</option>
                                            <option value="PORCENTAJE">Variacion Porcentual</option>
                                            <option value="AMBOS">Ambos</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Generar Reporte</button>
                                        <button type="button" class="btn btn-success">&#128202; Exportar Excel</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Cuenta/Centro Costo</th>
                                        <th>Descripcion</th>
                                        <th>Presupuesto</th>
                                        <th>Real</th>
                                        <th>Forecast</th>
                                        <th>Var P vs R</th>
                                        <th>Var P vs F</th>
                                        <th>% Ejecucion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            Genere un reporte de comparacion para ver los datos
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Alertas por Sobre-Ejecucion -->
                    <div class="subsection">
                        <h4>&#128276; Alertas por Sobre-Ejecucion</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Configurar Alerta</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_alerta">

                                    <div class="form-group">
                                        <label>Tipo Alerta</label>
                                        <select name="tipo_alerta" required>
                                            <option value="">Seleccione...</option>
                                            <option value="POR_CUENTA">Por Cuenta Contable</option>
                                            <option value="POR_CENTRO_COSTO">Por Centro de Costo</option>
                                            <option value="POR_PROYECTO">Por Proyecto</option>
                                            <option value="GLOBAL">Global</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Codigo (Cuenta/CC/Proyecto)</label>
                                        <input type="text" name="codigo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Umbral Alerta (%)</label>
                                        <input type="number" name="umbral" step="0.01" required placeholder="Ej: 90 para alertar al 90% de ejecucion">
                                    </div>

                                    <div class="form-group">
                                        <label>Umbral Critico (%)</label>
                                        <input type="number" name="umbral_critico" step="0.01" required placeholder="Ej: 100 para alertar al 100% de ejecucion">
                                    </div>

                                    <div class="form-group">
                                        <label>Notificar a</label>
                                        <input type="email" name="email" required multiple placeholder="correo@ejemplo.com">
                                    </div>

                                    <div class="form-group">
                                        <label>Frecuencia Notificacion</label>
                                        <select name="frecuencia" required>
                                            <option value="">Seleccione...</option>
                                            <option value="INMEDIATA">Inmediata</option>
                                            <option value="DIARIA">Diaria</option>
                                            <option value="SEMANAL">Semanal</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Alerta</button>
                                        <button type="button" class="btn btn-warning">&#128270; Probar Alerta</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Codigo</th>
                                        <th>Umbral</th>
                                        <th>Umbral Critico</th>
                                        <th>Notificar a</th>
                                        <th>Frecuencia</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay alertas configuradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Bloqueo de Imputaciones -->
                    <div class="subsection">
                        <h4>&#128274; Bloqueo de Imputaciones que Superen Presupuesto</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Configuracion de Bloqueo</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="configurar_bloqueo">

                                    <div class="form-group">
                                        <label>Modo Bloqueo</label>
                                        <select name="modo_bloqueo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="ADVERTENCIA">Solo Advertencia</option>
                                            <option value="BLOQUEO_PARCIAL">Bloqueo con Aprobacion</option>
                                            <option value="BLOQUEO_TOTAL">Bloqueo Total</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Nivel Control</label>
                                        <select name="nivel_control" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CUENTA">Por Cuenta Contable</option>
                                            <option value="CENTRO_COSTO">Por Centro de Costo</option>
                                            <option value="PROYECTO">Por Proyecto</option>
                                            <option value="TODOS">Todos los Niveles</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Tolerancia (%)</label>
                                        <input type="number" name="tolerancia" step="0.01" placeholder="Ej: 5 para permitir hasta 5% sobre presupuesto">
                                    </div>

                                    <div class="form-group">
                                        <label>Aprobador Excepciones</label>
                                        <input type="text" name="aprobador">
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Guardar Configuracion</button>
                                        <button type="button" class="btn btn-danger">&#128274; Activar Bloqueo</button>
                                        <button type="button" class="btn btn-warning">&#128275; Desactivar Bloqueo</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Imputaciones Bloqueadas Hoy</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Excepciones Aprobadas</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Excepciones Pendientes</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Monto Bloqueado</div>
                                <div class="kpi-value">$0</div>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Cuenta/CC</th>
                                        <th>Presupuesto</th>
                                        <th>Ejecutado</th>
                                        <th>Intento Imputacion</th>
                                        <th>Usuario</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay bloqueos registrados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 5.5: INTEGRACION -->
            <div class="tab-header" onclick="toggleTab('tab5')">
                <h2><span class="tab-icon">&#128279;</span> 5.5 Integracion</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab5">
                <div class="tab-inner">

                    <div class="subsection">
                        <h4>&#128260; Panel de Integraciones</h4>

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Integraciones Activas</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Sincronizaciones Hoy</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Registros Procesados</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Errores Pendientes</div>
                                <div class="kpi-value">0</div>
                            </div>
                        </div>

                        <div class="section-grid">
                            <div class="card">
                                <h3>&#128218; Contabilidad (Ejecuciones Reales)</h3>
                                <div class="form-group">
                                    <label>Estado Integracion</label>
                                    <span class="status-badge status-inactivo">Inactivo</span>
                                </div>
                                <div class="form-group">
                                    <label>Ultima Sincronizacion</label>
                                    <input type="text" value="Nunca" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Frecuencia</label>
                                    <select name="frecuencia_contabilidad">
                                        <option value="">Seleccione...</option>
                                        <option value="TIEMPO_REAL">Tiempo Real</option>
                                        <option value="HORARIA">Cada Hora</option>
                                        <option value="DIARIA">Diaria</option>
                                        <option value="MANUAL">Manual</option>
                                    </select>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-success">&#9654; Sincronizar Ahora</button>
                                    <button class="btn btn-primary">&#128270; Configurar</button>
                                </div>
                            </div>

                            <div class="card">
                                <h3>&#128181; Tesoreria (Flujo de Caja)</h3>
                                <div class="form-group">
                                    <label>Estado Integracion</label>
                                    <span class="status-badge status-inactivo">Inactivo</span>
                                </div>
                                <div class="form-group">
                                    <label>Ultima Sincronizacion</label>
                                    <input type="text" value="Nunca" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Frecuencia</label>
                                    <select name="frecuencia_tesoreria">
                                        <option value="">Seleccione...</option>
                                        <option value="TIEMPO_REAL">Tiempo Real</option>
                                        <option value="HORARIA">Cada Hora</option>
                                        <option value="DIARIA">Diaria</option>
                                        <option value="MANUAL">Manual</option>
                                    </select>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-success">&#9654; Sincronizar Ahora</button>
                                    <button class="btn btn-primary">&#128270; Configurar</button>
                                </div>
                            </div>

                            <div class="card">
                                <h3>&#128188; Activos Fijos (Inversiones)</h3>
                                <div class="form-group">
                                    <label>Estado Integracion</label>
                                    <span class="status-badge status-inactivo">Inactivo</span>
                                </div>
                                <div class="form-group">
                                    <label>Ultima Sincronizacion</label>
                                    <input type="text" value="Nunca" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Frecuencia</label>
                                    <select name="frecuencia_activos">
                                        <option value="">Seleccione...</option>
                                        <option value="TIEMPO_REAL">Tiempo Real</option>
                                        <option value="HORARIA">Cada Hora</option>
                                        <option value="DIARIA">Diaria</option>
                                        <option value="MANUAL">Manual</option>
                                    </select>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-success">&#9654; Sincronizar Ahora</button>
                                    <button class="btn btn-primary">&#128270; Configurar</button>
                                </div>
                            </div>

                            <div class="card">
                                <h3>&#128200; Reporting Financiero</h3>
                                <div class="form-group">
                                    <label>Estado Integracion</label>
                                    <span class="status-badge status-inactivo">Inactivo</span>
                                </div>
                                <div class="form-group">
                                    <label>Ultima Sincronizacion</label>
                                    <input type="text" value="Nunca" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Frecuencia</label>
                                    <select name="frecuencia_reporting">
                                        <option value="">Seleccione...</option>
                                        <option value="TIEMPO_REAL">Tiempo Real</option>
                                        <option value="HORARIA">Cada Hora</option>
                                        <option value="DIARIA">Diaria</option>
                                        <option value="MANUAL">Manual</option>
                                    </select>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-success">&#9654; Sincronizar Ahora</button>
                                    <button class="btn btn-primary">&#128270; Configurar</button>
                                </div>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Modulo</th>
                                        <th>Tipo Operacion</th>
                                        <th>Registros</th>
                                        <th>Exitosos</th>
                                        <th>Errores</th>
                                        <th>Duracion</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay sincronizaciones registradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <script>
        function toggleTab(tabId) {
            const tab = document.getElementById(tabId);
            const header = tab.previousElementSibling;

            // Cerrar otros tabs
            const allTabs = document.querySelectorAll('.tab-content');
            const allHeaders = document.querySelectorAll('.tab-header');

            allTabs.forEach(t => {
                if (t.id !== tabId) {
                    t.classList.remove('active');
                }
            });

            allHeaders.forEach(h => {
                if (h !== header) {
                    h.classList.remove('active');
                }
            });

            // Toggle tab actual
            tab.classList.toggle('active');
            header.classList.toggle('active');
        }
    </script>
</body>
</html>
