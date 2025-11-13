# 🚀 DESPLIEGUE A PUBLIC_HTML

## Mover CONECTA ERP a public_html para acceso web

---

## 📁 OPCIÓN 1: COPIAR TODO A PUBLIC_HTML (RECOMENDADO)

```bash
# 1. Crear backup si existe algo en public_html
cd ~
mv public_html public_html_backup_$(date +%Y%m%d)

# 2. Copiar conecta-erp a public_html
cp -r /home/user/conecta-erp /home/user/public_html

# 3. Verificar
ls -la ~/public_html
```

Ahora tu sitio estará en: **`http://tu-dominio.com/index.php`**

---

## 📁 OPCIÓN 2: SYMLINK (ENLACE SIMBÓLICO)

```bash
# 1. Eliminar public_html si existe
rm -rf ~/public_html

# 2. Crear enlace simbólico
ln -s /home/user/conecta-erp ~/public_html

# 3. Verificar
ls -la ~/public_html
```

---

## 🔐 CONFIGURAR PERMISOS

```bash
cd ~/public_html

# Permisos para directorios
find . -type d -exec chmod 755 {} \;

# Permisos para archivos
find . -type f -exec chmod 644 {} \;

# Permisos para archivos PHP ejecutables
chmod 755 instalar_sistema_completo.php
chmod 755 renombrar_archivos_espanol.php
chmod 755 actualizar_submodulos_espanol.php

# Permisos para cron
chmod 755 cron/verificar_trials_diarios.php

# Verificar propietario (reemplaza 'usuario' con tu usuario de hosting)
chown -R usuario:usuario ~/public_html
```

---

## 🌐 ESTRUCTURA DESPUÉS DE DEPLOYMENT

```
public_html/
├── index.php                      ← Página principal (reemplazar con index_con_planes.php)
├── index_con_planes.php           ← Nueva página con planes
├── admin/
│   ├── dashboard_admin.php
│   └── panel_super_admin.php     ← Panel super admin
├── user/
│   └── dashboard_user.php
├── modules/
│   ├── fi/                        ← TODOS en español
│   ├── co/                        ← libro_mayor.php, etc.
│   ├── sd/
│   ├── mm/
│   ├── pp/
│   └── hcm/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── includes/
│   ├── config.php                 ← REVISAR credenciales BD
│   ├── i18n.php
│   └── modal_registro_completo.php
├── database/
│   ├── INSTALAR_PHPMYADMIN.sql    ← Para instalar en phpMyAdmin
│   └── schema_*.sql
└── cron/
    └── verificar_trials_diarios.php
```

---

## ⚙️ CONFIGURAR .HTACCESS (OPCIONAL PERO RECOMENDADO)

Crea el archivo `.htaccess` en la raíz de public_html:

```bash
cd ~/public_html
nano .htaccess
```

Contenido:

```apache
# CONECTA ERP - Configuración Apache
RewriteEngine On

# Forzar HTTPS (si tienes SSL)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Seguridad: Ocultar archivos sensibles
<FilesMatch "^(config\.php|\.env|composer\.(json|lock)|package\.json)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Seguridad: Ocultar directorios
Options -Indexes

# PHP settings
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
php_value max_input_time 300
