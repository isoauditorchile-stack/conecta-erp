<?php
require_once __DIR__ . '/../../includes/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/index.php');
}

$user = getCurrentUser();
$db = Database::getInstance();

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    // Lógica para crear registro
                    showAlert('Registro creado exitosamente', 'success');
                    break;

                case 'update':
                    // Lógica para actualizar registro
                    showAlert('Registro actualizado exitosamente', 'success');
                    break;

                case 'delete':
                    // Lógica para eliminar registro
                    showAlert('Registro eliminado exitosamente', 'success');
                    break;
            }
        }
    } catch (Exception $e) {
        showAlert('Error: ' . $e->getMessage(), 'error');
    }
}

// Obtener datos
$records = $db->fetchAll("SELECT * FROM fi_closing_periods WHERE user_id = ? ORDER BY id DESC LIMIT 100", [$user['id']]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierres Contables - CONECTA ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <style>
        body { display: flex; min-height: 100vh; background: var(--dark); }
        .sidebar { width: 280px; background: var(--dark-light); border-right: 1px solid rgba(255, 255, 255, 0.08); padding: 2rem 0; position: fixed; height: 100vh; overflow-y: auto; }
        .logo { padding: 0 1.5rem 2rem; display: flex; align-items: center; gap: 1rem; font-size: 1.5rem; font-weight: 900; border-bottom: 1px solid rgba(255, 255, 255, 0.08); margin-bottom: 2rem; }
        .logo i { background: linear-gradient(135deg, var(--primary), var(--secondary)); padding: 0.6rem; border-radius: 12px; color: white; box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4); }
        .sidebar-menu { list-style: none; padding: 0 0.75rem; }
        .menu-link { display: flex; align-items: center; gap: 1rem; padding: 0.875rem 1rem; color: var(--text-muted); text-decoration: none; border-radius: 12px; transition: var(--transition); font-weight: 500; }
        .menu-link:hover, .menu-link.active { background: rgba(99, 102, 241, 0.1); color: var(--primary); }
        .menu-link i { width: 24px; text-align: center; }
        .main-content { flex: 1; margin-left: 280px; }
        .topbar { background: rgba(30, 41, 59, 0.8); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding: 1.25rem 2rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .content { padding: 2rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-title { font-size: 2rem; font-weight: 800; display: flex; align-items: center; gap: 1rem; }
        .page-title i { color: #3b82f6; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <a href="../../<?php echo isAdmin() ? 'admin' : 'user'; ?>/dashboard_<?php echo isAdmin() ? 'admin' : 'user'; ?>.php" class="logo" style="text-decoration: none; color: var(--text);">
            <i class="fas fa-cube"></i>
            <span>CONECTA ERP</span>
        </a>
        <ul class="sidebar-menu">
            <li><a href="../FI/index.php" class="menu-link"><i class="fas fa-arrow-left"></i> Volver a Finanzas</a></li>
            <li><a href="./closing.php" class="menu-link active"><i class="fa-lock"></i> Cierres Contables</a></li>
        </ul>
    </aside>
    <main class="main-content">
        <div class="topbar">
            <h1 class="page-title"><i class="fa-lock"></i> Cierres Contables</h1>
            <button class="btn btn-primary" onclick="openModal('createModal')">
                <i class="fas fa-plus"></i> Nuevo
            </button>
        </div>
        <div class="content">
            <?php if ($alert = getAlert()): ?>
                <div class="alert alert-<?php echo $alert['type']; ?>">
                    <i class="fas fa-info-circle"></i>
                    <?php echo htmlspecialchars($alert['message']); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Listado de Registros</h2>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Descripción</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 3rem;">
                                        <i class="fas fa-inbox" style="font-size: 3rem; color: var(--text-dark); margin-bottom: 1rem; display: block;"></i>
                                        <p style="color: var(--text-muted);">No hay registros disponibles</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($records as $record): ?>
                                    <tr>
                                        <td><?php echo $record['id']; ?></td>
                                        <td><?php echo htmlspecialchars($record['description'] ?? 'N/A'); ?></td>
                                        <td><?php echo formatDate($record['created_at'] ?? date('Y-m-d')); ?></td>
                                        <td>
                                            <button class="btn btn-secondary btn-sm" onclick="edit(<?php echo $record['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-error btn-sm" onclick="deleteRecord(<?php echo $record['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <script>
        function openModal(id) { alert('Funcionalidad de modal en desarrollo'); }
        function edit(id) { alert('Editar registro ' + id); }
        function deleteRecord(id) { if(confirm('¿Eliminar este registro?')) { alert('Eliminar ' + id); } }
    </script>
</body>
</html>