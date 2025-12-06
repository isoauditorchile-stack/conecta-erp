<?php
/**
 * AUDITOR PRO - Sistema de Exportación de Documentos
 * Genera todos los documentos del Sistema de Gestión ISO 27001:2022
 *
 * Soporta:
 * - 22 Políticas de Seguridad
 * - 10 Procedimientos
 * - 6 Inventarios
 * - 6 Formatos de Riesgos
 * - 5 Formatos de Controles
 * - 5 Formatos de Incidentes
 * - 6 Formatos de Auditorías
 * - 4 Formatos de Capacitación
 * - 5 Formatos de Monitoreo
 * - 5 Formatos de Continuidad
 * - 4 Formatos de Proveedores
 * - 7 Reportes de Certificación
 *
 * Formatos de exportación: DOCX, XLSX, PDF
 *
 * @package AuditorPRO
 * @version 2.0
 * @author AUDITOR PRO Team
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Cargar autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Obtener parámetros
$category = isset($_GET['category']) ? $_GET['category'] : 'politicas';
$code = isset($_GET['code']) ? $_GET['code'] : '';
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'docx';

if (empty($code)) {
    die('Código de documento no especificado');
}

// =====================================================
// CATÁLOGO COMPLETO DE DOCUMENTOS
// =====================================================

$documentos = [

    // ========== PROCEDIMIENTOS (10) ==========
    'procedimientos' => [
        'PROC-SI-001' => [
            'nombre' => 'Procedimiento de Gestión de Cambios',
            'version' => '1.0',
            'fecha' => date('d/m/Y'),
            'objetivo' => 'Establecer el proceso para gestionar cambios en sistemas de información minimizando riesgos e interrupciones.',
            'alcance' => 'Todos los cambios en infraestructura, aplicaciones, redes y configuraciones de producción.',
            'responsable' => 'Gerente de TI',
            'frecuencia' => 'Continuo',
            'procedimiento' => [
                '1. SOLICITUD DE CAMBIO' => [
                    '1.1. El solicitante completa formulario RFC (Request for Change)',
                    '1.2. Indica justificación, impacto, riesgos estimados',
                    '1.3. Adjunta documentación técnica si aplica',
                    '1.4. Envía a buzón cambios@empresa.com'
                ],
                '2. EVALUACIÓN' => [
                    '2.1. Change Manager recibe y registra solicitud',
                    '2.2. Clasifica cambio (Estándar, Normal, Emergencia)',
                    '2.3. Asigna a técnico para análisis de impacto',
                    '2.4. Técnico completa evaluación en 24 horas'
                ],
                '3. APROBACIÓN CAB' => [
                    '3.1. Change Manager agenda en próxima reunión CAB',
                    '3.2. CAB revisa: impacto, riesgos, plan de implementación',
                    '3.3. CAB decide: Aprobar, Rechazar o Diferir',
                    '3.4. Se notifica decisión al solicitante'
                ],
                '4. PLANIFICACIÓN' => [
                    '4.1. Se programa ventana de cambio',
                    '4.2. Se documenta plan paso a paso',
                    '4.3. Se define plan de rollback',
                    '4.4. Se realizan backups pre-cambio'
                ],
                '5. IMPLEMENTACIÓN' => [
                    '5.1. Técnico ejecuta cambio según plan',
                    '5.2. Documenta cada paso realizado',
                    '5.3. Realiza pruebas de validación',
                    '5.4. Confirma éxito o ejecuta rollback'
                ],
                '6. CIERRE' => [
                    '6.1. Se valida que objetivos fueron cumplidos',
                    '6.2. Se documenta resultado final',
                    '6.3. Se actualizan documentos técnicos',
                    '6.4. Se cierra RFC en sistema'
                ]
            ],
            'registros' => [
                'FO-SI-010: Formulario de Solicitud de Cambio',
                'FO-SI-011: Acta de Reunión CAB',
                'FO-SI-012: Reporte de Implementación de Cambio'
            ],
            'control_iso' => 'A.8.32 - Gestión de cambios'
        ],

        'PROC-SI-002' => [
            'nombre' => 'Procedimiento de Gestión de Incidentes de Seguridad',
            'version' => '1.0',
            'fecha' => date('d/m/Y'),
            'objetivo' => 'Detectar, responder y resolver incidentes de seguridad de forma rápida y efectiva.',
            'alcance' => 'Todos los incidentes que afecten confidencialidad, integridad o disponibilidad de la información.',
            'responsable' => 'CISO',
            'frecuencia' => 'Continuo (24/7)',
            'procedimiento' => [
                '1. DETECCIÓN Y REPORTE' => [
                    '1.1. Cualquier persona detecta posible incidente',
                    '1.2. Reporta inmediatamente a: security@empresa.com o ext. 911',
                    '1.3. Proporciona: qué, cuándo, dónde, quién, cómo',
                    '1.4. No intenta resolver por cuenta propia'
                ],
                '2. REGISTRO Y CLASIFICACIÓN' => [
                    '2.1. Analista de seguridad recibe reporte',
                    '2.2. Registra en sistema de tickets',
                    '2.3. Clasifica severidad: Crítico, Alto, Medio, Bajo',
                    '2.4. Asigna a equipo de respuesta según severidad'
                ],
                '3. CONTENCIÓN' => [
                    '3.1. Aislar sistemas afectados',
                    '3.2. Prevenir propagación del incidente',
                    '3.3. Preservar evidencia forense',
                    '3.4. Documentar todas las acciones'
                ],
                '4. ERRADICACIÓN' => [
                    '4.1. Identificar causa raíz',
                    '4.2. Eliminar malware/accesos no autorizados',
                    '4.3. Cerrar vulnerabilidades explotadas',
                    '4.4. Aplicar parches necesarios'
                ],
                '5. RECUPERACIÓN' => [
                    '5.1. Restaurar sistemas desde backups limpios',
                    '5.2. Validar integridad de datos',
                    '5.3. Monitorear comportamiento post-recuperación',
                    '5.4. Confirmar retorno a operación normal'
                ],
                '6. LECCIONES APRENDIDAS' => [
                    '6.1. Reunión post-incidente (dentro de 48h)',
                    '6.2. Documentar timeline completo',
                    '6.3. Identificar mejoras en controles',
                    '6.4. Actualizar procedimientos y políticas'
                ],
                '7. COMUNICACIÓN' => [
                    '7.1. Notificar a gerencia según severidad',
                    '7.2. Informar a afectados si corresponde',
                    '7.3. Notificar a autoridades si es legal requirement',
                    '7.4. Coordinar mensajes con área de comunicaciones'
                ]
            ],
            'registros' => [
                'FO-SI-008: Registro de Incidente de Seguridad',
                'FO-SI-009: Análisis de Causa Raíz',
                'FO-SI-025: Informe Post-Incidente'
            ],
            'control_iso' => 'A.5.24 - A.5.28 - Gestión de incidentes'
        ],

        'PROC-SI-003' => [
            'nombre' => 'Procedimiento de Gestión de Vulnerabilidades',
            'version' => '1.0',
            'fecha' => date('d/m/Y'),
            'objetivo' => 'Identificar, evaluar y remediar vulnerabilidades de seguridad de manera oportuna.',
            'alcance' => 'Todos los sistemas, aplicaciones, redes y endpoints.',
            'responsable' => 'Equipo de Seguridad',
            'frecuencia' => 'Semanal (escaneos), Continuo (remediación)',
            'procedimiento' => [
                '1. IDENTIFICACIÓN' => [
                    '1.1. Ejecutar escaneos automatizados semanalmente',
                    '1.2. Revisar boletines de seguridad de vendors',
                    '1.3. Monitorear feeds de vulnerabilidades (CVE)',
                    '1.4. Documentar vulnerabilidades encontradas'
                ],
                '2. CLASIFICACIÓN' => [
                    '2.1. Asignar puntuación CVSS',
                    '2.2. Clasificar severidad: Crítica, Alta, Media, Baja',
                    '2.3. Evaluar factores: exposición, criticidad activo, exploit público',
                    '2.4. Priorizar según riesgo real'
                ],
                '3. ASIGNACIÓN' => [
                    '3.1. Asignar responsable de remediación',
                    '3.2. Definir SLA según severidad:',
                    '    - Crítica: 24 horas',
                    '    - Alta: 7 días',
                    '    - Media: 30 días',
                    '    - Baja: 90 días',
                    '3.3. Crear ticket de seguimiento'
                ],
                '4. REMEDIACIÓN' => [
                    '4.1. Aplicar parche si está disponible',
                    '4.2. Probar parche en ambiente QA',
                    '4.3. Implementar en producción en ventana de cambio',
                    '4.4. Si no hay parche: implementar workaround'
                ],
                '5. VERIFICACIÓN' => [
                    '5.1. Re-escanear sistema remediado',
                    '5.2. Confirmar que vulnerabilidad fue corregida',
                    '5.3. Documentar resultado',
                    '5.4. Cerrar ticket'
                ],
                '6. EXCEPCIONES' => [
                    '6.1. Si no se puede remediar: documentar justificación',
                    '6.2. Implementar controles compensatorios',
                    '6.3. Obtener aprobación de CISO para aceptar riesgo',
                    '6.4. Revisar excepción trimestralmente'
                ],
                '7. REPORTEO' => [
                    '7.1. Dashboard en tiempo real de vulnerabilidades abiertas',
                    '7.2. Reporte semanal a TI',
                    '7.3. Reporte mensual a CISO',
                    '7.4. Métricas: tiempo medio de remediación, % cumplimiento SLA'
                ]
            ],
            'registros' => [
                'FO-SI-030: Registro de Vulnerabilidades',
                'FO-SI-031: Plan de Remediación',
                'FO-SI-032: Reporte de Escaneo de Vulnerabilidades'
            ],
            'control_iso' => 'A.8.8 - Gestión de vulnerabilidades técnicas'
        ],

        'PROC-SI-004' => [
            'nombre' => 'Procedimiento de Auditorías Internas',
            'version' => '1.0',
            'fecha' => date('d/m/Y'),
            'objetivo' => 'Verificar el cumplimiento y efectividad del Sistema de Gestión de Seguridad de la Información.',
            'alcance' => 'Todos los procesos, controles y áreas del SGSI.',
            'responsable' => 'Auditor Líder Interno',
            'frecuencia' => 'Anual (mínimo)',
            'procedimiento' => [
                '1. PLANIFICACIÓN' => [
                    '1.1. Definir alcance de auditoría',
                    '1.2. Asignar equipo auditor (independiente del área auditada)',
                    '1.3. Programar fechas con áreas a auditar',
                    '1.4. Preparar lista de verificación basada en ISO 27001'
                ],
                '2. REUNIÓN DE APERTURA' => [
                    '2.1. Presentar equipo auditor',
                    '2.2. Confirmar alcance y objetivos',
                    '2.3. Explicar metodología',
                    '2.4. Acordar logística'
                ],
                '3. EJECUCIÓN' => [
                    '3.1. Revisar documentación',
                    '3.2. Entrevistar personal',
                    '3.3. Observar procesos',
                    '3.4. Revisar registros y evidencias',
                    '3.5. Documentar hallazgos'
                ],
                '4. CLASIFICACIÓN DE HALLAZGOS' => [
                    '4.1. No Conformidad Mayor: Incumplimiento grave',
                    '4.2. No Conformidad Menor: Incumplimiento aislado',
                    '4.3. Observación: Oportunidad de mejora',
                    '4.4. Fortaleza: Buena práctica encontrada'
                ],
                '5. REUNIÓN DE CIERRE' => [
                    '5.1. Presentar hallazgos preliminares',
                    '5.2. Aclarar dudas',
                    '5.3. Acordar plazos de corrección',
                    '5.4. Agradecer colaboración'
                ],
                '6. INFORME' => [
                    '6.1. Redactar informe detallado',
                    '6.2. Incluir: hallazgos, evidencias, recomendaciones',
                    '6.3. Enviar a auditado y dirección',
                    '6.4. Plazo: 5 días hábiles post-auditoría'
                ],
                '7. SEGUIMIENTO' => [
                    '7.1. Área auditada presenta plan de acción',
                    '7.2. Implementa correcciones',
                    '7.3. Auditor verifica efectividad',
                    '7.4. Cierra hallazgos una vez corregidos'
                ]
            ],
            'registros' => [
                'FO-SI-040: Plan Anual de Auditorías',
                'FO-SI-041: Lista de Verificación de Auditoría',
                'FO-SI-042: Informe de Auditoría Interna',
                'FO-SI-043: Registro de No Conformidades'
            ],
            'control_iso' => 'Cláusula 9.2 - Auditoría interna'
        ],

        'PROC-SI-005' => [
            'nombre' => 'Procedimiento de Revisión por la Dirección',
            'version' => '1.0',
            'fecha' => date('d/m/Y'),
            'objetivo' => 'Asegurar que la dirección revise periódicamente el SGSI para garantizar su conveniencia, adecuación y eficacia.',
            'alcance' => 'Todo el Sistema de Gestión de Seguridad de la Información.',
            'responsable' => 'CISO / Alta Dirección',
            'frecuencia' => 'Trimestral',
            'procedimiento' => [
                '1. PREPARACIÓN' => [
                    '1.1. CISO recopila información de entrada:',
                    '    - Estado de acciones de revisiones previas',
                    '    - Cambios en contexto externo/interno',
                    '    - Feedback de partes interesadas',
                    '    - Resultados de auditorías',
                    '    - Resultados de análisis de riesgos',
                    '    - Incidentes de seguridad',
                    '    - Desempeño de controles',
                    '    - Cumplimiento de objetivos',
                    '    - Oportunidades de mejora',
                    '1.2. Prepara presentación ejecutiva',
                    '1.3. Convoca a comité de dirección'
                ],
                '2. REVISIÓN' => [
                    '2.1. CISO presenta informe de gestión',
                    '2.2. Se discuten indicadores clave (KPIs)',
                    '2.3. Se analizan incidentes significativos',
                    '2.4. Se revisan riesgos emergentes',
                    '2.5. Se evalúa efectividad del SGSI'
                ],
                '3. DECISIONES' => [
                    '3.1. Dirección determina:',
                    '    - Necesidad de cambios en SGSI',
                    '    - Adecuación de políticas y objetivos',
                    '    - Necesidad de recursos adicionales',
                    '    - Cambios en evaluación de riesgos',
                    '3.2. Se priorizan acciones de mejora',
                    '3.3. Se asignan responsables y plazos'
                ],
                '4. ACTA' => [
                    '4.1. Secretario documenta:',
                    '    - Asistentes',
                    '    - Información revisada',
                    '    - Decisiones tomadas',
                    '    - Acciones asignadas',
                    '4.2. Circula acta para aprobación',
                    '4.3. Distribuye a participantes'
                ],
                '5. SEGUIMIENTO' => [
                    '5.1. CISO monitorea ejecución de acciones',
                    '5.2. Envía reportes de avance mensualmente',
                    '5.3. Escala a dirección si hay retrasos',
                    '5.4. Cierra acciones completadas'
                ]
            ],
            'registros' => [
                'FO-SI-050: Informe de Revisión por la Dirección',
                'FO-SI-051: Acta de Reunión de Revisión',
                'FO-SI-052: Plan de Acciones de Mejora'
            ],
            'control_iso' => 'Cláusula 9.3 - Revisión por la dirección'
        ],

        'PROC-SI-006' => [
            'nombre' => 'Procedimiento de Control de Documentos',
            'version' => '1.0',
            'fecha' => date('d/m/Y'),
            'objetivo' => 'Asegurar que los documentos del SGSI estén controlados, actualizados y disponibles.',
            'alcance' => 'Políticas, procedimientos, instructivos, formatos y documentos externos del SGSI.',
            'responsable' => 'Coordinador de Calidad',
            'frecuencia' => 'Continuo',
            'procedimiento' => [
                '1. CREACIÓN' => [
                    '1.1. Identificar necesidad de nuevo documento',
                    '1.2. Usar plantilla corporativa',
                    '1.3. Asignar código único según nomenclatura',
                    '1.4. Completar encabezado con metadatos'
                ],
                '2. REVISIÓN' => [
                    '2.1. Propietario del proceso revisa borrador',
                    '2.2. CISO revisa aspectos de seguridad',
                    '2.3. Legal revisa si hay implicaciones legales',
                    '2.4. Se realizan ajustes según comentarios'
                ],
                '3. APROBACIÓN' => [
                    '3.1. Documento se envía a aprobador designado',
                    '3.2. Aprobador firma/autoriza',
                    '3.3. Se registra en Matriz de Documentos',
                    '3.4. Se asigna versión 1.0'
                ],
                '4. DISTRIBUCIÓN' => [
                    '4.1. Publicar en repositorio SharePoint',
                    '4.2. Notificar a usuarios vía email',
                    '4.3. Capacitar si es documento crítico',
                    '4.4. Retirar versión anterior'
                ],
                '5. ACTUALIZACIÓN' => [
                    '5.1. Propietario identifica necesidad de cambio',
                    '5.2. Solicita modificación',
                    '5.3. Se repite proceso de revisión y aprobación',
                    '5.4. Se incrementa número de versión'
                ],
                '6. CONTROL DE CAMBIOS' => [
                    '6.1. Documentar cambios en sección "Historial"',
                    '6.2. Incluir: versión, fecha, descripción, autor',
                    '6.3. Mantener versiones anteriores archivadas',
                    '6.4. No eliminar versiones previas'
                ],
                '7. DOCUMENTOS OBSOLETOS' => [
                    '7.1. Marcar claramente como OBSOLETO',
                    '7.2. Mover a carpeta de archivos',
                    '7.3. Retener según política de retención',
                    '7.4. Destruir después del período'
                ],
                '8. DOCUMENTOS EXTERNOS' => [
                    '8.1. Registrar en matriz',
                    '8.2. Verificar vigencia periódicamente',
                    '8.3. Actualizar cuando haya nuevas versiones',
                    '8.4. Identificar claramente como EXTERNO'
                ]
            ],
            'registros' => [
                'FO-SI-060: Matriz de Documentos',
                'FO-SI-061: Solicitud de Creación/Modificación de Documento',
                'FO-SI-062: Lista de Distribución'
            ],
            'control_iso' => 'Cláusula 7.5 - Información documentada'
        ],

        'PROC-SI-007' => [
            'nombre' => 'Procedimiento de Control de Registros',
            'version' => '1.0',
            'fecha' => date('d/m/Y'),
            'objetivo' => 'Asegurar la identificación, almacenamiento, protección, recuperación, retención y disposición de registros.',
            'alcance' => 'Todos los registros que evidencian conformidad con requisitos del SGSI.',
            'responsable' => 'Coordinador de Calidad',
            'frecuencia' => 'Continuo',
            'procedimiento' => [
                '1. IDENTIFICACIÓN' => [
                    '1.1. Registros llevan código único',
                    '1.2. Incluyen: fecha, responsable, firma',
                    '1.3. Son identificables y trazables',
                    '1.4. Se relacionan con proceso origen'
                ],
                '2. ALMACENAMIENTO' => [
                    '2.1. DIGITAL:',
                    '    - SharePoint con permisos controlados',
                    '    - Estructura de carpetas estandarizada',
                    '    - Nomenclatura consistente',
                    '    - Backup automático',
                    '2.2. FÍSICO:',
                    '    - Archivadores con llave',
                    '    - Identificación externa visible',
                    '    - Área de archivos controlada',
                    '    - Condiciones ambientales adecuadas'
                ],
                '3. PROTECCIÓN' => [
                    '3.1. Acceso solo a personal autorizado',
                    '3.2. Cifrado para registros sensibles',
                    '3.3. Protección contra daño/deterioro',
                    '3.4. Control de versiones'
                ],
                '4. RECUPERACIÓN' => [
                    '4.1. Sistema de búsqueda eficiente',
                    '4.2. Índices y metadatos',
                    '4.3. Tiempo de recuperación < 24 horas',
                    '4.4. Procedimiento documentado'
                ],
                '5. RETENCIÓN' => [
                    '5.1. Según Tabla de Retención:',
                    '    - Registros de incidentes: 7 años',
                    '    - Registros de auditoría: 7 años',
                    '    - Registros de capacitación: 5 años',
                    '    - Logs de sistemas: 1 año',
                    '    - Evaluaciones de riesgo: 5 años',
                    '5.2. Revisar anualmente necesidad de retención',
                    '5.3. Extender si hay litigio pendiente'
                ],
                '6. DISPOSICIÓN' => [
                    '6.1. Al cumplir período de retención:',
                    '    - Revisar si aún se necesita',
                    '    - Obtener aprobación para eliminación',
                    '    - Destruir de forma segura',
                    '    - Documentar destrucción',
                    '6.2. DIGITAL: Borrado seguro, sobrescritura',
                    '6.3. FÍSICO: Trituración cruzada'
                ],
                '7. AUDITORÍA' => [
                    '7.1. Revisión trimestral de cumplimiento',
                    '7.2. Verificar completitud de registros',
                    '7.3. Validar condiciones de almacenamiento',
                    '7.4. Reportar hallazgos'
                ]
            ],
            'registros' => [
                'FO-SI-070: Tabla de Retención de Registros',
                'FO-SI-071: Inventario de Registros',
                'FO-SI-072: Certificado de Destrucción'
            ],
            'control_iso' => 'Cláusula 7.5 - Información documentada'
        ],

        'PROC-SI-008' => [
            'nombre' => 'Procedimiento de Gestión de Activos',
            'version' => '1.0',
            'fecha' => date('d/m/Y'),
            'objetivo' => 'Identificar, valorar, clasificar y proteger los activos de información de la organización.',
            'alcance' => 'Todos los activos de información: hardware, software, datos, personal, servicios.',
            'responsable' => 'Gestior de Activos / CISO',
            'frecuencia' => 'Continuo, revisión trimestral',
            'procedimiento' => [
                '1. IDENTIFICACIÓN' => [
                    '1.1. Identificar todos los activos en alcance del SGSI',
                    '1.2. Asignar código único a cada activo',
                    '1.3. Categorizar: Hardware, Software, Datos, Servicios, Personal',
                    '1.4. Registrar en Inventario de Activos'
                ],
                '2. ASIGNACIÓN DE PROPIETARIO' => [
                    '2.1. Designar propietario para cada activo',
                    '2.2. Propietario es responsable de:',
                    '    - Clasificar el activo',
                    '    - Definir controles',
                    '    - Autorizar accesos',
                    '    - Revisar periódicamente',
                    '2.3. Notificar a propietario su designación'
                ],
                '3. VALORACIÓN' => [
                    '3.1. Propietario evalúa valor del activo considerando:',
                    '    - Costo de reposición',
                    '    - Impacto de pérdida de confidencialidad',
                    '    - Impacto de pérdida de integridad',
                    '    - Impacto de pérdida de disponibilidad',
                    '    - Valor de negocio',
                    '3.2. Asigna valoración: Crítico, Alto, Medio, Bajo',
                    '3.3. Documenta justificación'
                ],
                '4. CLASIFICACIÓN' => [
                    '4.1. Clasificar información según sensibilidad:',
                    '    - Pública',
                    '    - Interna',
                    '    - Confidencial',
                    '    - Estrictamente Confidencial',
                    '4.2. Etiquetar activo con su clasificación',
                    '4.3. Aplicar controles según clasificación'
                ],
                '5. PROTECCIÓN' => [
                    '5.1. Implementar controles físicos, técnicos y administrativos',
                    '5.2. Según valoración y clasificación',
                    '5.3. Documentar controles aplicados',
                    '5.4. Verificar efectividad'
                ],
                '6. USO ACEPTABLE' => [
                    '6.1. Definir uso aceptable del activo',
                    '6.2. Comunicar a usuarios',
                    '6.3. Capacitar si es necesario',
                    '6.4. Monitorear cumplimiento'
                ],
                '7. ACTUALIZACIÓN DE INVENTARIO' => [
                    '7.1. Actualizar inventario ante:',
                    '    - Adquisición de nuevos activos',
                    '    - Baja de activos',
                    '    - Cambios en activos existentes',
                    '    - Cambios de propietario',
                    '7.2. Plazo máximo: 48 horas',
                    '7.3. Auditoría trimestral del inventario'
                ],
                '8. RETIRO DE ACTIVOS' => [
                    '8.1. Solicitar aprobación de propietario',
                    '8.2. Eliminar datos de forma segura',
                    '8.3. Documentar disposición final',
                    '8.4. Actualizar inventario'
                ]
            ],
            'registros' => [
                'FO-SI-080: Inventario de Activos de Información',
                'FO-SI-081: Valoración y Clasificación de Activos',
                'FO-SI-082: Acta de Entrega/Retiro de Activos'
            ],
            'control_iso' => 'A.5.9, A.5.10, A.5.11 - Gestión de activos'
        ],

        'PROC-SI-009' => [
            'nombre' => 'Procedimiento de Alta y Baja de Personal',
            'version' => '1.0',
            'fecha' => date('d/m/Y'),
            'objetivo' => 'Gestionar el ciclo de vida de accesos y responsabilidades de empleados.',
            'alcance' => 'Todos los empleados, contratistas y terceros con acceso a sistemas.',
            'responsable' => 'Recursos Humanos / TI',
            'frecuencia' => 'Continuo',
            'procedimiento' => [
                '1. ALTA DE PERSONAL' => [
                    '1.1. RR.HH. notifica ingreso de nuevo empleado',
                    '1.2. Gerente define perfil de accesos necesarios',
                    '1.3. TI crea cuentas de usuario:',
                    '    - Email corporativo',
                    '    - Acceso a sistemas según rol',
                    '    - Asignación de equipos',
                    '1.4. Seguridad agenda capacitación de inducción',
                    '1.5. Empleado firma:',
                    '    - NDA (Acuerdo de Confidencialidad)',
                    '    - AUP (Política de Uso Aceptable)',
                    '    - Acta de Recepción de Activos',
                    '1.6. RR.HH. archiva documentos firmados'
                ],
                '2. CAPACITACIÓN INICIAL' => [
                    '2.1. Día 1: Capacitación de Seguridad obligatoria',
                    '2.2. Revisar políticas clave',
                    '2.3. Explicar responsabilidades',
                    '2.4. Demostrar cómo reportar incidentes',
                    '2.5. Evaluación de comprensión (80% aprobación)',
                    '2.6. Entregar credencial de acceso'
                ],
                '3. CAMBIO DE ROL' => [
                    '3.1. RR.HH. notifica cambio de posición',
                    '3.2. Nuevo gerente define accesos requeridos',
                    '3.3. TI ajusta permisos:',
                    '    - Revoca accesos del rol anterior',
                    '    - Otorga accesos del nuevo rol',
                    '    - Documenta cambios',
                    '3.4. Devuelve activos no necesarios',
                    '3.5. Firma nueva acta de activos si aplica'
                ],
                '4. BAJA DE PERSONAL' => [
                    '4.1. RR.HH. notifica término de relación laboral',
                    '4.2. Se programa fecha de salida',
                    '4.3. EN ÚLTIMO DÍA LABORAL:',
                    '    - 9:00 AM: TI deshabilita cuenta de email',
                    '    - 10:00 AM: Entrevista de salida con RR.HH.',
                    '    - 11:00 AM: Devolución de activos:',
                    '        * Laptop',
                    '        * Celular corporativo',
                    '        * Llaves',
                    '        * Tarjetas de acceso',
                    '        * Cualquier documentación',
                    '    - 12:00 PM: Firma Acta de Entrega',
                    '    - 13:00 PM: TI revoca todos los accesos',
                    '    - 14:00 PM: Seguridad desactiva credenciales físicas',
                    '4.4. Validar que no queden copias de información',
                    '4.5. Eliminar accesos VPN, sistemas, aplicaciones'
                ],
                '5. VERIFICACIÓN POST-BAJA' => [
                    '5.1. TI verifica que todos los accesos fueron revocados',
                    '5.2. Seguridad confirma desactivación de credenciales',
                    '5.3. RR.HH. archiva documentación de salida',
                    '5.4. Se actualiza inventario de activos',
                    '5.5. Se notifica a áreas relevantes'
                ],
                '6. CASO ESPECIAL: BAJA CONFLICTIVA' => [
                    '6.1. RR.HH. notifica a Seguridad y TI con antelación',
                    '6.2. Accesos se revocan INMEDIATAMENTE al notificar',
                    '6.3. Escort de seguridad durante recolección de pertenencias',
                    '6.4. No se permite acceso a sistemas',
                    '6.5. Monitoreo intensificado de intentos de acceso',
                    '6.6. Cambiar contraseñas de sistemas compartidos'
                ]
            ],
            'registros' => [
                'FO-SI-090: Checklist de Alta de Usuario',
                'FO-SI-091: Acta de Entrega de Activos',
                'FO-SI-092: Checklist de Baja de Usuario',
                'FO-SI-093: Acta de Devolución de Activos'
            ],
            'control_iso' => 'A.6.1 - A.6.6 - Seguridad en RR.HH.'
        ],

        'PROC-SI-010' => [
            'nombre' => 'Procedimiento de Gestión de Accesos',
            'version' => '1.0',
            'fecha' => date('d/m/Y'),
            'objetivo' => 'Controlar la asignación, revisión y revocación de accesos a sistemas y datos.',
            'alcance' => 'Todos los sistemas, aplicaciones, bases de datos y recursos de red.',
            'responsable' => 'Administrador de Accesos / TI',
            'frecuencia' => 'Continuo, revisión trimestral',
            'procedimiento' => [
                '1. SOLICITUD DE ACCESO' => [
                    '1.1. Usuario o su gerente solicita acceso vía formulario',
                    '1.2. Formulario debe incluir:',
                    '    - Sistema/recurso solicitado',
                    '    - Nivel de acceso requerido',
                    '    - Justificación de negocio',
                    '    - Duración (permanente o temporal)',
                    '1.3. Enviar a aprobadores correspondientes'
                ],
                '2. APROBACIÓN' => [
                    '2.1. NIVEL 1: Gerente directo aprueba necesidad',
                    '2.2. NIVEL 2: Propietario del sistema/dato aprueba',
                    '2.3. NIVEL 3 (solo privilegiados): CISO aprueba',
                    '2.4. Aprobaciones deben ser documentadas',
                    '2.5. Rechazos se notifican con justificación'
                ],
                '3. PROVISIÓN' => [
                    '3.1. TI recibe solicitud aprobada',
                    '3.2. Valida que aprobaciones estén completas',
                    '3.3. Crea/modifica cuenta con permisos mínimos necesarios',
                    '3.4. Aplica principio de menor privilegio',
                    '3.5. Documenta acceso otorgado',
                    '3.6. Notifica a usuario',
                    '3.7. Tiempo de provisión: 24 horas hábiles'
                ],
                '4. ACCESOS PRIVILEGIADOS' => [
                    '4.1. Requieren proceso especial:',
                    '    - Justificación detallada',
                    '    - Aprobación de CISO',
                    '    - Capacitación obligatoria',
                    '    - Firma de acuerdo de responsabilidad',
                    '4.2. Sesiones son monitoreadas y registradas',
                    '4.3. Revisión mensual de uso',
                    '4.4. Rotación de contraseñas cada 30 días'
                ],
                '5. ACCESOS TEMPORALES' => [
                    '5.1. Especificar fecha de expiración',
                    '5.2. Sistema revoca automáticamente al vencer',
                    '5.3. Notificación 7 días antes de expiración',
                    '5.4. Renovación requiere nueva aprobación'
                ],
                '6. REVISIÓN PERIÓDICA' => [
                    '6.1. MENSUAL: Accesos privilegiados',
                    '    - Lista de cuentas administrativas',
                    '    - Gerentes certifican que aún se necesitan',
                    '    - Se revocan los innecesarios',
                    '6.2. TRIMESTRAL: Todos los accesos',
                    '    - Exportar lista de usuarios por sistema',
                    '    - Propietarios revisan y certifican',
                    '    - TI revoca accesos no certificados',
                    '6.3. AD-HOC: Ante cambio de rol o salida',
                    '6.4. Documentar resultado de cada revisión'
                ],
                '7. REVOCACIÓN' => [
                    '7.1. INMEDIATA (dentro de 1 hora):',
                    '    - Término de relación laboral',
                    '    - Sospecha de compromiso',
                    '    - Violación de políticas',
                    '7.2. PROGRAMADA (dentro de 24 horas):',
                    '    - Cambio de rol',
                    '    - Expiración de acceso temporal',
                    '    - No uso por 90 días',
                    '7.3. Documentar razón de revocación',
                    '7.4. Notificar a gerente del usuario'
                ],
                '8. CUENTAS DE SERVICIO' => [
                    '8.1. Registro de todas las cuentas de servicio',
                    '8.2. Documentar propósito y sistemas que usan',
                    '8.3. Contraseñas gestionadas en PAM',
                    '8.4. Rotación automática cada 180 días',
                    '8.5. Revisión semestral de necesidad'
                ],
                '9. SEGREGACIÓN DE FUNCIONES' => [
                    '9.1. Identificar funciones incompatibles',
                    '9.2. Evitar que misma persona tenga ambas',
                    '9.3. Ejemplos:',
                    '    - Solicitar Y aprobar',
                    '    - Desarrollar Y promover a producción',
                    '    - Crear usuario Y aprobar acceso',
                    '9.4. Alertas si se detecta conflicto'
                ]
            ],
            'registros' => [
                'FO-SI-100: Solicitud de Acceso',
                'FO-SI-101: Matriz de Accesos por Usuario',
                'FO-SI-102: Certificación de Accesos',
                'FO-SI-103: Registro de Cambios en Accesos'
            ],
            'control_iso' => 'A.5.15 - A.5.18 - Control de acceso'
        ]
    ]
];

// =====================================================
// FUNCIONES DE EXPORTACIÓN
// =====================================================

/**
 * Genera documento Word para Procedimientos
 */
