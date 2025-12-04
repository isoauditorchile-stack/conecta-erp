<?php
session_start();
require_once('../config/config.php');

$conn = getDBConnection();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;

// Obtener estadísticas de activos
$sql = "SELECT COUNT(*) as total FROM iso27001_assets WHERE company_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$total_activos = $result->fetch_assoc()['total'];
$stmt->close();

// Activos por criticidad
$sql = "SELECT criticality, COUNT(*) as count FROM iso27001_assets WHERE company_id = ? GROUP BY criticality";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$activos_criticidad = [];
while ($row = $result->fetch_assoc()) {
    $activos_criticidad[$row['criticality']] = $row['count'];
}
$stmt->close();

// Obtener estadísticas de riesgos
$sql = "SELECT COUNT(*) as total FROM iso27001_risks WHERE company_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$total_riesgos = $result->fetch_assoc()['total'];
$stmt->close();

// Riesgos por nivel
$sql = "SELECT risk_level, COUNT(*) as count FROM iso27001_risks WHERE company_id = ? GROUP BY risk_level";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$riesgos_nivel = ['Crítico' => 0, 'Alto' => 0, 'Medio' => 0, 'Bajo' => 0];
while ($row = $result->fetch_assoc()) {
    $riesgos_nivel[$row['risk_level']] = $row['count'];
}
$stmt->close();

// Obtener estadísticas de controles
$sql = "SELECT COUNT(*) as total FROM iso27001_controls WHERE company_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$total_controles = $result->fetch_assoc()['total'];
$stmt->close();

// Controles por estado
$sql = "SELECT implementation_status, COUNT(*) as count FROM iso27001_controls WHERE company_id = ? AND applicable = 'Si' GROUP BY implementation_status";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$controles_estado = [];
while ($row = $result->fetch_assoc()) {
    $controles_estado[$row['implementation_status']] = $row['count'];
}
$stmt->close();

// Controles aplicables
$sql = "SELECT COUNT(*) as count FROM iso27001_controls WHERE company_id = ? AND applicable = 'Si'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$controles_aplicables = $result->fetch_assoc()['count'];
$stmt->close();

// Controles implementados
$sql = "SELECT COUNT(*) as count FROM iso27001_controls WHERE company_id = ? AND applicable = 'Si' AND implementation_status = 'Implementado'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$controles_implementados = $result->fetch_assoc()['count'];
$stmt->close();

// Calcular porcentaje de implementación
$porcentaje_implementacion = $controles_aplicables > 0 ? round(($controles_implementados / $controles_aplicables) * 100, 1) : 0;

// Obtener estadísticas de incidentes
$sql = "SELECT COUNT(*) as total FROM iso27001_incidents WHERE company_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$total_incidentes = $result->fetch_assoc()['total'];
$stmt->close();

// Incidentes por severidad
$sql = "SELECT severity, COUNT(*) as count FROM iso27001_incidents WHERE company_id = ? GROUP BY severity";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$incidentes_severidad = [];
while ($row = $result->fetch_assoc()) {
    $incidentes_severidad[$row['severity']] = $row['count'];
}
$stmt->close();

// Incidentes abiertos
$sql = "SELECT COUNT(*) as count FROM iso27001_incidents WHERE company_id = ? AND incident_status IN ('Abierto', 'En Investigación')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$incidentes_abiertos = $result->fetch_assoc()['count'];
$stmt->close();

// No conformidades
$sql = "SELECT COUNT(*) as total FROM iso27001_nonconformities WHERE company_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$total_nc = $result->fetch_assoc()['total'];
$stmt->close();

// No conformidades abiertas
$sql = "SELECT COUNT(*) as count FROM iso27001_nonconformities WHERE company_id = ? AND nc_status IN ('Abierta', 'En Análisis')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$nc_abiertas = $result->fetch_assoc()['count'];
$stmt->close();

// Calcular nivel de madurez del SGSI
$madurez_score = 0;
if ($total_activos >= 10) $madurez_score += 20;
if ($total_riesgos >= 10) $madurez_score += 20;
if ($porcentaje_implementacion >= 80) $madurez_score += 30;
elseif ($porcentaje_implementacion >= 50) $madurez_score += 20;
elseif ($porcentaje_implementacion >= 20) $madurez_score += 10;
if ($total_incidentes >= 1) $madurez_score += 15;
if ($nc_abiertas == 0 && $total_nc > 0) $madurez_score += 15;

