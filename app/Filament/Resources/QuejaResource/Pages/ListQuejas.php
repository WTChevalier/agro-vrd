<?php

namespace App\Filament\Resources\QuejaResource\Pages;

use App\Filament\Resources\QuejaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuejas extends ListRecords
{
    protected static string $resource = QuejaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}