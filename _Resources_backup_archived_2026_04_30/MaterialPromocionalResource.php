<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialPromocionalResource\Pages;
use App\Models\MaterialPromocional;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MaterialPromocionalResource extends Resource
{
    protected static ?string $model = MaterialPromocional::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Materiales';

    protected static ?string $modelLabel = 'Material Promocional';

    protected static ?string $pluralModelLabel = 'Materiales Promocionales';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Material')
                    ->schema([
                        Forms\Components\TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(200),

                        Forms\Components\Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('tipo')
                            ->label('Tipo')
                            ->options([
                                'sticker' => 'Sticker',
                                'display' => 'Display de Mesa',
                                'banner' => 'Banner',
                                'placa' => 'Placa',
                                'tarjeta' => 'Tarjeta',
                                'flyer' => 'Flyer',
                                'afiche' => 'Afiche',
                                'qr_stand' => 'Stand con QR',
                                'otro' => 'Otro',
                            ])
                            ->required(),

                        Forms\Components\Select::make('categoria')
                            ->label('Categoría')
                            ->options([
                                'branding' => 'Branding/Marca',
                                'certificacion' => 'Certificación',
                                'promocion' => 'Promoción',
                                'informativo' => 'Informativo',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Imágenes')
                    ->schema([
                        Forms\Components\FileUpload::make('imagen')
                            ->label('Imagen Principal')
                            ->image()
                            ->directory('materiales')
                            ->imageEditor(),

                        Forms\Components\FileUpload::make('archivo_diseno')
                            ->label('Archivo de Diseño')
                            ->directory('materiales/disenos')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/illustrator']),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Dimensiones y Especificaciones')
                    ->schema([
                        Forms\Components\TextInput::make('dimensiones')
                            ->label('Dimensiones')
                            ->maxLength(100)
                            ->helperText('Ej: 10cm x 15cm'),

                        Forms\Components\TextInput::make('material')
                            ->label('Material')
                            ->maxLength(100)
                            ->helperText('Ej: Vinilo adhesivo, Acrílico'),

                        Forms\Components\Textarea::make('especificaciones')
                            ->label('Especificaciones Técnicas')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Inventario y Precios')
                    ->schema([
                        Forms\Components\TextInput::make('stock')
                            ->label('Stock Disponible')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\TextInput::make('stock_minimo')
                            ->label('Stock Mínimo')
                            ->numeric()
                            ->default(10)
                            ->minValue(0)
                            ->helperText('Alerta cuando el stock llegue a este nivel'),

                        Forms\Components\TextInput::make('costo_unitario')
                            ->label('Costo Unitario')
                            ->numeric()
                            ->prefix('RD$')
                            ->default(0),

                        Forms\Components\TextInput::make('precio_venta')
                            ->label('Precio de Venta')
                            ->numeric()
                            ->prefix('RD$')
                            ->default(0)
                            ->helperText('0 = Gratuito'),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Disponibilidad')
                    ->schema([
                        Forms\Components\Toggle::make('incluido_en_kit')
                            ->label('Incluido en Kit de Bienvenida')
                            ->helperText('Se entrega automáticamente a nuevos restaurantes'),

                        Forms\Components\Toggle::make('disponible_tienda')
                            ->label('Disponible en Tienda')
                            ->helperText('Los restaurantes pueden solicitarlo'),

                        Forms\Components\Select::make('planes_disponibles')
                            ->label('Planes que Pueden Solicitar')
                            ->multiple()
                            ->options([
                                'vitrina' => 'Vitrina (Gratis)',
                                'basico' => 'Básico',
                                'profesional' => 'Profesional',
                                'premium' => 'Premium',
                                'cadena' => 'Cadena',
                            ])
                            ->helperText('Dejar vacío para disponible en todos'),

                        Forms\Components\Toggle::make('activo')
                            ->label('Activo')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')
                    ->label('')
                    ->square()
                    ->size(50),

                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sticker' => 'info',
                        'display' => 'success',
                        'banner' => 'warning',
                        'placa' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('categoria')
                    ->label('Categoría')
                    ->badge(),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable()
                    ->color(fn ($record) => $record->stock <= $record->stock_minimo ? 'danger' : null)
                    ->weight(fn ($record) => $record->stock <= $record->stock_minimo ? 'bold' : null),

                Tables\Columns\TextColumn::make('precio_venta')
                    ->label('Precio')
                    ->money('DOP')
                    ->sortable(),

                Tables\Columns\IconColumn::make('incluido_en_kit')
                    ->label('Kit')
                    ->boolean(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'sticker' => 'Sticker',
                        'display' => 'Display',
                        'banner' => 'Banner',
                        'placa' => 'Placa',
                        'tarjeta' => 'Tarjeta',
                    ]),

                Tables\Filters\SelectFilter::make('categoria')
                    ->label('Categoría')
                    ->options([
                        'branding' => 'Branding',
                        'certificacion' => 'Certificación',
                        'promocion' => 'Promoción',
                        'informativo' => 'Informativo',
                    ]),

                Tables\Filters\TernaryFilter::make('incluido_en_kit')
                    ->label('En Kit'),

                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Activo'),

                Tables\Filters\Filter::make('stock_bajo')
                    ->label('Stock Bajo')
                    ->query(fn ($query) => $query->whereColumn('stock', '<=', 'stock_minimo')),
            ])
            ->actions([
                Tables\Actions\Action::make('ajustar_stock')
                    ->label('Ajustar Stock')
                    ->icon('heroicon-o-plus-circle')
                    ->form([
                        Forms\Components\TextInput::make('cantidad')
                            ->label('Cantidad a Agregar/Restar')
                            ->numeric()
                            ->required()
                            ->helperText('Use números negativos para restar'),

                        Forms\Components\Textarea::make('motivo')
                            ->label('Motivo del Ajuste')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'stock' => max(0, $record->stock + $data['cantidad']),
                        ]);
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListMaterialesPromocionales::route('/'),
            'create' => Pages\CreateMaterialPromocional::route('/create'),
            'view' => Pages\ViewMaterialPromocional::route('/{record}'),
            'edit' => Pages\EditMaterialPromocional::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Contar items con stock bajo
        $stockBajo = static::getModel()::whereColumn('stock', '<=', 'stock_minimo')->count();
        return $stockBajo > 0 ? "⚠️ {$stockBajo}" : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
