<?php

/**
 * Sprint 139KK — Monitor Sistema page con actions de actualización.
 * Compatible Filament 3.x. Para Filament 5 ver MonitorSistema_v5.php.
 *
 * Última revisión: 2026-04-28 — WT Chevalier (Founder)
 */

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;

class MonitorSistema extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Monitor Sistema';
    protected static ?string $title = 'Monitor Sistema';
    protected static ?int $navigationSort = 99;
    protected string $view = 'filament.pages.monitor-sistema';

    protected function getActions(): array
    {
        return [
            Action::make('refrescar')
                ->label('Refrescar escaneo')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    Artisan::call('stack:check-updates', ['--silent' => true]);
                    Notification::make()
                        ->title('Escaneo completado')
                        ->body('Recarga la página para ver los datos nuevos.')
                        ->success()
                        ->send();
                }),

            Action::make('aplicar_parches')
                ->label('Aplicar parches seguros')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('¿Aplicar parches seguros?')
                ->modalDescription(new \Illuminate\Support\HtmlString(
                    '<p>Solo se actualizarán paquetes con bump tipo <strong>patch</strong> (semver Z en X.Y.Z) — bug fixes y mejoras menores, sin breaking changes.</p>' .
                    '<p style="margin-top:8px;"><strong>Garantías:</strong></p>' .
                    '<ul style="margin-left:20px; list-style: disc;">' .
                    '<li>Backup automático de composer.json + composer.lock</li>' .
                    '<li>Smoke test post-update (verifica /admin responde)</li>' .
                    '<li>Rollback automático si algo falla</li>' .
                    '<li>Audit log de la operación</li>' .
                    '</ul>' .
                    '<p style="margin-top:8px; color:#f59e0b;"><strong>Tiempo estimado: 1-3 minutos.</strong> El sitio puede estar lento o no disponible durante ese período.</p>'
                ))
                ->modalSubmitActionLabel('Sí, aplicar parches ahora')
                ->modalCancelActionLabel('Cancelar')
                ->action(function () {
                    set_time_limit(300);
                    Artisan::call('stack:apply-patches');
                    $output = Artisan::output();

                    if (str_contains(strtolower($output), 'rollback') || str_contains(strtolower($output), 'error')) {
                        Notification::make()
                            ->title('Falló — sistema restaurado')
                            ->body('Los parches no se aplicaron. Composer.json y composer.lock fueron restaurados desde backup. Ver detalles en logs.')
                            ->danger()
                            ->persistent()
                            ->send();
                    } elseif (str_contains($output, 'No hay parches pendientes')) {
                        Notification::make()
                            ->title('Nada que actualizar')
                            ->body('No hay parches pendientes en este momento.')
                            ->info()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Parches aplicados exitosamente')
                            ->body('Los paquetes fueron actualizados. Considera limpiar opcache si notas comportamiento extraño.')
                            ->success()
                            ->persistent()
                            ->send();
                    }
                }),

            Action::make('auditar_seguridad')
                ->label('Auditar seguridad')
                ->icon('heroicon-o-shield-exclamation')
                ->color('warning')
                ->action(function () {
                    Artisan::call('stack:check-updates', ['--silent' => true]);
                    Notification::make()
                        ->title('Auditoría completada')
                        ->body('Recarga la página para ver security advisories actualizados.')
                        ->warning()
                        ->send();
                }),
        ];
    }
}
