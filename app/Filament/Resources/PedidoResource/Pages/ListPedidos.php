<?php

namespace App\Filament\Resources\PedidoResource\Pages;

use App\Filament\Resources\PedidoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPedidos extends ListRecords
{
    protected static string $resource = PedidoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos'),
            'pendientes' => Tab::make('Pendientes')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('estado', 'pendiente')
                )
                ->badge(fn () =>
                    \App\Models\Pedido::where('estado', 'pendiente')->count()
                ),
            'activos' => Tab::make('Activos')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->whereIn('estado', ['confirmado', 'preparando', 'listo', 'en_camino'])
                ),
            'completados' => Tab::make('Completados')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('estado', 'entregado')
                ),
            'cancelados' => Tab::make('Cancelados')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('estado', 'cancelado')
                ),
        ];
    }
}
