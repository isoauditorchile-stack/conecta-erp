<?php
/**
 * API REST - PROVEEDORES
 * Endpoint para gestión de proveedores
 * Métodos: GET, POST, PUT, DELETE
 */

require_once 'config.php';

$auth = validarAPIKey();
$empresa_id = $auth['empresa_id'];
$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $proveedor_id = $_GET['id'] ?? null;

    if ($proveedor_id) {
        $stmt = $conn->prepare("SELECT p.*, COUNT(oc.id) as total_ordenes,
            COALESCE(SUM(oc.total), 0) as total_compras
            FROM proveedores p
            LEFT JOIN ordenes_compra oc ON p.id = oc.proveedor_id
            WHERE p.id = ? AND p.empresa_id = ?
            GROUP BY p.id");
        $stmt->bind_param("ii", $proveedor_id, $empresa_id);
        $stmt->execute();
        $proveedor = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$proveedor) {
            enviarError(404, 'Proveedor no encontrado', 'PROVIDER_NOT_FOUND');
        }

        enviarExito($proveedor);
    } else {
        $paginacion = obtenerParametrosPaginacion();
        $busqueda = $_GET['search'] ?? '';
        $activo = isset($_GET['activo']) ? intval($_GET['activo']) : null;

        $where = ["p.empresa_id = ?"];
        $params = [$empresa_id];
        $types = "i";

        if ($busqueda) {
            $where[] = "(p.razon_social LIKE ? OR p.rut LIKE ?)";
            $busqueda_param = "%{$busqueda}%";
            $params[] = $busqueda_param;
            $params[] = $busqueda_param;
            $types .= "ss";
        }

        if ($activo !== null) {
            $where[] = "p.activo = ?";
            $params[] = $activo;
            $types .= "i";
        }

        $where_sql = implode(' AND ', $where);

        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM proveedores p WHERE {$where_sql}");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $stmt = $conn->prepare("SELECT p.*, COUNT(oc.id) as total_ordenes,
            COALESCE(SUM(oc.total), 0) as total_compras
            FROM proveedores p
            LEFT JOIN ordenes_compra oc ON p.id = oc.proveedor_id
            WHERE {$where_sql}
            GROUP BY p.id
            ORDER BY p.razon_social
            LIMIT ? OFFSET ?");

        $params[] = $paginacion['per_page'];
        $params[] = $paginacion['offset'];
        $types .= "ii";

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $proveedores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $respuesta = respuestaPaginada($proveedores, $total, $paginacion);
        enviarExito($respuesta);
    }
}

elseif ($metodo === 'POST') {
    $datos = obtenerDatosInput();
    validarParametros($datos, ['razon_social', 'rut']);

    $razon_social = $datos['razon_social'];
    $rut = $datos['rut'];
    $giro = $datos['giro'] ?? '';
    $direccion = $datos['direccion'] ?? '';
    $telefono = $datos['telefono'] ?? '';
    $email = $datos['email'] ?? '';
    $contacto = $datos['contacto'] ?? '';

    // Verificar RUT duplicado
    $stmt = $conn->prepare("SELECT id FROM proveedores WHERE rut = ? AND empresa_id = ?");
    $stmt->bind_param("si", $rut, $empresa_id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        enviarError(409, 'El RUT ya existe', 'DUPLICATE_RUT');
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO proveedores
        (empresa_id, razon_social, rut, giro, direccion, telefono, email, contacto, activo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");

    $stmt->bind_param("isssssss", $empresa_id, $razon_social, $rut, $giro,
        $direccion, $telefono, $email, $contacto);

    if ($stmt->execute()) {
        $proveedor_id = $conn->insert_id;
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM proveedores WHERE id = ?");
        $stmt->bind_param("i", $proveedor_id);
        $stmt->execute();
        $proveedor = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        enviarExito($proveedor, 'Proveedor creado exitosamente', 201);
    } else {
        enviarError(500, 'Error al crear proveedor: ' . $stmt->error, 'DATABASE_ERROR');
    }
}

elseif ($metodo === 'PUT') {
    $datos = obtenerDatosInput();
    validarParametros($datos, ['id']);

    $proveedor_id = intval($datos['id']);

    $campos_permitidos = ['razon_social', 'giro', 'direccion', 'telefono', 'email', 'contacto', 'activo'];

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

    $params[] = $proveedor_id;
    $params[] = $empresa_id;
    $types .= "ii";

    $sql = "UPDATE proveedores SET " . implode(', ', $sets) . " WHERE id = ? AND empresa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM proveedores WHERE id = ?");
        $stmt->bind_param("i", $proveedor_id);
        $stmt->execute();
        $proveedor = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        enviarExito($proveedor, 'Proveedor actualizado exitosamente');
    } else {
        enviarError(404, 'Proveedor no encontrado', 'PROVIDER_NOT_FOUND');
    }
}

elseif ($metodo === 'DELETE') {
    $proveedor_id = $_GET['id'] ?? null;

    if (!$proveedor_id) {
        enviarError(400, 'ID requerido', 'MISSING_ID');
    }

    $stmt = $conn->prepare("UPDATE proveedores SET activo = 0 WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $proveedor_id, $empresa_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        enviarExito(null, 'Proveedor eliminado exitosamente');
    } else {
        enviarError(404, 'Proveedor no encontrado', 'PROVIDER_NOT_FOUND');
    }
}

else {
    enviarError(405, 'Método no permitido', 'METHOD_NOT_ALLOWED');
}
