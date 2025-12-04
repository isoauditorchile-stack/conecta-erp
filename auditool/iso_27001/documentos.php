<?php
session_start();

// Configuracion de base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'conectae_isogestionuser');
define('DB_PASS', 'pt125824caraud');
define('DB_NAME', 'conectae_isogestionbd');

// Conexion a base de datos
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Error de conexion: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Variables de sesion
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;
$language = isset($_SESSION['language']) ? $_SESSION['language'] : 'es';

// Obtener documentos de la base de datos
$stmt = $conn->prepare("SELECT * FROM iso27001_documents WHERE company_id = ? ORDER BY document_category, document_type, document_name");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$documents_result = $stmt->get_result();
$stmt->close();

// Obtener estadisticas
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_documents WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$total_documents = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_documents WHERE company_id = ? AND approval_status = 'aprobado'");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$approved_documents = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Catalogo de documentos y formatos por categoria
$document_catalog = [
    'Politicas' => [
        'icon' => '&#128220;',
        'color' => '#0066cc',
        'documents' => [
            ['name' => 'Politica de Seguridad de la Informacion', 'file' => 'POL-SI-001-Politica-Seguridad-Informacion.docx', 'type' => 'DOCX'],
            ['name' => 'Politica de Control de Acceso', 'file' => 'POL-SI-002-Politica-Control-Acceso.docx', 'type' => 'DOCX'],
            ['name' => 'Politica de Clasificacion de la Informacion', 'file' => 'POL-SI-003-Politica-Clasificacion-Informacion.docx', 'type' => 'DOCX'],
            ['name' => 'Politica de Uso Aceptable', 'file' => 'POL-SI-004-Politica-Uso-Aceptable.docx', 'type' => 'DOCX'],
            ['name' => 'Politica de Escritorio Limpio y Pantalla Limpia', 'file' => 'POL-SI-005-Politica-Escritorio-Pantalla-Limpia.docx', 'type' => 'DOCX'],
            ['name' => 'Politica de Transferencia de Informacion', 'file' => 'POL-SI-006-Politica-Transferencia-Informacion.docx', 'type' => 'DOCX'],
            ['name' => 'Politica de Gestion de Contrasenas', 'file' => 'POL-SI-007-Politica-Gestion-Contrasenas.docx', 'type' => 'DOCX'],
            ['name' => 'Politica de Criptografia', 'file' => 'POL-SI-008-Politica-Criptografia.docx', 'type' => 'DOCX'],
            ['name' => 'Politica de Continuidad del Negocio', 'file' => 'POL-SI-009-Politica-Continuidad-Negocio.docx', 'type' => 'DOCX'],
            ['name' => 'Politica de Respaldo y Recuperacion', 'file' => 'POL-SI-010-Politica-Respaldo-Recuperacion.docx', 'type' => 'DOCX']
        ]
    ],
    'Procedimientos' => [
        'icon' => '&#128196;',
        'color' => '#00994d',
        'documents' => [
            ['name' => 'Procedimiento de Gestion de Cambios', 'file' => 'PROC-SI-001-Gestion-Cambios.docx', 'type' => 'DOCX'],
            ['name' => 'Procedimiento de Gestion de Incidentes', 'file' => 'PROC-SI-002-Gestion-Incidentes.docx', 'type' => 'DOCX'],
            ['name' => 'Procedimiento de Gestion de Vulnerabilidades', 'file' => 'PROC-SI-003-Gestion-Vulnerabilidades.docx', 'type' => 'DOCX'],
            ['name' => 'Procedimiento de Auditorias Internas', 'file' => 'PROC-SI-004-Auditorias-Internas.docx', 'type' => 'DOCX'],
            ['name' => 'Procedimiento de Revision por la Direccion', 'file' => 'PROC-SI-005-Revision-Direccion.docx', 'type' => 'DOCX'],
            ['name' => 'Procedimiento de Control de Documentos', 'file' => 'PROC-SI-006-Control-Documentos.docx', 'type' => 'DOCX'],
            ['name' => 'Procedimiento de Control de Registros', 'file' => 'PROC-SI-007-Control-Registros.docx', 'type' => 'DOCX'],
            ['name' => 'Procedimiento de Gestion de Activos', 'file' => 'PROC-SI-008-Gestion-Activos.docx', 'type' => 'DOCX'],
            ['name' => 'Procedimiento de Alta y Baja de Personal', 'file' => 'PROC-SI-009-Alta-Baja-Personal.docx', 'type' => 'DOCX'],
            ['name' => 'Procedimiento de Gestion de Accesos', 'file' => 'PROC-SI-010-Gestion-Accesos.docx', 'type' => 'DOCX']
        ]
    ],
    'Formatos_Inventarios' => [
        'icon' => '&#128202;',
        'color' => '#ff6600',
        'documents' => [
            ['name' => 'Inventario de Activos de Informacion', 'file' => 'FOR-SI-001-Inventario-Activos.xlsx', 'type' => 'XLSX'],
            ['name' => 'Inventario de Hardware', 'file' => 'FOR-SI-002-Inventario-Hardware.xlsx', 'type' => 'XLSX'],
            ['name' => 'Inventario de Software', 'file' => 'FOR-SI-003-Inventario-Software.xlsx', 'type' => 'XLSX'],
            ['name' => 'Inventario de Aplicaciones', 'file' => 'FOR-SI-004-Inventario-Aplicaciones.xlsx', 'type' => 'XLSX'],
            ['name' => 'Inventario de Bases de Datos', 'file' => 'FOR-SI-005-Inventario-Bases-Datos.xlsx', 'type' => 'XLSX'],
            ['name' => 'Inventario de Usuarios y Accesos', 'file' => 'FOR-SI-006-Inventario-Usuarios-Accesos.xlsx', 'type' => 'XLSX']
        ]
    ],
    'Formatos_Riesgos' => [
        'icon' => '&#9888;',
        'color' => '#cc0000',
        'documents' => [
            ['name' => 'Analisis de Riesgos', 'file' => 'FOR-SI-010-Analisis-Riesgos.xlsx', 'type' => 'XLSX'],
            ['name' => 'Matriz de Riesgos', 'file' => 'FOR-SI-011-Matriz-Riesgos.xlsx', 'type' => 'XLSX'],
            ['name' => 'Plan de Tratamiento de Riesgos', 'file' => 'FOR-SI-012-Plan-Tratamiento-Riesgos.xlsx', 'type' => 'XLSX'],
            ['name' => 'Evaluacion de Riesgos por Proceso', 'file' => 'FOR-SI-013-Evaluacion-Riesgos-Proceso.xlsx', 'type' => 'XLSX'],
            ['name' => 'Registro de Riesgos Identificados', 'file' => 'FOR-SI-014-Registro-Riesgos.xlsx', 'type' => 'XLSX'],
            ['name' => 'Analisis de Impacto al Negocio (BIA)', 'file' => 'FOR-SI-015-BIA-Impacto-Negocio.xlsx', 'type' => 'XLSX']
        ]
    ],
    'Formatos_Controles' => [
        'icon' => '&#9881;',
        'color' => '#9900cc',
        'documents' => [
            ['name' => 'Anexo A - Controles ISO 27001', 'file' => 'FOR-SI-020-Anexo-A-Controles.xlsx', 'type' => 'XLSX'],
            ['name' => 'Declaracion de Aplicabilidad (SOA)', 'file' => 'FOR-SI-021-Declaracion-Aplicabilidad.xlsx', 'type' => 'XLSX'],
            ['name' => 'Evaluacion de Controles', 'file' => 'FOR-SI-022-Evaluacion-Controles.xlsx', 'type' => 'XLSX'],
            ['name' => 'Plan de Implementacion de Controles', 'file' => 'FOR-SI-023-Plan-Implementacion-Controles.xlsx', 'type' => 'XLSX'],
            ['name' => 'Efectividad de Controles', 'file' => 'FOR-SI-024-Efectividad-Controles.xlsx', 'type' => 'XLSX']
        ]
    ],
    'Formatos_Incidentes' => [
        'icon' => '&#128293;',
        'color' => '#cc3333',
        'documents' => [
            ['name' => 'Registro de Incidentes de Seguridad', 'file' => 'FOR-SI-030-Registro-Incidentes.xlsx', 'type' => 'XLSX'],
            ['name' => 'Reporte de Incidente de Seguridad', 'file' => 'FOR-SI-031-Reporte-Incidente.docx', 'type' => 'DOCX'],
            ['name' => 'Analisis de Causa Raiz de Incidente', 'file' => 'FOR-SI-032-Analisis-Causa-Raiz.docx', 'type' => 'DOCX'],
            ['name' => 'Plan de Respuesta a Incidentes', 'file' => 'FOR-SI-033-Plan-Respuesta-Incidentes.docx', 'type' => 'DOCX'],
            ['name' => 'Informe Post-Incidente', 'file' => 'FOR-SI-034-Informe-Post-Incidente.docx', 'type' => 'DOCX']
        ]
    ],
    'Formatos_Auditorias' => [
        'icon' => '&#128197;',
        'color' => '#0099cc',
        'documents' => [
            ['name' => 'Plan de Auditorias Internas', 'file' => 'FOR-SI-040-Plan-Auditorias.xlsx', 'type' => 'XLSX'],
            ['name' => 'Lista de Verificacion de Auditoria', 'file' => 'FOR-SI-041-Checklist-Auditoria.xlsx', 'type' => 'XLSX'],
            ['name' => 'Informe de Auditoria Interna', 'file' => 'FOR-SI-042-Informe-Auditoria.docx', 'type' => 'DOCX'],
            ['name' => 'Registro de No Conformidades', 'file' => 'FOR-SI-043-Registro-No-Conformidades.xlsx', 'type' => 'XLSX'],
            ['name' => 'Plan de Acciones Correctivas', 'file' => 'FOR-SI-044-Plan-Acciones-Correctivas.xlsx', 'type' => 'XLSX'],
            ['name' => 'Seguimiento de Hallazgos', 'file' => 'FOR-SI-045-Seguimiento-Hallazgos.xlsx', 'type' => 'XLSX']
        ]
    ],
    'Formatos_Capacitacion' => [
        'icon' => '&#127891;',
        'color' => '#66cc00',
        'documents' => [
            ['name' => 'Plan de Capacitacion en Seguridad', 'file' => 'FOR-SI-050-Plan-Capacitacion.xlsx', 'type' => 'XLSX'],
            ['name' => 'Registro de Asistencia a Capacitaciones', 'file' => 'FOR-SI-051-Asistencia-Capacitacion.xlsx', 'type' => 'XLSX'],
            ['name' => 'Evaluacion de Efectividad de Capacitacion', 'file' => 'FOR-SI-052-Evaluacion-Capacitacion.xlsx', 'type' => 'XLSX'],
            ['name' => 'Programa de Concientizacion', 'file' => 'FOR-SI-053-Programa-Concientizacion.docx', 'type' => 'DOCX']
        ]
    ],
    'Formatos_Monitoreo' => [
        'icon' => '&#128200;',
        'color' => '#ff9900',
        'documents' => [
            ['name' => 'Indicadores de Desempeno (KPI)', 'file' => 'FOR-SI-060-KPI-Indicadores.xlsx', 'type' => 'XLSX'],
            ['name' => 'Dashboard de Seguridad', 'file' => 'FOR-SI-061-Dashboard-Seguridad.xlsx', 'type' => 'XLSX'],
            ['name' => 'Metricas de Seguridad', 'file' => 'FOR-SI-062-Metricas-Seguridad.xlsx', 'type' => 'XLSX'],
            ['name' => 'Informe Mensual de Seguridad', 'file' => 'FOR-SI-063-Informe-Mensual.docx', 'type' => 'DOCX'],
            ['name' => 'Revision por la Direccion', 'file' => 'FOR-SI-064-Revision-Direccion.docx', 'type' => 'DOCX']
        ]
    ],
    'Formatos_Continuidad' => [
        'icon' => '&#128295;',
        'color' => '#cc6600',
        'documents' => [
            ['name' => 'Plan de Continuidad del Negocio (BCP)', 'file' => 'FOR-SI-070-Plan-Continuidad-Negocio.docx', 'type' => 'DOCX'],
            ['name' => 'Plan de Recuperacion de Desastres (DRP)', 'file' => 'FOR-SI-071-Plan-Recuperacion-Desastres.docx', 'type' => 'DOCX'],
            ['name' => 'Procedimiento de Respaldo de Datos', 'file' => 'FOR-SI-072-Procedimiento-Respaldos.docx', 'type' => 'DOCX'],
            ['name' => 'Prueba de Continuidad', 'file' => 'FOR-SI-073-Prueba-Continuidad.xlsx', 'type' => 'XLSX'],
            ['name' => 'Registro de Respaldos', 'file' => 'FOR-SI-074-Registro-Respaldos.xlsx', 'type' => 'XLSX']
        ]
    ],
    'Formatos_Proveedores' => [
        'icon' => '&#128101;',
        'color' => '#996633',
        'documents' => [
            ['name' => 'Evaluacion de Proveedores', 'file' => 'FOR-SI-080-Evaluacion-Proveedores.xlsx', 'type' => 'XLSX'],
            ['name' => 'Acuerdo de Confidencialidad (NDA)', 'file' => 'FOR-SI-081-Acuerdo-Confidencialidad.docx', 'type' => 'DOCX'],
            ['name' => 'Acuerdo de Nivel de Servicio (SLA)', 'file' => 'FOR-SI-082-SLA-Proveedores.docx', 'type' => 'DOCX'],
            ['name' => 'Cuestionario de Seguridad para Proveedores', 'file' => 'FOR-SI-083-Cuestionario-Proveedores.xlsx', 'type' => 'XLSX']
        ]
    ],
    'Reportes_Certificacion' => [
        'icon' => '&#127942;',
        'color' => '#0066cc',
        'documents' => [
            ['name' => 'Manual del Sistema de Gestion de Seguridad', 'file' => 'REP-SI-090-Manual-SGSI.docx', 'type' => 'DOCX'],
            ['name' => 'Alcance y Limites del SGSI', 'file' => 'REP-SI-091-Alcance-SGSI.docx', 'type' => 'DOCX'],
            ['name' => 'Contexto de la Organizacion', 'file' => 'REP-SI-092-Contexto-Organizacion.docx', 'type' => 'DOCX'],
            ['name' => 'Partes Interesadas y Requisitos', 'file' => 'REP-SI-093-Partes-Interesadas.xlsx', 'type' => 'XLSX'],
            ['name' => 'Objetivos de Seguridad', 'file' => 'REP-SI-094-Objetivos-Seguridad.xlsx', 'type' => 'XLSX'],
            ['name' => 'Organigrama del SGSI', 'file' => 'REP-SI-095-Organigrama-SGSI.docx', 'type' => 'DOCX'],
            ['name' => 'Roles y Responsabilidades', 'file' => 'REP-SI-096-Roles-Responsabilidades.xlsx', 'type' => 'XLSX']
        ]
    ]
];

