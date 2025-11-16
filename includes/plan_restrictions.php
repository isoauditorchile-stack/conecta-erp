<?php
/**
 * PLAN RESTRICTIONS - Sistema de restricciones por plan
 */

/**
 * Verifica si el usuario puede crear más usuarios según su plan
 */
function puedeCrearUsuario($conn, $empresa_id, $plan_id) {
    // Obtener límite del plan
    $stmt = $conn->prepare("SELECT max_usuarios FROM planes WHERE id = ?");
    $stmt->bind_param("i", $plan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $plan = $result->fetch_assoc();
    $stmt->close();

    // NULL = ilimitado
    if ($plan['max_usuarios'] === null) {
        return ['permitido' => true, 'mensaje' => 'Usuarios ilimitados'];
    }

    // Contar usuarios actuales de la empresa
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE empresa_id = ?");
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['total'];
    $stmt->close();

    if ($count >= $plan['max_usuarios']) {
        return [
            'permitido' => false,
            'mensaje' => "Has alcanzado el límite de {$plan['max_usuarios']} usuarios de tu plan. Actualiza tu plan para agregar más usuarios.",
            'actual' => $count,
            'maximo' => $plan['max_usuarios']
        ];
    }

    return [
        'permitido' => true,
        'mensaje' => 'Puedes crear usuario',
        'actual' => $count,
        'maximo' => $plan['max_usuarios']
    ];
}

/**
 * Verifica si el usuario puede crear más empresas según su plan
 */
function puedeCrearEmpresa($conn, $usuario_id, $plan_id) {
    // Obtener límite del plan
    $stmt = $conn->prepare("SELECT max_empresas FROM planes WHERE id = ?");
    $stmt->bind_param("i", $plan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $plan = $result->fetch_assoc();
    $stmt->close();

    // NULL = ilimitado
    if ($plan['max_empresas'] === null) {
        return ['permitido' => true, 'mensaje' => 'Empresas ilimitadas'];
    }

    // Contar empresas del usuario (multi-empresa)
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT empresa_id) as total FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['total'];
    $stmt->close();

    if ($count >= $plan['max_empresas']) {
        return [
            'permitido' => false,
            'mensaje' => "Has alcanzado el límite de {$plan['max_empresas']} empresas de tu plan. Actualiza tu plan para agregar más empresas.",
            'actual' => $count,
            'maximo' => $plan['max_empresas']
        ];
    }

    return [
        'permitido' => true,
        'mensaje' => 'Puedes crear empresa',
        'actual' => $count,
        'maximo' => $plan['max_empresas']
    ];
}

/**
 * Verifica si el usuario tiene acceso a un módulo específico según su plan
 */
function tieneAccesoModulo($conn, $plan_id, $modulo_id) {
    $stmt = $conn->prepare("SELECT modulos_incluidos FROM planes WHERE id = ?");
    $stmt->bind_param("i", $plan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $plan = $result->fetch_assoc();
    $stmt->close();

    $modulos_incluidos = json_decode($plan['modulos_incluidos'], true);

    if (in_array($modulo_id, $modulos_incluidos)) {
        return ['permitido' => true, 'mensaje' => 'Acceso permitido'];
    }

    return [
        'permitido' => false,
        'mensaje' => 'Este módulo no está incluido en tu plan. Actualiza tu plan para acceder.'
    ];
}

/**
 * Obtener límites actuales del usuario
 */
function getLimitesUsuario($conn, $usuario_id) {
    $stmt = $conn->prepare("SELECT u.plan_id, u.empresa_id, p.max_usuarios, p.max_empresas, p.modulos_incluidos, p.nombre_plan
                            FROM usuarios u
                            INNER JOIN planes p ON u.plan_id = p.id
                            WHERE u.id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    // Contar usuarios de la empresa
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE empresa_id = ?");
    $stmt->bind_param("i", $data['empresa_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuarios_actuales = $result->fetch_assoc()['total'];
    $stmt->close();

    return [
        'plan' => $data['nombre_plan'],
        'max_usuarios' => $data['max_usuarios'] ?? 'Ilimitados',
        'usuarios_actuales' => $usuarios_actuales,
        'max_empresas' => $data['max_empresas'] ?? 'Ilimitadas',
        'modulos_incluidos' => json_decode($data['modulos_incluidos'], true)
    ];
}

/**
 * Validar restricción antes de acción (usar en todos los puntos críticos)
 */
function validarRestriccion($tipo, $conn, $data) {
    switch ($tipo) {
        case 'crear_usuario':
            return puedeCrearUsuario($conn, $data['empresa_id'], $data['plan_id']);

        case 'crear_empresa':
            return puedeCrearEmpresa($conn, $data['usuario_id'], $data['plan_id']);

        case 'acceso_modulo':
            return tieneAccesoModulo($conn, $data['plan_id'], $data['modulo_id']);

        default:
            return ['permitido' => false, 'mensaje' => 'Tipo de restricción no válida'];
    }
}

/**
 * AISLAMIENTO MULTIEMPRESA
 * Agregar WHERE empresa_id = ? a todas las consultas de datos
 */
function filtrarPorEmpresa($query, $empresa_id) {
    // Esta función se debe usar en TODAS las consultas que traen datos
    // para asegurar que cada empresa solo ve sus propios datos
    return $query . " AND empresa_id = $empresa_id";
}

/**
 * Verificar que el usuario pertenece a la empresa que está intentando acceder
 */
function validarAccesoEmpresa($conn, $usuario_id, $empresa_id_solicitada) {
    $stmt = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();

    if ($usuario['empresa_id'] != $empresa_id_solicitada) {
        return [
            'permitido' => false,
            'mensaje' => 'No tienes acceso a los datos de esta empresa'
        ];
    }

    return ['permitido' => true, 'mensaje' => 'Acceso permitido'];
}
