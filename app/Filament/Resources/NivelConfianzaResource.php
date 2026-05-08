<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NivelConfianzaResource\Pages;
use App\Models\NivelConfianza;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NivelConfianzaResource extends Resource
{
    protected static ?string $model = NivelConfianza::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static \UnitEnum|string|null $navigationGroup = 'Calidad';

    protected static ?string $navigationLabel = 'Niveles de Confianza';

    protected static ?string $modelLabel = 'Nivel de Confianza';

    protected static ?string $pluralModelLabel = 'Niveles de Confianza';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('Información del Nivel')
                    ->schema([
                        Forms\Components\TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('Ej: nuevo, confiable, socio'),

                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\ColorPicker::make('color')
                            ->label('Color')
                            ->required()
                            ->default('#6b7280'),

                        Forms\Components\TextInput::make('icono')
                            ->label('Ícono')
                            ->maxLength(50)
                            ->default('heroicon-o-user'),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('Rango de Puntuación')
                    ->description('Define el rango de puntuación para este nivel (0-100)')
                    ->schema([
                        Forms\Components\TextInput::make('puntuacion_minima')
                            ->label('Puntuación Mínima')
                            ->numeric()
                            ->required()
                            ->minValue(-100)
                            ->maxValue(100)
                            ->default(0),

                        Forms\Components\TextInput::make('puntuacion_maxima')
                            ->label('Puntuación Máxima')
                            ->numeric()
                            ->required()
                            ->minValue(-100)
                            ->maxValue(100)
                            ->default(100),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('Restricciones')
                    ->description('Define las restricciones aplicadas a restaurantes en este nivel')
                    ->schema([
                        Forms\Components\KeyValue::make('restricciones')
                            ->label('Restricciones')
                            ->keyLabel('Código de Restricción')
                            ->valueLabel('Valor')
                            ->helperText('Ej: requiere_verificacion_previa → true, limite_pedidos_diarios → 50')
                            ->columnSpanFull(),
                    ]),

                Schemas\Components\Section::make('Acciones Automáticas')
                    ->description('Acciones que se ejecutan al entrar o salir de este nivel')
                    ->schema([
                        Forms\Components\TagsInput::make('acciones_entrada')
                            ->label('Al Entrar en este Nivel')
                            ->helperText('Acciones automáticas al alcanzar este nivel')
                            ->suggestions([
                                'notificar_restaurante',
                                'notificar_admin',
                                'activar_revision_manual',
                                'desactivar_funciones_premium',
                                'programar_verificacion',
                            ]),

                        Forms\Components\TagsInput::make('acciones_salida')
                            ->label('Al Salir de este Nivel')
                            ->helperText('Acciones automáticas al dejar este nivel')
                            ->suggestions([
                                'reactivar_funciones',
                                'notificar_mejora',
                                'notificar_deterioro',
                            ]),
                    ])
                    ->columns(2),

                Schemas\Components\Section::make('Configuración')
                    ->schema([
                        Forms\Components\Toggle::make('es_nivel_inicial')
                            ->label('Es Nivel Inicial')
                            ->helperText('Nivel asignado a nuevos restaurantes'),

                        Forms\Components\Toggle::make('requiere_accion_manual')
                            ->label('Requiere Acción Manual')
                            ->helperText('No se puede salir automáticamente de este nivel'),

                        Forms\Components\Toggle::make('visible_restaurante')
                            ->label('Visible al Restaurante')
                            ->default(true)
                            ->helperText('El restaurante puede ver este nivel'),

                        Forms\Components\Toggle::make('activo')
                            ->label('Activo')
                            ->default(true),

                        Forms\Components\TextInput::make('orden')
                            ->label('Orden')
                            ->numeric()
                            ->default(0)
                            ->helperText('Orden jerárquico (mayor = mejor)'),
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
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('puntuacion_minima')
                    ->label('Rango')
                    ->formatStateUsing(fn ($record) => "{$record->puntuacion_minima} - {$record->puntuacion_maxima} pts"),

                Tables\Columns\TextColumn::make('restaurantes_count')
                    ->label('Restaurantes')
                    ->counts('restaurantes')
                    ->sortable(),

                Tables\Columns\IconColumn::make('es_nivel_inicial')
                    ->label('Inicial')
                    ->boolean(),

                Tables\Columns\IconColumn::make('requiere_accion_manual')
                    ->label('Manual')
                    ->boolean(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('orden', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Activo'),
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
            'index' => Pages\ListNivelesConfianza::route('/'),
            'create' => Pages\CreateNivelConfianza::route('/create'),
            'view' => Pages\ViewNivelConfianza::route('/{record}'),
            'edit' => Pages\EditNivelConfianza::route('/{record}/edit'),
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
