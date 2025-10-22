# Instrucciones para Migración 005 - Campo es_nuevo

**Fecha:** 21 de octubre de 2025  
**Rama:** correciones_en_21  
**Commit:** 14a77f1

---

## ⚠️ IMPORTANTE: Ejecutar Migración Antes de Usar

Antes de usar las nuevas funcionalidades, **DEBES ejecutar la migración** para agregar el campo `es_nuevo` a la base de datos.

---

## 🔧 Cómo Ejecutar la Migración

### Opción 1: Desde el Navegador (Recomendado)

1. Abre tu navegador
2. Ve a la URL:
   ```
   http://tu-servidor/inventario_app/pages/admin/migracion_005_campo_es_nuevo.php
   ```
3. Verás un mensaje de confirmación:
   ```
   ✓ Columna 'es_nuevo' agregada correctamente
   ✓ Migración completada exitosamente
   ```
4. Click en "Volver a Insumos"

### Opción 2: Desde MySQL Command Line

```sql
USE inventario_insumos_v1;

ALTER TABLE insumos 
ADD COLUMN es_nuevo TINYINT(1) DEFAULT 1 
COMMENT '1=Nuevo, 0=Usado' 
AFTER id_licitacion;
```

---

## 📋 ¿Qué Hace la Migración?

La migración agrega un nuevo campo a la tabla `insumos`:

```sql
ALTER TABLE insumos 
ADD COLUMN es_nuevo TINYINT(1) DEFAULT 1 
COMMENT '1=Nuevo, 0=Usado' 
AFTER id_licitacion;
```

**Detalles:**
- **Nombre:** `es_nuevo`
- **Tipo:** `TINYINT(1)` (equivalente a BOOLEAN)
- **Valores:** `1` = Nuevo, `0` = Usado
- **Default:** `1` (todos los insumos existentes se marcarán como "Nuevos")
- **Posición:** Después de `id_licitacion`
- **Comment:** '1=Nuevo, 0=Usado'

---

## ✨ Nuevas Funcionalidades

### 1. Campo "Insumo Nuevo" (Checkbox)

**Ubicación:** Formulario de Agregar Insumo → Información Común → Debajo de "Punto de Almacenamiento"

**Características:**
- Checkbox marcado por defecto (nuevo)
- Label: "Insumo Nuevo (desmarcar si es usado)"
- Si está marcado: guarda 1 en BD
- Si está desmarcado: guarda 0 en BD

**Ejemplo:**
```
┌─────────────────────────────────┐
│ Punto de Almacenamiento *      │
│ [Depósito ▼]                   │
│                                 │
│ ☑ Insumo Nuevo                 │
│   (desmarcar si es usado)      │
└─────────────────────────────────┘
```

### 2. Campo "Nro. Expediente" (Select)

**Ubicación:** Formulario de Agregar Insumo → Información Común → Debajo de "Insumo Nuevo"

**Características:**
- Select con todas las licitaciones disponibles
- Opcional (puede quedar "Sin licitación")
- Muestra: cod_expediente de cada licitación
- Ordenadas por fecha (más recientes primero)

**Ejemplo:**
```
┌─────────────────────────────────┐
│ Nro. Expediente                 │
│ [Sin licitación ▼]              │
│   - Sin licitación              │
│   - EXP-2025-001                │
│   - EXP-2025-002                │
│   - EXP-2024-150                │
└─────────────────────────────────┘
```

### 3. Visualización en "Ver Insumo"

**Nuevos campos mostrados:**

```
┌───────────────────────────────────┐
│ Información General               │
├───────────────────────────────────┤
│ ...                               │
│ Punto de Stock: Depósito          │
│ Condición: [Nuevo]      ← NUEVO   │
│ Nro. Expediente: [EXP-2025-001]  │ ← NUEVO (si existe)
└───────────────────────────────────┘
```

**Badges:**
- **Nuevo:** Verde (`bg-success`)
- **Usado:** Amarillo (`bg-warning`)
- **Expediente:** Azul (`bg-primary`)

---

## 🧪 Pruebas Recomendadas

### Test 1: Migración Exitosa

