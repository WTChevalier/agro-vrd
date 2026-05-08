<?php

namespace App\Filament\Resources\ApiExternaResource\Pages;

use App\Filament\Resources\ApiExternaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApiExternas extends ListRecords
{
    protected static string $resource = ApiExternaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}