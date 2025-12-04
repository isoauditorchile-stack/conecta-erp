<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'conectae_isogestionuser');
define('DB_PASS', 'pt125824caraud');
define('DB_NAME', 'conectae_isogestionbd');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Error de conexion: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;
$language = isset($_SESSION['language']) ? $_SESSION['language'] : 'es';

$message = '';
$message_type = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $stmt = $conn->prepare("INSERT INTO iso22301_processes (company_id, process_id, process_name, process_description, process_owner, department, process_type, criticality, rto_hours, rpo_hours, mtpd_hours, mbco_hours, peak_periods, dependencies, resources_required, key_personnel, systems_applications, vital_records, suppliers, financial_impact_1h, financial_impact_4h, financial_impact_24h, financial_impact_1w, operational_impact, reputational_impact, legal_regulatory_impact, minimum_resources, alternate_location, backup_systems, workaround_procedures, created_by, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            $stmt->bind_param("isssssssddddsssssssddddsssssssi",
                $company_id,
                $_POST['process_id'],
                $_POST['process_name'],
                $_POST['process_description'],
                $_POST['process_owner'],
                $_POST['department'],
                $_POST['process_type'],
                $_POST['criticality'],
                $_POST['rto_hours'],
                $_POST['rpo_hours'],
                $_POST['mtpd_hours'],
                $_POST['mbco_hours'],
                $_POST['peak_periods'],
                $_POST['dependencies'],
                $_POST['resources_required'],
                $_POST['key_personnel'],
                $_POST['systems_applications'],
                $_POST['vital_records'],
                $_POST['suppliers'],
                $_POST['financial_impact_1h'],
                $_POST['financial_impact_4h'],
                $_POST['financial_impact_24h'],
                $_POST['financial_impact_1w'],
                $_POST['operational_impact'],
                $_POST['reputational_impact'],
                $_POST['legal_regulatory_impact'],
                $_POST['minimum_resources'],
                $_POST['alternate_location'],
                $_POST['backup_systems'],
                $_POST['workaround_procedures'],
                $user_id
            );

            if ($stmt->execute()) {
                $message = 'Proceso agregado exitosamente al BIA';
                $message_type = 'success';
            } else {
                $message = 'Error al agregar proceso: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// Obtener estadisticas
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso22301_processes WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$total_processes = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso22301_processes WHERE company_id = ? AND criticality = 'Muy Critico'");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$very_critical = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Obtener procesos
$stmt = $conn->prepare("SELECT * FROM iso22301_processes WHERE company_id = ? ORDER BY criticality DESC, rto_hours ASC");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$processes_result = $stmt->get_result();
$stmt->close();

$texts = [
    'es' => [
        'title' => 'Analisis de Impacto al Negocio (BIA)',
        'subtitle' => 'Identificacion y Evaluacion de Procesos Criticos',
        'total_processes' => 'Procesos Analizados',
        'very_critical' => 'Muy Criticos',
        'add_process' => 'Agregar Proceso',
        'process_id' => 'ID Proceso',
        'process_name' => 'Nombre del Proceso',
        'description' => 'Descripcion',
        'owner' => 'Responsable',
        'department' => 'Departamento',
        'type' => 'Tipo',
        'criticality' => 'Criticidad',
        'rto' => 'RTO (hrs)',
        'rpo' => 'RPO (hrs)',
        'mtpd' => 'MTPD (hrs)',
        'mbco' => 'MBCO (hrs)',
        'financial_impact' => 'Impacto Financiero',
        'actions' => 'Acciones',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
        'back' => 'Volver',
        'save' => 'Guardar',
        'cancel' => 'Cancelar'
    ]
];

$t = $texts[$language];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?> - ISO 22301 - AUDITOR PRO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1600px; margin: 0 auto; }
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .header h1 { font-size: 32px; color: #ff6600; margin-bottom: 10px; }
        .header p { color: #666; font-size: 16px; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #ff6600;
        }
        .stat-value { font-size: 36px; font-weight: bold; color: #333; }
        .stat-label { color: #666; font-size: 13px; margin-top: 5px; }
        .info-box {
            background: #fff3cd;
            border-left: 4px solid #ff6600;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .info-box h3 { color: #ff6600; margin-bottom: 10px; }
        .info-box ul { margin-left: 20px; margin-top: 10px; line-height: 1.8; }
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: #ff6600;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }
        tr:hover { background: #f8f9fa; }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-critical { background: #cc0000; color: white; }
        .badge-high { background: #ff6600; color: white; }
        .badge-medium { background: #ffcc00; color: #333; }
        .badge-low { background: #00994d; color: white; }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-primary { background: #0066cc; color: white; }
        .btn-success { background: #00994d; color: white; }
        .btn-danger { background: #cc0000; color: white; }
        .btn-secondary { background: #666; color: white; }
        .btn-small { padding: 6px 12px; font-size: 12px; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }
        .action-buttons { display: flex; gap: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="action-buttons">
            <a href="index.php" class="btn btn-secondary">← <?php echo $t['back']; ?></a>
            <button class="btn btn-success">+ <?php echo $t['add_process']; ?></button>
        </div>

        <div class="header">
            <h1>📊 <?php echo $t['title']; ?></h1>
            <p><?php echo $t['subtitle']; ?></p>

            <div class="stats">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $total_processes; ?></div>
                    <div class="stat-label"><?php echo $t['total_processes']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $very_critical; ?></div>
                    <div class="stat-label"><?php echo $t['very_critical']; ?></div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
        <div style="background: <?php echo $message_type == 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message_type == 'success' ? '#155724' : '#721c24'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold;">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="info-box">
            <h3>Sobre el Analisis de Impacto al Negocio (BIA)</h3>
            <p>El BIA identifica y evalua los efectos de interrupciones en procesos de negocio criticos. Define:</p>
            <ul>
                <li><strong>RTO (Recovery Time Objective):</strong> Tiempo maximo aceptable para recuperar el proceso</li>
                <li><strong>RPO (Recovery Point Objective):</strong> Perdida maxima aceptable de datos</li>
                <li><strong>MTPD (Maximum Tolerable Period of Disruption):</strong> Tiempo maximo que el proceso puede estar interrumpido</li>
                <li><strong>MBCO (Minimum Business Continuity Objective):</strong> Nivel minimo de servicio aceptable</li>
            </ul>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?php echo $t['process_id']; ?></th>
                        <th><?php echo $t['process_name']; ?></th>
                        <th><?php echo $t['department']; ?></th>
                        <th><?php echo $t['owner']; ?></th>
                        <th><?php echo $t['criticality']; ?></th>
                        <th><?php echo $t['rto']; ?></th>
                        <th><?php echo $t['rpo']; ?></th>
                        <th><?php echo $t['mtpd']; ?></th>
                        <th><?php echo $t['actions']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($process = $processes_result->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($process['process_id']); ?></strong></td>
                        <td><?php echo htmlspecialchars($process['process_name']); ?></td>
                        <td><?php echo htmlspecialchars($process['department']); ?></td>
                        <td><?php echo htmlspecialchars($process['process_owner']); ?></td>
                        <td>
                            <?php
                            $criticality = $process['criticality'];
                            $badge_class = 'badge-medium';
                            if ($criticality == 'Muy Critico') $badge_class = 'badge-critical';
                            elseif ($criticality == 'Critico') $badge_class = 'badge-high';
                            elseif ($criticality == 'Bajo') $badge_class = 'badge-low';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo $criticality; ?></span>
                        </td>
                        <td><?php echo $process['rto_hours']; ?> hrs</td>
                        <td><?php echo $process['rpo_hours']; ?> hrs</td>
                        <td><?php echo $process['mtpd_hours']; ?> hrs</td>
                        <td>
                            <button class="btn btn-primary btn-small"><?php echo $t['edit']; ?></button>
                            <button class="btn btn-danger btn-small"><?php echo $t['delete']; ?></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
