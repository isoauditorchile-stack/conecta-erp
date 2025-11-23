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
        case 'crear_tipo_impuesto':
            // TODO: Insertar en tabla impuestos_tipos
            $mensaje = 'Tipo de impuesto creado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_tasa':
            // TODO: Insertar en tabla impuestos_tasas
            $mensaje = 'Tasa de impuesto creada exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_parametro':
            // TODO: Insertar en tabla impuestos_parametros
            $mensaje = 'Parametro creado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'generar_libro_compras':
            // TODO: Generar libro de compras
            $mensaje = 'Libro de compras generado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'generar_libro_ventas':
            // TODO: Generar libro de ventas
            $mensaje = 'Libro de ventas generado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'generar_formulario':
            // TODO: Generar formulario tributario
            $mensaje = 'Formulario generado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_retencion':
            // TODO: Insertar en tabla impuestos_retenciones
            $mensaje = 'Retencion registrada exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_percepcion':
            // TODO: Insertar en tabla impuestos_percepciones
            $mensaje = 'Percepcion registrada exitosamente';
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
$tipos_impuestos = []; // TODO: SELECT * FROM impuestos_tipos
$tasas = []; // TODO: SELECT * FROM impuestos_tasas
$parametros = []; // TODO: SELECT * FROM impuestos_parametros
$libro_compras = []; // TODO: SELECT * FROM impuestos_libro_compras
$libro_ventas = []; // TODO: SELECT * FROM impuestos_libro_ventas
$retenciones = []; // TODO: SELECT * FROM impuestos_retenciones
$formularios = []; // TODO: SELECT * FROM impuestos_formularios
$declaraciones = []; // TODO: SELECT * FROM impuestos_declaraciones
$cuadres = []; // TODO: SELECT cuadre contable vs fiscal

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IMPUESTOS - ConectaERP</title>
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
            background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);
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
            background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);
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
            color: #b91c1c;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dc2626;
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
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
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
            background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);
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
            background: linear-gradient(135deg, #7c2d12 0%, #b91c1c 100%);
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
            background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);
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

        .status-presentado {
            background-color: #cce5ff;
            color: #004085;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .kpi-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-left: 4px solid #dc2626;
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
            color: #b91c1c;
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
            color: #b91c1c;
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

        .alert-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-box h5 {
            color: #856404;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .alert-box p {
            color: #856404;
            font-size: 14px;
        }

        @media print {
            .btn, .form-group, .action-buttons {
                display: none;
            }

            body {
                background: white;
            }

            .header {
                background: #b91c1c;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            <span class="header-icon">&#128179;</span>
            MODULO IMPUESTOS
        </h1>
    </div>

    <div class="container">
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="tabs-container">

            <!-- TAB 6.1: MAESTROS -->
            <div class="tab-header" onclick="toggleTab('tab1')">
                <h2><span class="tab-icon">&#128218;</span> 6.1 Maestros</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab1">
                <div class="tab-inner">

                    <!-- Tipos de Impuestos -->
                    <div class="subsection">
                        <h4>&#128179; Tipos de Impuestos</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>IVA - Impuesto al Valor Agregado</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_tipo_impuesto">
                                    <input type="hidden" name="tipo" value="IVA">

                                    <div class="form-group">
                                        <label>Codigo IVA</label>
                                        <input type="text" name="codigo" required placeholder="Ej: IVA19">
                                    </div>

                                    <div class="form-group">
                                        <label>Descripcion</label>
                                        <input type="text" name="descripcion" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Tasa (%)</label>
                                        <input type="number" name="tasa" step="0.01" required placeholder="Ej: 19.00">
                                    </div>

                                    <div class="form-group">
                                        <label>Cuenta Contable Debito Fiscal</label>
                                        <input type="text" name="cuenta_debito" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Cuenta Contable Credito Fiscal</label>
                                        <input type="text" name="cuenta_credito" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Aplica en</label>
                                        <select name="aplica_en" required>
                                            <option value="">Seleccione...</option>
                                            <option value="VENTAS">Ventas</option>
                                            <option value="COMPRAS">Compras</option>
                                            <option value="AMBOS">Ventas y Compras</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear IVA</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Retenciones</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_tipo_impuesto">
                                    <input type="hidden" name="tipo" value="RETENCION">

                                    <div class="form-group">
                                        <label>Codigo Retencion</label>
                                        <input type="text" name="codigo" required placeholder="Ej: RET10">
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo Retencion</label>
                                        <select name="tipo_retencion" required>
                                            <option value="">Seleccione...</option>
                                            <option value="HONORARIOS">Honorarios</option>
                                            <option value="DIVIDENDOS">Dividendos</option>
                                            <option value="INTERESES">Intereses</option>
                                            <option value="ARRIENDO">Arriendo</option>
                                            <option value="SERVICIOS">Servicios Profesionales</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Tasa (%)</label>
                                        <input type="number" name="tasa" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Base Calculo</label>
                                        <select name="base_calculo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="BRUTO">Monto Bruto</option>
                                            <option value="NETO">Monto Neto</option>
                                            <option value="AFECTO">Monto Afecto</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Cuenta Contable</label>
                                        <input type="text" name="cuenta_contable" required>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Retencion</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Impuestos Especificos</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_tipo_impuesto">
                                    <input type="hidden" name="tipo" value="ESPECIFICO">

                                    <div class="form-group">
                                        <label>Codigo Impuesto</label>
                                        <input type="text" name="codigo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo Especifico</label>
                                        <select name="tipo_especifico" required>
                                            <option value="">Seleccione...</option>
                                            <option value="ALCOHOLES">Bebidas Alcoholicas</option>
                                            <option value="COMBUSTIBLES">Combustibles</option>
                                            <option value="LUJO">Articulos de Lujo</option>
                                            <option value="TABACO">Tabaco</option>
                                            <option value="OTROS">Otros</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Metodo Calculo</label>
                                        <select name="metodo_calculo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="PORCENTAJE">Porcentaje sobre Valor</option>
                                            <option value="MONTO_FIJO">Monto Fijo por Unidad</option>
                                            <option value="ESCALA">Escala Progresiva</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Valor/Tasa</label>
                                        <input type="number" name="valor" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Cuenta Contable</label>
                                        <input type="text" name="cuenta_contable" required>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Impuesto</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Tasas Locales</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_tipo_impuesto">
                                    <input type="hidden" name="tipo" value="TASA_LOCAL">

                                    <div class="form-group">
                                        <label>Codigo Tasa</label>
                                        <input type="text" name="codigo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo Tasa Local</label>
                                        <select name="tipo_tasa" required>
                                            <option value="">Seleccione...</option>
                                            <option value="PATENTE_MUNICIPAL">Patente Municipal</option>
                                            <option value="TERRITORIAL">Impuesto Territorial</option>
                                            <option value="CIRCULACION">Permiso de Circulacion</option>
                                            <option value="OTROS">Otros</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Municipalidad/Region</label>
                                        <input type="text" name="region" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Tasa Aplicable (%)</label>
                                        <input type="number" name="tasa" step="0.0001" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodicidad</label>
                                        <select name="periodicidad" required>
                                            <option value="">Seleccione...</option>
                                            <option value="MENSUAL">Mensual</option>
                                            <option value="TRIMESTRAL">Trimestral</option>
                                            <option value="SEMESTRAL">Semestral</option>
                                            <option value="ANUAL">Anual</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Tasa</button>
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
                                        <th>Tipo Impuesto</th>
                                        <th>Descripcion</th>
                                        <th>Tasa/Valor</th>
                                        <th>Cuenta Contable</th>
                                        <th>Vigencia</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay tipos de impuestos registrados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Tablas de Tasas y Vigencias Historicas -->
                    <div class="subsection">
                        <h4>&#128197; Tablas de Tasas y Vigencias Historicas</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Registrar Tasa Historica</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_tasa">

                                    <div class="form-group">
                                        <label>Tipo Impuesto</label>
                                        <select name="tipo_impuesto" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Tasa (%)</label>
                                        <input type="number" name="tasa" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Fecha Vigencia Desde</label>
                                        <input type="date" name="fecha_desde" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Fecha Vigencia Hasta</label>
                                        <input type="date" name="fecha_hasta">
                                    </div>

                                    <div class="form-group">
                                        <label>Base Legal</label>
                                        <input type="text" name="base_legal" placeholder="Ej: Ley 21.210">
                                    </div>

                                    <div class="form-group">
                                        <label>Observaciones</label>
                                        <textarea name="observaciones"></textarea>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Registrar Tasa</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tipo Impuesto</th>
                                        <th>Tasa</th>
                                        <th>Vigencia Desde</th>
                                        <th>Vigencia Hasta</th>
                                        <th>Base Legal</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay tasas historicas registradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Parametros por Pais y Region -->
                    <div class="subsection">
                        <h4>&#127757; Parametros por Pais y Region</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Configurar Parametros</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_parametro">

                                    <div class="form-group">
                                        <label>Pais</label>
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
                                        <label>Region/Estado</label>
                                        <input type="text" name="region">
                                    </div>

                                    <div class="form-group">
                                        <label>Parametro</label>
                                        <select name="parametro" required>
                                            <option value="">Seleccione...</option>
                                            <option value="UTA">UTA - Unidad Tributaria Anual</option>
                                            <option value="UTM">UTM - Unidad Tributaria Mensual</option>
                                            <option value="UF">UF - Unidad de Fomento</option>
                                            <option value="TOPE_EXENTO">Tope Exento</option>
                                            <option value="MINIMO_NO_IMPONIBLE">Minimo No Imponible</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Valor</label>
                                        <input type="number" name="valor" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Parametro</button>
                                        <button type="button" class="btn btn-success">&#128259; Importar desde Entidad Oficial</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Pais</th>
                                        <th>Region</th>
                                        <th>Parametro</th>
                                        <th>Valor</th>
                                        <th>Periodo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay parametros configurados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 6.2: PROCESOS -->
            <div class="tab-header" onclick="toggleTab('tab2')">
                <h2><span class="tab-icon">&#9881;</span> 6.2 Procesos</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab2">
                <div class="tab-inner">

                    <!-- Calculo Automatico -->
                    <div class="subsection">
                        <h4>&#128268; Calculo Automatico de Impuestos</h4>

                        <div class="alert-box">
                            <h5>&#9888; Configuracion de Calculos</h5>
                            <p>Los calculos automaticos se ejecutan en tiempo real al registrar documentos en cada modulo. Configure las reglas y validaciones en cada seccion.</p>
                        </div>

                        <div class="section-grid">
                            <div class="card">
                                <h3>&#128176; Calculo en Ventas</h3>
                                <div class="form-group">
                                    <label>Estado</label>
                                    <span class="status-badge status-activo">Activo</span>
                                </div>
                                <div class="form-group">
                                    <label>Impuestos Aplicables</label>
                                    <select name="impuestos_ventas" multiple size="4">
                                        <option value="IVA19">IVA 19%</option>
                                        <option value="RET_HON">Retencion Honorarios</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Validaciones</label>
                                    <label><input type="checkbox" checked> Validar RUT</label><br>
                                    <label><input type="checkbox" checked> Validar Tasa Vigente</label><br>
                                    <label><input type="checkbox"> Aplicar Redondeo</label>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-primary">&#128270; Configurar</button>
                                    <button class="btn btn-warning">&#128203; Ver Log</button>
                                </div>
                            </div>

                            <div class="card">
                                <h3>&#128179; Calculo en Compras</h3>
                                <div class="form-group">
                                    <label>Estado</label>
                                    <span class="status-badge status-activo">Activo</span>
                                </div>
                                <div class="form-group">
                                    <label>Impuestos Aplicables</label>
                                    <select name="impuestos_compras" multiple size="4">
                                        <option value="IVA19">IVA 19%</option>
                                        <option value="RET_10">Retencion 10%</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Validaciones</label>
                                    <label><input type="checkbox" checked> Validar DTE</label><br>
                                    <label><input type="checkbox" checked> Validar en SII</label><br>
                                    <label><input type="checkbox"> Permitir CF Proporcional</label>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-primary">&#128270; Configurar</button>
                                    <button class="btn btn-warning">&#128203; Ver Log</button>
                                </div>
                            </div>

                            <div class="card">
                                <h3>&#128101; Calculo en Nomina</h3>
                                <div class="form-group">
                                    <label>Estado</label>
                                    <span class="status-badge status-activo">Activo</span>
                                </div>
                                <div class="form-group">
                                    <label>Impuestos Aplicables</label>
                                    <select name="impuestos_nomina" multiple size="4">
                                        <option value="RET_2DA">Retencion 2da Categoria</option>
                                        <option value="AFC">AFC</option>
                                        <option value="PREVISION">Prevision</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Validaciones</label>
                                    <label><input type="checkbox" checked> Aplicar Tramos</label><br>
                                    <label><input type="checkbox" checked> Considerar Reliquidaciones</label>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-primary">&#128270; Configurar</button>
                                    <button class="btn btn-warning">&#128203; Ver Log</button>
                                </div>
                            </div>

                            <div class="card">
                                <h3>&#128188; Calculo en Activos</h3>
                                <div class="form-group">
                                    <label>Estado</label>
                                    <span class="status-badge status-activo">Activo</span>
                                </div>
                                <div class="form-group">
                                    <label>Impuestos Aplicables</label>
                                    <select name="impuestos_activos" multiple size="4">
                                        <option value="IVA_CF">IVA Credito Fiscal</option>
                                        <option value="PATENTE">Patentes</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Validaciones</label>
                                    <label><input type="checkbox" checked> Prorrateo IVA</label><br>
                                    <label><input type="checkbox"> CF Diferido</label>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-primary">&#128270; Configurar</button>
                                    <button class="btn btn-warning">&#128203; Ver Log</button>
                                </div>
                            </div>

                            <div class="card">
                                <h3>&#128181; Calculo en Finanzas</h3>
                                <div class="form-group">
                                    <label>Estado</label>
                                    <span class="status-badge status-activo">Activo</span>
                                </div>
                                <div class="form-group">
                                    <label>Impuestos Aplicables</label>
                                    <select name="impuestos_finanzas" multiple size="4">
                                        <option value="RET_INT">Retencion Intereses</option>
                                        <option value="TIMBRES">Timbres y Estampillas</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Validaciones</label>
                                    <label><input type="checkbox" checked> Validar Tasas</label><br>
                                    <label><input type="checkbox"> Diferir Impuestos</label>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-primary">&#128270; Configurar</button>
                                    <button class="btn btn-warning">&#128203; Ver Log</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Libros de Compras y Ventas -->
                    <div class="subsection">
                        <h4>&#128214; Libros de Compras y Ventas</h4>

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">IVA Debito Fiscal Mes</div>
                                <div class="kpi-value">$0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">IVA Credito Fiscal Mes</div>
                                <div class="kpi-value">$0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">IVA a Pagar/Recuperar</div>
                                <div class="kpi-value">$0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Documentos Procesados</div>
                                <div class="kpi-value">0</div>
                            </div>
                        </div>

                        <div class="section-grid">
                            <div class="card">
                                <h3>Generar Libro de Compras</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_libro_compras">

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
                                        <label>Formato</label>
                                        <select name="formato" required>
                                            <option value="">Seleccione...</option>
                                            <option value="DETALLADO">Detallado</option>
                                            <option value="RESUMIDO">Resumido</option>
                                            <option value="SII">Formato SII</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128196; Generar Libro</button>
                                        <button type="button" class="btn btn-success">&#128190; Exportar a SII</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Generar Libro de Ventas</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_libro_ventas">

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
                                        <label>Formato</label>
                                        <select name="formato" required>
                                            <option value="">Seleccione...</option>
                                            <option value="DETALLADO">Detallado</option>
                                            <option value="RESUMIDO">Resumido</option>
                                            <option value="SII">Formato SII</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128196; Generar Libro</button>
                                        <button type="button" class="btn btn-success">&#128190; Exportar a SII</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tipo Doc</th>
                                        <th>Numero</th>
                                        <th>Fecha</th>
                                        <th>RUT</th>
                                        <th>Razon Social</th>
                                        <th>Neto</th>
                                        <th>IVA</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 30px; color: #6c757d;">
                                            Genere un libro para ver los documentos
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Retenciones y Percepciones -->
                    <div class="subsection">
                        <h4>&#128183; Registro de Retenciones y Percepciones</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Registrar Retencion/Percepcion</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_retencion">

                                    <div class="form-group">
                                        <label>Tipo</label>
                                        <select name="tipo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="RETENCION">Retencion</option>
                                            <option value="PERCEPCION">Percepcion</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo Impuesto</label>
                                        <select name="tipo_impuesto" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Documento Origen</label>
                                        <input type="text" name="documento_origen" required>
                                    </div>

                                    <div class="form-group">
                                        <label>RUT Proveedor/Cliente</label>
                                        <input type="text" name="rut" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Razon Social</label>
                                        <input type="text" name="razon_social" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Monto Base</label>
                                        <input type="number" name="monto_base" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Monto Retencion/Percepcion</label>
                                        <input type="number" name="monto" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Fecha</label>
                                        <input type="date" name="fecha" required>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Registrar</button>
                                        <button type="button" class="btn btn-success">&#128196; Emitir Certificado</button>
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
                                        <th>Impuesto</th>
                                        <th>Doc Origen</th>
                                        <th>RUT</th>
                                        <th>Razon Social</th>
                                        <th>Monto Base</th>
                                        <th>Monto Ret/Per</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay retenciones o percepciones registradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Formularios y Declaraciones -->
                    <div class="subsection">
                        <h4>&#128196; Generacion de Formularios y Archivos Electronicos</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Generar Formulario</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_formulario">

                                    <div class="form-group">
                                        <label>Tipo Formulario</label>
                                        <select name="tipo_formulario" required>
                                            <option value="">Seleccione...</option>
                                            <option value="F29">F29 - IVA y Renta Mensual</option>
                                            <option value="F50">F50 - Pagos Provisionales Mensuales</option>
                                            <option value="F22">F22 - Declaracion Renta Anual</option>
                                            <option value="F1887">F1887 - Retencion Honorarios</option>
                                            <option value="F1879">F1879 - Honorarios Percibidos</option>
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
                                        <label>Tipo Generacion</label>
                                        <select name="tipo_generacion" required>
                                            <option value="">Seleccione...</option>
                                            <option value="BORRADOR">Borrador</option>
                                            <option value="PREDECLARACION">Pre-Declaracion</option>
                                            <option value="DEFINITIVA">Definitiva</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128196; Generar Formulario</button>
                                        <button type="button" class="btn btn-success">&#128190; Exportar PDF</button>
                                        <button type="button" class="btn btn-warning">&#128229; Enviar a SII</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Formulario</th>
                                        <th>Periodo</th>
                                        <th>Empresa</th>
                                        <th>Fecha Generacion</th>
                                        <th>Tipo</th>
                                        <th>Monto a Pagar/Favor</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay formularios generados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Cuadre Contable vs Fiscal -->
                    <div class="subsection">
                        <h4>&#128202; Cuadre Contable vs Fiscal</h4>

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Base Contable</div>
                                <div class="kpi-value">$0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Base Tributaria</div>
                                <div class="kpi-value">$0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Diferencia</div>
                                <div class="kpi-value">$0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Ajustes Pendientes</div>
                                <div class="kpi-value">0</div>
                            </div>
                        </div>

                        <div class="section-grid">
                            <div class="card">
                                <h3>Ejecutar Cuadre</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="ejecutar_cuadre">

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo Cuadre</label>
                                        <select name="tipo_cuadre" required>
                                            <option value="">Seleccione...</option>
                                            <option value="IVA">IVA</option>
                                            <option value="RENTA">Impuesto a la Renta</option>
                                            <option value="COMPLETO">Cuadre Completo</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#9654; Ejecutar Cuadre</button>
                                        <button type="button" class="btn btn-success">&#128190; Exportar Reporte</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Concepto</th>
                                        <th>Base Contable</th>
                                        <th>Base Tributaria</th>
                                        <th>Diferencia</th>
                                        <th>Tipo Diferencia</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 30px; color: #6c757d;">
                                            Ejecute un cuadre para ver las diferencias
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Impuesto Diferido -->
                    <div class="subsection">
                        <h4>&#128197; Impuesto Diferido (Base Contable vs Tributaria)</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Calculo Impuesto Diferido</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="calcular_impuesto_diferido">

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo Diferencia</label>
                                        <select name="tipo_diferencia" required>
                                            <option value="">Seleccione...</option>
                                            <option value="TEMPORARIA">Temporaria</option>
                                            <option value="PERMANENTE">Permanente</option>
                                            <option value="AMBAS">Ambas</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Tasa Impuesto (%)</label>
                                        <input type="number" name="tasa" step="0.01" value="27" required>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Calcular</button>
                                        <button type="button" class="btn btn-success">&#128196; Generar Asiento</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Concepto</th>
                                        <th>Tipo</th>
                                        <th>Valor Contable</th>
                                        <th>Valor Tributario</th>
                                        <th>Diferencia</th>
                                        <th>Imp Diferido</th>
                                        <th>Cuenta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d;">
                                            Ejecute el calculo para ver los resultados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 6.3: INTEGRACION -->
            <div class="tab-header" onclick="toggleTab('tab3')">
                <h2><span class="tab-icon">&#128279;</span> 6.3 Integracion</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab3">
                <div class="tab-inner">

                    <div class="subsection">
                        <h4>&#128260; Panel de Integraciones Tributarias</h4>

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Integraciones Activas</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Documentos Sincronizados Hoy</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Asientos Generados</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Errores Pendientes</div>
                                <div class="kpi-value">0</div>
                            </div>
                        </div>

                        <div class="section-grid">
                            <div class="card">
                                <h3>&#128196; Comprobantes y Facturas</h3>
                                <div class="form-group">
                                    <label>Estado Integracion</label>
                                    <span class="status-badge status-inactivo">Inactivo</span>
                                </div>
                                <div class="form-group">
                                    <label>Ultima Sincronizacion</label>
                                    <input type="text" value="Nunca" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Documentos Procesados</label>
                                    <input type="text" value="0" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Modo Integracion</label>
                                    <select name="modo_comprobantes">
                                        <option value="">Seleccione...</option>
                                        <option value="TIEMPO_REAL">Tiempo Real</option>
                                        <option value="BATCH">Lotes (Batch)</option>
                                        <option value="MANUAL">Manual</option>
                                    </select>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-success">&#9654; Sincronizar</button>
                                    <button class="btn btn-primary">&#128270; Configurar</button>
                                </div>
                            </div>

                            <div class="card">
                                <h3>&#128218; Contabilidad</h3>
                                <div class="form-group">
                                    <label>Estado Integracion</label>
                                    <span class="status-badge status-inactivo">Inactivo</span>
                                </div>
                                <div class="form-group">
                                    <label>Ultima Sincronizacion</label>
                                    <input type="text" value="Nunca" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Asientos Generados</label>
                                    <input type="text" value="0" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Plantillas Activas</label>
                                    <select name="plantillas_contabilidad" multiple size="3">
                                        <option>IVA Debito Fiscal</option>
                                        <option>IVA Credito Fiscal</option>
                                        <option>Retenciones</option>
                                    </select>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-success">&#9654; Sincronizar</button>
                                    <button class="btn btn-primary">&#128270; Configurar</button>
                                </div>
                            </div>

                            <div class="card">
                                <h3>&#128181; Tesoreria</h3>
                                <div class="form-group">
                                    <label>Estado Integracion</label>
                                    <span class="status-badge status-inactivo">Inactivo</span>
                                </div>
                                <div class="form-group">
                                    <label>Ultima Sincronizacion</label>
                                    <input type="text" value="Nunca" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Pagos de Declaraciones</label>
                                    <input type="text" value="0" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Integracion con</label>
                                    <select name="integracion_tesoreria">
                                        <option value="">Seleccione...</option>
                                        <option value="F29">Pagos F29</option>
                                        <option value="F50">Pagos PPM</option>
                                        <option value="TODOS">Todos los Formularios</option>
                                    </select>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-success">&#9654; Sincronizar</button>
                                    <button class="btn btn-primary">&#128270; Configurar</button>
                                </div>
                            </div>

                            <div class="card">
                                <h3>&#128200; IFRS</h3>
                                <div class="form-group">
                                    <label>Estado Integracion</label>
                                    <span class="status-badge status-inactivo">Inactivo</span>
                                </div>
                                <div class="form-group">
                                    <label>Ultima Sincronizacion</label>
                                    <input type="text" value="Nunca" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Diferidos Calculados</label>
                                    <input type="text" value="0" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Tipo Integracion</label>
                                    <select name="tipo_integracion_ifrs">
                                        <option value="">Seleccione...</option>
                                        <option value="DIFERIDOS">Impuestos Diferidos</option>
                                        <option value="CORRIENTES">Impuestos Corrientes</option>
                                        <option value="AMBOS">Ambos</option>
                                    </select>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-success">&#9654; Sincronizar</button>
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
                                        <th>Modulo Origen</th>
                                        <th>Modulo Destino</th>
                                        <th>Tipo Operacion</th>
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
                                            No hay sincronizaciones registradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 6.4: CONTROLES -->
            <div class="tab-header" onclick="toggleTab('tab4')">
                <h2><span class="tab-icon">&#128274;</span> 6.4 Controles</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab4">
                <div class="tab-inner">

                    <!-- Validaciones -->
                    <div class="subsection">
                        <h4>&#9989; Validacion de Tasas segun Tipo de Operacion</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Configurar Validaciones</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="configurar_validaciones">

                                    <div class="form-group">
                                        <label>Validaciones Activas</label>
                                        <label><input type="checkbox" checked> Validar tasa segun tipo documento</label><br>
                                        <label><input type="checkbox" checked> Validar vigencia de tasa</label><br>
                                        <label><input type="checkbox" checked> Validar RUT en SII</label><br>
                                        <label><input type="checkbox"> Validar DTE en tiempo real</label><br>
                                        <label><input type="checkbox"> Bloquear si tasa incorrecta</label><br>
                                        <label><input type="checkbox" checked> Alertar si difiere de historico</label>
                                    </div>

                                    <div class="form-group">
                                        <label>Nivel Validacion</label>
                                        <select name="nivel_validacion" required>
                                            <option value="">Seleccione...</option>
                                            <option value="BASICO">Basico</option>
                                            <option value="INTERMEDIO">Intermedio</option>
                                            <option value="ESTRICTO">Estricto</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Guardar</button>
                                        <button type="button" class="btn btn-warning">&#128270; Probar Validaciones</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Documento</th>
                                        <th>Tipo Operacion</th>
                                        <th>Tasa Aplicada</th>
                                        <th>Tasa Esperada</th>
                                        <th>Validacion</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay validaciones registradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Alertas -->
                    <div class="subsection">
                        <h4>&#128276; Alertas por Desalineacion con Montos Contables</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Configurar Alertas</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="configurar_alertas">

                                    <div class="form-group">
                                        <label>Tipos de Alerta</label>
                                        <label><input type="checkbox" checked> Diferencia entre IVA calculado y contabilizado</label><br>
                                        <label><input type="checkbox" checked> Retenciones no cuadran con base</label><br>
                                        <label><input type="checkbox"> Diferencia libro compras vs contabilidad</label><br>
                                        <label><input type="checkbox"> Diferencia libro ventas vs contabilidad</label><br>
                                        <label><input type="checkbox" checked> IVA no recuperable sin justificacion</label>
                                    </div>

                                    <div class="form-group">
                                        <label>Tolerancia Maxima ($)</label>
                                        <input type="number" name="tolerancia" value="100" step="1">
                                    </div>

                                    <div class="form-group">
                                        <label>Notificar a</label>
                                        <input type="email" name="email_alertas" placeholder="correo@ejemplo.com">
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Guardar</button>
                                        <button type="button" class="btn btn-success">&#9654; Ejecutar Revision</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo Alerta</th>
                                        <th>Documento</th>
                                        <th>Monto Calculado</th>
                                        <th>Monto Contable</th>
                                        <th>Diferencia</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay alertas generadas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Historial de Declaraciones -->
                    <div class="subsection">
                        <h4>&#128209; Historial de Declaraciones</h4>

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Declaraciones Presentadas</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Rectificativas</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Pendientes Pago</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Monto Pendiente</div>
                                <div class="kpi-value">$0</div>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Formulario</th>
                                        <th>Periodo</th>
                                        <th>Fecha Presentacion</th>
                                        <th>Folio SII</th>
                                        <th>Monto</th>
                                        <th>Fecha Pago</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay declaraciones en el historial
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Historial de Rectificativas -->
                    <div class="subsection">
                        <h4>&#128221; Historial de Rectificativas</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Generar Rectificativa</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_rectificativa">

                                    <div class="form-group">
                                        <label>Declaracion Original</label>
                                        <select name="declaracion_original" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Motivo Rectificacion</label>
                                        <select name="motivo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="ERROR_CALCULO">Error de Calculo</option>
                                            <option value="DOCUMENTO_OMITIDO">Documento Omitido</option>
                                            <option value="DOCUMENTO_ERRONEO">Documento Erroneo</option>
                                            <option value="CAMBIO_CRITERIO">Cambio de Criterio</option>
                                            <option value="OTRO">Otro</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Descripcion Detallada</label>
                                        <textarea name="descripcion" required></textarea>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128196; Generar Rectificativa</button>
                                        <button type="button" class="btn btn-warning">&#128269; Simular Impacto</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Formulario</th>
                                        <th>Periodo</th>
                                        <th>Declaracion Original</th>
                                        <th>Fecha Rectificativa</th>
                                        <th>Motivo</th>
                                        <th>Monto Original</th>
                                        <th>Monto Rectificado</th>
                                        <th>Diferencia</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay rectificativas en el historial
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
