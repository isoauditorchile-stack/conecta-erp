<?php
session_start();
require_once('../config/config.php');

$conn = getDBConnection();

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;

// Obtener información de la empresa
$company = ['company_name' => 'AUDITOR PRO', 'rut' => '00.000.000-0', 'address' => 'Dirección', 'city' => 'Ciudad', 'country' => 'País'];
$sql = "SELECT company_name, rut, address, city, country FROM companies WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $company = $row;
    }
    $stmt->close();
}

$format = isset($_GET['format']) ? $_GET['format'] : 'excel';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$code = isset($_GET['code']) ? $_GET['code'] : '';

// =====================================================
// FUNCIONES DE GENERACIÓN
// =====================================================

function generateExcelXML($filename, $data, $headers, $title = '') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
    echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
    echo '<Worksheet ss:Name="Datos">' . "\n";
    echo '<Table>' . "\n";

    if ($title) {
        echo '<Row><Cell ss:MergeAcross="' . (count($headers) - 1) . '"><Data ss:Type="String"><b>' . htmlspecialchars($title) . '</b></Data></Cell></Row>' . "\n";
        echo '<Row></Row>' . "\n";
    }

    echo '<Row>' . "\n";
    foreach ($headers as $header) {
        echo '<Cell ss:StyleID="Header"><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>' . "\n";
    }
    echo '</Row>' . "\n";

    foreach ($data as $row) {
        echo '<Row>' . "\n";
        foreach ($row as $cell) {
            $cellValue = is_numeric($cell) ? $cell : htmlspecialchars($cell);
            $cellType = is_numeric($cell) ? 'Number' : 'String';
            echo '<Cell><Data ss:Type="' . $cellType . '">' . $cellValue . '</Data></Cell>' . "\n";
        }
        echo '</Row>' . "\n";
    }

    echo '</Table>' . "\n";
    echo '</Worksheet>' . "\n";
    echo '</Workbook>';
    exit;
}

