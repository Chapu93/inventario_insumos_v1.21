# Sistema de Permisos - Inventario de Insumos

## Módulos del Sistema

### 1. **Insumos** (`insumos`)
- `ver`: Ver listado de insumos
- `crear`: Crear nuevo insumo
- `editar`: Editar insumo existente
- `eliminar`: Eliminar insumo
- `baja`: Dar de baja insumo

### 2. **Asignaciones** (`asignaciones`)
- `ver`: Ver listado de asignaciones/remitos
- `crear`: Crear nueva asignación
- `editar`: Editar asignación
- `anular`: Anular asignación
- `devolver`: Devolver insumos

### 3. **Sedes** (`sedes`)
- `ver`: Ver listado y detalles de sedes
- `crear`: Crear nueva sede
- `editar`: Editar sede
- `eliminar`: Eliminar sede

### 4. **Telecomunicaciones** (`telecom`)
- `ver`: Ver infraestructura de red, líneas telefónicas, planos
- `editar`: Editar/agregar elementos de telecom
- `eliminar`: Eliminar elementos de telecom

### 5. **Reportes** (`reportes`)
- `ver`: Ver reportes
- `exportar`: Exportar reportes

### 6. **Usuarios** (`usuarios`)
- `ver`: Ver listado de usuarios
- `crear`: Crear nuevo usuario
- `editar`: Editar usuario
- `eliminar`: Eliminar usuario
- `cambiar_rol`: Cambiar rol de usuario
- `reset_password`: Restablecer contraseña

### 7. **Auditoría** (`auditoria`)
- `ver_todo`: Ver todo el registro de auditoría

### 8. **Sistema** (`sistema`)
- `backup`: Generar backup de la base de datos

---

## Propuesta de Permisos por Tipo de Usuario

### 🔴 **Super Administrador** (Acceso Total)
**Descripción**: Control completo del sistema

```json
{
  "insumos": ["ver", "crear", "editar", "eliminar", "baja"],
  "asignaciones": ["ver", "crear", "editar", "anular", "devolver"],
  "sedes": ["ver", "crear", "editar", "eliminar"],
  "telecom": ["ver", "editar", "eliminar"],
  "reportes": ["ver", "exportar"],
  "usuarios": ["ver", "crear", "editar", "eliminar", "cambiar_rol", "reset_password"],
  "auditoria": ["ver_todo"],
  "sistema": ["backup"]
}
```

### 🟠 **Administrador** (Gestión Operativa)
**Descripción**: Puede gestionar insumos, asignaciones y sedes, pero no usuarios ni sistema

```json
{
  "insumos": ["ver", "crear", "editar", "baja"],
  "asignaciones": ["ver", "crear", "editar", "devolver"],
  "sedes": ["ver", "crear", "editar"],
  "telecom": ["ver", "editar"],
  "reportes": ["ver", "exportar"],
  "usuarios": ["ver"],
  "auditoria": []
}
```

### 🟡 **Gestor** (Operaciones Diarias)
**Descripción**: Puede crear y gestionar asignaciones, ver insumos y sedes

```json
{
  "insumos": ["ver", "crear", "editar"],
  "asignaciones": ["ver", "crear", "editar", "devolver"],
  "sedes": ["ver"],
  "telecom": ["ver"],
  "reportes": ["ver", "exportar"],
  "usuarios": []
}
```

### 🟢 **Operador** (Solo Consulta y Asignaciones)
**Descripción**: Puede crear asignaciones y consultar información

```json
{
  "insumos": ["ver"],
  "asignaciones": ["ver", "crear"],
  "sedes": ["ver"],
  "telecom": ["ver"],
  "reportes": ["ver"]
}
```

### 🔵 **Consultor** (Solo Lectura)
**Descripción**: Solo puede ver información, sin modificar nada

```json
{
  "insumos": ["ver"],
  "asignaciones": ["ver"],
  "sedes": ["ver"],
  "telecom": ["ver"],
  "reportes": ["ver"]
}
```

---

## Problemas Detectados

### 1. **Módulo 'sedes' duplicado**
En `/pages/admin/usuarios/permisos.php` líneas 95-104 y 121-130

### 2. **Inconsistencias en verificación de permisos**
Algunos archivos pueden no estar verificando correctamente los permisos antes de mostrar botones de acción.

### 3. **Falta de verificación en backend**
Algunos endpoints AJAX pueden no estar verificando permisos en el servidor.

---

## Recomendaciones

1. **Eliminar duplicado del módulo 'sedes'**
2. **Revisar todos los botones de acción** para asegurar que usen `tienePermiso()`
3. **Verificar permisos en todos los endpoints AJAX**
4. **Agregar tests de permisos** para cada rol
5. **Documentar claramente** qué puede hacer cada rol
