# Guía de Usuario - Sistema de Gestión de Insumos Informáticos

## Tabla de Contenidos
1. [Introducción](#introducción)
2. [Conceptos Clave](#conceptos-clave)
3. [Flujos de Trabajo Principales](#flujos-de-trabajo-principales)
4. [Funcionalidades Adicionales](#funcionalidades-adicionales)
5. [Buenas Prácticas](#buenas-prácticas)
6. [Solución de Problemas Comunes](#solución-de-problemas-comunes)

---

## Introducción

Este sistema permite gestionar el inventario de insumos informáticos de manera centralizada, controlando su estado, ubicación, asignaciones y movimientos. El sistema está diseñado para facilitar el seguimiento de equipos desde su ingreso hasta su baja o devolución.

### Características Principales
- ✅ Gestión completa de inventario de insumos
- ✅ Asignación de insumos a personas con generación de remitos
- ✅ Control de estados (Disponible, Asignado, De Baja)
- ✅ Devoluciones parciales de insumos
- ✅ Historial completo de movimientos
- ✅ Generación de reportes y planillas
- ✅ Seguimiento de ingresos de insumos (compras, licitaciones, etc.)

---

## Conceptos Clave

### Tipos de Insumos

El sistema maneja diferentes tipos de insumos:

1. **PC Escritorio**: Computadoras de escritorio completas
   - Campos requeridos: Procesador, RAM, Almacenamiento, Motherboard
   - Campos opcionales: Sistema Operativo, Número de Serie

2. **Notebook**: Computadoras portátiles
   - Campos requeridos: Marca, Modelo, Procesador, RAM, Almacenamiento
   - Campos adicionales: Cargador, Funda, Micro SD, Caja, Adaptador de red

3. **Impresora**: Impresoras de cualquier tipo
   - Campos requeridos: Marca, Modelo

4. **Monitor**: Monitores
   - Campos requeridos: Marca, Modelo, Pulgadas, Conexión

5. **Escaner**: Escáneres
   - Campos requeridos: Marca, Modelo

6. **Varios**: Otros insumos (cables, adaptadores, etc.)
   - Campos requeridos: Nombre del Insumo (formato: "Insumo + Marca + Modelo + Conexión")
   - Permite manejar cantidades mayores a 1
   - Subcategorías: Hardware, Periféricos, Red

### Estados de Insumos

- **Disponible**: El insumo está en stock y puede ser asignado
- **Asignado**: El insumo está asignado a una persona
- **De Baja**: El insumo fue dado de baja del inventario

### Identificadores

- **ID Patrimonio**: Obligatorio para todos los tipos excepto "Varios"
- **ID Físico**: Opcional, identificación física del equipo
- **Número de Serie**: Opcional, número de serie del fabricante

### Estructura Organizacional

El sistema organiza los insumos por:
- **Zona** → **Localidad** → **Sede** → **Área**
- Cada insumo puede estar en un **Punto de Stock** (almacén) o asignado a una persona en un área específica

---

## Flujos de Trabajo Principales

### 1. Dar de Alta un Insumo Disponible

**Cuándo usar**: Cuando recibes un nuevo insumo que ingresará al inventario pero aún no sabes a quién asignarlo.

**Pasos**:

1. Acceder a **Insumos** → **Agregar Insumo**
2. Seleccionar el **Tipo de Insumo**
3. Completar los campos según el tipo seleccionado:
   - **Para tipos específicos** (PC, Notebook, etc.):
     - Completar: ID Patrimonio (*), ID Físico (*), Descripción (opcional)
     - **Número de Serie**: Opcional (puede dejarse vacío)
     - Completar las especificaciones técnicas requeridas
   - **Para tipo "Varios"**:
     - Completar: Nombre del Insumo (*) siguiendo el formato "Insumo + Marca + Modelo + Conexión"
     - Seleccionar subcategoría (Hardware, Periféricos, Red)
     - Indicar cantidad (puede ser mayor a 1)
4. Completar información común:
   - Fecha de Adquisición
   - Punto de Almacenamiento (por defecto: Depósito)
   - Marcar si es "Insumo Nuevo" o "Usado"
   - Opcionalmente, asociar a un Ingreso (compra, licitación, etc.)
5. Hacer clic en **Guardar**

**Resultado**: El insumo queda registrado con estado "Disponible" y puede ser asignado posteriormente.

---

### 2. Dar de Alta un Insumo Asignado Directamente

**Cuándo usar**: Cuando recibes un insumo y ya sabes a quién se lo vas a asignar inmediatamente.

**Pasos**:

1. Acceder a **Insumos** → **Agregar Insumo Asignado**
2. **Paso 1 - Datos de Asignación**:
   - Completar datos del agente asignado:
     - Nombre (*)
     - Apellido (*)
     - Fecha de Asignación (*)
   - Seleccionar ubicación:
     - Localidad (*) → se cargan las Sedes
     - Sede (*) → se cargan las Áreas
     - Área (*)
   - Opcionalmente, agregar observaciones
   - Hacer clic en **Siguiente**
3. **Paso 2 - Alta de Insumo**:
   - Seleccionar el **Tipo de Insumo**
   - Completar los campos según el tipo (mismo proceso que en "Agregar Insumo")
   - **Importante**: El número de serie es opcional
   - Completar información común (Punto de Almacenamiento, Fecha, etc.)
   - Hacer clic en **Guardar y Asignar**

**Resultado**: 
- El insumo queda registrado con estado "Asignado"
- Se genera automáticamente un remito con numeración única
- Se redirige a la vista del remito generado

---

### 3. Asignar un Insumo Existente

**Cuándo usar**: Cuando tienes un insumo disponible y quieres asignarlo a una persona.

**Pasos**:

1. Acceder a **Asignaciones** → **Nueva Asignación**
2. Seleccionar ubicación:
   - **Localidad** → se cargan las Sedes disponibles
   - **Sede** → se cargan las Áreas y los Insumos disponibles en esa sede
   - **Área** → se filtra la lista de insumos disponibles
3. Completar datos del agente asignado:
   - Nombre (*)
   - Apellido (*)
   - Fecha de Asignación (*)
   - Observaciones (opcional)
4. Seleccionar insumos:
   - En la lista de "Insumos Disponibles" aparecerán los insumos con estado "Disponible"
   - Para cada insumo, hacer clic en el botón **Seleccionar** (icono +)
   - **Para tipo "Varios"**: Si la cantidad es mayor a 1, puedes seleccionar cuántas unidades asignar
   - Los insumos seleccionados aparecerán en la sección "Insumos Seleccionados"
5. Hacer clic en **Siguiente**
6. Revisar el resumen de la asignación
7. Hacer clic en **Confirmar Asignación**

**Resultado**:
- Se genera un remito con numeración única (formato: REMITO_YYYY_NNNN)
- Los insumos cambian su estado a "Asignado"
- Se redirige a la vista del remito donde puedes imprimirlo en PDF

**Nota**: Si un insumo de tipo "Varios" tiene cantidad 0, no aparecerá en la lista de disponibles.

---

### 4. Devolver un Insumo Asignado

**Cuándo usar**: Cuando un insumo asignado debe ser devuelto al inventario (por cambio de personal, fin de uso, etc.).

**Pasos**:

1. Acceder a **Asignaciones** → **Listar**
2. Buscar el remito activo que contiene el insumo a devolver
3. Hacer clic en **Devolver Insumos** (botón con icono de devolución)
4. En el modal de devolución:
   - Seleccionar los insumos a devolver (marcar checkboxes)
   - **Para tipo "Varios"**: Indicar la cantidad a devolver (si es parcial)
   - Revisar que la cantidad a devolver no exceda la cantidad asignada
5. Hacer clic en **Confirmar Devolución**

**Resultado**:
- El insumo cambia su estado a "Disponible"
- Se actualiza el historial de devoluciones
- Si la devolución es parcial (tipo "Varios"), se mantiene la asignación con la cantidad restante

**Nota**: Solo se pueden devolver insumos de remitos con estado "Activa".

---

### 5. Editar un Insumo

**Cuándo usar**: Cuando necesitas corregir información de un insumo existente o actualizar sus especificaciones.

**Pasos**:

1. Acceder a **Insumos** → **Listar**
2. Buscar el insumo a editar (puedes usar los filtros por tipo o estado)
3. Hacer clic en el botón **Editar** (icono de lápiz)
4. Modificar los campos necesarios:
   - **Nota**: El tipo de insumo no se puede cambiar
   - El número de serie es opcional
   - La fecha de adquisición puede estar bloqueada si el insumo está asociado a un ingreso
5. Hacer clic en **Guardar**

**Resultado**: Los cambios se guardan y se actualiza la información del insumo.

**Restricciones**:
- No se puede cambiar el tipo de insumo
- No se puede modificar el estado desde esta pantalla (se modifica automáticamente con asignaciones/devoluciones)

---

### 6. Ver Detalles de un Insumo

**Cuándo usar**: Cuando necesitas consultar información completa de un insumo, su historial o ubicación actual.

**Pasos**:

1. Acceder a **Insumos** → **Listar**
2. Buscar el insumo
3. Hacer clic en el botón **Ver** (icono de ojo)

**Información mostrada**:
- Datos generales del insumo
- Especificaciones técnicas (según tipo)
- Estado actual y ubicación
- Información de asignación activa (si está asignado)
- Historial de asignaciones
- Última baja (si aplica)

**Nota**: Para insumos tipo "Varios", en lugar de mostrar "Varios" como tipo, se muestra la subcategoría (Hardware, Periféricos, Red).

---

### 7. Dar de Baja un Insumo

**Cuándo usar**: Cuando un insumo ya no puede ser utilizado (averiado, obsoleto, etc.) y debe salir del inventario.

**Pasos**:

1. Acceder a **Insumos** → **Listar**
2. Buscar el insumo a dar de baja
3. Hacer clic en **Dar de Baja** (botón con icono de flecha hacia abajo)
4. En el modal:
   - Completar el campo **Motivo/Observación** (*) - mínimo 3 caracteres
   - **Para tipo "Varios"**: Indicar la cantidad a dar de baja (si es parcial)
   - Revisar que la cantidad no exceda la disponible
5. Hacer clic en **Confirmar Baja**

**Restricciones**:
- No se puede dar de baja un insumo asignado (primero debe ser devuelto)
- Para tipo "Varios", se puede dar de baja parcial

**Resultado**:
- El insumo cambia su estado a "De Baja"
- Se registra la fecha y motivo de la baja
- El insumo ya no aparece en listas de disponibles

---

### 8. Registrar Ingresos de Insumos

**Cuándo usar**: Cuando necesitas registrar la entrada de insumos por compras, licitaciones, fondos u otros medios.

**Pasos**:

1. Acceder a **Insumos** → **Ingresos** → **Nuevo Ingreso**
2. Seleccionar el **Tipo de Ingreso**:
   - **Fondos**: Número de Nota
   - **Licitación**: Número de Expediente
   - **Compra Directa**: Número de Expediente
   - **Otros**: Número de Referencia
3. Completar:
   - Número de Referencia/Nota/Expediente (*)
   - Fecha de Finalización (*)
   - Descripción (opcional)
4. Hacer clic en **Siguiente**
5. Seleccionar insumos:
   - Buscar insumos disponibles (sin ingreso asignado)
   - Seleccionar los insumos que corresponden a este ingreso
   - **Para tipo "Varios"**: Indicar cantidad si corresponde
6. Hacer clic en **Siguiente**
7. Revisar resumen y confirmar

**Resultado**:
- Se crea el registro de ingreso
- Los insumos seleccionados quedan asociados al ingreso
- La fecha de adquisición de los insumos se establece automáticamente con la fecha de finalización del ingreso

**Ventajas**:
- Permite rastrear el origen de los insumos
- Facilita la asociación de fecha de adquisición
- Útil para reportes y auditorías

---

### 9. Generar Planilla de Relevamiento

**Cuándo usar**: Cuando necesitas realizar un relevamiento físico de equipos y necesitas una planilla impresa para completar manualmente.

**Pasos**:

1. Acceder a **Insumos** → **Listar**
2. Hacer clic en **Planilla de Relevamiento** (botón azul en la parte superior)
3. Seleccionar el formato deseado:
   - **4 formularios por hoja (2x2)**: Más espacio para escribir, formato más grande
   - **6 formularios por hoja (3x2)**: Más formularios por página, formato compacto
4. El PDF se genera automáticamente y se abre en una nueva pestaña
5. Imprimir el PDF

**Campos incluidos en la planilla**:
- ID PC
- Procesador
- RAM
- Almacenamiento
- Sistema Operativo
- Motherboard
- Sede/Oficina
- ID Monitor
- ID Impresora
- Detalles (campo amplio para notas)

---

### 10. Ver Remitos y Generar PDF

**Cuándo usar**: Cuando necesitas consultar un remito o imprimirlo.

**Pasos**:

1. Acceder a **Asignaciones** → **Listar**
2. Buscar el remito por número, persona o fecha
3. Hacer clic en **Ver Remito** o en el número del remito
4. En la vista del remito:
   - Revisar información completa del remito
   - Ver lista de insumos asignados
   - Ver historial de devoluciones (si aplica)
5. Para imprimir:
   - Hacer clic en **Imprimir Remito** (botón PDF)
   - El PDF se genera con el membrete institucional
   - Imprimir o guardar el PDF

**Información del remito**:
- Número único (formato: REMITO_YYYY_NNNN)
- Datos de la persona asignada
- Ubicación (Sede, Localidad, Área)
- Fecha de asignación
- Lista detallada de insumos
- Estado del remito (Activa, Devuelta, Anulada)

---

## Funcionalidades Adicionales

### Filtros y Búsqueda

En la mayoría de las listas puedes filtrar por:
- **Tipo de Insumo**: Filtrar por tipo específico o "Varios"
- **Estado**: Disponible, Asignado, De Baja
- **Búsqueda de texto**: Buscar por nombre, número de serie, ID físico o ID patrimonio

### Exportación de Datos

- **Exportar a Excel**: Disponible en la lista de insumos y asignaciones
- **Imprimir**: Generar vista imprimible de las tablas

### Dashboard

El dashboard muestra:
- Contadores de insumos (total, disponibles, asignados)
- Cantidades por subtipo de "Varios"
- Gráficos de distribución
- Asignaciones recientes
- Resumen de estados

---

## Buenas Prácticas

### Al Registrar Insumos

1. **Completar todos los campos obligatorios**: Especialmente ID Patrimonio para tipos específicos
2. **Usar formato correcto para "Varios"**: Nombre debe seguir "Insumo + Marca + Modelo + Conexión"
3. **Asociar a ingresos**: Siempre que sea posible, asociar insumos a su ingreso correspondiente
4. **Verificar duplicados**: El sistema valida automáticamente, pero revisa antes de guardar

### Al Asignar Insumos

1. **Completar datos correctamente**: Verificar nombre, apellido y ubicación
2. **Revisar antes de confirmar**: Usar el resumen previo a la confirmación
3. **Guardar remitos**: Descargar o imprimir el remito generado para mantener registro físico

### Al Devolver Insumos

1. **Registrar motivo**: Aunque no es obligatorio, es útil agregar observaciones
2. **Devoluciones parciales**: Para tipo "Varios", puedes devolver solo una parte
3. **Verificar estado**: Solo se pueden devolver insumos de remitos activos

### Mantenimiento de Datos

1. **Actualizar información**: Cuando cambien especificaciones de equipos, actualizar en el sistema
2. **Dar de baja oportunamente**: No mantener insumos obsoletos o averiados como "Disponible"
3. **Revisar estados**: Periódicamente revisar que los estados reflejen la realidad

---

## Solución de Problemas Comunes

### No puedo asignar un insumo

**Causas posibles**:
- El insumo no está en estado "Disponible"
- Para tipo "Varios", la cantidad disponible es 0
- El insumo está en otra sede

**Solución**:
- Verificar el estado del insumo en la lista
- Revisar la cantidad disponible para tipo "Varios"
- Verificar la sede actual del insumo

### No aparece el campo de número de serie

**Causa**: El campo número de serie es opcional y solo aparece para tipos específicos (no para "Varios")

**Solución**: Es normal, el campo puede dejarse vacío

### No puedo dar de baja un insumo

**Causas posibles**:
- El insumo está asignado (estado "Asignado")
- Es un tipo "Varios" y la cantidad disponible es 0

**Solución**:
- Primero devolver el insumo desde Asignaciones
- Verificar la cantidad disponible

### El PDF del remito no se genera

**Causas posibles**:
- Problemas de permisos en el servidor
- La plantilla del membrete no está disponible

**Solución**:
- Contactar al administrador del sistema
- Verificar que exista el archivo `membretada.pdf`

### Los filtros no funcionan

**Solución**:
- Limpiar filtros y volver a aplicarlos
- Verificar que hay datos que coincidan con el filtro
- Recargar la página

---

## Glosario de Términos

- **Insumo**: Cualquier elemento informático gestionado en el sistema (PC, Notebook, Impresora, etc.)
- **Remito**: Documento que formaliza la asignación de insumos a una persona
- **Asignación**: Proceso de asociar un insumo a una persona
- **Devolución**: Proceso de retornar un insumo asignado al inventario
- **Baja**: Proceso de retirar un insumo del inventario (no puede volver a asignarse)
- **Ingreso**: Registro de entrada de insumos al sistema (compra, licitación, etc.)
- **Punto de Stock**: Ubicación física de almacenamiento (ej: Depósito)
- **Estado**: Condición actual del insumo (Disponible, Asignado, De Baja)

---

## Contacto y Soporte

Para consultas técnicas o problemas con el sistema, contactar al administrador del sistema.

---

**Última actualización**: 2025
**Versión del sistema**: 1.21
