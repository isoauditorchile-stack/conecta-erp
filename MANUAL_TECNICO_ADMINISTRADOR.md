# 📘 MANUAL TÉCNICO COMPLETO - CONECTA ERP
## **SISTEMA EMPRESARIAL NIVEL SAP/SOFTLAND**

### 🔒 **DOCUMENTO CONFIDENCIAL - USO EXCLUSIVO ADMINISTRADORES**

**Versión**: 1.0
**Fecha**: Noviembre 2025
**Autor**: Equipo Técnico CONECTA ERP
**Clasificación**: CONFIDENCIAL - NO DISTRIBUIR A USUARIOS FINALES

---

## 📋 ÍNDICE GENERAL

### PARTE 1: INSTALACIÓN Y CONFIGURACIÓN INICIAL
1. [Requisitos del Sistema](#requisitos-sistema)
2. [Instalación de Base de Datos](#instalacion-bd)
3. [Configuración de Servidor Web](#config-servidor)
4. [Configuración de PHP y Extensiones](#config-php)
5. [Instalación de Módulos](#instalacion-modulos)

### PARTE 2: CONFIGURACIÓN POR MÓDULOS (17 MÓDULOS)
6. [Módulo 1: Administración Central](#modulo-admin)
7. [Módulo 2: Entidades](#modulo-entidades)
8. [Módulo 3: Finanzas FI](#modulo-finanzas)
9. [Módulo 4: Controlling CO](#modulo-controlling)
10. [Módulo 5: Ventas SD](#modulo-ventas)
11. [Módulo 6: Materiales MM](#modulo-materiales)
12. [Módulo 7: Producción PP](#modulo-produccion)
13. [Módulo 8: RRHH HCM](#modulo-rrhh)
14. [Módulo 9: SCM](#modulo-scm)
15. [Módulo 10: CRM](#modulo-crm)
16. [Módulo 11: Fidelización](#modulo-fidelizacion)
17. [Módulo 12: Business Intelligence](#modulo-bi)
18. [Módulo 13: Configuración](#modulo-configuracion)
19. [Módulo 14: E-Commerce](#modulo-ecommerce)
20. [Módulo 15: Proyectos](#modulo-proyectos)
21. [Módulo 16: Calidad](#modulo-calidad)
22. [Módulo 17: Mantenimiento](#modulo-mantenimiento)

### PARTE 3: INTEGRACIONES EXTERNAS
23. [Integración SII (Servicio de Impuestos Internos)](#integracion-sii)
24. [Integración Previred (Previsión Social)](#integracion-previred)
25. [Integración Transbank (Webpay Plus)](#integracion-transbank)

### PARTE 4: MÓDULO RELOJ CONTROL
26. [Sistema de Asistencia y Reloj Control](#reloj-control)
27. [Configuración de Dispositivos Biométricos](#dispositivos-biometricos)
28. [Generación de Reportes Excel](#reportes-excel)

### PARTE 5: API REST
29. [Configuración de API Keys](#api-keys)
30. [Endpoints Disponibles](#api-endpoints)
31. [Rate Limiting y Seguridad](#api-security)

### PARTE 6: TROUBLESHOOTING Y ERRORES
32. [Errores Comunes y Soluciones](#errores-comunes)
33. [Problemas de Conexión a BD](#errores-bd)
34. [Problemas de Integraciones](#errores-integraciones)
35. [Problemas de Performance](#errores-performance)

### PARTE 7: MANTENIMIENTO Y BACKUP
36. [Backups Automatizados](#backups)
37. [Monitoreo del Sistema](#monitoreo)
38. [Actualización de Versiones](#actualizaciones)

---

# PARTE 1: INSTALACIÓN Y CONFIGURACIÓN INICIAL

<a name="requisitos-sistema"></a>
## 1. REQUISITOS DEL SISTEMA

### Hardware Mínimo Recomendado

| Componente | Desarrollo | Producción (< 50 usuarios) | Producción (> 50 usuarios) |
|------------|------------|---------------------------|---------------------------|
| CPU | 2 cores | 4 cores | 8 cores |
| RAM | 4 GB | 8 GB | 16 GB |
| Disco | 20 GB SSD | 100 GB SSD | 500 GB SSD |
| Red | 10 Mbps | 100 Mbps | 1 Gbps |

### Software Requerido

#### Sistema Operativo
- **Recomendado**: Ubuntu Server 20.04 LTS o superior
- **Alternativo**: CentOS 7+, Debian 10+, Windows Server 2019+

#### Servidor Web
- **Apache 2.4+** con módulos:
  - mod_rewrite
  - mod_headers
  - mod_ssl (para HTTPS)
- **Nginx 1.18+** (alternativo)

#### PHP
- **Versión**: PHP 7.4 o superior (recomendado PHP 8.1)
- **Extensiones requeridas**:
  ```bash
  - php-mysqli
  - php-pdo
  - php-pdo-mysql
  - php-mbstring
  - php-xml
  - php-curl
  - php-gd
  - php-zip
  - php-openssl
  - php-soap (para SII y Previred)
  - php-intl
  - php-json
  ```

#### Base de Datos
- **MySQL 5.7+** o **MariaDB 10.3+**
- **Configuración mínima**:
  ```ini
  [mysqld]
  max_connections = 200
  innodb_buffer_pool_size = 2G
  innodb_log_file_size = 512M
  query_cache_size = 128M
  max_allowed_packet = 64M
  ```

#### Otros
- **Composer** (para dependencias PHP)
- **Node.js 14+** (opcional, para compilación de assets)
- **Git** (para control de versiones)

---

<a name="instalacion-bd"></a>
## 2. INSTALACIÓN DE BASE DE DATOS

### Paso 1: Crear Base de Datos

```sql
CREATE DATABASE conectae_conectaerpbd
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

### Paso 2: Crear Usuario y Permisos

```sql
CREATE USER 'conecta_user'@'localhost' IDENTIFIED BY 'PASSWORD_SEGURO_AQUI';

GRANT ALL PRIVILEGES ON conectae_conectaerpbd.* TO 'conecta_user'@'localhost';

FLUSH PRIVILEGES;
```

**⚠️ IMPORTANTE**: Cambiar `PASSWORD_SEGURO_AQUI` por una contraseña fuerte de al menos 16 caracteres con mayúsculas, minúsculas, números y símbolos.

### Paso 3: Importar Archivos SQL en Orden

**ORDEN ESTRICTO DE EJECUCIÓN:**

```bash
# 1. Estructura principal
mysql -u conecta_user -p conectae_conectaerpbd < sql/00_estructura_principal.sql

# 2. Módulos en orden
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/01_entidades.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/02_departamentos.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/03_finanzas.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/04_controlling.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/05_ventas.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/06_materiales.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/07_produccion.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/08_rrhh.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/09_scm.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/10_crm.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/11_fidelizacion.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/12_business_intelligence.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/13_configuracion.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/14_api_y_reportes.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/15_ecommerce.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/16_proyectos_calidad_mantenimiento.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/17_bi_avanzado.sql
mysql -u conecta_user -p conectae_conectaerpbd < sql/modulos/18_reloj_control.sql
```

### Paso 4: Verificar Instalación

```sql
-- Verificar tablas creadas
USE conectae_conectaerpbd;
SHOW TABLES;

-- Debería mostrar aproximadamente 120+ tablas

-- Verificar vistas
SHOW FULL TABLES WHERE Table_type = 'VIEW';

-- Verificar procedimientos almacenados
SHOW PROCEDURE STATUS WHERE Db = 'conectae_conectaerpbd';

-- Verificar eventos programados
SHOW EVENTS;
```

**⚠️ CRÍTICO**: Si alguna tabla falta o hay errores, NO continuar. Revisar logs de MySQL:

```bash
sudo tail -f /var/log/mysql/error.log
```

---

<a name="config-servidor"></a>
## 3. CONFIGURACIÓN DE SERVIDOR WEB

### Opción A: Apache

#### Archivo de Configuración VirtualHost

Crear archivo `/etc/apache2/sites-available/conecta-erp.conf`:

```apache
<VirtualHost *:80>
    ServerName conecta-erp.tusitio.com
    ServerAdmin admin@tusitio.com
    DocumentRoot /var/www/conecta-erp

    <Directory /var/www/conecta-erp>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Seguridad adicional
        <FilesMatch "\.(sql|md|log|ini)$">
            Require all denied
        </FilesMatch>
    </Directory>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/conecta-erp-error.log
    CustomLog ${APACHE_LOG_DIR}/conecta-erp-access.log combined

    # Headers de seguridad
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</VirtualHost>
```

#### Habilitar módulos y sitio

```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod ssl
sudo a2ensite conecta-erp.conf
sudo systemctl reload apache2
```

#### Configurar HTTPS con SSL (OBLIGATORIO para producción)

```bash
# Opción 1: Let's Encrypt (gratuito)
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d conecta-erp.tusitio.com

# Opción 2: Certificado propio
# Crear VirtualHost en puerto 443 con SSLEngine on
```

### Opción B: Nginx

Crear archivo `/etc/nginx/sites-available/conecta-erp`:

```nginx
server {
    listen 80;
    server_name conecta-erp.tusitio.com;
    root /var/www/conecta-erp;
    index index.php index.html;

    # Seguridad
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    # Denegar acceso a archivos sensibles
    location ~ \.(sql|md|log|ini)$ {
        deny all;
    }

    # PHP
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Logs
    access_log /var/log/nginx/conecta-erp-access.log;
    error_log /var/log/nginx/conecta-erp-error.log;
}
```

---

<a name="config-php"></a>
## 4. CONFIGURACIÓN DE PHP Y EXTENSIONES

### Archivo `includes/config.php`

**⚠️ CRÍTICO - CONFIGURAR CORRECTAMENTE**

```php
<?php
/**
 * CONFIGURACIÓN PRINCIPAL DE CONECTA ERP
 * ARCHIVO CONFIDENCIAL - NO SUBIR A REPOSITORIO PÚBLICO
 */

// =====================================================
// CONFIGURACIÓN DE BASE DE DATOS
// =====================================================
define('DB_HOST', 'localhost');          // Host de MySQL
define('DB_USER', 'conecta_user');       // Usuario creado anteriormente
define('DB_PASS', 'PASSWORD_SEGURO');    // ⚠️ CAMBIAR POR CONTRASEÑA REAL
define('DB_NAME', 'conectae_conectaerpbd');
define('DB_CHARSET', 'utf8mb4');

// =====================================================
// CONFIGURACIÓN DE ENTORNO
// =====================================================
define('ENVIRONMENT', 'production'); // development | production
define('DEBUG_MODE', false);         // true solo en desarrollo

// =====================================================
// RUTAS DEL SISTEMA
// =====================================================
define('BASE_URL', 'https://conecta-erp.tusitio.com');
define('BASE_PATH', '/var/www/conecta-erp');
define('UPLOAD_PATH', BASE_PATH . '/uploads');

// =====================================================
// CONFIGURACIÓN DE SESIONES
// =====================================================
define('SESSION_LIFETIME', 7200);    // 2 horas en segundos
define('SESSION_NAME', 'CONECTAERP_SESSION');

// =====================================================
// CONFIGURACIÓN DE SEGURIDAD
// =====================================================
define('ENCRYPTION_KEY', 'KEY_64_CARACTERES_UNICA_GENERADA');  // ⚠️ GENERAR KEY ÚNICA
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);   // 15 minutos

// =====================================================
// ZONA HORARIA
// =====================================================
date_default_timezone_set('America/Santiago');

// =====================================================
// CONEXIÓN A BASE DE DATOS
// =====================================================
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    if (DEBUG_MODE) {
        die("Error de conexión: " . $conn->connect_error);
    } else {
        // En producción, no mostrar detalles técnicos
        die("Error de conexión a la base de datos. Contacte al administrador.");
    }
}

$conn->set_charset(DB_CHARSET);

// =====================================================
// MANEJO DE ERRORES
// =====================================================
if (ENVIRONMENT === 'production') {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/logs/php-errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
```

### Generar Encryption Key Única

```bash
# Generar key de 64 caracteres
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

Copiar el resultado y reemplazar en `ENCRYPTION_KEY`.

### Verificar Extensiones PHP Instaladas

```bash
php -m | grep -E 'mysqli|pdo|mbstring|curl|soap|xml|zip|gd|openssl|intl|json'
```

Si falta alguna extensión:

```bash
# Ubuntu/Debian
sudo apt install php7.4-{mysqli,pdo,mbstring,curl,soap,xml,zip,gd,intl}

# CentOS/RHEL
sudo yum install php-{mysqli,pdo,mbstring,curl,soap,xml,zip,gd,intl}
```

### Configurar `php.ini`

Editar `/etc/php/7.4/apache2/php.ini` (o `/etc/php/7.4/fpm/php.ini` para Nginx):

```ini
[PHP]
max_execution_time = 300
max_input_time = 300
memory_limit = 512M
post_max_size = 64M
upload_max_filesize = 64M
session.gc_maxlifetime = 7200
date.timezone = America/Santiago

[opcache]
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

---

<a name="instalacion-modulos"></a>
## 5. INSTALACIÓN DE MÓDULOS

### Estructura de Directorios

```
/var/www/conecta-erp/
├── api/                      # API REST
├── assets/                   # CSS, JS, imágenes
├── cron/                     # Scripts CRON
├── includes/                 # Archivos PHP compartidos
├── logs/                     # Logs del sistema
├── modulos/                  # Módulos del ERP (17 módulos)
│   ├── admin/
│   ├── entidades/
│   ├── finanzas/
│   ├── controlling/
│   ├── ventas/
│   ├── materiales/
│   ├── produccion/
│   ├── rrhh/
│   │   └── asistencia/      # ⭐ Sistema Reloj Control
│   ├── scm/
│   ├── crm/
│   ├── fidelizacion/
│   ├── bi/
│   ├── configuracion/
│   ├── ecommerce/
│   ├── proyectos/
│   ├── calidad/
│   └── mantenimiento/
├── servicios/                # Integraciones (SII, Previred, Transbank)
├── sql/                      # Archivos SQL
├── uploads/                  # Archivos subidos por usuarios
└── user/                     # Dashboard usuarios

```

### Configurar Permisos de Archivos

```bash
# Propietario
sudo chown -R www-data:www-data /var/www/conecta-erp

# Directorios
sudo find /var/www/conecta-erp -type d -exec chmod 755 {} \;

# Archivos PHP
sudo find /var/www/conecta-erp -type f -name "*.php" -exec chmod 644 {} \;

# Directorios escribibles
sudo chmod -R 775 /var/www/conecta-erp/uploads
sudo chmod -R 775 /var/www/conecta-erp/logs

# Proteger archivos de configuración
sudo chmod 600 /var/www/conecta-erp/includes/config.php
```

### Crear Directorios Necesarios

```bash
sudo mkdir -p /var/www/conecta-erp/logs
sudo mkdir -p /var/www/conecta-erp/uploads/{facturas,productos,empleados,documentos}
sudo mkdir -p /var/www/conecta-erp/temp
```

---

# PARTE 2: CONFIGURACIÓN POR MÓDULOS

<a name="modulo-admin"></a>
## 6. MÓDULO 1: ADMINISTRACIÓN CENTRAL

### Descripción
Módulo core del sistema que gestiona usuarios, empresas, roles y auditoría.

### Submódulos
1. Gestión de Usuarios
2. Gestión de Empresas
3. Auditoría

### Configuración Inicial

#### Crear Primer Usuario Super Admin

```sql
INSERT INTO usuarios (
  email, password, nombre, apellido, es_super_admin, activo
) VALUES (
  '[email protected]',
  '$2y$10$hash_aqui',  -- ⚠️ Hash bcrypt de la contraseña
  'Admin',
  'Sistema',
  1,
  1
);
```

**Para generar hash de contraseña**:

```php
<?php
echo password_hash('CONTRASEÑA_TEMPORAL', PASSWORD_BCRYPT);
?>
```

#### Crear Primera Empresa

```sql
INSERT INTO empresas (
  nombre, rut, giro, direccion, telefono, email, plan_id, activo
) VALUES (
  'MI EMPRESA SPA',
  '76123456-7',
  'Comercio y servicios',
  'Av. Principal 123, Santiago',
  '+56912345678',
  '[email protected]',
  2,  -- Plan profesional
  1
);
```

### Funcionalidades Clave

#### Gestión de Usuarios (`modulos/admin/usuarios.php`)

**Roles disponibles**:
- `super_admin`: Acceso total al sistema
- `admin`: Administrador de empresa
- `gerente`: Acceso a reportes y gestión
- `vendedor`: Acceso a módulo ventas
- `operador`: Acceso limitado operativo

**Permisos JSON** (columna `permisos`):
```json
{
  "finanzas": ["read", "write"],
  "ventas": ["read", "write"],
  "inventario": ["read"],
  "rrhh": ["read"]
}
```

#### Auditoría (`modulos/admin/auditoria.php`)

**Eventos auditados automáticamente**:
- Login/logout de usuarios
- Creación/modificación/eliminación de registros
- Cambios en configuración
- Acceso a módulos sensibles

**Ver auditoría**:
```sql
SELECT * FROM auditoria
WHERE usuario_id = ?
AND fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY fecha DESC;
```

### Posibles Errores

❌ **Error**: "Acceso denegado para usuario"
✅ **Solución**:
```sql
-- Verificar permisos del usuario
SELECT * FROM usuarios WHERE id = X;

-- Actualizar rol si necesario
UPDATE usuarios SET rol = 'admin' WHERE id = X;
```

❌ **Error**: "No se puede eliminar usuario con datos asociados"
✅ **Solución**:
- Los usuarios no se eliminan, se desactivan:
```sql
UPDATE usuarios SET activo = 0 WHERE id = X;
```

---

<a name="modulo-entidades"></a>
## 7. MÓDULO 2: ENTIDADES

### Descripción
Gestión de clientes, proveedores, productos/servicios y empleados.

### Submódulos
1. Clientes
2. Proveedores
3. Productos y Servicios
4. Categorías
5. Empleados

### Configuración - Clientes

#### Tipos de Cliente
- **empresa**: Cliente corporativo
- **persona**: Persona natural
- **gobierno**: Entidades gubernamentales

#### Crear Cliente

```sql
INSERT INTO clientes (
  empresa_id, razon_social, rut, giro, tipo, direccion,
  telefono, email, contacto, condicion_pago, limite_credito
) VALUES (
  1,
  'CLIENTE EJEMPLO LTDA',
  '76987654-3',
  'Comercio',
  'empresa',
  'Av. Ejemplo 456, Santiago',
  '+56912345678',
  '[email protected]',
  'Juan Pérez',
  '30_dias',
  5000000
);
```

### Validación de RUT Chileno

El sistema valida automáticamente RUT chilenos con módulo 11.

**Función de validación**:
```php
function validarRUT($rut) {
    $rut = preg_replace('/[^k0-9]/i', '', $rut);
    $dv  = substr($rut, -1);
    $numero = substr($rut, 0, strlen($rut)-1);

    $i = 2;
    $suma = 0;
    foreach(array_reverse(str_split($numero)) as $v) {
        if($i==8)
            $i = 2;
        $suma += $v * $i;
        ++$i;
    }

    $dvr = 11 - ($suma % 11);

    if($dvr == 11)
        $dvr = 0;
    if($dvr == 10)
        $dvr = 'K';

    if((string)$dvr == strtoupper($rut))
        return true;
    else
        return false;
}
```

### Configuración - Productos

#### Tipos de Producto
- `producto_fisico`: Producto con inventario físico
- `servicio`: Servicio sin inventario
- `producto_digital`: Producto descargable

#### Crear Producto

```sql
INSERT INTO productos (
  empresa_id, codigo, nombre, descripcion, tipo, categoria_id,
  precio_costo, precio_venta, stock_minimo, unidad_medida, activo
) VALUES (
  1,
  'PROD-001',
  'Producto Ejemplo',
  'Descripción del producto',
  'producto_fisico',
  1,
  10000,
  15000,
  10,
  'unidad',
  1
);
```

### Posibles Errores

❌ **Error**: "RUT duplicado"
✅ **Solución**:
```sql
-- Verificar si RUT existe
SELECT * FROM clientes WHERE rut = '76987654-3';

-- Si es mismo cliente, actualizar en vez de insertar
-- Si es cliente diferente, verificar dígito verificador
```

❌ **Error**: "Stock negativo en producto"
✅ **Solución**:
```sql
-- Ver movimientos de inventario
SELECT * FROM movimientos_inventario
WHERE producto_id = X
ORDER BY fecha DESC LIMIT 10;

-- Ajustar stock si es necesario
INSERT INTO movimientos_inventario (
  empresa_id, producto_id, almacen_id, tipo_movimiento,
  cantidad, observaciones
) VALUES (
  1, X, 1, 'ajuste_positivo', 100, 'Ajuste por inventario físico'
);
```

---

(Continúa en siguiente sección debido a límite de longitud...)

