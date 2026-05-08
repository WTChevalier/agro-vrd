<?php

namespace App\Filament\Resources\PersonalResource\Pages;

use App\Filament\Resources\PersonalResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreatePersonal extends CreateRecord
{
    protected static string $resource = PersonalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generar código de empleado si no se proporcionó
        if (empty($data['codigo_empleado'])) {
            $data['codigo_empleado'] = 'EMP-' . strtoupper(Str::random(6));
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
