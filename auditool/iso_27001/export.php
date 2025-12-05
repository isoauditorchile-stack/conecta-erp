<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Cargar autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Obtener parámetros
$code = isset($_GET['code']) ? $_GET['code'] : '';
$format = isset($_GET['format']) ? $_GET['format'] : 'docx';

if (empty($code)) {
    die('Código de política no especificado');
}

// =====================================================
// CONTENIDO COMPLETO DE POLÍTICAS ISO 27001
// =====================================================

$politicas = [
    'POL-SI-001' => [
        'titulo' => 'POLÍTICA DE SEGURIDAD DE LA INFORMACIÓN',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Establecer el marco general de seguridad de la información en la organización, definiendo principios, responsabilidades y lineamientos para proteger los activos de información contra amenazas internas y externas.',
        'alcance' => 'Esta política aplica a todos los empleados, contratistas, proveedores, consultores y terceros que tengan acceso a la información de la organización, sin importar su ubicación geográfica o modalidad de trabajo.',
        'definiciones' => [
            'Información' => 'Activo que tiene valor para la organización',
            'Seguridad de la Información' => 'Preservación de confidencialidad, integridad y disponibilidad',
            'Incidente de Seguridad' => 'Evento que compromete la seguridad de la información',
            'Activo de Información' => 'Cualquier información o sistema que la procesa'
        ],
        'politica' => [
            '1. PRINCIPIOS FUNDAMENTALES' => [
                'a) CONFIDENCIALIDAD: Garantizar que la información es accesible solo por personas autorizadas',
                'b) INTEGRIDAD: Asegurar la exactitud y completitud de la información',
                'c) DISPONIBILIDAD: Garantizar acceso a la información cuando se necesite',
                'd) AUTENTICIDAD: Verificar identidad de usuarios y origen de la información',
                'e) NO REPUDIO: Evitar que un usuario niegue haber realizado una acción',
                'f) TRAZABILIDAD: Registrar todas las acciones sobre la información'
            ],
            '2. RESPONSABILIDADES' => [
                'ALTA DIRECCIÓN:',
                '- Aprobar y promover la política de seguridad',
                '- Asignar recursos necesarios para el SGSI',
                '- Revisar anualmente el desempeño del SGSI',
                '',
                'CISO (Chief Information Security Officer):',
                '- Implementar y supervisar el SGSI',
                '- Gestionar riesgos de seguridad',
                '- Reportar a la dirección',
                '- Coordinar respuesta a incidentes',
                '',
                'JEFES DE ÁREA:',
                '- Cumplir y hacer cumplir políticas',
                '- Identificar y clasificar activos',
                '- Reportar incidentes de seguridad',
                '',
                'TODOS LOS EMPLEADOS:',
                '- Conocer y cumplir políticas de seguridad',
                '- Proteger activos asignados',
                '- Reportar incidentes inmediatamente',
                '- Participar en capacitaciones'
            ],
            '3. GESTIÓN DE RIESGOS' => [
                '- Se realizará análisis de riesgos anualmente',
                '- Riesgos críticos serán tratados de inmediato',
                '- Se implementarán controles según ISO 27001:2022',
                '- Los riesgos residuales serán aceptados formalmente'
            ],
            '4. CLASIFICACIÓN DE LA INFORMACIÓN' => [
                'Toda información será clasificada según:',
                '- PÚBLICA: Sin restricciones',
                '- INTERNA: Solo para uso interno',
                '- CONFIDENCIAL: Acceso restringido',
                '- ESTRICTAMENTE CONFIDENCIAL: Máxima restricción'
            ],
            '5. CONTROL DE ACCESO' => [
                '- Acceso basado en principio de menor privilegio',
                '- Autenticación multifactor para accesos críticos',
                '- Revisión trimestral de permisos',
                '- Revocación inmediata al cesar relación laboral'
            ],
            '6. INCIDENTES DE SEGURIDAD' => [
                '- Todo incidente debe reportarse de inmediato',
                '- Se investigarán todos los incidentes',
                '- Se documentarán lecciones aprendidas',
                '- Se aplicarán medidas correctivas'
            ]
        ],
        'cumplimiento' => 'El incumplimiento de esta política puede resultar en acciones disciplinarias que incluyen advertencias, suspensión o terminación del empleo. En casos graves, se pueden aplicar acciones legales.',
        'revision' => 'Esta política será revisada anualmente o cuando cambios significativos lo requieran.',
        'control_iso' => 'A.5.1 - Políticas de seguridad de la información'
    ],

    'POL-SI-002' => [
        'titulo' => 'POLÍTICA DE CONTROL DE ACCESO',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Establecer reglas y procedimientos para la gestión y control de accesos a sistemas, aplicaciones, datos y recursos tecnológicos de la organización.',
        'alcance' => 'Todos los sistemas de información, aplicaciones, bases de datos, redes y recursos tecnológicos de la organización.',
        'definiciones' => [
            'Control de Acceso' => 'Proceso que limita y controla el acceso a recursos',
            'Autenticación' => 'Verificación de identidad de un usuario',
            'Autorización' => 'Concesión de derechos de acceso',
            'Principio de Menor Privilegio' => 'Otorgar solo permisos estrictamente necesarios'
        ],
        'politica' => [
            '1. PRINCIPIO DE MENOR PRIVILEGIO' => [
                '- Otorgar únicamente los accesos necesarios para realizar funciones',
                '- Evitar privilegios administrativos innecesarios',
                '- Revisar y justificar todos los accesos privilegiados',
                '- Segregar funciones incompatibles'
            ],
            '2. AUTENTICACIÓN' => [
                'CONTRASEÑAS:',
                '- Mínimo 12 caracteres',
                '- Combinar mayúsculas, minúsculas, números y símbolos',
                '- Cambio cada 90 días',
                '- No reutilizar últimas 10 contraseñas',
                '- Bloqueo tras 5 intentos fallidos',
                '',
                'MULTIFACTOR (MFA):',
                '- Obligatorio para accesos remotos',
                '- Obligatorio para cuentas administrativas',
                '- Obligatorio para sistemas críticos',
                '',
                'BIOMÉTRICA:',
                '- Para accesos físicos a áreas restringidas',
                '- Respaldo con método alternativo'
            ],
            '3. AUTORIZACIÓN' => [
                'SOLICITUD DE ACCESO:',
                '- Formulario formal (FO-SI-020)',
                '- Justificación de negocio',
                '- Aprobación de gerente directo',
                '- Provisión por equipo TI',
                '',
                'REVISIÓN DE ACCESOS:',
                '- Mensual: Accesos privilegiados',
                '- Trimestral: Todos los accesos',
                '- Certificación por gerentes de área',
                '- Documentación de revisiones',
                '',
                'REVOCACIÓN:',
                '- Inmediata al término de relación laboral',
                '- Dentro de 24h al cambio de función',
                '- Automática tras 90 días de inactividad'
            ],
            '4. CUENTAS PRIVILEGIADAS' => [
                '- Uso exclusivo para tareas administrativas',
                '- No usar para actividades diarias',
                '- Sesiones monitoreadas y registradas',
                '- PAM (Privileged Access Management) implementado',
                '- Rotación de contraseñas cada 30 días'
            ],
            '5. ACCESOS REMOTOS' => [
                '- VPN corporativa obligatoria',
                '- MFA obligatorio',
                '- Equipos corporativos con hardening',
                '- Prohibido desde equipos personales no autorizados',
                '- Monitoreo de conexiones remotas'
            ],
            '6. CUENTAS COMPARTIDAS' => [
                '- Prohibidas en general',
                '- Excepciones requieren aprobación CISO',
                '- Contraseña gestionada por PAM',
                '- Registro de uso individual',
                '- Revisión mensual de necesidad'
            ]
        ],
        'cumplimiento' => 'El uso indebido de credenciales o intentos de acceso no autorizado resultarán en acciones disciplinarias inmediatas, incluyendo terminación de empleo y acciones legales.',
        'revision' => 'Revisión anual o ante cambios en infraestructura tecnológica.',
        'control_iso' => 'A.5.15, A.5.16, A.5.17, A.5.18 - Controles de Acceso'
    ],

    'POL-SI-003' => [
        'titulo' => 'POLÍTICA DE CLASIFICACIÓN DE LA INFORMACIÓN',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Definir niveles de clasificación de la información y controles de protección correspondientes según su valor, sensibilidad y criticidad para la organización.',
        'alcance' => 'Toda la información generada, procesada, almacenada o transmitida por la organización, en cualquier formato o medio.',
        'definiciones' => [
            'Clasificación' => 'Asignación de nivel de sensibilidad a la información',
            'Etiquetado' => 'Marcado visible de documentos con su clasificación',
            'Propietario de Información' => 'Responsable de clasificar y proteger la información',
            'Custodio' => 'Responsable de implementar controles técnicos'
        ],
        'politica' => [
            '1. NIVELES DE CLASIFICACIÓN' => [
                'PÚBLICA:',
                '- Definición: Información sin valor confidencial',
                '- Ejemplos: Material de marketing, ofertas públicas',
                '- Impacto de divulgación: Ninguno o mínimo',
                '- Controles: Ninguno especial',
                '- Distribución: Sin restricciones',
                '',
                'INTERNA:',
                '- Definición: Información de uso interno',
                '- Ejemplos: Procedimientos internos, organigramas',
                '- Impacto: Bajo a medio',
                '- Controles: Acceso solo a empleados',
                '- Distribución: Solo interno, NDA para externos',
                '',
                'CONFIDENCIAL:',
                '- Definición: Información sensible del negocio',
                '- Ejemplos: Contratos, datos financieros, estrategia',
                '- Impacto: Alto',
                '- Controles: Acceso restringido, cifrado',
                '- Distribución: Solo personal autorizado, NDA obligatorio',
                '',
                'ESTRICTAMENTE CONFIDENCIAL:',
                '- Definición: Información crítica',
                '- Ejemplos: Datos personales sensibles, secretos comerciales',
                '- Impacto: Muy alto o catastrófico',
                '- Controles: Máxima restricción, cifrado obligatorio',
                '- Distribución: Mínimo personal con necesidad conocer'
            ],
            '2. RESPONSABILIDADES' => [
                'PROPIETARIO DE INFORMACIÓN:',
                '- Clasificar información bajo su responsabilidad',
                '- Definir controles de protección',
                '- Autorizar accesos',
                '- Revisar clasificación anualmente',
                '',
                'USUARIOS:',
                '- Respetar clasificación asignada',
                '- Aplicar controles correspondientes',
                '- Reportar clasificación incorrecta',
                '- No reclasificar sin autorización',
                '',
                'TI:',
                '- Implementar controles técnicos',
                '- Configurar cifrado según clasificación',
                '- Auditar cumplimiento'
            ],
            '3. ETIQUETADO' => [
                'DOCUMENTOS FÍSICOS:',
                '- Encabezado y pie de página',
                '- Color según clasificación',
                '- Marca de agua si aplica',
                '',
                'DOCUMENTOS DIGITALES:',
                '- Metadatos de clasificación',
                '- Encabezado/pie de página',
                '- DLP para detectar y prevenir fugas',
                '',
                'CORREOS ELECTRÓNICOS:',
                '- Asunto incluye [CONFIDENCIAL] si aplica',
                '- Banner automático en cuerpo',
                '- Cifrado automático para CONFIDENCIAL+'
            ],
            '4. CONTROLES POR CLASIFICACIÓN' => [
                'PÚBLICA:',
                '- Almacenamiento: Sin restricción',
                '- Transmisión: Sin cifrar',
                '- Copias: Permitidas',
                '- Eliminación: Normal',
                '',
                'INTERNA:',
                '- Almacenamiento: Repositorios corporativos',
                '- Transmisión: Dentro de red corporativa',
                '- Copias: Controladas',
                '- Eliminación: Segura',
                '',
                'CONFIDENCIAL:',
                '- Almacenamiento: Cifrado en reposo',
                '- Transmisión: Cifrado TLS/VPN',
                '- Copias: Autorizadas',
                '- Eliminación: Destrucción segura',
                '',
                'ESTRICTAMENTE CONFIDENCIAL:',
                '- Almacenamiento: Cifrado fuerte',
                '- Transmisión: Cifrado de extremo a extremo',
                '- Copias: Prohibidas salvo autorización',
                '- Eliminación: Destrucción certificada'
            ],
            '5. RECLASIFICACIÓN' => [
                '- Revisar anualmente',
                '- Actualizar ante cambios de contexto',
                '- Aprobación de propietario requerida',
                '- Documentar justificación'
            ]
        ],
        'cumplimiento' => 'El manejo inadecuado de información clasificada resultará en acciones disciplinarias proporcionales al daño causado.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.5.12 - Clasificación de la información'
    ],

    'POL-SI-004' => [
        'titulo' => 'POLÍTICA DE USO ACEPTABLE DE RECURSOS TECNOLÓGICOS',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Definir reglas claras sobre el uso aceptable de recursos tecnológicos corporativos para proteger la seguridad, productividad y reputación de la organización.',
        'alcance' => 'Todos los usuarios de recursos tecnológicos: empleados, contratistas, proveedores y visitantes.',
        'politica' => [
            '1. USO PERMITIDO' => [
                '- Actividades relacionadas con funciones laborales',
                '- Uso personal limitado y razonable (máximo 1 hora/día)',
                '- Comunicaciones profesionales',
                '- Capacitación y desarrollo profesional',
                '- Uso de herramientas aprobadas'
            ],
            '2. USO PROHIBIDO' => [
                'ESTRICTAMENTE PROHIBIDO:',
                '- Descarga o distribución de contenido ilegal',
                '- Pornografía, apuestas, violencia',
                '- Software pirata o no licenciado',
                '- Hacking o intentos de intrusión',
                '- Evasión de controles de seguridad',
                '- Instalación no autorizada de software',
                '- Uso de recursos para negocio personal',
                '- Spam o cadenas de correo',
                '- Acoso, discriminación o bullying',
                '- Divulgación de información confidencial'
            ],
            '3. CORREO ELECTRÓNICO' => [
                'PERMITIDO:',
                '- Comunicaciones profesionales',
                '- Uso personal limitado',
                '- Suscripción a newsletters profesionales',
                '',
                'PROHIBIDO:',
                '- Envío masivo no autorizado',
                '- Información confidencial sin cifrar',
                '- Suplantación de identidad',
                '- Apertura de adjuntos sospechosos',
                '',
                'OBLIGATORIO:',
                '- Verificar remitente antes de abrir adjuntos',
                '- Reportar phishing al equipo de seguridad',
                '- Usar firma corporativa',
                '- No reenviar información sensible externamente'
            ],
            '4. INTERNET' => [
                'PERMITIDO:',
                '- Investigación relacionada con trabajo',
                '- Noticias y redes profesionales',
                '- Navegación personal limitada',
                '',
                'PROHIBIDO:',
                '- Sitios categorizados como inapropiados',
                '- Descarga de software no autorizado',
                '- Streaming excesivo de video/música',
                '- P2P y torrents',
                '- Redes sociales en exceso (máximo 30 min/día)',
                '',
                'MONITOREO:',
                '- La organización monitorea tráfico web',
                '- Sitios visitados son registrados',
                '- DLP detecta transferencias indebidas',
                '- No hay expectativa de privacidad total'
            ],
            '5. DISPOSITIVOS' => [
                'EQUIPOS CORPORATIVOS:',
                '- Uso primario para trabajo',
                '- No remover componentes',
                '- No instalar software personal',
                '- Reportar pérdida o robo inmediatamente',
                '- Permitir borrado remoto',
                '',
                'DISPOSITIVOS MÓVILES (BYOD):',
                '- Registro en MDM obligatorio',
                '- Cifrado activado',
                '- PIN/biometría configurado',
                '- Separación datos corporativos/personales',
                '- Aceptar borrado remoto de datos corporativos'
            ],
            '6. REDES SOCIALES' => [
                '- No representar a la organización sin autorización',
                '- No divulgar información confidencial',
                '- No dañar reputación corporativa',
                '- Indicar que opiniones son personales',
                '- Respetar propiedad intelectual'
            ],
            '7. MONITOREO Y AUDITORÍA' => [
                'La organización se reserva el derecho de:',
                '- Monitorear uso de recursos tecnológicos',
                '- Revisar logs de acceso y actividad',
                '- Inspeccionar contenido almacenado',
                '- Bloquear accesos inapropiados',
                '- Auditar cumplimiento periódicamente',
                '',
                'No existe expectativa razonable de privacidad en:',
                '- Correo electrónico corporativo',
                '- Navegación web desde red corporativa',
                '- Archivos en equipos corporativos',
                '- Comunicaciones vía herramientas corporativas'
            ]
        ],
        'cumplimiento' => 'Violaciones a esta política pueden resultar en: advertencia, suspensión de acceso, terminación de empleo y acciones legales según gravedad.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.5.10 - Uso aceptable de los activos'
    ],

    'POL-SI-007' => [
        'titulo' => 'POLÍTICA DE GESTIÓN DE CONTRASEÑAS',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Establecer requisitos mínimos para la creación, uso, almacenamiento y administración segura de contraseñas.',
        'alcance' => 'Todos los sistemas, aplicaciones y servicios que requieran autenticación mediante contraseña.',
        'politica' => [
            '1. REQUISITOS DE COMPLEJIDAD' => [
                'LONGITUD Y COMPOSICIÓN:',
                '- Mínimo 12 caracteres (14 para administradores)',
                '- Combinación obligatoria de:',
                '  * Letras mayúsculas (A-Z)',
                '  * Letras minúsculas (a-z)',
                '  * Números (0-9)',
                '  * Símbolos especiales (!@#$%^&*)',
                '',
                'PROHIBICIONES:',
                '- Palabras del diccionario',
                '- Información personal (nombre, fecha nacimiento, etc.)',
                '- Secuencias obvias (123456, qwerty, abcdef)',
                '- Repetición de caracteres (aaaa, 1111)',
                '- Información pública sobre el usuario',
                '- Contraseñas comprometidas conocidas'
            ],
            '2. GESTIÓN DE CONTRASEÑAS' => [
                'CAMBIO DE CONTRASEÑAS:',
                '- Usuarios: Cada 90 días',
                '- Administradores: Cada 60 días',
                '- Cuentas de servicio: Cada 180 días',
                '- Cambio inmediato si hay sospecha de compromiso',
                '',
                'HISTORIAL:',
                '- No reutilizar últimas 10 contraseñas',
                '- Sistema registra historial',
                '- Validación automática al cambio',
                '',
                'PRIMERA CONTRASEÑA:',
                '- Generada aleatoriamente',
                '- Entregada de forma segura',
                '- Cambio obligatorio en primer acceso',
                '- Válida solo 24 horas'
            ],
            '3. ALMACENAMIENTO' => [
                'PROHIBIDO:',
                '- Escribir contraseñas en papel',
                '- Almacenar en archivos de texto plano',
                '- Guardar en navegador sin cifrado',
                '- Compartir vía email o mensajería',
                '- Almacenar en dispositivos no cifrados',
                '',
                'PERMITIDO:',
                '- Gestor de contraseñas aprobado',
                '- Bóveda cifrada corporativa',
                '- PAM para cuentas privilegiadas'
            ],
            '4. USO DE CONTRASEÑAS' => [
                'PROHIBIDO:',
                '- Compartir contraseñas',
                '- Usar misma contraseña en múltiples sistemas',
                '- Divulgar contraseña por teléfono',
                '- Escribir contraseña donde otros puedan ver',
                '- Guardar en auto-completar no seguro',
                '',
                'OBLIGATORIO:',
                '- Una contraseña única por sistema',
                '- No revelarla a nadie (ni TI, ni gerencia)',
                '- Cambiarla si fue compartida',
                '- Reportar si fue comprometida'
            ],
            '5. AUTENTICACIÓN MULTIFACTOR (MFA)' => [
                'OBLIGATORIO PARA:',
                '- Accesos remotos (VPN, RDP)',
                '- Cuentas administrativas',
                '- Sistemas que procesan datos confidenciales',
                '- Aplicaciones cloud críticas',
                '- Servicios de correo electrónico',
                '',
                'MÉTODOS ACEPTADOS:',
                '- Aplicación autenticadora (preferido)',
                '- SMS (solo si no hay alternativa)',
                '- Token de hardware',
                '- Biometría + contraseña'
            ],
            '6. RECUPERACIÓN DE CONTRASEÑA' => [
                'PROCESO:',
                '- Solicitud vía portal de autoservicio',
                '- Validación de identidad mediante:',
                '  * Preguntas de seguridad',
                '  * Código a email/SMS registrado',
                '  * Validación por gerente',
                '- Contraseña temporal válida 24h',
                '- Cambio obligatorio en primer uso',
                '',
                'HELPDESK:',
                '- Validar identidad del solicitante',
                '- No enviar contraseña por email',
                '- Entregar solo en persona o vía canal seguro',
                '- Registrar todas las recuperaciones'
            ],
            '7. BLOQUEO DE CUENTA' => [
                '- Bloqueo automático tras 5 intentos fallidos',
                '- Duración: 30 minutos',
                '- Desbloqueo: Automático o por TI',
                '- Notificación al usuario de intentos fallidos',
                '- Alerta al equipo de seguridad si es cuenta sensible'
            ],
            '8. CUENTAS COMPARTIDAS' => [
                '- Prohibidas en general',
                '- Excepción requiere aprobación de CISO',
                '- Documentar justificación',
                '- Gestionar con PAM',
                '- Auditar todos los usos',
                '- Revisar necesidad trimestralmente'
            ]
        ],
        'cumplimiento' => 'El uso indebido o divulgación de contraseñas resultará en suspensión inmediata de acceso y acciones disciplinarias.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.5.17 - Información de autenticación'
    ],

    'POL-SI-009' => [
        'titulo' => 'POLÍTICA DE CRIPTOGRAFÍA',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Definir el uso obligatorio de controles criptográficos para proteger la confidencialidad, integridad y autenticidad de la información.',
        'alcance' => 'Toda información clasificada como Confidencial o Estrictamente Confidencial, en cualquier formato o medio.',
        'politica' => [
            '1. CIFRADO DE DATOS EN REPOSO' => [
                'BASES DE DATOS:',
                '- Algoritmo: AES-256',
                '- TDE (Transparent Data Encryption) activado',
                '- Cifrado a nivel de columna para datos sensibles',
                '- Llaves gestionadas por HSM o Key Vault',
                '',
                'DISCOS Y VOLÚMENES:',
                '- Windows: BitLocker',
                '- macOS: FileVault 2',
                '- Linux: LUKS',
                '- Servidores: Cifrado a nivel de volumen',
                '',
                'RESPALDOS:',
                '- Cifrado obligatorio antes de almacenar',
                '- AES-256 mínimo',
                '- Llaves separadas de los datos',
                '- Verificación periódica de cifrado',
                '',
                'DISPOSITIVOS MÓVILES:',
                '- Cifrado nativo activado',
                '- iOS: Cifrado por defecto',
                '- Android: Cifrado de almacenamiento',
                '- Contenedores separados para datos corporativos'
            ],
            '2. CIFRADO DE DATOS EN TRÁNSITO' => [
                'PROTOCOLOS WEB:',
                '- HTTPS obligatorio (TLS 1.2 mínimo, TLS 1.3 preferido)',
                '- Certificados de CA confiable',
                '- Renovación antes de expiración',
                '- HSTS habilitado',
                '',
                'CORREO ELECTRÓNICO:',
                '- TLS para transporte (STARTTLS)',
                '- S/MIME o PGP para información confidencial',
                '- Cifrado de extremo a extremo para datos sensibles',
                '',
                'ACCESO REMOTO:',
                '- VPN con IPsec o SSL/TLS',
                '- Cifrado AES-256',
                '- Perfect Forward Secrecy (PFS)',
                '',
                'TRANSFERENCIA DE ARCHIVOS:',
                '- SFTP en lugar de FTP',
                '- FTPS con TLS 1.2+',
                '- SCP para transferencias Linux',
                '- Prohibido: FTP sin cifrar, HTTP sin TLS'
            ],
            '3. GESTIÓN DE LLAVES CRIPTOGRÁFICAS' => [
                'GENERACIÓN:',
                '- Generadores aleatorios certificados',
                '- Longitud mínima según algoritmo',
                '- Generación en ambiente seguro',
                '',
                'ALMACENAMIENTO:',
                '- HSM (Hardware Security Module) para llaves maestras',
                '- Key Vault para llaves operacionales',
                '- Nunca en texto plano',
                '- Separadas de datos cifrados',
                '',
                'DISTRIBUCIÓN:',
                '- Canales seguros y autenticados',
                '- Separación de componentes (split knowledge)',
                '- Registro de todas las distribuciones',
                '',
                'ROTACIÓN:',
                '- Llaves simétricas: Anual',
                '- Llaves asimétricas: Cada 2 años',
                '- Certificados SSL: Antes de expiración',
                '- Inmediata si hay sospecha de compromiso',
                '',
                'RESPALDO:',
                '- Copias cifradas en ubicación segura',
                '- Procedimiento de recuperación documentado',
                '- Pruebas de recuperación anuales',
                '',
                'DESTRUCCIÓN:',
                '- Al final de vida útil',
                '- Método certificado de destrucción',
                '- Documentar destrucción',
                '- Verificar imposibilidad de recuperación'
            ],
            '4. ALGORITMOS APROBADOS' => [
                'SIMÉTRICOS:',
                '- AES-256 (preferido)',
                '- AES-128 (aceptable)',
                '- ChaCha20 (para rendimiento)',
                '',
                'ASIMÉTRICOS:',
                '- RSA: 4096 bits mínimo',
                '- ECC: Curve25519, P-384',
                '',
                'HASH:',
                '- SHA-256 (mínimo)',
                '- SHA-384, SHA-512 (preferidos)',
                '- PROHIBIDO: MD5, SHA-1',
                '',
                'FIRMA DIGITAL:',
                '- RSA-PSS',
                '- ECDSA',
                '- EdDSA'
            ],
            '5. CERTIFICADOS DIGITALES' => [
                'ADQUISICIÓN:',
                '- Solo de CAs confiables',
                '- Validación Extended Validation para sitios públicos',
                '- PKI interna para servicios internos',
                '',
                'GESTIÓN:',
                '- Inventario de todos los certificados',
                '- Monitoreo de expiración',
                '- Renovación 30 días antes',
                '- Revocación inmediata si compromiso',
                '',
                'ALMACENAMIENTO PRIVADO:',
                '- Llaves privadas en HSM',
                '- Acceso restringido',
                '- Nunca en repositorios de código',
                '- Cifrado si almacenamiento en archivo'
            ],
            '6. PROHIBICIONES' => [
                'ALGORITMOS DÉBILES:',
                '- DES, 3DES',
                '- RC4',
                '- MD5 para propósitos de seguridad',
                '- SHA-1 para firmas',
                '',
                'PRÁCTICAS INSEGURAS:',
                '- Llaves embebidas en código',
                '- Cifrado casero o personalizado',
                '- Reutilización de IVs',
                '- Cifrado sin integridad (autenticación)'
            ]
        ],
        'cumplimiento' => 'El uso de cifrado débil o gestión inadecuada de llaves es una violación grave de seguridad.',
        'revision' => 'Revisión anual o ante nuevos estándares criptográficos.',
        'control_iso' => 'A.8.24 - Uso de criptografía'
    ],

    'POL-SI-013' => [
        'titulo' => 'POLÍTICA DE GESTIÓN DE INCIDENTES DE SEGURIDAD',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Establecer un proceso estructurado para identificar, reportar, evaluar, responder y aprender de incidentes de seguridad de la información.',
        'alcance' => 'Todos los incidentes que afecten o puedan afectar la confidencialidad, integridad o disponibilidad de la información.',
        'politica' => [
            '1. DEFINICIÓN DE INCIDENTE' => [
                'Se considera incidente de seguridad:',
                '- Acceso no autorizado a sistemas o datos',
                '- Modificación no autorizada de información',
                '- Pérdida o robo de dispositivos con datos',
                '- Infección por malware',
                '- Ataque de denegación de servicio',
                '- Fuga de información confidencial',
                '- Phishing exitoso',
                '- Vulneración de controles de seguridad',
                '- Uso indebido de recursos',
                '- Cualquier violación a políticas de seguridad'
            ],
            '2. CLASIFICACIÓN DE INCIDENTES' => [
                'CRÍTICO:',
                '- Impacto: Severo en operaciones críticas',
                '- Alcance: Múltiples sistemas o datos sensibles',
                '- Tiempo de respuesta: Inmediato',
                '- Ejemplo: Ransomware, fuga masiva de datos',
                '',
                'ALTO:',
                '- Impacto: Significativo en operaciones',
                '- Alcance: Sistema importante o datos confidenciales',
                '- Tiempo de respuesta: Dentro de 1 hora',
                '- Ejemplo: Compromiso de cuenta administrativa',
                '',
                'MEDIO:',
                '- Impacto: Limitado',
                '- Alcance: Sistema no crítico',
                '- Tiempo de respuesta: Dentro de 4 horas',
                '- Ejemplo: Malware contenido',
                '',
                'BAJO:',
                '- Impacto: Mínimo',
                '- Alcance: Usuario individual',
                '- Tiempo de respuesta: Dentro de 24 horas',
                '- Ejemplo: Phishing reportado antes de hacer clic'
            ],
            '3. REPORTE DE INCIDENTES' => [
                'OBLIGACIÓN DE REPORTAR:',
                '- Todo empleado debe reportar incidentes',
                '- Reportar inmediatamente, sin demora',
                '- No intentar resolver por cuenta propia',
                '- No compartir información del incidente',
                '',
                'CANALES DE REPORTE:',
                '- Email: security@empresa.com',
                '- Teléfono: +56 X XXXX XXXX (24/7)',
                '- Portal: https://incidentes.empresa.com',
                '- Presencial: Equipo de seguridad',
                '',
                'INFORMACIÓN A INCLUIR:',
                '- Qué: Descripción del incidente',
                '- Cuándo: Fecha y hora',
                '- Dónde: Sistema/ubicación afectado',
                '- Quién: Personas involucradas',
                '- Cómo: Cómo se descubrió',
                '- Impacto: Estimación inicial'
            ],
            '4. RESPUESTA A INCIDENTES' => [
                'FASE 1 - DETECCIÓN Y ANÁLISIS:',
                '- Confirmar que es un incidente real',
                '- Clasificar severidad',
                '- Asignar a analista',
                '- Documentar en FO-SI-008',
                '',
                'FASE 2 - CONTENCIÓN:',
                '- Contención a corto plazo (aislar amenaza)',
                '- Contención a largo plazo (solución temporal)',
                '- Preservar evidencia forense',
                '- Limitar propagación',
                '',
                'FASE 3 - ERRADICACIÓN:',
                '- Eliminar causa raíz',
                '- Eliminar malware/accesos no autorizados',
                '- Cerrar vulnerabilidades explotadas',
                '- Aplicar parches necesarios',
                '',
                'FASE 4 - RECUPERACIÓN:',
                '- Restaurar sistemas afectados',
                '- Validar funcionamiento',
                '- Monitorear anomalías',
                '- Retornar a operación normal',
                '',
                'FASE 5 - LECCIONES APRENDIDAS:',
                '- Reunión post-incidente',
                '- Documentar timeline',
                '- Identificar mejoras',
                '- Actualizar controles',
                '- Actualizar procedimientos'
            ],
            '5. COMUNICACIÓN' => [
                'INTERNA:',
                '- Equipo de respuesta: Inmediato',
                '- Gerencia: Según severidad',
                '- Afectados: Cuando sea seguro',
                '- Organización: Si impacto general',
                '',
                'EXTERNA:',
                '- Autoridades: Si legalmente requerido',
                '- Clientes: Si sus datos fueron afectados',
                '- Medios: Solo vocero autorizado',
                '- Reguladores: Dentro de plazos legales',
                '',
                'PRINCIPIOS:',
                '- Mensaje único y consistente',
                '- No especular',
                '- Proteger investigación en curso',
                '- Cumplir requisitos legales de notificación'
            ],
            '6. PRESERVACIÓN DE EVIDENCIA' => [
                '- No apagar sistemas sin autorización',
                '- Capturar memoria volátil si es posible',
                '- Crear imágenes forenses',
                '- Cadena de custodia estricta',
                '- Documentar todas las acciones',
                '- No alterar logs o archivos',
                '- Coordinar con legal si hay implicaciones'
            ],
            '7. ROLES Y RESPONSABILIDADES' => [
                'CSIRT (Computer Security Incident Response Team):',
                '- Liderar respuesta',
                '- Coordinar equipos',
                '- Tomar decisiones técnicas',
                '- Comunicar avances',
                '',
                'CISO:',
                '- Aprobar estrategia de respuesta',
                '- Comunicar a dirección',
                '- Decidir notificaciones externas',
                '',
                'LEGAL:',
                '- Asesorar en aspectos legales',
                '- Coordinar con autoridades',
                '- Gestionar aspectos de privacidad',
                '',
                'COMUNICACIONES:',
                '- Preparar mensajes',
                '- Gestionar comunicación externa',
                '- Monitorear reputación'
            ]
        ],
        'cumplimiento' => 'No reportar incidentes o interferir con la respuesta es una violación grave.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.5.24 - A.5.28 - Gestión de incidentes'
    ]
];

