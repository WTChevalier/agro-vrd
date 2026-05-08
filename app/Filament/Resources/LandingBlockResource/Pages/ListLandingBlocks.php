<?php

namespace App\Filament\Resources\LandingBlockResource\Pages;

use App\Filament\Resources\LandingBlockResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLandingBlocks extends ListRecords
{
    protected static string $resource = LandingBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
