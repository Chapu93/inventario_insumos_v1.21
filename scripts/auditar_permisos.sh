#!/bin/bash
# Script de auditoría de permisos en todos los módulos
# Verifica que los botones de acción tengan verificación de permisos

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║  AUDITORÍA DE PERMISOS - TODOS LOS MÓDULOS                ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

BASE_DIR="/opt/lampp/htdocs/inventario_app"
PROBLEMAS=0

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}═══ 1. VERIFICANDO PÁGINAS PRINCIPALES ═══${NC}"
echo ""

# Función para verificar archivo
verificar_archivo() {
    local archivo=$1
    local modulo=$2
    
    echo -n "Verificando: $(basename $archivo)... "
    
    # Verificar que tenga requerirAutenticacion() o estaAutenticado()
    if ! grep -q "requerirAutenticacion()\|estaAutenticado()" "$archivo"; then
        echo -e "${RED}✗ SIN AUTENTICACIÓN${NC}"
        ((PROBLEMAS++))
        return
    fi
    
    # Verificar que tenga verificarPermiso() o tienePermiso()
    if ! grep -q "verificarPermiso\|tienePermiso" "$archivo"; then
        echo -e "${YELLOW}⚠ SIN VERIFICACIÓN DE PERMISOS${NC}"
        ((PROBLEMAS++))
        return
    fi
    
    echo -e "${GREEN}✓ OK${NC}"
}

# Verificar páginas de insumos
echo -e "${YELLOW}→ Módulo: INSUMOS${NC}"
for archivo in "$BASE_DIR/pages/insumos"/*.php; do
    if [[ -f "$archivo" ]]; then
        verificar_archivo "$archivo" "insumos"
    fi
done
echo ""

# Verificar páginas de asignaciones
echo -e "${YELLOW}→ Módulo: ASIGNACIONES${NC}"
for archivo in "$BASE_DIR/pages/asignaciones"/*.php; do
    if [[ -f "$archivo" ]]; then
        verificar_archivo "$archivo" "asignaciones"
    fi
done
echo ""

# Verificar páginas de admin (sedes, telecom, etc)
echo -e "${YELLOW}→ Módulo: ADMINISTRACIÓN${NC}"
for archivo in "$BASE_DIR/pages/admin"/*.php; do
    if [[ -f "$archivo" ]]; then
        verificar_archivo "$archivo" "admin"
    fi
done
echo ""

# Verificar páginas de usuarios
echo -e "${YELLOW}→ Módulo: USUARIOS${NC}"
for archivo in "$BASE_DIR/pages/admin/usuarios"/*.php; do
    if [[ -f "$archivo" ]]; then
        verificar_archivo "$archivo" "usuarios"
    fi
done
echo ""

echo -e "${BLUE}═══ 2. VERIFICANDO ENDPOINTS AJAX ═══${NC}"
echo ""

for archivo in "$BASE_DIR/ajax"/*.php; do
    if [[ -f "$archivo" ]]; then
        nombre=$(basename "$archivo")
        echo -n "Verificando AJAX: $nombre... "
        
        if ! grep -q "estaAutenticado()\|requerirAutenticacion()" "$archivo"; then
            echo -e "${RED}✗ SIN AUTENTICACIÓN${NC}"
            ((PROBLEMAS++))
        else
            echo -e "${GREEN}✓ OK${NC}"
        fi
    fi
done
echo ""

echo -e "${BLUE}═══ 3. BUSCANDO BOTONES SIN VERIFICACIÓN ═══${NC}"
echo ""

# Buscar botones de eliminar sin verificación
echo -e "${YELLOW}→ Botones de ELIMINAR (btn-danger):${NC}"
grep -rn "btn-danger" "$BASE_DIR/pages/" --include="*.php" | while read -r linea; do
    archivo=$(echo "$linea" | cut -d: -f1)
    numero=$(echo "$linea" | cut -d: -f2)
    
    # Verificar si hay tienePermiso cerca (10 líneas antes)
    inicio=$((numero - 10))
    if [ $inicio -lt 1 ]; then inicio=1; fi
    
    if ! sed -n "${inicio},${numero}p" "$archivo" | grep -q "tienePermiso.*eliminar"; then
        echo -e "${RED}  ✗ $(basename $archivo):$numero - Sin verificación de permiso${NC}"
        ((PROBLEMAS++))
    fi
done
echo ""

# Buscar botones de editar sin verificación
echo -e "${YELLOW}→ Botones de EDITAR (btn-warning con fa-edit):${NC}"
grep -rn "btn-warning.*fa-edit\|fa-edit.*btn-warning" "$BASE_DIR/pages/" --include="*.php" | while read -r linea; do
    archivo=$(echo "$linea" | cut -d: -f1)
    numero=$(echo "$linea" | cut -d: -f2)
    
    inicio=$((numero - 10))
    if [ $inicio -lt 1 ]; then inicio=1; fi
    
    if ! sed -n "${inicio},${numero}p" "$archivo" | grep -q "tienePermiso.*editar"; then
        echo -e "${YELLOW}  ⚠ $(basename $archivo):$numero - Sin verificación de permiso${NC}"
    fi
done
echo ""

echo -e "${BLUE}═══ 4. VERIFICANDO ACCIONES ESPECÍFICAS ═══${NC}"
echo ""

# Verificar acción "anular" en asignaciones
echo -e "${YELLOW}→ Acción ANULAR en asignaciones:${NC}"
if grep -rn "anular" "$BASE_DIR/pages/asignaciones/" --include="*.php" | grep -q "tienePermiso.*anular"; then
    echo -e "${GREEN}  ✓ Verificación encontrada en páginas${NC}"
else
    echo -e "${YELLOW}  ⚠ No se encontró verificación en páginas${NC}"
fi

if grep -rn "anular" "$BASE_DIR/ajax/" --include="*asignaciones*.php" | grep -q "tienePermiso.*anular"; then
    echo -e "${GREEN}  ✓ Verificación encontrada en AJAX${NC}"
else
    echo -e "${RED}  ✗ No se encontró verificación en AJAX${NC}"
    ((PROBLEMAS++))
fi
echo ""

# Verificar acción "baja" en insumos
echo -e "${YELLOW}→ Acción DAR DE BAJA en insumos:${NC}"
if grep -rn "baja\|Dar de baja" "$BASE_DIR/pages/insumos/" --include="*.php" | grep -q "tienePermiso.*baja"; then
    echo -e "${GREEN}  ✓ Verificación encontrada${NC}"
else
    echo -e "${YELLOW}  ⚠ No se encontró verificación clara${NC}"
fi
echo ""

echo -e "${BLUE}═══ 5. RESUMEN ═══${NC}"
echo ""

if [ $PROBLEMAS -eq 0 ]; then
    echo -e "${GREEN}✓ No se encontraron problemas críticos${NC}"
    echo -e "${GREEN}✓ Todos los módulos tienen verificación de autenticación${NC}"
else
    echo -e "${RED}✗ Se encontraron $PROBLEMAS problemas potenciales${NC}"
    echo -e "${YELLOW}⚠ Revisa los archivos marcados arriba${NC}"
fi

echo ""
echo "Auditoría completada."
