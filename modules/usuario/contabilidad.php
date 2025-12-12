<?php
require_once __DIR__ . '/../../config/config.php';

// Verificar si está logueado
if (!is_logged_in()) {
    redirect('/login.php');
}

$user_id = $_SESSION['user_id'];
$user = db_get_row("SELECT * FROM usuarios WHERE id = ?", [$user_id]);
$empresa_id = $user['empresa_id'] ?? null;

// ========================================
// PROCESAR FORMULARIOS
// ========================================
$success = '';
$error = '';

// Crear cuenta contable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_cuenta'])) {
    try {
        db_query("INSERT INTO fi_plan_cuentas (
            empresa_id, codigo, nombre, nivel, tipo, naturaleza, cuenta_padre_id,
            acepta_movimiento, centro_costo_obligatorio, imputable_banco, moneda,
            codigo_tributario, estado, usuario_crea, fecha_crea
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())", [
            $empresa_id,
            $_POST['codigo'],
            $_POST['nombre'],
            $_POST['nivel'] ?? 1,
            $_POST['tipo'],
            $_POST['naturaleza'],
            $_POST['cuenta_padre_id'] ?: null,
            isset($_POST['acepta_movimiento']) ? 1 : 0,
            isset($_POST['centro_costo_obligatorio']) ? 1 : 0,
            isset($_POST['imputable_banco']) ? 1 : 0,
            $_POST['moneda'] ?? 'CLP',
            $_POST['codigo_tributario'] ?? '',
            'activo',
            $user_id
        ]);

        header("Location: " . $_SERVER['PHP_SELF'] . "?success=cuenta_creada");
        exit;
    } catch (Exception $e) {
        $error = "Error al crear cuenta: " . $e->getMessage();
    }
}

// Crear comprobante contable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_comprobante'])) {
    try {
        // Validar cuadratura
        $total_debe = 0;
        $total_haber = 0;

        if (isset($_POST['lineas_cuenta']) && is_array($_POST['lineas_cuenta'])) {
            foreach ($_POST['lineas_cuenta'] as $idx => $cuenta_id) {
                $debe = floatval($_POST['lineas_debe'][$idx] ?? 0);
                $haber = floatval($_POST['lineas_haber'][$idx] ?? 0);
                $total_debe += $debe;
                $total_haber += $haber;
            }
        }

        // Validar cuadratura con tolerancia
        $diferencia = abs($total_debe - $total_haber);
        if ($diferencia > 0.01) {
            throw new Exception("El comprobante no cuadra. Debe: $total_debe, Haber: $total_haber, Diferencia: $diferencia");
        }

        // Obtener siguiente número
        $tipo = $_POST['tipo'] ?? 'COMP';
        $periodo = $_POST['periodo'] ?? date('Y-m');
        $numero_max = db_get_var("SELECT MAX(CAST(numero AS UNSIGNED)) FROM fi_comprobantes WHERE empresa_id = ? AND tipo = ? AND periodo = ?", [$empresa_id, $tipo, $periodo]) ?? 0;
        $numero = str_pad($numero_max + 1, 8, '0', STR_PAD_LEFT);

        // Insertar comprobante
        db_query("INSERT INTO fi_comprobantes (
            empresa_id, numero, tipo, descripcion, fecha, periodo, moneda, tipo_cambio,
            total_debe, total_haber, estado, usuario_crea_id, fecha_crea, referencia,
            documento_origen_tipo, documento_origen_id, comentarios
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)", [
            $empresa_id,
            $numero,
            $tipo,
            $_POST['descripcion'],
            $_POST['fecha'],
            $periodo,
            $_POST['moneda'] ?? 'CLP',
            $_POST['tipo_cambio'] ?? 1.0,
            $total_debe,
            $total_haber,
            $_POST['estado'] ?? 'borrador',
            $user_id,
            $_POST['referencia'] ?? '',
            $_POST['documento_origen_tipo'] ?? null,
            $_POST['documento_origen_id'] ?? null,
            $_POST['comentarios'] ?? ''
        ]);

        $comprobante_id = db_get_var("SELECT LAST_INSERT_ID()");

        // Insertar líneas
        if (isset($_POST['lineas_cuenta']) && is_array($_POST['lineas_cuenta'])) {
            foreach ($_POST['lineas_cuenta'] as $idx => $cuenta_id) {
                if (empty($cuenta_id)) continue;

                $debe = floatval($_POST['lineas_debe'][$idx] ?? 0);
                $haber = floatval($_POST['lineas_haber'][$idx] ?? 0);

                if ($debe == 0 && $haber == 0) continue;

                db_query("INSERT INTO fi_comprobantes_detalle (
                    comprobante_id, linea_num, cuenta_id, descripcion, debe, haber,
                    moneda_linea, monto_moneda_local, centro_costo_id, proyecto_id,
                    documento_referencia, referencia_modulo
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                    $comprobante_id,
                    $idx + 1,
                    $cuenta_id,
                    $_POST['lineas_descripcion'][$idx] ?? '',
                    $debe,
                    $haber,
                    $_POST['moneda'] ?? 'CLP',
                    ($debe > 0 ? $debe : $haber) * floatval($_POST['tipo_cambio'] ?? 1.0),
                    $_POST['lineas_centro_costo'][$idx] ?: null,
                    $_POST['lineas_proyecto'][$idx] ?: null,
                    $_POST['lineas_doc_ref'][$idx] ?? '',
                    $_POST['lineas_modulo'][$idx] ?? ''
                ]);
            }
        }

        header("Location: " . $_SERVER['PHP_SELF'] . "?success=comprobante_creado&numero=" . $numero);
        exit;

    } catch (Exception $e) {
        $error = "Error al crear comprobante: " . $e->getMessage();
    }
}

