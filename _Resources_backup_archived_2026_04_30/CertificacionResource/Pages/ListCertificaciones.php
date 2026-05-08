<?php

namespace App\Filament\Resources\CertificacionResource\Pages;

use App\Filament\Resources\CertificacionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCertificaciones extends ListRecords
{
    protected static string $resource = CertificacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
