<?php
require_once __DIR__ . '/../../includes/config.php';
if (!isAuthenticated()) redirect('/index.php');
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Finanzas - CONECTA ERP</title>
    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>
    <link rel='stylesheet' href='../../assets/css/global.css'>
    <style>
        body { padding: 2rem; background: var(--dark); }
        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-title { font-size: 3rem; font-weight: 900; color: var(--text); margin-bottom: 1rem; }
        .page-title i { color: #3b82f6; }
        .modules-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; max-width: 1400px; margin: 0 auto; }
        .module-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 2rem; text-align: center; text-decoration: none; color: var(--text); transition: all 0.3s; }
        .module-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.08); border-color: #3b82f6; }
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
        <h1 class='page-title'><i class='fa-chart-line'></i> Finanzas</h1>
        <p style='color: var(--text-muted); font-size: 1.2rem;'>Selecciona un submódulo para comenzar</p>
    </div>
    <div class='modules-grid'>
                    <a href='./general_ledger.php' class='module-card'>
                        <div class='module-icon' style='background: #3b82f6;'>
                            <i class='fa-book'></i>
                        </div>
                        <div class='module-title'>Contabilidad General</div>
                    </a>
                    <a href='./accounts_receivable.php' class='module-card'>
                        <div class='module-icon' style='background: #3b82f6;'>
                            <i class='fa-hand-holding-usd'></i>
                        </div>
                        <div class='module-title'>Cuentas por Cobrar</div>
                    </a>
                    <a href='./accounts_payable.php' class='module-card'>
                        <div class='module-icon' style='background: #3b82f6;'>
                            <i class='fa-file-invoice-dollar'></i>
                        </div>
                        <div class='module-title'>Cuentas por Pagar</div>
                    </a>
                    <a href='./treasury.php' class='module-card'>
                        <div class='module-icon' style='background: #3b82f6;'>
                            <i class='fa-university'></i>
                        </div>
                        <div class='module-title'>Tesorería</div>
                    </a>
                    <a href='./fixed_assets.php' class='module-card'>
                        <div class='module-icon' style='background: #3b82f6;'>
                            <i class='fa-building'></i>
                        </div>
                        <div class='module-title'>Activos Fijos</div>
                    </a>
                    <a href='./banks.php' class='module-card'>
                        <div class='module-icon' style='background: #3b82f6;'>
                            <i class='fa-landmark'></i>
                        </div>
                        <div class='module-title'>Bancos y Conciliaciones</div>
                    </a>
                    <a href='./accounting_books.php' class='module-card'>
                        <div class='module-icon' style='background: #3b82f6;'>
                            <i class='fa-book-open'></i>
                        </div>
                        <div class='module-title'>Libros Contables</div>
                    </a>
                    <a href='./ifrs.php' class='module-card'>
                        <div class='module-icon' style='background: #3b82f6;'>
                            <i class='fa-chart-bar'></i>
                        </div>
                        <div class='module-title'>IFRS Reporting</div>
                    </a>
                    <a href='./journal_entries.php' class='module-card'>
                        <div class='module-icon' style='background: #3b82f6;'>
                            <i class='fa-pen-alt'></i>
                        </div>
                        <div class='module-title'>Asientos Contables</div>
                    </a>
                    <a href='./closing.php' class='module-card'>
                        <div class='module-icon' style='background: #3b82f6;'>
                            <i class='fa-lock'></i>
                        </div>
                        <div class='module-title'>Cierres Contables</div>
                    </a>
                    <a href='./reports.php' class='module-card'>
                        <div class='module-icon' style='background: #3b82f6;'>
                            <i class='fa-file-pdf'></i>
                        </div>
                        <div class='module-title'>Reportes Financieros</div>
                    </a>
    </div>
</body>
</html>