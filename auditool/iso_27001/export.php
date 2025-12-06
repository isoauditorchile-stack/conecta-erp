<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// SIN DEPENDENCIAS - PHP PURO
// Obtener parámetros
$code = isset($_GET['code']) ? $_GET['code'] : '';

if (empty($code)) {
    die('Código de documento no especificado');
}

// =====================================================
// TODOS LOS DOCUMENTOS ISO 27001:2022 - 85 DOCUMENTOS
// =====================================================

$documentos = [
    // === POLÍTICAS (22) ===
    'POL-SI-001' => [
        'tipo' => 'Política',
        'titulo' => 'POLÍTICA DE SEGURIDAD DE LA INFORMACIÓN',
        'contenido' => 'Establecer el marco general de seguridad de la información protegiendo los activos contra amenazas. Aplica a todos los empleados, contratistas y terceros con acceso a información organizacional.'
    ],
    'POL-SI-002' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE CONTROL DE ACCESO', 'contenido' => 'Gestión y control de accesos a sistemas, aplicaciones y datos mediante principio de menor privilegio y autenticación multifactor.'],
    'POL-SI-003' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE CLASIFICACIÓN DE LA INFORMACIÓN', 'contenido' => 'Niveles de clasificación: Pública, Interna, Confidencial y Estrictamente Confidencial con controles correspondientes.'],
    'POL-SI-004' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE SEGURIDAD FÍSICA', 'contenido' => 'Protección de instalaciones, equipos y personal mediante controles de acceso físico, videovigilancia y gestión de visitantes.'],
    'POL-SI-005' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE GESTIÓN DE ACTIVOS', 'contenido' => 'Inventario y clasificación de activos de información con propietarios designados y controles de protección.'],
    'POL-SI-006' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE RECURSOS HUMANOS', 'contenido' => 'Seguridad en contratación, formación continua y proceso de desvinculación con transferencia de conocimiento.'],
    'POL-SI-007' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE DESARROLLO SEGURO', 'contenido' => 'Ciclo de vida de desarrollo seguro con revisiones de código, pruebas de seguridad y gestión de vulnerabilidades.'],
    'POL-SI-008' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE GESTIÓN DE CAMBIOS', 'contenido' => 'Control de cambios en infraestructura y aplicaciones con aprobación formal, pruebas y plan de rollback.'],
    'POL-SI-009' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE RESPALDO Y RECUPERACIÓN', 'contenido' => 'Respaldos diarios incrementales, semanales completos, mensuales archivados con RPO 24h y RTO 72h.'],
    'POL-SI-010' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE GESTIÓN DE INCIDENTES', 'contenido' => 'Detección, reporte, análisis y respuesta a incidentes con equipo CSIRT y lecciones aprendidas.'],
    'POL-SI-011' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE USO ACEPTABLE', 'contenido' => 'Uso apropiado de recursos tecnológicos prohibiendo actividades ilegales, personales excesivas y transferencias no autorizadas.'],
    'POL-SI-012' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE CIFRADO', 'contenido' => 'Cifrado AES-256 para datos en reposo, TLS 1.3 para transmisión y gestión de claves criptográficas.'],
    'POL-SI-013' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE MONITOREO Y AUDITORÍA', 'contenido' => 'Monitoreo continuo de eventos de seguridad con SIEM, análisis de logs y auditorías trimestrales.'],
    'POL-SI-014' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE CONTINUIDAD DE NEGOCIO', 'contenido' => 'Plan de continuidad y recuperación ante desastres con sitio alterno y pruebas semestrales.'],
    'POL-SI-015' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE GESTIÓN DE PROVEEDORES', 'contenido' => 'Evaluación, selección y monitoreo de proveedores con cláusulas de seguridad en contratos.'],
    'POL-SI-016' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE SEGURIDAD EN LA NUBE', 'contenido' => 'Controles para servicios cloud: cifrado, acceso, cumplimiento y responsabilidad compartida.'],
    'POL-SI-017' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE PRIVACIDAD Y PROTECCIÓN DE DATOS', 'contenido' => 'Cumplimiento GDPR/LGPD con consentimiento, minimización, derechos ARCO y DPO designado.'],
    'POL-SI-018' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE TELETRABAJO', 'contenido' => 'Seguridad en trabajo remoto: VPN, equipos corporativos, espacios seguros y horarios definidos.'],
    'POL-SI-019' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE DISPOSITIVOS MÓVILES (MDM)', 'contenido' => 'Gestión de móviles corporativos con MDM, cifrado, bloqueo remoto y separación datos corporativos/personales.'],
    'POL-SI-020' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE CORREO ELECTRÓNICO', 'contenido' => 'Uso de correo: prohibir spam, phishing, archivos ejecutables y contenido ofensivo con filtros anti-malware.'],
    'POL-SI-021' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE REDES SOCIALES', 'contenido' => 'Uso responsable de redes sociales protegiendo información confidencial y reputación organizacional.'],
    'POL-SI-022' => ['tipo' => 'Política', 'titulo' => 'POLÍTICA DE DESTRUCCIÓN DE ACTIVOS', 'contenido' => 'Eliminación segura de información: borrado certificado, destrucción física y documentación del proceso.'],

    // === PROCEDIMIENTOS (10) ===
    'PROC-SI-001' => ['tipo' => 'Procedimiento', 'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE RIESGOS', 'contenido' => 'Identificar, analizar, evaluar y tratar riesgos de seguridad con metodología ISO 27005 y revisión anual.'],
    'PROC-SI-002' => ['tipo' => 'Procedimiento', 'titulo' => 'PROCEDIMIENTO DE CONTROL DE ACCESO', 'contenido' => 'Solicitud, aprobación, provisión y revocación de accesos con formularios y matriz de autorización.'],
    'PROC-SI-003' => ['tipo' => 'Procedimiento', 'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE INCIDENTES', 'contenido' => 'Detección, registro, clasificación, investigación, contención, erradicación, recuperación y lecciones aprendidas.'],
    'PROC-SI-004' => ['tipo' => 'Procedimiento', 'titulo' => 'PROCEDIMIENTO DE RESPALDO Y RECUPERACIÓN', 'contenido' => 'Configuración de respaldos, verificación de integridad, almacenamiento seguro y pruebas de restauración.'],
    'PROC-SI-005' => ['tipo' => 'Procedimiento', 'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE CAMBIOS', 'contenido' => 'Solicitud CAB, evaluación de impacto, aprobación, implementación en ventana de mantenimiento y validación.'],
    'PROC-SI-006' => ['tipo' => 'Procedimiento', 'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE VULNERABILIDADES', 'contenido' => 'Escaneo mensual, clasificación por severidad, parcheo según criticidad y reporte de cumplimiento.'],
    'PROC-SI-007' => ['tipo' => 'Procedimiento', 'titulo' => 'PROCEDIMIENTO DE AUDITORÍA INTERNA', 'contenido' => 'Planificación anual, ejecución de auditorías, hallazgos, planes de acción y seguimiento de remediación.'],
    'PROC-SI-008' => ['tipo' => 'Procedimiento', 'titulo' => 'PROCEDIMIENTO DE REVISIÓN DE ACCESOS', 'contenido' => 'Revisión trimestral de permisos, certificación de gerentes, revocación de accesos innecesarios.'],
    'PROC-SI-009' => ['tipo' => 'Procedimiento', 'titulo' => 'PROCEDIMIENTO DE MONITOREO DE SEGURIDAD', 'contenido' => 'Configuración SIEM, definición de reglas de correlación, alertas, análisis de eventos y respuesta.'],
    'PROC-SI-010' => ['tipo' => 'Procedimiento', 'titulo' => 'PROCEDIMIENTO DE CONTINUIDAD DE NEGOCIO', 'contenido' => 'Análisis de impacto (BIA), estrategias de continuidad, plan de recuperación y pruebas semestrales.'],

    // === INVENTARIOS (6) ===
    'INV-001' => ['tipo' => 'Inventario', 'titulo' => 'INVENTARIO DE ACTIVOS DE HARDWARE', 'contenido' => 'Servidores, computadores, laptops, dispositivos móviles, equipos de red con propietario, ubicación y clasificación.'],
    'INV-002' => ['tipo' => 'Inventario', 'titulo' => 'INVENTARIO DE ACTIVOS DE SOFTWARE', 'contenido' => 'Sistemas operativos, aplicaciones, bases de datos, licencias con versión, proveedor y fecha expiración.'],
    'INV-003' => ['tipo' => 'Inventario', 'titulo' => 'INVENTARIO DE ACTIVOS DE INFORMACIÓN', 'contenido' => 'Bases de datos, archivos, documentos con clasificación, propietario, ubicación y controles aplicados.'],
    'INV-004' => ['tipo' => 'Inventario', 'titulo' => 'INVENTARIO DE ACTIVOS DE SERVICIOS', 'contenido' => 'Servicios internos y externos, APIs, servicios cloud con SLA, proveedor y nivel de criticidad.'],
    'INV-005' => ['tipo' => 'Inventario', 'titulo' => 'INVENTARIO DE USUARIOS Y ACCESOS', 'contenido' => 'Usuarios activos, roles, permisos, sistemas con acceso, fecha último acceso y revisión.'],
    'INV-006' => ['tipo' => 'Inventario', 'titulo' => 'INVENTARIO DE PROVEEDORES CRÍTICOS', 'contenido' => 'Proveedores de TI y seguridad con servicios prestados, SLA, evaluación de riesgo y contactos.'],

    // === RIESGOS (6) ===
    'RISK-001' => ['tipo' => 'Riesgo', 'titulo' => 'ANÁLISIS DE RIESGOS DE ACTIVOS', 'contenido' => 'Evaluación de amenazas y vulnerabilidades de activos: ransomware, robo, fallo hardware, desastres naturales.'],
    'RISK-002' => ['tipo' => 'Riesgo', 'titulo' => 'MATRIZ DE RIESGOS INHERENTES', 'contenido' => 'Riesgos sin controles: probabilidad e impacto en escala 1-5 para accesos no autorizados, pérdida datos.'],
    'RISK-003' => ['tipo' => 'Riesgo', 'titulo' => 'MATRIZ DE RIESGOS RESIDUALES', 'contenido' => 'Riesgos después de controles implementados con nivel de riesgo aceptado por la dirección.'],
    'RISK-004' => ['tipo' => 'Riesgo', 'titulo' => 'PLAN DE TRATAMIENTO DE RIESGOS', 'contenido' => 'Estrategias: Mitigar (implementar controles), Transferir (seguros), Evitar (eliminar actividad), Aceptar (riesgos bajos).'],
    'RISK-005' => ['tipo' => 'Riesgo', 'titulo' => 'REGISTRO DE RIESGOS MATERIALIZADOS', 'contenido' => 'Incidentes ocurridos, impacto real, costo, controles que fallaron y mejoras implementadas.'],
    'RISK-006' => ['tipo' => 'Riesgo', 'titulo' => 'EVALUACIÓN DE RIESGOS DE TERCEROS', 'contenido' => 'Riesgos de proveedores y partners: acceso a datos, disponibilidad de servicios, cumplimiento legal.'],

    // === CONTROLES (5) ===
    'CTRL-001' => ['tipo' => 'Control', 'titulo' => 'CONTROLES TÉCNICOS IMPLEMENTADOS', 'contenido' => 'Firewall, IDS/IPS, Antivirus, DLP, Cifrado, MFA, WAF, SIEM con estado operativo y responsable.'],
    'CTRL-002' => ['tipo' => 'Control', 'titulo' => 'CONTROLES ADMINISTRATIVOS', 'contenido' => 'Políticas, procedimientos, capacitación, auditorías, revisiones de acceso con frecuencia y evidencias.'],
    'CTRL-003' => ['tipo' => 'Control', 'titulo' => 'CONTROLES FÍSICOS', 'contenido' => 'Control de acceso biométrico, CCTV, guardias de seguridad, UPS, aire acondicionado, extinción de incendios.'],
    'CTRL-004' => ['tipo' => 'Control', 'titulo' => 'PRUEBAS DE EFECTIVIDAD DE CONTROLES', 'contenido' => 'Pruebas trimestrales de controles críticos: pen testing, auditorías, simulacros, revisión de logs.'],
    'CTRL-005' => ['tipo' => 'Control', 'titulo' => 'MEJORA CONTINUA DE CONTROLES', 'contenido' => 'Análisis de incidentes, nuevas amenazas, cambios tecnológicos para actualizar controles existentes.'],

    // === INCIDENTES (5) ===
    'INC-001' => ['tipo' => 'Incidente', 'titulo' => 'REGISTRO DE INCIDENTES DE SEGURIDAD', 'contenido' => 'Fecha, hora, reportante, tipo de incidente, severidad, sistemas afectados, estado de investigación.'],
    'INC-002' => ['tipo' => 'Incidente', 'titulo' => 'CLASIFICACIÓN DE INCIDENTES', 'contenido' => 'Crítico (impacto severo, 1h respuesta), Alto (4h), Medio (24h), Bajo (72h) según impacto y urgencia.'],
    'INC-003' => ['tipo' => 'Incidente', 'titulo' => 'RESPUESTA Y CONTENCIÓN', 'contenido' => 'Aislamiento de sistemas afectados, recolección de evidencias forenses, análisis de causa raíz.'],
    'INC-004' => ['tipo' => 'Incidente', 'titulo' => 'COMUNICACIÓN DE INCIDENTES', 'contenido' => 'Notificación interna a dirección, externa a clientes/reguladores según severidad y requisitos legales.'],
    'INC-005' => ['tipo' => 'Incidente', 'titulo' => 'LECCIONES APRENDIDAS', 'contenido' => 'Análisis post-incidente: qué falló, qué funcionó, mejoras de controles, actualización de procedimientos.'],

    // === AUDITORÍAS (6) ===
    'AUD-001' => ['tipo' => 'Auditoría', 'titulo' => 'PLAN ANUAL DE AUDITORÍAS', 'contenido' => 'Calendario de auditorías internas ISO 27001: controles técnicos, políticas, procesos, cumplimiento.'],
    'AUD-002' => ['tipo' => 'Auditoría', 'titulo' => 'PROGRAMA DE AUDITORÍA', 'contenido' => 'Alcance, criterios, metodología, equipo auditor, cronograma para cada auditoría planificada.'],
    'AUD-003' => ['tipo' => 'Auditoría', 'titulo' => 'HALLAZGOS DE AUDITORÍA', 'contenido' => 'No conformidades mayores y menores, observaciones, oportunidades de mejora con evidencias documentadas.'],
    'AUD-004' => ['tipo' => 'Auditoría', 'titulo' => 'PLANES DE ACCIÓN CORRECTIVA', 'contenido' => 'Acciones para remediar hallazgos: responsable, fecha compromiso, recursos necesarios, verificación.'],
    'AUD-005' => ['tipo' => 'Auditoría', 'titulo' => 'SEGUIMIENTO DE AUDITORÍAS', 'contenido' => 'Validación de implementación de acciones correctivas, cierre de hallazgos, reporte a la dirección.'],
    'AUD-006' => ['tipo' => 'Auditoría', 'titulo' => 'AUDITORÍA DE CERTIFICACIÓN', 'contenido' => 'Auditoría externa de ente certificador: etapa 1 (revisión documental), etapa 2 (implementación), vigilancias.'],

    // === CAPACITACIÓN (4) ===
    'CAP-001' => ['tipo' => 'Capacitación', 'titulo' => 'PROGRAMA DE CAPACITACIÓN EN SEGURIDAD', 'contenido' => 'Inducción para nuevos, capacitación anual, formación especializada para TI, campañas de concientización.'],
    'CAP-002' => ['tipo' => 'Capacitación', 'titulo' => 'MÓDULOS DE CAPACITACIÓN', 'contenido' => 'Políticas de seguridad, phishing, ingeniería social, manejo de información clasificada, trabajo remoto seguro.'],
    'CAP-003' => ['tipo' => 'Capacitación', 'titulo' => 'REGISTRO DE CAPACITACIONES', 'contenido' => 'Empleado, curso, fecha, duración, evaluación, asistencia con firma de confirmación de recepción.'],
    'CAP-004' => ['tipo' => 'Capacitación', 'titulo' => 'EVALUACIÓN DE EFECTIVIDAD', 'contenido' => 'Exámenes post-capacitación, simulacros de phishing, métricas de incidentes por error humano.'],

    // === MONITOREO (5) ===
    'MON-001' => ['tipo' => 'Monitoreo', 'titulo' => 'MONITOREO DE EVENTOS DE SEGURIDAD', 'contenido' => 'SIEM 24/7, alertas de intentos de acceso fallidos, cambios de configuración, actividad anómala.'],
    'MON-002' => ['tipo' => 'Monitoreo', 'titulo' => 'INDICADORES DE DESEMPEÑO (KPI)', 'contenido' => 'Tiempo de detección/respuesta, incidentes por mes, disponibilidad de sistemas, cumplimiento de parches.'],
    'MON-003' => ['tipo' => 'Monitoreo', 'titulo' => 'MÉTRICAS DE SEGURIDAD', 'contenido' => 'Vulnerabilidades detectadas/remediadas, porcentaje de sistemas con antivirus actualizado, cobertura de respaldos.'],
    'MON-004' => ['tipo' => 'Monitoreo', 'titulo' => 'ANÁLISIS DE LOGS', 'contenido' => 'Revisión diaria de logs críticos, retención 1 año, análisis forense post-incidente, cumplimiento SOX.'],
    'MON-005' => ['tipo' => 'Monitoreo', 'titulo' => 'REPORTES A LA DIRECCIÓN', 'contenido' => 'Reporte mensual ejecutivo: estado del SGSI, incidentes, riesgos, cumplimiento, inversiones en seguridad.'],

    // === CONTINUIDAD (5) ===
    'CONT-001' => ['tipo' => 'Continuidad', 'titulo' => 'ANÁLISIS DE IMPACTO AL NEGOCIO (BIA)', 'contenido' => 'Procesos críticos, RTOs, RPOs, dependencias, impacto financiero de interrupción, recursos requeridos.'],
    'CONT-002' => ['tipo' => 'Continuidad', 'titulo' => 'PLAN DE CONTINUIDAD DE NEGOCIO (BCP)', 'contenido' => 'Estrategias de continuidad, procedimientos de activación, equipos de respuesta, sitios alternos, comunicación.'],
    'CONT-003' => ['tipo' => 'Continuidad', 'titulo' => 'PLAN DE RECUPERACIÓN ANTE DESASTRES (DRP)', 'contenido' => 'Restauración de infraestructura TI, priorización de sistemas, procedimientos de failover, respaldos offsite.'],
    'CONT-004' => ['tipo' => 'Continuidad', 'titulo' => 'PRUEBAS DE CONTINUIDAD', 'contenido' => 'Simulacros semestrales, pruebas de restauración, ejercicios de escritorio, validación de contactos de emergencia.'],
    'CONT-005' => ['tipo' => 'Continuidad', 'titulo' => 'MANTENIMIENTO DEL PLAN', 'contenido' => 'Actualización anual, después de cambios organizacionales, lecciones de incidentes reales y simulacros.'],

    // === PROVEEDORES (4) ===
    'PROV-001' => ['tipo' => 'Proveedores', 'titulo' => 'EVALUACIÓN DE RIESGO DE PROVEEDORES', 'contenido' => 'Cuestionario de seguridad, auditorías de terceros, certificaciones ISO 27001, SOC 2, revisión anual.'],
    'PROV-002' => ['tipo' => 'Proveedores', 'titulo' => 'CONTRATOS CON CLÁUSULAS DE SEGURIDAD', 'contenido' => 'SLA de disponibilidad, penalidades, confidencialidad, derecho a auditar, notificación de brechas, terminación.'],
    'PROV-003' => ['tipo' => 'Proveedores', 'titulo' => 'MONITOREO DE PROVEEDORES', 'contenido' => 'Revisión trimestral de SLA, incidentes causados por proveedor, cambios en su seguridad, renovaciones.'],
    'PROV-004' => ['tipo' => 'Proveedores', 'titulo' => 'SALIDA DE PROVEEDORES', 'contenido' => 'Transferencia de conocimiento, retorno/destrucción de información, revocación de accesos, auditoría final.'],

    // === REPORTES CERTIFICACIÓN (7) ===
    'CERT-001' => ['tipo' => 'Certificación', 'titulo' => 'DECLARACIÓN DE APLICABILIDAD (SOA)', 'contenido' => 'Anexo A ISO 27001:2022 - 93 controles: aplicables, no aplicables, justificación, nivel de implementación.'],
    'CERT-002' => ['tipo' => 'Certificación', 'titulo' => 'ALCANCE DEL SGSI', 'contenido' => 'Límites del sistema: procesos, ubicaciones, tecnologías incluidas/excluidas, justificación de exclusiones.'],
    'CERT-003' => ['tipo' => 'Certificación', 'titulo' => 'POLÍTICA DEL SGSI', 'contenido' => 'Compromiso de la dirección, objetivos de seguridad, cumplimiento legal, mejora continua, recursos asignados.'],
    'CERT-004' => ['tipo' => 'Certificación', 'titulo' => 'OBJETIVOS DE SEGURIDAD Y METAS', 'contenido' => 'Reducir incidentes 20%, certificación ISO 27001, implementar SIEM, capacitación 100% empleados.'],
    'CERT-005' => ['tipo' => 'Certificación', 'titulo' => 'REVISIÓN POR LA DIRECCIÓN', 'contenido' => 'Reunión trimestral: desempeño del SGSI, cumplimiento de objetivos, cambios internos/externos, mejoras necesarias.'],
    'CERT-006' => ['tipo' => 'Certificación', 'titulo' => 'EVIDENCIAS DE IMPLEMENTACIÓN', 'contenido' => 'Registros de capacitación, logs de monitoreo, resultados de auditorías, respaldos, pruebas de controles.'],
    'CERT-007' => ['tipo' => 'Certificación', 'titulo' => 'PLAN DE MEJORA CONTINUA', 'contenido' => 'Acciones correctivas de no conformidades, preventivas de riesgos emergentes, optimización de procesos.']
];

