<?php

namespace App\Filament\Resources\ApiExternaResource\Pages;

use App\Filament\Resources\ApiExternaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApiExterna extends EditRecord
{
    protected static string $resource = ApiExternaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}