// Aprobar comprobante
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aprobar_comprobante'])) {
    try {
        db_query("UPDATE fi_comprobantes SET estado = 'aprobado', usuario_aprueba_id = ?, fecha_aprueba = NOW() WHERE id = ? AND empresa_id = ?", [
            $user_id,
            $_POST['comprobante_id'],
            $empresa_id
        ]);
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=comprobante_aprobado");
        exit;
    } catch (Exception $e) {
        $error = "Error al aprobar: " . $e->getMessage();
    }
}

// Contabilizar comprobante
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contabilizar_comprobante'])) {
    try {
        // Validar periodo abierto
        $comp = db_get_row("SELECT * FROM fi_comprobantes WHERE id = ? AND empresa_id = ?", [$_POST['comprobante_id'], $empresa_id]);
        $periodo_cerrado = db_get_var("SELECT valor FROM parametros_contables WHERE empresa_id = ? AND clave = 'periodo_cerrado' AND valor = ?", [$empresa_id, $comp['periodo']]);

        if ($periodo_cerrado) {
            throw new Exception("No puede contabilizar en el periodo {$comp['periodo']}. El periodo está cerrado.");
        }

        db_query("UPDATE fi_comprobantes SET estado = 'contabilizado', usuario_contabiliza_id = ?, fecha_contabiliza = NOW() WHERE id = ? AND empresa_id = ?", [
            $user_id,
            $_POST['comprobante_id'],
            $empresa_id
        ]);
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=comprobante_contabilizado");
        exit;
    } catch (Exception $e) {
        $error = "Error al contabilizar: " . $e->getMessage();
    }
}

// Reversar comprobante
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reversar_comprobante'])) {
    try {
        $comp_original = db_get_row("SELECT * FROM fi_comprobantes WHERE id = ? AND empresa_id = ?", [$_POST['comprobante_id'], $empresa_id]);

        // Crear comprobante reverso
        $numero_max = db_get_var("SELECT MAX(CAST(numero AS UNSIGNED)) FROM fi_comprobantes WHERE empresa_id = ? AND tipo = 'REV'", [$empresa_id]) ?? 0;
        $numero_rev = str_pad($numero_max + 1, 8, '0', STR_PAD_LEFT);

        db_query("INSERT INTO fi_comprobantes (
            empresa_id, numero, tipo, descripcion, fecha, periodo, moneda, tipo_cambio,
            total_debe, total_haber, estado, usuario_crea_id, fecha_crea, referencia
        ) VALUES (?, ?, 'REV', ?, ?, ?, ?, ?, ?, ?, 'contabilizado', ?, NOW(), ?)", [
            $empresa_id,
            $numero_rev,
            "REVERSO DE: " . $comp_original['descripcion'],
            $_POST['fecha_reverso'] ?? date('Y-m-d'),
            $_POST['periodo_reverso'] ?? date('Y-m'),
            $comp_original['moneda'],
            $comp_original['tipo_cambio'],
            $comp_original['total_haber'], // invertido
            $comp_original['total_debe'],  // invertido
            $user_id,
            "Reverso de " . $comp_original['numero'] . " - " . ($_POST['motivo_reverso'] ?? '')
        ]);

        $comprobante_rev_id = db_get_var("SELECT LAST_INSERT_ID()");

        // Copiar líneas invertidas
        $lineas = db_query("SELECT * FROM fi_comprobantes_detalle WHERE comprobante_id = ?", [$comp_original['id']]);
        foreach ($lineas as $linea) {
            db_query("INSERT INTO fi_comprobantes_detalle (
                comprobante_id, linea_num, cuenta_id, descripcion, debe, haber,
                moneda_linea, monto_moneda_local, centro_costo_id, proyecto_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                $comprobante_rev_id,
                $linea['linea_num'],
                $linea['cuenta_id'],
                "REV: " . $linea['descripcion'],
                $linea['haber'], // invertido
                $linea['debe'],  // invertido
                $linea['moneda_linea'],
                $linea['monto_moneda_local'],
                $linea['centro_costo_id'],
                $linea['proyecto_id']
            ]);
        }

        // Marcar original como reversado
        db_query("UPDATE fi_comprobantes SET estado = 'reversado' WHERE id = ?", [$comp_original['id']]);

        // Registrar en tabla de reversiones
        db_query("INSERT INTO fi_reversiones (comprobante_original_id, comprobante_reverso_id, usuario, fecha, motivo) VALUES (?, ?, ?, NOW(), ?)", [
            $comp_original['id'],
            $comprobante_rev_id,
            $user_id,
            $_POST['motivo_reverso'] ?? ''
        ]);

        header("Location: " . $_SERVER['PHP_SELF'] . "?success=comprobante_reversado&reverso=" . $numero_rev);
        exit;
    } catch (Exception $e) {
        $error = "Error al reversar: " . $e->getMessage();
    }
}

