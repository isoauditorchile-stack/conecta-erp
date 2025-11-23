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
        case 'generar_estado':
            // TODO: Generar estado financiero
            $mensaje = 'Estado financiero generado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_dashboard':
            // TODO: Crear dashboard personalizado
            $mensaje = 'Dashboard creado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'calcular_ratios':
            // TODO: Calcular ratios financieros
            $mensaje = 'Ratios calculados exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'generar_comparativo':
            // TODO: Generar reporte comparativo
            $mensaje = 'Comparativo generado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'programar_envio':
            // TODO: Programar envio automatico
            $mensaje = 'Envio programado exitosamente';
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
$estados = []; // TODO: SELECT * FROM reporting_estados
$dashboards = []; // TODO: SELECT * FROM reporting_dashboards
$ratios = []; // TODO: SELECT * FROM reporting_ratios
$comparativos = []; // TODO: SELECT * FROM reporting_comparativos
$programaciones = []; // TODO: SELECT * FROM reporting_programaciones

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REPORTING FINANCIERO - ConectaERP</title>
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
            background: linear-gradient(135deg, #047857 0%, #10b981 100%);
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
            background: linear-gradient(135deg, #047857 0%, #10b981 100%);
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
            max-height: 8000px;
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
            color: #047857;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #10b981;
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
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
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
            background: linear-gradient(135deg, #047857 0%, #10b981 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #15803d 0%, #22c55e 100%);
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
            background: linear-gradient(135deg, #047857 0%, #10b981 100%);
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

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .kpi-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-left: 4px solid #10b981;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .kpi-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
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
            color: #047857;
        }

        .kpi-change {
            font-size: 12px;
            margin-top: 8px;
            font-weight: 500;
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
            color: #047857;
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

        .chart-placeholder {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 6px;
            padding: 40px;
            text-align: center;
            color: #6c757d;
            margin: 20px 0;
        }

        @media print {
            .btn, .form-group, .action-buttons {
                display: none;
            }

            body {
                background: white;
            }

            .header {
                background: #047857;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            <span class="header-icon">&#128202;</span>
            MODULO REPORTING FINANCIERO
        </h1>
    </div>

    <div class="container">
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="tabs-container">

            <!-- TAB 9.1: MODELOS DE REPORTE -->
            <div class="tab-header" onclick="toggleTab('tab1')">
                <h2><span class="tab-icon">&#128196;</span> 9.1 Modelos de Reporte</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab1">
                <div class="tab-inner">

                    <!-- Estados Legales -->
                    <div class="subsection">
                        <h4>&#128221; Estados Legales</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Generar Estados Legales</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_estado">
                                    <input type="hidden" name="tipo_estado" value="LEGAL">

                                    <div class="form-group">
                                        <label>Tipo Estado</label>
                                        <select name="tipo_legal" required>
                                            <option value="">Seleccione...</option>
                                            <option value="BALANCE">Balance General</option>
                                            <option value="RESULTADOS">Estado de Resultados</option>
                                            <option value="FLUJO_EFECTIVO">Flujo de Efectivo</option>
                                            <option value="CAMBIOS_PATRIMONIO">Cambios en el Patrimonio</option>
                                            <option value="COMPLETO">Estados Completos</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Empresa</label>
                                        <select name="empresa" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Normativa</label>
                                        <select name="normativa" required>
                                            <option value="">Seleccione...</option>
                                            <option value="LOCAL">GAAP Local</option>
                                            <option value="IFRS">IFRS</option>
                                            <option value="TRIBUTARIA">Tributaria</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Formato</label>
                                        <select name="formato" required>
                                            <option value="">Seleccione...</option>
                                            <option value="OFICIAL">Formato Oficial</option>
                                            <option value="SIMPLIFICADO">Simplificado</option>
                                            <option value="COMPARATIVO">Comparativo</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Generar</button>
                                        <button type="button" class="btn btn-success">&#128190; Exportar PDF</button>
                                        <button type="button" class="btn btn-warning">&#128190; Exportar Excel</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Estados de Gestion -->
                    <div class="subsection">
                        <h4>&#128200; Estados de Gestion</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Por Negocio</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_estado">
                                    <input type="hidden" name="tipo_estado" value="NEGOCIO">

                                    <div class="form-group">
                                        <label>Unidad de Negocio</label>
                                        <select name="unidad_negocio" required>
                                            <option value="">Seleccione...</option>
                                            <option value="TODAS">Todas las Unidades</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Nivel Detalle</label>
                                        <select name="detalle" required>
                                            <option value="">Seleccione...</option>
                                            <option value="RESUMEN">Resumen</option>
                                            <option value="DETALLADO">Detallado</option>
                                            <option value="ANALITICO">Analitico</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Generar</button>
                                        <button type="button" class="btn btn-success">&#128202; Grafico</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Por Producto</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_estado">
                                    <input type="hidden" name="tipo_estado" value="PRODUCTO">

                                    <div class="form-group">
                                        <label>Producto/Linea</label>
                                        <select name="producto" required>
                                            <option value="">Seleccione...</option>
                                            <option value="TODOS">Todos los Productos</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Metricas</label>
                                        <select name="metricas" multiple size="4">
                                            <option value="VENTAS">Ventas</option>
                                            <option value="MARGEN">Margen</option>
                                            <option value="RENTABILIDAD">Rentabilidad</option>
                                            <option value="PARTICIPACION">Participacion</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Generar</button>
                                        <button type="button" class="btn btn-success">&#128202; Grafico</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Por Canal</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_estado">
                                    <input type="hidden" name="tipo_estado" value="CANAL">

                                    <div class="form-group">
                                        <label>Canal</label>
                                        <select name="canal" required>
                                            <option value="">Seleccione...</option>
                                            <option value="TODOS">Todos los Canales</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Analisis</label>
                                        <select name="analisis" required>
                                            <option value="">Seleccione...</option>
                                            <option value="VENTAS">Ventas por Canal</option>
                                            <option value="COSTOS">Costos por Canal</option>
                                            <option value="RENTABILIDAD">Rentabilidad por Canal</option>
                                            <option value="COMPARATIVO">Comparativo entre Canales</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Generar</button>
                                        <button type="button" class="btn btn-success">&#128202; Grafico</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Dashboards -->
                    <div class="subsection">
                        <h4>&#128202; Cuadros de Mando (Dashboards) en Tiempo Real</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Crear Dashboard Personalizado</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_dashboard">

                                    <div class="form-group">
                                        <label>Nombre Dashboard</label>
                                        <input type="text" name="nombre" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo</label>
                                        <select name="tipo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="EJECUTIVO">Ejecutivo</option>
                                            <option value="OPERACIONAL">Operacional</option>
                                            <option value="FINANCIERO">Financiero</option>
                                            <option value="COMERCIAL">Comercial</option>
                                            <option value="PERSONALIZADO">Personalizado</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>KPIs a Incluir</label>
                                        <select name="kpis" multiple size="6">
                                            <option value="VENTAS">Ventas</option>
                                            <option value="MARGEN">Margen</option>
                                            <option value="LIQUIDEZ">Liquidez</option>
                                            <option value="ENDEUDAMIENTO">Endeudamiento</option>
                                            <option value="RENTABILIDAD">Rentabilidad</option>
                                            <option value="ROTACION">Rotacion</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Frecuencia Actualizacion</label>
                                        <select name="frecuencia" required>
                                            <option value="">Seleccione...</option>
                                            <option value="TIEMPO_REAL">Tiempo Real</option>
                                            <option value="HORARIA">Cada Hora</option>
                                            <option value="DIARIA">Diaria</option>
                                            <option value="MANUAL">Manual</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Dashboard</button>
                                        <button type="button" class="btn btn-success">&#128065; Vista Previa</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Ingresos del Mes</div>
                                <div class="kpi-value">$0</div>
                                <div class="kpi-change positive">+0% vs mes anterior</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Margen Bruto</div>
                                <div class="kpi-value">0%</div>
                                <div class="kpi-change positive">+0 pp vs mes anterior</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">EBITDA</div>
                                <div class="kpi-value">$0</div>
                                <div class="kpi-change positive">+0% vs mes anterior</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Margen EBITDA</div>
                                <div class="kpi-value">0%</div>
                                <div class="kpi-change positive">+0 pp vs mes anterior</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Liquidez Corriente</div>
                                <div class="kpi-value">0.00</div>
                                <div class="kpi-change positive">Saludable</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">ROE</div>
                                <div class="kpi-value">0%</div>
                                <div class="kpi-change positive">+0 pp vs ano anterior</div>
                            </div>
                        </div>

                        <div class="chart-placeholder">
                            <h3>&#128200; Graficos de Tendencia</h3>
                            <p>Los graficos se generaran automaticamente al seleccionar un dashboard</p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 9.2: CONTENIDO -->
            <div class="tab-header" onclick="toggleTab('tab2')">
                <h2><span class="tab-icon">&#128202;</span> 9.2 Contenido</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab2">
                <div class="tab-inner">

                    <!-- Ratios Financieros -->
                    <div class="subsection">
                        <h4>&#128200; Ratios Financieros</h4>

                        <div class="section-grid">
                            <div class="card">
                                <h3>Calcular Ratios</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="calcular_ratios">

                                    <div class="form-group">
                                        <label>Categoria Ratios</label>
                                        <select name="categoria" required>
                                            <option value="">Seleccione...</option>
                                            <option value="LIQUIDEZ">Ratios de Liquidez</option>
                                            <option value="ENDEUDAMIENTO">Ratios de Endeudamiento</option>
                                            <option value="RENTABILIDAD">Ratios de Rentabilidad</option>
                                            <option value="ROTACION">Ratios de Rotacion</option>
                                            <option value="TODOS">Todos los Ratios</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Empresa</label>
                                        <select name="empresa" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Calcular</button>
                                        <button type="button" class="btn btn-success">&#128202; Ver Tendencia</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Liquidez Corriente</div>
                                <div class="kpi-value">0.00</div>
                                <small style="color: #6c757d;">Activo Corriente / Pasivo Corriente</small>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Prueba Acida</div>
                                <div class="kpi-value">0.00</div>
                                <small style="color: #6c757d;">(AC - Inventarios) / PC</small>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Razon de Efectivo</div>
                                <div class="kpi-value">0.00</div>
                                <small style="color: #6c757d;">Efectivo / Pasivo Corriente</small>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Capital de Trabajo</div>
                                <div class="kpi-value">$0</div>
                                <small style="color: #6c757d;">Activo Corriente - Pasivo Corriente</small>
                            </div>
                        </div>

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Endeudamiento Total</div>
                                <div class="kpi-value">0%</div>
                                <small style="color: #6c757d;">Pasivo Total / Activo Total</small>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Razon Deuda Patrimonio</div>
                                <div class="kpi-value">0.00</div>
                                <small style="color: #6c757d;">Pasivo Total / Patrimonio</small>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Cobertura de Intereses</div>
                                <div class="kpi-value">0.00</div>
                                <small style="color: #6c757d;">EBIT / Gastos Financieros</small>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Apalancamiento Financiero</div>
                                <div class="kpi-value">0.00</div>
                                <small style="color: #6c757d;">Activo Total / Patrimonio</small>
                            </div>
                        </div>

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">ROE (Return on Equity)</div>
                                <div class="kpi-value">0%</div>
                                <small style="color: #6c757d;">Utilidad Neta / Patrimonio</small>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">ROA (Return on Assets)</div>
                                <div class="kpi-value">0%</div>
                                <small style="color: #6c757d;">Utilidad Neta / Activo Total</small>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Margen Neto</div>
                                <div class="kpi-value">0%</div>
                                <small style="color: #6c757d;">Utilidad Neta / Ventas</small>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Margen Operacional</div>
                                <div class="kpi-value">0%</div>
                                <small style="color: #6c757d;">Utilidad Operacional / Ventas</small>
                            </div>
                        </div>

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Rotacion de Inventarios</div>
                                <div class="kpi-value">0.00</div>
                                <small style="color: #6c757d;">Costo Ventas / Inventario Promedio</small>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Dias de Inventario</div>
                                <div class="kpi-value">0</div>
                                <small style="color: #6c757d;">365 / Rotacion Inventarios</small>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Rotacion CxC</div>
                                <div class="kpi-value">0.00</div>
                                <small style="color: #6c757d;">Ventas / CxC Promedio</small>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Dias de Cobro</div>
                                <div class="kpi-value">0</div>
                                <small style="color: #6c757d;">365 / Rotacion CxC</small>
                            </div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Comparativos -->
                    <div class="subsection">
                        <h4>&#128200; Analisis Comparativos</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Actual vs Presupuesto</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_comparativo">
                                    <input type="hidden" name="tipo" value="ACTUAL_PRESUPUESTO">

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Version Presupuesto</label>
                                        <select name="version" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Nivel Analisis</label>
                                        <select name="nivel" required>
                                            <option value="">Seleccione...</option>
                                            <option value="RESUMEN">Resumen</option>
                                            <option value="CUENTA">Por Cuenta</option>
                                            <option value="CENTRO_COSTO">Por Centro Costo</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Generar</button>
                                        <button type="button" class="btn btn-success">&#128202; Grafico Varianza</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Actual vs Ano Anterior</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_comparativo">
                                    <input type="hidden" name="tipo" value="ACTUAL_ANO_ANTERIOR">

                                    <div class="form-group">
                                        <label>Periodo Actual</label>
                                        <input type="month" name="periodo_actual" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Indicador</label>
                                        <select name="indicador" required>
                                            <option value="">Seleccione...</option>
                                            <option value="VENTAS">Ventas</option>
                                            <option value="COSTOS">Costos</option>
                                            <option value="GASTOS">Gastos</option>
                                            <option value="UTILIDAD">Utilidad</option>
                                            <option value="TODOS">Todos</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo Analisis</label>
                                        <select name="analisis" required>
                                            <option value="">Seleccione...</option>
                                            <option value="MENSUAL">Mensual</option>
                                            <option value="ACUMULADO">Acumulado</option>
                                            <option value="AMBOS">Ambos</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Generar</button>
                                        <button type="button" class="btn btn-success">&#128202; Grafico Tendencia</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Local vs IFRS</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_comparativo">
                                    <input type="hidden" name="tipo" value="LOCAL_IFRS">

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Estado Financiero</label>
                                        <select name="estado" required>
                                            <option value="">Seleccione...</option>
                                            <option value="BALANCE">Balance</option>
                                            <option value="RESULTADOS">Resultados</option>
                                            <option value="PATRIMONIO">Patrimonio</option>
                                            <option value="TODOS">Todos</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Generar</button>
                                        <button type="button" class="btn btn-warning">&#128203; Conciliacion</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Local vs Consolidado</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_comparativo">
                                    <input type="hidden" name="tipo" value="LOCAL_CONSOLIDADO">

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Empresa</label>
                                        <select name="empresa" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Grupo Economico</label>
                                        <select name="grupo" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Generar</button>
                                        <button type="button" class="btn btn-success">&#128202; Grafico</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 9.3: FUNCIONALIDADES -->
            <div class="tab-header" onclick="toggleTab('tab3')">
                <h2><span class="tab-icon">&#9881;</span> 9.3 Funcionalidades</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab3">
                <div class="tab-inner">

                    <!-- Drill-Down -->
                    <div class="subsection">
                        <h4>&#128269; Drill-Down desde Resumen hasta Asiento/Documento Origen</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Configuracion Drill-Down</h3>
                                <div class="form-group">
                                    <label>Niveles de Navegacion</label>
                                    <select name="niveles" multiple size="6">
                                        <option value="RESUMEN" selected>1. Resumen Ejecutivo</option>
                                        <option value="CUENTA" selected>2. Cuenta Contable</option>
                                        <option value="SUBCUENTA" selected>3. Subcuenta</option>
                                        <option value="ASIENTO" selected>4. Asiento Contable</option>
                                        <option value="DOCUMENTO" selected>5. Documento Fuente</option>
                                        <option value="ORIGEN" selected>6. Origen Transaccion</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Habilitado</label>
                                    <span class="status-badge status-activo">Activo</span>
                                </div>

                                <div class="btn-group">
                                    <button class="btn btn-success">&#128269; Probar Drill-Down</button>
                                </div>
                            </div>
                        </div>

                        <div class="chart-placeholder">
                            <h3>&#128200; Ejemplo de Drill-Down</h3>
                            <p>Click en cualquier valor del reporte para navegar hacia el detalle</p>
                            <p><strong>Resumen</strong> &#8594; <strong>Cuenta</strong> &#8594; <strong>Asiento</strong> &#8594; <strong>Documento</strong></p>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Filtros -->
                    <div class="subsection">
                        <h4>&#128269; Filtros Multidimensionales</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Aplicar Filtros</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="aplicar_filtros">

                                    <div class="form-group">
                                        <label>Empresa</label>
                                        <select name="empresa" multiple size="3">
                                            <option value="TODAS">Todas las Empresas</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Unidad de Negocio</label>
                                        <select name="unidad" multiple size="3">
                                            <option value="TODAS">Todas las Unidades</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Centro de Costo</label>
                                        <select name="centro_costo" multiple size="3">
                                            <option value="TODOS">Todos los Centros</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Proyecto</label>
                                        <select name="proyecto" multiple size="3">
                                            <option value="TODOS">Todos los Proyectos</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo_desde" placeholder="Desde"> -
                                        <input type="month" name="periodo_hasta" placeholder="Hasta">
                                    </div>

                                    <div class="form-group">
                                        <label>Moneda</label>
                                        <select name="moneda">
                                            <option value="">Seleccione...</option>
                                            <option value="CLP">CLP</option>
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128269; Aplicar Filtros</button>
                                        <button type="button" class="btn btn-warning">&#10006; Limpiar Filtros</button>
                                        <button type="button" class="btn btn-success">&#128190; Guardar Vista</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Programacion de Envios -->
                    <div class="subsection">
                        <h4>&#128229; Programacion de Envios Automaticos</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Envio por Email</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="programar_envio">
                                    <input type="hidden" name="tipo_envio" value="EMAIL">

                                    <div class="form-group">
                                        <label>Reporte a Enviar</label>
                                        <select name="reporte" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Destinatarios</label>
                                        <textarea name="destinatarios" placeholder="Ingrese emails separados por coma" required></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Frecuencia</label>
                                        <select name="frecuencia" required>
                                            <option value="">Seleccione...</option>
                                            <option value="DIARIO">Diario</option>
                                            <option value="SEMANAL">Semanal</option>
                                            <option value="MENSUAL">Mensual</option>
                                            <option value="TRIMESTRAL">Trimestral</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Hora Envio</label>
                                        <input type="time" name="hora" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Formato</label>
                                        <select name="formato" required>
                                            <option value="">Seleccione...</option>
                                            <option value="PDF">PDF</option>
                                            <option value="EXCEL">Excel</option>
                                            <option value="AMBOS">Ambos</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Programar</button>
                                        <button type="button" class="btn btn-warning">&#128270; Enviar Prueba</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Envio a Carpeta Compartida</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="programar_envio">
                                    <input type="hidden" name="tipo_envio" value="CARPETA">

                                    <div class="form-group">
                                        <label>Reporte a Generar</label>
                                        <select name="reporte" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Ruta Carpeta</label>
                                        <input type="text" name="ruta" placeholder="Ej: /servidor/reportes/" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Nombre Archivo</label>
                                        <input type="text" name="nombre_archivo" placeholder="Usa {FECHA} para fecha dinamica">
                                    </div>

                                    <div class="form-group">
                                        <label>Frecuencia</label>
                                        <select name="frecuencia" required>
                                            <option value="">Seleccione...</option>
                                            <option value="DIARIO">Diario</option>
                                            <option value="SEMANAL">Semanal</option>
                                            <option value="MENSUAL">Mensual</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Formato</label>
                                        <select name="formato" required>
                                            <option value="">Seleccione...</option>
                                            <option value="PDF">PDF</option>
                                            <option value="EXCEL">Excel</option>
                                            <option value="CSV">CSV</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Programar</button>
                                        <button type="button" class="btn btn-success">&#128270; Verificar Ruta</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Reporte</th>
                                        <th>Tipo Envio</th>
                                        <th>Destino</th>
                                        <th>Frecuencia</th>
                                        <th>Formato</th>
                                        <th>Proximo Envio</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay envios programados
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
