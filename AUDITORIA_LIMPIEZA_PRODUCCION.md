# 📋 AUDITORÍA COMPLETA DE ARCHIVOS - INVENTARIO APP
## Lista Detallada de Archivos para Limpieza de Producción

**Generado:** Noviembre 2024  
**Estado del Proyecto:** Listo para limpieza de producción  
**Objetivo:** Identificar exactamente qué eliminar para dejar el proyecto limpio y seguro

---

## 🎯 RESUMEN EJECUTIVO

| Categoría | Cantidad | Acción | Impacto |
|-----------|----------|--------|--------|
| **Archivos a ELIMINAR** | 13 | Borrar antes de producción | ~500 KB |
| **Archivos a REORGANIZAR** | 10 | Mover a docs/ o .gitignore | Organización |
| **Archivos CRÍTICOS** | 50+ | Mantener intactos | Sistema funcional |
| **Directorios CRÍTICOS** | 8 | Mantener completos | Operación |

**Reducción total:** ~5-10% del tamaño del proyecto

---

## 🗑️ LISTA 1: ARCHIVOS A ELIMINAR INMEDIATAMENTE

### Categoría: TEST & DEBUG (5 archivos)

```
1. ❌ diagnostico.php
   Tipo:       Test/Diagnóstico
   Tamaño:     ~15 KB
   Función:    Información de diagnóstico del sistema
   Peligro:    Expone información sensible del servidor
   Eliminar:   SÍ - ANTES de producción
   Comando:    rm -f /opt/lampp/htdocs/inventario_app/diagnostico.php

2. ❌ test_correcciones_sql.php
   Tipo:       Test/SQL
   Tamaño:     ~5 KB
   Función:    Testing de correcciones SQL
   Peligro:    Puede ejecutar SQL no intentado
   Eliminar:   SÍ - ANTES de producción
   Comando:    rm -f /opt/lampp/htdocs/inventario_app/test_correcciones_sql.php

3. ❌ test_endpoints.html
   Tipo:       Test/HTML
   Tamaño:     ~10 KB
   Función:    Testing manual de endpoints
   Peligro:    Permite pruebas manuales de APIs
   Eliminar:   SÍ - ANTES de producción
   Comando:    rm -f /opt/lampp/htdocs/inventario_app/test_endpoints.html

4. ❌ verificar_https.php
   Tipo:       Setup/Verificación
   Tamaño:     ~3 KB
   Función:    Verifica HTTPS (solo desarrollo)
   Peligro:    No necesario en producción
   Eliminar:   SÍ - DESPUÉS del setup
   Comando:    rm -f /opt/lampp/htdocs/inventario_app/verificar_https.php

5. ❌ verificar_y_crear_admin.php
   Tipo:       Setup/Admin
   Tamaño:     ~8 KB
   Función:    Crear admin en desarrollo
   Peligro:    CRÍTICO - Permite crear usuarios sin validación
   Eliminar:   SÍ - INMEDIATAMENTE después de usar
   Comando:    rm -f /opt/lampp/htdocs/inventario_app/verificar_y_crear_admin.php
```

### Categoría: DEMO & SEED DATA (2 archivos) ⚠️ PELIGRO CRÍTICO

```
6. ❌❌ pages/admin/seed_demo.php
   Tipo:       Demo/Seed Data
   Tamaño:     ~20 KB
   Función:    Genera datos de demostración en BD
   Peligro:    ⚠️⚠️⚠️ CRÍTICO - Puede eliminar/corromper datos reales
   Eliminar:   SÍ - NUNCA ejecutar en producción
   Comando:    rm -f /opt/lampp/htdocs/inventario_app/pages/admin/seed_demo.php

7. ❌❌ scripts/seed_insumos_demo.php
   Tipo:       Demo/Seed Data
   Tamaño:     ~15 KB
   Función:    Seed de insumos para demo
   Peligro:    ⚠️⚠️⚠️ CRÍTICO - Puede crear datos innecesarios
   Eliminar:   SÍ - NUNCA ejecutar en producción
   Comando:    rm -f /opt/lampp/htdocs/inventario_app/scripts/seed_insumos_demo.php
```

