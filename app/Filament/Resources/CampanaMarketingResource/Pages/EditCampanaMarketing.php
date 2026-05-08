<?php

namespace App\Filament\Resources\CampanaMarketingResource\Pages;

use App\Filament\Resources\CampanaMarketingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCampanaMarketing extends EditRecord
{
    protected static string $resource = CampanaMarketingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}