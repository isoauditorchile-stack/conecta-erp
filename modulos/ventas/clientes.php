<?php
/**
 * MÓDULO CLIENTES - COMPLETO
 * Gestión completa de clientes con validación RUT
 */
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$empresa_id = $_SESSION['empresa_id'];
$success = '';
$error = '';

// Crear/Editar Cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['guardar_cliente'])) {
        try {
            $rut = cleanString($conn, $_POST['rut']);
            $nombre = cleanString($conn, $_POST['nombre']);
            $razon_social = cleanString($conn, $_POST['razon_social']);
            $email = cleanString($conn, $_POST['email']);
            $telefono = cleanString($conn, $_POST['telefono']);
            $direccion = cleanString($conn, $_POST['direccion']);
            $ciudad = cleanString($conn, $_POST['ciudad']);
            $pais = cleanString($conn, $_POST['pais']);
            $giro = cleanString($conn, $_POST['giro'] ?? '');
            $contacto = cleanString($conn, $_POST['contacto'] ?? '');
            $tipo = $_POST['tipo'] ?? 'persona';
            $condicion_pago = $_POST['condicion_pago'] ?? 'contado';
            $limite_credito = floatval($_POST['limite_credito'] ?? 0);

            // Validar RUT si es Chile
            if ($pais === 'Chile' && !validateChileanRUT($rut)) {
                throw new Exception("RUT inválido");
            }

            $cliente_id = $_POST['cliente_id'] ?? null;

            if ($cliente_id) {
                // Actualizar
                $sql = "UPDATE clientes SET
                        rut = ?, nombre = ?, razon_social = ?, email = ?, telefono = ?,
                        direccion = ?, ciudad = ?, pais = ?, giro = ?, contacto = ?,
                        tipo = ?, condicion_pago = ?, limite_credito = ?,
                        updated_at = NOW()
                        WHERE id = ? AND empresa_id = ?";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssssssssdii",
                    $rut, $nombre, $razon_social, $email, $telefono,
                    $direccion, $ciudad, $pais, $giro, $contacto,
                    $tipo, $condicion_pago, $limite_credito,
                    $cliente_id, $empresa_id
                );
                $stmt->execute();
                $success = "Cliente actualizado correctamente";
            } else {
                // Crear
                $sql = "INSERT INTO clientes
                        (empresa_id, rut, nombre, razon_social, email, telefono, direccion,
                         ciudad, pais, giro, contacto, tipo, condicion_pago, limite_credito,
                         estado, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', NOW())";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssssssssssd",
                    $empresa_id, $rut, $nombre, $razon_social, $email, $telefono, $direccion,
                    $ciudad, $pais, $giro, $contacto, $tipo, $condicion_pago, $limite_credito
                );
                $stmt->execute();
                $success = "Cliente creado correctamente";
            }

            logAuditoria('guardar_cliente', 'clientes', $cliente_id, null, null, 'Cliente guardado');

        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    if (isset($_POST['eliminar_cliente'])) {
        $cliente_id = intval($_POST['cliente_id']);

        $stmt = $conn->prepare("UPDATE clientes SET estado = 'inactivo' WHERE id = ? AND empresa_id = ?");
        $stmt->bind_param("ii", $cliente_id, $empresa_id);
        $stmt->execute();

        $success = "Cliente eliminado correctamente";
    }
}

// Obtener clientes
$filtro = $_GET['filtro'] ?? '';
$where = "empresa_id = ?";
$params = [$empresa_id];
$types = "i";

if ($filtro) {
    $where .= " AND (nombre LIKE ? OR razon_social LIKE ? OR rut LIKE ? OR email LIKE ?)";
    $filtro_like = "%$filtro%";
    $params = array_merge($params, [$filtro_like, $filtro_like, $filtro_like, $filtro_like]);
    $types .= "ssss";
}

$clientes = fetchAll($conn,
    "SELECT * FROM clientes WHERE $where AND estado = 'activo' ORDER BY nombre",
    $params, $types
);

