<?php
session_start();
require_once('../config/config.php');

$conn = getDBConnection();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y Exportación - ISO 27001 - AUDITOR PRO</title>
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
            color: #0066cc;
            margin-bottom: 10px;
        }
        .header p { color: #666; font-size: 16px; }
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .report-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 6px solid;
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .report-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .report-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .report-description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .report-features {
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
        .feature-item:last-child { margin-bottom: 0; }
        .export-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            text-align: center;
            display: inline-block;
        }
        .btn-excel {
            background: #00994d;
            color: white;
        }
        .btn-word {
            background: #0066cc;
            color: white;
        }
        .btn-pdf {
            background: #cc0000;
            color: white;
        }
        .btn-secondary {
            background: #666;
            color: white;
            margin-bottom: 20px;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .card-activos { border-left-color: #00994d; }
        .card-riesgos { border-left-color: #cc0000; }
        .card-controles { border-left-color: #9900cc; }
        .card-soa { border-left-color: #0066cc; }
        .card-incidentes { border-left-color: #ff6600; }
        .card-ejecutivo { border-left-color: #cc00cc; }
        .card-cumplimiento { border-left-color: #00cc99; }
        .card-tratamiento { border-left-color: #ff9900; }
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
            <h1>📊 Reportes y Exportación</h1>
            <p>Genere reportes personalizados del SGSI en múltiples formatos</p>
        </div>

        <div class="reports-grid">
            <!-- Reporte de Activos -->
            <div class="report-card card-activos">
                <div class="report-icon">🗂️</div>
                <div class="report-title">Inventario de Activos</div>
                <div class="report-description">
                    Reporte completo del inventario de activos de información con clasificación CIA y responsables.
                </div>
                <div class="report-features">
                    <div class="feature-item">✓ Clasificación por tipo y categoría</div>
                    <div class="feature-item">✓ Valoración CIA detallada</div>
                    <div class="feature-item">✓ Propietarios y custodios</div>
                    <div class="feature-item">✓ Nivel de criticidad</div>
                </div>
                <div class="export-buttons">
                    <a href="export.php?type=activos&format=excel" class="btn btn-excel">📊 Excel</a>
                    <a href="export.php?type=activos&format=word" class="btn btn-word">📄 Word</a>
                </div>
            </div>

            <!-- Reporte de Riesgos -->
            <div class="report-card card-riesgos">
                <div class="report-icon">⚠️</div>
                <div class="report-title">Registro de Riesgos</div>
                <div class="report-description">
                    Análisis completo de riesgos con matriz 5×5, tratamiento y riesgo residual.
                </div>
                <div class="report-features">
                    <div class="feature-item">✓ Amenazas y vulnerabilidades</div>
                    <div class="feature-item">✓ Análisis probabilidad/impacto</div>
                    <div class="feature-item">✓ Plan de tratamiento</div>
                    <div class="feature-item">✓ Riesgo inherente y residual</div>
                </div>
                <div class="export-buttons">
                    <a href="export.php?type=riesgos&format=excel" class="btn btn-excel">📊 Excel</a>
                    <a href="export.php?type=riesgos&format=word" class="btn btn-word">📄 Word</a>
                </div>
            </div>

            <!-- SOA - Statement of Applicability -->
            <div class="report-card card-soa">
                <div class="report-icon">📋</div>
                <div class="report-title">SOA - Declaración de Aplicabilidad</div>
                <div class="report-description">
                    Statement of Applicability completo con los 93 controles del Anexo A de ISO 27001:2022.
                </div>
                <div class="report-features">
                    <div class="feature-item">✓ 93 controles Anexo A</div>
                    <div class="feature-item">✓ Estado de implementación</div>
                    <div class="feature-item">✓ Justificaciones</div>
                    <div class="feature-item">✓ Evidencias documentadas</div>
                </div>
                <div class="export-buttons">
                    <a href="matrices.php?export=true&type=soa" class="btn btn-excel">📊 Excel</a>
                    <a href="export.php?type=soa&format=word" class="btn btn-word">📄 Word</a>
                </div>
            </div>

            <!-- Reporte de Controles -->
            <div class="report-card card-controles">
                <div class="report-icon">⚙️</div>
                <div class="report-title">Evaluación de Controles</div>
                <div class="report-description">
                    Reporte de evaluación de controles de seguridad con efectividad y recomendaciones.
                </div>
                <div class="report-features">
                    <div class="feature-item">✓ Estado de implementación</div>
                    <div class="feature-item">✓ Porcentaje de efectividad</div>
                    <div class="feature-item">✓ Responsables asignados</div>
                    <div class="feature-item">✓ Observaciones y mejoras</div>
                </div>
                <div class="export-buttons">
                    <a href="export.php?type=controles&format=excel" class="btn btn-excel">📊 Excel</a>
                    <a href="export.php?type=controles&format=word" class="btn btn-word">📄 Word</a>
                </div>
            </div>

            <!-- Reporte de Incidentes -->
            <div class="report-card card-incidentes">
                <div class="report-icon">🚨</div>
                <div class="report-title">Incidentes de Seguridad</div>
                <div class="report-description">
                    Registro de incidentes con análisis de causas, acciones tomadas y lecciones aprendidas.
                </div>
                <div class="report-features">
                    <div class="feature-item">✓ Clasificación por severidad</div>
                    <div class="feature-item">✓ Tiempos de respuesta</div>
                    <div class="feature-item">✓ Acciones correctivas</div>
                    <div class="feature-item">✓ Lecciones aprendidas</div>
                </div>
                <div class="export-buttons">
                    <a href="export.php?type=incidentes&format=excel" class="btn btn-excel">📊 Excel</a>
                    <a href="export.php?type=incidentes&format=word" class="btn btn-word">📄 Word</a>
                </div>
            </div>

            <!-- Reporte Ejecutivo -->
            <div class="report-card card-ejecutivo">
                <div class="report-icon">📈</div>
                <div class="report-title">Informe Ejecutivo</div>
                <div class="report-description">
                    Dashboard ejecutivo con KPIs, nivel de madurez del SGSI y resumen de indicadores clave.
                </div>
                <div class="report-features">
                    <div class="feature-item">✓ KPIs del SGSI</div>
                    <div class="feature-item">✓ Nivel de madurez</div>
                    <div class="feature-item">✓ Gráficos de análisis</div>
                    <div class="feature-item">✓ Recomendaciones</div>
                </div>
                <div class="export-buttons">
                    <a href="export.php?type=informe_ejecutivo&format=word" class="btn btn-word">📄 Word</a>
                    <a href="resumen.php" class="btn btn-pdf" target="_blank">👁️ Ver Online</a>
                </div>
            </div>

            <!-- Reporte de Cumplimiento -->
            <div class="report-card card-cumplimiento">
                <div class="report-icon">✅</div>
                <div class="report-title">Cumplimiento Normativo</div>
                <div class="report-description">
                    Evaluación del cumplimiento de requisitos legales, regulatorios y contractuales.
                </div>
                <div class="report-features">
                    <div class="feature-item">✓ Requisitos identificados</div>
                    <div class="feature-item">✓ Estado de cumplimiento</div>
                    <div class="feature-item">✓ Evidencias documentadas</div>
                    <div class="feature-item">✓ Brechas y acciones</div>
                </div>
                <div class="export-buttons">
                    <a href="matrices.php?export=true&type=requisitos" class="btn btn-excel">📊 Excel</a>
                    <a href="export.php?type=cumplimiento&format=word" class="btn btn-word">📄 Word</a>
                </div>
            </div>

            <!-- Plan de Tratamiento -->
            <div class="report-card card-tratamiento">
                <div class="report-icon">🛡️</div>
                <div class="report-title">Plan de Tratamiento</div>
                <div class="report-description">
                    Plan consolidado de tratamiento de riesgos con estrategias, responsables y plazos.
                </div>
                <div class="report-features">
                    <div class="feature-item">✓ Opciones de tratamiento</div>
                    <div class="feature-item">✓ Acciones específicas</div>
                    <div class="feature-item">✓ Cronograma de implementación</div>
                    <div class="feature-item">✓ Seguimiento de avances</div>
                </div>
                <div class="export-buttons">
                    <a href="export.php?type=plan_tratamiento&format=excel" class="btn btn-excel">📊 Excel</a>
                    <a href="export.php?type=plan_tratamiento&format=word" class="btn btn-word">📄 Word</a>
                </div>
            </div>
        </div>

        <div class="info-box">
            <h3>ℹ️ Información sobre Reportes</h3>
            <ul>
                <li><strong>Formatos Disponibles:</strong> Los reportes se generan en Excel (.xls) y Word (.doc) según el tipo</li>
                <li><strong>Datos en Tiempo Real:</strong> Todos los reportes contienen datos actuales de la base de datos SQL</li>
                <li><strong>Uso en Auditorías:</strong> Estos reportes sirven como evidencia objetiva para auditorías ISO 27001</li>
                <li><strong>Personalización:</strong> Los reportes incluyen logo, fechas y datos de la empresa automáticamente</li>
                <li><strong>Matrices SGSI:</strong> Las matrices SOA, Riesgos y Activos también están disponibles en el módulo de Matrices</li>
                <li><strong>Exportación Masiva:</strong> Use el módulo de Matrices para exportar datos completos en formato Excel</li>
            </ul>

            <h3 style="margin-top: 25px;">📋 Otros Reportes Disponibles</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                    <strong>Políticas de Seguridad</strong><br>
                    <small>22 políticas en formato Word</small><br>
                    <a href="politicas.php" class="btn btn-word" style="margin-top: 10px; font-size: 12px;">Ver Políticas</a>
                </div>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                    <strong>Procedimientos</strong><br>
                    <small>25 procedimientos documentados</small><br>
                    <a href="procedimientos.php" class="btn btn-word" style="margin-top: 10px; font-size: 12px;">Ver Procedimientos</a>
                </div>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                    <strong>Formatos y Plantillas</strong><br>
                    <small>40 formatos en Excel/Word</small><br>
                    <a href="formatos.php" class="btn btn-excel" style="margin-top: 10px; font-size: 12px;">Ver Formatos</a>
                </div>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                    <strong>Documentos del SGSI</strong><br>
                    <small>80+ documentos categorizados</small><br>
                    <a href="documentos.php" class="btn btn-word" style="margin-top: 10px; font-size: 12px;">Ver Documentos</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
