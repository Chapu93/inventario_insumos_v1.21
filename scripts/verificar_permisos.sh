#!/bin/bash
# Script para verificar la implementación de permisos en el sistema

echo "=== VERIFICACIÓN DE PERMISOS EN EL SISTEMA ==="
echo ""

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Contador de problemas
PROBLEMAS=0

echo "1. Verificando archivos PHP que deberían tener verificación de permisos..."
echo ""

# Buscar archivos en pages/ que no tengan requerirAutenticacion()
echo -e "${YELLOW}Archivos sin requerirAutenticacion():${NC}"
grep -L "requerirAutenticacion()" /opt/lampp/htdocs/inventario_app/pages/**/*.php 2>/dev/null | while read file; do
    if [[ ! "$file" =~ "login.php" ]] && [[ ! "$file" =~ "logout.php" ]]; then
        echo -e "${RED}  ✗ $file${NC}"
        ((PROBLEMAS++))
    fi
done

echo ""
echo "2. Verificando botones de acción con permisos..."
echo ""

# Buscar botones de eliminar sin verificación de permisos
echo -e "${YELLOW}Botones de eliminar sin verificación de permisos:${NC}"
grep -rn "btn-danger\|fa-trash" /opt/lampp/htdocs/inventario_app/pages/ --include="*.php" | \
grep -v "tienePermiso\|verificarPermiso" | \
grep -v "Binary" | head -10

echo ""
echo "3. Verificando endpoints AJAX..."
echo ""

# Buscar archivos AJAX sin verificación
echo -e "${YELLOW}Archivos AJAX potencialmente sin verificación:${NC}"
find /opt/lampp/htdocs/inventario_app/ajax/ -name "*.php" -type f 2>/dev/null | while read file; do
    if ! grep -q "verificarPermiso\|tienePermiso\|requerirAutenticacion" "$file"; then
        echo -e "${RED}  ✗ $file${NC}"
        ((PROBLEMAS++))
    fi
done

echo ""
echo "4. Verificando módulos definidos..."
echo ""

# Extraer módulos del archivo de permisos
echo -e "${GREEN}Módulos definidos en el sistema:${NC}"
grep -A 1 "^\s*'" /opt/lampp/htdocs/inventario_app/pages/admin/usuarios/permisos.php | \
grep "nombre" | sed "s/.*'nombre' => '/  - /" | sed "s/',//"

echo ""
echo "=== RESUMEN ==="
if [ $PROBLEMAS -eq 0 ]; then
    echo -e "${GREEN}✓ No se encontraron problemas evidentes${NC}"
else
    echo -e "${RED}✗ Se encontraron $PROBLEMAS problemas potenciales${NC}"
fi
