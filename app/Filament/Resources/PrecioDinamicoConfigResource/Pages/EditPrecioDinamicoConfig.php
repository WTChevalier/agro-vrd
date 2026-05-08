<?php

namespace App\Filament\Resources\PrecioDinamicoConfigResource\Pages;

use App\Filament\Resources\PrecioDinamicoConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrecioDinamicoConfig extends EditRecord
{
    protected static string $resource = PrecioDinamicoConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}