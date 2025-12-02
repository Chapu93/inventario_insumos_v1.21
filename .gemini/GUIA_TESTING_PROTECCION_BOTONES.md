# 🧪 Guía de Testing: Validación de Protección de Botones por Permisos

## Descripción
Esta guía proporciona pasos detallados para validar que los botones de acción solo sean visibles cuando el usuario tiene los permisos correspondientes.

---

## 📋 Pre-requisitos

- [ ] Acceso a la aplicación en ambiente local (`http://localhost/inventario_app`)
- [ ] Cuentas de usuario con diferentes roles creadas (Admin, Limitado, Solo Lectura)
- [ ] Base de datos actualizada con última estructura
- [ ] Rama `recta-final` actualizada en el servidor local

---

## 🔐 Roles y Permisos de Prueba

### Usuario 1: Administrador
- **Rol:** Admin
- **Permisos:** Todos los módulos, todas las acciones
- **Expectativa:** Ver TODOS los botones en todas las páginas

### Usuario 2: Operario Limitado
- **Rol:** Operario
- **Permisos:** 
  - `insumos`: ver, editar
  - `asignaciones`: ver, devolver
  - Nada de telecom, sedes, areas
- **Expectativa:** Ver botones solo en insumos y asignaciones

### Usuario 3: Solo Lectura
- **Rol:** Consultor
- **Permisos:** 
  - `insumos`: ver
  - `asignaciones`: ver
  - `reportes`: ver
- **Expectativa:** SIN botones editar/eliminar/crear en ningún lado

---

## 🧪 Casos de Prueba

### MÓDULO: INSUMOS

#### Test 1.1: Página `ingresos_listar.php` - Botón "Nuevo Ingreso"

**URL:** `http://localhost/inventario_app/pages/insumos/ingresos_listar.php`

| Usuario | Debe Ver? | Estado |
|---------|-----------|--------|
| Admin | ✅ Sí | [ ] Pasar |
| Operario | ✅ Sí | [ ] Pasar |
| Consultor | ❌ No | [ ] Pasar |

**Pasos:**
1. Loguear como Consultor
2. Navegar a "Gestión de Insumos" → "Ingresos"
3. **Verificar:** Botón "Nuevo Ingreso" NO debe aparecer
4. Abrir DevTools (F12) → Console
5. **Verificar:** `window.PERMISOS.crear` debe ser `false`

```javascript
// En la consola, debería mostrar:
console.log(window.PERMISOS);
// {ver: true, editar: false, crear: false, eliminar: false, ...}
```

---

#### Test 1.2: Página `ver.php` (insumo) - Botón "Editar"

**URL:** `http://localhost/inventario_app/pages/insumos/ver.php?id=1`

| Usuario | Debe Ver? | Estado |
|---------|-----------|--------|
| Admin | ✅ Sí | [ ] Pasar |
| Operario | ✅ Sí | [ ] Pasar |
| Consultor | ❌ No | [ ] Pasar |

**Pasos:**
1. Loguear como Consultor
2. Navegar a un insumo específico
3. **Verificar:** Botón "Editar" NO debe aparecer
4. Link "Volver" SÍ debe aparecer

---

### MÓDULO: SEDES Y ÁREAS

#### Test 2.1: Página `sedes.php` - Botones de Acción

**URL:** `http://localhost/inventario_app/pages/admin/sedes.php`

| Usuario | Ver? | Editar? | Eliminar? | Estado |
|---------|------|---------|-----------|--------|
| Admin | ✅ | ✅ | ✅ | [ ] Pasar |
| Operario | ❌ | ❌ | ❌ | [ ] Pasar |
| Consultor | ❌ | ❌ | ❌ | [ ] Pasar |

**Pasos:**
1. Loguear como Operario
2. Navegar a Admin → Sedes
3. **Verificar:** Tabla vacía O sin botones de acción
4. Intentar acceder directamente: `http://localhost/inventario_app/pages/admin/sedes.php`
5. **Verificar:** Página debe cargar pero sin buttons

---

#### Test 2.2: Página `sede_detalle.php` - Acciones Rápidas

**URL:** `http://localhost/inventario_app/pages/admin/sede_detalle.php?id_sede=1&id_localidad=1`

