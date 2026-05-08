<?php

namespace App\Filament\Resources\RolResource\Pages;

use App\Filament\Resources\RolResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRol extends ViewRecord
{
    protected static string $resource = RolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