// Mensaje de éxito
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'cuenta_creada':
            $success = "Cuenta contable creada exitosamente";
            break;
        case 'comprobante_creado':
            $success = "Comprobante creado exitosamente. Número: " . ($_GET['numero'] ?? '');
            break;
        case 'comprobante_aprobado':
            $success = "Comprobante aprobado exitosamente";
            break;
        case 'comprobante_contabilizado':
            $success = "Comprobante contabilizado exitosamente";
            break;
        case 'comprobante_reversado':
            $success = "Comprobante reversado exitosamente. Reverso: " . ($_GET['reverso'] ?? '');
            break;
    }
}

// ========================================
// AUTO-CREAR TABLAS (sin datos embebidos)
// ========================================
try {
    // Plan de cuentas
    db_query("CREATE TABLE IF NOT EXISTS fi_plan_cuentas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        codigo VARCHAR(50) NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        nivel INT DEFAULT 1,
        tipo ENUM('activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto', 'orden') DEFAULT 'activo',
        naturaleza ENUM('Deudora', 'Acreedora') DEFAULT 'Deudora',
        cuenta_padre_id INT NULL,
        acepta_movimiento TINYINT(1) DEFAULT 1,
        centro_costo_obligatorio TINYINT(1) DEFAULT 0,
        imputable_banco TINYINT(1) DEFAULT 0,
        moneda VARCHAR(10) DEFAULT 'CLP',
        codigo_tributario VARCHAR(50),
        estado ENUM('activo', 'inactivo') DEFAULT 'activo',
        usuario_crea INT,
        fecha_crea DATETIME,
        meta_data JSON,
        UNIQUE KEY unique_cuenta_empresa (empresa_id, codigo),
        FOREIGN KEY (cuenta_padre_id) REFERENCES fi_plan_cuentas(id) ON DELETE SET NULL,
        INDEX idx_empresa (empresa_id),
        INDEX idx_tipo (tipo),
        INDEX idx_codigo (codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Comprobantes
    db_query("CREATE TABLE IF NOT EXISTS fi_comprobantes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        numero VARCHAR(50) NOT NULL,
        serie VARCHAR(20),
        tipo ENUM('ING', 'EGR', 'AJU', 'INV', 'PROV', 'CXC', 'CXP', 'PROD', 'VENT', 'COMP', 'REV', 'OTRO') DEFAULT 'COMP',
        descripcion VARCHAR(500) NOT NULL,
        fecha DATE NOT NULL,
        periodo VARCHAR(7) NOT NULL,
        moneda VARCHAR(10) DEFAULT 'CLP',
        tipo_cambio DECIMAL(15,6) DEFAULT 1.0,
        total_debe DECIMAL(15,2) DEFAULT 0,
        total_haber DECIMAL(15,2) DEFAULT 0,
        estado ENUM('borrador', 'pendiente_aprobacion', 'aprobado', 'contabilizado', 'anulado', 'reversado') DEFAULT 'borrador',
        usuario_crea_id INT NOT NULL,
        fecha_crea DATETIME,
        usuario_aprueba_id INT,
        fecha_aprueba DATETIME,
        usuario_contabiliza_id INT,
        fecha_contabiliza DATETIME,
        referencia VARCHAR(255),
        documento_origen_tipo VARCHAR(50),
        documento_origen_id INT,
        comentarios TEXT,
        hash_integridad VARCHAR(64),
        INDEX idx_empresa_periodo (empresa_id, periodo),
        INDEX idx_fecha (fecha),
        INDEX idx_estado (estado),
        INDEX idx_tipo (tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Detalle comprobantes
    db_query("CREATE TABLE IF NOT EXISTS fi_comprobantes_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        comprobante_id INT NOT NULL,
        linea_num INT NOT NULL,
        cuenta_id INT NOT NULL,
        descripcion VARCHAR(500),
        debe DECIMAL(15,2) DEFAULT 0,
        haber DECIMAL(15,2) DEFAULT 0,
        moneda_linea VARCHAR(10) DEFAULT 'CLP',
        monto_moneda_local DECIMAL(15,2),
        centro_costo_id INT,
        proyecto_id INT,
        documento_referencia VARCHAR(255),
        referencia_modulo VARCHAR(50),
        activo_fijo_id INT,
        metadata JSON,
        FOREIGN KEY (comprobante_id) REFERENCES fi_comprobantes(id) ON DELETE CASCADE,
        FOREIGN KEY (cuenta_id) REFERENCES fi_plan_cuentas(id) ON DELETE RESTRICT,
        INDEX idx_comprobante (comprobante_id),
        INDEX idx_cuenta (cuenta_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Parámetros contables
    db_query("CREATE TABLE IF NOT EXISTS parametros_contables (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT,
        clave VARCHAR(100) NOT NULL,
        valor TEXT,
        ambito ENUM('global', 'empresa') DEFAULT 'empresa',
        descripcion VARCHAR(255),
        UNIQUE KEY unique_param (empresa_id, clave),
        INDEX idx_clave (clave)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Reversiones
    db_query("CREATE TABLE IF NOT EXISTS fi_reversiones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        comprobante_original_id INT NOT NULL,
        comprobante_reverso_id INT NOT NULL,
        usuario INT NOT NULL,
        fecha DATETIME,
        motivo TEXT,
        FOREIGN KEY (comprobante_original_id) REFERENCES fi_comprobantes(id) ON DELETE CASCADE,
        FOREIGN KEY (comprobante_reverso_id) REFERENCES fi_comprobantes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Plantillas
    db_query("CREATE TABLE IF NOT EXISTS fi_plantillas_comprobantes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        codigo VARCHAR(50) NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        descripcion TEXT,
        lineas_template JSON,
        requiere_aprobacion TINYINT(1) DEFAULT 0,
        estado ENUM('activo', 'inactivo') DEFAULT 'activo',
        UNIQUE KEY unique_plantilla (empresa_id, codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Centros de costo
    db_query("CREATE TABLE IF NOT EXISTS ma_centros_costo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        codigo VARCHAR(50) NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        descripcion TEXT,
        estado ENUM('activo', 'inactivo') DEFAULT 'activo',
        UNIQUE KEY unique_cc (empresa_id, codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Proyectos
    db_query("CREATE TABLE IF NOT EXISTS ma_proyectos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        codigo VARCHAR(50) NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        estado ENUM('activo', 'cerrado') DEFAULT 'activo',
        UNIQUE KEY unique_proy (empresa_id, codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Reglas de asiento automático (mapeo desde módulos)
    db_query("CREATE TABLE IF NOT EXISTS fi_reglas_asiento (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        evento_origen VARCHAR(100) NOT NULL,
        modulo VARCHAR(50) NOT NULL,
        descripcion VARCHAR(255),
        mapeo_lineas JSON,
        condiciones JSON,
        cuentas_default JSON,
        activo TINYINT(1) DEFAULT 1,
        INDEX idx_modulo_evento (modulo, evento_origen)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Bancos
    db_query("CREATE TABLE IF NOT EXISTS ma_bancos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        codigo VARCHAR(50) NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        cuenta_contable_id INT,
        numero_cuenta VARCHAR(100),
        moneda VARCHAR(10) DEFAULT 'CLP',
        saldo_actual DECIMAL(15,2) DEFAULT 0,
        estado ENUM('activo', 'inactivo') DEFAULT 'activo',
        FOREIGN KEY (cuenta_contable_id) REFERENCES fi_plan_cuentas(id) ON DELETE SET NULL,
        UNIQUE KEY unique_banco (empresa_id, codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Conciliaciones bancarias
    db_query("CREATE TABLE IF NOT EXISTS fi_conciliaciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        banco_id INT NOT NULL,
        periodo VARCHAR(7) NOT NULL,
        fecha_conciliacion DATE NOT NULL,
        saldo_libro DECIMAL(15,2) DEFAULT 0,
        saldo_banco DECIMAL(15,2) DEFAULT 0,
        diferencia DECIMAL(15,2) DEFAULT 0,
        estado ENUM('pendiente', 'conciliado', 'con_diferencias') DEFAULT 'pendiente',
        detalles JSON,
        archivo_extracto VARCHAR(500),
        usuario_concilia INT,
        fecha_proceso DATETIME,
        FOREIGN KEY (banco_id) REFERENCES ma_bancos(id) ON DELETE CASCADE,
        INDEX idx_banco_periodo (banco_id, periodo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Movimientos bancarios (extracto)
    db_query("CREATE TABLE IF NOT EXISTS fi_movimientos_bancarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conciliacion_id INT NOT NULL,
        banco_id INT NOT NULL,
        fecha DATE NOT NULL,
        descripcion VARCHAR(500),
        referencia VARCHAR(100),
        cargo DECIMAL(15,2) DEFAULT 0,
        abono DECIMAL(15,2) DEFAULT 0,
        saldo DECIMAL(15,2) DEFAULT 0,
        conciliado TINYINT(1) DEFAULT 0,
        comprobante_id INT,
        FOREIGN KEY (conciliacion_id) REFERENCES fi_conciliaciones(id) ON DELETE CASCADE,
        FOREIGN KEY (banco_id) REFERENCES ma_bancos(id) ON DELETE CASCADE,
        FOREIGN KEY (comprobante_id) REFERENCES fi_comprobantes(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Periodos contables
    db_query("CREATE TABLE IF NOT EXISTS fi_periodos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        periodo VARCHAR(7) NOT NULL,
        estado ENUM('abierto', 'cerrado', 'bloqueado') DEFAULT 'abierto',
        fecha_apertura DATE,
        fecha_cierre DATE,
        usuario_cierre INT,
        motivo_cierre TEXT,
        UNIQUE KEY unique_periodo (empresa_id, periodo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Historial de cierres
    db_query("CREATE TABLE IF NOT EXISTS fi_cierre_periodo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        periodo VARCHAR(7) NOT NULL,
        fecha_cierre DATETIME NOT NULL,
        usuario_cierre INT NOT NULL,
        comprobantes_cerrados INT DEFAULT 0,
        saldo_debe DECIMAL(15,2) DEFAULT 0,
        saldo_haber DECIMAL(15,2) DEFAULT 0,
        checklist_cumplido JSON,
        observaciones TEXT,
        INDEX idx_periodo (periodo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

} catch (Exception $e) {
    // Silenciar errores si ya existen
}

// ========================================
// CONSULTAR DATOS
// ========================================

// Consultar comprobantes
try {
    $comprobantes = db_query("
        SELECT c.*,
               u1.nombre as usuario_crea_nombre,
               u2.nombre as usuario_aprueba_nombre,
               u3.nombre as usuario_contabiliza_nombre
        FROM fi_comprobantes c
        LEFT JOIN usuarios u1 ON c.usuario_crea_id = u1.id
        LEFT JOIN usuarios u2 ON c.usuario_aprueba_id = u2.id
        LEFT JOIN usuarios u3 ON c.usuario_contabiliza_id = u3.id
        WHERE c.empresa_id = ?
        ORDER BY c.fecha DESC, c.numero DESC
        LIMIT 100
    ", [$empresa_id]);
} catch (Exception $e) {
    $comprobantes = [];
}

// Consultar plan de cuentas
try {
    $cuentas = db_query("
        SELECT c.*, cp.nombre as cuenta_padre_nombre
        FROM fi_plan_cuentas c
        LEFT JOIN fi_plan_cuentas cp ON c.cuenta_padre_id = cp.id
        WHERE c.empresa_id = ?
        ORDER BY c.codigo ASC
    ", [$empresa_id]);
} catch (Exception $e) {
    $cuentas = [];
}

// Consultar datos para selects
try { $centros_costo = db_query("SELECT * FROM ma_centros_costo WHERE empresa_id = ? AND estado = 'activo' ORDER BY nombre ASC", [$empresa_id]); } catch (Exception $e) { $centros_costo = []; }
try { $proyectos = db_query("SELECT * FROM ma_proyectos WHERE empresa_id = ? AND estado = 'activo' ORDER BY nombre ASC", [$empresa_id]); } catch (Exception $e) { $proyectos = []; }

// Estadísticas
try {
    $stats = [
        'total_comprobantes' => db_get_var("SELECT COUNT(*) FROM fi_comprobantes WHERE empresa_id = ?", [$empresa_id]) ?? 0,
        'borradores' => db_get_var("SELECT COUNT(*) FROM fi_comprobantes WHERE empresa_id = ? AND estado = 'borrador'", [$empresa_id]) ?? 0,
        'contabilizados' => db_get_var("SELECT COUNT(*) FROM fi_comprobantes WHERE empresa_id = ? AND estado = 'contabilizado'", [$empresa_id]) ?? 0,
        'total_cuentas' => db_get_var("SELECT COUNT(*) FROM fi_plan_cuentas WHERE empresa_id = ?", [$empresa_id]) ?? 0
    ];
} catch (Exception $e) {
    $stats = [
        'total_comprobantes' => 0,
        'borradores' => 0,
        'contabilizados' => 0,
        'total_cuentas' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contabilidad General (FI) - CONECTA ERP</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">

    <style>
/* Layout principal */
.main-wrapper {
    margin-left: 260px !important;
    width: calc(100% - 260px) !important;
    padding: 0 !important;
}

.main-header {
    width: 100% !important;
    padding: 1rem 2rem !important;
}

.main-content {
    width: 100% !important;
    padding: 2rem !important;
    margin: 0 !important;
}

/* Botón fullscreen */
.fullscreen-btn {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 999;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

body.fullscreen-mode .sidebar { display: none !important; }
body.fullscreen-mode .main-wrapper {
    margin-left: 0 !important;
    width: 100% !important;
}
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include __DIR__ . '/../../includes/sidebar_user.php'; ?>

    <!-- Main Content -->
    <div class="main-wrapper" id="mainWrapper">
        <header class="main-header">
            <div class="header-left">
                <h4 class="mb-0" style="color: var(--text-primary);">
                    <i class="fas fa-calculator me-2"></i>
                    Contabilidad General (FI)
                </h4>
                <small class="text-muted">Gestión de comprobantes, plan de cuentas y reportes contables</small>
            </div>

            <div class="header-right">
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>

                <div class="user-menu">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['nombre_completo'] ?? 'U', 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo $user['nombre_completo'] ?? 'Usuario'; ?></div>
                        <div class="user-role">Usuario</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Botón Fullscreen -->
        <button class="fullscreen-btn" onclick="toggleFullscreen()" title="Modo pantalla completa">
            <i class="fas fa-expand"></i>
        </button>

        <div class="main-content">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title"><?= $stats['total_comprobantes'] ?></h3>
                        <p class="card-text text-muted">Total Comprobantes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title text-warning"><?= $stats['borradores'] ?></h3>
                        <p class="card-text text-muted">Borradores</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title text-success"><?= $stats['contabilizados'] ?></h3>
                        <p class="card-text text-muted">Contabilizados</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title text-info"><?= $stats['total_cuentas'] ?></h3>
                        <p class="card-text text-muted">Cuentas Contables</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="contabilidadTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="comprobantes-tab" data-bs-toggle="tab" data-bs-target="#comprobantes" type="button">
                    <i class="fas fa-file-invoice me-2"></i>Comprobantes
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="plancuentas-tab" data-bs-toggle="tab" data-bs-target="#plancuentas" type="button">
                    <i class="fas fa-list me-2"></i>Plan de Cuentas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="mayor-tab" data-bs-toggle="tab" data-bs-target="#mayor" type="button">
                    <i class="fas fa-book me-2"></i>Libro Mayor
                </button>
            </li>
        </ul>

        <div class="tab-content" id="contabilidadTabContent">

            <!-- TAB: Comprobantes -->
            <div class="tab-pane fade show active" id="comprobantes" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Comprobantes Contables</h5>
                        <button class="btn btn-primary" onclick="abrirModalComprobante()">
                            <i class="fas fa-plus me-2"></i>Nuevo Comprobante
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Número</th>
                                        <th>Tipo</th>
                                        <th>Fecha</th>
                                        <th>Periodo</th>
                                        <th>Descripción</th>
                                        <th>Total Debe</th>
                                        <th>Total Haber</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($comprobantes as $comp): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($comp['numero']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($comp['tipo']) ?></span></td>
                                        <td><?= date('d/m/Y', strtotime($comp['fecha'])) ?></td>
                                        <td><?= htmlspecialchars($comp['periodo']) ?></td>
                                        <td><?= htmlspecialchars($comp['descripcion']) ?></td>
                                        <td class="text-end"><?= number_format($comp['total_debe'], 2) ?></td>
                                        <td class="text-end"><?= number_format($comp['total_haber'], 2) ?></td>
                                        <td>
                                            <?php
                                            $badge_class = [
                                                'borrador' => 'secondary',
                                                'pendiente_aprobacion' => 'warning',
                                                'aprobado' => 'info',
                                                'contabilizado' => 'success',
                                                'anulado' => 'danger',
                                                'reversado' => 'dark'
                                            ];
                                            ?>
                                            <span class="badge bg-<?= $badge_class[$comp['estado']] ?? 'secondary' ?>">
                                                <?= ucfirst(str_replace('_', ' ', $comp['estado'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="verComprobante(<?= $comp['id'] ?>)" title="Ver detalle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($comp['estado'] === 'aprobado'): ?>
                                            <button class="btn btn-sm btn-success" onclick="contabilizarComprobante(<?= $comp['id'] ?>)" title="Contabilizar">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($comp['estado'] === 'contabilizado'): ?>
                                            <button class="btn btn-sm btn-warning" onclick="reversarComprobante(<?= $comp['id'] ?>)" title="Reversar">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($comp['estado'] === 'pendiente_aprobacion'): ?>
                                            <button class="btn btn-sm btn-primary" onclick="aprobarComprobante(<?= $comp['id'] ?>)" title="Aprobar">
                                                <i class="fas fa-thumbs-up"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($comprobantes)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">No hay comprobantes registrados</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Plan de Cuentas -->
            <div class="tab-pane fade" id="plancuentas" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Plan de Cuentas</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCuenta">
                            <i class="fas fa-plus me-2"></i>Nueva Cuenta
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Naturaleza</th>
                                        <th>Acepta Mov.</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cuentas as $cuenta): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($cuenta['codigo']) ?></strong></td>
                                        <td><?= htmlspecialchars($cuenta['nombre']) ?></td>
                                        <td><span class="badge bg-info"><?= ucfirst($cuenta['tipo']) ?></span></td>
                                        <td><?= $cuenta['naturaleza'] ?></td>
                                        <td>
                                            <?php if ($cuenta['acepta_movimiento']): ?>
                                                <i class="fas fa-check text-success"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times text-danger"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $cuenta['estado'] === 'activo' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($cuenta['estado']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" title="Ver movimientos">
                                                <i class="fas fa-list"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($cuentas)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No hay cuentas registradas</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Libro Mayor -->
            <div class="tab-pane fade" id="mayor" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Libro Mayor</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Cuenta</label>
                                <select class="form-select" id="filtro_cuenta_mayor">
                                    <option value="">Todas las cuentas</option>
                                    <?php foreach ($cuentas as $c): ?>
                                        <?php if ($c['acepta_movimiento']): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['codigo'] . ' - ' . $c['nombre']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Desde</label>
                                <input type="date" class="form-control" id="filtro_fecha_desde" value="<?= date('Y-m-01') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Hasta</label>
                                <input type="date" class="form-control" id="filtro_fecha_hasta" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-primary w-100" onclick="consultarMayor()">
                                    <i class="fas fa-search me-2"></i>Consultar
                                </button>
                            </div>
                        </div>
                        <div id="resultado_mayor">
                            <p class="text-muted text-center">Seleccione una cuenta y rango de fechas para consultar el mayor</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal: Nueva Cuenta -->
    <div class="modal fade" id="modalCuenta" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Nueva Cuenta Contable</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="codigo" required placeholder="ej: 1.1.01.000">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nombre" required placeholder="ej: Caja General">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select" name="tipo" required>
                                    <option value="">Seleccione...</option>
                                    <option value="activo">Activo</option>
                                    <option value="pasivo">Pasivo</option>
                                    <option value="patrimonio">Patrimonio</option>
                                    <option value="ingreso">Ingreso</option>
                                    <option value="gasto">Gasto</option>
                                    <option value="orden">Orden</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Naturaleza <span class="text-danger">*</span></label>
                                <select class="form-select" name="naturaleza" required>
                                    <option value="Deudora">Deudora</option>
                                    <option value="Acreedora">Acreedora</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nivel</label>
                                <input type="number" class="form-control" name="nivel" value="1" min="1" max="10">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cuenta Padre</label>
                                <select class="form-select" name="cuenta_padre_id">
                                    <option value="">Ninguna (cuenta raíz)</option>
                                    <?php foreach ($cuentas as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['codigo'] . ' - ' . $c['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Moneda</label>
                                <select class="form-select" name="moneda">
                                    <option value="CLP">CLP - Peso Chileno</option>
                                    <option value="USD">USD - Dólar</option>
                                    <option value="EUR">EUR - Euro</option>
                                    <option value="UF">UF - Unidad de Fomento</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código Tributario</label>
                                <input type="text" class="form-control" name="codigo_tributario" placeholder="Opcional">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Opciones</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="acepta_movimiento" id="acepta_mov" checked>
                                    <label class="form-check-label" for="acepta_mov">Acepta movimientos</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="centro_costo_obligatorio" id="cc_oblig">
                                    <label class="form-check-label" for="cc_oblig">Centro de costo obligatorio</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="imputable_banco" id="imp_banco">
                                    <label class="form-check-label" for="imp_banco">Imputable a bancos</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_cuenta" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar Cuenta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Nuevo Comprobante -->
    <div class="modal fade" id="modalComprobante" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST" id="formComprobante">
                    <div class="modal-header">
                        <h5 class="modal-title">Nuevo Comprobante Contable</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Encabezado -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select" name="tipo" required>
                                    <option value="COMP">Comprobante</option>
                                    <option value="ING">Ingreso</option>
                                    <option value="EGR">Egreso</option>
                                    <option value="AJU">Ajuste</option>
                                    <option value="INV">Inventario</option>
                                    <option value="CXC">Cuentas por Cobrar</option>
                                    <option value="CXP">Cuentas por Pagar</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="fecha" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Periodo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="periodo" value="<?= date('Y-m') ?>" required placeholder="YYYY-MM">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Moneda</label>
                                <select class="form-select" name="moneda" id="comp_moneda" onchange="actualizarTipoCambio()">
                                    <option value="CLP">CLP</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </div>
                            <div class="col-md-3 mt-2">
                                <label class="form-label">Tipo de Cambio</label>
                                <input type="number" class="form-control" name="tipo_cambio" id="comp_tipo_cambio" value="1.0" step="0.000001">
                            </div>
                            <div class="col-md-9 mt-2">
                                <label class="form-label">Descripción <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="descripcion" required placeholder="Descripción del comprobante">
                            </div>
                            <div class="col-md-6 mt-2">
                                <label class="form-label">Referencia</label>
                                <input type="text" class="form-control" name="referencia" placeholder="Doc. referencia">
                            </div>
                            <div class="col-md-6 mt-2">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="estado">
                                    <option value="borrador">Borrador</option>
                                    <option value="pendiente_aprobacion">Pendiente Aprobación</option>
                                    <option value="aprobado">Aprobado</option>
                                </select>
                            </div>
                        </div>

                        <!-- Líneas del comprobante -->
                        <h6 class="mb-3">Líneas del Comprobante</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="tablaLineas">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 25%">Cuenta</th>
                                        <th style="width: 20%">Descripción</th>
                                        <th style="width: 12%">Debe</th>
                                        <th style="width: 12%">Haber</th>
                                        <th style="width: 15%">C. Costo</th>
                                        <th style="width: 10%">Ref.</th>
                                        <th style="width: 6%"></th>
                                    </tr>
                                </thead>
                                <tbody id="lineasComprobante">
                                    <!-- Se genera dinámicamente -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" class="text-end"><strong>TOTALES:</strong></td>
                                        <td><strong id="total_debe">0.00</strong></td>
                                        <td><strong id="total_haber">0.00</strong></td>
                                        <td colspan="3">
                                            <span id="cuadratura_msg" class="text-muted">Esperando datos...</span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="agregarLinea()">
                            <i class="fas fa-plus me-2"></i>Agregar Línea
                        </button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_comprobante" class="btn btn-primary" id="btnGuardarComprobante">
                            <i class="fas fa-save me-2"></i>Guardar Comprobante
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Ver Comprobante -->
    <div class="modal fade" id="modalVerComprobante" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle del Comprobante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="contenidoVerComprobante">
                    <div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // DATOS PHP -> JavaScript
        const CUENTAS_JSON = <?= json_encode($cuentas) ?>;
        const CENTROS_COSTO_JSON = <?= json_encode($centros_costo) ?>;
        const PROYECTOS_JSON = <?= json_encode($proyectos) ?>;

        let lineaCounter = 0;

        function abrirModalComprobante() {
            document.getElementById('formComprobante').reset();
            document.getElementById('lineasComprobante').innerHTML = '';
            lineaCounter = 0;
            agregarLinea();
            agregarLinea();
            new bootstrap.Modal(document.getElementById('modalComprobante')).show();
        }

        function agregarLinea() {
            lineaCounter++;
            const tbody = document.getElementById('lineasComprobante');
            const tr = document.createElement('tr');
            tr.id = 'linea_' + lineaCounter;

            let opcionesCuentas = '<option value="">Seleccione cuenta...</option>';
            CUENTAS_JSON.forEach(c => {
                if (c.acepta_movimiento == 1) {
                    opcionesCuentas += `<option value="${c.id}">${c.codigo} - ${c.nombre}</option>`;
                }
            });

            let opcionesCC = '<option value="">Ninguno</option>';
            CENTROS_COSTO_JSON.forEach(cc => {
                opcionesCC += `<option value="${cc.id}">${cc.nombre}</option>`;
            });

            tr.innerHTML = `
                <td>
                    <select class="form-select form-select-sm" name="lineas_cuenta[]" required>
                        ${opcionesCuentas}
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="lineas_descripcion[]" placeholder="Descripción">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm text-end" name="lineas_debe[]" value="0" step="0.01" onchange="calcularTotales()">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm text-end" name="lineas_haber[]" value="0" step="0.01" onchange="calcularTotales()">
                </td>
                <td>
                    <select class="form-select form-select-sm" name="lineas_centro_costo[]">
                        ${opcionesCC}
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="lineas_doc_ref[]" placeholder="Ref">
                    <input type="hidden" name="lineas_proyecto[]" value="">
                    <input type="hidden" name="lineas_modulo[]" value="">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="eliminarLinea(${lineaCounter})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        }

        function eliminarLinea(id) {
            const linea = document.getElementById('linea_' + id);
            if (linea) {
                linea.remove();
                calcularTotales();
            }
        }

        function calcularTotales() {
            let totalDebe = 0;
            let totalHaber = 0;

            document.querySelectorAll('input[name="lineas_debe[]"]').forEach(input => {
                totalDebe += parseFloat(input.value) || 0;
            });

            document.querySelectorAll('input[name="lineas_haber[]"]').forEach(input => {
                totalHaber += parseFloat(input.value) || 0;
            });

            document.getElementById('total_debe').textContent = totalDebe.toFixed(2);
            document.getElementById('total_haber').textContent = totalHaber.toFixed(2);

            const diferencia = Math.abs(totalDebe - totalHaber);
            const msgElem = document.getElementById('cuadratura_msg');
            const btnGuardar = document.getElementById('btnGuardarComprobante');

            if (diferencia < 0.01 && totalDebe > 0) {
                msgElem.innerHTML = '<i class="fas fa-check-circle text-success"></i> Comprobante cuadrado';
                msgElem.className = 'text-success';
                btnGuardar.disabled = false;
            } else if (totalDebe === 0 && totalHaber === 0) {
                msgElem.textContent = 'Esperando datos...';
                msgElem.className = 'text-muted';
                btnGuardar.disabled = true;
            } else {
                msgElem.innerHTML = `<i class="fas fa-exclamation-triangle text-danger"></i> Diferencia: ${diferencia.toFixed(2)}`;
                msgElem.className = 'text-danger';
                btnGuardar.disabled = true;
            }
        }

        function actualizarTipoCambio() {
            const moneda = document.getElementById('comp_moneda').value;
            if (moneda === 'CLP') {
                document.getElementById('comp_tipo_cambio').value = '1.0';
            }
        }

        function verComprobante(id) {
            const modal = new bootstrap.Modal(document.getElementById('modalVerComprobante'));
            modal.show();

            fetch(`/api/contabilidad/comprobante/${id}`)
                .then(r => r.json())
                .then(data => {
                    // Aquí renderizar detalle (por ahora placeholder)
                    document.getElementById('contenidoVerComprobante').innerHTML = '<p>Comprobante ID: ' + id + '</p><p>Funcionalidad en construcción</p>';
                })
                .catch(err => {
                    document.getElementById('contenidoVerComprobante').innerHTML = '<div class="alert alert-danger">Error al cargar comprobante</div>';
                });
        }

        function aprobarComprobante(id) {
            if (!confirm('¿Aprobar este comprobante?')) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="aprobar_comprobante" value="1">
                <input type="hidden" name="comprobante_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function contabilizarComprobante(id) {
            if (!confirm('¿Contabilizar este comprobante? Esta acción es irreversible (solo puede reversarse).')) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="contabilizar_comprobante" value="1">
                <input type="hidden" name="comprobante_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function reversarComprobante(id) {
            const motivo = prompt('Ingrese el motivo del reverso:');
            if (!motivo) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="reversar_comprobante" value="1">
                <input type="hidden" name="comprobante_id" value="${id}">
                <input type="hidden" name="motivo_reverso" value="${motivo}">
                <input type="hidden" name="fecha_reverso" value="<?= date('Y-m-d') ?>">
                <input type="hidden" name="periodo_reverso" value="<?= date('Y-m') ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function consultarMayor() {
            const cuenta_id = document.getElementById('filtro_cuenta_mayor').value;
            const desde = document.getElementById('filtro_fecha_desde').value;
            const hasta = document.getElementById('filtro_fecha_hasta').value;

            if (!cuenta_id) {
                alert('Seleccione una cuenta');
                return;
            }

            document.getElementById('resultado_mayor').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Consultando...</div>';

            // Simulación (en producción sería AJAX a endpoint)
            setTimeout(() => {
                document.getElementById('resultado_mayor').innerHTML = `
                    <div class="alert alert-info">
                        <strong>Mayor de Cuenta ${cuenta_id}</strong><br>
                        Periodo: ${desde} al ${hasta}<br>
                        <em>Funcionalidad en construcción - requiere endpoint backend</em>
                    </div>
                `;
            }, 500);
        }

        // Inicializar al cargar
        document.addEventListener('DOMContentLoaded', function() {
            // Eventos adicionales aquí
        });

        // Función fullscreen
        function toggleFullscreen() {
            document.body.classList.toggle('fullscreen-mode');
            const icon = document.querySelector('.fullscreen-btn i');
            if (document.body.classList.contains('fullscreen-mode')) {
                icon.classList.remove('fa-expand');
                icon.classList.add('fa-compress');
            } else {
                icon.classList.remove('fa-compress');
                icon.classList.add('fa-expand');
            }
        }
    </script>
        </div> <!-- /main-content -->
    </div> <!-- /main-wrapper -->
</body>
</html>
