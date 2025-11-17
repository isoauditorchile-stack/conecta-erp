# 🔐 ARQUITECTURA DE CONTROL DE ACCESO POR MÓDULOS Y PLANES

## CONECTA ERP - Sistema Multi-Tenant con Control de Suscripciones

---

## 📋 ÍNDICE

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Arquitectura de Planes](#arquitectura-de-planes)
3. [Estructura de Tablas](#estructura-de-tablas)
4. [Flujo de Control de Acceso](#flujo-de-control-de-acceso)
5. [Implementación Técnica](#implementación-técnica)
6. [Casos de Uso](#casos-de-uso)
7. [API de Validación](#api-de-validación)
8. [Interfaz de Usuario](#interfaz-de-usuario)
9. [Seguridad](#seguridad)
10. [Migración y Upgrades](#migración-y-upgrades)

---

## 1. RESUMEN EJECUTIVO

CONECTA ERP utiliza un **sistema de control de acceso basado en suscripciones** que permite:

✅ **Control granular por módulo:** Cada plan incluye módulos específicos
✅ **Límites de usuarios:** Restricción del número de usuarios activos por empresa
✅ **Período de prueba:** 14 días gratis con acceso completo
✅ **Activación/desactivación automática:** Según estado de pago
✅ **Multi-empresa:** Cada empresa tiene su propio plan independiente
✅ **Upgrade/Downgrade:** Cambio de plan sin pérdida de datos

---

## 2. ARQUITECTURA DE PLANES

### 2.1 Planes Disponibles

El sistema incluye **4 planes predefinidos** en la tabla `planes`:

| ID | Plan | Precio Mensual | Usuarios | Empresas | Módulos Incluidos | Target |
|----|------|----------------|----------|----------|-------------------|--------|
| 1 | **Básico** | $49,990 | 5 | 1 | 6 módulos básicos | Pequeñas empresas |
| 2 | **Profesional** | $99,990 | 25 | 3 | 12 módulos completos | Empresas en crecimiento |
| 3 | **Empresarial** | $199,990 | Ilimitado | Ilimitado | 17 módulos (107 submódulos) | Grandes empresas |
| 4 | **Personalizado** | A cotizar | Personalizado | Personalizado | A medida | Corporaciones |

### 2.2 Módulos por Plan

#### Plan Básico (6 módulos)
1. ✅ Administración
2. ✅ Ventas (limitado)
3. ✅ Compras (limitado)
4. ✅ Inventario
5. ✅ Contabilidad (básica)
6. ✅ CRM (básico)

#### Plan Profesional (12 módulos)
Todos del Básico + :
7. ✅ Recursos Humanos
8. ✅ Finanzas
9. ✅ Producción
10. ✅ Logística
11. ✅ Marketing
12. ✅ Reportes Avanzados

#### Plan Empresarial (17 módulos completos - 107 submódulos)
Todos del Profesional + :
13. ✅ Business Intelligence
14. ✅ Configuración Avanzada
15. ✅ E-Commerce
16. ✅ Proyectos
17. ✅ Calidad y Mantenimiento

---

## 3. ESTRUCTURA DE TABLAS

### 3.1 Tabla `planes`

```sql
CREATE TABLE `planes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT NULL,
  `precio_mensual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `precio_anual` DECIMAL(10,2) NULL DEFAULT NULL,
  `max_usuarios` INT NULL DEFAULT NULL COMMENT 'NULL = ilimitado',
  `max_empresas` INT NULL DEFAULT NULL COMMENT 'NULL = ilimitado',
  `dias_trial` INT NOT NULL DEFAULT 14,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `caracteristicas` JSON NULL COMMENT 'Array de características incluidas',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Ejemplo de JSON `caracteristicas`:**
```json
{
  "modulos": [1, 2, 3, 4, 5, 6],
  "storage_gb": 50,
  "api_calls_mes": 10000,
  "soporte": "email",
  "reportes_personalizados": false,
  "integraciones_externas": true
}
```

### 3.2 Tabla `plan_modulos` (Relación Muchos a Muchos)

```sql
CREATE TABLE `plan_modulos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NOT NULL,
  `modulo_id` INT UNSIGNED NOT NULL,
  `acceso_completo` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Completo, 0=Limitado',
  `limite_registros` INT NULL DEFAULT NULL COMMENT 'NULL = sin límite',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plan_modulo` (`plan_id`, `modulo_id`),
  KEY `idx_plan` (`plan_id`),
  KEY `idx_modulo` (`modulo_id`),
  CONSTRAINT `fk_plan_modulos_plan` FOREIGN KEY (`plan_id`)
    REFERENCES `planes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plan_modulos_modulo` FOREIGN KEY (`modulo_id`)
    REFERENCES `modulos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.3 Tabla `modulos` (Catálogo de Módulos)

```sql
CREATE TABLE `modulos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL COMMENT 'URL slug para routing',
  `descripcion` TEXT NULL,
  `icono` VARCHAR(50) NULL DEFAULT 'fas fa-cube',
  `orden` INT NOT NULL DEFAULT 0,
  `parent_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Para submódulos',
  `requiere_licencia` TINYINT(1) NOT NULL DEFAULT 1,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Inserción de Módulos:**
```sql
-- Módulos principales
INSERT INTO `modulos` (`id`, `nombre`, `slug`, `icono`, `orden`) VALUES
(1, 'Administración', 'administracion', 'fas fa-cog', 1),
(2, 'Ventas', 'ventas', 'fas fa-shopping-cart', 2),
(3, 'Compras', 'compras', 'fas fa-shopping-bag', 3),
(4, 'Inventario', 'inventario', 'fas fa-boxes', 4),
(5, 'Contabilidad', 'contabilidad', 'fas fa-calculator', 5),
(6, 'Recursos Humanos', 'rrhh', 'fas fa-users', 6),
(7, 'CRM', 'crm', 'fas fa-handshake', 7),
(8, 'Producción', 'produccion', 'fas fa-industry', 8),
(9, 'Finanzas', 'finanzas', 'fas fa-chart-line', 9),
(10, 'Logística', 'logistica', 'fas fa-truck', 10),
(11, 'Marketing', 'marketing', 'fas fa-bullhorn', 11),
(12, 'Business Intelligence', 'bi', 'fas fa-chart-pie', 12),
(13, 'Configuración Avanzada', 'config', 'fas fa-sliders-h', 13),
(14, 'E-Commerce', 'ecommerce', 'fas fa-store', 14),
(15, 'Proyectos', 'proyectos', 'fas fa-project-diagram', 15),
(16, 'Calidad', 'calidad', 'fas fa-award', 16),
(17, 'Mantenimiento', 'mantenimiento', 'fas fa-tools', 17);

-- Submódulos de Ventas (ejemplo)
INSERT INTO `modulos` (`nombre`, `slug`, `parent_id`, `orden`) VALUES
('Cotizaciones', 'ventas-cotizaciones', 2, 1),
('Pedidos', 'ventas-pedidos', 2, 2),
('Facturas', 'ventas-facturas', 2, 3),
('Notas de Crédito', 'ventas-notas-credito', 2, 4),
('Gestión de Clientes', 'ventas-clientes', 2, 5),
('Listas de Precios', 'ventas-listas-precios', 2, 6),
('Descuentos y Promociones', 'ventas-promociones', 2, 7),
('Comisiones de Vendedores', 'ventas-comisiones', 2, 8),
('Metas de Ventas', 'ventas-metas', 2, 9),
('Análisis de Ventas', 'ventas-analisis', 2, 10),
('DTE (Documentos Electrónicos)', 'ventas-dte', 2, 11);
```

### 3.4 Tabla `suscripciones`

```sql
CREATE TABLE `suscripciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `empresa_id` INT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `estado` ENUM('trial', 'activa', 'suspendida', 'cancelada', 'expirada') NOT NULL DEFAULT 'trial',
  `fecha_inicio` DATETIME NOT NULL,
  `fecha_fin` DATETIME NULL DEFAULT NULL,
  `es_trial` TINYINT(1) NOT NULL DEFAULT 0,
  `auto_renovar` TINYINT(1) NOT NULL DEFAULT 1,
  `monto_mensual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `metodo_pago` VARCHAR(50) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_plan` (`plan_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_suscripciones_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_suscripciones_empresa` FOREIGN KEY (`empresa_id`)
    REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_suscripciones_plan` FOREIGN KEY (`plan_id`)
    REFERENCES `planes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.5 Tabla `pagos` (Historial de Pagos)

```sql
CREATE TABLE `pagos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `suscripcion_id` INT UNSIGNED NOT NULL,
  `empresa_id` INT UNSIGNED NOT NULL,
  `monto` DECIMAL(10,2) NOT NULL,
  `moneda` VARCHAR(3) NOT NULL DEFAULT 'CLP',
  `metodo_pago` VARCHAR(50) NOT NULL COMMENT 'Transbank, PayPal, MercadoPago, etc',
  `estado` ENUM('pendiente', 'completado', 'fallido', 'reembolsado') NOT NULL DEFAULT 'pendiente',
  `transaccion_id` VARCHAR(255) NULL COMMENT 'ID externo del procesador de pago',
  `fecha_pago` DATETIME NULL DEFAULT NULL,
  `periodo_desde` DATE NOT NULL,
  `periodo_hasta` DATE NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_suscripcion` (`suscripcion_id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_pago` (`fecha_pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 4. FLUJO DE CONTROL DE ACCESO

### 4.1 Diagrama de Flujo

```
┌─────────────────────┐
│ Usuario Inicia      │
│ Sesión              │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Cargar Datos de     │
│ Sesión:             │
│ - empresa_id        │
│ - plan_id           │
│ - suscripcion_id    │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Usuario Intenta     │
│ Acceder a Módulo    │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐      NO      ┌─────────────────────┐
│ ¿Suscripción        │─────────────>│ BLOQUEAR ACCESO     │
│ Activa?             │              │ Mostrar: "Suscripción│
└──────────┬──────────┘              │ Expirada"           │
           │ SÍ                       └─────────────────────┘
           ▼
┌─────────────────────┐      NO      ┌─────────────────────┐
│ ¿Plan incluye       │─────────────>│ BLOQUEAR ACCESO     │
│ este Módulo?        │              │ Mostrar: "Upgrade   │
└──────────┬──────────┘              │ Requerido"          │
           │ SÍ                       └─────────────────────┘
           ▼
┌─────────────────────┐      NO      ┌─────────────────────┐
│ ¿Usuario tiene      │─────────────>│ BLOQUEAR ACCESO     │
│ Permiso de Rol?     │              │ Mostrar: "Sin       │
└──────────┬──────────┘              │ Permisos"           │
           │ SÍ                       └─────────────────────┘
           ▼
┌─────────────────────┐
│ ✅ ACCESO PERMITIDO │
│ Cargar Módulo       │
└─────────────────────┘
```

### 4.2 Validaciones en Cada Capa

#### Capa 1: Suscripción Activa
```php
function validarSuscripcionActiva($empresa_id) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT s.*, p.nombre as plan_nombre
        FROM suscripciones s
        INNER JOIN planes p ON s.plan_id = p.id
        WHERE s.empresa_id = ?
          AND s.estado IN ('trial', 'activa')
          AND (s.fecha_fin IS NULL OR s.fecha_fin >= NOW())
        ORDER BY s.id DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}
```

#### Capa 2: Módulo Incluido en Plan
```php
function validarAccesoModulo($plan_id, $modulo_slug) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT pm.*, m.nombre as modulo_nombre
        FROM plan_modulos pm
        INNER JOIN modulos m ON pm.modulo_id = m.id
        WHERE pm.plan_id = ?
          AND m.slug = ?
          AND m.activo = 1
        LIMIT 1
    ");
    $stmt->bind_param("is", $plan_id, $modulo_slug);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}
```

#### Capa 3: Permisos de Rol
```php
function validarPermisoUsuario($usuario_id, $modulo_slug, $accion = 'ver') {
    global $conn;

    $stmt = $conn->prepare("
        SELECT p.*
        FROM usuario_roles ur
        INNER JOIN rol_permisos rp ON ur.rol_id = rp.rol_id
        INNER JOIN permisos p ON rp.permiso_id = p.id
        WHERE ur.usuario_id = ?
          AND p.modulo = ?
          AND p.accion = ?
        LIMIT 1
    ");
    $stmt->bind_param("iss", $usuario_id, $modulo_slug, $accion);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}
```

---

## 5. IMPLEMENTACIÓN TÉCNICA

### 5.1 Middleware de Autenticación

Crear archivo: `/includes/middleware_acceso.php`

```php
<?php
/**
 * MIDDLEWARE DE CONTROL DE ACCESO
 * Valida acceso a módulos según plan y permisos
 */

session_start();
require_once __DIR__ . '/config.php';

class MiddlewareAcceso {

    private $conn;
    private $empresa_id;
    private $usuario_id;
    private $plan_id;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->empresa_id = $_SESSION['empresa_id'] ?? null;
        $this->usuario_id = $_SESSION['user_id'] ?? null;

        if (!$this->empresa_id || !$this->usuario_id) {
            $this->redirigirLogin('Sesión expirada');
        }

        $this->cargarPlan();
    }

    /**
     * Cargar información del plan activo
     */
    private function cargarPlan() {
        $stmt = $this->conn->prepare("
            SELECT s.plan_id, s.estado, s.fecha_fin, s.es_trial,
                   p.nombre as plan_nombre, p.max_usuarios
            FROM suscripciones s
            INNER JOIN planes p ON s.plan_id = p.id
            WHERE s.empresa_id = ?
              AND s.estado IN ('trial', 'activa')
            ORDER BY s.id DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $this->empresa_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $this->mostrarPantallaSuscripcionExpirada();
            exit();
        }

        $suscripcion = $result->fetch_assoc();
        $this->plan_id = $suscripcion['plan_id'];

        // Guardar en sesión
        $_SESSION['plan_id'] = $this->plan_id;
        $_SESSION['plan_nombre'] = $suscripcion['plan_nombre'];
        $_SESSION['suscripcion_estado'] = $suscripcion['estado'];
        $_SESSION['es_trial'] = $suscripcion['es_trial'];

        // Verificar si está en trial y calcular días restantes
        if ($suscripcion['es_trial'] == 1) {
            $fecha_fin = strtotime($suscripcion['fecha_fin']);
            $hoy = time();
            $dias_restantes = ceil(($fecha_fin - $hoy) / 86400);
            $_SESSION['dias_trial_restantes'] = max(0, $dias_restantes);

            if ($dias_restantes <= 0) {
                $this->mostrarPantallaTrialExpirado();
                exit();
            }
        }
    }

    /**
     * Validar acceso a un módulo específico
     */
    public function validarModulo($modulo_slug) {
        // 1. Verificar si el módulo existe
        $stmt = $this->conn->prepare("
            SELECT id, nombre, requiere_licencia
            FROM modulos
            WHERE slug = ? AND activo = 1
        ");
        $stmt->bind_param("s", $modulo_slug);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $this->mostrarError404('Módulo no encontrado');
            exit();
        }

        $modulo = $result->fetch_assoc();

        // 2. Si el módulo no requiere licencia (ej: Administración básica), permitir
        if ($modulo['requiere_licencia'] == 0) {
            return true;
        }

        // 3. Verificar si el plan incluye este módulo
        $stmt = $this->conn->prepare("
            SELECT pm.acceso_completo, pm.limite_registros
            FROM plan_modulos pm
            INNER JOIN modulos m ON pm.modulo_id = m.id
            WHERE pm.plan_id = ?
              AND m.slug = ?
        ");
        $stmt->bind_param("is", $this->plan_id, $modulo_slug);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $this->mostrarPantallaUpgradeRequerido($modulo['nombre']);
            exit();
        }

        $acceso = $result->fetch_assoc();

        // 4. Guardar información de acceso en sesión
        $_SESSION['modulo_acceso_completo'] = $acceso['acceso_completo'];
        $_SESSION['modulo_limite_registros'] = $acceso['limite_registros'];

        return true;
    }

    /**
     * Validar límite de usuarios del plan
     */
    public function validarLimiteUsuarios() {
        $stmt = $this->conn->prepare("
            SELECT p.max_usuarios,
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

        // Si max_usuarios es NULL, es ilimitado
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
     * Mostrar pantalla de suscripción expirada
     */
    private function mostrarPantallaSuscripcionExpirada() {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
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
                }
                .expired-box {
                    background: white;
                    padding: 60px;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    text-align: center;
                    max-width: 600px;
                }
                .expired-icon {
                    font-size: 100px;
                    color: #ff6b6b;
                    margin-bottom: 30px;
                }
            </style>
        </head>
        <body>
            <div class="expired-box">
                <div class="expired-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h1 style="color: #2d3748; margin-bottom: 20px;">Suscripción Expirada</h1>
                <p style="color: #718096; font-size: 18px; margin-bottom: 30px;">
                    Tu período de prueba ha finalizado o tu suscripción ha expirado.
                </p>
                <a href="/planes.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-credit-card"></i> Ver Planes y Renovar
                </a>
                <br><br>
                <a href="/logout.php" style="color: #667eea;">Cerrar Sesión</a>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Mostrar pantalla de trial expirado
     */
    private function mostrarPantallaTrialExpirado() {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
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
                }
                .trial-box {
                    background: white;
                    padding: 60px;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    text-align: center;
                    max-width: 600px;
                }
                .trial-icon {
                    font-size: 100px;
                    color: #ffa500;
                    margin-bottom: 30px;
                }
            </style>
        </head>
        <body>
            <div class="trial-box">
                <div class="trial-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h1 style="color: #2d3748; margin-bottom: 20px;">Período de Prueba Finalizado</h1>
                <p style="color: #718096; font-size: 18px; margin-bottom: 30px;">
                    Tu período de prueba de 14 días ha finalizado. ¡Elige un plan para continuar usando CONECTA ERP!
                </p>
                <a href="/planes.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-rocket"></i> Elegir Plan
                </a>
                <br><br>
                <a href="/logout.php" style="color: #667eea;">Cerrar Sesión</a>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Mostrar pantalla de upgrade requerido
     */
    private function mostrarPantallaUpgradeRequerido($modulo_nombre) {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
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
                }
                .upgrade-box {
                    background: white;
                    padding: 60px;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    text-align: center;
                    max-width: 600px;
                }
                .upgrade-icon {
                    font-size: 100px;
                    color: #667eea;
                    margin-bottom: 30px;
                }
            </style>
        </head>
        <body>
            <div class="upgrade-box">
                <div class="upgrade-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h1 style="color: #2d3748; margin-bottom: 20px;">Upgrade Requerido</h1>
                <p style="color: #718096; font-size: 18px; margin-bottom: 15px;">
                    El módulo <strong><?php echo htmlspecialchars($modulo_nombre); ?></strong> no está incluido en tu plan actual.
                </p>
                <p style="color: #718096; margin-bottom: 30px;">
                    Tu plan: <strong><?php echo htmlspecialchars($_SESSION['plan_nombre'] ?? 'Desconocido'); ?></strong>
                </p>
                <a href="/planes.php?upgrade=1" class="btn btn-primary btn-lg">
                    <i class="fas fa-arrow-up"></i> Mejorar Plan
                </a>
                <br><br>
                <a href="/user/dashboard_user.php" style="color: #667eea;">
                    <i class="fas fa-arrow-left"></i> Volver al Dashboard
                </a>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Mostrar error 404
     */
    private function mostrarError404($mensaje) {
        http_response_code(404);
        echo "Error 404: $mensaje";
    }

    /**
     * Redirigir al login
     */
    private function redirigirLogin($mensaje = '') {
        session_destroy();
        header("Location: /index.php?error=" . urlencode($mensaje));
        exit();
    }
}

/**
 * Función helper para usar en cualquier página
 */
function requiereModulo($modulo_slug) {
    global $conn;
    $middleware = new MiddlewareAcceso($conn);
    return $middleware->validarModulo($modulo_slug);
}
?>
```

### 5.2 Uso en Páginas de Módulos

Ejemplo: `/modulos/ventas/index.php`

```php
<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/middleware_acceso.php';

// Validar acceso al módulo de ventas
requiereModulo('ventas');

// Si llegó aquí, el usuario tiene acceso
$empresa_id = $_SESSION['empresa_id'];
$usuario_id = $_SESSION['user_id'];

// Verificar si tiene acceso completo o limitado
$acceso_completo = $_SESSION['modulo_acceso_completo'] ?? 0;
$limite_registros = $_SESSION['modulo_limite_registros'] ?? null;

if (!$acceso_completo) {
    echo '<div class="alert alert-warning">
            <i class="fas fa-info-circle"></i> Estás usando la versión limitada de este módulo.
            <a href="/planes.php">Upgrade para desbloquear todas las funciones</a>
          </div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Ventas - CONECTA ERP</title>
</head>
<body>
    <h1>Módulo de Ventas</h1>
    <!-- Contenido del módulo -->
</body>
</html>
```

---

## 6. CASOS DE USO

### Caso 1: Registro de Nuevo Cliente

**Flujo:**
1. Cliente se registra en `index.php`
2. Sistema crea:
   - Empresa nueva
   - Usuario administrador
   - Suscripción en estado `trial` (14 días)
   - Plan asignado: `Básico` (plan_id = 1)
3. Cliente puede acceder a 6 módulos básicos
4. Después de 14 días, debe elegir plan de pago

**SQL de Ejemplo:**
```sql
-- Al registrarse
INSERT INTO suscripciones (usuario_id, empresa_id, plan_id, estado, fecha_inicio, fecha_fin, es_trial)
VALUES (123, 456, 1, 'trial', NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), 1);
```

### Caso 2: Upgrade de Plan

**Flujo:**
1. Cliente en Plan Básico quiere acceder a módulo RRHH
2. Sistema muestra pantalla "Upgrade Requerido"
3. Cliente hace clic en "Mejorar Plan"
4. Selecciona Plan Profesional
5. Procesa pago
6. Sistema actualiza suscripción

**SQL de Ejemplo:**
```sql
-- Actualizar suscripción
UPDATE suscripciones
SET plan_id = 2,  -- Plan Profesional
    estado = 'activa',
    es_trial = 0,
    monto_mensual = 99990.00,
    fecha_inicio = NOW(),
    fecha_fin = DATE_ADD(NOW(), INTERVAL 1 MONTH)
WHERE id = 789 AND empresa_id = 456;

-- Registrar pago
INSERT INTO pagos (suscripcion_id, empresa_id, monto, metodo_pago, estado, fecha_pago, periodo_desde, periodo_hasta)
VALUES (789, 456, 99990.00, 'Transbank', 'completado', NOW(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH));
```

### Caso 3: Expiración y Renovación

**Evento Automático:**
```sql
-- Event para marcar suscripciones expiradas
CREATE EVENT ev_verificar_suscripciones_expiradas
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    UPDATE suscripciones
    SET estado = 'expirada'
    WHERE estado = 'activa'
      AND fecha_fin < NOW()
      AND es_trial = 0;

    UPDATE suscripciones
    SET estado = 'expirada'
    WHERE estado = 'trial'
      AND fecha_fin < NOW();
END;
```

---

## 7. API DE VALIDACIÓN

Crear archivo: `/api/validar_acceso.php`

```php
<?php
/**
 * API REST para validar acceso a módulos
 * Útil para AJAX y Single Page Applications
 */

header('Content-Type: application/json');
session_start();
require_once '../includes/config.php';

$response = [
    'success' => false,
    'tiene_acceso' => false,
    'mensaje' => '',
    'datos' => []
];

// Verificar sesión
if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    $response['mensaje'] = 'Sesión no válida';
    echo json_encode($response);
    exit();
}

// Obtener parámetros
$modulo_slug = $_GET['modulo'] ?? '';
$empresa_id = $_SESSION['empresa_id'];

if (empty($modulo_slug)) {
    $response['mensaje'] = 'Parámetro modulo requerido';
    echo json_encode($response);
    exit();
}

// Obtener plan activo
$stmt = $conn->prepare("
    SELECT s.plan_id, p.nombre as plan_nombre, s.estado, s.es_trial, s.fecha_fin
    FROM suscripciones s
    INNER JOIN planes p ON s.plan_id = p.id
    WHERE s.empresa_id = ?
      AND s.estado IN ('trial', 'activa')
    ORDER BY s.id DESC
    LIMIT 1
");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['mensaje'] = 'No hay suscripción activa';
    $response['datos']['accion_requerida'] = 'renovar_suscripcion';
    echo json_encode($response);
    exit();
}

$suscripcion = $result->fetch_assoc();
$plan_id = $suscripcion['plan_id'];

// Verificar acceso al módulo
$stmt = $conn->prepare("
    SELECT pm.acceso_completo, pm.limite_registros, m.nombre as modulo_nombre
    FROM plan_modulos pm
    INNER JOIN modulos m ON pm.modulo_id = m.id
    WHERE pm.plan_id = ?
      AND m.slug = ?
      AND m.activo = 1
");
$stmt->bind_param("is", $plan_id, $modulo_slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['mensaje'] = 'Módulo no incluido en tu plan';
    $response['datos']['plan_actual'] = $suscripcion['plan_nombre'];
    $response['datos']['accion_requerida'] = 'upgrade_plan';
    echo json_encode($response);
    exit();
}

$acceso = $result->fetch_assoc();

// Acceso permitido
$response['success'] = true;
$response['tiene_acceso'] = true;
$response['mensaje'] = 'Acceso permitido';
$response['datos'] = [
    'plan' => $suscripcion['plan_nombre'],
    'modulo' => $acceso['modulo_nombre'],
    'acceso_completo' => $acceso['acceso_completo'] == 1,
    'limite_registros' => $acceso['limite_registros'],
    'es_trial' => $suscripcion['es_trial'] == 1,
    'dias_restantes' => null
];

if ($suscripcion['es_trial'] == 1) {
    $fecha_fin = strtotime($suscripcion['fecha_fin']);
    $hoy = time();
    $dias_restantes = ceil(($fecha_fin - $hoy) / 86400);
    $response['datos']['dias_restantes'] = max(0, $dias_restantes);
}

echo json_encode($response);
?>
```

**Uso desde JavaScript:**
```javascript
// Validar acceso antes de cargar módulo
async function validarAccesoModulo(moduloSlug) {
    const response = await fetch(`/api/validar_acceso.php?modulo=${moduloSlug}`);
    const data = await response.json();

    if (!data.tiene_acceso) {
        if (data.datos.accion_requerida === 'upgrade_plan') {
            mostrarModalUpgrade(data.datos.plan_actual);
        } else if (data.datos.accion_requerida === 'renovar_suscripcion') {
            mostrarModalRenovacion();
        }
        return false;
    }

    // Mostrar advertencia si es trial
    if (data.datos.es_trial && data.datos.dias_restantes <= 3) {
        mostrarAlertaTrial(data.datos.dias_restantes);
    }

    return true;
}
```

---

## 8. INTERFAZ DE USUARIO

### 8.1 Badge de Plan en Header

Agregar en `user/dashboard_user.php`:

```php
<!-- Header con información de plan -->
<div class="header-plan-info">
    <span class="badge bg-<?php echo $_SESSION['es_trial'] ? 'warning' : 'success'; ?>">
        <?php echo htmlspecialchars($_SESSION['plan_nombre'] ?? 'Sin Plan'); ?>
        <?php if ($_SESSION['es_trial'] ?? false): ?>
            - Trial (<?php echo $_SESSION['dias_trial_restantes'] ?? 0; ?> días restantes)
        <?php endif; ?>
    </span>
</div>
```

### 8.2 Sidebar con Módulos Bloqueados

Modificar sidebar para mostrar módulos con/sin acceso:

```php
<?php
// Obtener módulos del plan actual
$plan_id = $_SESSION['plan_id'];
$stmt = $conn->prepare("
    SELECT m.id, m.nombre, m.slug, m.icono, m.orden
    FROM modulos m
    INNER JOIN plan_modulos pm ON m.id = pm.modulo_id
    WHERE pm.plan_id = ? AND m.parent_id IS NULL AND m.activo = 1
    ORDER BY m.orden
");
$stmt->bind_param("i", $plan_id);
$stmt->execute();
$modulos_acceso = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener todos los módulos
$modulos_todos = $conn->query("SELECT * FROM modulos WHERE parent_id IS NULL AND activo = 1 ORDER BY orden")->fetch_all(MYSQLI_ASSOC);
?>

<div class="sidebar">
    <?php foreach ($modulos_todos as $modulo): ?>
        <?php
        $tiene_acceso = in_array($modulo['id'], array_column($modulos_acceso, 'id'));
        ?>
        <a href="<?php echo $tiene_acceso ? '/modulos/' . $modulo['slug'] : '#'; ?>"
           class="sidebar-item <?php echo !$tiene_acceso ? 'bloqueado' : ''; ?>"
           <?php if (!$tiene_acceso): ?>
               onclick="event.preventDefault(); mostrarModalUpgrade('<?php echo $modulo['nombre']; ?>');"
           <?php endif; ?>>
            <i class="<?php echo $modulo['icono']; ?>"></i>
            <span><?php echo $modulo['nombre']; ?></span>
            <?php if (!$tiene_acceso): ?>
                <i class="fas fa-lock float-end"></i>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>

<style>
.sidebar-item.bloqueado {
    opacity: 0.5;
    cursor: not-allowed;
}
.sidebar-item.bloqueado:hover {
    background: #f8f9fa;
}
</style>
```

---

## 9. SEGURIDAD

### 9.1 Prevención de Bypass

1. **NUNCA confiar solo en JavaScript**: Validar siempre en servidor
2. **Verificar en cada request**: No asumir que la sesión es válida
3. **Logs de auditoría**: Registrar intentos de acceso denegado

```php
// Registrar intento de acceso denegado
function logAccesoDenegado($usuario_id, $modulo_slug, $razon) {
    global $conn;
    $stmt = $conn->prepare("
        INSERT INTO logs_acceso_denegado (usuario_id, modulo_slug, razon, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?)
    ");
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $stmt->bind_param("issss", $usuario_id, $modulo_slug, $razon, $ip, $user_agent);
    $stmt->execute();
}
```

### 9.2 Rate Limiting

Prevenir abuso de intentos de acceso:

```php
function verificarRateLimit($usuario_id, $limite = 100, $ventana = 3600) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT COUNT(*) as intentos
        FROM logs_acceso_denegado
        WHERE usuario_id = ?
          AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->bind_param("ii", $usuario_id, $ventana);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result['intentos'] >= $limite) {
        // Suspender cuenta temporalmente
        return false;
    }

    return true;
}
```

---

## 10. MIGRACIÓN Y UPGRADES

### 10.1 Script de Migración de Plan

```php
<?php
/**
 * Migrar empresa de un plan a otro
 * /admin/migrar_plan.php
 */

function migrarPlan($empresa_id, $nuevo_plan_id, $razon = 'upgrade') {
    global $conn;

    $conn->begin_transaction();

    try {
        // 1. Obtener suscripción actual
        $stmt = $conn->prepare("
            SELECT * FROM suscripciones
            WHERE empresa_id = ? AND estado IN ('trial', 'activa')
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->bind_param("i", $empresa_id);
        $stmt->execute();
        $suscripcion_actual = $stmt->get_result()->fetch_assoc();

        if (!$suscripcion_actual) {
            throw new Exception("No hay suscripción activa");
        }

        // 2. Marcar suscripción actual como cancelada
        $stmt = $conn->prepare("UPDATE suscripciones SET estado = 'cancelada' WHERE id = ?");
        $stmt->bind_param("i", $suscripcion_actual['id']);
        $stmt->execute();

        // 3. Crear nueva suscripción
        $stmt = $conn->prepare("
            INSERT INTO suscripciones (usuario_id, empresa_id, plan_id, estado, fecha_inicio, fecha_fin, es_trial, monto_mensual)
            SELECT ?, ?, ?, 'activa', NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), 0, precio_mensual
            FROM planes WHERE id = ?
        ");
        $stmt->bind_param("iiii",
            $suscripcion_actual['usuario_id'],
            $empresa_id,
            $nuevo_plan_id,
            $nuevo_plan_id
        );
        $stmt->execute();

        // 4. Registrar en log de auditoría
        $stmt = $conn->prepare("
            INSERT INTO logs_cambio_plan (empresa_id, plan_anterior_id, plan_nuevo_id, razon, usuario_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiisi",
            $empresa_id,
            $suscripcion_actual['plan_id'],
            $nuevo_plan_id,
            $razon,
            $_SESSION['user_id']
        );
        $stmt->execute();

        $conn->commit();
        return true;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error en migrarPlan: " . $e->getMessage());
        return false;
    }
}
?>
```

---

## CONCLUSIÓN

Este sistema de control de acceso por módulos y planes permite:

✅ **Monetización flexible**: 4 niveles de planes adaptables
✅ **Escalabilidad**: Fácil agregar nuevos módulos y planes
✅ **Seguridad robusta**: Validación en múltiples capas
✅ **UX optimizada**: Mensajes claros de upgrade requerido
✅ **Auditoría completa**: Trazabilidad de todos los accesos

**Próximos pasos de implementación:**

1. Ejecutar SQL de creación de tablas (`modulos`, `plan_modulos`, etc.)
2. Insertar datos de planes y módulos
3. Implementar `MiddlewareAcceso` en todos los módulos
4. Crear páginas de planes y checkout
5. Integrar pasarelas de pago (Transbank, MercadoPago)
6. Configurar eventos automáticos de expiración
7. Testing completo de todos los flujos

---

**Documentación creada por:** Sistema CONECTA ERP
**Fecha:** Noviembre 2025
**Versión:** 1.0
