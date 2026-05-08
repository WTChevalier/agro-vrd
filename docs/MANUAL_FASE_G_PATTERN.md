# Manual Fase G — Replicar Pattern a Tenants Comerciales
**Sprints:** 816-826 · **Pattern reusable:** booking + subscriptions polymórficos

## Pattern aplicado

### 2 tablas genéricas con polymorphic relations
- **`bookings`** — reservable_type/id (Event/Plato/Cita) + cliente_type/id
- **`subscriptions`** — cliente_type/id + Stripe billing + frecuencia

## Aplicación per-tenant

### GurzTicket (Sprints 816-818)
- `Booking::create(['reservable_type' => Event::class, ...])` para tickets
- `Subscription` para "season pass" (acceso a múltiples eventos)
- QR check-in via Sprint 688

### SazónRD (Sprints 819-822)
- `Subscription` para "Combo del día" (semanal, mensual)
- `Booking` para reservas en restaurantes específicos
- Cart abandonment recovery via cron

### GurzMed (Sprints 823-826)
- `Booking::create(['reservable_type' => Cita::class, 'cliente_type' => Paciente::class])`
- `Subscription` para planes de pólizas pre-pagadas
- Receta digital via Sprint 717 plan

## Steps de replicación per-tenant

1. Run migration `2026_04_30_220500_pattern_booking.php` en DB del tenant
2. Crear modelos Eloquent con relaciones polymórficas:
   ```php
   class Event extends Model {
       public function bookings() {
           return $this->morphMany(Booking::class, 'reservable');
       }
   }
   ```
3. Wire-up Stripe Subscription via `StripeService` por tenant (cuenta única + metadata.tenant)
4. Filament Resources para Booking + Subscription
5. Smoke E2E

## Schedules añadidos por tenant
- `bookings:enviar-recordatorios` daily 18:00 (24h antes)
- `subscriptions:procesar-overdue` daily 04:00
- `subscriptions:next-billing-alert` daily 09:00

## TO-DOs documentados (siguiente sesión)
- [ ] Run migration en cada tenant DB (visi_rd, sazo_dbrd, gurz_dbT1quet3s, gurzmed_app)
- [ ] Crear modelos polymórficos por tenant
- [ ] Filament Resources Booking + Subscription
- [ ] Wire-up Stripe per tenant con Price IDs
- [ ] Recordatorios email + SMS (Twilio integration)
- [ ] Cancellation flow self-service
