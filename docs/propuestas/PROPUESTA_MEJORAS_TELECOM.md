# 🌐 Propuesta de Mejoras: Módulos de Infraestructura de Red y Vigilancia

## 📊 **Análisis de Estructura Actual**

### **Estado Actual:**

#### **Infraestructura de Red:**
- **Tabla:** `sedes_red_dispositivos`
- **Estructura Actual:**
  ```
  - id_dispositivo
  - id_sede
  - tipo_dispositivo (Switch, Router, UPS, AP, Firewall)
  - marca
  - modelo
  - cantidad
  - ubicacion
  - estado
  - observaciones
  ```
- **Interfaz:** Lista simple con filtros por localidad/sede

#### **Vigilancia:**
- **Tablas:**
  1. `sedes_vigilancia` (Servicio)
     ```
     - id_vigilancia
     - id_sede
     - proveedor
     - estado_servicio
     - observaciones
     ```
  2. `sedes_vigilancia_dispositivos` (Dispositivos)
     ```
     - id_vigilancia_dispositivo
     - id_vigilancia
     - tipo_dispositivo (DVR, NVR, Cámara, Sensor, Monitor)
     - marca
     - modelo
     - cantidad
     - ubicacion
     - estado
     ```
- **Interfaz:** Vista maestra-detalle (lista de servicios → detalle con dispositivos)

---

## ⚠️ **Problemas Identificados**

### **Infraestructura de Red:**
1. ❌ **No hay relación servicio-dispositivos** (como en vigilancia)
2. ❌ **Falta información crítica de red:**
   - IPs asignadas
   - Puertos utilizados
   - VLANs configuradas
   - Hostname/Nombre del dispositivo
   - Fecha de instalación
   - Número de serie
3. ❌ **No se registra conectividad/topología**
4. ❌ **Falta interfaz de vista detallada por sede**
5. ❌ **No hay campos para credenciales de acceso** (guardadas de forma segura)

### **Vigilancia:**
1. ✅ **Bien estructurado** (servicio + dispositivos)
2. ⚠️ **Mejoras menores:**
   - Agregar fecha de instalación del servicio
   - Agregar costo mensual del servicio
   - Agregar fecha de última revisión
   - Agregar IP/acceso remoto de DVR/NVR
   - Agregar número de contrato

---

## 🎯 **Propuesta de Mejora**

### **1. INFRAESTRUCTURA DE RED - Nueva Estructura**

#### **Opción A: Estructura Similar a Vigilancia (RECOMENDADA)**

Crear 2 tablas:

**Tabla 1: `sedes_red_servicios`** (Nueva - Servicio de Red)
```sql
CREATE TABLE sedes_red_servicios (
    id_red_servicio INT AUTO_INCREMENT PRIMARY KEY,
    id_sede INT NOT NULL,
    proveedor_internet VARCHAR(100),
    tipo_conexion ENUM('Fibra', 'Inalámbrica', 'Satelital', 'Mixta'),
    velocidad_contratada_mbps INT,
    ip_publica VARCHAR(50),
    fecha_instalacion DATE,
    estado_servicio ENUM('Activo', 'Pendiente', 'De Baja') DEFAULT 'Activo',
    costo_mensual DECIMAL(10,2),
    numero_contrato VARCHAR(100),
    observaciones TEXT,
    FOREIGN KEY (id_sede) REFERENCES sedes(id_sede) ON DELETE CASCADE,
    INDEX idx_sede (id_sede)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Tabla 2: `sedes_red_dispositivos`** (Modificar Existente)
```sql
ALTER TABLE sedes_red_dispositivos 
ADD COLUMN id_red_servicio INT AFTER id_dispositivo,
ADD COLUMN hostname VARCHAR(100) AFTER tipo_dispositivo,
ADD COLUMN ip_asignada VARCHAR(50) AFTER hostname,
ADD COLUMN puertos_totales INT AFTER ip_asignada,
ADD COLUMN puertos_usados INT AFTER puertos_totales,
ADD COLUMN vlan VARCHAR(50) AFTER puertos_usados,
ADD COLUMN numero_serie VARCHAR(100) AFTER modelo,
ADD COLUMN fecha_instalacion DATE AFTER numero_serie,
ADD COLUMN usuario_admin VARCHAR(100) AFTER fecha_instalacion,
ADD COLUMN password_hash VARCHAR(255) AFTER usuario_admin,
ADD COLUMN firmware_version VARCHAR(50) AFTER password_hash,
ADD COLUMN ultima_actualizacion DATE AFTER firmware_version,
ADD FOREIGN KEY (id_red_servicio) REFERENCES sedes_red_servicios(id_red_servicio) ON DELETE CASCADE,
ADD INDEX idx_servicio (id_red_servicio);

