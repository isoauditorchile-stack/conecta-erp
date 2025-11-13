# 🚀 INSTALACIÓN DE CONECTA ERP v2.0.0

## Sistema de Producción REAL - Guía Completa de Instalación

---

## ⚠️ IMPORTANTE - LEE ESTO PRIMERO

Este sistema está **100% listo para producción** y necesita:

1. **Servidor con PHP 7.4+**
2. **MySQL/MariaDB 5.7+**
3. **Acceso SSH o Terminal**

---

## 📋 MÉTODO 1: INSTALACIÓN AUTOMÁTICA (RECOMENDADO)

### Paso 1: Asegurar que MySQL esté corriendo

```bash
# Verificar estado de MySQL
sudo systemctl status mysql
# o
sudo systemctl status mariadb

# Si no está corriendo, iniciarlo
sudo systemctl start mysql
```

### Paso 2: Ejecutar el instalador automático

```bash
cd /home/user/conecta-erp
php instalar_sistema_completo.php
```

**Esto instalará automáticamente:**
- ✅ Base de datos `conectae_conectaerpbd`
- ✅ 14 módulos principales
- ✅ 106 submódulos
- ✅ Tablas de usuarios
- ✅ Sistema multi-país (8 países)
- ✅ Sistema multi-idioma (8 idiomas)
- ✅ Sistema de planes (4 planes)
- ✅ Sistema de suscripciones
- ✅ Sistema de pagos (PayPal + Transferencia)
- ✅ Sistema de aprobaciones manuales
- ✅ Sistema de notificaciones

### Paso 3: Verificar instalación

El script mostrará al final:

```
✓ modules: 14 registros
✓ submodules: 106 registros
✓ users: 0 registros
✓ countries: 8 registros
✓ planes: 5 registros
...

✅ INSTALACIÓN COMPLETADA EXITOSAMENTE
```

---

## 📋 MÉTODO 2: INSTALACIÓN MANUAL

Si el script automático no funciona, puedes instalar manualmente:

### Paso 1: Conectar a MySQL

```bash
mysql -u conectae_conectaerpuser -ppt125824caraud
```

### Paso 2: Crear base de datos

```sql
CREATE DATABASE IF NOT EXISTS conectae_conectaerpbd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE conectae_conectaerpbd;
```

### Paso 3: Ejecutar scripts SQL en orden

```sql
-- 1. Tablas base
source /home/user/conecta-erp/database/schema_completo_base.sql;

-- 2. Mejoras multi-país
source /home/user/conecta-erp/database/schema_improvements.sql;

-- 3. Sistema de planes y pagos
source /home/user/conecta-erp/database/schema_planes_pagos.sql;
```

### Paso 4: Verificar instalación

```sql
-- Ver tablas creadas
SHOW TABLES;

-- Verificar módulos
SELECT COUNT(*) FROM modules;  -- Debe mostrar 14

-- Verificar submódulos
SELECT COUNT(*) FROM submodules;  -- Debe mostrar 106

-- Verificar países
SELECT COUNT(*) FROM countries;  -- Debe mostrar 8

-- Verificar planes
SELECT COUNT(*) FROM planes;  -- Debe mostrar 5
```

---

## 🌐 CONFIGURACIÓN DE LA PÁGINA PRINCIPAL

### Opción A: Reemplazar index.php (Recomendado)

```bash
cd /home/user/conecta-erp
mv index.php index_old_backup.php
cp index_con_planes.php index.php
```

### Opción B: Usar directamente el archivo nuevo

Acceder a: `http://tu-dominio/index_con_planes.php`

---

## 👑 REGISTRO DEL SUPER ADMINISTRADOR

### Paso 1: Acceder a la página principal

```
http://tu-dominio/index.php
```

### Paso 2: Click en "Ver Planes"

### Paso 3: Seleccionar cualquier plan y registrarse

**IMPORTANTE:** Usa el email **auditorexchile@gmail.com**

