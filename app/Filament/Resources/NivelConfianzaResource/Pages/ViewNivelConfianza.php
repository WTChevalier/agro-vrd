<?php

namespace App\Filament\Resources\NivelConfianzaResource\Pages;

use App\Filament\Resources\NivelConfianzaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewNivelConfianza extends ViewRecord
{
    protected static string $resource = NivelConfianzaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
