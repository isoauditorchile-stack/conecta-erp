<?php
/**
 * API REST - INVENTARIO
 * Endpoint para consulta y gestión de inventario
 * Métodos: GET, POST (movimientos)
 */

require_once 'config.php';

$auth = validarAPIKey();
$empresa_id = $auth['empresa_id'];
$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $producto_id = $_GET['producto_id'] ?? null;
    $almacen_id = $_GET['almacen_id'] ?? null;
    $stock_bajo = isset($_GET['stock_bajo']) && $_GET['stock_bajo'] == '1';

    $paginacion = obtenerParametrosPaginacion();

    $where = ["i.empresa_id = ?"];
    $params = [$empresa_id];
    $types = "i";

    if ($producto_id) {
        $where[] = "i.producto_id = ?";
        $params[] = $producto_id;
        $types .= "i";
    }

    if ($almacen_id) {
        $where[] = "i.almacen_id = ?";
        $params[] = $almacen_id;
        $types .= "i";
    }

    if ($stock_bajo) {
        $where[] = "i.cantidad < p.stock_minimo";
    }

    $where_sql = implode(' AND ', $where);

    $stmt = $conn->prepare("SELECT COUNT(*) as total
        FROM inventario i
        INNER JOIN productos p ON i.producto_id = p.id
        WHERE {$where_sql}");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT i.*, p.nombre as producto_nombre, p.codigo as producto_codigo,
        p.stock_minimo, a.nombre as almacen_nombre,
        (i.cantidad * p.precio_costo) as valor_inventario
        FROM inventario i
        INNER JOIN productos p ON i.producto_id = p.id
        LEFT JOIN almacenes a ON i.almacen_id = a.id
        WHERE {$where_sql}
        ORDER BY p.nombre
        LIMIT ? OFFSET ?");

    $params[] = $paginacion['per_page'];
    $params[] = $paginacion['offset'];
    $types .= "ii";

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $inventario = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $respuesta = respuestaPaginada($inventario, $total, $paginacion);
    enviarExito($respuesta);
}

elseif ($metodo === 'POST') {
    // Registrar movimiento de inventario
    $datos = obtenerDatosInput();
    validarParametros($datos, ['producto_id', 'almacen_id', 'tipo_movimiento', 'cantidad']);

    $producto_id = intval($datos['producto_id']);
    $almacen_id = intval($datos['almacen_id']);
    $tipo_movimiento = $datos['tipo_movimiento']; // entrada, salida, ajuste, transferencia
    $cantidad = floatval($datos['cantidad']);
    $referencia = $datos['referencia'] ?? '';
    $observaciones = $datos['observaciones'] ?? '';

    if ($cantidad <= 0) {
        enviarError(400, 'La cantidad debe ser mayor a 0', 'INVALID_QUANTITY');
    }

    $conn->begin_transaction();

    try {
        // Registrar movimiento
        $stmt = $conn->prepare("INSERT INTO movimientos_inventario
            (empresa_id, producto_id, almacen_id, tipo_movimiento, cantidad, referencia, observaciones)
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("iiisdss", $empresa_id, $producto_id, $almacen_id, $tipo_movimiento,
            $cantidad, $referencia, $observaciones);
        $stmt->execute();
        $movimiento_id = $conn->insert_id;
        $stmt->close();

        // Actualizar stock en inventario
        $multiplicador = in_array($tipo_movimiento, ['entrada', 'ajuste_positivo']) ? 1 : -1;
        $cantidad_final = $cantidad * $multiplicador;

        $stmt = $conn->prepare("INSERT INTO inventario (empresa_id, producto_id, almacen_id, cantidad)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE cantidad = cantidad + ?");

        $stmt->bind_param("iiidd", $empresa_id, $producto_id, $almacen_id, $cantidad_final, $cantidad_final);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        // Obtener movimiento creado
        $stmt = $conn->prepare("SELECT * FROM movimientos_inventario WHERE id = ?");
        $stmt->bind_param("i", $movimiento_id);
        $stmt->execute();
        $movimiento = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        enviarExito($movimiento, 'Movimiento registrado exitosamente', 201);

    } catch (Exception $e) {
        $conn->rollback();
        enviarError(500, 'Error al registrar movimiento: ' . $e->getMessage(), 'DATABASE_ERROR');
    }
}

else {
    enviarError(405, 'Método no permitido', 'METHOD_NOT_ALLOWED');
}
