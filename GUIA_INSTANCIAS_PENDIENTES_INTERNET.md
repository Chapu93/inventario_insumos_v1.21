# 📡 Guía: Instancias de Estado Pendiente - Internet por Sede

## 📋 Descripción

Se ha implementado un sistema de **instancias** para los servicios de internet que se encuentran en estado **Pendiente**. Esto permite un seguimiento más detallado del proceso de contratación/gestión del servicio.

---

## 🎯 Instancias Disponibles

Cuando un servicio de internet está en estado **Pendiente**, debes especificar en qué instancia se encuentra:

### 1️⃣ **Solicitud de presupuesto**
- El servicio está en etapa de cotización
- Se está solicitando información de precios a proveedores
- **No requiere campos adicionales**

### 2️⃣ **Autorización superior** ⭐
- El servicio requiere aprobación de niveles superiores
- **Requiere campos adicionales:**
  - ✅ **Fecha de solicitud** (obligatoria)
  - ✅ **Archivo PDF de autorización** (obligatorio)
    - Solo formato PDF
    - Tamaño máximo: 5MB
    - Se almacena de forma segura en el servidor

### 3️⃣ **Servicio tarifado**
- El servicio ya está presupuestado
- Se conoce el costo pero aún no está activo
- **No requiere campos adicionales**

---

## 🖥️ Cómo Usar

### **Crear un nuevo servicio en estado Pendiente:**

1. Haz clic en **"Agregar"** en Internet por Sede
2. Completa los datos básicos del servicio
3. Selecciona **Estado: "Pendiente"**
4. 🎊 **Aparecerán automáticamente los campos de instancia**
5. Selecciona la **instancia** correspondiente:
   - Si eliges "Solicitud de presupuesto" → Solo guarda
   - Si eliges "Servicio tarifado" → Solo guarda
   - Si eliges **"Autorización superior"** → 📝 Completa:
     - **Fecha de solicitud**: Día en que se solicitó la autorización
     - **Archivo PDF**: Sube el documento de autorización
6. Haz clic en **"Guardar"**

### **Editar un servicio pendiente:**

1. Haz clic en el botón **"Editar" (✏️)** del servicio
2. El modal cargará automáticamente:
   - El estado actual
   - La instancia seleccionada
   - La fecha (si corresponde)
   - El archivo actual (si existe)
3. Si hay un archivo PDF:
   - Se muestra un **botón verde** con el nombre
   - Puedes **descargar el archivo actual**
   - Puedes **subir uno nuevo** para reemplazarlo (opcional)
4. Modifica lo que necesites
5. Guarda los cambios

### **Cambiar el estado de Pendiente a Activo:**

Cuando el servicio finalmente se active:
1. Edita el servicio
2. Cambia el estado de "Pendiente" a **"Activo"**
3. 🎊 **Los campos de instancia desaparecerán automáticamente**
4. Los datos de instancia se conservan en la BD pero ya no se muestran
5. Guarda los cambios

---

## 👀 Visualización en la Tabla

En la columna **"Estado"**, cuando un servicio está **Pendiente**, verás:

```
┌──────────────────────┐
│   ⚠️ Pendiente       │  ← Badge amarillo
│   🕐 [Instancia]     │  ← Nombre de la instancia
│                      │
│  (Si es Autorización superior:)
│   📅 DD/MM/YYYY      │  ← Fecha de solicitud
│   [📄 PDF]           │  ← Botón para descargar
└──────────────────────┘
```

**Ejemplo visual:**

| Estado |
|--------|
| ⚠️ **Pendiente** |
| 🕐 Autorización superior |
| 📅 27/10/2025 |
| **[📄 PDF]** ← Click para descargar |

---

## 🗂️ Gestión de Archivos

### **Ubicación:**
Los archivos PDF se guardan en:
```
public/uploads/autorizaciones_internet/
```

### **Nomenclatura:**
```
autorizacion_YYYYMMDD_HHMMSS_[uniqid].pdf

Ejemplo:
autorizacion_20251027_143055_6718a1b2c4f3e.pdf
```

### **Seguridad:**
✅ Solo se aceptan archivos PDF  
✅ Tamaño máximo: 5MB  
✅ Nombres únicos (evita sobrescritura)  
✅ Validación client-side y server-side  

### **Eliminación:**
- Al **eliminar un servicio**, el archivo PDF se borra automáticamente del servidor
- Al **cambiar de instancia**, el archivo permanece hasta que lo reemplaces

---

## 🔍 Filtrado y Búsqueda

Puedes usar la búsqueda de DataTables para encontrar servicios por:
- Instancia (ej: busca "Autorización superior")
- Fecha de solicitud
- Cualquier otro campo visible

