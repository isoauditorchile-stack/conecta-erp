<?php
/**
 * CONFIGURACIÓN DE CAJAS - Sistema Completo
 * Gestión de cajas registradoras y turnos
 * Multiempresa - SQL Completo
 */
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    header('Location: /login.php');
    exit;
}

$empresa_id = (int)$_SESSION['empresa_id'];
$usuario_id = (int)$_SESSION['user_id'];

// Obtener conexión a base de datos
if (!isset($conn)) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
}

$success = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Crear/Editar caja
        if (isset($_POST['guardar_caja'])) {
            $caja_id = isset($_POST['caja_id']) ? (int)$_POST['caja_id'] : 0;
            $nombre = trim($_POST['nombre']);
            $usuario_asignado = isset($_POST['usuario_id']) && $_POST['usuario_id'] !== '' ? (int)$_POST['usuario_id'] : null;

            if (empty($nombre)) {
                throw new Exception("El nombre es obligatorio");
            }

            if ($caja_id > 0) {
                // Editar
                $stmt = $conn->prepare("
                    UPDATE cajas
                    SET nombre = ?, usuario_id = ?
                    WHERE id = ? AND empresa_id = ?
                ");
                $stmt->bind_param('siii', $nombre, $usuario_asignado, $caja_id, $empresa_id);
                $stmt->execute();
                $success = "Caja actualizada correctamente";
            } else {
                // Crear
                $stmt = $conn->prepare("
                    INSERT INTO cajas (empresa_id, usuario_id, nombre, estado, fecha_apertura)
                    VALUES (?, ?, ?, 'cerrada', NOW())
                ");
                $stmt->bind_param('iis', $empresa_id, $usuario_asignado, $nombre);
                $stmt->execute();
                $success = "Caja creada correctamente";
            }

            logActivity($usuario_id, 'config_caja', $success, 'ventas');
        }

        // Eliminar caja
        if (isset($_POST['eliminar_caja'])) {
            $caja_id = (int)$_POST['caja_id'];

            // Verificar que no tenga ventas asociadas
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ventas_pos WHERE caja_id = ?");
            $stmt->bind_param('i', $caja_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if ($result['total'] > 0) {
                throw new Exception("No se puede eliminar una caja con ventas registradas");
            }

            $stmt = $conn->prepare("DELETE FROM cajas WHERE id = ? AND empresa_id = ?");
            $stmt->bind_param('ii', $caja_id, $empresa_id);
            $stmt->execute();

            $success = "Caja eliminada correctamente";
            logActivity($usuario_id, 'eliminar_caja', "Eliminó caja ID $caja_id", 'ventas');
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Error en config_cajas.php: " . $error);
    }
}

// Obtener cajas
$stmt = $conn->prepare("
    SELECT c.*, u.nombre as usuario_nombre, u.email as usuario_email,
           (SELECT COUNT(*) FROM ventas_pos WHERE caja_id = c.id) as total_ventas
    FROM cajas c
    LEFT JOIN usuarios u ON c.usuario_id = u.id
    WHERE c.empresa_id = ?
    ORDER BY c.nombre
");
$stmt->bind_param('i', $empresa_id);
$stmt->execute();
$cajas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener usuarios para asignar
$stmt = $conn->prepare("
    SELECT id, nombre, apellido, email
    FROM usuarios
    WHERE empresa_id = ? AND estado = 'activo'
    ORDER BY nombre
");
$stmt->bind_param('i', $empresa_id);
$stmt->execute();
$usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Cajas | CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(240, 147, 251, 0.3);
        }
        .caja-card {
            border-left: 4px solid #f093fb;
            transition: all 0.3s;
        }
        .caja-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        .estado-abierta { color: #28a745; }
        .estado-cerrada { color: #6c757d; }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="gradient-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-cash-register"></i> Configuración de Cajas</h1>
                    <p class="mb-0 opacity-75">Gestión de cajas registradoras y asignación de usuarios</p>
                </div>
                <div>
                    <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#cajaModal"
                            onclick="limpiarFormulario()">
                        <i class="fas fa-plus-circle"></i> Nueva Caja
                    </button>
                </div>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Cajas configuradas -->
        <div class="row">
            <?php foreach ($cajas as $caja): ?>
            <div class="col-md-4 mb-4">
                <div class="card caja-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cash-register text-primary"></i>
                                <?= htmlspecialchars($caja['nombre']) ?>
                            </h5>
                            <span class="badge bg-<?= $caja['estado'] === 'abierta' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($caja['estado']) ?>
                            </span>
                        </div>

                        <div class="mb-3">
                            <?php if ($caja['usuario_nombre']): ?>
                            <p class="mb-1">
                                <i class="fas fa-user text-muted"></i>
                                <strong>Asignada a:</strong> <?= htmlspecialchars($caja['usuario_nombre']) ?>
                            </p>
                            <p class="mb-1 text-muted small">
                                <i class="fas fa-envelope"></i> <?= htmlspecialchars($caja['usuario_email']) ?>
                            </p>
                            <?php else: ?>
                            <p class="text-muted">
                                <i class="fas fa-user-slash"></i> Sin usuario asignado
                            </p>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <p class="mb-1">
                                <i class="fas fa-shopping-cart text-muted"></i>
                                <strong>Ventas registradas:</strong> <?= number_format($caja['total_ventas']) ?>
                            </p>
                            <?php if ($caja['fecha_apertura']): ?>
                            <p class="mb-1 text-muted small">
                                <i class="fas fa-clock"></i> Creada: <?= date('d/m/Y H:i', strtotime($caja['fecha_apertura'])) ?>
                            </p>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary btn-sm"
                                    onclick="editarCaja(<?= htmlspecialchars(json_encode($caja)) ?>)">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <?php if ($caja['total_ventas'] == 0): ?>
                            <form method="POST" onsubmit="return confirm('¿Está seguro de eliminar esta caja?')">
                                <input type="hidden" name="caja_id" value="<?= $caja['id'] ?>">
                                <button type="submit" name="eliminar_caja" class="btn btn-outline-danger btn-sm w-100">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </form>
                            <?php else: ?>
                            <button class="btn btn-outline-secondary btn-sm" disabled title="No se puede eliminar una caja con ventas">
                                <i class="fas fa-lock"></i> No se puede eliminar
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($cajas)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-3x mb-3 d-block"></i>
                    <h5>No hay cajas configuradas</h5>
                    <p>Crea tu primera caja registradora haciendo clic en "Nueva Caja"</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Caja -->
    <div class="modal fade" id="cajaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="cajaModalTitle">
                        <i class="fas fa-cash-register"></i> Nueva Caja
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formCaja">
                    <div class="modal-body">
                        <input type="hidden" name="caja_id" id="cajaId">

                        <div class="mb-3">
                            <label class="form-label">Nombre de la Caja *</label>
                            <input type="text" name="nombre" id="cajaNombre" class="form-control"
                                   placeholder="Ej: Caja 1, Caja Principal, etc." required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Asignar Usuario</label>
                            <select name="usuario_id" id="cajaUsuario" class="form-select">
                                <option value="">Sin asignar</option>
                                <?php foreach ($usuarios as $usr): ?>
                                <option value="<?= $usr['id'] ?>">
                                    <?= htmlspecialchars($usr['nombre'] . ' ' . ($usr['apellido'] ?? '')) ?>
                                    (<?= htmlspecialchars($usr['email']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Opcional: asigna un usuario responsable de esta caja</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <small>La caja se creará en estado "cerrada". Los usuarios podrán abrirla desde el módulo POS.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="guardar_caja" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Caja
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function limpiarFormulario() {
        document.getElementById('formCaja').reset();
        document.getElementById('cajaId').value = '';
        document.getElementById('cajaModalTitle').innerHTML = '<i class="fas fa-cash-register"></i> Nueva Caja';
    }

    function editarCaja(caja) {
        document.getElementById('cajaId').value = caja.id;
        document.getElementById('cajaNombre').value = caja.nombre;
        document.getElementById('cajaUsuario').value = caja.usuario_id || '';
        document.getElementById('cajaModalTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Caja';

        const modal = new bootstrap.Modal(document.getElementById('cajaModal'));
        modal.show();
    }
    </script>
</body>
</html>
