# Migración: Licitaciones → Ingresos

**Fecha:** 21 de octubre de 2025  
**Rama:** correciones_en_21  
**Commits:** 13e01fa, ff7745b, e8c3e02, 7133713

---

## 🎯 Resumen de Cambios

### **ANTES:**
- Tabla: `licitaciones`
- Campo: `cod_expediente` (solo para licitaciones)
- Función: Solo licitaciones

### **AHORA:**
- Tabla: `ingresos`
- Campo: `nro_referencia` (genérico)
- Campo nuevo: `tipo_ingreso` (fondos, compra_directa, licitacion, otros)
- Función: Múltiples tipos de ingreso

---

## 📊 Estructura de Tabla Ingresos

```sql
CREATE TABLE ingresos (
    id_ingreso INT AUTO_INCREMENT PRIMARY KEY,
    tipo_ingreso ENUM('fondos', 'compra_directa', 'licitacion', 'otros') DEFAULT 'licitacion',
    nro_referencia VARCHAR(100) NOT NULL,
    fecha_finalizacion DATE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### **Tipos de Ingreso:**

| Tipo | Label | Nro. Campo | Badge | Ejemplo |
|------|-------|------------|-------|---------|
| `fondos` | Fondos | Nro. de Nota | Verde | NOTA-2025-001 |
| `compra_directa` | Compra Directa | Nro. de Expediente | Celeste | EXP-CD-2025-001 |
| `licitacion` | Licitación | Nro. de Expediente | Amarillo | EXP-LIC-2025-001 |
| `otros` | Otros | Nro. de Referencia | Gris | REF-2025-001 |

---

## 🚀 PASO 1: Ejecutar Migración SQL

### **Opción A: Desde Navegador (Recomendado)**

```
http://tu-servidor/inventario_app/pages/admin/migracion_006_licitaciones_a_ingresos.php
```

**Verificar mensaje:**
```
✓ Tabla renombrada correctamente
✓ Columna 'id_ingreso' creada
✓ Columna 'tipo_ingreso' agregada
✓ Columna 'nro_referencia' creada
✓ Actualizada tabla 'insumos'
✓ Foreign key creada correctamente
✓ MIGRACIÓN COMPLETADA EXITOSAMENTE
```

---

### **Opción B: SQL Directo**

```sql
-- Ejecutar desde phpMyAdmin o línea de comandos
SOURCE /ruta/a/SQL_MIGRACION_INGRESOS.sql;
```

---

## 📋 Cambios en Base de Datos

### **Tabla `licitaciones` → `ingresos`**

| Cambio | Antes | Ahora |
|--------|-------|-------|
| Nombre tabla | `licitaciones` | `ingresos` |
| PK | `id_licitacion` | `id_ingreso` |
| Campo referencia | `cod_expediente` | `nro_referencia` |
| Campo nuevo | - | `tipo_ingreso` |

### **Tabla `insumos`**

| Cambio | Antes | Ahora |
|--------|-------|-------|
| FK | `id_licitacion` | `id_ingreso` |
| Referencia | `licitaciones(id_licitacion)` | `ingresos(id_ingreso)` |
| ON DELETE | - | SET NULL |

---

## 🔄 Cambios en Archivos PHP

### **Archivos NUEVOS:**

```
✅ pages/insumos/ingresos_listar.php      - Listado con modal de creación
✅ pages/insumos/ingresos_editar.php      - Edición multi-paso (antes licitaciones_nueva_pasos.php)
✅ ajax/ingresos_list.php                 - Endpoint listado
✅ ajax/ingresos_get.php                  - Endpoint detalle
✅ ajax/ingresos_save_simple.php          - Endpoint crear
✅ ajax/ingresos_delete.php               - Endpoint eliminar
✅ pages/admin/migracion_006_licitaciones_a_ingresos.php - Script migración
✅ SQL_MIGRACION_INGRESOS.sql             - SQL directo
```

### **Archivos MODIFICADOS:**

```
✅ pages/insumos/agregar.php              - Select con tipos de ingreso
✅ pages/insumos/editar.php               - Select con tipos de ingreso
✅ pages/insumos/agregar_asignado.php     - Select con tipos de ingreso
✅ pages/insumos/ver.php                  - JOIN con ingresos, label dinámico
✅ pages/insumos/ver_ajax.php             - JOIN con ingresos, label dinámico
✅ includes/header.php                    - Menú actualizado a "Ingresos"
```

---

## 🎨 Interfaz de Usuario

### **1. Crear Nuevo Ingreso (Modal)**

```
┌─────────────────────────────────────┐
│ ✚ Nuevo Ingreso                     │
├─────────────────────────────────────┤
│ Tipo de Ingreso *:                  │
│ [Fondos ▼]                          │
│   - Fondos                          │
│   - Compra Directa                  │
│   - Licitación                      │
│   - Otros                           │
│                                     │
│ Nro. de Nota *:                     │
│ [NOTA-2025-001]                     │
│ ↑ Label cambia según tipo           │
│                                     │
│ Fecha Finalización:                 │
│ [2025-12-31]                        │
│                                     │
│ Descripción:                        │
│ [Fondos para equipamiento...]       │
├─────────────────────────────────────┤
│         [Cancelar]    [💾 Guardar] │
└─────────────────────────────────────┘
```

---

### **2. Asignar Insumo a Ingreso**

**En agregar.php / editar.php:**

```
┌─────────────────────────────────────┐
│ Información Común                   │
├─────────────────────────────────────┤
│ Tipo de Ingreso:                    │
│ [Sin ingreso asociado ▼]            │
│                                     │
│ Fondos                              │
│   └─ NOTA-2025-001                  │
│   └─ NOTA-2025-002                  │
│                                     │
│ Compra Directa                      │
│   └─ EXP-CD-2025-001                │
│                                     │
│ Licitación                          │
│   └─ EXP-LIC-2025-001               │
│   └─ EXP-LIC-2025-002               │
│                                     │
│ Otros                               │
│   └─ REF-2025-001                   │
└─────────────────────────────────────┘
```

**Al seleccionar, el label cambia:**
- Fondos → "💵 Nro. de Nota (Fondos)"
- Licitación → "📄 Nro. de Expediente (Licitación)"
- Compra Directa → "🛒 Nro. de Expediente (Compra Directa)"
- Otros → "⋯ Nro. de Referencia (Otros)"

---

### **3. Ver Ingreso (Modal)**

```
┌─────────────────────────────────────┐
│ 👁 Detalle de Ingreso              │
├─────────────────────────────────────┤
│ ℹ Información del Ingreso           │
│ ┌───────────────────────────────┐   │
│ │ Tipo: [Fondos] (verde)        │   │
│ │ Nro. Nota: [NOTA-2025-001]    │   │
│ │ Fecha Fin: 31/12/2025         │   │
│ │ Descripción: ...              │   │
│ └───────────────────────────────┘   │
│                                     │
│ 📦 Insumos Asociados (15)          │
│ ┌───────────────────────────────┐   │
│ │ Nombre  │ Tipo  │Cant│Estado │   │
│ │ Mouse   │Varios │ 50 │Disp.  │   │
│ │ Teclado │Varios │ 30 │Disp.  │   │
│ └───────────────────────────────┘   │
├─────────────────────────────────────┤
│        [Cerrar]    [✏️ Editar]      │
└─────────────────────────────────────┘
```

---

### **4. Listado de Ingresos**

```
┌──────────────────────────────────────────────────────────┐
│ Nro. Referencia │   Tipo    │ Finalización │ Insumos │ Acciones │
├──────────────────────────────────────────────────────────┤
│ NOTA-2025-001   │ [Fondos]  │ 31/12/2025  │  [15]   │ [👁️][✏️][🗑️]│
│ EXP-LIC-2025-010│[Licitación]│ 15/11/2025  │  [8]    │ [👁️][✏️][🗑️]│
│ EXP-CD-2025-005 │[Compra D.]│ 20/10/2025  │  [3]    │ [👁️][✏️][🗑️]│
└──────────────────────────────────────────────────────────┘
```

---

## 🔧 Funciones JavaScript

### **Cambio Dinámico de Label:**

```javascript
// En agregar.php
function cambiarTipoIngreso() {
    const tipo = select.options[select.selectedIndex].getAttribute('data-tipo');
    
    switch(tipo) {
        case 'fondos':
            label.innerHTML = '<i class="fas fa-money-bill me-1"></i>Nro. de Nota (Fondos)';
            break;
        case 'licitacion':
            label.innerHTML = '<i class="fas fa-file-signature me-1"></i>Nro. de Expediente (Licitación)';
            break;
        // ...
    }
}
```

---

## 🧪 Casos de Prueba

### **Caso 1: Crear Ingreso por Fondos**

```
1. Ir a Insumos → Ingresos
2. Click "Nuevo Ingreso"
3. Tipo: Fondos
4. Nro. Nota: NOTA-2025-001
5. Fecha: 31/12/2025
6. Descripción: Fondos para equipamiento
7. Guardar
8. ✅ Aparece en listado con badge verde "Fondos"
```

### **Caso 2: Asignar Insumo a Ingreso**

```
1. Ir a Agregar Insumo
2. Completar datos del insumo
3. En "Tipo de Ingreso" seleccionar:
   Fondos → NOTA-2025-001