// Función para generar Word
function generarWord($code, $pol) {
    $phpWord = new PhpWord();

    // Configurar documento
    $phpWord->setDefaultFontName('Calibri');
    $phpWord->setDefaultFontSize(11);

    $section = $phpWord->addSection([
        'marginLeft' => 1134,
        'marginRight' => 1134,
        'marginTop' => 1134,
        'marginBottom' => 1134,
    ]);

    // Tabla de encabezado
    $table = $section->addTable([
        'borderSize' => 6,
        'borderColor' => '000000',
        'width' => 100 * 50,
        'unit' => 'pct'
    ]);

    $table->addRow();
    $cell = $table->addCell(9500, ['bgColor' => '0066CC', 'gridSpan' => 2]);
    $cell->addText('AUDITOR PRO', [
        'bold' => true,
        'size' => 14,
        'color' => 'FFFFFF'
    ], ['alignment' => Alignment::ALIGN_CENTER]);

    $table->addRow();
    $table->addCell(4750)->addText('Código: ' . $code, ['bold' => true]);
    $table->addCell(4750)->addText('Versión: ' . $pol['version'], ['bold' => true]);

    $table->addRow();
    $table->addCell(4750)->addText('Fecha: ' . $pol['fecha']);
    $table->addCell(4750)->addText('Próxima Revisión: ' . date('d/m/Y', strtotime('+1 year')));

    $section->addTextBreak(2);

    // Título
    $section->addText(strtoupper($pol['titulo']), [
        'bold' => true,
        'size' => 16,
        'color' => '0066CC'
    ], ['alignment' => Alignment::ALIGN_CENTER]);

    $section->addTextBreak(2);

    // 1. Objetivo
    $section->addText('1. OBJETIVO', ['bold' => true, 'size' => 12, 'color' => '0066CC']);
    $section->addTextBreak();
    $section->addText($pol['objetivo'], [], ['alignment' => Alignment::ALIGN_BOTH]);
    $section->addTextBreak();

    // 2. Alcance
    $section->addText('2. ALCANCE', ['bold' => true, 'size' => 12, 'color' => '0066CC']);
    $section->addTextBreak();
    $section->addText($pol['alcance'], [], ['alignment' => Alignment::ALIGN_BOTH]);
    $section->addTextBreak();

    // 3. Definiciones (si existen)
    if (isset($pol['definiciones'])) {
        $section->addText('3. DEFINICIONES', ['bold' => true, 'size' => 12, 'color' => '0066CC']);
        $section->addTextBreak();
        foreach ($pol['definiciones'] as $termino => $definicion) {
            $section->addText($termino . ': ', ['bold' => true], ['alignment' => Alignment::ALIGN_BOTH]);
            $section->addText($definicion, [], ['alignment' => Alignment::ALIGN_BOTH]);
            $section->addTextBreak();
        }
    }

    // 4. Contenido de la política
    $seccion_num = isset($pol['definiciones']) ? 4 : 3;
    $section->addText($seccion_num . '. CONTENIDO DE LA POLÍTICA', ['bold' => true, 'size' => 12, 'color' => '0066CC']);
    $section->addTextBreak();

    foreach ($pol['politica'] as $titulo => $contenido) {
        $section->addText($titulo, ['bold' => true, 'size' => 11]);
        $section->addTextBreak();

        if (is_array($contenido)) {
            foreach ($contenido as $item) {
                if (empty(trim($item))) {
                    $section->addTextBreak();
                } else {
                    $section->addText($item, [], ['alignment' => Alignment::ALIGN_BOTH]);
                }
            }
        } else {
            $section->addText($contenido, [], ['alignment' => Alignment::ALIGN_BOTH]);
        }
        $section->addTextBreak();
    }

    // Cumplimiento
    $seccion_num++;
    $section->addText($seccion_num . '. CUMPLIMIENTO', ['bold' => true, 'size' => 12, 'color' => '0066CC']);
    $section->addTextBreak();
    $section->addText($pol['cumplimiento'], [], ['alignment' => Alignment::ALIGN_BOTH]);
    $section->addTextBreak(2);

    // Revisión
    $seccion_num++;
    $section->addText($seccion_num . '. REVISIÓN', ['bold' => true, 'size' => 12, 'color' => '0066CC']);
    $section->addTextBreak();
    $section->addText($pol['revision'], [], ['alignment' => Alignment::ALIGN_BOTH]);
    $section->addTextBreak(2);

    // Control ISO
    if (isset($pol['control_iso'])) {
        $seccion_num++;
        $section->addText($seccion_num . '. CONTROL ISO 27001:2022', ['bold' => true, 'size' => 12, 'color' => '0066CC']);
        $section->addTextBreak();
        $section->addText($pol['control_iso'], [], ['alignment' => Alignment::ALIGN_BOTH]);
        $section->addTextBreak(2);
    }

    // Tabla de firmas
    $section->addTextBreak(2);
    $tableF = $section->addTable([
        'borderSize' => 6,
        'borderColor' => '000000',
        'width' => 100 * 50,
        'unit' => 'pct'
    ]);

    $tableF->addRow();
    $tableF->addCell(3166, ['bgColor' => 'F0F0F0'])->addText('Elaborado por', ['bold' => true], ['alignment' => Alignment::ALIGN_CENTER]);
    $tableF->addCell(3166, ['bgColor' => 'F0F0F0'])->addText('Revisado por', ['bold' => true], ['alignment' => Alignment::ALIGN_CENTER]);
    $tableF->addCell(3166, ['bgColor' => 'F0F0F0'])->addText('Aprobado por', ['bold' => true], ['alignment' => Alignment::ALIGN_CENTER]);

    $tableF->addRow(1500);
    $tableF->addCell(3166)->addText('', [], ['alignment' => Alignment::ALIGN_CENTER]);
    $tableF->addCell(3166)->addText('', [], ['alignment' => Alignment::ALIGN_CENTER]);
    $tableF->addCell(3166)->addText('', [], ['alignment' => Alignment::ALIGN_CENTER]);

    $tableF->addRow();
    $tableF->addCell(3166)->addText('CISO', [], ['alignment' => Alignment::ALIGN_CENTER]);
    $tableF->addCell(3166)->addText('Gerente TI', [], ['alignment' => Alignment::ALIGN_CENTER]);
    $tableF->addCell(3166)->addText('Gerencia General', [], ['alignment' => Alignment::ALIGN_CENTER]);

    // Guardar
    $filename = $code . '_' . str_replace(' ', '_', $pol['titulo']) . '.docx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
    $writer->save('php://output');
    exit;
}