// Estadísticas
$stats = fetchOne($conn,
    "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN tipo = 'empresa' THEN 1 ELSE 0 END) as empresas,
        SUM(CASE WHEN tipo = 'persona' THEN 1 ELSE 0 END) as personas,
        SUM(limite_credito) as credito_total
     FROM clientes WHERE empresa_id = ? AND estado = 'activo'",
    [$empresa_id], 'i'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .stats-card { border-left: 4px solid #667eea; }
        .cliente-row { cursor: pointer; transition: all 0.3s; }
        .cliente-row:hover { background: #f8f9fa; transform: translateX(5px); }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1><i class="fas fa-users"></i> Clientes</h1>
                <p class="text-muted mb-0">Gestión de clientes y contactos</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#clienteModal" onclick="limpiarFormulario()">
                <i class="fas fa-plus"></i> Nuevo Cliente
            </button>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Clientes</h6>
                        <h3 class="mb-0"><?= number_format($stats['total'] ?? 0) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6 class="text-muted">Empresas</h6>
                        <h3 class="mb-0"><?= number_format($stats['empresas'] ?? 0) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6 class="text-muted">Personas</h6>
                        <h3 class="mb-0"><?= number_format($stats['personas'] ?? 0) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6 class="text-muted">Crédito Total</h6>
                        <h3 class="mb-0"><?= formatCurrency($stats['credito_total'] ?? 0, 'CLP') ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-10">
                        <input type="text" name="filtro" class="form-control"
                               placeholder="Buscar por nombre, RUT, email..."
                               value="<?= htmlspecialchars($filtro) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de Clientes -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Listado de Clientes (<?= count($clientes) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>RUT/ID</th>
                                <th>Nombre / Razón Social</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Ciudad</th>
                                <th>Tipo</th>
                                <th>Crédito</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                            <tr class="cliente-row">
                                <td><strong><?= formatRUT($cliente['rut']) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($cliente['nombre']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($cliente['razon_social']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($cliente['email'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($cliente['telefono'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($cliente['ciudad'] ?? '-') ?></td>
                                <td>
                                    <?php if ($cliente['tipo'] === 'empresa'): ?>
                                    <span class="badge bg-primary">Empresa</span>
                                    <?php else: ?>
                                    <span class="badge bg-info">Persona</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= formatCurrency($cliente['limite_credito'], 'CLP') ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="editarCliente(<?= $cliente['id'] ?>)" data-bs-toggle="modal" data-bs-target="#clienteModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="ventas.php?cliente=<?= $cliente['id'] ?>" class="btn btn-outline-success">
                                            <i class="fas fa-shopping-cart"></i>
                                        </a>
                                        <button class="btn btn-outline-danger" onclick="eliminarCliente(<?= $cliente['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (empty($clientes)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No hay clientes registrados
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cliente -->
    <div class="modal fade" id="clienteModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-user-plus"></i> Nuevo Cliente
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formCliente">
                    <input type="hidden" name="cliente_id" id="cliente_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Cliente *</label>
                                <select name="tipo" id="tipo" class="form-select" required>
                                    <option value="persona">Persona Natural</option>
                                    <option value="empresa">Empresa</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">RUT/ID *</label>
                                <input type="text" name="rut" id="rut" class="form-control" required placeholder="12.345.678-9">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Razón Social *</label>
                                <input type="text" name="razon_social" id="razon_social" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="tel" name="telefono" id="telefono" class="form-control">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="direccion" id="direccion" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ciudad</label>
                                <input type="text" name="ciudad" id="ciudad" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">País</label>
                                <select name="pais" id="pais" class="form-select">
                                    <option value="Chile">Chile</option>
                                    <option value="Argentina">Argentina</option>
                                    <option value="Perú">Perú</option>
                                    <option value="Colombia">Colombia</option>
                                    <option value="México">México</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Giro</label>
                                <input type="text" name="giro" id="giro" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Persona de Contacto</label>
                                <input type="text" name="contacto" id="contacto" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Condición de Pago</label>
                                <select name="condicion_pago" id="condicion_pago" class="form-select">
                                    <option value="contado">Contado</option>
                                    <option value="credito_30">Crédito 30 días</option>
                                    <option value="credito_60">Crédito 60 días</option>
                                    <option value="credito_90">Crédito 90 días</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Límite de Crédito</label>
                                <input type="number" name="limite_credito" id="limite_credito" class="form-control" step="1000" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="guardar_cliente" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cliente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../user/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const clientes = <?= json_encode($clientes) ?>;

    function limpiarFormulario() {
        document.getElementById('formCliente').reset();
        document.getElementById('cliente_id').value = '';
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Nuevo Cliente';
    }

    function editarCliente(id) {
        const cliente = clientes.find(c => c.id == id);
        if (!cliente) return;

        document.getElementById('cliente_id').value = cliente.id;
        document.getElementById('tipo').value = cliente.tipo;
        document.getElementById('rut').value = cliente.rut;
        document.getElementById('nombre').value = cliente.nombre;
        document.getElementById('razon_social').value = cliente.razon_social;
        document.getElementById('email').value = cliente.email || '';
        document.getElementById('telefono').value = cliente.telefono || '';
        document.getElementById('direccion').value = cliente.direccion || '';
        document.getElementById('ciudad').value = cliente.ciudad || '';
        document.getElementById('pais').value = cliente.pais || 'Chile';
        document.getElementById('giro').value = cliente.giro || '';
        document.getElementById('contacto').value = cliente.contacto || '';
        document.getElementById('condicion_pago').value = cliente.condicion_pago || 'contado';
        document.getElementById('limite_credito').value = cliente.limite_credito || 0;

        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Cliente';
    }

    function eliminarCliente(id) {
        if (!confirm('¿Está seguro de eliminar este cliente?')) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="eliminar_cliente" value="1">
            <input type="hidden" name="cliente_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
    </script>
</body>
</html>
