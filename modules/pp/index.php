<?php
require_once __DIR__ . '/../../includes/config.php';
if (!isAuthenticated()) redirect('/index.php');
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Producción - CONECTA ERP</title>
    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>
    <link rel='stylesheet' href='../../assets/css/global.css'>
    <style>
        body { padding: 2rem; background: var(--dark); }
        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-title { font-size: 3rem; font-weight: 900; color: var(--text); margin-bottom: 1rem; }
        .page-title i { color: #ef4444; }
        .modules-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; max-width: 1400px; margin: 0 auto; }
        .module-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 2rem; text-align: center; text-decoration: none; color: var(--text); transition: all 0.3s; }
        .module-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.08); border-color: #ef4444; }
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
        <h1 class='page-title'><i class='fa-industry'></i> Producción</h1>
        <p style='color: var(--text-muted); font-size: 1.2rem;'>Selecciona un submódulo para comenzar</p>
    </div>
    <div class='modules-grid'>
                    <a href='./production_orders.php' class='module-card'>
                        <div class='module-icon' style='background: #ef4444;'>
                            <i class='fa-industry'></i>
                        </div>
                        <div class='module-title'>Órdenes de Producción</div>
                    </a>
                    <a href='./bom.php' class='module-card'>
                        <div class='module-icon' style='background: #ef4444;'>
                            <i class='fa-list-alt'></i>
                        </div>
                        <div class='module-title'>BOM</div>
                    </a>
                    <a href='./routing.php' class='module-card'>
                        <div class='module-icon' style='background: #ef4444;'>
                            <i class='fa-route'></i>
                        </div>
                        <div class='module-title'>Rutinas</div>
                    </a>
                    <a href='./mrp_ii.php' class='module-card'>
                        <div class='module-icon' style='background: #ef4444;'>
                            <i class='fa-project-diagram'></i>
                        </div>
                        <div class='module-title'>MRP II</div>
                    </a>
                    <a href='./capacity.php' class='module-card'>
                        <div class='module-icon' style='background: #ef4444;'>
                            <i class='fa-chart-pie'></i>
                        </div>
                        <div class='module-title'>Capacidades</div>
                    </a>
                    <a href='./quality.php' class='module-card'>
                        <div class='module-icon' style='background: #ef4444;'>
                            <i class='fa-check-double'></i>
                        </div>
                        <div class='module-title'>Control de Calidad</div>
                    </a>
                    <a href='./maintenance.php' class='module-card'>
                        <div class='module-icon' style='background: #ef4444;'>
                            <i class='fa-tools'></i>
                        </div>
                        <div class='module-title'>Mantenimiento</div>
                    </a>
                    <a href='./formulas.php' class='module-card'>
                        <div class='module-icon' style='background: #ef4444;'>
                            <i class='fa-flask'></i>
                        </div>
                        <div class='module-title'>Fórmulas</div>
                    </a>
                    <a href='./costs.php' class='module-card'>
                        <div class='module-icon' style='background: #ef4444;'>
                            <i class='fa-dollar-sign'></i>
                        </div>
                        <div class='module-title'>Costos</div>
                    </a>
                    <a href='./work_centers.php' class='module-card'>
                        <div class='module-icon' style='background: #ef4444;'>
                            <i class='fa-industry'></i>
                        </div>
                        <div class='module-title'>Centros de Trabajo</div>
                    </a>
    </div>
</body>
</html>