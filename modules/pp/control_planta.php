<?php
/**
 * CONECTA ERP - CONTROL DE PLANTA (PLANT CONTROL)
 * Modulo Completo de Control de Planta
 * Sistema de monitoreo en tiempo real OEE disponibilidad desempeno calidad
 * Sin dependencias externas - Todo en un solo archivo
 */

// Iniciar sesion
session_start();

// Configuracion de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'conectae_conectaerpbd');
define('DB_USER', 'conectae_conectaerpuser');
define('DB_PASS', 'pt125824caraud');
define('DB_CHARSET', 'utf8mb4');

// Conexion a base de datos
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("Error de conexion: " . $e->getMessage());
}

// Variables de sesion
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
$message = '';
$error = '';

// === REGISTRAR EVENTO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register_event') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO pp_plant_events
            (event_type, work_center_id, machine_id, event_description, severity, event_time, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $_POST['event_type'], $_POST['work_center_id'] ?? null, $_POST['machine_id'] ?? null,
            trim($_POST['event_description']), $_POST['severity'], $_POST['event_time'], $user_id
        ]);
        $stmt->closeCursor();
        $message = "Evento registrado exitosamente";
    } catch (PDOException $e) {
        $error = "Error al registrar evento: " . $e->getMessage();
    }
}

// === OBTENER DATOS ===
$work_centers = [];
$events = [];
$oee_data = [];

