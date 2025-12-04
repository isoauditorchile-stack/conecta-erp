<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formularios Online - ISO 27001 - AUDITOR PRO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
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
        .forms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 6px solid;
        }
        .form-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .form-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .form-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .form-description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .form-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #00994d;
        }
        .stat-label {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
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
        .card-activos { border-left-color: #00994d; }
        .card-riesgos { border-left-color: #cc0000; }
        .card-incidentes { border-left-color: #cc3333; }
        .card-controles { border-left-color: #9900cc; }
        .card-nc { border-left-color: #ff6600; }
    </style>
</head>
<body>
    <div class="container">
        <a href="../index.php" class="btn btn-secondary">← Volver al Dashboard ISO 27001</a>

        <div class="header">
            <h1>📝 Formularios Online ISO 27001</h1>
            <p>Complete y envíe formularios directamente al sistema. Todos los datos se guardan en la base de datos SQL.</p>
        </div>

        <div class="forms-grid">
            <!-- Formulario Activos -->
            <div class="form-card card-activos">
                <div class="form-icon">🗂️</div>
                <div class="form-title">Inventario de Activos</div>
                <div class="form-description">
                    Registre activos de información con clasificación CIA, responsables y detalles técnicos.
                </div>
                <div class="form-stats">
                    <div class="stat-item">
                        <div class="stat-value">17</div>
                        <div class="stat-label">Campos</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">CIA</div>
                        <div class="stat-label">Clasificación</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">SQL</div>
                        <div class="stat-label">Guardado</div>
                    </div>
                </div>
                <a href="formulario-activos.php" class="btn btn-primary" style="width: 100%;">
                    ✏️ Llenar Formulario
                </a>
            </div>

            <!-- Formulario Riesgos -->
            <div class="form-card card-riesgos">
                <div class="form-icon">⚠️</div>
                <div class="form-title">Análisis de Riesgos</div>
                <div class="form-description">
                    Identifique y evalúe riesgos con matriz 5x5, cálculo automático de nivel y plan de tratamiento.
                </div>
                <div class="form-stats">
                    <div class="stat-item">
                        <div class="stat-value">5×5</div>
                        <div class="stat-label">Matriz</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">Auto</div>
                        <div class="stat-label">Cálculo</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">SQL</div>
                        <div class="stat-label">Guardado</div>
                    </div>
                </div>
                <a href="formulario-riesgos.php" class="btn btn-primary" style="width: 100%;">
                    ✏️ Llenar Formulario
                </a>
            </div>

            <!-- Formulario Incidentes -->
            <div class="form-card card-incidentes">
                <div class="form-icon">🚨</div>
                <div class="form-title">Incidentes de Seguridad</div>
                <div class="form-description">
                    Reporte incidentes de seguridad con clasificación, acciones de contención y lecciones aprendidas.
                </div>
                <div class="form-stats">
                    <div class="stat-item">
                        <div class="stat-value">16</div>
                        <div class="stat-label">Campos</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">4</div>
                        <div class="stat-label">Severidades</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">SQL</div>
                        <div class="stat-label">Guardado</div>
                    </div>
                </div>
                <a href="formulario-incidentes.php" class="btn btn-primary" style="width: 100%;">
                    ✏️ Llenar Formulario
                </a>
            </div>

            <!-- Formulario Controles -->
            <div class="form-card card-controles">
                <div class="form-icon">⚙️</div>
                <div class="form-title">Evaluación de Controles</div>
                <div class="form-description">
                    Evalúe controles del Anexo A con estado de implementación, efectividad y evidencias.
                </div>
                <div class="form-stats">
                    <div class="stat-item">
                        <div class="stat-value">93</div>
                        <div class="stat-label">Controles</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">%</div>
                        <div class="stat-label">Seguimiento</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">SQL</div>
                        <div class="stat-label">Guardado</div>
                    </div>
                </div>
                <a href="formulario-controles.php" class="btn btn-primary" style="width: 100%;">
                    ✏️ Llenar Formulario
                </a>
            </div>

            <!-- Formulario No Conformidades -->
            <div class="form-card card-nc">
                <div class="form-icon">⚠️</div>
                <div class="form-title">No Conformidades</div>
                <div class="form-description">
                    Registre no conformidades con análisis de causa raíz, acciones correctivas y seguimiento.
                </div>
                <div class="form-stats">
                    <div class="stat-item">
                        <div class="stat-value">3</div>
                        <div class="stat-label">Tipos</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">RCA</div>
                        <div class="stat-label">Análisis</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">SQL</div>
                        <div class="stat-label">Guardado</div>
                    </div>
                </div>
                <a href="formulario-no-conformidades.php" class="btn btn-primary" style="width: 100%;">
                    ✏️ Llenar Formulario
                </a>
            </div>
        </div>

        <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
            <h3 style="color: #333; margin-bottom: 15px;">ℹ️ Información</h3>
            <ul style="color: #666; line-height: 1.8; margin-left: 20px;">
                <li><strong>Formularios Online:</strong> Complete y envíe datos directamente desde su navegador</li>
                <li><strong>Guardado en SQL:</strong> Todos los datos se almacenan en la base de datos en tiempo real</li>
                <li><strong>Validación:</strong> Campos obligatorios marcados con asterisco (*)</li>
                <li><strong>Cálculos Automáticos:</strong> Los formularios de riesgos calculan automáticamente niveles</li>
                <li><strong>Exportable:</strong> Los datos ingresados pueden exportarse a Excel/Word desde los módulos principales</li>
            </ul>
        </div>
    </div>
</body>
</html>
