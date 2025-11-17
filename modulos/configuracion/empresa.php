<?php
/**
 * CONFIGURACIÓN DE EMPRESA - CONECTA ERP
 * Gestión completa de configuración de empresa
 * Incluye datos fiscales, logos, certificados digitales SII
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar autenticación
requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$empresa_id = $stmt->get_result()->fetch_assoc()['empresa_id'];
$stmt->close();

$mensaje = '';
$tipo_mensaje = '';

// ==================================================================
// PROCESAR FORMULARIO DE CONFIGURACIÓN
// ==================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_config'])) {
    $razon_social = trim($_POST['razon_social']);
    $rut = trim($_POST['rut']);
    $giro = trim($_POST['giro']);
    $direccion = trim($_POST['direccion']);
    $ciudad = trim($_POST['ciudad']);
    $region = trim($_POST['region']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $sitio_web = trim($_POST['sitio_web']);

    // Datos SII
    $resolucion_sii = trim($_POST['resolucion_sii']);
    $fecha_resolucion = $_POST['fecha_resolucion'];
    $actividad_economica = trim($_POST['actividad_economica']);

    // Datos contables
    $moneda_base = $_POST['moneda_base'];
    $periodo_fiscal = $_POST['periodo_fiscal'];

    // Logo
    $logo_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = 'logo_empresa_' . $empresa_id . '.' . $ext;
            $upload_path = '../../uploads/logos/' . $new_filename;

            if (!is_dir('../../uploads/logos')) {
                mkdir('../../uploads/logos', 0755, true);
            }

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                $logo_path = '/uploads/logos/' . $new_filename;
            }
        }
    }

    // Actualizar configuración
    $stmt = $conn->prepare("UPDATE configuracion_empresa SET
        razon_social = ?,
        rut = ?,
        giro = ?,
        direccion = ?,
        ciudad = ?,
        region = ?,
        telefono = ?,
        email = ?,
        sitio_web = ?,
        resolucion_sii = ?,
        fecha_resolucion = ?,
        actividad_economica = ?,
        moneda_base = ?,
        periodo_fiscal = ?,
        logo = COALESCE(?, logo)
        WHERE empresa_id = ?");

    $stmt->bind_param("sssssssssssssssi",
        $razon_social, $rut, $giro, $direccion, $ciudad, $region,
        $telefono, $email, $sitio_web, $resolucion_sii, $fecha_resolucion,
        $actividad_economica, $moneda_base, $periodo_fiscal, $logo_path, $empresa_id
    );

    if ($stmt->execute()) {
        $mensaje = "Configuración actualizada exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar configuración: " . $stmt->error;
        $tipo_mensaje = "danger";
    }
    $stmt->close();
}

// ==================================================================
// OBTENER CONFIGURACIÓN ACTUAL
// ==================================================================
$stmt = $conn->prepare("SELECT * FROM configuracion_empresa WHERE empresa_id = ? LIMIT 1");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$config = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Si no existe configuración, crear registro inicial
if (!$config) {
    $stmt = $conn->prepare("INSERT INTO configuracion_empresa (empresa_id) VALUES (?)");
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $stmt->close();

    // Recargar
    $stmt = $conn->prepare("SELECT * FROM configuracion_empresa WHERE empresa_id = ? LIMIT 1");
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $config = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Obtener lista de monedas
$monedas = $conn->query("SELECT * FROM monedas WHERE activo = 1 ORDER BY codigo");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Empresa - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .logo-preview {
            max-width: 200px;
            max-height: 100px;
            border: 2px dashed #ccc;
            padding: 10px;
            border-radius: 8px;
        }
        .section-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            margin-top: 30px;
            margin-bottom: 20px;
        }
        .info-card {
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
    </style>
</head>
<body class="bg-light">

    <?php include '../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid py-4">

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-building"></i> Configuración de Empresa</h2>
                    <p class="text-muted">Gestión de datos fiscales, logos y certificados digitales</p>
                </div>
                <a href="../dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>

            <!-- Mensajes -->
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Información importante -->
            <div class="info-card">
                <h5><i class="fas fa-info-circle text-primary"></i> Información Importante</h5>
                <ul class="mb-0">
                    <li>Esta configuración se utiliza en facturas electrónicas, cotizaciones y documentos oficiales</li>
                    <li>El RUT debe estar registrado en el SII con autorización para facturación electrónica</li>
                    <li>La resolución SII es obligatoria para emitir documentos tributarios electrónicos</li>
                    <li>El logo se mostrará en todos los documentos impresos</li>
                </ul>
            </div>

            <!-- Formulario de Configuración -->
            <form method="POST" enctype="multipart/form-data">

                <!-- SECCIÓN: DATOS GENERALES -->
                <div class="section-title">
                    <h4 class="mb-0"><i class="fas fa-building"></i> Datos Generales</h4>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Razón Social <span class="text-danger">*</span></label>
                                <input type="text" name="razon_social" class="form-control"
                                       value="<?php echo htmlspecialchars($config['razon_social'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">RUT <span class="text-danger">*</span></label>
                                <input type="text" name="rut" class="form-control"
                                       value="<?php echo htmlspecialchars($config['rut'] ?? ''); ?>"
                                       placeholder="12.345.678-9" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Giro / Actividad Comercial <span class="text-danger">*</span></label>
                                <input type="text" name="giro" class="form-control"
                                       value="<?php echo htmlspecialchars($config['giro'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Dirección <span class="text-danger">*</span></label>
                                <input type="text" name="direccion" class="form-control"
                                       value="<?php echo htmlspecialchars($config['direccion'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Ciudad <span class="text-danger">*</span></label>
                                <input type="text" name="ciudad" class="form-control"
                                       value="<?php echo htmlspecialchars($config['ciudad'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Región <span class="text-danger">*</span></label>
                                <select name="region" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    <?php
                                    $regiones = [
                                        'Región de Arica y Parinacota',
                                        'Región de Tarapacá',
                                        'Región de Antofagasta',
                                        'Región de Atacama',
                                        'Región de Coquimbo',
                                        'Región de Valparaíso',
                                        'Región Metropolitana',
                                        'Región del Libertador General Bernardo O\'Higgins',
                                        'Región del Maule',
                                        'Región de Ñuble',
                                        'Región del Biobío',
                                        'Región de La Araucanía',
                                        'Región de Los Ríos',
                                        'Región de Los Lagos',
                                        'Región de Aysén',
                                        'Región de Magallanes'
                                    ];
                                    foreach ($regiones as $region) {
                                        $selected = ($config['region'] ?? '') === $region ? 'selected' : '';
                                        echo "<option value=\"$region\" $selected>$region</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN: DATOS DE CONTACTO -->
                <div class="section-title">
                    <h4 class="mb-0"><i class="fas fa-address-book"></i> Datos de Contacto</h4>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Teléfono</label>
                                <input type="text" name="telefono" class="form-control"
                                       value="<?php echo htmlspecialchars($config['telefono'] ?? ''); ?>"
                                       placeholder="+56 2 1234 5678">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control"
                                       value="<?php echo htmlspecialchars($config['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Sitio Web</label>
                                <input type="url" name="sitio_web" class="form-control"
                                       value="<?php echo htmlspecialchars($config['sitio_web'] ?? ''); ?>"
                                       placeholder="https://www.ejemplo.cl">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN: DATOS TRIBUTARIOS (SII) -->
                <div class="section-title">
                    <h4 class="mb-0"><i class="fas fa-file-invoice-dollar"></i> Datos Tributarios (SII)</h4>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Número Resolución SII <span class="text-danger">*</span></label>
                                <input type="text" name="resolucion_sii" class="form-control"
                                       value="<?php echo htmlspecialchars($config['resolucion_sii'] ?? ''); ?>"
                                       placeholder="Ej: 80" required>
                                <small class="text-muted">Resolución de autorización de facturación electrónica</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Fecha Resolución <span class="text-danger">*</span></label>
                                <input type="date" name="fecha_resolucion" class="form-control"
                                       value="<?php echo $config['fecha_resolucion'] ?? ''; ?>" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Actividad Económica Principal</label>
                                <input type="text" name="actividad_economica" class="form-control"
                                       value="<?php echo htmlspecialchars($config['actividad_economica'] ?? ''); ?>"
                                       placeholder="Código actividad económica del SII">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN: CONFIGURACIÓN CONTABLE -->
                <div class="section-title">
                    <h4 class="mb-0"><i class="fas fa-calculator"></i> Configuración Contable</h4>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Moneda Base <span class="text-danger">*</span></label>
                                <select name="moneda_base" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    <?php while ($moneda = $monedas->fetch_assoc()): ?>
                                        <option value="<?php echo $moneda['codigo']; ?>"
                                                <?php echo ($config['moneda_base'] ?? '') === $moneda['codigo'] ? 'selected' : ''; ?>>
                                            <?php echo $moneda['codigo'] . ' - ' . $moneda['nombre']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Período Fiscal <span class="text-danger">*</span></label>
                                <select name="periodo_fiscal" class="form-select" required>
                                    <option value="mensual" <?php echo ($config['periodo_fiscal'] ?? '') === 'mensual' ? 'selected' : ''; ?>>Mensual</option>
                                    <option value="trimestral" <?php echo ($config['periodo_fiscal'] ?? '') === 'trimestral' ? 'selected' : ''; ?>>Trimestral</option>
                                    <option value="semestral" <?php echo ($config['periodo_fiscal'] ?? '') === 'semestral' ? 'selected' : ''; ?>>Semestral</option>
                                    <option value="anual" <?php echo ($config['periodo_fiscal'] ?? '') === 'anual' ? 'selected' : ''; ?>>Anual</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN: LOGO -->
                <div class="section-title">
                    <h4 class="mb-0"><i class="fas fa-image"></i> Logo de Empresa</h4>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Subir Logo</label>
                                <input type="file" name="logo" class="form-control" accept="image/*" id="logoInput">
                                <small class="text-muted">Formatos: JPG, PNG, GIF. Máximo 2MB</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Vista Previa</label>
                                <div>
                                    <?php if (!empty($config['logo'])): ?>
                                        <img src="<?php echo $config['logo']; ?>" class="logo-preview" id="logoPreview" alt="Logo actual">
                                    <?php else: ?>
                                        <img src="/assets/img/no-logo.png" class="logo-preview" id="logoPreview" alt="Sin logo">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="text-end mb-5">
                    <button type="reset" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-undo"></i> Restablecer
                    </button>
                    <button type="submit" name="guardar_config" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Guardar Configuración
                    </button>
                </div>

            </form>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview de logo
        document.getElementById('logoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('logoPreview').src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
