<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// SIN DEPENDENCIAS - SOLO PHP PURO
$code = isset($_GET['code']) ? $_GET['code'] : '';
$format = isset($_GET['format']) ? $_GET['format'] : 'docx';

if (empty($code)) {
    die('Código de documento no especificado');
}

// ===================================================
// TODOS LOS DOCUMENTOS ISO 27001 - CONTENIDO COMPLETO
// ===================================================

$documentos = [
    // ============= 22 POLÍTICAS =============
    'POL-SI-001' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE SEGURIDAD DE LA INFORMACIÓN',
        'version' => '1.0',
        'objetivo' => 'Establecer el marco general de seguridad de la información en la organización.',
        'alcance' => 'Todos los empleados, contratistas y terceros con acceso a información.',
        'contenido' => [
            'PRINCIPIOS FUNDAMENTALES' => [
                'Confidencialidad: Garantizar que la información es accesible solo por personas autorizadas',
                'Integridad: Asegurar la exactitud y completitud de la información',
                'Disponibilidad: Garantizar acceso a la información cuando se necesite',
                'Autenticidad: Verificar identidad de usuarios y origen de información',
                'No Repudio: Evitar que un usuario niegue haber realizado una acción'
            ],
            'RESPONSABILIDADES' => [
                'Alta Dirección: Aprobar y promover la política de seguridad, asignar recursos',
                'CISO: Implementar y supervisar el SGSI, gestionar riesgos de seguridad',
                'Jefes de Área: Cumplir y hacer cumplir políticas, identificar activos críticos',
                'Empleados: Conocer y cumplir políticas, proteger activos asignados, reportar incidentes'
            ],
            'GESTIÓN DE RIESGOS' => [
                'Análisis de riesgos anual',
                'Riesgos críticos tratados de inmediato',
                'Controles según ISO 27001:2022',
                'Riesgos residuales aceptados formalmente'
            ]
        ],
        'control_iso' => 'A.5.1 - Políticas de seguridad de la información'
    ],

    'POL-SI-002' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE CONTROL DE ACCESO',
        'version' => '1.0',
        'objetivo' => 'Establecer reglas para la gestión y control de accesos a sistemas y aplicaciones.',
        'alcance' => 'Todos los sistemas de información, aplicaciones y recursos tecnológicos.',
        'contenido' => [
            'AUTENTICACIÓN' => [
                'Contraseñas mínimo 12 caracteres con mayúsculas, minúsculas, números y símbolos',
                'MFA obligatorio para accesos remotos y cuentas administrativas',
                'Bloqueo automático tras 5 intentos fallidos',
                'Cambio de contraseña cada 90 días'
            ],
            'AUTORIZACIÓN' => [
                'Principio de menor privilegio',
                'Aprobación formal para otorgar accesos',
                'Revisión trimestral de permisos',
                'Revocación inmediata al cesar relación laboral'
            ],
            'CUENTAS PRIVILEGIADAS' => [
                'Uso exclusivo para tareas administrativas',
                'Sesiones monitoreadas y registradas',
                'Rotación de contraseñas cada 30 días',
                'Gestión con PAM'
            ]
        ],
        'control_iso' => 'A.5.15-A.5.18 - Control de acceso'
    ],

    'POL-SI-003' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE CLASIFICACIÓN DE LA INFORMACIÓN',
        'version' => '1.0',
        'objetivo' => 'Definir niveles de clasificación y controles de protección según sensibilidad.',
        'alcance' => 'Toda información generada, procesada, almacenada o transmitida por la organización.',
        'contenido' => [
            'NIVELES DE CLASIFICACIÓN' => [
                'PÚBLICA: Información sin valor confidencial, sin restricciones de distribución',
                'INTERNA: Uso interno solamente, requiere NDA para externos',
                'CONFIDENCIAL: Información sensible, acceso restringido, cifrado requerido',
                'ESTRICTAMENTE CONFIDENCIAL: Información crítica, máxima restricción, cifrado obligatorio'
            ],
            'ETIQUETADO' => [
                'Documentos físicos: Encabezado y pie de página con clasificación',
                'Documentos digitales: Metadatos de clasificación, DLP implementado',
                'Correos electrónicos: Banner automático según clasificación'
            ],
            'CONTROLES POR CLASIFICACIÓN' => [
                'PÚBLICA: Sin restricción de almacenamiento o transmisión',
                'INTERNA: Repositorios corporativos, dentro de red corporativa',
                'CONFIDENCIAL: Cifrado en reposo y tránsito, copias autorizadas',
                'ESTRICTAMENTE CONFIDENCIAL: Cifrado fuerte, destrucción certificada'
            ]
        ],
        'control_iso' => 'A.5.12 - Clasificación de la información'
    ],

    'POL-SI-004' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE USO ACEPTABLE DE RECURSOS TECNOLÓGICOS',
        'version' => '1.0',
        'objetivo' => 'Definir reglas sobre el uso aceptable de recursos tecnológicos corporativos.',
        'alcance' => 'Todos los usuarios de recursos tecnológicos.',
        'contenido' => [
            'USO PERMITIDO' => [
                'Actividades relacionadas con funciones laborales',
                'Uso personal limitado y razonable (máximo 1 hora/día)',
                'Comunicaciones profesionales'
            ],
            'USO PROHIBIDO' => [
                'Descarga o distribución de contenido ilegal, pornografía, apuestas',
                'Software pirata o no licenciado',
                'Hacking o intentos de intrusión',
                'Evasión de controles de seguridad',
                'Spam o acoso'
            ],
            'MONITOREO' => [
                'La organización monitorea uso de recursos tecnológicos',
                'No hay expectativa de privacidad total en recursos corporativos',
                'Sitios visitados son registrados'
            ]
        ],
        'control_iso' => 'A.5.10 - Uso aceptable de los activos'
    ],

    'POL-SI-005' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE GESTIÓN DE ACTIVOS DE INFORMACIÓN',
        'version' => '1.0',
        'objetivo' => 'Identificar, clasificar e inventariar activos de información.',
        'alcance' => 'Todos los activos de información: hardware, software, datos, servicios.',
        'contenido' => [
            'INVENTARIO' => [
                'Mantener inventario completo y actualizado',
                'Registro en sistema centralizado',
                'Actualización ante cambios (máximo 48h)',
                'Revisión trimestral de exactitud'
            ],
            'RESPONSABILIDADES' => [
                'Propietario: Clasificar el activo, definir controles, autorizar accesos',
                'Custodio: Implementar controles técnicos, mantener activo operativo',
                'Usuarios: Usar activos solo para fines autorizados, reportar pérdidas'
            ]
        ],
        'control_iso' => 'A.5.9 - Inventario de activos'
    ],

    'POL-SI-006' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE DESARROLLO SEGURO',
        'version' => '1.0',
        'objetivo' => 'Integrar seguridad en todo el ciclo de vida del desarrollo de software.',
        'alcance' => 'Todo desarrollo de software interno, outsourcing y mantenimiento.',
        'contenido' => [
            'SDLC SEGURO' => [
                'Requisitos: Definir requisitos de seguridad desde el inicio',
                'Diseño: Modelado de amenazas, arquitectura de seguridad',
                'Desarrollo: Code review, análisis estático (SAST)',
                'Pruebas: Análisis dinámico (DAST), penetration testing',
                'Despliegue: Hardening, configuración segura'
            ],
            'ESTÁNDARES DE CODIFICACIÓN' => [
                'Seguir OWASP Secure Coding Practices',
                'Validar TODA entrada de usuario',
                'No almacenar secretos en código',
                'Usar librerías criptográficas estándar'
            ]
        ],
        'control_iso' => 'A.8.25-A.8.29 - Desarrollo seguro'
    ],

    'POL-SI-007' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE GESTIÓN DE CONTRASEÑAS',
        'version' => '1.0',
        'objetivo' => 'Establecer requisitos para creación, uso y almacenamiento seguro de contraseñas.',
        'alcance' => 'Todos los sistemas que requieran autenticación.',
        'contenido' => [
            'REQUISITOS DE COMPLEJIDAD' => [
                'Mínimo 12 caracteres (14 para administradores)',
                'Combinar mayúsculas, minúsculas, números y símbolos',
                'Cambio cada 90 días (60 días para administradores)',
                'No reutilizar últimas 10 contraseñas'
            ],
            'ALMACENAMIENTO' => [
                'PROHIBIDO: Escribir en papel, archivos de texto plano, email',
                'PERMITIDO: Gestor de contraseñas aprobado, bóveda cifrada corporativa'
            ],
            'MFA OBLIGATORIO' => [
                'Accesos remotos (VPN, RDP)',
                'Cuentas administrativas',
                'Sistemas con datos confidenciales'
            ]
        ],
        'control_iso' => 'A.5.17 - Información de autenticación'
    ],

    'POL-SI-008' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE RESPALDO Y RECUPERACIÓN',
        'version' => '1.0',
        'objetivo' => 'Garantizar disponibilidad e integridad mediante respaldos regulares.',
        'alcance' => 'Todos los sistemas críticos y datos de la organización.',
        'contenido' => [
            'FRECUENCIA' => [
                'Datos críticos: Continuo o cada hora, RPO 1h, RTO 4h',
                'Datos importantes: Diario, RPO 24h, RTO 24h',
                'Datos normales: Semanal, RPO 7 días, RTO 72h'
            ],
            'REGLA 3-2-1' => [
                '3 copias de datos',
                '2 tipos de medios diferentes',
                '1 copia offsite'
            ],
            'VERIFICACIÓN' => [
                'Prueba mensual de restauración',
                'Restauración completa trimestral',
                'Documentar tiempos de recuperación'
            ]
        ],
        'control_iso' => 'A.8.13 - Copias de respaldo'
    ],

    'POL-SI-009' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE CRIPTOGRAFÍA',
        'version' => '1.0',
        'objetivo' => 'Definir uso de controles criptográficos para proteger información.',
        'alcance' => 'Toda información clasificada como Confidencial o superior.',
        'contenido' => [
            'CIFRADO EN REPOSO' => [
                'Bases de datos: AES-256, TDE activado',
                'Discos: BitLocker (Windows), FileVault (macOS), LUKS (Linux)',
                'Respaldos: Cifrado obligatorio AES-256'
            ],
            'CIFRADO EN TRÁNSITO' => [
                'HTTPS obligatorio (TLS 1.2 mínimo, TLS 1.3 preferido)',
                'VPN con IPsec o SSL/TLS, cifrado AES-256',
                'SFTP en lugar de FTP, FTPS con TLS 1.2+'
            ],
            'ALGORITMOS APROBADOS' => [
                'Simétricos: AES-256 (preferido), AES-128 (aceptable)',
                'Asimétricos: RSA 4096 bits, ECC Curve25519',
                'Hash: SHA-256 mínimo, SHA-384/512 preferidos',
                'PROHIBIDO: MD5, SHA-1, DES, 3DES, RC4'
            ]
        ],
        'control_iso' => 'A.8.24 - Uso de criptografía'
    ],

    'POL-SI-010' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE SEGURIDAD FÍSICA Y AMBIENTAL',
        'version' => '1.0',
        'objetivo' => 'Proteger instalaciones, equipos e infraestructura.',
        'alcance' => 'Todas las instalaciones, centros de datos y oficinas.',
        'contenido' => [
            'CONTROL DE ACCESO' => [
                'Tarjeta de acceso obligatoria para áreas restringidas',
                'Biometría para centro de datos',
                'Visitantes con gafete y acompañamiento permanente',
                'Videovigilancia 24/7 con grabación 90 días'
            ],
            'CENTRO DE DATOS' => [
                'Aire acondicionado redundante (18-27°C, 45-55% humedad)',
                'UPS con autonomía mínima 30 minutos',
                'Generador eléctrico de respaldo',
                'Sistema de supresión de incendios',
                'Detectores de agua en piso'
            ]
        ],
        'control_iso' => 'A.7.1-A.7.14 - Seguridad física'
    ],

    'POL-SI-011' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE GESTIÓN DE PROVEEDORES',
        'version' => '1.0',
        'objetivo' => 'Asegurar que proveedores cumplan requisitos de seguridad.',
        'alcance' => 'Todos los proveedores que procesan, almacenan o transmiten información.',
        'contenido' => [
            'CICLO DE VIDA' => [
                'Selección: Evaluación de seguridad, verificación de certificaciones',
                'Contratación: NDA, cláusulas de seguridad, SLA, derecho a auditar',
                'Operación: Monitoreo de cumplimiento, auditorías periódicas',
                'Terminación: Devolución/destrucción de información, certificado'
            ],
            'REQUISITOS CRÍTICOS' => [
                'Certificación ISO 27001',
                'SOC 2 Tipo II',
                'Pruebas de penetración anuales',
                'MFA implementado'
            ]
        ],
        'control_iso' => 'A.5.19-A.5.23 - Proveedores'
    ],

    'POL-SI-012' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE SEGURIDAD EN LA NUBE',
        'version' => '1.0',
        'objetivo' => 'Establecer controles para uso de servicios cloud.',
        'alcance' => 'Todos los servicios cloud (SaaS, PaaS, IaaS).',
        'contenido' => [
            'SELECCIÓN DE PROVEEDORES' => [
                'Certificación ISO 27001, SOC 2 Tipo II, CSA STAR',
                'SLA disponibilidad mínimo 99.9%',
                'Data center en jurisdicción aprobada',
                'Portabilidad de datos garantizada'
            ],
            'GESTIÓN DE IDENTIDADES' => [
                'SSO cuando sea soportado',
                'MFA obligatorio para todos los servicios',
                'Cuentas nominales, no compartidas'
            ],
            'PROTECCIÓN DE DATOS' => [
                'Cifrado en tránsito: TLS 1.2+ obligatorio',
                'Cifrado en reposo: Preferir BYOK (Bring Your Own Key)',
                'Respaldos propios en ubicación separada'
            ]
        ],
        'control_iso' => 'A.5.23 - Servicios cloud'
    ],

    'POL-SI-013' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE GESTIÓN DE INCIDENTES',
        'version' => '1.0',
        'objetivo' => 'Gestionar incidentes de seguridad de forma efectiva.',
        'alcance' => 'Todos los incidentes de seguridad de la información.',
        'contenido' => [
            'CLASIFICACIÓN' => [
                'CRÍTICO: Impacto severo, respuesta inmediata (ransomware, fuga masiva)',
                'ALTO: Impacto significativo, respuesta en 1 hora',
                'MEDIO: Impacto limitado, respuesta en 4 horas',
                'BAJO: Impacto mínimo, respuesta en 24 horas'
            ],
            'FASES DE RESPUESTA' => [
                'Detección y Análisis: Confirmar incidente, clasificar, documentar',
                'Contención: Aislar amenaza, preservar evidencia',
                'Erradicación: Eliminar causa raíz, cerrar vulnerabilidades',
                'Recuperación: Restaurar sistemas, validar funcionalidad',
                'Lecciones Aprendidas: Reunión post-incidente, mejoras'
            ]
        ],
        'control_iso' => 'A.5.24-A.5.28 - Gestión de incidentes'
    ],

    'POL-SI-014' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE CONTINUIDAD DEL NEGOCIO',
        'version' => '1.0',
        'objetivo' => 'Asegurar continuidad de operaciones críticas durante incidentes.',
        'alcance' => 'Todos los procesos de negocio y sistemas críticos.',
        'contenido' => [
            'ANÁLISIS DE IMPACTO (BIA)' => [
                'BIA cada 12 meses',
                'Determinar RPO, RTO, MTD por proceso',
                'Identificar dependencias críticas'
            ],
            'ESTRATEGIAS DE RECUPERACIÓN' => [
                'Hot Site: Datacenter alterno activo para sistemas críticos',
                'Warm Site: Infraestructura lista para sistemas importantes',
                'Cold Site: Espacio disponible para sistemas normales'
            ],
            'PRUEBAS' => [
                'Tabletop exercise anual',
                'Prueba técnica semestral',
                'Prueba completa bianual'
            ]
        ],
        'control_iso' => 'A.5.29-A.5.30 - Continuidad'
    ],

    'POL-SI-015' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE PRIVACIDAD Y PROTECCIÓN DE DATOS',
        'version' => '1.0',
        'objetivo' => 'Cumplir leyes de protección de datos personales.',
        'alcance' => 'Todos los datos personales procesados.',
        'contenido' => [
            'PRINCIPIOS' => [
                'Licitud: Base legal válida para tratamiento',
                'Finalidad: Propósitos determinados y explícitos',
                'Minimización: Solo datos necesarios',
                'Exactitud: Mantener datos actualizados',
                'Limitación de conservación: Solo tiempo necesario'
            ],
            'DERECHOS DE TITULARES' => [
                'Acceso: Saber qué datos tenemos (respuesta en 15 días)',
                'Rectificación: Corregir datos inexactos',
                'Cancelación: Solicitar eliminación',
                'Oposición: Oponerse a tratamientos',
                'Portabilidad: Recibir datos en formato estructurado'
            ]
        ],
        'control_iso' => 'A.5.34 - Privacidad'
    ],

    'POL-SI-016' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE TELETRABAJO',
        'version' => '1.0',
        'objetivo' => 'Establecer controles de seguridad para trabajo remoto.',
        'alcance' => 'Empleados que realizan teletrabajo.',
        'contenido' => [
            'EQUIPOS' => [
                'Preferir laptop corporativa con cifrado completo',
                'BYOD solo si autorizado, con contenedor corporativo',
                'Antivirus actualizado, firewall activo'
            ],
            'CONECTIVIDAD' => [
                'VPN corporativa obligatoria',
                'MFA activado',
                'Split tunneling prohibido',
                'No usar WiFi público sin VPN'
            ],
            'SEGURIDAD FÍSICA' => [
                'Espacio de trabajo dedicado',
                'Bloquear pantalla al alejarse',
                'No dejar laptop desatendida',
                'No permitir uso familiar de equipo'
            ]
        ],
        'control_iso' => 'A.6.7 - Trabajo remoto'
    ],

    'POL-SI-017' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE GESTIÓN DE VULNERABILIDADES',
        'version' => '1.0',
        'objetivo' => 'Identificar, evaluar y remediar vulnerabilidades.',
        'alcance' => 'Todos los sistemas, aplicaciones y dispositivos.',
        'contenido' => [
            'ESCANEO' => [
                'Semanal: Sistemas críticos',
                'Mensual: Todos los sistemas',
                'Antes de despliegue: Nuevos sistemas',
                'Pentesting anual'
            ],
            'SLA DE REMEDIACIÓN' => [
                'CRÍTICA (CVSS 9.0-10.0): 24 horas',
                'ALTA (CVSS 7.0-8.9): 7 días',
                'MEDIA (CVSS 4.0-6.9): 30 días',
                'BAJA (CVSS 0.1-3.9): 90 días'
            ]
        ],
        'control_iso' => 'A.8.8 - Vulnerabilidades'
    ],

    'POL-SI-018' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE SEGURIDAD EN REDES',
        'version' => '1.0',
        'objetivo' => 'Proteger las redes de la organización.',
        'alcance' => 'Todas las redes: LAN, WAN, WiFi.',
        'contenido' => [
            'SEGMENTACIÓN' => [
                'DMZ para servicios públicos',
                'Red corporativa (usuarios)',
                'Red de servidores (datacenter)',
                'Red de gestión (administración)',
                'Red de invitados (aislada)'
            ],
            'WIFI' => [
                'Corporativo: WPA3 o WPA2 enterprise, 802.1X',
                'Invitados: SSID separado, red aislada, portal cautivo',
                'Prohibido: WEP, WPA con PSK simple'
            ]
        ],
        'control_iso' => 'A.8.20-A.8.23 - Redes'
    ],

    'POL-SI-019' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE MONITOREO Y LOGGING',
        'version' => '1.0',
        'objetivo' => 'Registrar y monitorear actividades para detectar incidentes.',
        'alcance' => 'Todos los sistemas y aplicaciones.',
        'contenido' => [
            'EVENTOS A REGISTRAR' => [
                'Autenticación: Inicio/cierre de sesión, cambios de contraseña',
                'Autorización: Accesos denegados, cambios en permisos',
                'Sistemas: Cambios de configuración, errores',
                'Seguridad: Detecciones de malware, alertas IDS/IPS'
            ],
            'RETENCIÓN' => [
                'Logs de seguridad: 90 días online, 1 año archivo',
                'Logs operacionales: 30 días online, 180 días archivo',
                'Logs de auditoría: 7 años mínimo'
            ],
            'MONITOREO 24/7' => [
                'SOC monitorea eventos críticos',
                'Alertas automáticas',
                'Respuesta inmediata a críticos'
            ]
        ],
        'control_iso' => 'A.8.15-A.8.16 - Logging'
    ],

    'POL-SI-020' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE GESTIÓN DE CAMBIOS',
        'version' => '1.0',
        'objetivo' => 'Asegurar que cambios sean evaluados y aprobados.',
        'alcance' => 'Todos los cambios en sistemas de producción.',
        'contenido' => [
            'TIPOS DE CAMBIOS' => [
                'Estándar: Pre-aprobados, bajo riesgo',
                'Normal: Requiere RFC y aprobación CAB',
                'Emergencia: Proceso acelerado, documentación retrospectiva'
            ],
            'PROCESO' => [
                'Solicitud: Completar RFC con impacto y riesgos',
                'Evaluación: CAB analiza y prioriza',
                'Aprobación: CAB aprueba o rechaza',
                'Implementación: Ejecutar con plan de rollback',
                'Revisión: Verificar éxito, lecciones aprendidas'
            ]
        ],
        'control_iso' => 'A.8.32 - Gestión de cambios'
    ],

    'POL-SI-021' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE CAPACITACIÓN EN SEGURIDAD',
        'version' => '1.0',
        'objetivo' => 'Asegurar que empleados conozcan sus responsabilidades.',
        'alcance' => 'Todos los empleados y contratistas.',
        'contenido' => [
            'CAPACITACIÓN OBLIGATORIA' => [
                'Inducción (Día 1): Políticas básicas, firma de NDA',
                'Anual: Actualización, amenazas actuales, evaluación (80% aprobación)',
                'Trimestral: Boletines, simulaciones de phishing'
            ],
            'POR ROLES' => [
                'Desarrolladores: Desarrollo seguro OWASP (8h anuales)',
                'Administradores: Hardening, parches (12h anuales)',
                'Gerentes: Riesgos, continuidad (4h anuales)'
            ],
            'SIMULACIONES DE PHISHING' => [
                'Mensual, sin sanciones punitivas',
                'Educación constructiva',
                'Meta: <10% de clic'
            ]
        ],
        'control_iso' => 'A.6.3 - Concientización'
    ],

    'POL-SI-022' => [
        'tipo' => 'politica',
        'categoria' => 'Políticas',
        'titulo' => 'POLÍTICA DE RETENCIÓN Y ELIMINACIÓN',
        'version' => '1.0',
        'objetivo' => 'Definir períodos de retención y métodos de eliminación segura.',
        'alcance' => 'Toda información en cualquier formato.',
        'contenido' => [
            'PERÍODOS DE RETENCIÓN' => [
                'Documentos fiscales: 7 años',
                'Documentos laborales: Duración + 7 años',
                'Datos personales clientes activos: Durante relación',
                'Datos personales clientes inactivos: 2 años',
                'Logs de seguridad: 1 año online, 7 años archivo'
            ],
            'ELIMINACIÓN SEGURA' => [
                'Papel confidencial: Trituradora cruce cortado DIN P-4',
                'Discos duros: Sobrescritura DoD 5220.22-M o destrucción física',
                'SSD: Secure Erase o crypto-erase',
                'Dispositivos móviles: Factory reset + destrucción SIM'
            ]
        ],
        'control_iso' => 'A.5.10 - Eliminación'
    ],

    // ============= 10 PROCEDIMIENTOS =============
    'PROC-GC-001' => [
        'tipo' => 'procedimiento',
        'categoria' => 'Procedimientos',
        'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE CAMBIOS',
        'version' => '1.0',
        'objetivo' => 'Gestionar cambios en sistemas de forma controlada.',
        'alcance' => 'Todos los cambios en producción.',
        'pasos' => [
            '1. SOLICITUD' => ['Completar RFC', 'Describir cambio', 'Justificar necesidad', 'Identificar riesgos'],
            '2. EVALUACIÓN' => ['CAB revisa', 'Análisis de impacto', 'Determinar ventana'],
            '3. APROBACIÓN' => ['Presentación a CAB', 'Aprobación formal'],
            '4. IMPLEMENTACIÓN' => ['Backup pre-cambio', 'Ejecutar plan', 'Validar éxito'],
            '5. CIERRE' => ['Documentar lecciones', 'Actualizar documentación']
        ],
        'control_iso' => 'A.8.32'
    ],

    'PROC-INC-001' => [
        'tipo' => 'procedimiento',
        'categoria' => 'Procedimientos',
        'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE INCIDENTES',
        'version' => '1.0',
        'objetivo' => 'Gestionar incidentes de seguridad efectivamente.',
        'alcance' => 'Todos los incidentes de seguridad.',
        'pasos' => [
            '1. DETECCIÓN' => ['Identificar incidente', 'Reportar a security@', 'Registrar ticket'],
            '2. CLASIFICACIÓN' => ['Determinar severidad', 'Asignar prioridad', 'Asignar responsable'],
            '3. CONTENCIÓN' => ['Aislar sistemas', 'Preservar evidencia', 'Limitar propagación'],
            '4. ERRADICACIÓN' => ['Eliminar causa raíz', 'Cerrar vulnerabilidades'],
            '5. RECUPERACIÓN' => ['Restaurar sistemas', 'Validar funcionalidad'],
            '6. POST-INCIDENTE' => ['Reunión lecciones aprendidas', 'Actualizar procedimientos']
        ],
        'control_iso' => 'A.5.24-A.5.28'
    ],

    'PROC-VUL-001' => [
        'tipo' => 'procedimiento',
        'categoria' => 'Procedimientos',
        'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE VULNERABILIDADES',
        'version' => '1.0',
        'objetivo' => 'Identificar y remediar vulnerabilidades oportunamente.',
        'alcance' => 'Todos los sistemas y aplicaciones.',
        'pasos' => [
            '1. IDENTIFICACIÓN' => ['Escaneo semanal (críticos)', 'Escaneo mensual (todos)', 'Pentesting anual'],
            '2. CLASIFICACIÓN' => ['Crítica: 24h', 'Alta: 7 días', 'Media: 30 días', 'Baja: 90 días'],
            '3. PRIORIZACIÓN' => ['Evaluar exposición', 'Criticidad del activo', 'Existencia de exploit'],
            '4. REMEDIACIÓN' => ['Aplicar parche', 'Probar en QA', 'Implementar en producción'],
            '5. VERIFICACIÓN' => ['Re-escanear', 'Validar cierre', 'Documentar']
        ],
        'control_iso' => 'A.8.8'
    ],

    'PROC-AUD-001' => [
        'tipo' => 'procedimiento',
        'categoria' => 'Procedimientos',
        'titulo' => 'PROCEDIMIENTO DE AUDITORÍAS INTERNAS',
        'version' => '1.0',
        'objetivo' => 'Realizar auditorías para verificar cumplimiento del SGSI.',
        'alcance' => 'Todos los procesos del SGSI.',
        'pasos' => [
            '1. PLANIFICACIÓN' => ['Elaborar plan anual', 'Definir alcance', 'Seleccionar auditores'],
            '2. PREPARACIÓN' => ['Revisar documentación', 'Preparar checklist', 'Notificar auditados'],
            '3. EJECUCIÓN' => ['Reunión apertura', 'Revisión documentos', 'Entrevistas', 'Registrar hallazgos'],
            '4. REPORTE' => ['Elaborar informe', 'Clasificar hallazgos', 'Reunión cierre'],
            '5. SEGUIMIENTO' => ['Plan correctivo', 'Verificar cierre']
        ],
        'control_iso' => 'Cláusula 9.2'
    ],

    'PROC-REV-001' => [
        'tipo' => 'procedimiento',
        'categoria' => 'Procedimientos',
        'titulo' => 'PROCEDIMIENTO DE REVISIÓN POR LA DIRECCIÓN',
        'version' => '1.0',
        'objetivo' => 'Revisar periódicamente el SGSI.',
        'alcance' => 'Todo el SGSI.',
        'pasos' => [
            '1. PREPARACIÓN' => ['Programar reunión trimestral', 'Recopilar información', 'Convocar participantes'],
            '2. ENTRADAS' => ['Auditorías', 'Desempeño', 'Riesgos', 'Retroalimentación', 'Acciones previas'],
            '3. DESARROLLO' => ['Presentación', 'Discusión', 'Toma de decisiones'],
            '4. SALIDAS' => ['Mejoras', 'Cambios necesarios', 'Recursos', 'Acciones'],
            '5. SEGUIMIENTO' => ['Comunicar decisiones', 'Asignar responsables', 'Monitorear']
        ],
        'control_iso' => 'Cláusula 9.3'
    ],

    'PROC-DOC-001' => [
        'tipo' => 'procedimiento',
        'categoria' => 'Procedimientos',
        'titulo' => 'PROCEDIMIENTO DE CONTROL DE DOCUMENTOS',
        'version' => '1.0',
        'objetivo' => 'Controlar documentos del SGSI.',
        'alcance' => 'Todos los documentos del SGSI.',
        'pasos' => [
            '1. CREACIÓN' => ['Usar plantilla', 'Asignar código', 'Completar metadatos'],
            '2. REVISIÓN' => ['Revisión técnica', 'Corrección observaciones'],
            '3. APROBACIÓN' => ['Enviar a aprobador', 'Firma de aprobación'],
            '4. PUBLICACIÓN' => ['Publicar en repositorio', 'Notificar usuarios', 'Retirar versión anterior'],
            '5. CONTROL' => ['Registrar versiones', 'Documentar cambios', 'Archivar obsoletos']
        ],
        'control_iso' => 'Cláusula 7.5'
    ],

    'PROC-REG-001' => [
        'tipo' => 'procedimiento',
        'categoria' => 'Procedimientos',
        'titulo' => 'PROCEDIMIENTO DE CONTROL DE REGISTROS',
        'version' => '1.0',
        'objetivo' => 'Controlar creación, almacenamiento y retención de registros.',
        'alcance' => 'Todos los registros del SGSI.',
        'pasos' => [
            '1. GENERACIÓN' => ['Crear registro', 'Completar campos', 'Firmar si requerido'],
            '2. ALMACENAMIENTO' => ['Guardar en ubicación designada', 'Nomenclatura estándar', 'Backup'],
            '3. PROTECCIÓN' => ['Control de acceso', 'Cifrado si sensible', 'Prevenir alteración'],
            '4. RETENCIÓN' => ['Aplicar tabla de retención', 'Conservar según legal', 'Programar eliminación'],
            '5. DISPOSICIÓN' => ['Destrucción segura', 'Certificar eliminación', 'Documentar']
        ],
        'control_iso' => 'Cláusula 7.5'
    ],

    'PROC-ACT-001' => [
        'tipo' => 'procedimiento',
        'categoria' => 'Procedimientos',
        'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE ACTIVOS',
        'version' => '1.0',
        'objetivo' => 'Identificar e inventariar activos de información.',
        'alcance' => 'Todos los activos de información.',
        'pasos' => [
            '1. IDENTIFICACIÓN' => ['Identificar activos', 'Clasificar por tipo', 'Asignar código'],
            '2. INVENTARIO' => ['Registrar en inventario', 'Asignar propietario y custodio'],
            '3. CLASIFICACIÓN' => ['Determinar clasificación', 'Evaluar valor', 'Identificar dependencias'],
            '4. PROTECCIÓN' => ['Aplicar controles', 'Implementar cifrado', 'Controlar acceso'],
            '5. REVISIÓN' => ['Revisar trimestral', 'Actualizar información', 'Dar de baja obsoletos']
        ],
        'control_iso' => 'A.5.9'
    ],

    'PROC-PER-001' => [
        'tipo' => 'procedimiento',
        'categoria' => 'Procedimientos',
        'titulo' => 'PROCEDIMIENTO DE ALTA Y BAJA DE PERSONAL',
        'version' => '1.0',
        'objetivo' => 'Gestionar ciclo de vida de usuarios.',
        'alcance' => 'Todo el personal.',
        'pasos' => [
            '1. ALTA' => ['Notificación RRHH', 'Crear cuenta', 'Asignar permisos', 'Entregar equipos', 'Capacitación', 'Firma políticas'],
            '2. CAMBIO' => ['Notificación cambio', 'Ajustar permisos', 'Revocar innecesarios'],
            '3. BAJA' => ['Notificación RRHH', 'Revocar accesos', 'Deshabilitar cuentas', 'Recuperar equipos', 'Documentar']
        ],
        'control_iso' => 'A.6.1'
    ],

    'PROC-ACC-001' => [
        'tipo' => 'procedimiento',
        'categoria' => 'Procedimientos',
        'titulo' => 'PROCEDIMIENTO DE GESTIÓN DE ACCESOS',
        'version' => '1.0',
        'objetivo' => 'Controlar acceso lógico a sistemas.',
        'alcance' => 'Todos los sistemas corporativos.',
        'pasos' => [
            '1. SOLICITUD' => ['Formulario', 'Justificación', 'Aprobación gerente'],
            '2. PROVISIÓN' => ['Verificar aprobación', 'Crear/modificar cuenta', 'Configurar MFA', 'Notificar usuario'],
            '3. REVISIÓN' => ['Mensual: privilegiados', 'Trimestral: todos', 'Certificación gerentes'],
            '4. REVOCACIÓN' => ['Por término relación', 'Por inactividad 90 días', 'Documentar']
        ],
        'control_iso' => 'A.5.15-A.5.18'
    ],
];

