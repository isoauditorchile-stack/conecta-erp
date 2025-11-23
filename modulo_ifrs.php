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
        case 'configurar_libro':
            // TODO: Configurar libro contable
            $mensaje = 'Libro configurado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_ajuste':
            // TODO: Insertar en tabla ifrs_ajustes
            $mensaje = 'Ajuste IFRS creado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_catalogo':
            // TODO: Insertar en tabla ifrs_catalogos
            $mensaje = 'Catalogo de ajustes creado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'crear_asiento_manual':
            // TODO: Insertar asiento manual
            $mensaje = 'Asiento de ajuste creado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'aplicar_plantilla':
            // TODO: Aplicar plantilla de ajustes
            $mensaje = 'Plantilla aplicada exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'tratar_arrendamiento':
            // TODO: Insertar en tabla ifrs_arrendamientos
            $mensaje = 'Arrendamiento registrado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'reconocer_ingreso':
            // TODO: Insertar en tabla ifrs_reconocimiento_ingresos
            $mensaje = 'Reconocimiento de ingreso registrado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'medir_instrumento':
            // TODO: Insertar en tabla ifrs_instrumentos_financieros
            $mensaje = 'Instrumento financiero registrado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'calcular_deterioro':
            // TODO: Calcular deterioro de activos
            $mensaje = 'Deterioro calculado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'generar_comparacion':
            // TODO: Generar comparacion Local vs IFRS
            $mensaje = 'Comparacion generada exitosamente';
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
$libros = []; // TODO: SELECT * FROM ifrs_libros
$ajustes = []; // TODO: SELECT * FROM ifrs_ajustes
$catalogos = []; // TODO: SELECT * FROM ifrs_catalogos
$asientos = []; // TODO: SELECT * FROM ifrs_asientos
$arrendamientos = []; // TODO: SELECT * FROM ifrs_arrendamientos
$ingresos = []; // TODO: SELECT * FROM ifrs_reconocimiento_ingresos
$instrumentos = []; // TODO: SELECT * FROM ifrs_instrumentos_financieros
$deterioros = []; // TODO: SELECT * FROM ifrs_deterioros
$comparaciones = []; // TODO: SELECT comparacion Local vs IFRS

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IFRS - ConectaERP</title>
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
            background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
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
            background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
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
            color: #4338ca;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #6366f1;
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
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
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
            background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
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
            background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
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

        .status-aplicado {
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
            border-left: 4px solid #6366f1;
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
            color: #4338ca;
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
            color: #4338ca;
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

        .info-box {
            background: #e0e7ff;
            border: 1px solid #6366f1;
            border-left: 4px solid #4338ca;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .info-box h5 {
            color: #4338ca;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .info-box p {
            color: #4338ca;
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
                background: #4338ca;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            <span class="header-icon">&#128218;</span>
            MODULO IFRS
        </h1>
    </div>

    <div class="container">
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="tabs-container">

            <!-- TAB 8.1: CONFIGURACION -->
            <div class="tab-header" onclick="toggleTab('tab1')">
                <h2><span class="tab-icon">&#128295;</span> 8.1 Configuracion</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab1">
                <div class="tab-inner">

                    <div class="info-box">
                        <h5>&#9432; Libros Contables IFRS</h5>
                        <p>Configure los diferentes libros contables para gestionar simultaneamente la contabilidad local, IFRS y tributaria. Cada libro mantendra registros independientes segun las normativas correspondientes.</p>
                    </div>

                    <!-- Libros -->
                    <div class="subsection">
                        <h4>&#128210; Configuracion de Libros</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Libro Local (GAAP Local)</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="configurar_libro">
                                    <input type="hidden" name="tipo_libro" value="LOCAL">

                                    <div class="form-group">
                                        <label>Codigo Libro</label>
                                        <input type="text" name="codigo" value="LOCAL" readonly>
                                    </div>

                                    <div class="form-group">
                                        <label>Descripcion</label>
                                        <input type="text" name="descripcion" value="Libro Contable Local (GAAP CL)" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Normativa</label>
                                        <select name="normativa" required>
                                            <option value="">Seleccione...</option>
                                            <option value="GAAP_CL">GAAP Chile</option>
                                            <option value="GAAP_AR">GAAP Argentina</option>
                                            <option value="GAAP_PE">GAAP Peru</option>
                                            <option value="GAAP_CO">GAAP Colombia</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Plan de Cuentas</label>
                                        <select name="plan_cuentas" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Moneda Funcional</label>
                                        <select name="moneda" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CLP">CLP</option>
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Configurar</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Libro IFRS</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="configurar_libro">
                                    <input type="hidden" name="tipo_libro" value="IFRS">

                                    <div class="form-group">
                                        <label>Codigo Libro</label>
                                        <input type="text" name="codigo" value="IFRS" readonly>
                                    </div>

                                    <div class="form-group">
                                        <label>Descripcion</label>
                                        <input type="text" name="descripcion" value="Libro Contable IFRS" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Version IFRS</label>
                                        <select name="version_ifrs" required>
                                            <option value="">Seleccione...</option>
                                            <option value="IFRS_FULL">IFRS Full</option>
                                            <option value="IFRS_PYMES">IFRS para PYMES</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Plan de Cuentas</label>
                                        <select name="plan_cuentas" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Moneda Presentacion</label>
                                        <select name="moneda" required>
                                            <option value="">Seleccione...</option>
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                            <option value="CLP">CLP</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Configurar</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Libro Tributario</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="configurar_libro">
                                    <input type="hidden" name="tipo_libro" value="TRIBUTARIO">

                                    <div class="form-group">
                                        <label>Codigo Libro</label>
                                        <input type="text" name="codigo" value="TRIBUTARIO" readonly>
                                    </div>

                                    <div class="form-group">
                                        <label>Descripcion</label>
                                        <input type="text" name="descripcion" value="Libro Tributario" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Autoridad Fiscal</label>
                                        <select name="autoridad" required>
                                            <option value="">Seleccione...</option>
                                            <option value="SII_CL">SII Chile</option>
                                            <option value="AFIP_AR">AFIP Argentina</option>
                                            <option value="SUNAT_PE">SUNAT Peru</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Plan de Cuentas</label>
                                        <select name="plan_cuentas" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Moneda</label>
                                        <select name="moneda" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CLP">CLP</option>
                                            <option value="USD">USD</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Configurar</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Tipo Libro</th>
                                        <th>Normativa/Version</th>
                                        <th>Plan de Cuentas</th>
                                        <th>Moneda</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay libros configurados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Catalogos de Ajustes IFRS -->
                    <div class="subsection">
                        <h4>&#128195; Catalogo de Ajustes IFRS</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Crear Catalogo de Ajustes</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_catalogo">

                                    <div class="form-group">
                                        <label>Tipo Ajuste</label>
                                        <select name="tipo_ajuste" required>
                                            <option value="">Seleccione...</option>
                                            <option value="INGRESOS">Ingresos (IFRS 15)</option>
                                            <option value="ARRENDAMIENTOS">Arrendamientos (IFRS 16)</option>
                                            <option value="INSTRUMENTOS">Instrumentos Financieros (IFRS 9)</option>
                                            <option value="PROVISIONES">Provisiones (IAS 37)</option>
                                            <option value="IMPAIRMENT">Deterioro de Activos (IAS 36)</option>
                                            <option value="PROPIEDAD_PLANTA">Propiedad, Planta y Equipo (IAS 16)</option>
                                            <option value="INTANGIBLES">Activos Intangibles (IAS 38)</option>
                                            <option value="INVENTARIOS">Inventarios (IAS 2)</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Codigo Ajuste</label>
                                        <input type="text" name="codigo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Nombre Ajuste</label>
                                        <input type="text" name="nombre" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Descripcion</label>
                                        <textarea name="descripcion" required></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Norma Aplicable</label>
                                        <input type="text" name="norma" placeholder="Ej: IFRS 15, IAS 36">
                                    </div>

                                    <div class="form-group">
                                        <label>Frecuencia Aplicacion</label>
                                        <select name="frecuencia" required>
                                            <option value="">Seleccione...</option>
                                            <option value="MENSUAL">Mensual</option>
                                            <option value="TRIMESTRAL">Trimestral</option>
                                            <option value="ANUAL">Anual</option>
                                            <option value="EVENTUAL">Eventual</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Catalogo</button>
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
                                        <th>Tipo</th>
                                        <th>Nombre</th>
                                        <th>Norma</th>
                                        <th>Frecuencia</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay catalogos de ajustes registrados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 8.2: PROCESOS -->
            <div class="tab-header" onclick="toggleTab('tab2')">
                <h2><span class="tab-icon">&#9881;</span> 8.2 Procesos</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab2">
                <div class="tab-inner">

                    <!-- Asientos de Ajuste -->
                    <div class="subsection">
                        <h4>&#128221; Asientos de Ajuste IFRS</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Asiento Manual</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="crear_asiento_manual">

                                    <div class="form-group">
                                        <label>Catalogo Ajuste</label>
                                        <select name="catalogo_id" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Glosa</label>
                                        <textarea name="glosa" required></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Cuenta Debito</label>
                                        <input type="text" name="cuenta_debito" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Monto Debito</label>
                                        <input type="number" name="monto_debito" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Cuenta Credito</label>
                                        <input type="text" name="cuenta_credito" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Monto Credito</label>
                                        <input type="number" name="monto_credito" step="0.01" required>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Crear Asiento</button>
                                        <button type="button" class="btn btn-success">&#128196; Vista Previa</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Aplicar Plantilla</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="aplicar_plantilla">

                                    <div class="form-group">
                                        <label>Plantilla</label>
                                        <select name="plantilla_id" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Parametros</label>
                                        <textarea name="parametros" placeholder="JSON con parametros variables"></textarea>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#9654; Aplicar Plantilla</button>
                                        <button type="button" class="btn btn-warning">&#128270; Simular</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Numero</th>
                                        <th>Tipo</th>
                                        <th>Catalogo</th>
                                        <th>Glosa</th>
                                        <th>Debito</th>
                                        <th>Credito</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay asientos de ajuste registrados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Tratamiento de Arrendamientos -->
                    <div class="subsection">
                        <h4>&#127970; Tratamiento de Arrendamientos (IFRS 16)</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Registrar Arrendamiento</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="tratar_arrendamiento">

                                    <div class="form-group">
                                        <label>Tipo Arrendamiento</label>
                                        <select name="tipo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="FINANCIERO">Arrendamiento Financiero</option>
                                            <option value="OPERATIVO">Arrendamiento Operativo</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Codigo Contrato</label>
                                        <input type="text" name="codigo_contrato" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Descripcion Activo</label>
                                        <input type="text" name="descripcion" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Fecha Inicio</label>
                                        <input type="date" name="fecha_inicio" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Plazo (meses)</label>
                                        <input type="number" name="plazo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Pago Mensual</label>
                                        <input type="number" name="pago_mensual" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Tasa Interes Incremental (%)</label>
                                        <input type="number" name="tasa_interes" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Valor Presente Pagos</label>
                                        <input type="number" name="valor_presente" step="0.01" readonly>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Registrar</button>
                                        <button type="button" class="btn btn-success">&#128200; Calcular VP</button>
                                        <button type="button" class="btn btn-warning">&#128196; Tabla Amortizacion</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Contrato</th>
                                        <th>Tipo</th>
                                        <th>Activo</th>
                                        <th>Inicio</th>
                                        <th>Plazo</th>
                                        <th>Pago Mensual</th>
                                        <th>Valor Presente</th>
                                        <th>Saldo Pasivo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay arrendamientos registrados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Reconocimiento de Ingresos -->
                    <div class="subsection">
                        <h4>&#128176; Reconocimiento de Ingresos (IFRS 15)</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Contrato con Cliente</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="reconocer_ingreso">

                                    <div class="form-group">
                                        <label>Numero Contrato</label>
                                        <input type="text" name="numero_contrato" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Cliente</label>
                                        <input type="text" name="cliente" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Precio Transaccion</label>
                                        <input type="number" name="precio" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Metodo Reconocimiento</label>
                                        <select name="metodo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="TIEMPO">A lo Largo del Tiempo</option>
                                            <option value="PUNTO">En un Punto del Tiempo</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Criterio Medicion Progreso</label>
                                        <select name="criterio">
                                            <option value="">Seleccione...</option>
                                            <option value="COSTOS">Costos Incurridos</option>
                                            <option value="RECURSOS">Recursos Consumidos</option>
                                            <option value="TIEMPO">Tiempo Transcurrido</option>
                                            <option value="UNIDADES">Unidades Producidas</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo Inicio</label>
                                        <input type="date" name="periodo_inicio" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo Fin</label>
                                        <input type="date" name="periodo_fin">
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Registrar</button>
                                        <button type="button" class="btn btn-success">&#128200; Calcular Avance</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Contrato</th>
                                        <th>Cliente</th>
                                        <th>Precio</th>
                                        <th>Metodo</th>
                                        <th>% Avance</th>
                                        <th>Ingreso Reconocido</th>
                                        <th>Pendiente</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay contratos con reconocimiento de ingresos
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Instrumentos Financieros -->
                    <div class="subsection">
                        <h4>&#128202; Medicion de Instrumentos Financieros (IFRS 9)</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Registrar Instrumento</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="medir_instrumento">

                                    <div class="form-group">
                                        <label>Tipo Instrumento</label>
                                        <select name="tipo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="ACTIVO_FINANCIERO">Activo Financiero</option>
                                            <option value="PASIVO_FINANCIERO">Pasivo Financiero</option>
                                            <option value="DERIVADO">Derivado</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Clasificacion</label>
                                        <select name="clasificacion" required>
                                            <option value="">Seleccione...</option>
                                            <option value="COSTO_AMORTIZADO">Costo Amortizado</option>
                                            <option value="VALOR_RAZONABLE_ORI">Valor Razonable con cambios en ORI</option>
                                            <option value="VALOR_RAZONABLE_RESULTADO">Valor Razonable con cambios en Resultado</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Descripcion</label>
                                        <input type="text" name="descripcion" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Valor Inicial</label>
                                        <input type="number" name="valor_inicial" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Fecha Medicion</label>
                                        <input type="date" name="fecha_medicion" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Valor Razonable</label>
                                        <input type="number" name="valor_razonable" step="0.01">
                                    </div>

                                    <div class="form-group">
                                        <label>Modelo Valoracion</label>
                                        <select name="modelo">
                                            <option value="">Seleccione...</option>
                                            <option value="MERCADO">Precio de Mercado</option>
                                            <option value="DCF">Flujos Descontados</option>
                                            <option value="BLACK_SCHOLES">Black-Scholes</option>
                                            <option value="OTRO">Otro Modelo</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Registrar</button>
                                        <button type="button" class="btn btn-success">&#128200; Calcular Valor Razonable</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Clasificacion</th>
                                        <th>Descripcion</th>
                                        <th>Valor Inicial</th>
                                        <th>Valor Actual</th>
                                        <th>Ganancia/Perdida</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay instrumentos financieros registrados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Deterioro de Activos -->
                    <div class="subsection">
                        <h4>&#128201; Deterioro de Activos (IAS 36)</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Calcular Deterioro (Impairment)</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="calcular_deterioro">

                                    <div class="form-group">
                                        <label>Tipo Activo</label>
                                        <select name="tipo_activo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="PPE">Propiedad, Planta y Equipo</option>
                                            <option value="INTANGIBLE">Activo Intangible</option>
                                            <option value="GOODWILL">Plusvalia (Goodwill)</option>
                                            <option value="INVERSION">Inversion</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Codigo Activo</label>
                                        <input type="text" name="codigo_activo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Descripcion</label>
                                        <input type="text" name="descripcion" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Valor en Libros</label>
                                        <input type="number" name="valor_libros" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Valor Razonable menos Costos de Venta</label>
                                        <input type="number" name="valor_razonable" step="0.01">
                                    </div>

                                    <div class="form-group">
                                        <label>Valor en Uso</label>
                                        <input type="number" name="valor_uso" step="0.01">
                                    </div>

                                    <div class="form-group">
                                        <label>Fecha Prueba</label>
                                        <input type="date" name="fecha_prueba" required>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128200; Calcular Deterioro</button>
                                        <button type="button" class="btn btn-warning">&#128196; Informe Detallado</button>
                                        <button type="button" class="btn btn-success">&#128196; Generar Asiento</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Activos Evaluados</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Activos con Deterioro</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Deterioro Reconocido</div>
                                <div class="kpi-value">$0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Reversiones</div>
                                <div class="kpi-value">$0</div>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Activo</th>
                                        <th>Tipo</th>
                                        <th>Valor Libros</th>
                                        <th>Monto Recuperable</th>
                                        <th>Deterioro</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay pruebas de deterioro registradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Comparacion -->
                    <div class="subsection">
                        <h4>&#128202; Comparacion entre Resultado Local y IFRS</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Generar Comparacion</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="generar_comparacion">

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo Comparacion</label>
                                        <select name="tipo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="BALANCE">Balance</option>
                                            <option value="RESULTADOS">Estado de Resultados</option>
                                            <option value="PATRIMONIO">Patrimonio</option>
                                            <option value="COMPLETO">Comparacion Completa</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Nivel Detalle</label>
                                        <select name="nivel" required>
                                            <option value="">Seleccione...</option>
                                            <option value="RESUMEN">Resumen</option>
                                            <option value="DETALLADO">Detallado</option>
                                            <option value="CUENTA">Por Cuenta</option>
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
                                        <th>Saldo Local</th>
                                        <th>Saldo IFRS</th>
                                        <th>Diferencia</th>
                                        <th>% Var</th>
                                        <th>Motivo Diferencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d;">
                                            Genere una comparacion para ver los datos
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 8.3: INTEGRACION -->
            <div class="tab-header" onclick="toggleTab('tab3')">
                <h2><span class="tab-icon">&#128279;</span> 8.3 Integracion</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab3">
                <div class="tab-inner">

                    <div class="subsection">
                        <h4>&#128260; Panel de Integraciones IFRS</h4>

                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Integraciones Activas</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Ajustes Sincronizados Hoy</div>
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
                                <h3>&#128188; Activos Fijos</h3>
                                <div class="form-group">
                                    <label>Estado Integracion</label>
                                    <span class="status-badge status-inactivo">Inactivo</span>
                                </div>
                                <div class="form-group">
                                    <label>Ultima Sincronizacion</label>
                                    <input type="text" value="Nunca" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Ajustes IFRS</label>
                                    <select name="ajustes_activos" multiple size="4">
                                        <option>Componentes</option>
                                        <option>Revalorizaciones</option>
                                        <option>Impairment</option>
                                        <option>Vida Util</option>
                                    </select>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-success">&#9654; Sincronizar</button>
                                    <button class="btn btn-primary">&#128270; Configurar</button>
                                </div>
                            </div>

                            <div class="card">
                                <h3>&#127760; Consolidacion</h3>
                                <div class="form-group">
                                    <label>Estado Integracion</label>
                                    <span class="status-badge status-inactivo">Inactivo</span>
                                </div>
                                <div class="form-group">
                                    <label>Ultima Sincronizacion</label>
                                    <input type="text" value="Nunca" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Base Consolidacion</label>
                                    <select name="base_consolidacion">
                                        <option value="">Seleccione...</option>
                                        <option value="LOCAL">Base Local</option>
                                        <option value="IFRS">Base IFRS</option>
                                        <option value="AMBAS">Ambas</option>
                                    </select>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-success">&#9654; Sincronizar</button>
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
                                    <label>Estados IFRS</label>
                                    <select name="estados_ifrs" multiple size="4">
                                        <option>Balance IFRS</option>
                                        <option>Resultados IFRS</option>
                                        <option>Cambios en Patrimonio</option>
                                        <option>Flujo de Caja</option>
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