-- Nota: password_hash será encriptado, no en texto plano
```

#### **Ventajas de esta estructura:**
- ✅ Separación servicio de internet vs dispositivos de red
- ✅ Permite múltiples dispositivos por servicio
- ✅ Mantiene consistencia con módulo de vigilancia
- ✅ Permite rastrear costos y contratos
- ✅ Topología de red más clara

---

### **2. VIGILANCIA - Mejoras Incrementales**

#### **Modificaciones a `sedes_vigilancia`:**
```sql
ALTER TABLE sedes_vigilancia
ADD COLUMN fecha_instalacion DATE AFTER proveedor,
ADD COLUMN costo_mensual DECIMAL(10,2) AFTER fecha_instalacion,
ADD COLUMN numero_contrato VARCHAR(100) AFTER costo_mensual,
ADD COLUMN ip_acceso_remoto VARCHAR(100) AFTER numero_contrato,
ADD COLUMN puerto_acceso INT AFTER ip_acceso_remoto,
ADD COLUMN usuario_admin VARCHAR(100) AFTER puerto_acceso,
ADD COLUMN password_hash VARCHAR(255) AFTER usuario_admin,
ADD COLUMN fecha_ultima_revision DATE AFTER password_hash,
ADD COLUMN proxima_revision DATE AFTER fecha_ultima_revision;
```

#### **Modificaciones a `sedes_vigilancia_dispositivos`:**
```sql
ALTER TABLE sedes_vigilancia_dispositivos
ADD COLUMN numero_serie VARCHAR(100) AFTER modelo,
ADD COLUMN fecha_instalacion DATE AFTER numero_serie,
ADD COLUMN ip_asignada VARCHAR(50) AFTER fecha_instalacion,
ADD COLUMN puerto INT AFTER ip_asignada,
ADD COLUMN resolucion VARCHAR(50) AFTER puerto,
ADD COLUMN almacenamiento_gb INT AFTER resolucion,
ADD COLUMN tipo_montaje VARCHAR(50) AFTER almacenamiento_gb;
```

---

## 🎨 **Propuesta de Interfaz**

### **INFRAESTRUCTURA DE RED**

#### **Vista 1: Lista de Servicios de Red (Nueva)**
```
┌─────────────────────────────────────────────────────────────┐
│  🌐 Infraestructura de Red                   [+ Agregar]    │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  Filtros: [Localidad ▼] [Sede ▼] [Estado ▼]                │
│                                                               │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Sede         │ Proveedor │ Velocidad │ Dispositivos │    │
│  ├─────────────────────────────────────────────────────┤   │
│  │ 📍 Sede A    │ Telecom   │ 100 Mbps  │ [8] 👁️ ✏️ 🗑️│   │
│  │ Loc: Avellaneda │ Activo  │ $5.000   │              │    │
│  ├─────────────────────────────────────────────────────┤   │
│  │ 📍 Sede B    │ Fibertel  │ 50 Mbps   │ [5] 👁️ ✏️ 🗑️│   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

#### **Vista 2: Detalle de Servicio + Dispositivos (Nueva)**
```
┌─────────────────────────────────────────────────────────────┐
│  ← Infraestructura de Red / Sede A                           │
├─────────────────────────────────────────────────────────────┤
│  ┌───────────────────────┐  ┌───────────────────────────┐  │
│  │ 📡 Servicio Internet  │  │ 📊 Estadísticas           │  │
│  ├───────────────────────┤  ├───────────────────────────┤  │
│  │ Proveedor: Telecom    │  │ Total Dispositivos: 8     │  │
│  │ Velocidad: 100 Mbps   │  │ Switch: 2                 │  │
│  │ IP Pública: 200.x.x.x │  │ Router: 1                 │  │
│  │ Estado: 🟢 Activo     │  │ AP: 5                     │  │
│  │ Costo: $5.000/mes     │  │                           │  │
│  │ [✏️ Editar Servicio]  │  │ Puertos Totales: 240     │  │
│  └───────────────────────┘  │ Puertos Usados: 145 (60%) │  │
│                              └───────────────────────────┘  │
│                                                              │
│  ┌──────────────── 🖥️ Dispositivos ─────────────────────┐  │
│  │ [+ Agregar Dispositivo]          [📥 Exportar]        │  │
│  ├───────────────────────────────────────────────────────┤  │
│  │ Tipo     │ Hostname    │ IP         │ Puertos │ Acc. │  │
│  ├───────────────────────────────────────────────────────┤  │
│  │ Switch   │ SW-CORE-01  │ 192.168... │ 48/24   │ ✏️🗑️ │  │
│  │ Router   │ RT-MAIN-01  │ 192.168... │ -       │ ✏️🗑️ │  │
│  │ AP       │ AP-PISO1-01 │ 192.168... │ -       │ ✏️🗑️ │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### **VIGILANCIA - Mejoras en la Interfaz**

#### **Vista Mejorada de Servicio:**
```
┌─────────────────────────────────────────────────────────────┐
│  ← Vigilancia / Sede A                                       │
├─────────────────────────────────────────────────────────────┤
│  ┌───────────────────────┐  ┌───────────────────────────┐  │
│  │ 📹 Servicio Vigilancia│  │ 📊 Estadísticas           │  │
│  ├───────────────────────┤  ├───────────────────────────┤  │
│  │ Proveedor: Seguridad+ │  │ Cámaras: 12               │  │
│  │ Estado: 🟢 Activo     │  │ ├─ Activas: 10            │  │
│  │ Instalación: 01/2024  │  │ └─ Inactivas: 2           │  │
│  │ Costo: $8.500/mes     │  │                           │  │
│  │ Contrato: #2024-001   │  │ DVR: 2                    │  │
│  │                        │  │ Sensores: 4               │  │
│  │ 🌐 Acceso Remoto      │  │                           │  │
│  │ IP: 200.x.x.x:37777   │  │ Almacenamiento: 8TB      │  │
│  │                        │  │ Última Revisión: 10/24   │  │
│  │ ⏰ Próxima Revisión    │  │ Próxima: 01/2025         │  │
│  │ 15/01/2025            │  │                           │  │
│  │ [✏️ Editar Servicio]  │  └───────────────────────────┘  │
│  └───────────────────────┘                                  │
│                                                              │
│  ┌──────────────── 📹 Dispositivos ─────────────────────┐  │
│  │ Filtros: [Tipo ▼] [Estado ▼] [Ubicación ▼]           │  │
│  │ [+ Agregar]  [📥 Exportar]  [🗺️ Ver Mapa]           │  │
│  ├───────────────────────────────────────────────────────┤  │
│  │ Tipo    │ Marca  │ Ubicación    │ IP        │ Estado │  │
│  ├───────────────────────────────────────────────────────┤  │
│  │ DVR     │ Hik... │ Sala Serv... │ 192.x.x.1 │ 🟢 ✏️🗑️│  │
│  │ Cámara  │ Hik... │ Entrada P... │ 192.x.x.2 │ 🟢 ✏️🗑️│  │
│  │ Cámara  │ Hik... │ Pasillo 1F   │ 192.x.x.3 │ 🔴 ✏️🗑️│  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

