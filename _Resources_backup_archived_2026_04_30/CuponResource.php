<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CuponResource\Pages;
use App\Models\Cupon;
use App\Models\Restaurante;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CuponResource extends Resource
{
    protected static ?string $model = Cupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Cupones';

    protected static ?string $modelLabel = 'Cupón';

    protected static ?string $pluralModelLabel = 'Cupones';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Cupón')
                    ->schema([
                        Forms\Components\TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('El código que los clientes usarán'),

                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Tipo de Descuento')
                    ->schema([
                        Forms\Components\Select::make('tipo')
                            ->label('Tipo')
                            ->options([
                                'porcentaje' => 'Porcentaje',
                                'monto_fijo' => 'Monto Fijo',
                                'delivery_gratis' => 'Delivery Gratis',
                                '2x1' => '2x1',
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('valor')
                            ->label('Valor')
                            ->numeric()
                            ->required()
                            ->suffix(fn (Forms\Get $get): string =>
                                $get('tipo') === 'porcentaje' ? '%' : 'RD$'
                            )
                            ->visible(fn (Forms\Get $get): bool =>
                                in_array($get('tipo'), ['porcentaje', 'monto_fijo'])
                            ),

                        Forms\Components\TextInput::make('descuento_maximo')
                            ->label('Descuento Máximo')
                            ->numeric()
                            ->prefix('RD$')
                            ->helperText('Límite máximo del descuento')
                            ->visible(fn (Forms\Get $get): bool => $get('tipo') === 'porcentaje'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Restricciones')
                    ->schema([
                        Forms\Components\TextInput::make('valor_minimo_pedido')
                            ->label('Pedido Mínimo')
                            ->numeric()
                            ->prefix('RD$')
                            ->default(0),

                        Forms\Components\Select::make('restaurante_id')
                            ->label('Restaurante')
                            ->options(Restaurante::where('activo', true)->pluck('nombre', 'id'))
                            ->searchable()
                            ->placeholder('Todos los restaurantes'),

                        Forms\Components\Toggle::make('solo_primer_pedido')
                            ->label('Solo Primer Pedido')
                            ->helperText('Solo válido para nuevos clientes'),

                        Forms\Components\Toggle::make('solo_delivery')
                            ->label('Solo Delivery')
                            ->helperText('Solo válido para pedidos con delivery'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Límites de Uso')
                    ->schema([
                        Forms\Components\TextInput::make('limite_uso_total')
                            ->label('Límite Total de Usos')
                            ->numeric()
                            ->helperText('Dejar vacío para sin límite'),

                        Forms\Components\TextInput::make('limite_uso_por_usuario')
                            ->label('Límite por Usuario')
                            ->numeric()
                            ->default(1),

                        Forms\Components\TextInput::make('veces_usado')
                            ->label('Veces Usado')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Vigencia')
                    ->schema([
                        Forms\Components\DateTimePicker::make('fecha_inicio')
                            ->label('Fecha de Inicio')
                            ->required(),

                        Forms\Components\DateTimePicker::make('fecha_fin')
                            ->label('Fecha de Fin')
                            ->required()
                            ->after('fecha_inicio'),

                        Forms\Components\Toggle::make('activo')
                            ->label('Activo')
                            ->default(true),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\BadgeColumn::make('tipo')
                    ->label('Tipo')
                    ->colors([
                        'primary' => 'porcentaje',
                        'success' => 'monto_fijo',
                        'info' => 'delivery_gratis',
                        'warning' => '2x1',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'porcentaje' => 'Porcentaje',
                        'monto_fijo' => 'Monto Fijo',
                        'delivery_gratis' => 'Delivery Gratis',
                        '2x1' => '2x1',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('descripcion_valor')
                    ->label('Valor'),

                Tables\Columns\TextColumn::make('restaurante.nombre')
                    ->label('Restaurante')
                    ->placeholder('Todos')
                    ->limit(20),

                Tables\Columns\TextColumn::make('veces_usado')
                    ->label('Usos')
                    ->formatStateUsing(fn ($state, $record): string =>
                        $record->limite_uso_total
                            ? "{$state}/{$record->limite_uso_total}"
                            : $state
                    ),

                Tables\Columns\TextColumn::make('fecha_fin')
                    ->label('Expira')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\IconColumn::make('esta_vigente')
                    ->label('Vigente')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'porcentaje' => 'Porcentaje',
                        'monto_fijo' => 'Monto Fijo',
                        'delivery_gratis' => 'Delivery Gratis',
                        '2x1' => '2x1',
                    ]),

                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Activo'),

                Tables\Filters\Filter::make('vigentes')
                    ->label('Vigentes')
                    ->query(fn (Builder $query): Builder => $query->vigentes()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('copiar')
                    ->label('Copiar Código')
                    ->icon('heroicon-o-clipboard')
                    ->action(fn () => null),
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
            'index' => Pages\ListCupones::route('/'),
            'create' => Pages\CreateCupon::route('/create'),
            'view' => Pages\ViewCupon::route('/{record}'),
            'edit' => Pages\EditCupon::route('/{record}/edit'),
        ];
    }
}
