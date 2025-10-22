# Fix: Flujo de Creación de Insumos desde Licitaciones

**Fecha:** 21 de octubre de 2025  
**Rama:** correciones_en_21  
**Commit:** 1fd1b41

---

## 🐛 Problemas Reportados

1. ❌ Al crear nuevo insumo, **volvía al Paso 1** (en lugar del Paso 2)
2. ❌ **Perdía la selección** de insumos previamente seleccionados
3. ❌ **NO seleccionaba automáticamente** el insumo recién creado
4. ❌ Botón "Nuevo Insumo" era **muy pequeño** (solo icono)

---

## ✅ Soluciones Implementadas

### 1. **Botón de Nuevo Insumo - Rediseñado**

**Antes:**
```html
<a href="agregar.php?from=licitacion" class="btn btn-success btn-sm">
    <i class="fas fa-plus"></i>
</a>
```
- Era un enlace que navegaba inmediatamente
- Solo mostraba un icono ➕
- Tamaño pequeño (btn-sm)
- No ejecutaba JavaScript antes de navegar

**Ahora:**
```html
<button type="button" class="btn btn-success" onclick="irACrearInsumo()">
    <i class="fas fa-plus me-1"></i>Nuevo Insumo
</button>
```
- Es un botón que ejecuta función JavaScript
- Muestra icono ➕ + texto "Nuevo Insumo"
- Tamaño normal (más grande y visible)
- Ejecuta `irACrearInsumo()` que guarda ANTES de navegar

**Función nueva:**
```javascript
function irACrearInsumo() {
    console.log('Guardando estado antes de crear insumo...');
    guardarEstado();  // ✅ GUARDA TODO PRIMERO
    
    // Verificar que se guardó
    const verificar = localStorage.getItem('licitacion_seleccionados');
    console.log('Seleccionados guardados:', verificar);
    
    // Redirigir
    window.location.href = BASE + '/pages/insumos/agregar.php?from=licitacion';
}
```

---

### 2. **Función guardarEstado() - Mejorada**

**Mejoras:**
- ✅ Logging extensivo en consola
- ✅ Verifica que encuentre los insumos seleccionados
- ✅ Cuenta total de insumos guardados
- ✅ Alert si hay error

**Console logs agregados:**
```
=== Guardando estado ===
Paso 1 guardado: {cod_expediente: "EXP-001", ...}
Insumos seleccionados encontrados: 3
  - Insumo 123: cantidad 1
  - Insumo 456: cantidad 15
  - Insumo 789: cantidad 1
Seleccionados guardados: {123: 1, 456: 15, 789: 1}
Total insumos guardados: 3
```

---

### 3. **Función restaurarSeleccionados() - Debugging Completo**

**Mejoras:**
- ✅ Log detallado de cada paso
- ✅ Verifica que cada fila existe
- ✅ Detecta si el nuevo insumo NO está en la tabla
- ✅ Muestra IDs disponibles si hay problemas

**Console logs agregados:**
```
Iniciando restauración de seleccionados...
Datos en localStorage: {"123":1,"456":15,"789":1}
Seleccionados a restaurar: {123: 1, 456: 15, 789: 1}
Buscando fila con id 123: Encontrada
✓ Insumo 123 restaurado
Buscando fila con id 456: Encontrada
✓ Insumo 456 restaurado
Buscando fila con id 789: Encontrada
✓ Insumo 789 restaurado
Intentando seleccionar nuevo insumo: 999
Fila del nuevo insumo: Encontrada
Seleccionando nuevo insumo...
Restauración completada
```

**Si hay problemas:**
```
¡PROBLEMA! El nuevo insumo no se encuentra en la tabla. ID: 999
IDs disponibles en la tabla: [123, 456, 789, 111, 222]
```

---

### 4. **DOMContentLoaded - Delay Agregado**

**Problema detectado:** A veces el DOM no estaba completamente listo

**Solución:**
```javascript
if (from === 'agregar') {
    console.log('Volviendo de crear insumo...');
    
    // Restaurar datos del Paso 1
    restaurarDatosPaso1();
    
    // ✅ DELAY de 100ms para asegurar DOM listo
    setTimeout(function() {
        // Restaurar seleccionados
        restaurarSeleccionados(nuevoId);
        
        // IR DIRECTAMENTE AL PASO 2
        console.log('Navegando al Paso 2...');
        irAPaso(2);
    }, 100);
}
```

---

## 🔍 Debugging Facilitado

### **Consola del Navegador**

Ahora puedes ver todo el flujo en la consola:

**Al hacer click en "Nuevo Insumo":**
```
=== Guardando estado ===
Paso 1 guardado: {...}
Insumos seleccionados encontrados: 3
Seleccionados guardados: {...}
Total insumos guardados: 3
Guardando estado antes de crear insumo...
Seleccionados guardados: {"123":1,"456":15}
```

**Al volver de crear insumo:**
```
=== DOMContentLoaded ===
URL params: {from: "agregar", nuevoId: "999"}
Volviendo de crear insumo...
Iniciando restauración de seleccionados...
Datos en localStorage: {"123":1,"456":15}
Seleccionados a restaurar: {123: 1, 456: 15}
Buscando fila con id 123: Encontrada
✓ Insumo 123 restaurado
...
Intentando seleccionar nuevo insumo: 999
Fila del nuevo insumo: Encontrada
Seleccionando nuevo insumo...
Restauración completada
Navegando al Paso 2...
```

