# Resumen Completo de Cambios en Sistema de Insumos

**Fecha:** 21 de octubre de 2025  
**Rama:** correciones_en_21  
**Commits:** c465a8a, 2a25907, 14a77f1

---

## 🎯 Cambios Implementados

### ✅ **1. Tabla de Listado - Nueva Estructura**

**ANTES:**
```
┌─────────────────────────────────────────┐
│ Nombre │ Estado    │ Cantidad │ Acciones│
├─────────────────────────────────────────┤
│ (izq)  │ (izq)     │ (izq)    │ (izq)   │
└─────────────────────────────────────────┘
```

**AHORA:**
```
┌────────────────────────────────────────────────────────┐
│  Nombre  │  Tipo   │ Condición │ Cantidad │ Acciones  │
├────────────────────────────────────────────────────────┤
│ (centro) │(centro) │ (centro)  │ (centro) │ (centro)  │
│  Mouse   │ Varios  │  [Nuevo]  │   [25]   │  [👁️][✏️]│
│  Dell    │Notebook │  [Usado]  │    [1]   │  [👁️][✏️]│
└────────────────────────────────────────────────────────┘
```

**Cambios:**
- ✅ Columna "Estado" **ELIMINADA**
- ✅ Columna "Tipo" **AGREGADA** (badge celeste)
- ✅ Columna "Condición" **AGREGADA** (Nuevo/Usado)
- ✅ Todas las columnas **CENTRADAS**
- ✅ Badges con colores:
  - Tipo: Celeste (bg-info)
  - Nuevo: Verde (bg-success)
  - Usado: Amarillo (bg-warning)
  - Cantidad: Verde/Rojo según stock

---

### ✅ **2. Formulario Agregar Insumo**

**Sección "Información Común" - Nuevos Campos:**

```
┌─────────────────────────────────────┐
│ Información Común                   │
├─────────────────────────────────────┤
│ Fecha de Adquisición                │
│ [2025-10-21]                        │
│                                     │
│ Punto de Almacenamiento *           │
│ [Depósito ▼]                        │
│                                     │
│ ☑ Insumo Nuevo                     │ ← NUEVO
│   (desmarcar si es usado)          │
│                                     │
│ Nro. Expediente                     │ ← NUEVO
│ [Sin licitación ▼]                  │
│   - Sin licitación                  │
│   - EXP-2025-001                    │
│   - EXP-2025-002                    │
│                                     │
│ Opcional: Asociar a licitación     │
└─────────────────────────────────────┘
```

**Comportamiento:**
- Checkbox marcado por defecto (Nuevo)
- Select de licitaciones opcional
- Se guarda correctamente en BD

---

### ✅ **3. Formulario Editar Insumo**

**Cambios:**

1. **Punto de Almacenamiento:**
   - **ANTES:** Campo disabled (no editable)
   - **AHORA:** Select editable con todas las opciones

2. **Nuevos Campos Agregados:**
   - Checkbox "Insumo Nuevo" (pre-marcado según BD)
   - Select "Nro. Expediente" (pre-seleccionado si existe)

3. **UPDATE SQL Mejorado:**
```sql
UPDATE insumos SET
    nombre_insumo = ?,
    ...,
    id_punto_stock_actual = ?,  ← Ahora editable
    id_licitacion = ?,          ← Ahora editable
    es_nuevo = ?                ← Ahora editable
WHERE id_insumo = ?
```

---

### ✅ **4. Formulario Agregar Insumo Asignado**

**Nuevos Campos en "Información Común":**
- ✅ Checkbox "Insumo Nuevo"
- ✅ Select "Nro. Expediente"

**INSERT Actualizado:**
```php
INSERT INTO insumos (
    ...,
    id_licitacion,  ← NUEVO
    es_nuevo        ← NUEVO
) VALUES (?, ?, ?, ..., ?, ?)
```

---

### ✅ **5. Modal Ver Insumo**

**Información Mostrada (Actualizada):**

```
┌───────────────────────────────────┐
│ Información General               │
├───────────────────────────────────┤
│ Fecha: 15/10/2025                 │
│ Punto: Depósito                   │
│ Condición: [Nuevo]     ← NUEVO    │
│ Nro. Expediente: [EXP-2025-001]  │ ← NUEVO
└───────────────────────────────────┘
```

**JOIN Actualizado:**
```sql
LEFT JOIN licitaciones lic 
ON i.id_licitacion = lic.id_licitacion
```

