# Guía de Instalación en Producción

## 📋 Checklist Pre-Instalación

- [ ] Backup completo de la base de datos actual (si existe)
- [ ] Servidor web configurado (Apache/Nginx)
- [ ] PHP 7.4+ instalado
- [ ] MySQL/MariaDB 5.7+ instalado
- [ ] Composer instalado
- [ ] Permisos de escritura en directorio del proyecto

---

## 🚀 Instalación Desde Cero

### Paso 1: Clonar el Repositorio

```bash
git clone <url-repositorio>
cd inventario_insumos_v1
git checkout correciones_en_21
```

### Paso 2: Instalar Dependencias

```bash
composer install
```

Esto instalará:
- `setasign/fpdf` - Generación de PDFs
- `setasign/fpdi` - Importación de plantillas PDF

### Paso 3: Crear Base de Datos

```bash
# Crear base de datos
mysql -u root -p -e "CREATE DATABASE inventario_insumos_v1 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# Importar estructura y datos iniciales
mysql -u root -p inventario_insumos_v1 < inventario_insumos_v1.sql
```

### Paso 4: Aplicar Migración de Anulación de Remitos

```bash
mysql -u root -p inventario_insumos_v1 < sql/migracion_remitos_anulados.sql
```

### Paso 5: Configurar Variables de Entorno

Crear archivo `.env` en la raíz del proyecto:

```env
# Base de Datos
DB_HOST=localhost
DB_PORT=3306
DB_NAME=inventario_insumos_v1
DB_USER=tu_usuario
DB_PASS=tu_contraseña
# DB_SOCKET=/var/run/mysqld/mysqld.sock  # Opcional: si usas socket

# Aplicación
APP_BASE_URL=/inventario_app
```

O configurar variables en el servidor web (preferido para producción).

### Paso 6: Configurar Permisos

```bash
# Directorio de uploads debe ser escribible
chmod 755 public/uploads

# Asegurar que el servidor web pueda escribir
chown -R www-data:www-data public/uploads
```

### Paso 7: Configurar Servidor Web

#### Apache (con .htaccess)

```apache
<VirtualHost *:80>
    ServerName inventario.tudominio.com
    DocumentRoot /var/www/inventario_insumos_v1
    
    <Directory /var/www/inventario_insumos_v1>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Proteger directorio includes
    <Directory /var/www/inventario_insumos_v1/includes>
        Require all denied
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/inventario_error.log
    CustomLog ${APACHE_LOG_DIR}/inventario_access.log combined
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name inventario.tudominio.com;
    root /var/www/inventario_insumos_v1;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Proteger directorio includes
    location ~ ^/includes/ {
        deny all;
        return 403;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

### Paso 8: Verificar Instalación

Acceder a: `http://tudominio.com/pages/dashboard.php`

Deberías ver el dashboard vacío sin errores.

---

## 🧹 Limpiar Sistema para Producción

Si instalaste el sistema con datos de prueba y quieres limpiarlo:

### Opción 1: Script Automático (Recomendado)

```bash
# IMPORTANTE: Hacer backup primero
mysqldump -u root -p inventario_insumos_v1 > backup_antes_limpieza.sql

# Ejecutar script de limpieza
mysql -u root -p inventario_insumos_v1 < sql/limpiar_para_produccion.sql
```

Este script:
- ✅ Limpia remitos y asignaciones
- ✅ Limpia historial de bajas
- ✅ Limpia secuencia de remitos (**asegura que el primer remito sea 0001**)
- ✅ Resetea insumos a estado "Disponible"
- ✅ **MANTIENE** configuración (sedes, áreas, licitaciones, etc.)

### Opción 2: Limpieza Manual

Si prefieres hacerlo manualmente:

```sql
-- 1. Limpiar datos transaccionales
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE remitos_detalle;
TRUNCATE TABLE remitos;
TRUNCATE TABLE remito_secuencia;  -- ¡IMPORTANTE! Para que inicie en 1
TRUNCATE TABLE historial_bajas;
TRUNCATE TABLE ingresos_detalle;
TRUNCATE TABLE ingresos;
SET FOREIGN_KEY_CHECKS = 1;

-- 2. Resetear insumos
UPDATE insumos SET 
    estado = 'Disponible',
    id_sede_actual = NULL,
    id_area_asignacion_actual = NULL;
```

---

## 🔢 Verificar Numeración de Remitos

### Garantía de Numeración Correcta

El sistema **automáticamente** iniciará en `0001_YYYY` si la tabla `remito_secuencia` está vacía.

**Cómo funciona:**