**Datos sugeridos:**
- Nombre: Auditor
- Apellido: Ex Chile
- Email: **auditorexchile@gmail.com** ← IMPORTANTE
- Usuario: auditorex
- Contraseña: [la que quieras, mínimo 8 caracteres]
- Empresa: ISO Auditor Chile SpA
- País: Chile
- RUT: 12.345.678-9 (prueba el auto-formato!)

### Paso 4: Aprobación automática

Como eres **auditorexchile@gmail.com**, el sistema:
- ✅ Te aprueba automáticamente (sin esperar)
- ✅ NO te pone en trial
- ✅ Te da acceso inmediato al panel de super admin
- ✅ Estado: "active" (activo)

### Paso 5: Acceder al panel de super admin

```
http://tu-dominio/admin/panel_super_admin.php
```

---

## 👥 FLUJO PARA USUARIOS NORMALES

### 1. Usuario se registra
- Va a `index.php`
- Selecciona un plan
- Llena el formulario completo
- Click en "Crear Cuenta"

### 2. Estado inicial: "pending_approval"
- Usuario ve mensaje: "Tu solicitud será revisada por auditorexchile@gmail.com"
- NO puede iniciar sesión todavía

### 3. Tú (auditorexchile@gmail.com) apruebas
- Accedes a `/admin/panel_super_admin.php`
- Ves el usuario pendiente con todos sus datos
- Click en "Aprobar"
- **Automáticamente se activa trial de 14 días**

### 4. Usuario puede usar el sistema
- Estado: "trial"
- Tiene 14 días de acceso completo
- Recibirá notificaciones día 13, 7 y 1

### 5. Día 0: Desactivación automática
- Si no paga, se desactiva automáticamente
- Estado: "expired"
- NO puede iniciar sesión

---

## ⏰ CONFIGURAR NOTIFICACIONES AUTOMÁTICAS (CRON)

### Paso 1: Editar crontab

```bash
crontab -e
```

### Paso 2: Agregar línea para ejecutar diariamente a las 9:00 AM

```bash
0 9 * * * /usr/bin/php /home/user/conecta-erp/cron/verificar_trials_diarios.php
```

### Paso 3: Guardar y verificar

```bash
# Ver crontab actual
crontab -l
```

**Esto hará que todos los días a las 9:00 AM:**
- ✅ Se envíen notificaciones a usuarios en día 13, 7, 1
- ✅ Se desactiven automáticamente usuarios con trial vencido
- ✅ Se registren todas las notificaciones enviadas

---

## 💳 CONFIGURAR MÉTODOS DE PAGO

### PayPal (Opcional)

1. Obtener credenciales de PayPal:
   - Client ID
   - Secret

2. Actualizar en base de datos:

```sql
UPDATE configuracion_pagos SET
    paypal_client_id = 'TU_CLIENT_ID',
    paypal_secret = 'TU_SECRET',
    paypal_mode = 'live',  -- o 'sandbox' para pruebas
    paypal_activo = 1
WHERE id = 1;
```

### Transferencia Bancaria

1. Actualizar datos bancarios:

```sql
UPDATE configuracion_pagos SET
    banco_nombre = 'Banco de Chile',
    banco_cuenta_numero = '12345678',
    banco_cuenta_tipo = 'Cuenta Corriente',
    banco_titular = 'ISO Auditor Chile SpA',
    banco_rut_titular = '12.345.678-9',
    banco_email_contacto = 'auditorexchile@gmail.com',
    banco_activo = 1
WHERE id = 1;
```

---

## 📧 CONFIGURAR EMAILS (Opcional pero recomendado)

Para enviar emails reales de notificaciones:

### Opción A: PHPMailer (Recomendado)

```bash
composer require phpmailer/phpmailer
```

Luego editar `cron/verificar_trials_diarios.php` y actualizar la función `enviarEmailNotificacion()`.

### Opción B: Servicio SMTP Externo

Usar servicios como:
- SendGrid
- Mailgun
- Amazon SES

---

## ✅ VERIFICACIÓN POST-INSTALACIÓN

### 1. Verificar tablas en la base de datos

