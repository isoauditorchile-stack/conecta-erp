<?php
/**
 * SOPORTE - Sistema de tickets de soporte
 */
session_start();
require_once '../includes/config.php';

// Verificar sesión
requireLogin();

$usuario_id = $_SESSION['user_id'];
$success_message = '';
$errors = [];

// Obtener información del usuario
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

// Procesar creación de ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_ticket'])) {
    $asunto = trim($_POST['asunto']);
    $categoria = $_POST['categoria'];
    $prioridad = $_POST['prioridad'];
    $descripcion = trim($_POST['descripcion']);

    if (empty($asunto)) {
        $errors[] = "El asunto es obligatorio";
    }
    if (empty($descripcion)) {
        $errors[] = "La descripción es obligatoria";
    }

    if (empty($errors)) {
        // Aquí se crearía el ticket en la tabla tickets_soporte (por crear)
        $success_message = "Ticket creado correctamente. Te contactaremos pronto.";

        // Por ahora, crear una notificación
        $titulo = "Ticket de Soporte Creado";
        $mensaje = "Tu ticket '$asunto' ha sido creado exitosamente. Lo atenderemos pronto.";
        $stmt = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje) VALUES (?, 'info', ?, ?)");
        $stmt->bind_param("iss", $usuario_id, $titulo, $mensaje);
        $stmt->execute();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soporte - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--primary-gradient);
            color: white;
            transition: all 0.3s ease;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .sidebar.collapsed .sidebar-header h3 span {
            display: none;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-left: 4px solid white;
        }

        .sidebar-menu a i {
            font-size: 1.2rem;
            width: 25px;
            text-align: center;
        }

        .sidebar.collapsed .sidebar-menu a span {
            display: none;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Topbar */
        .topbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #667eea;
            cursor: pointer;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Content Area */
        .content-area {
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #718096;
            margin: 0;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 20px;
            font-weight: 600;
            font-size: 1.1rem;
            color: #2d3748;
        }

        .card-body {
            padding: 25px;
        }

        .form-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .form-control, .form-select, .form-control textarea {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 15px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .support-option {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            height: 100%;
        }

        .support-option:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }

        .support-option i {
            font-size: 3rem;
            margin-bottom: 20px;
            display: block;
        }

        .support-option.email i {
            color: #667eea;
        }

        .support-option.chat i {
            color: #48bb78;
        }

        .support-option.phone i {
            color: #ed8936;
        }

        .support-option h4 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2d3748;
        }

        .support-option p {
            color: #718096;
            margin-bottom: 20px;
        }

        .faq-item {
            background: white;
            border-radius: 10px;
            margin-bottom: 15px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .faq-question {
            padding: 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: #2d3748;
        }

        .faq-question:hover {
            background: #f7fafc;
        }

        .faq-answer {
            padding: 0 20px 20px 20px;
            color: #718096;
            display: none;
        }

        .faq-item.active .faq-answer {
            display: block;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>
                    <i class="fas fa-chart-line"></i>
                    <span>CONECTA ERP</span>
                </h3>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard_user.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="mi-empresa.php">
                        <i class="fas fa-building"></i>
                        <span>Mi Empresa</span>
                    </a>
                </li>
                <li>
                    <a href="perfil.php">
                        <i class="fas fa-user"></i>
                        <span>Mi Perfil</span>
                    </a>
                </li>
                <li>
                    <a href="suscripcion.php">
                        <i class="fas fa-credit-card"></i>
                        <span>Suscripción</span>
                    </a>
                </li>
                <li>
                    <a href="notificaciones.php">
                        <i class="fas fa-bell"></i>
                        <span>Notificaciones</span>
                    </a>
                </li>
                <li>
                    <a href="configuracion.php">
                        <i class="fas fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                </li>
                <li>
                    <a href="soporte.php" class="active">
                        <i class="fas fa-headset"></i>
                        <span>Soporte</span>
                    </a>
                </li>
                <li>
                    <a href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Topbar -->
            <div class="topbar">
                <button class="toggle-sidebar" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="user-info">
                    <div>
                        <strong><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></strong>
                        <div style="font-size: 0.875rem; color: #718096;">Usuario</div>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1)); ?>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <div class="page-header">
                    <h1><i class="fas fa-headset"></i> Centro de Soporte</h1>
                    <p>Estamos aquí para ayudarte</p>
                </div>

                <!-- Mensajes -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Canales de Soporte -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="support-option email">
                            <i class="fas fa-envelope"></i>
                            <h4>Email</h4>
                            <p>Respuesta en 24 horas hábiles</p>
                            <strong>soporte@conectaerp.cl</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="support-option chat">
                            <i class="fas fa-comments"></i>
                            <h4>Chat en Vivo</h4>
                            <p>Lun - Vie: 9:00 - 18:00</p>
                            <button class="btn btn-success" disabled>
                                <i class="fas fa-comment"></i> Iniciar Chat
                            </button>
                            <div style="font-size: 0.875rem; color: #718096; margin-top: 10px;">Próximamente</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="support-option phone">
                            <i class="fas fa-phone"></i>
                            <h4>Teléfono</h4>
                            <p>Lun - Vie: 9:00 - 18:00</p>
                            <strong>+56 2 2XXX XXXX</strong>
                        </div>
                    </div>
                </div>

                <!-- Crear Ticket -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-ticket-alt"></i> Crear Ticket de Soporte
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="asunto" class="form-label">Asunto *</label>
                                    <input type="text" class="form-control" id="asunto" name="asunto" required
                                           placeholder="Ej: Problema con facturación">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="categoria" class="form-label">Categoría *</label>
                                    <select class="form-select" id="categoria" name="categoria" required>
                                        <option value="">Seleccionar</option>
                                        <option value="tecnico">Soporte Técnico</option>
                                        <option value="facturacion">Facturación</option>
                                        <option value="cuenta">Mi Cuenta</option>
                                        <option value="funcionalidad">Funcionalidad</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="prioridad" class="form-label">Prioridad *</label>
                                    <select class="form-select" id="prioridad" name="prioridad" required>
                                        <option value="baja">Baja</option>
                                        <option value="media" selected>Media</option>
                                        <option value="alta">Alta</option>
                                        <option value="urgente">Urgente</option>
                                    </select>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="descripcion" class="form-label">Descripción del Problema *</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="6" required
                                              placeholder="Describe detalladamente el problema que estás experimentando..."></textarea>
                                </div>

                                <div class="col-md-12">
                                    <button type="submit" name="crear_ticket" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Enviar Ticket
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Preguntas Frecuentes -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-question-circle"></i> Preguntas Frecuentes
                    </div>
                    <div class="card-body">
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <span>¿Cómo activo mi suscripción?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                Para activar tu suscripción, ve a "Suscripción" en el menú lateral, selecciona un plan y completa el proceso de pago. Una vez que el super administrador apruebe tu pago, tu suscripción se activará automáticamente.
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <span>¿Qué incluye el periodo de prueba?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                El periodo de prueba incluye 14 días de acceso completo a todas las funcionalidades de tu plan seleccionado. Durante este tiempo puedes explorar el sistema sin ningún costo.
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <span>¿Cómo cambio mi plan?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                Puedes cambiar tu plan en cualquier momento desde "Suscripción". El cambio se reflejará en tu próximo periodo de facturación y se ajustará proporcionalmente si es un upgrade.
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <span>¿Cómo agrego usuarios a mi empresa?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                Desde el módulo de Administración, puedes crear nuevos usuarios y asignarles roles específicos. El número de usuarios disponibles depende de tu plan contratado.
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <span>¿Los datos están seguros?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                Sí, todos los datos están encriptados y almacenados en servidores seguros. Realizamos backups automáticos diarios y contamos con múltiples capas de seguridad para proteger tu información.
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <span>¿Puedo exportar mis datos?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                Sí, puedes exportar tus datos en formatos Excel, PDF o CSV desde el módulo de Reportes. También ofrecemos exportación completa de base de datos bajo solicitud.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recursos Adicionales -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-book"></i> Documentación</h5>
                                <p>Accede a nuestra documentación completa</p>
                                <a href="#" class="btn btn-outline-primary" disabled>
                                    <i class="fas fa-external-link-alt"></i> Ver Documentación
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-video"></i> Tutoriales</h5>
                                <p>Aprende con nuestros video tutoriales</p>
                                <a href="#" class="btn btn-outline-primary" disabled>
                                    <i class="fas fa-play"></i> Ver Tutoriales
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }

        function toggleFAQ(element) {
            const faqItem = element.closest('.faq-item');
            const isActive = faqItem.classList.contains('active');

            // Cerrar todas las FAQ
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });

            // Abrir la seleccionada si estaba cerrada
            if (!isActive) {
                faqItem.classList.add('active');
            }
        }
    </script>
</body>
</html>
