#!/bin/bash
#=====================================================
# CONECTA ERP - Script de Empaquetado
# Crea un archivo comprimido con todo el proyecto
#=====================================================

echo "╔════════════════════════════════════════════════════╗"
echo "║    CONECTA ERP - Empaquetador de Proyecto        ║"
echo "╚════════════════════════════════════════════════════╝"
echo ""

# Directorio base
BASE_DIR="/home/user/conecta-erp"
OUTPUT_DIR="/home/user"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
PACKAGE_NAME="conecta-erp_${TIMESTAMP}.tar.gz"

cd "$BASE_DIR" || exit 1

echo "📦 Empaquetando proyecto..."
echo ""

# Crear archivo tar.gz excluyendo .git
tar -czf "${OUTPUT_DIR}/${PACKAGE_NAME}" \
    --exclude='.git' \
    --exclude='*.tar.gz' \
    --exclude='node_modules' \
    .

if [ $? -eq 0 ]; then
    echo "✅ Paquete creado exitosamente!"
    echo ""
    echo "📍 Ubicación: ${OUTPUT_DIR}/${PACKAGE_NAME}"
    echo "📊 Tamaño: $(du -h "${OUTPUT_DIR}/${PACKAGE_NAME}" | cut -f1)"
    echo ""
    echo "📁 Contenido del paquete:"
    tar -tzf "${OUTPUT_DIR}/${PACKAGE_NAME}" | head -20
    echo "   ... y $(tar -tzf "${OUTPUT_DIR}/${PACKAGE_NAME}" | wc -l) archivos más"
    echo ""
    echo "🚀 Para descomprimir:"
    echo "   tar -xzf ${PACKAGE_NAME}"
else
    echo "❌ Error al crear el paquete"
    exit 1
fi
