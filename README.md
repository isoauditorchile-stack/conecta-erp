# CONECTA ERP - Sistema de Gestión Empresarial Completo

Sistema ERP profesional con **14 módulos principales** y **106 submódulos especializados**. Multiusuario, multimoneda, multipaís y multiidioma.

## 🚀 Características Principales

- ✅ **14 Módulos Integrados**: Finanzas, Controlling, Ventas, Materiales, Producción, RRHH, SCM, CRM, Fidelización, BI, Facturación Electrónica, Proyectos, Admin, Mobile
- ✅ **106 Submódulos Especializados**: Funcionalidades completas para cada área de negocio
- ✅ **Multiusuario**: Sistema de roles y permisos granular
- ✅ **Multimoneda**: Soporte para USD, CLP, EUR, y más
- ✅ **Multipaís**: Adaptado para Chile, Argentina, Perú, Colombia, México, Brasil, etc.
- ✅ **Multiidioma**: Español, English, Português, Français, Deutsch, Italiano, Русский, 中文
- ✅ **Sistema de Trial**: 14 días de prueba gratuita para nuevos usuarios
- ✅ **Diseño Moderno**: Interfaz elegante y responsive con diseño dark mode

## 📋 Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o superior / MariaDB 10.3 o superior
- Apache 2.4 o Nginx
- Extensiones PHP requeridas:
  - PDO
  - pdo_mysql
  - mbstring
  - openssl
  - json

## 🔧 Instalación

### 1. Clonar o Descargar el Proyecto

```bash
git clone https://github.com/tuusuario/conecta-erp.git
cd conecta-erp
```

### 2. Configurar Base de Datos

Edita el archivo `includes/config.php` con tus credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'conectae_conectaerpbd');
define('DB_USER', 'conectae_conectaerpuser');
define('DB_PASS', 'pt125824caraud');
```

### 3. Importar Esquema de Base de Datos

```bash
# Importar esquema principal
mysql -u root -p conectae_conectaerpbd < database/schema.sql

# Importar esquema parte 2
mysql -u root -p conectae_conectaerpbd < database/schema_part2.sql

# Insertar módulos y submódulos
mysql -u root -p conectae_conectaerpbd < database/install_submodules.sql
```

### 4. Crear Tabla de Logs de Actividad

```sql
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `module` VARCHAR(50),
  `ip_address` VARCHAR(45),
  `user_agent` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5. Configurar Permisos

```bash
chmod 755 -R /ruta/a/conecta-erp
chmod 777 -R /ruta/a/conecta-erp/uploads
```

### 6. Acceder al Sistema

Abre tu navegador y accede a:

```
http://localhost/conecta-erp/
```

## 👤 Usuarios de Prueba

### Administrador (Acceso Completo)

- **Email**: auditorexchile@gmail.com
- **Contraseña**: (La que configures al registrarte)

### Usuario Regular (Con Trial de 14 Días)

- Cualquier otro email que registres tendrá acceso trial

## 📚 Módulos del Sistema

### 1. Finanzas (FI) - 11 Submódulos
- Contabilidad General
- Cuentas por Cobrar
- Cuentas por Pagar
- Tesorería
- Activos Fijos
- Bancos y Conciliaciones
- Libros Contables
- IFRS Reporting
- Asientos Contables
- Cierres Contables
- Reportes Financieros

### 2. Controlling (CO) - 8 Submódulos
- Centros de Costo
- Órdenes Internas
- Análisis de Rentabilidad
- Presupuestos
- Control de Gastos
- Costos ABC
- Análisis de Proyectos
- Control de Inversiones

### 3. Ventas & Distribución (SD) - 9 Submódulos
- Clientes
- Cotizaciones
- Pedidos de Venta
- Facturación
- Punto de Venta (POS)
- E-commerce
- Precios Dinámicos
- Comisiones
- Análisis de Ventas

### 4. Materiales (MM) - 8 Submódulos
- Productos
- Almacenes
- Inventario
- Proveedores
- Órdenes de Compra
- Recepción de Mercancías
- MRP - Planificación
- Trazabilidad

