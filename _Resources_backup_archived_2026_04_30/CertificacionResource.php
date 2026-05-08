<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificacionResource\Pages;
use App\Models\Certificacion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CertificacionResource extends Resource
{
    protected static ?string $model = Certificacion::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Calidad';

    protected static ?string $navigationLabel = 'Certificaciones';

    protected static ?string $modelLabel = 'Certificación';

    protected static ?string $pluralModelLabel = 'Certificaciones';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Certificación')
                    ->schema([
                        Forms\Components\TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(10)
                            ->helperText('Ej: A, B, C, D, E'),

                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Ej: Excelencia, Muy Bueno, Bueno, etc.'),

                        Forms\Components\Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\ColorPicker::make('color')
                            ->label('Color')
                            ->required()
                            ->default('#10b981'),

                        Forms\Components\TextInput::make('icono')
                            ->label('Ícono')
                            ->maxLength(50)
                            ->helperText('Nombre del ícono Heroicon')
                            ->default('heroicon-o-star'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Puntuación Requerida')
                    ->schema([
                        Forms\Components\TextInput::make('puntuacion_minima')
                            ->label('Puntuación Mínima')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Puntuación mínima para obtener esta certificación'),

                        Forms\Components\TextInput::make('puntuacion_maxima')
                            ->label('Puntuación Máxima')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Puntuación máxima de este nivel'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Criterios de Evaluación')
                    ->description('Define los pesos para cada criterio de evaluación (deben sumar 100%)')
                    ->schema([
                        Forms\Components\Repeater::make('criterios')
                            ->label('Criterios')
                            ->schema([
                                Forms\Components\TextInput::make('codigo')
                                    ->label('Código')
                                    ->required()
                                    ->maxLength(50),

                                Forms\Components\TextInput::make('nombre')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(100),

                                Forms\Components\TextInput::make('peso')
                                    ->label('Peso (%)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%'),

                                Forms\Components\Textarea::make('descripcion')
                                    ->label('Descripción')
                                    ->rows(2),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['nombre'] ?? null)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Beneficios')
                    ->schema([
                        Forms\Components\TagsInput::make('beneficios')
                            ->label('Beneficios de la Certificación')
                            ->helperText('Lista de beneficios que obtiene el restaurante')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Configuración')
                    ->schema([
                        Forms\Components\TextInput::make('vigencia_meses')
                            ->label('Vigencia (meses)')
                            ->numeric()
                            ->required()
                            ->default(12)
                            ->minValue(1)
                            ->helperText('Duración de la certificación antes de requerir reevaluación'),

                        Forms\Components\Toggle::make('visible_publico')
                            ->label('Visible al Público')
                            ->default(true)
                            ->helperText('Mostrar este nivel de certificación a los clientes'),

                        Forms\Components\Toggle::make('activo')
                            ->label('Activo')
                            ->default(true),

                        Forms\Components\TextInput::make('orden')
                            ->label('Orden')
                            ->numeric()
                            ->default(0)
                            ->helperText('Orden de visualización (menor = mejor)'),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')
                    ->label(''),

                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->badge()
                    ->color(fn ($record) => $record->color)
                    ->size('lg')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('puntuacion_minima')
                    ->label('Rango')
                    ->formatStateUsing(fn ($record) => "{$record->puntuacion_minima} - {$record->puntuacion_maxima} pts"),

                Tables\Columns\TextColumn::make('restaurantes_count')
                    ->label('Restaurantes')
                    ->counts('restaurantes')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vigencia_meses')
                    ->label('Vigencia')
                    ->formatStateUsing(fn ($state) => "{$state} meses"),

                Tables\Columns\IconColumn::make('visible_publico')
                    ->label('Público')
                    ->boolean(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('orden')
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Activo'),

                Tables\Filters\TernaryFilter::make('visible_publico')
                    ->label('Visible al Público'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListCertificaciones::route('/'),
            'create' => Pages\CreateCertificacion::route('/create'),
            'view' => Pages\ViewCertificacion::route('/{record}'),
            'edit' => Pages\EditCertificacion::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('activo', true)->count();
    }
}
