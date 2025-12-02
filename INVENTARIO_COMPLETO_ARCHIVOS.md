# 📚 INVENTARIO COMPLETO DE ARCHIVOS DEL PROYECTO

## 🎯 Índice Rápido

- [Archivos Raíz](#archivos-raíz)
- [Carpeta includes/](#carpeta-includes)
- [Carpeta pages/](#carpeta-pages)
- [Carpeta ajax/](#carpeta-ajax)
- [Carpeta public/](#carpeta-public)
- [Carpeta sql/](#carpeta-sql)
- [Carpeta scripts/](#carpeta-scripts)
- [Resumen por Categoría](#resumen-por-categoría)

---

## 📄 ARCHIVOS RAÍZ

### 🔴 ELIMINAR

| Archivo | Tamaño | Motivo |
|---------|--------|--------|
| `diagnostico.php` | ~2KB | Script de diagnóstico del entorno |
| `test_correcciones_sql.php` | ~2KB | Testing SQL (desarrollo) |
| `test_endpoints.html` | ~5KB | Testing endpoints (desarrollo) |
| `verificar_https.php` | ~1KB | Check HTTPS (setup inicial) |
| `verificar_y_crear_admin.php` | ~1KB | Script de setup (ejecución única) |

**Total a eliminar:** ~11 KB

### 🟢 MANTENER

| Archivo | Tipo | Propósito |
|---------|------|----------|
| `index.php` | Core | Punto de entrada principal |
| `login.php` | Auth | Formulario de login |
| `logout.php` | Auth | Cierre de sesión |
| `composer.json` | Config | Dependencias PHP |
| `composer.lock` | Config | Lock dependencias |
| `.htaccess` | Config | Reescritura URL Apache |
| `README.md` | Doc | Documentación principal |
| `inventario_insumos_v1.sql` | SQL | BD completa del sistema |

---

## 📁 CARPETA `includes/` (CRÍTICA - MANTENER TODO)

```
includes/
├── auth.php           ✅ SISTEMA AUTENTICACIÓN Y PERMISOS (CRÍTICO)
├── config.php         ✅ CONFIGURACIÓN BASE DE DATOS
├── header.php         ✅ MENÚ Y HEADER HTML
├── footer.php         ✅ PIE DE PÁGINA
└── Logger.php         ✅ SISTEMA DE AUDITORÍA
```

**Estado:** 5/5 archivos MANTENER
**Criticidad:** MÁXIMA - Sistema de auth depende completamente de esto

---

## �� CARPETA `pages/` (ESTRUCTURA PRINCIPAL)

### `pages/dashboard.php` 🟢 MANTENER
- Panel principal del sistema
- Contadores y resumen
- **Criticidad:** MÁXIMA

### `pages/insumos/` 📦

| Archivo | Estado | Propósito |
|---------|--------|----------|
| `listar.php` | ✅ MANTENER | Listado insumos principal |
| `agregar.php` | ✅ MANTENER | Crear insumo (simple) |
| `agregar_nueva.php` | ✅ MANTENER | Crear insumo (completo) |
| `editar.php` | ✅ MANTENER | Editar insumo |
| `eliminar.php` | ✅ MANTENER | Eliminar insumo |
| `ver.php` | ✅ MANTENER | Ver detalles insumo |
| `ingresos_listar.php` | ✅ MANTENER | Listado ingresos |
| `ingresos_editar.php` | ✅ MANTENER | Editar ingreso |
| `ver_ajax.php` | ❌ ELIMINAR | Vista experimental no usada |

**Total:** 9 archivos (8 mantener, 1 eliminar)

### `pages/asignaciones/` 🔄

| Archivo | Estado | Propósito |
|---------|--------|----------|
| `listar.php` | ✅ MANTENER | Listado asignaciones |
| `nueva.php` | ✅ MANTENER | Crear asignación (redirect) |
| `nueva_pasos.php` | ✅ MANTENER | Crear asignación (paso a paso) |
| `cambiar_estado.php` | ✅ MANTENER | Cambiar estado asignación |
| `nueva_simple.php` | ❌ ELIMINAR | Versión alternativa obsoleta |

**Total:** 5 archivos (4 mantener, 1 eliminar)

### `pages/reportes/` 📊

| Archivo | Estado | Propósito |
|---------|--------|----------|
| `historial.php` | ✅ MANTENER | Historial movimientos |
| `remito.php` | ✅ MANTENER | Ver remito |
| `remito_pdf.php` | ✅ MANTENER | Generar PDF remito |
| `internet_historial_pdf.php` | ✅ MANTENER | Reporte internet PDF |
| `relevamientos_pdf.php` | ✅ MANTENER | Reporte relevamientos PDF |

**Total:** 5 archivos - TODOS MANTENER

### `pages/admin/` 🔐

#### Gestión de Sistema

| Archivo | Estado | Propósito |
|---------|--------|----------|
| `sedes.php` | ✅ MANTENER | Gestión sedes |
| `sede_detalle.php` | ✅ MANTENER | Detalles sede |
| `areas.php` | ✅ MANTENER | Gestión áreas |
| `auditoria.php` | ✅ MANTENER | Auditoría sistema |

#### Gestión de Usuarios

| Archivo | Estado | Propósito |
|---------|--------|----------|
| `usuarios/listar.php` | ✅ MANTENER | Listado usuarios |
| `usuarios/crear.php` | ✅ MANTENER | Crear usuario |
| `usuarios/editar.php` | ✅ MANTENER | Editar usuario |
| `usuarios/permisos.php` | ✅ MANTENER | Gestión permisos |

#### Telecomunicaciones (7 archivos)

| Archivo | Estado | Propósito |
|---------|--------|----------|
| `telecom_resumen.php` | ✅ MANTENER | Resumen telecomunicaciones |
| `telecom_internet.php` | ✅ MANTENER | Gestión internet |
| `telecom_telefonica.php` | ✅ MANTENER | Gestión telefonía |
| `telecom_vigilancia.php` | ✅ MANTENER | Gestión vigilancia |
| `telecom_vigilancia_servicio.php` | ✅ MANTENER | Detalles vigilancia |
| `telecom_red.php` | ✅ MANTENER | Gestión red |
| `telecom_planos.php` | ✅ MANTENER | Gestión planos |

#### Para ELIMINAR

| Archivo | Motivo |
|---------|--------|
| `poblar_sede_areas.php` | Script seeding (populate datos) |
| `seed_demo.php` | Script seeding (datos demo) |

**Total admin/:** 17 mantener + 2 eliminar

---

## 🔌 CARPETA `ajax/` (36 ENDPOINTS - TODOS CRÍTICOS)

```
ajax/
├── 🟢 insumos_*                    (Operaciones insumos)
│   ├── insumos_list_ssp.php
│   ├── insumos_disponibles.php
│   ├── insumos_eliminar.php
│   ├── insumos_por_ids.php
│   ├── insumo_baja.php
│   └── cargar_insumos.php
│
├── 🟢 asignacion_*                 (Operaciones asignaciones)
│   ├── asignaciones_list_ssp.php
│   ├── asignacion_eliminar.php
│   └── devolver_insumos.php
│
├── 🟢 remitos_*                    (Gestión remitos)
│   ├── remitos_list_ssp.php
│   ├── remito_detalle.php
│   ├── remito_items.php
│   ├── remito_items_update.php
│   └── remitos_anulados_ssp.php
│
├── 🟢 historial_*                  (Historial y auditoría)
│   ├── historial_movimientos_ssp.php
│   ├── historial_bajas_ssp.php
│   ├── historial_devoluciones_ssp.php
│   ├── auditoria_list_ssp.php
│   └── auditoria_detalles.php
│
├── 🟢 telecom_*                    (Telecomunicaciones)
│   ├── contadores_telecom.php
│   ├── internet_*
│   ├── telefonica_*
│   ├── vigilancia_*
│   ├── red_*
│   └── planos_*
│
├── 🟢 usuarios_*                   (Gestión usuarios)
│   ├── usuarios_cambiar_rol.php
│   └── usuarios_toggle_estado.php
│
├── 🟢 ingresos_*                   (Gestión ingresos)
│   ├── ingresos_list.php
│   ├── ingresos_get.php
│   ├── ingresos_save_simple.php
│   └── ingresos_delete.php
│
└── 🟢 utilidades                   (Funciones auxiliares)
    ├── cargar_sedes.php
    ├── cargar_areas.php
    ├── areas_por_sede.php
    ├── sedes_por_localidad.php
    ├── localidades_list.php
    ├── contadores_dashboard.php
    ├── validar_insumo_unico.php
    └── obtener_cadena_traslados.php
```

**Total:** 36 archivos - **TODOS MANTENER** ✅
**Criticidad:** MÁXIMA - Sistema AJAX depende completamente

---

## �� CARPETA `public/` (ESTÁTICOS)

### `public/css/`
- Todos los archivos CSS mantener
- **Ejemplos:** bootstrap.min.css, dataTable.css, custom.css
- Criticidad: MEDIA

### `public/js/`
- Todos los archivos JavaScript mantener
- **Ejemplos:** jquery.min.js, datatables.min.js, custom.js
- Criticidad: MEDIA

### `public/uploads/`
- **MANTENER VACIO O CON DATOS REALES**
- Directorio para archivos subidos por usuarios
- NO ELIMINAR DIRECTORIO
- Criticidad: MEDIA (datos de usuarios)

---

## 💾 CARPETA `sql/` (TODAS LAS MIGRACIONES)

### 🟢 MANTENER TODOS

| Archivo | Propósito |
|---------|----------|
| `actualizar_pc_completa_a_escritorio.sql` | Migración equipos |
| `agregar_fecha_baja_internet.sql` | Campo baja internet |
| `agregar_fecha_instalacion_internet.sql` | Campo instalación internet |
| `agregar_stock_dual_oficina_deposito.sql` | Stock dual |
| `agregar_traslados.sql` | Tabla traslados |
| `corregir_inconsistencias_y_redundancias.sql` | Limpieza datos |
| `limpiar_para_produccion.sql` | Script limpieza BD |
| `migracion_internet_instancias_pendientes.sql` | Instancias internet |
| `migracion_internet_velocidad_unica.sql` | Velocidad internet |
| `migracion_licitaciones_basico.sql` | Licitaciones |
| `migracion_remitos_anulados.sql` | Remitos anulados |
| `migracion_sistema_usuarios.sql` | Sistema usuarios y roles |
| `notebooks_extra_fields.sql` | Campos notebooks |
| ... (y más) | ... |

**Total:** ~15-20 archivos - **TODOS MANTENER** ✅
**Criticidad:** MÁXIMA - Estructura y datos

---

## 🔧 CARPETA `scripts/` 

### 🟢 MANTENER
- (Ninguno conocido en scripts/)

### 🔴 ELIMINAR
| Archivo | Motivo |
|---------|--------|
| `seed_insumos_demo.php` | Script seeding datos demo |

---

## 🔴 CARPETAS COMPLETAS A ELIMINAR

### `.gemini/` 
- Documentación interna de desarrollo
- Notas de análisis
- Guías técnicas internas
- **Total:** ~20 archivos .md
- **Tamaño:** ~200-300 KB

### `docs/`
- Documentación de desarrollo
- Manuales internos
- Análisis de requisitos
- **Total:** ~30+ archivos
- **Tamaño:** ~500 KB

### `DB pelada/`
- Backup de BD sin datos
- Redundante con `inventario_insumos_v1.sql`
- **Tamaño:** ~2-3 MB
- **Propósito:** Solo estructura, no datos

### `.git/` (OPCIONAL)
- Historial de repositorio
- Metadata de versioning
- **Tamaño:** ~5-10 MB
- **Recomendación:** MANTENER para auditoría

---

## 📊 RESUMEN POR CATEGORÍA

### Archivos Raíz

| Categoría | Mantener | Eliminar | Total |
|-----------|----------|----------|-------|
| Core | 3 | 0 | 3 |
| Config | 3 | 0 | 3 |
| SQL | 1 | 0 | 1 |
| Test/Debug | 0 | 5 | 5 |
| **TOTAL** | **7** | **5** | **12** |

### Carpeta pages/ (~70 archivos)

| Subsección | Mantener | Eliminar |
|------------|----------|----------|
| dashboard.php | 1 | 0 |
| insumos/ | 8 | 1 |
| asignaciones/ | 4 | 1 |
| reportes/ | 5 | 0 |
| admin/ (sedes, areas, etc) | 4 | 0 |
| admin/usuarios/ | 4 | 0 |
| admin/telecom/ | 7 | 0 |
| admin/seed | 0 | 2 |
| **TOTAL** | **33** | **4** |

### Carpeta ajax/ (36 endpoints)

| Estado | Cantidad |
|--------|----------|
| Mantener | 36 |
| Eliminar | 0 |
| **TOTAL** | **36** |

### Carpeta includes/ (5 archivos críticos)

| Estado | Cantidad |
|--------|----------|
| Mantener | 5 |
| Eliminar | 0 |
| **TOTAL** | **5** |

### Carpeta public/ (estáticos)

| Estado | Cantidad |
|--------|----------|
| Mantener | Todos |
| Eliminar | 0 |
| **TOTAL** | ~50-100 |

### Carpeta sql/ (migraciones)

| Estado | Cantidad |
|--------|----------|
| Mantener | ~15-20 |
| Eliminar | 0 |
| **TOTAL** | ~15-20 |

### Carpeta vendor/ (dependencias)

| Estado | Cantidad |
|--------|----------|
| Mantener | ~150 |
| Eliminar | 0 |
| **TOTAL** | ~150 |

---

## 🎯 NÚMEROS FINALES

### Archivos de Aplicación

```
Mantener: 
  - includes/: 5
  - pages/: 33
  - ajax/: 36
  - public/: ~80
  - sql/: ~18
  - root: 7
  Total: ~179 archivos

Eliminar:
  - root: 5
  - pages/: 4
  - scripts/: 1
  - carpetas: 4 (docs, .gemini, DB pelada, .git)
  Total: ~10 archivos + 4 carpetas
```

### Espacio Reducido

- **Eliminación archivos:** ~20 KB
- **Eliminación docs/:** ~500 KB
- **Eliminación .gemini/:** ~300 KB
- **Eliminación DB pelada/:** ~2-3 MB
- **Eliminación .git (opcional):** ~5-10 MB

**Total reducción potencial:** ~8-15 MB (~10-20%)

---

## ✅ VALIDACIÓN POST-LIMPIEZA

Después de eliminar, verificar:

1. **App arranca sin errores**
   ```bash
   php -S localhost:8000
   ```

2. **Verifica funcionalidades críticas:**
   - [ ] Login/Logout funciona
   - [ ] Dashboard carga
   - [ ] Listado insumos funciona
   - [ ] AJAX endpoints responden
   - [ ] Generación PDF funciona
   - [ ] Permisos funcionan

3. **Revisar logs:**
   ```bash
   tail -f logs/*.log
   ```

4. **Validar BD:**
   ```bash
   # No debería haber cambios en BD
   ```

---

## 📝 NOTAS IMPORTANTES

1. **No eliminar:** vendor/, public/, sql/, includes/
2. **Hacer backup:** Antes de cualquier eliminación
3. **Testing:** Validar 48 horas en staging
4. **Git:** Recomendación es MANTENER para auditoría
5. **Logs:** Directorio logs/ es crítico, mantener siempre

---

**Última actualización:** 2024
**Versión:** 1.0
**Estado:** LISTO PARA IMPLEMENTAR ✅

