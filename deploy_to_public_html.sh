#!/bin/bash
# CONECTA ERP v2.0.0 - Script de Deployment a public_html
# Este script copia el proyecto a public_html y lo configura automáticamente

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║  CONECTA ERP v2.0.0 - DEPLOYMENT TO PUBLIC_HTML         ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

# Variables
SOURCE_DIR="/home/user/conecta-erp"
DEST_DIR="/home/user/public_html"
BACKUP_DIR="/home/user/public_html_backup_$(date +%Y%m%d_%H%M%S)"

# Verificar que estamos en el directorio correcto
if [ ! -d "$SOURCE_DIR" ]; then
    echo "✗ Error: Directorio fuente no existe: $SOURCE_DIR"
    exit 1
fi

# 1. Hacer backup de public_html si existe
if [ -d "$DEST_DIR" ]; then
    echo "1. Haciendo backup de public_html existente..."
    mv "$DEST_DIR" "$BACKUP_DIR"
    echo "   ✓ Backup guardado en: $BACKUP_DIR"
    echo ""
else
    echo "1. public_html no existe, creando nuevo..."
    echo ""
fi

# 2. Copiar proyecto a public_html
echo "2. Copiando CONECTA ERP a public_html..."
cp -r "$SOURCE_DIR" "$DEST_DIR"
echo "   ✓ Proyecto copiado exitosamente"
echo ""

# 3. Reemplazar index.php con index_con_planes.php
echo "3. Configurando página principal con sistema de planes..."
cd "$DEST_DIR"
if [ -f "index_con_planes.php" ]; then
    mv index.php index_old_backup.php 2>/dev/null
    cp index_con_planes.php index.php
    echo "   ✓ index.php configurado con sistema de planes"
else
    echo "   ⚠ Advertencia: index_con_planes.php no encontrado"
fi
echo ""

# 4. Configurar permisos
echo "4. Configurando permisos..."

# Permisos para directorios (755)
find "$DEST_DIR" -type d -exec chmod 755 {} \; 2>/dev/null
echo "   ✓ Permisos de directorios: 755"

# Permisos para archivos (644)
find "$DEST_DIR" -type f -exec chmod 644 {} \; 2>/dev/null
echo "   ✓ Permisos de archivos: 644"

# Permisos especiales para scripts PHP ejecutables
chmod 755 "$DEST_DIR/instalar_sistema_completo.php" 2>/dev/null
chmod 755 "$DEST_DIR/renombrar_archivos_espanol.php" 2>/dev/null
chmod 755 "$DEST_DIR/actualizar_submodulos_espanol.php" 2>/dev/null
chmod 755 "$DEST_DIR/cron/verificar_trials_diarios.php" 2>/dev/null
echo "   ✓ Scripts ejecutables: 755"

# .htaccess
chmod 644 "$DEST_DIR/.htaccess" 2>/dev/null
echo "   ✓ .htaccess configurado"

echo ""

# 5. Crear directorio de logs si no existe
echo "5. Creando directorio de logs..."
mkdir -p "$DEST_DIR/logs"
chmod 755 "$DEST_DIR/logs"
touch "$DEST_DIR/logs/php_errors.log"
chmod 644 "$DEST_DIR/logs/php_errors.log"
echo "   ✓ Directorio logs creado"
echo ""

# 6. Verificar estructura
echo "6. Verificando estructura de archivos..."
EXPECTED_DIRS=("admin" "user" "modules" "assets" "includes" "database" "cron")
ALL_OK=true

for dir in "${EXPECTED_DIRS[@]}"; do
    if [ -d "$DEST_DIR/$dir" ]; then
        echo "   ✓ $dir/"
    else
        echo "   ✗ $dir/ - NO ENCONTRADO"
        ALL_OK=false
    fi
done

echo ""

# 7. Verificar archivos clave
echo "7. Verificando archivos clave..."
EXPECTED_FILES=("index.php" "includes/config.php" "admin/panel_super_admin.php" ".htaccess")

for file in "${EXPECTED_FILES[@]}"; do
    if [ -f "$DEST_DIR/$file" ]; then
        echo "   ✓ $file"
    else
        echo "   ✗ $file - NO ENCONTRADO"
        ALL_OK=false
    fi
done

echo ""

# 8. Verificar archivos renombrados en español
echo "8. Verificando módulos en español..."
SPANISH_FILES=("modules/fi/libro_mayor.php" "modules/hcm/empleados.php" "modules/sd/clientes.php")

for file in "${SPANISH_FILES[@]}"; do
    if [ -f "$DEST_DIR/$file" ]; then
        echo "   ✓ $file"
    else
        echo "   ⚠ $file - NO ENCONTRADO (quizás falta renombrar)"
    fi
done

echo ""

# Resumen final
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║  RESUMEN DE DEPLOYMENT                                   ║"
echo "╠═══════════════════════════════════════════════════════════╣"

if [ "$ALL_OK" = true ]; then
    echo "║  ✅ DEPLOYMENT COMPLETADO EXITOSAMENTE                  ║"
    echo "╠═══════════════════════════════════════════════════════════╣"
    echo "║                                                          ║"
    echo "║  Ubicación: /home/user/public_html                       ║"
    echo "║                                                          ║"
    echo "║  PRÓXIMOS PASOS:                                         ║"
    echo "║                                                          ║"
    echo "║  1. Instalar base de datos:                              ║"
    echo "║     cd /home/user/public_html                            ║"
    echo "║     php instalar_sistema_completo.php                    ║"
    echo "║                                                          ║"
    echo "║  2. Acceder al sistema:                                  ║"
    echo "║     http://tu-dominio.com/index.php                      ║"
    echo "║                                                          ║"
    echo "║  3. Registrarse como:                                    ║"
    echo "║     auditorexchile@gmail.com                             ║"
    echo "║                                                          ║"
    echo "║  4. Panel super admin:                                   ║"
    echo "║     http://tu-dominio.com/admin/panel_super_admin.php    ║"
    echo "║                                                          ║"
else
    echo "║  ⚠ DEPLOYMENT COMPLETADO CON ADVERTENCIAS               ║"
    echo "╠═══════════════════════════════════════════════════════════╣"
    echo "║  Revisa los mensajes arriba                              ║"
fi

echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

# Mostrar ubicación del backup
if [ -d "$BACKUP_DIR" ]; then
    echo "📦 Backup de public_html anterior guardado en:"
    echo "   $BACKUP_DIR"
    echo ""
fi

echo "✨ Sistema listo para acceso web!"
echo ""
