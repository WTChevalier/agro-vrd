<?php

namespace App\Filament\Resources\LandingBlockResource\Pages;

use App\Filament\Resources\LandingBlockResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLandingBlock extends EditRecord
{
    protected static string $resource = LandingBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
