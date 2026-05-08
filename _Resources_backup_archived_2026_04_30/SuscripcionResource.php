<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuscripcionResource\Pages;
use App\Models\Suscripcion;
use App\Models\Plan;
use App\Models\Restaurante;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SuscripcionResource extends Resource
{
    protected static ?string $model = Suscripcion::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Suscripciones';

    protected static ?string $navigationLabel = 'Suscripciones';

    protected static ?string $modelLabel = 'Suscripción';

    protected static ?string $pluralModelLabel = 'Suscripciones';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Suscripción')
                    ->schema([
                        Forms\Components\Select::make('restaurante_id')
                            ->label('Restaurante')
                            ->relationship('restaurante', 'nombre')
                            ->searchable()
                            ->required()
                            ->preload(),

                        Forms\Components\Select::make('plan_id')
                            ->label('Plan')
                            ->options(Plan::activos()->pluck('nombre', 'id'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $plan = Plan::find($state);
                                if ($plan) {
                                    $set('monto_mensual', $plan->precio_mensual);
                                }
                            }),

                        Forms\Components\TextInput::make('monto_mensual')
                            ->label('Monto Mensual')
                            ->numeric()
                            ->prefix('RD$')
                            ->required(),

                        Forms\Components\Select::make('ciclo_facturacion')
                            ->label('Ciclo de Facturación')
                            ->options([
                                'mensual' => 'Mensual',
                                'trimestral' => 'Trimestral',
                                'semestral' => 'Semestral',
                                'anual' => 'Anual',
                            ])
                            ->default('mensual')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Período')
                    ->schema([
                        Forms\Components\DatePicker::make('fecha_inicio')
                            ->label('Fecha de Inicio')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('fecha_fin')
                            ->label('Fecha de Fin')
                            ->helperText('Dejar vacío para suscripción sin fecha de vencimiento'),

                        Forms\Components\DatePicker::make('proxima_facturacion')
                            ->label('Próxima Facturación')
                            ->required()
                            ->default(now()->addMonth()),

                        Forms\Components\DatePicker::make('fecha_cancelacion')
                            ->label('Fecha de Cancelación')
                            ->visible(fn (Forms\Get $get) => in_array($get('estado'), ['cancelada', 'vencida'])),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\Select::make('estado')
                            ->label('Estado')
                            ->options([
                                Suscripcion::ESTADO_ACTIVA => '🟢 Activa',
                                Suscripcion::ESTADO_PRUEBA => '🔵 En Período de Prueba',
                                Suscripcion::ESTADO_PENDIENTE => '🟡 Pendiente de Pago',
                                Suscripcion::ESTADO_VENCIDA => '🟠 Vencida',
                                Suscripcion::ESTADO_SUSPENDIDA => '🔴 Suspendida',
                                Suscripcion::ESTADO_CANCELADA => '⚫ Cancelada',
                            ])
                            ->required()
                            ->default(Suscripcion::ESTADO_ACTIVA),

                        Forms\Components\Toggle::make('renovacion_automatica')
                            ->label('Renovación Automática')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Descuentos y Ajustes')
                    ->schema([
                        Forms\Components\TextInput::make('descuento_porcentaje')
                            ->label('Descuento')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100),

                        Forms\Components\TextInput::make('creditos')
                            ->label('Créditos Disponibles')
                            ->numeric()
                            ->prefix('RD$')
                            ->default(0),

                        Forms\Components\Textarea::make('notas')
                            ->label('Notas')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Motivo de Cancelación')
                    ->schema([
                        Forms\Components\Textarea::make('motivo_cancelacion')
                            ->label('Motivo')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Forms\Get $get) => in_array($get('estado'), ['cancelada', 'suspendida']))
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('restaurante.nombre')
                    ->label('Restaurante')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('plan.nombre')
                    ->label('Plan')
                    ->badge()
                    ->color(fn ($record) => $record->plan?->color ?? 'gray'),

                Tables\Columns\TextColumn::make('monto_mensual')
                    ->label('Monto')
                    ->money('DOP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ciclo_facturacion')
                    ->label('Ciclo')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'activa' => 'success',
                        'prueba' => 'info',
                        'pendiente' => 'warning',
                        'vencida' => 'warning',
                        'suspendida' => 'danger',
                        'cancelada' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('proxima_facturacion')
                    ->label('Próx. Facturación')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->proxima_facturacion?->isPast() ? 'danger' : null),

                Tables\Columns\IconColumn::make('renovacion_automatica')
                    ->label('Auto')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        Suscripcion::ESTADO_ACTIVA => 'Activa',
                        Suscripcion::ESTADO_PRUEBA => 'En Prueba',
                        Suscripcion::ESTADO_PENDIENTE => 'Pendiente',
                        Suscripcion::ESTADO_VENCIDA => 'Vencida',
                        Suscripcion::ESTADO_SUSPENDIDA => 'Suspendida',
                        Suscripcion::ESTADO_CANCELADA => 'Cancelada',
                    ]),

                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(Plan::pluck('nombre', 'id')),

                Tables\Filters\SelectFilter::make('ciclo_facturacion')
                    ->label('Ciclo')
                    ->options([
                        'mensual' => 'Mensual',
                        'trimestral' => 'Trimestral',
                        'semestral' => 'Semestral',
                        'anual' => 'Anual',
                    ]),

                Tables\Filters\Filter::make('proxima_facturacion')
                    ->label('Por Vencer')
                    ->query(fn (Builder $query) => $query->where('proxima_facturacion', '<=', now()->addDays(7))),
            ])
            ->actions([
                Tables\Actions\Action::make('renovar')
                    ->label('Renovar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->estado, ['activa', 'pendiente', 'vencida']))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'proxima_facturacion' => match ($record->ciclo_facturacion) {
                                'mensual' => now()->addMonth(),
                                'trimestral' => now()->addMonths(3),
                                'semestral' => now()->addMonths(6),
                                'anual' => now()->addYear(),
                            },
                            'estado' => Suscripcion::ESTADO_ACTIVA,
                        ]);
                    }),

                Tables\Actions\Action::make('suspender')
                    ->label('Suspender')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->visible(fn ($record) => $record->estado === 'activa')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('motivo')
                            ->label('Motivo de Suspensión')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'estado' => Suscripcion::ESTADO_SUSPENDIDA,
                            'motivo_cancelacion' => $data['motivo'],
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
            'index' => Pages\ListSuscripciones::route('/'),
            'create' => Pages\CreateSuscripcion::route('/create'),
            'view' => Pages\ViewSuscripcion::route('/{record}'),
            'edit' => Pages\EditSuscripcion::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('estado', Suscripcion::ESTADO_ACTIVA)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $vencidas = static::getModel()::where('estado', Suscripcion::ESTADO_VENCIDA)->count();
        return $vencidas > 0 ? 'warning' : 'success';
    }
}