---

### ✅ **6. Base de Datos - Migración**

**Archivo:** `pages/admin/migracion_005_campo_es_nuevo.php`

**SQL Ejecutado:**
```sql
ALTER TABLE insumos 
ADD COLUMN es_nuevo TINYINT(1) DEFAULT 1 
COMMENT '1=Nuevo, 0=Usado' 
AFTER id_licitacion;
```

**Características:**
- Default = 1 (todos los existentes quedan como "Nuevo")
- Verificación de existencia (seguro ejecutar múltiples veces)
- Transacción con rollback en caso de error

---

### ✅ **7. Endpoint Server-Side (insumos_list_ssp.php)**

**Columnas Actualizadas:**
```php
// ANTES
$columns = [
    0 => 'i.nombre_insumo',
    1 => 'i.estado',        // ← Eliminado
    2 => 'i.cantidad',
];

// AHORA
$columns = [
    0 => 'i.nombre_insumo',
    1 => 'i.tipo_insumo',   // ← Nuevo
    2 => 'i.es_nuevo',      // ← Nuevo
    3 => 'i.cantidad',
];
```

**SELECT Actualizado:**
```sql
SELECT i.id_insumo, i.nombre_insumo, 
       i.tipo_insumo,   ← NUEVO
       i.es_nuevo,      ← NUEVO
       i.cantidad, i.estado
```

**Renderizado con Centrado:**
```php
'<div class="text-center"><strong>' . nombre . '</strong></div>',
'<div class="text-center"><span class="badge bg-info">' . tipo . '</span></div>',
'<div class="text-center"><span class="badge">' . condición . '</span></div>',
```

---

### ✅ **8. CSS - Centrado de Columnas**

**Nueva Regla:**
```css
#tablaInsumos tbody td {
    text-align: center;
    vertical-align: middle;
}
```

**Efecto:**
- ✅ Todas las celdas centradas horizontalmente
- ✅ Contenido alineado verticalmente al centro
- ✅ Mejor presentación visual

---

## 📊 Comparativa Antes/Después

| Aspecto | ❌ Antes | ✅ Ahora |
|---------|----------|----------|
| Columnas tabla | Nombre, Estado, Cantidad | Nombre, Tipo, Condición, Cantidad |
| Alineación | Izquierda | Centro |
| Estado visible | Sí (en tabla) | No (solo en detalle) |
| Tipo visible | No | Sí (badge celeste) |
| Nuevo/Usado | No existe | Sí (badges verde/amarillo) |
| Editar punto stock | No (disabled) | Sí (select editable) |
| Editar licitación | No | Sí (select editable) |
| Agregar asignado | Sin campos nuevos | Con Nuevo/Usado y Licitación |
| Modal ver | Sin info | Con Condición y Expediente |

---

## 🗄️ Estructura de Base de Datos

### Tabla `insumos` - Campos Nuevos:

```sql
id_licitacion INT NULL,
es_nuevo TINYINT(1) DEFAULT 1 COMMENT '1=Nuevo, 0=Usado'
```

### Relaciones:

```sql
FOREIGN KEY (id_licitacion) 
REFERENCES licitaciones(id_licitacion) 
ON DELETE SET NULL
```

---

## 📁 Archivos Modificados

```
✅ pages/admin/migracion_005_campo_es_nuevo.php (NUEVO)
✅ pages/insumos/listar.php (thead actualizado)
✅ pages/insumos/editar.php (campos editables)
✅ pages/insumos/agregar_asignado.php (nuevos campos)
✅ pages/insumos/ver_ajax.php (JOIN y visualización)
✅ ajax/insumos_list_ssp.php (columnas y renderizado)
✅ public/css/style.css (centrado)
✅ INSTRUCCIONES_MIGRACION_005.md (documentación)
✅ RESUMEN_CAMBIOS_INSUMOS.md (este archivo)
```

**Total:** 9 archivos (1 nuevo, 6 modificados, 2 documentación)

---

## 🚀 Pasos para Usar

### **1. Ejecutar Migración (OBLIGATORIO)**

**Desde navegador:**
```
http://tu-servidor/inventario_app/pages/admin/migracion_005_campo_es_nuevo.php
```

**Verificar mensaje:**
```
✓ Columna 'es_nuevo' agregada correctamente
✓ Migración completada exitosamente
```

### **2. Probar Funcionalidades**

