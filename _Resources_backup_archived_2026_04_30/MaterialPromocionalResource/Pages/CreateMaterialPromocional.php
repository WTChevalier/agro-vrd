<?php

namespace App\Filament\Resources\MaterialPromocionalResource\Pages;

use App\Filament\Resources\MaterialPromocionalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaterialPromocional extends CreateRecord
{
    protected static string $resource = MaterialPromocionalResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