// Textos multiidioma
$texts = [
    'es' => [
        'title' => 'Documentos y Formatos',
        'subtitle' => 'ISO 27001 - Biblioteca de Documentos y Plantillas',
        'total_documents' => 'Total de Documentos',
        'approved_documents' => 'Documentos Aprobados',
        'categories' => 'Categorias de Documentos',
        'download' => 'Descargar',
        'download_all' => 'Descargar Todos',
        'back' => 'Volver',
        'print' => 'Imprimir',
        'document_name' => 'Nombre del Documento',
        'file_type' => 'Tipo',
        'actions' => 'Acciones'
    ]
];

$t = $texts[$language];
?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?> - AUDITOR PRO</title>
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
            padding: 25px 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header-title h1 {
            font-size: 28px;
            color: #cc0000;
            margin-bottom: 5px;
        }

        .header-title p {
            color: #666;
            font-size: 14px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-primary {
            background: #0066cc;
            color: white;
        }

        .btn-success {
            background: #00994d;
            color: white;
        }

        .btn-warning {
            background: #ff6600;
            color: white;
        }

        .btn-secondary {
            background: #666;
            color: white;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #cc0000;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }

        .category-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .category-header {
            padding: 20px 30px;
            border-left: 6px solid;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }

        .category-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .category-icon {
            font-size: 32px;
        }

        .category-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        .document-list {
            padding: 0;
        }

        .document-item {
            padding: 15px 30px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s ease;
        }

        .document-item:hover {
            background: #f8f9fa;
        }

        .document-item:last-child {
            border-bottom: none;
        }

        .document-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .document-number {
            background: #0066cc;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }

        .document-name {
            color: #333;
            font-weight: 500;
        }

        .document-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            background: #e9ecef;
            color: #495057;
        }

        .document-type.docx {
            background: #2b5797;
            color: white;
        }

        .document-type.xlsx {
            background: #217346;
            color: white;
        }

        .document-type.pdf {
            background: #cc0000;
            color: white;
        }

        @media print {
            body {
                background: white;
            }
            .action-buttons, .btn {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                text-align: center;
            }

            .action-buttons {
                margin-top: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .document-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>&#128196; <?php echo $t['title']; ?></h1>
                    <p><?php echo $t['subtitle']; ?></p>
                </div>
                <div class="action-buttons">
                    <button onclick="window.print()" class="btn btn-primary">&#128424; <?php echo $t['print']; ?></button>
                    <a href="export.php?format=zip&type=all_documents" class="btn btn-success">&#128229; <?php echo $t['download_all']; ?></a>
                    <a href="index.php" class="btn btn-secondary">&#8592; <?php echo $t['back']; ?></a>
                </div>
            </div>

            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-value"><?php echo count($document_catalog); ?></div>
                    <div class="stat-label"><?php echo $t['categories']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">
                        <?php
                        $total = 0;
                        foreach ($document_catalog as $category) {
                            $total += count($category['documents']);
                        }
                        echo $total;
                        ?>
                    </div>
                    <div class="stat-label"><?php echo $t['total_documents']; ?></div>
                </div>
            </div>
        </div>

        <?php foreach ($document_catalog as $category_key => $category): ?>
        <div class="category-section">
            <div class="category-header" style="border-color: <?php echo $category['color']; ?>">
                <div class="category-title">
                    <span class="category-icon"><?php echo $category['icon']; ?></span>
                    <span class="category-name"><?php echo str_replace('_', ' ', $category_key); ?></span>
                    <span class="badge" style="background: <?php echo $category['color']; ?>; color: white; padding: 5px 12px; border-radius: 12px; font-size: 12px;">
                        <?php echo count($category['documents']); ?> documentos
                    </span>
                </div>
                <a href="export.php?format=zip&category=<?php echo $category_key; ?>" class="btn btn-warning btn-small">
                    &#128229; Descargar Categoria
                </a>
            </div>

            <div class="document-list">
                <?php $counter = 1; ?>
                <?php foreach ($category['documents'] as $doc): ?>
                <div class="document-item">
                    <div class="document-info">
                        <div class="document-number"><?php echo $counter++; ?></div>
                        <div class="document-name"><?php echo $doc['name']; ?></div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="document-type <?php echo strtolower($doc['type']); ?>"><?php echo $doc['type']; ?></span>
                        <a href="templates/<?php echo $doc['file']; ?>" download class="btn btn-success btn-small">
                            &#128229; <?php echo $t['download']; ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-top: 30px;">
            <h3 style="color: #333; margin-bottom: 15px;">Informacion Importante</h3>
            <p style="color: #666; line-height: 1.8; margin-bottom: 15px;">
                Todos los documentos y formatos estan disponibles para descarga inmediata. Los archivos DOCX y XLSX son plantillas editables que puede personalizar segun las necesidades de su organizacion.
            </p>
            <p style="color: #666; line-height: 1.8;">
                <strong>Nota:</strong> Estos documentos estan disenados para cumplir con los requisitos de ISO 27001:2022 y deben ser revisados y aprobados por su organizacion antes de su implementacion oficial.
            </p>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
