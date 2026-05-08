<?php

namespace App\Filament\Resources\NivelConfianzaResource\Pages;

use App\Filament\Resources\NivelConfianzaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNivelConfianza extends CreateRecord
{
    protected static string $resource = NivelConfianzaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
