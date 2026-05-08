<?php

namespace App\Filament\Resources\RestauranteResource\Pages;

use App\Filament\Resources\RestauranteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantes extends ListRecords
{
    protected static string $resource = RestauranteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Restaurante'),
        ];
    }
}
