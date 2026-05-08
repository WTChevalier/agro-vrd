<?php

namespace App\Filament\Resources\QuejaResource\Pages;

use App\Filament\Resources\QuejaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQueja extends EditRecord
{
    protected static string $resource = QuejaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}