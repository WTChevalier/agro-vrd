<?php

namespace App\Filament\Resources\RestauranteResource\Pages;

use App\Filament\Resources\RestauranteResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRestaurante extends ViewRecord
{
    protected static string $resource = RestauranteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