4. ✅ Label cambia a "Nro. de Nota (Fondos)"
5. Guardar
6. Ver insumo → Muestra "Nro. Nota: NOTA-2025-001"
```

### **Caso 3: Ver Ingreso con Insumos**

```
1. En listado, click "Ver" (👁️)
2. ✅ Modal muestra:
   - Tipo: [Fondos] (verde)
   - Nro. Nota: NOTA-2025-001
   - Insumos asociados con cantidad
```

### **Caso 4: Editar Ingreso**

```
1. Click "Editar" (✏️)
2. ✅ Abre interfaz multi-paso
3. Permite modificar datos + insumos
```

---

## 📁 Archivos del Sistema

### **Flujo Completo:**

```
1. Crear Ingreso:
   ingresos_listar.php (modal) 
   → ajax/ingresos_save_simple.php
   → Tabla ingresos
   
2. Asignar Insumo:
   agregar.php/editar.php (select tipo + ref)
   → ajax/insumos_save.php
   → Campo id_ingreso en insumos
   
3. Ver Ingreso:
   ingresos_listar.php (botón ver)
   → ajax/ingresos_get.php
   → Modal con datos + insumos
   
4. Editar Ingreso:
   ingresos_editar.php (multi-paso)
   → ajax/ingresos_save.php
   → Modificar datos + insumos
