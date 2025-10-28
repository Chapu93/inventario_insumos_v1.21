# Guía de Tooltips Unificados

## 📋 Resumen

Todos los tooltips del proyecto ahora tienen un estilo y comportamiento consistente, optimizado para funcionar correctamente en todas las situaciones.

---

## 🎨 Estilo Unificado

### **Apariencia:**
- **Fondo**: Gris oscuro (`#2c3e50`)
- **Texto**: Blanco (`#ffffff`)
- **Fuente**: 0.875rem (14px)
- **Peso**: 500 (semi-bold)
- **Padding**: 6px 12px
- **Border-radius**: 4px
- **Sombra**: 0 4px 12px rgba(0, 0, 0, 0.3)
- **Max-width**: 250px
- **Animación**: Fade suave (0.15s)

---

## ⚙️ Configuración Unificada

### **Parámetros de Bootstrap Tooltip:**

```javascript
{
    trigger: 'hover',              // Solo al pasar el mouse
    delay: { show: 300, hide: 100 }, // 300ms para mostrar, 100ms para ocultar
    animation: true,               // Animación suave
    html: false,                   // Texto plano (seguridad)
    placement: 'top',              // Por defecto arriba
    container: 'body',             // Evita problemas de overflow
    boundary: 'viewport',          // Se mantiene en viewport
    fallbackPlacements: ['bottom', 'left', 'right'], // Alternativas
    customClass: 'custom-tooltip', // Clase personalizada
    sanitize: true                 // Sanitización de contenido
}
```

---

## 💻 Cómo Usar Tooltips

### **HTML Básico:**

```html
<!-- Tooltip simple -->
<button class="btn btn-primary" 
        data-bs-toggle="tooltip" 
        title="Este es el tooltip">
    Botón
</button>

<!-- Tooltip con aria-label (accesibilidad) -->
<button class="btn btn-info" 
        data-bs-toggle="tooltip" 
        title="Ver detalles"
        aria-label="Ver detalles del insumo">
    <i class="fas fa-eye"></i>
</button>
```

### **PHP (en endpoints AJAX):**

```php
$btn = '<button type="button" 
               class="btn btn-sm btn-info" 
               onclick="verItem(' . $id . ')" 
               data-bs-toggle="tooltip" 
               title="Ver detalles">
            <i class="fas fa-eye"></i>
        </button>';
```

---

## 🔧 Inicialización

### **Automática:**
La función `inicializarTooltips()` se llama automáticamente:

1. **Al cargar la página** (`document.ready`)
2. **Después de cada redibujado de DataTables** (`drawCallback`)
3. **Después de cargar contenido AJAX dinámico**

### **Manual (cuando sea necesario):**

```javascript
// Después de agregar contenido dinámico
$('#contenedor').html(nuevoContenido);
inicializarTooltips(); // Re-inicializar tooltips
```

---

## 🎯 Características y Mejoras

### **1. Prevención de Duplicados:**
```javascript
// Destruye tooltips existentes antes de crear nuevos
const instance = bootstrap.Tooltip.getInstance(el);
if (instance) {
    instance.dispose();
}
```

### **2. Auto-Ocultación:**
- ✅ Al hacer **scroll** (con debounce de 50ms)
- ✅ Al hacer **click fuera** del elemento
- ✅ Al **cambiar de página**

### **3. Posicionamiento Inteligente:**
- ✅ **Placement**: `top` por defecto
- ✅ **Fallback**: Si no cabe arriba, prueba `bottom`, `left`, `right`
- ✅ **Boundary**: `viewport` (nunca se sale de la pantalla)
- ✅ **Container**: `body` (evita problemas de overflow)

### **4. Z-Index Apropiado:**
- ✅ Normal: `z-index: 1070` (por encima del contenido)
- ✅ Con modal abierto: `z-index: 1080` (por encima del modal)

### **5. Performance:**
- ✅ Event listeners con `{ passive: true }`
- ✅ Debounce en scroll (50ms)
- ✅ Try-catch para evitar errores

