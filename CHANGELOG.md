# CHANGELOG - SITIA v2.0

## [2.0.2] - 2026-01-12 (Dark Mode Improvements)

### 🎨 Mejoras Modo Oscuro
- Corregido: Labels y textos de formularios ahora visibles
- Corregido: Filas de tabla con zebra striping en tonos oscuros
- Corregido: Tabs y navs con estilos apropiados
- Corregido: Contenido completo de modales ahora legible
- Corregido: Select2 dropdowns con tema oscuro
- **Nuevo**: Botones con colores adaptados al modo oscuro (todos los tipos)
- **Nuevo**: Botones outline con colores apropiados
- Agregado: Estilos para stepper, breadcrumbs, popovers
- Agregado: Scrollbars personalizadas en modo oscuro
- Agregado: Mejor contraste en alerts y badges

## [2.0.1] - 2026-01-12 (Hotfix)

### 🐛 Corrección
- **CSP Corregido**: Agregado `cdn.datatables.net` al Content-Security-Policy que estaba bloqueando la carga de tablas

## [2.0.0] - 2026-01-12

### 🔒 Seguridad
- **Security Headers**: Agregados X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, Content-Security-Policy
  - Archivo: `.htaccess`
  - Rollback: Eliminar bloque `SECURITY HEADERS`
  
- **Rate Limiting de Login**: Bloqueo de IP después de 10 intentos fallidos durante 15 minutos
  - Archivo: `includes/auth.php` (funciones `verificarRateLimitLogin`, `registrarIntentoLogin`)
  - Archivo: `login.php` (integración)
  - Rollback: Eliminar funciones y llamadas marcadas con `@added v2.0`
  
- **Session Regeneration**: Regeneración de ID de sesión después de login exitoso
  - Archivo: `includes/auth.php` línea ~200
  - Rollback: Eliminar `session_regenerate_id(true);`

- **Política de Contraseñas**: Mínimo 6 caracteres con letras y números
  - Archivo: `includes/password_policy.php` (nuevo)
  - Rollback: Eliminar el archivo

### ⚡ Performance
- **Sistema de Caché**: Caché de 5 minutos para estadísticas del dashboard
  - Archivo: `includes/config.php` (funciones `getFromCache`, `setToCache`, `invalidarCache`)
  - Archivo: `pages/dashboard.php` (uso del caché)
  - Rollback: Eliminar funciones y restaurar queries directas

### 🔍 Búsqueda Global
- **Endpoint de Búsqueda**: Busca en insumos, pedidos, remitos y sedes
  - Archivo: `ajax/busqueda_global.php` (nuevo)
  - Rollback: Eliminar el archivo

- **Componente UI**: Input de búsqueda en barra superior con dropdown de resultados
  - Archivo: `includes/header.php` (contenedor #busquedaGlobalContainer)
  - Archivo: `includes/footer.php` (JavaScript de búsqueda)
  - Atajo: `Ctrl+K` para enfocar, `ESC` para cerrar
  - Rollback: Eliminar bloques marcados con `@added v2.0`

### 🎨 UI/UX
- **Modo Oscuro**: Tema oscuro completo con persistencia en localStorage
  - Archivo: `public/css/style.css` (estilos `[data-theme="dark"]`)
  - Archivo: `includes/header.php` (inicialización de tema, botón toggle)
  - Archivo: `includes/footer.php` (JavaScript del toggle)
  - Rollback: Eliminar bloques de CSS y JS marcados con `@added v2.0`

---

## Archivos Modificados

| Archivo | Cambios |
|---------|---------|
| `.htaccess` | +15 líneas (Security Headers) |
| `includes/auth.php` | +65 líneas (Rate Limiting, session_regenerate_id) |
| `includes/config.php` | +55 líneas (Sistema de Caché) |
| `includes/password_policy.php` | Nuevo archivo |
| `includes/header.php` | +35 líneas (Búsqueda Global, Modo Oscuro) |
| `includes/footer.php` | +180 líneas (JS Búsqueda, JS Tema) |
| `public/css/style.css` | +210 líneas (Estilos Modo Oscuro) |
| `pages/dashboard.php` | +8 líneas (Uso de caché) |
| `ajax/busqueda_global.php` | Nuevo archivo |
| `login.php` | +10 líneas (Rate Limiting) |

---

## Instrucciones de Rollback

Para revertir todos los cambios de v2.0:

1. **Security Headers**: Eliminar bloque entre `# SECURITY HEADERS - Agregado v2.0` y `# ============================================` en `.htaccess`

2. **Rate Limiting**: En `includes/auth.php` eliminar desde `// RATE LIMITING PARA LOGIN` hasta el siguiente `// ============================================`. En `login.php` eliminar líneas con comentario `@added v2.0`

3. **Caché**: En `includes/config.php` eliminar bloque `SISTEMA DE CACHÉ SIMPLE`. En `dashboard.php` restaurar query directa sin `getFromCache`/`setToCache`

4. **Password Policy**: Eliminar `includes/password_policy.php`

5. **Búsqueda Global**: Eliminar `ajax/busqueda_global.php`. En `header.php` eliminar contenedor `#busquedaGlobalContainer`. En `footer.php` eliminar bloque `BÚSQUEDA GLOBAL`

6. **Modo Oscuro**: En `style.css` eliminar todo desde `/* MODO OSCURO */` hasta el final. En `header.php` eliminar script de inicialización de tema y botón `#btnToggleTema`. En `footer.php` eliminar script de toggle de tema
