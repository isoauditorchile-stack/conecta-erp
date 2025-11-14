<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo - CONECTA ERP</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php
require_once '../includes/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('../login_new.php');
}

$currentUser = getCurrentUser();
$db = Database::getInstance();

// Verificar permisos del módulo (ejemplo)
// $hasPermission = checkModulePermission($currentUser['id'], 'MODULE_CODE');
// if (!$hasPermission && !isAdmin()) {
//     redirect('../user/dashboard.php');
// }
?>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-chart-line"></i> CONECTA ERP
            </div>
            <p class="mb-0">Nombre del Módulo</p>
        </div>

        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
            </div>
            <div class="sidebar-user-info">
                <h4><?php echo $currentUser['full_name']; ?></h4>
                <p><?php echo $currentUser['email']; ?></p>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-menu-item">
                <a href="<?php echo isAdmin() ? '../admin/dashboard.php' : '../user/dashboard.php'; ?>" class="sidebar-menu-link">
                    <i class="fas fa-home sidebar-menu-icon"></i>
                    Dashboard
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link active">
                    <i class="fas fa-box sidebar-menu-icon"></i>
                    Submódulo 1
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="#" class="sidebar-menu-link">
                    <i class="fas fa-list sidebar-menu-icon"></i>
                    Submódulo 2
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="../logout.php" class="sidebar-menu-link">
                    <i class="fas fa-sign-out-alt sidebar-menu-icon"></i>
                    Cerrar Sesión
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-box"></i> Nombre del Módulo
                </h1>
                <p class="text-muted mb-0">Descripción del módulo</p>
            </div>
            <div>
                <button class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo
                </button>
            </div>
        </div>

        <!-- Contenido del Módulo -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Lista de Elementos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>Este es un módulo de ejemplo. Implemente su lógica aquí.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
