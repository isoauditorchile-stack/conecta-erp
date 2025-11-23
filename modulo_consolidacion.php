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
        case 'crear_grupo':
            // TODO: Insertar en tabla consolidacion_grupos
            $mensaje = 'Grupo economico creado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'agregar_empresa':
            // TODO: Insertar en tabla consolidacion_empresas
            $mensaje = 'Empresa agregada al grupo exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'configurar_moneda':
            // TODO: Actualizar configuracion de moneda
            $mensaje = 'Moneda de consolidacion configurada exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'mapear_cuentas':
            // TODO: Insertar en tabla consolidacion_mapeo_cuentas
            $mensaje = 'Mapeo de cuentas guardado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'cargar_saldos':
            // TODO: Cargar saldos desde contabilidad
            $mensaje = 'Saldos cargados exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'ejecutar_conversion':
            // TODO: Ejecutar conversion de moneda
            $mensaje = 'Conversion de moneda ejecutada exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_eliminacion':
            // TODO: Insertar en tabla consolidacion_eliminaciones
            $mensaje = 'Eliminacion creada exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'ejecutar_consolidacion':
            // TODO: Ejecutar proceso de consolidacion
            $mensaje = 'Consolidacion ejecutada exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'generar_reporte':
            // TODO: Generar reporte consolidado
            $mensaje = 'Reporte generado exitosamente';
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

