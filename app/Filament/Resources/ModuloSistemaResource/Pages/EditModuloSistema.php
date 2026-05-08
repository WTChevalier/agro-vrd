<?php

namespace App\Filament\Resources\ModuloSistemaResource\Pages;

use App\Filament\Resources\ModuloSistemaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModuloSistema extends EditRecord
{
    protected static string $resource = ModuloSistemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
