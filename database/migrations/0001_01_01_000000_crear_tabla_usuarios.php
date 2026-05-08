<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visitrd_usuario_id')->nullable()->unique()->comment('ID del usuario en visitRD para SSO');
            $table->string('nombre');
            $table->string('apellido')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verificado_en')->nullable();
            $table->string('password')->nullable()->comment('Nullable para usuarios SSO');
            $table->string('telefono', 20)->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('avatar')->nullable();
            $table->enum('rol', ['cliente', 'dueno_restaurante', 'repartidor', 'admin'])->default('cliente');
            $table->boolean('activo')->default(true);
            $table->json('direccion_predeterminada')->nullable();
            $table->decimal('saldo_billetera', 10, 2)->default(0);
            $table->integer('puntos_lealtad')->default(0);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tokens_recuperacion_password', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('creado_en')->nullable();
        });

        Schema::create('sesiones', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('usuario_id')->nullable()->index();
            $table->string('direccion_ip', 45)->nullable();
            $table->text('agente_usuario')->nullable();
            $table->longText('payload');
            $table->integer('ultima_actividad')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones');
        Schema::dropIfExists('tokens_recuperacion_password');
        Schema::dropIfExists('usuarios');
    }
};
