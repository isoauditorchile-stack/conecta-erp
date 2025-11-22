<?php
/**
 * CONECTA ERP - MODULO DE CONTABILIDAD FINANCIERA COMPLETO
 * Sistema completo de contabilidad con plan de cuentas jerarquico (7 niveles) y asientos contables
 * Integraciones automaticas, clasificaciones IFRS/NIC, auditoria completa
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

$action = $_GET['action'] ?? $_POST['action'] ?? 'cuentas';
$message = '';
$error = '';

// === CREAR TABLAS SI NO EXISTEN ===
try {
    // Tabla de plan de cuentas (7 niveles jerarquicos completos)
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_plan_cuentas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        codigo_completo VARCHAR(50) NOT NULL COMMENT 'Ej: 1-1-02-004-01-001-001',
        codigo_corto VARCHAR(20),
        nivel INT NOT NULL COMMENT '1 a 7 niveles jerarquicos',
        cuenta_padre INT NULL,
        estructura_jerarquica VARCHAR(255) COMMENT 'Path completo de IDs',
        orden_presentacion INT DEFAULT 0,
        tipo_cuenta ENUM('balance', 'resultado', 'orden') DEFAULT 'balance',

        nombre_cuenta VARCHAR(255) NOT NULL,
        descripcion TEXT,
        naturaleza ENUM('deudor', 'acreedor') NOT NULL,
        tipo ENUM('activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto', 'orden') NOT NULL,
        subtipo VARCHAR(100) COMMENT 'circulante fijo no_corriente costo inversion',

        moneda_base VARCHAR(3) DEFAULT 'CLP',
        moneda_alternativa VARCHAR(3),
        permite_movimientos TINYINT(1) DEFAULT 0,
        permite_asientos_automaticos TINYINT(1) DEFAULT 1,
        requiere_centro_costo TINYINT(1) DEFAULT 0,
        requiere_auxiliar ENUM('ninguno', 'cliente', 'proveedor', 'empleado', 'activo_fijo', 'otro'),
        requiere_proyecto TINYINT(1) DEFAULT 0,
        requiere_dimension_adicional TINYINT(1) DEFAULT 0,

        cuenta_revaluacion_id INT NULL,
        cuenta_ajuste_tipo_cambio_id INT NULL,
        es_cuenta_flujo_efectivo TINYINT(1) DEFAULT 0,
        es_cuenta_restringida TINYINT(1) DEFAULT 0,

        clasificacion_ifrs VARCHAR(100),
        clasificacion_nic_niif VARCHAR(100),
        clasificacion_analisis_financiero VARCHAR(100),
        clasificacion_libro_tributario VARCHAR(100),
        grupo_depreciacion VARCHAR(50),
        asignacion_rubro_balance VARCHAR(100),
        asignacion_rubro_resultado VARCHAR(100),
        clasificacion_informes_gestion VARCHAR(100),
        clasificacion_flujo_caja VARCHAR(100) COMMENT 'operacion inversion financiacion',
        clasificacion_proyectos VARCHAR(100),

        integracion_ventas TINYINT(1) DEFAULT 0,
        integracion_compras TINYINT(1) DEFAULT 0,
        integracion_clientes TINYINT(1) DEFAULT 0,
        integracion_bancos TINYINT(1) DEFAULT 0,
        integracion_inventario TINYINT(1) DEFAULT 0,
        integracion_activos_fijos TINYINT(1) DEFAULT 0,
        integracion_remuneraciones TINYINT(1) DEFAULT 0,
        integracion_produccion TINYINT(1) DEFAULT 0,
        integracion_tesoreria TINYINT(1) DEFAULT 0,
        integracion_impuestos TINYINT(1) DEFAULT 0,

        cuenta_parametrica_tipo VARCHAR(100) COMMENT 'deudas_proveedores cxc_clientes iva_credito etc',
        asignacion_tipo_documento VARCHAR(100),
        asignacion_area VARCHAR(100),
        asignacion_sucursal VARCHAR(100),
        asignacion_pais VARCHAR(3),
        asignacion_tipo_transaccion VARCHAR(100),
        cuenta_conciliacion_automatica TINYINT(1) DEFAULT 0,

        bloqueada TINYINT(1) DEFAULT 0,
        edicion_restringida TINYINT(1) DEFAULT 0,
        activa TINYINT(1) DEFAULT 1,
        version INT DEFAULT 1,

        usuario_creacion INT,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        usuario_modificacion INT,
        fecha_modificacion TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,

        UNIQUE KEY uk_codigo_completo (codigo_completo, company_id),
        INDEX idx_nivel (nivel),
        INDEX idx_cuenta_padre (cuenta_padre),
        INDEX idx_tipo (tipo),
        INDEX idx_permite_movimientos (permite_movimientos),
        INDEX idx_clasificacion_ifrs (clasificacion_ifrs),
        INDEX idx_activa (activa)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de asientos contables (cabecera)
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_asientos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        numero_asiento VARCHAR(50) NOT NULL,
        ano_contable INT NOT NULL,
        periodo_contable INT NOT NULL COMMENT '1-12',
        fecha_documento DATE NOT NULL,
        fecha_registro DATE NOT NULL,
        lote VARCHAR(50) COMMENT 'Batch',
        tipo_asiento ENUM('manual', 'automatico', 'reverso', 'ajuste', 'apertura', 'cierre') NOT NULL,
        origen VARCHAR(100) COMMENT 'ventas compras banco inventario produccion rrhh',
        glosa_cabecera TEXT,

        usuario_creacion INT,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        usuario_aprobacion INT NULL,
        fecha_aprobacion TIMESTAMP NULL,
        estado ENUM('borrador', 'aprobado', 'contabilizado', 'reversado', 'bloqueado') DEFAULT 'borrador',

        total_debito DECIMAL(18,2) DEFAULT 0,
        total_credito DECIMAL(18,2) DEFAULT 0,
        diferencia DECIMAL(18,2) GENERATED ALWAYS AS (total_debito - total_credito) STORED,
        cuadrado TINYINT(1) GENERATED ALWAYS AS (ABS(total_debito - total_credito) < 0.01) STORED,

        asiento_reversado_id INT NULL COMMENT 'ID del asiento que fue reversado',
        asiento_reverso_id INT NULL COMMENT 'ID del asiento reverso',
        es_asiento_critico TINYINT(1) DEFAULT 0,
        requiere_doble_aprobacion TINYINT(1) DEFAULT 0,
        bloqueado_auditoria TINYINT(1) DEFAULT 0,

        usuario_modifica INT NULL,
        fecha_ultima_modificacion TIMESTAMP NULL,

        UNIQUE KEY uk_numero_asiento (numero_asiento, company_id),
        INDEX idx_ano_periodo (ano_contable, periodo_contable),
        INDEX idx_fecha_documento (fecha_documento),
        INDEX idx_tipo_asiento (tipo_asiento),
        INDEX idx_origen (origen),
        INDEX idx_estado (estado),
        INDEX idx_lote (lote)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de lineas de asiento (detalle)
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_asientos_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asiento_id INT NOT NULL,
        linea INT NOT NULL,
        cuenta_id INT NOT NULL,
        descripcion_cuenta VARCHAR(255),
        debito DECIMAL(18,2) DEFAULT 0,
        credito DECIMAL(18,2) DEFAULT 0,
        moneda VARCHAR(3) DEFAULT 'CLP',
        tipo_cambio DECIMAL(12,6) DEFAULT 1.000000,
        debito_moneda_base DECIMAL(18,2) GENERATED ALWAYS AS (debito * tipo_cambio) STORED,
        credito_moneda_base DECIMAL(18,2) GENERATED ALWAYS AS (credito * tipo_cambio) STORED,

        centro_costo_id INT NULL,
        proyecto_id INT NULL,
        sucursal_id INT NULL,
        auxiliar_tipo VARCHAR(50) COMMENT 'cliente proveedor empleado activo_fijo',
        auxiliar_id INT NULL,
        referencia_documento VARCHAR(100),
        numero_documento VARCHAR(100),
        fecha_documento DATE,
        glosa_detalle TEXT,

        linea_reversada TINYINT(1) DEFAULT 0,
        regla_automatica VARCHAR(100),

        FOREIGN KEY (asiento_id) REFERENCES acc_asientos(id) ON DELETE CASCADE,
        FOREIGN KEY (cuenta_id) REFERENCES acc_plan_cuentas(id),
        INDEX idx_asiento (asiento_id),
        INDEX idx_cuenta (cuenta_id),
        INDEX idx_centro_costo (centro_costo_id),
        INDEX idx_auxiliar (auxiliar_tipo, auxiliar_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de periodos contables
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_periodos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        ano INT NOT NULL,
        periodo INT NOT NULL COMMENT '1-12',
        nombre VARCHAR(50),
        fecha_inicio DATE NOT NULL,
        fecha_fin DATE NOT NULL,
        abierto TINYINT(1) DEFAULT 1,
        bloqueado_auditoria TINYINT(1) DEFAULT 0,
        fecha_cierre TIMESTAMP NULL,
        usuario_cierre INT NULL,
        UNIQUE KEY uk_ano_periodo (ano, periodo, company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de centros de costo
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_centros_costo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        codigo VARCHAR(50) NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        descripcion TEXT,
        activo TINYINT(1) DEFAULT 1,
        UNIQUE KEY uk_codigo (codigo, company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de proyectos
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_proyectos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        codigo VARCHAR(50) NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        descripcion TEXT,
        fecha_inicio DATE,
        fecha_fin DATE,
        activo TINYINT(1) DEFAULT 1,
        UNIQUE KEY uk_codigo (codigo, company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de auditoria completa
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_auditoria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tabla VARCHAR(100) NOT NULL,
        registro_id INT NOT NULL,
        accion ENUM('crear', 'modificar', 'eliminar', 'aprobar', 'revertir', 'bloquear') NOT NULL,
        campo VARCHAR(100),
        valor_anterior TEXT,
        valor_nuevo TEXT,
        usuario_id INT,
        fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45),
        user_agent TEXT,
        INDEX idx_tabla_registro (tabla, registro_id),
        INDEX idx_fecha (fecha),
        INDEX idx_usuario (usuario_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de versiones de cuentas
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_cuentas_versiones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cuenta_id INT NOT NULL,
        version INT NOT NULL,
        datos_json TEXT,
        usuario_modificacion INT,
        fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_cuenta (cuenta_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de plantillas de asientos
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_plantillas_asientos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        codigo VARCHAR(50) NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        descripcion TEXT,
        tipo_asiento VARCHAR(50),
        plantilla_json TEXT,
        es_recurrente TINYINT(1) DEFAULT 0,
        frecuencia VARCHAR(50) COMMENT 'mensual trimestral anual',
        activa TINYINT(1) DEFAULT 1,
        UNIQUE KEY uk_codigo (codigo, company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de saldos contables
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_saldos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL DEFAULT 1,
        cuenta_id INT NOT NULL,
        ano INT NOT NULL,
        periodo INT NOT NULL,
        saldo_inicial_debito DECIMAL(18,2) DEFAULT 0,
        saldo_inicial_credito DECIMAL(18,2) DEFAULT 0,
        movimientos_debito DECIMAL(18,2) DEFAULT 0,
        movimientos_credito DECIMAL(18,2) DEFAULT 0,
        saldo_final_debito DECIMAL(18,2) DEFAULT 0,
        saldo_final_credito DECIMAL(18,2) DEFAULT 0,
        saldo_final DECIMAL(18,2) GENERATED ALWAYS AS (saldo_final_debito - saldo_final_credito) STORED,
        UNIQUE KEY uk_cuenta_periodo (cuenta_id, ano, periodo, company_id),
        INDEX idx_ano_periodo (ano, periodo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

} catch (PDOException $e) {
    $error = "Error al crear tablas: " . $e->getMessage();
}

// === CREAR CUENTA ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'crear_cuenta') {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO acc_plan_cuentas (
                company_id, codigo_completo, codigo_corto, nivel, cuenta_padre, estructura_jerarquica,
                orden_presentacion, tipo_cuenta, nombre_cuenta, descripcion, naturaleza, tipo, subtipo,
                moneda_base, moneda_alternativa, permite_movimientos, permite_asientos_automaticos,
                requiere_centro_costo, requiere_auxiliar, requiere_proyecto, requiere_dimension_adicional,
                cuenta_revaluacion_id, cuenta_ajuste_tipo_cambio_id, es_cuenta_flujo_efectivo, es_cuenta_restringida,
                clasificacion_ifrs, clasificacion_nic_niif, clasificacion_analisis_financiero, clasificacion_libro_tributario,
                grupo_depreciacion, asignacion_rubro_balance, asignacion_rubro_resultado,
                clasificacion_informes_gestion, clasificacion_flujo_caja, clasificacion_proyectos,
                integracion_ventas, integracion_compras, integracion_clientes, integracion_bancos,
                integracion_inventario, integracion_activos_fijos, integracion_remuneraciones,
                integracion_produccion, integracion_tesoreria, integracion_impuestos,
                cuenta_parametrica_tipo, asignacion_tipo_documento, asignacion_area, asignacion_sucursal,
                asignacion_pais, asignacion_tipo_transaccion, cuenta_conciliacion_automatica,
                bloqueada, edicion_restringida, activa, usuario_creacion
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $company_id,
            trim($_POST['codigo_completo']),
            trim($_POST['codigo_corto'] ?? ''),
            intval($_POST['nivel']),
            !empty($_POST['cuenta_padre']) ? intval($_POST['cuenta_padre']) : null,
            $_POST['estructura_jerarquica'] ?? '',
            intval($_POST['orden_presentacion'] ?? 0),
            $_POST['tipo_cuenta'] ?? 'balance',
            trim($_POST['nombre_cuenta']),
            trim($_POST['descripcion'] ?? ''),
            $_POST['naturaleza'],
            $_POST['tipo'],
            $_POST['subtipo'] ?? '',
            $_POST['moneda_base'] ?? 'CLP',
            $_POST['moneda_alternativa'] ?? null,
            isset($_POST['permite_movimientos']) ? 1 : 0,
            isset($_POST['permite_asientos_automaticos']) ? 1 : 0,
            isset($_POST['requiere_centro_costo']) ? 1 : 0,
            $_POST['requiere_auxiliar'] ?? 'ninguno',
            isset($_POST['requiere_proyecto']) ? 1 : 0,
            isset($_POST['requiere_dimension_adicional']) ? 1 : 0,
            !empty($_POST['cuenta_revaluacion_id']) ? intval($_POST['cuenta_revaluacion_id']) : null,
            !empty($_POST['cuenta_ajuste_tipo_cambio_id']) ? intval($_POST['cuenta_ajuste_tipo_cambio_id']) : null,
            isset($_POST['es_cuenta_flujo_efectivo']) ? 1 : 0,
            isset($_POST['es_cuenta_restringida']) ? 1 : 0,
            $_POST['clasificacion_ifrs'] ?? '',
            $_POST['clasificacion_nic_niif'] ?? '',
            $_POST['clasificacion_analisis_financiero'] ?? '',
            $_POST['clasificacion_libro_tributario'] ?? '',
            $_POST['grupo_depreciacion'] ?? '',
            $_POST['asignacion_rubro_balance'] ?? '',
            $_POST['asignacion_rubro_resultado'] ?? '',
            $_POST['clasificacion_informes_gestion'] ?? '',
            $_POST['clasificacion_flujo_caja'] ?? '',
            $_POST['clasificacion_proyectos'] ?? '',
            isset($_POST['integracion_ventas']) ? 1 : 0,
            isset($_POST['integracion_compras']) ? 1 : 0,
            isset($_POST['integracion_clientes']) ? 1 : 0,
            isset($_POST['integracion_bancos']) ? 1 : 0,
            isset($_POST['integracion_inventario']) ? 1 : 0,
            isset($_POST['integracion_activos_fijos']) ? 1 : 0,
            isset($_POST['integracion_remuneraciones']) ? 1 : 0,
            isset($_POST['integracion_produccion']) ? 1 : 0,
            isset($_POST['integracion_tesoreria']) ? 1 : 0,
            isset($_POST['integracion_impuestos']) ? 1 : 0,
            $_POST['cuenta_parametrica_tipo'] ?? '',
            $_POST['asignacion_tipo_documento'] ?? '',
            $_POST['asignacion_area'] ?? '',
            $_POST['asignacion_sucursal'] ?? '',
            $_POST['asignacion_pais'] ?? '',
            $_POST['asignacion_tipo_transaccion'] ?? '',
            isset($_POST['cuenta_conciliacion_automatica']) ? 1 : 0,
            isset($_POST['bloqueada']) ? 1 : 0,
            isset($_POST['edicion_restringida']) ? 1 : 0,
            isset($_POST['activa']) ? 1 : 1,
            $user_id
        ]);

        $cuenta_id = $pdo->lastInsertId();
        $stmt->closeCursor();

        // Registrar en auditoria
        $stmt = $pdo->prepare("
            INSERT INTO acc_auditoria (tabla, registro_id, accion, usuario_id)
            VALUES ('acc_plan_cuentas', ?, 'crear', ?)
        ");
        $stmt->execute([$cuenta_id, $user_id]);
        $stmt->closeCursor();

        $pdo->commit();
        $message = "Cuenta creada exitosamente: " . $_POST['codigo_completo'] . " - " . $_POST['nombre_cuenta'];

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al crear cuenta: " . $e->getMessage();
    }
}

// === CREAR ASIENTO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'crear_asiento') {
    try {
        $pdo->beginTransaction();

        // Validar periodo abierto
        $stmt = $pdo->prepare("
            SELECT abierto FROM acc_periodos
            WHERE company_id = ? AND ano = ? AND periodo = ?
        ");
        $ano = date('Y', strtotime($_POST['fecha_documento']));
        $periodo = date('n', strtotime($_POST['fecha_documento']));
        $stmt->execute([$company_id, $ano, $periodo]);
        $periodo_info = $stmt->fetch();
        $stmt->closeCursor();

        if ($periodo_info && !$periodo_info['abierto']) {
            throw new Exception("El periodo contable esta cerrado");
        }

        // Generar numero de asiento
        $stmt = $pdo->prepare("
            SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(numero_asiento, '-', -1) AS UNSIGNED)), 0) + 1 as siguiente
            FROM acc_asientos
            WHERE company_id = ? AND ano_contable = ?
        ");
        $stmt->execute([$company_id, $ano]);
        $siguiente = $stmt->fetch()['siguiente'];
        $stmt->closeCursor();

        $numero_asiento = 'AST-' . $ano . '-' . str_pad($siguiente, 8, '0', STR_PAD_LEFT);

        // Crear asiento
        $stmt = $pdo->prepare("
            INSERT INTO acc_asientos (
                company_id, numero_asiento, ano_contable, periodo_contable, fecha_documento,
                fecha_registro, lote, tipo_asiento, origen, glosa_cabecera, usuario_creacion,
                estado, es_asiento_critico, requiere_doble_aprobacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'borrador', ?, ?)
        ");
        $stmt->execute([
            $company_id, $numero_asiento, $ano, $periodo,
            $_POST['fecha_documento'],
            $_POST['fecha_registro'] ?? date('Y-m-d'),
            $_POST['lote'] ?? '',
            $_POST['tipo_asiento'],
            $_POST['origen'] ?? 'manual',
            trim($_POST['glosa_cabecera']),
            $user_id,
            isset($_POST['es_asiento_critico']) ? 1 : 0,
            isset($_POST['requiere_doble_aprobacion']) ? 1 : 0
        ]);

        $asiento_id = $pdo->lastInsertId();
        $stmt->closeCursor();

        // Procesar lineas
        $total_debito = 0;
        $total_credito = 0;

        if (isset($_POST['linea_cuenta_id']) && is_array($_POST['linea_cuenta_id'])) {
            for ($i = 0; $i < count($_POST['linea_cuenta_id']); $i++) {
                if (empty($_POST['linea_cuenta_id'][$i])) continue;

                $debito = floatval($_POST['linea_debito'][$i] ?? 0);
                $credito = floatval($_POST['linea_credito'][$i] ?? 0);

                if ($debito == 0 && $credito == 0) continue;

                // Validar cuenta activa y permite movimientos
                $stmt = $pdo->prepare("
                    SELECT id, nombre_cuenta, permite_movimientos, bloqueada
                    FROM acc_plan_cuentas
                    WHERE id = ? AND company_id = ? AND activa = 1
                ");
                $stmt->execute([$_POST['linea_cuenta_id'][$i], $company_id]);
                $cuenta = $stmt->fetch();
                $stmt->closeCursor();

                if (!$cuenta) {
                    throw new Exception("Cuenta invalida o inactiva en linea " . ($i + 1));
                }

                if (!$cuenta['permite_movimientos']) {
                    throw new Exception("La cuenta " . $cuenta['nombre_cuenta'] . " no permite movimientos");
                }

                if ($cuenta['bloqueada']) {
                    throw new Exception("La cuenta " . $cuenta['nombre_cuenta'] . " esta bloqueada");
                }

                // Insertar linea
                $stmt = $pdo->prepare("
                    INSERT INTO acc_asientos_detalle (
                        asiento_id, linea, cuenta_id, descripcion_cuenta, debito, credito, moneda, tipo_cambio,
                        centro_costo_id, proyecto_id, sucursal_id, auxiliar_tipo, auxiliar_id,
                        referencia_documento, numero_documento, fecha_documento, glosa_detalle, regla_automatica
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $asiento_id, $i + 1, $_POST['linea_cuenta_id'][$i], $cuenta['nombre_cuenta'],
                    $debito, $credito,
                    $_POST['linea_moneda'][$i] ?? 'CLP',
                    floatval($_POST['linea_tipo_cambio'][$i] ?? 1),
                    !empty($_POST['linea_centro_costo'][$i]) ? intval($_POST['linea_centro_costo'][$i]) : null,
                    !empty($_POST['linea_proyecto'][$i]) ? intval($_POST['linea_proyecto'][$i]) : null,
                    !empty($_POST['linea_sucursal'][$i]) ? intval($_POST['linea_sucursal'][$i]) : null,
                    $_POST['linea_auxiliar_tipo'][$i] ?? null,
                    !empty($_POST['linea_auxiliar_id'][$i]) ? intval($_POST['linea_auxiliar_id'][$i]) : null,
                    $_POST['linea_referencia'][$i] ?? '',
                    $_POST['linea_numero_doc'][$i] ?? '',
                    !empty($_POST['linea_fecha_doc'][$i]) ? $_POST['linea_fecha_doc'][$i] : null,
                    trim($_POST['linea_glosa'][$i] ?? ''),
                    $_POST['linea_regla'][$i] ?? ''
                ]);
                $stmt->closeCursor();

                $total_debito += $debito;
                $total_credito += $credito;
            }
        }

        // Actualizar totales
        $stmt = $pdo->prepare("
            UPDATE acc_asientos
            SET total_debito = ?, total_credito = ?
            WHERE id = ?
        ");
        $stmt->execute([$total_debito, $total_credito, $asiento_id]);
        $stmt->closeCursor();

        // Validar cuadre
        if (abs($total_debito - $total_credito) > 0.01) {
            throw new Exception("El asiento no cuadra. Debito: $total_debito, Credito: $total_credito");
        }

        // Registrar en auditoria
        $stmt = $pdo->prepare("
            INSERT INTO acc_auditoria (tabla, registro_id, accion, usuario_id)
            VALUES ('acc_asientos', ?, 'crear', ?)
        ");
        $stmt->execute([$asiento_id, $user_id]);
        $stmt->closeCursor();

        $pdo->commit();
        $message = "Asiento creado exitosamente: $numero_asiento";

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al crear asiento: " . $e->getMessage();
    }
}

// === CONTABILIZAR ASIENTO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'contabilizar_asiento') {
    try {
        $pdo->beginTransaction();

        $asiento_id = intval($_POST['asiento_id']);

        // Validar estado
        $stmt = $pdo->prepare("
            SELECT * FROM acc_asientos
            WHERE id = ? AND company_id = ? AND estado = 'borrador'
        ");
        $stmt->execute([$asiento_id, $company_id]);
        $asiento = $stmt->fetch();
        $stmt->closeCursor();

        if (!$asiento) {
            throw new Exception("Asiento no encontrado o ya contabilizado");
        }

        if (!$asiento['cuadrado']) {
            throw new Exception("El asiento no cuadra");
        }

        // Actualizar estado
        $stmt = $pdo->prepare("
            UPDATE acc_asientos
            SET estado = 'contabilizado', usuario_aprobacion = ?, fecha_aprobacion = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$user_id, $asiento_id]);
        $stmt->closeCursor();

        // Actualizar saldos
        $stmt = $pdo->prepare("SELECT * FROM acc_asientos_detalle WHERE asiento_id = ?");
        $stmt->execute([$asiento_id]);
        $lineas = $stmt->fetchAll();
        $stmt->closeCursor();

        foreach ($lineas as $linea) {
            // Verificar si existe saldo
            $stmt = $pdo->prepare("
                SELECT id FROM acc_saldos
                WHERE cuenta_id = ? AND ano = ? AND periodo = ? AND company_id = ?
            ");
            $stmt->execute([$linea['cuenta_id'], $asiento['ano_contable'], $asiento['periodo_contable'], $company_id]);
            $saldo = $stmt->fetch();
            $stmt->closeCursor();

            if ($saldo) {
                // Actualizar saldo
                $stmt = $pdo->prepare("
                    UPDATE acc_saldos
                    SET movimientos_debito = movimientos_debito + ?,
                        movimientos_credito = movimientos_credito + ?,
                        saldo_final_debito = saldo_inicial_debito + movimientos_debito + ?,
                        saldo_final_credito = saldo_inicial_credito + movimientos_credito + ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $linea['debito'], $linea['credito'],
                    $linea['debito'], $linea['credito'],
                    $saldo['id']
                ]);
                $stmt->closeCursor();
            } else {
                // Crear saldo
                $stmt = $pdo->prepare("
                    INSERT INTO acc_saldos (
                        company_id, cuenta_id, ano, periodo,
                        movimientos_debito, movimientos_credito,
                        saldo_final_debito, saldo_final_credito
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $company_id, $linea['cuenta_id'], $asiento['ano_contable'], $asiento['periodo_contable'],
                    $linea['debito'], $linea['credito'],
                    $linea['debito'], $linea['credito']
                ]);
                $stmt->closeCursor();
            }
        }

        // Registrar en auditoria
        $stmt = $pdo->prepare("
            INSERT INTO acc_auditoria (tabla, registro_id, accion, usuario_id)
            VALUES ('acc_asientos', ?, 'aprobar', ?)
        ");
        $stmt->execute([$asiento_id, $user_id]);
        $stmt->closeCursor();

        $pdo->commit();
        $message = "Asiento contabilizado exitosamente";

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al contabilizar: " . $e->getMessage();
    }
}

// === OBTENER DATOS ===
$cuentas = [];
$asientos = [];
$stats = [];
$centros_costo = [];
$proyectos = [];

try {
    // Estadisticas
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_cuentas,
               SUM(CASE WHEN activa = 1 THEN 1 ELSE 0 END) as cuentas_activas,
               SUM(CASE WHEN permite_movimientos = 1 THEN 1 ELSE 0 END) as cuentas_movimiento
        FROM acc_plan_cuentas
        WHERE company_id = ?
    ");
    $stmt->execute([$company_id]);
    $stats = $stmt->fetch();
    $stmt->closeCursor();

    // Cuentas
    if ($action === 'cuentas') {
        $stmt = $pdo->prepare("
            SELECT * FROM acc_plan_cuentas
            WHERE company_id = ?
            ORDER BY codigo_completo
        ");
        $stmt->execute([$company_id]);
        $cuentas = $stmt->fetchAll();
        $stmt->closeCursor();
    }

    // Asientos
    if ($action === 'asientos') {
        $stmt = $pdo->prepare("
            SELECT * FROM acc_asientos
            WHERE company_id = ?
            ORDER BY fecha_documento DESC, numero_asiento DESC
            LIMIT 100
        ");
        $stmt->execute([$company_id]);
        $asientos = $stmt->fetchAll();
        $stmt->closeCursor();
    }

    // Centros de costo
    $stmt = $pdo->prepare("SELECT * FROM acc_centros_costo WHERE company_id = ? AND activo = 1");
    $stmt->execute([$company_id]);
    $centros_costo = $stmt->fetchAll();
    $stmt->closeCursor();

    // Proyectos
    $stmt = $pdo->prepare("SELECT * FROM acc_proyectos WHERE company_id = ? AND activo = 1");
    $stmt->execute([$company_id]);
    $proyectos = $stmt->fetchAll();
    $stmt->closeCursor();

} catch (PDOException $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contabilidad - CONECTA ERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; color: #1a1a1a; line-height: 1.6; }
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
        .stat-card h3 { font-size: 0.8rem; color: #6b7280; margin-bottom: 0.5rem; text-transform: uppercase; font-weight: 600; }
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
        .btn-small { padding: 0.375rem 0.75rem; font-size: 0.813rem; }
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
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151; font-size: 0.875rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.625rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #1e40af; box-shadow: 0 0 0 3px rgba(30,64,175,0.1); }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .checkbox-group { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
        .checkbox-group input[type="checkbox"] { width: auto; }
        .alert { padding: 1rem 1.25rem; border-radius: 6px; margin-bottom: 1.5rem; font-weight: 500; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .nivel-badge { display: inline-block; padding: 0.125rem 0.5rem; background: #3b82f6; color: white; border-radius: 3px; font-size: 0.75rem; font-weight: 600; margin-right: 0.5rem; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Contabilidad Financiera Completa</h1>
        <p>Plan de Cuentas Jerarquico (7 niveles) - Asientos Contables - Integraciones - Auditoria</p>
    </div>

    <div class="tabs">
        <a href="?action=cuentas" class="<?php echo $action === 'cuentas' ? 'active' : ''; ?>">Cuentas</a>
        <a href="?action=asientos" class="<?php echo $action === 'asientos' ? 'active' : ''; ?>">Asientos Contables</a>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($action === 'cuentas'): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Cuentas</h3>
                <div class="value"><?php echo $stats['total_cuentas'] ?? 0; ?></div>
                <div class="label">en plan de cuentas</div>
            </div>
            <div class="stat-card">
                <h3>Cuentas Activas</h3>
                <div class="value"><?php echo $stats['cuentas_activas'] ?? 0; ?></div>
                <div class="label">actualmente activas</div>
            </div>
            <div class="stat-card">
                <h3>Con Movimientos</h3>
                <div class="value"><?php echo $stats['cuentas_movimiento'] ?? 0; ?></div>
                <div class="label">permite movimientos</div>
            </div>
            <div class="stat-card">
                <h3>Sistema</h3>
                <div class="value">OK</div>
                <div class="label">operativo</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Plan de Cuentas (7 Niveles Jerarquicos)</h2>
                <button onclick="document.getElementById('formNuevaCuenta').style.display='block'" class="btn btn-primary">Nueva Cuenta</button>
            </div>

            <div id="formNuevaCuenta" style="display:none; margin-bottom: 2rem; padding: 1.5rem; background: #f9fafb; border-radius: 8px;">
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Nueva Cuenta Contable</h3>
                <form method="POST" action="?action=crear_cuenta">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Codigo Completo *</label>
                            <input type="text" name="codigo_completo" placeholder="Ej: 1-1-02-004-01-001-001" required>
                        </div>
                        <div class="form-group">
                            <label>Codigo Corto</label>
                            <input type="text" name="codigo_corto" placeholder="Ej: 1102004">
                        </div>
                        <div class="form-group">
                            <label>Nivel *</label>
                            <select name="nivel" required>
                                <option value="1">Nivel 1 - Clase</option>
                                <option value="2">Nivel 2 - Grupo</option>
                                <option value="3">Nivel 3 - Subgrupo</option>
                                <option value="4">Nivel 4 - Cuenta</option>
                                <option value="5">Nivel 5 - Subcuenta</option>
                                <option value="6">Nivel 6 - Analitica</option>
                                <option value="7">Nivel 7 - Detalle</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nombre Cuenta *</label>
                            <input type="text" name="nombre_cuenta" required>
                        </div>
                        <div class="form-group">
                            <label>Naturaleza *</label>
                            <select name="naturaleza" required>
                                <option value="deudor">Deudor</option>
                                <option value="acreedor">Acreedor</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tipo *</label>
                            <select name="tipo" required>
                                <option value="activo">Activo</option>
                                <option value="pasivo">Pasivo</option>
                                <option value="patrimonio">Patrimonio</option>
                                <option value="ingreso">Ingreso</option>
                                <option value="gasto">Gasto</option>
                                <option value="orden">Orden</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subtipo</label>
                            <input type="text" name="subtipo" placeholder="circulante, fijo, no corriente">
                        </div>
                        <div class="form-group">
                            <label>Clasificacion IFRS</label>
                            <input type="text" name="clasificacion_ifrs">
                        </div>
                        <div class="form-group">
                            <label>Clasificacion NIC/NIIF</label>
                            <input type="text" name="clasificacion_nic_niif">
                        </div>
                        <div class="form-group">
                            <label>Flujo de Caja</label>
                            <select name="clasificacion_flujo_caja">
                                <option value="">Ninguna</option>
                                <option value="operacion">Operacion</option>
                                <option value="inversion">Inversion</option>
                                <option value="financiacion">Financiacion</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descripcion</label>
                        <textarea name="descripcion"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <div class="checkbox-group">
                            <input type="checkbox" name="permite_movimientos" id="permite_mov">
                            <label for="permite_mov">Permite Movimientos</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="requiere_centro_costo" id="req_cc">
                            <label for="req_cc">Requiere Centro Costo</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="requiere_proyecto" id="req_proy">
                            <label for="req_proy">Requiere Proyecto</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="es_cuenta_flujo_efectivo" id="flujo_ef">
                            <label for="flujo_ef">Cuenta Flujo Efectivo</label>
                        </div>
                    </div>

                    <h4 style="margin: 1.5rem 0 1rem; font-size: 1rem; color: #1f2937;">Integraciones</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
                        <div class="checkbox-group">
                            <input type="checkbox" name="integracion_ventas" id="int_ventas">
                            <label for="int_ventas">Ventas</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="integracion_compras" id="int_compras">
                            <label for="int_compras">Compras</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="integracion_clientes" id="int_clientes">
                            <label for="int_clientes">Clientes</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="integracion_bancos" id="int_bancos">
                            <label for="int_bancos">Bancos</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="integracion_inventario" id="int_inv">
                            <label for="int_inv">Inventario</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="integracion_activos_fijos" id="int_af">
                            <label for="int_af">Activos Fijos</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="integracion_remuneraciones" id="int_rem">
                            <label for="int_rem">Remuneraciones</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="integracion_produccion" id="int_prod">
                            <label for="int_prod">Produccion</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="integracion_tesoreria" id="int_tes">
                            <label for="int_tes">Tesoreria</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="integracion_impuestos" id="int_imp">
                            <label for="int_imp">Impuestos</label>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-success">Crear Cuenta</button>
                        <button type="button" onclick="document.getElementById('formNuevaCuenta').style.display='none'" class="btn btn-primary" style="background: #6b7280;">Cancelar</button>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nivel</th>
                            <th>Codigo</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Naturaleza</th>
                            <th>IFRS</th>
                            <th>Movimientos</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cuentas as $cuenta): ?>
                        <tr>
                            <td><span class="nivel-badge">N<?php echo $cuenta['nivel']; ?></span></td>
                            <td><strong><?php echo htmlspecialchars($cuenta['codigo_completo']); ?></strong></td>
                            <td style="padding-left: <?php echo ($cuenta['nivel'] * 1.5); ?>rem;"><?php echo htmlspecialchars($cuenta['nombre_cuenta']); ?></td>
                            <td><?php echo strtoupper($cuenta['tipo']); ?></td>
                            <td><?php echo strtoupper($cuenta['naturaleza']); ?></td>
                            <td><?php echo htmlspecialchars($cuenta['clasificacion_ifrs'] ?? '-'); ?></td>
                            <td><?php echo $cuenta['permite_movimientos'] ? 'Si' : 'No'; ?></td>
                            <td><span class="badge badge-<?php echo $cuenta['activa'] ? 'success' : 'danger'; ?>"><?php echo $cuenta['activa'] ? 'ACTIVA' : 'INACTIVA'; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($action === 'asientos'): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Asientos</h3>
                <div class="value"><?php echo count($asientos); ?></div>
                <div class="label">registrados</div>
            </div>
            <div class="stat-card">
                <h3>Ano Actual</h3>
                <div class="value"><?php echo date('Y'); ?></div>
                <div class="label">ejercicio fiscal</div>
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

        <div class="card">
            <div class="card-header">
                <h2>Asientos Contables</h2>
                <button onclick="document.getElementById('formNuevoAsiento').style.display='block'" class="btn btn-primary">Nuevo Asiento</button>
            </div>

            <div id="formNuevoAsiento" style="display:none; margin-bottom: 2rem; padding: 1.5rem; background: #f9fafb; border-radius: 8px;">
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Nuevo Asiento Contable</h3>
                <form method="POST" action="?action=crear_asiento">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Fecha Documento *</label>
                            <input type="date" name="fecha_documento" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Tipo Asiento *</label>
                            <select name="tipo_asiento" required>
                                <option value="manual">Manual</option>
                                <option value="automatico">Automatico</option>
                                <option value="ajuste">Ajuste</option>
                                <option value="reverso">Reverso</option>
                                <option value="apertura">Apertura</option>
                                <option value="cierre">Cierre</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Origen</label>
                            <input type="text" name="origen" value="manual" placeholder="ventas compras banco">
                        </div>
                        <div class="form-group">
                            <label>Lote</label>
                            <input type="text" name="lote" placeholder="Numero de lote">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Glosa Cabecera *</label>
                        <textarea name="glosa_cabecera" required></textarea>
                    </div>

                    <h4 style="margin: 1.5rem 0 1rem; font-size: 1rem; color: #1f2937;">Lineas del Asiento</h4>
                    <div id="lineasAsiento">
                        <div class="linea-asiento" style="display: grid; grid-template-columns: 2fr 1fr 1fr 2fr auto; gap: 0.5rem; margin-bottom: 0.5rem; align-items: end;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Cuenta</label>
                                <select name="linea_cuenta_id[]" required>
                                    <option value="">Seleccione...</option>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT id, codigo_completo, nombre_cuenta FROM acc_plan_cuentas WHERE company_id = ? AND permite_movimientos = 1 AND activa = 1 ORDER BY codigo_completo");
                                    $stmt->execute([$company_id]);
                                    $cuentas_select = $stmt->fetchAll();
                                    $stmt->closeCursor();
                                    foreach ($cuentas_select as $c):
                                    ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['codigo_completo'] . ' - ' . $c['nombre_cuenta']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Debito</label>
                                <input type="number" name="linea_debito[]" step="0.01" min="0" value="0">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Credito</label>
                                <input type="number" name="linea_credito[]" step="0.01" min="0" value="0">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Glosa</label>
                                <input type="text" name="linea_glosa[]">
                            </div>
                            <button type="button" class="btn btn-small" style="background: #dc2626; color: white; height: 38px;" onclick="this.parentElement.remove()">X</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-small" style="background: #10b981; color: white; margin-bottom: 1rem;" onclick="agregarLinea()">Agregar Linea</button>

                    <input type="hidden" name="linea_moneda[]" value="CLP">
                    <input type="hidden" name="linea_tipo_cambio[]" value="1">
                    <input type="hidden" name="linea_centro_costo[]" value="">
                    <input type="hidden" name="linea_proyecto[]" value="">
                    <input type="hidden" name="linea_sucursal[]" value="">
                    <input type="hidden" name="linea_auxiliar_tipo[]" value="">
                    <input type="hidden" name="linea_auxiliar_id[]" value="">
                    <input type="hidden" name="linea_referencia[]" value="">
                    <input type="hidden" name="linea_numero_doc[]" value="">
                    <input type="hidden" name="linea_fecha_doc[]" value="">
                    <input type="hidden" name="linea_regla[]" value="">

                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-success">Crear Asiento</button>
                        <button type="button" onclick="document.getElementById('formNuevoAsiento').style.display='none'" class="btn btn-primary" style="background: #6b7280;">Cancelar</button>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Numero</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Glosa</th>
                            <th>Debito</th>
                            <th>Credito</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asientos as $asiento): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($asiento['numero_asiento']); ?></strong></td>
                            <td><?php echo date('d/m/Y', strtotime($asiento['fecha_documento'])); ?></td>
                            <td><?php echo strtoupper($asiento['tipo_asiento']); ?></td>
                            <td><?php echo htmlspecialchars(substr($asiento['glosa_cabecera'], 0, 50)); ?></td>
                            <td>$<?php echo number_format($asiento['total_debito'], 2); ?></td>
                            <td>$<?php echo number_format($asiento['total_credito'], 2); ?></td>
                            <td><span class="badge badge-<?php echo $asiento['estado'] === 'contabilizado' ? 'success' : 'warning'; ?>"><?php echo strtoupper($asiento['estado']); ?></span></td>
                            <td>
                                <?php if ($asiento['estado'] === 'borrador'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="contabilizar_asiento">
                                    <input type="hidden" name="asiento_id" value="<?php echo $asiento['id']; ?>">
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
        <?php endif; ?>
    </div>

    <script>
    function agregarLinea() {
        var lineasDiv = document.getElementById('lineasAsiento');
        var primeraLinea = lineasDiv.querySelector('.linea-asiento');
        var nuevaLinea = primeraLinea.cloneNode(true);
        nuevaLinea.querySelectorAll('input').forEach(function(input) {
            if (input.type === 'number') input.value = '0';
            else input.value = '';
        });
        nuevaLinea.querySelector('select').selectedIndex = 0;
        lineasDiv.appendChild(nuevaLinea);
    }
    </script>
</body>
</html>
