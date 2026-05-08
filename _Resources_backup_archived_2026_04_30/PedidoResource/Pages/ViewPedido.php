<?php

namespace App\Filament\Resources\PedidoResource\Pages;

use App\Filament\Resources\PedidoResource;
use App\Models\EstadoPedido;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewPedido extends ViewRecord
{
    protected static string $resource = PedidoResource::class;

    protected function getHeaderActions(): array
    {
        $pedido = $this->record;

        return [
            Actions\Action::make('confirmar')
                ->label('Confirmar')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn () => $pedido->estado?->codigo === 'pendiente')
                ->requiresConfirmation()
                ->action(function () use ($pedido) {
                    $pedido->cambiarEstado('confirmado');
                    Notification::make()
                        ->title('Pedido confirmado')
                        ->success()
                        ->send();
                    $this->redirect(PedidoResource::getUrl('view', ['record' => $pedido]));
                }),

            Actions\Action::make('preparando')
                ->label('Iniciar Preparación')
                ->icon('heroicon-o-fire')
                ->color('warning')
                ->visible(fn () => $pedido->estado?->codigo === 'confirmado')
                ->requiresConfirmation()
                ->action(function () use ($pedido) {
                    $pedido->cambiarEstado('preparando');
                    Notification::make()
                        ->title('Pedido en preparación')
                        ->success()
                        ->send();
                    $this->redirect(PedidoResource::getUrl('view', ['record' => $pedido]));
                }),

            Actions\Action::make('listo')
                ->label('Marcar Listo')
                ->icon('heroicon-o-check-circle')
                ->color('info')
                ->visible(fn () => $pedido->estado?->codigo === 'preparando')
                ->requiresConfirmation()
                ->action(function () use ($pedido) {
                    $pedido->cambiarEstado('listo_recoger');
                    Notification::make()
                        ->title('Pedido listo para recoger')
                        ->success()
                        ->send();
                    $this->redirect(PedidoResource::getUrl('view', ['record' => $pedido]));
                }),

            Actions\Action::make('cancelar')
                ->label('Cancelar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($pedido->estado?->codigo, ['pendiente', 'confirmado']))
                ->requiresConfirmation()
                ->modalHeading('Cancelar Pedido')
                ->modalDescription('¿Estás seguro de cancelar este pedido?')
                ->form([
                    \Filament\Forms\Components\Textarea::make('motivo')
                        ->label('Motivo de cancelación')
                        ->required(),
                ])
                ->action(function (array $data) use ($pedido) {
                    $pedido->cancelar($data['motivo'], auth()->id());
                    Notification::make()
                        ->title('Pedido cancelado')
                        ->success()
                        ->send();
                    $this->redirect(PedidoResource::getUrl('view', ['record' => $pedido]));
                }),

            Actions\EditAction::make(),
        ];
    }
}
