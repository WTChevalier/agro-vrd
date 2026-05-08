<?php

namespace App\Filament\Resources\RestauranteResource\Pages;

use App\Filament\Resources\RestauranteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurante extends CreateRecord
{
    protected static string $resource = RestauranteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['aprobado_en'] = now();
        return $data;
    }
}