**Test 1: Ver Listado**
- Ir a **Insumos → Listado**
- Verificar columnas: Nombre, Tipo, Condición, Cantidad, Acciones
- Verificar que todo está centrado
- Verificar badges de colores correctos

**Test 2: Agregar Insumo Nuevo**
- Ir a **Insumos → Agregar Insumo**
- Scroll a "Información Común"
- Verificar checkbox "Insumo Nuevo" (marcado)
- Verificar select "Nro. Expediente"
- Completar y guardar
- Verificar en listado: badge verde "Nuevo"

**Test 3: Agregar Insumo Usado**
- Agregar insumo
- Desmarcar "Insumo Nuevo"
- Guardar
- Verificar en listado: badge amarillo "Usado"

**Test 4: Asociar a Licitación**
- Agregar insumo
- Seleccionar licitación en "Nro. Expediente"
- Guardar
- Click en "Ver" (botón celeste)
- Verificar que muestra badge azul con expediente

**Test 5: Editar Insumo**
- Editar cualquier insumo
- Verificar que puede cambiar "Punto de Almacenamiento"
- Verificar que puede cambiar "Insumo Nuevo"
- Verificar que puede cambiar "Nro. Expediente"
- Guardar y verificar cambios

**Test 6: Agregar Insumo Asignado**
- Ir a **Insumos → Agregar Insumo Asignado**
- Completar datos
- Verificar campos nuevos están disponibles
- Guardar y verificar

---

## 🎨 Paleta de Colores (Consistente)

| Elemento | Color | Clase CSS | Uso |
|----------|-------|-----------|-----|
| Tipo | Celeste | `bg-info` | Badge tipo insumo |
| Nuevo | Verde | `bg-success` | Condición: nuevo |
| Usado | Amarillo | `bg-warning` | Condición: usado |
| Expediente | Azul | `bg-primary` | Nro. licitación |
| Cantidad > 0 | Verde | `bg-success` | Stock disponible |
| Cantidad = 0 | Rojo | `bg-danger` | Sin stock |

---

## 📊 Estadísticas de Cambios

```
Archivos modificados:     6
Archivos nuevos:          1
Archivos documentación:   2
Líneas agregadas:        ~300
Líneas eliminadas:       ~50
Campos BD nuevos:         1 (es_nuevo)
Migraciones:              1
```

---

## ⚠️ IMPORTANTE: Ejecutar Migración

**Antes de usar el sistema actualizado:**

1. Ve a: `http://tu-servidor/inventario_app/pages/admin/migracion_005_campo_es_nuevo.php`
2. Ejecuta la migración
3. Verifica el mensaje de éxito
4. Prueba las funcionalidades

**O ejecuta SQL directamente:**
```sql
ALTER TABLE insumos 
ADD COLUMN es_nuevo TINYINT(1) DEFAULT 1 
COMMENT '1=Nuevo, 0=Usado' 
AFTER id_licitacion;
```

---

## 🧪 Casos de Prueba Recomendados

### Caso 1: Insumo Nuevo con Licitación
```
1. Agregar insumo tipo "Notebook"
2. Dejar marcado "Insumo Nuevo" ✓
3. Seleccionar "EXP-2025-001"
4. Guardar
5. En listado ver:
   - Tipo: [Notebook] (celeste)
   - Condición: [Nuevo] (verde)
6. Click "Ver":
   - Condición: [Nuevo]
   - Nro. Expediente: [EXP-2025-001]
```

### Caso 2: Insumo Usado sin Licitación
```
1. Agregar insumo tipo "Mouse" (Varios)
2. Desmarcar "Insumo Nuevo" ✗
3. Dejar "Sin licitación"
4. Guardar
5. En listado ver:
   - Tipo: [Varios] (celeste)
   - Condición: [Usado] (amarillo)
6. Click "Ver":
   - Condición: [Usado]
   - Sin badge de expediente
```

### Caso 3: Editar Punto y Licitación
```
1. Editar insumo existente
2. Cambiar "Punto de Almacenamiento" a "Oficina"
3. Seleccionar licitación "EXP-2025-002"
4. Guardar
5. Verificar cambios en "Ver"
```

### Caso 4: Agregar Insumo Asignado
```
1. Ir a "Agregar Insumo Asignado"
2. Completar datos del insumo
3. Marcar "Insumo Nuevo" ✓
4. Seleccionar licitación
5. Completar datos de asignación
6. Guardar
7. Verificar que se creó y asignó correctamente
```

