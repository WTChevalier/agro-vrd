<?php

namespace App\Filament\Resources\CertificacionResource\Pages;

use App\Filament\Resources\CertificacionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCertificacion extends CreateRecord
{
    protected static string $resource = CertificacionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
