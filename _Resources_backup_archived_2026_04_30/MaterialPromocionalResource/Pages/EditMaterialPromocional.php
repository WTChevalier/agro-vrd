<?php

namespace App\Filament\Resources\MaterialPromocionalResource\Pages;

use App\Filament\Resources\MaterialPromocionalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaterialPromocional extends EditRecord
{
    protected static string $resource = MaterialPromocionalResource::class;

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
