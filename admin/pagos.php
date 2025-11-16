<?php
session_start();
require_once '../includes/config.php';

// Verificar autenticación y rol de super admin
requireLogin();
requireSuperAdmin();

$message = '';
$message_type = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['pago_id'])) {
        $pago_id = intval($_POST['pago_id']);
        $action = $_POST['action'];

        switch ($action) {
            case 'aprobar':
                $stmt = $conn->prepare("UPDATE pagos SET estado = 'aprobado', fecha_pago = NOW() WHERE id = ?");
                $stmt->bind_param("i", $pago_id);
                if ($stmt->execute()) {
                    // Actualizar suscripción del usuario
                    $pago_info = $conn->query("SELECT usuario_id, suscripcion_id FROM pagos WHERE id = $pago_id")->fetch_assoc();

                    $stmt2 = $conn->prepare("UPDATE usuarios SET suscripcion_activa = 1, en_periodo_prueba = 0 WHERE id = ?");
                    $stmt2->bind_param("i", $pago_info['usuario_id']);
                    $stmt2->execute();
                    $stmt2->close();

                    $stmt3 = $conn->prepare("UPDATE suscripciones SET estado = 'activo' WHERE id = ?");
                    $stmt3->bind_param("i", $pago_info['suscripcion_id']);
                    $stmt3->execute();
                    $stmt3->close();

                    $message = "Pago aprobado y suscripción activada";
                    $message_type = "success";

                    logAuditoria('aprobar_pago', 'pagos', $pago_id, null, null, 'Pago aprobado por super admin');
                }
                $stmt->close();
                break;

            case 'rechazar':
                $stmt = $conn->prepare("UPDATE pagos SET estado = 'rechazado' WHERE id = ?");
                $stmt->bind_param("i", $pago_id);
                if ($stmt->execute()) {
                    $message = "Pago rechazado";
                    $message_type = "warning";

                    logAuditoria('rechazar_pago', 'pagos', $pago_id, null, null, 'Pago rechazado por super admin');
                }
                $stmt->close();
                break;

            case 'reembolsar':
                $stmt = $conn->prepare("UPDATE pagos SET estado = 'reembolsado' WHERE id = ?");
                $stmt->bind_param("i", $pago_id);
                if ($stmt->execute()) {
                    $message = "Pago marcado como reembolsado";
                    $message_type = "info";

                    logAuditoria('reembolsar_pago', 'pagos', $pago_id, null, null, 'Pago reembolsado por super admin');
                }
                $stmt->close();
                break;
        }
    }
}

// Filtros
$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_metodo = $_GET['metodo'] ?? 'todos';
$filtro_fecha = $_GET['fecha'] ?? 'todos';

// Construir query
$where_clauses = ["1=1"];

if ($filtro_estado !== 'todos') {
    $where_clauses[] = "p.estado = '$filtro_estado'";
}

if ($filtro_metodo !== 'todos') {
    $metodo_safe = $conn->real_escape_string($filtro_metodo);
    $where_clauses[] = "p.metodo_pago = '$metodo_safe'";
}