### 5. Producción (PP) - 10 Submódulos
- Órdenes de Producción
- BOM (Bill of Materials)
- Rutinas de Producción
- MRP II
- Planificación de Capacidades
- Control de Calidad
- Mantenimiento Preventivo
- Fórmulas de Producción
- Costos de Producción
- Centros de Trabajo

### 6. Capital Humano (HCM) - 11 Submódulos
- Empleados
- Nómina
- Reclutamiento
- Onboarding
- Evaluación de Desempeño
- Capacitación
- Desarrollo Organizacional
- Asistencia
- Beneficios
- Vacaciones y Permisos
- Reportes RRHH

### 7-14. Otros Módulos
- Supply Chain Management (SCM) - 10 submódulos
- CRM - 9 submódulos
- Fidelización - 7 submódulos
- Business Intelligence (BI) - 8 submódulos
- Facturación Electrónica - 6 submódulos
- Gestión de Proyectos - 8 submódulos
- Configuración & Admin - 10 submódulos
- Mobile Apps - 5 submódulos

## 🛠️ Desarrollo

### Generar Módulos Adicionales

Si necesitas regenerar o crear nuevos módulos:

```bash
php generate_modules.php
```

### Estructura de Directorios

```
conecta-erp/
├── admin/                 # Panel de administración
│   ├── dashboard_admin.php
│   └── users.php
├── user/                  # Panel de usuario
│   └── dashboard_user.php
├── modules/               # Todos los módulos del sistema
│   ├── fi/               # Finanzas
│   ├── co/               # Controlling
│   ├── sd/               # Ventas
│   └── ...
├── includes/              # Archivos de configuración
│   └── config.php
├── assets/
│   └── css/
│       └── global.css
├── database/              # Scripts SQL
│   ├── schema.sql
│   ├── schema_part2.sql
│   └── install_submodules.sql
└── uploads/               # Archivos subidos
```

## 🔐 Seguridad

- Contraseñas hasheadas con bcrypt
- Protección CSRF en formularios
- Sesiones seguras con regeneración periódica
- Validación y sanitización de datos
- Logs de auditoría de actividades
- Control de acceso basado en roles

## 🌐 Internacionalización

El sistema soporta múltiples idiomas:
- 🇪🇸 Español (por defecto)
- 🇺🇸 English
- 🇧🇷 Português
- 🇫🇷 Français
- 🇩🇪 Deutsch
- 🇮🇹 Italiano
- 🇷🇺 Русский
- 🇨🇳 中文

## 💰 Monedas Soportadas

- USD - Dólar Americano
- CLP - Peso Chileno
- EUR - Euro
- GBP - Libra Esterlina
- ARS - Peso Argentino
- PEN - Sol Peruano
- COP - Peso Colombiano
- MXN - Peso Mexicano
- BRL - Real Brasileño

## 📱 Responsive Design

El sistema está completamente optimizado para:
- 💻 Desktop
- 📱 Tablet
- 📲 Mobile

## 🐛 Solución de Problemas

### Error de conexión a base de datos
Verifica las credenciales en `includes/config.php`

### Página en blanco
Activa el reporte de errores en PHP:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Los módulos no aparecen
Ejecuta el script de instalación de submódulos:
```bash
mysql -u root -p conectae_conectaerpbd < database/install_submodules.sql
```

## 📞 Soporte

Para soporte y consultas:
- Email: auditorexchile@gmail.com
- Website: https://conectaerp.com

## 📄 Licencia

© 2024 CONECTA ERP. Todos los derechos reservados.

## 🚀 Próximas Funcionalidades

- [ ] API REST completa
- [ ] Aplicaciones móviles nativas
- [ ] Integraciones con servicios externos
- [ ] Módulo de IA y Machine Learning
- [ ] Blockchain para trazabilidad
- [ ] Realidad Aumentada para inventarios

## 👥 Contribuciones

Las contribuciones son bienvenidas. Por favor, crea un pull request con tus mejoras.

---

**Hecho con ❤️ en Chile 🇨🇱**
