<?php

namespace App\Console\Commands;

use App\Services\CalculadorConfianzaService;
use Illuminate\Console\Command;

/**
 * Comando para recalcular la puntuación de confianza de todos los restaurantes
 *
 * Uso: php artisan sazonrd:recalcular-confianza
 */
class RecalcularConfianzaCommand extends Command
{
    protected $signature = 'sazonrd:recalcular-confianza
                            {--restaurante= : ID de un restaurante específico}
                            {--forzar : Forzar recálculo incluso si fue reciente}';

    protected $description = 'Recalcula la puntuación de confianza de los restaurantes';

    public function handle(CalculadorConfianzaService $calculador): int
    {
        $this->info('🔄 Iniciando recálculo de puntuaciones de confianza...');
        $this->newLine();

        $restauranteId = $this->option('restaurante');

        if ($restauranteId) {
            // Recalcular un restaurante específico
            $restaurante = \App\Models\Restaurante::find($restauranteId);

            if (!$restaurante) {
                $this->error("❌ Restaurante con ID {$restauranteId} no encontrado");
                return Command::FAILURE;
            }

            $puntuacionAnterior = $restaurante->puntuacion_confianza;
            $calculador->calcularYActualizar($restaurante);

            $this->info("✅ Restaurante: {$restaurante->nombre}");
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Puntuación anterior', $puntuacionAnterior],
                    ['Puntuación nueva', $restaurante->puntuacion_confianza],
                    ['Nivel de confianza', $restaurante->nivelConfianza?->nombre ?? 'N/A'],
                ]
            );

            return Command::SUCCESS;
        }

        // Recalcular todos
        $bar = $this->output->createProgressBar();
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $bar->setMessage('Iniciando...');

        $resultados = $calculador->recalcularTodos();

        $bar->finish();
        $this->newLine(2);

        $this->info('📊 Resumen del recálculo:');
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Restaurantes procesados', $resultados['procesados']],
                ['Puntuaciones actualizadas', $resultados['actualizados']],
                ['Errores', count($resultados['errores'])],
            ]
        );

        if (!empty($resultados['errores'])) {
            $this->newLine();
            $this->warn('⚠️ Errores encontrados:');
            foreach ($resultados['errores'] as $error) {
                $this->line("  - Restaurante #{$error['restaurante_id']}: {$error['error']}");
            }
        }

        $this->newLine();
        $this->info('✅ Recálculo completado');

        return Command::SUCCESS;
    }
}
