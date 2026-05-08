<?php

namespace App\Filament\Resources\LandingConfigResource\Pages;

use App\Filament\Resources\LandingConfigResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLandingConfig extends EditRecord
{
    protected static string $resource = LandingConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
