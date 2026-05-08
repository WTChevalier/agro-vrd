<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-credit-card';

    protected static \UnitEnum|string|null $navigationGroup = 'Suscripciones';

    protected static ?string $navigationLabel = 'Planes';

    protected static ?string $modelLabel = 'Plan';

    protected static ?string $pluralModelLabel = 'Planes';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('Identificador único del plan'),

                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(3)
                            ->maxLength(500),

                        Forms\Components\ColorPicker::make('color')
                            ->label('Color')
                            ->default('#f59e0b'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('Precios')
                    ->schema([
                        Forms\Components\TextInput::make('precio_mensual')
                            ->label('Precio Mensual')
                            ->numeric()
                            ->prefix('RD$')
                            ->required()
                            ->default(0),

                        Forms\Components\TextInput::make('precio_anual')
                            ->label('Precio Anual')
                            ->numeric()
                            ->prefix('RD$')
                            ->helperText('Dejar vacío para calcular automáticamente'),

                        Forms\Components\TextInput::make('descuento_anual')
                            ->label('Descuento Anual')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100),

                        Forms\Components\TextInput::make('dias_prueba')
                            ->label('Días de Prueba')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(4),

                Schemas\Components\Section::make('Límites')
                    ->schema([
                        Forms\Components\TextInput::make('limite_platos')
                            ->label('Límite de Platos')
                            ->numeric()
                            ->default(0)
                            ->helperText('0 = ilimitado'),

                        Forms\Components\TextInput::make('limite_imagenes')
                            ->label('Límite de Imágenes')
                            ->numeric()
                            ->default(0)
                            ->helperText('0 = ilimitado'),

                        Forms\Components\TextInput::make('limite_categorias')
                            ->label('Límite de Categorías')
                            ->numeric()
                            ->default(0)
                            ->helperText('0 = ilimitado'),

                        Forms\Components\TextInput::make('limite_sucursales')
                            ->label('Límite de Sucursales')
                            ->numeric()
                            ->default(1),

                        Forms\Components\TextInput::make('limite_usuarios')
                            ->label('Límite de Usuarios')
                            ->numeric()
                            ->default(1),

                        Forms\Components\TextInput::make('limite_reportes')
                            ->label('Límite de Reportes')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),

                Schemas\Components\Section::make('Funciones Incluidas')
                    ->schema([
                        Forms\Components\KeyValue::make('funciones')
                            ->label('Funciones')
                            ->keyLabel('Código de Función')
                            ->valueLabel('Habilitado')
                            ->addActionLabel('Agregar función')
                            ->columnSpanFull(),
                    ]),

                Schemas\Components\Section::make('Configuración')
                    ->schema([
                        Forms\Components\Toggle::make('destacado')
                            ->label('Plan Destacado')
                            ->helperText('Se mostrará como recomendado'),

                        Forms\Components\Toggle::make('es_gratuito')
                            ->label('Es Gratuito')
                            ->reactive(),

                        Forms\Components\Toggle::make('es_personalizado')
                            ->label('Es Personalizado')
                            ->helperText('Para planes tipo "Cadena" o especiales'),

                        Forms\Components\Toggle::make('visible')
                            ->label('Visible')
                            ->default(true)
                            ->helperText('Mostrar en el catálogo de planes'),

                        Forms\Components\Toggle::make('activo')
                            ->label('Activo')
                            ->default(true),

                        Forms\Components\TextInput::make('orden')
                            ->label('Orden de Visualización')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')
                    ->label(''),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('precio_mensual')
                    ->label('Precio/mes')
                    ->money('DOP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('suscripciones_count')
                    ->label('Suscriptores')
                    ->counts('suscripciones')
                    ->sortable(),

                Tables\Columns\IconColumn::make('destacado')
                    ->label('Destacado')
                    ->boolean(),

                Tables\Columns\IconColumn::make('visible')
                    ->label('Visible')
                    ->boolean(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('orden')
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Activo'),

                Tables\Filters\TernaryFilter::make('visible')
                    ->label('Visible'),

                Tables\Filters\TernaryFilter::make('destacado')
                    ->label('Destacado'),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('orden');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlanes::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'view' => Pages\ViewPlan::route('/{record}'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) static::getModel()::count();
        } catch (\Exception $e) {
            return null;
        }
    }
}