// Verificar que el documento existe
if (!isset($documentos[$code])) {
    die('Documento no encontrado: ' . htmlspecialchars($code));
}

$doc = $documentos[$code];
$fecha = date('d/m/Y');
$organizacion = 'AUDITOR PRO - Sistema de Gestión de Seguridad de la Información';

// GENERAR HTML PARA IMPRIMIR A PDF
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($code . ' - ' . $doc['titulo']); ?></title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }
        .container {
            max-width: 21cm;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .header .subtitle {
            color: #7f8c8d;
            font-size: 14px;
            font-weight: normal;
        }
        .document-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .document-info h2 {
            font-size: 28px;
            margin-bottom: 15px;
            text-align: center;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .info-item {
            background: rgba(255,255,255,0.1);
            padding: 12px;
            border-radius: 5px;
        }
        .info-item label {
            font-size: 11px;
            text-transform: uppercase;
            opacity: 0.8;
            display: block;
            margin-bottom: 5px;
        }
        .info-item .value {
            font-size: 16px;
            font-weight: bold;
        }
        .section {
            background: #f8f9fa;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
            border-radius: 5px;
        }
        .section h3 {
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }
        .section p {
            text-align: justify;
            line-height: 1.8;
            color: #555;
        }
        .approval-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .approval-table th {
            background: #2c3e50;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
        }
        .approval-table td {
            padding: 15px 12px;
            border: 1px solid #ddd;
        }
        .approval-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
            text-align: center;
            color: #7f8c8d;
            font-size: 12px;
        }
        .badge {
            display: inline-block;
            padding: 5px 15px;
            background: #27ae60;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .control-iso {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: center;
            font-weight: bold;
            color: #856404;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white;
            }
            .container {
                max-width: 100%;
            }
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s;
            z-index: 1000;
        }
        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-button no-print">🖨️ Imprimir / Guardar PDF</button>

    <div class="container">
        <div class="header">
            <h1><?php echo htmlspecialchars($organizacion); ?></h1>
            <p class="subtitle">ISO/IEC 27001:2022 - Sistema de Gestión de Seguridad de la Información</p>
        </div>

        <div class="document-info">
            <div class="badge"><?php echo htmlspecialchars($doc['tipo']); ?></div>
            <h2><?php echo htmlspecialchars($doc['titulo']); ?></h2>

            <div class="info-grid">
                <div class="info-item">
                    <label>Código</label>
                    <div class="value"><?php echo htmlspecialchars($code); ?></div>
                </div>
                <div class="info-item">
                    <label>Versión</label>
                    <div class="value">1.0</div>
                </div>
                <div class="info-item">
                    <label>Fecha</label>
                    <div class="value"><?php echo $fecha; ?></div>
                </div>
                <div class="info-item">
                    <label>Estado</label>
                    <div class="value">✅ VIGENTE</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>📋 Contenido</h3>
            <p><?php echo nl2br(htmlspecialchars($doc['contenido'])); ?></p>
        </div>

        <div class="section">
            <h3>🎯 Objetivo</h3>
            <p>Establecer los lineamientos y controles necesarios para garantizar la seguridad de la información, proteger los activos organizacionales y asegurar el cumplimiento de la norma ISO/IEC 27001:2022.</p>
        </div>

        <div class="section">
            <h3>📍 Alcance</h3>
            <p>Este documento aplica a todos los procesos, sistemas, empleados, contratistas y terceros que interactúan con la información de la organización.</p>
        </div>

        <div class="section">
            <h3>👥 Responsabilidades</h3>
            <p><strong>Alta Dirección:</strong> Aprobar y proveer recursos para la implementación.<br>
            <strong>CISO:</strong> Supervisar la implementación y cumplimiento.<br>
            <strong>Empleados:</strong> Conocer, cumplir y reportar incumplimientos.</p>
        </div>

        <div class="control-iso">
            🔐 Control ISO 27001:2022 - Anexo A
        </div>

        <table class="approval-table">
            <thead>
                <tr>
                    <th>ROL</th>
                    <th>NOMBRE</th>
                    <th>FIRMA</th>
                    <th>FECHA</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Elaborado por</strong></td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td><?php echo $fecha; ?></td>
                </tr>
                <tr>
                    <td><strong>Revisado por</strong></td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td><strong>Aprobado por</strong></td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            </tbody>
        </table>

        <div class="section">
            <h3>📝 Historial de Versiones</h3>
            <table class="approval-table">
                <thead>
                    <tr>
                        <th>Versión</th>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Autor</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1.0</td>
                        <td><?php echo $fecha; ?></td>
                        <td>Versión inicial del documento</td>
                        <td>AUDITOR PRO</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <p><strong>AUDITOR PRO</strong> - Sistema de Gestión de Seguridad de la Información</p>
            <p>ISO/IEC 27001:2022 | Documento: <?php echo htmlspecialchars($code); ?> | Generado: <?php echo date('d/m/Y H:i:s'); ?></p>
            <p style="margin-top: 10px; font-size: 10px;">
                Este documento es confidencial y propiedad de la organización. Su distribución no autorizada está prohibida.
            </p>
        </div>
    </div>

    <script>
        // Auto-print si se pasa parámetro
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('autoprint') === '1') {
            window.onload = function() {
                window.print();
            };
        }
    </script>
</body>
</html>
