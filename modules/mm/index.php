<?php
require_once __DIR__ . '/../../includes/config.php';
if (!isAuthenticated()) redirect('/index.php');
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Materiales - CONECTA ERP</title>
    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>
    <link rel='stylesheet' href='../../assets/css/global.css'>
    <style>
        body { padding: 2rem; background: var(--dark); }
        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-title { font-size: 3rem; font-weight: 900; color: var(--text); margin-bottom: 1rem; }
        .page-title i { color: #f59e0b; }
        .modules-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; max-width: 1400px; margin: 0 auto; }
        .module-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 2rem; text-align: center; text-decoration: none; color: var(--text); transition: all 0.3s; }
        .module-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.08); border-color: #f59e0b; }
        .module-icon { width: 80px; height: 80px; margin: 0 auto 1rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: white; }
        .module-title { font-size: 1.2rem; font-weight: 700; }
        .back-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: rgba(255,255,255,0.05); border-radius: 10px; color: var(--text); text-decoration: none; margin-bottom: 2rem; }
        .back-btn:hover { background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>
    <a href='../../<?php echo isAdmin() ? 'admin' : 'user'; ?>/dashboard_<?php echo isAdmin() ? 'admin' : 'user'; ?>.php' class='back-btn'>
        <i class='fas fa-arrow-left'></i> Volver al Dashboard
    </a>
    <div class='page-header'>
        <h1 class='page-title'><i class='fa-boxes'></i> Materiales</h1>
        <p style='color: var(--text-muted); font-size: 1.2rem;'>Selecciona un submódulo para comenzar</p>
    </div>
    <div class='modules-grid'>
                    <a href='./products.php' class='module-card'>
                        <div class='module-icon' style='background: #f59e0b;'>
                            <i class='fa-box'></i>
                        </div>
                        <div class='module-title'>Productos</div>
                    </a>
                    <a href='./materiales.php' class='module-card'>
                        <div class='module-icon' style='background: #6366f1;'>
                            <i class='fa-cubes'></i>
                        </div>
                        <div class='module-title'>Maestro de Materiales</div>
                    </a>
                    <a href='./warehouses.php' class='module-card'>
                        <div class='module-icon' style='background: #f59e0b;'>
                            <i class='fa-warehouse'></i>
                        </div>
                        <div class='module-title'>Almacenes</div>
                    </a>
                    <a href='./inventory.php' class='module-card'>
                        <div class='module-icon' style='background: #f59e0b;'>
                            <i class='fa-cubes'></i>
                        </div>
                        <div class='module-title'>Inventario</div>
                    </a>
                    <a href='./suppliers.php' class='module-card'>
                        <div class='module-icon' style='background: #f59e0b;'>
                            <i class='fa-truck-loading'></i>
                        </div>
                        <div class='module-title'>Proveedores</div>
                    </a>
                    <a href='./purchase_orders.php' class='module-card'>
                        <div class='module-icon' style='background: #f59e0b;'>
                            <i class='fa-shopping-cart'></i>
                        </div>
                        <div class='module-title'>Órdenes de Compra</div>
                    </a>
                    <a href='./goods_receipt.php' class='module-card'>
                        <div class='module-icon' style='background: #f59e0b;'>
                            <i class='fa-dolly'></i>
                        </div>
                        <div class='module-title'>Recepción</div>
                    </a>
                    <a href='./mrp.php' class='module-card'>
                        <div class='module-icon' style='background: #f59e0b;'>
                            <i class='fa-cogs'></i>
                        </div>
                        <div class='module-title'>MRP</div>
                    </a>
                    <a href='./traceability.php' class='module-card'>
                        <div class='module-icon' style='background: #f59e0b;'>
                            <i class='fa-barcode'></i>
                        </div>
                        <div class='module-title'>Trazabilidad</div>
                    </a>
    </div>
</body>
</html>