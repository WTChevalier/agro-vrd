<?php

namespace App\Filament\Resources\CampanaMarketingResource\Pages;

use App\Filament\Resources\CampanaMarketingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCampanaMarketings extends ListRecords
{
    protected static string $resource = CampanaMarketingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}