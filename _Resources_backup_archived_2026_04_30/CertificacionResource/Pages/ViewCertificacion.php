<?php

namespace App\Filament\Resources\CertificacionResource\Pages;

use App\Filament\Resources\CertificacionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCertificacion extends ViewRecord
{
    protected static string $resource = CertificacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
