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
        $codigo_material = sanitize($_POST['codigo_material'] ?? '');
        $descripcion_corta = sanitize($_POST['descripcion_corta'] ?? '');
        $descripcion_larga = sanitize($_POST['descripcion_larga'] ?? '');
        $unidad_medida_id = intval($_POST['unidad_medida_id'] ?? 0);
        $codigo_barras = sanitize($_POST['codigo_barras'] ?? '');
        $codigo_qr = sanitize($_POST['codigo_qr'] ?? '');
        $tipo_material = sanitize($_POST['tipo_material'] ?? '');
        $marca = sanitize($_POST['marca'] ?? '');
        $modelo = sanitize($_POST['modelo'] ?? '');
        $categoria_id = intval($_POST['categoria_id'] ?? 0);
        $familia_id = intval($_POST['familia_id'] ?? 0);

        // Datos de Inventario
        $control_stock = isset($_POST['control_stock']) ? 1 : 0;
        $stock_minimo = floatval($_POST['stock_minimo'] ?? 0);
        $stock_maximo = floatval($_POST['stock_maximo'] ?? 0);
        $punto_reposicion = floatval($_POST['punto_reposicion'] ?? 0);
        $ubicacion_bodega = sanitize($_POST['ubicacion_bodega'] ?? '');
        $lote_serie = sanitize($_POST['lote_serie'] ?? '');
        $fecha_vencimiento = sanitize($_POST['fecha_vencimiento'] ?? '');
        $politica_rotacion = sanitize($_POST['politica_rotacion'] ?? '');

        // Datos Comerciales
        $precio_venta = floatval($_POST['precio_venta'] ?? 0);
        $precio_oferta = floatval($_POST['precio_oferta'] ?? 0);
        $impuesto_id = intval($_POST['impuesto_id'] ?? 0);
        $descuento_maximo = floatval($_POST['descuento_maximo'] ?? 0);
        $politica_comercial = sanitize($_POST['politica_comercial'] ?? '');
        $unidad_venta = sanitize($_POST['unidad_venta'] ?? '');
        $unidad_compra = sanitize($_POST['unidad_compra'] ?? '');
        $lista_precios_id = intval($_POST['lista_precios_id'] ?? 0);

        // Costos y Valorización
        $costo_promedio = floatval($_POST['costo_promedio'] ?? 0);
        $costo_estandar = floatval($_POST['costo_estandar'] ?? 0);
        $costo_ultimo = floatval($_POST['costo_ultimo'] ?? 0);
        $costo_reposicion = floatval($_POST['costo_reposicion'] ?? 0);
        $metodo_costeo = sanitize($_POST['metodo_costeo'] ?? '');
        $costo_importacion = floatval($_POST['costo_importacion'] ?? 0);
        $costo_fabricacion = floatval($_POST['costo_fabricacion'] ?? 0);

        // Producción
        $bom_asociado = sanitize($_POST['bom_asociado'] ?? '');
        $ruta_produccion = sanitize($_POST['ruta_produccion'] ?? '');
        $tiempo_estandar = intval($_POST['tiempo_estandar'] ?? 0);
        $merma_permitida = floatval($_POST['merma_permitida'] ?? 0);

        // Calidad
        $especificaciones_tecnicas = sanitize($_POST['especificaciones_tecnicas'] ?? '');
        $parametros_inspeccion = sanitize($_POST['parametros_inspeccion'] ?? '');
        $certificados = sanitize($_POST['certificados'] ?? '');
        $aprobacion_lote = isset($_POST['aprobacion_lote']) ? 1 : 0;

        // Dimensiones y Peso
        $peso = floatval($_POST['peso'] ?? 0);
        $largo = floatval($_POST['largo'] ?? 0);
        $ancho = floatval($_POST['ancho'] ?? 0);
        $alto = floatval($_POST['alto'] ?? 0);
        $volumen = floatval($_POST['volumen'] ?? 0);

        // Proveedor Principal
        $proveedor_principal_id = intval($_POST['proveedor_principal_id'] ?? 0);
        $lead_time_proveedor = intval($_POST['lead_time_proveedor'] ?? 0);

        // Estado
        $estado = sanitize($_POST['estado'] ?? 'activo');
        $observaciones = sanitize($_POST['observaciones'] ?? '');

        if ($accion === 'crear') {
            $stmt = $conn->prepare("INSERT INTO productos (
                codigo_material, descripcion_corta, descripcion_larga, unidad_medida_id,
                codigo_barras, codigo_qr, tipo_material, marca, modelo, categoria_id, familia_id,
                control_stock, stock_minimo, stock_maximo, punto_reposicion, ubicacion_bodega,
                lote_serie, fecha_vencimiento, politica_rotacion,
                precio_venta, precio_oferta, impuesto_id, descuento_maximo, politica_comercial,
                unidad_venta, unidad_compra, lista_precios_id,
                costo_promedio, costo_estandar, costo_ultimo, costo_reposicion, metodo_costeo,
                costo_importacion, costo_fabricacion,
                bom_asociado, ruta_produccion, tiempo_estandar, merma_permitida,
                especificaciones_tecnicas, parametros_inspeccion, certificados, aprobacion_lote,
                peso, largo, ancho, alto, volumen,
                proveedor_principal_id, lead_time_proveedor,
                estado, observaciones, fecha_registro
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            $stmt->bind_param("sssisssssiidddssssddidssiddddsddssiidsssiiddddiiss",
                $codigo_material, $descripcion_corta, $descripcion_larga, $unidad_medida_id,
                $codigo_barras, $codigo_qr, $tipo_material, $marca, $modelo, $categoria_id, $familia_id,
                $control_stock, $stock_minimo, $stock_maximo, $punto_reposicion, $ubicacion_bodega,
                $lote_serie, $fecha_vencimiento, $politica_rotacion,
                $precio_venta, $precio_oferta, $impuesto_id, $descuento_maximo, $politica_comercial,
                $unidad_venta, $unidad_compra, $lista_precios_id,
                $costo_promedio, $costo_estandar, $costo_ultimo, $costo_reposicion, $metodo_costeo,
                $costo_importacion, $costo_fabricacion,
                $bom_asociado, $ruta_produccion, $tiempo_estandar, $merma_permitida,
                $especificaciones_tecnicas, $parametros_inspeccion, $certificados, $aprobacion_lote,
                $peso, $largo, $ancho, $alto, $volumen,
                $proveedor_principal_id, $lead_time_proveedor,
                $estado, $observaciones
            );

            if ($stmt->execute()) {
                $producto_id = $conn->insert_id;
                logAuditoria('crear', 'productos', $producto_id, null, json_encode($_POST), 'Producto creado: ' . $descripcion_corta);
                $mensaje = 'Producto creado exitosamente';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al crear producto: ' . $stmt->error;
                $tipo_mensaje = 'error';
            }
        } else {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE productos SET
                codigo_material=?, descripcion_corta=?, descripcion_larga=?, unidad_medida_id=?,
                codigo_barras=?, codigo_qr=?, tipo_material=?, marca=?, modelo=?, categoria_id=?, familia_id=?,
                control_stock=?, stock_minimo=?, stock_maximo=?, punto_reposicion=?, ubicacion_bodega=?,
                lote_serie=?, fecha_vencimiento=?, politica_rotacion=?,
                precio_venta=?, precio_oferta=?, impuesto_id=?, descuento_maximo=?, politica_comercial=?,
                unidad_venta=?, unidad_compra=?, lista_precios_id=?,
                costo_promedio=?, costo_estandar=?, costo_ultimo=?, costo_reposicion=?, metodo_costeo=?,
                costo_importacion=?, costo_fabricacion=?,
                bom_asociado=?, ruta_produccion=?, tiempo_estandar=?, merma_permitida=?,
                especificaciones_tecnicas=?, parametros_inspeccion=?, certificados=?, aprobacion_lote=?,
                peso=?, largo=?, ancho=?, alto=?, volumen=?,
                proveedor_principal_id=?, lead_time_proveedor=?,
                estado=?, observaciones=?, fecha_modificacion=NOW()
                WHERE id=?");

            $stmt->bind_param("sssisssssiidddssssddidssiddddsddssiidsssiiddddiissi",
                $codigo_material, $descripcion_corta, $descripcion_larga, $unidad_medida_id,
                $codigo_barras, $codigo_qr, $tipo_material, $marca, $modelo, $categoria_id, $familia_id,
                $control_stock, $stock_minimo, $stock_maximo, $punto_reposicion, $ubicacion_bodega,
                $lote_serie, $fecha_vencimiento, $politica_rotacion,
                $precio_venta, $precio_oferta, $impuesto_id, $descuento_maximo, $politica_comercial,
                $unidad_venta, $unidad_compra, $lista_precios_id,
                $costo_promedio, $costo_estandar, $costo_ultimo, $costo_reposicion, $metodo_costeo,
                $costo_importacion, $costo_fabricacion,
                $bom_asociado, $ruta_produccion, $tiempo_estandar, $merma_permitida,
                $especificaciones_tecnicas, $parametros_inspeccion, $certificados, $aprobacion_lote,
                $peso, $largo, $ancho, $alto, $volumen,
                $proveedor_principal_id, $lead_time_proveedor,
                $estado, $observaciones, $id
            );

            if ($stmt->execute()) {
                logAuditoria('editar', 'productos', $id, null, json_encode($_POST), 'Producto actualizado: ' . $descripcion_corta);
                $mensaje = 'Producto actualizado exitosamente';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al actualizar producto: ' . $stmt->error;
                $tipo_mensaje = 'error';
            }
        }
    } elseif ($accion === 'eliminar') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE productos SET estado='eliminado', fecha_eliminacion=NOW() WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            logAuditoria('eliminar', 'productos', $id, null, null, 'Producto eliminado');
            $mensaje = 'Producto eliminado exitosamente';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al eliminar producto';
            $tipo_mensaje = 'error';
        }
    }
}