---

## 🐛 Solución de Problemas

### **Problema: Tooltip no aparece**

**Causas comunes:**
1. El elemento fue agregado dinámicamente después de cargar la página
2. DataTables redibujó la tabla

**Solución:**
```javascript
// Llamar después de agregar contenido
inicializarTooltips();
```

### **Problema: Tooltips duplicados**

**Causa:** Tooltip inicializado múltiples veces en el mismo elemento

**Solución:** La función ahora destruye tooltips existentes automáticamente

### **Problema: Tooltip no se oculta**

**Causa:** Evento de scroll o click no registrado

**Solución:** Ya implementado en main.js - los tooltips se ocultan automáticamente

### **Problema: Tooltip se corta o sale de pantalla**

**Solución:** Configuración con `boundary: 'viewport'` y fallback placements

---

## 📊 DataTables Integration

Los tooltips en DataTables se reinicializan automáticamente:

```javascript
$('#miTabla').DataTable({
    // ... configuración ...
    drawCallback: function() {
        inicializarTooltips(); // ← Ya incluido en el proyecto
    }
});
```

---

## ✅ Checklist de Implementación

Para agregar tooltips en un nuevo componente:

- [ ] Agregar `data-bs-toggle="tooltip"` al elemento
- [ ] Agregar `title="Texto del tooltip"`
- [ ] Agregar `aria-label` para accesibilidad (opcional pero recomendado)
- [ ] Si es contenido dinámico, llamar `inicializarTooltips()` después de agregarlo
- [ ] Si es DataTable, asegurar `drawCallback` con `inicializarTooltips()`

---

## 🎨 Ejemplos de Uso en el Proyecto

### **1. Botones de Acción (asignaciones):**
```php
'<button type="button" 
        class="btn btn-sm btn-info" 
        onclick="abrirVerAsignacion(\'' . $remito . '\')" 
        data-bs-toggle="tooltip" 
        title="Ver asignación"
        aria-label="Ver asignación">
    <i class="fas fa-eye"></i>
</button>'
```

### **2. Botones de Insumos:**
```php
'<button type="button" 
        class="btn btn-sm btn-warning" 
        onclick="verInsumo(' . $id . ')" 
        data-bs-toggle="tooltip" 
        title="Ver detalles"
        aria-label="Ver detalles del insumo">
    <i class="fas fa-eye"></i>
</button>'
```

### **3. Botones Deshabilitados:**
```php
'<button type="button" 
        class="btn btn-sm btn-warning" 
        disabled 
        data-bs-toggle="tooltip" 
        title="Sin ítems para devolver"
        aria-label="Devolver insumos">
    <i class="fas fa-undo"></i>
</button>'
```

---

## 📝 Notas Importantes

1. **Texto simple**: Los tooltips solo soportan texto plano (html: false)
2. **No abuses**: Usar solo para información complementaria breve
3. **Accesibilidad**: Siempre incluir aria-label en botones con iconos
4. **Performance**: La función está optimizada con debounce y passive listeners
5. **Consistencia**: Todos los tooltips del proyecto usan el mismo estilo

---

## 🔄 Migración de Tooltips Antiguos

Si encuentras tooltips con estilos antiguos, actualiza así:

### **Antes (inconsistente):**
```html
<button title="Ver" data-toggle="tooltip">Ver</button>
```

### **Después (unificado):**
```html
<button data-bs-toggle="tooltip" 
        title="Ver detalles"
        aria-label="Ver detalles">
    <i class="fas fa-eye"></i>
</button>
```

---

## 📞 Soporte

La función `inicializarTooltips()` está en:
- **Archivo**: `public/js/main.js` (líneas 366-434)
- **Estilos**: `public/css/style.css` (líneas 77-133)

Para modificar el comportamiento global, editar estos archivos.

---

**Implementado**: 2025-10-27  
**Versión**: 1.0  
**Estado**: ✅ Producción