function generateWordDoc($filename, $content, $title) {
    header('Content-Type: application/msword; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.doc"');
    header('Cache-Control: max-age=0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="utf-8"><title>' . htmlspecialchars($title) . '</title></head>';
    echo '<body>';
    echo '<div style="font-family: Arial, sans-serif; margin: 40px;">';
    echo '<h1 style="color: #00994d; border-bottom: 3px solid #00994d; padding-bottom: 10px;">' . htmlspecialchars($title) . '</h1>';
    echo $content;
    echo '</div></body></html>';
    exit;
}

// =====================================================
// SWITCH POR TIPO DE EXPORTACIÓN
// =====================================================

if ($type == 'formato') {

    switch ($code) {

        // FO-SI-001: Matriz de Requisitos ISO 27001
        case 'FO-SI-001':
            $requisitos = [
                ['4.1', 'Comprensión de la organización y su contexto', 'Obligatorio', 'Pendiente', '', ''],
                ['4.2', 'Comprensión de las necesidades y expectativas de las partes interesadas', 'Obligatorio', 'Pendiente', '', ''],
                ['4.3', 'Determinación del alcance del SGSI', 'Obligatorio', 'Pendiente', '', ''],
                ['4.4', 'Sistema de gestión de seguridad de la información', 'Obligatorio', 'Pendiente', '', ''],
                ['5.1', 'Liderazgo y compromiso', 'Obligatorio', 'Pendiente', '', ''],
                ['5.2', 'Política de seguridad de la información', 'Obligatorio', 'Pendiente', '', ''],
                ['5.3', 'Roles, responsabilidades y autoridades', 'Obligatorio', 'Pendiente', '', ''],
                ['6.1.1', 'Generalidades - Acciones para abordar riesgos', 'Obligatorio', 'Pendiente', '', ''],
                ['6.1.2', 'Evaluación de riesgos de seguridad', 'Obligatorio', 'Pendiente', '', ''],
                ['6.1.3', 'Tratamiento de riesgos', 'Obligatorio', 'Pendiente', '', ''],
                ['6.2', 'Objetivos de seguridad y planificación', 'Obligatorio', 'Pendiente', '', ''],
                ['7.1', 'Recursos', 'Obligatorio', 'Pendiente', '', ''],
                ['7.2', 'Competencia', 'Obligatorio', 'Pendiente', '', ''],
                ['7.3', 'Toma de conciencia', 'Obligatorio', 'Pendiente', '', ''],
                ['7.4', 'Comunicación', 'Obligatorio', 'Pendiente', '', ''],
                ['7.5', 'Información documentada', 'Obligatorio', 'Pendiente', '', ''],
                ['8.1', 'Planificación y control operacional', 'Obligatorio', 'Pendiente', '', ''],
                ['8.2', 'Evaluación de riesgos', 'Obligatorio', 'Pendiente', '', ''],
                ['8.3', 'Tratamiento de riesgos', 'Obligatorio', 'Pendiente', '', ''],
                ['9.1', 'Seguimiento, medición, análisis y evaluación', 'Obligatorio', 'Pendiente', '', ''],
                ['9.2', 'Auditoría interna', 'Obligatorio', 'Pendiente', '', ''],
                ['9.3', 'Revisión por la dirección', 'Obligatorio', 'Pendiente', '', ''],
                ['10.1', 'No conformidad y acción correctiva', 'Obligatorio', 'Pendiente', '', ''],
                ['10.2', 'Mejora continua', 'Obligatorio', 'Pendiente', '', '']
            ];
            generateExcelXML('FO-SI-001_Matriz_Requisitos_ISO27001', $requisitos,
                ['Cláusula', 'Requisito', 'Tipo', 'Estado', 'Evidencia', 'Observaciones'],
                'MATRIZ DE REQUISITOS ISO 27001:2022');
            break;

        // FO-SI-002: Plan de Implementación
        case 'FO-SI-002':
            $plan = [
                ['1', 'Diagnóstico inicial', 'Enero 2025', 'Febrero 2025', 'Consultor SGSI', 'Pendiente', ''],
                ['2', 'Definición alcance SGSI', 'Febrero 2025', 'Febrero 2025', 'Gerencia', 'Pendiente', ''],
                ['3', 'Análisis de brechas', 'Marzo 2025', 'Marzo 2025', 'Auditor', 'Pendiente', ''],
                ['4', 'Inventario de activos', 'Marzo 2025', 'Abril 2025', 'TI', 'Pendiente', ''],
                ['5', 'Análisis de riesgos', 'Abril 2025', 'Mayo 2025', 'CISO', 'Pendiente', ''],
                ['6', 'Plan de tratamiento riesgos', 'Mayo 2025', 'Junio 2025', 'CISO', 'Pendiente', ''],
                ['7', 'Implementación controles', 'Junio 2025', 'Septiembre 2025', 'TI/CISO', 'Pendiente', ''],
                ['8', 'Documentación SGSI', 'Julio 2025', 'Agosto 2025', 'Consultor', 'Pendiente', ''],
                ['9', 'Capacitación personal', 'Septiembre 2025', 'Septiembre 2025', 'RRHH', 'Pendiente', ''],
                ['10', 'Auditoría interna', 'Octubre 2025', 'Octubre 2025', 'Auditor Interno', 'Pendiente', ''],
                ['11', 'Revisión por dirección', 'Octubre 2025', 'Octubre 2025', 'Gerencia', 'Pendiente', ''],
                ['12', 'Auditoría de certificación', 'Noviembre 2025', 'Diciembre 2025', 'Certificadora', 'Pendiente', '']
            ];
            generateExcelXML('FO-SI-002_Plan_Implementacion_ISO27001', $plan,
                ['#', 'Actividad', 'Inicio', 'Fin', 'Responsable', 'Estado', 'Observaciones'],
                'PLAN DE IMPLEMENTACIÓN ISO 27001:2022');
            break;

        // FO-SI-003: Inventario de Activos
        case 'FO-SI-003':
            $activos = [
                ['ACT-001', 'Servidor Principal Base de Datos', 'Hardware', 'Centro de Datos', 'TI', 'Crítico', '5', '5', '5', 'Activo'],
                ['ACT-002', 'Sistema ERP', 'Software', 'Cloud', 'TI', 'Alto', '5', '5', '4', 'Activo'],
                ['ACT-003', 'Base de Datos Clientes', 'Datos', 'Centro de Datos', 'DBA', 'Crítico', '5', '5', '5', 'Activo'],
                ['ACT-004', 'Correo Electrónico Corporativo', 'Servicio', 'Cloud', 'TI', 'Alto', '4', '4', '5', 'Activo'],
                ['ACT-005', 'Firewall Perimetral', 'Hardware', 'Centro de Datos', 'Seguridad', 'Crítico', '4', '5', '5', 'Activo']
            ];
            generateExcelXML('FO-SI-003_Inventario_Activos', $activos,
                ['Código', 'Nombre', 'Tipo', 'Ubicación', 'Propietario', 'Criticidad', 'C', 'I', 'D', 'Estado'],
                'INVENTARIO DE ACTIVOS DE INFORMACIÓN');
            break;

        // FO-SI-004: Matriz de Riesgos
        case 'FO-SI-004':
            $riesgos = [
                ['R-001', 'Pérdida de datos por falla hardware', 'Servidor BD', '4', '5', '20', 'Crítico', 'Implementar RAID y backups', '2', '3', '6', 'Medio'],
                ['R-002', 'Acceso no autorizado a sistemas', 'Sistemas críticos', '3', '4', '12', 'Alto', 'MFA y control accesos', '2', '2', '4', 'Bajo'],
                ['R-003', 'Ransomware', 'Toda la infraestructura', '3', '5', '15', 'Alto', 'Antimalware + backups offline', '2', '3', '6', 'Medio'],
                ['R-004', 'Fuga de información', 'Datos clientes', '4', '5', '20', 'Crítico', 'DLP + cifrado', '2', '3', '6', 'Medio'],
                ['R-005', 'DDoS', 'Servicios web', '3', '3', '9', 'Medio', 'CDN + WAF', '2', '2', '4', 'Bajo']
            ];
            generateExcelXML('FO-SI-004_Matriz_Riesgos', $riesgos,
                ['ID', 'Riesgo', 'Activo', 'Prob', 'Imp', 'RI', 'Nivel', 'Tratamiento', 'Prob R', 'Imp R', 'RR', 'Nivel R'],
                'MATRIZ DE RIESGOS DE SEGURIDAD');
            break;

        // FO-SI-005: Plan Tratamiento de Riesgos
        case 'FO-SI-005':
            $tratamiento = [
                ['R-001', 'Pérdida datos por falla hardware', 'Mitigar', 'Implementar RAID 10', 'Adquisición servidor', 'TI', '2025-03-31', 'Pendiente', '$5,000'],
                ['R-001', 'Pérdida datos por falla hardware', 'Mitigar', 'Backups diarios automáticos', 'Configurar Veeam', 'TI', '2025-02-28', 'Pendiente', '$1,200'],
                ['R-002', 'Acceso no autorizado', 'Mitigar', 'Implementar MFA', 'Azure MFA', 'Seguridad', '2025-04-30', 'Pendiente', '$2,500'],
                ['R-003', 'Ransomware', 'Mitigar', 'Antimalware Enterprise', 'Kaspersky EDR', 'Seguridad', '2025-03-15', 'Pendiente', '$3,800'],
                ['R-004', 'Fuga información', 'Mitigar', 'DLP en endpoints', 'Symantec DLP', 'Seguridad', '2025-05-30', 'Pendiente', '$6,000']
            ];
            generateExcelXML('FO-SI-005_Plan_Tratamiento_Riesgos', $tratamiento,
                ['ID Riesgo', 'Descripción', 'Opción', 'Acción', 'Descripción Acción', 'Responsable', 'Fecha Límite', 'Estado', 'Costo'],
                'PLAN DE TRATAMIENTO DE RIESGOS');
            break;

        // FO-SI-006: SOA - Declaración de Aplicabilidad
        case 'FO-SI-006':
            $soa = [
                ['A.5.1', 'Políticas de seguridad', 'Aplicable', 'Implementado', 'POL-SI-001 Política General de Seguridad'],
                ['A.5.2', 'Roles y responsabilidades', 'Aplicable', 'Implementado', 'Matriz RACI definida'],
                ['A.5.7', 'Inteligencia de amenazas', 'Aplicable', 'Parcial', 'Suscripción feeds de amenazas pendiente'],
                ['A.5.9', 'Inventario de activos', 'Aplicable', 'Implementado', 'FO-SI-003 actualizado mensualmente'],
                ['A.5.10', 'Uso aceptable', 'Aplicable', 'Implementado', 'POL-SI-005 Uso Aceptable TI'],
                ['A.8.1', 'Dispositivos endpoint', 'Aplicable', 'Implementado', 'MDM + EDR en todos los endpoints'],
                ['A.8.2', 'Derechos de acceso privilegiados', 'Aplicable', 'Implementado', 'PAM implementado'],
                ['A.8.5', 'Autenticación segura', 'Aplicable', 'Implementado', 'MFA en sistemas críticos'],
                ['A.8.8', 'Gestión vulnerabilidades', 'Aplicable', 'Parcial', 'Escaneos mensuales, remediación en proceso']
            ];
            generateExcelXML('FO-SI-006_SOA_Declaracion_Aplicabilidad', $soa,
                ['Control', 'Nombre', 'Aplicabilidad', 'Estado', 'Justificación/Evidencia'],
                'DECLARACIÓN DE APLICABILIDAD (SOA) - ISO 27001:2022');
            break;

        // FO-SI-007: Matriz Controles Anexo A
        case 'FO-SI-007':
            $controles = [
                ['A.5.1', 'Organizacional', 'Políticas de seguridad de la información', 'Aplicable', '85%', 'Implementado', 'Política aprobada y comunicada'],
                ['A.5.7', 'Organizacional', 'Inteligencia de amenazas', 'Aplicable', '60%', 'Parcial', 'Pendiente suscripción feeds'],
                ['A.5.9', 'Organizacional', 'Inventario de activos', 'Aplicable', '100%', 'Implementado', 'Inventario actualizado mensualmente'],
                ['A.8.1', 'Tecnológico', 'Dispositivos endpoint', 'Aplicable', '90%', 'Implementado', 'EDR en 95% de equipos'],
                ['A.8.5', 'Tecnológico', 'Autenticación segura', 'Aplicable', '100%', 'Implementado', 'MFA obligatorio']
            ];
            generateExcelXML('FO-SI-007_Matriz_Controles_AnexoA', $controles,
                ['Control', 'Categoría', 'Descripción', 'Aplicabilidad', 'Implementación', 'Estado', 'Observaciones'],
                'MATRIZ DE CONTROLES ANEXO A - ISO 27001:2022');
            break;

        // FO-SI-008: Registro de Incidentes
        case 'FO-SI-008':
            $incidentes = [
                ['INC-2025-001', '2025-01-15 09:30', 'Alto', 'Malware detectado en workstation', 'Juan Pérez', 'En Investigación', 'TI', '2025-01-15 15:00', ''],
                ['INC-2025-002', '2025-01-18 14:20', 'Crítico', 'Intento acceso no autorizado servidor BD', 'Sistema IDS', 'Contenido', 'Seguridad', '2025-01-18 16:00', 'IP bloqueada'],
                ['INC-2025-003', '2025-01-22 11:15', 'Medio', 'Phishing reportado por usuario', 'María López', 'Cerrado', 'Seguridad', '2025-01-22 13:00', 'Usuario capacitado']
            ];
            generateExcelXML('FO-SI-008_Registro_Incidentes', $incidentes,
                ['ID', 'Fecha/Hora', 'Severidad', 'Descripción', 'Reportado por', 'Estado', 'Asignado a', 'Última Actualización', 'Resolución'],
                'REGISTRO DE INCIDENTES DE SEGURIDAD');
            break;

        // FO-SI-009 a FO-SI-010: Documentos Word
        case 'FO-SI-009':
        case 'FO-SI-010':
            $content = '<h2>Plan de Respuesta a Incidentes</h2>';
            $content .= '<p>Este documento define el marco de trabajo para la gestión de incidentes de seguridad.</p>';
            $content .= '<h3>1. Objetivo</h3><p>Establecer procedimientos para responder efectivamente a incidentes.</p>';
            $content .= '<h3>2. Alcance</h3><p>Todos los sistemas dentro del SGSI.</p>';
            generateWordDoc($code . '_Plan_Respuesta_Incidentes', $content, 'Plan de Respuesta a Incidentes');
            break;

        // FO-SI-011: Checklist Auditoría Interna
        case 'FO-SI-011':
            $checklist = [
                ['4.1', '¿Se han identificado las cuestiones internas y externas?', 'Cláusula 4.1', 'Sí', 'No', 'N/A', 'Documento contexto organización'],
                ['4.2', '¿Se han identificado las partes interesadas?', 'Cláusula 4.2', 'Sí', 'No', 'N/A', 'Matriz partes interesadas'],
                ['4.3', '¿Está documentado el alcance del SGSI?', 'Cláusula 4.3', 'Sí', 'No', 'N/A', 'Documento alcance SGSI'],
                ['5.2', '¿Existe política de seguridad aprobada?', 'Cláusula 5.2', 'Sí', 'No', 'N/A', 'POL-SI-001'],
                ['6.1.2', '¿Se realiza evaluación de riesgos?', 'Cláusula 6.1.2', 'Sí', 'No', 'N/A', 'FO-SI-004 Matriz riesgos'],
                ['8.2', '¿Se evalúan riesgos periódicamente?', 'Cláusula 8.2', 'Sí', 'No', 'N/A', 'Registro evaluaciones'],
                ['9.2', '¿Se realizan auditorías internas?', 'Cláusula 9.2', 'Sí', 'No', 'N/A', 'Informes auditoría'],
                ['9.3', '¿Se realizan revisiones por dirección?', 'Cláusula 9.3', 'Sí', 'No', 'N/A', 'Actas revisión']
            ];
            generateExcelXML('FO-SI-011_Checklist_Auditoria_Interna', $checklist,
                ['Req', 'Pregunta', 'Referencia', 'Cumple', 'No Cumple', 'N/A', 'Evidencia'],
                'CHECKLIST AUDITORÍA INTERNA ISO 27001');
            break;

        // FO-SI-012: Plan Anual de Auditorías
        case 'FO-SI-012':
            $plan_audit = [
                ['1', 'Auditoría Cláusula 4 - Contexto', '2025-02-15', '2025-02-16', 'Auditor Líder', 'Gerencia', 'Programada'],
                ['2', 'Auditoría Cláusula 5 - Liderazgo', '2025-03-20', '2025-03-21', 'Auditor Líder', 'Dirección', 'Programada'],
                ['3', 'Auditoría Cláusula 6 - Planificación', '2025-04-18', '2025-04-19', 'Auditor TI', 'TI/Seguridad', 'Programada'],
                ['4', 'Auditoría Controles Técnicos', '2025-05-22', '2025-05-23', 'Auditor TI', 'TI', 'Programada'],
                ['5', 'Auditoría Controles Organizacionales', '2025-06-19', '2025-06-20', 'Auditor RRHH', 'RRHH', 'Programada'],
                ['6', 'Auditoría Cláusula 9 - Evaluación', '2025-09-25', '2025-09-26', 'Auditor Líder', 'Calidad', 'Programada'],
                ['7', 'Auditoría Cláusula 10 - Mejora', '2025-10-23', '2025-10-24', 'Auditor Líder', 'Mejora Continua', 'Programada'],
                ['8', 'Auditoría Integral SGSI', '2025-11-20', '2025-11-22', 'Equipo Auditor', 'Todas', 'Programada']
            ];
            generateExcelXML('FO-SI-012_Plan_Anual_Auditorias', $plan_audit,
                ['#', 'Auditoría', 'Fecha Inicio', 'Fecha Fin', 'Auditor', 'Área', 'Estado'],
                'PLAN ANUAL DE AUDITORÍAS INTERNAS');
            break;

        // FO-SI-013: Informe Auditoría - Word
        case 'FO-SI-013':
            $content = '<h2>Informe de Auditoría Interna</h2>';
            $content .= '<p><strong>Auditoría #:</strong> AUD-2025-001</p>';
            $content .= '<p><strong>Fecha:</strong> ' . date('d/m/Y') . '</p>';
            $content .= '<h3>1. Alcance de la Auditoría</h3><p>Sistema de Gestión de Seguridad de la Información ISO 27001:2022</p>';
            $content .= '<h3>2. Hallazgos</h3><p>Se identificaron 3 no conformidades menores.</p>';
            generateWordDoc($code . '_Informe_Auditoria', $content, 'Informe de Auditoría Interna');
            break;

        // FO-SI-014: Registro No Conformidades
        case 'FO-SI-014':
            $nc = [
                ['NC-001', '2025-01-10', 'Auditoría Interna', 'Mayor', 'No existe inventario actualizado de activos', 'A.5.9', 'TI', '2025-02-28', 'Abierta'],
                ['NC-002', '2025-01-10', 'Auditoría Interna', 'Menor', 'Registros de backup sin verificación', 'A.8.13', 'TI', '2025-02-15', 'Cerrada'],
                ['NC-003', '2025-01-12', 'Revisión Gestión', 'Mayor', 'Plan de capacitación no implementado', 'A.6.3', 'RRHH', '2025-03-15', 'En Proceso']
            ];
            generateExcelXML('FO-SI-014_Registro_No_Conformidades', $nc,
                ['ID', 'Fecha', 'Origen', 'Tipo', 'Descripción', 'Requisito', 'Responsable', 'Fecha Cierre', 'Estado'],
                'REGISTRO DE NO CONFORMIDADES');
            break;

        // FO-SI-015: Plan Acciones Correctivas
        case 'FO-SI-015':
            $ac = [
                ['AC-001', 'NC-001', 'Crear inventario de activos', 'Implementar FO-SI-003 y actualizar mensualmente', 'Jefe TI', '2025-02-28', '50%', 'En Proceso'],
                ['AC-002', 'NC-002', 'Verificar backups', 'Crear procedimiento verificación mensual backups', 'Administrador BD', '2025-02-15', '100%', 'Completada'],
                ['AC-003', 'NC-003', 'Implementar capacitación', 'Ejecutar plan de capacitación trimestral', 'Jefe RRHH', '2025-03-15', '30%', 'En Proceso']
            ];
            generateExcelXML('FO-SI-015_Plan_Acciones_Correctivas', $ac,
                ['ID', 'NC Relacionada', 'Acción', 'Descripción', 'Responsable', 'Fecha Límite', 'Avance', 'Estado'],
                'PLAN DE ACCIONES CORRECTIVAS');
            break;

        // FO-SI-016: Acta Revisión Dirección - Word
        case 'FO-SI-016':
            $content = '<h2>Acta de Revisión por la Dirección</h2>';
            $content .= '<p><strong>Revisión #:</strong> RD-2025-001</p>';
            $content .= '<p><strong>Fecha:</strong> ' . date('d/m/Y') . '</p>';
            $content .= '<h3>1. Asistentes</h3><p>Gerencia General, CISO, Gerentes de Área</p>';
            $content .= '<h3>2. Temas Revisados</h3><p>Desempeño del SGSI, objetivos, mejoras</p>';
            generateWordDoc($code . '_Acta_Revision_Direccion', $content, 'Acta de Revisión por la Dirección');
            break;

        // FO-SI-017: Indicadores KPI
        case 'FO-SI-017':
            $kpi = [
                ['KPI-001', 'Tiempo promedio resolución incidentes', 'Horas', '< 24', '18', 'Mensual', 'Verde', 'Cumple objetivo'],
                ['KPI-002', 'Porcentaje usuarios capacitados', '%', '> 90', '85', 'Trimestral', 'Amarillo', 'Reforzar capacitación'],
                ['KPI-003', 'Disponibilidad sistemas críticos', '%', '> 99.5', '99.8', 'Mensual', 'Verde', 'Excelente'],
                ['KPI-004', 'Vulnerabilidades críticas sin parchar', 'Número', '0', '2', 'Mensual', 'Rojo', 'Aplicar parches urgente'],
                ['KPI-005', 'Incidentes de seguridad', 'Número', '< 5', '3', 'Mensual', 'Verde', 'Dentro del rango']
            ];
            generateExcelXML('FO-SI-017_Indicadores_KPI', $kpi,
                ['ID', 'Indicador', 'Unidad', 'Meta', 'Valor Actual', 'Frecuencia', 'Semáforo', 'Observaciones'],
                'INDICADORES DE SEGURIDAD (KPI)');
            break;

        // FO-SI-018: Dashboard
        case 'FO-SI-018':
            $dashboard = [
                ['Incidentes Mes', '3', '< 5', 'Verde'],
                ['Disponibilidad', '99.8%', '> 99.5%', 'Verde'],
                ['Vulnerabilidades Críticas', '2', '0', 'Rojo'],
                ['Usuarios Capacitados', '85%', '> 90%', 'Amarillo']
            ];
            generateExcelXML('FO-SI-018_Dashboard_Seguridad', $dashboard,
                ['Indicador', 'Valor Actual', 'Meta', 'Estado'],
                'DASHBOARD DE SEGURIDAD');
            break;

        // FO-SI-019: Matriz Usuarios y Accesos
        case 'FO-SI-019':
            $accesos = [
                ['USR-001', 'Juan Pérez', 'Gerente TI', 'jperez', 'Administrador Sistemas', 'ERP,BD,Firewall', 'Activo', '2024-01-15', '2025-01-15'],
                ['USR-002', 'María López', 'Analista', 'mlopez', 'Usuario', 'ERP,Email', 'Activo', '2024-03-10', '2025-03-10'],
                ['USR-003', 'Carlos Ruiz', 'DBA', 'cruiz', 'Administrador BD', 'BD,ERP', 'Activo', '2024-02-20', '2025-02-20'],
                ['USR-004', 'Ana Torres', 'CISO', 'atorres', 'Administrador Seguridad', 'Todos', 'Activo', '2024-01-10', '2025-01-10']
            ];
            generateExcelXML('FO-SI-019_Matriz_Usuarios_Accesos', $accesos,
                ['ID', 'Nombre', 'Cargo', 'Usuario', 'Rol', 'Sistemas', 'Estado', 'Fecha Alta', 'Revisión'],
                'MATRIZ DE USUARIOS Y ACCESOS');
            break;

        // FO-SI-020 a FO-SI-023: Formularios Word
        case 'FO-SI-020':
        case 'FO-SI-021':
        case 'FO-SI-022':
        case 'FO-SI-023':
            $content = '<h2>Formulario ' . $code . '</h2>';
            $content .= '<p>Documento en formato Word listo para personalizar según las necesidades de su organización.</p>';
            $content .= '<table border="1" width="100%"><tr><th>Campo</th><th>Valor</th></tr>';
            $content .= '<tr><td>Fecha:</td><td></td></tr>';
            $content .= '<tr><td>Solicitante:</td><td></td></tr>';
            $content .= '<tr><td>Área:</td><td></td></tr>';
            $content .= '</table>';
            generateWordDoc($code . '_Formulario', $content, 'Formulario ' . $code);
            break;

        // FO-SI-024: Plan Capacitación
        case 'FO-SI-024':
            $capacitacion = [
                ['CAP-001', 'Concientización Seguridad Información', 'Todo el personal', 'Virtual', '4', '2025-02-15', 'Seguridad', 'Programado', '120'],
                ['CAP-002', 'Gestión de Incidentes', 'Equipo TI', 'Presencial', '8', '2025-03-20', 'TI', 'Programado', '15'],
                ['CAP-003', 'Phishing y Ingeniería Social', 'Todo el personal', 'Virtual', '2', '2025-04-10', 'Seguridad', 'Programado', '120'],
                ['CAP-004', 'ISO 27001 Fundamentos', 'Equipo SGSI', 'Presencial', '16', '2025-05-15', 'Externo', 'Programado', '8']
            ];
            generateExcelXML('FO-SI-024_Plan_Capacitacion', $capacitacion,
                ['ID', 'Capacitación', 'Destinatarios', 'Modalidad', 'Horas', 'Fecha', 'Instructor', 'Estado', 'Asistentes'],
                'PLAN DE CAPACITACIÓN EN SEGURIDAD');
            break;

        // FO-SI-025: Registro Asistencia
        case 'FO-SI-025':
            $asistencia = [
                ['CAP-001', 'Juan Pérez', 'TI', 'Presente', '100', 'Aprobado', '2025-02-15', 'Certificado emitido'],
                ['CAP-001', 'María López', 'Ventas', 'Presente', '95', 'Aprobado', '2025-02-15', 'Certificado emitido'],
                ['CAP-001', 'Carlos Ruiz', 'TI', 'Ausente', '', 'Pendiente', '', 'Reprogramar'],
                ['CAP-002', 'Ana Torres', 'Seguridad', 'Presente', '100', 'Aprobado', '2025-03-20', 'Excelente participación']
            ];
            generateExcelXML('FO-SI-025_Registro_Asistencia', $asistencia,
                ['Capacitación', 'Nombre', 'Área', 'Asistencia', 'Calificación', 'Estado', 'Fecha', 'Observaciones'],
                'REGISTRO DE ASISTENCIA A CAPACITACIONES');
            break;

        // FO-SI-026: Evaluación Proveedores
        case 'FO-SI-026':
            $proveedores = [
                ['PROV-001', 'Cloud Provider SA', 'Cloud Computing', '95', '90', '100', '92', 'Aprobado', '2025-01-15', 'Certificado ISO 27001'],
                ['PROV-002', 'Security Solutions', 'Seguridad Gestionada', '88', '85', '95', '89', 'Aprobado', '2025-01-20', 'SOC 24/7'],
                ['PROV-003', 'Software Corp', 'Desarrollo Software', '75', '70', '80', '75', 'Condicional', '2025-02-01', 'Mejorar SLA']
            ];
            generateExcelXML('FO-SI-026_Evaluacion_Proveedores', $proveedores,
                ['ID', 'Proveedor', 'Servicio', 'Técnico', 'Seguridad', 'Cumplimiento', 'Global', 'Estado', 'Fecha Eval', 'Observaciones'],
                'EVALUACIÓN DE PROVEEDORES');
            break;

        // FO-SI-027: Cuestionario Proveedores
        case 'FO-SI-027':
            $cuestionario = [
                ['¿Tiene política de seguridad información?', 'Sí/No', 'Crítico', '', ''],
                ['¿Cuenta con certificación ISO 27001?', 'Sí/No', 'Alto', '', ''],
                ['¿Realiza backups diarios?', 'Sí/No', 'Crítico', '', ''],
                ['¿Tiene plan continuidad negocio?', 'Sí/No', 'Alto', '', '']
            ];
            generateExcelXML('FO-SI-027_Cuestionario_Proveedores', $cuestionario,
                ['Pregunta', 'Respuesta', 'Importancia', 'Evidencia', 'Observaciones'],
                'CUESTIONARIO DE SEGURIDAD PARA PROVEEDORES');
            break;

        // FO-SI-028, 029, 036, 040: Documentos maestros Word
        case 'FO-SI-028':
        case 'FO-SI-029':
        case 'FO-SI-036':
        case 'FO-SI-040':
            $titles = [
                'FO-SI-028' => 'Acuerdo de Nivel de Servicio (SLA)',
                'FO-SI-029' => 'Plan de Continuidad del Negocio',
                'FO-SI-036' => 'Contexto de la Organización',
                'FO-SI-040' => 'Manual del SGSI'
            ];
            $content = '<h2>' . $titles[$code] . '</h2>';
            $content .= '<p>Documento maestro en formato Word.</p>';
            $content .= '<p>Este documento debe ser personalizado según las necesidades de su organización.</p>';
            $content .= '<h3>Instrucciones</h3>';
            $content .= '<ol><li>Revisar la estructura del documento</li>';
            $content .= '<li>Completar con información específica de la organización</li>';
            $content .= '<li>Revisar y aprobar según procedimiento</li></ol>';
            generateWordDoc($code . '_Documento', $content, $titles[$code]);
            break;

        // FO-SI-030: Análisis BIA
        case 'FO-SI-030':
            $bia = [
                ['PROC-001', 'Procesamiento Transacciones', 'Crítico', '2h', '4h', '24h', '$50,000/día', 'Alto', 'Pérdida clientes'],
                ['PROC-002', 'Email Corporativo', 'Alto', '4h', '8h', '48h', '$5,000/día', 'Medio', 'Retrasos comunicación'],
                ['PROC-003', 'Portal Web', 'Crítico', '1h', '2h', '12h', '$100,000/día', 'Crítico', 'Pérdida ventas'],
                ['PROC-004', 'Backup Datos', 'Alto', '12h', '24h', '72h', '$10,000/día', 'Alto', 'Riesgo pérdida datos']
            ];
            generateExcelXML('FO-SI-030_Analisis_BIA', $bia,
                ['ID', 'Proceso', 'Criticidad', 'RTO', 'RPO', 'MTPD', 'Impacto Financiero', 'Impacto Operativo', 'Impacto Reputacional'],
                'ANÁLISIS DE IMPACTO AL NEGOCIO (BIA)');
            break;

        // FO-SI-031: Registro Respaldos
        case 'FO-SI-031':
            $backups = [
                ['2025-01-20 23:00', 'Base Datos Principal', 'Completo', '250 GB', 'Exitoso', '4h 30m', 'Verificado OK', 'Operador TI'],
                ['2025-01-21 23:00', 'Base Datos Principal', 'Incremental', '45 GB', 'Exitoso', '1h 15m', 'Verificado OK', 'Operador TI'],
                ['2025-01-22 23:00', 'Base Datos Principal', 'Incremental', '52 GB', 'Fallido', '', 'Error conexión', 'Operador TI'],
                ['2025-01-23 23:00', 'Base Datos Principal', 'Incremental', '48 GB', 'Exitoso', '1h 10m', 'Verificado OK', 'Operador TI']
            ];
            generateExcelXML('FO-SI-031_Registro_Respaldos', $backups,
                ['Fecha/Hora', 'Sistema', 'Tipo', 'Tamaño', 'Estado', 'Duración', 'Verificación', 'Responsable'],
                'REGISTRO DE RESPALDOS');
            break;

        // FO-SI-032: Pruebas Continuidad
        case 'FO-SI-032':
            $pruebas = [
                ['PRUEBA-001', '2025-01-15', 'Recuperación Base Datos', 'Exitoso', '2 horas', 'RTO cumplido', 'Equipo TI'],
                ['PRUEBA-002', '2025-02-20', 'Failover Firewall', 'Exitoso', '15 minutos', 'Automático', 'Seguridad']
            ];
            generateExcelXML('FO-SI-032_Pruebas_Continuidad', $pruebas,
                ['ID', 'Fecha', 'Prueba', 'Resultado', 'Tiempo', 'Observaciones', 'Responsable'],
                'PRUEBAS DE CONTINUIDAD');
            break;

        // FO-SI-033: Registro Vulnerabilidades
        case 'FO-SI-033':
            $vulns = [
                ['VULN-001', 'CVE-2024-1234', 'Servidor Web', 'Crítica', '9.8', 'Ejecución remota código', 'Abierta', 'Aplicar parche', '2025-02-15', 'TI'],
                ['VULN-002', 'CVE-2024-5678', 'Sistema ERP', 'Alta', '7.5', 'Inyección SQL', 'En Proceso', 'Actualización programada', '2025-02-20', 'DBA'],
                ['VULN-003', 'CVE-2024-9012', 'WordPress', 'Media', '5.4', 'XSS', 'Cerrada', 'Plugin actualizado', '2025-01-30', 'Web Admin']
            ];
            generateExcelXML('FO-SI-033_Registro_Vulnerabilidades', $vulns,
                ['ID', 'CVE', 'Activo', 'Severidad', 'CVSS', 'Descripción', 'Estado', 'Tratamiento', 'Fecha Límite', 'Responsable'],
                'REGISTRO DE VULNERABILIDADES');
            break;

        // FO-SI-034: Registro Cambios
        case 'FO-SI-034':
            $cambios = [
                ['CHG-001', '2025-01-15', 'Actualización Firewall', 'Alto', 'Gerente TI', 'Aprobado', 'Completado', '2025-01-18', 'Sin incidentes'],
                ['CHG-002', '2025-01-20', 'Migración Base Datos', 'Crítico', 'DBA', 'Aprobado', 'Completado', '2025-01-22', 'Exitoso'],
                ['CHG-003', '2025-01-25', 'Actualización Antivirus', 'Medio', 'Seguridad', 'Aprobado', 'En Proceso', '2025-02-01', '']
            ];
            generateExcelXML('FO-SI-034_Registro_Cambios', $cambios,
                ['ID', 'Fecha Solicitud', 'Descripción', 'Impacto', 'Solicitante', 'Aprobación', 'Estado', 'Fecha Implementación', 'Resultado'],
                'REGISTRO DE CAMBIOS');
            break;

        // FO-SI-035: Listado Maestro Documentos
        case 'FO-SI-035':
            $documentos = [
                ['POL-SI-001', 'Política General Seguridad Información', 'Política', 'v2.0', '2024-01-10', 'Gerencia', 'Vigente'],
                ['PROC-SI-001', 'Control de Documentos', 'Procedimiento', 'v1.5', '2024-02-15', 'Calidad', 'Vigente'],
                ['FO-SI-003', 'Inventario de Activos', 'Formato', 'v1.0', '2024-03-01', 'TI', 'Vigente'],
                ['MAN-SGSI-001', 'Manual del SGSI', 'Manual', 'v3.0', '2024-01-05', 'CISO', 'Vigente']
            ];
            generateExcelXML('FO-SI-035_Listado_Maestro_Documentos', $documentos,
                ['Código', 'Nombre', 'Tipo', 'Versión', 'Fecha', 'Responsable', 'Estado'],
                'LISTADO MAESTRO DE DOCUMENTOS');
            break;

        // FO-SI-037: Partes Interesadas
        case 'FO-SI-037':
            $partes = [
                ['Clientes', 'Externa', 'Alta', 'Protección datos personales, Disponibilidad servicios', 'ISO 27001, GDPR', 'Satisfacción > 90%'],
                ['Accionistas', 'Interna', 'Alta', 'Protección activos, Cumplimiento legal', 'Informes trimestrales', 'ROI seguridad'],
                ['Empleados', 'Interna', 'Media', 'Ambiente seguro, Capacitación', 'Política RRHH', 'Encuestas anuales'],
                ['Reguladores', 'Externa', 'Alta', 'Cumplimiento normativo', 'Leyes locales, ISO', 'Auditorías externas']
            ];
            generateExcelXML('FO-SI-037_Partes_Interesadas', $partes,
                ['Parte Interesada', 'Tipo', 'Importancia', 'Necesidades', 'Requisitos', 'Seguimiento'],
                'PARTES INTERESADAS Y REQUISITOS');
            break;

        // FO-SI-038: Objetivos de Seguridad
        case 'FO-SI-038':
            $objetivos = [
                ['OBJ-001', 'Reducir incidentes seguridad en 30%', 'Incidentes', '2025', 'CISO', '20 incidentes/año', 'En seguimiento', '45%'],
                ['OBJ-002', 'Capacitar 100% del personal', 'Concienciación', '2025', 'RRHH', '100% capacitado', 'En proceso', '75%'],
                ['OBJ-003', 'Disponibilidad 99.9% sistemas críticos', 'Disponibilidad', '2025', 'TI', '99.9% uptime', 'En seguimiento', '99.8%'],
                ['OBJ-004', 'Certificación ISO 27001', 'Cumplimiento', '2025', 'Gerencia', 'Certificado vigente', 'En proceso', '60%']
            ];
            generateExcelXML('FO-SI-038_Objetivos_Seguridad', $objetivos,
                ['ID', 'Objetivo', 'Categoría', 'Año', 'Responsable', 'Meta', 'Estado', 'Avance'],
                'OBJETIVOS DE SEGURIDAD');
            break;

        // FO-SI-039: Matriz Roles y Responsabilidades
        case 'FO-SI-039':
            $roles = [
                ['Gerente General', 'Aprobar política SGSI', 'Responsable', '', '', 'Revisión anual'],
                ['CISO', 'Gestionar SGSI', 'Responsable', 'Ejecutor', '', 'Dedicación 100%'],
                ['Gerente TI', 'Implementar controles técnicos', '', 'Responsable', 'Ejecutor', 'Coordinación con CISO'],
                ['Jefe RRHH', 'Capacitación personal', 'Consultado', 'Responsable', 'Ejecutor', 'Plan trimestral'],
                ['Auditor Interno', 'Auditorías internas', 'Informado', '', 'Responsable', 'Independiente']
            ];
            generateExcelXML('FO-SI-039_Matriz_Roles_Responsabilidades', $roles,
                ['Rol', 'Actividad', 'R-Responsable', 'A-Aprobador', 'C-Consultado', 'I-Informado', 'Observaciones'],
                'MATRIZ DE ROLES Y RESPONSABILIDADES');
            break;

        default:
            die('Formato no encontrado: ' . $code);
    }

} elseif ($type == 'procedimiento') {

    // =====================================================
    // GENERACIÓN DE PROCEDIMIENTOS
    // =====================================================

    $procedimientos_content = [
        'PROC-SI-001' => [
            'titulo' => 'PROCEDIMIENTO DE CONTROL DE DOCUMENTOS DEL SGSI',
            'objetivo' => 'Establecer los controles necesarios para asegurar que los documentos del Sistema de Gestión de Seguridad de la Información sean aprobados, revisados, actualizados y controlados adecuadamente.',
            'alcance' => 'Aplica a todos los documentos del SGSI incluyendo políticas, procedimientos, formatos, registros e información documentada requerida por ISO 27001:2022.',
            'responsabilidades' => '- Responsable del SGSI: Aprobar y mantener el procedimiento\n- Dueños de documentos: Crear y actualizar documentos\n- Comité de Documentación: Revisar y aprobar cambios',
            'procedimiento' => '1. CREACIÓN DE DOCUMENTOS\n1.1 Identificar necesidad de documento\n1.2 Asignar código según FO-SI-035\n1.3 Elaborar borrador usando plantilla estándar\n1.4 Incluir: objetivo, alcance, responsabilidades, descripción, registros\n\n2. REVISIÓN Y APROBACIÓN\n2.1 Revisar contenido técnico y forma\n2.2 Verificar alineación con ISO 27001\n2.3 Aprobar por autoridad competente\n2.4 Registrar en Listado Maestro\n\n3. DISTRIBUCIÓN Y COMUNICACIÓN\n3.1 Publicar en repositorio oficial\n3.2 Notificar a partes interesadas\n3.3 Capacitar en uso si aplica\n\n4. CONTROL DE CAMBIOS\n4.1 Solicitar cambio formalmente\n4.2 Evaluar impacto del cambio\n4.3 Actualizar versión\n4.4 Re-aprobar si es necesario\n\n5. RETENCIÓN Y DISPOSICIÓN\n5.1 Mantener documentos obsoletos por 3 años\n5.2 Marcar como "OBSOLETO"\n5.3 Eliminar según política retención',
            'registros' => '- FO-SI-035: Listado Maestro de Documentos\n- Registros de aprobación\n- Registro de distribución',
            'control' => 'A.5.37'
        ],
        'PROC-SI-002' => [
            'titulo' => 'PROCEDIMIENTO DE CONTROL DE REGISTROS',
            'objetivo' => 'Establecer los controles para la identificación, almacenamiento, protección, recuperación, tiempo de retención y disposición de registros del SGSI.',
            'alcance' => 'Todos los registros generados por el SGSI que evidencian conformidad con requisitos.',
            'responsabilidades' => '- Responsable SGSI: Definir tiempos de retención\n- Dueños de procesos: Generar y mantener registros\n- TI: Garantizar almacenamiento seguro',
            'procedimiento' => '1. IDENTIFICACIÓN\n- Código único\n- Fecha de creación\n- Responsable\n\n2. ALMACENAMIENTO\n- Físico: Archivadores con llave\n- Digital: Repositorio con control acceso\n\n3. PROTECCIÓN\n- Backups periódicos\n- Control de acceso\n- Cifrado si contiene datos sensibles\n\n4. RETENCIÓN\n- Registros ISO 27001: 3 años mínimo\n- Registros legales: Según normativa\n- Auditorías: 2 ciclos de certificación\n\n5. DISPOSICIÓN\n- Destrucción segura\n- Registro de eliminación',
            'registros' => 'Registro de destrucción de documentos',
            'control' => 'A.5.33'
        ],
        'PROC-SI-003' => [
            'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE ACTIVOS DE INFORMACIÓN',
            'objetivo' => 'Definir la metodología para identificar, inventariar, clasificar y gestionar los activos de información.',
            'alcance' => 'Todos los activos de información que soportan procesos dentro del alcance del SGSI.',
            'responsabilidades' => '- CISO: Supervisar inventario\n- Propietarios de activos: Clasificar y proteger\n- TI: Mantener inventario actualizado',
            'procedimiento' => '1. IDENTIFICACIÓN DE ACTIVOS\n- Hardware: Servidores, PCs, dispositivos red\n- Software: Aplicaciones, sistemas operativos\n- Datos: Bases de datos, archivos\n- Servicios: Cloud, comunicaciones\n- Personal: Usuarios, administradores\n- Instalaciones: Centros de datos, oficinas\n\n2. INVENTARIO\n- Usar FO-SI-003\n- Asignar código único\n- Registrar ubicación\n- Identificar propietario\n\n3. CLASIFICACIÓN CIA\n- Confidencialidad: 1-5\n- Integridad: 1-5\n- Disponibilidad: 1-5\n\n4. ETIQUETADO\n- Público, Interno, Confidencial, Estrictamente Confidencial\n\n5. REVISIÓN\n- Mensual: Activos críticos\n- Trimestral: Activos altos\n- Semestral: Resto',
            'registros' => 'FO-SI-003: Inventario de Activos',
            'control' => 'A.5.9'
        ],
        'PROC-SI-005' => [
            'titulo' => 'PROCEDIMIENTO DE ANÁLISIS Y EVALUACIÓN DE RIESGOS',
            'objetivo' => 'Definir la metodología para identificar, analizar, evaluar y tratar riesgos de seguridad de la información.',
            'alcance' => 'Todos los activos de información dentro del alcance del SGSI.',
            'responsabilidades' => '- CISO: Liderar análisis de riesgos\n- Propietarios activos: Participar en evaluación\n- Comité Riesgos: Aprobar tratamiento',
            'procedimiento' => '1. IDENTIFICACIÓN DE RIESGOS\n- Amenazas: Naturales, humanas, tecnológicas\n- Vulnerabilidades: Debilidades en activos\n- Eventos: Incidentes potenciales\n\n2. ANÁLISIS DE RIESGOS\n- Probabilidad (1-5):\n  1: Muy baja (< 5%)\n  2: Baja (5-25%)\n  3: Media (25-50%)\n  4: Alta (50-75%)\n  5: Muy alta (> 75%)\n\n- Impacto (1-5):\n  1: Insignificante\n  2: Menor\n  3: Moderado\n  4: Mayor\n  5: Catastrófico\n\n3. EVALUACIÓN\n- Riesgo Inherente = Probabilidad × Impacto\n- Matriz 5×5\n- Niveles:\n  1-3: Bajo\n  4-6: Medio\n  8-12: Alto\n  15-25: Crítico\n\n4. TRATAMIENTO\n- Mitigar: Implementar controles\n- Transferir: Seguros, outsourcing\n- Aceptar: Riesgos bajos\n- Evitar: Eliminar actividad\n\n5. MONITOREO\n- Trimestral: Riesgos críticos\n- Semestral: Riesgos altos\n- Anual: Resto',
            'registros' => '- FO-SI-004: Matriz de Riesgos\n- FO-SI-005: Plan Tratamiento',
            'control' => 'Cláusula 6.1.2'
        ],
        'PROC-SI-008' => [
            'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE INCIDENTES DE SEGURIDAD',
            'objetivo' => 'Establecer el proceso para identificar, reportar, evaluar, responder y aprender de incidentes de seguridad.',
            'alcance' => 'Todos los incidentes de seguridad que afecten la confidencialidad, integridad o disponibilidad de la información.',
            'responsabilidades' => '- CSIRT: Responder a incidentes\n- Usuarios: Reportar incidentes\n- CISO: Coordinar respuesta',
            'procedimiento' => '1. DETECCIÓN Y REPORTE\n- Identificar evento sospechoso\n- Reportar inmediatamente a CSIRT\n- Usar FO-SI-008\n\n2. CLASIFICACIÓN\n- Baja: Sin impacto operacional\n- Media: Impacto limitado\n- Alta: Impacto significativo\n- Crítica: Impacto severo\n\n3. CONTENCIÓN\n- Aislar sistemas afectados\n- Preservar evidencia\n- Limitar propagación\n\n4. ERRADICACIÓN\n- Eliminar causa raíz\n- Aplicar parches\n- Remover malware\n\n5. RECUPERACIÓN\n- Restaurar servicios\n- Verificar funcionamiento\n- Monitorear anomalías\n\n6. LECCIONES APRENDIDAS\n- Análisis post-incidente\n- Actualizar controles\n- Comunicar mejoras',
            'registros' => '- FO-SI-008: Registro de Incidentes\n- FO-SI-010: Informe de Incidente',
            'control' => 'A.5.24-A.5.28'
        ],
        'PROC-SI-010' => [
            'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE ACCESOS',
            'objetivo' => 'Establecer el proceso para otorgar, revisar, modificar y revocar derechos de acceso.',
            'alcance' => 'Todos los usuarios y sistemas dentro del alcance del SGSI.',
            'responsabilidades' => '- TI: Provisionar accesos\n- Gerentes: Aprobar accesos\n- RRHH: Notificar altas/bajas',
            'procedimiento' => '1. SOLICITUD DE ACCESO\n- FO-SI-020: Solicitud de Acceso\n- Justificación de negocio\n- Aprobación de gerente\n\n2. PROVISIÓN\n- Principio menor privilegio\n- Separación de funciones\n- Asignación de roles\n\n3. REVISIÓN\n- Mensual: Accesos privilegiados\n- Trimestral: Todos los accesos\n- Certificación por gerentes\n\n4. MODIFICACIÓN\n- Cambio de rol\n- Cambio de área\n- Nueva aprobación\n\n5. REVOCACIÓN\n- Baja de personal: Inmediato\n- Cambio rol: 24 horas\n- Inactividad: 90 días\n\n6. CONTROL\n- FO-SI-019: Matriz de Accesos\n- Logs de acceso\n- Alertas de anomalías',
            'registros' => '- FO-SI-019: Matriz Usuarios\n- FO-SI-020: Solicitudes\n- Logs de acceso',
            'control' => 'A.5.15-A.5.18'
        ],
        'PROC-SI-013' => [
            'titulo' => 'PROCEDIMIENTO DE RESPALDO Y RECUPERACIÓN DE INFORMACIÓN',
            'objetivo' => 'Definir las políticas y procedimientos para realizar respaldos de información y probar su recuperación.',
            'alcance' => 'Todos los sistemas y datos críticos del SGSI.',
            'responsabilidades' => '- TI: Ejecutar backups\n- DBA: Verificar integridad\n- CISO: Supervisar cumplimiento',
            'procedimiento' => '1. PLANIFICACIÓN\n- Identificar datos críticos\n- Definir RPO (Recovery Point Objective)\n- Definir RTO (Recovery Time Objective)\n\n2. EJECUCIÓN\n- Completo: Semanal\n- Incremental: Diario\n- Diferencial: Según criticidad\n\n3. ALMACENAMIENTO\n- Copia in-site: Servidor backup\n- Copia off-site: Nube/Sitio alterno\n- Regla 3-2-1\n\n4. VERIFICACIÓN\n- Automática post-backup\n- Manual mensual\n- Restauración de prueba trimestral\n\n5. RETENCIÓN\n- Backups diarios: 30 días\n- Backups semanales: 3 meses\n- Backups mensuales: 1 año\n\n6. RECUPERACIÓN\n- Procedimiento documentado\n- Pruebas trimestrales\n- Registro en FO-SI-031',
            'registros' => 'FO-SI-031: Registro de Respaldos',
            'control' => 'A.8.13'
        ],
        'PROC-SI-015' => [
            'titulo' => 'PROCEDIMIENTO DE AUDITORÍAS INTERNAS DEL SGSI',
            'objetivo' => 'Definir la planificación, ejecución, documentación y seguimiento de auditorías internas.',
            'alcance' => 'Todo el SGSI según alcance definido.',
            'responsabilidades' => '- Responsable SGSI: Planificar programa\n- Auditores internos: Ejecutar auditorías\n- Auditados: Facilitar evidencias',
            'procedimiento' => '1. PLANIFICACIÓN\n- FO-SI-012: Plan Anual\n- Definir alcance\n- Asignar auditores\n- Programar fechas\n\n2. PREPARACIÓN\n- Revisar documentación\n- Preparar FO-SI-011: Checklist\n- Notificar a auditados\n\n3. EJECUCIÓN\n- Reunión apertura\n- Revisión documental\n- Entrevistas\n- Inspección física\n- Reunión cierre\n\n4. REPORTE\n- FO-SI-013: Informe\n- Hallazgos\n- No conformidades\n- Oportunidades mejora\n\n5. SEGUIMIENTO\n- FO-SI-014: No Conformidades\n- FO-SI-015: Acciones Correctivas\n- Verificar eficacia',
            'registros' => '- FO-SI-012: Plan\n- FO-SI-013: Informe\n- FO-SI-014: NC',
            'control' => 'Cláusula 9.2'
        ]
    ];

    // Generar procedimientos restantes con estructura base
    $procedimientos_basicos = [
        'PROC-SI-004' => 'Clasificación y Etiquetado de Información',
        'PROC-SI-006' => 'Tratamiento de Riesgos',
        'PROC-SI-007' => 'Gestión de Cambios',
        'PROC-SI-009' => 'Gestión de Vulnerabilidades Técnicas',
        'PROC-SI-011' => 'Alta, Baja y Cambio de Personal',
        'PROC-SI-012' => 'Gestión de Proveedores y Terceros',
        'PROC-SI-014' => 'Continuidad del Negocio',
        'PROC-SI-016' => 'Revisión por la Dirección',
        'PROC-SI-017' => 'Acciones Correctivas',
        'PROC-SI-018' => 'Capacitación y Concientización',
        'PROC-SI-019' => 'Desarrollo Seguro de Software',
        'PROC-SI-020' => 'Uso de Criptografía',
        'PROC-SI-021' => 'Protección contra Malware',
        'PROC-SI-022' => 'Monitoreo y Registro de Eventos',
        'PROC-SI-023' => 'Gestión de Configuración',
        'PROC-SI-024' => 'Transferencia Segura de Información',
        'PROC-SI-025' => 'Cumplimiento Legal y Regulatorio'
    ];

    if (isset($procedimientos_content[$code])) {
        $proc = $procedimientos_content[$code];

        $content = '
        <div style="margin-bottom: 30px;">
            <table width="100%" border="1" style="border-collapse: collapse;">
                <tr>
                    <td colspan="2" style="background: #00994d; color: white; padding: 10px; text-align: center;">
                        <strong>' . $company['company_name'] . '</strong>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px;"><strong>Código:</strong> ' . $code . '</td>
                    <td style="padding: 10px;"><strong>Versión:</strong> 1.0</td>
                </tr>
                <tr>
                    <td style="padding: 10px;"><strong>Fecha:</strong> ' . date('Y-m-d') . '</td>
                    <td style="padding: 10px;"><strong>Control:</strong> ' . $proc['control'] . '</td>
                </tr>
            </table>
        </div>

        <h2>1. OBJETIVO</h2>
        <p>' . nl2br($proc['objetivo']) . '</p>

        <h2>2. ALCANCE</h2>
        <p>' . nl2br($proc['alcance']) . '</p>

        <h2>3. RESPONSABILIDADES</h2>
        <p>' . nl2br($proc['responsabilidades']) . '</p>

        <h2>4. PROCEDIMIENTO</h2>
        <p>' . nl2br($proc['procedimiento']) . '</p>

        <h2>5. REGISTROS</h2>
        <p>' . nl2br($proc['registros']) . '</p>

        <div style="margin-top: 50px;">
            <table width="100%" border="1" style="border-collapse: collapse;">
                <tr style="background: #f0f0f0;">
                    <th style="padding: 10px;">Elaborado por</th>
                    <th style="padding: 10px;">Revisado por</th>
                    <th style="padding: 10px;">Aprobado por</th>
                </tr>
                <tr>
                    <td style="padding: 30px; text-align: center;">_________________</td>
                    <td style="padding: 30px; text-align: center;">_________________</td>
                    <td style="padding: 30px; text-align: center;">_________________</td>
                </tr>
            </table>
        </div>';

        generateWordDoc($code . '_' . str_replace(' ', '_', $proc['titulo']), $content, $proc['titulo']);

    } elseif (isset($procedimientos_basicos[$code])) {

        $content = '
        <div style="margin-bottom: 30px;">
            <table width="100%" border="1" style="border-collapse: collapse;">
                <tr>
                    <td colspan="2" style="background: #00994d; color: white; padding: 10px; text-align: center;">
                        <strong>' . $company['company_name'] . '</strong>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px;"><strong>Código:</strong> ' . $code . '</td>
                    <td style="padding: 10px;"><strong>Versión:</strong> 1.0</td>
                </tr>
            </table>
        </div>

        <h2>' . strtoupper($procedimientos_basicos[$code]) . '</h2>

        <h3>1. OBJETIVO</h3>
        <p>Establecer las directrices para ' . strtolower($procedimientos_basicos[$code]) . ' en el marco del SGSI.</p>

        <h3>2. ALCANCE</h3>
        <p>Aplica a todos los procesos y sistemas dentro del alcance del SGSI según ISO 27001:2022.</p>

        <h3>3. RESPONSABILIDADES</h3>
        <ul>
            <li>Responsable del SGSI: Supervisar implementación</li>
            <li>Áreas involucradas: Ejecutar actividades</li>
            <li>Auditor interno: Verificar cumplimiento</li>
        </ul>

        <h3>4. DESARROLLO</h3>
        <p>Este documento debe ser personalizado según las necesidades específicas de su organización, considerando:</p>
        <ul>
            <li>Contexto organizacional</li>
            <li>Requisitos aplicables</li>
            <li>Riesgos identificados</li>
            <li>Controles implementados</li>
        </ul>

        <h3>5. REGISTROS</h3>
        <p>Registros asociados a este procedimiento según FO-SI-035.</p>

        <div style="margin-top: 50px;">
            <table width="100%" border="1" style="border-collapse: collapse;">
                <tr style="background: #f0f0f0;">
                    <th style="padding: 10px;">Elaborado</th>
                    <th style="padding: 10px;">Revisado</th>
                    <th style="padding: 10px;">Aprobado</th>
                </tr>
                <tr>
                    <td style="padding: 30px; text-align: center;">_________________</td>
                    <td style="padding: 30px; text-align: center;">_________________</td>
                    <td style="padding: 30px; text-align: center;">_________________</td>
                </tr>
            </table>
        </div>';

        generateWordDoc($code . '_' . str_replace(' ', '_', $procedimientos_basicos[$code]), $content, $procedimientos_basicos[$code]);
    } else {
        die('Procedimiento no encontrado: ' . $code);
    }
}

$conn->close();
?>
