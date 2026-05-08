<?php

namespace App\Filament\Resources\SuscripcionResource\Pages;

use App\Filament\Resources\SuscripcionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Suscripcion;

class ListSuscripciones extends ListRecords
{
    protected static string $resource = SuscripcionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todas' => Tab::make('Todas')
                ->badge(Suscripcion::count()),

            'activas' => Tab::make('Activas')
                ->badge(Suscripcion::where('estado', 'activa')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 'activa')),

            'pendientes' => Tab::make('Pendientes')
                ->badge(Suscripcion::where('estado', 'pendiente')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 'pendiente')),

            'vencidas' => Tab::make('Vencidas')
                ->badge(Suscripcion::where('estado', 'vencida')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 'vencida')),

            'suspendidas' => Tab::make('Suspendidas')
                ->badge(Suscripcion::where('estado', 'suspendida')->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 'suspendida')),
        ];
    }
}
