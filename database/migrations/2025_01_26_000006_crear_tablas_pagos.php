<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Métodos de pago guardados por usuario
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->enum('tipo', ['tarjeta', 'cuenta_bancaria'])->default('tarjeta');
            $table->string('proveedor')->comment('stripe, paypal, etc');
            $table->string('id_proveedor')->nullable();
            $table->string('ultimos_cuatro', 4)->nullable();
            $table->string('marca')->nullable()->comment('visa, mastercard, etc');
            $table->string('mes_expiracion', 2)->nullable();
            $table->string('ano_expiracion', 4)->nullable();
            $table->string('nombre_titular')->nullable();
            $table->boolean('predeterminado')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['usuario_id', 'predeterminado']);
        });

        // Transacciones de pago
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('metodo_pago_id')->nullable()->constrained('metodos_pago')->nullOnDelete();

            $table->string('proveedor')->comment('stripe, paypal, efectivo, transferencia');
            $table->string('id_transaccion_proveedor')->nullable();
            $table->decimal('monto', 12, 2);
            $table->string('moneda', 3)->default('DOP');
            $table->enum('estado', ['pendiente', 'procesando', 'completado', 'fallido', 'reembolsado', 'cancelado'])->default('pendiente');
            $table->text('motivo_fallo')->nullable();
            $table->json('respuesta_proveedor')->nullable();
            $table->timestamp('completado_en')->nullable();
            $table->timestamps();

            $table->index(['pedido_id', 'estado']);
        });

        // Reembolsos
        Schema::create('reembolsos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos')->cascadeOnDelete();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('procesado_por')->nullable()->constrained('usuarios')->nullOnDelete();

            $table->decimal('monto', 12, 2);
            $table->string('motivo');
            $table->text('notas')->nullable();
            $table->string('id_reembolso_proveedor')->nullable();
            $table->enum('estado', ['pendiente', 'procesando', 'completado', 'fallido'])->default('pendiente');
            $table->timestamp('completado_en')->nullable();
            $table->timestamps();
        });

        // Billetera/Saldo del usuario
        Schema::create('transacciones_billetera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->foreignId('pago_id')->nullable()->constrained('pagos')->nullOnDelete();

            $table->enum('tipo', ['credito', 'debito']);
            $table->enum('concepto', ['recarga', 'pago_pedido', 'reembolso', 'bono', 'cashback', 'ajuste'])->default('ajuste');
            $table->decimal('monto', 12, 2);
            $table->decimal('saldo_despues', 12, 2);
            $table->string('descripcion');
            $table->string('referencia')->nullable();
            $table->enum('estado', ['pendiente', 'completado', 'cancelado'])->default('completado');
            $table->timestamps();

            $table->index(['usuario_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacciones_billetera');
        Schema::dropIfExists('reembolsos');
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('metodos_pago');
    }
};
