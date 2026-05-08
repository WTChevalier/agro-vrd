<?php

namespace App\Filament\Resources\RepartidorResource\Pages;

use App\Filament\Resources\RepartidorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRepartidores extends ListRecords
{
    protected static string $resource = RepartidorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Repartidor'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos'),
            'pendientes' => Tab::make('Pendientes')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 'pendiente'))
                ->badge(fn () => \App\Models\Repartidor::where('estado', 'pendiente')->count()),
            'aprobados' => Tab::make('Aprobados')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 'aprobado')),
            'disponibles' => Tab::make('Disponibles')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 'aprobado')->where('disponible', true)),
        ];
    }
}
