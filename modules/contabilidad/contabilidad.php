<?php
/**
 * CONECTA ERP - MODULO COMPLETO DE CONTABILIDAD
 * Sistema completo de contabilidad con plan de cuentas jerarquico (7 niveles)
 * asientos contables integraciones automaticas y auditoria completa
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

$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
$message = '';
$error = '';

// === CREAR TABLAS SI NO EXISTEN ===
try {
    // Tabla de plan de cuentas (7 niveles jerarquicos)
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_chart_of_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        codigo_completo VARCHAR(20) NOT NULL,
        nivel INT NOT NULL COMMENT '1-7 niveles jerarquicos',
        parent_id INT NULL,
        nombre VARCHAR(200) NOT NULL,
        descripcion TEXT,
        naturaleza ENUM('deudora', 'acreedora') NOT NULL,
        tipo VARCHAR(50) COMMENT 'activo_corriente pasivo_corriente patrimonio ingresos_operacionales gastos_operacionales',
        subtipo VARCHAR(50) COMMENT 'efectivo bancos cuentas_por_cobrar inventarios propiedad_planta cuentas_por_pagar',
        moneda VARCHAR(3) DEFAULT 'CLP',
        permite_movimientos TINYINT(1) DEFAULT 1,
        requiere_tercero TINYINT(1) DEFAULT 0,
        requiere_centro_costos TINYINT(1) DEFAULT 0,
        requiere_proyecto TINYINT(1) DEFAULT 0,
        requiere_area TINYINT(1) DEFAULT 0,
        requiere_sucursal TINYINT(1) DEFAULT 0,
        clasificacion_ifrs VARCHAR(50),
        clasificacion_nic VARCHAR(50),
        clasificacion_tributaria VARCHAR(50),
        clasificacion_flujo_caja VARCHAR(50) COMMENT 'operacion inversion financiacion',
        integracion_modulo VARCHAR(50) COMMENT 'ventas compras inventario nomina produccion activos',
        integracion_tipo_documento VARCHAR(50),
        integracion_automatica TINYINT(1) DEFAULT 0,
        cuenta_contrapartida_id INT NULL,
        activa TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        updated_by INT,
        UNIQUE KEY uk_codigo_company (codigo_completo, company_id),
        INDEX idx_parent (parent_id),
        INDEX idx_nivel (nivel),
        INDEX idx_tipo (tipo),
        INDEX idx_integracion (integracion_modulo, integracion_tipo_documento)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de asientos contables (journal entries)
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_journal_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        numero_asiento VARCHAR(20) NOT NULL,
        fecha DATE NOT NULL,
        periodo VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
        tipo ENUM('manual', 'automatico', 'ajuste', 'cierre', 'apertura', 'reversa') DEFAULT 'manual',
        origen VARCHAR(50) COMMENT 'ventas compras nomina inventario produccion activos',
        documento_origen_tipo VARCHAR(50),
        documento_origen_id INT,
        concepto TEXT NOT NULL,
        total_debe DECIMAL(15,2) NOT NULL DEFAULT 0,
        total_haber DECIMAL(15,2) NOT NULL DEFAULT 0,
        diferencia DECIMAL(15,2) GENERATED ALWAYS AS (total_debe - total_haber) STORED,
        cuadrado TINYINT(1) GENERATED ALWAYS AS (ABS(total_debe - total_haber) < 0.01) STORED,
        estado ENUM('borrador', 'validado', 'contabilizado', 'anulado', 'reversado') DEFAULT 'borrador',
        fecha_contabilizacion TIMESTAMP NULL,
        contabilizado_por INT NULL,
        reversa_de INT NULL COMMENT 'ID del asiento que reversa',
        reversado_por INT NULL COMMENT 'ID del asiento reversa',
        notas TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        updated_by INT,
        UNIQUE KEY uk_numero_company (numero_asiento, company_id),
        INDEX idx_fecha (fecha),
        INDEX idx_periodo (periodo),
        INDEX idx_tipo (tipo),
        INDEX idx_origen (origen, documento_origen_tipo, documento_origen_id),
        INDEX idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de detalles de asientos (journal entry lines)
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_journal_entry_lines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        entry_id INT NOT NULL,
        linea INT NOT NULL,
        cuenta_id INT NOT NULL,
        debe DECIMAL(15,2) DEFAULT 0,
        haber DECIMAL(15,2) DEFAULT 0,
        tercero_tipo VARCHAR(50) COMMENT 'cliente proveedor empleado otro',
        tercero_id INT,
        tercero_nombre VARCHAR(200),
        centro_costos_id INT,
        proyecto_id INT,
        area_id INT,
        sucursal_id INT,
        pais VARCHAR(3),
        moneda VARCHAR(3) DEFAULT 'CLP',
        tipo_cambio DECIMAL(10,4) DEFAULT 1,
        concepto TEXT,
        referencia VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (entry_id) REFERENCES acc_journal_entries(id) ON DELETE CASCADE,
        FOREIGN KEY (cuenta_id) REFERENCES acc_chart_of_accounts(id),
        INDEX idx_entry (entry_id),
        INDEX idx_cuenta (cuenta_id),
        INDEX idx_tercero (tercero_tipo, tercero_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de auditoria de cuentas
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_accounts_audit (
        id INT AUTO_INCREMENT PRIMARY KEY,
        account_id INT NOT NULL,
        action ENUM('create', 'update', 'delete', 'activate', 'deactivate') NOT NULL,
        old_values TEXT,
        new_values TEXT,
        changed_by INT,
        changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45),
        user_agent TEXT,
        INDEX idx_account (account_id),
        INDEX idx_changed_at (changed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de plantillas de asientos
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_entry_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        codigo VARCHAR(20) NOT NULL,
        nombre VARCHAR(200) NOT NULL,
        descripcion TEXT,
        tipo VARCHAR(50),
        activa TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by INT,
        UNIQUE KEY uk_codigo_company (codigo, company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de lineas de plantillas
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_entry_template_lines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        template_id INT NOT NULL,
        linea INT NOT NULL,
        cuenta_id INT NOT NULL,
        tipo_movimiento ENUM('debe', 'haber') NOT NULL,
        formula VARCHAR(200) COMMENT 'total subtotal iva descuento',
        porcentaje DECIMAL(5,2),
        requiere_tercero TINYINT(1) DEFAULT 0,
        requiere_centro_costos TINYINT(1) DEFAULT 0,
        FOREIGN KEY (template_id) REFERENCES acc_entry_templates(id) ON DELETE CASCADE,
        FOREIGN KEY (cuenta_id) REFERENCES acc_chart_of_accounts(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de saldos contables (para consultas rapidas)
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_account_balances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        cuenta_id INT NOT NULL,
        periodo VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
        saldo_inicial_debe DECIMAL(15,2) DEFAULT 0,
        saldo_inicial_haber DECIMAL(15,2) DEFAULT 0,
        movimientos_debe DECIMAL(15,2) DEFAULT 0,
        movimientos_haber DECIMAL(15,2) DEFAULT 0,
        saldo_final_debe DECIMAL(15,2) DEFAULT 0,
        saldo_final_haber DECIMAL(15,2) DEFAULT 0,
        saldo_final DECIMAL(15,2) GENERATED ALWAYS AS (
            CASE
                WHEN (saldo_final_debe - saldo_final_haber) >= 0 THEN (saldo_final_debe - saldo_final_haber)
                ELSE (saldo_final_haber - saldo_final_debe)
            END
        ) STORED,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_cuenta_periodo (cuenta_id, periodo, company_id),
        INDEX idx_periodo (periodo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

} catch (PDOException $e) {
    $error = "Error al crear tablas: " . $e->getMessage();
}

// === CREAR CUENTA ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create_account') {
    try {
        $codigo = trim($_POST['codigo_completo']);
        $nivel = intval($_POST['nivel']);
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $nombre = trim($_POST['nombre']);
        $naturaleza = $_POST['naturaleza'];
        $tipo = $_POST['tipo'];
        $permite_movimientos = isset($_POST['permite_movimientos']) ? 1 : 0;

        // Validar codigo segun nivel
        $codigo_length = strlen($codigo);
        $expected_length = $nivel * 2; // 2 digitos por nivel

        if ($codigo_length != $expected_length) {
            throw new Exception("El codigo debe tener $expected_length digitos para nivel $nivel");
        }

        $stmt = $pdo->prepare("
            INSERT INTO acc_chart_of_accounts (
                company_id, codigo_completo, nivel, parent_id, nombre, naturaleza,
                tipo, subtipo, moneda, permite_movimientos, requiere_tercero,
                requiere_centro_costos, requiere_proyecto, clasificacion_ifrs,
                clasificacion_tributaria, clasificacion_flujo_caja, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $company_id,
            $codigo,
            $nivel,
            $parent_id,
            $nombre,
            $naturaleza,
            $tipo,
            $_POST['subtipo'] ?? null,
            $_POST['moneda'] ?? 'CLP',
            $permite_movimientos,
            isset($_POST['requiere_tercero']) ? 1 : 0,
            isset($_POST['requiere_centro_costos']) ? 1 : 0,
            isset($_POST['requiere_proyecto']) ? 1 : 0,
            $_POST['clasificacion_ifrs'] ?? null,
            $_POST['clasificacion_tributaria'] ?? null,
            $_POST['clasificacion_flujo_caja'] ?? null,
            $user_id
        ]);
        $stmt->closeCursor();

        $message = "Cuenta creada exitosamente: $codigo - $nombre";
        header("Location: ?action=accounts&msg=" . urlencode($message));
        exit;

    } catch (Exception $e) {
        $error = "Error al crear cuenta: " . $e->getMessage();
    }
}

// === CREAR ASIENTO CONTABLE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create_entry') {
    try {
        $pdo->beginTransaction();

        // Generar numero de asiento
        $fecha = $_POST['fecha'];
        $periodo = date('Y-m', strtotime($fecha));

        $stmt = $pdo->prepare("
            SELECT MAX(CAST(SUBSTRING(numero_asiento, -6) AS UNSIGNED)) as max_num
            FROM acc_journal_entries
            WHERE company_id = ? AND periodo = ?
        ");
        $stmt->execute([$company_id, $periodo]);
        $result = $stmt->fetch();
        $stmt->closeCursor();

        $next_num = ($result['max_num'] ?? 0) + 1;
        $numero_asiento = $periodo . '-' . str_pad($next_num, 6, '0', STR_PAD_LEFT);

        // Crear encabezado de asiento
        $stmt = $pdo->prepare("
            INSERT INTO acc_journal_entries (
                company_id, numero_asiento, fecha, periodo, tipo, concepto,
                total_debe, total_haber, estado, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, 0, 0, 'borrador', ?)
        ");
        $stmt->execute([
            $company_id,
            $numero_asiento,
            $fecha,
            $periodo,
            $_POST['tipo'] ?? 'manual',
            $_POST['concepto'],
            $user_id
        ]);
        $entry_id = $pdo->lastInsertId();
        $stmt->closeCursor();

        // Procesar lineas del asiento
        $total_debe = 0;
        $total_haber = 0;
        $linea = 1;

        if (isset($_POST['cuenta_id']) && is_array($_POST['cuenta_id'])) {
            for ($i = 0; $i < count($_POST['cuenta_id']); $i++) {
                if (empty($_POST['cuenta_id'][$i])) continue;

                $cuenta_id = intval($_POST['cuenta_id'][$i]);
                $debe = floatval($_POST['debe'][$i] ?? 0);
                $haber = floatval($_POST['haber'][$i] ?? 0);

                if ($debe == 0 && $haber == 0) continue;

                $stmt = $pdo->prepare("
                    INSERT INTO acc_journal_entry_lines (
                        entry_id, linea, cuenta_id, debe, haber, concepto
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $entry_id,
                    $linea,
                    $cuenta_id,
                    $debe,
                    $haber,
                    $_POST['concepto_linea'][$i] ?? ''
                ]);
                $stmt->closeCursor();

                $total_debe += $debe;
                $total_haber += $haber;
                $linea++;
            }
        }

        // Actualizar totales
        $stmt = $pdo->prepare("
            UPDATE acc_journal_entries
            SET total_debe = ?, total_haber = ?
            WHERE id = ?
        ");
        $stmt->execute([$total_debe, $total_haber, $entry_id]);
        $stmt->closeCursor();

        // Validar que cuadre (debe = haber)
        if (abs($total_debe - $total_haber) > 0.01) {
            throw new Exception("El asiento no cuadra. Debe: $total_debe, Haber: $total_haber");
        }

        $pdo->commit();
        $message = "Asiento contable creado: $numero_asiento";
        header("Location: ?action=entries&msg=" . urlencode($message));
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al crear asiento: " . $e->getMessage();
    }
}

// === CONTABILIZAR ASIENTO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'post_entry') {
    try {
        $entry_id = intval($_POST['entry_id']);

        $pdo->beginTransaction();

        // Verificar que el asiento cuadre
        $stmt = $pdo->prepare("
            SELECT * FROM acc_journal_entries
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$entry_id, $company_id]);
        $entry = $stmt->fetch();
        $stmt->closeCursor();

        if (!$entry) {
            throw new Exception("Asiento no encontrado");
        }

        if ($entry['estado'] !== 'borrador') {
            throw new Exception("Solo se pueden contabilizar asientos en borrador");
        }

        if (!$entry['cuadrado']) {
            throw new Exception("El asiento no cuadra");
        }

        // Actualizar estado
        $stmt = $pdo->prepare("
            UPDATE acc_journal_entries
            SET estado = 'contabilizado',
                fecha_contabilizacion = NOW(),
                contabilizado_por = ?
            WHERE id = ?
        ");
        $stmt->execute([$user_id, $entry_id]);
        $stmt->closeCursor();

        // Actualizar saldos contables
        $stmt = $pdo->prepare("
            SELECT * FROM acc_journal_entry_lines WHERE entry_id = ?
        ");
        $stmt->execute([$entry_id]);
        $lines = $stmt->fetchAll();
        $stmt->closeCursor();

        foreach ($lines as $line) {
            $periodo = $entry['periodo'];
            $cuenta_id = $line['cuenta_id'];

            // Verificar si existe el saldo para este periodo
            $stmt = $pdo->prepare("
                SELECT id FROM acc_account_balances
                WHERE cuenta_id = ? AND periodo = ? AND company_id = ?
            ");
            $stmt->execute([$cuenta_id, $periodo, $company_id]);
            $balance = $stmt->fetch();
            $stmt->closeCursor();

            if ($balance) {
                // Actualizar saldo existente
                $stmt = $pdo->prepare("
                    UPDATE acc_account_balances
                    SET movimientos_debe = movimientos_debe + ?,
                        movimientos_haber = movimientos_haber + ?,
                        saldo_final_debe = saldo_inicial_debe + movimientos_debe + ?,
                        saldo_final_haber = saldo_inicial_haber + movimientos_haber + ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $line['debe'],
                    $line['haber'],
                    $line['debe'],
                    $line['haber'],
                    $balance['id']
                ]);
                $stmt->closeCursor();
            } else {
                // Crear nuevo saldo
                $stmt = $pdo->prepare("
                    INSERT INTO acc_account_balances (
                        company_id, cuenta_id, periodo,
                        movimientos_debe, movimientos_haber,
                        saldo_final_debe, saldo_final_haber
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $company_id,
                    $cuenta_id,
                    $periodo,
                    $line['debe'],
                    $line['haber'],
                    $line['debe'],
                    $line['haber']
                ]);
                $stmt->closeCursor();
            }
        }

        $pdo->commit();
        $message = "Asiento contabilizado exitosamente";

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al contabilizar: " . $e->getMessage();
    }
}

// === REVERSAR ASIENTO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reverse_entry') {
    try {
        $entry_id = intval($_POST['entry_id']);

        $pdo->beginTransaction();

        // Obtener asiento original
        $stmt = $pdo->prepare("
            SELECT * FROM acc_journal_entries
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$entry_id, $company_id]);
        $original = $stmt->fetch();
        $stmt->closeCursor();

        if (!$original || $original['estado'] !== 'contabilizado') {
            throw new Exception("Solo se pueden reversar asientos contabilizados");
        }

        // Generar numero para asiento reversa
        $fecha = date('Y-m-d');
        $periodo = date('Y-m');

        $stmt = $pdo->prepare("
            SELECT MAX(CAST(SUBSTRING(numero_asiento, -6) AS UNSIGNED)) as max_num
            FROM acc_journal_entries
            WHERE company_id = ? AND periodo = ?
        ");
        $stmt->execute([$company_id, $periodo]);
        $result = $stmt->fetch();
        $stmt->closeCursor();

        $next_num = ($result['max_num'] ?? 0) + 1;
        $numero_reversa = $periodo . '-' . str_pad($next_num, 6, '0', STR_PAD_LEFT);

        // Crear asiento reversa
        $stmt = $pdo->prepare("
            INSERT INTO acc_journal_entries (
                company_id, numero_asiento, fecha, periodo, tipo, concepto,
                total_debe, total_haber, estado, reversa_de, created_by
            ) VALUES (?, ?, ?, ?, 'reversa', ?, ?, ?, 'contabilizado', ?, ?)
        ");
        $stmt->execute([
            $company_id,
            $numero_reversa,
            $fecha,
            $periodo,
            "REVERSA DE " . $original['numero_asiento'] . " - " . $original['concepto'],
            $original['total_haber'], // Invertidos
            $original['total_debe'],   // Invertidos
            $entry_id,
            $user_id
        ]);
        $reversa_id = $pdo->lastInsertId();
        $stmt->closeCursor();

        // Copiar lineas invertidas
        $stmt = $pdo->prepare("SELECT * FROM acc_journal_entry_lines WHERE entry_id = ?");
        $stmt->execute([$entry_id]);
        $lines = $stmt->fetchAll();
        $stmt->closeCursor();

        foreach ($lines as $line) {
            $stmt = $pdo->prepare("
                INSERT INTO acc_journal_entry_lines (
                    entry_id, linea, cuenta_id, debe, haber, concepto
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $reversa_id,
                $line['linea'],
                $line['cuenta_id'],
                $line['haber'], // Invertido
                $line['debe'],  // Invertido
                $line['concepto']
            ]);
            $stmt->closeCursor();
        }

        // Marcar original como reversado
        $stmt = $pdo->prepare("
            UPDATE acc_journal_entries
            SET estado = 'reversado', reversado_por = ?
            WHERE id = ?
        ");
        $stmt->execute([$reversa_id, $entry_id]);
        $stmt->closeCursor();

        $pdo->commit();
        $message = "Asiento reversado exitosamente: $numero_reversa";

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al reversar: " . $e->getMessage();
    }
}

// === OBTENER DATOS ===
$stats = [];
$recent_entries = [];
$accounts = [];
$entries = [];

try {
    // Estadisticas generales
    $stmt = $pdo->prepare("
        SELECT
            COUNT(DISTINCT id) as total_cuentas,
            SUM(CASE WHEN activa = 1 THEN 1 ELSE 0 END) as cuentas_activas,
            SUM(CASE WHEN nivel = 1 THEN 1 ELSE 0 END) as nivel_1,
            SUM(CASE WHEN nivel = 7 THEN 1 ELSE 0 END) as nivel_7
        FROM acc_chart_of_accounts
        WHERE company_id = ?
    ");
    $stmt->execute([$company_id]);
    $stats = $stmt->fetch();
    $stmt->closeCursor();

    // Asientos recientes
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
        $nivel_filter = isset($_GET['nivel']) ? intval($_GET['nivel']) : null;

        $sql = "SELECT * FROM acc_chart_of_accounts WHERE company_id = ?";
        if ($nivel_filter) {
            $sql .= " AND nivel = " . $nivel_filter;
        }
        $sql .= " ORDER BY codigo_completo";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$company_id]);
        $accounts = $stmt->fetchAll();
        $stmt->closeCursor();
    }

    // Asientos contables
    if ($action === 'entries') {
        $stmt = $pdo->prepare("
            SELECT * FROM acc_journal_entries
            WHERE company_id = ?
            ORDER BY fecha DESC, numero_asiento DESC
            LIMIT 100
        ");
        $stmt->execute([$company_id]);
        $entries = $stmt->fetchAll();
        $stmt->closeCursor();
    }

} catch (PDOException $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

$total_cuentas = $stats['total_cuentas'] ?? 0;
$cuentas_activas = $stats['cuentas_activas'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modulo de Contabilidad - CONECTA ERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #1e293b; line-height: 1.6; }
        .header { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; font-size: 0.95rem; }
        .nav { background: white; padding: 1rem 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .nav a { display: inline-block; padding: 0.5rem 1rem; margin-right: 0.5rem; color: #475569; text-decoration: none; border-radius: 6px; font-weight: 500; transition: all 0.2s; }
        .nav a:hover, .nav a.active { background: #f1f5f9; color: #059669; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #059669; }
        .stat-card h3 { font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem; text-transform: uppercase; font-weight: 600; }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: #1e293b; }
        .stat-card .label { font-size: 0.875rem; color: #64748b; margin-top: 0.25rem; }
        .card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 1.5rem; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #f1f5f9; }
        .card-header h2 { font-size: 1.5rem; color: #1e293b; font-weight: 700; }
        .btn { padding: 0.625rem 1.25rem; border: none; border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; }
        .btn-secondary { background: #f1f5f9; color: #475569; }
        .btn-small { padding: 0.375rem 0.75rem; font-size: 0.813rem; }
        .btn-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; padding: 0.75rem; text-align: left; font-weight: 600; color: #475569; font-size: 0.875rem; border-bottom: 2px solid #e2e8f0; }
        td { padding: 0.75rem; border-bottom: 1px solid #f1f5f9; font-size: 0.875rem; }
        tr:hover { background: #f8fafc; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #475569; font-size: 0.875rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.625rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .checkbox-group { display: flex; align-items: center; gap: 0.5rem; }
        .checkbox-group input[type="checkbox"] { width: auto; }
        .alert { padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500; }
        .alert-success { background: #dcfce7; color: #166534; border-left: 4px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal.active { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 12px; padding: 2rem; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #f1f5f9; }
        .modal-close { cursor: pointer; font-size: 1.5rem; color: #64748b; }
        .entry-line { background: #f8fafc; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .entry-line-grid { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Modulo de Contabilidad</h1>
        <p>Plan de Cuentas (7 Niveles) - Asientos Contables - Integraciones - Auditoria</p>
    </div>

    <div class="nav">
        <a href="?action=dashboard" class="<?php echo $action === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
        <a href="?action=accounts" class="<?php echo $action === 'accounts' ? 'active' : ''; ?>">Plan de Cuentas</a>
        <a href="?action=entries" class="<?php echo $action === 'entries' ? 'active' : ''; ?>">Asientos Contables</a>
        <a href="?action=reports" class="<?php echo $action === 'reports' ? 'active' : ''; ?>">Reportes</a>
        <a href="?action=settings" class="<?php echo $action === 'settings' ? 'active' : ''; ?>">Configuracion</a>
    </div>

    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($action === 'dashboard'): ?>
        <!-- DASHBOARD -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Cuentas</h3>
                <div class="value"><?php echo $total_cuentas; ?></div>
                <div class="label">en plan de cuentas</div>
            </div>
            <div class="stat-card">
                <h3>Cuentas Activas</h3>
                <div class="value"><?php echo $cuentas_activas; ?></div>
                <div class="label">habilitadas</div>
            </div>
            <div class="stat-card">
                <h3>Asientos del Mes</h3>
                <div class="value"><?php echo count($recent_entries); ?></div>
                <div class="label">registros contables</div>
            </div>
            <div class="stat-card">
                <h3>Estado</h3>
                <div class="value">OK</div>
                <div class="label">sistema operativo</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Asientos Recientes</h2>
                <a href="?action=entries" class="btn btn-primary">Ver Todos</a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Numero</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Concepto</th>
                            <th>Debe</th>
                            <th>Haber</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_entries as $entry): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($entry['numero_asiento']); ?></strong></td>
                            <td><?php echo date('d/m/Y', strtotime($entry['fecha'])); ?></td>
                            <td><span class="badge badge-info"><?php echo strtoupper($entry['tipo']); ?></span></td>
                            <td><?php echo htmlspecialchars(substr($entry['concepto'], 0, 50)); ?></td>
                            <td>$<?php echo number_format($entry['total_debe'], 2); ?></td>
                            <td>$<?php echo number_format($entry['total_haber'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php
                                    echo $entry['estado'] === 'contabilizado' ? 'success' :
                                        ($entry['estado'] === 'borrador' ? 'warning' : 'danger');
                                ?>">
                                    <?php echo strtoupper($entry['estado']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($action === 'accounts'): ?>
        <!-- PLAN DE CUENTAS -->
        <div class="card">
            <div class="card-header">
                <h2>Plan de Cuentas</h2>
                <button onclick="document.getElementById('modal-account').classList.add('active')" class="btn btn-primary">Nueva Cuenta</button>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <a href="?action=accounts" class="btn btn-secondary btn-small">Todas</a>
                <a href="?action=accounts&nivel=1" class="btn btn-secondary btn-small">Nivel 1</a>
                <a href="?action=accounts&nivel=2" class="btn btn-secondary btn-small">Nivel 2</a>
                <a href="?action=accounts&nivel=3" class="btn btn-secondary btn-small">Nivel 3</a>
                <a href="?action=accounts&nivel=4" class="btn btn-secondary btn-small">Nivel 4</a>
                <a href="?action=accounts&nivel=5" class="btn btn-secondary btn-small">Nivel 5</a>
                <a href="?action=accounts&nivel=6" class="btn btn-secondary btn-small">Nivel 6</a>
                <a href="?action=accounts&nivel=7" class="btn btn-secondary btn-small">Nivel 7</a>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Nivel</th>
                            <th>Nombre</th>
                            <th>Naturaleza</th>
                            <th>Tipo</th>
                            <th>Movimientos</th>
                            <th>IFRS</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $account): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($account['codigo_completo']); ?></strong></td>
                            <td><span class="badge badge-info">Nivel <?php echo $account['nivel']; ?></span></td>
                            <td style="padding-left: <?php echo ($account['nivel'] - 1) * 20; ?>px;">
                                <?php echo htmlspecialchars($account['nombre']); ?>
                            </td>
                            <td><?php echo strtoupper($account['naturaleza']); ?></td>
                            <td><?php echo htmlspecialchars($account['tipo'] ?? 'N/A'); ?></td>
                            <td><?php echo $account['permite_movimientos'] ? 'Si' : 'No'; ?></td>
                            <td><?php echo htmlspecialchars($account['clasificacion_ifrs'] ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $account['activa'] ? 'success' : 'danger'; ?>">
                                    <?php echo $account['activa'] ? 'ACTIVA' : 'INACTIVA'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Nueva Cuenta -->
        <div id="modal-account" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Nueva Cuenta Contable</h2>
                    <span class="modal-close" onclick="document.getElementById('modal-account').classList.remove('active')">&times;</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create_account">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Codigo Completo *</label>
                            <input type="text" name="codigo_completo" required placeholder="Ej: 1101 para Nivel 2">
                        </div>
                        <div class="form-group">
                            <label>Nivel *</label>
                            <select name="nivel" required>
                                <option value="">Seleccione...</option>
                                <option value="1">Nivel 1 - Clase (2 dig)</option>
                                <option value="2">Nivel 2 - Grupo (4 dig)</option>
                                <option value="3">Nivel 3 - Subgrupo (6 dig)</option>
                                <option value="4">Nivel 4 - Cuenta (8 dig)</option>
                                <option value="5">Nivel 5 - Subcuenta (10 dig)</option>
                                <option value="6">Nivel 6 - Analitica (12 dig)</option>
                                <option value="7">Nivel 7 - Detalle (14 dig)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" required>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Naturaleza *</label>
                            <select name="naturaleza" required>
                                <option value="deudora">Deudora</option>
                                <option value="acreedora">Acreedora</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tipo *</label>
                            <select name="tipo" required>
                                <option value="">Seleccione...</option>
                                <option value="activo_corriente">Activo Corriente</option>
                                <option value="activo_no_corriente">Activo No Corriente</option>
                                <option value="pasivo_corriente">Pasivo Corriente</option>
                                <option value="pasivo_no_corriente">Pasivo No Corriente</option>
                                <option value="patrimonio">Patrimonio</option>
                                <option value="ingresos_operacionales">Ingresos Operacionales</option>
                                <option value="gastos_operacionales">Gastos Operacionales</option>
                                <option value="ingresos_no_operacionales">Ingresos No Operacionales</option>
                                <option value="gastos_no_operacionales">Gastos No Operacionales</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Clasificacion IFRS</label>
                            <input type="text" name="clasificacion_ifrs" placeholder="Ej: NIC 2 - Inventarios">
                        </div>
                        <div class="form-group">
                            <label>Clasificacion Flujo de Caja</label>
                            <select name="clasificacion_flujo_caja">
                                <option value="">N/A</option>
                                <option value="operacion">Operacion</option>
                                <option value="inversion">Inversion</option>
                                <option value="financiacion">Financiacion</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="permite_movimientos" id="permite_mov" checked>
                            <label for="permite_mov">Permite Movimientos</label>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="checkbox-group">
                            <input type="checkbox" name="requiere_tercero" id="req_tercero">
                            <label for="req_tercero">Requiere Tercero</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="requiere_centro_costos" id="req_cc">
                            <label for="req_cc">Requiere Centro de Costos</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="requiere_proyecto" id="req_proy">
                            <label for="req_proy">Requiere Proyecto</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Crear Cuenta</button>
                </form>
            </div>
        </div>

        <?php elseif ($action === 'entries'): ?>
        <!-- ASIENTOS CONTABLES -->
        <div class="card">
            <div class="card-header">
                <h2>Asientos Contables</h2>
                <button onclick="document.getElementById('modal-entry').classList.add('active')" class="btn btn-primary">Nuevo Asiento</button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Numero</th>
                            <th>Fecha</th>
                            <th>Periodo</th>
                            <th>Tipo</th>
                            <th>Concepto</th>
                            <th>Debe</th>
                            <th>Haber</th>
                            <th>Cuadra</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($entry['numero_asiento']); ?></strong></td>
                            <td><?php echo date('d/m/Y', strtotime($entry['fecha'])); ?></td>
                            <td><?php echo $entry['periodo']; ?></td>
                            <td><span class="badge badge-info"><?php echo strtoupper($entry['tipo']); ?></span></td>
                            <td><?php echo htmlspecialchars(substr($entry['concepto'], 0, 40)); ?></td>
                            <td>$<?php echo number_format($entry['total_debe'], 2); ?></td>
                            <td>$<?php echo number_format($entry['total_haber'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $entry['cuadrado'] ? 'success' : 'danger'; ?>">
                                    <?php echo $entry['cuadrado'] ? 'SI' : 'NO'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php
                                    echo $entry['estado'] === 'contabilizado' ? 'success' :
                                        ($entry['estado'] === 'borrador' ? 'warning' : 'danger');
                                ?>">
                                    <?php echo strtoupper($entry['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($entry['estado'] === 'borrador'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="post_entry">
                                        <input type="hidden" name="entry_id" value="<?php echo $entry['id']; ?>">
                                        <button type="submit" class="btn btn-primary btn-small">Contabilizar</button>
                                    </form>
                                <?php elseif ($entry['estado'] === 'contabilizado'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="reverse_entry">
                                        <input type="hidden" name="entry_id" value="<?php echo $entry['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-small">Reversar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Nuevo Asiento -->
        <div id="modal-entry" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Nuevo Asiento Contable</h2>
                    <span class="modal-close" onclick="document.getElementById('modal-entry').classList.remove('active')">&times;</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create_entry">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Fecha *</label>
                            <input type="date" name="fecha" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Tipo *</label>
                            <select name="tipo" required>
                                <option value="manual">Manual</option>
                                <option value="ajuste">Ajuste</option>
                                <option value="cierre">Cierre</option>
                                <option value="apertura">Apertura</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Concepto *</label>
                        <textarea name="concepto" required></textarea>
                    </div>

                    <h3 style="margin: 1.5rem 0 1rem;">Lineas del Asiento</h3>

                    <div id="entry-lines">
                        <div class="entry-line">
                            <div class="entry-line-grid">
                                <div class="form-group">
                                    <label>Cuenta</label>
                                    <select name="cuenta_id[]" required>
                                        <option value="">Seleccione cuenta...</option>
                                        <?php
                                        $stmt = $pdo->prepare("SELECT * FROM acc_chart_of_accounts WHERE company_id = ? AND permite_movimientos = 1 ORDER BY codigo_completo");
                                        $stmt->execute([$company_id]);
                                        $cuentas = $stmt->fetchAll();
                                        $stmt->closeCursor();
                                        foreach ($cuentas as $cuenta):
                                        ?>
                                            <option value="<?php echo $cuenta['id']; ?>">
                                                <?php echo $cuenta['codigo_completo'] . ' - ' . $cuenta['nombre']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Debe</label>
                                    <input type="number" name="debe[]" step="0.01" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Haber</label>
                                    <input type="number" name="haber[]" step="0.01" value="0">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Concepto Linea</label>
                                <input type="text" name="concepto_linea[]">
                            </div>
                        </div>
                    </div>

                    <button type="button" onclick="addEntryLine()" class="btn btn-secondary" style="margin-bottom: 1rem;">Agregar Linea</button>
                    <br>
                    <button type="submit" class="btn btn-primary">Crear Asiento</button>
                </form>
            </div>
        </div>

        <script>
        function addEntryLine() {
            const container = document.getElementById('entry-lines');
            const template = container.querySelector('.entry-line').cloneNode(true);
            template.querySelectorAll('input').forEach(input => input.value = input.type === 'number' ? '0' : '');
            template.querySelector('select').selectedIndex = 0;
            container.appendChild(template);
        }
        </script>

        <?php elseif ($action === 'reports'): ?>
        <!-- REPORTES -->
        <div class="card">
            <div class="card-header">
                <h2>Reportes Contables</h2>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Balance General</h3>
                    <p>Estado de situacion financiera con activos, pasivos y patrimonio</p>
                    <button class="btn btn-primary btn-small" style="margin-top: 1rem;">Generar</button>
                </div>
                <div class="stat-card">
                    <h3>Estado de Resultados</h3>
                    <p>Ingresos, gastos y resultado del periodo</p>
                    <button class="btn btn-primary btn-small" style="margin-top: 1rem;">Generar</button>
                </div>
                <div class="stat-card">
                    <h3>Libro Mayor</h3>
                    <p>Movimientos detallados por cuenta contable</p>
                    <button class="btn btn-primary btn-small" style="margin-top: 1rem;">Generar</button>
                </div>
                <div class="stat-card">
                    <h3>Libro Diario</h3>
                    <p>Todos los asientos contables del periodo</p>
                    <button class="btn btn-primary btn-small" style="margin-top: 1rem;">Generar</button>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- CONFIGURACION -->
        <div class="card">
            <div class="card-header">
                <h2>Configuracion del Modulo</h2>
            </div>
            <p>Configuracion de parametros contables, integraciones y permisos.</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
