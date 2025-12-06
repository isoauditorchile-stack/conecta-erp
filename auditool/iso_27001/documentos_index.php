<?php
/**
 * AUDITOR PRO - Índice de Documentos ISO 27001:2022
 * Sistema completo de gestión documentaria
 *
 * @version 2.0
 * @package AuditorPRO
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca de Documentos ISO 27001:2022 - AUDITOR PRO</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            text-align: center;
        }

        .header h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 1.1em;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 3em;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            margin-top: 10px;
        }

        .category {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .category-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #f0f0f0;
        }

        .category-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .category-icon {
            font-size: 2em;
        }

        .category-name {
            font-size: 1.8em;
            color: #333;
            font-weight: 600;
        }

        .category-count {
            background: #667eea;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: bold;
        }

        .download-all-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .download-all-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .document-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
            border-left: 4px solid #667eea;
        }

        .document-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .document-number {
            display: inline-block;
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            margin-right: 10px;
        }

        .document-title {
            font-size: 1.1em;
            color: #333;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .document-format {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 5px;
            font-size: 0.85em;
            font-weight: bold;
            margin-right: 10px;
        }

        .document-format.xlsx {
            background: #10793f;
        }

        .document-format.pdf {
            background: #dc3545;
        }

        .download-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            float: right;
            transition: background 0.3s;
        }

        .download-btn:hover {
            background: #5568d3;
        }

        .info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }

        .info-box h3 {
            color: #856404;
            margin-bottom: 10px;
        }

        .info-box p {
            color: #856404;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🏆 AUDITOR PRO</h1>
            <p>Biblioteca Completa de Documentos ISO 27001:2022</p>
        </div>

        <!-- Estadísticas -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number">85</div>
                <div class="stat-label">Documentos Totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">22</div>
                <div class="stat-label">Políticas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">10</div>
                <div class="stat-label">Procedimientos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">53</div>
                <div class="stat-label">Formatos</div>
            </div>
        </div>

        <!-- POLÍTICAS -->
        <div class="category">
            <div class="category-header">
                <div class="category-title">
                    <span class="category-icon">🔐</span>
                    <span class="category-name">Políticas de Seguridad</span>
                </div>
                <div class="category-count">22 documentos</div>
            </div>
            <div class="documents-grid">
                <?php
                $politicas = [
                    ['num' => 1, 'code' => 'POL-SI-001', 'nombre' => 'Política de Seguridad de la Información'],
                    ['num' => 2, 'code' => 'POL-SI-002', 'nombre' => 'Política de Control de Acceso'],
                    ['num' => 3, 'code' => 'POL-SI-003', 'nombre' => 'Política de Clasificación de la Información'],
                    ['num' => 4, 'code' => 'POL-SI-004', 'nombre' => 'Política de Uso Aceptable de Recursos'],
                    ['num' => 5, 'code' => 'POL-SI-005', 'nombre' => 'Política de Gestión de Activos'],
                    ['num' => 6, 'code' => 'POL-SI-006', 'nombre' => 'Política de Desarrollo Seguro'],
                    ['num' => 7, 'code' => 'POL-SI-007', 'nombre' => 'Política de Gestión de Contraseñas'],
                    ['num' => 8, 'code' => 'POL-SI-008', 'nombre' => 'Política de Respaldo y Recuperación'],
                    ['num' => 9, 'code' => 'POL-SI-009', 'nombre' => 'Política de Criptografía'],
                    ['num' => 10, 'code' => 'POL-SI-010', 'nombre' => 'Política de Seguridad Física y Ambiental'],
                    ['num' => 11, 'code' => 'POL-SI-011', 'nombre' => 'Política de Gestión de Proveedores'],
                    ['num' => 12, 'code' => 'POL-SI-012', 'nombre' => 'Política de Seguridad en la Nube'],
                    ['num' => 13, 'code' => 'POL-SI-013', 'nombre' => 'Política de Gestión de Incidentes'],
                    ['num' => 14, 'code' => 'POL-SI-014', 'nombre' => 'Política de Continuidad del Negocio'],
                    ['num' => 15, 'code' => 'POL-SI-015', 'nombre' => 'Política de Privacidad y Protección de Datos'],
                    ['num' => 16, 'code' => 'POL-SI-016', 'nombre' => 'Política de Teletrabajo'],
                    ['num' => 17, 'code' => 'POL-SI-017', 'nombre' => 'Política de Gestión de Vulnerabilidades'],
                    ['num' => 18, 'code' => 'POL-SI-018', 'nombre' => 'Política de Seguridad en Redes'],
                    ['num' => 19, 'code' => 'POL-SI-019', 'nombre' => 'Política de Monitoreo y Logging'],
                    ['num' => 20, 'code' => 'POL-SI-020', 'nombre' => 'Política de Gestión de Cambios'],
                    ['num' => 21, 'code' => 'POL-SI-021', 'nombre' => 'Política de Capacitación y Concientización'],
                    ['num' => 22, 'code' => 'POL-SI-022', 'nombre' => 'Política de Retención y Eliminación']
                ];

                foreach ($politicas as $pol) {
                    echo '<div class="document-card">';
                    echo '<div class="document-title">';
                    echo '<span class="document-number">' . $pol['num'] . '</span>';
                    echo $pol['nombre'];
                    echo '</div>';
                    echo '<span class="document-format">DOCX</span>';
                    echo '<span class="document-format pdf">PDF</span>';
                    echo '<button class="download-btn" onclick="descargar(\'politicas\', \'' . $pol['code'] . '\', \'docx\')">📥 Descargar</button>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- PROCEDIMIENTOS -->
        <div class="category">
            <div class="category-header">
                <div class="category-title">
                    <span class="category-icon">📋</span>
                    <span class="category-name">Procedimientos</span>
                </div>
                <div class="category-count">10 documentos</div>
            </div>
            <div class="documents-grid">
                <?php
                $procedimientos = [
                    ['num' => 1, 'code' => 'PROC-SI-001', 'nombre' => 'Procedimiento de Gestión de Cambios'],
                    ['num' => 2, 'code' => 'PROC-SI-002', 'nombre' => 'Procedimiento de Gestión de Incidentes'],
                    ['num' => 3, 'code' => 'PROC-SI-003', 'nombre' => 'Procedimiento de Gestión de Vulnerabilidades'],
                    ['num' => 4, 'code' => 'PROC-SI-004', 'nombre' => 'Procedimiento de Auditorías Internas'],
                    ['num' => 5, 'code' => 'PROC-SI-005', 'nombre' => 'Procedimiento de Revisión por la Dirección'],
                    ['num' => 6, 'code' => 'PROC-SI-006', 'nombre' => 'Procedimiento de Control de Documentos'],
                    ['num' => 7, 'code' => 'PROC-SI-007', 'nombre' => 'Procedimiento de Control de Registros'],
                    ['num' => 8, 'code' => 'PROC-SI-008', 'nombre' => 'Procedimiento de Gestión de Activos'],
                    ['num' => 9, 'code' => 'PROC-SI-009', 'nombre' => 'Procedimiento de Alta y Baja de Personal'],
                    ['num' => 10, 'code' => 'PROC-SI-010', 'nombre' => 'Procedimiento de Gestión de Accesos']
                ];

                foreach ($procedimientos as $proc) {
                    echo '<div class="document-card">';
                    echo '<div class="document-title">';
                    echo '<span class="document-number">' . $proc['num'] . '</span>';
                    echo $proc['nombre'];
                    echo '</div>';
                    echo '<span class="document-format">DOCX</span>';
                    echo '<button class="download-btn" onclick="descargar(\'procedimientos\', \'' . $proc['code'] . '\', \'docx\')">📥 Descargar</button>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- INVENTARIOS -->
        <div class="category">
            <div class="category-header">
                <div class="category-title">
                    <span class="category-icon">📊</span>
                    <span class="category-name">Formatos - Inventarios</span>
                </div>
                <div class="category-count">6 documentos</div>
            </div>
            <div class="documents-grid">
                <?php
                $inventarios = [
                    ['num' => 1, 'code' => 'INV-001', 'nombre' => 'Inventario de Activos de Información'],
                    ['num' => 2, 'code' => 'INV-002', 'nombre' => 'Inventario de Hardware'],
                    ['num' => 3, 'code' => 'INV-003', 'nombre' => 'Inventario de Software'],
                    ['num' => 4, 'code' => 'INV-004', 'nombre' => 'Inventario de Aplicaciones'],
                    ['num' => 5, 'code' => 'INV-005', 'nombre' => 'Inventario de Bases de Datos'],
                    ['num' => 6, 'code' => 'INV-006', 'nombre' => 'Inventario de Usuarios y Accesos']
                ];

                foreach ($inventarios as $inv) {
                    echo '<div class="document-card">';
                    echo '<div class="document-title">';
                    echo '<span class="document-number">' . $inv['num'] . '</span>';
                    echo $inv['nombre'];
                    echo '</div>';
                    echo '<span class="document-format xlsx">XLSX</span>';
                    echo '<button class="download-btn" onclick="descargar(\'inventarios\', \'' . $inv['code'] . '\', \'xlsx\')">📥 Descargar</button>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- RIESGOS -->
        <div class="category">
            <div class="category-header">
                <div class="category-title">
                    <span class="category-icon">⚠</span>
                    <span class="category-name">Formatos - Riesgos</span>
                </div>
                <div class="category-count">6 documentos</div>
            </div>
            <div class="documents-grid">
                <?php
                $riesgos = [
                    ['num' => 1, 'code' => 'RISK-001', 'nombre' => 'Análisis de Riesgos'],
                    ['num' => 2, 'code' => 'RISK-002', 'nombre' => 'Matriz de Riesgos'],
                    ['num' => 3, 'code' => 'RISK-003', 'nombre' => 'Plan de Tratamiento de Riesgos'],
                    ['num' => 4, 'code' => 'RISK-004', 'nombre' => 'Evaluación de Riesgos por Proceso'],
                    ['num' => 5, 'code' => 'RISK-005', 'nombre' => 'Registro de Riesgos Identificados'],
                    ['num' => 6, 'code' => 'RISK-006', 'nombre' => 'Análisis de Impacto al Negocio (BIA)']
                ];

                foreach ($riesgos as $risk) {
                    echo '<div class="document-card">';
                    echo '<div class="document-title">';
                    echo '<span class="document-number">' . $risk['num'] . '</span>';
                    echo $risk['nombre'];
                    echo '</div>';
                    echo '<span class="document-format xlsx">XLSX</span>';
                    echo '<button class="download-btn" onclick="descargar(\'riesgos\', \'' . $risk['code'] . '\', \'xlsx\')">📥 Descargar</button>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Información -->
        <div class="info-box">
            <h3>ℹ️ Información Importante</h3>
            <p><strong>Todos los documentos están disponibles para descarga inmediata.</strong> Los archivos DOCX y XLSX son plantillas editables que puede personalizar según las necesidades de su organización.</p>
            <p style="margin-top: 10px;"><strong>Nota:</strong> Estos documentos están diseñados para cumplir con los requisitos de ISO 27001:2022 y deben ser revisados y aprobados por su organización antes de su implementación oficial.</p>
        </div>
    </div>

    <script>
        function descargar(categoria, codigo, formato) {
            window.location.href = 'export.php?category=' + categoria + '&code=' + codigo + '&format=' + formato;
        }
    </script>
</body>
</html>