1. Ejecutar migración
2. Verificar mensaje de éxito
3. Abrir PHPMyAdmin o MySQL
4. Verificar que tabla `insumos` tiene columna `es_nuevo`
5. Verificar que insumos existentes tienen `es_nuevo = 1`

### Test 2: Agregar Insumo Nuevo

1. Ir a **Insumos → Agregar Insumo**
2. Completar datos del insumo
3. **Verificar:** Checkbox "Insumo Nuevo" está marcado
4. **Verificar:** Select "Nro. Expediente" está visible
5. Guardar insumo
6. Ir a **Ver insumo**
7. **Verificar:** Muestra badge "Nuevo" verde
8. En BD verificar: `es_nuevo = 1`

### Test 3: Agregar Insumo Usado

1. Ir a **Insumos → Agregar Insumo**
2. Completar datos del insumo
3. **Desmarcar** checkbox "Insumo Nuevo"
4. Guardar insumo
5. Ir a **Ver insumo**
6. **Verificar:** Muestra badge "Usado" amarillo
7. En BD verificar: `es_nuevo = 0`

### Test 4: Asociar a Licitación

1. **Primero:** Crear una licitación (ej: EXP-TEST-001)
2. Ir a **Insumos → Agregar Insumo**
3. Completar datos del insumo
4. En "Nro. Expediente" seleccionar: **EXP-TEST-001**
5. Guardar insumo
6. Ir a **Ver insumo**
7. **Verificar:** Muestra badge azul "EXP-TEST-001"
8. En BD verificar: `id_licitacion = (ID de la licitación)`

### Test 5: Insumos Existentes

1. Ir a **Insumos → Listar**
2. Ver cualquier insumo existente
3. **Verificar:** Todos muestran "Nuevo" (por el DEFAULT 1)
4. Editar un insumo y cambiar a "Usado"
5. Verificar que se guarda correctamente

---

## 📊 Estructura de Base de Datos

### Tabla: `insumos`

**Campos nuevos:**
```sql
id_licitacion INT NULL
es_nuevo TINYINT(1) DEFAULT 1  ← NUEVO
```

**Relación con `licitaciones`:**
```sql
FOREIGN KEY (id_licitacion) 
REFERENCES licitaciones(id_licitacion) 
ON DELETE SET NULL
```

---

## 🔄 Rollback (Si es Necesario)

Si necesitas revertir la migración:

```sql
ALTER TABLE insumos DROP COLUMN es_nuevo;
```

**⚠️ ADVERTENCIA:** Esto eliminará todos los datos de este campo.

---

## 📝 Notas Adicionales

### Valores por Defecto

- **Insumos nuevos:** `es_nuevo = 1` (checkbox marcado)
- **Insumos existentes:** `es_nuevo = 1` (por el DEFAULT en migración)
- **Licitación:** `id_licitacion = NULL` (opcional)

### Lógica de Guardado

```php
// En agregar.php
$esNuevo = isset($_POST['es_nuevo']) && $_POST['es_nuevo'] == '1' ? 1 : 0;
$idLicitacion = !empty($_POST['id_licitacion']) ? (int)$_POST['id_licitacion'] : null;
```

### Visualización

```php
// Badge Nuevo/Usado
$badge = ($insumo['es_nuevo'] ?? 1) ? 'bg-success' : 'bg-warning';
$texto = ($insumo['es_nuevo'] ?? 1) ? 'Nuevo' : 'Usado';

// Badge Expediente (si existe)
if (!empty($insumo['licitacion_expediente'])) {
    echo '<span class="badge bg-primary">' . $expediente . '</span>';
}
```

---

## ✅ Checklist de Implementación

- [x] Crear archivo de migración
- [x] Modificar formulario agregar.php
- [x] Actualizar lógica de INSERT
- [x] Modificar consulta en listar.php
- [x] Modificar consulta en ver.php
- [x] Agregar visualización de campos
- [x] Commit y push a repositorio
- [ ] **Ejecutar migración en servidor**
- [ ] Probar funcionalidad completa

---

**Estado:** ✅ Código completo y pusheado  
**Próximo paso:** Ejecutar migración en tu servidor  
**Rama:** `correciones_en_21`
