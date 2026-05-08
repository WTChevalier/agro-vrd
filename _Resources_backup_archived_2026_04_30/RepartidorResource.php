<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RepartidorResource\Pages;
use App\Models\Repartidor;
use App\Models\Usuario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RepartidorResource extends Resource
{
    protected static ?string $model = Repartidor::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Operaciones';

    protected static ?string $navigationLabel = 'Repartidores';

    protected static ?string $modelLabel = 'Repartidor';

    protected static ?string $pluralModelLabel = 'Repartidores';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Usuario')
                    ->schema([
                        Forms\Components\Select::make('usuario_id')
                            ->label('Usuario')
                            ->options(Usuario::where('rol', 'repartidor')
                                ->orWhereDoesntHave('repartidor')
                                ->pluck('nombre', 'id'))
                            ->searchable()
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit'),

                        Forms\Components\TextInput::make('codigo_repartidor')
                            ->label('Código de Repartidor')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Vehículo')
                    ->schema([
                        Forms\Components\Select::make('tipo_vehiculo')
                            ->label('Tipo de Vehículo')
                            ->options([
                                'motocicleta' => 'Motocicleta',
                                'bicicleta' => 'Bicicleta',
                                'carro' => 'Carro',
                                'a_pie' => 'A Pie',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('placa_vehiculo')
                            ->label('Placa')
                            ->maxLength(15),

                        Forms\Components\TextInput::make('marca_vehiculo')
                            ->label('Marca')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('modelo_vehiculo')
                            ->label('Modelo')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('color_vehiculo')
                            ->label('Color')
                            ->maxLength(30),

                        Forms\Components\FileUpload::make('foto_vehiculo')
                            ->label('Foto del Vehículo')
                            ->image()
                            ->directory('repartidores/vehiculos'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Licencia')
                    ->schema([
                        Forms\Components\TextInput::make('licencia_conducir')
                            ->label('Número de Licencia')
                            ->maxLength(30),

                        Forms\Components\DatePicker::make('fecha_vencimiento_licencia')
                            ->label('Fecha de Vencimiento'),

                        Forms\Components\FileUpload::make('foto_licencia')
                            ->label('Foto de la Licencia')
                            ->image()
                            ->directory('repartidores/licencias'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Zona de Trabajo')
                    ->schema([
                        Forms\Components\TextInput::make('zona_trabajo')
                            ->label('Zona de Trabajo')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('radio_trabajo_km')
                            ->label('Radio de Trabajo')
                            ->numeric()
                            ->suffix('km')
                            ->default(5),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\Select::make('estado')
                            ->label('Estado')
                            ->options([
                                'pendiente' => 'Pendiente de Aprobación',
                                'aprobado' => 'Aprobado',
                                'rechazado' => 'Rechazado',
                                'suspendido' => 'Suspendido',
                            ])
                            ->required(),

                        Forms\Components\Toggle::make('disponible')
                            ->label('Disponible')
                            ->default(false),

                        Forms\Components\Textarea::make('motivo_rechazo')
                            ->label('Motivo de Rechazo/Suspensión')
                            ->visible(fn (Forms\Get $get): bool =>
                                in_array($get('estado'), ['rechazado', 'suspendido'])
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Estadísticas')
                    ->schema([
                        Forms\Components\TextInput::make('calificacion')
                            ->label('Calificación')
                            ->disabled(),

                        Forms\Components\TextInput::make('total_entregas')
                            ->label('Total Entregas')
                            ->disabled(),

                        Forms\Components\TextInput::make('balance_pendiente')
                            ->label('Balance Pendiente')
                            ->prefix('RD$')
                            ->disabled(),

                        Forms\Components\TextInput::make('total_ganado')
                            ->label('Total Ganado')
                            ->prefix('RD$')
                            ->disabled(),
                    ])
                    ->columns(4)
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo_repartidor')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('usuario.nombre_completo')
                    ->label('Nombre')
                    ->searchable(['usuario.nombre', 'usuario.apellido']),

                Tables\Columns\TextColumn::make('usuario.celular')
                    ->label('Celular'),

                Tables\Columns\BadgeColumn::make('tipo_vehiculo')
                    ->label('Vehículo')
                    ->colors([
                        'primary' => 'motocicleta',
                        'success' => 'bicicleta',
                        'warning' => 'carro',
                        'secondary' => 'a_pie',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'motocicleta' => 'Moto',
                        'bicicleta' => 'Bicicleta',
                        'carro' => 'Carro',
                        'a_pie' => 'A Pie',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pendiente',
                        'success' => 'aprobado',
                        'danger' => 'rechazado',
                        'secondary' => 'suspendido',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente' => 'Pendiente',
                        'aprobado' => 'Aprobado',
                        'rechazado' => 'Rechazado',
                        'suspendido' => 'Suspendido',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('disponible')
                    ->label('Disponible')
                    ->boolean(),

                Tables\Columns\TextColumn::make('calificacion')
                    ->label('Calificación')
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format($state, 1) . ' ⭐' : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_entregas')
                    ->label('Entregas')
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_pendiente')
                    ->label('Balance')
                    ->money('DOP')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'aprobado' => 'Aprobado',
                        'rechazado' => 'Rechazado',
                        'suspendido' => 'Suspendido',
                    ]),

                Tables\Filters\SelectFilter::make('tipo_vehiculo')
                    ->label('Tipo de Vehículo')
                    ->options([
                        'motocicleta' => 'Motocicleta',
                        'bicicleta' => 'Bicicleta',
                        'carro' => 'Carro',
                        'a_pie' => 'A Pie',
                    ]),

                Tables\Filters\TernaryFilter::make('disponible')
                    ->label('Disponible'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Repartidor $record): bool => $record->estado === 'pendiente')
                    ->requiresConfirmation()
                    ->action(fn (Repartidor $record) => $record->aprobar(auth()->id())),
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
            'index' => Pages\ListRepartidores::route('/'),
            'create' => Pages\CreateRepartidor::route('/create'),
            'view' => Pages\ViewRepartidor::route('/{record}'),
            'edit' => Pages\EditRepartidor::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('estado', 'pendiente')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
