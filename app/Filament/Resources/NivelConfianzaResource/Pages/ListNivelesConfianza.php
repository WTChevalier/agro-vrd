<?php

namespace App\Filament\Resources\NivelConfianzaResource\Pages;

use App\Filament\Resources\NivelConfianzaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNivelesConfianza extends ListRecords
{
    protected static string $resource = NivelConfianzaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
