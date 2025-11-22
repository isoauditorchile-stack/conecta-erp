<?php
/**
 * CONECTA ERP - MODULO DE CONTABILIDAD FINANCIERA (SAP FI LEVEL)
 * Sistema completo de contabilidad nivel SAP FI
 * Plan de cuentas jerarquico - Asientos - Subledgers - Clearing - Reportes Excel
 * Sin dependencias externas - Todo en un solo archivo
 */

// Iniciar sesion
session_start();

// Configuracion de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'conectae_conectaerpbd');
define('DB_USER', 'conectae_conectaerpuser');
define('DB_PASS', 'pt125824caraud');
define('DB_CHARSET', 'utf8mb4');

// Conexion a base de datos
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("Error de conexion: " . $e->getMessage());
}

// Variables de sesion
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

$action = $_GET['action'] ?? $_POST['action'] ?? 'accounts';
$message = '';
$error = '';

// === CREAR TABLAS SI NO EXISTEN ===
try {
    // Tabla de ejercicios fiscales (fiscal years)
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_fiscal_years (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        year VARCHAR(4) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status ENUM('open', 'closed') DEFAULT 'open',
        closed_by INT NULL,
        closed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_year_company (year, company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de periodos contables
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_periods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        fiscal_year_id INT NOT NULL,
        period VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
        period_number INT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status ENUM('open', 'closed') DEFAULT 'open',
        closed_by INT NULL,
        closed_at TIMESTAMP NULL,
        UNIQUE KEY uk_period_company (period, company_id),
        INDEX idx_fiscal_year (fiscal_year_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de tipos de documento
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_document_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        code VARCHAR(10) NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        category ENUM('accounting', 'ar', 'ap', 'bank', 'asset', 'other') DEFAULT 'accounting',
        number_range_start INT DEFAULT 1,
        number_range_end INT DEFAULT 999999,
        next_number INT DEFAULT 1,
        active TINYINT(1) DEFAULT 1,
        UNIQUE KEY uk_code_company (code, company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de claves de contabilizacion (posting keys)
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_posting_keys (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        key_code VARCHAR(10) NOT NULL,
        name VARCHAR(100) NOT NULL,
        type ENUM('debit', 'credit') NOT NULL,
        account_type ENUM('gl', 'customer', 'vendor', 'asset') DEFAULT 'gl',
        special_gl VARCHAR(20),
        active TINYINT(1) DEFAULT 1,
        UNIQUE KEY uk_key_company (key_code, company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de plan de cuentas (7 niveles jerarquicos)
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_chart_of_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        codigo_completo VARCHAR(20) NOT NULL,
        nivel INT NOT NULL COMMENT '1-7 niveles jerarquicos',
        parent_id INT NULL,
        nombre VARCHAR(200) NOT NULL,
        nombre_ingles VARCHAR(200),
        descripcion TEXT,
        naturaleza ENUM('deudora', 'acreedora') NOT NULL,
        tipo VARCHAR(50) COMMENT 'activo_corriente pasivo_corriente patrimonio ingresos gastos',
        subtipo VARCHAR(50),
        account_group VARCHAR(20),
        moneda VARCHAR(3) DEFAULT 'CLP',
        permite_movimientos TINYINT(1) DEFAULT 1,
        requiere_tercero TINYINT(1) DEFAULT 0,
        tipo_tercero ENUM('customer', 'vendor', 'employee', 'other'),
        requiere_centro_costos TINYINT(1) DEFAULT 0,
        requiere_proyecto TINYINT(1) DEFAULT 0,
        requiere_segmento TINYINT(1) DEFAULT 0,
        requiere_area TINYINT(1) DEFAULT 0,
        requiere_documento VARCHAR(100),
        clasificacion_ifrs VARCHAR(50),
        clasificacion_nic VARCHAR(50),
        clasificacion_tributaria VARCHAR(50),
        clasificacion_flujo_caja VARCHAR(50) COMMENT 'operacion inversion financiacion',
        clasificacion_balance VARCHAR(50) COMMENT 'activo_corriente pasivo_corriente etc',
        clasificacion_resultado VARCHAR(50) COMMENT 'ingresos_operacionales gastos_ventas etc',
        integracion_modulo VARCHAR(50) COMMENT 'ventas compras inventario nomina produccion activos',
        integracion_tipo_documento VARCHAR(50),
        integracion_automatica TINYINT(1) DEFAULT 0,
        cuenta_contrapartida_id INT NULL,
        cuenta_retencion_id INT NULL,
        cuenta_anticipo_id INT NULL,
        reconcile_account TINYINT(1) DEFAULT 0 COMMENT 'Cuenta de conciliacion',
        open_item_management TINYINT(1) DEFAULT 0 COMMENT 'Gestion de partidas abiertas',
        line_item_display TINYINT(1) DEFAULT 1,
        sort_key VARCHAR(20),
        activa TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        updated_by INT,
        UNIQUE KEY uk_codigo_company (codigo_completo, company_id),
        INDEX idx_parent (parent_id),
        INDEX idx_nivel (nivel),
        INDEX idx_tipo (tipo),
        INDEX idx_account_group (account_group),
        INDEX idx_integracion (integracion_modulo, integracion_tipo_documento)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de asientos contables (journal entries / documents)
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_journal_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        document_type_id INT,
        numero_documento VARCHAR(20) NOT NULL,
        numero_referencia VARCHAR(50),
        fecha_documento DATE NOT NULL,
        fecha_contabilizacion DATE NOT NULL,
        periodo VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
        fiscal_year VARCHAR(4),
        tipo ENUM('manual', 'automatico', 'ajuste', 'cierre', 'apertura', 'reversa', 'provision') DEFAULT 'manual',
        posting_key VARCHAR(10),
        origen VARCHAR(50) COMMENT 'ventas compras nomina inventario produccion activos tesoreria',
        documento_origen_tipo VARCHAR(50),
        documento_origen_id INT,
        documento_origen_numero VARCHAR(50),
        concepto TEXT NOT NULL,
        texto_cabecera VARCHAR(200),
        moneda VARCHAR(3) DEFAULT 'CLP',
        tipo_cambio DECIMAL(10,4) DEFAULT 1,
        total_debe DECIMAL(15,2) NOT NULL DEFAULT 0,
        total_haber DECIMAL(15,2) NOT NULL DEFAULT 0,
        total_debe_moneda_local DECIMAL(15,2) DEFAULT 0,
        total_haber_moneda_local DECIMAL(15,2) DEFAULT 0,
        diferencia DECIMAL(15,2) GENERATED ALWAYS AS (total_debe - total_haber) STORED,
        cuadrado TINYINT(1) GENERATED ALWAYS AS (ABS(total_debe - total_haber) < 0.01) STORED,
        estado ENUM('borrador', 'preliminar', 'contabilizado', 'anulado', 'reversado') DEFAULT 'borrador',
        fecha_contabilizacion_sistema TIMESTAMP NULL,
        contabilizado_por INT NULL,
        reversa_de INT NULL COMMENT 'ID del documento que reversa',
        reversado_por INT NULL COMMENT 'ID del documento reversa',
        reversal_reason VARCHAR(200),
        batch_id VARCHAR(50),
        assignment VARCHAR(50),
        user_name VARCHAR(100),
        notas TEXT,
        attachment VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        updated_by INT,
        UNIQUE KEY uk_numero_company (numero_documento, company_id),
        INDEX idx_fecha_doc (fecha_documento),
        INDEX idx_fecha_cont (fecha_contabilizacion),
        INDEX idx_periodo (periodo),
        INDEX idx_fiscal_year (fiscal_year),
        INDEX idx_tipo (tipo),
        INDEX idx_origen (origen, documento_origen_tipo, documento_origen_id),
        INDEX idx_estado (estado),
        INDEX idx_batch (batch_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de lineas de asiento (journal entry lines)
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_journal_entry_lines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        entry_id INT NOT NULL,
        linea INT NOT NULL,
        posting_key VARCHAR(10),
        cuenta_id INT NOT NULL,
        cuenta_codigo VARCHAR(20),
        special_gl VARCHAR(20) COMMENT 'A-anticipo D-documento inicial',
        debe DECIMAL(15,2) DEFAULT 0,
        haber DECIMAL(15,2) DEFAULT 0,
        debe_moneda_local DECIMAL(15,2) DEFAULT 0,
        haber_moneda_local DECIMAL(15,2) DEFAULT 0,
        moneda VARCHAR(3) DEFAULT 'CLP',
        tipo_cambio DECIMAL(10,4) DEFAULT 1,
        tercero_tipo VARCHAR(50) COMMENT 'customer vendor employee other',
        tercero_id INT,
        tercero_codigo VARCHAR(50),
        tercero_nombre VARCHAR(200),
        centro_costos_id INT,
        centro_costos_codigo VARCHAR(20),
        proyecto_id INT,
        proyecto_codigo VARCHAR(20),
        segmento_id INT,
        segmento_codigo VARCHAR(20),
        area_id INT,
        sucursal_id INT,
        pais VARCHAR(3),
        concepto TEXT,
        texto_posicion VARCHAR(200),
        assignment VARCHAR(50),
        referencia VARCHAR(100),
        baseline_date DATE COMMENT 'Fecha base para vencimiento',
        payment_terms VARCHAR(20),
        due_date DATE COMMENT 'Fecha de vencimiento',
        discount_date1 DATE,
        discount_percent1 DECIMAL(5,2),
        discount_date2 DATE,
        discount_percent2 DECIMAL(5,2),
        payment_block VARCHAR(10),
        payment_method VARCHAR(20),
        house_bank VARCHAR(20),
        bank_account VARCHAR(50),
        tax_code VARCHAR(10),
        tax_amount DECIMAL(15,2) DEFAULT 0,
        withholding_tax_type VARCHAR(10),
        withholding_tax_code VARCHAR(10),
        withholding_tax_amount DECIMAL(15,2) DEFAULT 0,
        cleared TINYINT(1) DEFAULT 0 COMMENT 'Compensado',
        clearing_document VARCHAR(20),
        clearing_date DATE,
        open_amount DECIMAL(15,2),
        invoice_reference VARCHAR(50),
        business_area VARCHAR(20),
        profit_center VARCHAR(20),
        functional_area VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (entry_id) REFERENCES acc_journal_entries(id) ON DELETE CASCADE,
        FOREIGN KEY (cuenta_id) REFERENCES acc_chart_of_accounts(id),
        INDEX idx_entry (entry_id),
        INDEX idx_cuenta (cuenta_id),
        INDEX idx_tercero (tercero_tipo, tercero_id),
        INDEX idx_centro_costos (centro_costos_id),
        INDEX idx_cleared (cleared),
        INDEX idx_due_date (due_date),
        INDEX idx_assignment (assignment)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de condiciones de pago (payment terms)
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_payment_terms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        code VARCHAR(20) NOT NULL,
        name VARCHAR(100) NOT NULL,
        descripcion TEXT,
        days_net INT DEFAULT 0,
        days_discount1 INT DEFAULT 0,
        discount_percent1 DECIMAL(5,2) DEFAULT 0,
        days_discount2 INT DEFAULT 0,
        discount_percent2 DECIMAL(5,2) DEFAULT 0,
        active TINYINT(1) DEFAULT 1,
        UNIQUE KEY uk_code_company (code, company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de codigos de impuestos (tax codes)
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_tax_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        code VARCHAR(10) NOT NULL,
        name VARCHAR(100) NOT NULL,
        tax_type VARCHAR(20) COMMENT 'IVA retencion otro',
        tax_percent DECIMAL(5,2) NOT NULL,
        account_id INT,
        active TINYINT(1) DEFAULT 1,
        UNIQUE KEY uk_code_company (code, company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de centros de costo
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_cost_centers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        code VARCHAR(20) NOT NULL,
        name VARCHAR(100) NOT NULL,
        descripcion TEXT,
        responsible_user_id INT,
        active TINYINT(1) DEFAULT 1,
        UNIQUE KEY uk_code_company (code, company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de proyectos
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        code VARCHAR(20) NOT NULL,
        name VARCHAR(100) NOT NULL,
        descripcion TEXT,
        start_date DATE,
        end_date DATE,
        status VARCHAR(20),
        UNIQUE KEY uk_code_company (code, company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de segmentos de negocio
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_segments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        code VARCHAR(20) NOT NULL,
        name VARCHAR(100) NOT NULL,
        descripcion TEXT,
        active TINYINT(1) DEFAULT 1,
        UNIQUE KEY uk_code_company (code, company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de saldos contables por periodo
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_account_balances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        cuenta_id INT NOT NULL,
        fiscal_year VARCHAR(4),
        periodo VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
        saldo_inicial_debe DECIMAL(15,2) DEFAULT 0,
        saldo_inicial_haber DECIMAL(15,2) DEFAULT 0,
        movimientos_debe DECIMAL(15,2) DEFAULT 0,
        movimientos_haber DECIMAL(15,2) DEFAULT 0,
        saldo_final_debe DECIMAL(15,2) DEFAULT 0,
        saldo_final_haber DECIMAL(15,2) DEFAULT 0,
        saldo_final DECIMAL(15,2) DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_cuenta_periodo (cuenta_id, periodo, company_id),
        INDEX idx_periodo (periodo),
        INDEX idx_fiscal_year (fiscal_year)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de partidas abiertas (open items)
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_open_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        entry_id INT NOT NULL,
        line_id INT NOT NULL,
        cuenta_id INT NOT NULL,
        tercero_tipo VARCHAR(50),
        tercero_id INT,
        document_number VARCHAR(20),
        document_date DATE,
        posting_date DATE,
        due_date DATE,
        payment_terms VARCHAR(20),
        original_amount DECIMAL(15,2),
        open_amount DECIMAL(15,2),
        currency VARCHAR(3),
        assignment VARCHAR(50),
        cleared TINYINT(1) DEFAULT 0,
        clearing_document VARCHAR(20),
        clearing_date DATE,
        days_overdue INT,
        aging_bucket VARCHAR(20),
        INDEX idx_tercero (tercero_tipo, tercero_id),
        INDEX idx_cleared (cleared),
        INDEX idx_due_date (due_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de auditoria
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        table_name VARCHAR(100),
        record_id INT,
        action ENUM('create', 'update', 'delete', 'post', 'reverse', 'clear') NOT NULL,
        old_values TEXT,
        new_values TEXT,
        user_id INT,
        username VARCHAR(100),
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_table (table_name, record_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de configuracion del modulo
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        config_key VARCHAR(100) NOT NULL,
        config_value TEXT,
        config_type VARCHAR(50),
        description TEXT,
        updated_by INT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_key_company (config_key, company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Insertar datos iniciales de configuracion
    $pdo->exec("INSERT IGNORE INTO acc_config (company_id, config_key, config_value, description) VALUES
        (1, 'company_name', 'CONECTA ERP', 'Nombre de la empresa'),
        (1, 'company_tax_id', '76.XXX.XXX-X', 'RUT de la empresa'),
        (1, 'fiscal_year_variant', '01', 'Variante ejercicio fiscal'),
        (1, 'currency_local', 'CLP', 'Moneda local'),
        (1, 'currency_group', 'USD,EUR,CLP', 'Monedas permitidas'),
        (1, 'decimal_places', '2', 'Decimales en importes'),
        (1, 'tax_reporting', 'monthly', 'Frecuencia reporte impuestos'),
        (1, 'chart_of_accounts_template', 'CHILE_IFRS', 'Plantilla plan de cuentas')");

    // Insertar tipos de documento iniciales
    $pdo->exec("INSERT IGNORE INTO acc_document_types (company_id, code, name, category) VALUES
        (1, 'SA', 'Documento Contable', 'accounting'),
        (1, 'DR', 'Factura Cliente', 'ar'),
        (1, 'DG', 'Abono Cliente', 'ar'),
        (1, 'DZ', 'Anticipo Cliente', 'ar'),
        (1, 'KR', 'Factura Proveedor', 'ap'),
        (1, 'KG', 'Abono Proveedor', 'ap'),
        (1, 'KZ', 'Anticipo Proveedor', 'ap'),
        (1, 'ZP', 'Pago', 'bank'),
        (1, 'ZV', 'Cobro', 'bank'),
        (1, 'AB', 'Asiento de Cierre', 'accounting'),
        (1, 'AA', 'Asiento de Apertura', 'accounting')");

    // Insertar claves de contabilizacion iniciales
    $pdo->exec("INSERT IGNORE INTO acc_posting_keys (company_id, key_code, name, type, account_type) VALUES
        (1, '01', 'Factura - Debe Cliente', 'debit', 'customer'),
        (1, '11', 'Abono - Haber Cliente', 'credit', 'customer'),
        (1, '19', 'Anticipo - Debe Cliente', 'debit', 'customer'),
        (1, '21', 'Factura - Debe Proveedor', 'debit', 'vendor'),
        (1, '31', 'Abono - Haber Proveedor', 'credit', 'vendor'),
        (1, '29', 'Anticipo - Haber Proveedor', 'credit', 'vendor'),
        (1, '40', 'Debe Cuenta Mayor', 'debit', 'gl'),
        (1, '50', 'Haber Cuenta Mayor', 'credit', 'gl'),
        (1, '70', 'Debe Activo Fijo', 'debit', 'asset'),
        (1, '75', 'Haber Activo Fijo', 'credit', 'asset')");

    // Insertar condiciones de pago iniciales
    $pdo->exec("INSERT IGNORE INTO acc_payment_terms (company_id, code, name, days_net, days_discount1, discount_percent1) VALUES
        (1, 'Z001', 'Pago inmediato', 0, 0, 0),
        (1, 'Z015', 'Neto 15 dias', 15, 0, 0),
        (1, 'Z030', 'Neto 30 dias', 30, 10, 2.0),
        (1, 'Z060', 'Neto 60 dias', 60, 10, 3.0),
        (1, 'Z090', 'Neto 90 dias', 90, 15, 5.0)");

} catch (PDOException $e) {
    $error = "Error al crear tablas: " . $e->getMessage();
}

// === GENERAR REPORTE EXCEL ===
if ($action === 'export_excel') {
    $report_type = $_GET['type'] ?? 'balance';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $report_type . '_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    if ($report_type === 'balance_8col') {
        // BALANCE DE 8 COLUMNAS
        echo "<html><head><meta charset='UTF-8'></head><body>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><td colspan='10' style='text-align:center; font-size:16px; font-weight:bold;'>BALANCE DE COMPROBACION DE 8 COLUMNAS</td></tr>";
        echo "<tr><td colspan='10' style='text-align:center; font-size:12px;'>CONECTA ERP</td></tr>";
        echo "<tr><td colspan='10' style='text-align:center; font-size:12px;'>Periodo: " . date('Y-m') . "</td></tr>";
        echo "<tr><td colspan='10'>&nbsp;</td></tr>";
        echo "<tr style='background-color: #4472C4; color: white; font-weight: bold;'>";
        echo "<td>Cuenta</td>";
        echo "<td>Nombre</td>";
        echo "<td>Saldo Inicial Debe</td>";
        echo "<td>Saldo Inicial Haber</td>";
        echo "<td>Movimientos Debe</td>";
        echo "<td>Movimientos Haber</td>";
        echo "<td>Saldo Final Debe</td>";
        echo "<td>Saldo Final Haber</td>";
        echo "<td>Activo</td>";
        echo "<td>Pasivo</td>";
        echo "</tr>";

        $stmt = $pdo->prepare("
            SELECT c.codigo_completo, c.nombre, c.naturaleza, c.tipo,
                   COALESCE(b.saldo_inicial_debe, 0) as si_debe,
                   COALESCE(b.saldo_inicial_haber, 0) as si_haber,
                   COALESCE(b.movimientos_debe, 0) as mov_debe,
                   COALESCE(b.movimientos_haber, 0) as mov_haber,
                   COALESCE(b.saldo_final_debe, 0) as sf_debe,
                   COALESCE(b.saldo_final_haber, 0) as sf_haber
            FROM acc_chart_of_accounts c
            LEFT JOIN acc_account_balances b ON c.id = b.cuenta_id AND b.periodo = ?
            WHERE c.company_id = ? AND c.permite_movimientos = 1
            ORDER BY c.codigo_completo
        ");
        $stmt->execute([date('Y-m'), $company_id]);
        $accounts = $stmt->fetchAll();
        $stmt->closeCursor();

        $total_si_debe = 0; $total_si_haber = 0;
        $total_mov_debe = 0; $total_mov_haber = 0;
        $total_sf_debe = 0; $total_sf_haber = 0;
        $total_activo = 0; $total_pasivo = 0;

        foreach ($accounts as $acc) {
            $saldo_final = $acc['sf_debe'] - $acc['sf_haber'];
            $activo = ($saldo_final > 0 && in_array($acc['tipo'], ['activo_corriente', 'activo_no_corriente'])) ? $saldo_final : 0;
            $pasivo = ($saldo_final < 0 || in_array($acc['tipo'], ['pasivo_corriente', 'pasivo_no_corriente', 'patrimonio'])) ? abs($saldo_final) : 0;

            echo "<tr>";
            echo "<td>" . htmlspecialchars($acc['codigo_completo']) . "</td>";
            echo "<td>" . htmlspecialchars($acc['nombre']) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($acc['si_debe'], 2) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($acc['si_haber'], 2) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($acc['mov_debe'], 2) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($acc['mov_haber'], 2) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($acc['sf_debe'], 2) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($acc['sf_haber'], 2) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($activo, 2) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($pasivo, 2) . "</td>";
            echo "</tr>";

            $total_si_debe += $acc['si_debe'];
            $total_si_haber += $acc['si_haber'];
            $total_mov_debe += $acc['mov_debe'];
            $total_mov_haber += $acc['mov_haber'];
            $total_sf_debe += $acc['sf_debe'];
            $total_sf_haber += $acc['sf_haber'];
            $total_activo += $activo;
            $total_pasivo += $pasivo;
        }

        echo "<tr style='background-color: #E7E6E6; font-weight: bold;'>";
        echo "<td colspan='2'>TOTALES</td>";
        echo "<td style='text-align:right;'>" . number_format($total_si_debe, 2) . "</td>";
        echo "<td style='text-align:right;'>" . number_format($total_si_haber, 2) . "</td>";
        echo "<td style='text-align:right;'>" . number_format($total_mov_debe, 2) . "</td>";
        echo "<td style='text-align:right;'>" . number_format($total_mov_haber, 2) . "</td>";
        echo "<td style='text-align:right;'>" . number_format($total_sf_debe, 2) . "</td>";
        echo "<td style='text-align:right;'>" . number_format($total_sf_haber, 2) . "</td>";
        echo "<td style='text-align:right;'>" . number_format($total_activo, 2) . "</td>";
        echo "<td style='text-align:right;'>" . number_format($total_pasivo, 2) . "</td>";
        echo "</tr>";
        echo "</table></body></html>";

    } elseif ($report_type === 'libro_mayor') {
        // LIBRO MAYOR GENERAL
        $cuenta_id = $_GET['cuenta_id'] ?? null;

        echo "<html><head><meta charset='UTF-8'></head><body>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><td colspan='10' style='text-align:center; font-size:16px; font-weight:bold;'>LIBRO MAYOR GENERAL</td></tr>";
        echo "<tr><td colspan='10' style='text-align:center; font-size:12px;'>CONECTA ERP</td></tr>";
        echo "<tr><td colspan='10' style='text-align:center; font-size:12px;'>Periodo: " . date('Y-m') . "</td></tr>";
        echo "<tr><td colspan='10'>&nbsp;</td></tr>";

        if ($cuenta_id) {
            $stmt = $pdo->prepare("SELECT * FROM acc_chart_of_accounts WHERE id = ?");
            $stmt->execute([$cuenta_id]);
            $cuenta = $stmt->fetch();
            $stmt->closeCursor();

            echo "<tr><td colspan='10'><strong>Cuenta: " . $cuenta['codigo_completo'] . " - " . $cuenta['nombre'] . "</strong></td></tr>";
        }

        echo "<tr style='background-color: #4472C4; color: white; font-weight: bold;'>";
        echo "<td>Fecha Doc.</td>";
        echo "<td>Fecha Cont.</td>";
        echo "<td>Documento</td>";
        echo "<td>Ref.</td>";
        echo "<td>Concepto</td>";
        echo "<td>Tercero</td>";
        echo "<td>Debe</td>";
        echo "<td>Haber</td>";
        echo "<td>Saldo</td>";
        echo "<td>Asignacion</td>";
        echo "</tr>";

        $where_clause = $cuenta_id ? "AND jel.cuenta_id = " . intval($cuenta_id) : "";

        $stmt = $pdo->prepare("
            SELECT je.fecha_documento, je.fecha_contabilizacion, je.numero_documento,
                   je.numero_referencia, je.concepto,
                   jel.debe, jel.haber, jel.tercero_nombre, jel.assignment,
                   c.codigo_completo, c.nombre as cuenta_nombre
            FROM acc_journal_entry_lines jel
            INNER JOIN acc_journal_entries je ON jel.entry_id = je.id
            INNER JOIN acc_chart_of_accounts c ON jel.cuenta_id = c.id
            WHERE je.company_id = ? AND je.estado = 'contabilizado'
            AND je.periodo = ? $where_clause
            ORDER BY je.fecha_contabilizacion, je.numero_documento, jel.linea
        ");
        $stmt->execute([$company_id, date('Y-m')]);
        $lines = $stmt->fetchAll();
        $stmt->closeCursor();

        $saldo = 0;
        foreach ($lines as $line) {
            $saldo += $line['debe'] - $line['haber'];
            echo "<tr>";
            echo "<td>" . date('d/m/Y', strtotime($line['fecha_documento'])) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($line['fecha_contabilizacion'])) . "</td>";
            echo "<td>" . htmlspecialchars($line['numero_documento']) . "</td>";
            echo "<td>" . htmlspecialchars($line['numero_referencia']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($line['concepto'], 0, 50)) . "</td>";
            echo "<td>" . htmlspecialchars($line['tercero_nombre'] ?? '') . "</td>";
            echo "<td style='text-align:right;'>" . number_format($line['debe'], 2) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($line['haber'], 2) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($saldo, 2) . "</td>";
            echo "<td>" . htmlspecialchars($line['assignment'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table></body></html>";

    } elseif ($report_type === 'aging') {
        // ANTIGUEDAD DE SALDOS (AR/AP AGING)
        $tipo = $_GET['tipo'] ?? 'customer';
        $titulo = $tipo === 'customer' ? 'CLIENTES' : 'PROVEEDORES';

        echo "<html><head><meta charset='UTF-8'></head><body>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><td colspan='8' style='text-align:center; font-size:16px; font-weight:bold;'>ANTIGUEDAD DE SALDOS - $titulo</td></tr>";
        echo "<tr><td colspan='8' style='text-align:center; font-size:12px;'>CONECTA ERP - Al: " . date('d/m/Y') . "</td></tr>";
        echo "<tr><td colspan='8'>&nbsp;</td></tr>";
        echo "<tr style='background-color: #4472C4; color: white; font-weight: bold;'>";
        echo "<td>Codigo</td>";
        echo "<td>Nombre</td>";
        echo "<td>Al Dia</td>";
        echo "<td>1-30 dias</td>";
        echo "<td>31-60 dias</td>";
        echo "<td>61-90 dias</td>";
        echo "<td>Mas 90 dias</td>";
        echo "<td>Total</td>";
        echo "</tr>";

        $stmt = $pdo->prepare("
            SELECT jel.tercero_codigo, jel.tercero_nombre,
                   SUM(CASE WHEN DATEDIFF(CURDATE(), jel.due_date) <= 0 THEN jel.open_amount ELSE 0 END) as aldia,
                   SUM(CASE WHEN DATEDIFF(CURDATE(), jel.due_date) BETWEEN 1 AND 30 THEN jel.open_amount ELSE 0 END) as d30,
                   SUM(CASE WHEN DATEDIFF(CURDATE(), jel.due_date) BETWEEN 31 AND 60 THEN jel.open_amount ELSE 0 END) as d60,
                   SUM(CASE WHEN DATEDIFF(CURDATE(), jel.due_date) BETWEEN 61 AND 90 THEN jel.open_amount ELSE 0 END) as d90,
                   SUM(CASE WHEN DATEDIFF(CURDATE(), jel.due_date) > 90 THEN jel.open_amount ELSE 0 END) as d90plus,
                   SUM(jel.open_amount) as total
            FROM acc_journal_entry_lines jel
            INNER JOIN acc_journal_entries je ON jel.entry_id = je.id
            WHERE je.company_id = ? AND jel.tercero_tipo = ? AND jel.cleared = 0
            GROUP BY jel.tercero_codigo, jel.tercero_nombre
            HAVING total <> 0
            ORDER BY total DESC
        ");
        $stmt->execute([$company_id, $tipo]);
        $items = $stmt->fetchAll();
        $stmt->closeCursor();

        $t_aldia = 0; $t_d30 = 0; $t_d60 = 0; $t_d90 = 0; $t_d90plus = 0; $t_total = 0;

        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['tercero_codigo']) . "</td>";
            echo "<td>" . htmlspecialchars($item['tercero_nombre']) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($item['aldia'], 2) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($item['d30'], 2) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($item['d60'], 2) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($item['d90'], 2) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($item['d90plus'], 2) . "</td>";
            echo "<td style='text-align:right;'><strong>" . number_format($item['total'], 2) . "</strong></td>";
            echo "</tr>";

            $t_aldia += $item['aldia'];
            $t_d30 += $item['d30'];
            $t_d60 += $item['d60'];
            $t_d90 += $item['d90'];
            $t_d90plus += $item['d90plus'];
            $t_total += $item['total'];
        }

        echo "<tr style='background-color: #E7E6E6; font-weight: bold;'>";
        echo "<td colspan='2'>TOTALES</td>";
        echo "<td style='text-align:right;'>" . number_format($t_aldia, 2) . "</td>";
        echo "<td style='text-align:right;'>" . number_format($t_d30, 2) . "</td>";
        echo "<td style='text-align:right;'>" . number_format($t_d60, 2) . "</td>";
        echo "<td style='text-align:right;'>" . number_format($t_d90, 2) . "</td>";
        echo "<td style='text-align:right;'>" . number_format($t_d90plus, 2) . "</td>";
        echo "<td style='text-align:right;'>" . number_format($t_total, 2) . "</td>";
        echo "</tr>";
        echo "</table></body></html>";

    } elseif ($report_type === 'estado_resultados') {
        // ESTADO DE RESULTADOS
        echo "<html><head><meta charset='UTF-8'></head><body>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><td colspan='3' style='text-align:center; font-size:16px; font-weight:bold;'>ESTADO DE RESULTADOS</td></tr>";
        echo "<tr><td colspan='3' style='text-align:center; font-size:12px;'>CONECTA ERP</td></tr>";
        echo "<tr><td colspan='3' style='text-align:center; font-size:12px;'>Periodo: " . date('Y-m') . "</td></tr>";
        echo "<tr><td colspan='3'>&nbsp;</td></tr>";
        echo "<tr style='background-color: #4472C4; color: white; font-weight: bold;'>";
        echo "<td>Cuenta</td>";
        echo "<td>Nombre</td>";
        echo "<td>Importe</td>";
        echo "</tr>";

        // Ingresos
        echo "<tr style='background-color: #70AD47; font-weight: bold;'><td colspan='3'>INGRESOS OPERACIONALES</td></tr>";
        $stmt = $pdo->prepare("
            SELECT c.codigo_completo, c.nombre, COALESCE(b.saldo_final_haber - b.saldo_final_debe, 0) as saldo
            FROM acc_chart_of_accounts c
            LEFT JOIN acc_account_balances b ON c.id = b.cuenta_id AND b.periodo = ?
            WHERE c.company_id = ? AND c.tipo LIKE '%ingreso%'
            ORDER BY c.codigo_completo
        ");
        $stmt->execute([date('Y-m'), $company_id]);
        $ingresos = $stmt->fetchAll();
        $stmt->closeCursor();

        $total_ingresos = 0;
        foreach ($ingresos as $ing) {
            if ($ing['saldo'] != 0) {
                echo "<tr><td>" . $ing['codigo_completo'] . "</td><td>" . $ing['nombre'] . "</td><td style='text-align:right;'>" . number_format($ing['saldo'], 2) . "</td></tr>";
                $total_ingresos += $ing['saldo'];
            }
        }
        echo "<tr style='font-weight:bold;'><td colspan='2'>TOTAL INGRESOS</td><td style='text-align:right;'>" . number_format($total_ingresos, 2) . "</td></tr>";

        // Gastos
        echo "<tr style='background-color: #FFC000; font-weight: bold;'><td colspan='3'>GASTOS OPERACIONALES</td></tr>";
        $stmt = $pdo->prepare("
            SELECT c.codigo_completo, c.nombre, COALESCE(b.saldo_final_debe - b.saldo_final_haber, 0) as saldo
            FROM acc_chart_of_accounts c
            LEFT JOIN acc_account_balances b ON c.id = b.cuenta_id AND b.periodo = ?
            WHERE c.company_id = ? AND c.tipo LIKE '%gasto%'
            ORDER BY c.codigo_completo
        ");
        $stmt->execute([date('Y-m'), $company_id]);
        $gastos = $stmt->fetchAll();
        $stmt->closeCursor();

        $total_gastos = 0;
        foreach ($gastos as $gasto) {
            if ($gasto['saldo'] != 0) {
                echo "<tr><td>" . $gasto['codigo_completo'] . "</td><td>" . $gasto['nombre'] . "</td><td style='text-align:right;'>" . number_format($gasto['saldo'], 2) . "</td></tr>";
                $total_gastos += $gasto['saldo'];
            }
        }
        echo "<tr style='font-weight:bold;'><td colspan='2'>TOTAL GASTOS</td><td style='text-align:right;'>" . number_format($total_gastos, 2) . "</td></tr>";

        $utilidad = $total_ingresos - $total_gastos;
        echo "<tr style='background-color: #5B9BD5; color: white; font-weight: bold; font-size: 14px;'>";
        echo "<td colspan='2'>UTILIDAD / PERDIDA DEL PERIODO</td>";
        echo "<td style='text-align:right;'>" . number_format($utilidad, 2) . "</td>";
        echo "</tr>";

        echo "</table></body></html>";
    }

    exit;
}

// === CREAR CUENTA ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create_account') {
    try {
        $codigo = trim($_POST['codigo_completo']);
        $nivel = intval($_POST['nivel']);
        $nombre = trim($_POST['nombre']);

        $stmt = $pdo->prepare("
            INSERT INTO acc_chart_of_accounts (
                company_id, codigo_completo, nivel, parent_id, nombre, nombre_ingles,
                descripcion, naturaleza, tipo, subtipo, account_group, moneda,
                permite_movimientos, requiere_tercero, tipo_tercero, requiere_centro_costos,
                requiere_proyecto, clasificacion_ifrs, clasificacion_flujo_caja,
                reconcile_account, open_item_management, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $company_id, $codigo, $nivel,
            !empty($_POST['parent_id']) ? $_POST['parent_id'] : null,
            $nombre,
            $_POST['nombre_ingles'] ?? null,
            $_POST['descripcion'] ?? null,
            $_POST['naturaleza'],
            $_POST['tipo'] ?? null,
            $_POST['subtipo'] ?? null,
            $_POST['account_group'] ?? null,
            $_POST['moneda'] ?? 'CLP',
            isset($_POST['permite_movimientos']) ? 1 : 0,
            isset($_POST['requiere_tercero']) ? 1 : 0,
            $_POST['tipo_tercero'] ?? null,
            isset($_POST['requiere_centro_costos']) ? 1 : 0,
            isset($_POST['requiere_proyecto']) ? 1 : 0,
            $_POST['clasificacion_ifrs'] ?? null,
            $_POST['clasificacion_flujo_caja'] ?? null,
            isset($_POST['reconcile_account']) ? 1 : 0,
            isset($_POST['open_item_management']) ? 1 : 0,
            $user_id
        ]);
        $stmt->closeCursor();

        $message = "Cuenta creada exitosamente: $codigo - $nombre";

    } catch (Exception $e) {
        $error = "Error al crear cuenta: " . $e->getMessage();
    }
}

// === CREAR ASIENTO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create_entry') {
    try {
        $pdo->beginTransaction();

        $fecha_doc = $_POST['fecha_documento'];
        $fecha_cont = $_POST['fecha_contabilizacion'];
        $periodo = date('Y-m', strtotime($fecha_cont));
        $doc_type = $_POST['document_type'] ?? 'SA';

        // Generar numero de documento
        $stmt = $pdo->prepare("SELECT next_number FROM acc_document_types WHERE code = ? AND company_id = ?");
        $stmt->execute([$doc_type, $company_id]);
        $doc_config = $stmt->fetch();
        $stmt->closeCursor();

        $numero_doc = $doc_type . '-' . str_pad($doc_config['next_number'], 10, '0', STR_PAD_LEFT);

        // Actualizar contador
        $stmt = $pdo->prepare("UPDATE acc_document_types SET next_number = next_number + 1 WHERE code = ? AND company_id = ?");
        $stmt->execute([$doc_type, $company_id]);
        $stmt->closeCursor();

        // Crear encabezado
        $stmt = $pdo->prepare("
            INSERT INTO acc_journal_entries (
                company_id, numero_documento, numero_referencia, fecha_documento, fecha_contabilizacion,
                periodo, fiscal_year, tipo, concepto, texto_cabecera, moneda, estado, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'borrador', ?)
        ");
        $stmt->execute([
            $company_id, $numero_doc,
            $_POST['numero_referencia'] ?? null,
            $fecha_doc, $fecha_cont, $periodo,
            date('Y', strtotime($fecha_cont)),
            $_POST['tipo'] ?? 'manual',
            $_POST['concepto'],
            $_POST['texto_cabecera'] ?? null,
            $_POST['moneda'] ?? 'CLP',
            $user_id
        ]);
        $entry_id = $pdo->lastInsertId();
        $stmt->closeCursor();

        // Procesar lineas
        $total_debe = 0;
        $total_haber = 0;

        if (isset($_POST['cuenta_id']) && is_array($_POST['cuenta_id'])) {
            for ($i = 0; $i < count($_POST['cuenta_id']); $i++) {
                if (empty($_POST['cuenta_id'][$i])) continue;

                $debe = floatval($_POST['debe'][$i] ?? 0);
                $haber = floatval($_POST['haber'][$i] ?? 0);

                if ($debe == 0 && $haber == 0) continue;

                $stmt = $pdo->prepare("
                    INSERT INTO acc_journal_entry_lines (
                        entry_id, linea, posting_key, cuenta_id, debe, haber,
                        concepto, assignment, due_date, open_amount
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $entry_id, $i + 1,
                    $_POST['posting_key'][$i] ?? '40',
                    $_POST['cuenta_id'][$i],
                    $debe, $haber,
                    $_POST['concepto_linea'][$i] ?? '',
                    $_POST['assignment'][$i] ?? '',
                    $_POST['due_date'][$i] ?? null,
                    $debe > 0 ? $debe : $haber
                ]);
                $stmt->closeCursor();

                $total_debe += $debe;
                $total_haber += $haber;
            }
        }

        // Actualizar totales
        $stmt = $pdo->prepare("UPDATE acc_journal_entries SET total_debe = ?, total_haber = ? WHERE id = ?");
        $stmt->execute([$total_debe, $total_haber, $entry_id]);
        $stmt->closeCursor();

        // Validar balance
        if (abs($total_debe - $total_haber) > 0.01) {
            throw new Exception("El documento no cuadra. Debe: $total_debe, Haber: $total_haber");
        }

        $pdo->commit();
        $message = "Documento creado: $numero_doc";

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// === CONTABILIZAR DOCUMENTO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'post_entry') {
    try {
        $entry_id = intval($_POST['entry_id']);
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT * FROM acc_journal_entries WHERE id = ? AND company_id = ?");
        $stmt->execute([$entry_id, $company_id]);
        $entry = $stmt->fetch();
        $stmt->closeCursor();

        if (!$entry['cuadrado']) {
            throw new Exception("El documento no cuadra");
        }

        // Actualizar estado
        $stmt = $pdo->prepare("
            UPDATE acc_journal_entries
            SET estado = 'contabilizado', fecha_contabilizacion_sistema = NOW(), contabilizado_por = ?
            WHERE id = ?
        ");
        $stmt->execute([$user_id, $entry_id]);
        $stmt->closeCursor();

        // Actualizar saldos
        $stmt = $pdo->prepare("SELECT * FROM acc_journal_entry_lines WHERE entry_id = ?");
        $stmt->execute([$entry_id]);
        $lines = $stmt->fetchAll();
        $stmt->closeCursor();

        foreach ($lines as $line) {
            $periodo = $entry['periodo'];
            $cuenta_id = $line['cuenta_id'];

            $stmt = $pdo->prepare("
                SELECT id FROM acc_account_balances
                WHERE cuenta_id = ? AND periodo = ? AND company_id = ?
            ");
            $stmt->execute([$cuenta_id, $periodo, $company_id]);
            $balance = $stmt->fetch();
            $stmt->closeCursor();

            if ($balance) {
                $stmt = $pdo->prepare("
                    UPDATE acc_account_balances
                    SET movimientos_debe = movimientos_debe + ?,
                        movimientos_haber = movimientos_haber + ?,
                        saldo_final_debe = saldo_inicial_debe + movimientos_debe + ?,
                        saldo_final_haber = saldo_inicial_haber + movimientos_haber + ?,
                        saldo_final = (saldo_inicial_debe + movimientos_debe + ?) - (saldo_inicial_haber + movimientos_haber + ?)
                    WHERE id = ?
                ");
                $stmt->execute([
                    $line['debe'], $line['haber'],
                    $line['debe'], $line['haber'],
                    $line['debe'], $line['haber'],
                    $balance['id']
                ]);
                $stmt->closeCursor();
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO acc_account_balances (
                        company_id, cuenta_id, fiscal_year, periodo,
                        movimientos_debe, movimientos_haber,
                        saldo_final_debe, saldo_final_haber, saldo_final
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $company_id, $cuenta_id, $entry['fiscal_year'], $periodo,
                    $line['debe'], $line['haber'],
                    $line['debe'], $line['haber'],
                    $line['debe'] - $line['haber']
                ]);
                $stmt->closeCursor();
            }
        }

        $pdo->commit();
        $message = "Documento contabilizado exitosamente";

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// === GUARDAR CONFIGURACION ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_config') {
    try {
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'config_') === 0) {
                $config_key = str_replace('config_', '', $key);

                $stmt = $pdo->prepare("
                    INSERT INTO acc_config (company_id, config_key, config_value, updated_by)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE config_value = ?, updated_by = ?
                ");
                $stmt->execute([$company_id, $config_key, $value, $user_id, $value, $user_id]);
                $stmt->closeCursor();
            }
        }

        $message = "Configuracion guardada exitosamente";

    } catch (Exception $e) {
        $error = "Error al guardar: " . $e->getMessage();
    }
}

// === OBTENER DATOS ===
$stats = [];
$recent_entries = [];
$accounts = [];
$entries = [];
$config_values = [];

try {
    // Estadisticas
    $stmt = $pdo->prepare("
        SELECT
            COUNT(DISTINCT id) as total_cuentas,
            SUM(CASE WHEN activa = 1 THEN 1 ELSE 0 END) as cuentas_activas
        FROM acc_chart_of_accounts
        WHERE company_id = ?
    ");
    $stmt->execute([$company_id]);
    $stats = $stmt->fetch();
    $stmt->closeCursor();

    // Documentos recientes
    $stmt = $pdo->prepare("
        SELECT * FROM acc_journal_entries
        WHERE company_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$company_id]);
    $recent_entries = $stmt->fetchAll();
    $stmt->closeCursor();

    // Plan de cuentas
    if ($action === 'accounts') {
        $stmt = $pdo->prepare("
            SELECT * FROM acc_chart_of_accounts
            WHERE company_id = ?
            ORDER BY codigo_completo
        ");
        $stmt->execute([$company_id]);
        $accounts = $stmt->fetchAll();
        $stmt->closeCursor();
    }

    // Documentos contables
    if ($action === 'entries') {
        $stmt = $pdo->prepare("
            SELECT * FROM acc_journal_entries
            WHERE company_id = ?
            ORDER BY fecha_contabilizacion DESC
            LIMIT 100
        ");
        $stmt->execute([$company_id]);
        $entries = $stmt->fetchAll();
        $stmt->closeCursor();
    }

    // Configuracion
    if ($action === 'settings') {
        $stmt = $pdo->prepare("SELECT * FROM acc_config WHERE company_id = ?");
        $stmt->execute([$company_id]);
        $configs = $stmt->fetchAll();
        $stmt->closeCursor();

        foreach ($configs as $cfg) {
            $config_values[$cfg['config_key']] = $cfg['config_value'];
        }
    }

} catch (PDOException $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contabilidad Financiera - CONECTA ERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: #f5f5f5; color: #1a1a1a; line-height: 1.6; }
        .header { background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%); color: white; padding: 1.5rem 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .header h1 { font-size: 1.75rem; font-weight: 600; margin-bottom: 0.25rem; }
        .header p { opacity: 0.95; font-size: 0.9rem; }
        .tabs { background: white; padding: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 2rem; display: flex; gap: 0; }
        .tabs a { flex: 1; text-align: center; padding: 1.25rem 2rem; color: #4b5563; text-decoration: none; font-weight: 600; font-size: 1.1rem; transition: all 0.2s; border-bottom: 3px solid transparent; background: #f9fafb; }
        .tabs a:hover { background: #e5e7eb; color: #1e40af; }
        .tabs a.active { color: #1e40af; border-bottom-color: #1e40af; background: white; }
        .container { max-width: 1600px; margin: 0 auto; padding: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #1e40af; }
        .stat-card h3 { font-size: 0.8rem; color: #6b7280; margin-bottom: 0.5rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: #1f2937; }
        .stat-card .label { font-size: 0.85rem; color: #9ca3af; margin-top: 0.25rem; }
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 1.5rem; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb; }
        .card-header h2 { font-size: 1.25rem; color: #1f2937; font-weight: 600; }
        .btn { padding: 0.625rem 1.25rem; border: none; border-radius: 6px; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background: #1e40af; color: white; }
        .btn-primary:hover { background: #1e3a8a; }
        .btn-success { background: #059669; color: white; }
        .btn-success:hover { background: #047857; }
        .btn-secondary { background: #f3f4f6; color: #374151; }
        .btn-secondary:hover { background: #e5e7eb; }
        .btn-small { padding: 0.375rem 0.75rem; font-size: 0.813rem; }
        .btn-export { background: #10b981; color: white; }
        .btn-export:hover { background: #059669; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th { background: #f9fafb; padding: 0.75rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 2px solid #e5e7eb; }
        td { padding: 0.75rem; border-bottom: 1px solid #f3f4f6; }
        tr:hover { background: #f9fafb; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151; font-size: 0.875rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.625rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #1e40af; box-shadow: 0 0 0 3px rgba(30,64,175,0.1); }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .checkbox-group { display: flex; align-items: center; gap: 0.5rem; }
        .checkbox-group input[type="checkbox"] { width: auto; }
        .alert { padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem; font-weight: 500; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .config-section { background: #f9fafb; padding: 1.5rem; border-radius: 6px; margin-bottom: 1.5rem; }
        .config-section h3 { font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 1rem; }
        .report-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; }
        .report-card { background: white; border: 2px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; transition: all 0.2s; }
        .report-card:hover { border-color: #1e40af; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .report-card h3 { font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem; }
        .report-card p { font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Contabilidad Financiera - SAP FI Level</h1>
        <p>Gestion contable empresarial con plan de cuentas, asientos, subledgers, clearing y reportes</p>
    </div>

    <div class="tabs">
        <a href="?action=accounts" class="<?php echo $action === 'accounts' ? 'active' : ''; ?>">Cuentas</a>
        <a href="?action=entries" class="<?php echo $action === 'entries' ? 'active' : ''; ?>">Asientos Contables</a>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($action === 'accounts'): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Cuentas Contables</h3>
                <div class="value"><?php echo $stats['total_cuentas'] ?? 0; ?></div>
                <div class="label">en plan de cuentas</div>
            </div>
            <div class="stat-card">
                <h3>Cuentas Activas</h3>
                <div class="value"><?php echo $stats['cuentas_activas'] ?? 0; ?></div>
                <div class="label">actualmente activas</div>
            </div>
            <div class="stat-card">
                <h3>Ejercicio Fiscal</h3>
                <div class="value"><?php echo date('Y'); ?></div>
                <div class="label">periodo actual</div>
            </div>
            <div class="stat-card">
                <h3>Sistema</h3>
                <div class="value">OK</div>
                <div class="label">operativo</div>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem; display: flex; gap: 1rem;">
            <a href="?action=export_excel&type=balance_8col" class="btn btn-export">Balance 8 Columnas</a>
            <a href="?action=export_excel&type=libro_mayor" class="btn btn-export">Libro Mayor</a>
            <a href="?action=export_excel&type=estado_resultados" class="btn btn-export">Estado Resultados</a>
            <a href="?action=settings" class="btn btn-secondary">Configuracion</a>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>Plan de Cuentas</h2>
                <button onclick="alert('Funcion de creacion de cuentas')" class="btn btn-primary">Nueva Cuenta</button>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Naturaleza</th>
                            <th>Movimientos</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $account): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($account['codigo_completo']); ?></strong></td>
                            <td><?php echo htmlspecialchars($account['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($account['tipo'] ?? 'N/A'); ?></td>
                            <td><?php echo strtoupper($account['naturaleza']); ?></td>
                            <td><?php echo $account['permite_movimientos'] ? 'Si' : 'No'; ?></td>
                            <td><span class="badge badge-<?php echo $account['activa'] ? 'success' : 'danger'; ?>"><?php echo $account['activa'] ? 'ACTIVA' : 'INACTIVA'; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($action === 'entries'): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Documentos del Mes</h3>
                <div class="value"><?php echo count($recent_entries); ?></div>
                <div class="label">registros contables</div>
            </div>
            <div class="stat-card">
                <h3>Ejercicio Fiscal</h3>
                <div class="value"><?php echo date('Y'); ?></div>
                <div class="label">periodo actual</div>
            </div>
            <div class="stat-card">
                <h3>Periodo</h3>
                <div class="value"><?php echo date('m'); ?></div>
                <div class="label">mes actual</div>
            </div>
            <div class="stat-card">
                <h3>Sistema</h3>
                <div class="value">OK</div>
                <div class="label">operativo</div>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem; display: flex; gap: 1rem;">
            <a href="?action=export_excel&type=aging&tipo=customer" class="btn btn-export">Antiguedad Clientes</a>
            <a href="?action=export_excel&type=aging&tipo=vendor" class="btn btn-export">Antiguedad Proveedores</a>
            <a href="?action=export_excel&type=balance_general" class="btn btn-export">Balance General</a>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Asientos Contables</h2>
                <button onclick="alert('Funcion de creacion de documentos')" class="btn btn-primary">Nuevo Asiento</button>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Numero</th>
                            <th>Fecha Doc.</th>
                            <th>Fecha Cont.</th>
                            <th>Concepto</th>
                            <th>Debe</th>
                            <th>Haber</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($entry['numero_documento']); ?></strong></td>
                            <td><?php echo date('d/m/Y', strtotime($entry['fecha_documento'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($entry['fecha_contabilizacion'])); ?></td>
                            <td><?php echo htmlspecialchars(substr($entry['concepto'], 0, 50)); ?></td>
                            <td>$<?php echo number_format($entry['total_debe'], 2); ?></td>
                            <td>$<?php echo number_format($entry['total_haber'], 2); ?></td>
                            <td><span class="badge badge-<?php echo $entry['estado'] === 'contabilizado' ? 'success' : 'warning'; ?>"><?php echo strtoupper($entry['estado']); ?></span></td>
                            <td>
                                <?php if ($entry['estado'] === 'borrador'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="post_entry">
                                    <input type="hidden" name="entry_id" value="<?php echo $entry['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-small">Contabilizar</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($action === 'settings'): ?>
        <div style="margin-bottom: 1.5rem;">
            <a href="?action=accounts" class="btn btn-secondary">Volver a Cuentas</a>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>Configuracion del Modulo Contable</h2>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="save_config">

                <div class="config-section">
                    <h3>Datos de la Empresa</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombre de la Empresa *</label>
                            <input type="text" name="config_company_name" value="<?php echo htmlspecialchars($config_values['company_name'] ?? 'CONECTA ERP'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>RUT / Tax ID *</label>
                            <input type="text" name="config_company_tax_id" value="<?php echo htmlspecialchars($config_values['company_tax_id'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Moneda Local *</label>
                            <select name="config_currency_local">
                                <option value="CLP" <?php echo ($config_values['currency_local'] ?? 'CLP') === 'CLP' ? 'selected' : ''; ?>>CLP - Peso Chileno</option>
                                <option value="USD" <?php echo ($config_values['currency_local'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD - Dolar</option>
                                <option value="EUR" <?php echo ($config_values['currency_local'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="config-section">
                    <h3>Parametros Contables</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Variante Ejercicio Fiscal</label>
                            <input type="text" name="config_fiscal_year_variant" value="<?php echo htmlspecialchars($config_values['fiscal_year_variant'] ?? '01'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Decimales en Importes</label>
                            <select name="config_decimal_places">
                                <option value="0" <?php echo ($config_values['decimal_places'] ?? '2') === '0' ? 'selected' : ''; ?>>0</option>
                                <option value="2" <?php echo ($config_values['decimal_places'] ?? '2') === '2' ? 'selected' : ''; ?>>2</option>
                                <option value="4" <?php echo ($config_values['decimal_places'] ?? '2') === '4' ? 'selected' : ''; ?>>4</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Frecuencia Reporte Impuestos</label>
                            <select name="config_tax_reporting">
                                <option value="monthly" <?php echo ($config_values['tax_reporting'] ?? 'monthly') === 'monthly' ? 'selected' : ''; ?>>Mensual</option>
                                <option value="quarterly" <?php echo ($config_values['tax_reporting'] ?? '') === 'quarterly' ? 'selected' : ''; ?>>Trimestral</option>
                                <option value="yearly" <?php echo ($config_values['tax_reporting'] ?? '') === 'yearly' ? 'selected' : ''; ?>>Anual</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="config-section">
                    <h3>Integraciones</h3>
                    <div class="form-grid">
                        <div class="checkbox-group">
                            <input type="checkbox" name="config_integration_sales" id="int_sales" value="1">
                            <label for="int_sales">Integracion automatica con Ventas</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="config_integration_purchases" id="int_purch" value="1">
                            <label for="int_purch">Integracion automatica con Compras</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="config_integration_inventory" id="int_inv" value="1">
                            <label for="int_inv">Integracion automatica con Inventario</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="config_integration_payroll" id="int_pay" value="1">
                            <label for="int_pay">Integracion automatica con Nomina</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="config_integration_production" id="int_prod" value="1">
                            <label for="int_prod">Integracion automatica con Produccion</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="config_integration_assets" id="int_asset" value="1">
                            <label for="int_asset">Integracion automatica con Activos Fijos</label>
                        </div>
                    </div>
                </div>

                <div class="config-section">
                    <h3>Seguridad y Auditoria</h3>
                    <div class="form-grid">
                        <div class="checkbox-group">
                            <input type="checkbox" name="config_require_approval" id="req_appr" value="1">
                            <label for="req_appr">Requerir aprobacion para contabilizar</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="config_enable_audit_log" id="audit_log" value="1" checked>
                            <label for="audit_log">Habilitar log de auditoria completo</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="config_lock_past_periods" id="lock_past" value="1">
                            <label for="lock_past">Bloquear periodos cerrados</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">Guardar Configuracion</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