if ($madurez_score >= 80) $nivel_madurez = 'Optimizado';
elseif ($madurez_score >= 60) $nivel_madurez = 'Gestionado';
elseif ($madurez_score >= 40) $nivel_madurez = 'Definido';
elseif ($madurez_score >= 20) $nivel_madurez = 'Inicial';
else $nivel_madurez = 'Inexistente';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen Ejecutivo - ISO 27001 - AUDITOR PRO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 36px;
            color: #00994d;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 16px;
        }
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .kpi-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 5px solid;
        }
        .kpi-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        .kpi-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .kpi-label {
            color: #666;
            font-size: 14px;
        }
        .kpi-activos { border-left-color: #00994d; }
        .kpi-activos .kpi-value { color: #00994d; }
        .kpi-riesgos { border-left-color: #cc0000; }
        .kpi-riesgos .kpi-value { color: #cc0000; }
        .kpi-controles { border-left-color: #9900cc; }
        .kpi-controles .kpi-value { color: #9900cc; }
        .kpi-incidentes { border-left-color: #cc3333; }
        .kpi-incidentes .kpi-value { color: #cc3333; }
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .chart-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        .chart-bar {
            margin-bottom: 15px;
        }
        .chart-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }
        .bar {
            height: 30px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            padding-left: 15px;
            color: white;
            font-weight: bold;
            transition: all 0.3s;
        }
        .bar:hover {
            transform: scaleX(1.02);
        }
        .bar-critico { background: #cc0000; }
        .bar-alto { background: #ff6600; }
        .bar-medio { background: #ffaa00; }
        .bar-bajo { background: #00994d; }
        .bar-implementado { background: #00994d; }
        .bar-parcial { background: #ffaa00; }
        .bar-planeado { background: #0066cc; }
        .bar-noaplica { background: #999; }
        .madurez-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 30px;
        }
        .madurez-gauge {
            width: 100%;
            max-width: 400px;
            height: 40px;
            background: linear-gradient(to right, #cc0000 0%, #ff6600 25%, #ffaa00 50%, #66cc00 75%, #00994d 100%);
            border-radius: 20px;
            margin: 30px auto;
            position: relative;
        }
        .madurez-indicator {
            position: absolute;
            top: -10px;
            width: 4px;
            height: 60px;
            background: #333;
            transition: left 0.5s;
        }
        .madurez-label {
            font-size: 32px;
            font-weight: bold;
            color: #00994d;
            margin-bottom: 10px;
        }
        .madurez-score {
            font-size: 48px;
            font-weight: bold;
            color: #0066cc;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s;
            margin: 5px;
        }
        .btn-primary {
            background: #00994d;
            color: white;
        }
        .btn-primary:hover {
            background: #007a3d;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #666;
            color: white;
        }
        .actions-bar {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn btn-secondary">← Volver al Dashboard ISO 27001</a>

        <div class="header">
            <h1>📊 Resumen Ejecutivo del SGSI</h1>
            <p>Dashboard con indicadores clave de desempeño (KPIs) del Sistema de Gestión de Seguridad de la Información</p>
        </div>

        <!-- KPIs Principales -->
        <div class="kpi-grid">
            <div class="kpi-card kpi-activos">
                <div class="kpi-icon">🗂️</div>
                <div class="kpi-value"><?php echo $total_activos; ?></div>
                <div class="kpi-label">Activos de Información</div>
            </div>

            <div class="kpi-card kpi-riesgos">
                <div class="kpi-icon">⚠️</div>
                <div class="kpi-value"><?php echo $total_riesgos; ?></div>
                <div class="kpi-label">Riesgos Identificados</div>
            </div>

            <div class="kpi-card kpi-controles">
                <div class="kpi-icon">⚙️</div>
                <div class="kpi-value"><?php echo $porcentaje_implementacion; ?>%</div>
                <div class="kpi-label">Controles Implementados</div>
            </div>

            <div class="kpi-card kpi-incidentes">
                <div class="kpi-icon">🚨</div>
                <div class="kpi-value"><?php echo $incidentes_abiertos; ?></div>
                <div class="kpi-label">Incidentes Abiertos</div>
            </div>
        </div>

        <!-- Nivel de Madurez -->
        <div class="madurez-card">
            <h2>🎯 Nivel de Madurez del SGSI</h2>
            <div class="madurez-score"><?php echo $madurez_score; ?>/100</div>
            <div class="madurez-label"><?php echo $nivel_madurez; ?></div>
            <div class="madurez-gauge">
                <div class="madurez-indicator" style="left: <?php echo $madurez_score; ?>%;"></div>
            </div>
            <p style="color: #666; margin-top: 20px;">
                <?php
                if ($madurez_score >= 80) echo "Excelente: El SGSI está optimizado y mejora continuamente.";
                elseif ($madurez_score >= 60) echo "Bueno: El SGSI está gestionado con procesos medibles.";
                elseif ($madurez_score >= 40) echo "Regular: El SGSI está definido pero requiere mejoras.";
                elseif ($madurez_score >= 20) echo "Básico: El SGSI está en fase inicial de implementación.";
                else echo "Crítico: Se requiere iniciar implementación del SGSI urgentemente.";
                ?>
            </p>
        </div>

        <!-- Gráficos de Análisis -->
        <div class="charts-grid">
            <!-- Riesgos por Nivel -->
            <div class="chart-card">
                <div class="chart-title">⚠️ Distribución de Riesgos por Nivel</div>

                <div class="chart-bar">
                    <div class="chart-label">
                        <span>Crítico</span>
                        <span><strong><?php echo $riesgos_nivel['Crítico']; ?></strong></span>
                    </div>
                    <div class="bar bar-critico" style="width: <?php echo $total_riesgos > 0 ? ($riesgos_nivel['Crítico']/$total_riesgos)*100 : 0; ?>%;">
                        <?php if ($riesgos_nivel['Crítico'] > 0) echo $riesgos_nivel['Crítico']; ?>
                    </div>
                </div>

                <div class="chart-bar">
                    <div class="chart-label">
                        <span>Alto</span>
                        <span><strong><?php echo $riesgos_nivel['Alto']; ?></strong></span>
                    </div>
                    <div class="bar bar-alto" style="width: <?php echo $total_riesgos > 0 ? ($riesgos_nivel['Alto']/$total_riesgos)*100 : 0; ?>%;">
                        <?php if ($riesgos_nivel['Alto'] > 0) echo $riesgos_nivel['Alto']; ?>
                    </div>
                </div>

                <div class="chart-bar">
                    <div class="chart-label">
                        <span>Medio</span>
                        <span><strong><?php echo $riesgos_nivel['Medio']; ?></strong></span>
                    </div>
                    <div class="bar bar-medio" style="width: <?php echo $total_riesgos > 0 ? ($riesgos_nivel['Medio']/$total_riesgos)*100 : 0; ?>%;">
                        <?php if ($riesgos_nivel['Medio'] > 0) echo $riesgos_nivel['Medio']; ?>
                    </div>
                </div>

                <div class="chart-bar">
                    <div class="chart-label">
                        <span>Bajo</span>
                        <span><strong><?php echo $riesgos_nivel['Bajo']; ?></strong></span>
                    </div>
                    <div class="bar bar-bajo" style="width: <?php echo $total_riesgos > 0 ? ($riesgos_nivel['Bajo']/$total_riesgos)*100 : 0; ?>%;">
                        <?php if ($riesgos_nivel['Bajo'] > 0) echo $riesgos_nivel['Bajo']; ?>
                    </div>
                </div>
            </div>

            <!-- Controles por Estado -->
            <div class="chart-card">
                <div class="chart-title">⚙️ Estado de Implementación de Controles</div>

                <?php
                $estados = ['Implementado', 'Parcialmente Implementado', 'Planeado', 'No Aplicable'];
                $colores = ['implementado', 'parcial', 'planeado', 'noaplica'];

                for ($i = 0; $i < count($estados); $i++) {
                    $estado = $estados[$i];
                    $count = isset($controles_estado[$estado]) ? $controles_estado[$estado] : 0;
                    $width = $controles_aplicables > 0 ? ($count/$controles_aplicables)*100 : 0;
                ?>
                <div class="chart-bar">
                    <div class="chart-label">
                        <span><?php echo $estado; ?></span>
                        <span><strong><?php echo $count; ?></strong></span>
                    </div>
                    <div class="bar bar-<?php echo $colores[$i]; ?>" style="width: <?php echo $width; ?>%;">
                        <?php if ($count > 0) echo $count; ?>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <div class="actions-bar">
            <h3 style="margin-bottom: 20px;">⚡ Acciones Rápidas</h3>
            <a href="matrices.php" class="btn btn-primary">📊 Exportar Matrices</a>
            <a href="importador.php" class="btn btn-primary">📤 Importar Datos</a>
            <a href="export.php?type=informe_ejecutivo" class="btn btn-primary">📄 Informe Ejecutivo</a>
            <a href="formularios/" class="btn btn-primary">📝 Formularios Online</a>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
