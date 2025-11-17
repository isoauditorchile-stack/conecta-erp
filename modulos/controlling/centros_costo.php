<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
requireLogin();
$usuario_id = $_SESSION['usuario_id'];
$empresa_id = $_SESSION['empresa_id'];
$mensaje = '';

// CREAR CENTRO DE COSTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_centro'])) {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $tipo = $_POST['tipo'];
    $centro_padre_id = isset($_POST['centro_padre_id']) && $_POST['centro_padre_id'] !== '' ? intval($_POST['centro_padre_id']) : NULL;

    $stmt = $conn->prepare("INSERT INTO centros_costo (empresa_id, codigo, nombre, tipo, centro_padre_id, creado_por) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssii", $empresa_id, $codigo, $nombre, $tipo, $centro_padre_id, $usuario_id);
    if ($stmt->execute()) {
        logAuditoria($conn, $usuario_id, $empresa_id, 'crear', 'centros_costo', $conn->insert_id, 'controlling', "Centro costo: $codigo");
        $mensaje = "Centro de costo creado exitosamente";
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) as total, COALESCE(SUM(CASE WHEN activo=1 THEN 1 ELSE 0 END), 0) as activos FROM centros_costo WHERE empresa_id = ?");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Centros de Costo - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>.sidebar{position:fixed;left:0;top:0;width:280px;height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;}.content{margin-left:280px;padding:30px;}</style>
</head>
<body>
    <div class="sidebar"><h3><i class="fas fa-sitemap"></i> Centros Costo</h3><a href="../../user/dashboard.php" class="btn btn-light btn-sm w-100 mt-3"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
    <div class="content">
        <h2><i class="fas fa-sitemap text-primary"></i> Centros de Costo</h2>
        <?php if($mensaje): ?><div class="alert alert-success"><?php echo $mensaje; ?></div><?php endif; ?>
        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Código</th><th>Nombre</th><th>Tipo</th><th>Estado</th></tr></thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT codigo, nombre, tipo, activo FROM centros_costo WHERE empresa_id = ? ORDER BY codigo");
                        $stmt->bind_param("i", $empresa_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['codigo']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td><?php echo ucfirst($row['tipo']); ?></td>
                            <td><span class="badge bg-<?php echo $row['activo']?'success':'secondary'; ?>"><?php echo $row['activo']?'Activo':'Inactivo'; ?></span></td>
                        </tr>
                        <?php endwhile; $stmt->close(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
