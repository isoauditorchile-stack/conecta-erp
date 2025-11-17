<?php
/**
 * ===============================================
 * CONECTA ERP - MIDDLEWARE DE CONTROL DE ACCESO
 * ===============================================
 * Valida acceso a módulos según plan y suscripción
 *
 * USO:
 * require_once 'includes/middleware_acceso.php';
 * requiereModulo('ventas');
 */

// Solo iniciar sesión si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

class MiddlewareAcceso {

    private $conn;
    private $empresa_id;
    private $usuario_id;
    private $plan_id;
    private $plan_nombre;
    private $suscripcion_estado;
    private $es_trial;
    private $fecha_fin;

    /**
     * Constructor
     */
    public function __construct($conn) {
        $this->conn = $conn;
        $this->empresa_id = $_SESSION['empresa_id'] ?? null;
        $this->usuario_id = $_SESSION['user_id'] ?? null;

        // Verificar que el usuario esté autenticado
        if (!$this->empresa_id || !$this->usuario_id) {
            $this->redirigirLogin('Sesión expirada. Por favor inicia sesión nuevamente.');
            exit();
        }

        // Cargar información del plan
        $this->cargarPlan();
    }

    /**
     * Cargar información del plan activo de la empresa
     */
    private function cargarPlan() {
        $stmt = $this->conn->prepare("
            SELECT
                s.plan_id,
                s.estado,
                s.fecha_fin,
                s.es_trial,
                p.nombre as plan_nombre,
                p.max_usuarios
            FROM suscripciones s
            INNER JOIN planes p ON s.plan_id = p.id
            WHERE s.empresa_id = ?
              AND s.estado IN ('trial', 'activa')
              AND (s.fecha_fin IS NULL OR s.fecha_fin >= NOW())
            ORDER BY s.id DESC
            LIMIT 1
        ");

        if (!$stmt) {
            die("Error al preparar consulta de suscripción: " . $this->conn->error);
        }

        $stmt->bind_param("i", $this->empresa_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // No hay suscripción activa
            $this->mostrarPantallaSuscripcionExpirada();
            exit();
        }

        $suscripcion = $result->fetch_assoc();
        $this->plan_id = $suscripcion['plan_id'];
        $this->plan_nombre = $suscripcion['plan_nombre'];
        $this->suscripcion_estado = $suscripcion['estado'];
        $this->es_trial = $suscripcion['es_trial'];
        $this->fecha_fin = $suscripcion['fecha_fin'];

        // Guardar en sesión para acceso rápido
        $_SESSION['plan_id'] = $this->plan_id;
        $_SESSION['plan_nombre'] = $this->plan_nombre;
        $_SESSION['suscripcion_estado'] = $this->suscripcion_estado;
        $_SESSION['es_trial'] = $this->es_trial;

        // Si está en trial, calcular días restantes
        if ($this->es_trial == 1 && $this->fecha_fin) {
            $fecha_fin_ts = strtotime($this->fecha_fin);
            $hoy_ts = time();
            $dias_restantes = ceil(($fecha_fin_ts - $hoy_ts) / 86400);
            $_SESSION['dias_trial_restantes'] = max(0, $dias_restantes);

            // Si el trial ya expiró
            if ($dias_restantes <= 0) {
                $this->mostrarPantallaTrialExpirado();
                exit();
            }
        }

        $stmt->close();
    }

    /**
     * Validar acceso a un módulo específico
     *
     * @param string $modulo_slug Slug del módulo (ej: 'ventas', 'rrhh')
     * @return bool True si tiene acceso, exit() si no
     */
    public function validarModulo($modulo_slug) {
        // Usar el stored procedure para validar
        $stmt = $this->conn->prepare("CALL sp_validar_acceso_modulo(?, ?, @tiene_acceso, @acceso_completo, @limite_registros, @mensaje)");

        if (!$stmt) {
            die("Error al preparar validación de módulo: " . $this->conn->error);
        }

        $stmt->bind_param("is", $this->empresa_id, $modulo_slug);
        $stmt->execute();
        $stmt->close();

        // Obtener resultados del procedure
        $result = $this->conn->query("SELECT @tiene_acceso as tiene_acceso, @acceso_completo as acceso_completo, @limite_registros as limite_registros, @mensaje as mensaje");
        $validacion = $result->fetch_assoc();

        // Registrar en sesión
        $_SESSION['modulo_actual'] = $modulo_slug;
        $_SESSION['modulo_acceso_completo'] = $validacion['acceso_completo'];
        $_SESSION['modulo_limite_registros'] = $validacion['limite_registros'];

        // Si no tiene acceso
        if ($validacion['tiene_acceso'] == 0) {
            // Registrar intento denegado
            $this->registrarAccesoDenegado($modulo_slug, $validacion['mensaje']);

            // Mostrar pantalla de upgrade
            $this->mostrarPantallaUpgradeRequerido($modulo_slug);
            exit();
        }

        return true;
    }

    /**
     * Validar límite de usuarios del plan
     */
    public function validarLimiteUsuarios() {
        $stmt = $this->conn->prepare("
            SELECT
                p.max_usuarios,
                (SELECT COUNT(*) FROM usuarios WHERE empresa_id = ? AND estado = 'activo') as usuarios_activos
            FROM suscripciones s
            INNER JOIN planes p ON s.plan_id = p.id
            WHERE s.empresa_id = ?
              AND s.estado IN ('trial', 'activa')
            LIMIT 1
        ");

        $stmt->bind_param("ii", $this->empresa_id, $this->empresa_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $datos = $result->fetch_assoc();
        $stmt->close();

        // NULL = ilimitado
        if ($datos['max_usuarios'] === null) {
            return true;
        }

        // Verificar si se alcanzó el límite
        if ($datos['usuarios_activos'] >= $datos['max_usuarios']) {
            return false;
        }

        return true;
    }

    /**
     * Registrar intento de acceso denegado en log
     */
    private function registrarAccesoDenegado($modulo_slug, $razon) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $stmt = $this->conn->prepare("
            INSERT INTO logs_acceso_denegado (usuario_id, empresa_id, modulo_slug, razon, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param("iissss",
            $this->usuario_id,
            $this->empresa_id,
            $modulo_slug,
            $razon,
            $ip,
            $user_agent
        );

        $stmt->execute();
        $stmt->close();
    }

    /**
     * Pantalla: Suscripción Expirada
     */
    private function mostrarPantallaSuscripcionExpirada() {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Suscripción Expirada - CONECTA ERP</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
            <style>
                body {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }
                .expired-box {
                    background: white;
                    padding: 60px 40px;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    text-align: center;
                    max-width: 600px;
                    width: 90%;
                }
                .expired-icon {
                    font-size: 100px;
                    color: #ff6b6b;
                    margin-bottom: 30px;
                    animation: pulse 2s infinite;
                }
                @keyframes pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                }
                h1 {
                    color: #2d3748;
                    margin-bottom: 20px;
                    font-size: 2rem;
                    font-weight: 700;
                }
                p {
                    color: #718096;
                    font-size: 1.1rem;
                    margin-bottom: 30px;
                    line-height: 1.6;
                }
                .btn-renovar {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    padding: 15px 40px;
                    font-size: 1.1rem;
                    font-weight: 600;
                    border-radius: 10px;
                    text-decoration: none;
                    display: inline-block;
                    transition: all 0.3s ease;
                }
                .btn-renovar:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
                    color: white;
                }
                .link-logout {
                    display: block;
                    margin-top: 30px;
                    color: #667eea;
                    text-decoration: none;
                    font-weight: 500;
                }
                .link-logout:hover {
                    text-decoration: underline;
                }
            </style>
        </head>
        <body>
            <div class="expired-box">
                <div class="expired-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h1>Suscripción Expirada</h1>
                <p>
                    Tu período de prueba ha finalizado o tu suscripción ha expirado.<br>
                    Para continuar usando CONECTA ERP, por favor renueva tu suscripción.
                </p>
                <a href="/index.php#planes" class="btn-renovar">
                    <i class="fas fa-credit-card"></i> Ver Planes y Renovar
                </a>
                <a href="/logout.php" class="link-logout">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Pantalla: Trial Expirado
     */
    private function mostrarPantallaTrialExpirado() {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Período de Prueba Finalizado - CONECTA ERP</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
            <style>
                body {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }
                .trial-box {
                    background: white;
                    padding: 60px 40px;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    text-align: center;
                    max-width: 600px;
                    width: 90%;
                }
                .trial-icon {
                    font-size: 100px;
                    color: #ffa500;
                    margin-bottom: 30px;
                    animation: swing 2s infinite;
                }
                @keyframes swing {
                    0%, 100% { transform: rotate(0deg); }
                    25% { transform: rotate(-10deg); }
                    75% { transform: rotate(10deg); }
                }
                h1 {
                    color: #2d3748;
                    margin-bottom: 20px;
                    font-size: 2rem;
                    font-weight: 700;
                }
                p {
                    color: #718096;
                    font-size: 1.1rem;
                    margin-bottom: 30px;
                    line-height: 1.6;
                }
                .btn-elegir {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    padding: 15px 40px;
                    font-size: 1.1rem;
                    font-weight: 600;
                    border-radius: 10px;
                    text-decoration: none;
                    display: inline-block;
                    transition: all 0.3s ease;
                }
                .btn-elegir:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
                    color: white;
                }
                .highlight {
                    background: #fff3cd;
                    padding: 20px;
                    border-radius: 10px;
                    margin-bottom: 30px;
                }
                .highlight strong {
                    color: #856404;
                    font-size: 1.2rem;
                }
                .link-logout {
                    display: block;
                    margin-top: 30px;
                    color: #667eea;
                    text-decoration: none;
                    font-weight: 500;
                }
            </style>
        </head>
        <body>
            <div class="trial-box">
                <div class="trial-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h1>Período de Prueba Finalizado</h1>
                <p>
                    Tu período de prueba de 14 días ha finalizado.<br>
                    ¡Gracias por probar CONECTA ERP!
                </p>
                <div class="highlight">
                    <strong>¿Te gustó CONECTA ERP?</strong><br>
                    Elige un plan para continuar disfrutando de todas las funcionalidades.
                </div>
                <a href="/index.php#planes" class="btn-elegir">
                    <i class="fas fa-rocket"></i> Elegir Plan
                </a>
                <a href="/logout.php" class="link-logout">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Pantalla: Upgrade Requerido
     */
    private function mostrarPantallaUpgradeRequerido($modulo_slug) {
        // Obtener nombre del módulo
        $stmt = $this->conn->prepare("SELECT nombre FROM modulos WHERE slug = ? LIMIT 1");
        $stmt->bind_param("s", $modulo_slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $modulo_nombre = $result->num_rows > 0 ? $result->fetch_assoc()['nombre'] : $modulo_slug;
        $stmt->close();

        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Upgrade Requerido - CONECTA ERP</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
            <style>
                body {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }
                .upgrade-box {
                    background: white;
                    padding: 60px 40px;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    text-align: center;
                    max-width: 600px;
                    width: 90%;
                }
                .upgrade-icon {
                    font-size: 100px;
                    color: #667eea;
                    margin-bottom: 30px;
                    animation: bounce 2s infinite;
                }
                @keyframes bounce {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-20px); }
                }
                h1 {
                    color: #2d3748;
                    margin-bottom: 20px;
                    font-size: 2rem;
                    font-weight: 700;
                }
                p {
                    color: #718096;
                    font-size: 1.1rem;
                    margin-bottom: 30px;
                    line-height: 1.6;
                }
                .module-info {
                    background: #f0f4ff;
                    border-left: 4px solid #667eea;
                    padding: 20px;
                    border-radius: 10px;
                    margin-bottom: 30px;
                    text-align: left;
                }
                .module-info strong {
                    color: #667eea;
                }
                .btn-upgrade {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    padding: 15px 40px;
                    font-size: 1.1rem;
                    font-weight: 600;
                    border-radius: 10px;
                    text-decoration: none;
                    display: inline-block;
                    transition: all 0.3s ease;
                }
                .btn-upgrade:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
                    color: white;
                }
                .link-back {
                    display: block;
                    margin-top: 30px;
                    color: #667eea;
                    text-decoration: none;
                    font-weight: 500;
                }
            </style>
        </head>
        <body>
            <div class="upgrade-box">
                <div class="upgrade-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h1>Upgrade Requerido</h1>
                <p>
                    El módulo que intentas acceder no está incluido en tu plan actual.
                </p>
                <div class="module-info">
                    <p style="margin: 0;"><strong>Módulo solicitado:</strong> <?php echo htmlspecialchars($modulo_nombre); ?></p>
                    <p style="margin: 10px 0 0 0;"><strong>Tu plan actual:</strong> <?php echo htmlspecialchars($this->plan_nombre); ?></p>
                </div>
                <a href="/index.php#planes" class="btn-upgrade">
                    <i class="fas fa-arrow-up"></i> Mejorar Plan
                </a>
                <a href="/user/dashboard_user.php" class="link-back">
                    <i class="fas fa-arrow-left"></i> Volver al Dashboard
                </a>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Redirigir al login
     */
    private function redirigirLogin($mensaje = '') {
        session_destroy();
        $url = '/index.php';
        if (!empty($mensaje)) {
            $url .= '?error=' . urlencode($mensaje);
        }
        header("Location: $url");
        exit();
    }
}

/**
 * ===============================================
 * FUNCIÓN HELPER GLOBAL
 * ===============================================
 * Usa esta función en cualquier módulo para validar acceso
 *
 * EJEMPLO:
 * require_once '../../includes/middleware_acceso.php';
 * requiereModulo('ventas');
 */
function requiereModulo($modulo_slug) {
    global $conn;

    if (!isset($conn) || !$conn) {
        die("Error: Conexión a base de datos no disponible");
    }

    $middleware = new MiddlewareAcceso($conn);
    return $middleware->validarModulo($modulo_slug);
}

/**
 * Verificar límite de usuarios antes de crear uno nuevo
 */
function validarLimiteUsuarios() {
    global $conn;
    $middleware = new MiddlewareAcceso($conn);
    return $middleware->validarLimiteUsuarios();
}
?>