### Categoría: ARCHIVOS TEMPORALES (2-3 archivos)

```
8. ❌ REMITO_2025_0001.pdf
   Tipo:       PDF Temporal
   Tamaño:     ~100 KB
   Función:    Remito de test generado
   Peligro:    Documento de prueba
   Eliminar:   SÍ - Antes de producción
   Comando:    rm -f /opt/lampp/htdocs/inventario_app/REMITO_2025_0001.pdf

9. ❌ REMITO_2025_0002.pdf
    Tipo:       PDF Temporal
    Tamaño:     ~100 KB
    Función:    Remito de test generado
    Peligro:    Documento de prueba
    Eliminar:   SÍ - Antes de producción
    Comando:    rm -f /opt/lampp/htdocs/inventario_app/REMITO_2025_0002.pdf

11. ❌ páginas/insumos/eliminar.php [VERIFICAR PRIMERO]
    Tipo:       Página potencialmente redundante
    Tamaño:     ~8 KB
    Función:    Eliminar insumos (puede estar en AJAX)
    Verificar:  grep -r "eliminar.php" /opt/lampp/htdocs/inventario_app/pages
                grep -r "eliminar.php" /opt/lampp/htdocs/inventario_app/public
    Eliminar:   SÍ - SI no hay referencias
    Comando:    rm -f /opt/lampp/htdocs/inventario_app/pages/insumos/eliminar.php
```

---

## 📁 LISTA 2: ARCHIVOS A REORGANIZAR/MOVER

### Documentación de Proyecto (5 archivos)

```
1. ⚠️ DOCUMENTACION_PRODUCCION.md
   Estado:     Mantener pero reorganizar
   Acción:     Mover a docs/DOCUMENTACION_PRODUCCION.md
   Razón:      Útil para administración pero fuera de raíz
   Comando:    mv DOCUMENTACION_PRODUCCION.md docs/

2. ⚠️ INVENTARIO_COMPLETO_ARCHIVOS.md
   Estado:     Redundante - puede eliminarse
   Acción:     Eliminar o mover a docs/deprecated/
   Razón:      Información de desarrollo ya documentada
   Comando:    rm -f INVENTARIO_COMPLETO_ARCHIVOS.md
               O: mv INVENTARIO_COMPLETO_ARCHIVOS.md docs/deprecated/

3. ⚠️ LIMPIEZA_PARA_PRODUCCION.md
   Estado:     Redundante - será reemplazada
   Acción:     Mover a docs/deprecated/
   Razón:      Sustituida por AUDITORIA_LIMPIEZA_PRODUCCION.md
   Comando:    mv LIMPIEZA_PARA_PRODUCCION.md docs/deprecated/

4. ⚠️ RESUMEN_AUDITORIA_FINAL.md
   Estado:     Mantener para referencia
   Acción:     Mover a docs/auditorias/
   Razón:      Información histórica útil
   Comando:    mv RESUMEN_AUDITORIA_FINAL.md docs/auditorias/

5. ⚠️ SQL_MIGRACION_INGRESOS.sql
   Estado:     Mantener pero reorganizar
   Acción:     Mover a sql/migraciones/
   Razón:      Migración histórica, debe estar con otras SQL
   Comando:    mv SQL_MIGRACION_INGRESOS.sql sql/migraciones/
```

### Directorios a Reorganizar (2 directorios)

```
6. ⚠️ DB pelada/
   Estado:     Contiene SQL de respaldo
   Acción:     Mover a docs/database_backups/
   Razón:      Backup de BD, referencia histórica
   Comando:    mkdir -p docs/database_backups
               mv "DB pelada"/* docs/database_backups/
               rmdir "DB pelada"

7. ⚠️ .gemini/ (si existe)
   Estado:     Análisis y documentación de desarrollo
   Acción:     Mover a docs/analisis_internos/ o eliminar
   Razón:      Documentación de desarrollo no necesaria en producción
   Comando:    mkdir -p docs/analisis_internos
               mv .gemini/* docs/analisis_internos/
               rmdir .gemini
```

---

