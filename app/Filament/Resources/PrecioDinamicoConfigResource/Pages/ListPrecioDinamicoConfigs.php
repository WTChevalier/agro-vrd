<?php

namespace App\Filament\Resources\PrecioDinamicoConfigResource\Pages;

use App\Filament\Resources\PrecioDinamicoConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrecioDinamicoConfigs extends ListRecords
{
    protected static string $resource = PrecioDinamicoConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}