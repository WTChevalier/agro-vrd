<?php

namespace App\Filament\Resources\SuscripcionResource\Pages;

use App\Filament\Resources\SuscripcionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSuscripcion extends CreateRecord
{
    protected static string $resource = SuscripcionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
