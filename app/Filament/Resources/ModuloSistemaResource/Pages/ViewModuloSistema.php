<?php

namespace App\Filament\Resources\ModuloSistemaResource\Pages;

use App\Filament\Resources\ModuloSistemaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewModuloSistema extends ViewRecord
{
    protected static string $resource = ModuloSistemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