```sql
USE conectae_conectaerpbd;

-- Debe mostrar 11+ tablas
SHOW TABLES;

-- Verificar contenido
SELECT COUNT(*) FROM modules;        -- 14
SELECT COUNT(*) FROM submodules;     -- 106
SELECT COUNT(*) FROM countries;      -- 8
SELECT COUNT(*) FROM planes;         -- 5
SELECT COUNT(*) FROM translations;   -- 40+
```

### 2. Verificar archivos renombrados

```bash
# Verificar que los archivos están en español
ls modules/fi/
# Debe mostrar: libro_mayor.php, cuentas_por_pagar.php, etc.

ls modules/hcm/
# Debe mostrar: empleados.php, nomina.php, reclutamiento.php, etc.
```

### 3. Probar registro

1. Ir a `http://tu-dominio/index.php`
2. Click en "Ver Planes"
3. Seleccionar plan
4. Registrarse con email de prueba
5. Verificar que aparece en panel de super admin

### 4. Probar aprobación

1. Login como auditorexchile@gmail.com
2. Ir a `/admin/panel_super_admin.php`
3. Ver usuario pendiente
4. Aprobar
5. Verificar que estado cambió a "trial"

---

## 🔧 SOLUCIÓN DE PROBLEMAS

### Error: "Tabla no existe"

**Solución:** Ejecutar nuevamente el instalador:

```bash
php instalar_sistema_completo.php
```

### Error: "Access denied for user"

**Solución:** Verificar credenciales en `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'conectae_conectaerpbd');
define('DB_USER', 'conectae_conectaerpuser');
define('DB_PASS', 'pt125824caraud');
```

### Error: "MySQL connection refused"

**Solución:** Iniciar MySQL:

```bash
sudo systemctl start mysql
# o
sudo systemctl start mariadb
```

### Error: "Can't connect to local MySQL server"

**Solución:** Verificar que MySQL está corriendo:

```bash
sudo systemctl status mysql
```

### Archivos todavía en inglés

**Solución:** Ejecutar script de renombrado:

```bash
php renombrar_archivos_espanol.php
```

---

## 📚 ARCHIVOS DE DOCUMENTACIÓN

Después de instalar, lee estos archivos:

1. **`README_SISTEMA_PRODUCCION.md`** - Documentación completa del sistema
2. **`MEJORAS_IMPLEMENTADAS.md`** - Lista de todas las mejoras
3. **`INSTALACION.md`** - Este archivo

---

## 🎯 PRÓXIMOS PASOS DESPUÉS DE INSTALAR

1. ✅ Registrarte como auditorexchile@gmail.com
2. ✅ Acceder al panel de super admin
3. ✅ Crear un usuario de prueba y aprobarlo
4. ✅ Probar el trial de 14 días
5. ✅ Configurar crontab para notificaciones
6. ✅ Configurar método de pago (PayPal o banco)
7. ✅ Configurar SMTP para emails reales
8. ✅ Poner en producción!

---

## 📞 SOPORTE

**Super Administrador del Sistema:**
Email: auditorexchile@gmail.com

**Sistema:**
CONECTA ERP v2.0.0
Sistema de Producción REAL

---

## ✅ CHECKLIST DE INSTALACIÓN

- [ ] MySQL corriendo
- [ ] Ejecutar `instalar_sistema_completo.php`
- [ ] Verificar 14 módulos instalados
- [ ] Verificar 106 submódulos instalados
- [ ] Verificar 8 países instalados
- [ ] Verificar 5 planes instalados
- [ ] Reemplazar index.php
- [ ] Registrarse como auditorexchile@gmail.com
- [ ] Acceder a panel super admin
- [ ] Configurar crontab
- [ ] Configurar método de pago
- [ ] Probar registro de usuario normal
- [ ] Probar aprobación de usuario
- [ ] Probar notificaciones de trial
- [ ] Sistema listo para producción! 🎉

---

**Fecha:** 2025-11-13
**Versión:** 2.0.0
**Estado:** PRODUCCIÓN REAL