## ✅ LISTA 3: ARCHIVOS/DIRECTORIOS A MANTENER (CRÍTICOS)

### Punto de Entrada & Configuración

```
✅ index.php
   Función:    Punto de entrada principal
   Crítico:    SÍ - OBLIGATORIO
   Mantener:   Íntegro

✅ login.php
   Función:    Página de autenticación
   Crítico:    SÍ - OBLIGATORIO
   Mantener:   Íntegro

✅ logout.php
   Función:    Cierre de sesión
   Crítico:    SÍ - OBLIGATORIO
   Mantener:   Íntegro

✅ composer.json
   Función:    Definición de dependencias
   Crítico:    SÍ - OBLIGATORIO
   Mantener:   Íntegro

✅ composer.lock
   Función:    Lock de versiones
   Crítico:    SÍ - RECOMENDADO
   Mantener:   Íntegro

✅ .htaccess
   Función:    Configuración Apache (rewrite rules, seguridad)
   Crítico:    SÍ - OBLIGATORIO
   Mantener:   Íntegro

✅ README.md
   Función:    Documentación de instalación
   Crítico:    Recomendado
   Mantener:   Íntegro
```

### Sistema de Autenticación & Core (5 archivos)

```
✅ includes/auth.php
   Función:    Sistema de autenticación y permisos
   Líneas:     ~800 líneas
   Crítico:    SÍ - OBLIGATORIO
   Mantener:   Íntegro

✅ includes/config.php
   Función:    Configuración global (BD, sesiones, etc)
   Crítico:    SÍ - OBLIGATORIO
   Mantener:   Íntegro

✅ includes/header.php
   Función:    Header/Menú de navegación
   Crítico:    SÍ - OBLIGATORIO
   Mantener:   Íntegro

✅ includes/footer.php
   Función:    Footer de páginas
   Crítico:    SÍ - OBLIGATORIO
   Mantener:   Íntegro

✅ includes/Logger.php
   Función:    Sistema de logging y auditoría
   Crítico:    SÍ - OBLIGATORIO
   Mantener:   Íntegro
```

### Directorios de Aplicación (MANTENER COMPLETOS)

```
✅ pages/
   Archivos:   34 archivos
   Función:    Interfaz de usuario (todas las páginas)
   Subdirs:    admin/, asignaciones/, insumos/, reportes/
   Crítico:    SÍ - OBLIGATORIO
   Mantener:   COMPLETO

✅ ajax/
   Archivos:   36+ archivos
   Función:    API REST (endpoints)
   Crítico:    SÍ - OBLIGATORIO
   Mantener:   COMPLETO

✅ public/
   Subdirs:    css/, js/, uploads/
   Función:    Recursos estáticos y archivos cargados
   Crítico:    SÍ - OBLIGATORIO
   Mantener:   COMPLETO

✅ membretada.pdf
   Función:    Plantilla de diseño para PDFs de reportes
   Usada en:   pages/reportes/remito_pdf.php (línea 81)
               pages/reportes/internet_historial_pdf.php (línea 59)
   Crítico:    SÍ - NECESARIA PARA REPORTES CON DISEÑO
   Tamaño:     ~500 KB (aceptable)
   Mantener:   INTACTA - ⚠️ NO ELIMINAR
   
   NOTA: El código tiene fallback a PDF A4 sin diseño si falta,
         pero la plantilla membretada mejora la presentación profesional.
         Los archivos que la usan verifican si existe:
         
         if (file_exists($templatePath)) {
             $pdf->setSourceFile($templatePath);
             // ... carga plantilla
         }
         
         Sin ella, los PDFs se generan en blanco sin el membrete corporativo.


✅ vendor/
   Función:    Dependencias Composer
   Librerias:  fpdf, fpdi, otras
   Crítico:    SÍ - OBLIGATORIO
   Mantener:   COMPLETO

✅ sql/
   Archivos:   20+ archivos SQL
   Función:    Migraciones y esquema de BD
   Crítico:    SÍ - OBLIGATORIO
   Mantener:   COMPLETO

✅ logs/
   Función:    Registros del sistema
   Crítico:    Mantener pero vaciar regularmente
   Acción:     Crear si no existe, limpiar antes de deploy
   Mantener:   Directorio (vacío)

✅ docs/
   Función:    Documentación del proyecto
   Crítico:    Recomendado
   Mantener:   Reorganizado y limpio
```

