# 🧹 CHECKLIST DE LIMPIEZA PARA PRODUCCIÓN

## 📋 Resumen de Acciones

**Objetivo:** Remover archivos de desarrollo, debug, test y documentación interna para dejar un proyecto limpio y listo para producción.

**Archivos a eliminar:** ~12 archivos + directorios
**Archivos a mantener:** ~80 archivos críticos + vendor + estáticos

---

## 🗑️ ARCHIVOS INDIVIDUALES A ELIMINAR

### Scripts de Instalación y Setup (NO NECESARIOS EN PRODUCCIÓN)

```bash
# ❌ Estos deben ser eliminados ANTES de deploy
rm -f diagnostico.php
rm -f verificar_https.php  
rm -f verificar_y_crear_admin.php
rm -f test_correcciones_sql.php
rm -f test_endpoints.html
```

**Por qué:**
- `diagnostico.php` - Script de diagnóstico del entorno (solo desarrollo)
- `verificar_https.php` - Verificación de HTTPS (configuración del servidor)
- `verificar_y_crear_admin.php` - Setup inicial (solo ejecución puntual)
- `test_correcciones_sql.php` - Testing de correcciones (desarrollo)
- `test_endpoints.html` - Testing de endpoints (desarrollo)

---

### Scripts de Seeding de Datos (NO USAR EN PRODUCCIÓN)

```bash
# ❌ Estos generan datos de prueba - ELIMINAR
rm -f pages/admin/poblar_sede_areas.php
rm -f pages/admin/seed_demo.php
rm -f scripts/seed_insumos_demo.php
```

**Por qué:**
- Cargan datos de prueba masivamente
- No deben ejecutarse en producción
- Pueden corromper datos reales si se acceden accidentalmente

---

### Archivos Experimentales o No Utilizados

```bash
# ❌ Versiones alternativas no utilizadas
rm -f pages/insumos/ver_ajax.php
rm -f pages/asignaciones/nueva_simple.php
```

**Por qué:**
- Versiones obsoletas o experimentales
- El sistema usa las versiones principales
- Generan confusión en mantenimiento

---

## 📁 DIRECTORIOS A ELIMINAR

### Documentación de Desarrollo

```bash
# ❌ Documentación interna del desarrollo
rm -rf .gemini/
rm -rf docs/
```

**Por qué:**
- Notas internas de desarrollo
- Guías de implementación
- Análisis técnicos internos
- No necesarios para operación

---

### Copias de Base de Datos

```bash
# ❌ Backup de DB sin datos
rm -rf "DB pelada/"
```

**Por qué:**
- Solo estructura SQL sin datos
- Redundante con `inventario_insumos_v1.sql`
- Toma espacio innecesario

---

### Repositorio Git (OPCIONAL)

```bash
# ⚠️ OPCIONAL: Solo si no necesitas historial en producción
# rm -rf .git/
```

**Por qué:**
- Reduce tamaño ~5-10 MB
- Conservar solo si quieres mantener historial de cambios
- Recomendación: MANTENER para auditoría

---

## ✅ ARCHIVOS A MANTENER

### Núcleo del Sistema
- ✅ `index.php` - Punto de entrada
- ✅ `login.php` - Autenticación
- ✅ `logout.php` - Cierre sesión
- ✅ `includes/config.php` - Config DB
- ✅ `includes/auth.php` - Sistema auth y permisos
- ✅ `includes/header.php` - Menú
- ✅ `includes/footer.php` - Pie
- ✅ `includes/Logger.php` - Auditoría

### Configuración
- ✅ `composer.json` - Dependencias
- ✅ `composer.lock` - Lock dependencias  
- ✅ `.htaccess` - Reescritura URLs
- ✅ `README.md` - Documentación principal

### SQL
- ✅ `inventario_insumos_v1.sql` - BD completa
- ✅ `sql/` - Todas las migraciones necesarias

### Contenido Dinámico
- ✅ `public/css/` - Estilos
- ✅ `public/js/` - Scripts
- ✅ `public/uploads/` - Archivos subidos
- ✅ `logs/` - Logs de auditoría

### Todas las Páginas PHP en:
- ✅ `pages/dashboard.php`
- ✅ `pages/insumos/` (todas excepto `ver_ajax.php`)
- ✅ `pages/asignaciones/` (todas excepto `nueva_simple.php`)
- ✅ `pages/reportes/` (todas)
- ✅ `pages/admin/` (todas excepto seed/poblar)