| Usuario | Agregar Internet? | Editar Sede? | Ver Remitos? | Estado |
|---------|------------------|-------------|--------------|--------|
| Admin | ✅ | ✅ | ✅ | [ ] Pasar |
| Operario | ❌ | ❌ | ✅ | [ ] Pasar |
| Consultor | ❌ | ❌ | ✅ | [ ] Pasar |

**Pasos:**
1. Loguear como Operario
2. Navegar a Admin → Sedes → Detalle de una sede
3. **Verificar:** 
   - Botones de telecom NO deben aparecer
   - Botón "Editar Sede" NO debe aparecer
   - Botón "Ver Remitos" SÍ debe aparecer

---

### MÓDULO: TELECOM

#### Test 3.1: Página `telecom_internet.php` - Botones de Acción

**URL:** `http://localhost/inventario_app/pages/admin/telecom_internet.php`

| Usuario | Ver? | Editar? | Eliminar? | Estado |
|---------|------|---------|-----------|--------|
| Admin | ✅ | ✅ | ✅ | [ ] Pasar |
| Operario | ❌ | ❌ | ❌ | [ ] Pasar |
| Consultor | ❌ | ❌ | ❌ | [ ] Pasar |

**Pasos:**
1. Loguear como Operario
2. Intentar acceder a Admin → Telecom → Internet
3. **Verificar:** 
   - Página debería mostrar "Permiso denegado" O cargar vacía
   - Si hay datos, NO deben haber botones de editar/eliminar
4. Verificar status code HTTP 403 si lo intenta directo

---

#### Test 3.2: Página `telecom_vigilancia_servicio.php` - Botones Service y Device

**URL:** `http://localhost/inventario_app/pages/admin/telecom_vigilancia_servicio.php?id=1`

| Usuario | Editar Servicio? | Eliminar Servicio? | Editar Device? | Eliminar Device? | Estado |
|---------|-----|----------|----------|----------|--------|
| Admin | ✅ | ✅ | ✅ | ✅ | [ ] Pasar |
| Operario | ❌ | ❌ | ❌ | ❌ | [ ] Pasar |

**Pasos:**
1. Loguear como Operario
2. Si acceso denegado → OK
3. Si acceso permitido (alguien con permisos):
   - **Verificar:** Botones de servicio envueltos
   - **Verificar:** Botones de dispositivos (si existen) también envueltos

---

### MÓDULO: USUARIOS

#### Test 4.1: Página `usuarios/listar.php` - Ya Protegida

**URL:** `http://localhost/inventario_app/pages/admin/usuarios/listar.php`

| Usuario | Editar? | Cambiar Rol? | Estado |
|---------|---------|--------------|--------|
| Admin | ✅ | ✅ | [ ] Pasar |
| Operario | ❌ | ❌ | [ ] Pasar |

**Pasos:**
1. Loguear como Operario
2. Acceder a Admin → Usuarios
3. **Verificar:** Permiso denegado O sin botones

---

## 🎯 Checklist de Validación Visual

### Admin (debe ver TODOS)
- [ ] Botón "Nuevo Ingreso" en ingresos_listar.php
- [ ] Botón "Editar" en ver.php (insumos)
- [ ] Botones edit/delete en sedes.php
- [ ] Botones edit/delete en areas.php
- [ ] Botones edit/delete en todos los telecom_*.php
- [ ] Botones en usuarios/listar.php
- [ ] 7 botones en sede_detalle.php (acciones rápidas)

### Operario (ver según permisos)
- [ ] Botón "Nuevo Ingreso" EN ingresos_listar.php (si tiene permisos)
- [ ] Botón "Editar" EN ver.php si tiene permiso insumos:editar
- [ ] NO botones en sedes.php
- [ ] NO botones en areas.php
- [ ] NO botones en telecom_*.php
- [ ] NO botones en usuarios/listar.php
- [ ] Botón "Ver Remitos" EN sede_detalle.php

### Consultor (solo lectura)
- [ ] NO botón "Nuevo Ingreso"
- [ ] NO botón "Editar" en ver.php
- [ ] NO botones de acción en ningún módulo admin
- [ ] Botones de exportar/imprimir SÍ deben estar visibles
- [ ] Links de navegación SÍ deben estar visibles