// Obtener datos para las grillas
$grupos = []; // TODO: SELECT * FROM consolidacion_grupos
$empresas_grupo = []; // TODO: SELECT * FROM consolidacion_empresas
$monedas = []; // TODO: SELECT * FROM consolidacion_monedas
$mapeos = []; // TODO: SELECT * FROM consolidacion_mapeo_cuentas
$saldos = []; // TODO: SELECT * FROM consolidacion_saldos
$conversiones = []; // TODO: SELECT * FROM consolidacion_conversiones
$eliminaciones = []; // TODO: SELECT * FROM consolidacion_eliminaciones
$consolidaciones = []; // TODO: SELECT * FROM consolidacion_procesos
$reportes = []; // TODO: SELECT * FROM consolidacion_reportes

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CONSOLIDACION - ConectaERP</title>
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
            background: linear-gradient(135deg, #0c4a6e 0%, #0284c7 100%);
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
            background: linear-gradient(135deg, #0c4a6e 0%, #0284c7 100%);
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
            color: #0c4a6e;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0284c7;
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
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
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
            background: linear-gradient(135deg, #0c4a6e 0%, #0284c7 100%);
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
            background: linear-gradient(135deg, #0c4a6e 0%, #0284c7 100%);
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

        .status-completado {
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
            border-left: 4px solid #0284c7;
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
            color: #0c4a6e;
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
            color: #0c4a6e;
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

        .org-tree {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .org-tree-node {
            background: white;
            border: 2px solid #0284c7;
            border-radius: 6px;
            padding: 15px;
            margin: 10px;
            display: inline-block;
        }

        .org-tree-node.holding {
            border-color: #dc2626;
            background: #fef2f2;
        }

        .org-tree-node.subsidiary {
            border-color: #10b981;
            background: #f0fdf4;
        }

        @media print {
            .btn, .form-group, .action-buttons {
                display: none;
            }

            body {
                background: white;
            }

            .header {
                background: #0c4a6e;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            <span class="header-icon">&#127760;</span>
            MODULO CONSOLIDACION
        </h1>
    </div>

    <div class="container">
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="tabs-container">

            <!-- TAB 7.1: CONFIGURACION -->
            <div class="tab-header" onclick="toggleTab('tab1')">
                <h2><span class="tab-icon">&#128295;</span> 7.1 Configuracion</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab1">
                <div class="tab-inner">

                    <!-- Definicion de Grupo Economico -->
                    <div class="subsection">
                        <h4>&#127760; Definicion de Grupo Economico</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Crear Grupo Economico</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_grupo">

                                    <div class="form-group">
                                        <label>Codigo Grupo</label>
                                        <input type="text" name="codigo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Nombre Grupo</label>
                                        <input type="text" name="nombre" required>
                                    </div>

                                    <div class="form-group">
                                        <label>RUT Holding</label>
                                        <input type="text" name="rut_holding" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Pais Sede</label>
                                        <select name="pais" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CL">Chile</option>
                                            <option value="AR">Argentina</option>
                                            <option value="PE">Peru</option>
                                            <option value="CO">Colombia</option>
                                            <option value="MX">Mexico</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Ejercicio Fiscal</label>
                                        <select name="ejercicio_fiscal" required>
                                            <option value="">Seleccione...</option>
                                            <option value="ENERO_DICIEMBRE">Enero - Diciembre</option>
                                            <option value="JULIO_JUNIO">Julio - Junio</option>
                                            <option value="OCTUBRE_SEPTIEMBRE">Octubre - Septiembre</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Responsable Consolidacion</label>
                                        <input type="text" name="responsable" required>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Grupo</button>
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
                                        <th>Nombre Grupo</th>
                                        <th>RUT Holding</th>
                                        <th>Pais</th>
                                        <th>Ejercicio Fiscal</th>
                                        <th>Responsable</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay grupos economicos registrados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Empresas Consolidadas -->
                    <div class="subsection">
                        <h4>&#128188; Empresas Consolidadas</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Agregar Empresa al Grupo</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="agregar_empresa">

                                    <div class="form-group">
                                        <label>Grupo Economico</label>
                                        <select name="grupo_id" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>RUT Empresa</label>
                                        <input type="text" name="rut" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Razon Social</label>
                                        <input type="text" name="razon_social" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Metodo Consolidacion</label>
                                        <select name="metodo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="TOTAL">Metodo Total (Control Total)</option>
                                            <option value="PROPORCIONAL">Metodo Proporcional (Control Conjunto)</option>
                                            <option value="EQUIVALENCIA">Puesta en Equivalencia (Influencia Significativa)</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Porcentaje Participacion (%)</label>
                                        <input type="number" name="porcentaje" step="0.01" min="0" max="100" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Pais</label>
                                        <select name="pais" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CL">Chile</option>
                                            <option value="AR">Argentina</option>
                                            <option value="PE">Peru</option>
                                            <option value="CO">Colombia</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Moneda Funcional</label>
                                        <select name="moneda_funcional" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CLP">CLP - Peso Chileno</option>
                                            <option value="USD">USD - Dolar</option>
                                            <option value="EUR">EUR - Euro</option>
                                            <option value="ARS">ARS - Peso Argentino</option>
                                            <option value="PEN">PEN - Sol Peruano</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Nivel Jerarquico</label>
                                        <select name="nivel" required>
                                            <option value="">Seleccione...</option>
                                            <option value="HOLDING">Holding (Nivel 0)</option>
                                            <option value="FILIAL_1">Filial Directa (Nivel 1)</option>
                                            <option value="FILIAL_2">Subfilial (Nivel 2)</option>
                                            <option value="FILIAL_3">Nivel 3</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Agregar Empresa</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>RUT</th>
                                        <th>Razon Social</th>
                                        <th>Metodo</th>
                                        <th>% Participacion</th>
                                        <th>Pais</th>
                                        <th>Moneda</th>
                                        <th>Nivel</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay empresas agregadas al grupo
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Moneda de Consolidacion -->
                    <div class="subsection">
                        <h4>&#128176; Moneda de Consolidacion</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Configurar Moneda</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="configurar_moneda">

                                    <div class="form-group">
                                        <label>Grupo Economico</label>
                                        <select name="grupo_id" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Moneda de Presentacion</label>
                                        <select name="moneda_presentacion" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CLP">CLP - Peso Chileno</option>
                                            <option value="USD">USD - Dolar</option>
                                            <option value="EUR">EUR - Euro</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Fuente Tipos de Cambio</label>
                                        <select name="fuente_tc" required>
                                            <option value="">Seleccione...</option>
                                            <option value="BANCO_CENTRAL">Banco Central</option>
                                            <option value="SII">SII</option>
                                            <option value="MANUAL">Manual</option>
                                            <option value="API_EXTERNA">API Externa</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Redondeo</label>
                                        <select name="redondeo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="0">Sin decimales</option>
                                            <option value="2">2 decimales</option>
                                            <option value="4">4 decimales</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Guardar Configuracion</button>
                                        <button type="button" class="btn btn-success">&#128190; Importar TC</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Moneda</th>
                                        <th>Fecha</th>
                                        <th>TC Cierre</th>
                                        <th>TC Promedio</th>
                                        <th>TC Historico</th>
                                        <th>Fuente</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay tipos de cambio registrados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Plan de Cuentas Consolidado -->
                    <div class="subsection">
                        <h4>&#128211; Plan de Cuentas Consolidado y Mapeo</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Mapear Cuentas desde Filial</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="mapear_cuentas">

                                    <div class="form-group">
                                        <label>Empresa Filial</label>
                                        <select name="empresa_id" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Cuenta Filial</label>
                                        <input type="text" name="cuenta_filial" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Nombre Cuenta Filial</label>
                                        <input type="text" name="nombre_filial" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Cuenta Consolidada</label>
                                        <input type="text" name="cuenta_consolidada" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Nombre Cuenta Consolidada</label>
                                        <input type="text" name="nombre_consolidada" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Factor Conversion</label>
                                        <select name="factor" required>
                                            <option value="">Seleccione...</option>
                                            <option value="1">1:1 (Sin cambio)</option>
                                            <option value="-1">-1 (Invertir signo)</option>
                                            <option value="OTRO">Otro factor</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Mapeo</button>
                                        <button type="button" class="btn btn-success">&#128190; Importar Mapeo Masivo</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Empresa</th>
                                        <th>Cuenta Filial</th>
                                        <th>Nombre Filial</th>
                                        <th>Cuenta Consolidada</th>
                                        <th>Nombre Consolidada</th>
                                        <th>Factor</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay mapeos configurados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 7.2: PROCESOS -->
            <div class="tab-header" onclick="toggleTab('tab2')">
                <h2><span class="tab-icon">&#9881;</span> 7.2 Procesos</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab2">
                <div class="tab-inner">

                    <!-- Carga de Saldos -->
                    <div class="subsection">
                        <h4>&#128190; Carga de Saldos por Empresa</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Cargar Saldos Automaticamente</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="cargar_saldos">

                                    <div class="form-group">
                                        <label>Grupo Economico</label>
                                        <select name="grupo_id" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Empresa</label>
                                        <select name="empresa_id" required>
                                            <option value="">Seleccione...</option>
                                            <option value="TODAS">Todas las Empresas</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Origen Datos</label>
                                        <select name="origen" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CONTABILIDAD">Modulo Contabilidad</option>
                                            <option value="ARCHIVO">Archivo Excel</option>
                                            <option value="API">API Externa</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-success">&#128190; Cargar Saldos</button>
                                        <button type="button" class="btn btn-warning">&#128203; Ver Log</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Empresas Cargadas</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Total Cuentas</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Saldo Total Activos</div>
                                <div class="kpi-value">$0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Saldo Total Pasivos</div>
                                <div class="kpi-value">$0</div>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Empresa</th>
                                        <th>Cuenta</th>
                                        <th>Descripcion</th>
                                        <th>Saldo Moneda Local</th>
                                        <th>Moneda</th>
                                        <th>Periodo</th>
                                        <th>Fecha Carga</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay saldos cargados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Conversion de Moneda -->
                    <div class="subsection">
                        <h4>&#128177; Conversion de Moneda</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Tasa Cierre</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="ejecutar_conversion">
                                    <input type="hidden" name="tipo_tasa" value="CIERRE">

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Aplicar a</label>
                                        <select name="aplicar_a" required>
                                            <option value="">Seleccione...</option>
                                            <option value="ACTIVOS">Activos</option>
                                            <option value="PASIVOS">Pasivos</option>
                                            <option value="PATRIMONIO">Patrimonio</option>
                                            <option value="TODOS">Todos los Saldos</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#9654; Ejecutar Conversion</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Tasa Promedio</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="ejecutar_conversion">
                                    <input type="hidden" name="tipo_tasa" value="PROMEDIO">

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Aplicar a</label>
                                        <select name="aplicar_a" required>
                                            <option value="">Seleccione...</option>
                                            <option value="INGRESOS">Ingresos</option>
                                            <option value="COSTOS">Costos</option>
                                            <option value="GASTOS">Gastos</option>
                                            <option value="RESULTADOS">Todos los Resultados</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#9654; Ejecutar Conversion</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Tasa Historica</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="ejecutar_conversion">
                                    <input type="hidden" name="tipo_tasa" value="HISTORICA">

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Aplicar a</label>
                                        <select name="aplicar_a" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CAPITAL">Capital Social</option>
                                            <option value="RESERVAS">Reservas</option>
                                            <option value="APORTES">Aportes de Capital</option>
                                            <option value="ACTIVOS_FIJOS">Activos Fijos</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#9654; Ejecutar Conversion</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Empresa</th>
                                        <th>Cuenta</th>
                                        <th>Saldo Original</th>
                                        <th>Moneda Origen</th>
                                        <th>TC Aplicado</th>
                                        <th>Tipo TC</th>
                                        <th>Saldo Convertido</th>
                                        <th>Moneda Destino</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            Ejecute una conversion para ver los resultados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Eliminaciones -->
                    <div class="subsection">
                        <h4>&#128260; Eliminaciones</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Eliminacion de Saldos Reciprocos</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_eliminacion">
                                    <input type="hidden" name="tipo" value="SALDOS_RECIPROCOS">

                                    <div class="form-group">
                                        <label>Empresa Deudora</label>
                                        <select name="empresa_deudora" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Empresa Acreedora</label>
                                        <select name="empresa_acreedora" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Cuenta por Cobrar</label>
                                        <input type="text" name="cta_cobrar" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Cuenta por Pagar</label>
                                        <input type="text" name="cta_pagar" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Monto a Eliminar</label>
                                        <input type="number" name="monto" step="0.01" required>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Eliminacion</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Eliminacion de Ventas Internas</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_eliminacion">
                                    <input type="hidden" name="tipo" value="VENTAS_INTERNAS">

                                    <div class="form-group">
                                        <label>Empresa Vendedora</label>
                                        <select name="empresa_vendedora" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Empresa Compradora</label>
                                        <select name="empresa_compradora" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Monto Venta Interna</label>
                                        <input type="number" name="monto_venta" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Costo Venta</label>
                                        <input type="number" name="costo_venta" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Margen No Realizado</label>
                                        <input type="number" name="margen" step="0.01" required>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Eliminacion</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Eliminacion de Dividendos</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_eliminacion">
                                    <input type="hidden" name="tipo" value="DIVIDENDOS">

                                    <div class="form-group">
                                        <label>Empresa Pagadora</label>
                                        <select name="empresa_pagadora" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Empresa Receptora</label>
                                        <select name="empresa_receptora" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Monto Dividendos</label>
                                        <input type="number" name="monto" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Eliminacion</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Eliminacion de Prestamos Intragrupo</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_eliminacion">
                                    <input type="hidden" name="tipo" value="PRESTAMOS">

                                    <div class="form-group">
                                        <label>Empresa Prestamista</label>
                                        <select name="empresa_prestamista" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Empresa Prestataria</label>
                                        <select name="empresa_prestataria" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Monto Prestamo</label>
                                        <input type="number" name="monto_prestamo" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Intereses del Periodo</label>
                                        <input type="number" name="intereses" step="0.01">
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Eliminacion</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Empresa 1</th>
                                        <th>Empresa 2</th>
                                        <th>Concepto</th>
                                        <th>Monto</th>
                                        <th>Periodo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay eliminaciones registradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Consolidacion -->
                    <div class="subsection">
                        <h4>&#128200; Consolidacion Encadenada y por Niveles</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Ejecutar Consolidacion</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="ejecutar_consolidacion">

                                    <div class="form-group">
                                        <label>Grupo Economico</label>
                                        <select name="grupo_id" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo Consolidacion</label>
                                        <select name="tipo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="ENCADENADA">Consolidacion Encadenada</option>
                                            <option value="NIVEL_1">Solo Nivel 1</option>
                                            <option value="NIVEL_2">Hasta Nivel 2</option>
                                            <option value="TODOS_NIVELES">Todos los Niveles</option>
                                            <option value="HOLDING">Consolidacion Holding</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Modo</label>
                                        <select name="modo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="PRELIMINAR">Preliminar</option>
                                            <option value="DEFINITIVA">Definitiva</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Incluir Eliminaciones</label>
                                        <select name="eliminaciones" required>
                                            <option value="SI">Si</option>
                                            <option value="NO">No</option>
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

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Empresas Consolidadas</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Total Activos Consolidados</div>
                                <div class="kpi-value">$0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Total Pasivos Consolidados</div>
                                <div class="kpi-value">$0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Patrimonio Consolidado</div>
                                <div class="kpi-value">$0</div>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Fecha Ejecucion</th>
                                        <th>Grupo</th>
                                        <th>Periodo</th>
                                        <th>Tipo</th>
                                        <th>Empresas</th>
                                        <th>Modo</th>
                                        <th>Duracion</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay consolidaciones ejecutadas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 7.3: REPORTING -->
            <div class="tab-header" onclick="toggleTab('tab3')">
                <h2><span class="tab-icon">&#128202;</span> 7.3 Reporting</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab3">
                <div class="tab-inner">

                    <!-- Estados Consolidados -->
                    <div class="subsection">
                        <h4>&#128200; Estados Financieros Consolidados</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Balance Consolidado</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_reporte">
                                    <input type="hidden" name="tipo_reporte" value="BALANCE">

                                    <div class="form-group">
                                        <label>Consolidacion</label>
                                        <select name="consolidacion_id" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Formato</label>
                                        <select name="formato" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CLASIFICADO">Clasificado</option>
                                            <option value="COMPARATIVO">Comparativo</option>
                                            <option value="DETALLADO">Detallado por Empresa</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Generar</button>
                                        <button type="button" class="btn btn-success">&#128190; Exportar Excel</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Estado de Resultados Consolidado</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_reporte">
                                    <input type="hidden" name="tipo_reporte" value="RESULTADOS">

                                    <div class="form-group">
                                        <label>Consolidacion</label>
                                        <select name="consolidacion_id" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Formato</label>
                                        <select name="formato" required>
                                            <option value="">Seleccione...</option>
                                            <option value="FUNCION">Por Funcion</option>
                                            <option value="NATURALEZA">Por Naturaleza</option>
                                            <option value="COMPARATIVO">Comparativo</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Generar</button>
                                        <button type="button" class="btn btn-success">&#128190; Exportar Excel</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Flujo de Caja Consolidado</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_reporte">
                                    <input type="hidden" name="tipo_reporte" value="FLUJO_CAJA">

                                    <div class="form-group">
                                        <label>Consolidacion</label>
                                        <select name="consolidacion_id" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Metodo</label>
                                        <select name="metodo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="DIRECTO">Metodo Directo</option>
                                            <option value="INDIRECTO">Metodo Indirecto</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Generar</button>
                                        <button type="button" class="btn btn-success">&#128190; Exportar Excel</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Cuenta</th>
                                        <th>Descripcion</th>
                                        <th>Saldo Actual</th>
                                        <th>Saldo Anterior</th>
                                        <th>Variacion</th>
                                        <th>% Variacion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 30px; color: #6c757d;">
                                            Genere un reporte para ver los datos
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Analisis por Segmento -->
                    <div class="subsection">
                        <h4>&#128202; Analisis por Segmento</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Analisis por Region</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_reporte">
                                    <input type="hidden" name="tipo_reporte" value="REGION">

                                    <div class="form-group">
                                        <label>Consolidacion</label>
                                        <select name="consolidacion_id" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Indicador</label>
                                        <select name="indicador" required>
                                            <option value="">Seleccione...</option>
                                            <option value="INGRESOS">Ingresos</option>
                                            <option value="RESULTADO">Resultado</option>
                                            <option value="ACTIVOS">Activos</option>
                                            <option value="TODOS">Todos</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Generar</button>
                                        <button type="button" class="btn btn-success">&#128202; Grafico</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Analisis por Linea de Negocio</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_reporte">
                                    <input type="hidden" name="tipo_reporte" value="NEGOCIO">

                                    <div class="form-group">
                                        <label>Consolidacion</label>
                                        <select name="consolidacion_id" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Linea de Negocio</label>
                                        <select name="linea" required>
                                            <option value="">Seleccione...</option>
                                            <option value="RETAIL">Retail</option>
                                            <option value="INDUSTRIAL">Industrial</option>
                                            <option value="SERVICIOS">Servicios</option>
                                            <option value="TODOS">Todos</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Generar</button>
                                        <button type="button" class="btn btn-success">&#128202; Grafico</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Ingresos Consolidados</div>
                                <div class="kpi-value">$0</div>
                                <div class="kpi-change positive">+0% vs periodo anterior</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">EBITDA Consolidado</div>
                                <div class="kpi-value">$0</div>
                                <div class="kpi-change positive">+0% vs periodo anterior</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Margen EBITDA</div>
                                <div class="kpi-value">0%</div>
                                <div class="kpi-change positive">+0 pp vs periodo anterior</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">ROE Consolidado</div>
                                <div class="kpi-value">0%</div>
                                <div class="kpi-change positive">+0 pp vs periodo anterior</div>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Segmento</th>
                                        <th>Ingresos</th>
                                        <th>Costos</th>
                                        <th>Gastos</th>
                                        <th>Resultado</th>
                                        <th>Margen</th>
                                        <th>% Contribucion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d;">
                                            Genere un analisis para ver los datos
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