---

## 📊 Casos de Uso

### **Caso 1: Servicio en cotización**
```
Estado: Pendiente
Instancia: Solicitud de presupuesto
```
→ No requiere más información

### **Caso 2: Esperando aprobación de Secretaría**
```
Estado: Pendiente
Instancia: Autorización superior
Fecha solicitud: 15/10/2025
Archivo: nota_autorizacion_senaf.pdf
```
→ Permite trackear el proceso de autorización

### **Caso 3: Presupuesto aprobado, esperando instalación**
```
Estado: Pendiente
Instancia: Servicio tarifado
```
→ Se conoce el costo, falta la activación

### **Caso 4: Servicio finalmente activado**
```
Estado: Activo
Instancia: (se oculta automáticamente)
```
→ Los datos de instancia se conservan pero no se muestran

---

## ⚠️ Validaciones

El sistema valida automáticamente:

| Validación | Descripción |
|------------|-------------|
| **Estado Pendiente sin instancia** | ❌ No permite guardar |
| **Autorización sin fecha** | ❌ No permite guardar |
| **Autorización sin PDF (nuevo)** | ❌ No permite guardar |
| **Autorización sin PDF (edición)** | ✅ Permite si ya existe |
| **Archivo no PDF** | ❌ Rechazado con alerta |
| **Archivo > 5MB** | ❌ Rechazado con alerta |

---

## 🔧 Migración de Base de Datos

Si instalas en un servidor nuevo o actualizas uno existente:

### **Opción A: Migración automática**
El código ejecuta migraciones automáticas al cargar la página. No necesitas hacer nada.

### **Opción B: Migración manual**
Si prefieres ejecutar la migración manualmente:

```sql
-- Ejecutar este archivo SQL:
sql/migracion_internet_instancias_pendientes.sql
```

El script agrega:
- Campo `instancia_pendiente` (ENUM)
- Campo `fecha_solicitud_autorizacion` (DATE)
- Campo `archivo_autorizacion` (VARCHAR)
- Índices para optimizar búsquedas

---

## 📱 Responsividad

✅ El modal se adapta a dispositivos móviles  
✅ Los campos condicionales tienen animaciones suaves  
✅ Los botones de descarga son touch-friendly  
✅ Las alertas son claras y legibles  

---

## 🆘 Preguntas Frecuentes

### **¿Puedo cambiar la instancia después de guardar?**
Sí, puedes editar el servicio y cambiar la instancia en cualquier momento.

### **¿Qué pasa si subo un archivo nuevo al editar?**
El archivo anterior se **reemplaza** por el nuevo. El archivo viejo permanece en el servidor pero ya no está vinculado.

### **¿Puedo eliminar el archivo sin eliminar el servicio?**
No directamente. Deberías cambiar la instancia a otra opción o cambiar el estado.

### **¿Los archivos PDF son públicos?**
Los archivos están en `public/uploads/` pero requieren conocer la URL exacta (nombre único). No hay listado público.

### **¿Qué pasa con los servicios existentes?**
Los servicios que ya estaban en estado "Pendiente" ahora mostrarán los campos de instancia al editarlos. Puedes completarlos o cambiar el estado.

### **¿Se pueden agregar más instancias?**
Sí, pero requiere modificar el código (el ENUM en la base de datos y el select en el formulario).

---

## ✅ Checklist de Implementación

Para verificar que todo funciona correctamente:

- [ ] Crear un servicio nuevo con estado "Pendiente" → Instancia "Solicitud de presupuesto"
- [ ] Crear un servicio nuevo con estado "Pendiente" → Instancia "Servicio tarifado"
- [ ] Crear un servicio nuevo con estado "Pendiente" → Instancia "Autorización superior"
  - [ ] Completar fecha de solicitud
  - [ ] Subir un archivo PDF válido
  - [ ] Verificar que aparece el botón de descarga en la tabla
- [ ] Editar un servicio con "Autorización superior"
  - [ ] Verificar que se muestra el archivo actual
  - [ ] Descargar el PDF y verificar que abre correctamente
  - [ ] (Opcional) Subir un nuevo PDF y verificar el reemplazo
- [ ] Cambiar un servicio de "Pendiente" a "Activo"
  - [ ] Verificar que los campos de instancia desaparecen
  - [ ] Verificar que en la tabla ya no se muestra la instancia
- [ ] Eliminar un servicio con archivo PDF
  - [ ] Verificar que el archivo se elimina del servidor

---

## 🎉 ¡Listo!

Ahora puedes gestionar de forma más precisa el estado de los servicios de internet pendientes, con trazabilidad completa del proceso de autorización cuando sea necesario. 🚀