if ($filtro_fecha === 'hoy') {
    $where_clauses[] = "DATE(p.fecha_creacion) = CURDATE()";
} elseif ($filtro_fecha === 'semana') {
    $where_clauses[] = "p.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($filtro_fecha === 'mes') {
    $where_clauses[] = "p.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

$where_sql = implode(' AND ', $where_clauses);

// Obtener pagos
$query = "SELECT p.*,
          u.nombre, u.apellido, u.email,
          e.nombre_empresa,
          pl.nombre as plan_nombre,
          s.fecha_inicio, s.fecha_fin
          FROM pagos p
          LEFT JOIN usuarios u ON p.usuario_id = u.id
          LEFT JOIN empresas e ON u.empresa_id = e.id
          LEFT JOIN suscripciones s ON p.suscripcion_id = s.id
          LEFT JOIN planes pl ON s.plan_id = pl.id
          WHERE $where_sql
          ORDER BY p.fecha_creacion DESC
          LIMIT 100";

$pagos = $conn->query($query);

// Estadísticas
$stats = [];
$stats['total_pagos'] = $conn->query("SELECT COUNT(*) as total FROM pagos")->fetch_assoc()['total'];
$stats['pendientes'] = $conn->query("SELECT COUNT(*) as total FROM pagos WHERE estado = 'pendiente'")->fetch_assoc()['total'];
$stats['aprobados'] = $conn->query("SELECT COUNT(*) as total FROM pagos WHERE estado = 'aprobado'")->fetch_assoc()['total'];
$stats['rechazados'] = $conn->query("SELECT COUNT(*) as total FROM pagos WHERE estado = 'rechazado'")->fetch_assoc()['total'];
$stats['total_ingresos'] = $conn->query("SELECT SUM(monto) as total FROM pagos WHERE estado = 'aprobado'")->fetch_assoc()['total'] ?? 0;
$stats['ingresos_mes'] = $conn->query("SELECT SUM(monto) as total FROM pagos WHERE estado = 'aprobado' AND MONTH(fecha_pago) = MONTH(NOW())")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pagos - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
            --topbar-height: 70px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        /* Sidebar - Mismo estilo que usuarios.php */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .sidebar.collapsed .sidebar-header h2 .text {
            display: none;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid white;
        }

        .sidebar-menu a i {
            font-size: 1.2rem;
            min-width: 30px;
        }

        .sidebar-menu a span {
            margin-left: 10px;
        }

        .sidebar.collapsed .sidebar-menu a span {
            display: none;
        }

        /* Topbar */
        .topbar {
            position: fixed;
            left: var(--sidebar-width);
            top: 0;
            right: 0;
            height: var(--topbar-height);
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            transition: all 0.3s ease;
            z-index: 999;
        }

        .sidebar.collapsed ~ .topbar {
            left: var(--sidebar-collapsed-width);
        }

        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #333;
            cursor: pointer;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 30px;
            transition: all 0.3s ease;
            min-height: calc(100vh - var(--topbar-height));
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
        }

        .stat-card-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Filters */
        .filters-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        /* Table */
        .table-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .table {
            margin-bottom: 0;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .btn-action {
            padding: 5px 10px;
            font-size: 0.85rem;
            margin: 2px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-rocket"></i> <span class="text">CONECTA ERP</span></h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard_admin.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="usuarios.php"><i class="fas fa-users"></i> <span>Usuarios</span></a></li>
            <li><a href="empresas.php"><i class="fas fa-building"></i> <span>Empresas</span></a></li>
            <li><a href="suscripciones.php"><i class="fas fa-credit-card"></i> <span>Suscripciones</span></a></li>
            <li><a href="pagos.php" class="active"><i class="fas fa-dollar-sign"></i> <span>Pagos</span></a></li>
            <li><a href="configuracion.php"><i class="fas fa-cog"></i> <span>Configuración</span></a></li>
            <li><a href="reportes.php"><i class="fas fa-chart-bar"></i> <span>Reportes</span></a></li>
            <li><a href="auditoria.php"><i class="fas fa-shield-alt"></i> <span>Auditoría</span></a></li>
            <li><a href="../user/dashboard_user.php" style="margin: 20px 10px; background: rgba(255,255,255,0.2); border-radius: 10px;"><i class="fas fa-eye"></i> <span>Ver como Usuario</span></a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a></li>
        </ul>
    </div>

    <!-- Topbar -->
    <div class="topbar">
        <button class="toggle-sidebar" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <div class="user-menu">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1) . substr($_SESSION['apellido'], 0, 1)); ?>
            </div>
            <div>
                <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']); ?></div>
                <div style="font-size: 0.85rem; color: #6c757d;">Super Administrador</div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1 style="margin-bottom: 30px; color: #333;">
            <i class="fas fa-dollar-sign"></i> Gestión de Pagos
        </h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-value"><?php echo number_format($stats['total_pagos']); ?></div>
                <div class="stat-card-label">Total Pagos</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-value text-warning"><?php echo number_format($stats['pendientes']); ?></div>
                <div class="stat-card-label">Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-value text-success"><?php echo number_format($stats['aprobados']); ?></div>
                <div class="stat-card-label">Aprobados</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-value text-danger"><?php echo number_format($stats['rechazados']); ?></div>
                <div class="stat-card-label">Rechazados</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-value text-success"><?php echo formatCurrency($stats['total_ingresos']); ?></div>
                <div class="stat-card-label">Ingresos Totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-value text-info"><?php echo formatCurrency($stats['ingresos_mes']); ?></div>
                <div class="stat-card-label">Ingresos Este Mes</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-card">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="pendiente" <?php echo $filtro_estado === 'pendiente' ? 'selected' : ''; ?>>Pendientes</option>
                            <option value="aprobado" <?php echo $filtro_estado === 'aprobado' ? 'selected' : ''; ?>>Aprobados</option>
                            <option value="rechazado" <?php echo $filtro_estado === 'rechazado' ? 'selected' : ''; ?>>Rechazados</option>
                            <option value="reembolsado" <?php echo $filtro_estado === 'reembolsado' ? 'selected' : ''; ?>>Reembolsados</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Método de Pago</label>
                        <select name="metodo" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_metodo === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="transferencia" <?php echo $filtro_metodo === 'transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                            <option value="webpay" <?php echo $filtro_metodo === 'webpay' ? 'selected' : ''; ?>>WebPay</option>
                            <option value="mercadopago" <?php echo $filtro_metodo === 'mercadopago' ? 'selected' : ''; ?>>Mercado Pago</option>
                            <option value="tarjeta" <?php echo $filtro_metodo === 'tarjeta' ? 'selected' : ''; ?>>Tarjeta</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha</label>
                        <select name="fecha" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_fecha === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="hoy" <?php echo $filtro_fecha === 'hoy' ? 'selected' : ''; ?>>Hoy</option>
                            <option value="semana" <?php echo $filtro_fecha === 'semana' ? 'selected' : ''; ?>>Última Semana</option>
                            <option value="mes" <?php echo $filtro_fecha === 'mes' ? 'selected' : ''; ?>>Último Mes</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <a href="pagos.php" class="btn btn-secondary w-100"><i class="fas fa-redo"></i> Limpiar Filtros</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabla de Pagos -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Empresa</th>
                            <th>Plan</th>
                            <th>Monto</th>
                            <th>Método</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pagos && $pagos->num_rows > 0): ?>
                            <?php while ($pago = $pagos->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $pago['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($pago['nombre'] . ' ' . $pago['apellido']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($pago['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($pago['nombre_empresa'] ?? 'N/A'); ?></td>
                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($pago['plan_nombre'] ?? 'N/A'); ?></span></td>
                                    <td><strong><?php echo $pago['moneda']; ?> <?php echo number_format($pago['monto'], 0, ',', '.'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($pago['metodo_pago'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        $badge_class = match($pago['estado']) {
                                            'aprobado' => 'success',
                                            'pendiente' => 'warning',
                                            'rechazado' => 'danger',
                                            'reembolsado' => 'info',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class; ?>"><?php echo ucfirst($pago['estado']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo formatDate($pago['fecha_creacion'], 'd/m/Y H:i'); ?><br>
                                        <?php if ($pago['fecha_pago']): ?>
                                            <small class="text-success">Pagado: <?php echo formatDate($pago['fecha_pago'], 'd/m/Y'); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if ($pago['estado'] === 'pendiente'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="pago_id" value="<?php echo $pago['id']; ?>">
                                                    <input type="hidden" name="action" value="aprobar">
                                                    <button type="submit" class="btn btn-sm btn-success btn-action" title="Aprobar">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="pago_id" value="<?php echo $pago['id']; ?>">
                                                    <input type="hidden" name="action" value="rechazar">
                                                    <button type="submit" class="btn btn-sm btn-danger btn-action" title="Rechazar">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($pago['estado'] === 'aprobado'): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas marcar como reembolsado?');">
                                                    <input type="hidden" name="pago_id" value="<?php echo $pago['id']; ?>">
                                                    <input type="hidden" name="action" value="reembolsar">
                                                    <button type="submit" class="btn btn-sm btn-warning btn-action" title="Reembolsar">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($pago['comprobante']): ?>
                                                <a href="<?php echo htmlspecialchars($pago['comprobante']); ?>" target="_blank" class="btn btn-sm btn-info btn-action" title="Ver comprobante">
                                                    <i class="fas fa-file-invoice"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No hay pagos registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
