<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tablas de Materiales Promocionales
 *
 * Crea las tablas para:
 * - Inventario de materiales
 * - Kits por plan
 * - Entregas de materiales
 * - QR codes de restaurantes
 * - Estadísticas de escaneos
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // MATERIALES PROMOCIONALES
        // Inventario de materiales disponibles
        // =====================================================================
        Schema::create('materiales_promocionales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique(); // 'sticker_puerta_15x15', 'placa_cert_a', etc.
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->string('tipo', 50); // 'sticker', 'display', 'placa', 'flyer', 'uniforme', 'otro'
            $table->string('tamano', 50)->nullable(); // '15x15cm', '20x25cm', etc.
            $table->string('material_fabricacion', 100)->nullable(); // 'vinil', 'acrilico', 'papel', etc.
            $table->decimal('costo_unitario', 10, 2)->default(0);
            $table->decimal('precio_venta', 10, 2)->nullable(); // Si se vende aparte
            $table->integer('stock_actual')->default(0);
            $table->integer('stock_minimo')->default(10); // Alerta cuando baja de este número
            $table->integer('stock_maximo')->nullable();
            $table->string('imagen')->nullable();
            $table->json('imagenes_adicionales')->nullable();
            $table->string('proveedor')->nullable();
            $table->integer('tiempo_produccion_dias')->nullable();
            $table->boolean('requiere_personalizacion')->default(true); // Necesita nombre del restaurante
            $table->boolean('requiere_certificacion')->default(false); // Solo para certificación específica
            $table->foreignId('certificacion_minima_id')->nullable()->constrained('certificaciones')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tipo');
            $table->index('activo');
            $table->index('stock_actual');
        });

        // =====================================================================
        // KITS POR PLAN
        // Qué materiales incluye cada plan
        // =====================================================================
        Schema::create('kits_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('planes')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materiales_promocionales')->cascadeOnDelete();
            $table->integer('cantidad')->default(1);
            $table->boolean('es_inicial')->default(true); // Se da al contratar
            $table->boolean('es_renovable')->default(false); // Se puede pedir más
            $table->integer('renovacion_cada_meses')->nullable(); // Cada cuántos meses se puede renovar
            $table->integer('maximo_renovaciones')->nullable(); // Límite de renovaciones
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'material_id']);
        });

        // =====================================================================
        // ENTREGAS DE MATERIALES
        // Registro de materiales entregados a restaurantes
        // =====================================================================
        Schema::create('entregas_materiales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materiales_promocionales');
            $table->integer('cantidad')->default(1);
            $table->string('motivo', 50); // 'kit_inicial', 'renovacion', 'upgrade', 'reposicion', 'promocion'
            $table->string('estado', 30)->default('pendiente'); // 'pendiente', 'en_produccion', 'listo', 'en_camino', 'entregado', 'cancelado'
            $table->text('personalizacion')->nullable(); // Datos para personalizar (nombre, etc.)
            $table->foreignId('solicitado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('entregado_por')->nullable()->constrained('personal')->nullOnDelete();
            $table->date('fecha_solicitud')->nullable();
            $table->date('fecha_produccion')->nullable();
            $table->date('fecha_entrega')->nullable();
            $table->string('foto_entrega')->nullable(); // Evidencia de entrega
            $table->json('fotos_adicionales')->nullable();
            $table->text('notas')->nullable();
            $table->text('notas_entrega')->nullable();
            $table->decimal('costo', 10, 2)->nullable();
            $table->boolean('cobrado')->default(false);
            $table->timestamps();

            $table->index('estado');
            $table->index('motivo');
            $table->index(['restaurante_id', 'created_at']);
        });

        // =====================================================================
        // MOVIMIENTOS DE INVENTARIO
        // Entradas y salidas de materiales
        // =====================================================================
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materiales_promocionales')->cascadeOnDelete();
            $table->string('tipo', 30); // 'entrada', 'salida', 'ajuste', 'devolucion'
            $table->integer('cantidad'); // Positivo para entrada, negativo para salida
            $table->integer('stock_anterior');
            $table->integer('stock_nuevo');
            $table->string('motivo', 100)->nullable();
            $table->foreignId('entrega_id')->nullable()->constrained('entregas_materiales')->nullOnDelete();
            $table->string('referencia')->nullable(); // Número de factura, orden, etc.
            $table->decimal('costo_unitario', 10, 2)->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();

            $table->index('tipo');
            $table->index(['material_id', 'created_at']);
        });

        // =====================================================================
        // QR CODES DE RESTAURANTES
        // Códigos QR únicos para cada restaurante
        // =====================================================================
        Schema::create('qr_restaurantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->unique()->constrained('restaurantes')->cascadeOnDelete();
            $table->string('codigo_unico', 50)->unique(); // 'elconuco', 'sabor-criollo'
            $table->string('url_corta', 100)->unique(); // sazonrd.com/r/elconuco
            $table->string('url_completa', 255);
            $table->string('imagen_qr')->nullable(); // URL de la imagen QR generada
            $table->integer('escaneos_total')->default(0);
            $table->integer('escaneos_mes')->default(0);
            $table->integer('conversiones_total')->default(0); // Escaneos que terminaron en pedido
            $table->timestamp('ultimo_escaneo_at')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('codigo_unico');
            $table->index('activo');
        });

        // =====================================================================
        // ESCANEOS DE QR
        // Estadísticas de escaneos de códigos QR
        // =====================================================================
        Schema::create('escaneos_qr', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_id')->constrained('qr_restaurantes')->cascadeOnDelete();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->string('fuente', 30)->nullable(); // 'puerta', 'mesa', 'flyer', 'placa', 'web', 'redes', 'desconocido'
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('dispositivo', 30)->nullable(); // 'movil', 'desktop', 'tablet'
            $table->string('sistema_operativo', 50)->nullable();
            $table->string('navegador', 50)->nullable();
            $table->string('ubicacion')->nullable(); // Ciudad aproximada por IP
            $table->json('ubicacion_geo')->nullable(); // Coordenadas si las hay
            $table->string('referer')->nullable(); // De dónde venía
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete(); // Si estaba logueado
            $table->boolean('convirtio')->default(false); // Si hizo pedido después
            $table->foreignId('pedido_id')->nullable(); // ID del pedido si convirtió
            $table->timestamps();

            $table->index(['qr_id', 'created_at']);
            $table->index('fuente');
            $table->index('convirtio');
            $table->index('created_at');
        });

        // =====================================================================
        // PLACAS DE CERTIFICACIÓN
        // Registro de placas emitidas (para verificación)
        // =====================================================================
        Schema::create('placas_certificacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->foreignId('certificacion_id')->constrained('certificaciones');
            $table->foreignId('entrega_id')->nullable()->constrained('entregas_materiales')->nullOnDelete();
            $table->string('codigo_placa', 30)->unique(); // SR-2026-00156
            $table->string('codigo_verificacion', 20)->unique(); // Para QR de verificación
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->string('estado', 30)->default('activa'); // 'activa', 'vencida', 'revocada', 'reemplazada'
            $table->text('motivo_revocacion')->nullable();
            $table->foreignId('reemplazada_por')->nullable()->constrained('placas_certificacion')->nullOnDelete();
            $table->integer('verificaciones_count')->default(0); // Veces que escanearon para verificar
            $table->timestamp('ultima_verificacion_at')->nullable();
            $table->timestamps();

            $table->index('codigo_placa');
            $table->index('codigo_verificacion');
            $table->index('estado');
            $table->index(['restaurante_id', 'estado']);
            $table->index('fecha_vencimiento');
        });

        // =====================================================================
        // VERIFICACIONES DE PLACA
        // Cuando alguien escanea el QR de una placa
        // =====================================================================
        Schema::create('verificaciones_placa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placa_id')->constrained('placas_certificacion')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ubicacion')->nullable();
            $table->json('ubicacion_geo')->nullable();
            $table->boolean('placa_valida')->default(true); // Si la placa era válida al momento
            $table->string('resultado', 30); // 'valida', 'vencida', 'revocada', 'no_encontrada'
            $table->timestamps();

            $table->index(['placa_id', 'created_at']);
            $table->index('resultado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verificaciones_placa');
        Schema::dropIfExists('placas_certificacion');
        Schema::dropIfExists('escaneos_qr');
        Schema::dropIfExists('qr_restaurantes');
        Schema::dropIfExists('movimientos_inventario');
        Schema::dropIfExists('entregas_materiales');
        Schema::dropIfExists('kits_plan');
        Schema::dropIfExists('materiales_promocionales');
    }
};