1. Al crear el primer remito, la función `generarNumeroRemito()`:
   - Busca registro para el año actual en `remito_secuencia`
   - Si no existe, inserta con `ultimo = 0`
   - Lee `ultimo` (será 0)
   - Suma 1 → primer número = 1
   - Retorna `0001_2025` (formato con padding)

### Verificar Manualmente

```sql
-- Verificar que la tabla está vacía
SELECT * FROM remito_secuencia;
-- Resultado esperado: 0 filas

-- Verificar que no hay remitos
SELECT COUNT(*) FROM remitos;
-- Resultado esperado: 0

-- Si hay datos residuales, limpiar
TRUNCATE TABLE remito_secuencia;
```

### Forzar Número Específico (Si es necesario)

```sql
-- Si por alguna razón necesitas empezar desde un número específico:
INSERT INTO remito_secuencia (anio, ultimo) VALUES (2025, 0);
-- El siguiente remito será 0001_2025

-- O si quieres empezar desde 100:
INSERT INTO remito_secuencia (anio, ultimo) VALUES (2025, 99);
-- El siguiente remito será 0100_2025
```

---

## 📊 Configuración Inicial Recomendada

### 1. Configurar Sedes y Áreas

```sql
-- Las sedes y áreas deberían venir en el SQL inicial
-- Verificar:
SELECT * FROM sedes;
SELECT * FROM areas;

-- Si necesitas agregar más:
INSERT INTO sedes (nombre_sede, direccion, id_localidad) 
VALUES ('Sede Central', 'Calle Principal 123', 1);

INSERT INTO areas (nombre_area, descripcion) 
VALUES ('Informática', 'Área de TI');
```

### 2. Asociar Áreas a Sedes

```sql
-- Verificar relaciones
SELECT * FROM sede_areas;

-- Agregar si es necesario
INSERT INTO sede_areas (id_sede, id_area) VALUES (1, 1);
```

### 3. Crear Primer Insumo de Prueba

Acceder a: `Insumos → Agregar Insumo` desde el navegador.

### 4. Crear Primera Asignación

Acceder a: `Asignaciones → Nueva Asignación`

**El primer remito generado será:** `0001_2025` ✅

---

## 🔒 Seguridad en Producción

### 1. Proteger Archivos Sensibles

```bash
# .env no debe ser accesible desde web
chmod 600 .env

# Incluir en .htaccess o nginx config
# Deny access to .env
<Files ".env">
    Require all denied
</Files>
```

### 2. Configurar HTTPS

```bash
# Certbot para Apache
sudo certbot --apache -d inventario.tudominio.com

# Certbot para Nginx
sudo certbot --nginx -d inventario.tudominio.com
```

### 3. Actualizar Permisos

```bash
# Archivos PHP solo lectura
find . -type f -name "*.php" -exec chmod 644 {} \;

# Directorios
find . -type d -exec chmod 755 {} \;

# Uploads escribible
chmod 755 public/uploads
```

---

## 📝 Post-Instalación

### Checklist Final

- [ ] Dashboard carga sin errores
- [ ] Puedes crear un insumo
- [ ] Puedes crear una asignación
- [ ] El primer remito es `0001_YYYY`
- [ ] PDF se genera correctamente
- [ ] Modal de remito muestra insumos
- [ ] Pestaña "Anulados" en historial funciona
- [ ] No hay remitos anulados o devueltos de prueba

---

## 🆘 Solución de Problemas

### Problema: El primer remito no es 0001

**Causa:** La tabla `remito_secuencia` tiene datos residuales.

**Solución:**
```sql
TRUNCATE TABLE remito_secuencia;
-- El siguiente remito será 0001_YYYY
```

### Problema: Error al generar PDF

**Causa:** Dependencias de Composer no instaladas o `membretada.pdf` falta.

**Solución:**
```bash
composer install
# Verificar que existe
ls -la membretada.pdf
```

### Problema: CSS/JS no cargan

**Causa:** `APP_BASE_URL` incorrecta.

**Solución:**
```env
# En .env, ajustar según tu configuración
APP_BASE_URL=/inventario_app
# O si está en raíz del dominio:
APP_BASE_URL=
```

---

## 📞 Soporte

Para problemas o dudas:
1. Revisar logs del servidor web
2. Revisar logs de PHP
3. Verificar permisos de archivos
4. Consultar esta documentación

**Archivos de documentación:**
- `INSTRUCCIONES_MIGRACION_ANULADOS.md` - Sistema de anulación
- `RESUMEN_CAMBIOS.md` - Cambios recientes
- Este archivo - Instalación en producción

---

## 🎉 ¡Listo!

Tu sistema de gestión de insumos está instalado y configurado.

**Primer paso:** Crear tu primera asignación y verificar que el remito sea `0001_2025` ✅
