<?php
/**
 * PANEL DE USUARIO - INICIO COMPLETO
 * Solo auditorexchile@gmail.com tiene acceso total a módulos ISO
 */

define('ACCESO_PERMITIDO', true);
require_once dirname(__DIR__) . '/incluir/configuracion.php';
require_once RUTA_INCLUIR . '/base_datos.php';
require_once RUTA_INCLUIR . '/sesion.php';

// Iniciar sesión
Sesion::iniciar();

// Verificar autenticación
if (!Sesion::esta_autenticado()) {
    header('Location: /autenticacion/login.php');
    exit;
}

$nombre_usuario = Sesion::obtener('usuario_nombre', 'Usuario');
$correo_usuario = Sesion::obtener('usuario_correo', '');
$id_usuario = Sesion::obtener('usuario_id', 0);
$id_empresa = Sesion::obtener('usuario_empresa', 0);
$es_admin = Sesion::es_administrador();

// VERIFICAR SI ES ADMIN PRINCIPAL (auditorexchile@gmail.com) - ÚNICO CON ACCESO TOTAL A MÓDULOS ISO
$es_admin_principal = Sesion::es_admin_principal();

// Conectar a BD
try {
    $bd = OperacionesBD::obtener_instancia()->obtener_conexion();
} catch (Exception $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Estadísticas
try {
    // Mis documentos
    $stmt = $bd->prepare("SELECT COUNT(*) FROM documentos WHERE id_empresa = ? AND activo = 1");
    $stmt->execute([$id_empresa]);
    $total_documentos = $stmt->fetchColumn();

    // Normas disponibles
    $total_normas = $bd->query("SELECT COUNT(*) FROM normas_iso WHERE activa = 1")->fetchColumn();

    // Formatos disponibles
    $total_formatos = $bd->query("SELECT COUNT(*) FROM formatos_plantillas WHERE activo = 1")->fetchColumn();

    // Descargas del mes
    $stmt = $bd->prepare("SELECT COUNT(*) FROM historial_descargas WHERE id_usuario = ? AND MONTH(fecha_descarga) = MONTH(CURRENT_DATE())");
    $stmt->execute([$id_usuario]);
    $descargas_mes = $stmt->fetchColumn();

    // Recursos disponibles
    $total_recursos = $bd->query("SELECT COUNT(*) FROM recursos WHERE activo = 1")->fetchColumn();

    // Formularios disponibles
    $total_formularios = 10; // Formularios online disponibles

    // Normas más descargadas
    $normas_populares = $bd->query("SELECT * FROM normas_iso WHERE activa = 1 ORDER BY descargas DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

    // Formatos recientes
    $formatos_recientes = $bd->query("SELECT * FROM formatos_plantillas WHERE activo = 1 ORDER BY fecha_creacion DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

    // Recursos recientes
    $recursos_recientes = $bd->query("SELECT * FROM recursos WHERE activo = 1 ORDER BY fecha_creacion DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $total_documentos = $total_normas = $total_formatos = $descargas_mes = $total_recursos = $total_formularios = 0;
    $normas_populares = $formatos_recientes = $recursos_recientes = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Usuario - AuditorPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #212529;
            padding: 20px 0;
        }
        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #495057;
            color: #fff;
        }
        .stat-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .resource-card {
            height: 100%;
            transition: all 0.3s;
        }
        .resource-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .admin-principal-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <div class="text-center mb-4">
                    <h4 class="text-white">AuditorPro</h4>
                    <p class="text-muted small">Panel Usuario</p>
                    <?php if ($es_admin_principal): ?>
                    <span class="admin-principal-badge"><i class="fas fa-crown"></i> ADMIN PRINCIPAL</span>
                    <?php endif; ?>
                </div>
                <a href="inicio.php" class="active"><i class="fas fa-home"></i> Inicio</a>
                <hr class="text-white">
                <div class="px-3 text-muted small mb-2">BIBLIOTECA</div>
                <a href="normas.php"><i class="fas fa-certificate"></i> Normas ISO</a>
                <a href="formatos.php"><i class="fas fa-file-alt"></i> Formatos</a>
                <a href="recursos.php"><i class="fas fa-book"></i> Recursos</a>
                <hr class="text-white">
                <div class="px-3 text-muted small mb-2">MIS DOCUMENTOS</div>
                <a href="documentos.php"><i class="fas fa-folder-open"></i> Ver Documentos</a>
                <a href="subir_documento.php"><i class="fas fa-upload"></i> Subir Documento</a>
                <hr class="text-white">
                <div class="px-3 text-muted small mb-2">FORMULARIOS</div>
                <a href="formularios.php"><i class="fas fa-edit"></i> Formularios Online</a>
                <hr class="text-white">
                <div class="px-3 text-muted small mb-2">MI CUENTA</div>
                <a href="mi_suscripcion.php"><i class="fas fa-credit-card"></i> Mi Suscripción</a>
                <a href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a>
                <?php if ($es_admin): ?>
                <hr class="text-white">
                <a href="../administrador/inicio.php" class="text-warning"><i class="fas fa-user-shield"></i> Modo Admin</a>
                <?php endif; ?>
                <hr class="text-white">
                <a href="../autenticacion/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>

            <!-- Contenido Principal -->
            <div class="col-md-10 p-4">
                <!-- Bienvenida -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h2><i class="fas fa-user"></i> Bienvenido/a, <?php echo htmlspecialchars($nombre_usuario); ?></h2>
                                <p class="mb-0">Accede a tus recursos de auditoría, control interno y gestión de calidad</p>
                                <?php if ($es_admin_principal): ?>
                                <p class="mb-0 mt-2"><i class="fas fa-crown"></i> <strong>Acceso Total:</strong> Tienes acceso completo a todos los 30 módulos de normas ISO</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-2 mb-3">
                        <div class="card stat-card" style="border-left-color: #007bff;">
                            <div class="card-body text-center">
                                <i class="fas fa-folder fa-3x text-primary mb-2"></i>
                                <h3 class="mb-0"><?php echo number_format($total_documentos); ?></h3>
                                <p class="text-muted small mb-0">Mis Documentos</p>
                                <a href="documentos/listar.php" class="btn btn-primary btn-sm mt-2">Ver</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card stat-card" style="border-left-color: #28a745;">
                            <div class="card-body text-center">
                                <i class="fas fa-certificate fa-3x text-success mb-2"></i>
                                <h3 class="mb-0"><?php echo number_format($total_normas); ?></h3>
                                <p class="text-muted small mb-0">Normas ISO</p>
                                <a href="biblioteca/normas.php" class="btn btn-success btn-sm mt-2">Ver</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card stat-card" style="border-left-color: #17a2b8;">
                            <div class="card-body text-center">
                                <i class="fas fa-file-alt fa-3x text-info mb-2"></i>
                                <h3 class="mb-0"><?php echo number_format($total_formatos); ?></h3>
                                <p class="text-muted small mb-0">Formatos</p>
                                <a href="biblioteca/formatos.php" class="btn btn-info btn-sm mt-2">Ver</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card stat-card" style="border-left-color: #ffc107;">
                            <div class="card-body text-center">
                                <i class="fas fa-book fa-3x text-warning mb-2"></i>
                                <h3 class="mb-0"><?php echo number_format($total_recursos); ?></h3>
                                <p class="text-muted small mb-0">Recursos</p>
                                <a href="contenidos.php" class="btn btn-warning btn-sm mt-2">Ver</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card stat-card" style="border-left-color: #6f42c1;">
                            <div class="card-body text-center">
                                <i class="fas fa-edit fa-3x text-purple mb-2"></i>
                                <h3 class="mb-0"><?php echo number_format($total_formularios); ?></h3>
                                <p class="text-muted small mb-0">Formularios</p>
                                <a href="formulario_online.php" class="btn btn-secondary btn-sm mt-2">Ver</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card stat-card" style="border-left-color: #dc3545;">
                            <div class="card-body text-center">
                                <i class="fas fa-download fa-3x text-danger mb-2"></i>
                                <h3 class="mb-0"><?php echo number_format($descargas_mes); ?></h3>
                                <p class="text-muted small mb-0">Descargas (mes)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Accesos Rápidos -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4><i class="fas fa-bolt"></i> Accesos Rápidos</h4>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card resource-card">
                            <div class="card-body text-center">
                                <i class="fas fa-upload fa-3x text-primary mb-3"></i>
                                <h5>Subir Documento</h5>
                                <p class="text-muted">Carga tus documentos al sistema</p>
                                <a href="documentos/subir.php" class="btn btn-primary">Subir</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card resource-card">
                            <div class="card-body text-center">
                                <i class="fas fa-edit fa-3x text-success mb-3"></i>
                                <h5>Crear Formulario</h5>
                                <p class="text-muted">Completa formularios online</p>
                                <a href="formulario_online.php" class="btn btn-success">Crear</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card resource-card">
                            <div class="card-body text-center">
                                <i class="fas fa-certificate fa-3x text-info mb-3"></i>
                                <h5>Explorar Normas</h5>
                                <p class="text-muted">100+ normas ISO disponibles</p>
                                <a href="biblioteca/normas.php" class="btn btn-info">Explorar</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card resource-card">
                            <div class="card-body text-center">
                                <i class="fas fa-file-download fa-3x text-warning mb-3"></i>
                                <h5>Descargar Formatos</h5>
                                <p class="text-muted">Plantillas listas para usar</p>
                                <a href="biblioteca/formatos.php" class="btn btn-warning">Descargar</a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (count($normas_populares) > 0): ?>
                <!-- Normas Más Descargadas -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4><i class="fas fa-star"></i> Normas ISO Más Populares</h4>
                    </div>
                    <?php foreach ($normas_populares as $norma): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($norma['codigo_norma']); ?></h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars(substr($norma['nombre_norma'], 0, 80)); ?>...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-download"></i> <?php echo number_format($norma['descargas']); ?> descargas
                                    </small>
                                    <a href="biblioteca/normas.php?ver=<?php echo $norma['id_norma']; ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (count($formatos_recientes) > 0): ?>
                <!-- Formatos Recientes -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4><i class="fas fa-clock"></i> Formatos Recientes</h4>
                    </div>
                    <?php foreach ($formatos_recientes as $formato): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($formato['nombre_formato']); ?></h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars(substr($formato['descripcion'], 0, 80)); ?>...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <?php if ($formato['archivo_word']): ?><i class="fas fa-file-word text-primary"></i><?php endif; ?>
                                        <?php if ($formato['archivo_excel']): ?><i class="fas fa-file-excel text-success"></i><?php endif; ?>
                                        <?php if ($formato['archivo_pdf']): ?><i class="fas fa-file-pdf text-danger"></i><?php endif; ?>
                                    </small>
                                    <a href="biblioteca/formatos.php?ver=<?php echo $formato['id_formato']; ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- ============================================== -->
                <!-- MÓDULOS DE NORMAS ISO - CONTROL DE ACCESO -->
                <!-- Solo auditorexchile@gmail.com ve todos los módulos -->
                <!-- ============================================== -->
                <?php if ($es_admin_principal): ?>
                <!-- VISTA COMPLETA PARA ADMIN PRINCIPAL -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-success">
                            <h3><i class="fas fa-crown text-warning"></i> Módulos de Normas ISO - Acceso Total</h3>
                            <p class="mb-0">Como <strong>Administrador Principal</strong> (<?php echo htmlspecialchars($correo_usuario); ?>), tienes acceso completo a todos los 30 módulos de normas ISO con documentación completa, controles, políticas, procedimientos y formatos.</p>
                        </div>
                    </div>
                </div>

                <!-- MÓDULOS DE NORMAS ISO (30 normas) -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h3><i class="fas fa-certificate text-primary"></i> Módulos de Normas ISO</h3>
                        <p class="text-muted">Accede a la documentación completa, controles, políticas, procedimientos y formatos de cada norma</p>
                    </div>
                </div>

                <!-- Gestión de Calidad -->
                <div class="row mb-3">
                    <div class="col-12">
                        <h5 class="text-primary"><i class="fas fa-star"></i> Gestión de la Calidad</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-primary">
                            <div class="card-header bg-primary text-white"><strong>ISO 9001:2015</strong></div>
                            <div class="card-body">
                                <h6>Sistema de Gestión de la Calidad</h6>
                                <p class="small text-muted">Requisitos para SGC, mejora continua y satisfacción del cliente</p>
                                <a href="modulos/iso9001/index.php" class="btn btn-primary btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-secondary">
                            <div class="card-header bg-secondary text-white"><strong>ISO 9000:2015</strong></div>
                            <div class="card-body">
                                <h6>Fundamentos y Vocabulario</h6>
                                <p class="small text-muted">Conceptos fundamentales y terminología de SGC</p>
                                <a href="modulos/iso9000/index.php" class="btn btn-secondary btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-secondary">
                            <div class="card-header bg-secondary text-white"><strong>ISO 9004</strong></div>
                            <div class="card-body">
                                <h6>Gestión para el Éxito Sostenido</h6>
                                <p class="small text-muted">Mejora del desempeño y éxito sostenido de la organización</p>
                                <a href="modulos/iso9004/index.php" class="btn btn-secondary btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Medio Ambiente y Energía -->
                <div class="row mb-3">
                    <div class="col-12">
                        <h5 class="text-success"><i class="fas fa-leaf"></i> Medio Ambiente y Energía</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-success">
                            <div class="card-header bg-success text-white"><strong>ISO 14001:2015</strong></div>
                            <div class="card-body">
                                <h6>Sistema de Gestión Ambiental</h6>
                                <p class="small text-muted">Gestión de aspectos ambientales y cumplimiento legal</p>
                                <a href="modulos/iso14001/index.php" class="btn btn-success btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-success">
                            <div class="card-header bg-success text-white"><strong>ISO 50001</strong></div>
                            <div class="card-body">
                                <h6>Gestión de la Energía</h6>
                                <p class="small text-muted">Eficiencia energética y reducción de costos</p>
                                <a href="modulos/iso50001/index.php" class="btn btn-success btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-success">
                            <div class="card-header bg-success text-white"><strong>ISO 14064-1</strong></div>
                            <div class="card-body">
                                <h6>Gases de Efecto Invernadero</h6>
                                <p class="small text-muted">Cuantificación y reporte de GEI</p>
                                <a href="modulos/iso14064/index.php" class="btn btn-success btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SST -->
                <div class="row mb-3">
                    <div class="col-12">
                        <h5 class="text-warning"><i class="fas fa-hard-hat"></i> Seguridad y Salud en el Trabajo</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-warning">
                            <div class="card-header bg-warning text-dark"><strong>ISO 45001:2018</strong></div>
                            <div class="card-body">
                                <h6>Sistema de Gestión de SST</h6>
                                <p class="small text-muted">Prevención de lesiones y enfermedades laborales</p>
                                <a href="modulos/iso45001/index.php" class="btn btn-warning btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seguridad de la Información (Familia 27000) -->
                <div class="row mb-3">
                    <div class="col-12">
                        <h5 class="text-danger"><i class="fas fa-shield-alt"></i> Seguridad de la Información y Ciberseguridad</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-danger">
                            <div class="card-header bg-danger text-white"><strong>ISO 27001</strong></div>
                            <div class="card-body">
                                <h6>SGSI - Seguridad de la Información</h6>
                                <p class="small text-muted">93 controles, gestión de riesgos SI</p>
                                <a href="modulos/iso27001/index.php" class="btn btn-danger btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-danger">
                            <div class="card-header bg-danger text-white"><strong>ISO 27002</strong></div>
                            <div class="card-body">
                                <h6>Controles de Seguridad</h6>
                                <p class="small text-muted">114 controles de seguridad de la información</p>
                                <a href="modulos/iso27002/index.php" class="btn btn-danger btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-danger">
                            <div class="card-header bg-danger text-white"><strong>ISO 27005</strong></div>
                            <div class="card-body">
                                <h6>Gestión de Riesgos SI</h6>
                                <p class="small text-muted">Metodología de análisis y tratamiento de riesgos</p>
                                <a href="modulos/iso27005/index.php" class="btn btn-danger btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-danger">
                            <div class="card-header bg-danger text-white"><strong>ISO 27017</strong></div>
                            <div class="card-body">
                                <h6>Seguridad en la Nube</h6>
                                <p class="small text-muted">Controles para servicios cloud</p>
                                <a href="modulos/iso27017/index.php" class="btn btn-danger btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-danger">
                            <div class="card-header bg-danger text-white"><strong>ISO 27018</strong></div>
                            <div class="card-body">
                                <h6>Protección Datos en la Nube</h6>
                                <p class="small text-muted">Privacidad y protección de datos personales</p>
                                <a href="modulos/iso27018/index.php" class="btn btn-danger btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-danger">
                            <div class="card-header bg-danger text-white"><strong>ISO 27032</strong></div>
                            <div class="card-body">
                                <h6>Ciberseguridad</h6>
                                <p class="small text-muted">Guía de ciberseguridad y protección en internet</p>
                                <a href="modulos/iso27032/index.php" class="btn btn-danger btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-danger">
                            <div class="card-header bg-danger text-white"><strong>ISO 27035</strong></div>
                            <div class="card-body">
                                <h6>Gestión de Incidentes</h6>
                                <p class="small text-muted">Respuesta y gestión de incidentes de seguridad</p>
                                <a href="modulos/iso27035/index.php" class="btn btn-danger btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Servicios TI / Continuidad -->
                <div class="row mb-3">
                    <div class="col-12">
                        <h5 class="text-info"><i class="fas fa-server"></i> Servicios TI y Continuidad</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-info">
                            <div class="card-header bg-info text-white"><strong>ISO 20000-1</strong></div>
                            <div class="card-body">
                                <h6>Gestión de Servicios TI</h6>
                                <p class="small text-muted">Sistema de gestión de servicios (SGSTI / ITSM)</p>
                                <a href="modulos/iso20000/index.php" class="btn btn-info btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-info">
                            <div class="card-header bg-info text-white"><strong>ISO 22301</strong></div>
                            <div class="card-body">
                                <h6>Continuidad del Negocio</h6>
                                <p class="small text-muted">Sistema de gestión de continuidad del negocio (SGCN)</p>
                                <a href="modulos/iso22301/index.php" class="btn btn-info btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gestión de Riesgos -->
                <div class="row mb-3">
                    <div class="col-12">
                        <h5 class="text-dark"><i class="fas fa-exclamation-triangle"></i> Gestión de Riesgos</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-dark">
                            <div class="card-header bg-dark text-white"><strong>ISO 31000</strong></div>
                            <div class="card-body">
                                <h6>Gestión del Riesgo</h6>
                                <p class="small text-muted">Directrices para gestión integral de riesgos</p>
                                <a href="modulos/iso31000/index.php" class="btn btn-dark btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-dark">
                            <div class="card-header bg-dark text-white"><strong>ISO 31010</strong></div>
                            <div class="card-body">
                                <h6>Técnicas de Evaluación del Riesgo</h6>
                                <p class="small text-muted">31 técnicas de identificación y análisis de riesgos</p>
                                <a href="modulos/iso31010/index.php" class="btn btn-dark btn-sm w-100">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compliance y Gobernanza (Familia 37000) -->
                <div class="row mb-3">
                    <div class="col-12">
                        <h5 style="color: #6f42c1;"><i class="fas fa-gavel"></i> Compliance, Antisoborno y Gobernanza</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100" style="border-color: #6f42c1;">
                            <div class="card-header text-white" style="background-color: #6f42c1;"><strong>ISO 37001</strong></div>
                            <div class="card-body">
                                <h6>Sistema Antisoborno</h6>
                                <p class="small text-muted">Prevención, detección y respuesta al soborno</p>
                                <a href="modulos/iso37001/index.php" class="btn btn-sm w-100" style="background-color: #6f42c1; color: white;">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100" style="border-color: #6f42c1;">
                            <div class="card-header text-white" style="background-color: #6f42c1;"><strong>ISO 37002</strong></div>
                            <div class="card-body">
                                <h6>Sistemas de Denuncias</h6>
                                <p class="small text-muted">Whistleblowing / canal de denuncias</p>
                                <a href="modulos/iso37002/index.php" class="btn btn-sm w-100" style="background-color: #6f42c1; color: white;">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100" style="border-color: #6f42c1;">
                            <div class="card-header text-white" style="background-color: #6f42c1;"><strong>ISO 37301</strong></div>
                            <div class="card-body">
                                <h6>Sistema de Gestión de Compliance</h6>
                                <p class="small text-muted">Cumplimiento normativo integral (reemplaza ISO 19600)</p>
                                <a href="modulos/iso37301/index.php" class="btn btn-sm w-100" style="background-color: #6f42c1; color: white;">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100" style="border-color: #6f42c1;">
                            <div class="card-header text-white" style="background-color: #6f42c1;"><strong>ISO 37000</strong></div>
                            <div class="card-body">
                                <h6>Gobernanza de Organizaciones</h6>
                                <p class="small text-muted">Principios y directrices de buen gobierno corporativo</p>
                                <a href="modulos/iso37000/index.php" class="btn btn-sm w-100" style="background-color: #6f42c1; color: white;">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100" style="border-color: #6f42c1;">
                            <div class="card-header text-white" style="background-color: #6f42c1;"><strong>ISO 26000</strong></div>
                            <div class="card-body">
                                <h6>Responsabilidad Social</h6>
                                <p class="small text-muted">Guía de responsabilidad social empresarial (RSE)</p>
                                <a href="modulos/iso26000/index.php" class="btn btn-sm w-100" style="background-color: #6f42c1; color: white;">Acceder al Módulo</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Otras Normas -->
                <div class="row mb-3">
                    <div class="col-12">
                        <h5 class="text-secondary"><i class="fas fa-ellipsis-h"></i> Otras Normas Especializadas</h5>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card h-100 border-secondary">
                            <div class="card-header bg-secondary text-white"><strong>ISO 22000</strong></div>
                            <div class="card-body">
                                <h6>Inocuidad Alimentaria</h6>
                                <a href="modulos/iso22000/index.php" class="btn btn-secondary btn-sm w-100">Acceder</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card h-100 border-secondary">
                            <div class="card-header bg-secondary text-white"><strong>ISO 17025</strong></div>
                            <div class="card-body">
                                <h6>Laboratorios de Ensayo</h6>
                                <a href="modulos/iso17025/index.php" class="btn btn-secondary btn-sm w-100">Acceder</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card h-100 border-secondary">
                            <div class="card-header bg-secondary text-white"><strong>ISO 17020</strong></div>
                            <div class="card-body">
                                <h6>Organismos de Inspección</h6>
                                <a href="modulos/iso17020/index.php" class="btn btn-secondary btn-sm w-100">Acceder</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card h-100 border-secondary">
                            <div class="card-header bg-secondary text-white"><strong>ISO 17021-1</strong></div>
                            <div class="card-body">
                                <h6>Organismos de Certificación</h6>
                                <a href="modulos/iso17021/index.php" class="btn btn-secondary btn-sm w-100">Acceder</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card h-100 border-secondary">
                            <div class="card-header bg-secondary text-white"><strong>ISO 19011</strong></div>
                            <div class="card-body">
                                <h6>Auditorías de Sistemas</h6>
                                <a href="modulos/iso19011/index.php" class="btn btn-secondary btn-sm w-100">Acceder</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card h-100 border-secondary">
                            <div class="card-header bg-secondary text-white"><strong>ISO 13485</strong></div>
                            <div class="card-body">
                                <h6>Dispositivos Médicos</h6>
                                <a href="modulos/iso13485/index.php" class="btn btn-secondary btn-sm w-100">Acceder</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card h-100 border-secondary">
                            <div class="card-header bg-secondary text-white"><strong>IATF 16949</strong></div>
                            <div class="card-body">
                                <h6>Calidad Automotriz</h6>
                                <a href="modulos/iatf16949/index.php" class="btn btn-secondary btn-sm w-100">Acceder</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-success mt-4">
                    <i class="fas fa-check-circle"></i> <strong>Sistema Completo:</strong> 30 Normas ISO con documentación completa, controles, políticas, procedimientos, formatos Excel/Word y checklists de auditoría.
                </div>

                <?php else: ?>
                <!-- VISTA LIMITADA PARA USUARIOS REGULARES -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h3><i class="fas fa-certificate text-primary"></i> Módulos de Normas ISO</h3>
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-lock"></i> Acceso Limitado</h5>
                            <p>Actualmente tienes acceso limitado. Para acceder a todos los 30 módulos de normas ISO con documentación completa, controles, políticas, procedimientos y formatos, actualiza tu plan o contacta al administrador.</p>
                            <a href="mi_suscripcion.php" class="btn btn-warning me-2"><i class="fas fa-arrow-up"></i> Ver Mi Plan</a>
                            <a href="mailto:<?php echo EMAIL_ADMIN; ?>" class="btn btn-outline-warning">Contactar Admin</a>
                        </div>
                    </div>
                </div>

                <!-- Mostrar solo vista previa de 3 módulos bloqueados -->
                <div class="row mb-3">
                    <div class="col-12">
                        <h5 class="text-muted"><i class="fas fa-star"></i> Vista Previa de Módulos (Requiere Plan Premium)</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-secondary" style="opacity: 0.6;">
                            <div class="card-header bg-secondary text-white"><strong>ISO 9001:2015</strong></div>
                            <div class="card-body position-relative">
                                <h6>Sistema de Gestión de la Calidad</h6>
                                <p class="small text-muted">Requisitos para SGC, mejora continua</p>
                                <button class="btn btn-secondary btn-sm w-100" disabled><i class="fas fa-lock"></i> Requiere Upgrade</button>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 10px 20px; border-radius: 5px;">
                                    <i class="fas fa-lock"></i> Bloqueado
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-secondary" style="opacity: 0.6;">
                            <div class="card-header bg-secondary text-white"><strong>ISO 27001</strong></div>
                            <div class="card-body position-relative">
                                <h6>Seguridad de la Información</h6>
                                <p class="small text-muted">93 controles de seguridad</p>
                                <button class="btn btn-secondary btn-sm w-100" disabled><i class="fas fa-lock"></i> Requiere Upgrade</button>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 10px 20px; border-radius: 5px;">
                                    <i class="fas fa-lock"></i> Bloqueado
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-secondary" style="opacity: 0.6;">
                            <div class="card-header bg-secondary text-white"><strong>ISO 14001:2015</strong></div>
                            <div class="card-body position-relative">
                                <h6>Gestión Ambiental</h6>
                                <p class="small text-muted">Sistema de gestión ambiental</p>
                                <button class="btn btn-secondary btn-sm w-100" disabled><i class="fas fa-lock"></i> Requiere Upgrade</button>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 10px 20px; border-radius: 5px;">
                                    <i class="fas fa-lock"></i> Bloqueado
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-3x me-3"></i>
                        <div class="flex-grow-1">
                            <h5><i class="fas fa-rocket"></i> Desbloquea Todo el Potencial</h5>
                            <p class="mb-0">Accede a los 30 módulos completos de normas ISO con toda la documentación, controles, políticas, procedimientos, formatos Excel/Word y checklists de auditoría.</p>
                        </div>
                        <a href="../planes.php" class="btn btn-primary btn-lg">Ver Planes</a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Banner Upgrade -->
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-info-circle fa-3x me-3"></i>
                                <div class="flex-grow-1">
                                    <h5>¿Necesitas más acceso?</h5>
                                    <p class="mb-0">Actualiza tu plan para acceder a todos los recursos, formatos y herramientas premium</p>
                                </div>
                                <a href="../planes.php" class="btn btn-primary">Ver Planes</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
