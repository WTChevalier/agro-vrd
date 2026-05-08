<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrecioDinamicoConfigResource\Pages;
use App\Models\PrecioDinamicoConfig;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PrecioDinamicoConfigResource extends Resource
{
    protected static ?string $model = PrecioDinamicoConfig::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static \UnitEnum|string|null $navigationGroup = 'Algoritmo';
    protected static ?string $navigationLabel = 'Precios Dinámicos';
    protected static ?string $modelLabel = 'Precio Dinámico';
    protected static ?string $pluralModelLabel = 'Precios Dinámicos';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Schemas\Components\Section::make('Configuración General')->schema([
                Forms\Components\TextInput::make('nombre')->required()->maxLength(255),
                Forms\Components\Select::make('aplica_a')
                    ->options([
                        'delivery' => '🚚 Delivery',
                        'productos' => '🍔 Productos',
                        'comision' => '💰 Comisión',
                    ])->required(),
                Forms\Components\Toggle::make('activo')->default(true),
            ])->columns(3),

            Schemas\Components\Section::make('Factores de Precio')
                ->description('Configura los factores que afectan el precio dinámico')
                ->schema([
                    Forms\Components\KeyValue::make('factores')
                        ->keyLabel('Factor')
                        ->valueLabel('Configuración (JSON)')
                        ->addActionLabel('Agregar Factor')
                        ->helperText('Ej: demanda_alta, hora_pico, clima, repartidores_escasos'),
                ]),

            Schemas\Components\Section::make('Límites del Multiplicador')->schema([
                Forms\Components\TextInput::make('multiplicador_minimo')
                    ->numeric()->step(0.01)->default(0.80)
                    ->helperText('Mínimo: 0.80 = 20% descuento'),
                Forms\Components\TextInput::make('multiplicador_maximo')
                    ->numeric()->step(0.01)->default(2.00)
                    ->helperText('Máximo: 2.00 = 100% aumento'),
            ])->columns(2),

            Schemas\Components\Section::make('Comunicación al Usuario')->schema([
                Forms\Components\Toggle::make('mostrar_explicacion')
                    ->label('Mostrar explicación al usuario')
                    ->default(true),
                Forms\Components\TextInput::make('mensaje_surge')
                    ->label('Mensaje de Surge Pricing')
                    ->placeholder('Alta demanda en tu zona. Precio ajustado.')
                    ->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('aplica_a')->badge()
                    ->colors([
                        'primary' => 'delivery',
                        'success' => 'productos',
                        'warning' => 'comision',
                    ]),
                Tables\Columns\TextColumn::make('multiplicador_minimo')
                    ->label('Mín')->suffix('x'),
                Tables\Columns\TextColumn::make('multiplicador_maximo')
                    ->label('Máx')->suffix('x'),
                Tables\Columns\IconColumn::make('mostrar_explicacion')->boolean(),
                Tables\Columns\IconColumn::make('activo')->boolean(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrecioDinamicoConfigs::route('/'),
            'create' => Pages\CreatePrecioDinamicoConfig::route('/create'),
            'edit' => Pages\EditPrecioDinamicoConfig::route('/{record}/edit'),
        ];
    }
}