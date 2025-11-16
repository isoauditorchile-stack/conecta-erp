# CÓMO IMPLEMENTAR RESTRICCIONES POR PLAN Y AISLAMIENTO MULTIEMPRESA

## 🔒 **RESTRICCIONES POR PLAN**

### **1. Validar antes de crear usuario**

```php
require_once 'includes/plan_restrictions.php';

// Al crear un nuevo usuario en la empresa
$plan_id = $_SESSION['plan_id']; // Plan del usuario actual
$empresa_id = $_SESSION['empresa_id'];

$validacion = puedeCrearUsuario($conn, $empresa_id, $plan_id);

if (!$validacion['permitido']) {
    // BLOQUEAR: Mostrar mensaje de error
    $error = $validacion['mensaje'];
    // "Has alcanzado el límite de 5 usuarios de tu plan. Actualiza tu plan para agregar más usuarios."
    exit();
}

// PERMITIR: Continuar con la creación del usuario
```

### **2. Validar antes de crear empresa**

```php
// Al crear nueva empresa (multiempresa)
$usuario_id = $_SESSION['user_id'];
$plan_id = $_SESSION['plan_id'];

$validacion = puedeCrearEmpresa($conn, $usuario_id, $plan_id);

if (!$validacion['permitido']) {
    // BLOQUEAR
    $error = $validacion['mensaje'];
    // "Has alcanzado el límite de 3 empresas de tu plan..."
    exit();
}

// PERMITIR
```

### **3. Validar acceso a módulos**

```php
// Al acceder a un módulo específico
$plan_id = $_SESSION['plan_id'];
$modulo_id = 7; // Ejemplo: Módulo de Inventario

$validacion = tieneAccesoModulo($conn, $plan_id, $modulo_id);

if (!$validacion['permitido']) {
    // BLOQUEAR: Redirigir o mostrar upgrade
    header("Location: /upgrade.php?modulo=$modulo_id");
    exit();
}

// PERMITIR: Mostrar el módulo
```

### **4. Mostrar límites actuales**

```php
// En el dashboard o perfil, mostrar límites
$limites = getLimitesUsuario($conn, $_SESSION['user_id']);

echo "Plan: {$limites['plan']}";
echo "Usuarios: {$limites['usuarios_actuales']} / {$limites['max_usuarios']}";
echo "Empresas: 1 / {$limites['max_empresas']}";
```

---

## 🏢 **AISLAMIENTO MULTIEMPRESA (MULTI-TENANCY)**

### **REGLA DE ORO:**
**CADA CONSULTA SQL DEBE FILTRAR POR `empresa_id`**

### **1. SELECT - Listar datos**

```php
// ❌ MAL - Muestra datos de TODAS las empresas
$query = "SELECT * FROM facturas ORDER BY fecha DESC";

// ✅ BIEN - Solo datos de MI empresa
$empresa_id = $_SESSION['empresa_id'];
$query = "SELECT * FROM facturas WHERE empresa_id = ? ORDER BY fecha DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $empresa_id);
```

### **2. INSERT - Crear datos**

```php
// Al crear cualquier registro, SIEMPRE incluir empresa_id
$empresa_id = $_SESSION['empresa_id'];

$stmt = $conn->prepare("INSERT INTO productos (empresa_id, nombre, precio, stock) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isdi", $empresa_id, $nombre, $precio, $stock);
```

### **3. UPDATE - Actualizar datos**

```php
// ❌ MAL - Podría actualizar datos de otra empresa
$stmt = $conn->prepare("UPDATE clientes SET telefono = ? WHERE id = ?");

// ✅ BIEN - Solo actualiza SI es de mi empresa
$empresa_id = $_SESSION['empresa_id'];
$stmt = $conn->prepare("UPDATE clientes SET telefono = ? WHERE id = ? AND empresa_id = ?");
$stmt->bind_param("sii", $telefono, $cliente_id, $empresa_id);
```

### **4. DELETE - Eliminar datos**

```php
// ❌ MAL
$stmt = $conn->prepare("DELETE FROM facturas WHERE id = ?");

// ✅ BIEN
$empresa_id = $_SESSION['empresa_id'];
$stmt = $conn->prepare("DELETE FROM facturas WHERE id = ? AND empresa_id = ?");
$stmt->bind_param("ii", $factura_id, $empresa_id);
```

### **5. JOINS - Relaciones entre tablas**

```php
// TODAS las tablas deben filtrar por empresa_id
$query = "SELECT f.*, c.nombre as cliente_nombre
          FROM facturas f
          INNER JOIN clientes c ON f.cliente_id = c.id AND c.empresa_id = ?
          WHERE f.empresa_id = ?
          ORDER BY f.fecha DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $empresa_id, $empresa_id);
```

---

## 📋 **TABLAS QUE DEBEN TENER `empresa_id`**

### **Tablas CON empresa_id (aisladas):**
```
✅ empresas (obviamente)
✅ usuarios
✅ facturas
✅ clientes
✅ proveedores
✅ productos
✅ inventarios
✅ compras
✅ ventas
✅ gastos
✅ ingresos
✅ empleados
✅ liquidaciones
✅ activos_fijos
✅ proyectos
✅ documentos
```

