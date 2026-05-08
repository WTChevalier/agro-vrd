<?php

namespace App\Filament\Resources\AlgoritmoConfigResource\Pages;

use App\Filament\Resources\AlgoritmoConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAlgoritmoConfig extends EditRecord
{
    protected static string $resource = AlgoritmoConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}