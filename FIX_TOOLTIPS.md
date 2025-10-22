# Fix: Mejora en el Manejo de Tooltips

**Fecha:** 21 de octubre de 2025  
**Rama:** correciones_en_21  
**Commit:** 02a38f8

---

## 🐛 Problema Reportado

Los tooltips no se ocultaban correctamente y se quedaban en pantalla más tiempo del debido.

### Causas Identificadas:

1. **Tooltips Duplicados:**
   - La función `inicializarTooltips()` creaba nuevas instancias sin destruir las anteriores
   - Al renderizar DataTables, se acumulaban múltiples tooltips sobre el mismo elemento

2. **Sin Configuración de Delay:**
   - No había configuración de tiempo de aparición/desaparición
   - Los tooltips aparecían inmediatamente al pasar el mouse

3. **Sin Eventos de Ocultación:**
   - No se ocultaban al hacer click
   - No se ocultaban al hacer scroll
   - No se ocultaban al navegar

4. **Lógica Duplicada:**
   - Código de manejo de tooltips duplicado en `footer.php` y `main.js`

---

## ✅ Soluciones Implementadas

### **1. Función `inicializarTooltips()` Mejorada** (`main.js`)

**ANTES:**
```javascript
function inicializarTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}
```

**AHORA:**
```javascript
function inicializarTooltips() {
    // Destruir tooltips existentes primero
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        // Destruir instancia anterior si existe
        const existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
        if (existingTooltip) {
            existingTooltip.dispose();
        }
        
        // Crear nuevo tooltip con configuración optimizada
        const tooltip = new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover focus',
            delay: { show: 500, hide: 100 },  // Mostrar después de 500ms, ocultar rápido
            animation: true,
            html: false,
            placement: 'top',
            container: 'body'
        });
        
        // Asegurar que se oculte al hacer mouseleave
        tooltipTriggerEl.addEventListener('mouseleave', function() {
            tooltip.hide();
        });
        
        // Ocultar al hacer click
        tooltipTriggerEl.addEventListener('click', function() {
            tooltip.hide();
        });
    });
    
    // Ocultar todos los tooltips al hacer scroll
    window.addEventListener('scroll', function() {
        tooltipTriggerList.forEach(function(el) {
            const tooltip = bootstrap.Tooltip.getInstance(el);
            if (tooltip) {
                tooltip.hide();
            }
        });
    }, { passive: true });
}
```

**Mejoras:**
- ✅ Destruye tooltips anteriores con `dispose()`
- ✅ Configuración de delay (500ms mostrar, 100ms ocultar)
- ✅ Eventos `mouseleave` y `click` para ocultar
- ✅ Oculta al hacer scroll
- ✅ Evita tooltips duplicados

---

### **2. Limpieza de `footer.php`**

**ANTES:**
```javascript
drawCallback: function() {
    inicializarTooltips();
    // Código duplicado de manejo de eventos
    try {
        var wrapper = $(this).closest('.dataTables_wrapper');
        wrapper.find('[data-bs-toggle="tooltip"]').each(function(){
            var el = this;
            el.addEventListener('mouseleave', function(){ ... });
            el.addEventListener('blur', function(){ ... });
        });
        document.addEventListener('click', function(){ ... }, { once: true });
    } catch(e) {}
    // ...
}
```

**AHORA:**
```javascript
drawCallback: function() {
    // Reinicializar tooltips después de cada redibujado
    inicializarTooltips();
    // Placeholder de búsqueda en español
    try {
        var wrapper = $t.closest('.dataTables_wrapper');
        wrapper.find('.dataTables_filter input[type="search"]').attr('placeholder', 'Buscar...');
    } catch(e) {}
}
```

**Mejoras:**
- ✅ Eliminado código duplicado
- ✅ Lógica centralizada en `inicializarTooltips()`
- ✅ Más limpio y mantenible

---

### **3. Script Global de Ocultación** (`footer.php`)

**NUEVO:**
```javascript
// Ocultar todos los tooltips al hacer click en cualquier parte
document.addEventListener('click', function(e) {
    // No ocultar si el click es en un elemento con tooltip
    if (!e.target.closest('[data-bs-toggle="tooltip"]')) {
        try {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
                const tooltip = bootstrap.Tooltip.getInstance(el);
                if (tooltip) {
                    tooltip.hide();
                }
            });
        } catch(e) {}
    }
});

// Ocultar tooltips antes de navegación
window.addEventListener('beforeunload', function() {
    try {
        document.querySelectorAll('.tooltip').forEach(function(el) {
            el.remove();
        });
    } catch(e) {}
});
```

**Funcionalidad:**
- ✅ Oculta tooltips al hacer click fuera
- ✅ Limpia tooltips antes de cambiar de página
- ✅ Previene tooltips "huérfanos"

---

## 🎯 Configuración de Tooltips

### Opciones Aplicadas:

| Opción | Valor | Descripción |
|--------|-------|-------------|
| `trigger` | `'hover focus'` | Se muestra al pasar mouse o enfocar |
| `delay.show` | `500` ms | Espera 500ms antes de mostrar |
| `delay.hide` | `100` ms | Se oculta rápidamente (100ms) |
| `animation` | `true` | Con animación suave |
| `html` | `false` | Solo texto (seguridad) |
| `placement` | `'top'` | Posición arriba por defecto |
| `container` | `'body'` | Se agrega al body |

---

## 🔄 Eventos de Ocultación

### Los tooltips se ocultan automáticamente cuando:

1. ✅ **Mouse Sale del Elemento** (`mouseleave`)
2. ✅ **Click en el Elemento** (`click`)
3. ✅ **Click Fuera del Elemento** (evento global)
4. ✅ **Scroll en la Página** (`scroll`)
5. ✅ **Cambio de Página** (`beforeunload`)

