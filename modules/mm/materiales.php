<?php
/**
 * MODULO: Maestro de Materiales
 * Descripcion: Gestion completa de materiales con todas sus propiedades
 * Modulo: MM - Materials Management
 * Sin dependencias externas - Sin AJAX - UTF-8
 */

require_once __DIR__ . '/../../includes/config.php';

// Verificar autenticacion
if (!isAuthenticated()) {
    header('Location: /index.php');
    exit;
}

$user = getCurrentUser();
$db = Database::getInstance();

// Variables para el formulario
$editMode = false;
$material = null;

// =====================================================
// PROCESAMIENTO DE FORMULARIOS
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = isset($_POST['action']) ? $_POST['action'] : '';

        switch ($action) {
            case 'create':
                // Insertar nuevo material
                $sql = "INSERT INTO mm_maestro_materiales (
                    user_id,
                    -- Identificacion
                    codigo_material, codigo_interno, codigo_barras, descripcion, descripcion_larga,
                    tipo_material, familia, subfamilia, categoria, marca, modelo, sku, estado_material,
                    -- Datos Generales
                    unidad_medida_base, unidad_medida_compra, unidad_medida_venta,
                    factor_conversion_compra, factor_conversion_venta,
                    peso, volumen, dimensiones, color, talla, formato,
                    -- Informacion Comercial
                    precio_base, precio_minimo, precio_maximo, lista_precios, moneda,
                    impuesto_asociado, afecta_iva, tipo_venta, tipo_producto,
                    -- Control de Inventario
                    controla_stock, stock_minimo, stock_maximo, punto_reposicion,
                    lote, serie, vida_util, fecha_vencimiento, politica_rotacion,
                    ubicacion_bodega, bodegas_asociadas, stock_segun_bodega,
                    -- Costos
                    costo_promedio, costo_ultimo, costo_estandar, metodo_costeo,
                    margen_sugerido, markup_sugerido,
                    -- Proveedores
                    proveedor_principal, proveedores_secundarios, codigo_proveedor,
                    costo_proveedor, plazo_entrega, minimo_compra, unidad_compra,
                    -- Datos para Compras
                    permitir_compras, cantidad_minima_compra, cantidad_multiplo_compra,
                    plazo_reposicion, costo_flete, costo_importacion,
                    -- Datos para Ventas
                    permitir_venta, unidad_venta, descuento_maximo, lista_precios_venta,
                    comision_vendedor, aplica_promociones,
                    -- Imagen y Adjuntos
                    imagen_principal, imagen_secundaria, ficha_tecnica, manual, documentos_asociados,
                    -- Integraciones
                    integracion_contabilidad, integracion_inventario, integracion_compras,
                    integracion_ventas, integracion_ecommerce, integracion_pos, integracion_produccion,
                    -- Auditoria
                    usuario_creacion, fecha_creacion, historial_cambios
                ) VALUES (
                    ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, NOW(), ?
                )";

                $historial = 'Material creado';

                $params = [
                    $user['id'],
                    // Identificacion
                    sanitize($_POST['codigo_material']),
                    sanitize($_POST['codigo_interno']),
                    sanitize($_POST['codigo_barras']),
                    sanitize($_POST['descripcion']),
                    sanitize($_POST['descripcion_larga']),
                    sanitize($_POST['tipo_material']),
                    sanitize($_POST['familia']),
                    sanitize($_POST['subfamilia']),
                    sanitize($_POST['categoria']),
                    sanitize($_POST['marca']),
                    sanitize($_POST['modelo']),
                    sanitize($_POST['sku']),
                    sanitize($_POST['estado_material']),
                    // Datos Generales
                    sanitize($_POST['unidad_medida_base']),
                    sanitize($_POST['unidad_medida_compra']),
                    sanitize($_POST['unidad_medida_venta']),
                    floatval($_POST['factor_conversion_compra']),
                    floatval($_POST['factor_conversion_venta']),
                    floatval($_POST['peso']),
                    floatval($_POST['volumen']),
                    sanitize($_POST['dimensiones']),
                    sanitize($_POST['color']),
                    sanitize($_POST['talla']),
                    sanitize($_POST['formato']),
                    // Informacion Comercial
                    floatval($_POST['precio_base']),
                    floatval($_POST['precio_minimo']),
                    floatval($_POST['precio_maximo']),
                    sanitize($_POST['lista_precios']),
                    sanitize($_POST['moneda']),
                    sanitize($_POST['impuesto_asociado']),
                    isset($_POST['afecta_iva']) ? 1 : 0,
                    sanitize($_POST['tipo_venta']),
                    sanitize($_POST['tipo_producto']),
                    // Control de Inventario
                    isset($_POST['controla_stock']) ? 1 : 0,
                    floatval($_POST['stock_minimo']),
                    floatval($_POST['stock_maximo']),
                    floatval($_POST['punto_reposicion']),
                    sanitize($_POST['lote']),
                    sanitize($_POST['serie']),
                    intval($_POST['vida_util']),
                    $_POST['fecha_vencimiento'] ?: null,
                    sanitize($_POST['politica_rotacion']),
                    sanitize($_POST['ubicacion_bodega']),
                    sanitize($_POST['bodegas_asociadas']),
                    sanitize($_POST['stock_segun_bodega']),
                    // Costos
                    floatval($_POST['costo_promedio']),
                    floatval($_POST['costo_ultimo']),
                    floatval($_POST['costo_estandar']),
                    sanitize($_POST['metodo_costeo']),
                    floatval($_POST['margen_sugerido']),
                    floatval($_POST['markup_sugerido']),
                    // Proveedores
                    sanitize($_POST['proveedor_principal']),
                    sanitize($_POST['proveedores_secundarios']),
                    sanitize($_POST['codigo_proveedor']),
                    floatval($_POST['costo_proveedor']),
                    intval($_POST['plazo_entrega']),
                    floatval($_POST['minimo_compra']),
                    sanitize($_POST['unidad_compra']),
                    // Datos para Compras
                    isset($_POST['permitir_compras']) ? 1 : 0,
                    floatval($_POST['cantidad_minima_compra']),
                    floatval($_POST['cantidad_multiplo_compra']),
                    intval($_POST['plazo_reposicion']),
                    floatval($_POST['costo_flete']),
                    floatval($_POST['costo_importacion']),
                    // Datos para Ventas
                    isset($_POST['permitir_venta']) ? 1 : 0,
                    sanitize($_POST['unidad_venta']),
                    floatval($_POST['descuento_maximo']),
                    sanitize($_POST['lista_precios_venta']),
                    floatval($_POST['comision_vendedor']),
                    isset($_POST['aplica_promociones']) ? 1 : 0,
                    // Imagen y Adjuntos
                    sanitize($_POST['imagen_principal']),
                    sanitize($_POST['imagen_secundaria']),
                    sanitize($_POST['ficha_tecnica']),
                    sanitize($_POST['manual']),
                    sanitize($_POST['documentos_asociados']),
                    // Integraciones
                    isset($_POST['integracion_contabilidad']) ? 1 : 0,
                    isset($_POST['integracion_inventario']) ? 1 : 0,
                    isset($_POST['integracion_compras']) ? 1 : 0,
                    isset($_POST['integracion_ventas']) ? 1 : 0,
                    isset($_POST['integracion_ecommerce']) ? 1 : 0,
                    isset($_POST['integracion_pos']) ? 1 : 0,
                    isset($_POST['integracion_produccion']) ? 1 : 0,
                    // Auditoria
                    $user['id'],
                    $historial
                ];

                $db->insert($sql, $params);
                showAlert('Material creado exitosamente', 'success');
                header('Location: materiales.php');
                exit;
                break;

            case 'update':
                // Actualizar material existente
                $material_id = intval($_POST['material_id']);

                $sql = "UPDATE mm_maestro_materiales SET
                    -- Identificacion
                    codigo_material = ?, codigo_interno = ?, codigo_barras = ?,
                    descripcion = ?, descripcion_larga = ?,
                    tipo_material = ?, familia = ?, subfamilia = ?, categoria = ?,
                    marca = ?, modelo = ?, sku = ?, estado_material = ?,
                    -- Datos Generales
                    unidad_medida_base = ?, unidad_medida_compra = ?, unidad_medida_venta = ?,
                    factor_conversion_compra = ?, factor_conversion_venta = ?,
                    peso = ?, volumen = ?, dimensiones = ?, color = ?, talla = ?, formato = ?,
                    -- Informacion Comercial
                    precio_base = ?, precio_minimo = ?, precio_maximo = ?, lista_precios = ?, moneda = ?,
                    impuesto_asociado = ?, afecta_iva = ?, tipo_venta = ?, tipo_producto = ?,
                    -- Control de Inventario
                    controla_stock = ?, stock_minimo = ?, stock_maximo = ?, punto_reposicion = ?,
                    lote = ?, serie = ?, vida_util = ?, fecha_vencimiento = ?, politica_rotacion = ?,
                    ubicacion_bodega = ?, bodegas_asociadas = ?, stock_segun_bodega = ?,
                    -- Costos
                    costo_promedio = ?, costo_ultimo = ?, costo_estandar = ?, metodo_costeo = ?,
                    margen_sugerido = ?, markup_sugerido = ?,
                    -- Proveedores
                    proveedor_principal = ?, proveedores_secundarios = ?, codigo_proveedor = ?,
                    costo_proveedor = ?, plazo_entrega = ?, minimo_compra = ?, unidad_compra = ?,
                    -- Datos para Compras
                    permitir_compras = ?, cantidad_minima_compra = ?, cantidad_multiplo_compra = ?,
                    plazo_reposicion = ?, costo_flete = ?, costo_importacion = ?,
                    -- Datos para Ventas
                    permitir_venta = ?, unidad_venta = ?, descuento_maximo = ?, lista_precios_venta = ?,
                    comision_vendedor = ?, aplica_promociones = ?,
                    -- Imagen y Adjuntos
                    imagen_principal = ?, imagen_secundaria = ?, ficha_tecnica = ?, manual = ?, documentos_asociados = ?,
                    -- Integraciones
                    integracion_contabilidad = ?, integracion_inventario = ?, integracion_compras = ?,
                    integracion_ventas = ?, integracion_ecommerce = ?, integracion_pos = ?, integracion_produccion = ?,
                    -- Auditoria
                    usuario_modificacion = ?, fecha_modificacion = NOW(),
                    historial_cambios = CONCAT(IFNULL(historial_cambios, ''), '\n', ?)
                WHERE id = ? AND user_id = ?";

                $historial = 'Actualizado - ' . date('Y-m-d H:i:s');

                $params = [
                    // Identificacion
                    sanitize($_POST['codigo_material']),
                    sanitize($_POST['codigo_interno']),
                    sanitize($_POST['codigo_barras']),
                    sanitize($_POST['descripcion']),
                    sanitize($_POST['descripcion_larga']),
                    sanitize($_POST['tipo_material']),
                    sanitize($_POST['familia']),
                    sanitize($_POST['subfamilia']),
                    sanitize($_POST['categoria']),
                    sanitize($_POST['marca']),
                    sanitize($_POST['modelo']),
                    sanitize($_POST['sku']),
                    sanitize($_POST['estado_material']),
                    // Datos Generales
                    sanitize($_POST['unidad_medida_base']),
                    sanitize($_POST['unidad_medida_compra']),
                    sanitize($_POST['unidad_medida_venta']),
                    floatval($_POST['factor_conversion_compra']),
                    floatval($_POST['factor_conversion_venta']),
                    floatval($_POST['peso']),
                    floatval($_POST['volumen']),
                    sanitize($_POST['dimensiones']),
                    sanitize($_POST['color']),
                    sanitize($_POST['talla']),
                    sanitize($_POST['formato']),
                    // Informacion Comercial
                    floatval($_POST['precio_base']),
                    floatval($_POST['precio_minimo']),
                    floatval($_POST['precio_maximo']),
                    sanitize($_POST['lista_precios']),
                    sanitize($_POST['moneda']),
                    sanitize($_POST['impuesto_asociado']),
                    isset($_POST['afecta_iva']) ? 1 : 0,
                    sanitize($_POST['tipo_venta']),
                    sanitize($_POST['tipo_producto']),
                    // Control de Inventario
                    isset($_POST['controla_stock']) ? 1 : 0,
                    floatval($_POST['stock_minimo']),
                    floatval($_POST['stock_maximo']),
                    floatval($_POST['punto_reposicion']),
                    sanitize($_POST['lote']),
                    sanitize($_POST['serie']),
                    intval($_POST['vida_util']),
                    $_POST['fecha_vencimiento'] ?: null,
                    sanitize($_POST['politica_rotacion']),
                    sanitize($_POST['ubicacion_bodega']),
                    sanitize($_POST['bodegas_asociadas']),
                    sanitize($_POST['stock_segun_bodega']),
                    // Costos
                    floatval($_POST['costo_promedio']),
                    floatval($_POST['costo_ultimo']),
                    floatval($_POST['costo_estandar']),
                    sanitize($_POST['metodo_costeo']),
                    floatval($_POST['margen_sugerido']),
                    floatval($_POST['markup_sugerido']),
                    // Proveedores
                    sanitize($_POST['proveedor_principal']),
                    sanitize($_POST['proveedores_secundarios']),
                    sanitize($_POST['codigo_proveedor']),
                    floatval($_POST['costo_proveedor']),
                    intval($_POST['plazo_entrega']),
                    floatval($_POST['minimo_compra']),
                    sanitize($_POST['unidad_compra']),
                    // Datos para Compras
                    isset($_POST['permitir_compras']) ? 1 : 0,
                    floatval($_POST['cantidad_minima_compra']),
                    floatval($_POST['cantidad_multiplo_compra']),
                    intval($_POST['plazo_reposicion']),
                    floatval($_POST['costo_flete']),
                    floatval($_POST['costo_importacion']),
                    // Datos para Ventas
                    isset($_POST['permitir_venta']) ? 1 : 0,
                    sanitize($_POST['unidad_venta']),
                    floatval($_POST['descuento_maximo']),
                    sanitize($_POST['lista_precios_venta']),
                    floatval($_POST['comision_vendedor']),
                    isset($_POST['aplica_promociones']) ? 1 : 0,
                    // Imagen y Adjuntos
                    sanitize($_POST['imagen_principal']),
                    sanitize($_POST['imagen_secundaria']),
                    sanitize($_POST['ficha_tecnica']),
                    sanitize($_POST['manual']),
                    sanitize($_POST['documentos_asociados']),
                    // Integraciones
                    isset($_POST['integracion_contabilidad']) ? 1 : 0,
                    isset($_POST['integracion_inventario']) ? 1 : 0,
                    isset($_POST['integracion_compras']) ? 1 : 0,
                    isset($_POST['integracion_ventas']) ? 1 : 0,
                    isset($_POST['integracion_ecommerce']) ? 1 : 0,
                    isset($_POST['integracion_pos']) ? 1 : 0,
                    isset($_POST['integracion_produccion']) ? 1 : 0,
                    // Auditoria
                    $user['id'],
                    $historial,
                    $material_id,
                    $user['id']
                ];

                $db->update($sql, $params);
                showAlert('Material actualizado exitosamente', 'success');
                header('Location: materiales.php');
                exit;
                break;

            case 'delete':
                // Eliminar material
                $material_id = intval($_POST['material_id']);
                $sql = "DELETE FROM mm_maestro_materiales WHERE id = ? AND user_id = ?";
                $db->delete($sql, [$material_id, $user['id']]);
                showAlert('Material eliminado exitosamente', 'success');
                header('Location: materiales.php');
                exit;
                break;
        }
    } catch (Exception $e) {
        showAlert('Error: ' . $e->getMessage(), 'error');
    }
}

