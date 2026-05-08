# 🚀 Checklist de Despliegue - SazónRD

## Pre-Despliegue

### 1. Verificar Requisitos del Servidor
- [ ] PHP 8.2+ instalado
- [ ] Extensiones PHP: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, GD/Imagick
- [ ] Composer 2.x instalado
- [ ] Node.js 18+ y NPM instalados
- [ ] MySQL 8.0+ o MariaDB 10.6+
- [ ] Redis (opcional, recomendado para caché y colas)

### 2. Configurar Archivo .env
```bash
cp .env.example .env
```

Variables críticas a configurar:
```env
APP_NAME="SazónRD"
APP_ENV=production
APP_KEY=  # Se genera con: php artisan key:generate
APP_DEBUG=false
APP_URL=https://sazonrd.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sazonrd
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password

# Caché y Sesiones
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Correo
MAIL_MAILER=smtp
MAIL_HOST=smtp.tuservidor.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@sazonrd.com
MAIL_FROM_NAME="SazónRD"

# SSO con VisitRD (si aplica)
VISITRD_SSO_ENABLED=false
VISITRD_CLIENT_ID=
VISITRD_CLIENT_SECRET=
VISITRD_REDIRECT_URI=
```

## Despliegue

### 3. Instalar Dependencias
```bash
# Dependencias PHP
composer install --optimize-autoloader --no-dev

# Dependencias JavaScript
npm ci
npm run build
```

### 4. Generar Clave de Aplicación
```bash
php artisan key:generate
```

### 5. Ejecutar Migraciones
```bash
# Primera vez (base de datos vacía)
php artisan migrate

# Con datos iniciales
php artisan db:seed
```

**⚠️ IMPORTANTE:** Los seeders crean:
- Permisos y Roles del sistema
- Planes de suscripción
- Niveles de confianza
- Certificaciones
- Módulos del sistema
- Provincias y municipios de RD
- Configuración global inicial

### 6. Crear Enlaces Simbólicos
```bash
php artisan storage:link
```

### 7. Cachear Configuración
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
php artisan filament:cache-components
```

### 8. Configurar Permisos de Carpetas
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 9. Crear Usuario Administrador
```bash
php artisan make:filament-user
```
O directamente en la base de datos con el seeder de usuarios.

## Post-Despliegue

### 10. Configurar Cron Jobs
Añadir al crontab del servidor:
```bash
* * * * * cd /ruta/a/sazonrd && php artisan schedule:run >> /dev/null 2>&1
```

### 11. Configurar Supervisor para Colas
Crear `/etc/supervisor/conf.d/sazonrd-worker.conf`:
```ini
[program:sazonrd-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/a/sazonrd/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/ruta/a/sazonrd/storage/logs/worker.log
stopwaitsecs=3600
```

Luego:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sazonrd-worker:*
```

### 12. Configurar Nginx
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name sazonrd.com www.sazonrd.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name sazonrd.com www.sazonrd.com;
    root /ruta/a/sazonrd/public;

    ssl_certificate /etc/letsencrypt/live/sazonrd.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/sazonrd.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Comandos Artisan Personalizados

### Recalcular Confianza de Restaurantes
```bash
# Todos los restaurantes
php artisan sazonrd:recalcular-confianza

# Un restaurante específico
php artisan sazonrd:recalcular-confianza --restaurante=123

# Forzar recálculo aunque sea reciente
php artisan sazonrd:recalcular-confianza --forzar
```

### Procesar Suscripciones
```bash
# Ejecutar procesamiento
php artisan sazonrd:procesar-suscripciones

# Simular sin cambios (dry run)
php artisan sazonrd:procesar-suscripciones --dry-run
```

## URLs del Sistema

| Panel | URL | Acceso |
|-------|-----|--------|
| Admin | `/admin` | Personal SazónRD |
| Restaurante | `/restaurante` | Dueños de restaurantes |
| API | `/api/v1` | Aplicaciones móviles |

## Verificación Post-Despliegue

- [ ] Acceder a `/admin` y verificar login
- [ ] Verificar que los módulos aparecen en Sistema > Módulos
- [ ] Verificar que los planes aparecen en Suscripciones > Planes
- [ ] Crear un restaurante de prueba
- [ ] Verificar que las notificaciones funcionan
- [ ] Verificar que las colas están procesando
- [ ] Revisar logs en `storage/logs/laravel.log`

## Troubleshooting

### Error 500 después del despliegue
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
composer dump-autoload
```

### Permisos de archivos
```bash
sudo chown -R www-data:www-data /ruta/a/sazonrd
sudo find /ruta/a/sazonrd -type f -exec chmod 644 {} \;
sudo find /ruta/a/sazonrd -type d -exec chmod 755 {} \;
sudo chmod -R 775 storage bootstrap/cache
```

### Migraciones fallan
```bash
# Ver estado de migraciones
php artisan migrate:status

# Rollback y reintentar
php artisan migrate:rollback
php artisan migrate
```

## Contacto Soporte Técnico

En caso de problemas durante el despliegue, contactar al equipo de desarrollo.

---

**Versión del documento:** 1.0
**Última actualización:** Enero 2025
