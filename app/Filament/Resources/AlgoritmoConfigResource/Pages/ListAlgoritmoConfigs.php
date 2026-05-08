<?php

namespace App\Filament\Resources\AlgoritmoConfigResource\Pages;

use App\Filament\Resources\AlgoritmoConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAlgoritmoConfigs extends ListRecords
{
    protected static string $resource = AlgoritmoConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}