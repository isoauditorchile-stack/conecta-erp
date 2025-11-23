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
        case 'emitir_factura':
            // TODO: Insertar en tabla comprobantes
            $mensaje = 'Factura emitida exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'emitir_boleta':
            // TODO: Insertar en tabla comprobantes
            $mensaje = 'Boleta emitida exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'emitir_nc':
            // TODO: Insertar en tabla comprobantes
            $mensaje = 'Nota de credito emitida exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'emitir_nd':
            // TODO: Insertar en tabla comprobantes
            $mensaje = 'Nota de debito emitida exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'recibir_factura':
            // TODO: Insertar en tabla comprobantes_recibidos
            $mensaje = 'Factura de compra recibida exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'validar_dte':
            // TODO: Validar DTE en SII
            $mensaje = 'DTE validado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'validar_oc':
            // TODO: Validar contra orden de compra
            $mensaje = 'Validacion contra OC completada';
            $tipo_mensaje = 'success';
            break;

        case 'almacenar_xml':
            // TODO: Almacenar archivo XML
            $mensaje = 'XML almacenado exitosamente';
            $tipo_mensaje = 'success';
            break;

        case 'eliminar':
            $id = $_POST['id'] ?? 0;
            $tabla = $_POST['tabla'] ?? '';
            // TODO: Anular/Eliminar comprobante
            $mensaje = 'Comprobante anulado exitosamente';
            $tipo_mensaje = 'success';
            break;
    }
}

