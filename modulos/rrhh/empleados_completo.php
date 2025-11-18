<?php
/**
 * MÓDULO RRHH - EMPLEADOS COMPLETO
 * Con upload de foto, fórmulas y gestión completa
 */
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$empresa_id = $_SESSION['empresa_id'];
$success = '';
$error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['guardar_empleado'])) {
        try {
            $conn->begin_transaction();

            // Datos básicos
            $datos = [
                'empresa_id' => $empresa_id,
                'rut' => cleanString($conn, $_POST['rut']),
                'nombre' => cleanString($conn, $_POST['nombre']),
                'apellido' => cleanString($conn, $_POST['apellido']),
                'email' => cleanString($conn, $_POST['email']),
                'telefono' => cleanString($conn, $_POST['telefono']),
                'fecha_nacimiento' => $_POST['fecha_nacimiento'],
                'fecha_ingreso' => $_POST['fecha_ingreso'],
                'cargo' => cleanString($conn, $_POST['cargo']),
                'departamento' => cleanString($conn, $_POST['departamento']),
                'sueldo_base' => floatval($_POST['sueldo_base']),
                'tipo_contrato' => $_POST['tipo_contrato'],
                'afp' => $_POST['afp'],
                'sistema_salud' => $_POST['sistema_salud'],
                'cargas_familiares' => intval($_POST['cargas_familiares'] ?? 0),
                'estado' => 'activo'
            ];

            // ISAPRE si aplica
            if ($_POST['sistema_salud'] === 'isapre') {
                $datos['nombre_isapre'] = $_POST['nombre_isapre'] ?? '';
                $datos['plan_isapre_uf'] = floatval($_POST['plan_isapre_uf'] ?? 0);
            }

            // Upload de foto
            $foto_path = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../uploads/empleados/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $filename = 'emp_' . uniqid() . '.' . $extension;
                $foto_path = '/uploads/empleados/' . $filename;

                if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $filename)) {
                    $datos['foto'] = $foto_path;
                }
            }

            // Insertar empleado
            $columns = implode(', ', array_keys($datos));
            $placeholders = implode(', ', array_fill(0, count($datos), '?'));
            $types = str_repeat('s', count($datos));

            $sql = "INSERT INTO empleados ($columns) VALUES ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...array_values($datos));
            $stmt->execute();
            $empleado_id = $conn->insert_id();

            $conn->commit();
            $success = 'Empleado creado correctamente';

            logAuditoria('crear_empleado', 'empleados', $empleado_id, null, json_encode($datos), 'Empleado creado');

        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Obtener empleados
