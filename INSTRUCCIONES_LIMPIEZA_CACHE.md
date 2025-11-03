# 🔄 Instrucciones: Limpiar Caché del Navegador

## ⚠️ PROBLEMA: Cambios no se ven después de git pull

Si después de hacer `git pull` no ves los cambios (botones, PDFs, campos, etc.), es **CACHÉ DEL NAVEGADOR**.

---

## ✅ SOLUCIÓN RÁPIDA (99% de los casos)

### **Windows/Linux:**
```
Ctrl + Shift + R
```
o
```
Ctrl + F5
```

### **Mac:**
```
Cmd + Shift + R
```

**Hazlo 2-3 veces seguidas** en la página que no se actualiza.

---

## 🔍 SOLUCIÓN COMPLETA (Si la rápida no funciona)

### **Chrome/Edge/Brave:**

1. Abre las **Herramientas de Desarrollo** (F12)
2. Haz **click derecho** en el botón de recargar (🔄)
3. Selecciona: **"Vaciar caché y recargar de forma forzada"**

O desde el menú:
1. Menú (⋮) → Más herramientas → Borrar datos de navegación
2. Selecciona: **"Imágenes y archivos en caché"**
3. Rango: **"Última hora"**
4. Click en **"Borrar datos"**

### **Firefox:**

1. Menú (☰) → Configuración
2. Privacidad y seguridad
3. Cookies y datos del sitio
4. Click en **"Limpiar datos"**
5. Selecciona: **"Contenido web en caché"**
6. Click en **"Limpiar"**

O rápido:
```
Ctrl + Shift + Supr
```
Selecciona "Caché" y limpia.

---

## 🧪 VERIFICACIÓN

Para confirmar que limpiaste la caché correctamente:

1. **Abre Herramientas de Desarrollo (F12)**
2. Ve a la pestaña **"Network"** o **"Red"**
3. Marca la casilla **"Disable cache"** o **"Deshabilitar caché"**
4. **Recarga la página (F5)**
5. Busca el archivo: `telecom_internet.php`
   - Debe mostrar: **Status 200**
   - Debe mostrar: **Size: XXX KB** (no "from cache")

---

## 🚨 SI AÚN NO FUNCIONA

### **1. Verifica que hiciste el pull correctamente:**
```bash
cd /ruta/al/proyecto
git status
git log --oneline -5
```

Deberías ver los últimos commits recientes.

### **2. Verifica la base de datos:**

Ejecuta en phpMyAdmin o terminal:
```sql
-- Verificar si los nuevos campos existen
SHOW COLUMNS FROM sedes_internet LIKE 'archivo_autorizacion';
SHOW COLUMNS FROM sedes_internet LIKE 'fecha_instalacion';
SHOW COLUMNS FROM sedes_internet LIKE 'fecha_baja';

-- Ver un registro específico con sus campos
SELECT id_internet, estado_servicio, archivo_autorizacion, 
       fecha_instalacion, fecha_solicitud_autorizacion, fecha_baja
FROM sedes_internet
WHERE id_internet = 1; -- Cambia el ID por uno que exista
```

Si `archivo_autorizacion` es **NULL**, significa que ese registro no tiene archivo asociado.

### **3. Verifica permisos:**
```bash
ls -la public/uploads/autorizaciones_internet/
```

Debe mostrar los archivos PDF con permisos de lectura.

---

## 📝 CHECKLIST

- [ ] Hice `git pull`
- [ ] Limpié la caché del navegador (Ctrl+Shift+R varias veces)
- [ ] Verifiqué en Network (F12) que los archivos cargan sin caché
- [ ] Verifiqué que los campos existen en la base de datos
- [ ] El registro que estoy viendo tiene `archivo_autorizacion` con valor (no NULL)

---

## ✅ COMPORTAMIENTO ESPERADO

### **En la Tabla:**

**Servicio con archivo de autorización (cualquier estado):**
```
Estado: Activo/Pendiente/De Baja
[📄 PDF] ← Botón visible
```

**Servicio sin archivo de autorización:**
```
Estado: Activo/Pendiente/De Baja
(sin botón PDF)
```

### **En Modal Ver Detalles:**

**Servicio que pasó por todas las etapas:**
```
Estado: Activo
  • Fecha de Instalación: 01/10/2025
  • Instancia: Autorización superior
  • Fecha de Solicitud: 15/09/2025
  
DOCUMENTACIÓN
  [📄 Descargar PDF]
```

---

## 💡 TIP PARA DESARROLLO

Mientras desarrollas, mantén las Herramientas de Desarrollo abiertas con **"Disable cache"** marcado. Esto evita problemas de caché durante el desarrollo.

---

**Si después de seguir estos pasos aún no funciona, envía capturas de:**
1. La consola del navegador (F12 → Console)
2. El resultado de `SHOW COLUMNS FROM sedes_internet`
3. Un `SELECT` del registro que estás probando