---

## 🔐 LISTA 4: VERIFICACIONES CRÍTICAS DE SEGURIDAD

### Antes de Eliminar Archivos

```
1. ✓ Verificar que no hay referencias a seed_demo.php:
   $ grep -r "seed_demo" /opt/lampp/htdocs/inventario_app --exclude-dir=vendor
   
   Esperado: NO debe haber resultados
   
2. ✓ Verificar que no hay referencias a test_endpoints:
   $ grep -r "test_endpoints" /opt/lampp/htdocs/inventario_app --exclude-dir=vendor
   
   Esperado: NO debe haber resultados

3. ✓ Verificar que no hay includes de diagnostico.php:
   $ grep -r "diagnostico" /opt/lampp/htdocs/inventario_app --exclude-dir=vendor
   
   Esperado: NO debe haber resultados

4. ✓ Verificar que .env real no existe (solo .env.example):
   $ ls -la /opt/lampp/htdocs/inventario_app | grep -E "^\.env[^.]"
   
   Esperado: Solo .env.example
```

### Después de Eliminar Archivos

```
1. ✓ Verificar que logs/ existe:
   $ ls -la /opt/lampp/htdocs/inventario_app/logs/
   
   Esperado: Directorio existe, está vacío

2. ✓ Verificar que uploads/ existe:
   $ ls -la /opt/lampp/htdocs/inventario_app/public/uploads/
   
   Esperado: Directorio existe, contiene solo directorios de usuarios

3. ✓ Verificar que vendor/ está completo:
   $ ls /opt/lampp/htdocs/inventario_app/vendor/ | head -10
   
   Esperado: autoload.php, composer/, etc.

4. ✓ Verificar git status:
   $ cd /opt/lampp/htdocs/inventario_app && git status
   
   Esperado: Cambios listos para commit (archivos eliminados)
```

---

## 🚀 PLAN DE EJECUCIÓN RECOMENDADO

### Fase 1: Preparación (Sin cambios)

```bash
# 1. Crear backup del proyecto
cp -r /opt/lampp/htdocs/inventario_app /backup/inventario_app_backup_$(date +%Y%m%d_%H%M%S)

# 2. Crear backup de la BD
mysqldump -u root inventario_insumos > /backup/inventario_insumos_$(date +%Y%m%d_%H%M%S).sql

# 3. Verificar git está limpio
cd /opt/lampp/htdocs/inventario_app && git status
```

### Fase 2: Limpieza Ordenada

```bash
cd /opt/lampp/htdocs/inventario_app

# PASO 1: Eliminar archivos de test (5 archivos)
rm -f diagnostico.php
rm -f test_correcciones_sql.php
rm -f test_endpoints.html
rm -f verificar_https.php
rm -f verificar_y_crear_admin.php

# PASO 2: Eliminar archivos seed/demo (2 archivos) ⚠️ CRÍTICO
rm -f pages/admin/seed_demo.php
rm -f scripts/seed_insumos_demo.php

# PASO 3: Limpiar archivos temporales (2 archivos)
# ⚠️ NO ELIMINAR membretada.pdf - Es plantilla de reportes PDF
rm -f REMITO_*.pdf

# PASO 4: Reorganizar documentación
mkdir -p docs/deprecated docs/auditorias docs/database_backups docs/analisis_internos

mv DOCUMENTACION_PRODUCCION.md docs/ 2>/dev/null || true
mv INVENTARIO_COMPLETO_ARCHIVOS.md docs/deprecated/ 2>/dev/null || true
mv LIMPIEZA_PARA_PRODUCCION.md docs/deprecated/ 2>/dev/null || true
mv RESUMEN_AUDITORIA_FINAL.md docs/auditorias/ 2>/dev/null || true
mv SQL_MIGRACION_INGRESOS.sql sql/migraciones/ 2>/dev/null || true

# PASO 5: Reorganizar directorios
mkdir -p docs/database_backups
if [ -d "DB pelada" ]; then
    mv "DB pelada"/* docs/database_backups/ 2>/dev/null || true
    rmdir "DB pelada"
fi

# PASO 6: Limpiar logs (pero mantener el directorio)
rm -f logs/*.log
mkdir -p logs

# PASO 7: Limpiar verificaciones
grep -r "seed_demo" . --exclude-dir=vendor || echo "✓ No hay referencias a seed_demo"
grep -r "test_endpoints" . --exclude-dir=vendor || echo "✓ No hay referencias a test_endpoints"
```