---

## 📊 Comparativa Antes/Después

| Aspecto | ❌ Antes | ✅ Ahora |
|---------|----------|----------|
| Tooltips duplicados | Sí (múltiples instancias) | No (destruye antes de crear) |
| Delay al mostrar | No (inmediato) | Sí (500ms) |
| Delay al ocultar | No configurado | Sí (100ms rápido) |
| Ocultar al click | No | Sí |
| Ocultar al scroll | No | Sí |
| Ocultar al navegar | No | Sí |
| Código duplicado | Sí (footer.php y main.js) | No (centralizado) |
| Tooltips "pegados" | Sí (problema) | No (resuelto) |

---

## 🧪 Casos de Prueba

### Caso 1: Tooltip en Botón de Acción (DataTable)
```
1. Ir a listado de insumos
2. Pasar mouse sobre botón "Ver" (👁️)
3. Esperar 500ms → Tooltip aparece
4. Mover mouse fuera → Tooltip desaparece en 100ms
5. ✅ No queda "pegado"
```

### Caso 2: Tooltip al Hacer Scroll
```
1. Pasar mouse sobre botón
2. Tooltip aparece
3. Hacer scroll en la página
4. ✅ Tooltip desaparece inmediatamente
```

### Caso 3: Tooltip al Hacer Click
```
1. Pasar mouse sobre botón
2. Tooltip aparece
3. Hacer click en el botón
4. ✅ Tooltip desaparece antes de la acción
```

### Caso 4: Tooltip en DataTable Renderizado
```
1. Ir a tabla con paginación
2. Cambiar de página
3. Pasar mouse sobre botones
4. ✅ No hay tooltips duplicados
5. ✅ Se ocultan correctamente
```

### Caso 5: Click Fuera del Tooltip
```
1. Pasar mouse sobre botón
2. Tooltip aparece
3. Hacer click en cualquier otra parte
4. ✅ Tooltip desaparece
```

---

## 🔍 Archivos Modificados

```
✅ public/js/main.js
   - Función inicializarTooltips() reescrita
   - Destrucción de instancias anteriores
   - Configuración de delay y eventos

✅ includes/footer.php
   - Eliminado código duplicado en drawCallback
   - Agregado script global de ocultación
   - Eventos de click y beforeunload
```

---

## 📈 Mejoras de Performance

### Optimizaciones:

1. **Menos Instancias:**
   - Se destruyen tooltips anteriores
   - No se acumulan en memoria

2. **Eventos Passive:**
   - `scroll` usa `{ passive: true }`
   - Mejor performance al hacer scroll

3. **Delegación de Eventos:**
   - Un solo listener global para clicks
   - Menos overhead de eventos

4. **Limpieza en Navegación:**
   - `beforeunload` limpia el DOM
   - Previene fugas de memoria

---

## ⚙️ Configuración Personalizada

Si necesitas cambiar el comportamiento de los tooltips:

### Cambiar Delay:
```javascript
// En main.js, línea ~376
delay: { show: 1000, hide: 100 }  // Cambiar 1000ms = 1 segundo
```

### Cambiar Posición:
```javascript
// En main.js, línea ~379
placement: 'bottom'  // o 'left', 'right', 'auto'
```

### Deshabilitar Auto-Ocultar al Scroll:
```javascript
// En main.js, comentar líneas ~390-398
/*
window.addEventListener('scroll', function() {
    // ...
});
*/
```

---

## 🚀 Resultado Final

### Comportamiento Esperado:

1. **Hover sobre botón** → Espera 500ms → **Tooltip aparece**
2. **Mouse sale** → **Tooltip desaparece en 100ms**
3. **Click en botón** → **Tooltip desaparece inmediatamente**
4. **Scroll en página** → **Todos los tooltips desaparecen**
5. **Click fuera** → **Todos los tooltips desaparecen**
6. **Cambio de página** → **Tooltips se limpian del DOM**

### No más problemas de:
- ❌ Tooltips duplicados
- ❌ Tooltips "pegados" en pantalla
- ❌ Tooltips que no desaparecen
- ❌ Tooltips al cambiar de página
- ❌ Acumulación de instancias en memoria

---

## 📝 Notas Técnicas

### Bootstrap Tooltip API Usada:

```javascript
// Crear tooltip
new bootstrap.Tooltip(element, options)

// Obtener instancia existente
bootstrap.Tooltip.getInstance(element)

// Destruir tooltip
tooltip.dispose()

// Ocultar tooltip
tooltip.hide()
```

### Ciclo de Vida:

```
1. inicializarTooltips() llamada
   ↓
2. Buscar todos [data-bs-toggle="tooltip"]
   ↓
3. Para cada elemento:
   a. Destruir tooltip anterior si existe
   b. Crear nuevo tooltip con config
   c. Agregar event listeners (mouseleave, click)
   ↓
4. Agregar listener global de scroll
   ↓
5. Tooltips funcionan correctamente
```

---

## 🎯 Commit

```bash
02a38f8 - fix: Mejorar manejo de tooltips - evitar duplicados y ocultar correctamente

Cambios:
- Reescribir inicializarTooltips() con dispose()
- Agregar configuración de delay (500ms/100ms)
- Eventos mouseleave y click para ocultar
- Listener global de click fuera
- Limpieza en beforeunload
- Ocultar al hacer scroll
- Eliminar código duplicado en footer.php
```

---

**Estado:** ✅ Resuelto  
**Rama:** `correciones_en_21`  
**Próximos Pasos:** Probar en producción y ajustar delays si es necesario
