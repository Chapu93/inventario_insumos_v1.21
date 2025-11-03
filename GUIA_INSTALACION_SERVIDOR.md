# 🚀 Guía de Instalación en Servidor Ubuntu 24

## Problema: Estilos y Tooltips No Cargan

Si después de copiar el proyecto a tu servidor Ubuntu los estilos del footer y los tooltips no funcionan, sigue esta guía:

---

## 📋 Paso 1: Diagnóstico

1. **Accede al archivo de diagnóstico:**
   ```
   http://tu-servidor/ruta-al-proyecto/diagnostico.php
   ```

2. **Revisa los resultados:**
   - ✅ Todos los archivos deben existir y ser legibles
   - ✅ Las URLs generadas deben ser correctas
   - ✅ `app_base_url()` debe devolver la ruta correcta

---

## 🔧 Paso 2: Configurar la Ruta Base

### Opción A: Crear archivo `.env` (RECOMENDADO)

1. **Copia el ejemplo:**
   ```bash
   cd /ruta/al/proyecto
   cp .env.example .env
   ```

2. **Edita el archivo `.env`:**
   ```bash
   nano .env
   ```

3. **Configura `APP_BASE_URL` según tu instalación:**

   **Ejemplo 1 - Proyecto en subdirectorio:**
   ```env
   # URL: http://tuservidor.com/inventario_app
   APP_BASE_URL=/inventario_app
   ```

   **Ejemplo 2 - Proyecto en subdirectorio profundo:**
   ```env
   # URL: http://tuservidor.com/sistemas/senaf/inventario
   APP_BASE_URL=/sistemas/senaf/inventario
   ```

   **Ejemplo 3 - Proyecto en raíz:**
   ```env
   # URL: http://inventario.tuservidor.com
   APP_BASE_URL=
   ```

4. **Guarda y cierra** (Ctrl+O, Enter, Ctrl+X en nano)

### Opción B: Configurar Variable de Entorno en Apache

Si prefieres configurarlo en Apache, edita tu VirtualHost:

```bash
sudo nano /etc/apache2/sites-available/tu-sitio.conf
```

Agrega dentro de `<VirtualHost>`:
```apache
SetEnv APP_BASE_URL "/inventario_app"
```

Reinicia Apache:
```bash
sudo systemctl restart apache2
```

---

## 🗂️ Paso 3: Verificar Permisos de Archivos

Los archivos CSS/JS deben ser legibles por el servidor web:

```bash
cd /ruta/al/proyecto

# Verificar propietario (debe ser www-data o el usuario de Apache)
ls -la public/css/style.css
ls -la public/js/main.js

# Si es necesario, corregir permisos:
sudo chown -R www-data:www-data public/
sudo chmod -R 755 public/

# O si usas otro usuario (ej: ubuntu):
sudo chown -R ubuntu:www-data public/
sudo chmod -R 755 public/
```

---

## 🧹 Paso 4: Limpiar Caché del Navegador

Esto es **MUY IMPORTANTE** - a menudo es la única causa del problema:

### Chrome/Edge/Brave:
- Windows/Linux: `Ctrl + Shift + R` o `Ctrl + F5`
- Mac: `Cmd + Shift + R`

### Firefox:
- Windows/Linux: `Ctrl + Shift + R` o `Ctrl + F5`
- Mac: `Cmd + Shift + R`

### Safari:
- `Cmd + Option + E` (vaciar caché) y luego `Cmd + R`

### Método alternativo (todos los navegadores):
1. Abre las **Herramientas de Desarrollo** (F12)
2. Haz clic derecho en el botón de recargar
3. Selecciona **"Vaciar caché y recargar de forma forzada"**

---

## 🔍 Paso 5: Verificar en el Navegador

1. **Abre las Herramientas de Desarrollo** (F12)

2. **Ve a la pestaña "Network" o "Red"**

3. **Recarga la página** (F5)

4. **Busca estos archivos:**
   - `style.css` - Debe cargar con estado **200 OK**
   - `main.js` - Debe cargar con estado **200 OK**

5. **Si ves errores 404:**
   - La ruta `APP_BASE_URL` está mal configurada
   - Vuelve al Paso 2