// CONTINUARÁ con los 53 FORMATOS restantes...
// Por el límite de tamaño, aquí van los primeros formatos de cada categoría

$documentos = array_merge($documentos, [
    // ===== INVENTARIOS (6) =====
    'FO-INV-001' => ['tipo' => 'excel', 'categoria' => 'Inventarios', 'titulo' => 'INVENTARIO DE ACTIVOS DE INFORMACIÓN',
        'columnas' => 'ID|Nombre|Tipo|Clasificación|Propietario|Custodio|Ubicación|Valor|Estado|Fecha'],
    'FO-INV-002' => ['tipo' => 'excel', 'categoria' => 'Inventarios', 'titulo' => 'INVENTARIO DE HARDWARE',
        'columnas' => 'ID|Tipo|Marca|Modelo|Serie|SO|IP|MAC|Usuario|Ubicación|Estado|Compra|Garantía'],
    'FO-INV-003' => ['tipo' => 'excel', 'categoria' => 'Inventarios', 'titulo' => 'INVENTARIO DE SOFTWARE',
        'columnas' => 'ID|Nombre|Versión|Fabricante|Tipo|Licencia|Cantidad|Vencimiento|Costo|Estado'],
    'FO-INV-004' => ['tipo' => 'excel', 'categoria' => 'Inventarios', 'titulo' => 'INVENTARIO DE APLICACIONES',
        'columnas' => 'ID|Nombre|Tipo|Propietario|Responsable|URL|Servidor|BD|Usuarios|Criticidad|Estado'],
    'FO-INV-005' => ['tipo' => 'excel', 'categoria' => 'Inventarios', 'titulo' => 'INVENTARIO DE BASES DE DATOS',
        'columnas' => 'ID|Nombre|Motor|Versión|Servidor|Puerto|DBA|Clasificación|Tamaño|Backup|Cifrado'],
    'FO-INV-006' => ['tipo' => 'excel', 'categoria' => 'Inventarios', 'titulo' => 'INVENTARIO DE USUARIOS Y ACCESOS',
        'columnas' => 'ID|Nombre|Email|Dpto|Cargo|Tipo|Estado|Sistemas|Roles|MFA|Última conexión'],

    // ===== RIESGOS (6) =====
    'FO-RISK-001' => ['tipo' => 'excel', 'categoria' => 'Riesgos', 'titulo' => 'ANÁLISIS DE RIESGOS',
        'columnas' => 'ID|Activo|Amenaza|Vulnerabilidad|Probabilidad|Impacto|Riesgo Inherente|Controles|Riesgo Residual|Tratamiento'],
    'FO-RISK-002' => ['tipo' => 'excel', 'categoria' => 'Riesgos', 'titulo' => 'MATRIZ DE RIESGOS',
        'columnas' => 'ID|Riesgo|Probabilidad|Impacto|Nivel|Clasificación|Estado'],
    'FO-RISK-003' => ['tipo' => 'excel', 'categoria' => 'Riesgos', 'titulo' => 'PLAN DE TRATAMIENTO DE RIESGOS',
        'columnas' => 'ID|Riesgo|Nivel|Tratamiento|Acción|Control ISO|Responsable|Fecha|Presupuesto|Estado|Avance'],
    'FO-RISK-004' => ['tipo' => 'excel', 'categoria' => 'Riesgos', 'titulo' => 'EVALUACIÓN DE RIESGOS POR PROCESO',
        'columnas' => 'Proceso|Responsable|Activos|Amenazas|Vulnerabilidades|Riesgo|Controles|Acción|Prioridad'],
    'FO-RISK-005' => ['tipo' => 'excel', 'categoria' => 'Riesgos', 'titulo' => 'REGISTRO DE RIESGOS IDENTIFICADOS',
        'columnas' => 'ID|Fecha|Identificado por|Categoría|Descripción|Activos|Impacto|Probabilidad|Estado|Asignado'],
    'FO-RISK-006' => ['tipo' => 'excel', 'categoria' => 'Riesgos', 'titulo' => 'ANÁLISIS DE IMPACTO AL NEGOCIO (BIA)',
        'columnas' => 'Proceso|Responsable|Criticidad|Activos|RPO|RTO|MTD|Impacto$/h|Impacto Reputacional|Estrategia'],

    // ===== CONTROLES (5) =====
    'FO-CTRL-001' => ['tipo' => 'excel', 'categoria' => 'Controles', 'titulo' => 'ANEXO A - CONTROLES ISO 27001',
        'columnas' => 'Control|Nombre|Categoría|Aplica|Justificación|Implementado %|Evidencia|Responsable|Estado'],
    'FO-CTRL-002' => ['tipo' => 'excel', 'categoria' => 'Controles', 'titulo' => 'DECLARACIÓN DE APLICABILIDAD (SOA)',
        'columnas' => 'Control|Nombre|Aplica|Justificación|Implementación|Evidencia|Responsable|Estado|%|Riesgos'],
    'FO-CTRL-003' => ['tipo' => 'excel', 'categoria' => 'Controles', 'titulo' => 'EVALUACIÓN DE CONTROLES',
        'columnas' => 'ID|Control|Tipo|Método|Fecha|Evaluador|Diseño|Implementación|Efectividad %|Hallazgos|Acción'],
    'FO-CTRL-004' => ['tipo' => 'excel', 'categoria' => 'Controles', 'titulo' => 'PLAN DE IMPLEMENTACIÓN DE CONTROLES',
        'columnas' => 'ID|Control|Prioridad|Actividades|Responsable|Recursos|Presupuesto|Inicio|Fin|Estado|Avance %'],
    'FO-CTRL-005' => ['tipo' => 'excel', 'categoria' => 'Controles', 'titulo' => 'EFECTIVIDAD DE CONTROLES',
        'columnas' => 'ID|Control|Objetivo|KPI|Meta|Resultado|Cumplimiento %|Tendencia|Período|Efectividad|Acción'],

    // ===== INCIDENTES (5) =====
    'FO-INC-001' => ['tipo' => 'excel', 'categoria' => 'Incidentes', 'titulo' => 'REGISTRO DE INCIDENTES DE SEGURIDAD',
        'columnas' => 'ID|Fecha|Reportado|Tipo|Severidad|Descripción|Sistemas|Usuarios|Estado|Asignado|Resolución|Acciones|Causa|Lecciones'],
    'FO-INC-002' => ['tipo' => 'word', 'categoria' => 'Incidentes', 'titulo' => 'REPORTE DE INCIDENTE DE SEGURIDAD'],
    'FO-INC-003' => ['tipo' => 'word', 'categoria' => 'Incidentes', 'titulo' => 'ANÁLISIS DE CAUSA RAÍZ'],
    'FO-INC-004' => ['tipo' => 'word', 'categoria' => 'Incidentes', 'titulo' => 'PLAN DE RESPUESTA A INCIDENTES'],
    'FO-INC-005' => ['tipo' => 'word', 'categoria' => 'Incidentes', 'titulo' => 'INFORME POST-INCIDENTE'],

    // ===== AUDITORÍAS (6) =====
    'FO-AUD-001' => ['tipo' => 'excel', 'categoria' => 'Auditorías', 'titulo' => 'PLAN DE AUDITORÍAS INTERNAS',
        'columnas' => 'Proceso|Alcance|Auditor|Fecha Planificada|Duración|Estado|Observaciones'],
    'FO-AUD-002' => ['tipo' => 'excel', 'categoria' => 'Auditorías', 'titulo' => 'LISTA DE VERIFICACIÓN DE AUDITORÍA',
        'columnas' => 'Item|Requisito|Control|Evidencia Esperada|Cumple|Hallazgo|Observaciones'],
    'FO-AUD-003' => ['tipo' => 'word', 'categoria' => 'Auditorías', 'titulo' => 'INFORME DE AUDITORÍA INTERNA'],
    'FO-AUD-004' => ['tipo' => 'excel', 'categoria' => 'Auditorías', 'titulo' => 'REGISTRO DE NO CONFORMIDADES',
        'columnas' => 'ID|Fecha|Proceso|Tipo|Descripción|Auditor|Responsable|Causa|Acción Correctiva|Plazo|Estado'],
    'FO-AUD-005' => ['tipo' => 'excel', 'categoria' => 'Auditorías', 'titulo' => 'PLAN DE ACCIONES CORRECTIVAS',
        'columnas' => 'ID NC|Acción|Responsable|Recursos|Fecha Inicio|Fecha Fin|Estado|Avance %|Verificación'],
    'FO-AUD-006' => ['tipo' => 'excel', 'categoria' => 'Auditorías', 'titulo' => 'SEGUIMIENTO DE HALLAZGOS',
        'columnas' => 'ID|Hallazgo|Severidad|Acción|Responsable|Plazo|Estado|Evidencia Cierre|Fecha Cierre'],

    // ===== CAPACITACIÓN (4) =====
    'FO-CAP-001' => ['tipo' => 'excel', 'categoria' => 'Capacitación', 'titulo' => 'PLAN DE CAPACITACIÓN EN SEGURIDAD',
        'columnas' => 'Curso|Objetivo|Dirigido a|Modalidad|Duración|Proveedor|Fecha|Asistentes|Costo'],
    'FO-CAP-002' => ['tipo' => 'excel', 'categoria' => 'Capacitación', 'titulo' => 'REGISTRO DE ASISTENCIA A CAPACITACIONES',
        'columnas' => 'Nombre|Cargo|Dpto|Curso|Fecha|Duración|Asistió|Evaluación|Aprobado|Certificado'],
    'FO-CAP-003' => ['tipo' => 'excel', 'categoria' => 'Capacitación', 'titulo' => 'EVALUACIÓN DE EFECTIVIDAD',
        'columnas' => 'Curso|Fecha|Instructor|Asistentes|Eval Promedio|Aprobados %|Efectividad|Comentarios|Mejoras'],
    'FO-CAP-004' => ['tipo' => 'word', 'categoria' => 'Capacitación', 'titulo' => 'PROGRAMA DE CONCIENTIZACIÓN'],

    // ===== MONITOREO (5) =====
    'FO-MON-001' => ['tipo' => 'excel', 'categoria' => 'Monitoreo', 'titulo' => 'INDICADORES DE DESEMPEÑO (KPI)',
        'columnas' => 'KPI|Descripción|Fórmula|Meta|Frecuencia|Responsable|Ene|Feb|Mar|Abr|May|Jun|Jul|Ago|Sep|Oct|Nov|Dic'],
    'FO-MON-002' => ['tipo' => 'excel', 'categoria' => 'Monitoreo', 'titulo' => 'DASHBOARD DE SEGURIDAD',
        'columnas' => 'Métrica|Valor Actual|Objetivo|Estado|Tendencia|Última Actualización'],
    'FO-MON-003' => ['tipo' => 'excel', 'categoria' => 'Monitoreo', 'titulo' => 'MÉTRICAS DE SEGURIDAD',
        'columnas' => 'Categoría|Métrica|Unidad|Ene|Feb|Mar|Abr|May|Jun|Jul|Ago|Sep|Oct|Nov|Dic|Promedio'],
    'FO-MON-004' => ['tipo' => 'word', 'categoria' => 'Monitoreo', 'titulo' => 'INFORME MENSUAL DE SEGURIDAD'],
    'FO-MON-005' => ['tipo' => 'word', 'categoria' => 'Monitoreo', 'titulo' => 'REVISIÓN POR LA DIRECCIÓN'],

    // ===== CONTINUIDAD (5) =====
    'FO-CONT-001' => ['tipo' => 'word', 'categoria' => 'Continuidad', 'titulo' => 'PLAN DE CONTINUIDAD DEL NEGOCIO (BCP)'],
    'FO-CONT-002' => ['tipo' => 'word', 'categoria' => 'Continuidad', 'titulo' => 'PLAN DE RECUPERACIÓN DE DESASTRES (DRP)'],
    'FO-CONT-003' => ['tipo' => 'word', 'categoria' => 'Continuidad', 'titulo' => 'PROCEDIMIENTO DE RESPALDO DE DATOS'],
    'FO-CONT-004' => ['tipo' => 'excel', 'categoria' => 'Continuidad', 'titulo' => 'PRUEBA DE CONTINUIDAD',
        'columnas' => 'Prueba|Tipo|Alcance|Fecha|Participantes|Resultado|Tiempo Recuperación|Hallazgos|Mejoras'],
    'FO-CONT-005' => ['tipo' => 'excel', 'categoria' => 'Continuidad', 'titulo' => 'REGISTRO DE RESPALDOS',
        'columnas' => 'Fecha|Sistema|Tipo|Tamaño|Duración|Estado|Ubicación|Verificado|Observaciones'],

    // ===== PROVEEDORES (4) =====
    'FO-PROV-001' => ['tipo' => 'excel', 'categoria' => 'Proveedores', 'titulo' => 'EVALUACIÓN DE PROVEEDORES',
        'columnas' => 'Proveedor|Servicio|Criticidad|ISO 27001|SOC 2|Puntaje Seguridad|Aprobado|Revisión'],
    'FO-PROV-002' => ['tipo' => 'word', 'categoria' => 'Proveedores', 'titulo' => 'ACUERDO DE CONFIDENCIALIDAD (NDA)'],
    'FO-PROV-003' => ['tipo' => 'word', 'categoria' => 'Proveedores', 'titulo' => 'ACUERDO DE NIVEL DE SERVICIO (SLA)'],
    'FO-PROV-004' => ['tipo' => 'excel', 'categoria' => 'Proveedores', 'titulo' => 'CUESTIONARIO DE SEGURIDAD PARA PROVEEDORES',
        'columnas' => 'Pregunta|Categoría|Respuesta|Evidencia|Evaluación|Cumple'],

    // ===== CERTIFICACIÓN (7) =====
    'REP-CERT-001' => ['tipo' => 'word', 'categoria' => 'Certificación', 'titulo' => 'MANUAL DEL SGSI'],
    'REP-CERT-002' => ['tipo' => 'word', 'categoria' => 'Certificación', 'titulo' => 'ALCANCE Y LÍMITES DEL SGSI'],
    'REP-CERT-003' => ['tipo' => 'word', 'categoria' => 'Certificación', 'titulo' => 'CONTEXTO DE LA ORGANIZACIÓN'],
    'REP-CERT-004' => ['tipo' => 'excel', 'categoria' => 'Certificación', 'titulo' => 'PARTES INTERESADAS Y REQUISITOS',
        'columnas' => 'Parte Interesada|Tipo|Necesidades|Expectativas|Requisitos|Impacto|Gestión'],
    'REP-CERT-005' => ['tipo' => 'excel', 'categoria' => 'Certificación', 'titulo' => 'OBJETIVOS DE SEGURIDAD',
        'columnas' => 'Objetivo|Indicador|Meta|Responsable|Plazo|Recursos|Estado|Avance %'],
    'REP-CERT-006' => ['tipo' => 'word', 'categoria' => 'Certificación', 'titulo' => 'ORGANIGRAMA DEL SGSI'],
    'REP-CERT-007' => ['tipo' => 'excel', 'categoria' => 'Certificación', 'titulo' => 'ROLES Y RESPONSABILIDADES',
        'columnas' => 'Rol|Responsable|Funciones|Autoridad|Reporta a|Requisitos'],
]);

