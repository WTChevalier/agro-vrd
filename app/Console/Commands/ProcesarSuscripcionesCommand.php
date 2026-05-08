<?php

namespace App\Console\Commands;

use App\Services\GestorSuscripcionesService;
use Illuminate\Console\Command;

/**
 * Comando para procesar suscripciones vencidas y enviar recordatorios
 *
 * Uso: php artisan sazonrd:procesar-suscripciones
 */
class ProcesarSuscripcionesCommand extends Command
{
    protected $signature = 'sazonrd:procesar-suscripciones
                            {--dry-run : Simular sin hacer cambios}';

    protected $description = 'Procesa suscripciones vencidas, envía recordatorios y aplica restricciones';

    public function handle(GestorSuscripcionesService $gestor): int
    {
        $this->info('🔄 Procesando suscripciones...');
        $this->newLine();

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️ Modo simulación - No se harán cambios reales');
            $this->newLine();
        }

        // Obtener resumen actual
        $resumenAntes = $gestor->obtenerResumen();

        $this->info('📊 Estado actual de suscripciones:');
        $this->table(
            ['Estado', 'Cantidad'],
            [
                ['Activas', $resumenAntes['activas']],
                ['Pendientes de pago', $resumenAntes['pendientes']],
                ['Vencidas', $resumenAntes['vencidas']],
                ['Suspendidas', $resumenAntes['suspendidas']],
            ]
        );

        $this->newLine();
        $this->info("💰 MRR actual: RD$ " . number_format($resumenAntes['mrr'], 2));
        $this->newLine();

        if (!$dryRun) {
            // Procesar
            $resultados = $gestor->procesarVencidas();

            $this->info('📋 Acciones realizadas:');
            $this->table(
                ['Acción', 'Cantidad'],
                [
                    ['Avisos de renovación enviados', $resultados['avisos_enviados']],
                    ['Suscripciones marcadas como vencidas', $resultados['vencidas']],
                    ['Suscripciones suspendidas', $resultados['suspendidas']],
                ]
            );

            // Resumen después
            $resumenDespues = $gestor->obtenerResumen();

            $this->newLine();
            $this->info('📊 Nuevo estado de suscripciones:');
            $this->table(
                ['Estado', 'Cantidad'],
                [
                    ['Activas', $resumenDespues['activas']],
                    ['Pendientes de pago', $resumenDespues['pendientes']],
                    ['Vencidas', $resumenDespues['vencidas']],
                    ['Suspendidas', $resumenDespues['suspendidas']],
                ]
            );
        }

        $this->newLine();
        $this->info('✅ Procesamiento completado');

        return Command::SUCCESS;
    }
}