## 🚀 **Funcionalidades Nuevas Propuestas**

### **Ambos Módulos:**

1. **🔐 Gestión Segura de Credenciales:**
   - Almacenar credenciales encriptadas
   - Botón "Copiar Contraseña" (desencripta temporalmente)
   - Solo visible para Super Admin y Administrador
   - Log de acceso a credenciales en auditoría

2. **📊 Dashboard por Sede:**
   - Resumen de conectividad (red + internet)
   - Estado de vigilancia en tiempo real
   - Alertas (revisiones pendientes, dispositivos caídos)

3. **🗺️ Mapa/Diagrama de Red:**
   - Vista visual de topología (opcional, fase 2)
   - Muestra dispositivos interconectados
   - Estados en tiempo real

4. **📈 Reportes Mejorados:**
   - Reporte de costos mensuales por sede
   - Reporte de inventario completo de red
   - Reporte de revisiones de vigilancia
   - Exportar en PDF/Excel

5. **⏰ Recordatorios:**
   - Notificación de próximas revisiones de vigilancia
   - Alertas de contratos próximos a vencer
   - Actualización de firmware pendiente

---

## 📋 **Plan de Implementación**

### **Fase 1: Estructura Base (1-2 días)**
- ✅ Crear/Modificar tablas SQL
- ✅ Migración de datos existentes
- ✅ Testing de integridad

### **Fase 2: Interfaz Red (2-3 días)**
- ✅ Vista lista de servicios de red
- ✅ Vista detalle con dispositivos
- ✅ CRUD completo
- ✅ Filtros y búsqueda

### **Fase 3: Interfaz Vigilancia (1-2 días)**
- ✅ Actualizar formularios con nuevos campos
- ✅ Mejorar vista de detalle
- ✅ Agregar estadísticas

### **Fase 4: Funcionalidades Avanzadas (2-3 días)**
- ✅ Sistema de credenciales seguras
- ✅ Reportes mejorados
- ✅ Integración con auditoría

### **Fase 5: Pulido y Testing (1 día)**
- ✅ Responsive design
- ✅ Testing completo
- ✅ Documentación

---

## 💡 **Recomendaciones Adicionales**

1. **Seguridad:**
   - Encriptar todas las contraseñas con AES-256
   - Solo mostrar credenciales a roles autorizados
   - Auditar acceso a credenciales

2. **UX/UI:**
   - Usar iconos claros (🌐 para red, 📹 para vigilancia)
   - Color coding por estado (verde=activo, amarillo=pendiente, rojo=baja)
   - Badges para contadores y estados
   - Tooltips informativos

3. **Datos:**
   - Validar IPs con formato correcto
   - Validar rangos de puertos
   - Sugerir nombres de hostname estándar

4. **Integración:**
   - Vincular con módulo de sedes (vista unificada)
   - Vincular con módulo de internet (si existe)
   - Auditar todas las modificaciones

---

## ❓ **Preguntas para Definir Alcance**

1. ¿Quieres la estructura **similar a vigilancia** (servicio + dispositivos)?
2. ¿Necesitas almacenar **credenciales de acceso** a dispositivos?
3. ¿Te interesa el **cálculo de costos** mensuales por sede?
4. ¿Necesitas **alertas/recordatorios** automáticos?
5. ¿Prefieres implementar todo de una vez o por fases?

---

**¿Te gusta esta propuesta? ¿Qué ajustes te gustaría hacer?** 🎯