### **Tablas SIN empresa_id (compartidas):**
```
❌ paises (datos globales)
❌ idiomas (datos globales)
❌ planes (datos globales)
❌ configuracion (datos del sistema)
❌ indicadores_economicos (UF, Dólar, etc)
❌ roles (catálogo global)
❌ permisos (catálogo global)
```

---

## 🛡️ **FUNCIÓN HELPER PARA VALIDAR ACCESO**

```php
/**
 * Validar que el usuario pertenece a la empresa antes de acceder a datos
 */
function validarAccesoEmpresa($conn, $usuario_id, $empresa_id_solicitada) {
    $stmt = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();

    if ($usuario['empresa_id'] != $empresa_id_solicitada) {
        // ATAQUE: Usuario intentando acceder a datos de otra empresa
        logAuditoria('intento_acceso_empresa', 'empresas', $empresa_id_solicitada, null, null,
            "Usuario {$usuario_id} intentó acceder a empresa {$empresa_id_solicitada}");

        die("Acceso denegado");
    }

    return true;
}

// USO:
if (isset($_GET['empresa_id'])) {
    validarAccesoEmpresa($conn, $_SESSION['user_id'], $_GET['empresa_id']);
}
```

---

## 🎯 **EJEMPLO COMPLETO: Crear Factura**

```php
<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/plan_restrictions.php';

// 1. Verificar login
requireLogin();

// 2. Obtener empresa_id del usuario autenticado
$usuario_id = $_SESSION['user_id'];
$empresa_id = $_SESSION['empresa_id'];
$plan_id = $_SESSION['plan_id'];

// 3. Validar acceso al módulo de facturación (módulo #3)
$validacion = tieneAccesoModulo($conn, $plan_id, 3);
if (!$validacion['permitido']) {
    die("Tu plan no incluye el módulo de Facturación. <a href='/upgrade.php'>Actualizar Plan</a>");
}

// 4. Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = $_POST['cliente_id'];
    $total = $_POST['total'];

    // 5. VALIDAR que el cliente pertenece a MI empresa
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $cliente_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Cliente no encontrado o no pertenece a tu empresa");
    }
    $stmt->close();

    // 6. Crear factura CON empresa_id
    $stmt = $conn->prepare("INSERT INTO facturas (empresa_id, cliente_id, total, fecha, estado)
                            VALUES (?, ?, ?, NOW(), 'emitida')");
    $stmt->bind_param("iid", $empresa_id, $cliente_id, $total);
    $stmt->execute();
    $factura_id = $stmt->insert_id;
    $stmt->close();

    echo "Factura #$factura_id creada exitosamente";
}

// 7. Listar clientes SOLO de mi empresa para el select
$stmt = $conn->prepare("SELECT * FROM clientes WHERE empresa_id = ? AND activo = 1 ORDER BY nombre");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$clientes = $stmt->get_result();
?>

<select name="cliente_id">
    <?php while ($cliente = $clientes->fetch_assoc()): ?>
        <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nombre']) ?></option>
    <?php endwhile; ?>
</select>
```

---

## ⚠️ **PUNTOS CRÍTICOS DE SEGURIDAD**

### **1. NUNCA confiar en datos del usuario**

```php
// ❌ PELIGRO: Usuario puede manipular el GET/POST
$empresa_id = $_GET['empresa_id']; // NUNCA HACER ESTO

// ✅ SEGURO: Siempre usar el empresa_id de la sesión
$empresa_id = $_SESSION['empresa_id'];
```

### **2. Validar SIEMPRE en el servidor**

```php
// No basta con ocultar botones en el frontend
// SIEMPRE validar en PHP antes de ejecutar acciones
if (!tienePermiso('eliminar_factura')) {
    die("No tienes permiso");
}
```

### **3. Logs de auditoría para intentos sospechosos**

```php
// Si un usuario intenta acceder a datos de otra empresa, LOGUEAR
logAuditoria('intento_acceso_no_autorizado', 'facturas', $factura_id,
    null, null, "Usuario {$usuario_id} intentó acceder a factura de empresa diferente");
```

---

## 📊 **RESUMEN DE IMPLEMENTACIÓN**

| Acción | Validación Requerida |
|--------|---------------------|
| Crear usuario | `puedeCrearUsuario()` + límite de plan |
| Crear empresa | `puedeCrearEmpresa()` + límite de plan |
| Acceder a módulo | `tieneAccesoModulo()` |
| SELECT datos | `WHERE empresa_id = ?` |
| INSERT datos | `(empresa_id, ...)` |
| UPDATE datos | `WHERE id = ? AND empresa_id = ?` |
| DELETE datos | `WHERE id = ? AND empresa_id = ?` |
| Ver datos de URL | `validarAccesoEmpresa()` |

---

## ✅ **CHECKLIST DE SEGURIDAD**

- [ ] Todos los SELECT filtran por `empresa_id`
- [ ] Todos los INSERT incluyen `empresa_id`
- [ ] Todos los UPDATE/DELETE validan `empresa_id`
- [ ] Se validan límites de plan antes de crear usuarios/empresas
- [ ] Se valida acceso a módulos según plan
- [ ] Se loguean intentos de acceso no autorizados
- [ ] NUNCA se confía en `$_GET` o `$_POST` para empresa_id
- [ ] SIEMPRE se usa `$_SESSION['empresa_id']`

---

**Fecha:** 2025-11-16
**Sistema:** CONECTA ERP
**Seguridad:** Multi-tenant + Plan Restrictions
