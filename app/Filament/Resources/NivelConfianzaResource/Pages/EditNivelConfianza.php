<?php

namespace App\Filament\Resources\NivelConfianzaResource\Pages;

use App\Filament\Resources\NivelConfianzaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNivelConfianza extends EditRecord
{
    protected static string $resource = NivelConfianzaResource::class;

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