// =====================================================
// OBTENER DATOS PARA EDICION
// =====================================================

if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $material_id = intval($_GET['edit']);
    $material = $db->fetchOne(
        "SELECT * FROM mm_maestro_materiales WHERE id = ? AND user_id = ?",
        [$material_id, $user['id']]
    );
    if ($material) {
        $editMode = true;
    }
}

// =====================================================
// OBTENER LISTA DE MATERIALES
// =====================================================

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filtro_familia = isset($_GET['familia']) ? sanitize($_GET['familia']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize($_GET['estado']) : '';

$sql = "SELECT * FROM mm_maestro_materiales WHERE user_id = ?";
$params = [$user['id']];

if ($search) {
    $sql .= " AND (codigo_material LIKE ? OR descripcion LIKE ? OR codigo_barras LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($filtro_familia) {
    $sql .= " AND familia = ?";
    $params[] = $filtro_familia;
}

if ($filtro_estado) {
    $sql .= " AND estado_material = ?";
    $params[] = $filtro_estado;
}

$sql .= " ORDER BY codigo_material ASC";

$materiales = $db->fetchAll($sql, $params);

// Obtener familias unicas para filtro
$familias = $db->fetchAll(
    "SELECT DISTINCT familia FROM mm_maestro_materiales WHERE user_id = ? AND familia IS NOT NULL AND familia != '' ORDER BY familia",
    [$user['id']]
);

// Obtener alerta si existe
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maestro de Materiales - CONECTA ERP</title>
    <link rel="stylesheet" href="/assets/css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0a0a0a;
            color: #e5e5e5;
            min-height: 100vh;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: #111;
            border-right: 1px solid #222;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar h2 {
            color: #6366f1;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 15px;
            color: #999;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #1a1a1a;
            color: #6366f1;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 20px;
        }

        .topbar {
            background: #111;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar h1 {
            font-size: 24px;
            color: #fff;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-primary:hover {
            background: #5558e3;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-secondary {
            background: #4b5563;
            color: white;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }

        .card {
            background: #111;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid #222;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #d1d5db;
            font-size: 14px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            color: #e5e5e5;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6366f1;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        thead {
            background: #1a1a1a;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #222;
        }

        th {
            color: #9ca3af;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            color: #e5e5e5;
            font-size: 14px;
        }

        tr:hover {
            background: #1a1a1a;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .badge-info {
            background: rgba(99, 102, 241, 0.2);
            color: #6366f1;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            overflow-y: auto;
            padding: 20px;
        }

        .modal.active {
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }

        .modal-content {
            background: #111;
            border-radius: 12px;
            width: 100%;
            max-width: 1200px;
            margin: 20px auto;
            border: 1px solid #222;
        }

        .modal-header {
            padding: 25px 30px;
            border-bottom: 1px solid #222;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            color: #fff;
            font-size: 20px;
        }

        .modal-body {
            padding: 30px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #222;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .close-modal {
            background: none;
            border: none;
            color: #999;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-modal:hover {
            color: #fff;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h3 {
            color: #6366f1;
            font-size: 16px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #222;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid #10b981;
            color: #10b981;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #ef4444;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid #f59e0b;
            color: #f59e0b;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .filters {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2><i class="fas fa-cube"></i> Materials Management</h2>
            <nav class="sidebar-menu">
                <a href="/modules/mm/index.php"><i class="fas fa-home"></i> Inicio MM</a>
                <a href="/modules/mm/productos.php"><i class="fas fa-box"></i> Productos</a>
                <a href="/modules/mm/materiales.php" class="active"><i class="fas fa-cubes"></i> Materiales</a>
                <a href="/modules/mm/almacenes.php"><i class="fas fa-warehouse"></i> Almacenes</a>
                <a href="/modules/mm/inventario.php"><i class="fas fa-clipboard-list"></i> Inventario</a>
                <a href="/modules/mm/proveedores.php"><i class="fas fa-truck"></i> Proveedores</a>
                <a href="/user/dashboard_user.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
                <a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesion</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Topbar -->
            <div class="topbar">
                <h1><i class="fas fa-cubes"></i> Maestro de Materiales</h1>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Nuevo Material
                </button>
            </div>

            <?php if ($alert): ?>
                <div class="alert alert-<?php echo $alert['type']; ?>">
                    <i class="fas fa-<?php echo $alert['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($alert['message']); ?></span>
                </div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="card">
                <form method="GET" action="materiales.php">
                    <div class="filters">
                        <div class="form-group">
                            <label>Buscar</label>
                            <input type="text" name="search" placeholder="Codigo, descripcion, codigo de barras..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <label>Familia</label>
                            <select name="familia">
                                <option value="">Todas las familias</option>
                                <?php foreach ($familias as $fam): ?>
                                    <option value="<?php echo htmlspecialchars($fam['familia']); ?>" <?php echo $filtro_familia === $fam['familia'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($fam['familia']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Estado</label>
                            <select name="estado">
                                <option value="">Todos los estados</option>
                                <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                <option value="inactivo" <?php echo $filtro_estado === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                <option value="descontinuado" <?php echo $filtro_estado === 'descontinuado' ? 'selected' : ''; ?>>Descontinuado</option>
                                <option value="en_evaluacion" <?php echo $filtro_estado === 'en_evaluacion' ? 'selected' : ''; ?>>En Evaluacion</option>
                            </select>
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabla de Materiales -->
            <div class="card">
                <div class="table-container">
                    <?php if (count($materiales) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Descripcion</th>
                                    <th>Familia</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Stock Min/Max</th>
                                    <th>Costo</th>
                                    <th>Precio</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($materiales as $mat): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($mat['codigo_material']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($mat['descripcion']); ?></td>
                                        <td><?php echo htmlspecialchars($mat['familia'] ?: '-'); ?></td>
                                        <td>
                                            <?php
                                            $tipo_badge = [
                                                'normal' => 'info',
                                                'servicio' => 'success',
                                                'conjunto' => 'warning',
                                                'kit' => 'danger'
                                            ];
                                            $badge_class = $tipo_badge[$mat['tipo_producto']] ?? 'info';
                                            ?>
                                            <span class="badge badge-<?php echo $badge_class; ?>">
                                                <?php echo htmlspecialchars($mat['tipo_producto']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $estado_badge = [
                                                'activo' => 'success',
                                                'inactivo' => 'warning',
                                                'descontinuado' => 'danger',
                                                'en_evaluacion' => 'info'
                                            ];
                                            $badge_class = $estado_badge[$mat['estado_material']] ?? 'info';
                                            ?>
                                            <span class="badge badge-<?php echo $badge_class; ?>">
                                                <?php echo htmlspecialchars($mat['estado_material']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($mat['stock_minimo'], 2); ?> / <?php echo number_format($mat['stock_maximo'], 2); ?></td>
                                        <td><?php echo $mat['moneda']; ?> <?php echo number_format($mat['costo_promedio'], 2); ?></td>
                                        <td><?php echo $mat['moneda']; ?> <?php echo number_format($mat['precio_base'], 2); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="materiales.php?edit=<?php echo $mat['id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Seguro que desea eliminar este material?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="material_id" value="<?php echo $mat['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-cubes"></i>
                            <h3>No hay materiales registrados</h3>
                            <p>Comienza creando tu primer material</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Formulario -->
    <div id="materialModal" class="modal <?php echo $editMode ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-<?php echo $editMode ? 'edit' : 'plus'; ?>"></i>
                    <?php echo $editMode ? 'Editar Material' : 'Nuevo Material'; ?>
                </h2>
                <button class="close-modal" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="materiales.php">
                <input type="hidden" name="action" value="<?php echo $editMode ? 'update' : 'create'; ?>">
                <?php if ($editMode): ?>
                    <input type="hidden" name="material_id" value="<?php echo $material['id']; ?>">
                <?php endif; ?>

                <div class="modal-body">
                    <!-- Seccion: Identificacion del Material -->
                    <div class="form-section">
                        <h3><i class="fas fa-fingerprint"></i> Identificacion del Material</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Codigo Material <span style="color: #ef4444;">*</span></label>
                                <input type="text" name="codigo_material" required value="<?php echo $editMode ? htmlspecialchars($material['codigo_material']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Codigo Interno</label>
                                <input type="text" name="codigo_interno" value="<?php echo $editMode ? htmlspecialchars($material['codigo_interno']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Codigo de Barras</label>
                                <input type="text" name="codigo_barras" value="<?php echo $editMode ? htmlspecialchars($material['codigo_barras']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Descripcion <span style="color: #ef4444;">*</span></label>
                                <input type="text" name="descripcion" required value="<?php echo $editMode ? htmlspecialchars($material['descripcion']) : ''; ?>">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>Descripcion Larga</label>
                                <textarea name="descripcion_larga"><?php echo $editMode ? htmlspecialchars($material['descripcion_larga']) : ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Tipo de Material</label>
                                <input type="text" name="tipo_material" value="<?php echo $editMode ? htmlspecialchars($material['tipo_material']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Familia</label>
                                <input type="text" name="familia" value="<?php echo $editMode ? htmlspecialchars($material['familia']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Subfamilia</label>
                                <input type="text" name="subfamilia" value="<?php echo $editMode ? htmlspecialchars($material['subfamilia']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Categoria</label>
                                <input type="text" name="categoria" value="<?php echo $editMode ? htmlspecialchars($material['categoria']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Marca</label>
                                <input type="text" name="marca" value="<?php echo $editMode ? htmlspecialchars($material['marca']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Modelo</label>
                                <input type="text" name="modelo" value="<?php echo $editMode ? htmlspecialchars($material['modelo']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>SKU</label>
                                <input type="text" name="sku" value="<?php echo $editMode ? htmlspecialchars($material['sku']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Estado del Material</label>
                                <select name="estado_material">
                                    <option value="activo" <?php echo ($editMode && $material['estado_material'] === 'activo') ? 'selected' : ''; ?>>Activo</option>
                                    <option value="inactivo" <?php echo ($editMode && $material['estado_material'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                                    <option value="descontinuado" <?php echo ($editMode && $material['estado_material'] === 'descontinuado') ? 'selected' : ''; ?>>Descontinuado</option>
                                    <option value="en_evaluacion" <?php echo ($editMode && $material['estado_material'] === 'en_evaluacion') ? 'selected' : ''; ?>>En Evaluacion</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Seccion: Datos Generales -->
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> Datos Generales</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Unidad de Medida Base</label>
                                <input type="text" name="unidad_medida_base" value="<?php echo $editMode ? htmlspecialchars($material['unidad_medida_base']) : ''; ?>" placeholder="UN, KG, M, etc.">
                            </div>
                            <div class="form-group">
                                <label>Unidad de Medida Compra</label>
                                <input type="text" name="unidad_medida_compra" value="<?php echo $editMode ? htmlspecialchars($material['unidad_medida_compra']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Unidad de Medida Venta</label>
                                <input type="text" name="unidad_medida_venta" value="<?php echo $editMode ? htmlspecialchars($material['unidad_medida_venta']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Factor Conversion Compra</label>
                                <input type="number" step="0.0001" name="factor_conversion_compra" value="<?php echo $editMode ? $material['factor_conversion_compra'] : '1.0000'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Factor Conversion Venta</label>
                                <input type="number" step="0.0001" name="factor_conversion_venta" value="<?php echo $editMode ? $material['factor_conversion_venta'] : '1.0000'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Peso (Kg)</label>
                                <input type="number" step="0.0001" name="peso" value="<?php echo $editMode ? $material['peso'] : '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Volumen (M3)</label>
                                <input type="number" step="0.0001" name="volumen" value="<?php echo $editMode ? $material['volumen'] : '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Dimensiones</label>
                                <input type="text" name="dimensiones" value="<?php echo $editMode ? htmlspecialchars($material['dimensiones']) : ''; ?>" placeholder="Largo x Ancho x Alto">
                            </div>
                            <div class="form-group">
                                <label>Color</label>
                                <input type="text" name="color" value="<?php echo $editMode ? htmlspecialchars($material['color']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Talla</label>
                                <input type="text" name="talla" value="<?php echo $editMode ? htmlspecialchars($material['talla']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Formato</label>
                                <input type="text" name="formato" value="<?php echo $editMode ? htmlspecialchars($material['formato']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Seccion: Informacion Comercial -->
                    <div class="form-section">
                        <h3><i class="fas fa-dollar-sign"></i> Informacion Comercial</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Precio Base</label>
                                <input type="number" step="0.01" name="precio_base" value="<?php echo $editMode ? $material['precio_base'] : '0.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Precio Minimo</label>
                                <input type="number" step="0.01" name="precio_minimo" value="<?php echo $editMode ? $material['precio_minimo'] : '0.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Precio Maximo</label>
                                <input type="number" step="0.01" name="precio_maximo" value="<?php echo $editMode ? $material['precio_maximo'] : '0.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Lista de Precios</label>
                                <input type="text" name="lista_precios" value="<?php echo $editMode ? htmlspecialchars($material['lista_precios']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Moneda</label>
                                <select name="moneda">
                                    <option value="CLP" <?php echo ($editMode && $material['moneda'] === 'CLP') ? 'selected' : ''; ?>>CLP - Peso Chileno</option>
                                    <option value="USD" <?php echo ($editMode && $material['moneda'] === 'USD') ? 'selected' : ''; ?>>USD - Dolar</option>
                                    <option value="EUR" <?php echo ($editMode && $material['moneda'] === 'EUR') ? 'selected' : ''; ?>>EUR - Euro</option>
                                    <option value="BRL" <?php echo ($editMode && $material['moneda'] === 'BRL') ? 'selected' : ''; ?>>BRL - Real</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Impuesto Asociado</label>
                                <input type="text" name="impuesto_asociado" value="<?php echo $editMode ? htmlspecialchars($material['impuesto_asociado']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Tipo de Venta</label>
                                <input type="text" name="tipo_venta" value="<?php echo $editMode ? htmlspecialchars($material['tipo_venta']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Tipo de Producto</label>
                                <select name="tipo_producto">
                                    <option value="normal" <?php echo ($editMode && $material['tipo_producto'] === 'normal') ? 'selected' : ''; ?>>Normal</option>
                                    <option value="servicio" <?php echo ($editMode && $material['tipo_producto'] === 'servicio') ? 'selected' : ''; ?>>Servicio</option>
                                    <option value="conjunto" <?php echo ($editMode && $material['tipo_producto'] === 'conjunto') ? 'selected' : ''; ?>>Conjunto</option>
                                    <option value="kit" <?php echo ($editMode && $material['tipo_producto'] === 'kit') ? 'selected' : ''; ?>>Kit</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="afecta_iva" id="afecta_iva" value="1" <?php echo ($editMode && $material['afecta_iva']) ? 'checked' : 'checked'; ?>>
                                    <label for="afecta_iva" style="margin: 0;">Afecto a IVA</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seccion: Control de Inventario -->
                    <div class="form-section">
                        <h3><i class="fas fa-boxes"></i> Control de Inventario</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="controla_stock" id="controla_stock" value="1" <?php echo ($editMode && $material['controla_stock']) ? 'checked' : 'checked'; ?>>
                                    <label for="controla_stock" style="margin: 0;">Controla Stock</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Stock Minimo</label>
                                <input type="number" step="0.01" name="stock_minimo" value="<?php echo $editMode ? $material['stock_minimo'] : '0.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Stock Maximo</label>
                                <input type="number" step="0.01" name="stock_maximo" value="<?php echo $editMode ? $material['stock_maximo'] : '0.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Punto de Reposicion</label>
                                <input type="number" step="0.01" name="punto_reposicion" value="<?php echo $editMode ? $material['punto_reposicion'] : '0.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Lote</label>
                                <input type="text" name="lote" value="<?php echo $editMode ? htmlspecialchars($material['lote']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Serie</label>
                                <input type="text" name="serie" value="<?php echo $editMode ? htmlspecialchars($material['serie']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Vida Util (dias)</label>
                                <input type="number" name="vida_util" value="<?php echo $editMode ? $material['vida_util'] : '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Fecha de Vencimiento</label>
                                <input type="date" name="fecha_vencimiento" value="<?php echo $editMode ? $material['fecha_vencimiento'] : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Politica de Rotacion</label>
                                <select name="politica_rotacion">
                                    <option value="FIFO" <?php echo ($editMode && $material['politica_rotacion'] === 'FIFO') ? 'selected' : ''; ?>>FIFO - Primero en Entrar, Primero en Salir</option>
                                    <option value="LIFO" <?php echo ($editMode && $material['politica_rotacion'] === 'LIFO') ? 'selected' : ''; ?>>LIFO - Ultimo en Entrar, Primero en Salir</option>
                                    <option value="FEFO" <?php echo ($editMode && $material['politica_rotacion'] === 'FEFO') ? 'selected' : ''; ?>>FEFO - Primero en Vencer, Primero en Salir</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Ubicacion en Bodega</label>
                                <input type="text" name="ubicacion_bodega" value="<?php echo $editMode ? htmlspecialchars($material['ubicacion_bodega']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Bodegas Asociadas</label>
                                <input type="text" name="bodegas_asociadas" value="<?php echo $editMode ? htmlspecialchars($material['bodegas_asociadas']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Stock Segun Bodega</label>
                                <input type="text" name="stock_segun_bodega" value="<?php echo $editMode ? htmlspecialchars($material['stock_segun_bodega']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Seccion: Costos -->
                    <div class="form-section">
                        <h3><i class="fas fa-calculator"></i> Costos</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Costo Promedio</label>
                                <input type="number" step="0.01" name="costo_promedio" value="<?php echo $editMode ? $material['costo_promedio'] : '0.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Costo Ultimo</label>
                                <input type="number" step="0.01" name="costo_ultimo" value="<?php echo $editMode ? $material['costo_ultimo'] : '0.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Costo Estandar</label>
                                <input type="number" step="0.01" name="costo_estandar" value="<?php echo $editMode ? $material['costo_estandar'] : '0.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Metodo de Costeo</label>
                                <select name="metodo_costeo">
                                    <option value="promedio" <?php echo ($editMode && $material['metodo_costeo'] === 'promedio') ? 'selected' : ''; ?>>Promedio Ponderado</option>
                                    <option value="fifo" <?php echo ($editMode && $material['metodo_costeo'] === 'fifo') ? 'selected' : ''; ?>>FIFO</option>
                                    <option value="lifo" <?php echo ($editMode && $material['metodo_costeo'] === 'lifo') ? 'selected' : ''; ?>>LIFO</option>
                                    <option value="estandar" <?php echo ($editMode && $material['metodo_costeo'] === 'estandar') ? 'selected' : ''; ?>>Costo Estandar</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Margen Sugerido (%)</label>
                                <input type="number" step="0.01" name="margen_sugerido" value="<?php echo $editMode ? $material['margen_sugerido'] : '0.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Markup Sugerido (%)</label>
                                <input type="number" step="0.01" name="markup_sugerido" value="<?php echo $editMode ? $material['markup_sugerido'] : '0.00'; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Seccion: Proveedores -->
                    <div class="form-section">
                        <h3><i class="fas fa-truck"></i> Proveedores</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Proveedor Principal</label>
                                <input type="text" name="proveedor_principal" value="<?php echo $editMode ? htmlspecialchars($material['proveedor_principal']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Codigo en Proveedor</label>
                                <input type="text" name="codigo_proveedor" value="<?php echo $editMode ? htmlspecialchars($material['codigo_proveedor']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Costo del Proveedor</label>
                                <input type="number" step="0.01" name="costo_proveedor" value="<?php echo $editMode ? $material['costo_proveedor'] : '0.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Plazo de Entrega (dias)</label>
                                <input type="number" name="plazo_entrega" value="<?php echo $editMode ? $material['plazo_entrega'] : '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Minimo de Compra</label>
                                <input type="number" step="0.01" name="minimo_compra" value="<?php echo $editMode ? $material['minimo_compra'] : '0.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Unidad de Compra</label>
                                <input type="text" name="unidad_compra" value="<?php echo $editMode ? htmlspecialchars($material['unidad_compra']) : ''; ?>">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>Proveedores Secundarios</label>
                                <textarea name="proveedores_secundarios"><?php echo $editMode ? htmlspecialchars($material['proveedores_secundarios']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Seccion: Datos para Compras -->
                    <div class="form-section">
                        <h3><i class="fas fa-shopping-cart"></i> Datos para Compras</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="permitir_compras" id="permitir_compras" value="1" <?php echo ($editMode && $material['permitir_compras']) ? 'checked' : 'checked'; ?>>
                                    <label for="permitir_compras" style="margin: 0;">Permitir Compras</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Cantidad Minima de Compra</label>
                                <input type="number" step="0.01" name="cantidad_minima_compra" value="<?php echo $editMode ? $material['cantidad_minima_compra'] : '0.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Cantidad Multiplo de Compra</label>
                                <input type="number" step="0.01" name="cantidad_multiplo_compra" value="<?php echo $editMode ? $material['cantidad_multiplo_compra'] : '1.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Plazo de Reposicion (dias)</label>
                                <input type="number" name="plazo_reposicion" value="<?php echo $editMode ? $material['plazo_reposicion'] : '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Costo de Flete</label>
                                <input type="number" step="0.01" name="costo_flete" value="<?php echo $editMode ? $material['costo_flete'] : '0.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Costo de Importacion</label>
                                <input type="number" step="0.01" name="costo_importacion" value="<?php echo $editMode ? $material['costo_importacion'] : '0.00'; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Seccion: Datos para Ventas -->
                    <div class="form-section">
                        <h3><i class="fas fa-cash-register"></i> Datos para Ventas</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="permitir_venta" id="permitir_venta" value="1" <?php echo ($editMode && $material['permitir_venta']) ? 'checked' : 'checked'; ?>>
                                    <label for="permitir_venta" style="margin: 0;">Permitir Venta</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="aplica_promociones" id="aplica_promociones" value="1" <?php echo ($editMode && $material['aplica_promociones']) ? 'checked' : 'checked'; ?>>
                                    <label for="aplica_promociones" style="margin: 0;">Aplica Promociones</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Unidad de Venta</label>
                                <input type="text" name="unidad_venta" value="<?php echo $editMode ? htmlspecialchars($material['unidad_venta']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Descuento Maximo (%)</label>
                                <input type="number" step="0.01" name="descuento_maximo" value="<?php echo $editMode ? $material['descuento_maximo'] : '0.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label>Lista de Precios Venta</label>
                                <input type="text" name="lista_precios_venta" value="<?php echo $editMode ? htmlspecialchars($material['lista_precios_venta']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Comision Vendedor (%)</label>
                                <input type="number" step="0.01" name="comision_vendedor" value="<?php echo $editMode ? $material['comision_vendedor'] : '0.00'; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Seccion: Imagen y Adjuntos -->
                    <div class="form-section">
                        <h3><i class="fas fa-image"></i> Imagen y Adjuntos</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Imagen Principal (URL)</label>
                                <input type="text" name="imagen_principal" value="<?php echo $editMode ? htmlspecialchars($material['imagen_principal']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Imagen Secundaria (URL)</label>
                                <input type="text" name="imagen_secundaria" value="<?php echo $editMode ? htmlspecialchars($material['imagen_secundaria']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Ficha Tecnica (URL)</label>
                                <input type="text" name="ficha_tecnica" value="<?php echo $editMode ? htmlspecialchars($material['ficha_tecnica']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Manual (URL)</label>
                                <input type="text" name="manual" value="<?php echo $editMode ? htmlspecialchars($material['manual']) : ''; ?>">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>Documentos Asociados</label>
                                <textarea name="documentos_asociados"><?php echo $editMode ? htmlspecialchars($material['documentos_asociados']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Seccion: Integraciones -->
                    <div class="form-section">
                        <h3><i class="fas fa-plug"></i> Integraciones</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="integracion_contabilidad" id="integracion_contabilidad" value="1" <?php echo ($editMode && $material['integracion_contabilidad']) ? 'checked' : ''; ?>>
                                    <label for="integracion_contabilidad" style="margin: 0;">Contabilidad</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="integracion_inventario" id="integracion_inventario" value="1" <?php echo ($editMode && $material['integracion_inventario']) ? 'checked' : 'checked'; ?>>
                                    <label for="integracion_inventario" style="margin: 0;">Inventario</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="integracion_compras" id="integracion_compras" value="1" <?php echo ($editMode && $material['integracion_compras']) ? 'checked' : 'checked'; ?>>
                                    <label for="integracion_compras" style="margin: 0;">Compras</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="integracion_ventas" id="integracion_ventas" value="1" <?php echo ($editMode && $material['integracion_ventas']) ? 'checked' : 'checked'; ?>>
                                    <label for="integracion_ventas" style="margin: 0;">Ventas</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="integracion_ecommerce" id="integracion_ecommerce" value="1" <?php echo ($editMode && $material['integracion_ecommerce']) ? 'checked' : ''; ?>>
                                    <label for="integracion_ecommerce" style="margin: 0;">E-Commerce</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="integracion_pos" id="integracion_pos" value="1" <?php echo ($editMode && $material['integracion_pos']) ? 'checked' : ''; ?>>
                                    <label for="integracion_pos" style="margin: 0;">Punto de Venta (POS)</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="integracion_produccion" id="integracion_produccion" value="1" <?php echo ($editMode && $material['integracion_produccion']) ? 'checked' : ''; ?>>
                                    <label for="integracion_produccion" style="margin: 0;">Produccion</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> <?php echo $editMode ? 'Actualizar' : 'Crear'; ?> Material
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('materialModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('materialModal').classList.remove('active');
            document.body.style.overflow = 'auto';
            // Limpiar URL si estamos en modo edicion
            if (window.location.search.includes('edit=')) {
                window.location.href = 'materiales.php';
            }
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('materialModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Prevenir cierre al hacer clic dentro del contenido
        document.querySelector('.modal-content').addEventListener('click', function(e) {
            e.stopPropagation();
        });
    </script>
</body>
</html>
