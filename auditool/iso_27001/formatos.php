<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'conectae_isogestionuser');
define('DB_PASS', 'pt125824caraud');
define('DB_NAME', 'conectae_isogestionbd');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Error de conexion: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// Catalogo completo de formatos ISO 27001
$formatos = [
    ['codigo' => 'FO-SI-001', 'titulo' => 'Matriz de Requisitos ISO 27001', 'descripcion' => 'Matriz completa con todos los requisitos de ISO 27001:2022 y su estado de implementacion.', 'categoria' => 'Diagnostico', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-002', 'titulo' => 'Plan de Implementacion ISO 27001', 'descripcion' => 'Cronograma detallado para la implementacion del Sistema de Gestion de Seguridad de la Informacion.', 'categoria' => 'Planificacion', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-003', 'titulo' => 'Inventario de Activos de Informacion', 'descripcion' => 'Plantilla para inventariar y clasificar todos los activos de informacion de la organizacion.', 'categoria' => 'Activos', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-004', 'titulo' => 'Matriz de Riesgos', 'descripcion' => 'Herramienta para identificar, evaluar y priorizar riesgos de seguridad de la informacion.', 'categoria' => 'Riesgos', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-005', 'titulo' => 'Plan de Tratamiento de Riesgos', 'descripcion' => 'Documento para planificar las acciones de tratamiento de riesgos identificados.', 'categoria' => 'Riesgos', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-006', 'titulo' => 'Declaracion de Aplicabilidad (SOA)', 'descripcion' => 'Documento que indica que controles del Anexo A son aplicables y cuales no, con justificacion.', 'categoria' => 'Controles', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-007', 'titulo' => 'Matriz de Controles Anexo A', 'descripcion' => 'Plantilla con los 93 controles de ISO 27001:2022 para evaluar su implementacion.', 'categoria' => 'Controles', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-008', 'titulo' => 'Registro de Incidentes de Seguridad', 'descripcion' => 'Formato para documentar y hacer seguimiento a incidentes de seguridad.', 'categoria' => 'Incidentes', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-009', 'titulo' => 'Plan de Respuesta a Incidentes', 'descripcion' => 'Documento que define roles, responsabilidades y pasos para responder a incidentes.', 'categoria' => 'Incidentes', 'tipo' => 'Word'],
    ['codigo' => 'FO-SI-010', 'titulo' => 'Informe de Incidente de Seguridad', 'descripcion' => 'Plantilla para reportar incidentes de seguridad con todos los detalles relevantes.', 'categoria' => 'Incidentes', 'tipo' => 'Word'],
    ['codigo' => 'FO-SI-011', 'titulo' => 'Checklist de Auditoria Interna ISO 27001', 'descripcion' => 'Lista de verificacion completa para realizar auditorias internas del SGSI.', 'categoria' => 'Auditoria', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-012', 'titulo' => 'Plan Anual de Auditorias', 'descripcion' => 'Planificacion de auditorias internas del SGSI para el ano.', 'categoria' => 'Auditoria', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-013', 'titulo' => 'Informe de Auditoria Interna', 'descripcion' => 'Formato para documentar los resultados de las auditorias internas.', 'categoria' => 'Auditoria', 'tipo' => 'Word'],
    ['codigo' => 'FO-SI-014', 'titulo' => 'Registro de No Conformidades', 'descripcion' => 'Formato para documentar no conformidades identificadas en auditorias o procesos.', 'categoria' => 'Mejora', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-015', 'titulo' => 'Plan de Acciones Correctivas', 'descripcion' => 'Plantilla para planificar e implementar acciones correctivas.', 'categoria' => 'Mejora', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-016', 'titulo' => 'Acta de Revision por la Direccion', 'descripcion' => 'Formato para documentar las revisiones del SGSI por la alta direccion.', 'categoria' => 'Direccion', 'tipo' => 'Word'],
    ['codigo' => 'FO-SI-017', 'titulo' => 'Indicadores de Seguridad (KPI)', 'descripcion' => 'Plantilla para definir y hacer seguimiento a indicadores clave del SGSI.', 'categoria' => 'Medicion', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-018', 'titulo' => 'Dashboard de Seguridad', 'descripcion' => 'Panel de control visual con metricas e indicadores del SGSI.', 'categoria' => 'Medicion', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-019', 'titulo' => 'Matriz de Usuarios y Accesos', 'descripcion' => 'Control de usuarios, sus roles y permisos de acceso a sistemas.', 'categoria' => 'Accesos', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-020', 'titulo' => 'Solicitud de Acceso a Sistemas', 'descripcion' => 'Formato para solicitar y aprobar accesos a sistemas de informacion.', 'categoria' => 'Accesos', 'tipo' => 'Word'],
    ['codigo' => 'FO-SI-021', 'titulo' => 'Formulario de Alta de Personal', 'descripcion' => 'Checklist de actividades de seguridad al incorporar nuevo personal.', 'categoria' => 'RRHH', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-022', 'titulo' => 'Formulario de Baja de Personal', 'descripcion' => 'Checklist de actividades de seguridad al terminar contrato de personal.', 'categoria' => 'RRHH', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-023', 'titulo' => 'Acuerdo de Confidencialidad (NDA)', 'descripcion' => 'Plantilla de acuerdo de confidencialidad para empleados y terceros.', 'categoria' => 'RRHH', 'tipo' => 'Word'],
    ['codigo' => 'FO-SI-024', 'titulo' => 'Plan de Capacitacion en Seguridad', 'descripcion' => 'Programacion anual de capacitaciones en seguridad de la informacion.', 'categoria' => 'RRHH', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-025', 'titulo' => 'Registro de Asistencia a Capacitaciones', 'descripcion' => 'Control de asistencia y evaluacion de capacitaciones.', 'categoria' => 'RRHH', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-026', 'titulo' => 'Evaluacion de Proveedores', 'descripcion' => 'Criterios y registro de evaluacion de proveedores de servicios TI.', 'categoria' => 'Proveedores', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-027', 'titulo' => 'Cuestionario de Seguridad para Proveedores', 'descripcion' => 'Cuestionario para evaluar la seguridad de proveedores antes de contratarlos.', 'categoria' => 'Proveedores', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-028', 'titulo' => 'Acuerdo de Nivel de Servicio (SLA)', 'descripcion' => 'Plantilla de SLA con clausulas de seguridad para proveedores.', 'categoria' => 'Proveedores', 'tipo' => 'Word'],
    ['codigo' => 'FO-SI-029', 'titulo' => 'Plan de Continuidad del Negocio', 'descripcion' => 'Documento maestro del plan de continuidad y recuperacion.', 'categoria' => 'Continuidad', 'tipo' => 'Word'],
    ['codigo' => 'FO-SI-030', 'titulo' => 'Analisis de Impacto al Negocio (BIA)', 'descripcion' => 'Herramienta para analizar el impacto de interrupciones en procesos criticos.', 'categoria' => 'Continuidad', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-031', 'titulo' => 'Registro de Respaldos', 'descripcion' => 'Control de respaldos de informacion realizados y su verificacion.', 'categoria' => 'Continuidad', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-032', 'titulo' => 'Prueba de Continuidad', 'descripcion' => 'Formato para documentar pruebas del plan de continuidad.', 'categoria' => 'Continuidad', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-033', 'titulo' => 'Registro de Vulnerabilidades', 'descripcion' => 'Control de vulnerabilidades identificadas y su tratamiento.', 'categoria' => 'Operaciones', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-034', 'titulo' => 'Registro de Cambios', 'descripcion' => 'Control de cambios en sistemas, redes y configuraciones.', 'categoria' => 'Operaciones', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-035', 'titulo' => 'Listado Maestro de Documentos', 'descripcion' => 'Control de todos los documentos del Sistema de Gestion de Seguridad.', 'categoria' => 'Documentacion', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-036', 'titulo' => 'Contexto de la Organizacion', 'descripcion' => 'Analisis de cuestiones internas y externas que afectan el SGSI.', 'categoria' => 'Planificacion', 'tipo' => 'Word'],
    ['codigo' => 'FO-SI-037', 'titulo' => 'Partes Interesadas y Requisitos', 'descripcion' => 'Identificacion de partes interesadas y sus necesidades de seguridad.', 'categoria' => 'Planificacion', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-038', 'titulo' => 'Objetivos de Seguridad', 'descripcion' => 'Definicion y seguimiento de objetivos de seguridad del SGSI.', 'categoria' => 'Planificacion', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-039', 'titulo' => 'Matriz de Roles y Responsabilidades', 'descripcion' => 'Definicion de roles y responsabilidades en el SGSI.', 'categoria' => 'Organizacion', 'tipo' => 'Excel'],
    ['codigo' => 'FO-SI-040', 'titulo' => 'Manual del SGSI', 'descripcion' => 'Documento maestro que describe el Sistema de Gestion de Seguridad.', 'categoria' => 'Documentacion', 'tipo' => 'Word']
];

$total_formatos = count($formatos);
$formatos_excel = count(array_filter($formatos, fn($f) => $f['tipo'] == 'Excel'));
$formatos_word = count(array_filter($formatos, fn($f) => $f['tipo'] == 'Word'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formatos y Plantillas - ISO 27001 - AUDITOR PRO</title>
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
        }
        .header h1 {
            font-size: 32px;
            color: #00994d;
            margin-bottom: 10px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #00994d;
        }
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #333;
        }
        .stat-label {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #00994d;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-excel {
            background: #217346;
            color: white;
        }
        .badge-word {
            background: #2b5797;
            color: white;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-download {
            background: #00994d;
            color: white;
        }
        .btn-back {
            background: #666;
            color: white;
            margin-bottom: 20px;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .info-box {
            background: #d4edda;
            border-left: 4px solid #00994d;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        .info-box h3 {
            color: #00994d;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn btn-back">← Volver al Dashboard ISO 27001</a>

        <div class="header">
            <h1>📊 Formatos y Plantillas</h1>
            <p>Descarga formatos listos para usar en tu Sistema de Gestion de Seguridad de la Informacion.</p>

            <div class="stats">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $total_formatos; ?></div>
                    <div class="stat-label">Formatos Disponibles</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $formatos_excel; ?></div>
                    <div class="stat-label">Formatos Excel</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $formatos_word; ?></div>
                    <div class="stat-label">Formatos Word</div>
                </div>
            </div>
        </div>

        <div class="info-box">
            <h3>Sobre los Formatos</h3>
            <p>Estos formatos estan disenados para facilitar la implementacion y mantenimiento de tu Sistema de Gestion de Seguridad de la Informacion segun ISO 27001:2022. Son plantillas editables que puedes personalizar segun las necesidades de tu organizacion.</p>
            <p style="margin-top: 10px;"><strong>Recomendacion:</strong> Revisa cada formato antes de implementarlo para asegurar que se ajusta al contexto de tu organizacion.</p>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Titulo</th>
                        <th>Descripcion</th>
                        <th>Categoria</th>
                        <th>Tipo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($formatos as $formato): ?>
                    <tr>
                        <td><strong><?php echo $formato['codigo']; ?></strong></td>
                        <td><strong><?php echo $formato['titulo']; ?></strong></td>
                        <td><?php echo $formato['descripcion']; ?></td>
                        <td><?php echo $formato['categoria']; ?></td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($formato['tipo']); ?>">
                                <?php echo $formato['tipo'] == 'Excel' ? '📊 Excel' : '📄 Word'; ?>
                            </span>
                        </td>
                        <td>
                            <a href="export.php?format=<?php echo strtolower($formato['tipo']); ?>&type=formato&code=<?php echo $formato['codigo']; ?>" class="btn btn-download">
                                📥 Descargar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