---

## 🔧 Validación Técnica (DevTools)

### Verificar PERMISOS Object en Navegador

```javascript
// Abrir DevTools > Console y ejecutar:

// En páginas ingresos_listar.php:
console.log(window.PERMISOS);
// Debería mostrar objeto con propiedades booleanas

// Verificar permiso específico:
console.log('¿Puede crear?', window.PERMISOS.crear); // true o false

// Inspeccionar HTML del botón:
document.querySelector('.btn-primary')?.outerHTML;
// Si tienePermiso es false, el botón NO debe estar en el DOM
```

### Verificar en Elementos HTML

1. Abrir DevTools (F12)
2. Inspector de Elementos
3. Buscar botón esperado: `Ctrl+F` → "Editar" o "Eliminar"
4. Si NO aparece → ✅ Correcto
5. Si aparece pero está deshabilitado (`disabled`) → ❌ Incorrecto (debería no estar en DOM)

---

## 📊 Reporte de Resultados

### Template de Reporte

```
# Reporte de Testing - Protección de Botones

## Fecha: [Fecha]
## Tester: [Nombre]

### Resultados Finales
- [ ] Admin: Todos los botones visibles ✅
- [ ] Operario: Botones limitados según permisos ✅
- [ ] Consultor: Solo lectura, sin botones ✅

### Detalle por Módulo

#### Insumos
- [ ] ingresos_listar.php: Botón "Nuevo Ingreso" protegido ✅
- [ ] ver.php: Botón "Editar" protegido ✅

#### Sedes
- [ ] sedes.php: Botones edit/delete protegidos ✅
- [ ] sede_detalle.php: Acciones rápidas protegidas ✅

#### Telecom
- [ ] telecom_internet.php: Botones edit/delete protegidos ✅
- [ ] telecom_telefonia.php: Botones edit/delete protegidos ✅
- [ ] telecom_red.php: Botones edit/delete protegidos ✅
- [ ] telecom_planos.php: Botón delete protegido ✅
- [ ] telecom_vigilancia.php: Botones edit/delete protegidos ✅
- [ ] telecom_vigilancia_servicio.php: 4 botones protegidos ✅

#### Areas
- [ ] areas.php: Botones edit/delete protegidos ✅

#### Usuarios
- [ ] usuarios/listar.php: Botones protegidos ✅

### Problemas Encontrados
[Listar aquí si hay]

### Observaciones
[Agregar observaciones]

### Conclusión
✅ PASÓ / ❌ FALLÓ

---
```

---

## 🆘 Troubleshooting

### Problema: Botones siguen apareciendo

**Soluciones:**
1. Limpiar caché del navegador: `Ctrl+Shift+Del`
2. Verificar que esté en rama `recta-final`: `git branch`
3. Verificar que los cambios están en el archivo: `grep -n "tienePermiso" pages/admin/sedes.php`
4. Recargar página con `Ctrl+F5` (fuerza recarga)

### Problema: "Permiso denegado" en toda la página

**Soluciones:**
1. Verificar que el usuario tiene permiso `modulo:ver`
2. Revisar archivo `ajax/` correspondiente para validación de permisos
3. Verificar permisos en base de datos: `SELECT * FROM usuarios_permisos WHERE id_usuario = ?`

### Problema: Botones deshabilitados pero visibles

**No es correcto.** Los botones deben estar fuera del DOM completamente, no solo deshabilitados.

**Solución:**
- Cambiar de: `<button disabled>` 
- A: Envolver con condicional PHP `<?php if (...): ?> <button> ... <?php endif; ?>`

---

## 📞 Contacto y Soporte

Si encuentras problemas:
1. Revisar el commit correspondiente: `git show <commit_hash>`
2. Verificar archivo en cuestión: `git diff HEAD~1 <archivo>`
3. Consultar documentación: `AUDITORIA_PERMISOS_VISIBILIDAD_BOTONES.md`

---

**Última actualización:** 2 de diciembre de 2025  
**Rama:** recta-final  
**Versión:** 1.0
