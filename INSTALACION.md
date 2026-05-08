# SazónRD - Guía de Instalación

## Requisitos del Sistema

- PHP >= 8.2
- MySQL 8.0+ o MariaDB 10.6+
- Composer 2.x
- Node.js 18+ y NPM
- Extensiones PHP: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, GD

## Instalación

### 1. Subir archivos al servidor

Sube todos los archivos de la carpeta `SazonRD` a `/home/sazonrd.com/public_html/`

### 2. Crear base de datos

En CyberPanel > Databases > Create Database:
- **Nombre:** sazon_rd
- **Usuario:** sazon_user
- **Password:** (generar uno seguro)

### 3. Configurar el archivo .env

```bash
cp .env.example .env
```

Editar `.env` con los datos correctos:

```env
APP_NAME="SazónRD"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sazonrd.com

DB_DATABASE=sazon_rd
DB_USERNAME=sazon_user
DB_PASSWORD=tu_password_aqui

# Conexión a visitRD para sincronización
VISITRD_DB_HOST=127.0.0.1
VISITRD_DB_DATABASE=visi_rd
VISITRD_DB_USERNAME=visi_user
VISITRD_DB_PASSWORD=password_visitrd

# SSO con visitRD
VISITRD_SSO_URL=https://visitrepublicadominicana.com
VISITRD_API_KEY=generar_api_key_segura
```

### 4. Instalar dependencias

```bash
cd /home/sazonrd.com/public_html

# Instalar dependencias PHP
composer install --no-dev --optimize-autoloader

# Generar key de aplicación
php artisan key:generate

# Instalar dependencias JavaScript
npm install
npm run build
```

### 5. Ejecutar migraciones y seeders

```bash
# Crear tablas
php artisan migrate

# Cargar datos iniciales
php artisan db:seed

# Crear link simbólico para storage
php artisan storage:link

# Limpiar y optimizar
php artisan optimize:clear
php artisan optimize
```

### 6. Configurar permisos

```bash
chmod -R 755 storage bootstrap/cache
chown -R sazonrd:sazonrd storage bootstrap/cache
```

### 7. Configurar cron job

En CyberPanel > Cron Jobs, agregar:

```
* * * * * cd /home/sazonrd.com/public_html && php artisan schedule:run >> /dev/null 2>&1
```

### 8. Configurar queue worker (opcional pero recomendado)

Crear un servicio systemd para el queue worker:

```bash
sudo nano /etc/systemd/system/sazonrd-worker.service
```

```ini
[Unit]
Description=SazónRD Queue Worker
After=network.target

[Service]
User=sazonrd
Group=sazonrd
Restart=always
ExecStart=/usr/bin/php /home/sazonrd.com/public_html/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable sazonrd-worker
sudo systemctl start sazonrd-worker
```

## Crear usuario administrador

```bash
php artisan tinker
```

```php
\App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@sazonrd.com',
    'password' => bcrypt('password_seguro'),
    'role' => 'admin',
    'is_active' => true,
    'email_verified_at' => now(),
]);
```

## URLs de acceso

- **Sitio público:** https://sazonrd.com
- **Panel Admin:** https://sazonrd.com/admin
- **Panel Restaurante:** https://sazonrd.com/restaurante
- **Panel Repartidor:** https://sazonrd.com/repartidor

## Sincronización con visitRD

### En visitRD (servidor que envía)

Agregar webhook para sincronizar restaurantes:

```php
// Cuando se crea/actualiza un restaurante en visitRD
Http::withHeaders([
    'X-API-Key' => config('services.sazonrd.api_key'),
])->post('https://sazonrd.com/api/v1/sync/restaurants', [
    'visitrd_id' => $restaurant->id,
    'name' => $restaurant->name,
    'slug' => $restaurant->slug,
    // ... otros campos
]);
```

### En SazónRD (servidor que recibe)

El endpoint `/api/v1/sync/restaurants` ya está configurado para recibir y procesar la sincronización.

## Configuración de pagos (Stripe)

1. Crear cuenta en Stripe
2. Obtener API keys
3. Configurar en `.env`:

```env
STRIPE_KEY=pk_live_xxx
STRIPE_SECRET=sk_live_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
```

4. Configurar webhook en Stripe Dashboard:
   - URL: `https://sazonrd.com/webhooks/stripe`
   - Eventos: `payment_intent.succeeded`, `payment_intent.failed`

## Solución de problemas

### Error 500
```bash
php artisan optimize:clear
chmod -R 755 storage bootstrap/cache
```

### Migraciones fallan
```bash
php artisan migrate:status
php artisan migrate:fresh --seed  # ⚠️ Borra todos los datos
```

### Cache no se actualiza
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

## Soporte

Para soporte técnico, contactar a: soporte@sazonrd.com