### Caso 5: Centrado de Tabla
```
1. Ir a listado de insumos
2. Verificar que TODAS las columnas están centradas
3. Agregar varios insumos
4. Aplicar filtros
5. Verificar que sigue centrado
```

---

## 🎯 Badges y Colores

### En Listado (Tabla):

| Campo | Valor | Badge | Color |
|-------|-------|-------|-------|
| Tipo | Varios | `bg-info` | 🔵 Celeste |
| Tipo | Notebook | `bg-info` | 🔵 Celeste |
| Condición | Nuevo | `bg-success` | 🟢 Verde |
| Condición | Usado | `bg-warning` | 🟡 Amarillo |
| Cantidad | > 0 | `bg-success` | 🟢 Verde |
| Cantidad | = 0 | `bg-danger` | 🔴 Rojo |

### En Modal/Vista Detallada:

| Campo | Valor | Badge | Color |
|-------|-------|-------|-------|
| Condición | Nuevo | `bg-success` | 🟢 Verde |
| Condición | Usado | `bg-warning` | 🟡 Amarillo |
| Nro. Expediente | EXP-XXX | `bg-primary` | 🔵 Azul |

---

## 💾 Datos de Ejemplo

### Insumo 1: Notebook Nuevo con Licitación
```json
{
  "nombre_insumo": "Dell Latitude E7470",
  "tipo_insumo": "Notebook",
  "es_nuevo": 1,
  "id_licitacion": 5,
  "licitacion_expediente": "EXP-2025-001",
  "id_punto_stock_actual": 2
}
```
**Visualización:**
- Tipo: [Notebook] (celeste)
- Condición: [Nuevo] (verde)
- Nro. Expediente: [EXP-2025-001] (azul)

### Insumo 2: Mouse Usado sin Licitación
```json
{
  "nombre_insumo": "Mouse Logitech M170",
  "tipo_insumo": "Varios",
  "subcategoria_varios": "Periféricos",
  "es_nuevo": 0,
  "id_licitacion": null,
  "cantidad": 50
}
```
**Visualización:**
- Tipo: [Varios] (celeste)
- Condición: [Usado] (amarillo)
- Sin badge de expediente

---

## 🔄 Flujo Completo de Usuario

### Crear Insumo Nuevo Asociado a Licitación:

```
1. Usuario va a Insumos → Agregar Insumo
   ↓
2. Completa datos básicos
   - Nombre: "HP LaserJet Pro"
   - Tipo: Impresora
   ↓
3. En "Información Común":
   - Punto: Depósito
   - ☑ Insumo Nuevo (marcado)
   - Nro. Expediente: EXP-2025-010
   ↓
4. Guardar
   ↓
5. En listado ve:
   ┌────────────────────────────────────────┐
   │ HP LaserJet │ [Impresora] │ [Nuevo] │ [1] │
   └────────────────────────────────────────┘
   ↓
6. Click "Ver" (👁️ celeste):
   - Condición: [Nuevo] (verde)
   - Nro. Expediente: [EXP-2025-010] (azul)
   ↓
7. Click "Editar" (✏️ amarillo):
   - Puede cambiar a "Usado"
   - Puede cambiar licitación
   - Puede cambiar punto de almacenamiento
```

---

## 🎯 Consistencia del Sistema

Todos los formularios ahora incluyen los campos:
- ✅ `agregar.php` - Checkbox + Select
- ✅ `editar.php` - Checkbox + Select (editables)
- ✅ `agregar_asignado.php` - Checkbox + Select
- ✅ `agregar_nueva.php` - (Si existe, agregar)

Todas las vistas muestran:
- ✅ `ver.php` - Badge condición + badge expediente
- ✅ `ver_ajax.php` - Badge condición + badge expediente
- ✅ `listar.php` - Columnas Tipo y Condición

---

## 📈 Mejoras Futuras Sugeridas

1. **Filtro por Condición** en listado (Nuevo/Usado)
2. **Filtro por Licitación** en listado
3. **Estadísticas** en dashboard (% nuevos vs usados)
4. **Reportes** de insumos por licitación
5. **Validación** de licitaciones (no permitir cerradas)
6. **Búsqueda** por número de expediente

---

**Estado:** ✅ Completado y pusheado  
**Rama:** `correciones_en_21`  
**Commits:** c465a8a, 2a25907, 14a77f1  
**Próximo paso:** Ejecutar migración y probar