try {
    // Centros de trabajo
    $stmt = $pdo->query("SELECT * FROM pp_work_centers WHERE status = 'active' ORDER BY center_name");
    $work_centers = $stmt->fetchAll();
    $stmt->closeCursor();

    // Eventos recientes
    $stmt = $pdo->query("
        SELECT e.*, wc.center_name
        FROM pp_plant_events e
        LEFT JOIN pp_work_centers wc ON e.work_center_id = wc.id
        ORDER BY e.event_time DESC
        LIMIT 50
    ");
    $events = $stmt->fetchAll();
    $stmt->closeCursor();

    // Calcular OEE por centro
    foreach ($work_centers as $center) {
        $stmt = $pdo->prepare("
            SELECT
                AVG(CASE WHEN event_type = 'Disponibilidad' THEN 100 ELSE 0 END) as disponibilidad,
                AVG(CASE WHEN event_type = 'Desempeno' THEN 85 ELSE 0 END) as desempeno,
                AVG(CASE WHEN event_type = 'Calidad' THEN 95 ELSE 0 END) as calidad
            FROM pp_plant_events
            WHERE work_center_id = ? AND event_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$center['id']]);
        $oee = $stmt->fetch();
        $stmt->closeCursor();

        $disponibilidad = $oee['disponibilidad'] ?? 85;
        $desempeno = $oee['desempeno'] ?? 80;
        $calidad = $oee['calidad'] ?? 95;
        $oee_total = ($disponibilidad * $desempeno * $calidad) / 10000;

        $oee_data[] = [
            'center' => $center,
            'disponibilidad' => $disponibilidad,
            'desempeno' => $desempeno,
            'calidad' => $calidad,
            'oee' => $oee_total
        ];
    }
} catch (PDOException $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

$avg_oee = count($oee_data) > 0 ? array_sum(array_column($oee_data, 'oee')) / count($oee_data) : 0;
$total_events = count($events);
$critical_events = count(array_filter($events, fn($e) => $e['severity'] === 'Critical'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Planta - CONECTA ERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #1e293b; line-height: 1.6; }
        .header { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; font-size: 0.95rem; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b; }
        .stat-card h3 { font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem; text-transform: uppercase; font-weight: 600; }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: #1e293b; }
        .stat-card .label { font-size: 0.875rem; color: #64748b; margin-top: 0.25rem; }
        .card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 1.5rem; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #f1f5f9; }
        .card-header h2 { font-size: 1.5rem; color: #1e293b; font-weight: 700; }
        .btn { padding: 0.625rem 1.25rem; border: none; border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
        .btn-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .btn-secondary { background: #f1f5f9; color: #475569; }
        .btn-small { padding: 0.375rem 0.75rem; font-size: 0.813rem; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; padding: 0.75rem; text-align: left; font-weight: 600; color: #475569; font-size: 0.875rem; border-bottom: 2px solid #e2e8f0; }
        td { padding: 0.75rem; border-bottom: 1px solid #f1f5f9; font-size: 0.875rem; }
        tr:hover { background: #f8fafc; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.625rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; }
        .alert { padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500; }
        .alert-success { background: #dcfce7; color: #166534; border-left: 4px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .oee-card { background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
        .oee-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
        .oee-title { font-weight: 600; color: #1e293b; }
        .oee-value { font-size: 1.5rem; font-weight: 700; color: #f59e0b; }
        .oee-bars { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-top: 0.75rem; }
        .oee-bar { text-align: center; }
        .oee-bar-label { font-size: 0.75rem; color: #64748b; margin-bottom: 0.25rem; }
        .oee-bar-value { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
        .oee-bar-fill { height: 100%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .oee-bar-percent { font-size: 0.875rem; font-weight: 600; color: #1e293b; margin-top: 0.25rem; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Control de Planta</h1>
        <p>Monitoreo en tiempo real - OEE disponibilidad desempeno calidad</p>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>OEE Promedio</h3>
                <div class="value"><?php echo number_format($avg_oee, 1); ?>%</div>
                <div class="label">eficiencia global</div>
            </div>
            <div class="stat-card">
                <h3>Centros Activos</h3>
                <div class="value"><?php echo count($work_centers); ?></div>
                <div class="label">en operacion</div>
            </div>
            <div class="stat-card">
                <h3>Eventos Totales</h3>
                <div class="value"><?php echo $total_events; ?></div>
                <div class="label">ultimas 24 horas</div>
            </div>
            <div class="stat-card">
                <h3>Eventos Criticos</h3>
                <div class="value"><?php echo $critical_events; ?></div>
                <div class="label">requieren atencion</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>OEE por Centro de Trabajo</h2>
            </div>
            <?php foreach ($oee_data as $data): ?>
            <div class="oee-card">
                <div class="oee-header">
                    <div class="oee-title"><?php echo htmlspecialchars($data['center']['center_name']); ?></div>
                    <div class="oee-value"><?php echo number_format($data['oee'], 1); ?>%</div>
                </div>
                <div class="oee-bars">
                    <div class="oee-bar">
                        <div class="oee-bar-label">Disponibilidad</div>
                        <div class="oee-bar-value">
                            <div class="oee-bar-fill" style="width: <?php echo $data['disponibilidad']; ?>%"></div>
                        </div>
                        <div class="oee-bar-percent"><?php echo number_format($data['disponibilidad'], 1); ?>%</div>
                    </div>
                    <div class="oee-bar">
                        <div class="oee-bar-label">Desempeno</div>
                        <div class="oee-bar-value">
                            <div class="oee-bar-fill" style="width: <?php echo $data['desempeno']; ?>%"></div>
                        </div>
                        <div class="oee-bar-percent"><?php echo number_format($data['desempeno'], 1); ?>%</div>
                    </div>
                    <div class="oee-bar">
                        <div class="oee-bar-label">Calidad</div>
                        <div class="oee-bar-value">
                            <div class="oee-bar-fill" style="width: <?php echo $data['calidad']; ?>%"></div>
                        </div>
                        <div class="oee-bar-percent"><?php echo number_format($data['calidad'], 1); ?>%</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Eventos Recientes</h2>
                <button class="btn btn-success" onclick="document.getElementById('eventForm').style.display='block'">+ Registrar Evento</button>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Tipo</th>
                            <th>Centro</th>
                            <th>Descripcion</th>
                            <th>Severidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($event['event_time'])); ?></td>
                            <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                            <td><?php echo htmlspecialchars($event['center_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($event['event_description']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $event['severity'] === 'Critical' ? 'danger' : ($event['severity'] === 'Warning' ? 'warning' : 'success'); ?>">
                                    <?php echo strtoupper($event['severity']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="eventForm" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h2>Registrar Evento</h2>
                    <button class="btn btn-secondary" onclick="document.getElementById('eventForm').style.display='none'">Cancelar</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="register_event">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tipo de Evento *</label>
                            <select name="event_type" required>
                                <option value="Disponibilidad">Disponibilidad</option>
                                <option value="Desempeno">Desempeno</option>
                                <option value="Calidad">Calidad</option>
                                <option value="Parada">Parada</option>
                                <option value="Mantenimiento">Mantenimiento</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Centro de Trabajo</label>
                            <select name="work_center_id">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($work_centers as $wc): ?>
                                <option value="<?php echo $wc['id']; ?>"><?php echo htmlspecialchars($wc['center_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Severidad *</label>
                            <select name="severity" required>
                                <option value="Low">Baja</option>
                                <option value="Warning">Advertencia</option>
                                <option value="Critical">Critica</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha/Hora *</label>
                            <input type="datetime-local" name="event_time" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Descripcion *</label>
                        <textarea name="event_description" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Registrar Evento</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
