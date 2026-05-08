<?php
/**
 * Sprints 816-826 Fase G — Pattern reusable booking + subscriptions.
 *
 * Genérico para tenants comerciales:
 *   - GurzTicket: events booking + ticket sales
 *   - SazónRD: meal subscriptions + cart
 *   - GurzMed: citas + receta digital
 *
 * Diseño: tablas con campos polymórficos (entidad_type + entidad_id).
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Bookings genéricos
        if (! Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $t) {
                $t->id();
                $t->morphs('reservable'); // Event, Plato, Cita, Clase, etc.
                $t->morphs('cliente');    // User, Paciente, Alumno
                $t->datetime('fecha_para');
                $t->string('estado', 20)->default('pending'); // pending, confirmed, attended, canceled, no_show
                $t->decimal('monto', 10, 2)->default(0);
                $t->string('currency', 3)->default('USD');
                $t->string('stripe_payment_intent_id', 100)->nullable();
                $t->string('estado_pago', 20)->default('pending');
                $t->datetime('confirmado_at')->nullable();
                $t->datetime('cancelado_at')->nullable();
                $t->json('metadata')->nullable();
                $t->timestamps();
                $t->index(['estado', 'fecha_para']);
                $t->index('estado_pago');
            });
        }

        // Subscriptions genéricas
        if (! Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $t) {
                $t->id();
                $t->morphs('cliente');
                $t->string('plan_slug', 50);
                $t->string('stripe_subscription_id', 100)->nullable();
                $t->string('stripe_customer_id', 100)->nullable();
                $t->string('estado', 20)->default('active');
                $t->date('fecha_inicio');
                $t->date('fecha_fin')->nullable();
                $t->date('next_billing_at')->nullable();
                $t->decimal('monto_recurrente', 10, 2);
                $t->string('currency', 3)->default('USD');
                $t->string('frecuencia', 20)->default('monthly'); // monthly, weekly, annually
                $t->json('metadata')->nullable();
                $t->timestamps();
                $t->index('estado');
                $t->index('next_billing_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('bookings');
    }
};
