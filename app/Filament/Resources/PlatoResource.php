<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlatoResource\Pages;
use App\Models\Plato;
use App\Models\Restaurante;
use App\Models\CategoriaMenu;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PlatoResource extends Resource
{
    protected static ?string $model = Plato::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cake';

    protected static \UnitEnum|string|null $navigationGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Platos';

    protected static ?string $modelLabel = 'Plato';

    protected static ?string $pluralModelLabel = 'Platos';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\Select::make('restaurante_id')
                            ->label('Restaurante')
                            ->options(Restaurante::where('activo', true)->pluck('nombre', 'id'))
                            ->searchable()
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('categoria_id')
                            ->label('Categoría')
                            ->options(fn (Forms\Get $get) =>
                                CategoriaMenu::where('restaurante_id', $get('restaurante_id'))
                                    ->where('activa', true)
                                    ->pluck('nombre', 'id')
                            )
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre del Plato')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                                $operation === 'create' ? $set('slug', Str::slug($state)) : null
                            ),

                        Forms\Components\TextInput::make('slug')
                            ->label('URL amigable')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('Precio y Oferta')
                    ->schema([
                        Forms\Components\TextInput::make('precio')
                            ->label('Precio')
                            ->numeric()
                            ->prefix('RD$')
                            ->required(),

                        Forms\Components\TextInput::make('precio_oferta')
                            ->label('Precio de Oferta')
                            ->numeric()
                            ->prefix('RD$')
                            ->helperText('Dejar vacío si no hay oferta'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('Detalles')
                    ->schema([
                        Forms\Components\TextInput::make('calorias')
                            ->label('Calorías')
                            ->numeric()
                            ->suffix('kcal'),

                        Forms\Components\TextInput::make('tiempo_preparacion')
                            ->label('Tiempo de Preparación')
                            ->numeric()
                            ->suffix('min'),

                        Forms\Components\TextInput::make('orden')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),

                        Forms\Components\TagsInput::make('ingredientes')
                            ->label('Ingredientes'),

                        Forms\Components\TagsInput::make('alergenos')
                            ->label('Alérgenos')
                            ->suggestions(['Gluten', 'Lácteos', 'Huevos', 'Pescado', 'Mariscos', 'Frutos secos', 'Soya']),

                        Forms\Components\TagsInput::make('etiquetas')
                            ->label('Etiquetas')
                            ->suggestions(['Picante', 'Vegetariano', 'Vegano', 'Sin gluten', 'Popular', 'Nuevo']),
                    ])
                    ->columns(3),

                Schemas\Components\Section::make('Imagen')
                    ->schema([
                        Forms\Components\FileUpload::make('imagen')
                            ->label('Imagen del Plato')
                            ->image()
                            ->directory('platos')
                            ->imageEditor()
                            ->columnSpanFull(),
                    ]),

                Schemas\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\Toggle::make('disponible')
                            ->label('Disponible')
                            ->default(true),

                        Forms\Components\Toggle::make('destacado')
                            ->label('Destacado')
                            ->default(false),

                        Forms\Components\Toggle::make('nuevo')
                            ->label('Nuevo')
                            ->default(true),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(asset('images/plato-default.png')),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('restaurante.nombre')
                    ->label('Restaurante')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('categoria.nombre')
                    ->label('Categoría')
                    ->sortable(),

                Tables\Columns\TextColumn::make('precio')
                    ->label('Precio')
                    ->money('DOP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('precio_oferta')
                    ->label('Oferta')
                    ->money('DOP')
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('calificacion')
                    ->label('Calificación')
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format($state, 1) . ' ⭐' : '-')
                    ->sortable(),

                Tables\Columns\IconColumn::make('disponible')
                    ->label('Disponible')
                    ->boolean(),

                Tables\Columns\IconColumn::make('destacado')
                    ->label('Destacado')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('restaurante_id')
                    ->label('Restaurante')
                    ->options(Restaurante::where('activo', true)->pluck('nombre', 'id'))
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('disponible')
                    ->label('Disponible'),

                Tables\Filters\TernaryFilter::make('destacado')
                    ->label('Destacado'),

                Tables\Filters\Filter::make('con_oferta')
                    ->label('Con Oferta')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('precio_oferta')),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPlatos::route('/'),
            'create' => Pages\CreatePlato::route('/create'),
            'view' => Pages\ViewPlato::route('/{record}'),
            'edit' => Pages\EditPlato::route('/{record}/edit'),
        ];
    }


    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
