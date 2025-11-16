<?php
/**
 * API REST - PRODUCTOS
 * Endpoint para gestión de productos y servicios
 * Métodos: GET, POST, PUT, DELETE
 */

require_once 'config.php';

$auth = validarAPIKey();
$empresa_id = $auth['empresa_id'];
$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $producto_id = $_GET['id'] ?? null;

    if ($producto_id) {
        $stmt = $conn->prepare("SELECT p.*, c.nombre as categoria_nombre,
            (SELECT COALESCE(SUM(cantidad), 0) FROM inventario WHERE producto_id = p.id) as stock_total
            FROM productos p
            LEFT JOIN categorias_producto c ON p.categoria_id = c.id
            WHERE p.id = ? AND p.empresa_id = ?");
        $stmt->bind_param("ii", $producto_id, $empresa_id);
        $stmt->execute();
        $producto = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$producto) {
            enviarError(404, 'Producto no encontrado', 'PRODUCT_NOT_FOUND');
        }

        enviarExito($producto);
    } else {
        $paginacion = obtenerParametrosPaginacion();
        $busqueda = $_GET['search'] ?? '';
        $categoria = $_GET['categoria'] ?? '';
        $tipo = $_GET['tipo'] ?? '';

        $where = ["p.empresa_id = ?"];
        $params = [$empresa_id];
        $types = "i";

        if ($busqueda) {
            $where[] = "(p.nombre LIKE ? OR p.codigo LIKE ? OR p.codigo_barra LIKE ?)";
            $busqueda_param = "%{$busqueda}%";
            $params = array_merge($params, [$busqueda_param, $busqueda_param, $busqueda_param]);
            $types .= "sss";
        }

        if ($categoria) {
            $where[] = "p.categoria_id = ?";
            $params[] = $categoria;
            $types .= "i";
        }

        if ($tipo) {
            $where[] = "p.tipo = ?";
            $params[] = $tipo;
            $types .= "s";
        }

        $where_sql = implode(' AND ', $where);

        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM productos p WHERE {$where_sql}");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $stmt = $conn->prepare("SELECT p.*, c.nombre as categoria_nombre,
            (SELECT COALESCE(SUM(cantidad), 0) FROM inventario WHERE producto_id = p.id) as stock_total
            FROM productos p
            LEFT JOIN categorias_producto c ON p.categoria_id = c.id
            WHERE {$where_sql}
            ORDER BY p.nombre
            LIMIT ? OFFSET ?");

        $params[] = $paginacion['per_page'];
        $params[] = $paginacion['offset'];
        $types .= "ii";

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $respuesta = respuestaPaginada($productos, $total, $paginacion);
        enviarExito($respuesta);
    }
}

elseif ($metodo === 'POST') {
    $datos = obtenerDatosInput();
    validarParametros($datos, ['nombre', 'tipo', 'precio_venta']);

    $nombre = $datos['nombre'];
    $codigo = $datos['codigo'] ?? null;
    $codigo_barra = $datos['codigo_barra'] ?? null;
    $tipo = $datos['tipo'];
    $descripcion = $datos['descripcion'] ?? '';
    $categoria_id = $datos['categoria_id'] ?? null;
    $precio_costo = floatval($datos['precio_costo'] ?? 0);
    $precio_venta = floatval($datos['precio_venta']);
    $unidad_medida = $datos['unidad_medida'] ?? 'UND';
    $stock_minimo = floatval($datos['stock_minimo'] ?? 0);
    $gravado = isset($datos['gravado']) ? intval($datos['gravado']) : 1;

    $stmt = $conn->prepare("INSERT INTO productos
        (empresa_id, nombre, codigo, codigo_barra, tipo, descripcion, categoria_id,
         precio_costo, precio_venta, unidad_medida, stock_minimo, gravado, activo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

    $stmt->bind_param("issssssddsdl", $empresa_id, $nombre, $codigo, $codigo_barra, $tipo,
        $descripcion, $categoria_id, $precio_costo, $precio_venta, $unidad_medida,
        $stock_minimo, $gravado);

    if ($stmt->execute()) {
        $producto_id = $conn->insert_id;
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $producto = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        enviarExito($producto, 'Producto creado exitosamente', 201);
    } else {
        enviarError(500, 'Error al crear producto: ' . $stmt->error, 'DATABASE_ERROR');
    }
}

elseif ($metodo === 'PUT') {
    $datos = obtenerDatosInput();
    validarParametros($datos, ['id']);

    $producto_id = intval($datos['id']);

    $stmt = $conn->prepare("SELECT id FROM productos WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $producto_id, $empresa_id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        enviarError(404, 'Producto no encontrado', 'PRODUCT_NOT_FOUND');
    }
    $stmt->close();

    $campos_permitidos = ['nombre', 'codigo', 'codigo_barra', 'descripcion', 'categoria_id',
        'precio_costo', 'precio_venta', 'unidad_medida', 'stock_minimo', 'gravado', 'activo'];

    $sets = [];
    $params = [];
    $types = "";

    foreach ($campos_permitidos as $campo) {
        if (isset($datos[$campo])) {
            $sets[] = "{$campo} = ?";
            $params[] = $datos[$campo];
            $types .= is_numeric($datos[$campo]) ? "d" : "s";
        }
    }

    if (empty($sets)) {
        enviarError(400, 'No hay campos para actualizar', 'NO_FIELDS_TO_UPDATE');
    }

    $params[] = $producto_id;
    $params[] = $empresa_id;
    $types .= "ii";

    $sql = "UPDATE productos SET " . implode(', ', $sets) . " WHERE id = ? AND empresa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $producto = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        enviarExito($producto, 'Producto actualizado exitosamente');
    } else {
        enviarError(500, 'Error al actualizar producto: ' . $stmt->error, 'DATABASE_ERROR');
    }
}

elseif ($metodo === 'DELETE') {
    $producto_id = $_GET['id'] ?? null;

    if (!$producto_id) {
        enviarError(400, 'ID de producto requerido', 'MISSING_ID');
    }

    $stmt = $conn->prepare("UPDATE productos SET activo = 0 WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $producto_id, $empresa_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $stmt->close();
        enviarExito(null, 'Producto eliminado exitosamente');
    } else {
        enviarError(404, 'Producto no encontrado', 'PRODUCT_NOT_FOUND');
    }
}

else {
    enviarError(405, 'Método no permitido', 'METHOD_NOT_ALLOWED');
}