```

---

## ⚠️ INSTRUCCIONES DE MIGRACIÓN

### **PASO 1: Backup (OBLIGATORIO)**

```sql
-- Desde MySQL/MariaDB
mysqldump -u usuario -p base_datos licitaciones > backup_licitaciones.sql
mysqldump -u usuario -p base_datos insumos > backup_insumos.sql
```

### **PASO 2: Ejecutar Migración**

**Desde navegador:**
```
http://tu-servidor/inventario_app/pages/admin/migracion_006_licitaciones_a_ingresos.php
```

**Mensaje esperado:**
```
════════════════════════════════════════
✓ MIGRACIÓN COMPLETADA EXITOSAMENTE
════════════════════════════════════════

Cambios realizados:
• Tabla 'licitaciones' → 'ingresos'
• Columna 'id_licitacion' → 'id_ingreso'
• Columna 'cod_expediente' → 'nro_referencia'
• Campo nuevo 'tipo_ingreso' (fondos, compra_directa, licitacion, otros)
• Actualizada tabla 'insumos': id_licitacion → id_ingreso
• Foreign key actualizada con ON DELETE SET NULL
```

### **PASO 3: Verificar Datos**

```sql
-- Verificar tabla ingresos
SELECT * FROM ingresos LIMIT 5;

