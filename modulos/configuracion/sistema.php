<?php
/**
 * MÓDULO 13: CONFIGURACIÓN AVANZADA DEL SISTEMA
 * Gestión completa de configuraciones empresariales
 * Sistema REAL - Nivel Empresarial SAP/Softland
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];

$mensaje = '';
$tipo_mensaje = '';

// =====================================================
// GUARDAR CONFIGURACIÓN GENERAL
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_configuracion'])) {
    $configuraciones = $_POST['config'] ?? [];

    $errores = 0;
    $actualizados = 0;

    foreach ($configuraciones as $clave => $valor) {
        // Verificar si existe
        $stmt = $conn->prepare("SELECT id FROM configuracion_sistema
            WHERE empresa_id = ? AND clave = ?");
        $stmt->bind_param("is", $empresa_id, $clave);
        $stmt->execute();
        $existe = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existe) {
            // Actualizar
            $stmt = $conn->prepare("UPDATE configuracion_sistema
                SET valor = ?, fecha_modificacion = NOW(), modificado_por = ?
                WHERE id = ?");
            $stmt->bind_param("sii", $valor, $usuario_id, $existe['id']);

            if ($stmt->execute()) {
                $actualizados++;
            } else {
                $errores++;
            }
            $stmt->close();
        }
    }

    if ($errores == 0) {
        logAuditoria($conn, $usuario_id, $empresa_id, 'actualizar', 'configuracion_sistema', 0,
            'configuracion', "Configuración actualizada: {$actualizados} parámetros");

        $mensaje = "Configuración guardada exitosamente ({$actualizados} parámetros actualizados)";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Se actualizaron {$actualizados} parámetros con {$errores} errores";
        $tipo_mensaje = "warning";
    }
}

// =====================================================
// CREAR/ACTUALIZAR IMPUESTO
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_impuesto'])) {
    $impuesto_id = isset($_POST['impuesto_id']) && $_POST['impuesto_id'] !== '' ? intval($_POST['impuesto_id']) : NULL;
    $nombre = trim($_POST['nombre']);
    $codigo = trim($_POST['codigo']);
    $tipo = $_POST['tipo'];
    $porcentaje = floatval($_POST['porcentaje']);
    $base_imponible = $_POST['base_imponible'];
    $aplica_ventas = isset($_POST['aplica_ventas']) ? 1 : 0;
    $aplica_compras = isset($_POST['aplica_compras']) ? 1 : 0;
    $cuenta_contable = trim($_POST['cuenta_contable']);
    $pais_codigo = trim($_POST['pais_codigo']);
    $fecha_vigencia_inicio = $_POST['fecha_vigencia_inicio'];
    $fecha_vigencia_fin = isset($_POST['fecha_vigencia_fin']) && $_POST['fecha_vigencia_fin'] !== '' ? $_POST['fecha_vigencia_fin'] : NULL;

    if (empty($nombre) || empty($codigo) || $porcentaje < 0) {
        $mensaje = "Todos los campos obligatorios deben estar completos";
        $tipo_mensaje = "danger";
    } else {
        if ($impuesto_id) {
            // Actualizar
            $stmt = $conn->prepare("UPDATE configuracion_impuestos
                SET nombre = ?, codigo = ?, tipo = ?, porcentaje = ?, base_imponible = ?,
                    aplica_ventas = ?, aplica_compras = ?, cuenta_contable = ?, pais_codigo = ?,
                    fecha_vigencia_inicio = ?, fecha_vigencia_fin = ?
                WHERE id = ? AND empresa_id = ?");

            $stmt->bind_param("sssdssiisssii", $nombre, $codigo, $tipo, $porcentaje, $base_imponible,
                $aplica_ventas, $aplica_compras, $cuenta_contable, $pais_codigo,
                $fecha_vigencia_inicio, $fecha_vigencia_fin, $impuesto_id, $empresa_id);

            $accion = 'actualizar';
            $mensaje_log = "Impuesto actualizado: {$nombre}";
        } else {
            // Crear
            $stmt = $conn->prepare("INSERT INTO configuracion_impuestos
                (empresa_id, nombre, codigo, tipo, porcentaje, base_imponible,
                 aplica_ventas, aplica_compras, cuenta_contable, pais_codigo,
                 fecha_vigencia_inicio, fecha_vigencia_fin)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("isssdssiisss", $empresa_id, $nombre, $codigo, $tipo, $porcentaje,
                $base_imponible, $aplica_ventas, $aplica_compras, $cuenta_contable, $pais_codigo,
                $fecha_vigencia_inicio, $fecha_vigencia_fin);

            $accion = 'crear';
            $mensaje_log = "Impuesto creado: {$nombre}";
        }

        if ($stmt->execute()) {
            $id_registro = $impuesto_id ?: $conn->insert_id;

            logAuditoria($conn, $usuario_id, $empresa_id, $accion, 'configuracion_impuestos', $id_registro,
                'configuracion', $mensaje_log);

            $mensaje = $impuesto_id ? "Impuesto actualizado exitosamente" : "Impuesto creado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al guardar impuesto: " . $stmt->error;
            $tipo_mensaje = "danger";
        }
        $stmt->close();
    }
}

// =====================================================
// CREAR/ACTUALIZAR INTEGRACIÓN
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_integracion'])) {
    $integracion_id = isset($_POST['integracion_id']) && $_POST['integracion_id'] !== '' ? intval($_POST['integracion_id']) : NULL;
    $servicio = $_POST['servicio'];
    $nombre_display = trim($_POST['nombre_display']);
    $credenciales_json = trim($_POST['credenciales']);
    $configuracion_json = trim($_POST['configuracion']);
    $ambiente = $_POST['ambiente'];
    $activa = isset($_POST['activa']) ? 1 : 0;

    // Validar JSON
    if (!empty($credenciales_json)) {
        $credenciales_decoded = json_decode($credenciales_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $mensaje = "Error en formato JSON de credenciales";
            $tipo_mensaje = "danger";
            goto skip_integracion;
        }
    }

    if ($integracion_id) {
        // Actualizar
        $stmt = $conn->prepare("UPDATE configuracion_integraciones
            SET nombre_display = ?, credenciales = ?, configuracion = ?, ambiente = ?, activa = ?
            WHERE id = ? AND empresa_id = ?");

        $stmt->bind_param("sssssii", $nombre_display, $credenciales_json, $configuracion_json,
            $ambiente, $activa, $integracion_id, $empresa_id);

        $accion = 'actualizar';
    } else {
        // Crear
        $stmt = $conn->prepare("INSERT INTO configuracion_integraciones
            (empresa_id, servicio, nombre_display, credenciales, configuracion, ambiente, activa, creado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("isssssii", $empresa_id, $servicio, $nombre_display, $credenciales_json,
            $configuracion_json, $ambiente, $activa, $usuario_id);

        $accion = 'crear';
    }

    if ($stmt->execute()) {
        $id_registro = $integracion_id ?: $conn->insert_id;

        logAuditoria($conn, $usuario_id, $empresa_id, $accion, 'configuracion_integraciones', $id_registro,
            'configuracion', "Integración {$servicio}: {$nombre_display}");

        $mensaje = "Integración guardada exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al guardar integración: " . $stmt->error;
        $tipo_mensaje = "danger";
    }
    $stmt->close();

    skip_integracion:
}

// =====================================================
// CREAR/ACTUALIZAR SECUENCIA DE NUMERACIÓN
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_secuencia'])) {
    $secuencia_id = isset($_POST['secuencia_id']) && $_POST['secuencia_id'] !== '' ? intval($_POST['secuencia_id']) : NULL;
    $tipo_documento = trim($_POST['tipo_documento']);
    $prefijo = trim($_POST['prefijo']);
    $sufijo = trim($_POST['sufijo']);
    $longitud_numero = intval($_POST['longitud_numero']);
    $valor_actual = intval($_POST['valor_actual']);
    $valor_inicial = intval($_POST['valor_inicial']);
    $incremento = intval($_POST['incremento']);
    $separador = trim($_POST['separador']);
    $activa = isset($_POST['activa']) ? 1 : 0;

    if (empty($tipo_documento)) {
        $mensaje = "El tipo de documento es obligatorio";
        $tipo_mensaje = "danger";
    } else {
        if ($secuencia_id) {
            // Actualizar
            $stmt = $conn->prepare("UPDATE secuencias_numeracion
                SET tipo_documento = ?, prefijo = ?, sufijo = ?, longitud_numero = ?,
                    valor_actual = ?, valor_inicial = ?, incremento = ?, separador = ?, activa = ?
                WHERE id = ? AND empresa_id = ?");

            $stmt->bind_param("sssiiiiisii", $tipo_documento, $prefijo, $sufijo, $longitud_numero,
                $valor_actual, $valor_inicial, $incremento, $separador, $activa, $secuencia_id, $empresa_id);
        } else {
            // Crear
            $stmt = $conn->prepare("INSERT INTO secuencias_numeracion
                (empresa_id, tipo_documento, prefijo, sufijo, longitud_numero,
                 valor_actual, valor_inicial, incremento, separador, activa)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("isssiiiiis", $empresa_id, $tipo_documento, $prefijo, $sufijo,
                $longitud_numero, $valor_actual, $valor_inicial, $incremento, $separador, $activa);
        }

        if ($stmt->execute()) {
            $mensaje = "Secuencia guardada exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al guardar secuencia: " . $stmt->error;
            $tipo_mensaje = "danger";
        }
        $stmt->close();
    }
}

// =====================================================
// OBTENER ESTADÍSTICAS DESDE SQL
// =====================================================
$stmt = $conn->prepare("SELECT
    (SELECT COUNT(*) FROM configuracion_sistema WHERE empresa_id = ?) as total_parametros,
    (SELECT COUNT(*) FROM configuracion_impuestos WHERE empresa_id = ? AND activo = 1) as impuestos_activos,
    (SELECT COUNT(*) FROM configuracion_integraciones WHERE empresa_id = ? AND activa = 1) as integraciones_activas,
    (SELECT COUNT(*) FROM secuencias_numeracion WHERE empresa_id = ?) as secuencias_totales
FROM DUAL");
$stmt->bind_param("iiii", $empresa_id, $empresa_id, $empresa_id, $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Obtener configuración general
$stmt = $conn->prepare("SELECT categoria, clave, valor, tipo_dato, descripcion, es_sistema
    FROM configuracion_sistema
    WHERE empresa_id = ?
    ORDER BY categoria, clave");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$configuraciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Agrupar por categoría
$config_por_categoria = [];
foreach ($configuraciones as $config) {
    $config_por_categoria[$config['categoria']][] = $config;
}

// Obtener impuestos
$stmt = $conn->prepare("SELECT * FROM configuracion_impuestos
    WHERE empresa_id = ?
    ORDER BY activo DESC, nombre");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$impuestos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obtener integraciones
$stmt = $conn->prepare("SELECT * FROM configuracion_integraciones
    WHERE empresa_id = ?
    ORDER BY activa DESC, servicio");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$integraciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obtener secuencias
$stmt = $conn->prepare("SELECT * FROM secuencias_numeracion
    WHERE empresa_id = ?
    ORDER BY tipo_documento");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$secuencias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        :root { --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .sidebar { position: fixed; left: 0; top: 0; width: 280px; height: 100vh; background: var(--primary-gradient); color: white; padding: 20px; overflow-y: auto; z-index: 1000; }
        .content { margin-left: 280px; padding: 30px; }
        .stats-card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid; }
        .stats-card.blue { border-left-color: #667eea; }
        .stats-card.green { border-left-color: #48bb78; }
        .stats-card.purple { border-left-color: #9f7aea; }
        .stats-card.orange { border-left-color: #ed8936; }
        .nav-tabs .nav-link { color: #667eea; font-weight: 600; }
        .nav-tabs .nav-link.active { background: var(--primary-gradient); color: white; }
        .config-group { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .config-label { font-weight: 600; color: #2d3748; margin-bottom: 5px; }
        .config-description { font-size: 12px; color: #718096; }
        .badge-activo { background: #48bb78; color: white; }
        .badge-inactivo { background: #cbd5e0; color: #2d3748; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3 class="mb-4"><i class="fas fa-cog"></i> Configuración</h3>
        <a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mb-3">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>

        <div class="mt-4">
            <h6 class="text-uppercase mb-3" style="font-size: 12px; opacity: 0.8;">Secciones</h6>
            <a href="#general" class="btn btn-outline-light btn-sm w-100 mb-2" data-bs-toggle="tab">
                <i class="fas fa-sliders-h"></i> General
            </a>
            <a href="#impuestos" class="btn btn-outline-light btn-sm w-100 mb-2" data-bs-toggle="tab">
                <i class="fas fa-percentage"></i> Impuestos
            </a>
            <a href="#integraciones" class="btn btn-outline-light btn-sm w-100 mb-2" data-bs-toggle="tab">
                <i class="fas fa-plug"></i> Integraciones
            </a>
            <a href="#secuencias" class="btn btn-outline-light btn-sm w-100 mb-2" data-bs-toggle="tab">
                <i class="fas fa-hashtag"></i> Numeración
            </a>
        </div>
    </div>

    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-cog text-primary"></i> Configuración del Sistema</h2>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card blue">
                    <h6 style="color:#667eea">Parámetros</h6>
                    <h3><?php echo number_format($stats['total_parametros']); ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card green">
                    <h6 style="color:#48bb78">Impuestos Activos</h6>
                    <h3><?php echo number_format($stats['impuestos_activos']); ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card purple">
                    <h6 style="color:#9f7aea">Integraciones</h6>
                    <h3><?php echo number_format($stats['integraciones_activas']); ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card orange">
                    <h6 style="color:#ed8936">Secuencias</h6>
                    <h3><?php echo number_format($stats['secuencias_totales']); ?></h3>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#general">
                            <i class="fas fa-sliders-h"></i> Configuración General
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#impuestos">
                            <i class="fas fa-percentage"></i> Impuestos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#integraciones">
                            <i class="fas fa-plug"></i> Integraciones
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#secuencias">
                            <i class="fas fa-hashtag"></i> Numeración Automática
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- TAB: CONFIGURACIÓN GENERAL -->
                    <div class="tab-pane fade show active" id="general">
                        <form method="POST">
                            <?php foreach ($config_por_categoria as $categoria => $configs): ?>
                            <div class="config-group">
                                <h5 class="mb-3">
                                    <i class="fas fa-folder text-primary"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $categoria)); ?>
                                </h5>
                                <div class="row">
                                    <?php foreach ($configs as $config): ?>
                                    <div class="col-md-6 mb-3">
                                        <label class="config-label">
                                            <?php echo ucfirst(str_replace('_', ' ', $config['clave'])); ?>
                                            <?php if ($config['es_sistema']): ?>
                                            <span class="badge bg-secondary ms-2">Sistema</span>
                                            <?php endif; ?>
                                        </label>
                                        <?php if ($config['descripcion']): ?>
                                        <div class="config-description"><?php echo htmlspecialchars($config['descripcion']); ?></div>
                                        <?php endif; ?>

                                        <?php if ($config['tipo_dato'] === 'boolean'): ?>
                                        <select name="config[<?php echo $config['clave']; ?>]" class="form-control form-control-sm" <?php echo $config['es_sistema'] ? 'disabled' : ''; ?>>
                                            <option value="true" <?php echo $config['valor'] === 'true' ? 'selected' : ''; ?>>Sí</option>
                                            <option value="false" <?php echo $config['valor'] === 'false' ? 'selected' : ''; ?>>No</option>
                                        </select>
                                        <?php else: ?>
                                        <input type="text" name="config[<?php echo $config['clave']; ?>]"
                                               value="<?php echo htmlspecialchars($config['valor']); ?>"
                                               class="form-control form-control-sm"
                                               <?php echo $config['es_sistema'] ? 'readonly' : ''; ?>>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <button type="submit" name="guardar_configuracion" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </form>
                    </div>

                    <!-- TAB: IMPUESTOS -->
                    <div class="tab-pane fade" id="impuestos">
                        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalImpuesto">
                            <i class="fas fa-plus"></i> Nuevo Impuesto
                        </button>

                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Código</th>
                                    <th>Tipo</th>
                                    <th>Porcentaje</th>
                                    <th>Aplica</th>
                                    <th>País</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($impuestos as $imp): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($imp['nombre']); ?></td>
                                    <td><code><?php echo htmlspecialchars($imp['codigo']); ?></code></td>
                                    <td><span class="badge bg-info"><?php echo strtoupper($imp['tipo']); ?></span></td>
                                    <td><?php echo number_format($imp['porcentaje'], 2); ?>%</td>
                                    <td>
                                        <?php if ($imp['aplica_ventas']): ?><span class="badge bg-success">Ventas</span><?php endif; ?>
                                        <?php if ($imp['aplica_compras']): ?><span class="badge bg-primary">Compras</span><?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($imp['pais_codigo']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $imp['activo'] ? 'badge-activo' : 'badge-inactivo'; ?>">
                                            <?php echo $imp['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editarImpuesto(<?php echo htmlspecialchars(json_encode($imp)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- TAB: INTEGRACIONES -->
                    <div class="tab-pane fade" id="integraciones">
                        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalIntegracion">
                            <i class="fas fa-plus"></i> Nueva Integración
                        </button>

                        <div class="row">
                            <?php foreach ($integraciones as $int): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5><?php echo htmlspecialchars($int['nombre_display']); ?></h5>
                                                <span class="badge bg-secondary"><?php echo strtoupper($int['servicio']); ?></span>
                                                <span class="badge bg-info ms-2"><?php echo ucfirst($int['ambiente']); ?></span>
                                            </div>
                                            <span class="badge <?php echo $int['activa'] ? 'badge-activo' : 'badge-inactivo'; ?>">
                                                <?php echo $int['activa'] ? 'Activa' : 'Inactiva'; ?>
                                            </span>
                                        </div>

                                        <?php if ($int['ultima_sincronizacion']): ?>
                                        <p class="mb-0 mt-2 text-muted small">
                                            <i class="fas fa-sync-alt"></i>
                                            Última sincronización: <?php echo date('d/m/Y H:i', strtotime($int['ultima_sincronizacion'])); ?>
                                        </p>
                                        <?php endif; ?>

                                        <div class="mt-3">
                                            <button class="btn btn-sm btn-warning" onclick="editarIntegracion(<?php echo htmlspecialchars(json_encode($int)); ?>)">
                                                <i class="fas fa-edit"></i> Editar
                                            </button>
                                            <button class="btn btn-sm btn-info">
                                                <i class="fas fa-vial"></i> Probar Conexión
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- TAB: SECUENCIAS -->
                    <div class="tab-pane fade" id="secuencias">
                        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalSecuencia">
                            <i class="fas fa-plus"></i> Nueva Secuencia
                        </button>

                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tipo Documento</th>
                                    <th>Prefijo</th>
                                    <th>Longitud</th>
                                    <th>Valor Actual</th>
                                    <th>Ejemplo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($secuencias as $sec): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sec['tipo_documento']); ?></td>
                                    <td><code><?php echo htmlspecialchars($sec['prefijo']); ?></code></td>
                                    <td><?php echo $sec['longitud_numero']; ?> dígitos</td>
                                    <td><?php echo number_format($sec['valor_actual']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($sec['ejemplo']); ?></strong></td>
                                    <td>
                                        <span class="badge <?php echo $sec['activa'] ? 'badge-activo' : 'badge-inactivo'; ?>">
                                            <?php echo $sec['activa'] ? 'Activa' : 'Inactiva'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editarSecuencia(<?php echo htmlspecialchars(json_encode($sec)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarImpuesto(data) {
            alert('Función de edición de impuestos - Implementar modal');
        }

        function editarIntegracion(data) {
            alert('Función de edición de integraciones - Implementar modal');
        }

        function editarSecuencia(data) {
            alert('Función de edición de secuencias - Implementar modal');
        }
    </script>
</body>
</html>