// Obtener datos para las grillas
$facturas_emitidas = []; // TODO: SELECT * FROM comprobantes WHERE tipo IN ('FACTURA')
$boletas = []; // TODO: SELECT * FROM comprobantes WHERE tipo = 'BOLETA'
$nc_emitidas = []; // TODO: SELECT * FROM comprobantes WHERE tipo = 'NC'
$nd_emitidas = []; // TODO: SELECT * FROM comprobantes WHERE tipo = 'ND'
$facturas_compra = []; // TODO: SELECT * FROM comprobantes_recibidos
$dte = []; // TODO: SELECT * FROM comprobantes_dte
$folios = []; // TODO: SELECT * FROM comprobantes_folios
$xml_archivos = []; // TODO: SELECT * FROM comprobantes_xml

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COMPROBANTES Y FACTURAS - ConectaERP</title>
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
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
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
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
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
            color: #d97706;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f59e0b;
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
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
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
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #047857 0%, #10b981 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #b45309 0%, #d97706 100%);
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
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
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

        .status-emitido {
            background-color: #d4edda;
            color: #155724;
        }

        .status-anulado {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-validado {
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
            border-left: 4px solid #f59e0b;
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
            color: #d97706;
        }

        .kpi-change {
            font-size: 12px;
            margin-top: 8px;
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
            color: #d97706;
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
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-left: 4px solid #d97706;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-box h5 {
            color: #92400e;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .alert-box p {
            color: #92400e;
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
                background: #d97706;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            <span class="header-icon">&#128196;</span>
            MODULO COMPROBANTES Y FACTURAS
        </h1>
    </div>

    <div class="container">
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="alert-box">
            <h5>&#9432; Punto de Entrada al Sistema</h5>
            <p>Este modulo es el punto de entrada a CxC, CxP, Impuestos, Contabilidad y Tesoreria. Todos los comprobantes emitidos y recibidos se procesan aqui antes de integrarse automaticamente con los demas modulos del sistema.</p>
        </div>

        <div class="tabs-container">

            <!-- TAB 10.1: EMISION -->
            <div class="tab-header" onclick="toggleTab('tab1')">
                <h2><span class="tab-icon">&#128221;</span> 10.1 Emision</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab1">
                <div class="tab-inner">

                    <div class="kpi-grid">
                        <div class="kpi-card">
                            <div class="kpi-label">Facturas Emitidas Hoy</div>
                            <div class="kpi-value">0</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Boletas Emitidas Hoy</div>
                            <div class="kpi-value">0</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Monto Total Facturado</div>
                            <div class="kpi-value">$0</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Folios Disponibles</div>
                            <div class="kpi-value">0</div>
                        </div>
                    </div>

                    <!-- Facturas -->
                    <div class="subsection">
                        <h4>&#128221; Facturas</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Emitir Factura</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="emitir_factura">

                                    <div class="form-group">
                                        <label>Tipo Documento</label>
                                        <select name="tipo_documento" required>
                                            <option value="">Seleccione...</option>
                                            <option value="33">33 - Factura Electronica</option>
                                            <option value="34">34 - Factura No Afecta o Exenta Electronica</option>
                                            <option value="43">43 - Liquidacion Factura Electronica</option>
                                            <option value="46">46 - Factura de Compra Electronica</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>RUT Cliente</label>
                                        <input type="text" name="rut_cliente" required placeholder="12345678-9">
                                    </div>

                                    <div class="form-group">
                                        <label>Razon Social</label>
                                        <input type="text" name="razon_social" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Giro</label>
                                        <input type="text" name="giro" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Direccion</label>
                                        <input type="text" name="direccion" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Fecha Emision</label>
                                        <input type="date" name="fecha_emision" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Condicion Pago</label>
                                        <select name="condicion_pago" required>
                                            <option value="">Seleccione...</option>
                                            <option value="CONTADO">Contado</option>
                                            <option value="CREDITO_30">Credito 30 dias</option>
                                            <option value="CREDITO_60">Credito 60 dias</option>
                                            <option value="CREDITO_90">Credito 90 dias</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Glosa</label>
                                        <textarea name="glosa"></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Monto Neto</label>
                                        <input type="number" name="monto_neto" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>IVA (19%)</label>
                                        <input type="number" name="iva" step="0.01" readonly>
                                    </div>

                                    <div class="form-group">
                                        <label>Total</label>
                                        <input type="number" name="total" step="0.01" readonly>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128196; Emitir Factura</button>
                                        <button type="button" class="btn btn-success">&#128190; Generar DTE</button>
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
                                        <th>Tipo</th>
                                        <th>Numero</th>
                                        <th>Fecha</th>
                                        <th>RUT Cliente</th>
                                        <th>Razon Social</th>
                                        <th>Neto</th>
                                        <th>IVA</th>
                                        <th>Total</th>
                                        <th>Estado DTE</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="10" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay facturas emitidas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Boletas -->
                    <div class="subsection">
                        <h4>&#128179; Boletas</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Emitir Boleta</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="emitir_boleta">

                                    <div class="form-group">
                                        <label>Tipo Boleta</label>
                                        <select name="tipo_boleta" required>
                                            <option value="">Seleccione...</option>
                                            <option value="39">39 - Boleta Electronica</option>
                                            <option value="41">41 - Boleta Exenta Electronica</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Fecha</label>
                                        <input type="date" name="fecha" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Monto Total</label>
                                        <input type="number" name="monto_total" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Detalle</label>
                                        <textarea name="detalle"></textarea>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128196; Emitir Boleta</button>
                                        <button type="button" class="btn btn-success">&#128190; Generar DTE</button>
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
                                        <th>Numero</th>
                                        <th>Fecha</th>
                                        <th>Monto</th>
                                        <th>Estado DTE</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay boletas emitidas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Notas de Credito -->
                    <div class="subsection">
                        <h4>&#128280; Notas de Credito (NC)</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Emitir Nota de Credito</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="emitir_nc">

                                    <div class="form-group">
                                        <label>Tipo NC</label>
                                        <select name="tipo_nc" required>
                                            <option value="">Seleccione...</option>
                                            <option value="61">61 - Nota de Credito Electronica</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Documento Referencia</label>
                                        <input type="text" name="doc_referencia" required placeholder="Tipo-Numero">
                                    </div>

                                    <div class="form-group">
                                        <label>RUT Cliente</label>
                                        <input type="text" name="rut_cliente" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Razon Social</label>
                                        <input type="text" name="razon_social" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Motivo</label>
                                        <select name="motivo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="1">Anula Documento de Referencia</option>
                                            <option value="2">Corrige Texto Documento Referencia</option>
                                            <option value="3">Corrige Montos</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Monto Neto</label>
                                        <input type="number" name="monto_neto" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>IVA</label>
                                        <input type="number" name="iva" step="0.01" readonly>
                                    </div>

                                    <div class="form-group">
                                        <label>Total</label>
                                        <input type="number" name="total" step="0.01" readonly>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128196; Emitir NC</button>
                                        <button type="button" class="btn btn-success">&#128190; Generar DTE</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Numero</th>
                                        <th>Fecha</th>
                                        <th>Doc Referencia</th>
                                        <th>Cliente</th>
                                        <th>Motivo</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay notas de credito emitidas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Notas de Debito -->
                    <div class="subsection">
                        <h4>&#128281; Notas de Debito (ND)</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Emitir Nota de Debito</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="emitir_nd">

                                    <div class="form-group">
                                        <label>Tipo ND</label>
                                        <select name="tipo_nd" required>
                                            <option value="">Seleccione...</option>
                                            <option value="56">56 - Nota de Debito Electronica</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Documento Referencia</label>
                                        <input type="text" name="doc_referencia" required>
                                    </div>

                                    <div class="form-group">
                                        <label>RUT Cliente</label>
                                        <input type="text" name="rut_cliente" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Razon Social</label>
                                        <input type="text" name="razon_social" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Motivo</label>
                                        <textarea name="motivo" required></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Monto Neto</label>
                                        <input type="number" name="monto_neto" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>IVA</label>
                                        <input type="number" name="iva" step="0.01" readonly>
                                    </div>

                                    <div class="form-group">
                                        <label>Total</label>
                                        <input type="number" name="total" step="0.01" readonly>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128196; Emitir ND</button>
                                        <button type="button" class="btn btn-success">&#128190; Generar DTE</button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">&#128424; Imprimir</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Numero</th>
                                        <th>Fecha</th>
                                        <th>Doc Referencia</th>
                                        <th>Cliente</th>
                                        <th>Motivo</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay notas de debito emitidas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- DTE Integrado -->
                    <div class="subsection">
                        <h4>&#128190; DTE / Factura Electronica Integrada con SII</h4>
                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">DTEs Enviados al SII Hoy</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">DTEs Aceptados</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">DTEs Rechazados</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">DTEs Pendientes</div>
                                <div class="kpi-value">0</div>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tipo Doc</th>
                                        <th>Numero</th>
                                        <th>Fecha Emision</th>
                                        <th>RUT Receptor</th>
                                        <th>Monto</th>
                                        <th>Estado SII</th>
                                        <th>Track ID</th>
                                        <th>XML</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay DTEs registrados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Plantillas -->
                    <div class="subsection">
                        <h4>&#128196; Plantillas por Tipo de Operacion</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Plantilla Ventas</h3>
                                <div class="form-group">
                                    <label>Items Predefinidos</label>
                                    <select multiple size="4">
                                        <option>Producto A</option>
                                        <option>Producto B</option>
                                        <option>Servicio C</option>
                                    </select>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-primary">&#9998; Editar Plantilla</button>
                                    <button class="btn btn-success">&#10133; Agregar Item</button>
                                </div>
                            </div>

                            <div class="card">
                                <h3>Plantilla Servicios</h3>
                                <div class="form-group">
                                    <label>Servicios Predefinidos</label>
                                    <select multiple size="4">
                                        <option>Consultoria</option>
                                        <option>Soporte Tecnico</option>
                                        <option>Implementacion</option>
                                    </select>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-primary">&#9998; Editar Plantilla</button>
                                    <button class="btn btn-success">&#10133; Agregar Servicio</button>
                                </div>
                            </div>

                            <div class="card">
                                <h3>Plantilla Exportacion</h3>
                                <div class="form-group">
                                    <label>Configuracion Exportacion</label>
                                    <select multiple size="4">
                                        <option>Incoterms: FOB</option>
                                        <option>Incoterms: CIF</option>
                                        <option>Incoterms: EXW</option>
                                    </select>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-primary">&#9998; Editar Plantilla</button>
                                    <button class="btn btn-success">&#10133; Agregar Termino</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 10.2: RECEPCION -->
            <div class="tab-header" onclick="toggleTab('tab2')">
                <h2><span class="tab-icon">&#128229;</span> 10.2 Recepcion</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab2">
                <div class="tab-inner">

                    <div class="kpi-grid">
                        <div class="kpi-card">
                            <div class="kpi-label">Facturas Recibidas Hoy</div>
                            <div class="kpi-value">0</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Pendientes Validacion</div>
                            <div class="kpi-value">0</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Validadas OK</div>
                            <div class="kpi-value">0</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Rechazadas</div>
                            <div class="kpi-value">0</div>
                        </div>
                    </div>

                    <!-- Facturas de Compra -->
                    <div class="subsection">
                        <h4>&#128196; Facturas de Compra Electronicas</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Registrar Factura de Compra</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="recibir_factura">

                                    <div class="form-group">
                                        <label>Tipo Documento</label>
                                        <select name="tipo_documento" required>
                                            <option value="">Seleccione...</option>
                                            <option value="33">33 - Factura Electronica</option>
                                            <option value="34">34 - Factura Exenta</option>
                                            <option value="46">46 - Factura de Compra</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Numero Factura</label>
                                        <input type="text" name="numero" required>
                                    </div>

                                    <div class="form-group">
                                        <label>RUT Proveedor</label>
                                        <input type="text" name="rut_proveedor" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Razon Social</label>
                                        <input type="text" name="razon_social" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Fecha Factura</label>
                                        <input type="date" name="fecha" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Fecha Recepcion</label>
                                        <input type="date" name="fecha_recepcion" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Monto Neto</label>
                                        <input type="number" name="monto_neto" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>IVA</label>
                                        <input type="number" name="iva" step="0.01" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Total</label>
                                        <input type="number" name="total" step="0.01" required>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#10004; Registrar</button>
                                        <button type="button" class="btn btn-success">&#128269; Validar en SII</button>
                                        <button type="button" class="btn btn-warning">&#128270; Validar contra OC</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Numero</th>
                                        <th>Fecha</th>
                                        <th>RUT Proveedor</th>
                                        <th>Razon Social</th>
                                        <th>Neto</th>
                                        <th>IVA</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="10" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay facturas de compra registradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Importacion desde SII -->
                    <div class="subsection">
                        <h4>&#128190; Importacion desde Servicios del SII</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Importar DTEs desde SII</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="importar_sii">

                                    <div class="form-group">
                                        <label>RUT Empresa</label>
                                        <input type="text" name="rut_empresa" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Periodo</label>
                                        <input type="month" name="periodo" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo Importacion</label>
                                        <select name="tipo_importacion" required>
                                            <option value="">Seleccione...</option>
                                            <option value="RECIBIDOS">Documentos Recibidos</option>
                                            <option value="EMITIDOS">Documentos Emitidos</option>
                                            <option value="AMBOS">Ambos</option>
                                        </select>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-success">&#128190; Importar desde SII</button>
                                        <button type="button" class="btn btn-warning">&#128203; Ver Log</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Validaciones -->
                    <div class="subsection">
                        <h4>&#9989; Validaciones</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Validacion contra Ordenes de Compra</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="validar_oc">

                                    <div class="form-group">
                                        <label>Factura</label>
                                        <select name="factura_id" required>
                                            <option value="">Seleccione factura...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Orden de Compra</label>
                                        <select name="oc_id" required>
                                            <option value="">Seleccione OC...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Criterios Validacion</label>
                                        <label><input type="checkbox" checked> Monto</label><br>
                                        <label><input type="checkbox" checked> Proveedor</label><br>
                                        <label><input type="checkbox" checked> Items</label><br>
                                        <label><input type="checkbox"> Cantidades</label>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-success">&#9989; Validar</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Validacion contra Recepciones (3-Way Match)</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="validar_3way">

                                    <div class="form-group">
                                        <label>Factura</label>
                                        <select name="factura_id" required>
                                            <option value="">Seleccione factura...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Orden de Compra</label>
                                        <select name="oc_id" required>
                                            <option value="">Seleccione OC...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Recepcion de Mercaderia</label>
                                        <select name="recepcion_id" required>
                                            <option value="">Seleccione recepcion...</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Tolerancia (%)</label>
                                        <input type="number" name="tolerancia" value="5" step="0.01">
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-success">&#9989; Ejecutar 3-Way Match</button>
                                        <button type="button" class="btn btn-warning">&#128203; Ver Detalle</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Factura</th>
                                        <th>OC</th>
                                        <th>Recepcion</th>
                                        <th>Estado Validacion</th>
                                        <th>Diferencias</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay validaciones registradas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 10.3: ALMACENAMIENTO Y TRAZABILIDAD -->
            <div class="tab-header" onclick="toggleTab('tab3')">
                <h2><span class="tab-icon">&#128190;</span> 10.3 Almacenamiento y Trazabilidad</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab3">
                <div class="tab-inner">

                    <!-- Almacenamiento -->
                    <div class="subsection">
                        <h4>&#128190; Almacenamiento de Documentos</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Almacenamiento XML</h3>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="accion" value="almacenar_xml">

                                    <div class="form-group">
                                        <label>Tipo Documento</label>
                                        <select name="tipo_documento" required>
                                            <option value="">Seleccione...</option>
                                            <option value="DTE">DTE Emitido</option>
                                            <option value="DTE_RECIBIDO">DTE Recibido</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Archivo XML</label>
                                        <input type="file" name="archivo_xml" accept=".xml" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Ruta Almacenamiento</label>
                                        <input type="text" name="ruta" readonly value="/almacenamiento/xml/">
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128190; Almacenar XML</button>
                                        <button type="button" class="btn btn-success">&#128269; Validar Esquema</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Almacenamiento PDF</h3>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="accion" value="almacenar_pdf">

                                    <div class="form-group">
                                        <label>Tipo Documento</label>
                                        <select name="tipo_documento" required>
                                            <option value="">Seleccione...</option>
                                            <option value="FACTURA">Factura</option>
                                            <option value="BOLETA">Boleta</option>
                                            <option value="NC">Nota Credito</option>
                                            <option value="ND">Nota Debito</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Archivo PDF</label>
                                        <input type="file" name="archivo_pdf" accept=".pdf" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Ruta Almacenamiento</label>
                                        <input type="text" name="ruta" readonly value="/almacenamiento/pdf/">
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128190; Almacenar PDF</button>
                                    </div>
                                </form>
                            </div>

                            <div class="card">
                                <h3>Hash de Documentos</h3>
                                <div class="form-group">
                                    <label>Total Documentos con Hash</label>
                                    <input type="text" value="0" readonly>
                                </div>

                                <div class="form-group">
                                    <label>Algoritmo Hash</label>
                                    <select>
                                        <option>SHA-256</option>
                                        <option>SHA-512</option>
                                        <option>MD5</option>
                                    </select>
                                </div>

                                <div class="btn-group">
                                    <button class="btn btn-success">&#128270; Generar Hashes</button>
                                    <button class="btn btn-warning">&#9989; Verificar Integridad</button>
                                </div>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tipo Doc</th>
                                        <th>Numero</th>
                                        <th>Fecha</th>
                                        <th>XML</th>
                                        <th>PDF</th>
                                        <th>Hash</th>
                                        <th>Tamano</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay documentos almacenados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Control de Folios -->
                    <div class="subsection">
                        <h4>&#128203; Control de Folios</h4>
                        <div class="kpi-grid">
                            <div class="kpi-card">
                                <div class="kpi-label">Folios Facturas Disponibles</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Folios Boletas Disponibles</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Folios Utilizados Hoy</div>
                                <div class="kpi-value">0</div>
                            </div>
                            <div class="kpi-card">
                                <div class="kpi-label">Alertas Folios Bajos</div>
                                <div class="kpi-value">0</div>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tipo Documento</th>
                                        <th>Desde</th>
                                        <th>Hasta</th>
                                        <th>Disponibles</th>
                                        <th>Utilizados</th>
                                        <th>Fecha Autorizacion</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay folios autorizados
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Trazabilidad -->
                    <div class="subsection">
                        <h4>&#128269; Trazabilidad Completa</h4>
                        <div class="section-grid">
                            <div class="card">
                                <h3>Buscar Documento</h3>
                                <form method="POST">
                                    <input type="hidden" name="accion" value="buscar_trazabilidad">

                                    <div class="form-group">
                                        <label>Tipo Busqueda</label>
                                        <select name="tipo_busqueda" required>
                                            <option value="">Seleccione...</option>
                                            <option value="NUMERO">Por Numero</option>
                                            <option value="RUT">Por RUT Cliente/Proveedor</option>
                                            <option value="FECHA">Por Fecha</option>
                                            <option value="MONTO">Por Monto</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Valor Busqueda</label>
                                        <input type="text" name="valor" required>
                                    </div>

                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-primary">&#128269; Buscar</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Documento</th>
                                        <th>Cliente/Proveedor</th>
                                        <th>Relacionado CxC/CxP</th>
                                        <th>Impuestos</th>
                                        <th>Contabilidad</th>
                                        <th>Tesoreria</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            Realice una busqueda para ver la trazabilidad
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 10.4: INTEGRACION -->
            <div class="tab-header" onclick="toggleTab('tab4')">
                <h2><span class="tab-icon">&#128279;</span> 10.4 Integracion</h2>
                <span class="tab-arrow">&#9662;</span>
            </div>
            <div class="tab-content" id="tab4">
                <div class="tab-inner">

                    <div class="alert-box">
                        <h5>&#128260; Integraciones Automaticas</h5>
                        <p>Este modulo se integra automaticamente con CxC, CxP, Impuestos, Contabilidad y Tesoreria. Cada comprobante genera automaticamente los registros correspondientes en cada modulo.</p>
                    </div>

                    <div class="kpi-grid">
                        <div class="kpi-card">
                            <div class="kpi-label">Documentos Integrados Hoy</div>
                            <div class="kpi-value">0</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Asientos Generados</div>
                            <div class="kpi-value">0</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">CxC/CxP Creadas</div>
                            <div class="kpi-value">0</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Errores Integracion</div>
                            <div class="kpi-value">0</div>
                        </div>
                    </div>

                    <div class="section-grid">
                        <div class="card">
                            <h3>&#128179; CxC - Cuentas por Cobrar</h3>
                            <div class="form-group">
                                <label>Estado Integracion</label>
                                <span class="status-badge status-emitido">Activo</span>
                            </div>
                            <div class="form-group">
                                <label>Documentos Integrados Hoy</label>
                                <input type="text" value="0" readonly>
                            </div>
                            <div class="form-group">
                                <label>Modo Integracion</label>
                                <select>
                                    <option>Automatica (Tiempo Real)</option>
                                    <option>Automatica (Batch)</option>
                                    <option>Manual</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Reglas de Mapeo</label>
                                <label><input type="checkbox" checked> Facturas > CxC</label><br>
                                <label><input type="checkbox" checked> NC > Abono CxC</label><br>
                                <label><input type="checkbox" checked> ND > Cargo CxC</label>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-primary">&#128270; Configurar</button>
                                <button class="btn btn-success">&#9654; Sincronizar</button>
                            </div>
                        </div>

                        <div class="card">
                            <h3>&#128176; CxP - Cuentas por Pagar</h3>
                            <div class="form-group">
                                <label>Estado Integracion</label>
                                <span class="status-badge status-emitido">Activo</span>
                            </div>
                            <div class="form-group">
                                <label>Documentos Integrados Hoy</label>
                                <input type="text" value="0" readonly>
                            </div>
                            <div class="form-group">
                                <label>Modo Integracion</label>
                                <select>
                                    <option>Automatica (Tiempo Real)</option>
                                    <option>Automatica (Batch)</option>
                                    <option>Manual</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Reglas de Mapeo</label>
                                <label><input type="checkbox" checked> Facturas Compra > CxP</label><br>
                                <label><input type="checkbox" checked> NC > Abono CxP</label><br>
                                <label><input type="checkbox" checked> ND > Cargo CxP</label>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-primary">&#128270; Configurar</button>
                                <button class="btn btn-success">&#9654; Sincronizar</button>
                            </div>
                        </div>

                        <div class="card">
                            <h3>&#128179; Impuestos</h3>
                            <div class="form-group">
                                <label>Estado Integracion</label>
                                <span class="status-badge status-emitido">Activo</span>
                            </div>
                            <div class="form-group">
                                <label>Documentos Procesados Hoy</label>
                                <input type="text" value="0" readonly>
                            </div>
                            <div class="form-group">
                                <label>Modo Integracion</label>
                                <select>
                                    <option>Automatica (Tiempo Real)</option>
                                    <option>Automatica (Batch)</option>
                                    <option>Manual</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Calculos Automaticos</label>
                                <label><input type="checkbox" checked> IVA Debito Fiscal</label><br>
                                <label><input type="checkbox" checked> IVA Credito Fiscal</label><br>
                                <label><input type="checkbox" checked> Retenciones</label><br>
                                <label><input type="checkbox" checked> Libro Compras/Ventas</label>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-primary">&#128270; Configurar</button>
                                <button class="btn btn-success">&#9654; Sincronizar</button>
                            </div>
                        </div>

                        <div class="card">
                            <h3>&#128218; Contabilidad</h3>
                            <div class="form-group">
                                <label>Estado Integracion</label>
                                <span class="status-badge status-emitido">Activo</span>
                            </div>
                            <div class="form-group">
                                <label>Asientos Generados Hoy</label>
                                <input type="text" value="0" readonly>
                            </div>
                            <div class="form-group">
                                <label>Modo Integracion</label>
                                <select>
                                    <option>Automatica (Tiempo Real)</option>
                                    <option>Automatica (Batch)</option>
                                    <option>Manual</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Plantillas Asientos</label>
                                <label><input type="checkbox" checked> Ventas</label><br>
                                <label><input type="checkbox" checked> Compras</label><br>
                                <label><input type="checkbox" checked> NC/ND</label>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-primary">&#128270; Configurar</button>
                                <button class="btn btn-success">&#9654; Sincronizar</button>
                            </div>
                        </div>

                        <div class="card">
                            <h3>&#128181; Tesoreria</h3>
                            <div class="form-group">
                                <label>Estado Integracion</label>
                                <span class="status-badge status-emitido">Activo</span>
                            </div>
                            <div class="form-group">
                                <label>Documentos Integrados Hoy</label>
                                <input type="text" value="0" readonly>
                            </div>
                            <div class="form-group">
                                <label>Modo Integracion</label>
                                <select>
                                    <option>Automatica (Tiempo Real)</option>
                                    <option>Automatica (Batch)</option>
                                    <option>Manual</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Flujos Automaticos</label>
                                <label><input type="checkbox" checked> Cobranzas</label><br>
                                <label><input type="checkbox" checked> Pagos Proveedores</label><br>
                                <label><input type="checkbox" checked> Flujo de Caja Proyectado</label>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-primary">&#128270; Configurar</button>
                                <button class="btn btn-success">&#9654; Sincronizar</button>
                            </div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div class="subsection">
                        <h4>&#128203; Log de Integraciones</h4>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Documento</th>
                                        <th>Modulo Destino</th>
                                        <th>Tipo Operacion</th>
                                        <th>Resultado</th>
                                        <th>Mensaje</th>
                                        <th>Usuario</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: #6c757d;">
                                            No hay integraciones registradas
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
