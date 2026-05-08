<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModuloSistemaResource\Pages;
use App\Models\ModuloSistema;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ModuloSistemaResource extends Resource
{
    protected static ?string $model = ModuloSistema::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Módulos';

    protected static ?string $modelLabel = 'Módulo del Sistema';

    protected static ?string $pluralModelLabel = 'Módulos del Sistema';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Módulo')
                    ->schema([
                        Forms\Components\TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->helperText('Identificador único del módulo (ej: delivery, reservas)'),

                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(200),

                        Forms\Components\Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('icono')
                            ->label('Ícono')
                            ->maxLength(50)
                            ->default('heroicon-o-cube')
                            ->helperText('Nombre del ícono Heroicon'),

                        Forms\Components\ColorPicker::make('color')
                            ->label('Color')
                            ->default('#3b82f6'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Estado Global')
                    ->description('Controla si el módulo está habilitado a nivel de toda la plataforma')
                    ->schema([
                        Forms\Components\Toggle::make('habilitado_global')
                            ->label('Habilitado Globalmente')
                            ->helperText('Si está desactivado, el módulo no estará disponible para ningún restaurante')
                            ->reactive()
                            ->default(true),

                        Forms\Components\Placeholder::make('advertencia')
                            ->content('⚠️ Desactivar un módulo afectará a TODOS los restaurantes que lo utilizan.')
                            ->visible(fn (Forms\Get $get) => !$get('habilitado_global')),
                    ]),

                Forms\Components\Section::make('Configuración')
                    ->schema([
                        Forms\Components\Toggle::make('requiere_plan')
                            ->label('Requiere Plan Específico')
                            ->helperText('Solo disponible para ciertos planes'),

                        Forms\Components\Toggle::make('en_desarrollo')
                            ->label('En Desarrollo')
                            ->helperText('Módulo en fase de pruebas'),

                        Forms\Components\Toggle::make('es_premium')
                            ->label('Es Premium')
                            ->helperText('Solo para planes de pago'),

                        Forms\Components\Toggle::make('activo')
                            ->label('Activo')
                            ->default(true),

                        Forms\Components\TextInput::make('orden')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Dependencias')
                    ->schema([
                        Forms\Components\Select::make('dependencias')
                            ->label('Módulos Requeridos')
                            ->multiple()
                            ->options(ModuloSistema::pluck('nombre', 'codigo'))
                            ->helperText('Módulos que deben estar activos para que este funcione'),
                    ]),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('configuracion')
                            ->label('Configuración Adicional')
                            ->keyLabel('Clave')
                            ->valueLabel('Valor')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')
                    ->label(''),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Módulo')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->codigo),

                Tables\Columns\TextColumn::make('funciones_count')
                    ->label('Funciones')
                    ->counts('funciones')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('habilitado_global')
                    ->label('Global')
                    ->afterStateUpdated(function ($record, $state) {
                        Notification::make()
                            ->title($state ? 'Módulo activado' : 'Módulo desactivado')
                            ->body("El módulo {$record->nombre} ha sido " . ($state ? 'activado' : 'desactivado') . ' globalmente.')
                            ->success()
                            ->send();
                    }),

                Tables\Columns\IconColumn::make('requiere_plan')
                    ->label('Plan')
                    ->boolean(),

                Tables\Columns\IconColumn::make('es_premium')
                    ->label('Premium')
                    ->boolean(),

                Tables\Columns\IconColumn::make('en_desarrollo')
                    ->label('Dev')
                    ->boolean()
                    ->trueIcon('heroicon-o-beaker')
                    ->falseIcon('heroicon-o-check-circle'),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('orden')
            ->filters([
                Tables\Filters\TernaryFilter::make('habilitado_global')
                    ->label('Habilitado Global'),

                Tables\Filters\TernaryFilter::make('es_premium')
                    ->label('Premium'),

                Tables\Filters\TernaryFilter::make('en_desarrollo')
                    ->label('En Desarrollo'),

                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Activo'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_global')
                    ->label(fn ($record) => $record->habilitado_global ? 'Desactivar' : 'Activar')
                    ->icon(fn ($record) => $record->habilitado_global ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn ($record) => $record->habilitado_global ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => ($record->habilitado_global ? 'Desactivar' : 'Activar') . ' módulo')
                    ->modalDescription(fn ($record) => $record->habilitado_global
                        ? 'Esto desactivará el módulo para TODOS los restaurantes. ¿Está seguro?'
                        : 'Esto activará el módulo globalmente. Los restaurantes podrán usarlo según su plan.')
                    ->action(function ($record) {
                        $record->update(['habilitado_global' => !$record->habilitado_global]);
                    }),
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
            'index' => Pages\ListModulosSistema::route('/'),
            'create' => Pages\CreateModuloSistema::route('/create'),
            'view' => Pages\ViewModuloSistema::route('/{record}'),
            'edit' => Pages\EditModuloSistema::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('habilitado_global', true)->count() . '/' . static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
    }
}
