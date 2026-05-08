# Manual de Administración — SazónRD
**Sprint:** 692 · **Versión:** 1.0

## 1. Acceso
URL: https://sazonrd.com/admin · 2FA recomendado

## 2. Modelos principales
- **Plato** — platos del menú (con i18n nombre + descripcion)
- **Restaurante** — restaurantes (con i18n nombre + descripcion + direccion)
- **Categoria** — categorías de comida
- **Pedido** — orders de clientes
- **ZonaDelivery** — delivery zones por barrio

## 3. Cómo cargar plato
Sidebar → Platos → Crear → llenar nombre + descripción + precio + foto.
Observer auto-traduce a 9 locales tras guardar (Sprint 627).

## 4. Comandos artisan
- `i18n:auto-traducir-sazon` — Traduce platos a 9 locales (Sprint 653)
- `sazonrd:procesar-suscripciones` — Cron 06:00 RD
- `sazonrd:recalcular-confianza` — Cron 03:00 RD

## 5. Mantenimiento
Cron schedule:run activo (Sprint 663) · Backups diarios · Logs al Hub