// Obtener datos para el formulario
$unidades_medida = $conn->query("SELECT * FROM unidades_medida ORDER BY nombre");
$categorias = $conn->query("SELECT * FROM categorias_productos ORDER BY nombre");
$familias = $conn->query("SELECT * FROM familias_productos ORDER BY nombre");
$impuestos = $conn->query("SELECT * FROM tipos_impuestos ORDER BY nombre");
$listas_precios = $conn->query("SELECT * FROM listas_precios WHERE estado='activo' ORDER BY nombre");
$proveedores = $conn->query("SELECT * FROM proveedores WHERE estado='activo' ORDER BY nombre_legal");

// Filtros de búsqueda
$buscar = sanitize($_GET['buscar'] ?? '');
$filtro_tipo = sanitize($_GET['filtro_tipo'] ?? '');
$filtro_estado = sanitize($_GET['filtro_estado'] ?? '');
$filtro_categoria = intval($_GET['filtro_categoria'] ?? 0);

// Consulta de productos
$where = ["estado != 'eliminado'"];
$params = [];
$types = '';

if ($buscar) {
    $where[] = "(codigo_material LIKE ? OR descripcion_corta LIKE ? OR codigo_barras LIKE ?)";
    $buscar_param = "%$buscar%";
    $params[] = &$buscar_param;
    $params[] = &$buscar_param;
    $params[] = &$buscar_param;
    $types .= 'sss';
}

