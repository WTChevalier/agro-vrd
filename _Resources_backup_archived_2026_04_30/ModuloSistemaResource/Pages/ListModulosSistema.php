<?php

namespace App\Filament\Resources\ModuloSistemaResource\Pages;

use App\Filament\Resources\ModuloSistemaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModulosSistema extends ListRecords
{
    protected static string $resource = ModuloSistemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
