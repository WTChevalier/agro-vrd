<?php

namespace App\Filament\Resources\LandingConfigResource\Pages;

use App\Filament\Resources\LandingConfigResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLandingConfigs extends ListRecords
{
    protected static string $resource = LandingConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