if ($filtro_tipo) {
    $where[] = "tipo_material = ?";
    $params[] = &$filtro_tipo;
    $types .= 's';
}

if ($filtro_estado) {
    $where[] = "estado = ?";
    $params[] = &$filtro_estado;
    $types .= 's';
}

if ($filtro_categoria) {
    $where[] = "categoria_id = ?";
    $params[] = &$filtro_categoria;
    $types .= 'i';
}

$sql = "SELECT * FROM productos WHERE " . implode(" AND ", $where) . " ORDER BY descripcion_corta";
$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$productos = $stmt->get_result();

// Modo edición
$editar = null;
if (isset($_GET['editar'])) {
    $id = intval($_GET['editar']);
    $stmt = $conn->prepare("SELECT * FROM productos WHERE id=?");
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
    <title>Maestro de Productos y Servicios - CONECTA ERP</title>
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
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
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
            background: #30cfd0;
            color: white;
        }

        .btn-primary:hover {
            background: #2ab5b6;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(48, 207, 208, 0.3);
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
            border-color: #30cfd0;
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
            border-color: #30cfd0;
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
            color: #30cfd0;
            border-bottom-color: #30cfd0;
        }

        .tab:hover {
            color: #30cfd0;
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
            border-left: 4px solid #30cfd0;
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
            background: #e6fffa;
            border: 1px solid #81e6d9;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #234e52;
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
        <h1>MAESTRO DE PRODUCTOS Y SERVICIOS</h1>
        <p>Maestro de Materiales - Compras / Ventas / Inventario / Produccion / MRP / Calidad</p>
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
            $stats_total = $conn->query("SELECT COUNT(*) as total FROM productos WHERE estado != 'eliminado'")->fetch_assoc()['total'];
            $stats_activos = $conn->query("SELECT COUNT(*) as total FROM productos WHERE estado = 'activo'")->fetch_assoc()['total'];
            $stats_productos = $conn->query("SELECT COUNT(*) as total FROM productos WHERE tipo_material = 'producto'")->fetch_assoc()['total'];
            $stats_servicios = $conn->query("SELECT COUNT(*) as total FROM productos WHERE tipo_material = 'servicio'")->fetch_assoc()['total'];
            ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Materiales</h3>
                    <div class="value"><?php echo number_format($stats_total); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Activos</h3>
                    <div class="value"><?php echo number_format($stats_activos); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Productos</h3>
                    <div class="value"><?php echo number_format($stats_productos); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Servicios</h3>
                    <div class="value"><?php echo number_format($stats_servicios); ?></div>
                </div>
            </div>

            <!-- BARRA DE HERRAMIENTAS -->
            <form method="GET" class="toolbar">
                <a href="?nuevo=1" class="btn btn-primary">+ Nuevo Material</a>

                <div class="search-box">
                    <input type="text" name="buscar" placeholder="Buscar por codigo, descripcion o codigo de barras..." value="<?php echo htmlspecialchars($buscar); ?>">
                </div>

                <select name="filtro_tipo">
                    <option value="">Todos los tipos</option>
                    <option value="producto" <?php echo $filtro_tipo === 'producto' ? 'selected' : ''; ?>>Producto</option>
                    <option value="servicio" <?php echo $filtro_tipo === 'servicio' ? 'selected' : ''; ?>>Servicio</option>
                    <option value="kit" <?php echo $filtro_tipo === 'kit' ? 'selected' : ''; ?>>Kit</option>
                    <option value="conjunto" <?php echo $filtro_tipo === 'conjunto' ? 'selected' : ''; ?>>Conjunto</option>
                    <option value="materia_prima" <?php echo $filtro_tipo === 'materia_prima' ? 'selected' : ''; ?>>Materia Prima</option>
                    <option value="repuesto" <?php echo $filtro_tipo === 'repuesto' ? 'selected' : ''; ?>>Repuesto</option>
                </select>

                <select name="filtro_categoria">
                    <option value="0">Todas las categorias</option>
                    <?php
                    $categorias->data_seek(0);
                    while ($cat = $categorias->fetch_assoc()):
                    ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $filtro_categoria == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo $cat['nombre']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select name="filtro_estado">
                    <option value="">Todos los estados</option>
                    <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                    <option value="inactivo" <?php echo $filtro_estado === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                    <option value="bloqueado" <?php echo $filtro_estado === 'bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
                </select>

                <button type="submit" class="btn btn-secondary">Buscar</button>
                <button type="button" onclick="window.print()" class="btn btn-info">Imprimir</button>
            </form>

            <!-- TABLA DE PRODUCTOS -->
            <div class="card">
                <div class="table-container">
                    <?php if ($productos->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Descripcion</th>
                                    <th>Tipo</th>
                                    <th>Marca</th>
                                    <th>Stock Min</th>
                                    <th>Precio Venta</th>
                                    <th>Costo Promedio</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($producto = $productos->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($producto['codigo_material']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($producto['descripcion_corta']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php
                                                $tipo = $producto['tipo_material'];
                                                echo $tipo === 'producto' ? 'success' :
                                                    ($tipo === 'servicio' ? 'info' :
                                                    ($tipo === 'materia_prima' ? 'warning' : 'secondary'));
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $producto['tipo_material'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($producto['marca']); ?></td>
                                        <td><?php echo number_format($producto['stock_minimo'], 2); ?></td>
                                        <td><?php echo formatCurrency($producto['precio_venta']); ?></td>
                                        <td><?php echo formatCurrency($producto['costo_promedio']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php
                                                echo $producto['estado'] === 'activo' ? 'success' :
                                                    ($producto['estado'] === 'bloqueado' ? 'danger' : 'warning');
                                            ?>">
                                                <?php echo ucfirst($producto['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?editar=<?php echo $producto['id']; ?>" class="btn btn-info btn-sm">Editar</a>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Esta seguro de eliminar este producto?');">
                                                    <input type="hidden" name="accion" value="eliminar">
                                                    <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <h3>No se encontraron productos</h3>
                            <p>Intenta ajustar los filtros o crea un nuevo producto</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- FORMULARIO DE PRODUCTO -->
            <div class="card">
                <form method="POST" style="padding: 30px;">
                    <input type="hidden" name="accion" value="<?php echo $editar ? 'editar' : 'crear'; ?>">
                    <?php if ($editar): ?>
                        <input type="hidden" name="id" value="<?php echo $editar['id']; ?>">
                    <?php endif; ?>

                    <div class="info-box">
                        Complete todos los campos marcados con asterisco como obligatorios. Este maestro integra con Compras, Ventas, Inventario, Produccion, MRP y Calidad.
                    </div>

                    <!-- TABS -->
                    <div class="tabs">
                        <button type="button" class="tab active" onclick="showTab(0)">Datos Generales</button>
                        <button type="button" class="tab" onclick="showTab(1)">Inventario</button>
                        <button type="button" class="tab" onclick="showTab(2)">Datos Comerciales</button>
                        <button type="button" class="tab" onclick="showTab(3)">Costos</button>
                        <button type="button" class="tab" onclick="showTab(4)">Produccion</button>
                        <button type="button" class="tab" onclick="showTab(5)">Calidad</button>
                        <button type="button" class="tab" onclick="showTab(6)">Dimensiones</button>
                        <button type="button" class="tab" onclick="showTab(7)">Proveedor</button>
                    </div>

                    <!-- TAB 1: DATOS GENERALES -->
                    <div class="tab-content active">
                        <div class="form-title">DATOS GENERALES</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Codigo Material <span class="required">*</span></label>
                                <input type="text" name="codigo_material" required
                                       value="<?php echo $editar['codigo_material'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Descripcion Corta <span class="required">*</span></label>
                                <input type="text" name="descripcion_corta" required
                                       value="<?php echo $editar['descripcion_corta'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Unidad de Medida Base <span class="required">*</span></label>
                                <select name="unidad_medida_id" required>
                                    <option value="0">Seleccione...</option>
                                    <?php
                                    $unidades_medida->data_seek(0);
                                    while ($unidad = $unidades_medida->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $unidad['id']; ?>"
                                                <?php echo ($editar['unidad_medida_id'] ?? 0) == $unidad['id'] ? 'selected' : ''; ?>>
                                            <?php echo $unidad['codigo']; ?> - <?php echo $unidad['nombre']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Codigo de Barras</label>
                                <input type="text" name="codigo_barras"
                                       value="<?php echo $editar['codigo_barras'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Codigo QR / RFID</label>
                                <input type="text" name="codigo_qr"
                                       value="<?php echo $editar['codigo_qr'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Tipo de Material <span class="required">*</span></label>
                                <select name="tipo_material" required>
                                    <option value="">Seleccione...</option>
                                    <option value="producto" <?php echo ($editar['tipo_material'] ?? '') === 'producto' ? 'selected' : ''; ?>>Producto</option>
                                    <option value="servicio" <?php echo ($editar['tipo_material'] ?? '') === 'servicio' ? 'selected' : ''; ?>>Servicio</option>
                                    <option value="kit" <?php echo ($editar['tipo_material'] ?? '') === 'kit' ? 'selected' : ''; ?>>Kit</option>
                                    <option value="conjunto" <?php echo ($editar['tipo_material'] ?? '') === 'conjunto' ? 'selected' : ''; ?>>Conjunto</option>
                                    <option value="materia_prima" <?php echo ($editar['tipo_material'] ?? '') === 'materia_prima' ? 'selected' : ''; ?>>Materia Prima</option>
                                    <option value="repuesto" <?php echo ($editar['tipo_material'] ?? '') === 'repuesto' ? 'selected' : ''; ?>>Repuesto</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Marca</label>
                                <input type="text" name="marca"
                                       value="<?php echo $editar['marca'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Modelo</label>
                                <input type="text" name="modelo"
                                       value="<?php echo $editar['modelo'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Categoria</label>
                                <select name="categoria_id">
                                    <option value="0">Seleccione...</option>
                                    <?php
                                    $categorias->data_seek(0);
                                    while ($cat = $categorias->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $cat['id']; ?>"
                                                <?php echo ($editar['categoria_id'] ?? 0) == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo $cat['nombre']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Familia</label>
                                <select name="familia_id">
                                    <option value="0">Seleccione...</option>
                                    <?php
                                    $familias->data_seek(0);
                                    while ($fam = $familias->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $fam['id']; ?>"
                                                <?php echo ($editar['familia_id'] ?? 0) == $fam['id'] ? 'selected' : ''; ?>>
                                            <?php echo $fam['nombre']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Descripcion Larga</label>
                            <textarea name="descripcion_larga" rows="4"><?php echo $editar['descripcion_larga'] ?? ''; ?></textarea>
                        </div>
                    </div>

                    <!-- TAB 2: INVENTARIO -->
                    <div class="tab-content">
                        <div class="form-title">DATOS DE INVENTARIO</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Control de Stock</label>
                                <div class="checkbox-group">
                                    <input type="checkbox" name="control_stock" value="1"
                                           <?php echo ($editar['control_stock'] ?? 0) ? 'checked' : ''; ?>>
                                    <span>Gestionar stock</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Stock Minimo</label>
                                <input type="number" step="0.01" name="stock_minimo"
                                       value="<?php echo $editar['stock_minimo'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Stock Maximo</label>
                                <input type="number" step="0.01" name="stock_maximo"
                                       value="<?php echo $editar['stock_maximo'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Punto de Reposicion</label>
                                <input type="number" step="0.01" name="punto_reposicion"
                                       value="<?php echo $editar['punto_reposicion'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Ubicacion en Bodega</label>
                                <input type="text" name="ubicacion_bodega"
                                       value="<?php echo $editar['ubicacion_bodega'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Lote / Serie</label>
                                <input type="text" name="lote_serie"
                                       value="<?php echo $editar['lote_serie'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Fecha de Vencimiento</label>
                                <input type="date" name="fecha_vencimiento"
                                       value="<?php echo $editar['fecha_vencimiento'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Politica de Rotacion</label>
                                <select name="politica_rotacion">
                                    <option value="">Seleccione...</option>
                                    <option value="FIFO" <?php echo ($editar['politica_rotacion'] ?? '') === 'FIFO' ? 'selected' : ''; ?>>FIFO - Primero en Entrar Primero en Salir</option>
                                    <option value="LIFO" <?php echo ($editar['politica_rotacion'] ?? '') === 'LIFO' ? 'selected' : ''; ?>>LIFO - Ultimo en Entrar Primero en Salir</option>
                                    <option value="FEFO" <?php echo ($editar['politica_rotacion'] ?? '') === 'FEFO' ? 'selected' : ''; ?>>FEFO - Primero en Vencer Primero en Salir</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: DATOS COMERCIALES -->
                    <div class="tab-content">
                        <div class="form-title">DATOS COMERCIALES</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Precio de Venta</label>
                                <input type="number" step="0.01" name="precio_venta"
                                       value="<?php echo $editar['precio_venta'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Precio de Oferta</label>
                                <input type="number" step="0.01" name="precio_oferta"
                                       value="<?php echo $editar['precio_oferta'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Impuesto Asociado</label>
                                <select name="impuesto_id">
                                    <option value="0">Seleccione...</option>
                                    <?php
                                    $impuestos->data_seek(0);
                                    while ($imp = $impuestos->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $imp['id']; ?>"
                                                <?php echo ($editar['impuesto_id'] ?? 0) == $imp['id'] ? 'selected' : ''; ?>>
                                            <?php echo $imp['nombre']; ?> - <?php echo $imp['porcentaje']; ?>%
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Descuento Maximo Permitido (%)</label>
                                <input type="number" step="0.01" name="descuento_maximo"
                                       value="<?php echo $editar['descuento_maximo'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Politica Comercial por Canal</label>
                                <input type="text" name="politica_comercial"
                                       value="<?php echo $editar['politica_comercial'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Unidad de Venta</label>
                                <input type="text" name="unidad_venta"
                                       value="<?php echo $editar['unidad_venta'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Unidad de Compra</label>
                                <input type="text" name="unidad_compra"
                                       value="<?php echo $editar['unidad_compra'] ?? ''; ?>">
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
                        </div>
                    </div>

                    <!-- TAB 4: COSTOS -->
                    <div class="tab-content">
                        <div class="form-title">COSTOS Y VALORIZACION</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Costo Promedio</label>
                                <input type="number" step="0.01" name="costo_promedio"
                                       value="<?php echo $editar['costo_promedio'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Costo Estandar</label>
                                <input type="number" step="0.01" name="costo_estandar"
                                       value="<?php echo $editar['costo_estandar'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Costo Ultimo</label>
                                <input type="number" step="0.01" name="costo_ultimo"
                                       value="<?php echo $editar['costo_ultimo'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Costo de Reposicion</label>
                                <input type="number" step="0.01" name="costo_reposicion"
                                       value="<?php echo $editar['costo_reposicion'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Metodo de Costeo</label>
                                <select name="metodo_costeo">
                                    <option value="">Seleccione...</option>
                                    <option value="promedio" <?php echo ($editar['metodo_costeo'] ?? '') === 'promedio' ? 'selected' : ''; ?>>Costo Promedio Ponderado</option>
                                    <option value="estandar" <?php echo ($editar['metodo_costeo'] ?? '') === 'estandar' ? 'selected' : ''; ?>>Costo Estandar</option>
                                    <option value="fifo" <?php echo ($editar['metodo_costeo'] ?? '') === 'fifo' ? 'selected' : ''; ?>>FIFO</option>
                                    <option value="lifo" <?php echo ($editar['metodo_costeo'] ?? '') === 'lifo' ? 'selected' : ''; ?>>LIFO</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Costo de Importacion</label>
                                <input type="number" step="0.01" name="costo_importacion"
                                       value="<?php echo $editar['costo_importacion'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Costo de Fabricacion</label>
                                <input type="number" step="0.01" name="costo_fabricacion"
                                       value="<?php echo $editar['costo_fabricacion'] ?? '0'; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: PRODUCCION -->
                    <div class="tab-content">
                        <div class="form-title">DATOS DE PRODUCCION</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>BOM Asociado</label>
                                <input type="text" name="bom_asociado"
                                       value="<?php echo $editar['bom_asociado'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Ruta de Produccion</label>
                                <input type="text" name="ruta_produccion"
                                       value="<?php echo $editar['ruta_produccion'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Tiempo Estandar (minutos)</label>
                                <input type="number" name="tiempo_estandar"
                                       value="<?php echo $editar['tiempo_estandar'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Merma Permitida (%)</label>
                                <input type="number" step="0.01" name="merma_permitida"
                                       value="<?php echo $editar['merma_permitida'] ?? '0'; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 6: CALIDAD -->
                    <div class="tab-content">
                        <div class="form-title">CONTROL DE CALIDAD</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Aprobacion de Lote</label>
                                <div class="checkbox-group">
                                    <input type="checkbox" name="aprobacion_lote" value="1"
                                           <?php echo ($editar['aprobacion_lote'] ?? 0) ? 'checked' : ''; ?>>
                                    <span>Requiere aprobacion</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Especificaciones Tecnicas</label>
                                <textarea name="especificaciones_tecnicas"><?php echo $editar['especificaciones_tecnicas'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Parametros de Inspeccion</label>
                                <textarea name="parametros_inspeccion"><?php echo $editar['parametros_inspeccion'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Certificados</label>
                                <textarea name="certificados"><?php echo $editar['certificados'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 7: DIMENSIONES -->
                    <div class="tab-content">
                        <div class="form-title">DIMENSIONES Y PESO</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Peso (kg)</label>
                                <input type="number" step="0.01" name="peso"
                                       value="<?php echo $editar['peso'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Largo (cm)</label>
                                <input type="number" step="0.01" name="largo"
                                       value="<?php echo $editar['largo'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Ancho (cm)</label>
                                <input type="number" step="0.01" name="ancho"
                                       value="<?php echo $editar['ancho'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Alto (cm)</label>
                                <input type="number" step="0.01" name="alto"
                                       value="<?php echo $editar['alto'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Volumen (m3)</label>
                                <input type="number" step="0.001" name="volumen"
                                       value="<?php echo $editar['volumen'] ?? '0'; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 8: PROVEEDOR -->
                    <div class="tab-content">
                        <div class="form-title">PROVEEDOR PRINCIPAL</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Proveedor Principal</label>
                                <select name="proveedor_principal_id">
                                    <option value="0">Seleccione...</option>
                                    <?php
                                    $proveedores->data_seek(0);
                                    while ($prov = $proveedores->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $prov['id']; ?>"
                                                <?php echo ($editar['proveedor_principal_id'] ?? 0) == $prov['id'] ? 'selected' : ''; ?>>
                                            <?php echo $prov['nombre_legal']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Lead Time Proveedor (dias)</label>
                                <input type="number" name="lead_time_proveedor"
                                       value="<?php echo $editar['lead_time_proveedor'] ?? '0'; ?>">
                            </div>
                        </div>

                        <div class="form-title">ESTADO Y OBSERVACIONES</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Estado del Material</label>
                                <select name="estado">
                                    <option value="activo" <?php echo ($editar['estado'] ?? 'activo') === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="inactivo" <?php echo ($editar['estado'] ?? '') === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                    <option value="bloqueado" <?php echo ($editar['estado'] ?? '') === 'bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
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
                            <?php echo $editar ? 'Actualizar Material' : 'Crear Material'; ?>
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
