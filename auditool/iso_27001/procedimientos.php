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

// Catalogo completo de procedimientos ISO 27001
$procedimientos = [
    [
        'codigo' => 'PROC-SI-001',
        'titulo' => 'Control de Documentos del SGSI',
        'descripcion' => 'Define como se aprueban, revisan, actualizan y controlan los documentos del Sistema de Gestion de Seguridad de la Informacion.',
        'clausula' => 'A.5.37',
        'control_anexo' => 'A.5.37 - Procedimientos operativos documentados',
        'tipo' => 'Obligatorio',
        'categoria' => 'Documentacion'
    ],
    [
        'codigo' => 'PROC-SI-002',
        'titulo' => 'Control de Registros',
        'descripcion' => 'Establece los controles necesarios para la identificacion, almacenamiento, proteccion, recuperacion, tiempo de retencion y disposicion de registros.',
        'clausula' => 'A.5.33',
        'control_anexo' => 'A.5.33 - Proteccion de registros',
        'tipo' => 'Obligatorio',
        'categoria' => 'Documentacion'
    ],
    [
        'codigo' => 'PROC-SI-003',
        'titulo' => 'Gestion de Activos de Informacion',
        'descripcion' => 'Define la metodologia para identificar, inventariar, clasificar y gestionar los activos de informacion de la organizacion.',
        'clausula' => 'A.5.9',
        'control_anexo' => 'A.5.9 - Inventario de informacion y otros activos asociados',
        'tipo' => 'Obligatorio',
        'categoria' => 'Activos'
    ],
    [
        'codigo' => 'PROC-SI-004',
        'titulo' => 'Clasificacion y Etiquetado de Informacion',
        'descripcion' => 'Establece los niveles de clasificacion de la informacion y los procedimientos para su etiquetado y manejo.',
        'clausula' => 'A.5.12 / A.5.13',
        'control_anexo' => 'A.5.12 - Clasificacion de la informacion, A.5.13 - Etiquetado',
        'tipo' => 'Obligatorio',
        'categoria' => 'Activos'
    ],
    [
        'codigo' => 'PROC-SI-005',
        'titulo' => 'Analisis y Evaluacion de Riesgos',
        'descripcion' => 'Define la metodologia para identificar, analizar, evaluar y tratar los riesgos de seguridad de la informacion.',
        'clausula' => 'Clausula 6.1.2',
        'control_anexo' => 'Clausula 6.1.2 - Evaluacion de riesgos de seguridad de la informacion',
        'tipo' => 'Obligatorio',
        'categoria' => 'Riesgos'
    ],
    [
        'codigo' => 'PROC-SI-006',
        'titulo' => 'Tratamiento de Riesgos',
        'descripcion' => 'Establece las opciones de tratamiento de riesgos y el proceso para implementar las acciones seleccionadas.',
        'clausula' => 'Clausula 6.1.3',
        'control_anexo' => 'Clausula 6.1.3 - Tratamiento de riesgos',
        'tipo' => 'Obligatorio',
        'categoria' => 'Riesgos'
    ],
    [
        'codigo' => 'PROC-SI-007',
        'titulo' => 'Gestion de Cambios',
        'descripcion' => 'Define el proceso para gestionar cambios en sistemas, redes, aplicaciones y configuraciones de seguridad.',
        'clausula' => 'A.8.32',
        'control_anexo' => 'A.8.32 - Gestion de cambios',
        'tipo' => 'Obligatorio',
        'categoria' => 'Operaciones'
    ],
    [
        'codigo' => 'PROC-SI-008',
        'titulo' => 'Gestion de Incidentes de Seguridad',
        'descripcion' => 'Establece el proceso para identificar, reportar, evaluar, responder y aprender de incidentes de seguridad.',
        'clausula' => 'A.5.24 a A.5.28',
        'control_anexo' => 'A.5.24-28 - Gestion de incidentes',
        'tipo' => 'Obligatorio',
        'categoria' => 'Incidentes'
    ],
    [
        'codigo' => 'PROC-SI-009',
        'titulo' => 'Gestion de Vulnerabilidades Tecnicas',
        'descripcion' => 'Define el proceso para identificar, evaluar y gestionar vulnerabilidades tecnicas en sistemas y aplicaciones.',
        'clausula' => 'A.8.8',
        'control_anexo' => 'A.8.8 - Gestion de vulnerabilidades tecnicas',
        'tipo' => 'Obligatorio',
        'categoria' => 'Operaciones'
    ],
    [
        'codigo' => 'PROC-SI-010',
        'titulo' => 'Gestion de Accesos',
        'descripcion' => 'Establece el proceso para otorgar, revisar, modificar y revocar derechos de acceso a sistemas y datos.',
        'clausula' => 'A.5.15 a A.5.18',
        'control_anexo' => 'A.5.15-18 - Control de acceso',
        'tipo' => 'Obligatorio',
        'categoria' => 'Accesos'
    ],
    [
        'codigo' => 'PROC-SI-011',
        'titulo' => 'Alta, Baja y Cambio de Personal',
        'descripcion' => 'Define los controles de seguridad durante el proceso de incorporacion, cambio de rol y terminacion de empleo.',
        'clausula' => 'A.6.1 / A.6.5',
        'control_anexo' => 'A.6.1 - Seleccion, A.6.5 - Responsabilidades post-terminacion',
        'tipo' => 'Obligatorio',
        'categoria' => 'Recursos Humanos'
    ],
    [
        'codigo' => 'PROC-SI-012',
        'titulo' => 'Gestion de Proveedores y Terceros',
        'descripcion' => 'Establece los controles para seleccionar, evaluar y gestionar la seguridad en relaciones con proveedores.',
        'clausula' => 'A.5.19 a A.5.23',
        'control_anexo' => 'A.5.19-23 - Seguridad en relaciones con proveedores',
        'tipo' => 'Obligatorio',
        'categoria' => 'Terceros'
    ],
    [
        'codigo' => 'PROC-SI-013',
        'titulo' => 'Respaldo y Recuperacion de Informacion',
        'descripcion' => 'Define las politicas y procedimientos para realizar respaldos de informacion y probar su recuperacion.',
        'clausula' => 'A.8.13',
        'control_anexo' => 'A.8.13 - Respaldo de informacion',
        'tipo' => 'Obligatorio',
        'categoria' => 'Continuidad'
    ],
    [
        'codigo' => 'PROC-SI-014',
        'titulo' => 'Continuidad del Negocio',
        'descripcion' => 'Establece el marco para planificar, implementar y mantener la continuidad de operaciones ante interrupciones.',
        'clausula' => 'A.5.29 / A.5.30',
        'control_anexo' => 'A.5.29-30 - Continuidad del negocio',
        'tipo' => 'Obligatorio',
        'categoria' => 'Continuidad'
    ],
    [
        'codigo' => 'PROC-SI-015',
        'titulo' => 'Auditorias Internas del SGSI',
        'descripcion' => 'Define la planificacion, ejecucion, documentacion y seguimiento de las auditorias internas del SGSI.',
        'clausula' => 'Clausula 9.2',
        'control_anexo' => 'Clausula 9.2 - Auditoria interna',
        'tipo' => 'Obligatorio',
        'categoria' => 'Evaluacion'
    ],
    [
        'codigo' => 'PROC-SI-016',
        'titulo' => 'Revision por la Direccion',
        'descripcion' => 'Establece la frecuencia, contenido y documentacion de las revisiones del SGSI por la alta direccion.',
        'clausula' => 'Clausula 9.3',
        'control_anexo' => 'Clausula 9.3 - Revision por la direccion',
        'tipo' => 'Obligatorio',
        'categoria' => 'Evaluacion'
    ],
    [
        'codigo' => 'PROC-SI-017',
        'titulo' => 'Acciones Correctivas',
        'descripcion' => 'Define el proceso para identificar no conformidades, determinar causas raiz e implementar acciones correctivas.',
        'clausula' => 'Clausula 10.1',
        'control_anexo' => 'Clausula 10.1 - No conformidad y accion correctiva',
        'tipo' => 'Obligatorio',
        'categoria' => 'Mejora'
    ],
    [
        'codigo' => 'PROC-SI-018',
        'titulo' => 'Capacitacion y Concientizacion en Seguridad',
        'descripcion' => 'Establece el programa de capacitacion y concientizacion para todo el personal en temas de seguridad.',
        'clausula' => 'A.6.3',
        'control_anexo' => 'A.6.3 - Conciencia, educacion y capacitacion',
        'tipo' => 'Recomendado',
        'categoria' => 'Recursos Humanos'
    ],
    [
        'codigo' => 'PROC-SI-019',
        'titulo' => 'Desarrollo Seguro de Software',
        'descripcion' => 'Define los requisitos de seguridad y buenas practicas para el ciclo de vida de desarrollo de software.',
        'clausula' => 'A.8.25 a A.8.31',
        'control_anexo' => 'A.8.25-31 - Ciclo de vida de desarrollo seguro',
        'tipo' => 'Recomendado',
        'categoria' => 'Desarrollo'
    ],
    [
        'codigo' => 'PROC-SI-020',
        'titulo' => 'Uso de Criptografia',
        'descripcion' => 'Establece las politicas y procedimientos para el uso de controles criptograficos.',
        'clausula' => 'A.8.24',
        'control_anexo' => 'A.8.24 - Uso de criptografia',
        'tipo' => 'Recomendado',
        'categoria' => 'Controles Tecnicos'
    ],
    [
        'codigo' => 'PROC-SI-021',
        'titulo' => 'Proteccion contra Malware',
        'descripcion' => 'Define las medidas de proteccion contra software malicioso y los procedimientos de respuesta.',
        'clausula' => 'A.8.7',
        'control_anexo' => 'A.8.7 - Proteccion contra malware',
        'tipo' => 'Recomendado',
        'categoria' => 'Controles Tecnicos'
    ],
    [
        'codigo' => 'PROC-SI-022',
        'titulo' => 'Monitoreo y Registro de Eventos',
        'descripcion' => 'Establece los requisitos para el registro, monitoreo y analisis de eventos de seguridad.',
        'clausula' => 'A.8.15 / A.8.16',
        'control_anexo' => 'A.8.15-16 - Registro y monitoreo',
        'tipo' => 'Recomendado',
        'categoria' => 'Operaciones'
    ],
    [
        'codigo' => 'PROC-SI-023',
        'titulo' => 'Gestion de Configuracion',
        'descripcion' => 'Define el proceso para gestionar y controlar las configuraciones de seguridad de sistemas.',
        'clausula' => 'A.8.9',
        'control_anexo' => 'A.8.9 - Gestion de configuracion',
        'tipo' => 'Recomendado',
        'categoria' => 'Operaciones'
    ],
    [
        'codigo' => 'PROC-SI-024',
        'titulo' => 'Transferencia Segura de Informacion',
        'descripcion' => 'Establece los controles para la transferencia segura de informacion por diferentes medios.',
        'clausula' => 'A.5.14',
        'control_anexo' => 'A.5.14 - Transferencia de informacion',
        'tipo' => 'Recomendado',
        'categoria' => 'Operaciones'
    ],
    [
        'codigo' => 'PROC-SI-025',
        'titulo' => 'Cumplimiento Legal y Regulatorio',
        'descripcion' => 'Define el proceso para identificar, documentar y cumplir con requisitos legales y regulatorios.',
        'clausula' => 'A.5.31',
        'control_anexo' => 'A.5.31 - Requisitos legales y regulatorios',
        'tipo' => 'Recomendado',
        'categoria' => 'Cumplimiento'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procedimientos del SGSI - ISO 27001 - AUDITOR PRO</title>
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
            color: #0066cc;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 16px;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #0066cc;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        .info-box h3 {
            color: #0066cc;
            margin-bottom: 10px;
        }
        .info-box p {
            color: #555;
            line-height: 1.6;
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
            background: #0066cc;
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
        .badge-obligatorio {
            background: #ff4444;
            color: white;
        }
        .badge-recomendado {
            background: #ffaa00;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn btn-back">← Volver al Dashboard ISO 27001</a>

        <div class="header">
            <h1>📋 Procedimientos del Sistema de Gestion de Seguridad de la Informacion</h1>
            <p>Plantillas de procedimientos necesarios para ISO 27001:2022</p>
        </div>

        <div class="info-box">
            <h3>Sobre los Procedimientos</h3>
            <p>ISO 27001:2022 requiere que la organizacion determine y gestione los procesos necesarios para el SGSI. Los procedimientos marcados como <strong>"Obligatorios"</strong> son esenciales para asegurar el cumplimiento efectivo de la norma y sus controles del Anexo A.</p>
            <p style="margin-top: 10px;">Todos los procedimientos estan disenados para ser personalizables segun el contexto y necesidades de su organizacion.</p>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Titulo</th>
                        <th>Descripcion</th>
                        <th>Clausula/Control</th>
                        <th>Tipo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($procedimientos as $proc): ?>
                    <tr>
                        <td><strong><?php echo $proc['codigo']; ?></strong></td>
                        <td><strong><?php echo $proc['titulo']; ?></strong></td>
                        <td><?php echo $proc['descripcion']; ?></td>
                        <td style="font-size: 11px; color: #666;"><?php echo $proc['control_anexo']; ?></td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($proc['tipo']); ?>">
                                <?php echo $proc['tipo']; ?>
                            </span>
                        </td>
                        <td>
                            <a href="export.php?format=docx&type=procedimiento&code=<?php echo $proc['codigo']; ?>" class="btn btn-download">
                                📥 Descargar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="info-box" style="margin-top: 30px;">
            <h3>Estructura Recomendada de un Procedimiento</h3>
            <ul style="margin-left: 20px; margin-top: 10px; line-height: 2;">
                <li><strong>Objetivo:</strong> Proposito del procedimiento</li>
                <li><strong>Alcance:</strong> Areas, procesos o actividades cubiertas</li>
                <li><strong>Referencias:</strong> Documentos relacionados (normas, politicas, etc.)</li>
                <li><strong>Definiciones:</strong> Terminos clave utilizados</li>
                <li><strong>Responsabilidades:</strong> Roles y responsabilidades</li>
                <li><strong>Descripcion de Actividades:</strong> Paso a paso del proceso</li>
                <li><strong>Registros:</strong> Documentos generados</li>
                <li><strong>Anexos:</strong> Formatos, diagramas de flujo, etc.</li>
            </ul>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