6. **Si ves errores 403:**
   - Problema de permisos
   - Vuelve al Paso 3

---

## 🐛 Paso 6: Verificar Errores de PHP

```bash
# Ver últimos errores de Apache
sudo tail -f /var/log/apache2/error.log

# O en Nginx
sudo tail -f /var/log/nginx/error.log
```

Recarga la página y observa si aparecen errores relacionados con `filemtime()` o archivos no encontrados.

---

## ⚙️ Paso 7: Configuración Específica de Apache/Nginx

### Apache - Habilitar mod_rewrite (si usas URLs amigables):

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Apache - Permitir .htaccess:

Edita el VirtualHost:
```bash
sudo nano /etc/apache2/sites-available/tu-sitio.conf
```

Asegúrate de tener:
```apache
<Directory /ruta/al/proyecto>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

### Nginx - Configuración básica:

```nginx
location /inventario_app {
    alias /ruta/al/proyecto;
    index index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $request_filename;
    }
    
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

---

## 🧪 Paso 8: Prueba Manual de URLs

Intenta acceder directamente a:

```
http://tu-servidor/ruta-al-proyecto/public/css/style.css
http://tu-servidor/ruta-al-proyecto/public/js/main.js
```

**Deberías ver el código CSS y JavaScript respectivamente.**

Si obtienes 404, tu `APP_BASE_URL` está mal configurada.

---

## 🆘 Problemas Comunes y Soluciones

### Problema: "El CSS carga pero los tooltips no funcionan"

**Solución:**
```bash
# Verifica que main.js se esté cargando
# En la consola del navegador (F12) ejecuta:
typeof inicializarTooltips
# Debe devolver "function", no "undefined"
```

Si devuelve "undefined", main.js no se está cargando. Vuelve al Paso 4 (limpiar caché).

### Problema: "filemtime(): stat failed"

**Causa:** El path al archivo es incorrecto.

**Solución:** Verifica que el archivo existe:
```bash
ls -la /ruta/al/proyecto/public/js/main.js
```

### Problema: "APP_BASE_URL está vacío"

**Solución:** Asegúrate de:
1. Tener un archivo `.env` con `APP_BASE_URL` definido
2. O configurar la variable de entorno en Apache/Nginx
3. O el sistema intentará detectarla automáticamente (puede fallar)

### Problema: "Los estilos cargan pero se ven rotos"

**Causa:** Bootstrap o Font Awesome no cargan desde CDN.

**Solución:** 
1. Verifica tu conexión a internet desde el servidor
2. Intenta acceder: https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css

---

## ✅ Checklist Final

Antes de contactar soporte, verifica:

- [ ] Ejecuté `diagnostico.php` y revisé los resultados
- [ ] Configuré correctamente `APP_BASE_URL` en `.env`
- [ ] Verifiqué permisos de archivos (755 para directorios, 644 para archivos)
- [ ] Limpié la caché del navegador con **Ctrl+Shift+R** o **Ctrl+F5**
- [ ] Verifiqué en Network (F12) que los archivos cargan con 200 OK
- [ ] Los archivos CSS y JS son accesibles directamente por URL
- [ ] No hay errores en `/var/log/apache2/error.log` o consola del navegador

---

## 📚 Bibliotecas Necesarias

El proyecto **NO requiere instalar bibliotecas adicionales en el servidor**. Todas las dependencias frontend (Bootstrap, Font Awesome, DataTables, etc.) se cargan desde CDN.

**Solo necesitas:**
- PHP 7.4+ (recomendado 8.1+)
- Apache 2.4+ o Nginx
- MySQL/MariaDB
- Extensiones PHP: `pdo_mysql`, `mbstring`, `json`

**Verificar extensiones:**
```bash
php -m | grep -E 'pdo_mysql|mbstring|json'
```

---

## 🎯 TL;DR - Solución Rápida

**99% de los casos se solucionan con:**

```bash
# 1. Crear .env
echo "APP_BASE_URL=/tu_ruta_base" > .env

# 2. Limpiar caché del navegador
# Presiona: Ctrl + Shift + R (o Ctrl + F5)
```

**¡Eso es todo!** 🚀
