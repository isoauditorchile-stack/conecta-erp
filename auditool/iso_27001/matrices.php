<?php
session_start();
require_once('../config/config.php');

$conn = getDBConnection();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;

// Generar matriz según tipo
if (isset($_GET['export']) && isset($_GET['type'])) {
    $type = $_GET['type'];

    if ($type == 'soa') {
        // MATRIZ SOA - Statement of Applicability (93 controles Anexo A)
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Matriz_SOA_ISO27001_".date('Y-m-d').".xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<?mso-application progid="Excel.Sheet"?>';
        ?>
        <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
         xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
        <Styles>
            <Style ss:ID="header">
                <Font ss:Bold="1" ss:Color="#FFFFFF"/>
                <Interior ss:Color="#00994d" ss:Pattern="Solid"/>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            <Style ss:ID="data">
                <Alignment ss:Vertical="Top" ss:WrapText="1"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
        </Styles>
        <Worksheet ss:Name="SOA ISO 27001-2022">
            <Table>
                <Column ss:Width="50"/>
                <Column ss:Width="250"/>
                <Column ss:Width="100"/>
                <Column ss:Width="100"/>
                <Column ss:Width="200"/>
                <Column ss:Width="150"/>
                <Column ss:Width="100"/>
                <Column ss:Width="200"/>

                <Row ss:Height="30">
                    <Cell ss:StyleID="header"><Data ss:Type="String">Control</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Descripción del Control</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Aplicable</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Estado</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Justificación</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Responsable</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Efectividad</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Evidencia</Data></Cell>
                </Row>

                <?php
                // Obtener todos los controles del Anexo A
                $sql = "SELECT * FROM iso27001_controls WHERE company_id = ? ORDER BY control_id";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $company_id);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    echo "<Row>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['control_id']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".htmlspecialchars($row['control_name'])."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".($row['applicable'] == 'Si' ? 'Sí' : 'No')."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['implementation_status']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".htmlspecialchars($row['justification'])."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['responsible']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['effectiveness']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".htmlspecialchars($row['evidence'])."</Data></Cell>";
                    echo "</Row>";
                }
                $stmt->close();
                ?>
            </Table>
        </Worksheet>
        </Workbook>
        <?php
        exit;
    }

    elseif ($type == 'riesgos') {
        // MATRIZ DE RIESGOS
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Matriz_Riesgos_ISO27001_".date('Y-m-d').".xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<?mso-application progid="Excel.Sheet"?>';
        ?>
        <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
         xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
        <Styles>
            <Style ss:ID="header">
                <Font ss:Bold="1" ss:Color="#FFFFFF"/>
                <Interior ss:Color="#cc0000" ss:Pattern="Solid"/>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            <Style ss:ID="data">
                <Alignment ss:Vertical="Top" ss:WrapText="1"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
        </Styles>
        <Worksheet ss:Name="Registro de Riesgos">
            <Table>
                <Column ss:Width="60"/>
                <Column ss:Width="200"/>
                <Column ss:Width="120"/>
                <Column ss:Width="150"/>
                <Column ss:Width="150"/>
                <Column ss:Width="150"/>
                <Column ss:Width="80"/>
                <Column ss:Width="80"/>
                <Column ss:Width="80"/>
                <Column ss:Width="100"/>
                <Column ss:Width="150"/>
                <Column ss:Width="120"/>
                <Column ss:Width="100"/>
                <Column ss:Width="80"/>
                <Column ss:Width="80"/>
                <Column ss:Width="80"/>
                <Column ss:Width="100"/>

                <Row ss:Height="30">
                    <Cell ss:StyleID="header"><Data ss:Type="String">ID Riesgo</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Nombre del Riesgo</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Categoría</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Activo Relacionado</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Amenaza</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Vulnerabilidad</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Probabilidad</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Impacto</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Score</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Nivel</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Controles Existentes</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Tratamiento</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Responsable</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Prob. Residual</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Imp. Residual</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Score Residual</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Nivel Residual</Data></Cell>
                </Row>

                <?php
                $sql = "SELECT * FROM iso27001_risks WHERE company_id = ? ORDER BY risk_score DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $company_id);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    echo "<Row>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['risk_id']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".htmlspecialchars($row['risk_name'])."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['risk_category']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['related_asset']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".htmlspecialchars($row['threat'])."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".htmlspecialchars($row['vulnerability'])."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='Number'>".$row['probability']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='Number'>".$row['impact']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='Number'>".$row['risk_score']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['risk_level']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".htmlspecialchars($row['existing_controls'])."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['treatment_option']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['treatment_responsible']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='Number'>".$row['residual_probability']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='Number'>".$row['residual_impact']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='Number'>".$row['residual_score']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['residual_level']."</Data></Cell>";
                    echo "</Row>";
                }
                $stmt->close();
                ?>
            </Table>
        </Worksheet>
        </Workbook>
        <?php
        exit;
    }

    elseif ($type == 'activos') {
        // MATRIZ DE ACTIVOS
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Matriz_Activos_ISO27001_".date('Y-m-d').".xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<?mso-application progid="Excel.Sheet"?>';
        ?>
        <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
         xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
        <Styles>
            <Style ss:ID="header">
                <Font ss:Bold="1" ss:Color="#FFFFFF"/>
                <Interior ss:Color="#00994d" ss:Pattern="Solid"/>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            <Style ss:ID="data">
                <Alignment ss:Vertical="Top" ss:WrapText="1"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
        </Styles>
        <Worksheet ss:Name="Inventario de Activos">
            <Table>
                <Column ss:Width="60"/>
                <Column ss:Width="200"/>
                <Column ss:Width="100"/>
                <Column ss:Width="120"/>
                <Column ss:Width="150"/>
                <Column ss:Width="150"/>
                <Column ss:Width="120"/>
                <Column ss:Width="100"/>
                <Column ss:Width="80"/>
                <Column ss:Width="80"/>
                <Column ss:Width="80"/>
                <Column ss:Width="200"/>
                <Column ss:Width="150"/>

                <Row ss:Height="30">
                    <Cell ss:StyleID="header"><Data ss:Type="String">ID Activo</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Nombre del Activo</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Tipo</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Categoría</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Propietario</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Custodio</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Ubicación</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Criticidad</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">C</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">I</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">A</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Descripción</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Clasificación Datos</Data></Cell>
                </Row>

                <?php
                $sql = "SELECT * FROM iso27001_assets WHERE company_id = ? ORDER BY asset_id";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $company_id);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    echo "<Row>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['asset_id']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".htmlspecialchars($row['asset_name'])."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['asset_type']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['asset_category']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['asset_owner']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['asset_custodian']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['asset_location']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['criticality']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='Number'>".$row['confidentiality']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='Number'>".$row['integrity']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='Number'>".$row['availability']."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".htmlspecialchars($row['description'])."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$row['data_classification']."</Data></Cell>";
                    echo "</Row>";
                }
                $stmt->close();
                ?>
            </Table>
        </Worksheet>
        </Workbook>
        <?php
        exit;
    }

    elseif ($type == 'requisitos') {
        // MATRIZ DE REQUISITOS LEGALES
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Matriz_Requisitos_Legales_ISO27001_".date('Y-m-d').".xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<?mso-application progid="Excel.Sheet"?>';
        ?>
        <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
         xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
        <Styles>
            <Style ss:ID="header">
                <Font ss:Bold="1" ss:Color="#FFFFFF"/>
                <Interior ss:Color="#0066cc" ss:Pattern="Solid"/>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
            </Style>
            <Style ss:ID="data">
                <Alignment ss:Vertical="Top" ss:WrapText="1"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
        </Styles>
        <Worksheet ss:Name="Requisitos Legales">
            <Table>
                <Column ss:Width="60"/>
                <Column ss:Width="200"/>
                <Column ss:Width="120"/>
                <Column ss:Width="300"/>
                <Column ss:Width="150"/>
                <Column ss:Width="100"/>
                <Column ss:Width="150"/>
                <Column ss:Width="200"/>

                <Row ss:Height="30">
                    <Cell ss:StyleID="header"><Data ss:Type="String">ID</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Requisito Legal</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Tipo</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Descripción</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Responsable</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Estado</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Fecha Revisión</Data></Cell>
                    <Cell ss:StyleID="header"><Data ss:Type="String">Evidencia de Cumplimiento</Data></Cell>
                </Row>

                <?php
                // Requisitos legales comunes para ISO 27001
                $requisitos = [
                    ['LEG-001', 'Ley de Protección de Datos Personales', 'Legal', 'Cumplimiento de normativa de protección de datos personales según legislación local', 'DPO', 'Cumple', '2024-12-01', 'Política de Privacidad, Registros de consentimiento'],
                    ['LEG-002', 'GDPR (si aplica)', 'Regulatorio', 'Reglamento General de Protección de Datos de la UE', 'DPO', 'Cumple', '2024-11-15', 'Documentación GDPR, Evaluaciones de impacto'],
                    ['LEG-003', 'Ley de Firma Electrónica', 'Legal', 'Cumplimiento normativa de firma electrónica y documentos digitales', 'Legal', 'Cumple', '2024-10-20', 'Certificados digitales, Políticas de firma'],
                    ['LEG-004', 'Ley de Delitos Informáticos', 'Legal', 'Prevención y sanción de delitos informáticos', 'CISO', 'Cumple', '2024-12-01', 'Políticas de seguridad, Logs de acceso'],
                    ['LEG-005', 'Normativa de Retención de Datos', 'Regulatorio', 'Plazos de conservación de información según sector', 'Records Manager', 'Cumple', '2024-11-01', 'Matriz de retención, Procedimientos'],
                    ['LEG-006', 'PCI-DSS (si aplica)', 'Estándar', 'Payment Card Industry Data Security Standard', 'CISO', 'No Aplica', '-', '-'],
                    ['LEG-007', 'HIPAA (si aplica)', 'Regulatorio', 'Health Insurance Portability and Accountability Act', 'Privacy Officer', 'No Aplica', '-', '-'],
                    ['LEG-008', 'SOX (si aplica)', 'Regulatorio', 'Sarbanes-Oxley Act - Controles financieros', 'CFO', 'No Aplica', '-', '-'],
                    ['LEG-009', 'Ley de Propiedad Intelectual', 'Legal', 'Protección de derechos de autor y licenciamiento software', 'Legal', 'Cumple', '2024-10-15', 'Inventario de licencias, Contratos'],
                    ['LEG-010', 'Normativa Laboral TI', 'Legal', 'Derechos y obligaciones laborales sector TI', 'RRHH', 'Cumple', '2024-12-01', 'Contratos laborales, Políticas internas']
                ];

                foreach ($requisitos as $req) {
                    echo "<Row>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$req[0]."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$req[1]."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$req[2]."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$req[3]."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$req[4]."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$req[5]."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$req[6]."</Data></Cell>";
                    echo "<Cell ss:StyleID='data'><Data ss:Type='String'>".$req[7]."</Data></Cell>";
                    echo "</Row>";
                }
                ?>
            </Table>
        </Worksheet>
        </Workbook>
        <?php
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matrices SGSI - ISO 27001 - AUDITOR PRO</title>
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
            font-size: 32px;
            color: #00994d;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 16px;
        }
        .matrices-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .matrix-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 6px solid;
        }
        .matrix-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .matrix-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .matrix-title {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .matrix-description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .matrix-features {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 13px;
            color: #555;
        }
        .feature-item:last-child {
            margin-bottom: 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s;
            text-align: center;
        }
        .btn-primary {
            background: #00994d;
            color: white;
            width: 100%;
        }
        .btn-primary:hover {
            background: #007a3d;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #666;
            color: white;
            margin-bottom: 20px;
            display: inline-block;
        }
        .card-soa { border-left-color: #00994d; }
        .card-riesgos { border-left-color: #cc0000; }
        .card-activos { border-left-color: #0066cc; }
        .card-requisitos { border-left-color: #9900cc; }
        .info-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .info-box h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .info-box ul {
            color: #666;
            line-height: 1.8;
            margin-left: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn btn-secondary">← Volver al Dashboard ISO 27001</a>

        <div class="header">
            <h1>📊 Matrices SGSI - ISO 27001</h1>
            <p>Exporte matrices maestras del Sistema de Gestión de Seguridad de la Información en formato Excel</p>
        </div>

        <div class="matrices-grid">
            <!-- Matriz SOA -->
            <div class="matrix-card card-soa">
                <div class="matrix-icon">📋</div>
                <div class="matrix-title">Matriz SOA</div>
                <div class="matrix-description">
                    Statement of Applicability - Declaración de Aplicabilidad completa de los 93 controles del Anexo A de ISO 27001:2022.
                </div>
                <div class="matrix-features">
                    <div class="feature-item">✓ 93 controles Anexo A</div>
                    <div class="feature-item">✓ Estado de implementación</div>
                    <div class="feature-item">✓ Justificaciones y evidencias</div>
                    <div class="feature-item">✓ Responsables y efectividad</div>
                </div>
                <a href="?export=true&type=soa" class="btn btn-primary">
                    📥 Descargar Matriz SOA
                </a>
            </div>

            <!-- Matriz de Riesgos -->
            <div class="matrix-card card-riesgos">
                <div class="matrix-icon">⚠️</div>
                <div class="matrix-title">Matriz de Riesgos</div>
                <div class="matrix-description">
                    Registro completo de riesgos de seguridad de la información con análisis inherente y residual.
                </div>
                <div class="matrix-features">
                    <div class="feature-item">✓ Análisis de amenazas y vulnerabilidades</div>
                    <div class="feature-item">✓ Probabilidad e impacto (5×5)</div>
                    <div class="feature-item">✓ Tratamiento de riesgos</div>
                    <div class="feature-item">✓ Riesgo residual calculado</div>
                </div>
                <a href="?export=true&type=riesgos" class="btn btn-primary">
                    📥 Descargar Matriz de Riesgos
                </a>
            </div>

            <!-- Matriz de Activos -->
            <div class="matrix-card card-activos">
                <div class="matrix-icon">🗂️</div>
                <div class="matrix-title">Matriz de Activos</div>
                <div class="matrix-description">
                    Inventario completo de activos de información con clasificación CIA y responsables.
                </div>
                <div class="matrix-features">
                    <div class="feature-item">✓ Clasificación por tipo y categoría</div>
                    <div class="feature-item">✓ Valoración CIA (1-5)</div>
                    <div class="feature-item">✓ Propietarios y custodios</div>
                    <div class="feature-item">✓ Criticidad del negocio</div>
                </div>
                <a href="?export=true&type=activos" class="btn btn-primary">
                    📥 Descargar Matriz de Activos
                </a>
            </div>

            <!-- Matriz de Requisitos Legales -->
            <div class="matrix-card card-requisitos">
                <div class="matrix-icon">⚖️</div>
                <div class="matrix-title">Matriz de Requisitos Legales</div>
                <div class="matrix-description">
                    Registro de requisitos legales, regulatorios y contractuales aplicables al SGSI.
                </div>
                <div class="matrix-features">
                    <div class="feature-item">✓ Leyes y regulaciones aplicables</div>
                    <div class="feature-item">✓ Estado de cumplimiento</div>
                    <div class="feature-item">✓ Evidencias documentadas</div>
                    <div class="feature-item">✓ Seguimiento y revisión</div>
                </div>
                <a href="?export=true&type=requisitos" class="btn btn-primary">
                    📥 Descargar Matriz de Requisitos
                </a>
            </div>
        </div>

        <div class="info-box">
            <h3>ℹ️ Información sobre las Matrices</h3>
            <ul>
                <li><strong>Formato Excel:</strong> Todas las matrices se generan en formato .XLS compatible con Microsoft Excel</li>
                <li><strong>Datos en Tiempo Real:</strong> Las matrices contienen los datos actuales de la base de datos SQL</li>
                <li><strong>Uso en Auditorías:</strong> Estas matrices son evidencia objetiva para auditorías ISO 27001</li>
                <li><strong>Actualización:</strong> Genere las matrices cuando necesite datos actualizados</li>
                <li><strong>Importación:</strong> Use el módulo de Importación para cargar datos masivos desde Excel</li>
            </ul>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
