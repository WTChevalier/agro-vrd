<?php

namespace App\Filament\Resources\MaterialPromocionalResource\Pages;

use App\Filament\Resources\MaterialPromocionalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterialesPromocionales extends ListRecords
{
    protected static string $resource = MaterialPromocionalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
