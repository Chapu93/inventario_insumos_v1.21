# Plan de Implementación: Sistema de Backups Automáticos para SITIA

Este documento contiene la especificación y las instrucciones de configuración para implementar el sistema de backups automáticos en el futuro. 

El sistema está diseñado para ser portable entre desarrollo (XAMPP/MariaDB) y producción (MySQL clásico), respaldando la base de datos, el código de la aplicación y las subidas de los usuarios de forma segura.

---

## 1. Ubicación y Seguridad de los Backups

Por seguridad, los respaldos se guardarán en la carpeta del perfil de usuario de Linux (`~/backups_sitia/` o `$HOME/backups_sitia`). 

Al colocarlos fuera del directorio de Apache (`/opt/lampp/htdocs/`), se evita que la base de datos o archivos sensibles puedan ser descargados desde el navegador web.

---

## 2. Script de Backup (`scripts/backup.sh`)

Crear un archivo en la ruta `/opt/lampp/htdocs/inventario_app/scripts/backup.sh` con el siguiente contenido:

```bash
#!/bin/bash
# ==============================================================================
# Script de Backup Automático Local para SITIA
# ==============================================================================

# Timezone y fecha para los nombres de archivo
export TZ="America/Argentina/Buenos_Aires"
DATE=$(date +"%Y-%m-%d_%H-%M-%S")

# Directorios de la Aplicación
APP_DIR="/opt/lampp/htdocs/inventario_app"
BACKUP_DIR="$HOME/backups_sitia"

# Crear directorio de backup si no existe
mkdir -p "$BACKUP_DIR"

# Cargar variables de entorno del archivo .env si existe
if [ -f "$APP_DIR/.env" ]; then
    export $(grep -v '^#' "$APP_DIR/.env" | xargs)
fi

# Credenciales de Base de Datos
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-joaquin}"
DB_PASS="${DB_PASS:-12345678}"
DB_NAME="${DB_NAME:-inventario_insumos_v1}"

# Detectar binario mysqldump (Genérico para Producción o XAMPP para Desarrollo)
if command -v mysqldump &> /dev/null; then
    MYSQLDUMP="mysqldump"
elif [ -f "/opt/lampp/bin/mysqldump" ]; then
    MYSQLDUMP="/opt/lampp/bin/mysqldump"
else
    echo "[$(date)] ERROR: No se encontró mysqldump en el sistema ni en XAMPP." >&2
    exit 1
fi

echo "[$(date)] Iniciando backup de SITIA en $BACKUP_DIR..."

# 1. RESPALDO DE BASE DE DATOS
echo "[$(date)] Respaldando base de datos: $DB_NAME con $MYSQLDUMP..."
DB_FILE="$BACKUP_DIR/db_${DB_NAME}_$DATE.sql"
$MYSQLDUMP -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$DB_FILE"

if [ $? -eq 0 ]; then
    gzip -f "$DB_FILE"
    echo "[$(date)] Base de datos respaldada y comprimida."
else
    echo "[$(date)] ERROR: Fallo al respaldar la base de datos." >&2
fi

# 2. RESPALDO DE ARCHIVOS SUBIDOS (UPLOADS)
echo "[$(date)] Respaldando archivos subidos (uploads)..."
UPLOADS_FILE="$BACKUP_DIR/uploads_${DATE}.tar.gz"
tar -czf "$UPLOADS_FILE" -C "$APP_DIR" uploads

if [ $? -eq 0 ]; then
    echo "[$(date)] Archivos subidos respaldados."
else
    echo "[$(date)] ERROR: Fallo al respaldar uploads." >&2
fi

# 3. RESPALDO DEL CÓDIGO FUENTE
echo "[$(date)] Respaldando código fuente del sistema..."
APP_FILE="$BACKUP_DIR/sistema_${DATE}.tar.gz"
tar -czf "$APP_FILE" -C "$APP_DIR" --exclude='uploads' --exclude='backups' --exclude='.git' .

if [ $? -eq 0 ]; then
    echo "[$(date)] Código fuente respaldado."
else
    echo "[$(date)] ERROR: Fallo al respaldar código fuente." >&2
fi

# 4. POLÍTICA DE RETENCIÓN (Eliminar archivos de más de 15 días)
echo "[$(date)] Aplicando política de retención local..."
find "$BACKUP_DIR" -type f -mtime +15 -exec rm {} \;

echo "[$(date)] Proceso de backup finalizado."
echo "--------------------------------------------------"
```

---

## 3. Asignación de Permisos

Una vez creado el archivo, se le deben otorgar permisos de ejecución para que el programador de tareas de Linux pueda correrlo:

```bash
chmod +x /opt/lampp/htdocs/inventario_app/scripts/backup.sh
```

---

## 4. Automatización con Cron (Tarea Programada en Linux)

Para que el script se ejecute automáticamente todas las noches a las **03:00 AM**:

1. Abre el crontab del usuario del servidor:
   ```bash
   crontab -e
   ```
2. Añade la siguiente línea al final del archivo (reemplaza `usuario` con el nombre de usuario de tu perfil del sistema en el servidor):
   ```cron
   0 3 * * * /bin/bash /opt/lampp/htdocs/inventario_app/scripts/backup.sh >> /home/usuario/backups_sitia/backup.log 2>&1
   ```

---

## 5. Opcional: Configuración Futura para Push a GitHub (Repositorio Privado)

Si en el futuro deseas que los backups se suban automáticamente a un repositorio privado de GitHub, puedes seguir estos pasos:

### Modificación al Script de Backup
Añadir estas líneas al final de `backup.sh` (antes de la línea de finalización):

```bash
# Sincronización a GitHub
echo "[$(date)] Sincronizando con repositorio de GitHub..."
cd "$BACKUP_DIR"

if [ ! -d ".git" ]; then
    git init
    echo "*.log" > .gitignore
    git add .gitignore
    git commit -m "Commit inicial de backups"
    # Reemplazar con tu repo privado
    git remote add origin git@github.com:usuario/tu-repo-backups.git
fi

git add .
git commit -m "Backup automático - $DATE"
BRANCH=$(git symbolic-ref --short -q HEAD || echo "main")
git push origin "$BRANCH"
```

### Autenticación en el Servidor
Para que la subida funcione sin pedir contraseña, debes configurar en el servidor una clave SSH sin contraseña y registrar la clave pública (`id_rsa.pub`) en las configuraciones del repositorio de GitHub.
