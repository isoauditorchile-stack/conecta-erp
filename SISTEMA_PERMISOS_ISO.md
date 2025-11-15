# Sistema de Permisos para Módulos ISO

## 📋 Descripción

Este sistema implementa un control de acceso estricto para los 30 módulos de normas ISO en el panel de usuario de AuditorPro.

**Regla de Acceso:**
- ✅ **Solo `auditorexchile@gmail.com` tiene acceso TOTAL** a todos los módulos ISO
- ❌ **Todos los demás usuarios** tienen acceso limitado y deben actualizar su plan

---

## 🏗️ Arquitectura del Sistema

### Archivos Creados

```
conecta-erp/
├── incluir/                          # Carpeta de includes en español
│   ├── configuracion.php             # Configuración general del sistema
│   ├── base_datos.php                # Clase BaseDatos (Singleton)
│   └── sesion.php                    # Clase Sesion con verificación de super admin
│
├── usuario/                          # Panel de usuario
│   └── inicio.php                    # Dashboard con control de acceso a módulos ISO
│
└── configurar_super_admin.php        # Script para configurar permisos
```

---

## 🔐 Lógica de Permisos

### Constante de Super Admin
```php
// En incluir/configuracion.php
define('SUPER_ADMIN_EMAIL', 'auditorexchile@gmail.com');
```

### Verificación en Clase Sesion
```php
// En incluir/sesion.php
public static function es_super_admin() {
    if (!self::esta_autenticado()) {
        return false;
    }
    $correo_usuario = self::obtener('usuario_correo', '');
    return strtolower(trim($correo_usuario)) === strtolower(SUPER_ADMIN_EMAIL);
}
```

### Implementación en usuario/inicio.php
```php
// Verificar si es super admin
$es_super_admin = Sesion::es_super_admin();

// Mostrar módulos completos solo al super admin
<?php if ($es_super_admin): ?>
    <!-- Todos los 30 módulos ISO con acceso completo -->
<?php else: ?>
    <!-- Vista limitada con mensaje de upgrade -->
<?php endif; ?>
```

---

## 🚀 Instalación y Configuración

### Paso 1: Ejecutar Script de Configuración

Accede al script de configuración desde tu navegador:

```
http://tu-dominio/configurar_super_admin.php
```

Este script:
1. Verifica que `auditorexchile@gmail.com` exista en la BD
2. Verifica/crea las columnas `is_admin` y `is_super_admin` en la tabla `users`
3. Establece los permisos de super admin
4. Elimina restricciones de trial
5. Muestra el estado final

### Paso 2: Verificar Tabla Users

Asegúrate de que la tabla `users` tenga estas columnas:

```sql
ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN is_super_admin TINYINT(1) DEFAULT 0;
```

### Paso 3: Configurar Super Admin Manualmente (Alternativa)

Si prefieres hacerlo manualmente vía SQL:

```sql
UPDATE users
SET
    is_admin = 1,
    is_super_admin = 1,
    status = 'active',
    trial_ends_at = NULL
WHERE email = 'auditorexchile@gmail.com';
```

---

## 🎯 Funcionamiento

### Para Super Admin (auditorexchile@gmail.com)

Al iniciar sesión, el usuario verá:

1. **Badge de Super Admin** en el sidebar
2. **Alerta verde** indicando acceso total
3. **Todos los 30 módulos ISO** completamente disponibles:
   - Gestión de Calidad (ISO 9001, 9000, 9004)
   - Medio Ambiente (ISO 14001, 50001, 14064)
   - SST (ISO 45001)
   - Seguridad de la Información (ISO 27001, 27002, 27005, 27017, 27018, 27032, 27035)
   - Servicios TI (ISO 20000, 22301)
   - Gestión de Riesgos (ISO 31000, 31010)
   - Compliance (ISO 37001, 37002, 37301, 37000, 26000)
   - Otras normas especializadas (ISO 22000, 17025, 17020, 17021, 19011, 13485, IATF 16949)

### Para Usuarios Regulares

Al iniciar sesión, verán:

1. **Alerta amarilla** indicando acceso limitado
2. **Vista previa** de solo 3 módulos (bloqueados)
3. **Botón de upgrade** para actualizar plan
4. **Mensaje de restricción** en las tarjetas de módulos

---

## 📊 Métodos de la Clase Sesion

```php
// Verificar autenticación
Sesion::esta_autenticado()          // bool

// Verificar si es admin
Sesion::es_administrador()          // bool

// Verificar si es SUPER ADMIN (auditorexchile@gmail.com)
Sesion::es_super_admin()            // bool

// Alias para acceso total
Sesion::tiene_acceso_total()        // bool

// Obtener datos de sesión
Sesion::obtener('clave', 'default') // mixed

// Establecer datos de sesión
Sesion::establecer('clave', $valor) // void
```

---

## ⚠️ Seguridad

### Protecciones Implementadas

1. **Verificación de email exacta**: Comparación case-insensitive con trim
2. **Constante centralizada**: Un solo lugar para definir el super admin
3. **Múltiples capas de verificación**:
   - Sesión activa
   - Email coincidente
   - Estado activo en BD
4. **No se basa en ID**: El email es la única fuente de verdad
5. **Protección contra acceso directo**: `define('ACCESO_PERMITIDO', true)`

### Recomendaciones

- 🔒 Usar HTTPS en producción
- 🔐 Activar `session.cookie_secure = 1` en producción
- 🔑 Implementar 2FA para el super admin
- 📝 Auditar accesos al panel de usuario
- 🚫 Eliminar `configurar_super_admin.php` después de la configuración inicial

---

## 🧪 Pruebas

### Probar como Super Admin

1. Inicia sesión con `auditorexchile@gmail.com`
2. Accede a `http://tu-dominio/usuario/inicio.php`
3. Deberías ver:
   - Badge "SUPER ADMIN" con corona
   - Alerta verde de acceso total
   - Todos los 30 módulos ISO visibles
   - Enlaces activos a cada módulo

### Probar como Usuario Regular

1. Inicia sesión con cualquier otro email
2. Accede a `http://tu-dominio/usuario/inicio.php`
3. Deberías ver:
   - Alerta amarilla de acceso limitado
   - Solo 3 módulos en versión demo (bloqueados)
   - Botón "Actualizar Plan"
   - Mensaje "Requiere Upgrade" en las tarjetas

---

## 🔧 Troubleshooting

### Problema: El super admin no ve todos los módulos

**Solución:**
1. Verifica que hayas cerrado sesión después de ejecutar el script
2. Limpia cookies y vuelve a iniciar sesión
3. Ejecuta el script `configurar_super_admin.php` nuevamente
4. Verifica en BD que `is_admin = 1` para ese usuario

### Problema: Todos los usuarios ven todos los módulos

**Solución:**
1. Verifica que estés usando `usuario/inicio.php` y no otro archivo
2. Confirma que el archivo tenga la lógica `<?php if ($es_super_admin): ?>`
3. Verifica que la constante `SUPER_ADMIN_EMAIL` esté bien definida

### Problema: Error de base de datos

**Solución:**
1. Verifica las credenciales en `incluir/configuracion.php`
2. Asegúrate de que las tablas existan
3. Ejecuta las consultas ALTER TABLE manualmente

---

## 📞 Soporte

Para más información o problemas:
- Email: auditorexchile@gmail.com
- Revisa los logs de PHP en `error_log`
- Activa el modo debug en `incluir/configuracion.php`

---

## 📄 Licencia

Sistema propietario de AuditorPro - Todos los derechos reservados
