<?php
require_once __DIR__ . '/../../includes/config.php';
if (!isAuthenticated()) redirect('/index.php');
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Capital Humano - CONECTA ERP</title>
    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>
    <link rel='stylesheet' href='../../assets/css/global.css'>
    <style>
        body { padding: 2rem; background: var(--dark); }
        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-title { font-size: 3rem; font-weight: 900; color: var(--text); margin-bottom: 1rem; }
        .page-title i { color: #ec4899; }
        .modules-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; max-width: 1400px; margin: 0 auto; }
        .module-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 2rem; text-align: center; text-decoration: none; color: var(--text); transition: all 0.3s; }
        .module-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.08); border-color: #ec4899; }
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
        <h1 class='page-title'><i class='fa-user-tie'></i> Capital Humano</h1>
        <p style='color: var(--text-muted); font-size: 1.2rem;'>Selecciona un submódulo para comenzar</p>
    </div>
    <div class='modules-grid'>
                    <a href='./employees.php' class='module-card'>
                        <div class='module-icon' style='background: #ec4899;'>
                            <i class='fa-id-card'></i>
                        </div>
                        <div class='module-title'>Empleados</div>
                    </a>
                    <a href='./payroll.php' class='module-card'>
                        <div class='module-icon' style='background: #ec4899;'>
                            <i class='fa-money-check-alt'></i>
                        </div>
                        <div class='module-title'>Nómina</div>
                    </a>
                    <a href='./recruitment.php' class='module-card'>
                        <div class='module-icon' style='background: #ec4899;'>
                            <i class='fa-user-plus'></i>
                        </div>
                        <div class='module-title'>Reclutamiento</div>
                    </a>
                    <a href='./onboarding.php' class='module-card'>
                        <div class='module-icon' style='background: #ec4899;'>
                            <i class='fa-handshake'></i>
                        </div>
                        <div class='module-title'>Onboarding</div>
                    </a>
                    <a href='./performance.php' class='module-card'>
                        <div class='module-icon' style='background: #ec4899;'>
                            <i class='fa-star'></i>
                        </div>
                        <div class='module-title'>Evaluación</div>
                    </a>
                    <a href='./training.php' class='module-card'>
                        <div class='module-icon' style='background: #ec4899;'>
                            <i class='fa-graduation-cap'></i>
                        </div>
                        <div class='module-title'>Capacitación</div>
                    </a>
                    <a href='./org_development.php' class='module-card'>
                        <div class='module-icon' style='background: #ec4899;'>
                            <i class='fa-sitemap'></i>
                        </div>
                        <div class='module-title'>Desarrollo Org.</div>
                    </a>
                    <a href='./attendance.php' class='module-card'>
                        <div class='module-icon' style='background: #ec4899;'>
                            <i class='fa-clock'></i>
                        </div>
                        <div class='module-title'>Asistencia</div>
                    </a>
                    <a href='./benefits.php' class='module-card'>
                        <div class='module-icon' style='background: #ec4899;'>
                            <i class='fa-gift'></i>
                        </div>
                        <div class='module-title'>Beneficios</div>
                    </a>
                    <a href='./leaves.php' class='module-card'>
                        <div class='module-icon' style='background: #ec4899;'>
                            <i class='fa-plane'></i>
                        </div>
                        <div class='module-title'>Vacaciones</div>
                    </a>
                    <a href='./reports.php' class='module-card'>
                        <div class='module-icon' style='background: #ec4899;'>
                            <i class='fa-file-excel'></i>
                        </div>
                        <div class='module-title'>Reportes RRHH</div>
                    </a>
    </div>
</body>
</html>