-- Verificar que todos los registros tienen tipo
SELECT tipo_ingreso, COUNT(*) 
FROM ingresos 
GROUP BY tipo_ingreso;

-- Resultado esperado:
-- licitacion | X  (todos los anteriores)

-- Verificar insumos asociados
SELECT COUNT(*) FROM insumos WHERE id_ingreso IS NOT NULL;
```

### **PASO 4: Actualizar Menú**

El menú ya está actualizado en `includes/header.php`:
- "Licitaciones" → "Ingresos"
- Link: `ingresos_listar.php`

---

## 🎯 Funcionalidad Nueva

### **Crear Ingreso - Modal Dinámico:**

1. **Seleccionar Tipo:**
   - Fondos / Compra Directa / Licitación / Otros

2. **Label Dinámico:**
   - El label del campo cambia según el tipo seleccionado
   - Fondos → "Nro. de Nota"
   - Licitación → "Nro. de Expediente"

3. **Validación:**
   - No permite duplicados de tipo+referencia
   - Campos obligatorios: tipo, nro_referencia

---

### **Asignar Insumo a Ingreso:**

1. **Select Agrupado:**
   ```
   Sin ingreso asociado
   ─────────────────
   Fondos
     └─ NOTA-2025-001
     └─ NOTA-2025-002
   Compra Directa
     └─ EXP-CD-2025-001
   Licitación
     └─ EXP-LIC-2025-001
   Otros
     └─ REF-2025-001
   ```

2. **Label Automático:**
   - Al seleccionar un ingreso
   - El label cambia según su tipo
   - Muestra icono correspondiente

---

### **Ver Ingreso:**

1. **Información:**
   - Badge coloreado según tipo
   - Nro. referencia con badge azul
   - Fecha de finalización
   - Descripción

2. **Insumos:**
   - Tabla con: Nombre | Tipo | Cantidad | Estado
   - Primera columna izquierda, resto centrado
   - Badges de colores

3. **Acciones:**
   - Botón "Editar" para ir a interfaz completa

---

## 🎨 Badges de Colores

### **Tipos de Ingreso:**

```css
Fondos         → bg-success  (verde)  💵
Compra Directa → bg-info     (celeste) 🛒
Licitación     → bg-warning  (amarillo) 📄
Otros          → bg-secondary (gris)   ⋯
```

### **En Vistas de Insumos:**

```html
<!-- Si es Fondos -->
<p><strong>Nro. Nota (Fondos):</strong> 
   <span class="badge bg-primary">NOTA-2025-001</span>
</p>

<!-- Si es Licitación -->
<p><strong>Nro. Expediente (Licitación):</strong> 
   <span class="badge bg-primary">EXP-LIC-2025-001</span>
