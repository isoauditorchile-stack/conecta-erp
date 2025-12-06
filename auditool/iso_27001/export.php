<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Obtener parámetros
$code = isset($_GET['code']) ? $_GET['code'] : '';
$format = isset($_GET['format']) ? $_GET['format'] : 'docx';

if (empty($code)) {
    die('Código de documento no especificado');
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

// =====================================================
// PROCEDIMIENTOS ISO 27001
// =====================================================

$procedimientos = [
    'PROC-SI-001' => [
        'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE CAMBIOS',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Establecer un proceso estructurado para gestionar cambios en sistemas de información, infraestructura y aplicaciones, minimizando riesgos y asegurando la continuidad del negocio.',
        'alcance' => 'Todos los cambios en sistemas de información, infraestructura tecnológica, aplicaciones, bases de datos y configuraciones de seguridad.',
        'definiciones' => [
            'Cambio' => 'Modificación en sistemas, infraestructura o aplicaciones',
            'RFC' => 'Request for Change - Solicitud formal de cambio',
            'CAB' => 'Change Advisory Board - Comité de gestión de cambios',
            'Cambio de Emergencia' => 'Cambio urgente que no puede esperar proceso normal'
        ],
        'procedimiento' => [
            '1. SOLICITUD DE CAMBIO (RFC)' => [
                'Todo cambio debe iniciar con RFC formal que incluya:',
                '- Descripción detallada del cambio',
                '- Justificación de negocio',
                '- Análisis de impacto y riesgos',
                '- Plan de implementación',
                '- Plan de rollback',
                '- Recursos necesarios',
                '- Ventana de implementación propuesta',
                '- Pruebas a realizar'
            ],
            '2. CATEGORIZACIÓN DE CAMBIOS' => [
                'CAMBIO ESTÁNDAR:',
                '- Pre-aprobado, bajo riesgo, frecuente',
                '- Ejemplos: actualizaciones de antivirus, parches menores',
                '- Proceso simplificado',
                '',
                'CAMBIO NORMAL:',
                '- Requiere evaluación y aprobación del CAB',
                '- Ejemplos: nuevas aplicaciones, cambios en infraestructura',
                '- Proceso completo de gestión de cambios',
                '',
                'CAMBIO DE EMERGENCIA:',
                '- Urgente, requiere implementación inmediata',
                '- Ejemplos: corrección de vulnerabilidad crítica',
                '- Proceso acelerado con aprobación post-implementación'
            ],
            '3. EVALUACIÓN DE IMPACTO' => [
                'El solicitante debe evaluar:',
                '- Sistemas afectados',
                '- Usuarios impactados',
                '- Tiempo de implementación',
                '- Riesgo de falla',
                '- Impacto en disponibilidad',
                '- Dependencias con otros sistemas',
                '- Requisitos de comunicación'
            ],
            '4. APROBACIÓN DEL CAB' => [
                'CAB compuesto por:',
                '- CIO (Presidente)',
                '- CISO',
                '- Gerente de Infraestructura',
                '- Gerente de Desarrollo',
                '- Representante de Operaciones',
                '',
                'CAB evalúa:',
                '- Viabilidad técnica',
                '- Alineación con objetivos',
                '- Riesgos vs beneficios',
                '- Disponibilidad de recursos',
                '- Conflictos con otros cambios'
            ],
            '5. PLANIFICACIÓN E IMPLEMENTACIÓN' => [
                'ANTES DE IMPLEMENTAR:',
                '- Backup completo de sistemas afectados',
                '- Comunicación a usuarios',
                '- Preparación de ambiente de pruebas',
                '- Validación del plan de rollback',
                '',
                'DURANTE IMPLEMENTACIÓN:',
                '- Seguir plan aprobado',
                '- Documentar desviaciones',
                '- Monitoreo continuo',
                '- Comunicación de progreso',
                '',
                'DESPUÉS DE IMPLEMENTAR:',
                '- Pruebas de validación',
                '- Monitoreo post-cambio (72 horas)',
                '- Documentación final',
                '- Cierre formal del cambio'
            ],
            '6. ROLLBACK' => [
                'Plan de rollback debe incluir:',
                '- Criterios para activar rollback',
                '- Pasos detallados para revertir',
                '- Tiempo estimado de reversión',
                '- Responsables de ejecutar',
                '',
                'Rollback se ejecuta si:',
                '- Falla técnica crítica',
                '- Impacto negativo en producción',
                '- No cumple criterios de éxito',
                '- Decisión del CAB'
            ],
            '7. CAMBIOS DE EMERGENCIA' => [
                'Proceso acelerado:',
                '1. Notificación a CISO y CIO',
                '2. Aprobación verbal documentada',
                '3. Implementación inmediata',
                '4. Documentación dentro de 24 horas',
                '5. Revisión post-implementación por CAB',
                '',
                'Solo para:',
                '- Incidentes de seguridad críticos',
                '- Fallas que afectan operación',
                '- Vulnerabilidades críticas'
            ],
            '8. REGISTRO Y DOCUMENTACIÓN' => [
                'Sistema de gestión de cambios registra:',
                '- Todas las RFCs',
                '- Decisiones del CAB',
                '- Detalles de implementación',
                '- Resultados y validaciones',
                '- Lecciones aprendidas',
                '',
                'Métricas trimestrales:',
                '- Total de cambios',
                '- Tasa de éxito',
                '- Cambios con rollback',
                '- Tiempo promedio de implementación'
            ]
        ],
        'cumplimiento' => 'Todos los cambios deben seguir este procedimiento. Cambios no autorizados resultarán en acciones disciplinarias.',
        'revision' => 'Revisión semestral o cuando cambios en infraestructura lo requieran.',
        'control_iso' => 'A.8.32 - Gestión de cambios'
    ],

    'PROC-SI-002' => [
        'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE INCIDENTES DE SEGURIDAD',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Establecer un proceso sistemático para detectar, reportar, responder y recuperarse de incidentes de seguridad de la información.',
        'alcance' => 'Todos los incidentes de seguridad que afecten confidencialidad, integridad o disponibilidad de información de la organización.',
        'definiciones' => [
            'Incidente de Seguridad' => 'Evento que compromete la seguridad de la información',
            'Evento de Seguridad' => 'Ocurrencia identificada en sistema o servicio',
            'CSIRT' => 'Computer Security Incident Response Team',
            'Indicador de Compromiso' => 'Evidencia de actividad maliciosa'
        ],
        'procedimiento' => [
            '1. DETECCIÓN DE INCIDENTES' => [
                'Fuentes de detección:',
                '- SIEM (Security Information and Event Management)',
                '- IDS/IPS (Intrusion Detection/Prevention Systems)',
                '- Antivirus y EDR (Endpoint Detection and Response)',
                '- Logs de sistemas y aplicaciones',
                '- Reportes de usuarios',
                '- Alertas de proveedores',
                '- Monitoreo de redes',
                '',
                'Indicadores comunes:',
                '- Intentos de acceso no autorizado',
                '- Malware detectado',
                '- Comportamiento anómalo de sistemas',
                '- Fugas de información',
                '- Ataques de denegación de servicio',
                '- Phishing exitoso'
            ],
            '2. CLASIFICACIÓN DE INCIDENTES' => [
                'SEVERIDAD 1 - CRÍTICA:',
                '- Compromiso masivo de datos',
                '- Ransomware activo',
                '- Interrupción total de servicios críticos',
                '- Respuesta: Inmediata (15 minutos)',
                '',
                'SEVERIDAD 2 - ALTA:',
                '- Compromiso de sistemas importantes',
                '- Malware en sistemas productivos',
                '- Acceso no autorizado a información sensible',
                '- Respuesta: 1 hora',
                '',
                'SEVERIDAD 3 - MEDIA:',
                '- Malware contenido',
                '- Intentos de intrusión bloqueados',
                '- Violaciones menores de política',
                '- Respuesta: 4 horas',
                '',
                'SEVERIDAD 4 - BAJA:',
                '- Eventos de seguridad sin impacto',
                '- Falsos positivos investigados',
                '- Respuesta: 24 horas'
            ],
            '3. REPORTE DE INCIDENTES' => [
                'CANALES DE REPORTE:',
                '- Email: security@organizacion.com',
                '- Teléfono: +56 X XXXX-XXXX (24/7)',
                '- Portal interno de incidentes',
                '- Directamente al supervisor',
                '',
                'INFORMACIÓN A INCLUIR:',
                '- Qué ocurrió',
                '- Cuándo fue detectado',
                '- Sistemas afectados',
                '- Datos comprometidos',
                '- Acciones tomadas',
                '- Evidencia disponible',
                '',
                'TODO EMPLEADO DEBE REPORTAR:',
                '- Inmediatamente al detectar',
                '- Sin intentar investigar por cuenta propia',
                '- Preservando evidencia',
                '- Sin divulgar el incidente'
            ],
            '4. RESPUESTA INICIAL' => [
                'CSIRT ejecuta (primeros 30 minutos):',
                '1. Validar el incidente',
                '2. Clasificar severidad',
                '3. Asignar coordinador',
                '4. Activar equipo de respuesta',
                '5. Iniciar bitácora del incidente',
                '6. Notificar a stakeholders',
                '',
                'CONTENCIÓN INMEDIATA:',
                '- Aislar sistemas comprometidos',
                '- Bloquear cuentas afectadas',
                '- Deshabilitar vectores de ataque',
                '- Preservar evidencia forense',
                '- Prevenir propagación'
            ],
            '5. INVESTIGACIÓN Y ANÁLISIS' => [
                'RECOLECCIÓN DE EVIDENCIA:',
                '- Capturas de memoria RAM',
                '- Imágenes forenses de discos',
                '- Logs de sistemas',
                '- Tráfico de red capturado',
                '- Cadena de custodia documentada',
                '',
                'ANÁLISIS FORENSE:',
                '- Determinar vector de ataque',
                '- Identificar alcance del compromiso',
                '- Línea de tiempo del incidente',
                '- Datos exfiltrados',
                '- Vulnerabilidades explotadas',
                '- Atribución (si es posible)',
                '',
                'HERRAMIENTAS:',
                '- EnCase / FTK para análisis forense',
                '- Wireshark para tráfico de red',
                '- Volatility para análisis de memoria',
                '- SIEM para correlación de eventos'
            ],
            '6. ERRADICACIÓN Y RECUPERACIÓN' => [
                'ERRADICACIÓN:',
                '- Eliminar malware de todos los sistemas',
                '- Cerrar vulnerabilidades explotadas',
                '- Cambiar credenciales comprometidas',
                '- Aplicar parches de seguridad',
                '- Mejorar controles',
                '',
                'RECUPERACIÓN:',
                '- Restaurar desde backups limpios',
                '- Reconstruir sistemas comprometidos',
                '- Validar integridad de datos',
                '- Pruebas de funcionalidad',
                '- Monitoreo intensivo post-recuperación',
                '',
                'VALIDACIÓN:',
                '- Escaneo completo de malware',
                '- Revisión de configuraciones',
                '- Pruebas de penetración',
                '- Monitoreo 30 días extendido'
            ],
            '7. COMUNICACIÓN' => [
                'INTERNA:',
                '- Alta Dirección (inmediato para SEV 1-2)',
                '- Empleados afectados',
                '- Áreas de negocio impactadas',
                '- Actualizaciones cada 4 horas (SEV 1)',
                '',
                'EXTERNA (según aplique):',
                '- Clientes afectados (72 horas)',
                '- Autoridades regulatorias',
                '- Fuerzas de seguridad',
                '- Medios de comunicación (solo portavoz)',
                '- Proveedores de seguros',
                '',
                'TEMPLATE DE COMUNICACIÓN:',
                '- Qué pasó (hechos)',
                '- Qué estamos haciendo',
                '- Impacto estimado',
                '- Próximos pasos',
                '- Punto de contacto'
            ],
            '8. POST-INCIDENTE' => [
                'REPORTE FINAL (máximo 5 días):',
                '- Resumen ejecutivo',
                '- Línea de tiempo detallada',
                '- Causa raíz',
                '- Impacto total',
                '- Acciones tomadas',
                '- Costos asociados',
                '- Lecciones aprendidas',
                '',
                'REUNIÓN POST-MORTEM:',
                '- Dentro de 7 días del cierre',
                '- Participan: CSIRT, afectados, gerencia',
                '- Analizar qué funcionó y qué no',
                '- Identificar mejoras',
                '- Plan de acción correctivo',
                '',
                'MEJORA CONTINUA:',
                '- Actualizar playbooks',
                '- Mejorar controles',
                '- Capacitación adicional',
                '- Actualizar políticas',
                '- Simulacros de incidentes'
            ]
        ],
        'cumplimiento' => 'Todo incidente debe ser reportado y gestionado según este procedimiento. La no-reportación es violación grave.',
        'revision' => 'Revisión trimestral y después de cada incidente crítico.',
        'control_iso' => 'A.5.24, A.5.25, A.5.26 - Gestión de Incidentes de Seguridad'
    ],

    'PROC-SI-003' => [
        'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE COPIAS DE SEGURIDAD',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Garantizar la disponibilidad, integridad y recuperabilidad de información crítica mediante copias de seguridad periódicas y verificadas.',
        'alcance' => 'Todos los sistemas, aplicaciones, bases de datos y archivos críticos de la organización.',
        'definiciones' => [
            'Backup' => 'Copia de seguridad de datos',
            'RPO' => 'Recovery Point Objective - Máxima pérdida de datos tolerable',
            'RTO' => 'Recovery Time Objective - Tiempo máximo de recuperación',
            'Backup Incremental' => 'Solo cambios desde último backup',
            'Backup Diferencial' => 'Cambios desde último backup completo',
            'Backup Completo' => 'Copia total de datos'
        ],
        'procedimiento' => [
            '1. CLASIFICACIÓN DE DATOS PARA BACKUP' => [
                'NIVEL CRÍTICO (Tier 1):',
                '- Bases de datos transaccionales',
                '- Sistemas ERP/CRM',
                '- Información financiera',
                '- RPO: 1 hora',
                '- RTO: 4 horas',
                '- Frecuencia: Cada 4 horas',
                '',
                'NIVEL IMPORTANTE (Tier 2):',
                '- Servidores de aplicaciones',
                '- Archivos de usuarios',
                '- Correo electrónico',
                '- RPO: 24 horas',
                '- RTO: 24 horas',
                '- Frecuencia: Diaria',
                '',
                'NIVEL ESTÁNDAR (Tier 3):',
                '- Archivos compartidos',
                '- Documentación',
                '- Logs históricos',
                '- RPO: 7 días',
                '- RTO: 72 horas',
                '- Frecuencia: Semanal'
            ],
            '2. ESQUEMA DE BACKUPS' => [
                'BACKUP COMPLETO:',
                '- Todos los domingos a las 02:00',
                '- Todos los sistemas Tier 1 y Tier 2',
                '- Retención: 4 semanas',
                '',
                'BACKUP DIFERENCIAL:',
                '- Lunes a sábado a las 02:00',
                '- Sistemas Tier 1 y Tier 2',
                '- Retención: 7 días',
                '',
                'BACKUP INCREMENTAL:',
                '- Cada 4 horas (Tier 1)',
                '- Cada 6 horas (Tier 2)',
                '- Retención: 48 horas',
                '',
                'SNAPSHOTS:',
                '- Cada hora para bases de datos críticas',
                '- Retención: 24 horas'
            ],
            '3. TECNOLOGÍAS Y HERRAMIENTAS' => [
                'SOFTWARE DE BACKUP:',
                '- Veeam Backup & Replication (VMs)',
                '- SQL Server Always On (bases de datos)',
                '- AWS Backup (nube)',
                '- Acronis Cyber Backup (endpoints)',
                '',
                'ALMACENAMIENTO:',
                '- Primario: NAS on-premise (disco)',
                '- Secundario: Cintas LTO-8',
                '- Terciario: AWS S3 Glacier (nube)',
                '',
                'REGLA 3-2-1:',
                '- 3 copias de datos',
                '- 2 medios diferentes',
                '- 1 copia offsite'
            ],
            '4. PROCESO DE BACKUP' => [
                'AUTOMATIZACIÓN:',
                '1. Jobs programados se ejecutan automáticamente',
                '2. Sistema envía notificaciones de inicio',
                '3. Backup se ejecuta según schedule',
                '4. Verificación automática de integridad',
                '5. Reporte de éxito/falla',
                '6. Alertas si fallas',
                '',
                'MONITOREO:',
                '- Dashboard de estado de backups',
                '- Alertas por email/SMS si fallas',
                '- Revisión diaria por equipo de IT',
                '- Métricas semanales de cumplimiento',
                '',
                'VERIFICACIÓN DIARIA:',
                '- Revisar logs de backup',
                '- Confirmar completitud',
                '- Verificar tamaño esperado',
                '- Documentar excepciones'
            ],
            '5. PRUEBAS DE RESTAURACIÓN' => [
                'FRECUENCIA DE PRUEBAS:',
                '- Mensual: Sistema Tier 1 aleatorio',
                '- Trimestral: Sistema Tier 2 aleatorio',
                '- Anual: Restauración completa DR',
                '',
                'PROCESO DE PRUEBA:',
                '1. Seleccionar sistema a probar',
                '2. Preparar ambiente de prueba',
                '3. Restaurar desde backup',
                '4. Validar integridad de datos',
                '5. Verificar funcionalidad',
                '6. Medir tiempo de restauración',
                '7. Documentar resultados',
                '8. Actualizar procedimientos si necesario',
                '',
                'CRITERIOS DE ÉXITO:',
                '- Restauración completa sin errores',
                '- Datos íntegros y accesibles',
                '- Tiempo dentro de RTO',
                '- Funcionalidad validada'
            ],
            '6. SEGURIDAD DE BACKUPS' => [
                'CIFRADO:',
                '- AES-256 en tránsito y reposo',
                '- Claves gestionadas en KMS',
                '- Rotación de claves anual',
                '',
                'CONTROL DE ACCESO:',
                '- Solo personal autorizado',
                '- Autenticación multifactor',
                '- Segregación de funciones',
                '- Logs de acceso auditados',
                '',
                'INMUTABILIDAD:',
                '- Backups críticos inmutables',
                '- Período de retención forzado',
                '- Protección contra ransomware',
                '- Air-gap para copias offsite',
                '',
                'ALMACENAMIENTO SEGURO:',
                '- Cintas en bóveda externa',
                '- Ubicación clasificada',
                '- Control ambiental',
                '- Inventario mensual'
            ],
            '7. RETENCIÓN DE BACKUPS' => [
                'POLÍTICA DE RETENCIÓN:',
                '- Diarios: 7 días',
                '- Semanales: 4 semanas',
                '- Mensuales: 12 meses',
                '- Anuales: 7 años (cumplimiento legal)',
                '',
                'ELIMINACIÓN SEGURA:',
                '- Borrado criptográfico de cintas',
                '- Destrucción física si necesario',
                '- Certificado de destrucción',
                '- Registro en inventario',
                '',
                'EXCEPCIONES LEGALES:',
                '- Legal hold suspende eliminación',
                '- Coordinación con Legal',
                '- Documentación de retención extendida'
            ],
            '8. PROCEDIMIENTO DE RESTAURACIÓN' => [
                'SOLICITUD DE RESTAURACIÓN:',
                '1. Ticket formal con justificación',
                '2. Aprobación de gerente',
                '3. Autorización de CISO si datos sensibles',
                '',
                'EJECUCIÓN:',
                '1. Identificar backup apropiado',
                '2. Validar integridad antes de restaurar',
                '3. Coordinar ventana de restauración',
                '4. Ejecutar restauración',
                '5. Verificar datos restaurados',
                '6. Validación por usuario/área',
                '7. Documentar en ticket',
                '',
                'RESTAURACIÓN DE EMERGENCIA:',
                '- Proceso acelerado',
                '- Notificación a gerencia',
                '- Prioridad máxima',
                '- Documentación post-facto'
            ]
        ],
        'cumplimiento' => 'Backups son críticos para continuidad del negocio. Fallas en ejecución o validación deben escalarse inmediatamente.',
        'revision' => 'Revisión semestral o después de fallas en restauración.',
        'control_iso' => 'A.8.13 - Respaldo de información'
    ],

    'PROC-SI-004' => [
        'titulo' => 'PROCEDIMIENTO DE CONTROL DE ACCESO FÍSICO',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Prevenir acceso no autorizado a instalaciones, áreas restringidas y activos físicos de la organización.',
        'alcance' => 'Todas las instalaciones de la organización incluyendo oficinas, centros de datos, áreas de almacenamiento y ubicaciones remotas.',
        'definiciones' => [
            'Área Restringida' => 'Zona con acceso limitado a personal autorizado',
            'Tailgating' => 'Seguir a persona autorizada para entrar sin autorización',
            'Visitante' => 'Persona sin acceso permanente a instalaciones',
            'Badge' => 'Credencial de identificación y acceso'
        ],
        'procedimiento' => [
            '1. CLASIFICACIÓN DE ÁREAS' => [
                'ZONA PÚBLICA:',
                '- Lobby de recepción',
                '- Salas de reuniones con clientes',
                '- Cafetería',
                '- Acceso: Cualquier persona',
                '',
                'ZONA INTERNA:',
                '- Oficinas generales',
                '- Áreas de trabajo',
                '- Salas de reuniones internas',
                '- Acceso: Empleados con badge',
                '',
                'ZONA RESTRINGIDA:',
                '- Salas de servidores',
                '- Centro de datos',
                '- Archivo de documentos confidenciales',
                '- Acceso: Personal autorizado específicamente',
                '',
                'ZONA CRÍTICA:',
                '- Sala de seguridad/monitoreo',
                '- Bóveda de backups',
                '- Cuarto de telecomunicaciones',
                '- Acceso: Doble autenticación (badge + biométrico)'
            ],
            '2. CONTROL EN RECEPCIÓN' => [
                'EMPLEADOS:',
                '- Presentar badge visible',
                '- Registro automático por torniquete',
                '- Alertas si badge deshabilitado',
                '',
                'VISITANTES:',
                '1. Presentar identificación oficial',
                '2. Verificar cita agendada',
                '3. Registrar en libro/sistema de visitas:',
                '   - Nombre completo',
                '   - Empresa',
                '   - Persona a visitar',
                '   - Hora de entrada',
                '   - Número de identificación',
                '4. Entregar badge temporal',
                '5. Briefing de normas de seguridad',
                '6. Acompañamiento por anfitrión',
                '7. Devolución de badge al salir',
                '8. Registro de hora de salida',
                '',
                'PROVEEDORES/CONTRATISTAS:',
                '- Badge temporal con permisos específicos',
                '- Autorización previa de gerente de área',
                '- Lista de equipos/herramientas a ingresar',
                '- Inspección de salida si aplica'
            ],
            '3. SISTEMAS DE CONTROL DE ACCESO' => [
                'TECNOLOGÍAS IMPLEMENTADAS:',
                '- Torniquetes con lectores de badge',
                '- Lectores biométricos (huella/facial)',
                '- Cerraduras electrónicas',
                '- Cámaras CCTV (24/7)',
                '- Sensores de apertura de puertas',
                '- Sistema de alarmas',
                '',
                'INTEGRACIÓN:',
                '- Sistema centralizado de gestión',
                '- Logs de todos los accesos',
                '- Alertas en tiempo real',
                '- Integración con sistema HR',
                '- Dashboards de monitoreo',
                '',
                'GRABACIÓN DE VIDEO:',
                '- Retención: 90 días',
                '- Almacenamiento cifrado',
                '- Acceso solo personal autorizado',
                '- Backup diario de grabaciones'
            ],
            '4. GESTIÓN DE CREDENCIALES' => [
                'EMISIÓN DE BADGES:',
                '- Foto del empleado',
                '- Nombre completo',
                '- Cargo',
                '- Número de empleado',
                '- Fecha de emisión',
                '- Chip RFID codificado',
                '',
                'ACTIVACIÓN:',
                '- Solicitud de supervisor',
                '- Verificación de documentos HR',
                '- Configuración de permisos',
                '- Entrega contra firma',
                '- Capacitación en uso',
                '',
                'REEMPLAZO:',
                '- Pérdida: Reporte inmediato',
                '- Desactivación automática',
                '- Investigación de seguridad',
                '- Nuevo badge con diferente código',
                '- Costo: a cargo del empleado',
                '',
                'DESACTIVACIÓN:',
                '- Inmediata al término de contrato',
                '- Al reportar pérdida',
                '- Por suspensión disciplinaria',
                '- Devolución obligatoria'
            ],
            '5. ACCESO A ÁREAS RESTRINGIDAS' => [
                'AUTORIZACIÓN:',
                '- Solicitud formal (FO-SI-025)',
                '- Justificación de negocio',
                '- Aprobación de gerente de área',
                '- Aprobación adicional de CISO',
                '- Vigencia definida (revisión trimestral)',
                '',
                'ACCESO DE EMERGENCIA:',
                '- Solo personal de emergencias',
                '- Break-glass procedures',
                '- Notificación automática a CISO',
                '- Revisión dentro de 24 horas',
                '',
                'ACOMPAÑAMIENTO:',
                '- Visitantes siempre acompañados',
                '- Proveedores acompañados en zonas críticas',
                '- Anfitrión responsable de visitante',
                '- Badge de visitante visible'
            ],
            '6. HORARIOS Y ACCESOS FUERA DE HORARIO' => [
                'HORARIO NORMAL:',
                '- Lunes a viernes: 07:00 - 20:00',
                '- Sábados: 08:00 - 14:00',
                '- Acceso automático con badge',
                '',
                'FUERA DE HORARIO:',
                '- Requiere autorización previa',
                '- Registro adicional en seguridad',
                '- Justificación documentada',
                '- Alertas a supervisores',
                '- Personal de seguridad notificado',
                '',
                'ACCESO 24/7:',
                '- Solo personal crítico (IT, seguridad)',
                '- Pre-autorizado en sistema',
                '- Logs monitoreados'
            ],
            '7. MONITOREO Y VIGILANCIA' => [
                'PERSONAL DE SEGURIDAD:',
                '- 24/7 en recepción principal',
                '- Rondas cada 2 horas',
                '- Verificación de puertas y ventanas',
                '- Reporte de anomalías',
                '',
                'MONITOREO CCTV:',
                '- Central de monitoreo 24/7',
                '- Cobertura de todas las entradas',
                '- Zonas críticas con múltiples ángulos',
                '- Detección de movimiento',
                '- Alertas automáticas',
                '',
                'ALERTAS Y RESPUESTA:',
                '- Puerta forzada: Respuesta inmediata',
                '- Acceso no autorizado: Investigación',
                '- Tailgating detectado: Intervención',
                '- Alarma activada: Protocolo de emergencia'
            ],
            '8. AUDITORÍA Y CUMPLIMIENTO' => [
                'REVISIONES PERIÓDICAS:',
                '- Semanal: Logs de accesos anómalos',
                '- Mensual: Accesos fuera de horario',
                '- Trimestral: Permisos vigentes vs reales',
                '- Anual: Auditoría completa del sistema',
                '',
                'MÉTRICAS:',
                '- Total de accesos por área',
                '- Intentos de acceso denegados',
                '- Tiempo promedio de respuesta a alertas',
                '- Incidentes de seguridad física',
                '',
                'REPORTES:',
                '- Mensual a gerencia de seguridad',
                '- Trimestral a alta dirección',
                '- Inmediato ante incidentes'
            ]
        ],
        'cumplimiento' => 'El acceso no autorizado o el permitir acceso a terceros sin seguir procedimientos es violación grave de seguridad.',
        'revision' => 'Revisión semestral o tras incidentes de seguridad física.',
        'control_iso' => 'A.7.1, A.7.2, A.7.3 - Seguridad física y ambiental'
    ],

    'PROC-SI-005' => [
        'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE ACTIVOS DE INFORMACIÓN',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Identificar, clasificar, inventariar y gestionar todos los activos de información de la organización para garantizar su protección adecuada.',
        'alcance' => 'Todos los activos de información incluyendo hardware, software, datos, documentos y servicios.',
        'definiciones' => [
            'Activo de Información' => 'Cualquier elemento con valor para la organización',
            'Propietario de Activo' => 'Responsable del valor y protección del activo',
            'Custodio' => 'Responsable del almacenamiento y mantenimiento técnico',
            'Ciclo de Vida' => 'Desde adquisición hasta disposición final'
        ],
        'procedimiento' => [
            '1. CATEGORÍAS DE ACTIVOS' => [
                'ACTIVOS DE INFORMACIÓN:',
                '- Bases de datos',
                '- Archivos y documentos',
                '- Contratos y acuerdos',
                '- Manuales y procedimientos',
                '- Planes de continuidad',
                '- Información de respaldo',
                '',
                'ACTIVOS DE SOFTWARE:',
                '- Aplicaciones empresariales',
                '- Sistemas operativos',
                '- Herramientas de desarrollo',
                '- Utilitarios',
                '- Licencias',
                '',
                'ACTIVOS DE HARDWARE:',
                '- Servidores',
                '- Equipos de red',
                '- Estaciones de trabajo',
                '- Dispositivos móviles',
                '- Medios de almacenamiento',
                '- Equipos de respaldo',
                '',
                'SERVICIOS:',
                '- Servicios de comunicación',
                '- Servicios cloud',
                '- Servicios de TI externos'
            ],
            '2. INVENTARIO DE ACTIVOS' => [
                'REGISTRO OBLIGATORIO:',
                'Todo activo debe registrarse con:',
                '- ID único de activo',
                '- Descripción detallada',
                '- Tipo y categoría',
                '- Ubicación física/lógica',
                '- Propietario del activo',
                '- Custodio técnico',
                '- Clasificación de seguridad',
                '- Valor para el negocio',
                '- Fecha de adquisición',
                '- Fecha de fin de vida',
                '- Estado (activo, en mantenimiento, retirado)',
                '',
                'HERRAMIENTA DE INVENTARIO:',
                '- Sistema CMDB (Configuration Management Database)',
                '- Escaneo automático de red',
                '- Agentes en endpoints',
                '- Integración con compras',
                '- Actualizaciones en tiempo real'
            ],
            '3. IDENTIFICACIÓN Y ETIQUETADO' => [
                'ETIQUETADO FÍSICO:',
                '- Etiqueta con código de barras/QR',
                '- ID de activo visible',
                '- Etiqueta de clasificación si aplica',
                '- Información de propietario',
                '',
                'ETIQUETADO LÓGICO:',
                '- Metadata en archivos',
                '- Tags en documentos',
                '- Marcas de agua en documentos sensibles',
                '- Headers/footers con clasificación',
                '',
                'CONVENCIÓN DE NOMENCLATURA:',
                '- SRV-[ÁREA]-[FUNCIÓN]-[NÚMERO]',
                '- WS-[ÁREA]-[USUARIO]-[NÚMERO]',
                '- NET-[TIPO]-[UBICACIÓN]-[NÚMERO]'
            ],
            '4. ASIGNACIÓN DE PROPIETARIOS' => [
                'PROPIETARIO DE ACTIVO (NEGOCIO):',
                'Responsabilidades:',
                '- Clasificar el activo',
                '- Definir requisitos de seguridad',
                '- Aprobar accesos',
                '- Autorizar cambios',
                '- Revisar controles periódicamente',
                '- Decidir sobre retención/eliminación',
                '',
                'CUSTODIO (TI):',
                'Responsabilidades:',
                '- Implementar controles técnicos',
                '- Mantener disponibilidad',
                '- Ejecutar respaldos',
                '- Aplicar parches',
                '- Monitorear rendimiento',
                '- Reportar incidentes',
                '',
                'USUARIOS:',
                'Responsabilidades:',
                '- Usar según políticas',
                '- Proteger activos asignados',
                '- Reportar pérdidas',
                '- Devolver al término'
            ],
            '5. CLASIFICACIÓN DE ACTIVOS' => [
                'PROCESO DE CLASIFICACIÓN:',
                '1. Propietario evalúa:',
                '   - Impacto si se compromete confidencialidad',
                '   - Impacto si se pierde integridad',
                '   - Impacto si no está disponible',
                '2. Asignar nivel según matriz de impacto',
                '3. Documentar en inventario',
                '4. Aplicar controles correspondientes',
                '',
                'NIVELES DE CLASIFICACIÓN:',
                '- PÚBLICO: Sin restricciones',
                '  Ejemplos: Material de marketing',
                '  Controles: Básicos',
                '',
                '- INTERNO: Solo uso interno',
                '  Ejemplos: Políticas internas',
                '  Controles: Autenticación',
                '',
                '- CONFIDENCIAL: Acceso restringido',
                '  Ejemplos: Información de clientes',
                '  Controles: Cifrado, logs de acceso',
                '',
                '- RESTRINGIDO: Máxima protección',
                '  Ejemplos: Datos financieros, PI',
                '  Controles: Cifrado fuerte, DLP, auditoría'
            ],
            '6. MANEJO DURANTE CICLO DE VIDA' => [
                'ADQUISICIÓN:',
                '- Solicitud formal',
                '- Aprobación presupuestal',
                '- Evaluación de seguridad',
                '- Registro en inventario',
                '- Asignación de propietario',
                '',
                'OPERACIÓN:',
                '- Mantenimiento según fabricante',
                '- Parches de seguridad',
                '- Monitoreo de rendimiento',
                '- Auditorías periódicas',
                '- Actualización de inventario',
                '',
                'TRANSFERENCIA:',
                '- Autorización de propietario',
                '- Actualización de custodio',
                '- Borrado seguro si aplica',
                '- Registro de transferencia',
                '',
                'RETIRO/DISPOSICIÓN:',
                '- Aprobación de propietario',
                '- Backup de datos si necesario',
                '- Sanitización de datos (NIST SP 800-88)',
                '- Destrucción física si aplica',
                '- Certificado de destrucción',
                '- Actualización de inventario (estado: retirado)',
                '- Baja contable'
            ],
            '7. MANEJO DE ACTIVOS FUERA DE LA ORGANIZACIÓN' => [
                'EQUIPOS PORTÁTILES:',
                '- Cifrado de disco completo obligatorio',
                '- VPN para acceso remoto',
                '- Software de gestión remota',
                '- Cláusula de responsabilidad firmada',
                '',
                'TRABAJO REMOTO:',
                '- Política de escritorio limpio',
                '- No uso de USB no autorizados',
                '- Conexión solo por VPN',
                '- Reporte inmediato de pérdida',
                '',
                'RETORNO DE ACTIVOS:',
                '- Al término de relación laboral',
                '- Al finalizar proyecto',
                '- Formato de entrega formal',
                '- Verificación de estado',
                '- Sanitización antes de reasignar'
            ],
            '8. AUDITORÍA DE INVENTARIO' => [
                'AUDITORÍA FÍSICA:',
                '- Trimestral para activos críticos',
                '- Anual para todos los activos',
                '- Verificar ubicación vs inventario',
                '- Identificar activos no registrados',
                '- Actualizar discrepancias',
                '',
                'AUDITORÍA LÓGICA:',
                '- Escaneo automático semanal',
                '- Comparar con CMDB',
                '- Identificar shadow IT',
                '- Detectar software no autorizado',
                '',
                'REPORTE DE AUDITORÍA:',
                '- Activos no encontrados',
                '- Activos sin propietario',
                '- Activos sin clasificar',
                '- Recomendaciones de mejora',
                '- Plan de acción correctivo'
            ]
        ],
        'cumplimiento' => 'Todos los activos deben estar inventariados y clasificados. El uso de activos no registrados está prohibido.',
        'revision' => 'Revisión anual o cuando cambios organizacionales lo requieran.',
        'control_iso' => 'A.5.9, A.5.10, A.5.11, A.5.12 - Gestión de activos'
    ],

    'PROC-SI-006' => [
        'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE VULNERABILIDADES',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Identificar, evaluar, priorizar y remediar vulnerabilidades de seguridad en sistemas, aplicaciones e infraestructura.',
        'alcance' => 'Todos los sistemas de información, aplicaciones, infraestructura de red y dispositivos de la organización.',
        'definiciones' => [
            'Vulnerabilidad' => 'Debilidad que puede ser explotada por amenazas',
            'CVE' => 'Common Vulnerabilities and Exposures',
            'CVSS' => 'Common Vulnerability Scoring System',
            'Parche' => 'Actualización de software que corrige vulnerabilidad',
            'Exploit' => 'Código que aprovecha vulnerabilidad'
        ],
        'procedimiento' => [
            '1. ESCANEO DE VULNERABILIDADES' => [
                'FRECUENCIA:',
                '- Sistemas críticos: Semanal',
                '- Sistemas importantes: Quincenal',
                '- Sistemas estándar: Mensual',
                '- Después de cambios importantes',
                '- Antes de poner sistema en producción',
                '',
                'HERRAMIENTAS:',
                '- Nessus / Qualys para infraestructura',
                '- OWASP ZAP / Burp Suite para aplicaciones web',
                '- SonarQube para código fuente',
                '- Dependency Check para librerías',
                '- OpenVAS para escaneos adicionales',
                '',
                'ALCANCE DEL ESCANEO:',
                '- Todos los hosts de red',
                '- Aplicaciones web internas y externas',
                '- Bases de datos',
                '- Servicios cloud',
                '- Dispositivos de red',
                '- Endpoints'
            ],
            '2. ANÁLISIS Y CLASIFICACIÓN' => [
                'EVALUACIÓN DE SEVERIDAD (CVSS v3):',
                '- CRÍTICA (9.0-10.0):',
                '  * Explotación remota sin autenticación',
                '  * Impacto total en CIA',
                '  * SLA: 24 horas',
                '',
                '- ALTA (7.0-8.9):',
                '  * Compromiso significativo',
                '  * Requiere poca interacción',
                '  * SLA: 7 días',
                '',
                '- MEDIA (4.0-6.9):',
                '  * Impacto moderado',
                '  * Requiere condiciones específicas',
                '  * SLA: 30 días',
                '',
                '- BAJA (0.1-3.9):',
                '  * Impacto mínimo',
                '  * Difícil explotación',
                '  * SLA: 90 días',
                '',
                'FACTORES ADICIONALES:',
                '- Existe exploit público?',
                '- Está siendo explotada activamente?',
                '- Exposición a Internet?',
                '- Criticidad del sistema afectado?',
                '- Datos sensibles en riesgo?'
            ],
            '3. PRIORIZACIÓN' => [
                'MATRIZ DE RIESGO:',
                '- Severidad de vulnerabilidad (CVSS)',
                '- Criticidad del activo',
                '- Exposición (interna/externa)',
                '- Existencia de compensaciones',
                '',
                'CRITERIOS DE PRIORIDAD MÁXIMA:',
                '- CVSS crítico + Internet-facing',
                '- Exploit activo + datos sensibles',
                '- Sistemas financieros',
                '- Infraestructura crítica',
                '',
                'EXCEPCIONES:',
                '- Justificación documentada',
                '- Aprobación de CISO',
                '- Controles compensatorios implementados',
                '- Riesgo aceptado formalmente',
                '- Revisión trimestral'
            ],
            '4. REMEDIACIÓN' => [
                'PROCESO ESTÁNDAR:',
                '1. Asignar a equipo responsable',
                '2. Investigar solución (parche, configuración, workaround)',
                '3. Probar en ambiente de desarrollo',
                '4. Validar en ambiente de QA',
                '5. Crear plan de implementación',
                '6. RFC si es cambio importante',
                '7. Implementar en producción',
                '8. Validar corrección',
                '9. Re-escanear para confirmar',
                '10. Cerrar ticket',
                '',
                'PARCHES DE SEGURIDAD:',
                '- Críticos: Proceso acelerado',
                '- Validación reducida pero obligatoria',
                '- Implementación en ventanas de mantenimiento',
                '- Rollback plan preparado',
                '',
                'REMEDIACIÓN ALTERNATIVA:',
                'Si parche no disponible:',
                '- Configuración de seguridad',
                '- Reglas de firewall',
                '- WAF rules',
                '- IPS signatures',
                '- Deshabilitar funcionalidad vulnerable',
                '- Aislamiento de red'
            ],
            '5. GESTIÓN DE PARCHES' => [
                'CLASIFICACIÓN DE PARCHES:',
                '- Seguridad Crítica: 24-48 horas',
                '- Seguridad Alta: 7 días',
                '- Seguridad Media: 30 días',
                '- Funcionalidad: Próxima ventana',
                '',
                'PROCESO DE PARCHADO:',
                'MES 1 - Semana 1: Microsoft Patch Tuesday',
                '- Evaluación de parches publicados',
                '- Descarga y prueba inicial',
                '',
                'MES 1 - Semana 2:',
                '- Pruebas en desarrollo',
                '- Validación de compatibilidad',
                '',
                'MES 1 - Semana 3:',
                '- Despliegue en QA/UAT',
                '- Validación de usuarios',
                '',
                'MES 1 - Semana 4:',
                '- Despliegue en producción por fases',
                '- Monitoreo intensivo',
                '',
                'AUTOMATIZACIÓN:',
                '- WSUS para Windows',
                '- Ansible para Linux',
                '- SCCM para endpoints',
                '- Parches automáticos para antivirus'
            ],
            '6. GESTIÓN DE FALSOS POSITIVOS' => [
                'VALIDACIÓN:',
                '- Todo hallazgo de alta/crítica validado manualmente',
                '- Verificar si vulnerabilidad realmente existe',
                '- Confirmar versión afectada',
                '- Probar explotabilidad si es seguro',
                '',
                'DOCUMENTACIÓN DE FP:',
                '- Razón por la que es falso positivo',
                '- Evidencia de validación',
                '- Configuración de scanner para suprimir',
                '- Revisión en próximo escaneo',
                '',
                'AJUSTE DE HERRAMIENTAS:',
                '- Plugins actualizados',
                '- Reglas refinadas',
                '- Baseline de ambiente'
            ],
            '7. REPORTE Y MÉTRICAS' => [
                'REPORTE SEMANAL:',
                '- Nuevas vulnerabilidades críticas/altas',
                '- Vulnerabilidades vencidas (past SLA)',
                '- Top 10 sistemas con más vulnerabilidades',
                '- Tendencia de remediación',
                '',
                'REPORTE MENSUAL A GERENCIA:',
                '- Resumen ejecutivo',
                '- Vulnerabilidades por severidad',
                '- Tasa de remediación',
                '- Cumplimiento de SLAs',
                '- Vulnerabilidades antiguas (>90 días)',
                '- Comparación mes anterior',
                '',
                'KPIs:',
                '- Tiempo promedio de remediación',
                '- % de cumplimiento de SLA',
                '- Vulnerabilidades nuevas vs cerradas',
                '- Cobertura de escaneos',
                '- Tendencia de postura de seguridad'
            ],
            '8. DIVULGACIÓN RESPONSABLE' => [
                'SI DESCUBRIMOS VULNERABILIDAD EN TERCEROS:',
                '1. No explotar la vulnerabilidad',
                '2. Documentar hallazgo',
                '3. Contactar al vendor/desarrollador',
                '4. Dar tiempo razonable para corregir (90 días)',
                '5. Coordinar divulgación pública si aplica',
                '',
                'SI RECIBIMOS REPORTE DE VULNERABILIDAD:',
                '1. Acusar recibo (24 horas)',
                '2. Validar el reporte',
                '3. Clasificar severidad',
                '4. Comunicar timeline de corrección',
                '5. Desarrollar y probar fix',
                '6. Desplegar corrección',
                '7. Notificar al reportante',
                '8. Agradecer y reconocer (si desea)',
                '',
                'BUG BOUNTY:',
                '- Programa en plataforma reconocida',
                '- Alcance claramente definido',
                '- Reglas de engagement',
                '- Recompensas según severidad',
                '- Proceso de validación y pago'
            ]
        ],
        'cumplimiento' => 'La gestión de vulnerabilidades es crítica para la seguridad. El incumplimiento de SLAs debe ser justificado y aprobado por CISO.',
        'revision' => 'Revisión trimestral o cuando nuevas amenazas lo requieran.',
        'control_iso' => 'A.8.8 - Gestión de vulnerabilidades técnicas'
    ],

    'PROC-SI-007' => [
        'titulo' => 'PROCEDIMIENTO DE DESARROLLO SEGURO DE SOFTWARE',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Integrar seguridad en todas las fases del ciclo de vida de desarrollo de software para prevenir vulnerabilidades.',
        'alcance' => 'Todos los proyectos de desarrollo de software, tanto interno como tercerizado.',
        'definiciones' => [
            'SDLC' => 'Software Development Life Cycle',
            'SAST' => 'Static Application Security Testing',
            'DAST' => 'Dynamic Application Security Testing',
            'DevSecOps' => 'Integración de seguridad en DevOps',
            'Threat Modeling' => 'Modelado de amenazas'
        ],
        'procedimiento' => [
            '1. FASE DE REQUISITOS' => [
                'REQUISITOS DE SEGURIDAD:',
                '- Autenticación y autorización',
                '- Protección de datos sensibles',
                '- Logging y auditoría',
                '- Gestión de sesiones',
                '- Validación de entradas',
                '- Manejo de errores',
                '- Cifrado de datos',
                '',
                'ANÁLISIS DE PRIVACIDAD:',
                '- PIA (Privacy Impact Assessment) si aplica',
                '- Cumplimiento GDPR/CCPA',
                '- Minimización de datos',
                '- Retención y eliminación',
                '',
                'CLASIFICACIÓN DE DATOS:',
                '- Identificar datos sensibles',
                '- Aplicar clasificación',
                '- Definir controles necesarios'
            ],
            '2. FASE DE DISEÑO' => [
                'THREAT MODELING:',
                'Metodología STRIDE:',
                '- Spoofing (Suplantación)',
                '- Tampering (Manipulación)',
                '- Repudiation (Repudio)',
                '- Information Disclosure (Divulgación)',
                '- Denial of Service (Denegación)',
                '- Elevation of Privilege (Elevación)',
                '',
                'DIAGRAMA DE ARQUITECTURA:',
                '- Flujos de datos',
                '- Límites de confianza',
                '- Puntos de entrada',
                '- Almacenamiento de datos',
                '- Componentes externos',
                '',
                'REVISIÓN DE DISEÑO DE SEGURIDAD:',
                '- Aprobación de arquitecto de seguridad',
                '- Validación de controles',
                '- Documentación de decisiones',
                '- Registro de riesgos aceptados'
            ],
            '3. FASE DE DESARROLLO' => [
                'ESTÁNDARES DE CODIFICACIÓN:',
                '- OWASP Secure Coding Practices',
                '- Guías específicas por lenguaje',
                '- Revisión de código obligatoria',
                '- Linters de seguridad',
                '',
                'CONTROLES OBLIGATORIOS:',
                '- Validación de entrada en servidor',
                '- Parametrización de consultas SQL',
                '- Codificación de salida',
                '- Uso de frameworks de seguridad',
                '- Manejo seguro de autenticación',
                '- Tokens CSRF',
                '- Headers de seguridad HTTP',
                '',
                'GESTIÓN DE SECRETOS:',
                '- NO hardcodear credenciales',
                '- Usar secretos manager (Vault, KMS)',
                '- Variables de entorno',
                '- Rotación de secretos',
                '',
                'DEPENDENCIAS:',
                '- Solo librerías aprobadas',
                '- Verificar integridad (checksums)',
                '- Mantener actualizado',
                '- Escaneo de vulnerabilidades'
            ],
            '4. PRUEBAS DE SEGURIDAD' => [
                'SAST (ANÁLISIS ESTÁTICO):',
                'Herramientas:',
                '- SonarQube',
                '- Checkmarx',
                '- Fortify',
                '',
                'Integración:',
                '- En IDE del desarrollador',
                '- En pipeline CI/CD',
                '- Umbral de severidad',
                '- Bloqueo de build si críticos',
                '',
                'DAST (ANÁLISIS DINÁMICO):',
                'Herramientas:',
                '- OWASP ZAP',
                '- Burp Suite Professional',
                '- Acunetix',
                '',
                'Ejecución:',
                '- Ambiente de QA',
                '- Credenciales de prueba',
                '- Cobertura completa',
                '',
                'SCA (ANÁLISIS DE COMPONENTES):',
                'Herramientas:',
                '- Snyk',
                '- WhiteSource',
                '- Dependency-Check',
                '',
                'Verificación:',
                '- Vulnerabilidades conocidas',
                '- Licencias de software',
                '- Versiones obsoletas',
                '',
                'PENETRATION TESTING:',
                '- Antes de producción',
                '- Por equipo externo',
                '- Aplicaciones críticas',
                '- Retest después de fixes'
            ],
            '5. CI/CD SEGURO' => [
                'PIPELINE DE SEGURIDAD:',
                '1. Commit de código',
                '2. SAST automático',
                '3. Análisis de dependencias',
                '4. Unit tests (incluyendo seguridad)',
                '5. Build',
                '6. Escaneo de contenedor (si aplica)',
                '7. Deploy a QA',
                '8. DAST automático',
                '9. Aprobación manual',
                '10. Deploy a producción',
                '',
                'GATES DE CALIDAD:',
                '- 0 vulnerabilidades críticas',
                '- <5 vulnerabilidades altas',
                '- Cobertura de código >80%',
                '- Pruebas de seguridad pasadas',
                '',
                'SEGURIDAD DE PIPELINE:',
                '- Acceso basado en roles',
                '- Logs de auditoría',
                '- Secretos en vault',
                '- Firmas de artefactos',
                '- Ambientes aislados'
            ],
            '6. REVISIÓN DE CÓDIGO' => [
                'PEER REVIEW:',
                '- Todo código revisado antes de merge',
                '- Pull request obligatorio',
                '- Al menos 1 aprobación',
                '- Checklist de seguridad',
                '',
                'CHECKLIST DE SEGURIDAD:',
                '□ Validación de entrada',
                '□ Manejo de errores apropiado',
                '□ No hay secretos hardcodeados',
                '□ Logs no contienen datos sensibles',
                '□ Autorización verificada',
                '□ SQL parametrizado',
                '□ Codificación de salida',
                '□ Uso seguro de criptografía',
                '',
                'SECURITY CHAMPION:',
                '- Al menos uno por equipo',
                '- Capacitación avanzada en seguridad',
                '- Punto de contacto con equipo de seguridad',
                '- Revisión de cambios sensibles'
            ],
            '7. DEPLOYMENT Y CONFIGURACIÓN' => [
                'HARDENING DE AMBIENTE:',
                '- Principio de menor privilegio',
                '- Servicios innecesarios deshabilitados',
                '- Configuración segura por defecto',
                '- Actualizaciones automáticas',
                '',
                'CONFIGURACIÓN DE SEGURIDAD:',
                '- HTTPS obligatorio (TLS 1.2+)',
                '- Headers de seguridad:',
                '  * Content-Security-Policy',
                '  * X-Frame-Options',
                '  * X-Content-Type-Options',
                '  * Strict-Transport-Security',
                '  * X-XSS-Protection',
                '- Cookies con flags Secure y HttpOnly',
                '- Timeouts de sesión apropiados',
                '',
                'INFRAESTRUCTURA COMO CÓDIGO:',
                '- Versionado en Git',
                '- Revisión antes de aplicar',
                '- Escaneo de seguridad (Terraform, CloudFormation)',
                '- Políticas como código (OPA)'
            ],
            '8. MONITOREO Y RESPUESTA' => [
                'LOGGING DE SEGURIDAD:',
                'Registrar:',
                '- Autenticación (éxitos y fallas)',
                '- Autorización (accesos denegados)',
                '- Cambios en datos sensibles',
                '- Errores de seguridad',
                '- Actividad administrativa',
                '',
                'NO registrar:',
                '- Contraseñas',
                '- Tokens de sesión',
                '- Números de tarjetas completos',
                '- Datos personales sensibles',
                '',
                'WAF (WEB APPLICATION FIREWALL):',
                '- Ruleset OWASP ModSecurity',
                '- Modo de aprendizaje inicial',
                '- Tuning para reducir FPs',
                '- Actualización continua',
                '',
                'MONITOREO DE ATAQUES:',
                '- Intentos de inyección SQL',
                '- XSS attempts',
                '- Path traversal',
                '- Autenticación brute force',
                '- Anomalías de tráfico'
            ]
        ],
        'cumplimiento' => 'Todo desarrollo debe seguir este procedimiento. Excepciones requieren aprobación de CISO y aceptación formal de riesgos.',
        'revision' => 'Revisión semestral o cuando surjan nuevas vulnerabilidades comunes.',
        'control_iso' => 'A.8.25, A.8.26, A.8.27, A.8.28, A.8.29 - Desarrollo seguro'
    ],

    'PROC-SI-008' => [
        'titulo' => 'PROCEDIMIENTO DE CAPACITACIÓN Y CONCIENCIACIÓN EN SEGURIDAD',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Asegurar que todos los empleados tengan el conocimiento y habilidades necesarios para cumplir con las políticas de seguridad y proteger los activos de información.',
        'alcance' => 'Todos los empleados, contratistas, proveedores y terceros con acceso a sistemas o información de la organización.',
        'definiciones' => [
            'Capacitación' => 'Formación formal en seguridad de la información',
            'Concienciación' => 'Actividades para mantener seguridad presente',
            'Phishing Simulation' => 'Prueba de susceptibilidad a phishing',
            'Security Champion' => 'Empleado con conocimientos avanzados de seguridad'
        ],
        'procedimiento' => [
            '1. PROGRAMA DE CAPACITACIÓN' => [
                'CAPACITACIÓN DE INDUCCIÓN:',
                'Para todos los nuevos empleados:',
                '- Durante primera semana',
                '- Duración: 2 horas',
                '- Contenido:',
                '  * Políticas de seguridad',
                '  * Clasificación de información',
                '  * Contraseñas seguras',
                '  * Phishing y social engineering',
                '  * Uso aceptable de recursos',
                '  * Reporte de incidentes',
                '  * Escritorio y pantalla limpios',
                '- Quiz final (80% para aprobar)',
                '- Certificado de completitud',
                '- Obligatorio antes de acceso a sistemas',
                '',
                'CAPACITACIÓN ANUAL:',
                'Para todos los empleados:',
                '- Renovación anual obligatoria',
                '- Duración: 1 hora',
                '- Actualización de políticas',
                '- Nuevas amenazas',
                '- Lecciones de incidentes del año',
                '- Quiz final',
                '- Tracking de completitud'
            ],
            '2. CAPACITACIÓN POR ROLES' => [
                'DESARROLLADORES:',
                '- Secure coding (8 horas)',
                '- OWASP Top 10 (4 horas)',
                '- Code review de seguridad (4 horas)',
                '- Anual',
                '',
                'ADMINISTRADORES DE SISTEMAS:',
                '- Hardening de sistemas (8 horas)',
                '- Gestión de parches (4 horas)',
                '- Respuesta a incidentes (8 horas)',
                '- Anual',
                '',
                'USUARIOS PRIVILEGIADOS:',
                '- Responsabilidades de accesos privilegiados (2 horas)',
                '- Gestión de secretos (2 horas)',
                '- Semestral',
                '',
                'GERENTES:',
                '- Gestión de riesgos (4 horas)',
                '- Responsabilidades de aprobación (2 horas)',
                '- Anual',
                '',
                'PERSONAL DE SEGURIDAD:',
                '- Certificaciones profesionales (CISSP, CEH, etc.)',
                '- Conferencias de seguridad',
                '- Capacitación continua',
                '- Presupuesto anual asignado'
            ],
            '3. PROGRAMA DE CONCIENCIACIÓN' => [
                'COMUNICACIONES MENSUALES:',
                '- Email de seguridad del mes',
                '- Tip de seguridad semanal',
                '- Boletín trimestral',
                '- Casos de estudio de incidentes',
                '',
                'CAMPAÑAS TEMÁTICAS:',
                'Mes 1-2: Contraseñas y autenticación',
                'Mes 3-4: Phishing y social engineering',
                'Mes 5-6: Seguridad física',
                'Mes 7-8: Protección de datos',
                'Mes 9-10: Seguridad móvil',
                'Mes 11-12: Preparación para ciberataques',
                '',
                'RECURSOS:',
                '- Posters en áreas comunes',
                '- Protectores de pantalla',
                '- Intranet con recursos de seguridad',
                '- Videos cortos (2-3 minutos)',
                '- Infografías',
                '- Guías de referencia rápida'
            ],
            '4. SIMULACIONES DE PHISHING' => [
                'FRECUENCIA:',
                '- Mensual para todos los empleados',
                '- Incremento de dificultad progresivo',
                '- Variedad de técnicas',
                '',
                'PROCESO:',
                '1. Seleccionar plantilla de phishing',
                '2. Personalizar para contexto organizacional',
                '3. Enviar a empleados seleccionados',
                '4. Monitorear clicks y reportes',
                '5. Educación inmediata para quienes fallan',
                '6. Análisis de resultados',
                '7. Reporte a gerencia',
                '',
                'MÉTRICAS:',
                '- Tasa de clicks',
                '- Tasa de reporte',
                '- Tiempo promedio de reporte',
                '- Tendencia mes a mes',
                '- Comparación por departamento',
                '',
                'CONSECUENCIAS:',
                '- 1er fallo: Capacitación adicional',
                '- 2do fallo (3 meses): Notificación a supervisor',
                '- 3er fallo (6 meses): Reunión con RRHH',
                '- Fallos repetidos: Evaluación de desempeño'
            ],
            '5. PROGRAMA SECURITY CHAMPIONS' => [
                'SELECCIÓN:',
                '- Al menos uno por departamento',
                '- Voluntarios con interés en seguridad',
                '- Aprobación de gerente',
                '- Compromiso de tiempo (2-4 horas/mes)',
                '',
                'CAPACITACIÓN:',
                '- Bootcamp inicial (16 horas)',
                '- Reuniones mensuales',
                '- Acceso a capacitación avanzada',
                '- Certificaciones subsidiadas',
                '',
                'RESPONSABILIDADES:',
                '- Promover seguridad en su equipo',
                '- Responder preguntas básicas',
                '- Organizar actividades de concienciación',
                '- Feedback sobre políticas',
                '- Identificar necesidades de capacitación',
                '',
                'BENEFICIOS:',
                '- Reconocimiento público',
                '- Certificado anual',
                '- Desarrollo profesional',
                '- Networking con seguridad'
            ],
            '6. EVALUACIÓN Y SEGUIMIENTO' => [
                'MÉTRICAS DE CAPACITACIÓN:',
                '- % de completitud de capacitación obligatoria',
                '- Puntuación promedio en quizzes',
                '- Tiempo promedio de completitud',
                '- Capacitación vencida',
                '',
                'MÉTRICAS DE CONCIENCIACIÓN:',
                '- Engagement con comunicaciones',
                '- Resultados de phishing simulations',
                '- Reportes de incidentes por empleados',
                '- Participación en actividades',
                '',
                'REPORTES:',
                '- Mensual: Dashboard de capacitación',
                '- Trimestral: Análisis de tendencias',
                '- Anual: Evaluación del programa',
                '',
                'OBJETIVOS:',
                '- 100% completitud de capacitación obligatoria',
                '- <10% de clicks en phishing simulations',
                '- >50% de reportes de phishing',
                '- Reducción YoY de incidentes por error humano'
            ],
            '7. CAPACITACIÓN DE TERCEROS' => [
                'PROVEEDORES CON ACCESO A SISTEMAS:',
                '- Capacitación básica obligatoria',
                '- Políticas relevantes',
                '- Acuerdo de confidencialidad',
                '- Renovación anual',
                '',
                'CONTRATISTAS:',
                '- Misma capacitación que empleados',
                '- Según duración y acceso',
                '- Tracking independiente',
                '',
                'CLIENTES (SI APLICA):',
                '- Guías de uso seguro',
                '- Best practices',
                '- Portal de recursos',
                '- Webinars opcionales'
            ],
            '8. MEJORA CONTINUA' => [
                'EVALUACIÓN ANUAL DEL PROGRAMA:',
                '- Efectividad de capacitación',
                '- Relevancia del contenido',
                '- Feedback de participantes',
                '- Análisis de incidentes vs capacitación',
                '- Benchmarking con industria',
                '',
                'ACTUALIZACIONES:',
                '- Contenido actualizado con nuevas amenazas',
                '- Lecciones de incidentes incorporadas',
                '- Nuevas técnicas de enseñanza',
                '- Gamificación',
                '- Microlearning',
                '',
                'RECONOCIMIENTO:',
                '- Empleado más consciente de seguridad (trimestral)',
                '- Mejor reporte de incidente',
                '- Departamento con mejor desempeño',
                '- Incentivos y premios'
            ]
        ],
        'cumplimiento' => 'La capacitación de seguridad es obligatoria. El acceso a sistemas puede ser revocado si capacitación no está al día.',
        'revision' => 'Revisión anual del programa de capacitación.',
        'control_iso' => 'A.6.3 - Concienciación, educación y capacitación en seguridad'
    ],

    'PROC-SI-009' => [
        'titulo' => 'PROCEDIMIENTO DE CONTINUIDAD DEL NEGOCIO Y RECUPERACIÓN DE DESASTRES',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Garantizar la continuidad de operaciones críticas y la recuperación de sistemas ante desastres, minimizando el impacto al negocio.',
        'alcance' => 'Todos los procesos de negocio críticos y sistemas de información que los soportan.',
        'definiciones' => [
            'BCP' => 'Business Continuity Plan',
            'DRP' => 'Disaster Recovery Plan',
            'RTO' => 'Recovery Time Objective - Tiempo máximo de recuperación',
            'RPO' => 'Recovery Point Objective - Pérdida de datos tolerable',
            'MTPD' => 'Maximum Tolerable Period of Disruption'
        ],
        'procedimiento' => [
            '1. ANÁLISIS DE IMPACTO AL NEGOCIO (BIA)' => [
                'IDENTIFICACIÓN DE PROCESOS CRÍTICOS:',
                'Para cada proceso evaluar:',
                '- Descripción del proceso',
                '- Sistemas que lo soportan',
                '- Personal clave',
                '- Dependencias internas/externas',
                '- Impacto de interrupción por tiempo',
                '- Criticidad (Crítico/Importante/Normal)',
                '',
                'CLASIFICACIÓN POR CRITICIDAD:',
                'TIER 1 - CRÍTICO:',
                '- Impacto inmediato severo',
                '- Pérdidas financieras significativas',
                '- Incumplimiento legal/regulatorio',
                '- RTO: 4 horas',
                '- RPO: 1 hora',
                '',
                'TIER 2 - IMPORTANTE:',
                '- Impacto significativo en 24-48h',
                '- Afecta satisfacción de clientes',
                '- RTO: 24 horas',
                '- RPO: 24 horas',
                '',
                'TIER 3 - NORMAL:',
                '- Impacto menor o retrasado',
                '- Puede tolerarse varios días',
                '- RTO: 72 horas',
                '- RPO: 7 días'
            ],
            '2. EVALUACIÓN DE RIESGOS' => [
                'AMENAZAS IDENTIFICADAS:',
                '- Desastres naturales (terremoto, incendio, inundación)',
                '- Fallas de infraestructura (electricidad, internet)',
                '- Ciberataques (ransomware, DDoS)',
                '- Fallas de hardware',
                '- Errores humanos',
                '- Pandemias',
                '- Terrorismo',
                '',
                'ANÁLISIS:',
                '- Probabilidad de ocurrencia',
                '- Impacto potencial',
                '- Controles preventivos existentes',
                '- Estrategias de mitigación',
                '- Priorización de riesgos'
            ],
            '3. ESTRATEGIAS DE CONTINUIDAD' => [
                'INFRAESTRUCTURA:',
                '- Sitio de recuperación alterno (DR site)',
                '  * Hot site: Réplica activa (sistemas críticos)',
                '  * Warm site: Hardware listo, datos a restaurar',
                '  * Cold site: Solo espacio físico',
                '- Alta disponibilidad (HA) para sistemas críticos',
                '- Redundancia geográfica',
                '- Cloud como DR',
                '',
                'DATOS:',
                '- Backups según PROC-SI-003',
                '- Replicación en tiempo real (Tier 1)',
                '- Backups offsite',
                '- Regla 3-2-1',
                '',
                'PERSONAL:',
                '- Trabajo remoto habilitado',
                '- Personal de respaldo capacitado',
                '- Procedimientos documentados',
                '- Contactos de emergencia actualizados',
                '',
                'PROVEEDORES:',
                '- Cláusulas de continuidad en contratos',
                '- Proveedores alternativos identificados',
                '- SLAs definidos'
            ],
            '4. PLAN DE CONTINUIDAD (BCP)' => [
                'ESTRUCTURA DEL BCP:',
                '1. RESUMEN EJECUTIVO',
                '2. OBJETIVOS Y ALCANCE',
                '3. ROLES Y RESPONSABILIDADES:',
                '   - Comité de Crisis',
                '   - Equipos de Recuperación',
                '   - Contactos de Emergencia',
                '4. PROCEDIMIENTOS DE ACTIVACIÓN:',
                '   - Criterios para declarar desastre',
                '   - Proceso de notificación',
                '   - Escalamiento',
                '5. PROCEDIMIENTOS DE RECUPERACIÓN:',
                '   - Por proceso de negocio',
                '   - Por sistema',
                '   - Checklists paso a paso',
                '6. COMUNICACIÓN:',
                '   - Empleados',
                '   - Clientes',
                '   - Proveedores',
                '   - Medios',
                '   - Autoridades',
                '7. RETORNO A NORMALIDAD',
                '',
                'COMITÉ DE CRISIS:',
                '- CEO (líder)',
                '- CIO',
                '- CISO',
                '- CFO',
                '- Director de Operaciones',
                '- Director de RRHH',
                '- Director de Comunicaciones'
            ],
            '5. PLAN DE RECUPERACIÓN DE DESASTRES (DRP)' => [
                'PROCEDIMIENTOS DE RECUPERACIÓN:',
                '',
                'FASE 1 - EVALUACIÓN (0-2 horas):',
                '1. Declaración de desastre',
                '2. Activación del equipo de DR',
                '3. Evaluación de daños',
                '4. Determinación de estrategia de recuperación',
                '5. Comunicación a stakeholders',
                '',
                'FASE 2 - RECUPERACIÓN (2-24 horas):',
                '1. Activación de sitio alterno',
                '2. Restauración de infraestructura crítica:',
                '   - Red y conectividad',
                '   - Servidores de autenticación',
                '   - Bases de datos Tier 1',
                '   - Aplicaciones críticas',
                '3. Validación de sistemas',
                '4. Habilitación de usuarios',
                '5. Monitoreo intensivo',
                '',
                'FASE 3 - OPERACIÓN ALTERNATIVA (1-7 días):',
                '1. Operación desde sitio DR',
                '2. Recuperación de sistemas Tier 2',
                '3. Evaluación de sitio primario',
                '4. Planificación de retorno',
                '',
                'FASE 4 - RETORNO (variable):',
                '1. Reparación de sitio primario',
                '2. Validación y pruebas',
                '3. Migración de vuelta',
                '4. Resincronización de datos',
                '5. Retorno a operación normal',
                '6. Post-mortem'
            ],
            '6. COMUNICACIÓN EN CRISIS' => [
                'PLAN DE COMUNICACIÓN:',
                '',
                'INTERNO:',
                '- Sistema de notificación masiva',
                '- Árbol telefónico',
                '- Email, SMS, app móvil',
                '- Línea directa de información',
                '- Actualizaciones cada 2-4 horas',
                '',
                'EXTERNO:',
                '- Clientes: Notificación de impacto',
                '- Proveedores: Activación de planes',
                '- Medios: Solo portavoz autorizado',
                '- Autoridades: Cumplimiento legal',
                '- Aseguradoras: Reporte de daños',
                '',
                'TEMPLATES:',
                '- Declaración inicial',
                '- Actualizaciones de progreso',
                '- Retorno a normalidad',
                '- FAQ para empleados',
                '- FAQ para clientes'
            ],
            '7. PRUEBAS Y EJERCICIOS' => [
                'TIPOS DE PRUEBAS:',
                '',
                'DESKTOP WALKTHROUGH (Trimestral):',
                '- Revisión de procedimientos',
                '- Discusión de escenarios',
                '- Identificación de gaps',
                '- Duración: 2-3 horas',
                '- Participantes: Equipos clave',
                '',
                'PRUEBA TÉCNICA (Semestral):',
                '- Restauración de sistema específico',
                '- Validación de backups',
                '- Prueba de RTO/RPO',
                '- Sin declarar desastre real',
                '',
                'SIMULACRO COMPLETO (Anual):',
                '- Escenario de desastre completo',
                '- Activación de todos los equipos',
                '- Recuperación de sistemas críticos',
                '- Comunicaciones reales',
                '- Evaluación completa',
                '- Duración: 1-2 días',
                '',
                'EVALUACIÓN:',
                '- RTO/RPO alcanzados?',
                '- Procedimientos claros?',
                '- Recursos suficientes?',
                '- Comunicación efectiva?',
                '- Lecciones aprendidas',
                '- Plan de mejora'
            ],
            '8. MANTENIMIENTO DEL PLAN' => [
                'ACTUALIZACIONES:',
                '- Revisión trimestral de contactos',
                '- Actualización semestral de procedimientos',
                '- Revisión anual completa del plan',
                '- Después de cada incidente real',
                '- Después de cambios organizacionales',
                '- Después de cambios en infraestructura',
                '',
                'CONTROL DE VERSIONES:',
                '- Versionado formal',
                '- Registro de cambios',
                '- Aprobación de cambios',
                '- Distribución de versión actualizada',
                '',
                'CAPACITACIÓN:',
                '- Inducción para nuevos miembros de equipos',
                '- Actualización anual para todos',
                '- Capacitación específica por rol',
                '- Disponibilidad 24/7 de documentación',
                '',
                'MÉTRICAS:',
                '- Tiempo de recuperación en pruebas',
                '- Éxito de restauraciones de backup',
                '- % de personal capacitado',
                '- Edad promedio de datos de contacto',
                '- Frecuencia de actualizaciones'
            ]
        ],
        'cumplimiento' => 'Los planes de continuidad y recuperación son críticos. La participación en pruebas y mantenimiento es obligatoria para personal designado.',
        'revision' => 'Revisión trimestral del plan y anual completa.',
        'control_iso' => 'A.5.29, A.5.30 - Continuidad del negocio'
    ],

    'PROC-SI-010' => [
        'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE PROVEEDORES Y TERCEROS',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Gestionar riesgos de seguridad asociados con proveedores, contratistas y terceros que procesan, almacenan o transmiten información de la organización.',
        'alcance' => 'Todos los proveedores y terceros con acceso a información, sistemas o instalaciones de la organización.',
        'definiciones' => [
            'Proveedor Crítico' => 'Proveedor con acceso a información sensible o sistemas críticos',
            'SLA' => 'Service Level Agreement',
            'NDA' => 'Non-Disclosure Agreement',
            'DPA' => 'Data Processing Agreement',
            'Debido Diligencia' => 'Proceso de evaluación de seguridad del proveedor'
        ],
        'procedimiento' => [
            '1. CLASIFICACIÓN DE PROVEEDORES' => [
                'TIER 1 - CRÍTICO:',
                '- Acceso a datos sensibles o PII',
                '- Procesamiento de transacciones financieras',
                '- Acceso a infraestructura crítica',
                '- Servicios sin los cuales el negocio no opera',
                'Evaluación: Muy rigurosa',
                'Frecuencia de auditoría: Anual',
                '',
                'TIER 2 - IMPORTANTE:',
                '- Acceso a información interna',
                '- Servicios importantes pero no críticos',
                '- Impacto moderado si falla',
                'Evaluación: Moderada',
                'Frecuencia de auditoría: Cada 2 años',
                '',
                'TIER 3 - ESTÁNDAR:',
                '- Sin acceso a información sensible',
                '- Servicios no críticos',
                '- Fácilmente reemplazables',
                'Evaluación: Básica',
                'Frecuencia de auditoría: No requerida'
            ],
            '2. PROCESO DE SELECCIÓN' => [
                'REQUISITOS MÍNIMOS:',
                '- Estabilidad financiera',
                '- Referencias verificables',
                '- Experiencia en la industria',
                '- Capacidad técnica',
                '- Certificaciones de seguridad',
                '',
                'EVALUACIÓN DE SEGURIDAD:',
                'Cuestionario de seguridad que incluya:',
                '- Políticas de seguridad',
                '- Certificaciones (ISO 27001, SOC 2, etc.)',
                '- Gestión de accesos',
                '- Cifrado de datos',
                '- Gestión de incidentes',
                '- Continuidad del negocio',
                '- Cumplimiento regulatorio',
                '- Ubicación de datos',
                '- Subcontratación',
                '',
                'DEBIDO DILIGENCIA PARA TIER 1:',
                '1. Cuestionario de seguridad detallado',
                '2. Revisión de certificaciones',
                '3. Reportes de auditoría (SOC 2 Type II)',
                '4. Visita a instalaciones (opcional)',
                '5. Referencias de clientes',
                '6. Revisión financiera',
                '7. Evaluación de riesgos',
                '8. Aprobación de comité de seguridad'
            ],
            '3. CONTRATOS Y ACUERDOS' => [
                'CLÁUSULAS DE SEGURIDAD OBLIGATORIAS:',
                '',
                'CONFIDENCIALIDAD:',
                '- NDA (Non-Disclosure Agreement)',
                '- Clasificación de información',
                '- Uso permitido de datos',
                '- Prohibición de divulgación',
                '',
                'PROTECCIÓN DE DATOS:',
                '- DPA si procesa datos personales',
                '- Cumplimiento GDPR/CCPA',
                '- Ubicación de almacenamiento',
                '- Transferencias internacionales',
                '- Derechos de sujetos de datos',
                '',
                'CONTROLES DE SEGURIDAD:',
                '- Estándares mínimos a cumplir',
                '- Cifrado de datos',
                '- Control de acceso',
                '- Logging y monitoreo',
                '- Gestión de parches',
                '',
                'INCIDENTES:',
                '- Obligación de reportar (24 horas)',
                '- Cooperación en investigaciones',
                '- Notificación a afectados',
                '',
                'AUDITORÍA:',
                '- Derecho a auditar',
                '- Frecuencia de auditorías',
                '- Acceso a reportes de auditoría',
                '',
                'CONTINUIDAD:',
                '- Plan de continuidad del proveedor',
                '- RTO/RPO acordados',
                '- Pruebas de DR',
                '',
                'TERMINACIÓN:',
                '- Retorno/destrucción de datos',
                '- Certificación de eliminación',
                '- Revocación de accesos',
                '- Período de transición',
                '',
                'RESPONSABILIDAD:',
                '- Límites de responsabilidad',
                '- Seguro de ciberseguridad',
                '- Indemnización'
            ],
            '4. ONBOARDING DE PROVEEDORES' => [
                'PROCESO:',
                '1. Firma de contratos y acuerdos',
                '2. Capacitación en políticas de seguridad',
                '3. Provisión de accesos (menor privilegio)',
                '4. Configuración de VPN si aplica',
                '5. Registro en sistema de gestión de proveedores',
                '6. Comunicación a áreas relevantes',
                '7. Asignación de responsable interno',
                '',
                'ACCESOS:',
                '- Solicitud formal de accesos',
                '- Justificación de negocio',
                '- Aprobación de gerente de área',
                '- Aprobación de seguridad',
                '- Cuentas nominales (no genéricas)',
                '- MFA obligatorio',
                '- Acceso VPN para remotos',
                '- Revisión trimestral'
            ],
            '5. GESTIÓN CONTINUA' => [
                'MONITOREO:',
                '- Revisión de SLA mensual',
                '- Monitoreo de accesos',
                '- Alertas de actividades anómalas',
                '- Logs de auditoría',
                '',
                'REVISIONES PERIÓDICAS:',
                'Tier 1 (Anual):',
                '- Actualización de cuestionario de seguridad',
                '- Revisión de certificaciones',
                '- Nuevos reportes de auditoría',
                '- Evaluación de incidentes del año',
                '- Renovación de contratos',
                '',
                'Tier 2 (Bianual):',
                '- Cuestionario simplificado',
                '- Revisión de certificaciones',
                '',
                'Tier 3:',
                '- Revisión al renovar contrato',
                '',
                'GESTIÓN DE CAMBIOS:',
                '- Proveedor debe notificar cambios significativos',
                '- Nuevas subcontrataciones',
                '- Cambios de ubicación de datos',
                '- Fusiones/adquisiciones',
                '- Re-evaluación de seguridad si aplica'
            ],
            '6. AUDITORÍA DE PROVEEDORES' => [
                'TIPOS DE AUDITORÍA:',
                '',
                'REVISIÓN DOCUMENTAL:',
                '- Políticas y procedimientos',
                '- Certificaciones vigentes',
                '- Reportes SOC 2',
                '- Reportes de penetration testing',
                '- Registros de incidentes',
                '',
                'AUDITORÍA ON-SITE (Tier 1):',
                '- Visita a instalaciones',
                '- Entrevistas con personal',
                '- Revisión de controles físicos',
                '- Validación de controles técnicos',
                '- Reporte de hallazgos',
                '- Plan de acción correctivo',
                '',
                'AUDITORÍA REMOTA:',
                '- Cuestionarios detallados',
                '- Video conferencias',
                '- Revisión de evidencias',
                '- Testing remoto',
                '',
                'SEGUIMIENTO:',
                '- Revisión de hallazgos',
                '- Validación de remediación',
                '- Re-evaluación de riesgo'
            ],
            '7. GESTIÓN DE INCIDENTES CON PROVEEDORES' => [
                'REPORTE DE INCIDENTES:',
                '- Proveedor reporta en 24 horas',
                '- Formato estandarizado',
                '- Información requerida:',
                '  * Descripción del incidente',
                '  * Datos afectados',
                '  * Sistemas comprometidos',
                '  * Clientes impactados',
                '  * Acciones tomadas',
                '  * Timeline',
                '',
                'RESPUESTA:',
                '1. Notificación recibida',
                '2. Evaluación de impacto',
                '3. Activación de equipo interno',
                '4. Coordinación con proveedor',
                '5. Comunicación a afectados si necesario',
                '6. Monitoreo de remediación',
                '7. Reporte post-incidente',
                '8. Lecciones aprendidas',
                '',
                'ESCALAMIENTO:',
                '- Incidente menor: Gestión operativa',
                '- Incidente significativo: Notificación a gerencia',
                '- Incidente crítico: Comité de crisis, evaluación de continuidad de relación'
            ],
            '8. OFFBOARDING DE PROVEEDORES' => [
                'PROCESO DE TERMINACIÓN:',
                '1. Notificación formal según contrato',
                '2. Plan de transición',
                '3. Identificación de datos a retornar/eliminar',
                '4. Revocación de accesos',
                '5. Retorno de activos físicos',
                '6. Eliminación segura de datos',
                '7. Certificación de destrucción',
                '8. Auditoría de cierre',
                '9. Lecciones aprendidas',
                '10. Actualización de inventario',
                '',
                'RETORNO DE DATOS:',
                '- Formato acordado',
                '- Verificación de completitud',
                '- Transferencia segura',
                '',
                'ELIMINACIÓN DE DATOS:',
                '- Según NIST SP 800-88',
                '- Certificado de destrucción',
                '- Evidencia de eliminación',
                '- Validación si es posible',
                '',
                'REVOCACIÓN DE ACCESOS:',
                '- Cuentas deshabilitadas',
                '- Certificados revocados',
                '- VPN desconectada',
                '- Llaves físicas devueltas',
                '- Validación de no acceso residual'
            ]
        ],
        'cumplimiento' => 'Todos los proveedores deben ser evaluados y gestionados según este procedimiento. Acuerdos sin cláusulas de seguridad requeridas deben ser aprobados por CISO.',
        'revision' => 'Revisión anual o cuando regulaciones cambien.',
        'control_iso' => 'A.5.19, A.5.20, A.5.21, A.5.22, A.5.23 - Relaciones con proveedores'
    ]
];


// =====================================================
// INVENTARIOS ISO 27001:2022
// =====================================================

$inventarios = [
    'INV-SI-001' => [
        'titulo' => 'INVENTARIO DE ACTIVOS DE INFORMACIÓN',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Mantener un registro actualizado de todos los activos de información de la organización para su adecuada protección y gestión.',
        'alcance' => 'Todos los activos de información en cualquier formato: digital, física, conocimiento.',
        'contenido' => [
            'ACTIVOS DE INFORMACIÓN DIGITAL' => [
                'BASES DE DATOS:',
                '- BD-001: Base de datos de clientes (PostgreSQL)',
                '  * Clasificación: CONFIDENCIAL',
                '  * Propietario: Director Comercial',
                '  * Custodio: DBA Principal',
                '  * Ubicación: Servidor PROD-DB-01',
                '  * Criticidad: ALTA',
                '',
                '- BD-002: Base de datos financiera (Oracle)',
                '  * Clasificación: RESTRINGIDO',
                '  * Propietario: CFO',
                '  * Custodio: Gerente de TI',
                '  * Ubicación: Servidor PROD-DB-02',
                '  * Criticidad: CRÍTICA',
                '',
                '- BD-003: Base de datos de recursos humanos',
                '  * Clasificación: CONFIDENCIAL',
                '  * Propietario: Director de RRHH',
                '  * Custodio: Administrador RRHH',
                '  * Ubicación: Cloud (AWS RDS)',
                '  * Criticidad: ALTA',
                '',
                'ARCHIVOS Y DOCUMENTOS:',
                '- Repositorio de contratos (SharePoint)',
                '- Documentación técnica (Confluence)',
                '- Archivos financieros (servidor de archivos)',
                '- Propiedad intelectual (repositorio Git)',
                '- Registros de auditoría (SIEM)'
            ],
            'ACTIVOS DE APLICACIONES' => [
                'APLICACIONES EMPRESARIALES:',
                '- APP-001: ERP Corporativo',
                '  * Versión: SAP S/4HANA',
                '  * Criticidad: CRÍTICA',
                '  * Datos procesados: Financieros, inventarios, ventas',
                '  * Propietario: CFO',
                '',
                '- APP-002: CRM',
                '  * Versión: Salesforce Enterprise',
                '  * Criticidad: ALTA',
                '  * Datos procesados: Clientes, oportunidades',
                '  * Propietario: Director Comercial',
                '',
                '- APP-003: Portal de clientes',
                '  * Tecnología: React + Node.js',
                '  * Criticidad: ALTA',
                '  * Datos procesados: Información de clientes',
                '  * Propietario: Director Digital',
                '',
                '- APP-004: Sistema de nómina',
                '  * Versión: Interna',
                '  * Criticidad: ALTA',
                '  * Datos procesados: Datos personales empleados',
                '  * Propietario: Director RRHH'
            ],
            'ACTIVOS DE HARDWARE' => [
                'SERVIDORES:',
                '- 15 servidores físicos (on-premise)',
                '- 45 servidores virtuales (VMware)',
                '- 30 instancias cloud (AWS EC2)',
                '',
                'EQUIPOS DE RED:',
                '- 3 firewalls (Palo Alto)',
                '- 8 switches core (Cisco)',
                '- 25 switches de acceso',
                '- 12 access points WiFi',
                '- 2 balanceadores de carga (F5)',
                '',
                'ESTACIONES DE TRABAJO:',
                '- 250 laptops corporativas',
                '- 80 desktops',
                '- 150 dispositivos móviles (smartphones)',
                '- 75 tablets'
            ],
            'ACTIVOS DE SERVICIOS' => [
                'SERVICIOS CLOUD:',
                '- Microsoft 365 (correo, colaboración)',
                '- AWS (infraestructura)',
                '- Salesforce (CRM)',
                '- GitHub (código fuente)',
                '- Jira/Confluence (gestión proyectos)',
                '',
                'SERVICIOS DE COMUNICACIÓN:',
                '- VoIP corporativo',
                '- Video conferencia (Zoom)',
                '- Mensajería corporativa (Slack)',
                '',
                'SERVICIOS DE SEGURIDAD:',
                '- SIEM (Splunk)',
                '- EDR (CrowdStrike)',
                '- WAF (Cloudflare)',
                '- DLP (Symantec)'
            ],
            'ACTIVOS FÍSICOS' => [
                'DOCUMENTACIÓN FÍSICA:',
                '- Contratos físicos archivados',
                '- Documentos legales',
                '- Registros históricos',
                '- Backups en cinta',
                '',
                'UBICACIÓN:',
                '- Archivo central (piso 3)',
                '- Bóveda de seguridad',
                '- Custodia externa (Iron Mountain)'
            ],
            'ACTIVOS DE CONOCIMIENTO' => [
                'PROPIEDAD INTELECTUAL:',
                '- Código fuente propietario',
                '- Algoritmos y modelos',
                '- Diseños y especificaciones',
                '- Marcas registradas',
                '- Patentes',
                '',
                'CONOCIMIENTO INSTITUCIONAL:',
                '- Procedimientos operativos',
                '- Know-how técnico',
                '- Relaciones con clientes',
                '- Estrategias de negocio'
            ],
            'PROCESO DE GESTIÓN' => [
                'REGISTRO DE ACTIVOS:',
                '- Todo activo registrado en CMDB',
                '- ID único asignado',
                '- Propietario y custodio identificados',
                '- Clasificación de seguridad',
                '- Ubicación documentada',
                '',
                'REVISIÓN:',
                '- Trimestral: Activos críticos',
                '- Semestral: Todos los activos',
                '- Actualización continua',
                '',
                'RESPONSABILIDADES:',
                '- Propietario: Clasificar y definir requisitos',
                '- Custodio: Implementar controles',
                '- TI: Mantener inventario actualizado'
            ]
        ],
        'control_iso' => 'A.5.9 - Inventario de activos',
        'actualizacion' => 'Actualización continua, revisión trimestral'
    ],

    'INV-SI-002' => [
        'titulo' => 'INVENTARIO DE SOFTWARE Y LICENCIAS',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Gestionar y controlar el software instalado y las licencias para garantizar cumplimiento legal y seguridad.',
        'alcance' => 'Todo el software utilizado en la organización, licenciado y de código abierto.',
        'contenido' => [
            'SOFTWARE DE SISTEMA' => [
                'SISTEMAS OPERATIVOS:',
                '- Windows Server 2019/2022: 15 licencias',
                '- Windows 10/11 Enterprise: 330 licencias',
                '- Red Hat Enterprise Linux: 25 suscripciones',
                '- Ubuntu Server LTS: Ilimitado (open source)',
                '',
                'VIRTUALIZACIÓN:',
                '- VMware vSphere Enterprise Plus: 8 sockets',
                '- VMware vCenter: 1 instancia',
                '- Docker Enterprise: 50 nodos'
            ],
            'SOFTWARE DE APLICACIONES' => [
                'PRODUCTIVIDAD:',
                '- Microsoft 365 E3: 350 licencias',
                '- Adobe Creative Cloud: 25 licencias',
                '- AutoCAD: 15 licencias',
                '',
                'DESARROLLO:',
                '- Visual Studio Enterprise: 30 licencias',
                '- IntelliJ IDEA Ultimate: 20 licencias',
                '- Postman Enterprise: 15 licencias',
                '',
                'GESTIÓN DE PROYECTOS:',
                '- Jira Software: 200 usuarios',
                '- Confluence: 200 usuarios',
                '- Microsoft Project: 10 licencias'
            ],
            'SOFTWARE EMPRESARIAL' => [
                'ERP Y FINANZAS:',
                '- SAP S/4HANA: Licenciamiento por usuarios nombrados',
                '  * Professional: 50 usuarios',
                '  * Limited Professional: 150 usuarios',
                '',
                'CRM Y VENTAS:',
                '- Salesforce Enterprise: 80 licencias',
                '- Salesforce Service Cloud: 40 licencias',
                '',
                'RECURSOS HUMANOS:',
                '- Workday HCM: 350 empleados',
                '- BambooHR: Módulo de reclutamiento'
            ],
            'SOFTWARE DE SEGURIDAD' => [
                'ENDPOINT PROTECTION:',
                '- CrowdStrike Falcon: 400 endpoints',
                '- Microsoft Defender for Endpoint: 350 endpoints',
                '',
                'SEGURIDAD DE RED:',
                '- Palo Alto Firewall licenses: 3 dispositivos',
                '- Splunk Enterprise Security: 50GB/día',
                '- Tenable Nessus Professional: 3 scanners',
                '',
                'GESTIÓN DE IDENTIDAD:',
                '- Okta Workforce Identity: 350 usuarios',
                '- HashiCorp Vault Enterprise: Ilimitado'
            ],
            'SOFTWARE DE CÓDIGO ABIERTO' => [
                'REGISTRO Y CONTROL:',
                '- PostgreSQL 14',
                '- MySQL 8.0',
                '- MongoDB Community',
                '- Redis',
                '- Nginx',
                '- Apache HTTP Server',
                '- Node.js',
                '- Python',
                '- Git',
                '',
                'GESTIÓN:',
                '- Inventario de componentes OSS',
                '- Análisis de licencias',
                '- Escaneo de vulnerabilidades',
                '- Actualización regular'
            ],
            'GESTIÓN DE LICENCIAS' => [
                'PROCESO:',
                '1. Solicitud de software',
                '2. Aprobación de gerente',
                '3. Verificación de disponibilidad de licencia',
                '4. Provisión y registro',
                '5. Revisión trimestral de uso',
                '6. Recuperación de licencias no utilizadas',
                '',
                'COMPLIANCE:',
                '- Auditorías internas semestrales',
                '- Preparación para auditorías de vendors',
                '- Registro de todas las instalaciones',
                '- Evidencia de licenciamiento',
                '',
                'RENOVACIONES:',
                '- Tracking de fechas de vencimiento',
                '- Proceso de renovación 60 días antes',
                '- Evaluación de necesidad continua',
                '- Negociación de contratos'
            ],
            'SOFTWARE NO AUTORIZADO' => [
                'DETECCIÓN:',
                '- Escaneo automático de endpoints',
                '- Revisión de instalaciones',
                '- Reportes de anomalías',
                '',
                'ACCIÓN:',
                '- Notificación al usuario',
                '- Desinstalación remota',
                '- Reporte a supervisor',
                '- Acción disciplinaria si reincidente'
            ]
        ],
        'control_iso' => 'A.5.9, A.8.19 - Inventario de activos y licenciamiento',
        'actualizacion' => 'Actualización continua, auditoría trimestral'
    ],

    'INV-SI-003' => [
        'titulo' => 'INVENTARIO DE EQUIPOS Y DISPOSITIVOS',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Mantener registro detallado de todos los equipos de hardware para control, mantenimiento y seguridad.',
        'alcance' => 'Todos los dispositivos de hardware de la organización.',
        'contenido' => [
            'SERVIDORES FÍSICOS' => [
                'PRODUCCIÓN:',
                '- SRV-PROD-001: Dell PowerEdge R740',
                '  * Ubicación: Datacenter Principal - Rack A1',
                '  * Función: Servidor de aplicaciones',
                '  * Specs: 2x Intel Xeon Gold, 256GB RAM, 4TB SSD',
                '  * S/N: XXXXXXX',
                '  * Fecha compra: 2022-03-15',
                '  * Garantía hasta: 2025-03-15',
                '',
                '- SRV-PROD-002: HPE ProLiant DL380 Gen10',
                '  * Ubicación: Datacenter Principal - Rack A2',
                '  * Función: Servidor de bases de datos',
                '  * Specs: 2x Intel Xeon Platinum, 512GB RAM, 8TB SSD',
                '  * Criticidad: CRÍTICA',
                '',
                'DESARROLLO:',
                '- SRV-DEV-001 a SRV-DEV-005',
                '  * Ambiente de desarrollo y pruebas',
                '  * Criticidad: MEDIA'
            ],
            'INFRAESTRUCTURA DE RED' => [
                'FIREWALLS:',
                '- FW-001: Palo Alto PA-5220',
                '  * Ubicación: Perímetro principal',
                '  * Throughput: 20 Gbps',
                '  * S/N: XXXXX',
                '  * Suscripciones activas: Threat Prevention, URL Filtering, WildFire',
                '',
                '- FW-002: Palo Alto PA-3220',
                '  * Ubicación: Perímetro secundario',
                '  * Modo: HA Active-Passive',
                '',
                'SWITCHES CORE:',
                '- SW-CORE-01/02: Cisco Catalyst 9500',
                '  * Stack de 2 unidades',
                '  * 48 puertos 10GbE',
                '  * Ubicación: Datacenter',
                '',
                'BALANCEADORES:',
                '- LB-001/002: F5 BIG-IP 4000s',
                '  * Configuración HA',
                '  * SSL/TLS offloading',
                '',
                'WIRELESS:',
                '- 12x Cisco Meraki MR46 Access Points',
                '  * Cobertura de 3 pisos de oficinas',
                '  * WPA3 Enterprise'
            ],
            'ESTACIONES DE TRABAJO' => [
                'LAPTOPS CORPORATIVAS:',
                '- 150x Dell Latitude 7420',
                '  * Intel Core i7, 16GB RAM, 512GB SSD',
                '  * Windows 11 Enterprise',
                '  * Asignación: Personal administrativo',
                '',
                '- 100x Lenovo ThinkPad X1 Carbon',
                '  * Intel Core i5, 16GB RAM, 256GB SSD',
                '  * Asignación: Desarrolladores',
                '',
                'DESKTOPS:',
                '- 80x HP EliteDesk 800 G8',
                '  * Uso: Estaciones fijas en oficina',
                '',
                'WORKSTATIONS:',
                '- 20x Dell Precision 7920',
                '  * Uso: Diseño, CAD, rendering',
                '  * Specs: Xeon, 64GB RAM, NVIDIA Quadro'
            ],
            'DISPOSITIVOS MÓVILES' => [
                'SMARTPHONES CORPORATIVOS:',
                '- 120x iPhone 13/14',
                '  * MDM: Microsoft Intune',
                '  * Políticas: Cifrado, PIN, borrado remoto',
                '',
                '- 30x Samsung Galaxy S22/S23',
                '  * MDM: Microsoft Intune',
                '  * Android Enterprise',
                '',
                'TABLETS:',
                '- 50x iPad Pro',
                '  * Uso: Ventas, presentaciones',
                '- 25x iPad estándar',
                '  * Uso: Inventarios, almacén'
            ],
            'EQUIPOS DE ALMACENAMIENTO' => [
                'SAN/NAS:',
                '- NAS-001: NetApp FAS8300',
                '  * Capacidad: 100TB',
                '  * Uso: Almacenamiento de archivos corporativos',
                '',
                '- SAN-001: Dell EMC Unity 550F',
                '  * Capacidad: 200TB',
                '  * Uso: Almacenamiento de bases de datos',
                '',
                'BACKUP:',
                '- BACKUP-001: HPE StoreOnce 5650',
                '  * Capacidad: 150TB',
                '  * Deduplicación integrada',
                '',
                '- Librería de cintas: Dell EMC ML3',
                '  * 60 slots LTO-8',
                '  * Capacidad total: 720TB (nativo)'
            ],
            'EQUIPOS ESPECIALIZADOS' => [
                'SEGURIDAD FÍSICA:',
                '- 30x Cámaras IP Axis',
                '- NVR: Synology RS3621xs+',
                '- Control de acceso: HID VertX V100',
                '',
                'IMPRESORAS/MULTIFUNCIONALES:',
                '- 40x Impresoras de red HP Enterprise',
                '- 15x Multifuncionales Xerox',
                '',
                'UPS:',
                '- 3x APC Symmetra PX 100kW',
                '  * Autonomía: 15 minutos a carga plena',
                '  * Para datacenter',
                '- 50x APC Smart-UPS 1500VA',
                '  * Para estaciones críticas'
            ],
            'GESTIÓN DE CICLO DE VIDA' => [
                'ADQUISICIÓN:',
                '- Proceso de solicitud y aprobación',
                '- Registro inmediato en inventario',
                '- Etiquetado con código de barras',
                '- Asignación de responsable',
                '',
                'MANTENIMIENTO:',
                '- Mantenimiento preventivo según fabricante',
                '- Registro de incidencias',
                '- Historial de reparaciones',
                '',
                'RETIRO:',
                '- Criterios: Fin de garantía, obsolescencia',
                '- Sanitización de datos',
                '- Certificado de destrucción',
                '- Baja contable y de inventario',
                '- Disposición ecológica'
            ]
        ],
        'control_iso' => 'A.5.9, A.7.8 - Inventario de activos y gestión de equipos',
        'actualizacion' => 'Actualización en tiempo real, auditoría mensual'
    ],

    'INV-SI-004' => [
        'titulo' => 'INVENTARIO DE SERVICIOS DE TI Y CLOUD',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Registrar y gestionar todos los servicios de TI, cloud y SaaS utilizados por la organización.',
        'alcance' => 'Servicios internos, cloud públicos y aplicaciones SaaS.',
        'contenido' => [
            'SERVICIOS CLOUD - IAAS' => [
                'AMAZON WEB SERVICES (AWS):',
                '- Cuenta: Producción Principal',
                '- Region: us-east-1, sa-east-1',
                '- Servicios:',
                '  * EC2: 30 instancias',
                '  * RDS: 5 bases de datos PostgreSQL',
                '  * S3: 15TB de almacenamiento',
                '  * CloudFront: CDN',
                '  * Route53: DNS',
                '  * Lambda: 50 funciones',
                '  * VPC: 3 redes virtuales',
                '- Costo mensual promedio: $15,000 USD',
                '- Responsable: Gerente de Infraestructura Cloud',
                '',
                'MICROSOFT AZURE:',
                '- Cuenta: Desarrollo y QA',
                '- Region: East US',
                '- Servicios:',
                '  * Virtual Machines: 15 instancias',
                '  * Azure SQL Database: 3 instancias',
                '  * Azure DevOps',
                '  * Azure AD',
                '- Costo mensual: $8,000 USD'
            ],
            'SERVICIOS CLOUD - PAAS' => [
                'HEROKU:',
                '- Plan: Enterprise',
                '- Aplicaciones: 10 apps en producción',
                '- Dynos: 25 Professional dynos',
                '- Add-ons: PostgreSQL, Redis',
                '',
                'GOOGLE CLOUD PLATFORM:',
                '- GKE: 2 clusters Kubernetes',
                '- Cloud Storage: 5TB',
                '- BigQuery: Analytics',
                '- Cloud Functions'
            ],
            'SERVICIOS SAAS - PRODUCTIVIDAD' => [
                'MICROSOFT 365:',
                '- Plan: E3',
                '- Licencias: 350',
                '- Servicios:',
                '  * Exchange Online',
                '  * SharePoint Online',
                '  * OneDrive for Business',
                '  * Teams',
                '  * Office Apps',
                '- Costo anual: $84,000 USD',
                '- Contrato hasta: 2025-12-31',
                '',
                'GOOGLE WORKSPACE:',
                '- Plan: Business Standard',
                '- Licencias: 50 (para subsidiaria)',
                '- Gmail, Drive, Meet, Calendar'
            ],
            'SERVICIOS SAAS - CRM Y VENTAS' => [
                'SALESFORCE:',
                '- Edition: Enterprise',
                '- Licencias:',
                '  * Sales Cloud: 60 usuarios',
                '  * Service Cloud: 40 usuarios',
                '- Integraciones:',
                '  * Marketing Cloud',
                '  * Tableau CRM',
                '- Costo anual: $180,000 USD',
                '- Gerente de cuenta: John Smith',
                '- Soporte: Premier Success Plan',
                '',
                'HUBSPOT:',
                '- Plan: Professional',
                '- Uso: Marketing automation',
                '- Contactos: 50,000',
                '- Usuarios: 15'
            ],
            'SERVICIOS SAAS - DESARROLLO' => [
                'GITHUB:',
                '- Plan: Enterprise Cloud',
                '- Organización: companyname',
                '- Usuarios: 80',
                '- Repositorios: 250+',
                '- Features: Advanced Security, Actions',
                '',
                'JIRA SOFTWARE:',
                '- Plan: Premium',
                '- Usuarios: 200',
                '- Proyectos: 45',
                '',
                'CONFLUENCE:',
                '- Plan: Premium',
                '- Usuarios: 200',
                '- Espacios: 30',
                '',
                'JENKINS:',
                '- Self-hosted en AWS',
                '- Pipelines: 100+',
                '- Plugins: 80+'
            ],
            'SERVICIOS SAAS - SEGURIDAD' => [
                'CROWDSTRIKE FALCON:',
                '- Plan: Enterprise',
                '- Endpoints protegidos: 400',
                '- Módulos:',
                '  * Endpoint Protection',
                '  * Threat Intelligence',
                '  * Vulnerability Management',
                '',
                'OKTA:',
                '- Plan: Workforce Identity',
                '- Usuarios: 350',
                '- Integraciones: 45 apps',
                '- MFA: Universal MFA',
                '',
                'CLOUDFLARE:',
                '- Plan: Enterprise',
                '- Dominios protegidos: 8',
                '- Servicios:',
                '  * WAF',
                '  * DDoS Protection',
                '  * CDN',
                '  * DNS',
                '',
                'SPLUNK CLOUD:',
                '- Ingestión: 50 GB/día',
                '- Retención: 90 días',
                '- Apps: Enterprise Security'
            ],
            'SERVICIOS SAAS - COMUNICACIÓN' => [
                'ZOOM:',
                '- Plan: Business Plus',
                '- Licencias: 200 hosts',
                '- Features: Webinars, Phone',
                '',
                'SLACK:',
                '- Plan: Enterprise Grid',
                '- Usuarios: 350',
                '- Workspaces: 3',
                '- Integraciones: 30+',
                '',
                'SENDGRID:',
                '- Plan: Pro 100K',
                '- Emails/mes: 100,000',
                '- Uso: Emails transaccionales'
            ],
            'SERVICIOS INTERNOS' => [
                'ACTIVE DIRECTORY:',
                '- Dominio: company.local',
                '- Domain Controllers: 3',
                '- Usuarios: 350',
                '- Grupos: 150',
                '- GPOs: 45',
                '',
                'DNS INTERNO:',
                '- Servidores: 2 (primario, secundario)',
                '- Zonas: 5',
                '',
                'DHCP:',
                '- Servidores: 2',
                '- Scopes: 10',
                '',
                'SMTP RELAY:',
                '- Exchange Server 2019',
                '- Híbrido con Microsoft 365'
            ],
            'GESTIÓN DE SERVICIOS' => [
                'CATÁLOGO DE SERVICIOS:',
                '- Registro centralizado',
                '- Propietario de servicio identificado',
                '- SLA definido',
                '- Dependencias mapeadas',
                '- Costos asignados',
                '',
                'REVISIÓN:',
                '- Mensual: Uso y costos',
                '- Trimestral: Necesidad continua',
                '- Anual: Renovación de contratos',
                '',
                'OPTIMIZACIÓN:',
                '- Identificación de servicios redundantes',
                '- Consolidación de vendors',
                '- Optimización de costos',
                '- Eliminación de shadow IT'
            ]
        ],
        'control_iso' => 'A.5.9, A.5.23 - Inventario de activos y gestión de servicios cloud',
        'actualizacion' => 'Actualización mensual, revisión trimestral de contratos'
    ],

    'INV-SI-005' => [
        'titulo' => 'INVENTARIO DE USUARIOS Y ACCESOS',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Mantener registro de todos los usuarios y sus niveles de acceso a sistemas y datos.',
        'alcance' => 'Todos los usuarios internos, externos y cuentas de servicio.',
        'contenido' => [
            'USUARIOS INTERNOS' => [
                'EMPLEADOS ACTIVOS: 325',
                '',
                'DISTRIBUCIÓN POR DEPARTAMENTO:',
                '- Tecnología: 80 usuarios',
                '  * Desarrollo: 45',
                '  * Infraestructura: 20',
                '  * Seguridad: 8',
                '  * Soporte: 12',
                '',
                '- Operaciones: 120 usuarios',
                '  * Producción: 60',
                '  * Logística: 35',
                '  * Calidad: 25',
                '',
                '- Administrativo: 75 usuarios',
                '  * Finanzas: 25',
                '  * RRHH: 15',
                '  * Legal: 10',
                '  * Compras: 15',
                '  * Administración: 10',
                '',
                '- Comercial: 50 usuarios',
                '  * Ventas: 35',
                '  * Marketing: 15'
            ],
            'USUARIOS PRIVILEGIADOS' => [
                'ADMINISTRADORES DE SISTEMAS: 12',
                '- admin_jperez: Administrador de dominio',
                '- admin_mgarcia: Administrador de bases de datos',
                '- admin_lrodriguez: Administrador de red',
                '- admin_alopez: Administrador cloud AWS',
                '- admin_cmartinez: Administrador cloud Azure',
                '',
                'CONTROLES:',
                '- Cuentas separadas para uso admin',
                '- MFA obligatorio',
                '- Acceso mediante PAM (Privileged Access Management)',
                '- Sesiones grabadas',
                '- Rotación de contraseñas cada 30 días',
                '- Revisión mensual de actividad',
                '',
                'ADMINISTRADORES DE APLICACIONES: 8',
                '- Administrador SAP: 2 usuarios',
                '- Administrador Salesforce: 2 usuarios',
                '- Administrador Microsoft 365: 2 usuarios',
                '- Administrador GitHub: 2 usuarios'
            ],
            'USUARIOS EXTERNOS' => [
                'CONTRATISTAS: 25',
                '- Desarrollo: 15 contratistas',
                '- Soporte técnico: 5 contratistas',
                '- Consultores: 5 contratistas',
                '',
                'CONTROLES:',
                '- Cuentas diferenciadas (prefijo CTR_)',
                '- Vigencia limitada',
                '- Revisión mensual',
                '- Desactivación automática al vencer contrato',
                '- Acceso mediante VPN',
                '- Sin acceso a datos sensibles sin aprobación especial',
                '',
                'PROVEEDORES CON ACCESO: 12',
                '- Proveedor de soporte hardware',
                '- Proveedor de limpieza de datos',
                '- Consultores de seguridad',
                '- Auditores externos',
                '',
                'CONTROLES:',
                '- Acceso temporal',
                '- Monitoreo de actividad',
                '- Sesiones supervisadas para acceso crítico'
            ],
            'CUENTAS DE SERVICIO' => [
                'APLICACIONES: 45 cuentas',
                '- svc_erp_integration',
                '- svc_crm_sync',
                '- svc_backup',
                '- svc_monitoring',
                '- svc_api_gateway',
                '',
                'CONTROLES:',
                '- No interactivas',
                '- Contraseñas complejas (32 caracteres)',
                '- Gestionadas en Vault',
                '- Rotación automática cada 90 días',
                '- Permisos mínimos necesarios',
                '- Logging de actividad',
                '',
                'AUTOMATIZACIÓN: 20 cuentas',
                '- Tareas programadas',
                '- Scripts de mantenimiento',
                '- Procesos ETL'
            ],
            'GRUPOS Y ROLES' => [
                'GRUPOS DE SEGURIDAD (AD): 150',
                '',
                'GRUPOS POR FUNCIÓN:',
                '- GRP_Desarrolladores (45 miembros)',
                '- GRP_DBA (5 miembros)',
                '- GRP_Seguridad (8 miembros)',
                '- GRP_Finanzas (25 miembros)',
                '- GRP_RRHH (15 miembros)',
                '',
                'GRUPOS POR APLICACIÓN:',
                '- GRP_SAP_Users (150 miembros)',
                '- GRP_SAP_Power (50 miembros)',
                '- GRP_SAP_Admin (5 miembros)',
                '- GRP_Salesforce_Users (80 miembros)',
                '- GRP_Salesforce_Admin (2 miembros)',
                '',
                'ROLES RBAC:',
                '- Role_Admin: Administración completa',
                '- Role_PowerUser: Funciones avanzadas',
                '- Role_User: Usuario estándar',
                '- Role_ReadOnly: Solo lectura'
            ],
            'ACCESOS POR CLASIFICACIÓN' => [
                'ACCESO A DATOS PÚBLICOS: 325 usuarios',
                '- Sin restricciones especiales',
                '',
                'ACCESO A DATOS INTERNOS: 325 usuarios',
                '- Todos los empleados',
                '- Autenticación requerida',
                '',
                'ACCESO A DATOS CONFIDENCIALES: 120 usuarios',
                '- Según necesidad de negocio',
                '- Aprobación de gerente',
                '- Registro de accesos',
                '',
                'ACCESO A DATOS RESTRINGIDOS: 35 usuarios',
                '- Aprobación de gerente y CISO',
                '- MFA obligatorio',
                '- Logging y alertas',
                '- Revisión trimestral',
                '- DLP implementado'
            ],
            'GESTIÓN DE CICLO DE VIDA' => [
                'ALTA DE USUARIOS:',
                '- Solicitud de RRHH',
                '- Formulario de accesos (FO-SI-020)',
                '- Aprobación de gerente',
                '- Provisión por TI',
                '- Capacitación de seguridad',
                '- Plazo: 24 horas desde aprobación',
                '',
                'MODIFICACIONES:',
                '- Cambio de rol/departamento',
                '- Solicitud formal',
                '- Aprobación de nuevo gerente',
                '- Remoción de accesos anteriores',
                '- Adición de nuevos accesos',
                '- Plazo: 24 horas',
                '',
                'BAJA DE USUARIOS:',
                '- Notificación de RRHH',
                '- Desactivación inmediata (0-4 horas)',
                '- Revocación de VPN',
                '- Devolución de equipos',
                '- Transferencia de datos a supervisor',
                '- Conversión a cuenta de archivo (30 días)',
                '- Eliminación completa (6 meses)',
                '',
                'CUENTAS INACTIVAS:',
                '- Detección: Sin login por 60 días',
                '- Notificación a gerente y usuario',
                '- Desactivación automática a los 90 días',
                '- Revisión mensual'
            ],
            'REVISIÓN Y CERTIFICACIÓN' => [
                'REVISIÓN MENSUAL:',
                '- Cuentas privilegiadas',
                '- Usuarios externos',
                '- Accesos a datos restringidos',
                '',
                'CERTIFICACIÓN TRIMESTRAL:',
                '- Por gerentes de área',
                '- Validar que cada usuario necesita sus accesos',
                '- Remoción de accesos innecesarios',
                '- Reporte de cumplimiento',
                '',
                'AUDITORÍA ANUAL:',
                '- Revisión completa de todos los usuarios',
                '- Validación de segregación de funciones',
                '- Cumplimiento de políticas',
                '- Acciones correctivas'
            ]
        ],
        'control_iso' => 'A.5.15, A.5.16, A.5.17, A.5.18 - Gestión de accesos',
        'actualizacion' => 'Actualización en tiempo real, certificación trimestral'
    ],

    'INV-SI-006' => [
        'titulo' => 'INVENTARIO DE PROVEEDORES Y TERCEROS',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Registrar y gestionar todos los proveedores que procesan, almacenan o tienen acceso a información de la organización.',
        'alcance' => 'Proveedores de TI, servicios cloud, consultores y cualquier tercero con acceso a activos de información.',
        'contenido' => [
            'PROVEEDORES CRÍTICOS (TIER 1)' => [
                'AMAZON WEB SERVICES:',
                '- Servicio: Cloud Infrastructure (IaaS/PaaS)',
                '- Criticidad: CRÍTICA',
                '- Datos procesados: Todos los tipos',
                '- Ubicación de datos: US-East-1, SA-East-1',
                '- Certificaciones: ISO 27001, SOC 2 Type II, PCI-DSS',
                '- Contrato vigente hasta: 2026-12-31',
                '- Responsable interno: CTO',
                '- Última auditoría: 2024-06',
                '- Próxima auditoría: 2025-06',
                '',
                'MICROSOFT:',
                '- Servicio: Microsoft 365, Azure AD',
                '- Criticidad: CRÍTICA',
                '- Datos procesados: Email, documentos, datos de usuarios',
                '- Ubicación: Multi-región',
                '- Certificaciones: ISO 27001, SOC 2, GDPR compliant',
                '- Contrato hasta: 2025-12-31',
                '',
                'SALESFORCE:',
                '- Servicio: CRM',
                '- Criticidad: CRÍTICA',
                '- Datos procesados: Información de clientes, ventas',
                '- Ubicación: US Data Centers',
                '- Certificaciones: ISO 27001, SOC 2 Type II',
                '- Contrato hasta: 2025-08-31',
                '',
                'CROWDSTRIKE:',
                '- Servicio: Endpoint Protection (EDR)',
                '- Criticidad: CRÍTICA',
                '- Datos procesados: Telemetría de endpoints, logs',
                '- Certificaciones: ISO 27001, SOC 2',
                '- Contrato hasta: 2025-10-31'
            ],
            'PROVEEDORES IMPORTANTES (TIER 2)' => [
                'GITHUB:',
                '- Servicio: Repositorio de código fuente',
                '- Criticidad: ALTA',
                '- Datos: Código fuente, secretos (en vault)',
                '- Certificaciones: SOC 2',
                '',
                'ATLASSIAN:',
                '- Servicio: Jira, Confluence',
                '- Criticidad: ALTA',
                '- Datos: Proyectos, documentación',
                '',
                'OKTA:',
                '- Servicio: Identity and Access Management',
                '- Criticidad: ALTA',
                '- Datos: Identidades de usuarios',
                '- Certificaciones: ISO 27001, SOC 2',
                '',
                'SPLUNK:',
                '- Servicio: SIEM',
                '- Criticidad: ALTA',
                '- Datos: Logs de seguridad',
                '',
                'CLOUDFLARE:',
                '- Servicio: WAF, CDN, DNS',
                '- Criticidad: ALTA',
                '- Datos: Tráfico web, configuraciones'
            ],
            'PROVEEDORES ESTÁNDAR (TIER 3)' => [
                'ZOOM:',
                '- Servicio: Video conferencia',
                '- Criticidad: MEDIA',
                '- Datos: Metadatos de reuniones',
                '',
                'SLACK:',
                '- Servicio: Mensajería corporativa',
                '- Criticidad: MEDIA',
                '- Datos: Comunicaciones internas',
                '',
                'SENDGRID:',
                '- Servicio: Email transaccional',
                '- Criticidad: MEDIA',
                '- Datos: Emails, direcciones',
                '',
                'ZENDESK:',
                '- Servicio: Soporte al cliente',
                '- Criticidad: MEDIA',
                '- Datos: Tickets de soporte'
            ],
            'PROVEEDORES DE SERVICIOS PROFESIONALES' => [
                'CONSULTORÍA DE SEGURIDAD:',
                '- Empresa: SecureIT Consulting',
                '- Servicio: Auditorías de seguridad, pentesting',
                '- Frecuencia: Anual',
                '- Acceso: Temporal durante engagements',
                '- NDA: Vigente',
                '- Última evaluación: 2024-09',
                '',
                'AUDITORÍA EXTERNA:',
                '- Empresa: Deloitte',
                '- Servicio: Auditoría financiera y de TI',
                '- Frecuencia: Anual',
                '- Acceso: Sistemas financieros, documentación',
                '',
                'DESARROLLO DE SOFTWARE:',
                '- Empresa: DevPartner LLC',
                '- Servicio: Desarrollo de aplicaciones',
                '- Personal: 15 desarrolladores',
                '- Acceso: Repositorios de código, ambientes de desarrollo',
                '- Contrato: Project-based'
            ],
            'PROVEEDORES DE HARDWARE Y MANTENIMIENTO' => [
                'DELL TECHNOLOGIES:',
                '- Servicio: Hardware, soporte técnico',
                '- Equipos: Servidores, storage',
                '- Soporte: ProSupport Plus',
                '- Acceso: Remoto para soporte (supervisado)',
                '',
                'CISCO:',
                '- Servicio: Equipos de red, soporte',
                '- Equipos: Switches, routers',
                '- Soporte: SmartNet',
                '',
                'PALO ALTO NETWORKS:',
                '- Servicio: Firewalls, soporte',
                '- Acceso: Remoto para soporte crítico'
            ],
            'GESTIÓN DE PROVEEDORES' => [
                'PROCESO DE EVALUACIÓN:',
                '- Cuestionario de seguridad',
                '- Revisión de certificaciones',
                '- Análisis de riesgos',
                '- Aprobación de comité',
                '',
                'CONTRATOS Y ACUERDOS:',
                '- NDA (todos los proveedores)',
                '- DPA (si procesan datos personales)',
                '- SLA definidos',
                '- Cláusulas de seguridad',
                '- Derecho a auditar',
                '',
                'MONITOREO CONTINUO:',
                '- Revisión mensual de SLA',
                '- Incidentes de seguridad reportados',
                '- Cambios en certificaciones',
                '- Noticias de brechas de seguridad',
                '',
                'AUDITORÍAS:',
                '- Tier 1: Anual',
                '- Tier 2: Cada 2 años',
                '- Tier 3: Al renovar contrato',
                '',
                'OFFBOARDING:',
                '- Revocación de accesos',
                '- Retorno/destrucción de datos',
                '- Certificación de eliminación',
                '- Lecciones aprendidas'
            ],
            'MÉTRICAS Y REPORTES' => [
                'MÉTRICAS:',
                '- Total de proveedores: 65',
                '  * Tier 1 (Críticos): 8',
                '  * Tier 2 (Importantes): 15',
                '  * Tier 3 (Estándar): 42',
                '',
                '- Cumplimiento de SLA: 97%',
                '- Proveedores con certificación ISO 27001: 12',
                '- Proveedores con SOC 2: 18',
                '- Auditorías completadas este año: 6',
                '- Incidentes de seguridad reportados: 2',
                '',
                'REPORTES:',
                '- Mensual: Cumplimiento de SLA',
                '- Trimestral: Estado de certificaciones',
                '- Anual: Evaluación completa de proveedores',
                '- Ad-hoc: Incidentes de seguridad'
            ]
        ],
        'control_iso' => 'A.5.19, A.5.20, A.5.21 - Seguridad en las relaciones con proveedores',
        'actualizacion' => 'Actualización continua, revisión trimestral'
    ]
];


// =====================================================
// ANÁLISIS DE RIESGOS ISO 27001:2022
// =====================================================

$riesgos = [
    'RISK-SI-001' => [
        'titulo' => 'ANÁLISIS DE RIESGOS DE CIBERSEGURIDAD',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Identificar, evaluar y gestionar riesgos de ciberseguridad que puedan comprometer los activos de información.',
        'alcance' => 'Infraestructura TI, aplicaciones, redes y datos de la organización.',
        'contenido' => [
            'RIESGO 1: RANSOMWARE' => [
                'DESCRIPCIÓN:',
                '- Ataque de ransomware que cifra datos críticos',
                '- Demanda de rescate para recuperar información',
                '- Posible exfiltración de datos antes del cifrado',
                '',
                'AMENAZA:',
                '- Grupos de ransomware organizados',
                '- Ataques dirigidos a la industria',
                '- Probabilidad: ALTA (ataques semanales en el sector)',
                '',
                'VULNERABILIDAD:',
                '- Endpoints sin EDR actualizado',
                '- Usuarios susceptibles a phishing',
                '- Backups no verificados regularmente',
                '- Segmentación de red insuficiente',
                '',
                'IMPACTO:',
                '- Financiero: $500,000 - $2,000,000',
                '  * Pérdida de productividad',
                '  * Costo de recuperación',
                '  * Posible pago de rescate',
                '  * Multas regulatorias',
                '- Operacional: Interrupción de 3-14 días',
                '- Reputacional: ALTO - Pérdida de confianza de clientes',
                '- Legal: Posibles demandas, investigaciones regulatorias',
                '',
                'EVALUACIÓN DE RIESGO:',
                '- Probabilidad: ALTA (4/5)',
                '- Impacto: CRÍTICO (5/5)',
                '- Riesgo Inherente: CRÍTICO (20/25)',
                '',
                'CONTROLES EXISTENTES:',
                '- Antivirus/EDR en todos los endpoints',
                '- Backups diarios con retención 30 días',
                '- Capacitación anual de seguridad',
                '- Email filtering',
                '- MFA para accesos remotos',
                '',
                'RIESGO RESIDUAL:',
                '- Con controles actuales: ALTO (12/25)',
                '- Razón: Backups no probados regularmente, segmentación limitada',
                '',
                'TRATAMIENTO:',
                '- MITIGAR mediante controles adicionales:',
                '  1. Implementar EDR avanzado con detección de ransomware',
                '  2. Pruebas mensuales de restauración de backups',
                '  3. Segmentación de red (VLAN)',
                '  4. Simulaciones trimestrales de phishing',
                '  5. Backups inmutables (air-gapped)',
                '- Responsable: CISO',
                '- Plazo: 6 meses',
                '- Presupuesto: $150,000',
                '',
                'RIESGO OBJETIVO:',
                '- Con controles adicionales: MEDIO (6/25)',
                '- Aceptable según apetito de riesgo'
            ],
            'RIESGO 2: ACCESO NO AUTORIZADO' => [
                'DESCRIPCIÓN:',
                '- Acceso no autorizado a sistemas críticos',
                '- Compromiso de cuentas privilegiadas',
                '- Movimiento lateral en la red',
                '',
                'AMENAZA:',
                '- Atacantes externos',
                '- Insiders maliciosos',
                '- Credenciales comprometidas',
                '- Probabilidad: MEDIA',
                '',
                'VULNERABILIDAD:',
                '- Contraseñas débiles en algunos sistemas legacy',
                '- MFA no implementado en todos los sistemas',
                '- Logs de acceso no monitoreados en tiempo real',
                '',
                'IMPACTO:',
                '- Confidencialidad: Robo de datos sensibles',
                '- Integridad: Modificación de registros financieros',
                '- Disponibilidad: Sabotaje de sistemas',
                '- Impacto: ALTO (4/5)',
                '',
                'EVALUACIÓN:',
                '- Probabilidad: MEDIA (3/5)',
                '- Impacto: ALTO (4/5)',
                '- Riesgo Inherente: ALTO (12/25)',
                '',
                'TRATAMIENTO:',
                '- MITIGAR:',
                '  1. MFA universal para todos los sistemas',
                '  2. PAM para cuentas privilegiadas',
                '  3. SIEM con alertas en tiempo real',
                '  4. Zero Trust Network Access',
                '- Plazo: 4 meses',
                '- Riesgo Residual Objetivo: BAJO (4/25)'
            ],
            'RIESGO 3: FUGA DE DATOS (DATA BREACH)' => [
                'DESCRIPCIÓN:',
                '- Exfiltración de información confidencial',
                '- Exposición de datos personales (PII)',
                '- Violación de privacidad de clientes',
                '',
                'AMENAZA:',
                '- Ataques APT',
                '- Insiders maliciosos',
                '- Error humano',
                '- Configuración incorrecta de cloud',
                '- Probabilidad: MEDIA-ALTA',
                '',
                'VULNERABILIDAD:',
                '- DLP no implementado completamente',
                '- Cifrado no aplicado a todos los datos sensibles',
                '- Clasificación de datos incompleta',
                '- USB no bloqueados',
                '',
                'IMPACTO:',
                '- Financiero: $1,000,000 - $5,000,000',
                '  * Multas GDPR (hasta 4% de ingresos)',
                '  * Costos de notificación',
                '  * Investigación forense',
                '  * Litigación',
                '- Reputacional: CRÍTICO',
                '- Legal: Investigaciones regulatorias',
                '- Impacto: CRÍTICO (5/5)',
                '',
                'EVALUACIÓN:',
                '- Probabilidad: MEDIA-ALTA (4/5)',
                '- Impacto: CRÍTICO (5/5)',
                '- Riesgo Inherente: CRÍTICO (20/25)',
                '',
                'TRATAMIENTO:',
                '- MITIGAR:',
                '  1. Implementar DLP en endpoints y red',
                '  2. Cifrado de datos en reposo y tránsito',
                '  3. Clasificación completa de información',
                '  4. CASB para cloud apps',
                '  5. Bloqueo de USB no autorizados',
                '  6. Monitoreo de exfiltración de datos',
                '- Plazo: 8 meses',
                '- Presupuesto: $200,000',
                '- Riesgo Residual Objetivo: MEDIO (8/25)'
            ],
            'RIESGO 4: DENEGACIÓN DE SERVICIO (DDoS)' => [
                'DESCRIPCIÓN:',
                '- Ataque DDoS contra servicios públicos',
                '- Saturación de ancho de banda',
                '- Indisponibilidad de aplicaciones críticas',
                '',
                'AMENAZA:',
                '- Competidores',
                '- Hacktivistas',
                '- Extorsionadores',
                '- Probabilidad: MEDIA',
                '',
                'VULNERABILIDAD:',
                '- Protección DDoS básica',
                '- Sin plan de respuesta a DDoS',
                '- Monitoreo de tráfico limitado',
                '',
                'IMPACTO:',
                '- Financiero: $50,000 - $200,000 por día',
                '- Operacional: Servicios no disponibles',
                '- Reputacional: Percepción de debilidad',
                '- Impacto: ALTO (4/5)',
                '',
                'EVALUACIÓN:',
                '- Probabilidad: MEDIA (3/5)',
                '- Impacto: ALTO (4/5)',
                '- Riesgo Inherente: ALTO (12/25)',
                '',
                'TRATAMIENTO:',
                '- MITIGAR:',
                '  1. Cloudflare Enterprise con DDoS protection',
                '  2. CDN para distribuir carga',
                '  3. Plan de respuesta a DDoS',
                '  4. Monitoreo de tráfico 24/7',
                '- Plazo: 3 meses',
                '- Presupuesto: $80,000/año',
                '- Riesgo Residual: BAJO (4/25)'
            ],
            'RIESGO 5: VULNERABILIDADES SIN PARCHEAR' => [
                'DESCRIPCIÓN:',
                '- Explotación de vulnerabilidades conocidas',
                '- Sistemas sin parches de seguridad',
                '- Zero-days sin mitigación',
                '',
                'AMENAZA:',
                '- Exploit kits automatizados',
                '- Ataques dirigidos',
                '- Probabilidad: ALTA',
                '',
                'VULNERABILIDAD:',
                '- Proceso de parchado inconsistente',
                '- Sistemas legacy sin soporte',
                '- Ventanas de mantenimiento limitadas',
                '',
                'IMPACTO:',
                '- Compromiso de sistemas',
                '- Escalación de privilegios',
                '- Movimiento lateral',
                '- Impacto: ALTO (4/5)',
                '',
                'EVALUACIÓN:',
                '- Probabilidad: ALTA (4/5)',
                '- Impacto: ALTO (4/5)',
                '- Riesgo Inherente: CRÍTICO (16/25)',
                '',
                'TRATAMIENTO:',
                '- MITIGAR:',
                '  1. Automatización de parchado',
                '  2. Proceso formal de gestión de vulnerabilidades',
                '  3. Escaneo semanal de vulnerabilidades',
                '  4. Reemplazo de sistemas legacy',
                '  5. Virtual patching con IPS/WAF',
                '- Plazo: 6 meses',
                '- Riesgo Residual: MEDIO (6/25)'
            ],
            'RIESGO 6: INGENIERÍA SOCIAL Y PHISHING' => [
                'DESCRIPCIÓN:',
                '- Empleados engañados para revelar credenciales',
                '- Instalación de malware vía phishing',
                '- Transferencias fraudulentas',
                '',
                'AMENAZA:',
                '- Campañas de phishing masivas',
                '- Spear phishing dirigido',
                '- CEO fraud / BEC',
                '- Probabilidad: ALTA',
                '',
                'VULNERABILIDAD:',
                '- Usuarios no suficientemente capacitados',
                '- Falta de simulaciones regulares',
                '- Controles técnicos insuficientes',
                '',
                'IMPACTO:',
                '- Financiero: $100,000 - $1,000,000',
                '- Compromiso de credenciales',
                '- Fraude de transferencias',
                '- Impacto: ALTO (4/5)',
                '',
                'EVALUACIÓN:',
                '- Probabilidad: ALTA (4/5)',
                '- Impacto: ALTO (4/5)',
                '- Riesgo Inherente: CRÍTICO (16/25)',
                '',
                'TRATAMIENTO:',
                '- MITIGAR:',
                '  1. Simulaciones mensuales de phishing',
                '  2. Capacitación continua',
                '  3. Email filtering avanzado',
                '  4. DMARC/SPF/DKIM',
                '  5. Verificación de transferencias (dual approval)',
                '- Plazo: Continuo',
                '- Presupuesto: $50,000/año',
                '- Riesgo Residual: MEDIO (8/25)'
            ]
        ],
        'metodologia' => 'ISO 27005 - Gestión de Riesgos de Seguridad de la Información',
        'revision' => 'Trimestral o cuando cambios significativos ocurran',
        'control_iso' => 'A.5.7 - Inteligencia de amenazas, A.8.8 - Gestión de vulnerabilidades'
    ],

    'RISK-SI-002' => [
        'titulo' => 'ANÁLISIS DE RIESGOS DE CUMPLIMIENTO Y REGULATORIOS',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Evaluar riesgos relacionados con incumplimiento de regulaciones y leyes de protección de datos.',
        'alcance' => 'Cumplimiento de GDPR, leyes locales de protección de datos y regulaciones de la industria.',
        'contenido' => [
            'RIESGO: INCUMPLIMIENTO GDPR' => [
                'Descripción: Violación de principios GDPR en tratamiento de datos personales',
                'Probabilidad: MEDIA (3/5)',
                'Impacto: CRÍTICO (5/5) - Multas hasta 4% de ingresos anuales',
                'Riesgo Inherente: ALTO (15/25)',
                'Controles: Oficial de protección de datos, evaluaciones de impacto de privacidad, registro de actividades de tratamiento',
                'Riesgo Residual: MEDIO (8/25)',
                'Tratamiento: Implementar Privacy by Design, auditorías trimestrales de cumplimiento'
            ],
            'RIESGO: RETENCIÓN INADECUADA DE DATOS' => [
                'Descripción: Retención de datos más allá del período legal o necesario',
                'Probabilidad: MEDIA-ALTA (4/5)',
                'Impacto: ALTO (4/5)',
                'Controles: Política de retención, procesos de eliminación automática',
                'Tratamiento: Implementar sistema de gestión de ciclo de vida de datos'
            ],
            'RIESGO: FALTA DE CONSENTIMIENTO' => [
                'Descripción: Procesamiento de datos sin consentimiento adecuado',
                'Probabilidad: MEDIA (3/5)',
                'Impacto: ALTO (4/5)',
                'Controles: Formularios de consentimiento, gestión de preferencias',
                'Tratamiento: Sistema de gestión de consentimientos (CMP)'
            ]
        ],
        'control_iso' => 'A.5.31, A.5.32, A.5.33, A.5.34 - Aspectos legales y regulatorios',
        'revision' => 'Semestral o cuando cambien regulaciones'
    ],

    'RISK-SI-003' => [
        'titulo' => 'ANÁLISIS DE RIESGOS DE RECURSOS HUMANOS',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Evaluar riesgos relacionados con personal, desde contratación hasta término de relación laboral.',
        'alcance' => 'Todos los empleados, contratistas y personal temporal.',
        'contenido' => [
            'RIESGO: INSIDER THREAT (AMENAZA INTERNA)' => [
                'Descripción: Empleado malicioso o negligente que compromete seguridad',
                'Tipos: Robo de datos, sabotaje, fraude, espionaje corporativo',
                'Probabilidad: MEDIA (3/5)',
                'Impacto: CRÍTICO (5/5)',
                'Riesgo Inherente: ALTO (15/25)',
                'Controles: Background checks, segregación de funciones, monitoreo de actividad, DLP',
                'Riesgo Residual: MEDIO (9/25)',
                'Tratamiento: UEBA (User and Entity Behavior Analytics), revisión trimestral de accesos'
            ],
            'RIESGO: FALTA DE CAPACITACIÓN' => [
                'Descripción: Personal sin conocimientos adecuados de seguridad',
                'Probabilidad: ALTA (4/5)',
                'Impacto: ALTO (4/5)',
                'Controles: Programa de capacitación anual, simulaciones de phishing',
                'Tratamiento: Capacitación continua, programa de concienciación mensual'
            ],
            'RIESGO: SALIDA DE PERSONAL CLAVE' => [
                'Descripción: Pérdida de conocimiento al término de relación laboral',
                'Probabilidad: MEDIA (3/5)',
                'Impacto: ALTO (4/5)',
                'Controles: Documentación de procesos, transferencia de conocimiento',
                'Tratamiento: Gestión de conocimiento, planes de sucesión'
            ],
            'RIESGO: ACCESOS NO REVOCADOS' => [
                'Descripción: Empleados desvinculados mantienen acceso a sistemas',
                'Probabilidad: MEDIA (3/5)',
                'Impacto: CRÍTICO (5/5)',
                'Controles: Proceso de offboarding, revisión mensual de cuentas',
                'Tratamiento: Automatización de revocación, integración HR-TI'
            ]
        ],
        'control_iso' => 'A.6.1, A.6.2, A.6.3, A.6.4 - Seguridad de recursos humanos',
        'revision' => 'Anual o cuando cambios organizacionales ocurran'
    ],

    'RISK-SI-004' => [
        'titulo' => 'ANÁLISIS DE RIESGOS DE INFRAESTRUCTURA Y OPERACIONES',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Evaluar riesgos operacionales y de infraestructura que puedan afectar disponibilidad y continuidad.',
        'alcance' => 'Centros de datos, redes, sistemas de energía y servicios críticos.',
        'contenido' => [
            'RIESGO: FALLA DE DATACENTER' => [
                'Descripción: Interrupción completa del datacenter principal',
                'Causas: Incendio, inundación, falla eléctrica, terremoto',
                'Probabilidad: BAJA (2/5)',
                'Impacto: CRÍTICO (5/5)',
                'Riesgo Inherente: MEDIO (10/25)',
                'Controles: Sistemas de supresión de incendios, UPS, generadores, diseño antisísmico',
                'Riesgo Residual: BAJO (5/25)',
                'Tratamiento: Sitio DR en ubicación geográfica diferente, replicación de datos'
            ],
            'RIESGO: FALLA DE CONECTIVIDAD' => [
                'Descripción: Pérdida de conectividad a internet o WAN',
                'Probabilidad: MEDIA (3/5)',
                'Impacto: ALTO (4/5)',
                'Controles: Enlaces redundantes, múltiples ISPs, SD-WAN',
                'Tratamiento: Failover automático, monitoreo 24/7'
            ],
            'RIESGO: OBSOLESCENCIA TECNOLÓGICA' => [
                'Descripción: Sistemas legacy sin soporte, hardware EOL',
                'Probabilidad: ALTA (4/5)',
                'Impacto: ALTO (4/5)',
                'Controles: Inventario de activos, plan de renovación tecnológica',
                'Tratamiento: Modernización de infraestructura, migración a cloud'
            ],
            'RIESGO: FALLA EN BACKUPS' => [
                'Descripción: Backups corruptos o no recuperables',
                'Probabilidad: MEDIA (3/5)',
                'Impacto: CRÍTICO (5/5)',
                'Controles: Backups automáticos, verificación de integridad',
                'Tratamiento: Pruebas mensuales de restauración, regla 3-2-1'
            ]
        ],
        'control_iso' => 'A.7.1 a A.7.14, A.8.1 a A.8.34 - Seguridad física y operacional',
        'revision' => 'Trimestral'
    ],

    'RISK-SI-005' => [
        'titulo' => 'ANÁLISIS DE RIESGOS DE TERCEROS Y CADENA DE SUMINISTRO',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Evaluar riesgos asociados con proveedores, contratistas y socios de negocio.',
        'alcance' => 'Proveedores de TI, cloud, servicios gestionados y terceros con acceso a información.',
        'contenido' => [
            'RIESGO: BRECHA DE SEGURIDAD EN PROVEEDOR' => [
                'Descripción: Compromiso de seguridad en proveedor afecta nuestra organización',
                'Ejemplos: SolarWinds, Kaseya, MOVEit',
                'Probabilidad: MEDIA-ALTA (4/5)',
                'Impacto: CRÍTICO (5/5)',
                'Riesgo Inherente: CRÍTICO (20/25)',
                'Controles: Due diligence, cláusulas de seguridad en contratos, auditorías de proveedores',
                'Riesgo Residual: ALTO (12/25)',
                'Tratamiento: Monitoreo continuo, revisión de SOC 2 reports, plan de contingencia'
            ],
            'RIESGO: DEPENDENCIA DE PROVEEDOR ÚNICO' => [
                'Descripción: Vendor lock-in, falta de alternativas',
                'Probabilidad: ALTA (4/5)',
                'Impacto: ALTO (4/5)',
                'Controles: Identificación de proveedores alternativos, datos portables',
                'Tratamiento: Multi-cloud strategy, arquitectura desacoplada'
            ],
            'RIESGO: INCUMPLIMIENTO DE SLA' => [
                'Descripción: Proveedor no cumple con niveles de servicio acordados',
                'Probabilidad: MEDIA (3/5)',
                'Impacto: MEDIO (3/5)',
                'Controles: SLAs definidos, monitoreo de cumplimiento, penalizaciones',
                'Tratamiento: Revisión mensual de métricas, reuniones de servicio'
            ],
            'RIESGO: FUGA DE DATOS VÍA TERCEROS' => [
                'Descripción: Datos compartidos con terceros son comprometidos',
                'Probabilidad: MEDIA (3/5)',
                'Impacto: CRÍTICO (5/5)',
                'Controles: Cifrado, minimización de datos compartidos, NDA',
                'Tratamiento: Auditorías de proveedores, DPA estrictos'
            ]
        ],
        'control_iso' => 'A.5.19 a A.5.23 - Seguridad en relaciones con proveedores',
        'revision' => 'Trimestral para proveedores críticos'
    ],

    'RISK-SI-006' => [
        'titulo' => 'ANÁLISIS DE RIESGOS DE CLOUD Y NUEVAS TECNOLOGÍAS',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Evaluar riesgos específicos de servicios cloud, DevOps, contenedores y tecnologías emergentes.',
        'alcance' => 'Servicios cloud (AWS, Azure), contenedores, Kubernetes, APIs, microservicios.',
        'contenido' => [
            'RIESGO: CONFIGURACIÓN INCORRECTA DE CLOUD' => [
                'Descripción: S3 buckets públicos, security groups mal configurados',
                'Probabilidad: ALTA (4/5)',
                'Impacto: CRÍTICO (5/5)',
                'Riesgo Inherente: CRÍTICO (20/25)',
                'Controles: Cloud Security Posture Management (CSPM), IaC scanning',
                'Riesgo Residual: MEDIO (8/25)',
                'Tratamiento: Automatización de compliance, políticas como código'
            ],
            'RIESGO: VULNERABILIDADES EN CONTENEDORES' => [
                'Descripción: Imágenes de contenedores con vulnerabilidades conocidas',
                'Probabilidad: ALTA (4/5)',
                'Impacto: ALTO (4/5)',
                'Controles: Scanning de imágenes, registro privado, imágenes firmadas',
                'Tratamiento: Pipeline de seguridad integrado, políticas de admisión'
            ],
            'RIESGO: APIS INSEGURAS' => [
                'Descripción: APIs sin autenticación, rate limiting o validación',
                'Probabilidad: ALTA (4/5)',
                'Impacto: ALTO (4/5)',
                'Controles: API Gateway, OAuth 2.0, rate limiting',
                'Tratamiento: Testing de seguridad de APIs, OWASP API Top 10'
            ],
            'RIESGO: SHADOW IT / CLOUD' => [
                'Descripción: Uso no autorizado de servicios cloud',
                'Probabilidad: ALTA (4/5)',
                'Impacto: MEDIO (3/5)',
                'Controles: CASB, inventario de servicios cloud',
                'Tratamiento: Proceso de aprobación de servicios, descubrimiento automático'
            ],
            'RIESGO: SECRETOS EXPUESTOS EN CÓDIGO' => [
                'Descripción: Credenciales hardcodeadas en repositorios Git',
                'Probabilidad: MEDIA-ALTA (4/5)',
                'Impacto: CRÍTICO (5/5)',
                'Controles: Git-secrets, pre-commit hooks, secret scanning',
                'Tratamiento: Secrets management (Vault), rotación automática'
            ]
        ],
        'control_iso' => 'A.8.9, A.8.23, A.8.24 - Seguridad en desarrollo y cloud',
        'revision' => 'Trimestral o con adopción de nuevas tecnologías'
    ]
];


// ===================================================== 
// CONTROLES DE SEGURIDAD ISO 27001:2022
// =====================================================

$controles = [
    'CTRL-SI-001' => [
        'titulo' => 'CONTROLES DE ACCESO LÓGICO Y AUTENTICACIÓN',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Documentar implementación de controles de acceso y autenticación según ISO 27001:2022.',
        'alcance' => 'Todos los sistemas de información y aplicaciones.',
        'controles_implementados' => [
            'A.5.15 - Control de acceso' => 'IMPLEMENTADO - Política de control de acceso, autenticación multifactor',
            'A.5.16 - Gestión de identidades' => 'IMPLEMENTADO - Active Directory, Okta IAM',
            'A.5.17 - Información de autenticación' => 'IMPLEMENTADO - Política de contraseñas, gestión de secretos',
            'A.5.18 - Derechos de acceso' => 'IMPLEMENTADO - Proceso formal de solicitud, revisión trimestral'
        ],
        'evidencias' => ['Logs de acceso', 'Reportes de revisión', 'Configuraciones de sistemas'],
        'control_iso' => 'A.5.15 a A.5.18'
    ],
    
    'CTRL-SI-002' => [
        'titulo' => 'CONTROLES CRIPTOGRÁFICOS',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Documentar implementación de controles criptográficos.',
        'alcance' => 'Cifrado de datos en reposo y tránsito.',
        'controles_implementados' => [
            'A.8.24 - Uso de criptografía' => 'IMPLEMENTADO - TLS 1.2+, AES-256, gestión de claves en KMS',
            'Cifrado en tránsito' => 'HTTPS obligatorio, VPN con IPSec',
            'Cifrado en reposo' => 'Bases de datos cifradas, cifrado de disco completo en laptops',
            'Gestión de claves' => 'AWS KMS, rotación anual de claves'
        ],
        'control_iso' => 'A.8.24'
    ],
    
    'CTRL-SI-003' => [
        'titulo' => 'CONTROLES DE SEGURIDAD FÍSICA',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Documentar controles de seguridad física y ambiental.',
        'alcance' => 'Instalaciones, datacenter y áreas restringidas.',
        'controles_implementados' => [
            'A.7.1 - Perímetros de seguridad física' => 'Cercas, guardias de seguridad',
            'A.7.2 - Controles de entrada física' => 'Torniquetes, badges, CCTV',
            'A.7.3 - Seguridad de oficinas' => 'Áreas clasificadas, acceso controlado',
            'A.7.4 - Monitoreo de seguridad física' => 'CCTV 24/7, 90 días de retención',
            'A.7.10 - Protección contra amenazas ambientales' => 'Sistemas de supresión de incendios, UPS, control de temperatura'
        ],
        'control_iso' => 'A.7.1 a A.7.14'
    ],
    
    'CTRL-SI-004' => [
        'titulo' => 'CONTROLES DE SEGURIDAD EN DESARROLLO',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Documentar controles de desarrollo seguro de software.',
        'alcance' => 'Ciclo de vida de desarrollo de software.',
        'controles_implementados' => [
            'A.8.25 - Ciclo de vida de desarrollo seguro' => 'SDLC con seguridad integrada, threat modeling',
            'A.8.26 - Requisitos de seguridad' => 'Requisitos definidos en fase de diseño',
            'A.8.27 - Arquitectura y principios de diseño seguro' => 'Revisión de arquitectura, principios OWASP',
            'A.8.28 - Codificación segura' => 'Estándares de codificación, code review',
            'A.8.29 - Pruebas de seguridad' => 'SAST, DAST, penetration testing',
            'A.8.30 - Desarrollo externalizado' => 'Cláusulas de seguridad en contratos'
        ],
        'control_iso' => 'A.8.25 a A.8.30'
    ],
    
    'CTRL-SI-005' => [
        'titulo' => 'CONTROLES DE CONTINUIDAD Y RESPALDO',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Documentar controles de continuidad del negocio y respaldo.',
        'alcance' => 'Sistemas críticos y procesos de negocio.',
        'controles_implementados' => [
            'A.5.29 - Seguridad de la información durante disrupciones' => 'BCP documentado y probado',
            'A.5.30 - Preparación de TIC para continuidad' => 'DR site, replicación de datos',
            'A.8.13 - Respaldo de información' => 'Backups diarios, retención 30 días, pruebas mensuales',
            'A.8.14 - Redundancia de instalaciones' => 'Equipos redundantes, enlaces redundantes'
        ],
        'control_iso' => 'A.5.29, A.5.30, A.8.13, A.8.14'
    ]
];

// =====================================================
// REGISTRO DE INCIDENTES DE SEGURIDAD
// =====================================================

$incidentes = [
    'INC-SI-001' => [
        'titulo' => 'REGISTRO DE INCIDENTES DE SEGURIDAD - Q1 2024',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'periodo' => 'Enero - Marzo 2024',
        'resumen' => [
            'Total de incidentes: 12',
            'Críticos: 1',
            'Altos: 2',
            'Medios: 5',
            'Bajos: 4'
        ],
        'incidentes_destacados' => [
            'INC-2024-003 [CRÍTICO]' => [
                'Fecha: 15/01/2024',
                'Tipo: Intento de ransomware',
                'Descripción: Malware detectado en endpoint vía email phishing',
                'Impacto: Bajo - Contenido por EDR antes de cifrar archivos',
                'Respuesta: Aislamiento de host, análisis forense, reimagen',
                'Lecciones: Capacitación adicional al usuario afectado',
                'Estado: CERRADO'
            ],
            'INC-2024-007 [ALTO]' => [
                'Fecha: 28/02/2024',
                'Tipo: Acceso no autorizado',
                'Descripción: Intento de acceso con credenciales comprometidas',
                'Impacto: Bajo - Bloqueado por MFA',
                'Respuesta: Reset de contraseña, análisis de logs',
                'Lecciones: Usuario reutilizaba contraseña de sitio externo comprometido',
                'Estado: CERRADO'
            ]
        ],
        'metricas' => [
            'MTTR (Mean Time to Respond): 45 minutos',
            'MTTR (Mean Time to Resolve): 4.2 horas',
            'Incidentes prevenidos por controles: 23',
            'False positives: 8'
        ],
        'control_iso' => 'A.5.24, A.5.25, A.5.26'
    ],
    
    'INC-SI-002' => [
        'titulo' => 'REGISTRO DE INCIDENTES DE SEGURIDAD - Q2 2024',
        'periodo' => 'Abril - Junio 2024',
        'resumen' => ['Total: 15', 'Críticos: 0', 'Altos: 3', 'Medios: 7', 'Bajos: 5'],
        'tendencia' => 'Aumento de phishing, mejora en tiempo de respuesta',
        'control_iso' => 'A.5.24, A.5.25, A.5.26'
    ],
    
    'INC-SI-003' => [
        'titulo' => 'REGISTRO DE INCIDENTES DE SEGURIDAD - Q3 2024',
        'periodo' => 'Julio - Septiembre 2024',
        'resumen' => ['Total: 10', 'Críticos: 1', 'Altos: 2', 'Medios: 4', 'Bajos: 3'],
        'mejoras_implementadas' => ['EDR actualizado', 'Simulaciones de phishing mensuales'],
        'control_iso' => 'A.5.24, A.5.25, A.5.26'
    ],
    
    'INC-SI-004' => [
        'titulo' => 'REGISTRO DE INCIDENTES DE SEGURIDAD - Q4 2024',
        'periodo' => 'Octubre - Diciembre 2024',
        'resumen' => ['Total: 8', 'Críticos: 0', 'Altos: 1', 'Medios: 3', 'Bajos: 4'],
        'tendencia' => 'Reducción significativa de incidentes - 33% vs Q1',
        'control_iso' => 'A.5.24, A.5.25, A.5.26'
    ],
    
    'INC-SI-005' => [
        'titulo' => 'ANÁLISIS ANUAL DE INCIDENTES 2024',
        'periodo' => 'Año completo 2024',
        'resumen' => [
            'Total anual: 45 incidentes',
            'Críticos: 2 (4.4%)',
            'Altos: 8 (17.8%)',
            'Medios: 19 (42.2%)',
            'Bajos: 16 (35.6%)'
        ],
        'categorias' => [
            'Phishing/Social Engineering: 18 (40%)',
            'Malware: 12 (26.7%)',
            'Acceso no autorizado: 8 (17.8%)',
            'Pérdida/robo de dispositivos: 4 (8.9%)',
            'Otros: 3 (6.6%)'
        ],
        'costo_total_estimado' => '$125,000 USD',
        'mejoras_año_siguiente' => ['SOAR para automatización', 'Threat intelligence', 'Security awareness mejorado'],
        'control_iso' => 'A.5.24, A.5.25, A.5.26'
    ]
];

// =====================================================
// AUDITORÍAS DE SEGURIDAD
// =====================================================

$auditorias = [
    'AUD-SI-001' => [
        'titulo' => 'AUDITORÍA INTERNA DE SGSI - 2024',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'tipo' => 'Auditoría Interna Anual',
        'alcance' => 'Todo el Sistema de Gestión de Seguridad de la Información',
        'auditores' => 'Equipo interno de auditoría (independiente de TI)',
        'resultados' => [
            'Conformidades: 82/90 controles (91%)',
            'No conformidades mayores: 2',
            'No conformidades menores: 6',
            'Oportunidades de mejora: 15'
        ],
        'no_conformidades_mayores' => [
            'NCM-01: Pruebas de restauración de backups no realizadas mensualmente',
            'NCM-02: Revisión de accesos privilegiados sin evidencia de últimos 2 trimestres'
        ],
        'plan_accion' => 'Plazo de corrección: 30 días, seguimiento en 60 días',
        'control_iso' => 'A.5.36, A.5.37 - Auditoría interna'
    ],
    
    'AUD-SI-002' => [
        'titulo' => 'AUDITORÍA EXTERNA ISO 27001 - CERTIFICACIÓN',
        'tipo' => 'Auditoría de Certificación',
        'organismo' => 'BSI (British Standards Institution)',
        'resultado' => 'CERTIFICACIÓN OTORGADA',
        'vigencia' => '2024-2027 (3 años)',
        'hallazgos' => [
            'No conformidades: 0',
            'Observaciones: 3',
            'Fortalezas identificadas: 8'
        ],
        'observaciones' => [
            'Documentar mejor el proceso de revisión de políticas',
            'Mejorar métricas de concienciación de seguridad',
            'Formalizar proceso de threat intelligence'
        ],
        'control_iso' => 'Certificación ISO/IEC 27001:2022'
    ],
    
    'AUD-SI-003' => [
        'titulo' => 'AUDITORÍA DE CUMPLIMIENTO GDPR',
        'tipo' => 'Auditoría de Cumplimiento Regulatorio',
        'alcance' => 'Procesamiento de datos personales de ciudadanos UE',
        'resultado' => 'CONFORME con observaciones',
        'hallazgos' => [
            'Registro de actividades de tratamiento: CONFORME',
            'Evaluaciones de impacto de privacidad: CONFORME',
            'Derechos de sujetos de datos: CONFORME',
            'Transferencias internacionales: OBSERVACIÓN'
        ],
        'recomendaciones' => [
            'Implementar Standard Contractual Clauses (SCC) actualizadas',
            'Documentar DPIAs para nuevos procesamientos',
            'Mejorar proceso de respuesta a solicitudes DSAR'
        ],
        'control_iso' => 'A.5.31 a A.5.34 - Requisitos legales y regulatorios'
    ],
    
    'AUD-SI-004' => [
        'titulo' => 'AUDITORÍA DE CONTROLES DE ACCESO',
        'tipo' => 'Auditoría Técnica Específica',
        'alcance' => 'Sistemas de autenticación, autorización y gestión de identidades',
        'hallazgos' => [
            'Cuentas inactivas >90 días: 15 encontradas',
            'Usuarios con privilegios excesivos: 8',
            'Cuentas compartidas: 3',
            'Contraseñas que no cumplen política: 0'
        ],
        'acciones_correctivas' => [
            'Desactivación de cuentas inactivas',
            'Revisión y ajuste de privilegios',
            'Eliminación de cuentas compartidas',
            'Automatización de revisiones'
        ],
        'control_iso' => 'A.5.15 a A.5.18 - Control de acceso'
    ],
    
    'AUD-SI-005' => [
        'titulo' => 'AUDITORÍA DE SEGURIDAD EN DESARROLLO',
        'tipo' => 'Auditoría de Procesos de Desarrollo',
        'alcance' => 'SDLC, prácticas de desarrollo seguro, DevSecOps',
        'evaluacion' => [
            'SAST implementado: SÍ - SonarQube',
            'DAST implementado: SÍ - OWASP ZAP',
            'Code review obligatorio: SÍ - 95% compliance',
            'Gestión de secretos: SÍ - HashiCorp Vault',
            'Dependency scanning: SÍ - Snyk'
        ],
        'brechas' => [
            'Threat modeling no realizado en todos los proyectos',
            'Pruebas de penetración solo anuales (recomendado semestral)',
            'Security champions solo en 60% de equipos'
        ],
        'control_iso' => 'A.8.25 a A.8.30 - Desarrollo seguro'
    ],
    
    'AUD-SI-006' => [
        'titulo' => 'AUDITORÍA DE PROVEEDORES CRÍTICOS',
        'tipo' => 'Auditoría de Terceros',
        'proveedores_auditados' => '8 proveedores Tier 1',
        'resultados' => [
            'Conformes: 6 proveedores',
            'Conformes con observaciones: 2 proveedores',
            'No conformes: 0'
        ],
        'observaciones_principales' => [
            'Proveedor A: Plan de continuidad no probado en último año',
            'Proveedor B: Certificación SOC 2 próxima a vencer'
        ],
        'seguimiento' => 'Planes de acción acordados con ambos proveedores',
        'control_iso' => 'A.5.19 a A.5.23 - Relaciones con proveedores'
    ]
];

// =====================================================
// CAPACITACIÓN Y CONCIENCIACIÓN
// =====================================================

$capacitacion = [
    'CAP-SI-001' => [
        'titulo' => 'PROGRAMA DE CAPACITACIÓN EN SEGURIDAD 2024',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Plan anual de capacitación y concienciación en seguridad de la información',
        'contenido' => [
            'CAPACITACIÓN OBLIGATORIA' => [
                'Inducción de seguridad (nuevos empleados):',
                '- Duración: 2 horas',
                '- Completitud: 100% (325/325)',
                '- Aprobación mínima: 80%',
                '- Tasa de aprobación: 94%',
                '',
                'Capacitación anual de seguridad:',
                '- Duración: 1 hora',
                '- Completitud: 98% (318/325)',
                '- Temas: Phishing, contraseñas, GDPR, incidentes',
                '',
                'Simulaciones de phishing:',
                '- Frecuencia: Mensual',
                '- Tasa de clicks Q1: 15%',
                '- Tasa de clicks Q4: 7%',
                '- Mejora: 53%'
            ],
            'CAPACITACIÓN POR ROLES' => [
                'Desarrolladores (45 personas):',
                '- Secure Coding: 100% completado',
                '- OWASP Top 10: 100% completado',
                '',
                'Administradores de sistemas (12 personas):',
                '- Hardening de sistemas: 100%',
                '- Respuesta a incidentes: 100%',
                '',
                'Usuarios privilegiados (25 personas):',
                '- Gestión de privilegios: 96%'
            ],
            'PROGRAMA SECURITY CHAMPIONS' => [
                'Champions activos: 12 (uno por departamento)',
                'Reuniones mensuales: 12/12 realizadas',
                'Actividades lideradas: 24',
                'Satisfacción del programa: 4.5/5'
            ]
        ],
        'metricas' => [
            'Inversión total: $85,000',
            'Horas de capacitación: 1,250',
            'Empleados capacitados: 325',
            'Certificaciones obtenidas: 8 (CISSP, CEH, etc.)'
        ],
        'control_iso' => 'A.6.3 - Concienciación, educación y capacitación'
    ],
    
    'CAP-SI-002' => [
        'titulo' => 'CONTENIDO DE CAPACITACIÓN - MÓDULOS DISPONIBLES',
        'modulos' => [
            'Seguridad Básica (obligatorio)' => '1 hora - Quiz final',
            'Phishing y Social Engineering' => '30 min - Casos prácticos',
            'Gestión de Contraseñas' => '20 min - Demo de password manager',
            'GDPR y Privacidad' => '45 min - Escenarios',
            'Seguridad en Trabajo Remoto' => '30 min',
            'Clasificación de Información' => '25 min',
            'Reporte de Incidentes' => '15 min',
            'Ingeniería Social' => '40 min - Videos de casos reales',
            'Seguridad Móvil' => '30 min',
            'Secure Coding (desarrolladores)' => '8 horas - Hands-on labs'
        ],
        'formatos' => ['E-learning', 'Presencial', 'Videos', 'Workshops', 'Simulaciones'],
        'control_iso' => 'A.6.3'
    ],
    
    'CAP-SI-003' => [
        'titulo' => 'CAMPAÑAS DE CONCIENCIACIÓN 2024',
        'campañas_realizadas' => [
            'Enero-Febrero: Mes de las Contraseñas Seguras',
            'Marzo-Abril: Phishing Awareness',
            'Mayo-Junio: Seguridad Física y Escritorio Limpio',
            'Julio-Agosto: Protección de Datos y GDPR',
            'Septiembre-Octubre: Seguridad Móvil',
            'Noviembre-Diciembre: Preparación para Amenazas Cibernéticas'
        ],
        'materiales' => [
            'Posters: 36 diseños diferentes',
            'Videos: 12 videos cortos (2-3 min)',
            'Infografías: 24',
            'Newsletters: 12 ediciones mensuales',
            'Tips semanales: 52'
        ],
        'engagement' => [
            'Apertura de emails: 78%',
            'Clicks en contenido: 45%',
            'Participación en quizzes: 62%',
            'Feedback positivo: 89%'
        ],
        'control_iso' => 'A.6.3'
    ],
    
    'CAP-SI-004' => [
        'titulo' => 'EVALUACIÓN Y MEJORA DEL PROGRAMA DE CAPACITACIÓN',
        'evaluacion_2024' => [
            'Efectividad general: 8.5/10',
            'Reducción de incidentes por error humano: 35%',
            'Mejora en simulaciones de phishing: 53%',
            'Satisfacción de empleados: 4.2/5'
        ],
        'areas_mejora' => [
            'Más contenido práctico hands-on',
            'Gamificación del aprendizaje',
            'Microlearning (contenido corto diario)',
            'Mayor frecuencia de simulaciones'
        ],
        'plan_2025' => [
            'Plataforma de gamificación de seguridad',
            'Programa de reconocimiento mejorado',
            'Contenido en múltiples idiomas',
            'Realidad virtual para training de incidentes',
            'Presupuesto propuesto: $120,000'
        ],
        'control_iso' => 'A.6.3'
    ]
];

// =====================================================
// MONITOREO Y MÉTRICAS DE SEGURIDAD
// =====================================================

$monitoreo = [
    'MON-SI-001' => [
        'titulo' => 'DASHBOARD DE MÉTRICAS DE SEGURIDAD - TIEMPO REAL',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Monitoreo continuo de indicadores clave de seguridad',
        'metricas_tiempo_real' => [
            'EVENTOS DE SEGURIDAD (últimas 24h)' => [
                'Total de eventos: 45,234',
                'Alertas generadas: 156',
                'Incidentes confirmados: 3',
                'False positives: 12'
            ],
            'ESTADO DE SISTEMAS' => [
                'Sistemas monitoreados: 235/235 (100%)',
                'Sistemas saludables: 232 (98.7%)',
                'Sistemas con alertas: 3',
                'Sistemas caídos: 0'
            ],
            'AMENAZAS BLOQUEADAS' => [
                'Emails maliciosos bloqueados: 1,234',
                'Malware bloqueado: 45',
                'Intentos de intrusión bloqueados: 89',
                'URLs maliciosas bloqueadas: 234'
            ],
            'VULNERABILIDADES' => [
                'Críticas abiertas: 2',
                'Altas abiertas: 15',
                'Medias abiertas: 87',
                'Bajas abiertas: 234'
            ]
        ],
        'herramientas' => ['Splunk SIEM', 'CrowdStrike EDR', 'Palo Alto Firewalls', 'Tenable Nessus'],
        'control_iso' => 'A.8.15, A.8.16 - Registro y monitoreo'
    ],
    
    'MON-SI-002' => [
        'titulo' => 'REPORTE MENSUAL DE MÉTRICAS DE SEGURIDAD',
        'periodo' => 'Noviembre 2024',
        'kpis_principales' => [
            'Disponibilidad de sistemas: 99.8%',
            'Tiempo promedio de detección de incidentes: 15 minutos',
            'Tiempo promedio de respuesta: 35 minutos',
            'Tiempo promedio de resolución: 3.5 horas',
            'Cumplimiento de parchado: 94%',
            'Cobertura de antivirus/EDR: 100%'
        ],
        'tendencias' => [
            'Incidentes vs mes anterior: -12%',
            'Vulnerabilidades críticas resueltas: 100%',
            'Simulaciones de phishing - tasa de clicks: 7% (mejora de 3%)'
        ],
        'control_iso' => 'A.5.37 - Revisión independiente'
    ],
    
    'MON-SI-003' => [
        'titulo' => 'MONITOREO DE CUMPLIMIENTO DE CONTROLES',
        'controles_evaluados' => '93 controles ISO 27001:2022',
        'estado' => [
            'Implementados y funcionando: 85 (91%)',
            'Parcialmente implementados: 6 (7%)',
            'No implementados: 2 (2%)',
            'No aplicables: 0'
        ],
        'controles_requieren_atencion' => [
            'A.8.23 - Web filtering (parcial)',
            'A.8.28 - Secure coding (en progreso)'
        ],
        'proxima_revision' => 'Trimestral',
        'control_iso' => 'A.5.10 - Uso aceptable de información'
    ],
    
    'MON-SI-004' => [
        'titulo' => 'MONITOREO DE PROVEEDORES DE SEGURIDAD',
        'proveedores_criticos' => '8 proveedores monitoreados',
        'metricas' => [
            'Cumplimiento de SLA: 97%',
            'Incidentes de proveedores: 2',
            'Cambios notificados: 5',
            'Certificaciones vigentes: 8/8 (100%)'
        ],
        'alertas' => [
            'Proveedor X - SOC 2 vence en 30 días',
            'Proveedor Y - Cambio de datacenter notificado'
        ],
        'control_iso' => 'A.5.22 - Monitoreo de servicios de proveedores'
    ],
    
    'MON-SI-005' => [
        'titulo' => 'MÉTRICAS DE CONCIENCIACIÓN Y CAPACITACIÓN',
        'metricas' => [
            'Completitud de capacitación anual: 98%',
            'Simulaciones de phishing realizadas: 12/12',
            'Tasa promedio de clicks en phishing: 7%',
            'Reportes de phishing por usuarios: 156',
            'Certificaciones de seguridad del equipo: 8'
        ],
        'comparativo_anual' => [
            '2023 - Tasa de clicks: 18%',
            '2024 - Tasa de clicks: 7%',
            'Mejora: 61%'
        ],
        'control_iso' => 'A.6.3 - Concienciación'
    ]
];

// =====================================================
// CONTINUIDAD DEL NEGOCIO Y DR
// =====================================================

$continuidad = [
    'CONT-SI-001' => [
        'titulo' => 'PLAN DE CONTINUIDAD DEL NEGOCIO (BCP)',
        'version' => '2.0',
        'fecha' => date('d/m/Y'),
        'objetivo' => 'Garantizar continuidad de operaciones críticas ante disrupciones',
        'alcance' => 'Procesos de negocio críticos y sistemas que los soportan',
        'procesos_criticos' => [
            '1. Procesamiento de transacciones' => [
                'RTO: 4 horas',
                'RPO: 1 hora',
                'Sistemas: ERP, Base de datos transaccional',
                'Personal clave: 5 personas',
                'Sitio alterno: DR Site AWS'
            ],
            '2. Atención a clientes' => [
                'RTO: 8 horas',
                'RPO: 24 horas',
                'Sistemas: CRM, Portal de clientes',
                'Personal clave: 10 personas',
                'Alternativa: Trabajo remoto'
            ],
            '3. Email y comunicaciones' => [
                'RTO: 2 horas',
                'RPO: 1 hora',
                'Sistemas: Microsoft 365',
                'Redundancia: Nativa de cloud'
            ]
        ],
        'estrategias' => [
            'Trabajo remoto habilitado para 100% del personal',
            'Sitio de recuperación (DR) en cloud',
            'Acuerdos con proveedores alternativos',
            'Inventario de equipos de respaldo'
        ],
        'ultima_prueba' => '2024-09-15',
        'resultado_prueba' => 'Exitosa - RTO cumplido',
        'control_iso' => 'A.5.29, A.5.30'
    ],
    
    'CONT-SI-002' => [
        'titulo' => 'PLAN DE RECUPERACIÓN DE DESASTRES (DRP)',
        'version' => '2.0',
        'objetivo' => 'Recuperación de sistemas críticos ante desastre',
        'escenarios_contemplados' => [
            'Incendio en datacenter',
            'Falla eléctrica prolongada',
            'Terremoto',
            'Inundación',
            'Ciberataque (ransomware)',
            'Falla masiva de hardware'
        ],
        'site_recuperacion' => [
            'Ubicación: AWS us-east-1 (primario en Chile)',
            'Tipo: Warm site',
            'Capacidad: 100% de sistemas críticos',
            'Tiempo de activación: 2-4 horas',
            'Replicación de datos: Continua para DB críticas'
        ],
        'procedimientos' => [
            'Fase 1 - Evaluación (0-1h): Declaración de desastre, evaluación de daños',
            'Fase 2 - Activación (1-4h): Activación de DR site, restauración de servicios críticos',
            'Fase 3 - Operación (4-48h): Operación desde sitio alterno',
            'Fase 4 - Retorno: Migración de vuelta a sitio principal'
        ],
        'ultima_prueba_dr' => '2024-10-20',
        'resultado' => 'RTO: 3.5 horas (objetivo 4h) - CUMPLIDO',
        'control_iso' => 'A.5.30'
    ],
    
    'CONT-SI-003' => [
        'titulo' => 'ANÁLISIS DE IMPACTO AL NEGOCIO (BIA)',
        'version' => '1.5',
        'fecha_analisis' => '2024-01-15',
        'procesos_evaluados' => '25 procesos de negocio',
        'criticidad' => [
            'TIER 1 - Crítico (5 procesos)' => [
                'Procesamiento de transacciones',
                'Facturación',
                'Atención a clientes',
                'Operación de producción',
                'Nómina'
            ],
            'TIER 2 - Importante (10 procesos)' => [
                'Desarrollo de productos',
                'Gestión de inventario',
                'Compras',
                'Marketing',
                'Otros...'
            ],
            'TIER 3 - Normal (10 procesos)' => [
                'Capacitación',
                'Gestión documental',
                'Otros...'
            ]
        ],
        'impactos_cuantificados' => [
            'Pérdida financiera por hora de downtime: $25,000',
            'Pérdida de productividad: $10,000/día',
            'Impacto reputacional: Alto para >24h downtime',
            'Penalizaciones contractuales: $50,000/día en SLA críticos'
        ],
        'proxima_revision' => '2025-01-15 (anual)',
        'control_iso' => 'A.5.29'
    ],
    
    'CONT-SI-004' => [
        'titulo' => 'REGISTRO DE PRUEBAS DE CONTINUIDAD',
        'pruebas_2024' => [
            'Prueba 1 - Restauración de base de datos (Mensual)' => [
                'Última: 2024-11-15',
                'Resultado: Exitosa',
                'Tiempo: 45 minutos',
                'Datos restaurados: 100%'
            ],
            'Prueba 2 - Failover de sistemas críticos (Trimestral)' => [
                'Última: 2024-10-20',
                'Resultado: Exitosa',
                'RTO alcanzado: 3.5 horas (objetivo 4h)',
                'Hallazgos: 2 mejoras identificadas'
            ],
            'Prueba 3 - Simulacro completo de DR (Anual)' => [
                'Fecha: 2024-09-15',
                'Duración: 8 horas',
                'Participantes: 25 personas',
                'Resultado: Exitosa con observaciones',
                'Lecciones aprendidas: 8 documentadas'
            ],
            'Prueba 4 - Activación de equipo de crisis (Semestral)' => [
                'Última: 2024-06-10',
                'Escenario: Ciberataque ransomware',
                'Resultado: Exitosa',
                'Tiempo de convocatoria: 20 minutos'
            ]
        ],
        'mejoras_implementadas' => [
            'Automatización de failover para DB críticas',
            'Playbooks actualizados con lecciones aprendidas',
            'Capacitación adicional a equipo de respuesta'
        ],
        'control_iso' => 'A.5.30'
    ],
    
    'CONT-SI-005' => [
        'titulo' => 'CONTACTOS DE EMERGENCIA Y ESCALAMIENTO',
        'comite_crisis' => [
            'CEO - Juan Pérez - +56 9 XXXX XXXX',
            'CIO - María García - +56 9 XXXX XXXX',
            'CISO - Luis Rodríguez - +56 9 XXXX XXXX',
            'CFO - Ana López - +56 9 XXXX XXXX',
            'Director Operaciones - Carlos Martínez - +56 9 XXXX XXXX'
        ],
        'equipos_respuesta' => [
            'Equipo DR (6 personas) - Disponibilidad 24/7',
            'Equipo Incidentes (4 personas) - On-call rotation',
            'Equipo Comunicaciones (3 personas)'
        ],
        'proveedores_emergencia' => [
            'AWS Support - Enterprise - 24/7',
            'Microsoft Support - Premier - 24/7',
            'Consultor de seguridad - SecureIT - Retainer'
        ],
        'ultima_actualizacion' => 'Mensual - próxima: 2024-12-01',
        'control_iso' => 'A.5.29'
    ]
];

// =====================================================
// GESTIÓN DE PROVEEDORES
// =====================================================

$proveedores = [
    'PROV-SI-001' => [
        'titulo' => 'EVALUACIÓN DE SEGURIDAD DE PROVEEDORES - TIER 1',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'proveedores_tier1' => '8 proveedores críticos',
        'criterios_evaluacion' => [
            'Certificaciones de seguridad (ISO 27001, SOC 2)',
            'Políticas de seguridad',
            'Gestión de incidentes',
            'Continuidad del negocio',
            'Cumplimiento regulatorio',
            'Ubicación y protección de datos',
            'Cifrado y controles de acceso'
        ],
        'resultados' => [
            'AWS - Score: 95/100 - APROBADO',
            'Microsoft - Score: 94/100 - APROBADO',
            'Salesforce - Score: 92/100 - APROBADO',
            'CrowdStrike - Score: 93/100 - APROBADO',
            'Okta - Score: 91/100 - APROBADO',
            'GitHub - Score: 89/100 - APROBADO',
            'Splunk - Score: 90/100 - APROBADO',
            'Cloudflare - Score: 92/100 - APROBADO'
        ],
        'acciones_requeridas' => [
            'GitHub: Solicitar SOC 2 Type II actualizado',
            'Okta: Validar plan de continuidad'
        ],
        'proxima_evaluacion' => 'Anual para Tier 1',
        'control_iso' => 'A.5.19, A.5.20, A.5.21'
    ],
    
    'PROV-SI-002' => [
        'titulo' => 'ACUERDOS DE NIVEL DE SERVICIO (SLA) - RESUMEN',
        'proveedores_con_sla' => '25 proveedores',
        'cumplimiento_global' => '97.2%',
        'slas_criticos' => [
            'AWS - Disponibilidad 99.9% - CUMPLIDO (99.95%)',
            'Microsoft 365 - Uptime 99.9% - CUMPLIDO (99.97%)',
            'Salesforce - Uptime 99.9% - CUMPLIDO (99.92%)',
            'ISP Primario - Uptime 99.5% - CUMPLIDO (99.8%)'
        ],
        'incumplimientos' => [
            'Proveedor de soporte técnico - SLA respuesta 4h - Incumplido 2 veces en Q4',
            'Acción: Reunión de mejora de servicio agendada'
        ],
        'control_iso' => 'A.5.22'
    ],
    
    'PROV-SI-003' => [
        'titulo' => 'REGISTRO DE INCIDENTES DE PROVEEDORES',
        'periodo' => 'Año 2024',
        'total_incidentes' => '8',
        'incidentes_destacados' => [
            'INC-PROV-2024-03 - AWS - Interrupción regional' => [
                'Fecha: 2024-03-15',
                'Duración: 2 horas',
                'Impacto: Servicios no críticos afectados',
                'Mitigación: Failover a otra región',
                'RCA recibido: Sí'
            ],
            'INC-PROV-2024-07 - Microsoft 365 - Degradación de servicio' => [
                'Fecha: 2024-07-22',
                'Duración: 4 horas',
                'Impacto: Email lento',
                'Mitigación: Ninguna necesaria',
                'Compensación: Crédito de servicio aplicado'
            ]
        ],
        'tendencia' => 'Estable - similar a 2023',
        'control_iso' => 'A.5.22'
    ],
    
    'PROV-SI-004' => [
        'titulo' => 'PLAN DE TRANSICIÓN Y OFFBOARDING DE PROVEEDORES',
        'objetivo' => 'Proceso para término de relación con proveedores',
        'proveedores_terminados_2024' => '3',
        'proceso' => [
            '1. Notificación según contrato (60-90 días)',
            '2. Identificación de proveedor sustituto',
            '3. Plan de migración de datos/servicios',
            '4. Revocación de accesos',
            '5. Retorno/destrucción de datos',
            '6. Certificación de eliminación',
            '7. Cierre administrativo'
        ],
        'casos_2024' => [
            'Proveedor A - Servicio de backup - Migrado a solución in-house',
            'Proveedor B - Antivirus legacy - Migrado a CrowdStrike',
            'Proveedor C - Hosting - Migrado a AWS'
        ],
        'lecciones' => [
            'Planificar con mayor anticipación',
            'Validar portabilidad de datos desde inicio',
            'Documentar dependencias detalladamente'
        ],
        'control_iso' => 'A.5.23'
    ]
];

// =====================================================
// CERTIFICACIÓN Y CUMPLIMIENTO ISO 27001
// =====================================================

$certificacion = [
    'CERT-SI-001' => [
        'titulo' => 'CERTIFICADO ISO 27001:2022',
        'organismo_certificador' => 'BSI (British Standards Institution)',
        'numero_certificado' => 'IS 789456',
        'fecha_emision' => '2024-03-15',
        'fecha_vencimiento' => '2027-03-14',
        'alcance_certificacion' => 'Sistema de Gestión de Seguridad de la Información para servicios de TI y desarrollo de software',
        'norma' => 'ISO/IEC 27001:2022',
        'sitios_cubiertos' => [
            'Oficina Principal - Santiago, Chile',
            'Centro de Datos - AWS us-east-1',
            'Oficina Sucursal - Valparaíso'
        ],
        'exclusiones' => 'Ninguna',
        'auditorias_vigilancia' => [
            'Primera vigilancia: 2025-03-15 (12 meses)',
            'Segunda vigilancia: 2026-03-15 (24 meses)',
            'Recertificación: 2027-03-15 (36 meses)'
        ],
        'control_iso' => 'Certificación completa ISO/IEC 27001:2022'
    ],
    
    'CERT-SI-002' => [
        'titulo' => 'DECLARACIÓN DE APLICABILIDAD (SOA)',
        'version' => '2.0',
        'fecha' => date('d/m/Y'),
        'total_controles' => '93 controles ISO 27001:2022',
        'controles_aplicables' => '91 controles (97.8%)',
        'controles_excluidos' => [
            'A.7.11 - Utilidades de soporte' => 'No aplicable - Sin datacenter propio on-premise',
            'A.7.12 - Seguridad del cableado' => 'No aplicable - Infraestructura cloud'
        ],
        'estado_implementacion' => [
            'Completamente implementados: 85 (93.4%)',
            'Parcialmente implementados: 6 (6.6%)',
            'Planeados: 0'
        ],
        'controles_parciales' => [
            'A.8.23 - Filtrado web' => 'En implementación - Plazo Q1 2025',
            'A.8.28 - Secure coding' => 'En mejora continua'
        ],
        'revision' => 'Anual o cuando cambios significativos ocurran',
        'aprobacion' => 'Aprobado por Alta Dirección - CEO',
        'control_iso' => 'Anexo A completo ISO/IEC 27001:2022'
    ],
    
    'CERT-SI-003' => [
        'titulo' => 'POLÍTICA DEL SISTEMA DE GESTIÓN DE SEGURIDAD DE LA INFORMACIÓN',
        'version' => '2.0',
        'fecha' => date('d/m/Y'),
        'alcance' => 'Toda la organización',
        'compromisos_alta_direccion' => [
            '1. Proteger la confidencialidad, integridad y disponibilidad de la información',
            '2. Cumplir con requisitos legales, regulatorios y contractuales',
            '3. Gestionar riesgos de seguridad de la información sistemáticamente',
            '4. Mejorar continuamente la eficacia del SGSI',
            '5. Proporcionar recursos adecuados para el SGSI',
            '6. Establecer objetivos de seguridad medibles',
            '7. Revisar el SGSI periódicamente'
        ],
        'objetivos_seguridad' => [
            'Mantener disponibilidad de sistemas críticos >99.5%',
            'Tiempo de resolución de incidentes críticos <4 horas',
            'Capacitación de seguridad 100% del personal',
            'Cumplimiento de controles >90%',
            'Reducir incidentes de seguridad 10% anual'
        ],
        'aprobado_por' => 'CEO - Juan Pérez',
        'fecha_aprobacion' => '2024-01-15',
        'proxima_revision' => '2025-01-15',
        'control_iso' => 'Cláusula 5 - Liderazgo'
    ],
    
    'CERT-SI-004' => [
        'titulo' => 'REVISIÓN POR LA DIRECCIÓN - 2024',
        'fecha_revision' => '2024-11-30',
        'participantes' => [
            'CEO - Juan Pérez',
            'CIO - María García',
            'CISO - Luis Rodríguez',
            'CFO - Ana López',
            'Gerente de Calidad',
            'Gerente de RRHH'
        ],
        'entradas_revision' => [
            'Estado de acciones de revisiones previas: 8/8 completadas',
            'Cambios en contexto externo e interno: Nuevas regulaciones de ciberseguridad',
            'Retroalimentación sobre desempeño del SGSI: Positiva',
            'Resultados de auditorías: Certificación otorgada, 2 NCM resueltas',
            'Cumplimiento de objetivos: 4/5 objetivos alcanzados',
            'Retroalimentación de partes interesadas: Clientes satisfechos con seguridad',
            'Resultados de evaluación de riesgos: 3 riesgos nuevos identificados',
            'Oportunidades de mejora continua: 12 identificadas'
        ],
        'salidas_revision' => [
            'Decisiones sobre mejora continua: Implementar SOAR, mejorar DLP',
            'Cambios necesarios al SGSI: Actualizar políticas para cloud',
            'Necesidades de recursos: Presupuesto $200K para nuevas herramientas',
            'Objetivos 2025: Certificación ISO 22301 (BCM), SOC 2 Type II'
        ],
        'acciones' => [
            'Implementar SOAR - Responsable: CISO - Plazo: Q2 2025',
            'Proyecto DLP - Responsable: Gerente TI - Plazo: Q3 2025',
            'Iniciar certificación ISO 22301 - Responsable: CISO - Plazo: Q4 2025'
        ],
        'conclusion' => 'SGSI es eficaz y apropiado. Continuar con mejora continua.',
        'control_iso' => 'Cláusula 9.3 - Revisión por la dirección'
    ],
    
    'CERT-SI-005' => [
        'titulo' => 'EVALUACIÓN DE RIESGOS - RESUMEN EJECUTIVO',
        'version' => '3.0',
        'fecha_evaluacion' => '2024-06-30',
        'metodologia' => 'ISO/IEC 27005:2022',
        'activos_evaluados' => '156 activos críticos',
        'riesgos_identificados' => '42',
        'distribucion_riesgos' => [
            'Críticos: 3 (7%)',
            'Altos: 8 (19%)',
            'Medios: 18 (43%)',
            'Bajos: 13 (31%)'
        ],
        'top_3_riesgos' => [
            '1. Ransomware - Riesgo CRÍTICO - Tratamiento: Mitigar',
            '2. Fuga de datos - Riesgo CRÍTICO - Tratamiento: Mitigar',
            '3. Acceso no autorizado - Riesgo ALTO - Tratamiento: Mitigar'
        ],
        'tratamiento_riesgos' => [
            'Mitigar: 29 riesgos (69%)',
            'Aceptar: 11 riesgos (26%)',
            'Transferir: 2 riesgos (5%) - Seguros cibernéticos',
            'Evitar: 0 riesgos'
        ],
        'inversion_controles' => '$450,000 USD',
        'riesgo_residual_promedio' => 'MEDIO - Aceptable según apetito de riesgo',
        'proxima_evaluacion' => 'Trimestral para riesgos críticos, anual completa',
        'aprobacion' => 'Aprobado por CEO y Junta Directiva',
        'control_iso' => 'Cláusula 6.1.2 - Evaluación de riesgos'
    ],
    
    'CERT-SI-006' => [
        'titulo' => 'PLAN DE TRATAMIENTO DE RIESGOS 2024-2025',
        'version' => '1.0',
        'objetivo' => 'Implementar controles para reducir riesgos identificados',
        'proyectos_principales' => [
            'Proyecto 1: Protección contra Ransomware' => [
                'Presupuesto: $150,000',
                'Plazo: 6 meses',
                'Controles: EDR avanzado, backups inmutables, segmentación de red',
                'Responsable: CISO',
                'Estado: En progreso (60%)'
            ],
            'Proyecto 2: Data Loss Prevention (DLP)' => [
                'Presupuesto: $200,000',
                'Plazo: 8 meses',
                'Controles: DLP endpoint, DLP red, CASB',
                'Responsable: Gerente TI',
                'Estado: Planificación'
            ],
            'Proyecto 3: Zero Trust Network' => [
                'Presupuesto: $180,000',
                'Plazo: 12 meses',
                'Controles: Micro-segmentación, MFA universal, ZTNA',
                'Responsable: Gerente Infraestructura',
                'Estado: Diseño'
            ]
        ],
        'seguimiento' => 'Mensual en comité de seguridad',
        'metricas_exito' => [
            'Reducción de riesgo residual promedio de ALTO a MEDIO',
            'Implementación de 15 controles nuevos',
            'Cumplimiento de timeline 90%'
        ],
        'control_iso' => 'Cláusula 6.1.3 - Tratamiento de riesgos'
    ],
    
    'CERT-SI-007' => [
        'titulo' => 'REGISTRO DE MEJORA CONTINUA',
        'version' => '1.0',
        'fecha' => date('d/m/Y'),
        'mejoras_implementadas_2024' => [
            'Mejora 1: Automatización de parchado' => [
                'Origen: Auditoría interna',
                'Implementada: Q1 2024',
                'Beneficio: Reducción de 70% en tiempo de parchado',
                'Inversión: $30,000'
            ],
            'Mejora 2: Simulaciones de phishing mensuales' => [
                'Origen: Análisis de incidentes',
                'Implementada: Q2 2024',
                'Beneficio: Reducción de 53% en tasa de clicks',
                'Inversión: $15,000/año'
            ],
            'Mejora 3: SIEM con correlación avanzada' => [
                'Origen: Revisión por la dirección',
                'Implementada: Q3 2024',
                'Beneficio: Detección 40% más rápida',
                'Inversión: $80,000'
            ],
            'Mejora 4: Portal de seguridad para usuarios' => [
                'Origen: Feedback de empleados',
                'Implementada: Q4 2024',
                'Beneficio: Mejor acceso a recursos de seguridad',
                'Inversión: $20,000'
            ]
        ],
        'mejoras_planificadas_2025' => [
            'SOAR para respuesta automatizada a incidentes',
            'Threat intelligence platform',
            'Security data lake',
            'Gamificación de capacitación de seguridad'
        ],
        'roi_mejoras' => 'Retorno estimado: 3:1 (reducción de incidentes y eficiencia operativa)',
        'control_iso' => 'Cláusula 10 - Mejora continua'
    ]
];

// =====================================================
// MERGE ALL DOCUMENTS
// =====================================================

$documentos = array_merge(
    $politicas,
    $procedimientos,
    $inventarios,
    $riesgos,
    $controles,
    $incidentes,
    $auditorias,
    $capacitacion,
    $monitoreo,
    $continuidad,
    $proveedores,
    $certificacion
);

// Verificar que existe el documento solicitado
if (!isset($documentos[$code])) {
    die('Documento no encontrado: ' . htmlspecialchars($code));
}

$doc = $documentos[$code];


// =====================================================
// FUNCIONES DE GENERACIÓN SIN COMPOSER
// =====================================================

function generarWord($code, $doc) {
    // Generar HTML que puede ser guardado como .doc
    header('Content-Type: application/vnd.ms-word');
    header('Content-Disposition: attachment;filename="' . $code . '.doc"');
    header('Cache-Control: max-age=0');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><title>' . htmlspecialchars($doc['titulo']) . '</title></head>';
    echo '<body>';
    
    // Encabezado
    echo '<div style="text-align: center; margin-bottom: 30px;">';
    echo '<h1 style="color: #003366;">' . htmlspecialchars($doc['titulo']) . '</h1>';
    echo '<p><strong>Código:</strong> ' . htmlspecialchars($code) . '</p>';
    if (isset($doc['version'])) echo '<p><strong>Versión:</strong> ' . htmlspecialchars($doc['version']) . '</p>';
    if (isset($doc['fecha'])) echo '<p><strong>Fecha:</strong> ' . htmlspecialchars($doc['fecha']) . '</p>';
    echo '</div>';
    
    // Objetivo y Alcance
    if (isset($doc['objetivo'])) {
        echo '<h2>1. OBJETIVO</h2>';
        echo '<p>' . nl2br(htmlspecialchars($doc['objetivo'])) . '</p>';
    }
    
    if (isset($doc['alcance'])) {
        echo '<h2>2. ALCANCE</h2>';
        echo '<p>' . nl2br(htmlspecialchars($doc['alcance'])) . '</p>';
    }
    
    // Definiciones
    if (isset($doc['definiciones'])) {
        echo '<h2>3. DEFINICIONES</h2>';
        echo '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse;">';
        foreach ($doc['definiciones'] as $termino => $definicion) {
            echo '<tr>';
            echo '<td style="width: 30%; background-color: #f0f0f0;"><strong>' . htmlspecialchars($termino) . '</strong></td>';
            echo '<td>' . htmlspecialchars($definicion) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    // Política o Procedimiento o Contenido
    $seccion = 4;
    if (isset($doc['politica'])) {
        echo '<h2>' . $seccion . '. POLÍTICA</h2>';
        renderArray($doc['politica']);
        $seccion++;
    }
    
    if (isset($doc['procedimiento'])) {
        echo '<h2>' . $seccion . '. PROCEDIMIENTO</h2>';
        renderArray($doc['procedimiento']);
        $seccion++;
    }
    
    if (isset($doc['contenido'])) {
        echo '<h2>' . $seccion . '. CONTENIDO</h2>';
        renderArray($doc['contenido']);
        $seccion++;
    }
    
    if (isset($doc['controles_implementados'])) {
        echo '<h2>' . $seccion . '. CONTROLES IMPLEMENTADOS</h2>';
        renderArray($doc['controles_implementados']);
        $seccion++;
    }
    
    if (isset($doc['metricas'])) {
        echo '<h2>' . $seccion . '. MÉTRICAS</h2>';
        renderArray($doc['metricas']);
        $seccion++;
    }
    
    // Cumplimiento y Revisión
    if (isset($doc['cumplimiento'])) {
        echo '<h2>' . $seccion . '. CUMPLIMIENTO</h2>';
        echo '<p>' . nl2br(htmlspecialchars($doc['cumplimiento'])) . '</p>';
        $seccion++;
    }
    
    if (isset($doc['revision'])) {
        echo '<h2>' . $seccion . '. REVISIÓN</h2>';
        echo '<p>' . nl2br(htmlspecialchars($doc['revision'])) . '</p>';
        $seccion++;
    }
    
    // Control ISO
    if (isset($doc['control_iso'])) {
        echo '<hr style="margin-top: 30px;">';
        echo '<p style="font-size: 0.9em; color: #666;"><strong>Control ISO 27001:2022:</strong> ' . htmlspecialchars($doc['control_iso']) . '</p>';
    }
    
    echo '</body></html>';
}

function generarExcel($code, $doc) {
    // Generar HTML table que puede ser guardado como .xls
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $code . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<html><head><meta charset="UTF-8"><title>' . htmlspecialchars($doc['titulo']) . '</title></head><body>';
    echo '<table border="1">';
    
    // Título
    echo '<tr><td colspan="2" style="background-color: #003366; color: white; font-size: 16px; font-weight: bold; text-align: center;">' . htmlspecialchars($doc['titulo']) . '</td></tr>';
    echo '<tr><td style="background-color: #f0f0f0; font-weight: bold;">Código</td><td>' . htmlspecialchars($code) . '</td></tr>';
    if (isset($doc['version'])) echo '<tr><td style="background-color: #f0f0f0; font-weight: bold;">Versión</td><td>' . htmlspecialchars($doc['version']) . '</td></tr>';
    if (isset($doc['fecha'])) echo '<tr><td style="background-color: #f0f0f0; font-weight: bold;">Fecha</td><td>' . htmlspecialchars($doc['fecha']) . '</td></tr>';
    
    echo '<tr><td colspan="2"></td></tr>';
    
    if (isset($doc['objetivo'])) {
        echo '<tr><td style="background-color: #f0f0f0; font-weight: bold;">Objetivo</td><td>' . nl2br(htmlspecialchars($doc['objetivo'])) . '</td></tr>';
    }
    
    if (isset($doc['alcance'])) {
        echo '<tr><td style="background-color: #f0f0f0; font-weight: bold;">Alcance</td><td>' . nl2br(htmlspecialchars($doc['alcance'])) . '</td></tr>';
    }
    
    echo '<tr><td colspan="2"></td></tr>';
    echo '<tr><td colspan="2" style="background-color: #e0e0e0; font-weight: bold;">CONTENIDO DETALLADO</td></tr>';
    
    // Contenido en tabla
    $contenido_array = null;
    if (isset($doc['politica'])) $contenido_array = $doc['politica'];
    elseif (isset($doc['procedimiento'])) $contenido_array = $doc['procedimiento'];
    elseif (isset($doc['contenido'])) $contenido_array = $doc['contenido'];
    elseif (isset($doc['controles_implementados'])) $contenido_array = $doc['controles_implementados'];
    
    if ($contenido_array && is_array($contenido_array)) {
        renderArrayAsTable($contenido_array);
    }
    
    if (isset($doc['control_iso'])) {
        echo '<tr><td colspan="2"></td></tr>';
        echo '<tr><td style="background-color: #f0f0f0; font-weight: bold;">Control ISO 27001:2022</td><td>' . htmlspecialchars($doc['control_iso']) . '</td></tr>';
    }
    
    echo '</table></body></html>';
}

function generarPDF($code, $doc) {
    // Generar HTML optimizado para imprimir como PDF
    header('Content-Type: text/html; charset=UTF-8');
    
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
    echo '<title>' . htmlspecialchars($doc['titulo']) . '</title>';
    echo '<style>
        @media print {
            body { margin: 2cm; }
            .page-break { page-break-before: always; }
        }
        body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #003366; font-size: 20pt; text-align: center; margin-bottom: 10px; border-bottom: 3px solid #003366; padding-bottom: 10px; }
        h2 { color: #003366; font-size: 14pt; margin-top: 20px; border-bottom: 1px solid #003366; padding-bottom: 5px; }
        h3 { color: #0066cc; font-size: 12pt; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table, th, td { border: 1px solid #ccc; }
        th { background-color: #003366; color: white; padding: 8px; text-align: left; }
        td { padding: 6px; }
        .header-info { text-align: center; margin-bottom: 30px; color: #666; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 9pt; color: #666; }
        ul { margin: 5px 0; padding-left: 20px; }
        .print-button { padding: 10px 20px; background-color: #003366; color: white; border: none; cursor: pointer; margin-bottom: 20px; }
        @media print { .print-button { display: none; } }
    </style>';
    echo '</head><body>';
    
    echo '<button class="print-button" onclick="window.print()">Imprimir / Guardar como PDF</button>';
    
    // Encabezado
    echo '<h1>' . htmlspecialchars($doc['titulo']) . '</h1>';
    echo '<div class="header-info">';
    echo '<strong>Código:</strong> ' . htmlspecialchars($code) . ' | ';
    if (isset($doc['version'])) echo '<strong>Versión:</strong> ' . htmlspecialchars($doc['version']) . ' | ';
    if (isset($doc['fecha'])) echo '<strong>Fecha:</strong> ' . htmlspecialchars($doc['fecha']);
    echo '</div>';
    
    // Contenido
    if (isset($doc['objetivo'])) {
        echo '<h2>1. OBJETIVO</h2>';
        echo '<p>' . nl2br(htmlspecialchars($doc['objetivo'])) . '</p>';
    }
    
    if (isset($doc['alcance'])) {
        echo '<h2>2. ALCANCE</h2>';
        echo '<p>' . nl2br(htmlspecialchars($doc['alcance'])) . '</p>';
    }
    
    if (isset($doc['definiciones'])) {
        echo '<h2>3. DEFINICIONES</h2>';
        echo '<table><thead><tr><th style="width:30%">Término</th><th>Definición</th></tr></thead><tbody>';
        foreach ($doc['definiciones'] as $termino => $definicion) {
            echo '<tr><td><strong>' . htmlspecialchars($termino) . '</strong></td><td>' . htmlspecialchars($definicion) . '</td></tr>';
        }
        echo '</tbody></table>';
    }
    
    $seccion = 4;
    if (isset($doc['politica'])) {
        echo '<h2>' . $seccion . '. POLÍTICA</h2>';
        renderArray($doc['politica']);
        $seccion++;
    }
    
    if (isset($doc['procedimiento'])) {
        echo '<h2>' . $seccion . '. PROCEDIMIENTO</h2>';
        renderArray($doc['procedimiento']);
        $seccion++;
    }
    
    if (isset($doc['contenido'])) {
        echo '<h2>' . $seccion . '. CONTENIDO</h2>';
        renderArray($doc['contenido']);
        $seccion++;
    }
    
    if (isset($doc['controles_implementados'])) {
        echo '<h2>' . $seccion . '. CONTROLES IMPLEMENTADOS</h2>';
        renderArray($doc['controles_implementados']);
        $seccion++;
    }
    
    if (isset($doc['metricas'])) {
        echo '<h2>' . $seccion . '. MÉTRICAS</h2>';
        renderArray($doc['metricas']);
        $seccion++;
    }
    
    if (isset($doc['cumplimiento'])) {
        echo '<h2>' . $seccion . '. CUMPLIMIENTO</h2>';
        echo '<p>' . nl2br(htmlspecialchars($doc['cumplimiento'])) . '</p>';
        $seccion++;
    }
    
    if (isset($doc['revision'])) {
        echo '<h2>' . $seccion . '. REVISIÓN</h2>';
        echo '<p>' . nl2br(htmlspecialchars($doc['revision'])) . '</p>';
    }
    
    // Footer
    echo '<div class="footer">';
    if (isset($doc['control_iso'])) {
        echo '<strong>Control ISO 27001:2022:</strong> ' . htmlspecialchars($doc['control_iso']) . '<br>';
    }
    echo 'Documento generado el ' . date('d/m/Y H:i:s') . ' - Sistema de Gestión de Seguridad de la Información';
    echo '</div>';
    
    echo '</body></html>';
}

// Función auxiliar para renderizar arrays anidados
function renderArray($arr, $level = 0) {
    foreach ($arr as $key => $value) {
        if (is_array($value)) {
            if (is_numeric($key)) {
                echo '<ul>';
                foreach ($value as $item) {
                    echo '<li>' . nl2br(htmlspecialchars($item)) . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<h3>' . htmlspecialchars($key) . '</h3>';
                renderArray($value, $level + 1);
            }
        } else {
            if (is_numeric($key)) {
                echo '<p>• ' . nl2br(htmlspecialchars($value)) . '</p>';
            } else {
                echo '<p><strong>' . htmlspecialchars($key) . ':</strong> ' . nl2br(htmlspecialchars($value)) . '</p>';
            }
        }
    }
}

// Función auxiliar para renderizar arrays como tabla de Excel
function renderArrayAsTable($arr, $indent = 0) {
    foreach ($arr as $key => $value) {
        if (is_array($value)) {
            echo '<tr><td colspan="2" style="background-color: #e0e0e0; font-weight: bold; padding-left: ' . ($indent * 20) . 'px;">' . htmlspecialchars($key) . '</td></tr>';
            renderArrayAsTable($value, $indent + 1);
        } else {
            $keyCell = is_numeric($key) ? '' : htmlspecialchars($key);
            echo '<tr><td style="background-color: #f5f5f5; padding-left: ' . ($indent * 20) . 'px;">' . $keyCell . '</td><td>' . nl2br(htmlspecialchars($value)) . '</td></tr>';
        }
    }
}

// =====================================================
// EJECUTAR GENERACIÓN SEGÚN FORMATO
// =====================================================

switch ($format) {
    case 'docx':
    case 'word':
        generarWord($code, $doc);
        break;
    case 'xlsx':
    case 'excel':
        generarExcel($code, $doc);
        break;
    case 'pdf':
    default:
        generarPDF($code, $doc);
        break;
}

