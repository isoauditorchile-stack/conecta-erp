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

$message = '';
$message_type = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_control') {
            $stmt = $conn->prepare("UPDATE iso27001_controls SET applicability = ?, justification = ?, implementation_status = ?, implementation_level = ?, responsible = ?, implementation_date = ?, next_review = ?, evidence = ?, comments = ?, updated_by = ?, updated_date = NOW() WHERE id = ? AND company_id = ?");

            $stmt->bind_param("ssssssssiii",
                $_POST['applicability'],
                $_POST['justification'],
                $_POST['implementation_status'],
                $_POST['implementation_level'],
                $_POST['responsible'],
                $_POST['implementation_date'],
                $_POST['next_review'],
                $_POST['evidence'],
                $_POST['comments'],
                $user_id,
                $_POST['control_id'],
                $company_id
            );

            if ($stmt->execute()) {
                $message = 'Control actualizado exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al actualizar control: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// Obtener filtros
$filter_domain = isset($_GET['filter_domain']) ? $_GET['filter_domain'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_applicability = isset($_GET['filter_applicability']) ? $_GET['filter_applicability'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construir query con filtros
$query = "SELECT * FROM iso27001_controls WHERE company_id = ?";
$params = [$company_id];
$types = "i";

if ($filter_domain) {
    $query .= " AND control_domain = ?";
    $params[] = $filter_domain;
    $types .= "s";
}

if ($filter_status) {
    $query .= " AND implementation_status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_applicability) {
    $query .= " AND applicability = ?";
    $params[] = $filter_applicability;
    $types .= "s";
}

if ($search) {
    $query .= " AND (control_id LIKE ? OR control_name LIKE ? OR control_description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$query .= " ORDER BY control_domain, control_id ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$controls_result = $stmt->get_result();
$stmt->close();

// Obtener estadisticas
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_controls WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$total_controls = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_controls WHERE company_id = ? AND implementation_status = 'implementado'");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$implemented_controls = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_controls WHERE company_id = ? AND applicability = 'Aplicable'");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$applicable_controls = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$compliance_percentage = $applicable_controls > 0 ? round(($implemented_controls / $applicable_controls) * 100, 2) : 0;

// Anexo A - Controles ISO 27001:2022
$anexo_a_controls = [
    'A.5' => [
        'name' => 'Controles Organizacionales',
        'controls' => [
            ['id' => 'A.5.1', 'name' => 'Politicas de seguridad de la informacion', 'description' => 'Se debe definir, aprobar, comunicar y revisar periodicamente un conjunto de politicas de seguridad de la informacion.'],
            ['id' => 'A.5.2', 'name' => 'Roles y responsabilidades de seguridad de la informacion', 'description' => 'Se deben definir y asignar las responsabilidades de seguridad de la informacion.'],
            ['id' => 'A.5.3', 'name' => 'Segregacion de funciones', 'description' => 'Los deberes y areas de responsabilidad en conflicto deben estar segregados.'],
            ['id' => 'A.5.4', 'name' => 'Responsabilidades de la direccion', 'description' => 'La direccion debe requerir que todo el personal y partes externas apliquen la seguridad de acuerdo con las politicas y procedimientos establecidos.'],
            ['id' => 'A.5.5', 'name' => 'Contacto con autoridades', 'description' => 'Se deben mantener contactos apropiados con autoridades relevantes.'],
            ['id' => 'A.5.6', 'name' => 'Contacto con grupos de interes especial', 'description' => 'Se deben mantener contactos apropiados con grupos de interes especial y foros de seguridad.'],
            ['id' => 'A.5.7', 'name' => 'Inteligencia de amenazas', 'description' => 'Se debe recopilar y analizar informacion sobre amenazas de seguridad de la informacion.'],
            ['id' => 'A.5.8', 'name' => 'Seguridad de la informacion en la gestion de proyectos', 'description' => 'La seguridad de la informacion debe integrarse en la gestion de proyectos.'],
            ['id' => 'A.5.9', 'name' => 'Inventario de informacion y otros activos asociados', 'description' => 'Se debe desarrollar y mantener un inventario de informacion y otros activos asociados.'],
            ['id' => 'A.5.10', 'name' => 'Uso aceptable de la informacion y otros activos asociados', 'description' => 'Se deben identificar, documentar e implementar reglas para el uso aceptable de informacion y activos.'],
            ['id' => 'A.5.11', 'name' => 'Devolucion de activos', 'description' => 'El personal y las partes externas deben devolver todos los activos de la organizacion en su posesion al terminar su empleo, contrato o acuerdo.'],
            ['id' => 'A.5.12', 'name' => 'Clasificacion de la informacion', 'description' => 'La informacion debe clasificarse de acuerdo con las necesidades de seguridad de la informacion.'],
            ['id' => 'A.5.13', 'name' => 'Etiquetado de la informacion', 'description' => 'Se debe desarrollar e implementar un conjunto apropiado de procedimientos para el etiquetado de la informacion.'],
            ['id' => 'A.5.14', 'name' => 'Transferencia de informacion', 'description' => 'Se deben aplicar reglas, procedimientos o acuerdos formales de transferencia de informacion.'],
            ['id' => 'A.5.15', 'name' => 'Control de acceso', 'description' => 'Se deben establecer e implementar reglas para controlar el acceso fisico y logico a la informacion.'],
            ['id' => 'A.5.16', 'name' => 'Gestion de identidades', 'description' => 'Se debe gestionar el ciclo de vida completo de las identidades.'],
            ['id' => 'A.5.17', 'name' => 'Informacion de autenticacion', 'description' => 'La asignacion y gestion de informacion de autenticacion debe controlarse mediante un proceso de gestion.'],
            ['id' => 'A.5.18', 'name' => 'Derechos de acceso', 'description' => 'Los derechos de acceso a la informacion y otros activos asociados deben proporcionarse, revisarse, modificarse y eliminarse.'],
            ['id' => 'A.5.19', 'name' => 'Seguridad de la informacion en las relaciones con proveedores', 'description' => 'Se deben definir e implementar procesos y procedimientos para gestionar los riesgos de seguridad.'],
            ['id' => 'A.5.20', 'name' => 'Abordar la seguridad de la informacion en acuerdos con proveedores', 'description' => 'Los requisitos relevantes de seguridad de la informacion deben establecerse y acordarse con cada proveedor.'],
            ['id' => 'A.5.21', 'name' => 'Gestion de la seguridad de la informacion en la cadena de suministro de TIC', 'description' => 'Se deben definir e implementar procesos y procedimientos para gestionar los riesgos asociados.'],
            ['id' => 'A.5.22', 'name' => 'Monitoreo, revision y gestion de cambios de servicios de proveedores', 'description' => 'La organizacion debe monitorear, revisar y auditar periodicamente el cambio de provision de servicios del proveedor.'],
            ['id' => 'A.5.23', 'name' => 'Seguridad de la informacion para el uso de servicios en la nube', 'description' => 'Los procesos de adquisicion, uso, gestion y salida de servicios en la nube deben establecerse.'],
            ['id' => 'A.5.24', 'name' => 'Planificacion y preparacion de la gestion de incidentes de seguridad de la informacion', 'description' => 'La organizacion debe planificar y prepararse para gestionar incidentes de seguridad.'],
            ['id' => 'A.5.25', 'name' => 'Evaluacion y decision sobre eventos de seguridad de la informacion', 'description' => 'La organizacion debe evaluar los eventos de seguridad y decidir si se categorizan como incidentes.'],
            ['id' => 'A.5.26', 'name' => 'Respuesta a incidentes de seguridad de la informacion', 'description' => 'Se debe responder a los incidentes de seguridad de acuerdo con procedimientos documentados.'],
            ['id' => 'A.5.27', 'name' => 'Aprender de los incidentes de seguridad de la informacion', 'description' => 'El conocimiento obtenido de los incidentes debe usarse para fortalecer la seguridad.'],
            ['id' => 'A.5.28', 'name' => 'Recopilacion de evidencias', 'description' => 'La organizacion debe establecer procedimientos para la identificacion, recopilacion y preservacion de evidencias.'],
            ['id' => 'A.5.29', 'name' => 'Seguridad de la informacion durante la interrupcion', 'description' => 'La organizacion debe planificar como mantener la seguridad de la informacion durante la interrupcion.'],
            ['id' => 'A.5.30', 'name' => 'Preparacion de las TIC para la continuidad del negocio', 'description' => 'La preparacion de las TIC debe planificarse, implementarse, mantenerse y probarse.'],
            ['id' => 'A.5.31', 'name' => 'Requisitos legales, estatutarios, reglamentarios y contractuales', 'description' => 'Los requisitos de seguridad legales, estatutarios, reglamentarios y contractuales deben identificarse.'],
            ['id' => 'A.5.32', 'name' => 'Derechos de propiedad intelectual', 'description' => 'La organizacion debe implementar procedimientos apropiados para proteger los derechos de propiedad intelectual.'],
            ['id' => 'A.5.33', 'name' => 'Proteccion de registros', 'description' => 'Los registros deben protegerse contra perdida, destruccion, falsificacion, acceso no autorizado y divulgacion.'],
            ['id' => 'A.5.34', 'name' => 'Privacidad y proteccion de informacion de identificacion personal', 'description' => 'La organizacion debe identificar y cumplir con los requisitos de privacidad y proteccion de PII.'],
            ['id' => 'A.5.35', 'name' => 'Revision independiente de la seguridad de la informacion', 'description' => 'El enfoque de gestion de seguridad de la informacion debe revisarse independientemente a intervalos planificados.'],
            ['id' => 'A.5.36', 'name' => 'Cumplimiento con politicas, reglas y estandares de seguridad de la informacion', 'description' => 'El cumplimiento con las politicas, reglas y estandares debe revisarse periodicamente.'],
            ['id' => 'A.5.37', 'name' => 'Procedimientos operativos documentados', 'description' => 'Los procedimientos operativos para instalaciones de procesamiento de informacion deben documentarse y estar disponibles.']
        ]
    ],
    'A.6' => [
        'name' => 'Controles de Personas',
        'controls' => [
            ['id' => 'A.6.1', 'name' => 'Seleccion', 'description' => 'Las verificaciones de antecedentes de todos los candidatos deben realizarse antes del empleo.'],
            ['id' => 'A.6.2', 'name' => 'Terminos y condiciones de empleo', 'description' => 'Los acuerdos contractuales deben establecer las responsabilidades del personal y de la organizacion.'],
            ['id' => 'A.6.3', 'name' => 'Conciencia, educacion y capacitacion en seguridad de la informacion', 'description' => 'El personal de la organizacion y las partes interesadas relevantes deben recibir educacion y capacitacion adecuadas.'],
            ['id' => 'A.6.4', 'name' => 'Proceso disciplinario', 'description' => 'Debe haber un proceso disciplinario formal y comunicado para tomar medidas contra el personal que haya cometido una violacion.'],
            ['id' => 'A.6.5', 'name' => 'Responsabilidades despues de la terminacion o cambio de empleo', 'description' => 'Las responsabilidades y deberes de seguridad de la informacion que permanecen validos despues de la terminacion deben definirse.'],
            ['id' => 'A.6.6', 'name' => 'Acuerdos de confidencialidad o no divulgacion', 'description' => 'Los acuerdos de confidencialidad o no divulgacion deben identificarse, documentarse y revisarse periodicamente.'],
            ['id' => 'A.6.7', 'name' => 'Trabajo remoto', 'description' => 'Se deben implementar medidas de seguridad cuando el personal trabaja remotamente.'],
            ['id' => 'A.6.8', 'name' => 'Reporte de eventos de seguridad de la informacion', 'description' => 'La organizacion debe proporcionar un mecanismo para que el personal reporte eventos de seguridad observados o sospechados.']
        ]
    ],
    'A.7' => [
        'name' => 'Controles Fisicos',
        'controls' => [
            ['id' => 'A.7.1', 'name' => 'Perimetros de seguridad fisica', 'description' => 'Se deben definir y usar perimetros de seguridad para proteger areas que contienen informacion y otros activos asociados.'],
            ['id' => 'A.7.2', 'name' => 'Controles de entrada fisica', 'description' => 'Las areas seguras deben protegerse mediante controles de entrada apropiados.'],
            ['id' => 'A.7.3', 'name' => 'Seguridad de oficinas, habitaciones e instalaciones', 'description' => 'Se debe disenar e implementar seguridad fisica para oficinas, habitaciones e instalaciones.'],
            ['id' => 'A.7.4', 'name' => 'Monitoreo de seguridad fisica', 'description' => 'Las instalaciones deben monitorearse continuamente contra acceso fisico no autorizado.'],
            ['id' => 'A.7.5', 'name' => 'Proteccion contra amenazas fisicas y ambientales', 'description' => 'Se debe disenar e implementar proteccion contra amenazas fisicas y ambientales.'],
            ['id' => 'A.7.6', 'name' => 'Trabajo en areas seguras', 'description' => 'Se deben disenar e implementar medidas de seguridad para trabajar en areas seguras.'],
            ['id' => 'A.7.7', 'name' => 'Escritorio limpio y pantalla limpia', 'description' => 'Se deben definir e implementar reglas de escritorio limpio y pantalla limpia.'],
            ['id' => 'A.7.8', 'name' => 'Ubicacion y proteccion de equipos', 'description' => 'Los equipos deben ubicarse y protegerse de manera segura.'],
            ['id' => 'A.7.9', 'name' => 'Seguridad de activos fuera de las instalaciones', 'description' => 'Los activos fuera de las instalaciones deben protegerse.'],
            ['id' => 'A.7.10', 'name' => 'Medios de almacenamiento', 'description' => 'Los medios de almacenamiento deben gestionarse a lo largo de su ciclo de vida.'],
            ['id' => 'A.7.11', 'name' => 'Utilidades de soporte', 'description' => 'Las instalaciones de procesamiento de informacion deben protegerse contra fallas de energia y otras interrupciones.'],
            ['id' => 'A.7.12', 'name' => 'Seguridad del cableado', 'description' => 'Los cables que transportan energia, datos o servicios de informacion deben protegerse contra interceptacion, interferencia o dano.'],
            ['id' => 'A.7.13', 'name' => 'Mantenimiento de equipos', 'description' => 'Los equipos deben mantenerse correctamente para asegurar disponibilidad, integridad y confidencialidad.'],
            ['id' => 'A.7.14', 'name' => 'Eliminacion o reutilizacion segura de equipos', 'description' => 'Los elementos de equipo que contienen medios de almacenamiento deben verificarse para asegurar que se eliminen o sobrescriban.']
        ]
    ],
    'A.8' => [
        'name' => 'Controles Tecnologicos',
        'controls' => [
            ['id' => 'A.8.1', 'name' => 'Dispositivos de punto final de usuario', 'description' => 'La informacion almacenada en, procesada por o accesible a traves de dispositivos de punto final de usuario debe protegerse.'],
            ['id' => 'A.8.2', 'name' => 'Derechos de acceso privilegiado', 'description' => 'La asignacion y uso de derechos de acceso privilegiado debe restringirse y gestionarse.'],
            ['id' => 'A.8.3', 'name' => 'Restriccion de acceso a la informacion', 'description' => 'El acceso a la informacion y otros activos asociados debe restringirse de acuerdo con la politica de control de acceso.'],
            ['id' => 'A.8.4', 'name' => 'Acceso al codigo fuente', 'description' => 'El acceso de lectura y escritura al codigo fuente, herramientas de desarrollo y bibliotecas de software debe gestionarse adecuadamente.'],
            ['id' => 'A.8.5', 'name' => 'Autenticacion segura', 'description' => 'Las tecnologias y procedimientos de autenticacion segura deben implementarse basandose en restricciones de acceso.'],
            ['id' => 'A.8.6', 'name' => 'Gestion de capacidad', 'description' => 'El uso de recursos debe monitorearse y ajustarse de acuerdo con los requisitos actuales y proyectados de capacidad.'],
            ['id' => 'A.8.7', 'name' => 'Proteccion contra malware', 'description' => 'Se debe implementar proteccion contra malware y los usuarios deben ser conscientes.'],
            ['id' => 'A.8.8', 'name' => 'Gestion de vulnerabilidades tecnicas', 'description' => 'Se debe obtener informacion sobre vulnerabilidades tecnicas, evaluar la exposicion y tomar medidas apropiadas.'],
            ['id' => 'A.8.9', 'name' => 'Gestion de configuracion', 'description' => 'Se deben establecer, documentar, implementar, monitorear y revisar configuraciones de seguridad.'],
            ['id' => 'A.8.10', 'name' => 'Eliminacion de informacion', 'description' => 'La informacion almacenada en sistemas de informacion, dispositivos o cualquier otro medio de almacenamiento debe eliminarse cuando ya no se requiera.'],
            ['id' => 'A.8.11', 'name' => 'Enmascaramiento de datos', 'description' => 'El enmascaramiento de datos debe usarse de acuerdo con la politica de control de acceso de la organizacion.'],
            ['id' => 'A.8.12', 'name' => 'Prevencion de fuga de datos', 'description' => 'Se deben aplicar medidas de prevencion de fuga de datos a sistemas, redes y otros dispositivos.'],
            ['id' => 'A.8.13', 'name' => 'Respaldo de informacion', 'description' => 'Se deben mantener copias de respaldo de informacion, software e imagenes del sistema.'],
            ['id' => 'A.8.14', 'name' => 'Redundancia de instalaciones de procesamiento de informacion', 'description' => 'Las instalaciones de procesamiento de informacion deben implementarse con redundancia suficiente.'],
            ['id' => 'A.8.15', 'name' => 'Registro', 'description' => 'Los registros que registran actividades, excepciones, fallas y otros eventos relevantes deben producirse, almacenarse y protegerse.'],
            ['id' => 'A.8.16', 'name' => 'Actividades de monitoreo', 'description' => 'Las redes, sistemas y aplicaciones deben monitorearse para comportamiento anomalo y se deben tomar acciones apropiadas.'],
            ['id' => 'A.8.17', 'name' => 'Sincronizacion de relojes', 'description' => 'Los relojes de sistemas de procesamiento de informacion deben sincronizarse con fuentes de tiempo aprobadas.'],
            ['id' => 'A.8.18', 'name' => 'Uso de programas utilitarios privilegiados', 'description' => 'El uso de programas utilitarios que pueden anular controles debe restringirse y controlarse estrechamente.'],
            ['id' => 'A.8.19', 'name' => 'Instalacion de software en sistemas operativos', 'description' => 'Se deben implementar procedimientos y medidas para gestionar de forma segura la instalacion de software.'],
            ['id' => 'A.8.20', 'name' => 'Seguridad de redes', 'description' => 'Las redes y los dispositivos de red deben asegurarse, gestionarse y controlarse.'],
            ['id' => 'A.8.21', 'name' => 'Seguridad de servicios de red', 'description' => 'Los mecanismos de seguridad, niveles de servicio y requisitos de servicio deben identificarse e incluirse en acuerdos.'],
            ['id' => 'A.8.22', 'name' => 'Segregacion de redes', 'description' => 'Los grupos de servicios de informacion, usuarios y sistemas de informacion deben segregarse en redes.'],
            ['id' => 'A.8.23', 'name' => 'Filtrado web', 'description' => 'El acceso a sitios web externos debe gestionarse para reducir la exposicion a contenido malicioso.'],
            ['id' => 'A.8.24', 'name' => 'Uso de criptografia', 'description' => 'Se deben definir e implementar reglas para el uso efectivo de criptografia.'],
            ['id' => 'A.8.25', 'name' => 'Ciclo de vida de desarrollo seguro', 'description' => 'Se deben establecer e implementar reglas para el desarrollo seguro de software y sistemas.'],
            ['id' => 'A.8.26', 'name' => 'Requisitos de seguridad de aplicaciones', 'description' => 'Los requisitos de seguridad de la informacion deben identificarse, especificarse y aprobarse.'],
            ['id' => 'A.8.27', 'name' => 'Principios de arquitectura e ingenieria de sistemas seguros', 'description' => 'Los principios para ingenieria de sistemas seguros deben establecerse, documentarse e implementarse.'],
            ['id' => 'A.8.28', 'name' => 'Codificacion segura', 'description' => 'Los principios de codificacion segura deben aplicarse al desarrollo de software.'],
            ['id' => 'A.8.29', 'name' => 'Pruebas de seguridad en desarrollo y aceptacion', 'description' => 'Los procesos de prueba de seguridad deben definirse y implementarse en el ciclo de vida de desarrollo.'],
            ['id' => 'A.8.30', 'name' => 'Desarrollo externalizado', 'description' => 'La organizacion debe dirigir, monitorear y revisar las actividades relacionadas con el desarrollo externalizado.'],
            ['id' => 'A.8.31', 'name' => 'Separacion de entornos de desarrollo, prueba y produccion', 'description' => 'Los entornos de desarrollo, prueba y produccion deben separarse y asegurarse.'],
            ['id' => 'A.8.32', 'name' => 'Gestion de cambios', 'description' => 'Los cambios en instalaciones y sistemas de procesamiento de informacion deben estar sujetos a procedimientos de gestion de cambios.'],
            ['id' => 'A.8.33', 'name' => 'Informacion de prueba', 'description' => 'La informacion de prueba debe seleccionarse, protegerse y gestionarse cuidadosamente.'],
            ['id' => 'A.8.34', 'name' => 'Proteccion de sistemas de informacion durante pruebas de auditoria', 'description' => 'Las pruebas de auditoria y otras actividades de aseguramiento en sistemas operativos deben planificarse y acordarse.']
        ]
    ]
];

// Textos multiidioma
$texts = [
    'es' => [
        'title' => 'Controles de Seguridad - Anexo A',
        'subtitle' => 'ISO 27001:2022 - Evaluacion y Seguimiento de Controles',
        'total_controls' => 'Total de Controles',
        'implemented' => 'Implementados',
        'applicable' => 'Aplicables',
        'compliance' => 'Cumplimiento',
        'domain' => 'Dominio',
        'status' => 'Estado',
        'applicability' => 'Aplicabilidad',
        'all' => 'Todos',
        'filter' => 'Filtrar',
        'search' => 'Buscar',
        'control_id' => 'ID Control',
        'control_name' => 'Nombre del Control',
        'implementation_status' => 'Estado de Implementacion',
        'implementation_level' => 'Nivel de Implementacion',
        'responsible' => 'Responsable',
        'actions' => 'Acciones',
        'edit' => 'Editar',
        'back' => 'Volver',
        'print' => 'Imprimir',
        'export_excel' => 'Exportar Excel',
        'export_pdf' => 'Exportar PDF',
        'download_soa' => 'Descargar SOA',
        'update' => 'Actualizar'
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1600px; margin: 0 auto; }
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
        .header-title h1 { font-size: 28px; color: #9900cc; margin-bottom: 5px; }
        .header-title p { color: #666; font-size: 14px; }
        .action-buttons { display: flex; gap: 10px; }
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
        .btn-primary { background: #0066cc; color: white; }
        .btn-success { background: #00994d; color: white; }
        .btn-secondary { background: #666; color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #9900cc;
        }
        .stat-value { font-size: 32px; font-weight: bold; color: #333; }
        .stat-label { color: #666; font-size: 13px; margin-top: 5px; }
        .compliance-bar {
            width: 100%;
            height: 35px;
            background: #f0f0f0;
            border-radius: 20px;
            overflow: hidden;
            margin-top: 10px;
        }
        .compliance-fill {
            height: 100%;
            background: linear-gradient(90deg, #9900cc 0%, #cc66ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .domain-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            overflow: hidden;
        }
        .domain-header {
            padding: 20px 30px;
            background: #f8f9fa;
            border-left: 6px solid #9900cc;
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .controls-table {
            width: 100%;
            border-collapse: collapse;
        }
        .controls-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            font-size: 13px;
        }
        .controls-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            font-size: 13px;
        }
        .controls-table tr:hover { background: #f8f9fa; }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-implemented { background: #00994d; color: white; }
        .badge-partial { background: #ffcc00; color: #333; }
        .badge-pending { background: #cc0000; color: white; }
        .badge-applicable { background: #0066cc; color: white; }
        .badge-not-applicable { background: #999; color: white; }
        @media (max-width: 768px) {
            .header-top { flex-direction: column; text-align: center; }
            .action-buttons { margin-top: 15px; flex-wrap: wrap; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>&#9881; <?php echo $t['title']; ?></h1>
                    <p><?php echo $t['subtitle']; ?></p>
                </div>
                <div class="action-buttons">
                    <button onclick="window.print()" class="btn btn-primary">&#128424; <?php echo $t['print']; ?></button>
                    <a href="export.php?format=excel&type=controls" class="btn btn-success">&#128202; <?php echo $t['export_excel']; ?></a>
                    <a href="export.php?format=pdf&type=soa" class="btn btn-success">&#128196; <?php echo $t['download_soa']; ?></a>
                    <a href="index.php" class="btn btn-secondary">&#8592; <?php echo $t['back']; ?></a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $total_controls; ?></div>
                    <div class="stat-label"><?php echo $t['total_controls']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $applicable_controls; ?></div>
                    <div class="stat-label"><?php echo $t['applicable']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $implemented_controls; ?></div>
                    <div class="stat-label"><?php echo $t['implemented']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $compliance_percentage; ?>%</div>
                    <div class="stat-label"><?php echo $t['compliance']; ?></div>
                    <div class="compliance-bar">
                        <div class="compliance-fill" style="width: <?php echo $compliance_percentage; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
        <div style="background: <?php echo $message_type == 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message_type == 'success' ? '#155724' : '#721c24'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold;">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php foreach ($anexo_a_controls as $domain_key => $domain): ?>
        <div class="domain-section">
            <div class="domain-header">
                <?php echo $domain_key; ?> - <?php echo $domain['name']; ?>
                <span style="float: right; font-size: 14px; background: #9900cc; color: white; padding: 5px 15px; border-radius: 15px;">
                    <?php echo count($domain['controls']); ?> controles
                </span>
            </div>
            <table class="controls-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Control</th>
                        <th>Descripcion</th>
                        <th>Aplicabilidad</th>
                        <th>Estado</th>
                        <th><?php echo $t['actions']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($domain['controls'] as $control): ?>
                    <tr>
                        <td><strong><?php echo $control['id']; ?></strong></td>
                        <td><?php echo $control['name']; ?></td>
                        <td style="max-width: 400px;"><?php echo $control['description']; ?></td>
                        <td><span class="badge badge-applicable">Aplicable</span></td>
                        <td><span class="badge badge-pending">Pendiente</span></td>
                        <td>
                            <button class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">
                                <?php echo $t['edit']; ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
<?php
$conn->close();
?>