// ===================================================
// FUNCIONES DE GENERACIÓN - SIN DEPENDENCIAS
// ===================================================

function generarHTML($doc, $format) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($doc['titulo']) . '</title>
    <style>
        body { font-family: Arial, Calibri, sans-serif; font-size: 11pt; margin: 40px; }
        h1 { color: #0066CC; text-align: center; border-bottom: 3px solid #0066CC; padding-bottom: 10px; }
        h2 { color: #0066CC; border-bottom: 2px solid #0066CC; padding-bottom: 5px; margin-top: 20px; }
        h3 { color: #333; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background-color: #0066CC; color: white; font-weight: bold; }
        .header-table { background-color: #0066CC; color: white; }
        .info-label { background-color: #E6F2FF; font-weight: bold; width: 30%; }
        ul { margin: 5px 0; padding-left: 25px; }
        li { margin: 3px 0; }
    </style>
</head>
<body>';

    // Encabezado
    $html .= '<table>
        <tr class="header-table">
            <td colspan="4" style="text-align: center; font-size: 14pt; font-weight: bold;">
                AUDITOR PRO - ISO 27001:2022
            </td>
        </tr>
        <tr>
            <td class="info-label">Código:</td>
            <td>' . htmlspecialchars($_GET['code']) . '</td>
            <td class="info-label">Versión:</td>
            <td>' . htmlspecialchars($doc['version']) . '</td>
        </tr>
        <tr>
            <td class="info-label">Categoría:</td>
            <td>' . htmlspecialchars($doc['categoria']) . '</td>
            <td class="info-label">Fecha:</td>
            <td>' . date('d/m/Y') . '</td>
        </tr>
    </table>';

    $html .= '<h1>' . htmlspecialchars($doc['titulo']) . '</h1>';

    // Contenido según tipo
    if ($doc['tipo'] == 'politica' || $doc['tipo'] == 'procedimiento') {
        $html .= '<h2>OBJETIVO</h2>';
        $html .= '<p>' . htmlspecialchars($doc['objetivo']) . '</p>';

        $html .= '<h2>ALCANCE</h2>';
        $html .= '<p>' . htmlspecialchars($doc['alcance']) . '</p>';

        $html .= '<h2>CONTENIDO</h2>';

        if (isset($doc['contenido'])) {
            foreach ($doc['contenido'] as $titulo => $items) {
                $html .= '<h3>' . htmlspecialchars($titulo) . '</h3><ul>';
                foreach ($items as $item) {
                    $html .= '<li>' . htmlspecialchars($item) . '</li>';
                }
                $html .= '</ul>';
            }
        }

        if (isset($doc['pasos'])) {
            foreach ($doc['pasos'] as $titulo => $items) {
                $html .= '<h3>' . htmlspecialchars($titulo) . '</h3><ul>';
                foreach ($items as $item) {
                    $html .= '<li>' . htmlspecialchars($item) . '</li>';
                }
                $html .= '</ul>';
            }
        }

        if (isset($doc['control_iso'])) {
            $html .= '<h2>CONTROL ISO 27001:2022</h2>';
            $html .= '<p>' . htmlspecialchars($doc['control_iso']) . '</p>';
        }
    } elseif ($doc['tipo'] == 'excel' && isset($doc['columnas'])) {
        // Formato Excel con columnas
        $cols = explode('|', $doc['columnas']);
        $html .= '<table><thead><tr>';
        foreach ($cols as $col) {
            $html .= '<th>' . htmlspecialchars($col) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        // Agregar 10 filas vacías para ejemplo
        for ($i = 0; $i < 10; $i++) {
            $html .= '<tr>';
            foreach ($cols as $col) {
                $html .= '<td>&nbsp;</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
    } else {
        // Documento Word genérico
        $html .= '<p>Documento: ' . htmlspecialchars($doc['titulo']) . '</p>';
        $html .= '<p>Este es un documento plantilla que debe ser completado según las necesidades de su organización.</p>';
    }

    // Tabla de firmas
    $html .= '<div style="page-break-before: always;"></div>';
    $html .= '<h2>CONTROL DE APROBACIONES</h2>';
    $html .= '<table>
        <tr><th>ROL</th><th>NOMBRE</th><th>FIRMA</th><th>FECHA</th></tr>
        <tr><td>Elaborado por</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>Revisado por</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>Aprobado por</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
    </table>';

    $html .= '</body></html>';

    return $html;
}

// ===================================================
// EJECUCIÓN
// ===================================================

if (!isset($documentos[$code])) {
    die('Documento no encontrado: ' . $code);
}

$doc = $documentos[$code];
$html = generarHTML($doc, $format);

// Determinar tipo de descarga
if ($doc['tipo'] == 'excel' || $format == 'xlsx') {
    // Descargar como Excel (HTML que Excel puede abrir)
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $code . '_' . date('Ymd') . '.xls"');
} else {
    // Descargar como Word (HTML que Word puede abrir)
    header('Content-Type: application/msword; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $code . '_' . date('Ymd') . '.doc"');
}

header('Cache-Control: max-age=0');
echo "\xEF\xBB\xBF"; // UTF-8 BOM
echo $html;
exit;