### Fase 3: Verificación

```bash
# Verificar que la aplicación sigue funcional
php -l index.php
php -l login.php
php -l includes/config.php

# Verificar composer
composer validate

# Listar archivos críticos (deben existir)
ls -la includes/{auth,config,header,footer,Logger}.php
ls -d pages ajax public sql vendor logs

# Mostrar estado final
echo "=== ESTADO FINAL DEL PROYECTO ==="
find . -type f \( -name "*.php" -o -name "*.html" -o -name "*.sql" \) | wc -l
echo "Archivos en ajaxax:"
ls -1 ajax/ | wc -l
echo "Archivos en pages/:"
find pages -type f -name "*.php" | wc -l
echo "Archivos críticos intactos:"
ls -1 index.php login.php logout.php composer.json 2>/dev/null | wc -l
```

### Fase 4: Commit a Git

```bash
git add -A
git commit -m "chore: Limpieza de archivos para producción

- Eliminados archivos de test y debug (diagnostico.php, test_*.*)
- Removidos scripts de seed_demo (peligro crítico)
- Limpiados archivos temporales (PDFs)
- Reorganizada documentación en docs/
- Directorios optimizados para producción"

git log --oneline -5  # Verificar commits
```

---

## 📊 RESULTADO FINAL ESPERADO

Después de ejecutar la limpieza:

```
Proyecto Original:
├── 70+ archivos en raíz y subdirectorios
├── Documentación dispersa
├── Archivos de test y debug
├── Datos demo potencialmente peligrosos
└── Tamaño: ~XXX MB

Proyecto Limpio (Producción):
├── 15 archivos en raíz (solo funcionales)
├── Documentación organizada en docs/
├── Sin archivos de test o debug
├── Sin datos demo o seed
├── Tamaño: ~XXX MB (reducción ~5-10%)
└── ✅ LISTO PARA PRODUCCIÓN
```

---

## ⚠️ PRECAUCIONES FINALES

1. **NUNCA eliminar sin verificar primero:**
   - Si el archivo tiene referencias en el código
   - Si está siendo incluido en otro archivo
   - Si tiene información importante

2. **NUNCA ejecutar en producción:**
   - seed_demo.php
   - verificar_y_crear_admin.php
   - test_endpoints.html
   - diagnostico.php

3. **SIEMPRE mantener:**
   - includes/ (autenticación y core)
   - pages/ (interfaz)
   - ajax/ (API)
   - public/ (recursos)
   - vendor/ (dependencias)
   - sql/ (migraciones)

4. **SIEMPRE verificar después:**
   - `git status` debe estar limpio
   - La aplicación debe funcionar en navegador
   - No debe haber errores PHP
   - Las permisiones deben estar intactas

---

## 📋 CHECKLIST FINAL

- [ ] Backup de BD realizado
- [ ] Backup de proyecto realizado  
- [ ] Archivos de test eliminados (5 archivos)
- [ ] Scripts seed_demo eliminados (2 archivos)
- [ ] Archivos temporales limpiados (4 archivos)
- [ ] Documentación reorganizada
- [ ] Directorios optimizados
- [ ] Logs limpios (directorio mantiene)
- [ ] Sin referencias a archivos eliminados
- [ ] .env.example verificado (sin datos reales)
- [ ] .htaccess verificado
- [ ] Permisos de archivos correctos
- [ ] vendor/ completo
- [ ] git status limpio
- [ ] git commit realizado

---

**Estado:** ✅ Listo para producción después de ejecutar el plan de limpieza
