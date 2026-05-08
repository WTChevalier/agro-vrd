<?php

namespace App\Filament\Resources\LealtadRecompensaResource\Pages;

use App\Filament\Resources\LealtadRecompensaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLealtadRecompensa extends EditRecord
{
    protected static string $resource = LealtadRecompensaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}