</p>
```

---

## 📊 Datos de Ejemplo

### **Ingreso tipo Fondos:**

```json
{
  "id_ingreso": 1,
  "tipo_ingreso": "fondos",
  "nro_referencia": "NOTA-2025-001",
  "fecha_finalizacion": "2025-12-31",
  "descripcion": "Fondos asignados para compra de equipamiento informático",
  "num_insumos": 15
}
```

**Insumos asociados:**
- 50 Mouse USB
- 30 Teclados
- 20 Auriculares
- Total: 15 líneas de insumos

---

### **Ingreso tipo Licitación:**

```json
{
  "id_ingreso": 2,
  "tipo_ingreso": "licitacion",
  "nro_referencia": "EXP-LIC-2025-010",
  "fecha_finalizacion": "2025-11-15",
  "descripcion": "Licitación pública para adquisición de notebooks",
  "num_insumos": 8
}
```

**Insumos asociados:**
- 8 Notebooks Dell Latitude

---

## ⚡ Cambios Retrocompatibles

### **Datos Existentes:**

- ✅ Todas las licitaciones antiguas → tipo 'licitacion'
- ✅ cod_expediente migra a nro_referencia
- ✅ Insumos mantienen asociación
- ✅ No se pierde información

### **URLs:**

| Antes | Ahora | Estado |
|-------|-------|--------|
| `licitaciones_listar.php` | `ingresos_listar.php` | ✅ Nuevo |
| `licitaciones_nueva_pasos.php` | `ingresos_editar.php` | ✅ Copiado |
| Endpoints `/ajax/licitaciones_*` | `/ajax/ingresos_*` | ✅ Nuevos |

**Nota:** Los archivos antiguos `licitaciones_*` siguen existiendo por compatibilidad, pero el sistema usa los nuevos `ingresos_*`.

---

## 🔍 Verificación Post-Migración

### **1. Verificar Tabla Ingresos:**

```sql
DESCRIBE ingresos;
```

**Resultado esperado:**
```
id_ingreso       | INT | PK
tipo_ingreso     | ENUM('fondos','compra_directa','licitacion','otros')
nro_referencia   | VARCHAR(100)
fecha_finalizacion | DATE
descripcion      | TEXT
created_at       | TIMESTAMP
```

### **2. Verificar Foreign Key:**

```sql
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'insumos' 
  AND COLUMN_NAME = 'id_ingreso';
```

**Resultado esperado:**
```
fk_insumos_ingreso | id_ingreso | ingresos | id_ingreso
```

### **3. Verificar Datos Migrados:**

```sql
-- Ver distribución por tipo
SELECT tipo_ingreso, COUNT(*) as total 
FROM ingresos 
GROUP BY tipo_ingreso;

-- Ver insumos asociados
SELECT 
    ing.tipo_ingreso,
    ing.nro_referencia,
    COUNT(ins.id_insumo) as total_insumos
FROM ingresos ing
LEFT JOIN insumos ins ON ins.id_ingreso = ing.id_ingreso
GROUP BY ing.id_ingreso;
```

---

## 🚨 Troubleshooting

### **Error: "Table 'licitaciones' doesn't exist"**

**Causa:** La migración ya se ejecutó  
**Solución:** Verificar que existe tabla `ingresos`

```sql
SHOW TABLES LIKE 'ingresos';
```

---

### **Error: "Column 'id_licitacion' doesn't exist"**

**Causa:** La migración se completó  
**Solución:** Verificar que existe `id_ingreso`

```sql
SHOW COLUMNS FROM insumos LIKE 'id_ingreso';
```

---

### **Error: "Duplicate column name 'tipo_ingreso'"**

**Causa:** La migración se ejecutó parcialmente  
**Solución:** Ejecutar solo las partes faltantes o resetear

---

## 📝 Notas Importantes

1. **Retrocompatibilidad:**
   - Los archivos antiguos siguen existiendo
   - Puedes hacer rollback si es necesario

2. **Backup:**
   - SIEMPRE hacer backup antes de migrar
   - Guardar dumps de licitaciones e insumos

3. **Validación:**
   - No permite duplicados de tipo+referencia
   - Campo tipo es obligatorio
   - Campo nro_referencia es obligatorio

4. **Foreign Keys:**
   - ON DELETE SET NULL: si eliminas ingreso, insumos quedan sin asignar
   - ON UPDATE CASCADE: si cambias id_ingreso, se actualiza en insumos

---

## 🎉 Resumen

**Antes:**
- Solo licitaciones
- Campo fijo: cod_expediente

**Ahora:**
- 4 tipos de ingreso
- Campo dinámico según tipo
- Interfaz adaptativa
- Mejor organización

---

**Estado:** ✅ Código completo, listo para migrar  
**Próximo Paso:** Ejecutar migración en servidor  
**Rama:** `correciones_en_21`
