<?php

namespace App\Filament\Resources\RepartidorResource\Pages;

use App\Filament\Resources\RepartidorResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRepartidor extends ViewRecord
{
    protected static string $resource = RepartidorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
