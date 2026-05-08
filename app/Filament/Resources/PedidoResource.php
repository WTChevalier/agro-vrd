<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PedidoResource\Pages;
use App\Models\Pedido;
use App\Models\EstadoPedido;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PedidoResource extends Resource
{
    protected static ?string $model = Pedido::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static \UnitEnum|string|null $navigationGroup = 'Operaciones';

    protected static ?string $navigationLabel = 'Pedidos';

    protected static ?string $modelLabel = 'Pedido';

    protected static ?string $pluralModelLabel = 'Pedidos';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Section::make('Información del Pedido')
                    ->schema([
                        Forms\Components\TextInput::make('numero_pedido')
                            ->label('Número de Pedido')
                            ->disabled(),

                        Forms\Components\Select::make('estado_id')
                            ->label('Estado')
                            ->options(EstadoPedido::pluck('nombre', 'id'))
                            ->required(),

                        Forms\Components\Select::make('tipo')
                            ->label('Tipo')
                            ->options([
                                'delivery' => 'Delivery',
                                'recoger' => 'Para Recoger',
                                'en_local' => 'En el Local',
                            ])
                            ->disabled(),

                        Forms\Components\Select::make('metodo_pago')
                            ->label('Método de Pago')
                            ->options([
                                'efectivo' => 'Efectivo',
                                'tarjeta' => 'Tarjeta',
                                'billetera' => 'Billetera',
                                'transferencia' => 'Transferencia',
                            ])
                            ->disabled(),
                    ])
                    ->columns(4),

                Schemas\Components\Section::make('Cliente y Restaurante')
                    ->schema([
                        Forms\Components\Select::make('usuario_id')
                            ->label('Cliente')
                            ->relationship('usuario', 'nombre')
                            ->disabled(),

                        Forms\Components\Select::make('restaurante_id')
                            ->label('Restaurante')
                            ->relationship('restaurante', 'nombre')
                            ->disabled(),

                        Forms\Components\Select::make('repartidor_id')
                            ->label('Repartidor')
                            ->relationship('repartidor', 'codigo_repartidor')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),

                Schemas\Components\Section::make('Dirección de Entrega')
                    ->schema([
                        Forms\Components\Textarea::make('direccion_entrega')
                            ->label('Dirección')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nombre_contacto')
                            ->label('Nombre de Contacto')
                            ->disabled(),

                        Forms\Components\TextInput::make('telefono_contacto')
                            ->label('Teléfono')
                            ->disabled(),

                        Forms\Components\Textarea::make('instrucciones_entrega')
                            ->label('Instrucciones')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record?->tipo === 'delivery'),

                Schemas\Components\Section::make('Totales')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->prefix('RD$')
                            ->disabled(),

                        Forms\Components\TextInput::make('descuento')
                            ->label('Descuento')
                            ->prefix('RD$')
                            ->disabled(),

                        Forms\Components\TextInput::make('tarifa_delivery')
                            ->label('Delivery')
                            ->prefix('RD$')
                            ->disabled(),

                        Forms\Components\TextInput::make('propina')
                            ->label('Propina')
                            ->prefix('RD$')
                            ->disabled(),

                        Forms\Components\TextInput::make('impuestos')
                            ->label('ITBIS')
                            ->prefix('RD$')
                            ->disabled(),

                        Forms\Components\TextInput::make('total')
                            ->label('Total')
                            ->prefix('RD$')
                            ->disabled(),
                    ])
                    ->columns(6),

                Schemas\Components\Section::make('Notas')
                    ->schema([
                        Forms\Components\Textarea::make('notas')
                            ->label('Notas del Pedido')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('motivo_cancelacion')
                            ->label('Motivo de Cancelación')
                            ->visible(fn ($record) => $record?->esta_cancelado)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_pedido')
                    ->label('# Pedido')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('usuario.nombre_completo')
                    ->label('Cliente')
                    ->searchable(['usuario.nombre', 'usuario.apellido']),

                Tables\Columns\TextColumn::make('restaurante.nombre')
                    ->label('Restaurante')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\BadgeColumn::make('tipo')
                    ->label('Tipo')
                    ->colors([
                        'primary' => 'delivery',
                        'success' => 'recoger',
                        'warning' => 'en_local',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'delivery' => 'Delivery',
                        'recoger' => 'Recoger',
                        'en_local' => 'En Local',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('estado.nombre')
                    ->label('Estado')
                    ->colors([
                        'warning' => fn ($state) => in_array($state, ['Pendiente', 'Preparando']),
                        'info' => fn ($state) => in_array($state, ['Confirmado', 'Listo para recoger']),
                        'primary' => fn ($state) => $state === 'En camino',
                        'success' => fn ($state) => $state === 'Entregado',
                        'danger' => fn ($state) => $state === 'Cancelado',
                    ]),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('DOP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('metodo_pago')
                    ->label('Pago')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'efectivo' => 'Efectivo',
                        'tarjeta' => 'Tarjeta',
                        'billetera' => 'Billetera',
                        'transferencia' => 'Transf.',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('estado_id')
                    ->label('Estado')
                    ->options(EstadoPedido::pluck('nombre', 'id')),

                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'delivery' => 'Delivery',
                        'recoger' => 'Para Recoger',
                        'en_local' => 'En el Local',
                    ]),

                Tables\Filters\SelectFilter::make('metodo_pago')
                    ->label('Método de Pago')
                    ->options([
                        'efectivo' => 'Efectivo',
                        'tarjeta' => 'Tarjeta',
                        'billetera' => 'Billetera',
                        'transferencia' => 'Transferencia',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
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
            'index' => Pages\ListPedidos::route('/'),
            'view' => Pages\ViewPedido::route('/{record}'),
            'edit' => Pages\EditPedido::route('/{record}/edit'),
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

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
