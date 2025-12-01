#!/bin/bash

###############################################################################
#                    SCRIPT DE LIMPIEZA PARA PRODUCCIÓN                      #
#                   Sistema de Inventario de Insumos                         #
###############################################################################
#
# Este script elimina archivos de test, debug y demo de forma segura
# antes de desplegar a producción.
#
# USO:
#   bash cleanup_produccion.sh [--dry-run] [--backup]
#
# OPCIONES:
#   --dry-run    : Simula cambios sin ejecutar (recomendado primera vez)
#   --backup     : Crea backup automático antes de eliminar
#   --help       : Muestra este mensaje
#
###############################################################################

set -e  # Exit on error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables globales
DRY_RUN=false
CREATE_BACKUP=false
PROJECT_ROOT="/opt/lampp/htdocs/inventario_app"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

###############################################################################
# Funciones de utilidad
###############################################################################

print_header() {
    echo -e "${BLUE}"
    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║     LIMPIEZA DE PROYECTO PARA PRODUCCIÓN                  ║"
    echo "║     Sistema de Inventario de Insumos                      ║"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

print_section() {
    echo -e "\n${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}$1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_action() {
    if [ "$DRY_RUN" = true ]; then
        echo -e "  ${YELLOW}[DRY RUN]${NC} $1"
    else
        echo -e "  → $1"
    fi
}

confirm() {
    local prompt="$1"
    local response
    
    echo -e -n "${YELLOW}$prompt${NC} (s/n): "
    read -r response
    
    if [[ "$response" =~ ^[sS]$ ]]; then
        return 0
    else
        return 1
    fi
}

execute_command() {
    local cmd="$1"
    local description="$2"
    
    if [ -z "$description" ]; then
        description="$cmd"
    fi
    
    print_action "$description"
    
    if [ "$DRY_RUN" = true ]; then
        print_info "Comando a ejecutar: $cmd"
        return 0
    else
        eval "$cmd" && print_success "Completado" || print_error "Error al ejecutar: $cmd"
    fi
}

###############################################################################
# Funciones de verificación
###############################################################################

check_environment() {
    print_section "1. VERIFICACIÓN DEL ENTORNO"
    
    print_info "Verificando directorio del proyecto..."
    if [ ! -d "$PROJECT_ROOT" ]; then
        print_error "Directorio del proyecto no encontrado: $PROJECT_ROOT"
        exit 1
    fi
    print_success "Directorio encontrado: $PROJECT_ROOT"
    
    print_info "Verificando acceso a git..."
    if ! cd "$PROJECT_ROOT" 2>/dev/null; then
        print_error "No se puede acceder al directorio del proyecto"
        exit 1
    fi
    
    if ! git rev-parse --git-dir > /dev/null 2>&1; then
        print_warning "Este no es un repositorio git válido"
    else
        print_success "Repositorio git detectado"
    fi
    
    print_info "Verificando estado git..."
    if git status -s | grep -q .; then
        print_warning "Hay cambios sin commitear en git"
        git status -s | head -5
        echo "    ..."
    else
        print_success "Git está limpio"
    fi
}

check_references() {
    print_section "2. VERIFICACIÓN DE REFERENCIAS"
    
    print_info "Buscando referencias a archivos a eliminar..."
    
    local files_to_check=(
        "seed_demo"
        "test_endpoints"
        "diagnostico.php"
        "verificar_y_crear_admin"
        "test_correcciones_sql"
    )
    
    local found_references=false
    
    for file in "${files_to_check[@]}"; do
        if grep -r "$file" . --exclude-dir=vendor --exclude-dir=.git --exclude-dir=logs 2>/dev/null | grep -v "AUDITORIA_LIMPIEZA" | grep -q .; then
            print_warning "Encontradas referencias a: $file"
            grep -r "$file" . --exclude-dir=vendor --exclude-dir=.git --exclude-dir=logs 2>/dev/null | head -3
            found_references=true
        else
            print_success "No hay referencias a: $file"
        fi
    done
    
    if [ "$found_references" = true ]; then
        print_warning "Se encontraron referencias a archivos a eliminar"
        if ! confirm "¿Continuar de todas formas?"; then
            print_info "Limpieza cancelada"
            exit 0
        fi
    fi
}

create_backup() {
    print_section "3. CREACIÓN DE BACKUP"
    
    if [ "$CREATE_BACKUP" = false ]; then
        print_info "Saltando creación de backup (usar --backup para crear)"
        return 0
    fi
    
    local backup_dir="/backup/inventario_app_${TIMESTAMP}"
    
    print_info "Creando backup en: $backup_dir"
    
    if [ "$DRY_RUN" = false ]; then
        mkdir -p /backup
        
        if cp -r "$PROJECT_ROOT" "$backup_dir"; then
            print_success "Backup del proyecto creado"
        else
            print_error "Error al crear backup del proyecto"
            return 1
        fi
        
        # Backup de base de datos si es posible
        if command -v mysqldump &> /dev/null; then
            print_info "Creando backup de base de datos..."
            if mysqldump -u root inventario_insumos > "/backup/inventario_insumos_${TIMESTAMP}.sql" 2>/dev/null; then
                print_success "Backup de BD creado"
            else
                print_warning "No se pudo crear backup de BD (usuario/pass podría ser diferente)"
            fi
        fi
    else
        print_info "[DRY RUN] Se crearía backup en: $backup_dir"
    fi
}

###############################################################################
# Funciones de limpieza
###############################################################################

delete_test_files() {
    print_section "4. ELIMINACIÓN DE ARCHIVOS DE TEST"
    
    local files=(
        "diagnostico.php"
        "test_correcciones_sql.php"
        "test_endpoints.html"
        "verificar_https.php"
        "verificar_y_crear_admin.php"
    )
    
    for file in "${files[@]}"; do
        if [ -f "$PROJECT_ROOT/$file" ]; then
            execute_command "rm -f '$PROJECT_ROOT/$file'" "Eliminando: $file"
        else
            print_info "No encontrado (ok): $file"
        fi
    done
}

delete_seed_files() {
    print_section "5. ELIMINACIÓN DE ARCHIVOS SEED/DEMO (⚠ CRÍTICO)"
    
    print_warning "Los siguientes archivos pueden dañar datos si se ejecutan en producción"
    
    local files=(
        "pages/admin/seed_demo.php"
        "scripts/seed_insumos_demo.php"
    )
    
    for file in "${files[@]}"; do
        if [ -f "$PROJECT_ROOT/$file" ]; then
            print_warning "ELIMINANDO: $file"
            execute_command "rm -f '$PROJECT_ROOT/$file'" "Eliminando: $file"
        else
            print_info "No encontrado (ok): $file"
        fi
    done
}

delete_temp_files() {
    print_section "6. LIMPIEZA DE ARCHIVOS TEMPORALES"
    
    print_info "Buscando PDFs temporales (NO eliminar membretada.pdf - es plantilla de reportes)..."
    
    cd "$PROJECT_ROOT"
    
    if ls REMITO_*.pdf 1> /dev/null 2>&1; then
        for pdf in REMITO_*.pdf; do
            execute_command "rm -f '$PROJECT_ROOT/$pdf'" "Eliminando: $pdf"
        done
    else
        print_info "No hay archivos REMITO_*.pdf temporales"
    fi
    
    # Verificar que membretada.pdf se mantiene
    if [ -f "membretada.pdf" ]; then
        print_success "membretada.pdf presente (MANTENER - plantilla de reportes PDF)"
    else
        print_warning "membretada.pdf no encontrado (será necesario recrearlo)"
    fi
}

reorganize_documentation() {
    print_section "7. REORGANIZACIÓN DE DOCUMENTACIÓN"
    
    cd "$PROJECT_ROOT"
    
    # Crear directorios de documentación
    execute_command "mkdir -p docs/deprecated docs/auditorias docs/database_backups docs/analisis_internos" \
                   "Creando directorios de documentación"
    
    # Mover archivos de documentación
    local doc_files=(
        "DOCUMENTACION_PRODUCCION.md:docs/"
        "INVENTARIO_COMPLETO_ARCHIVOS.md:docs/deprecated/"
        "LIMPIEZA_PARA_PRODUCCION.md:docs/deprecated/"
        "RESUMEN_AUDITORIA_FINAL.md:docs/auditorias/"
    )
    
    for file_pair in "${doc_files[@]}"; do
        local src="${file_pair%:*}"
        local dest="${file_pair#*:}"
        
        if [ -f "$PROJECT_ROOT/$src" ]; then
            execute_command "mv '$PROJECT_ROOT/$src' '$PROJECT_ROOT/$dest'" \
                          "Moviendo: $src → $dest"
        fi
    done
    
    # Reorganizar SQL
    if [ -f "$PROJECT_ROOT/SQL_MIGRACION_INGRESOS.sql" ]; then
        execute_command "mkdir -p sql/migraciones && mv SQL_MIGRACION_INGRESOS.sql sql/migraciones/" \
                       "Moviendo: SQL_MIGRACION_INGRESOS.sql → sql/migraciones/"
    fi
}

reorganize_directories() {
    print_section "8. REORGANIZACIÓN DE DIRECTORIOS"
    
    cd "$PROJECT_ROOT"
    
    # Mover DB pelada
    if [ -d "DB pelada" ]; then
        execute_command "mkdir -p docs/database_backups && mv 'DB pelada'/* docs/database_backups/ 2>/dev/null; rmdir 'DB pelada'" \
                       "Moviendo: DB pelada/ → docs/database_backups/"
    else
        print_info "No encontrado (ok): DB pelada/"
    fi
    
    # Mover .gemini si existe
    if [ -d ".gemini" ]; then
        execute_command "mkdir -p docs/analisis_internos && mv .gemini/* docs/analisis_internos/ 2>/dev/null; rmdir .gemini" \
                       "Moviendo: .gemini/ → docs/analisis_internos/"
    else
        print_info "No encontrado (ok): .gemini/"
    fi
}

clean_logs() {
    print_section "9. LIMPIEZA DE LOGS"
    
    cd "$PROJECT_ROOT"
    
    if [ ! -d "logs" ]; then
        execute_command "mkdir -p logs" "Creando directorio logs/"
    fi
    
    if ls logs/*.log 1> /dev/null 2>&1; then
        execute_command "rm -f logs/*.log" "Limpiando archivos de log"
    else
        print_info "No hay archivos de log para limpiar"
    fi
    
    print_success "Directorio logs/ listo (vacío)"
}

###############################################################################
# Funciones finales
###############################################################################

verify_project() {
    print_section "10. VERIFICACIÓN FINAL DEL PROYECTO"
    
    cd "$PROJECT_ROOT"
    
    print_info "Verificando archivos críticos..."
    
    local critical_files=(
        "index.php"
        "login.php"
        "logout.php"
        "composer.json"
        "includes/auth.php"
        "includes/config.php"
        ".htaccess"
    )
    
    local all_exist=true
    for file in "${critical_files[@]}"; do
        if [ -f "$file" ]; then
            print_success "Encontrado: $file"
        else
            print_error "FALTANTE: $file"
            all_exist=false
        fi
    done
    
    print_info "Verificando directorios críticos..."
    
    local critical_dirs=(
        "includes"
        "pages"
        "ajax"
        "public"
        "vendor"
        "sql"
        "logs"
    )
    
    for dir in "${critical_dirs[@]}"; do
        if [ -d "$dir" ]; then
            print_success "Encontrado: $dir/"
        else
            print_error "FALTANTE: $dir/"
            all_exist=false
        fi
    done
    
    if [ "$all_exist" = true ]; then
        print_success "TODOS los archivos críticos están presentes"
    else
        print_error "FALTA algún archivo crítico"
    fi
    
    # Estadísticas
    print_info "Estadísticas del proyecto:"
    echo "  - Archivos PHP: $(find . -type f -name "*.php" | wc -l)"
    echo "  - Archivos en ajax/: $(ls -1 ajax/ 2>/dev/null | wc -l)"
    echo "  - Páginas en pages/: $(find pages -type f -name "*.php" 2>/dev/null | wc -l)"
    echo "  - Archivos SQL: $(ls -1 sql/*.sql 2>/dev/null | wc -l)"
}

generate_git_commit() {
    print_section "11. PREPARACIÓN PARA GIT"
    
    cd "$PROJECT_ROOT"
    
    print_info "Estado de git:"
    if git status -s | head -10; then
        print_success "Cambios detectados"
    else
        print_info "Sin cambios"
        return 0
    fi
    
    if [ "$DRY_RUN" = true ]; then
        print_info "[DRY RUN] No se realizará commit"
        return 0
    fi
    
    if confirm "¿Realizar commit de los cambios?"; then
        execute_command "git add -A" "Agregando cambios a git"
        execute_command "git commit -m 'chore: Limpieza de archivos para producción

- Eliminados archivos de test y debug
- Removidos scripts de seed_demo (peligro crítico)
- Limpiados archivos temporales
- Reorganizada documentación
- Directorios optimizados para producción

Cambios:
  - Archivos test/debug eliminados: 5
  - Scripts seed eliminados: 2  
  - Archivos temporales limpiados: ~4
  - Documentación reorganizada: 4+
  - Tamaño reducido: ~5-10%'" \
                       "Realizando commit"
        print_success "Commit realizado"
    else
        print_info "Commit cancelado - cambios sin hacer commit"
    fi
}

###############################################################################
# Funciones de ayuda
###############################################################################

show_help() {
    cat << 'EOF'

SCRIPT DE LIMPIEZA PARA PRODUCCIÓN
===================================

DESCRIPCIÓN:
  Limpia archivos de test, debug y demo del proyecto antes de desplegar
  a producción.

USO:
  bash cleanup_produccion.sh [OPCIONES]

OPCIONES:
  --dry-run     Simula los cambios sin ejecutar (recomendado primera vez)
  --backup      Crea backup automático antes de eliminar
  --help        Muestra este mensaje

EJEMPLOS:

  # Primera vez: simular cambios
  bash cleanup_produccion.sh --dry-run

  # Si todo se ve bien: ejecutar con backup
  bash cleanup_produccion.sh --backup

  # Después de verificar: ejecutar limpieza
  bash cleanup_produccion.sh

ACCIONES REALIZADAS:

  1. Verifica el entorno (git, directorios)
  2. Busca referencias a archivos a eliminar
  3. Crea backup (si se especifica)
  4. Elimina archivos de test:
     - diagnostico.php
     - test_correcciones_sql.php
     - test_endpoints.html
     - verificar_https.php
     - verificar_y_crear_admin.php
  
  5. Elimina archivos seed (CRÍTICO):
     - pages/admin/seed_demo.php
     - scripts/seed_insumos_demo.php
  
  6. Limpia archivos temporales (PDFs)
  7. Reorganiza documentación en docs/
  8. Reorganiza directorios especiales
  9. Limpia logs/
  10. Verifica proyecto final
  11. Prepara commit a git

PRECAUCIONES:

  ⚠ NUNCA ejecutar en producción sin --dry-run primero
  ⚠ SIEMPRE crear backup con --backup
  ⚠ VERIFICAR que no hay referencias antes de eliminar
  ⚠ Los archivos seed_demo son CRÍTICOS para la BD

ARCHIVOS DE RESPALDO:

  Ubicación: /backup/
  - inventario_app_TIMESTAMP/        (copia del proyecto)
  - inventario_insumos_TIMESTAMP.sql (dump de BD)

EOF
    exit 0
}

###############################################################################
# Función principal
###############################################################################

main() {
    print_header
    
    # Parsear argumentos
    while [[ $# -gt 0 ]]; do
        case $1 in
            --dry-run)
                DRY_RUN=true
                print_warning "MODO DRY RUN: Los cambios NO se ejecutarán"
                shift
                ;;
            --backup)
                CREATE_BACKUP=true
                print_info "Se creará backup antes de la limpieza"
                shift
                ;;
            --help)
                show_help
                ;;
            *)
                print_error "Opción no reconocida: $1"
                echo "Use: bash cleanup_produccion.sh --help"
                exit 1
                ;;
        esac
    done
    
    # Verificaciones previas
    check_environment
    check_references
    create_backup
    
    # Confirmación final
    if ! confirm "\n¿Continuar con la limpieza del proyecto?"; then
        print_info "Limpieza cancelada"
        exit 0
    fi
    
    # Ejecutar limpieza
    delete_test_files
    delete_seed_files
    delete_temp_files
    reorganize_documentation
    reorganize_directories
    clean_logs
    verify_project
    generate_git_commit
    
    # Resumen final
    print_section "LIMPIEZA COMPLETADA"
    print_success "El proyecto ha sido limpiado para producción"
    echo ""
    print_info "Siguiente pasos:"
    echo "  1. Revisar: git log --oneline -5"
    echo "  2. Verificar: git status"
    echo "  3. Probar: Acceder a http://localhost/inventario_app"
    echo "  4. Deployar cuando todo funcione correctamente"
    echo ""
    if [ "$DRY_RUN" = true ]; then
        print_warning "Modo DRY RUN: Ningún cambio fue realizado"
    fi
    if [ "$CREATE_BACKUP" = true ] && [ "$DRY_RUN" = false ]; then
        print_success "Backups disponibles en: /backup/"
    fi
    
    echo ""
}

# Ejecutar función principal
main "$@"