// Función para generar Excel
function generarExcel($code, $pol) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Política');

    // Configurar anchos
    $sheet->getColumnDimension('A')->setWidth(25);
    $sheet->getColumnDimension('B')->setWidth(80);

    $row = 1;

    // Encabezado
    $sheet->mergeCells("A{$row}:B{$row}");
    $sheet->setCellValue("A{$row}", 'AUDITOR PRO');
    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FFFFFF');
    $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0066CC');
    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;

    $sheet->setCellValue("A{$row}", 'Código:');
    $sheet->setCellValue("B{$row}", $code);
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;

    $sheet->setCellValue("A{$row}", 'Versión:');
    $sheet->setCellValue("B{$row}", $pol['version']);
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;

    $sheet->setCellValue("A{$row}", 'Fecha:');
    $sheet->setCellValue("B{$row}", $pol['fecha']);
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row += 2;

    // Título
    $sheet->mergeCells("A{$row}:B{$row}");
    $sheet->setCellValue("A{$row}", strtoupper($pol['titulo']));
    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('0066CC');
    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row += 2;

    // Objetivo
    $sheet->mergeCells("A{$row}:B{$row}");
    $sheet->setCellValue("A{$row}", '1. OBJETIVO');
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;

    $sheet->mergeCells("A{$row}:B{$row}");
    $sheet->setCellValue("A{$row}", $pol['objetivo']);
    $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true);
    $row += 2;

    // Alcance
    $sheet->mergeCells("A{$row}:B{$row}");
    $sheet->setCellValue("A{$row}", '2. ALCANCE');
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;

    $sheet->mergeCells("A{$row}:B{$row}");
    $sheet->setCellValue("A{$row}", $pol['alcance']);
    $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true);
    $row += 2;

    // Contenido
    $sheet->mergeCells("A{$row}:B{$row}");
    $sheet->setCellValue("A{$row}", '3. CONTENIDO DE LA POLÍTICA');
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;

    foreach ($pol['politica'] as $titulo => $contenido) {
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", $titulo);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E7E6E6');
        $row++;

        if (is_array($contenido)) {
            foreach ($contenido as $item) {
                if (!empty(trim($item))) {
                    $sheet->mergeCells("A{$row}:B{$row}");
                    $sheet->setCellValue("A{$row}", $item);
                    $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true);
                    $row++;
                }
            }
        } else {
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->setCellValue("A{$row}", $contenido);
            $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true);
            $row++;
        }
        $row++;
    }

    // Guardar
    $filename = $code . '_' . str_replace(' ', '_', $pol['titulo']) . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// EJECUTAR
if (!isset($politicas[$code])) {
    die('Política no encontrada: ' . $code);
}

$pol = $politicas[$code];

if ($format === 'docx') {
    generarWord($code, $pol);
} elseif ($format === 'xlsx') {
    generarExcel($code, $pol);
} else {
    die('Formato no soportado: ' . $format);
}
?>