### Todos los Endpoints AJAX
- ✅ `ajax/` (todos los 36 archivos)

### Vendor/Librerías
- ✅ `vendor/` (FPDF, FPDI, Composer)

---

## 🔧 SCRIPT DE LIMPIEZA AUTOMATIZADO

```bash
#!/bin/bash
# Script de limpieza para producción

cd /opt/lampp/htdocs/inventario_app

# Eliminar archivos individuales
rm -f diagnostico.php
rm -f verificar_https.php  
rm -f verificar_y_crear_admin.php
rm -f test_correcciones_sql.php
rm -f test_endpoints.html

# Eliminar archivos de seeding
rm -f pages/admin/poblar_sede_areas.php
rm -f pages/admin/seed_demo.php
rm -f scripts/seed_insumos_demo.php

# Eliminar archivos experimentales
rm -f pages/insumos/ver_ajax.php
rm -f pages/asignaciones/nueva_simple.php

# Eliminar documentación de desarrollo
rm -rf .gemini/
rm -rf docs/

# Eliminar copias de BD
rm -rf "DB pelada/"

echo "✅ Limpieza completada"
echo "📊 Archivos removidos: 12 + directorios"
echo "⚠️  Recuerda: hacer BACKUP antes de ejecutar esto en producción"
```

---

## 📊 ANÁLISIS DE IMPACTO

| Categoría | Archivos | Acción | Riesgo |
|-----------|----------|--------|--------|
| Scripts Setup | 5 | Eliminar | BAJO - No afecta funcionamiento |
| Seeding | 3 | Eliminar | BAJO - No se usan en producción |
| Experimentales | 2 | Eliminar | BAJO - Versiones reemplazadas |
| Documentación | 2 dirs | Eliminar | BAJO - Info de desarrollo |
| BD Backup | 1 dir | Eliminar | BAJO - Redundante |
| **TOTAL CRÍTICO** | **~12** | **ELIMINAR** | **✅ SEGURO** |

---

## 🚀 CHECKLIST PRE-DEPLOY

- [ ] Hacer backup completo del proyecto actual
- [ ] Hacer backup de la base de datos
- [ ] Ejecutar script de limpieza en ambiente de STAGING
- [ ] Verificar que todas las funcionalidades siguen operando
- [ ] Revisar logs para errores
- [ ] Validar que el sistema arranca sin errores
- [ ] Ejecutar testes de endpoints críticos
- [ ] Validar subida de archivos en uploads/
- [ ] Confirmar generación de PDFs
- [ ] Verificar roles y permisos en operación
- [ ] Ejecutar limpieza en PRODUCCIÓN

---

## ⚠️ ADVERTENCIAS IMPORTANTES

1. **BACKUP PREVIO** ⚠️ OBLIGATORIO
   - Hacer backup completo antes de cualquier eliminación
   - Especialmente base de datos

2. **TESTING** 🧪 IMPORTANTE
   - Ejecutar limpieza en staging primero
   - Validar 48 horas en staging
   - Tester todas las funcionalidades críticas

3. **GIT** 📚 CONSIDERAR
   - Si mantienes `.git/`, ocupa ~5-10 MB extra
   - Pero proporciona auditoría de cambios
   - Recomendación: **MANTENER en producción**

4. **LOGS** 📋 IMPORTANTE
   - Mantener el directorio `logs/`
   - Es crítico para auditoría y debugging
   - Configurar rotación de logs

---

## ✨ RESULTADO FINAL

Después de ejecutar la limpieza, tu proyecto estará:

✅ **Limpio** - Sin archivos innecesarios
✅ **Seguro** - Sin scripts de debug o setup expuestos
✅ **Optimizado** - Reducido en tamaño (~5-10 MB)
✅ **Productivo** - Con todas las funcionalidades intactas
✅ **Auditable** - Git history conservado

---

## 📞 SOPORTE POST-LIMPIEZA

Si después de la limpieza encuentras problemas:

1. Restaurar desde backup
2. Revisar logs en `logs/` 
3. Ejecutar diagnostico nuevamente

El sistema puede ejecutarse sin esos archivos sin problemas.

---

**Última actualización:** 2024
**Estado:** Listo para aplicar en producción
**Riesgo:** BAJO ✅