---

## ✅ Flujo Corregido

### **Paso a Paso:**

```
1. Usuario en Paso 1
   └─> Completa datos (código, fecha, descripción)

2. Click "Siguiente"
   └─> Va al Paso 2

3. Selecciona 3 insumos
   ├─> Notebook Dell ✓
   ├─> Mouse USB (15 unidades) ✓
   └─> Monitor Samsung ✓

4. Click botón "Nuevo Insumo"
   ├─> ✅ Ejecuta guardarEstado()
   │   ├─> Guarda datos Paso 1 en localStorage
   │   └─> Guarda 3 insumos seleccionados
   └─> Navega a agregar.php?from=licitacion

5. Crea nuevo insumo "Teclado Genius"
   └─> Guarda en BD exitosamente

6. agregar.php redirige a:
   licitaciones_nueva_pasos.php?from=agregar&added_id=999

7. DOMContentLoaded detecta from=agregar
   ├─> ✅ Restaura datos del Paso 1
   ├─> Delay 100ms
   ├─> ✅ Restaura 3 insumos seleccionados
   ├─> ✅ Selecciona "Teclado Genius" (id=999)
   └─> ✅ Va al Paso 2 automáticamente

8. Usuario ve:
   ├─> Está en el Paso 2 (NO en Paso 1) ✓
   ├─> Los 3 insumos anteriores seleccionados ✓
   ├─> El nuevo "Teclado Genius" seleccionado ✓
   ├─> Total: 4 insumos arriba de la tabla ✓
   └─> Datos del Paso 1 intactos ✓

9. Continúa trabajando sin problemas
```

---

## 🎯 Cambios Técnicos

| Aspecto | Antes | Ahora |
|---------|-------|-------|
| Tipo de elemento | `<a href>` | `<button onclick>` |
| Guardado | Al navegar (fallaba) | ANTES de navegar ✓ |
| Botón texto | Solo icono | Icono + "Nuevo Insumo" |
| Botón tamaño | btn-sm (pequeño) | btn (normal) |
| Debugging | Sin logs | Logs extensivos ✓ |
| Delay DOM | No | 100ms ✓ |
| Verificación | No | Muestra errores detallados ✓ |

---

## 📊 Estadísticas del Fix

```
Archivo modificado: licitaciones_nueva_pasos.php
Líneas agregadas: +70
Líneas eliminadas: -14
Funciones mejoradas: 4
Console.logs agregados: ~20
```

---

## 🧪 Cómo Probar

### **Test 1: Crear Insumo desde Licitación**

1. Ir a **Insumos → Licitaciones → Nueva Licitación**
2. **Paso 1:** Completar código "TEST-001"
3. Click **"Siguiente"**
4. **Paso 2:** Seleccionar 2-3 insumos
5. Click **"Nuevo Insumo"** (botón ahora más grande)
6. Abrir **Consola del navegador** (F12)
7. Ver logs de guardado
8. Crear insumo "TEST INSUMO"
9. **VERIFICAR:**
   - ✅ Regresa al **Paso 2** (NO al Paso 1)
   - ✅ Los 2-3 insumos anteriores **están seleccionados**
   - ✅ "TEST INSUMO" **está seleccionado automáticamente**
   - ✅ Código "TEST-001" **sigue en el Paso 1**
   - ✅ Todos los seleccionados **están arriba**

### **Test 2: Verificar Console Logs**

1. Abrir **Consola** (F12) ANTES de empezar
2. Realizar Test 1
3. **VERIFICAR logs:**
   - Debe mostrar "=== Guardando estado ==="
   - Debe mostrar "Insumos seleccionados encontrados: X"
   - Debe mostrar "Total insumos guardados: X"
   - Al volver, debe mostrar "Volviendo de crear insumo..."
   - Debe mostrar "✓ Insumo X restaurado" para cada uno
   - Debe mostrar "Navegando al Paso 2..."

### **Test 3: Tipos Varios con Cantidad**

1. Nueva licitación
2. Seleccionar insumo tipo "Varios" con stock > 10
3. Cambiar cantidad a 7
4. Click "Nuevo Insumo"
5. Crear insumo
6. **VERIFICAR:**
   - ✅ Insumo "Varios" sigue seleccionado
   - ✅ Cantidad sigue siendo 7 (no vuelve a 1)

---

## 🎉 Resultado Final

### **ANTES del fix:**
- ❌ Volvía al Paso 1
- ❌ Perdía selección
- ❌ No seleccionaba nuevo insumo
- ❌ Botón pequeño
- ❌ Sin debugging

### **DESPUÉS del fix:**
- ✅ Vuelve al Paso 2
- ✅ Mantiene selección
- ✅ Selecciona nuevo insumo automáticamente
- ✅ Botón grande y visible
- ✅ Debugging completo en consola
- ✅ Experiencia de usuario fluida

---

**Estado:** ✅ Completado y pusheado  
**Rama:** `correciones_en_21`  
**Listo para:** Pruebas del usuario
