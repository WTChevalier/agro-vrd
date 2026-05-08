<?php

namespace App\Filament\Resources\LealtadRecompensaResource\Pages;

use App\Filament\Resources\LealtadRecompensaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLealtadRecompensas extends ListRecords
{
    protected static string $resource = LealtadRecompensaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}