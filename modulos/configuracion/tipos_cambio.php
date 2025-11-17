<?php
/**
 * GESTIÓN DE TIPOS DE CAMBIO - CONECTA ERP
 * Actualización manual y automática de tipos de cambio
 * Integración con API mindicador.cl
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

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
// CREAR TIPO DE CAMBIO MANUAL
// ==================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_tipo_cambio'])) {
    $moneda_origen = $_POST['moneda_origen'];
    $moneda_destino = $_POST['moneda_destino'];
    $tasa = floatval($_POST['tasa']);
    $fecha = $_POST['fecha'];

    $stmt = $conn->prepare("INSERT INTO tipos_cambio (empresa_id, moneda_origen, moneda_destino, tasa, fecha, origen) VALUES (?, ?, ?, ?, ?, 'manual')");
    $stmt->bind_param("issds", $empresa_id, $moneda_origen, $moneda_destino, $tasa, $fecha);

    if ($stmt->execute()) {
        $mensaje = "Tipo de cambio creado exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al crear tipo de cambio: " . $stmt->error;
        $tipo_mensaje = "danger";
    }
    $stmt->close();
}

// ==================================================================
// ACTUALIZAR DESDE API
// ==================================================================
if (isset($_GET['actualizar_api'])) {
    $api_url = 'https://mindicador.cl/api';
    $context = stream_context_create(['http' => ['timeout' => 10]]);
    $response = @file_get_contents($api_url, false, $context);

    if ($response) {
        $data = json_decode($response, true);
        $fecha = date('Y-m-d');
        $actualizados = 0;

        // USD
        if (isset($data['dolar']['valor'])) {
            $tasa_usd = floatval($data['dolar']['valor']);
            $stmt = $conn->prepare("INSERT INTO tipos_cambio (empresa_id, moneda_origen, moneda_destino, tasa, fecha, origen) VALUES (?, 'USD', 'CLP', ?, ?, 'api') ON DUPLICATE KEY UPDATE tasa = VALUES(tasa)");
            $stmt->bind_param("ids", $empresa_id, $tasa_usd, $fecha);
            $stmt->execute();
            $actualizados++;
        }

        // EUR
        if (isset($data['euro']['valor'])) {
            $tasa_eur = floatval($data['euro']['valor']);
            $stmt = $conn->prepare("INSERT INTO tipos_cambio (empresa_id, moneda_origen, moneda_destino, tasa, fecha, origen) VALUES (?, 'EUR', 'CLP', ?, ?, 'api') ON DUPLICATE KEY UPDATE tasa = VALUES(tasa)");
            $stmt->bind_param("ids", $empresa_id, $tasa_eur, $fecha);
            $stmt->execute();
            $actualizados++;
        }

        // UF
        if (isset($data['uf']['valor'])) {
            $tasa_uf = floatval($data['uf']['valor']);
            $stmt = $conn->prepare("INSERT INTO tipos_cambio (empresa_id, moneda_origen, moneda_destino, tasa, fecha, origen) VALUES (?, 'UF', 'CLP', ?, ?, 'api') ON DUPLICATE KEY UPDATE tasa = VALUES(tasa)");
            $stmt->bind_param("ids", $empresa_id, $tasa_uf, $fecha);
            $stmt->execute();
            $actualizados++;
        }

        // UTM
        if (isset($data['utm']['valor'])) {
            $tasa_utm = floatval($data['utm']['valor']);
            $stmt = $conn->prepare("INSERT INTO tipos_cambio (empresa_id, moneda_origen, moneda_destino, tasa, fecha, origen) VALUES (?, 'UTM', 'CLP', ?, ?, 'api') ON DUPLICATE KEY UPDATE tasa = VALUES(tasa)");
            $stmt->bind_param("ids", $empresa_id, $tasa_utm, $fecha);
            $stmt->execute();
            $actualizados++;
        }

        $mensaje = "Tipos de cambio actualizados exitosamente ($actualizados monedas)";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al conectar con la API de indicadores";
        $tipo_mensaje = "danger";
    }
}

// ==================================================================
// LISTADO DE TIPOS DE CAMBIO
// ==================================================================
$fecha_filtro = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

$query = "SELECT tc.*,
    mo.nombre as nombre_origen, mo.simbolo as simbolo_origen,
    md.nombre as nombre_destino, md.simbolo as simbolo_destino
    FROM tipos_cambio tc
    LEFT JOIN monedas mo ON tc.moneda_origen = mo.codigo
    LEFT JOIN monedas md ON tc.moneda_destino = md.codigo
    WHERE tc.empresa_id = $empresa_id AND tc.fecha = '$fecha_filtro'
    ORDER BY tc.moneda_origen ASC";

$tipos_cambio = $conn->query($query);

// Obtener monedas disponibles
$monedas = $conn->query("SELECT * FROM monedas WHERE activo = 1 ORDER BY codigo");

// Tipos de cambio actuales (hoy)
$hoy = date('Y-m-d');
$tc_hoy = $conn->query("SELECT * FROM tipos_cambio WHERE empresa_id = $empresa_id AND fecha = '$hoy' ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tipos de Cambio - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .rate-card {
            border-left: 4px solid;
            transition: all 0.3s;
        }
        .rate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .currency-symbol {
            font-size: 2rem;
            opacity: 0.3;
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
                    <h2><i class="fas fa-exchange-alt"></i> Tipos de Cambio</h2>
                    <p class="text-muted">Gestión de tasas de cambio de monedas</p>
                </div>
                <div>
                    <a href="?actualizar_api=1" class="btn btn-success me-2">
                        <i class="fas fa-sync"></i> Actualizar desde API
                    </a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                        <i class="fas fa-plus"></i> Nuevo Tipo de Cambio
                    </button>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Tipos de Cambio Actuales (HOY) -->
            <div class="mb-4">
                <h4 class="mb-3"><i class="fas fa-calendar-day"></i> Tipos de Cambio Hoy (<?php echo date('d-m-Y'); ?>)</h4>
                <div class="row">
                    <?php
                    $tc_hoy_data = [];
                    while ($tc = $tc_hoy->fetch_assoc()) {
                        $tc_hoy_data[] = $tc;
                    }

                    $colors = ['#667eea', '#48bb78', '#f59e0b', '#ef4444', '#8b5cf6'];
                    foreach ($tc_hoy_data as $index => $tc):
                        $color = $colors[$index % count($colors)];
                    ?>
                        <div class="col-md-4 col-lg-2 mb-3">
                            <div class="card rate-card shadow-sm h-100" style="border-left-color: <?php echo $color; ?>;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="text-muted mb-0"><?php echo $tc['moneda_origen']; ?></h6>
                                            <small class="text-muted">/ <?php echo $tc['moneda_destino']; ?></small>
                                        </div>
                                        <span class="badge bg-secondary"><?php echo $tc['origen']; ?></span>
                                    </div>
                                    <h4 class="mb-0" style="color: <?php echo $color; ?>;">
                                        $<?php echo number_format($tc['tasa'], 2, ',', '.'); ?>
                                    </h4>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($tc_hoy_data)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No hay tipos de cambio registrados para hoy.
                                <a href="?actualizar_api=1" class="alert-link">Actualizar desde API</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Filtro por Fecha -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-10">
                            <label class="form-label fw-bold">Filtrar por Fecha</label>
                            <input type="date" name="fecha" class="form-control" value="<?php echo $fecha_filtro; ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de Tipos de Cambio -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Tipos de Cambio - <?php echo date('d-m-Y', strtotime($fecha_filtro)); ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Moneda Origen</th>
                                    <th>Moneda Destino</th>
                                    <th class="text-end">Tasa de Cambio</th>
                                    <th>Origen</th>
                                    <th>Fecha Registro</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($tipos_cambio->num_rows > 0): ?>
                                    <?php while ($tc = $tipos_cambio->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $tc['moneda_origen']; ?></strong>
                                                <br><small class="text-muted"><?php echo $tc['nombre_origen']; ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo $tc['moneda_destino']; ?></strong>
                                                <br><small class="text-muted"><?php echo $tc['nombre_destino']; ?></small>
                                            </td>
                                            <td class="text-end">
                                                <h5 class="mb-0 text-primary">
                                                    <?php echo $tc['simbolo_destino']; ?> <?php echo number_format($tc['tasa'], 4, ',', '.'); ?>
                                                </h5>
                                            </td>
                                            <td>
                                                <?php if ($tc['origen'] === 'api'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-cloud"></i> API
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-user"></i> Manual
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d-m-Y H:i', strtotime($tc['created_at'])); ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-info" onclick="verHistorial('<?php echo $tc['moneda_origen']; ?>', '<?php echo $tc['moneda_destino']; ?>')">
                                                    <i class="fas fa-history"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No hay tipos de cambio para esta fecha</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Crear Tipo de Cambio -->
    <div class="modal fade" id="modalCrear" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-plus"></i> Nuevo Tipo de Cambio</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Moneda Origen <span class="text-danger">*</span></label>
                            <select name="moneda_origen" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                <?php
                                $monedas->data_seek(0);
                                while ($m = $monedas->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $m['codigo']; ?>">
                                        <?php echo $m['codigo'] . ' - ' . $m['nombre']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Moneda Destino <span class="text-danger">*</span></label>
                            <select name="moneda_destino" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                <?php
                                $monedas->data_seek(0);
                                while ($m = $monedas->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $m['codigo']; ?>">
                                        <?php echo $m['codigo'] . ' - ' . $m['nombre']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tasa de Cambio <span class="text-danger">*</span></label>
                            <input type="number" name="tasa" class="form-control" step="0.0001" min="0" required
                                   placeholder="Ej: 920.50">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Fecha <span class="text-danger">*</span></label>
                            <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="alert alert-info mb-0">
                            <small>
                                <i class="fas fa-info-circle"></i>
                                La tasa debe expresar cuántas unidades de la moneda destino equivalen a 1 unidad de la moneda origen.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_tipo_cambio" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verHistorial(origen, destino) {
            alert(`Historial de ${origen}/${destino}\n\nEsta funcionalidad mostrará un gráfico de evolución histórica.`);
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