$empleados = fetchAll($conn,
    "SELECT * FROM empleados WHERE empresa_id = ? ORDER BY apellido, nombre",
    [$empresa_id], 'i'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empleados - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .foto-preview {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #667eea;
        }
        .empleado-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .empleado-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-users"></i> Gestión de Empleados</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoEmpleadoModal">
                <i class="fas fa-plus"></i> Nuevo Empleado
            </button>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible">
            <i class="fas fa-check"></i> <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible">
            <i class="fas fa-times"></i> <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Lista de empleados -->
        <div class="row">
            <?php foreach ($empleados as $emp): ?>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card empleado-card h-100">
                    <div class="card-body text-center">
                        <?php if ($emp['foto']): ?>
                        <img src="<?= htmlspecialchars($emp['foto']) ?>" class="foto-preview mb-3" alt="Foto">
                        <?php else: ?>
                        <div class="foto-preview mb-3 d-flex align-items-center justify-content-center bg-light">
                            <i class="fas fa-user fa-3x text-muted"></i>
                        </div>
                        <?php endif; ?>

                        <h5 class="mb-1"><?= htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']) ?></h5>
                        <p class="text-muted small mb-2"><?= htmlspecialchars($emp['cargo'] ?? 'Sin cargo') ?></p>
                        <p class="mb-2"><small><i class="fas fa-id-card"></i> <?= formatRUT($emp['rut']) ?></small></p>
                        <p class="mb-2"><strong><?= formatCurrency($emp['sueldo_base'], 'CLP') ?></strong></p>

                        <div class="btn-group btn-group-sm w-100">
                            <a href="liquidaciones.php?empleado=<?= $emp['id'] ?>" class="btn btn-outline-primary">
                                <i class="fas fa-file-invoice-dollar"></i> Liquidación
                            </a>
                            <button class="btn btn-outline-secondary">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal Nuevo Empleado -->
    <div class="modal fade" id="nuevoEmpleadoModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Nuevo Empleado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <!-- Foto -->
                            <div class="col-md-3 text-center mb-3">
                                <label class="form-label fw-bold">Foto del Empleado</label>
                                <div class="mb-3">
                                    <img id="fotoPreview" src="/assets/img/avatar-default.png" class="foto-preview" alt="Preview">
                                </div>
                                <input type="file" name="foto" id="fotoInput" class="form-control" accept="image/*">
                                <small class="text-muted">Opcional - Max 2MB</small>
                            </div>

                            <!-- Datos personales -->
                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">RUT *</label>
                                        <input type="text" name="rut" class="form-control" required placeholder="12.345.678-9">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nombre *</label>
                                        <input type="text" name="nombre" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Apellido *</label>
                                        <input type="text" name="apellido" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Fecha Nacimiento</label>
                                        <input type="date" name="fecha_nacimiento" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Teléfono</label>
                                        <input type="tel" name="telefono" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <!-- Datos laborales -->
                            <div class="col-12"><hr><h6>Datos Laborales</h6></div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cargo *</label>
                                <input type="text" name="cargo" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Departamento</label>
                                <input type="text" name="departamento" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fecha Ingreso *</label>
                                <input type="date" name="fecha_ingreso" class="form-control" required value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Sueldo Base *</label>
                                <input type="number" name="sueldo_base" class="form-control" required min="0" step="1000">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipo Contrato *</label>
                                <select name="tipo_contrato" class="form-select" required>
                                    <option value="indefinido">Indefinido</option>
                                    <option value="plazo_fijo">Plazo Fijo</option>
                                    <option value="por_obra">Por Obra</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cargas Familiares</label>
                                <input type="number" name="cargas_familiares" class="form-control" value="0" min="0">
                            </div>

                            <!-- Previsión -->
                            <div class="col-12"><hr><h6>Previsión y Salud</h6></div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">AFP *</label>
                                <select name="afp" class="form-select" required>
                                    <option value="capital">Capital</option>
                                    <option value="cuprum">Cuprum</option>
                                    <option value="habitat">Habitat</option>
                                    <option value="planvital">PlanVital</option>
                                    <option value="provida">Provida</option>
                                    <option value="modelo">Modelo</option>
                                    <option value="uno">Uno</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sistema Salud *</label>
                                <select name="sistema_salud" id="sistemaSalud" class="form-select" required>
                                    <option value="fonasa">FONASA</option>
                                    <option value="isapre">ISAPRE</option>
                                </select>
                            </div>
                            <div id="isapreFields" style="display:none;" class="col-12">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nombre ISAPRE</label>
                                        <select name="nombre_isapre" class="form-select">
                                            <option value="colmena">Colmena</option>
                                            <option value="consalud">Consalud</option>
                                            <option value="cruz_blanca">Cruz Blanca</option>
                                            <option value="banmedica">Banmédica</option>
                                            <option value="vida_tres">Vida Tres</option>
                                            <option value="nueva_masvida">Nueva Masvida</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Plan ISAPRE (UF)</label>
                                        <input type="number" name="plan_isapre_uf" class="form-control" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="guardar_empleado" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Empleado
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../user/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Preview de foto
    document.getElementById('fotoInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('fotoPreview').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });

    // Mostrar campos ISAPRE
    document.getElementById('sistemaSalud').addEventListener('change', function() {
        document.getElementById('isapreFields').style.display =
            this.value === 'isapre' ? 'block' : 'none';
    });
    </script>
</body>
</html>
