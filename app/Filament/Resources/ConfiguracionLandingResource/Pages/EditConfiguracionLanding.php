<?php

namespace App\Filament\Resources\ConfiguracionLandingResource\Pages;

use App\Filament\Resources\ConfiguracionLandingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConfiguracionLanding extends EditRecord
{
    protected static string $resource = ConfiguracionLandingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}