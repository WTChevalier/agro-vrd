<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlgoritmoConfigResource\Pages;
use App\Models\AlgoritmoConfig;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AlgoritmoConfigResource extends Resource
{
    protected static ?string $model = AlgoritmoConfig::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static \UnitEnum|string|null $navigationGroup = 'Algoritmo';
    protected static ?string $navigationLabel = 'Configuración';
    protected static ?string $modelLabel = 'Algoritmo';
    protected static ?string $pluralModelLabel = 'Algoritmos';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Schemas\Components\Section::make('Información General')->schema([
                Forms\Components\TextInput::make('nombre')->required()->maxLength(255),
                Forms\Components\Textarea::make('descripcion')->rows(2),
                Forms\Components\Select::make('tipo')
                    ->options([
                        'ranking_restaurantes' => 'Ranking Restaurantes',
                        'ranking_platos' => 'Ranking Platos',
                        'asignacion_repartidores' => 'Asignación Repartidores',
                        'recomendaciones' => 'Recomendaciones',
                        'busqueda' => 'Búsqueda',
                        'precios_dinamicos' => 'Precios Dinámicos',
                    ])->required(),
                Forms\Components\TextInput::make('version')->default('1.0'),
            ])->columns(2),

            Schemas\Components\Section::make('Factores y Pesos')->schema([
                Forms\Components\KeyValue::make('factores')
                    ->keyLabel('Factor')
                    ->valueLabel('Configuración (JSON)')
                    ->addActionLabel('Agregar Factor')
                    ->reorderable(),
            ]),

            Schemas\Components\Section::make('Configuración Avanzada')->schema([
                Forms\Components\KeyValue::make('configuracion')
                    ->keyLabel('Parámetro')
                    ->valueLabel('Valor'),
                Forms\Components\TextInput::make('cache_duracion_minutos')
                    ->numeric()->default(15)->suffix('minutos'),
            ]),

            Schemas\Components\Section::make('Estado')->schema([
                Forms\Components\Toggle::make('es_version_activa')->label('Versión Activa'),
                Forms\Components\Toggle::make('activo')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tipo')->badge()
                    ->colors([
                        'primary' => 'ranking_restaurantes',
                        'success' => 'asignacion_repartidores',
                        'warning' => 'precios_dinamicos',
                    ]),
                Tables\Columns\TextColumn::make('version')->badge()->color('gray'),
                Tables\Columns\IconColumn::make('es_version_activa')->boolean()->label('Activa'),
                Tables\Columns\IconColumn::make('activo')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo'),
                Tables\Filters\TernaryFilter::make('activo'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('activar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['es_version_activa' => true])),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlgoritmoConfigs::route('/'),
            'create' => Pages\CreateAlgoritmoConfig::route('/create'),
            'edit' => Pages\EditAlgoritmoConfig::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('activo', true)->count();
    }
}