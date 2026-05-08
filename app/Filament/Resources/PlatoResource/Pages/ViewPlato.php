<?php

namespace App\Filament\Resources\PlatoResource\Pages;

use App\Filament\Resources\PlatoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPlato extends ViewRecord
{
    protected static string $resource = PlatoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
