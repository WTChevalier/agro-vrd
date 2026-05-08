<?php

namespace App\Filament\Resources\ModuloSistemaResource\Pages;

use App\Filament\Resources\ModuloSistemaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateModuloSistema extends CreateRecord
{
    protected static string $resource = ModuloSistemaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
