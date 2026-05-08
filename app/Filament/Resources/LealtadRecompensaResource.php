<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LealtadRecompensaResource\Pages;
use App\Models\LealtadRecompensa;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LealtadRecompensaResource extends Resource
{
    protected static ?string $model = LealtadRecompensa::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-gift';
    protected static \UnitEnum|string|null $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Recompensas';
    protected static ?string $modelLabel = 'Recompensa';
    protected static ?string $pluralModelLabel = 'Recompensas';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Schemas\Components\Section::make('Información de la Recompensa')->schema([
                Forms\Components\TextInput::make('nombre')->required()->maxLength(255),
                Forms\Components\Textarea::make('descripcion')->rows(2),
                Forms\Components\Select::make('tipo')
                    ->options([
                        'descuento' => '🏷️ Descuento',
                        'producto_gratis' => '🎁 Producto Gratis',
                        'delivery_gratis' => '🚚 Delivery Gratis',
                        'upgrade' => '⬆️ Upgrade',
                        'experiencia' => '✨ Experiencia',
                    ])->required(),
                Forms\Components\TextInput::make('puntos_requeridos')
                    ->numeric()->required()->suffix('puntos'),
            ])->columns(2),

            Schemas\Components\Section::make('Valor')->schema([
                Forms\Components\TextInput::make('valor')->numeric()->prefix('RD$')
                    ->helperText('Valor monetario de la recompensa'),
                Forms\Components\TextInput::make('descuento_porcentaje')
                    ->numeric()->suffix('%')->maxValue(100),
                Forms\Components\TextInput::make('dias_validez')
                    ->numeric()->default(30)->suffix('días'),
            ])->columns(3),

            Schemas\Components\Section::make('Límites')->schema([
                Forms\Components\TextInput::make('max_canjes_por_usuario')->numeric()
                    ->helperText('Dejar vacío para ilimitado'),
                Forms\Components\TextInput::make('max_canjes_totales')->numeric()
                    ->helperText('Stock total disponible'),
                Forms\Components\TextInput::make('canjes_actuales')->numeric()->disabled(),
            ])->columns(3),

            Schemas\Components\Section::make('Vigencia')->schema([
                Forms\Components\DatePicker::make('fecha_inicio'),
                Forms\Components\DatePicker::make('fecha_fin'),
                Forms\Components\Toggle::make('activo')->default(true),
                Forms\Components\TextInput::make('orden')->numeric()->default(0),
            ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tipo')->badge()
                    ->colors([
                        'primary' => 'descuento',
                        'success' => 'producto_gratis',
                        'info' => 'delivery_gratis',
                        'warning' => 'upgrade',
                        'danger' => 'experiencia',
                    ]),
                Tables\Columns\TextColumn::make('puntos_requeridos')
                    ->numeric()->sortable()->suffix(' pts'),
                Tables\Columns\TextColumn::make('descuento_porcentaje')->suffix('%'),
                Tables\Columns\TextColumn::make('canjes_actuales')
                    ->label('Canjes'),
                Tables\Columns\IconColumn::make('activo')->boolean(),
            ])
            ->reorderable('orden')
            ->defaultSort('orden')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo'),
                Tables\Filters\TernaryFilter::make('activo'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLealtadRecompensas::route('/'),
            'create' => Pages\CreateLealtadRecompensa::route('/create'),
            'edit' => Pages\EditLealtadRecompensa::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('activo', true)->count();
    }
}