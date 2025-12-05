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
    ],

    'POL-SI-005' => [
        'titulo' => 'POLÍTICA DE GESTIÓN DE ACTIVOS DE INFORMACIÓN',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Establecer un marco para identificar, clasificar, inventariar y proteger los activos de información de la organización durante todo su ciclo de vida.',
        'alcance' => 'Todos los activos de información: hardware, software, datos, personal, servicios, ubicaciones físicas y reputación.',
        'definiciones' => [
            'Activo' => 'Cualquier cosa que tiene valor para la organización',
            'Propietario de Activo' => 'Persona responsable de un activo',
            'Custodio' => 'Persona que administra técnicamente el activo',
            'Inventario de Activos' => 'Registro completo de todos los activos'
        ],
        'politica' => [
            '1. INVENTARIO DE ACTIVOS' => [
                'OBLIGATORIO MANTENER:',
                '- Inventario completo y actualizado',
                '- Registro en sistema centralizado',
                '- Actualización ante cambios (máximo 48h)',
                '- Revisión trimestral de exactitud',
                '- Auditoría anual de inventario',
                '',
                'INFORMACIÓN MÍNIMA POR ACTIVO:',
                '- Código único de identificación',
                '- Nombre y descripción',
                '- Tipo y categoría',
                '- Ubicación física/lógica',
                '- Propietario y custodio',
                '- Clasificación de seguridad',
                '- Valor para la organización',
                '- Estado (activo, en mantenimiento, retirado)',
                '- Dependencias con otros activos',
                '- Fecha de adquisición y vida útil'
            ],
            '2. CLASIFICACIÓN DE ACTIVOS' => [
                'CATEGORÍAS DE ACTIVOS:',
                '',
                'INFORMACIÓN:',
                '- Bases de datos',
                '- Archivos y documentos',
                '- Información en papel',
                '- Contratos y acuerdos',
                '- Planes y procedimientos',
                '',
                'SOFTWARE:',
                '- Aplicaciones de negocio',
                '- Sistemas operativos',
                '- Herramientas de desarrollo',
                '- Utilidades y firmware',
                '',
                'HARDWARE:',
                '- Servidores y estaciones',
                '- Equipos de red',
                '- Dispositivos móviles',
                '- Medios de almacenamiento',
                '- Equipos de comunicación',
                '',
                'SERVICIOS:',
                '- Servicios cloud',
                '- Conectividad',
                '- Servicios de terceros',
                '',
                'PERSONAS:',
                '- Personal clave',
                '- Conocimiento especializado',
                '',
                'INTANGIBLES:',
                '- Reputación',
                '- Imagen de marca',
                '- Confianza de clientes'
            ],
            '3. RESPONSABILIDADES' => [
                'PROPIETARIO DE ACTIVO:',
                '- Clasificar el activo',
                '- Definir controles de protección',
                '- Autorizar accesos',
                '- Determinar retención y disposición',
                '- Revisar periódicamente seguridad',
                '',
                'CUSTODIO:',
                '- Implementar controles técnicos',
                '- Mantener activo operativo',
                '- Realizar respaldos si aplica',
                '- Reportar incidentes',
                '- Aplicar parches y actualizaciones',
                '',
                'USUARIOS:',
                '- Usar activos solo para fines autorizados',
                '- Proteger activos asignados',
                '- Reportar pérdida o daño',
                '- No transferir sin autorización',
                '- Devolver al término de uso',
                '',
                'GESTOR DE ACTIVOS:',
                '- Mantener inventario actualizado',
                '- Coordinar asignación de propietarios',
                '- Auditar cumplimiento',
                '- Generar reportes'
            ],
            '4. USO ACEPTABLE' => [
                '- Solo para propósitos de negocio autorizados',
                '- Respet ar clasificación y controles',
                '- No uso personal excesivo',
                '- Prohibido préstamo a terceros sin autorización',
                '- Reportar malfuncionamiento inmediatamente',
                '- No modificación no autorizada'
            ],
            '5. RETORNO DE ACTIVOS' => [
                'AL TÉRMINO DE RELACIÓN LABORAL:',
                '- Entrega completa de todos los activos',
                '- Firma de acta de entrega',
                '- Devolución de credenciales',
                '- Eliminación de accesos',
                '- Validación de no retención de copias',
                '',
                'AL CAMBIO DE FUNCIÓN:',
                '- Revisión de activos asignados',
                '- Retorno de activos no necesarios',
                '- Reasignación según nueva función'
            ],
            '6. DISPOSICIÓN FINAL' => [
                'DESTRUCCIÓN SEGURA:',
                '- Datos en medios magnéticos: Degaussing',
                '- Discos duros: Destrucción física certificada',
                '- Documentos papel: Trituración cruzada',
                '- Dispositivos móviles: Factory reset + destrucción SIM',
                '- Certificado de destrucción obligatorio',
                '',
                'DONACIÓN O VENTA:',
                '- Sanitización completa certificada',
                '- Múltiples pasadas de sobrescritura',
                '- Verificación de eliminación',
                '- Documentar destino final',
                '- Aprobación de propietario'
            ],
            '7. ETIQUETADO FÍSICO' => [
                '- Etiqueta con código de activo',
                '- Código de barras o QR',
                '- Marca de clasificación si aplica',
                '- Información de contacto propietario',
                '- Etiquetas permanentes y durables'
            ]
        ],
        'cumplimiento' => 'El uso indebido o pérdida de activos puede resultar en responsabilidad de reposición y acciones disciplinarias.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.5.9, A.5.10, A.5.11 - Gestión de activos'
    ],

    'POL-SI-006' => [
        'titulo' => 'POLÍTICA DE DESARROLLO SEGURO',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Asegurar que la seguridad esté integrada en todo el ciclo de vida del desarrollo de software y que las aplicaciones sean desarrolladas siguiendo prácticas seguras.',
        'alcance' => 'Todo desarrollo de software interno, outsourcing, adquisición de software y mantenimiento de aplicaciones.',
        'politica' => [
            '1. CICLO DE VIDA SEGURO (SDLC)' => [
                'FASE DE REQUISITOS:',
                '- Definir requisitos de seguridad',
                '- Identificar datos sensibles a procesar',
                '- Definir niveles de acceso',
                '- Establecer requisitos de auditoría',
                '- Cumplimiento regulatorio',
                '',
                'FASE DE DISEÑO:',
                '- Modelado de amenazas (STRIDE, DREAD)',
                '- Arquitectura de seguridad',
                '- Principio de menor privilegio',
                '- Defensa en profundidad',
                '- Revisión de diseño seguro',
                '',
                'FASE DE DESARROLLO:',
                '- Coding standards seguros (OWASP)',
                '- Code review de seguridad',
                '- Análisis estático (SAST)',
                '- Gestión segura de secretos',
                '- Sin contraseñas en código',
                '',
                'FASE DE PRUEBAS:',
                '- Pruebas de seguridad funcional',
                '- Análisis dinámico (DAST)',
                '- Pruebas de penetración',
                '- Fuzzing',
                '- Validación OWASP Top 10',
                '',
                'FASE DE DESPLIEGUE:',
                '- Hardening de servidores',
                '- Configuración segura',
                '- Segregación de ambientes',
                '- Despliegue automatizado',
                '- Rollback plan',
                '',
                'FASE DE MANTENIMIENTO:',
                '- Gestión de vulnerabilidades',
                '- Parches de seguridad',
                '- Monitoreo continuo',
                '- Respuesta a incidentes'
            ],
            '2. ESTÁNDARES DE CODIFICACIÓN' => [
                'OBLIGATORIO SEGUIR:',
                '- OWASP Secure Coding Practices',
                '- CWE Top 25',
                '- Guías del lenguaje específico',
                '- Estándares internos documentados',
                '',
                'VALIDACIÓN DE ENTRADA:',
                '- Validar TODA entrada de usuario',
                '- Lista blanca preferida sobre lista negra',
                '- Sanitización de datos',
                '- Longitud máxima',
                '- Tipo de dato correcto',
                '- Encoding apropiado',
                '',
                'AUTENTICACIÓN Y SESIONES:',
                '- No reinventar autenticación',
                '- Usar frameworks probados',
                '- Tokens de sesión aleatorios',
                '- Timeout de sesiones',
                '- Logout seguro',
                '- Protección CSRF',
                '',
                'CONTROL DE ACCESO:',
                '- Validar autorización en cada request',
                '- Principio de menor privilegio',
                '- Rechazar por defecto',
                '- No confiar en cliente',
                '',
                'CRIPTOGRAFÍA:',
                '- Usar librerías estándar',
                '- No inventar algoritmos',
                '- Gestión segura de llaves',
                '- TLS para datos en tránsito',
                '',
                'GESTIÓN DE ERRORES:',
                '- No revelar información sensible',
                '- Mensajes genéricos al usuario',
                '- Logging detallado interno',
                '- Página de error personalizada',
                '',
                'LOGGING Y AUDITORÍA:',
                '- Registrar eventos de seguridad',
                '- Incluir timestamp, usuario, acción',
                '- Proteger integridad de logs',
                '- No registrar datos sensibles',
                '- Retención según política'
            ],
            '3. ANÁLISIS DE SEGURIDAD' => [
                'ANÁLISIS ESTÁTICO (SAST):',
                '- Obligatorio antes de cada release',
                '- Integrado en CI/CD pipeline',
                '- Remediar vulnerabilidades críticas y altas',
                '- Documentar falsos positivos',
                '',
                'ANÁLISIS DINÁMICO (DAST):',
                '- En ambiente de QA',
                '- Simular ataques reales',
                '- Validar OWASP Top 10',
                '- Ejecutar antes de producción',
                '',
                'SCA (Software Composition Analysis):',
                '- Escanear dependencias de terceros',
                '- Identificar CVEs conocidos',
                '- Actualizar librerías vulnerables',
                '- Verificar licencias',
                '',
                'PRUEBAS DE PENETRACIÓN:',
                '- Anual para aplicaciones críticas',
                '- Antes de lanzamiento mayor',
                '- Por equipo independiente o externo',
                '- Plan de remediación de hallazgos'
            ],
            '4. GESTIÓN DE CÓDIGO FUENTE' => [
                'REPOSITORIO:',
                '- Git obligatorio',
                '- Repositorio privado',
                '- Control de acceso estricto',
                '- Rama protegida para producción',
                '- Code review obligatorio',
                '',
                'SECRETOS:',
                '- NUNCA en código fuente',
                '- Usar vault/secrets manager',
                '- Escaneo automático de secretos',
                '- Rotación periódica',
                '',
                'VERSIONADO:',
                '- Semantic versioning',
                '- Tags para releases',
                '- Changelog de seguridad',
                '- Trazabilidad completa'
            ],
            '5. AMBIENTES' => [
                'SEGREGACIÓN OBLIGATORIA:',
                '- Desarrollo',
                '- QA/Testing',
                '- Pre-producción',
                '- Producción',
                '',
                'PRODUCCIÓN:',
                '- No desarrollo directo',
                '- Datos anonimizados si se copian',
                '- Acceso limitado',
                '- Auditoría completa',
                '- Despliegue solo automatizado'
            ],
            '6. DEPENDENCIAS DE TERCEROS' => [
                '- Inventario de todas las librerías',
                '- Verificar fuentes confiables',
                '- Análisis de vulnerabilidades',
                '- Actualización periódica',
                '- Licencias compatibles',
                '- Validar integridad (checksums)'
            ],
            '7. APIs' => [
                '- Autenticación obligatoria',
                '- Rate limiting',
                '- Validación de entrada',
                '- Versionado de API',
                '- Documentación de seguridad',
                '- Monitoreo de uso'
            ]
        ],
        'cumplimiento' => 'El despliegue de código que no cumpla estándares de seguridad será rechazado.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.8.25 - A.8.29 - Desarrollo seguro'
    ],

    'POL-SI-008' => [
        'titulo' => 'POLÍTICA DE RESPALDO Y RECUPERACIÓN',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Garantizar la disponibilidad e integridad de la información mediante respaldos regulares y procedimientos de recuperación probados.',
        'alcance' => 'Todos los sistemas críticos y datos de la organización.',
        'politica' => [
            '1. FRECUENCIA DE RESPALDOS' => [
                'DATOS CRÍTICOS:',
                '- Respaldo: Continuo o cada hora',
                '- Tipo: Incremental',
                '- Retención: 30 días online + 1 año archivo',
                '- RPO (Recovery Point Objective): 1 hora',
                '- RTO (Recovery Time Objective): 4 horas',
                '',
                'DATOS IMPORTANTES:',
                '- Respaldo: Diario',
                '- Tipo: Incremental diario, completo semanal',
                '- Retención: 7 días online + 90 días archivo',
                '- RPO: 24 horas',
                '- RTO: 24 horas',
                '',
                'DATOS NORMALES:',
                '- Respaldo: Semanal',
                '- Tipo: Completo semanal',
                '- Retención: 30 días',
                '- RPO: 7 días',
                '- RTO: 72 horas'
            ],
            '2. TIPOS DE RESPALDO' => [
                'COMPLETO (FULL):',
                '- Todo el sistema/datos',
                '- Semanal para sistemas',
                '- Mensual archivado',
                '',
                'INCREMENTAL:',
                '- Solo cambios desde último respaldo',
                '- Diario',
                '- Rápido y eficiente',
                '',
                'DIFERENCIAL:',
                '- Cambios desde último completo',
                '- Alternativa a incremental',
                '',
                'SNAPSHOT:',
                '- Imagen del sistema en un momento',
                '- Antes de cambios mayores',
                '- Recuperación rápida'
            ],
            '3. ALMACENAMIENTO' => [
                'REGLA 3-2-1:',
                '- 3 copias de datos',
                '- 2 tipos de medios diferentes',
                '- 1 copia offsite',
                '',
                'PRIMARIO:',
                '- Storage en datacenter',
                '- Acceso rápido',
                '- Retención corta',
                '',
                'SECUNDARIO:',
                '- Cloud storage',
                '- Geografía diferente',
                '- Retención media',
                '',
                'TERCIARIO:',
                '- Cold storage / tape',
                '- Archivo largo plazo',
                '- Inmutable'
            ],
            '4. CIFRADO DE RESPALDOS' => [
                '- AES-256 obligatorio',
                '- Cifrado en origen',
                '- Gestión de llaves separada',
                '- Cifrado en tránsito',
                '- Cifrado en reposo'
            ],
            '5. VERIFICACIÓN' => [
                'INTEGRIDAD:',
                '- Checksums MD5/SHA-256',
                '- Validación automática post-respaldo',
                '- Alerta si falla verificación',
                '',
                'RESTAURACIÓN:',
                '- Prueba mensual de restauración',
                '- Restauración completa trimestral',
                '- Documentar tiempo de recuperación',
                '- Validar integridad de datos restaurados'
            ],
            '6. MONITOREO' => [
                '- Alertas de respaldos fallidos',
                '- Dashboard de estado',
                '- Reporte semanal a TI',
                '- Reporte mensual a dirección',
                '- Métricas: Tasa de éxito, tiempo, tamaño'
            ],
            '7. RETENCIÓN' => [
                'LEGAL:',
                '- Según requisitos legales',
                '- Mínimo 7 años para documentos fiscales',
                '- Conservar evidencia de litigios',
                '',
                'OPERACIONAL:',
                '- Según criticidad',
                '- Balance costo-beneficio',
                '- Revisar anualmente'
            ],
            '8. RECUPERACIÓN' => [
                'PROCEDIMIENTO DOCUMENTADO:',
                '- Paso a paso',
                '- Tiempos estimados',
                '- Puntos de contacto',
                '- Checklist de validación',
                '',
                'PRIORIZACIÓN:',
                '- Sistemas críticos primero',
                '- Según BIA (Business Impact Analysis)',
                '',
                'VALIDACIÓN POST-RECUPERACIÓN:',
                '- Integridad de datos',
                '- Funcionalidad de aplicaciones',
                '- Desempeño',
                '- Conexiones',
                '- Documentar lecciones aprendidas'
            ]
        ],
        'cumplimiento' => 'Fallas recurrentes en respaldos sin acción correctiva es causa de responsabilidad.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.8.13 - Copias de respaldo'
    ],

    'POL-SI-010' => [
        'titulo' => 'POLÍTICA DE SEGURIDAD FÍSICA Y AMBIENTAL',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Proteger las instalaciones, equipos e infraestructura de la organización contra amenazas físicas y ambientales.',
        'alcance' => 'Todas las instalaciones, centros de datos, oficinas y áreas donde se procesan datos de la organización.',
        'politica' => [
            '1. PERÍMETRO DE SEGURIDAD' => [
                'ÁREAS CLASIFICADAS:',
                '',
                'ZONA PÚBLICA:',
                '- Recepción, lobby',
                '- Acceso sin restricción',
                '- Supervisión por recepcionista',
                '',
                'ZONA RESTRINGIDA:',
                '- Oficinas generales',
                '- Tarjeta de acceso obligatoria',
                '- Solo empleados y visitantes autorizados',
                '',
                'ZONA DE ALTA SEGURIDAD:',
                '- Centro de datos, sala de servidores',
                '- Doble autenticación (tarjeta + biometría)',
                '- Lista de acceso aprobada',
                '- Registro de todos los ingresos',
                '- Acompañamiento obligatorio para visitas'
            ],
            '2. CONTROL DE ACCESO FÍSICO' => [
                'EMPLEADOS:',
                '- Tarjeta de identificación visible',
                '- Credencial con foto y nombre',
                '- Reportar pérdida inmediatamente',
                '- Devolver al término de relación',
                '',
                'VISITANTES:',
                '- Registro obligatorio',
                '- Gafete temporal visible',
                '- Acompañamiento permanente',
                '- Entrega de gafete al salir',
                '- No acceso a áreas restringidas',
                '',
                'PROVEEDORES/MANTENIMIENTO:',
                '- Autorización previa',
                '- Verificación de identidad',
                '- Escolta permanente',
                '- Inspección de herramientas/equipos',
                '- Registro de trabajos realizados'
            ],
            '3. CENTRO DE DATOS' => [
                'ACCESO:',
                '- Lista de acceso autorizado (máximo 10 personas)',
                '- Doble factor: Tarjeta + biometría',
                '- Registro biométrico de entrada/salida',
                '- Videovigilancia 24/7 con grabación 90 días',
                '- Alarma de puerta abierta',
                '',
                'CONTROLES AMBIENTALES:',
                '- Aire acondicionado redundante',
                '- Temperatura: 18-27°C',
                '- Humedad: 45-55%',
                '- Monitoreo continuo de temperatura y humedad',
                '- Alertas automáticas',
                '',
                'ENERGÍA:',
                '- UPS (Uninterruptible Power Supply)',
                '- Autonomía mínima: 30 minutos',
                '- Generador eléctrico de respaldo',
                '- Prueba mensual de generador',
                '- Mantenimiento trimestral',
                '',
                'DETECCIÓN Y SUPRESIÓN DE INCENDIOS:',
                '- Detectores de humo',
                '- Sistema de supresión (gas inerte o rociadores pre-acción)',
                '- Alarma audible y visual',
                '- Inspección anual de sistemas',
                '- Extintores tipo C ubicados estratégicamente',
                '',
                'PROTECCIÓN CONTRA AGUA:',
                '- Detectores de agua en piso',
                '- Alejado de tuberías de agua',
                '- Drenaje de piso',
                '',
                'CABLEADO:',
                '- Cableado estructurado',
                '- Etiquetado claro',
                '- Piso falso o bandejas',
                '- Protección física',
                '- Segregación de energía y datos'
            ],
            '4. OFICINAS Y ÁREAS DE TRABAJO' => [
                'ESCRITORIO LIMPIO (CLEAR DESK):',
                '- Guardar documentos sensibles al finalizar',
                '- No dejar información visible',
                '- Bloquear estación de trabajo al ausentarse',
                '- No passwords escritos visibles',
                '',
                'PANTALLA LIMPIA (CLEAR SCREEN):',
                '- Bloqueo automático tras 5 min inactividad',
                '- Protector de pantalla con contraseña',
                '- Posicionar pantalla para evitar "shoulder surfing"',
                '',
                'IMPRESORAS/FOTOCOPIADORAS:',
                '- Retirar documentos inmediatamente',
                '- Destrucción segura de descartes',
                '- Limpieza de memoria al retirar equipo'
            ],
            '5. VIDEOVIGILANCIA' => [
                'UBICACIONES:',
                '- Entradas y salidas',
                '- Estacionamientos',
                '- Perímetro exterior',
                '- Centro de datos',
                '- Áreas de almacenamiento',
                '',
                'GESTIÓN:',
                '- Grabación continua 24/7',
                '- Retención: 30 días (90 días datacenter)',
                '- Acceso restringido a grabaciones',
                '- Backup de grabaciones críticas',
                '- Aviso visible de cámaras'
            ],
            '6. ALARMAS' => [
                '- Sistema de alarma perimetral',
                '- Activación fuera de horario',
                '- Conexión a central de monitoreo',
                '- Respuesta en menos de 15 minutos',
                '- Prueba mensual'
            ],
            '7. EQUIPOS PORTÁTILES' => [
                '- No dejar en vehículos visibles',
                '- Candado de seguridad en oficina',
                '- Cifrado de disco completo',
                '- Reporte inmediato de pérdida',
                '- Borrado remoto activado'
            ],
            '8. DESASTRES NATURALES' => [
                '- Plan de evacuación publicado',
                '- Simulacros semestrales',
                '- Punto de reunión definido',
                '- Lista de emergencias actualizada',
                '- Kit de emergencia',
                '- Respaldos offsite geográficamente distantes'
            ]
        ],
        'cumplimiento' => 'Permitir acceso no autorizado a áreas restringidas es una violación grave.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.7.1 - A.7.14 - Seguridad física y ambiental'
    ],

    'POL-SI-011' => [
        'titulo' => 'POLÍTICA DE GESTIÓN DE PROVEEDORES Y TERCEROS',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Asegurar que los proveedores y terceros que acceden a información o sistemas de la organización cumplan con requisitos de seguridad adecuados.',
        'alcance' => 'Todos los proveedores de servicios, outsourcing, consultores y terceros que procesan, almacenan o transmiten información de la organización.',
        'politica' => [
            '1. CICLO DE VIDA DEL PROVEEDOR' => [
                'FASE 1 - SELECCIÓN:',
                '- Evaluación de seguridad inicial',
                '- Due diligence',
                '- Verificación de certificaciones (ISO 27001, SOC 2)',
                '- Referencias de clientes',
                '- Revisión de políticas de seguridad',
                '',
                'FASE 2 - CONTRATACIÓN:',
                '- Acuerdo de confidencialidad (NDA)',
                '- Cláusulas de seguridad en contrato',
                '- SLA de seguridad',
                '- Derecho a auditar',
                '- Notificación de brechas',
                '- Responsabilidad por daños',
                '- Terminación por incumplimiento',
                '',
                'FASE 3 - OPERACIÓN:',
                '- Monitoreo de cumplimiento',
                '- Auditorías periódicas',
                '- Revisión de reportes SOC',
                '- Gestión de incidentes',
                '- Evaluaciones anuales',
                '',
                'FASE 4 - TERMINACIÓN:',
                '- Devolución o destrucción de información',
                '- Certificado de destrucción',
                '- Revocación de accesos',
                '- Finalización de conexiones',
                '- Auditoría de salida'
            ],
            '2. CLASIFICACIÓN DE PROVEEDORES' => [
                'CRÍTICOS:',
                '- Procesan datos confidenciales',
                '- Servicios críticos de negocio',
                '- Acceso a sistemas productivos',
                '- Requisitos: ISO 27001, auditoría anual',
                '',
                'IMPORTANTES:',
                '- Procesan datos internos',
                '- Servicios importantes',
                '- Acceso limitado',
                '- Requisitos: Políticas documentadas, SOC 2',
                '',
                'NORMALES:',
                '- Datos públicos solamente',
                '- Servicios de bajo impacto',
                '- Sin acceso a sistemas',
                '- Requisitos: NDA, políticas básicas'
            ],
            '3. REQUISITOS MÍNIMOS' => [
                'OBLIGATORIO PARA TODOS:',
                '- NDA firmado',
                '- Seguro de responsabilidad',
                '- Plan de continuidad de negocio',
                '- Política de seguridad documentada',
                '- Programa de capacitación',
                '',
                'CRÍTICOS ADICIONAL:',
                '- Certificación ISO 27001',
                '- SOC 2 Tipo II',
                '- Pruebas de penetración anuales',
                '- Cifrado de datos',
                '- MFA implementado',
                '- SOC (Security Operations Center)',
                '- Plan de respuesta a incidentes'
            ],
            '4. CONTROL DE ACCESO' => [
                'PRINCIPIOS:',
                '- Menor privilegio',
                '- Acceso justo a tiempo',
                '- Acceso temporal',
                '- Monitoreo de actividades',
                '',
                'ACCESO REMOTO:',
                '- VPN corporativa obligatoria',
                '- Cuentas individuales (no compartidas)',
                '- MFA obligatorio',
                '- Sesiones registradas',
                '- Restricción de horarios',
                '',
                'ACCESO FÍSICO:',
                '- Autorización previa',
                '- Gafete visible',
                '- Escolta permanente',
                '- Registro de entrada/salida',
                '- Inspección de equipos'
            ],
            '5. CLOUD SERVICE PROVIDERS' => [
                'REQUISITOS:',
                '- Certificaciones: ISO 27001, SOC 2, CSA STAR',
                '- Ubicación de datos conocida',
                '- Cifrado de datos en reposo y tránsito',
                '- Segregación de datos por cliente',
                '- Backup y recuperación',
                '- Portabilidad de datos',
                '',
                'SaaS:',
                '- SSO (Single Sign-On) integrado',
                '- MFA soportado',
                '- API para integración',
                '- Logs de auditoría disponibles',
                '- SLA de disponibilidad',
                '',
                'IaaS/PaaS:',
                '- Controles de identidad robustos',
                '- Segmentación de red',
                '- Monitoreo y alertas',
                '- Compliance reports',
                '- DDoS protection'
            ],
            '6. SUBCONTRATACIÓN' => [
                '- Notificación obligatoria a cliente',
                '- Aprobación previa requerida',
                '- Mismo nivel de seguridad',
                '- Responsabilidad solidaria',
                '- Derecho a auditar subcontratistas'
            ],
            '7. GESTIÓN DE INCIDENTES' => [
                'NOTIFICACIÓN:',
                '- Incidentes de seguridad: Dentro de 24 horas',
                '- Brechas de datos: Dentro de 2 horas',
                '- Caídas de servicio: Inmediato',
                '',
                'RESPUESTA:',
                '- Proveedor debe cooperar en investigación',
                '- Preservación de evidencia',
                '- Análisis de causa raíz',
                '- Plan de remediación',
                '- Notificación a afectados si corresponde'
            ],
            '8. AUDITORÍA Y CUMPLIMIENTO' => [
                'DERECHO A AUDITAR:',
                '- Acceso a instalaciones',
                '- Revisión de controles',
                '- Inspección de registros',
                '- Entrevistas con personal',
                '',
                'FRECUENCIA:',
                '- Críticos: Anual',
                '- Importantes: Cada 2 años',
                '- Por causa: Si hay incidente mayor',
                '',
                'REPORTES:',
                '- SOC 2 Tipo II anual',
                '- Pen test anual',
                '- Vulnerability scans trimestrales'
            ]
        ],
        'cumplimiento' => 'Contratación de proveedores sin evaluación de seguridad es prohibido.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.5.19 - A.5.23 - Seguridad en relaciones con proveedores'
    ],

    'POL-SI-012' => [
        'titulo' => 'POLÍTICA DE SEGURIDAD EN LA NUBE',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Establecer controles de seguridad para el uso de servicios cloud (SaaS, PaaS, IaaS) y proteger los datos de la organización en entornos cloud.',
        'alcance' => 'Todos los servicios cloud utilizados por la organización y datos almacenados en la nube.',
        'politica' => [
            '1. SELECCIÓN DE PROVEEDORES CLOUD' => [
                'REQUISITOS OBLIGATORIOS:',
                '- Certificación ISO 27001',
                '- SOC 2 Tipo II',
                '- Certificación CSA STAR',
                '- Cumplimiento GDPR si datos personales',
                '- SLA de disponibilidad mínimo 99.9%',
                '- Data center en jurisdicción aprobada',
                '- Portabilidad y extracción de datos',
                '- Derecho a auditoría',
                '',
                'EVALUACIÓN:',
                '- Revisar certificaciones',
                '- Analizar políticas de seguridad',
                '- Verificar ubicación de datos',
                '- Revisar términos de servicio',
                '- Evaluar costos de migración/salida'
            ],
            '2. MODELOS DE NUBE' => [
                'CLOUD PÚBLICA:',
                '- Solo para datos clasificados como Públicos o Internos',
                '- Cifrado obligatorio',
                '- Aprobación de seguridad requerida',
                '',
                'CLOUD PRIVADA:',
                '- Para datos Confidenciales',
                '- Segregación de red',
                '- Control total de infraestructura',
                '',
                'CLOUD HÍBRIDA:',
                '- Clasificar qué datos van a cada segmento',
                '- Conexión segura entre entornos',
                '- Políticas consistentes'
            ],
            '3. GESTIÓN DE IDENTIDADES' => [
                'SSO (Single Sign-On):',
                '- Implementar SSO cuando el servicio lo soporte',
                '- Federación con Active Directory',
                '- SAML 2.0 u OAuth 2.0',
                '',
                'MFA:',
                '- Obligatorio para todos los servicios cloud',
                '- Preferir aplicación autenticadora',
                '- No SMS como único factor',
                '',
                'CUENTAS:',
                '- Cuentas nominales (no compartidas)',
                '- Deshabilitar usuarios locales si hay SSO',
                '- Provisioning/Deprovisioning automatizado',
                '- Revisión trimestral de accesos'
            ],
            '4. PROTECCIÓN DE DATOS' => [
                'CIFRADO:',
                '- Datos en tránsito: TLS 1.2+ obligatorio',
                '- Datos en reposo: Cifrado a nivel de aplicación',
                '- Llaves gestionadas por cliente (BYOK) cuando posible',
                '- No almacenar llaves en mismo proveedor que datos',
                '',
                'CLASIFICACIÓN:',
                '- Etiquetar datos según clasificación',
                '- DLP (Data Loss Prevention) activado',
                '- Prevenir compartir público accidental',
                '',
                'RESPALDOS:',
                '- No confiar solo en proveedor cloud',
                '- Respaldos propios en ubicación separada',
                '- Probar restauración regularmente'
            ],
            '5. SEGURIDAD EN SaaS' => [
                'CONFIGURACIÓN:',
                '- Revisar configuraciones de seguridad',
                '- Deshabilitar funciones no necesarias',
                '- Aplicar baseline de seguridad',
                '- CASB (Cloud Access Security Broker) si disponible',
                '',
                'INTEGRACIONES:',
                '- Revisar permisos de apps de terceros',
                '- Auditar integraciones periódicamente',
                '- Revocar integraciones no usadas',
                '',
                'COMPARTIR:',
                '- Restringir compartir externo',
                '- Links con expiración',
                '- Notificar cuando se comparte información sensible'
            ],
            '6. SEGURIDAD EN IaaS/PaaS' => [
                'CONFIGURACIÓN DE INFRAESTRUCTURA:',
                '- Infrastructure as Code (Terraform, CloudFormation)',
                '- Versionado de configuraciones',
                '- Revisión de seguridad antes de deploy',
                '',
                'SEGMENTACIÓN:',
                '- VPCs separadas por ambiente',
                '- Subnets públicas y privadas',
                '- Security Groups restrictivos',
                '- Principio de menor privilegio en reglas',
                '',
                'IAM (Identity and Access Management):',
                '- Roles en lugar de usuarios',
                '- Políticas granulares',
                '- MFA en cuentas root',
                '- Rotar access keys cada 90 días',
                '',
                'MONITOREO:',
                '- CloudTrail / equivalente activado',
                '- Logs centralizados',
                '- Alertas de actividad sospechosa',
                '- Revisión de costos inusuales'
            ],
            '7. SHADOW IT' => [
                '- Prohibido uso de servicios cloud no aprobados',
                '- CASB para detectar shadow IT',
                '- Proceso claro para solicitar nuevos servicios',
                '- Educación sobre riesgos',
                '- Monitoreo de tráfico a servicios cloud'
            ],
            '8. CONTINUIDAD' => [
                '- No dependencia de un solo proveedor (evitar lock-in)',
                '- Plan de contingencia para caída de servicio',
                '- Respaldos independientes',
                '- Capacidad de migrar a otro proveedor',
                '- Probar plan de continuidad anualmente'
            ],
            '9. CUMPLIMIENTO Y AUDITORÍA' => [
                '- Revisar reportes SOC 2 anualmente',
                '- Verificar certificaciones vigentes',
                '- Auditar logs de acceso',
                '- Revisar facturas por uso inusual',
                '- Cumplir requisitos regulatorios (GDPR, etc.)'
            ]
        ],
        'cumplimiento' => 'Uso de servicios cloud no aprobados es violación grave de seguridad.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.5.23 - Seguridad en uso de servicios cloud'
    ],

    'POL-SI-014' => [
        'titulo' => 'POLÍTICA DE CONTINUIDAD DEL NEGOCIO',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Asegurar la capacidad de la organización para continuar operaciones críticas durante y después de incidentes disruptivos.',
        'alcance' => 'Todos los procesos de negocio, sistemas de información y recursos de la organización.',
        'politica' => [
            '1. ANÁLISIS DE IMPACTO (BIA)' => [
                'OBLIGATORIO REALIZAR:',
                '- BIA cada 12 meses',
                '- Identificar procesos críticos',
                '- Determinar RPO (Recovery Point Objective)',
                '- Determinar RTO (Recovery Time Objective)',
                '- Calcular MTD (Maximum Tolerable Downtime)',
                '- Identificar dependencias',
                '',
                'CLASIFICACIÓN DE PROCESOS:',
                'CRÍTICOS (RTO < 4 horas):',
                '- Sistemas de producción',
                '- Plataforma de ventas',
                '- Servicios al cliente',
                '',
                'IMPORTANTES (RTO < 24 horas):',
                '- ERP/CRM',
                '- Email corporativo',
                '- Intranet',
                '',
                'NORMALES (RTO < 72 horas):',
                '- Sistemas administrativos',
                '- Reportería'
            ],
            '2. PLAN DE CONTINUIDAD (BCP)' => [
                'CONTENIDO OBLIGATORIO:',
                '- Roles y responsabilidades',
                '- Procedimientos de activación',
                '- Estrategias de recuperación',
                '- Sitio alterno si aplica',
                '- Proveedores críticos',
                '- Lista de contactos',
                '- Dependencias de sistemas',
                '- Secuencia de recuperación',
                '',
                'ESTRATEGIAS:',
                'HOT SITE:',
                '- Datacenter alterno activo',
                '- Replicación en tiempo real',
                '- Para sistemas críticos',
                '',
                'WARM SITE:',
                '- Infraestructura lista, datos periódicos',
                '- Activación en horas',
                '- Para sistemas importantes',
                '',
                'COLD SITE:',
                '- Espacio disponible',
                '- Activación en días',
                '- Para sistemas normales'
            ],
            '3. PLAN DE RECUPERACIÓN (DRP)' => [
                'PROCEDIMIENTOS DOCUMENTADOS:',
                '- Paso a paso detallado',
                '- Comandos específicos',
                '- Tiempos estimados',
                '- Puntos de decisión',
                '- Criterios de éxito',
                '',
                'RUNBOOKS:',
                '- Uno por sistema crítico',
                '- Actualizado tras cada cambio',
                '- Probado regularmente',
                '- Accesible offsite'
            ],
            '4. COMUNICACIÓN DE CRISIS' => [
                'PLAN DE COMUNICACIÓN:',
                '',
                'INTERNA:',
                '- Empleados: Múltiples canales',
                '- Gerencia: Cadena de mando',
                '- TI: Canal técnico dedicado',
                '',
                'EXTERNA:',
                '- Clientes: Portal y email',
                '- Proveedores: Contactos clave',
                '- Medios: Vocero oficial',
                '- Reguladores: Según requisitos',
                '',
                'MENSAJES PREDEFINIDOS:',
                '- Plantillas para escenarios comunes',
                '- Aprobación de legal y comunicaciones',
                '- Actualización cada 6 meses'
            ],
            '5. EQUIPO DE RESPUESTA' => [
                'CRISIS MANAGEMENT TEAM (CMT):',
                '- Gerencia General (líder)',
                '- CISO',
                '- CTO',
                '- CFO',
                '- Legal',
                '- Comunicaciones',
                '',
                'DISASTER RECOVERY TEAM (DRT):',
                '- Líder técnico',
                '- Administradores de sistemas',
                '- DBAs',
                '- Redes',
                '- Seguridad',
                '',
                'DISPONIBILIDAD:',
                '- Contactables 24/7',
                '- Backup definido para cada rol',
                '- Lista de contactos actualizada',
                '- Herramientas de comunicación redundantes'
            ],
            '6. PRUEBAS Y EJERCICIOS' => [
                'OBLIGATORIO:',
                '',
                'TABLETOP EXERCISE (Anual):',
                '- Simulación de escenarios',
                '- Sin sistemas reales',
                '- Todo el CMT participa',
                '',
                'PRUEBA TÉCNICA (Semestral):',
                '- Recuperación de sistema no crítico',
                '- Verificar runbooks',
                '- Medir tiempos reales',
                '',
                'PRUEBA COMPLETA (Bianual):',
                '- Failover de sistemas críticos',
                '- Activación de sitio alterno',
                '- Participación completa',
                '- Medición de RTOs',
                '',
                'DOCUMENTACIÓN:',
                '- Informe de cada prueba',
                '- Lecciones aprendidas',
                '- Plan de acción correctivo',
                '- Actualización de planes'
            ],
            '7. MANTENIMIENTO' => [
                '- Revisión y actualización anual',
                '- Actualización tras cambios mayores',
                '- Actualización tras ejercicios',
                '- Actualización tras incidentes reales',
                '- Versionado de documentos',
                '- Distribución de versiones actualizadas'
            ],
            '8. PROVEEDORES CRÍTICOS' => [
                '- Identificar proveedores críticos',
                '- Exigir sus planes BCP/DRP',
                '- Definir SLAs de recuperación',
                '- Incluir en ejercicios',
                '- Tener proveedores alternos'
            ]
        ],
        'cumplimiento' => 'Falta de planes actualizados pone en riesgo la continuidad de la organización.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.5.29, A.5.30 - Continuidad del negocio'
    ],

    'POL-SI-015' => [
        'titulo' => 'POLÍTICA DE PRIVACIDAD Y PROTECCIÓN DE DATOS PERSONALES',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Garantizar el cumplimiento de leyes de protección de datos personales y respetar la privacidad de clientes, empleados y terceros.',
        'alcance' => 'Todos los datos personales procesados por la organización, en cualquier formato.',
        'definiciones' => [
            'Dato Personal' => 'Información sobre persona natural identificada o identificable',
            'Dato Sensible' => 'Origen racial, opiniones políticas, salud, vida sexual, etc.',
            'Tratamiento' => 'Cualquier operación sobre datos personales',
            'Responsable' => 'Quien decide sobre el tratamiento de datos',
            'Encargado' => 'Quien procesa datos por cuenta del responsable'
        ],
        'politica' => [
            '1. PRINCIPIOS' => [
                'LICITUD:',
                '- Base legal válida para tratamiento',
                '- Consentimiento informado cuando aplique',
                '- Cumplimiento de obligación legal',
                '- Ejecución de contrato',
                '- Interés legítimo justificado',
                '',
                'FINALIDAD:',
                '- Propósitos determinados y explícitos',
                '- No uso para fines incompatibles',
                '- Informar finalidad al titular',
                '',
                'MINIMIZACIÓN:',
                '- Solo datos necesarios',
                '- No recopilar datos innecesarios',
                '- Revisar periódicamente necesidad',
                '',
                'EXACTITUD:',
                '- Mantener datos actualizados',
                '- Corregir datos inexactos',
                '- Facilitar actualización a titulares',
                '',
                'LIMITACIÓN DE CONSERVACIÓN:',
                '- Conservar solo tiempo necesario',
                '- Definir plazos de retención',
                '- Eliminar al cumplir propósito',
                '',
                'INTEGRIDAD Y CONFIDENCIALIDAD:',
                '- Proteger contra acceso no autorizado',
                '- Proteger contra pérdida o daño',
                '- Medidas técnicas y organizativas'
            ],
            '2. DERECHOS DE LOS TITULARES' => [
                'ACCESO (ARCO):',
                '- Derecho a saber qué datos tenemos',
                '- Respuesta en máximo 15 días',
                '- Gratuito (primera vez)',
                '',
                'RECTIFICACIÓN:',
                '- Corregir datos inexactos',
                '- Completar datos incompletos',
                '',
                'CANCELACIÓN:',
                '- Solicitar eliminación de datos',
                '- Evaluar si hay obligación legal de retener',
                '',
                'OPOSICIÓN:',
                '- Oponerse a ciertos tratamientos',
                '- Especialmente marketing directo',
                '',
                'PORTABILIDAD:',
                '- Recibir datos en formato estructurado',
                '- Transmitir a otro responsable',
                '',
                'LIMITACIÓN:',
                '- Solicitar suspender tratamiento',
                '',
                'CANAL:',
                '- Formulario web',
                '- Email: privacidad@empresa.com',
                '- Correo postal',
                '- Presencial'
            ],
            '3. CATEGORÍAS DE DATOS' => [
                'DATOS DE EMPLEADOS:',
                '- Datos laborales y de nómina',
                '- Evaluaciones de desempeño',
                '- Datos biométricos (huella)',
                '- Monitoreo de correo/internet',
                '- Acceso restringido a RR.HH.',
                '',
                'DATOS DE CLIENTES:',
                '- Contacto y facturación',
                '- Historial de compras',
                '- Preferencias',
                '- No compartir sin consentimiento',
                '',
                'DATOS DE VISITANTES WEB:',
                '- Cookies y tracking',
                '- Aviso de cookies obligatorio',
                '- Opción de rechazar cookies no esenciales',
                '',
                'DATOS SENSIBLES:',
                '- Solo si estrictamente necesario',
                '- Consentimiento explícito',
                '- Protección adicional',
                '- Cifrado obligatorio'
            ],
            '4. CONSENTIMIENTO' => [
                'REQUISITOS:',
                '- Libre',
                '- Informado',
                '- Específico',
                '- Inequívoco',
                '- Documentado',
                '',
                'INFORMACIÓN OBLIGATORIA:',
                '- Identidad del responsable',
                '- Finalidades del tratamiento',
                '- Legitimación',
                '- Destinatarios',
                '- Plazo de conservación',
                '- Derechos del titular',
                '- Derecho a retirar consentimiento',
                '',
                'RETIRO:',
                '- Tan fácil como darlo',
                '- Mecanismo claro',
                '- Efecto inmediato'
            ],
            '5. TRANSFERENCIAS INTERNACIONALES' => [
                'PROHIBIDAS A:',
                '- Países sin protección adecuada',
                '',
                'PERMITIDAS CON:',
                '- Decisión de adecuación',
                '- Cláusulas contractuales tipo',
                '- Binding Corporate Rules',
                '- Certificación (ej: Privacy Shield histórico)',
                '',
                'DOCUMENTAR:',
                '- País destino',
                '- Finalidad',
                '- Garantías aplicadas',
                '- Evaluación de riesgo'
            ],
            '6. ENCARGADOS DEL TRATAMIENTO' => [
                'CONTRATO OBLIGATORIO:',
                '- Instrucciones específicas',
                '- Confidencialidad',
                '- Medidas de seguridad',
                '- Subencargados con autorización',
                '- Asistencia en derechos',
                '- Eliminación al término',
                '- Auditorías',
                '',
                'EVALUACIÓN:',
                '- Garantías suficientes',
                '- Certificaciones de seguridad',
                '- Referencias'
            ],
            '7. BRECHAS DE DATOS' => [
                'NOTIFICACIÓN A AUTORIDAD:',
                '- Dentro de 72 horas de conocer brecha',
                '- Si riesgo para titulares',
                '- Describir naturaleza, consecuencias, medidas',
                '',
                'COMUNICACIÓN A TITULARES:',
                '- Si alto riesgo para derechos',
                '- Lenguaje claro y sencillo',
                '- Medidas recomendadas',
                '',
                'REGISTRO:',
                '- Documentar todas las brechas',
                '- Aunque no se notifiquen',
                '- Disponible para autoridad'
            ],
            '8. EVALUACIÓN DE IMPACTO (DPIA)' => [
                'OBLIGATORIA SI:',
                '- Tratamiento a gran escala de datos sensibles',
                '- Monitoreo sistemático',
                '- Uso de nuevas tecnologías',
                '- Perfilado automatizado',
                '',
                'CONTENIDO:',
                '- Descripción del tratamiento',
                '- Necesidad y proporcionalidad',
                '- Riesgos para titulares',
                '- Medidas de mitigación'
            ],
            '9. DELEGADO DE PROTECCIÓN DE DATOS (DPO)' => [
                'FUNCIONES:',
                '- Informar y asesorar',
                '- Supervisar cumplimiento',
                '- Asesorar en DPIA',
                '- Cooperar con autoridad',
                '- Punto de contacto',
                '',
                'REQUISITOS:',
                '- Conocimiento especializado',
                '- Independencia',
                '- Recursos adecuados',
                '- Reporta a máximo nivel'
            ]
        ],
        'cumplimiento' => 'Incumplimiento de leyes de protección de datos puede resultar en multas severas.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.5.34 - Privacidad y protección de datos personales'
    ],

    'POL-SI-016' => [
        'titulo' => 'POLÍTICA DE TELETRABAJO Y TRABAJO REMOTO',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Establecer controles de seguridad para empleados que trabajan fuera de las instalaciones de la organización.',
        'alcance' => 'Todos los empleados, contratistas y terceros que realizan teletrabajo o acceso remoto.',
        'politica' => [
            '1. ELEGIBILIDAD' => [
                '- Aprobación de gerente directo requerida',
                '- Evaluación de función (no todas aplican)',
                '- Firma de addendum de teletrabajo',
                '- Capacitación de seguridad obligatoria',
                '- Espacio de trabajo adecuado en casa'
            ],
            '2. EQUIPOS' => [
                'EQUIPOS CORPORATIVOS:',
                '- Preferido: Laptop corporativa',
                '- Cifrado de disco completo activado',
                '- Antivirus actualizado',
                '- Firewall personal activo',
                '- Software corporativo preinstalado',
                '- MDM (Mobile Device Management) instalado',
                '- Bloqueo automático 5 minutos',
                '',
                'BYOD (Bring Your Own Device):',
                '- Solo si autorizado expresamente',
                '- Contenedor corporativo (sandbox)',
                '- No mezclar datos personales/corporativos',
                '- Aceptar borrado remoto de datos corporativos',
                '- Cumplir requisitos técnicos mínimos'
            ],
            '3. CONECTIVIDAD' => [
                'ACCESO REMOTO:',
                '- VPN corporativa obligatoria',
                '- MFA activado',
                '- Split tunneling prohibido (todo tráfico por VPN)',
                '- Timeout de sesión: 12 horas',
                '',
                'RED DOMÉSTICA:',
                '- WiFi con WPA2 o WPA3',
                '- Cambiar contraseña por defecto del router',
                '- No usar WiFi público sin VPN',
                '- Separar red personal de corporativa si posible',
                '',
                'PROHIBIDO:',
                '- WiFi de cafeterías/aeropuertos sin VPN',
                '- Redes abiertas sin cifrado',
                '- Compartir conexión desde móvil sin VPN'
            ],
            '4. SEGURIDAD FÍSICA' => [
                'ESPACIO DE TRABAJO:',
                '- Área dedicada si posible',
                '- Puerta con cerradura o privacidad',
                '- No visible desde ventanas',
                '- Pantalla no visible para visitantes',
                '',
                'PROTECCIÓN DE EQUIPOS:',
                '- No dejar laptop desatendida',
                '- Bloquear pantalla al alejarse',
                '- Cable de seguridad si en espacio compartido',
                '- No permitir uso familiar de equipo',
                '',
                'DOCUMENTOS:',
                '- Armario con llave para documentos',
                '- Destrucción segura en casa',
                '- No tirar documentos sensibles a basura común'
            ],
            '5. COMUNICACIONES' => [
                'HERRAMIENTAS APROBADAS:',
                '- Email corporativo',
                '- Microsoft Teams / Slack corporativo',
                '- Zoom/Webex corporativo',
                '',
                'VIDEOCONFERENCIAS:',
                '- Fondo virtual o difuminado',
                '- Verificar que no hay info sensible visible',
                '- Sala de espera activa',
                '- Contraseña para reuniones sensibles',
                '- Grabación solo si autorizada',
                '',
                'PROHIBIDO:',
                '- WhatsApp para información confidencial',
                '- Email personal para temas corporativos',
                '- Herramientas no aprobadas'
            ],
            '6. ALMACENAMIENTO DE DATOS' => [
                'PERMITIDO:',
                '- OneDrive/SharePoint corporativo',
                '- Disco local cifrado',
                '- Repositorios corporativos',
                '',
                'PROHIBIDO:',
                '- Dropbox personal',
                '- Google Drive personal',
                '- USB no cifrados',
                '- Email personal como backup',
                '',
                'BUENAS PRÁCTICAS:',
                '- Sincronizar archivos con nube corporativa',
                '- No acumular archivos solo en local',
                '- Respaldar regularmente'
            ],
            '7. HORARIOS Y DISPONIBILIDAD' => [
                '- Respetar horario laboral acordado',
                '- Disponibilidad por canales corporativos',
                '- Respuesta en tiempos razonables',
                '- Indicar disponibilidad en calendar/status',
                '- Derecho a desconexión fuera de horario'
            ],
            '8. VISITAS Y FAMILIA' => [
                '- No permitir uso de equipo a familiares',
                '- No discutir temas confidenciales con familia presente',
                '- Cerrar sesiones durante visitas',
                '- Pantallas no visibles para visitantes',
                '- No dejar documentos a la vista'
            ],
            '9. INCIDENTES' => [
                'REPORTAR INMEDIATAMENTE:',
                '- Pérdida o robo de equipo',
                '- Acceso no autorizado',
                '- Infección de malware',
                '- Fallo de equipo',
                '- Pérdida de datos',
                '',
                'CONTACTO 24/7:',
                '- Helpdesk: extension@empresa.com',
                '- Seguridad: security@empresa.com',
                '- Teléfono: +56 X XXXX XXXX'
            ],
            '10. ERGONOMÍA Y SALUD' => [
                '- Silla ergonómica',
                '- Escritorio apropiado',
                '- Monitor a altura de ojos',
                '- Iluminación adecuada',
                '- Pausas regulares',
                '- Derecho a solicitar evaluación ergonómica'
            ],
            '11. TERMINACIÓN' => [
                'AL RETORNO A OFICINA:',
                '- Devolver equipo corporativo si fue prestado',
                '- Eliminar datos corporativos si BYOD',
                '- Desinstalar software corporativo',
                '',
                'AL TÉRMINO DE RELACIÓN:',
                '- Devolución de todos los equipos',
                '- Revocación de accesos remotos',
                '- Firma de acta de devolución'
            ]
        ],
        'cumplimiento' => 'Violaciones de seguridad en teletrabajo tienen las mismas consecuencias que en oficina.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.6.7 - Trabajo remoto'
    ],

    'POL-SI-017' => [
        'titulo' => 'POLÍTICA DE GESTIÓN DE VULNERABILIDADES',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Identificar, evaluar y remediar vulnerabilidades de seguridad de manera oportuna y efectiva.',
        'alcance' => 'Todos los sistemas, aplicaciones, dispositivos de red y endpoints de la organización.',
        'politica' => [
            '1. IDENTIFICACIÓN DE VULNERABILIDADES' => [
                'ESCANEO AUTOMATIZADO:',
                '- Semanal: Sistemas críticos',
                '- Mensual: Todos los sistemas',
                '- Antes de despliegue: Nuevos sistemas',
                '- Ad-hoc: Tras publicación de vulnerabilidad crítica',
                '',
                'HERRAMIENTAS:',
                '- Escáner de vulnerabilidades (Nessus, Qualys, OpenVAS)',
                '- Escáner de aplicaciones web (OWASP ZAP, Burp)',
                '- Análisis de código estático (SonarQube)',
                '- Análisis de composición (Snyk, Dependabot)',
                '',
                'PENTESTING:',
                '- Anual: Infraestructura',
                '- Anual: Aplicaciones críticas',
                '- Tras cambios mayores',
                '- Por equipo externo certificado'
            ],
            '2. CLASIFICACIÓN Y PRIORIZACIÓN' => [
                'SEVERIDAD:',
                '',
                'CRÍTICA:',
                '- CVSS 9.0-10.0',
                '- Explotación remota sin autenticación',
                '- Ejecución de código',
                '- Compromiso total del sistema',
                '- SLA remediación: 24 horas',
                '',
                'ALTA:',
                '- CVSS 7.0-8.9',
                '- Elevación de privilegios',
                '- Divulgación de información sensible',
                '- SLA remediación: 7 días',
                '',
                'MEDIA:',
                '- CVSS 4.0-6.9',
                '- Requiere interacción del usuario',
                '- Impacto limitado',
                '- SLA remediación: 30 días',
                '',
                'BAJA:',
                '- CVSS 0.1-3.9',
                '- Impacto mínimo',
                '- SLA remediación: 90 días',
                '',
                'FACTORES ADICIONALES:',
                '- Exposición (Internet vs interna)',
                '- Criticidad del activo',
                '- Existencia de exploit público',
                '- Explotación activa (0-day)',
                '- Datos procesados'
            ],
            '3. REMEDIACIÓN' => [
                'PATCHING:',
                '- Parches críticos de SO: 48 horas',
                '- Parches importantes: 15 días',
                '- Parches normales: 30 días',
                '- Siempre probar en QA primero',
                '- Ventanas de mantenimiento planificadas',
                '',
                'WORKAROUNDS TEMPORALES:',
                'Si parche no disponible o no aplicable:',
                '- Reglas de firewall',
                '- Deshabilitar funcionalidad vulnerable',
                '- Segmentación de red',
                '- Monitoreo aumentado',
                '- IPS signatures',
                '',
                'ACEPTACIÓN DE RIESGO:',
                'Si no se puede remediar:',
                '- Documentar justificación técnica',
                '- Controles compensatorios',
                '- Aprobación de CISO',
                '- Revisión trimestral',
                '- Fecha de vencimiento'
            ],
            '4. GESTIÓN DE PARCHES' => [
                'INVENTARIO:',
                '- Sistemas operativos',
                '- Aplicaciones',
                '- Firmware',
                '- Dispositivos de red',
                '',
                'TESTING:',
                '- Ambiente de QA',
                '- Plan de rollback',
                '- Documentar incompatibilidades',
                '- Validar funcionalidad',
                '',
                'DESPLIEGUE:',
                '- Automatizado cuando posible',
                '- Ventanas de mantenimiento',
                '- Críticos primero',
                '- Monitoreo post-despliegue',
                '',
                'CASOS ESPECIALES:',
                '- Sistemas legacy sin soporte',
                '- Sistemas industriales (OT)',
                '- Sistemas de terceros',
                '- Requieren evaluación especial'
            ],
            '5. MONITOREO' => [
                'FUENTES DE INFORMACIÓN:',
                '- CVE feeds',
                '- Vendor security bulletins',
                '- CERT advisories',
                '- Security mailing lists',
                '- Threat intelligence',
                '',
                'MÉTRICAS:',
                '- Tiempo medio de remediación',
                '- % vulnerabilidades remediadas a tiempo',
                '- Vulnerabilidades abiertas por severidad',
                '- Tendencias',
                '- Reincidencias',
                '',
                'REPORTES:',
                '- Dashboard en tiempo real',
                '- Reporte semanal a TI',
                '- Reporte mensual a CISO',
                '- Reporte trimestral a dirección'
            ],
            '6. RESPONSABILIDADES' => [
                'EQUIPO DE SEGURIDAD:',
                '- Ejecutar escaneos',
                '- Analizar resultados',
                '- Clasificar vulnerabilidades',
                '- Asignar a responsables',
                '- Seguimiento de remediación',
                '',
                'ADMINISTRADORES DE SISTEMAS:',
                '- Aplicar parches',
                '- Implementar workarounds',
                '- Documentar acciones',
                '- Probar en QA',
                '',
                'DESARROLLADORES:',
                '- Remediar vulnerabilidades de código',
                '- Actualizar dependencias',
                '- Code review de seguridad',
                '',
                'GERENTES DE ÁREA:',
                '- Aprobar ventanas de mantenimiento',
                '- Proveer recursos',
                '- Aprobar aceptación de riesgos'
            ],
            '7. FALSOS POSITIVOS' => [
                '- Documentar y registrar',
                '- Tuning de escáner',
                '- Revalidar periódicamente',
                '- No ignorar sin análisis'
            ],
            '8. EXCEPCIONES' => [
                'PROCESO:',
                '- Solicitud formal',
                '- Justificación técnica',
                '- Controles compensatorios',
                '- Aprobación de CISO',
                '- Revisión trimestral',
                '- Fecha de vencimiento',
                '- Documentación completa'
            ]
        ],
        'cumplimiento' => 'No remediar vulnerabilidades críticas en tiempo es inaceptable.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.8.8 - Gestión de vulnerabilidades técnicas'
    ],

    'POL-SI-018' => [
        'titulo' => 'POLÍTICA DE SEGURIDAD EN REDES',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Proteger la confidencialidad, integridad y disponibilidad de las redes de la organización mediante controles de seguridad apropiados.',
        'alcance' => 'Todas las redes de la organización: LAN, WAN, WiFi, redes remotas y conexiones con terceros.',
        'politica' => [
            '1. ARQUITECTURA DE RED' => [
                'SEGMENTACIÓN OBLIGATORIA:',
                '- DMZ para servicios públicos',
                '- Red corporativa (usuarios)',
                '- Red de servidores (datacenter)',
                '- Red de gestión (administración)',
                '- Red de invitados (aislada)',
                '- Red IoT/OT (separada)',
                '',
                'PRINCIPIOS:',
                '- Zero Trust Network Access',
                '- Microsegmentación',
                '- Defensa en profundidad',
                '- Menor privilegio en ACLs'
            ],
            '2. FIREWALLS' => [
                'OBLIGATORIO:',
                '- Firewall perimetral',
                '- Firewall interno entre segmentos',
                '- Reglas whitelist (denegar por defecto)',
                '- Logging de todo el tráfico',
                '',
                'GESTIÓN:',
                '- Revisión trimestral de reglas',
                '- Eliminar reglas obsoletas',
                '- Justificar cada regla',
                '- Change management para cambios',
                '- Backup de configuraciones',
                '',
                'MONITOREO:',
                '- Alertas de bloqueos inusuales',
                '- Dashboard de tráfico',
                '- Análisis de logs',
                '- Reportes mensuales'
            ],
            '3. IDS/IPS' => [
                'UBICACIÓN:',
                '- Perímetro (entrada Internet)',
                '- Entre segmentos críticos',
                '- Datacenter',
                '',
                'CONFIGURACIÓN:',
                '- Signatures actualizadas diariamente',
                '- Tuning para reducir falsos positivos',
                '- Modo preventivo (IPS) en producción',
                '- Alertas a SOC/SIEM',
                '',
                'RESPUESTA:',
                '- Investigación de alertas críticas: Inmediata',
                '- Alertas altas: Dentro de 1 hora',
                '- Bloqueo automático de IPs maliciosas'
            ],
            '4. WIFI' => [
                'CORPORATIVO:',
                '- WPA3 (o WPA2 enterprise)',
                '- 802.1X con RADIUS',
                '- Certificados o credenciales AD',
                '- Separación de VLANs por rol',
                '- Ocultar SSID no requerido',
                '',
                'INVITADOS:',
                '- SSID separado',
                '- Red completamente aislada',
                '- Portal cautivo con términos',
                '- Ancho de banda limitado',
                '- Tiempo de sesión limitado',
                '- Sin acceso a recursos internos',
                '',
                'IOT/DISPOSITIVOS:',
                '- VLAN separada',
                '- Sin acceso a red corporativa',
                '- Firewall estricto',
                '- Monitoreo de anomalías',
                '',
                'PROHIBICIONES:',
                '- WEP (obsoleto e inseguro)',
                '- WPA con PSK simple',
                '- SSID con nombre de empresa',
                '- Puntos de acceso rogue'
            ],
            '5. VPN' => [
                'ACCESO REMOTO:',
                '- IPsec o SSL VPN',
                '- MFA obligatorio',
                '- Cifrado AES-256',
                '- Perfect Forward Secrecy',
                '- Split tunneling prohibido',
                '- Timeout de sesión: 12 horas',
                '- Logging completo',
                '',
                'SITE-TO-SITE:',
                '- IPsec con ESP',
                '- IKEv2',
                '- Redundancia de túneles',
                '- Monitoreo de disponibilidad',
                '',
                'GESTIÓN DE CLIENTES:',
                '- Cliente VPN corporativo',
                '- Actualización automática',
                '- Kill switch activo',
                '- DNS leak protection'
            ],
            '6. NAC (Network Access Control)' => [
                '802.1X:',
                '- Autenticación antes de acceso',
                '- Verificación de estado de equipo',
                '- Antivirus actualizado',
                '- Parches críticos aplicados',
                '- Certificado válido',
                '',
                'QUARANTINE:',
                '- VLAN de cuarentena para no conformes',
                '- Acceso solo a remediation',
                '- Bloqueo de recursos corporativos'
            ],
            '7. VLAN' => [
                'DISEÑO:',
                '- Una VLAN por función/departamento',
                '- Separación de voz y datos',
                '- VLAN de gestión separada',
                '- VLAN nativa no usada',
                '',
                'SEGURIDAD:',
                '- VLAN pruning',
                '- Private VLANs donde aplicable',
                '- No routing automático entre VLANs'
            ],
            '8. DNS' => [
                'INTERNO:',
                '- Servidores DNS redundantes',
                '- Split DNS (interno/externo)',
                '- DNSSEC',
                '- Rate limiting',
                '- Logging de consultas',
                '',
                'EXTERNO:',
                '- Proveedores confiables',
                '- DNS over HTTPS (DoH)',
                '- Filtrado de dominios maliciosos',
                '- Prevención de DNS tunneling'
            ],
            '9. PROXY' => [
                '- Proxy explícito obligatorio',
                '- Filtrado de contenido (web filtering)',
                '- Inspección SSL/TLS',
                '- Categorización de sitios',
                '- Bloqueo de malware',
                '- DLP integrado',
                '- Logging detallado'
            ],
            '10. MONITOREO DE RED' => [
                'NETFLOW/SFLOW:',
                '- Análisis de tráfico',
                '- Detección de anomalías',
                '- Bandwidth monitoring',
                '- Identificación de aplicaciones',
                '',
                'SNMP:',
                '- SNMPv3 únicamente',
                '- Monitoreo de dispositivos',
                '- Alertas de errores',
                '- Gráficas de utilización',
                '',
                'PACKET CAPTURE:',
                '- TAP/SPAN en puntos estratégicos',
                '- Almacenamiento PCAP temporal',
                '- Herramientas de análisis forense'
            ],
            '11. CAMBIOS EN RED' => [
                '- Proceso de change management',
                '- Aprobación requerida',
                '- Ventana de cambio definida',
                '- Plan de rollback',
                '- Backup de configs pre-cambio',
                '- Documentación actualizada',
                '- Validación post-cambio'
            ],
            '12. HARDENING DE DISPOSITIVOS' => [
                '- Deshabilitar servicios innecesarios',
                '- SSH v2 solamente',
                '- SNMP v3 con cifrado',
                '- Contraseñas robustas',
                '- Banner legal',
                '- Logging remoto (syslog)',
                '- NTP sincronizado',
                '- ACLs en interfaces de gestión'
            ]
        ],
        'cumplimiento' => 'Configuraciones inseguras de red ponen en riesgo toda la organización.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.8.20 - A.8.23 - Seguridad en redes'
    ],

    'POL-SI-019' => [
        'titulo' => 'POLÍTICA DE MONITOREO, LOGGING Y AUDITORÍA',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Registrar, monitorear y auditar actividades en sistemas de información para detectar y responder a incidentes de seguridad.',
        'alcance' => 'Todos los sistemas, aplicaciones, dispositivos de red y servicios de la organización.',
        'politica' => [
            '1. LOGGING OBLIGATORIO' => [
                'EVENTOS A REGISTRAR:',
                '',
                'AUTENTICACIÓN:',
                '- Inicio de sesión exitoso/fallido',
                '- Logout',
                '- Cambios de contraseña',
                '- Bloqueos de cuenta',
                '- Uso de privilegios elevados',
                '',
                'AUTORIZACIÓN:',
                '- Acceso denegado',
                '- Cambios en permisos',
                '- Uso de roles privilegiados',
                '',
                'SISTEMAS:',
                '- Inicios/paros de servicios',
                '- Cambios de configuración',
                '- Actualizaciones de software',
                '- Errores de sistema',
                '- Reinicio de equipos',
                '',
                'APLICACIONES:',
                '- Transacciones críticas',
                '- Errores de aplicación',
                '- Excepciones',
                '- Cambios en datos sensibles',
                '',
                'RED:',
                '- Conexiones aceptadas/rechazadas',
                '- Cambios de firewall',
                '- Alertas IDS/IPS',
                '- VPN sesiones',
                '',
                'SEGURIDAD:',
                '- Detecciones de malware',
                '- Vulnerabilidades encontradas',
                '- Intentos de intrusión',
                '- Violaciones de políticas DLP'
            ],
            '2. CONTENIDO DE LOGS' => [
                'CAMPOS OBLIGATORIOS:',
                '- Timestamp (con timezone)',
                '- Usuario/proceso',
                '- Dirección IP origen',
                '- Tipo de evento',
                '- Resultado (éxito/fallo)',
                '- Objeto accedido',
                '- Severity level',
                '',
                'PROHIBIDO REGISTRAR:',
                '- Contraseñas',
                '- Números de tarjeta completos',
                '- Datos sensibles sin enmascarar',
                '- Información médica'
            ],
            '3. CENTRALIZACIÓN' => [
                'SIEM (Security Information and Event Management):',
                '- Todos los logs a SIEM central',
                '- Recolección en tiempo real',
                '- Normalización de logs',
                '- Correlación de eventos',
                '- Alertas automatizadas',
                '',
                'FORMATO:',
                '- Syslog CEF/LEEF preferido',
                '- JSON para APIs',
                '- Timezone UTC consistente'
            ],
            '4. RETENCIÓN' => [
                'LOGS DE SEGURIDAD:',
                '- Online: 90 días mínimo',
                '- Archivo: 1 año mínimo',
                '- Regulatorio: 7 años si aplica',
                '',
                'LOGS OPERACIONALES:',
                '- Online: 30 días',
                '- Archivo: 180 días',
                '',
                'LOGS DE AUDITORÍA:',
                '- 7 años mínimo',
                '',
                'ALMACENAMIENTO:',
                '- Compresión permitida',
                '- Cifrado en reposo',
                '- Backup regular',
                '- Inmutable (WORM preferido)'
            ],
            '5. PROTECCIÓN DE LOGS' => [
                'INTEGRIDAD:',
                '- Syslog sobre TLS',
                '- Checksums/hashes',
                '- Digital signatures',
                '- WORM storage',
                '',
                'ACCESO:',
                '- Solo equipo de seguridad y auditores',
                '- Acceso read-only para análisis',
                '- MFA para acceso',
                '- Auditoría de accesos a logs',
                '',
                'BACKUP:',
                '- Backup diario de logs',
                '- Retención según política',
                '- Verificación de restauración'
            ],
            '6. MONITOREO EN TIEMPO REAL' => [
                'SOC (Security Operations Center):',
                '- Monitoreo 24/7',
                '- Dashboard en tiempo real',
                '- Alertas automáticas',
                '- Playbooks de respuesta',
                '',
                'ALERTAS CRÍTICAS:',
                '- Múltiples intentos fallidos',
                '- Acceso desde IP desconocida',
                '- Acceso privilegiado fuera de horario',
                '- Cambios en usuarios/permisos',
                '- Detección de malware',
                '- Exfiltración de datos',
                '- Escaneo de red',
                '',
                'RESPUESTA:',
                '- Crítico: Inmediato',
                '- Alto: Dentro de 15 minutos',
                '- Medio: Dentro de 1 hora',
                '- Bajo: Dentro de 24 horas'
            ],
            '7. ANÁLISIS' => [
                'BÚSQUEDAS:',
                '- Herramientas de búsqueda eficientes',
                '- Queries predefinidos',
                '- Reportes programados',
                '',
                'CORRELATION RULES:',
                '- Detectar patrones de ataque',
                '- Identificar anomalías',
                '- Actualizar rules regularmente',
                '- Tuning de falsos positivos',
                '',
                'THREAT HUNTING:',
                '- Proactivo semanal',
                '- Búsqueda de IOCs',
                '- Análisis de comportamiento'
            ],
            '8. AUDITORÍA' => [
                'AUDITORÍAS INTERNAS:',
                '- Trimestral: Accesos privilegiados',
                '- Semestral: Cumplimiento de políticas',
                '- Anual: Revisión completa',
                '',
                'REVISIONES:',
                '- Logs de cambios en sistemas',
                '- Accesos fuera de horario',
                '- Uso de cuentas privilegiadas',
                '- Intentos de acceso fallidos',
                '- Cambios en configuraciones',
                '',
                'DOCUMENTACIÓN:',
                '- Informe de cada auditoría',
                '- Hallazgos y recomendaciones',
                '- Plan de acción',
                '- Seguimiento de remediación'
            ],
            '9. SINCRONIZACIÓN DE TIEMPO' => [
                'NTP:',
                '- Servidor NTP interno',
                '- Sincronizado con fuente autoritativa',
                '- Todos los sistemas sincronizados',
                '- Monitoreo de drift',
                '- Alertas de desincronización',
                '',
                'TIMEZONE:',
                '- UTC en logs centralizados',
                '- Conversión a local en visualización'
            ],
            '10. MÉTRICAS Y KPIs' => [
                '- Eventos por segundo',
                '- Alertas generadas',
                '- Tiempo medio de respuesta',
                '- Falsos positivos',
                '- Incidentes detectados',
                '- Cobertura de logging',
                '- Disponibilidad SIEM'
            ],
            '11. REPORTING' => [
                'DIARIO:',
                '- Resumen de alertas críticas',
                '- Incidentes del día',
                '',
                'SEMANAL:',
                '- Top 10 alertas',
                '- Tendencias',
                '- Accesos privilegiados',
                '',
                'MENSUAL:',
                '- Reporte ejecutivo',
                '- KPIs',
                '- Comparación mes anterior',
                '- Proyectos de mejora',
                '',
                'TRIMESTRAL:',
                '- Reporte a dirección',
                '- Cumplimiento de políticas',
                '- ROI de herramientas'
            ]
        ],
        'cumplimiento' => 'Falta de logging adecuado impide investigación de incidentes.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.8.15 - A.8.16 - Logging y monitoreo'
    ],

    'POL-SI-020' => [
        'titulo' => 'POLÍTICA DE GESTIÓN DE CAMBIOS',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Asegurar que los cambios en sistemas y servicios sean evaluados, aprobados, probados y documentados apropiadamente.',
        'alcance' => 'Todos los cambios en sistemas de producción, infraestructura, aplicaciones, redes y configuraciones.',
        'politica' => [
            '1. TIPOS DE CAMBIOS' => [
                'ESTÁNDAR (Pre-aprobados):',
                '- Bajo riesgo y frecuentes',
                '- Procedimiento documentado',
                '- No requiere aprobación CAB',
                '- Ejemplos: Reinicio de servicio, rotación de logs, backup restore',
                '',
                'NORMAL:',
                '- Mayoría de cambios',
                '- Requiere RFC (Request for Change)',
                '- Aprobación de CAB',
                '- Testing en QA',
                '- Ventana de cambio programada',
                '',
                'EMERGENCIA:',
                '- Para resolver incidente crítico',
                '- Proceso acelerado',
                '- Aprobación verbal de Emergency CAB',
                '- Documentación retrospectiva',
                '- Post-Implementation Review obligatorio'
            ],
            '2. PROCESO DE CAMBIO' => [
                'FASE 1 - SOLICITUD (RFC):',
                '- Formulario de cambio completo',
                '- Descripción detallada',
                '- Justificación de negocio',
                '- Impacto estimado',
                '- Riesgos identificados',
                '- Plan de rollback',
                '- Recursos necesarios',
                '',
                'FASE 2 - EVALUACIÓN:',
                '- Análisis de impacto',
                '- Evaluación de riesgos',
                '- Identificar dependencias',
                '- Estimar esfuerzo',
                '- Determinar ventana de cambio',
                '',
                'FASE 3 - APROBACIÓN:',
                '- Presentación a CAB',
                '- Revisión técnica',
                '- Aprobación de sponsor',
                '- Aprobación de seguridad si aplica',
                '',
                'FASE 4 - IMPLEMENTACIÓN:',
                '- Comunicación previa',
                '- Backup pre-cambio',
                '- Ejecución según plan',
                '- Documentación de acciones',
                '- Validación de éxito',
                '',
                'FASE 5 - REVISIÓN:',
                '- Verificar objetivos cumplidos',
                '- Identificar problemas',
                '- Documentar lecciones',
                '- Actualizar documentación',
                '- Cerrar RFC'
            ],
            '3. CAB (Change Advisory Board)' => [
                'MIEMBROS:',
                '- Change Manager (líder)',
                '- Representantes TI',
                '- Seguridad',
                '- Redes',
                '- Aplicaciones',
                '- Negocio (según impacto)',
                '',
                'REUNIONES:',
                '- Semanal (regular)',
                '- Ad-hoc (emergencias)',
                '- Revisión de RFCs pendientes',
                '- Análisis de cambios fallidos',
                '',
                'RESPONSABILIDADES:',
                '- Evaluar RFCs',
                '- Aprobar/rechazar cambios',
                '- Priorizar cambios',
                '- Resolver conflictos',
                '- Autorizar Emergency Changes'
            ],
            '4. VENTANAS DE CAMBIO' => [
                'PROGRAMADAS:',
                '- Producción: Viernes 22:00-02:00',
                '- Sistemas no críticos: Martes/Jueves noche',
                '- Cambios mayores: Fin de semana',
                '',
                'PROHIBIDAS:',
                '- Lunes (inicio de semana)',
                '- Fechas de cierre financiero',
                '- Temporadas altas',
                '- Días festivos',
                '- Black Friday, Cyber Monday, etc.',
                '',
                'FREEZE PERIODS:',
                '- 2 semanas antes de cierre fiscal',
                '- Temporada de ventas peak',
                '- Solo cambios de emergencia'
            ],
            '5. TESTING' => [
                'OBLIGATORIO:',
                '- Pruebas en ambiente QA',
                '- Validación funcional',
                '- Pruebas de regresión',
                '- Pruebas de desempeño si aplica',
                '- Pruebas de seguridad si aplica',
                '',
                'DOCUMENTAR:',
                '- Casos de prueba',
                '- Resultados',
                '- Evidencia de éxito',
                '- Problemas encontrados'
            ],
            '6. PLAN DE ROLLBACK' => [
                'OBLIGATORIO PARA TODO CAMBIO:',
                '- Procedimiento detallado',
                '- Tiempo estimado',
                '- Criterios para rollback',
                '- Responsables',
                '- Validación post-rollback',
                '',
                'BACKUPS:',
                '- Backup completo pre-cambio',
                '- Verificar integridad',
                '- Disponibilidad inmediata',
                '- Procedimiento de restore probado'
            ],
            '7. COMUNICACIÓN' => [
                'PRE-CAMBIO:',
                '- Notificación 48h antes',
                '- Descripción del cambio',
                '- Impacto esperado',
                '- Horario de ventana',
                '- Contactos de soporte',
                '',
                'DURANTE CAMBIO:',
                '- Status updates',
                '- Problemas encontrados',
                '- Extensión de ventana si necesario',
                '',
                'POST-CAMBIO:',
                '- Confirmación de éxito',
                '- Problemas pendientes',
                '- Acciones de seguimiento'
            ],
            '8. DOCUMENTACIÓN' => [
                'ACTUALIZAR:',
                '- Diagramas de red',
                '- Inventario de configuraciones',
                '- Runbooks',
                '- Documentación técnica',
                '- Base de conocimiento',
                '',
                'REGISTRAR:',
                '- Todas las acciones realizadas',
                '- Desviaciones del plan',
                '- Problemas encontrados',
                '- Tiempo real vs estimado'
            ],
            '9. CAMBIOS DE SEGURIDAD' => [
                'PRIORITARIOS:',
                '- Parches críticos',
                '- Remediación de vulnerabilidades',
                '- Respuesta a incidentes',
                '',
                'EVALUACIÓN ADICIONAL:',
                '- Revisión de seguridad',
                '- Análisis de riesgo',
                '- Aprobación de CISO',
                '- Testing de seguridad'
            ],
            '10. MÉTRICAS' => [
                '- Total de cambios por mes',
                '- Tasa de éxito de cambios',
                '- Cambios fallidos',
                '- Cambios con rollback',
                '- Tiempo promedio de implementación',
                '- Cambios de emergencia',
                '- Incidentes causados por cambios',
                '- Cumplimiento de ventanas'
            ],
            '11. AUDITORÍA' => [
                '- Revisión mensual de cambios',
                '- Identificar cambios no autorizados',
                '- Verificar documentación',
                '- Evaluar cumplimiento de proceso',
                '- Lecciones aprendidas'
            ]
        ],
        'cumplimiento' => 'Cambios no autorizados son violación grave y pueden causar interrupciones mayores.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.8.32 - Gestión de cambios'
    ],

    'POL-SI-021' => [
        'titulo' => 'POLÍTICA DE CAPACITACIÓN Y CONCIENTIZACIÓN EN SEGURIDAD',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Asegurar que todos los empleados conozcan y comprendan sus responsabilidades de seguridad y las amenazas actuales.',
        'alcance' => 'Todos los empleados, contratistas, proveedores y terceros con acceso a información o sistemas de la organización.',
        'politica' => [
            '1. PROGRAMA DE CAPACITACIÓN' => [
                'OBLIGATORIO PARA TODOS:',
                '',
                'INDUCCIÓN (Día 1):',
                '- Políticas de seguridad básicas',
                '- Uso aceptable de recursos',
                '- Manejo de contraseñas',
                '- Reporte de incidentes',
                '- Firma de acuerdos (NDA, AUP)',
                '- Evaluación de comprensión',
                '',
                'ANUAL (Todos los empleados):',
                '- Actualización de políticas',
                '- Amenazas actuales (phishing, ransomware)',
                '- Ingeniería social',
                '- Protección de datos personales',
                '- Casos de estudio de incidentes',
                '- Evaluación obligatoria (80% aprobación)',
                '',
                'REFORZAMIENTO (Trimestral):',
                '- Boletines de seguridad',
                '- Tips de seguridad',
                '- Comunicados de nuevas amenazas',
                '- Simulaciones de phishing'
            ],
            '2. CAPACITACIÓN POR ROLES' => [
                'DESARROLLADORES:',
                '- Desarrollo seguro (OWASP)',
                '- Code review de seguridad',
                '- Gestión de secretos',
                '- Seguridad en APIs',
                '- DevSecOps',
                '- Anual: 8 horas mínimo',
                '',
                'ADMINISTRADORES DE SISTEMAS:',
                '- Hardening de servidores',
                '- Gestión de parches',
                '- Gestión de logs',
                '- Respuesta a incidentes',
                '- Anual: 12 horas mínimo',
                '',
                'GERENTES:',
                '- Riesgos de seguridad',
                '- Continuidad de negocio',
                '- Privacidad de datos',
                '- Due diligence de proveedores',
                '- Anual: 4 horas mínimo',
                '',
                'USUARIOS PRIVILEGIADOS:',
                '- Responsabilidades especiales',
                '- Protección de credenciales',
                '- Auditoría de acciones',
                '- Semestral: 2 horas mínimo'
            ],
            '3. CONCIENTIZACIÓN CONTINUA' => [
                'CAMPAÑAS:',
                '',
                'MENSUAL:',
                '- Tema específico del mes',
                '- Carteles en oficinas',
                '- Email awareness',
                '- Intranet destacados',
                '',
                'TEMAS ROTATIVOS:',
                '- Enero: Contraseñas seguras',
                '- Febrero: Phishing',
                '- Marzo: Ingeniería social',
                '- Abril: Protección de dispositivos móviles',
                '- Mayo: Redes sociales',
                '- Junio: Ransomware',
                '- Julio: Trabajo remoto seguro',
                '- Agosto: Protección de datos',
                '- Septiembre: Wi-Fi seguro',
                '- Octubre: Mes de Ciberseguridad',
                '- Noviembre: Seguridad física',
                '- Diciembre: Amenazas en vacaciones',
                '',
                'CANALES:',
                '- Email corporativo',
                '- Intranet',
                '- Digital signage',
                '- Teams/Slack',
                '- Newsletter mensual'
            ],
            '4. SIMULACIONES DE PHISHING' => [
                'FRECUENCIA:',
                '- Mensual: Campaña simulada',
                '- Aleatoria: No predecible',
                '- Varíar complejidad',
                '',
                'PROCESO:',
                '- Envío de email simulado',
                '- Tracking de clics',
                '- Página de educación al hacer clic',
                '- No sanciones punitivas',
                '- Educación constructiva',
                '',
                'SEGUIMIENTO:',
                '- Capacitación adicional para repetidores',
                '- Reconocimiento a mejores performers',
                '- Métricas departamentales',
                '- Tendencias de mejora'
            ],
            '5. CERTIFICACIONES' => [
                'PERSONAL DE SEGURIDAD:',
                'Recomendadas:',
                '- CISSP',
                '- CISA',
                '- CEH',
                '- OSCP',
                '- Security+',
                '',
                'ADMINISTRADORES:',
                '- Certificaciones de vendors',
                '- CCNA Security',
                '- MCSA/MCSE',
                '',
                'APOYO:',
                '- Organización paga certificación',
                '- Tiempo para estudio',
                '- Renovación periódica'
            ],
            '6. EVALUACIÓN Y MEDICIÓN' => [
                'MÉTRICAS:',
                '- % completitud de capacitaciones',
                '- Scores de evaluaciones',
                '- Tasa de clic en phishing simulado',
                '- Incidentes causados por error humano',
                '- NPS de capacitaciones',
                '',
                'METAS:',
                '- 100% completitud en tiempo',
                '- 80% aprobación en evaluaciones',
                '- <10% clic en phishing',
                '- Reducción year-over-year de incidentes'
            ],
            '7. CONTENIDO DE CAPACITACIÓN' => [
                'OBLIGATORIO INCLUIR:',
                '- Políticas de seguridad aplicables',
                '- Consecuencias de incumplimiento',
                '- Ejemplos prácticos',
                '- Casos reales (anonimizados)',
                '- Acciones específicas esperadas',
                '- Dónde obtener ayuda',
                '- Cómo reportar incidentes',
                '',
                'METODOLOGÍA:',
                '- Videos cortos (micro-learning)',
                '- Interactivo (quizzes)',
                '- Escenarios reales',
                '- Gamificación',
                '- Mobile-friendly',
                '- Multiidioma si aplica'
            ],
            '8. PROVEEDORES Y TERCEROS' => [
                '- Capacitación inicial obligatoria',
                '- Módulo reducido adaptado',
                '- Firma de políticas',
                '- Evaluación de comprensión',
                '- Actualización anual si contrato largo'
            ],
            '9. RECONOCIMIENTO' => [
                'POSITIVO:',
                '- Reconocer reporte de phishing real',
                '- Premio a mejores scores',
                '- Certificado de "Security Champion"',
                '- Gamificación con puntos',
                '',
                'CULTURA:',
                '- Fomentar preguntas',
                '- No culpar errores honestos',
                '- Celebrar mejoras',
                '- Embajadores de seguridad'
            ],
            '10. ACTUALIZACIÓN DE CONTENIDO' => [
                '- Revisión trimestral',
                '- Incorporar nuevas amenazas',
                '- Actualizar por cambios regulatorios',
                '- Feedback de participantes',
                '- Lecciones de incidentes reales',
                '- Benchmarking con industria'
            ]
        ],
        'cumplimiento' => 'Capacitación en seguridad no es opcional, es requisito de empleo.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.6.3 - Concientización, educación y capacitación en seguridad'
    ],

    'POL-SI-022' => [
        'titulo' => 'POLÍTICA DE RETENCIÓN Y ELIMINACIÓN DE INFORMACIÓN',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Definir períodos de retención apropiados y métodos seguros de eliminación de información al final de su ciclo de vida.',
        'alcance' => 'Toda la información de la organización en cualquier formato: digital, papel, medios magnéticos, ópticos, etc.',
        'politica' => [
            '1. PERÍODOS DE RETENCIÓN' => [
                'LEGAL Y REGULATORIO:',
                '',
                'DOCUMENTOS FISCALES Y CONTABLES:',
                '- Facturas, boletas: 7 años',
                '- Libros contables: 7 años',
                '- Declaraciones impuestos: 7 años',
                '- Comprobantes de pago: 7 años',
                '',
                'DOCUMENTOS LABORALES:',
                '- Contratos de trabajo: Duración + 7 años',
                '- Liquidaciones: 7 años',
                '- Finiquitos: 7 años',
                '- Accidentes laborales: Permanente',
                '',
                'DOCUMENTOS CORPORATIVOS:',
                '- Actas de directorio: Permanente',
                '- Contratos mayores: Duración + 7 años',
                '- Escrituras: Permanente',
                '- Propiedad intelectual: Permanente',
                '',
                'DATOS PERSONALES:',
                '- Clientes activos: Duración relación',
                '- Clientes inactivos: 2 años',
                '- Candidatos no seleccionados: 1 año',
                '- Empleados: Durante empleo + 7 años',
                '- Visitantes web: Según consentimiento'
            ],
            '2. PERÍODOS POR TIPO DE INFORMACIÓN' => [
                'OPERACIONAL:',
                '- Emails corporativos: 2 años',
                '- Logs de sistemas: 1 año',
                '- Backups operacionales: 30-90 días',
                '- Documentos de proyectos: Fin proyecto + 3 años',
                '',
                'SEGURIDAD:',
                '- Logs de seguridad: 1 año online, 7 años archivo',
                '- Registros de acceso: 1 año',
                '- Incidentes de seguridad: 7 años',
                '- Análisis de vulnerabilidades: 3 años',
                '',
                'MARKETING:',
                '- Bases de datos marketing: Según consentimiento',
                '- Campañas: 3 años',
                '- Analytics: 2 años',
                '',
                'TEMPORAL:',
                '- Documentos de trabajo: Fin de uso',
                '- Borradores: Al aprobar versión final',
                '- Duplicados: Eliminar',
                '- Datos de prueba: Fin de pruebas'
            ],
            '3. REVISIÓN DE INFORMACIÓN' => [
                'ANUAL:',
                '- Revisar información almacenada',
                '- Identificar información sin dueño',
                '- Clasificar información no clasificada',
                '- Eliminar información vencida',
                '',
                'PROCESO:',
                '- Propietarios revisan su información',
                '- Justificar retención extendida',
                '- Actualizar registros de retención',
                '- Programar eliminación'
            ],
            '4. ELIMINACIÓN SEGURA' => [
                'PAPEL:',
                'CONFIDENCIAL Y SUPERIOR:',
                '- Trituradora cruce cortado (DIN P-4)',
                '- Contenedores seguros',
                '- Servicio certificado de destrucción',
                '- Certificado de destrucción',
                '',
                'INTERNA:',
                '- Trituradora simple',
                '- No tirar intacto',
                '',
                'DIGITAL:',
                '',
                'DISCOS DUROS (datos confidenciales):',
                '- Método 1: Sobrescritura múltiple (DoD 5220.22-M)',
                '- Método 2: Degaussing',
                '- Método 3: Destrucción física certificada',
                '- Verificar eliminación',
                '- Certificado si se contrata',
                '',
                'SSD Y FLASH:',
                '- Secure Erase (ATA)',
                '- Crypto-erase si cifrado',
                '- Destrucción física si muy sensible',
                '',
                'MEDIOS ÓPTICOS (CD/DVD):',
                '- Trituración',
                '- Incineración',
                '- No suficiente: rayar o romper manualmente',
                '',
                'CINTAS MAGNÉTICAS:',
                '- Degaussing',
                '- Destrucción física',
                '- Incineración',
                '',
                'DISPOSITIVOS MÓVILES:',
                '- Factory reset',
                '- Destrucción de SIM/microSD',
                '- Verificar eliminación de cuenta cloud',
                '- Destrucción física si contenía datos críticos'
            ],
            '5. CLOUD Y SaaS' => [
                'ELIMINACIÓN:',
                '- Eliminar desde interfaz',
                '- Vaciar papelera/trash',
                '- Solicitar eliminación permanente a proveedor',
                '- Certificado de eliminación',
                '- Validar en backups del proveedor',
                '',
                'AL TÉRMINO DE SERVICIO:',
                '- Exportar datos necesarios',
                '- Eliminar toda la información',
                '- Cerrar cuenta',
                '- Confirmación por escrito'
            ],
            '6. DOCUMENTACIÓN' => [
                'REGISTRAR:',
                '- Qué se eliminó',
                '- Cuándo',
                '- Quién autorizó',
                '- Método utilizado',
                '- Certificados si aplica',
                '',
                'RETENER REGISTRO:',
                '- 7 años mínimo',
                '- Disponible para auditorías',
                '- Evidencia de cumplimiento legal'
            ],
            '7. EXCEPCIONES' => [
                'HOLD LEGAL (Legal Hold):',
                '- Si hay litigio/investigación',
                '- Suspender eliminación',
                '- Preservar evidencia',
                '- Notificar a custodios',
                '- Documentar alcance',
                '- Levantar hold al finalizar',
                '',
                'ARCHIVO HISTÓRICO:',
                '- Valor histórico/archivístico',
                '- Aprobación de dirección',
                '- Almacenamiento apropiado',
                '- Catalogación'
            ],
            '8. RESPONSABILIDADES' => [
                'PROPIETARIO DE INFORMACIÓN:',
                '- Definir retención específica',
                '- Autorizar eliminación',
                '- Identificar legal holds',
                '',
                'TI:',
                '- Implementar controles técnicos',
                '- Ejecutar eliminación segura',
                '- Generar certificados',
                '- Documentar proceso',
                '',
                'LEGAL:',
                '- Definir requisitos legales',
                '- Aprobar períodos de retención',
                '- Gestionar legal holds',
                '',
                'PRIVACIDAD:',
                '- Cumplimiento GDPR/privacidad',
                '- Solicitudes de eliminación',
                '- Derecho al olvido'
            ],
            '9. DONACIÓN O VENTA DE EQUIPOS' => [
                'PROCESO OBLIGATORIO:',
                '- Eliminación certificada de datos',
                '- Inventario actualizado',
                '- Aprobación de propietario',
                '- Documentar destino',
                '- Verificación de eliminación',
                '',
                'NO DONAR/VENDER:',
                '- Equipos que procesaron datos críticos',
                '- Sin capacidad de eliminar datos',
                '- En su lugar: destruir'
            ],
            '10. CAPACITACIÓN' => [
                '- Todos deben conocer políticas',
                '- Procedimientos de eliminación',
                '- Qué no hacer (tirar a basura)',
                '- Consecuencias de violaciones',
                '- Reforzamiento anual'
            ]
        ],
        'cumplimiento' => 'Retención excesiva o eliminación inadecuada puede resultar en violaciones legales o de privacidad.',
        'revision' => 'Revisión anual.',
        'control_iso' => 'A.5.10 - Eliminación de información'
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
