# 📚 Documentación SITIA

**Sistema de Inventario de Telecomunicaciones, Insumos y Administración**

---

## 📖 Índice General

Esta carpeta contiene toda la documentación organizada por categorías.

---

## 🚀 Instalación

Guías para instalar y configurar el sistema en diferentes entornos.

- [**Instalación en Producción**](instalacion/GUIA_INSTALACION_PRODUCCION.md) - Pasos completos para producción
- [**Instalación en Servidor**](instalacion/GUIA_INSTALACION_SERVIDOR.md) - Configuración de servidor web
- [**Limpieza de Caché**](instalacion/INSTRUCCIONES_LIMPIEZA_CACHE.md) - Solución de problemas de caché

---

## 👥 Para Usuarios

Guías de uso del sistema para usuarios finales.

- [**Guía de Usuario**](usuario/GUIA_USUARIO.md) - Manual completo del sistema
- [**Guía de Tooltips**](usuario/GUIA_TOOLTIPS.md) - Ayudas contextuales
- [**Acceso al Sistema**](usuario/ACCESO_SISTEMA.md) - Cómo iniciar sesión
- [**Gestión de Internet**](usuario/GUIA_INSTANCIAS_PENDIENTES_INTERNET.md) - Instancias pendientes

---

## 🔄 Migraciones

Scripts e instrucciones para actualizar la base de datos.

- [**Remitos Anulados**](migraciones/INSTRUCCIONES_MIGRACION_ANULADOS.md) - Sistema de anulación
- [**Sistema de Login**](migraciones/INSTRUCCIONES_SISTEMA_LOGIN.md) - Autenticación y roles

### Scripts SQL disponibles en `/sql/`
- `migracion_sistema_usuarios.sql` - Sistema de usuarios y roles
- `migracion_remitos_anulados.sql` - Anulación de remitos
- `corregir_inconsistencias_y_redundancias.sql` - Optimización de BD
- Más scripts en la carpeta `/sql/`

---

## 📊 Análisis Técnicos

Documentos de análisis del sistema y base de datos.

- [**Análisis de Inconsistencias SQL**](analisis/ANALISIS_INCONSISTENCIAS_SQL.md) - Problemas identificados
- [**Análisis SQL Actualizado**](analisis/ANALISIS_SQL_ACTUALIZADO.md) - Estado actual de la BD
- [**Comparativa Telecom**](analisis/COMPARATIVA_ANTES_DESPUES_TELECOM.md) - Mejoras implementadas

---

## 💡 Propuestas de Mejora

Documentos con propuestas de nuevas funcionalidades y mejoras.

- [**Mejoras Telecom**](propuestas/PROPUESTA_MEJORAS_TELECOM.md) - Propuesta completa
- [**Mejoras UI Telecom**](propuestas/MEJORAS_UI_TELECOM_SIN_CAMBIOS_BD.md) - Mejoras de interfaz
- [**Sistema de Login**](propuestas/PROPUESTA_SISTEMA_LOGIN.md) - Sistema de autenticación

---

## 📝 Changelog

Registro de cambios y actualizaciones del sistema.

- [**Resumen de Cambios**](changelog/RESUMEN_CAMBIOS.md) - Cambios recientes

---

## 🔧 Para Desarrolladores

Información técnica para desarrolladores que trabajan en el proyecto.

### Estructura del Proyecto
```
inventario_app/
├── includes/           # Configuración y helpers
├── pages/             # Páginas del sistema
├── ajax/              # Endpoints AJAX (36 archivos)
├── public/            # Assets (CSS, JS)
├── sql/               # Scripts de migración
└── docs/              # Esta documentación
```

### Tecnologías
- **Backend:** PHP 7.4+, PDO, MySQL/MariaDB
- **Frontend:** Bootstrap 5, jQuery, DataTables, Select2
- **PDF:** FPDF, FPDI
- **Seguridad:** bcrypt, CSRF tokens, prepared statements

### Módulos Principales
1. **Insumos** - Gestión de inventario
2. **Asignaciones** - Remitos y asignaciones
3. **Telecomunicaciones** - Internet, Telefonía, Red, Vigilancia
4. **Administración** - Usuarios, Sedes, Áreas, Auditoría
5. **Reportes** - Historial y estadísticas

### API Endpoints
Los endpoints AJAX están en `/ajax/` y siguen este formato de respuesta:

```json
{
  "success": true,
  "data": {},
  "timestamp": "2025-11-17T..."
}
```

### Sistema de Logging
```php
Logger::debug("Mensaje", ['contexto' => 'valor']);
Logger::info("Información");
Logger::warning("Advertencia");
Logger::error("Error", ['detalles' => $e->getMessage()]);
```

### Helpers Disponibles
```php
json_success($data);           // Respuesta exitosa
json_error($message, $code);   // Respuesta de error
conectarDB();                  // Conexión PDO
generarNumeroRemito();         // Número único de remito
```

---

## 🆘 Solución de Problemas

### Problema: CSS/JS no cargan
- Verificar `APP_BASE_URL` en `.env`
- Limpiar caché del navegador (Ctrl+F5)

### Problema: Error de conexión BD
- Verificar credenciales en `includes/config.php`
- Asegurar que la BD existe

### Problema: Sesión expira rápido
- Ajustar timeout en `includes/auth.php` (línea 250)

### Más problemas
Consultar las guías específicas en cada sección.

---

## 📞 Contacto y Soporte

Para problemas o consultas:
1. Revisar esta documentación
2. Consultar logs en `/logs/` (si está habilitado)
3. Contactar al administrador del sistema

---

## 📌 Versiones

- **Sistema:** v1.0
- **Documentación:** Actualizada 17/11/2025
- **Rama:** correcciones_en_21

---

## 🔗 Enlaces Rápidos

- [⬆️ Volver al README Principal](../README.md)
- [🚀 Guía de Instalación Rápida](instalacion/GUIA_INSTALACION_PRODUCCION.md)
- [👤 Manual de Usuario](usuario/GUIA_USUARIO.md)
- [🔧 Mejoras Propuestas](MEJORAS_PROPUESTAS_CORRECCIONES_21.md)

---

**Última actualización:** 17 de Noviembre de 2025