function generarProcedimientoWord($code, $doc) {
    $phpWord = new PhpWord();

    // Configurar documento
    $phpWord->getDocInfo()
        ->setCreator('AUDITOR PRO')
        ->setTitle($doc['nombre'])
        ->setSubject('Procedimiento ISO 27001:2022');

    $section = $phpWord->addSection();

    // Estilos
    $titleStyle = ['name' => 'Arial', 'size' => 16, 'bold' => true, 'color' => '0066CC'];
    $heading1Style = ['name' => 'Arial', 'size' => 14, 'bold' => true, 'color' => '0066CC'];
    $heading2Style = ['name' => 'Arial', 'size' => 12, 'bold' => true];
    $normalStyle = ['name' => 'Arial', 'size' => 11];

    // Título
    $section->addText($doc['nombre'], $titleStyle, ['alignment' => Alignment::HORIZONTAL_CENTER]);
    $section->addTextBreak();

    // Información del documento
    $table = $section->addTable(['borderSize' => 6, 'borderColor' => '0066CC']);
    $table->addRow();
    $table->addCell(3000)->addText('Código:', $heading2Style);
    $table->addCell(6000)->addText($code, $normalStyle);

    $table->addRow();
    $table->addCell(3000)->addText('Versión:', $heading2Style);
    $table->addCell(6000)->addText($doc['version'], $normalStyle);

    $table->addRow();
    $table->addCell(3000)->addText('Fecha:', $heading2Style);
    $table->addCell(6000)->addText($doc['fecha'], $normalStyle);

    $table->addRow();
    $table->addCell(3000)->addText('Responsable:', $heading2Style);
    $table->addCell(6000)->addText($doc['responsable'], $normalStyle);

    $section->addTextBreak();

    // Objetivo
    $section->addText('1. OBJETIVO', $heading1Style);
    $section->addText($doc['objetivo'], $normalStyle);
    $section->addTextBreak();

    // Alcance
    $section->addText('2. ALCANCE', $heading1Style);
    $section->addText($doc['alcance'], $normalStyle);
    $section->addTextBreak();

    // Procedimiento
    $section->addText('3. PROCEDIMIENTO', $heading1Style);
    foreach ($doc['procedimiento'] as $titulo => $pasos) {
        $section->addText($titulo, $heading2Style);
        if (is_array($pasos)) {
            foreach ($pasos as $paso) {
                $section->addText($paso, $normalStyle, ['indentation' => ['left' => 720]]);
            }
        }
        $section->addTextBreak();
    }

    // Registros
    $section->addText('4. REGISTROS', $heading1Style);
    foreach ($doc['registros'] as $registro) {
        $section->addText('• ' . $registro, $normalStyle);
    }
    $section->addTextBreak();

    // Control ISO
    $section->addText('5. CONTROL ISO 27001:2022', $heading1Style);
    $section->addText($doc['control_iso'], $normalStyle);

    // Guardar y descargar
    $filename = tempnam(sys_get_temp_dir(), 'proc_') . '.docx';
    $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($filename);

    $downloadName = $code . '_' . str_replace(' ', '_', substr($doc['nombre'], 0, 30)) . '.docx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($filename));
    readfile($filename);
    unlink($filename);
    exit;
}

// =====================================================
// ROUTER PRINCIPAL
// =====================================================

// Verificar que la categoría existe
if (!isset($documentos[$category])) {
    die('Categoría no encontrada: ' . $category);
}

// Verificar que el documento existe
if (!isset($documentos[$category][$code])) {
    die('Documento no encontrado: ' . $code);
}

$documento = $documentos[$category][$code];

// Generar según formato
switch ($format) {
    case 'docx':
    case 'word':
        if ($category === 'procedimientos') {
            generarProcedimientoWord($code, $documento);
        }
        break;

    case 'xlsx':
    case 'excel':
        // TODO: Implementar generación Excel
        die('Formato Excel en desarrollo');
        break;

    case 'pdf':
        // TODO: Implementar generación PDF
        die('Formato PDF en desarrollo');
        break;

    default:
        die('Formato no soportado: ' . $format);
}
