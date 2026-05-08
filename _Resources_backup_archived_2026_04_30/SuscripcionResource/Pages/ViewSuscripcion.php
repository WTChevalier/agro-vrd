<?php

namespace App\Filament\Resources\SuscripcionResource\Pages;

use App\Filament\Resources\SuscripcionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSuscripcion extends ViewRecord
{
    protected static string $resource = SuscripcionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
