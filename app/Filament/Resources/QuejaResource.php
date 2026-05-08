<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuejaResource\Pages;
use App\Models\Queja;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuejaResource extends Resource
{
    protected static ?string $model = Queja::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static \UnitEnum|string|null $navigationGroup = 'Soporte';
    protected static ?string $navigationLabel = 'Quejas';
    protected static ?string $modelLabel = 'Queja';
    protected static ?string $pluralModelLabel = 'Quejas';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Schemas\Components\Section::make('Información del Ticket')->schema([
                Forms\Components\TextInput::make('numero_ticket')->disabled(),
                Forms\Components\Select::make('estado')
                    ->options([
                        'abierto' => '🔴 Abierto',
                        'en_proceso' => '🟡 En Proceso',
                        'esperando_respuesta' => '🟠 Esperando Respuesta',
                        'resuelto' => '🟢 Resuelto',
                        'cerrado' => '⚫ Cerrado',
                        'escalado' => '🔵 Escalado',
                    ])->required(),
                Forms\Components\Select::make('prioridad')
                    ->options([
                        'baja' => '🟢 Baja',
                        'media' => '🟡 Media',
                        'alta' => '🟠 Alta',
                        'urgente' => '🔴 Urgente',
                    ])->required(),
            ])->columns(3),

            Schemas\Components\Section::make('Detalle')->schema([
                Forms\Components\TextInput::make('asunto')->required()->columnSpanFull(),
                Forms\Components\Textarea::make('descripcion')->required()->rows(4)->columnSpanFull(),
                Forms\Components\Select::make('tipo')
                    ->options([
                        'pedido' => 'Pedido',
                        'producto' => 'Producto',
                        'servicio' => 'Servicio',
                        'cobro' => 'Cobro',
                        'entrega' => 'Entrega',
                        'app' => 'Aplicación',
                        'otro' => 'Otro',
                    ])->required(),
                Forms\Components\TextInput::make('categoria'),
            ])->columns(2),

            Schemas\Components\Section::make('Resolución')->schema([
                Forms\Components\Textarea::make('resolucion')->rows(3)->columnSpanFull(),
                Forms\Components\TextInput::make('compensacion_otorgada')->numeric()->prefix('RD$'),
                Forms\Components\TextInput::make('puntos_compensacion')->numeric()->suffix('pts'),
                Forms\Components\Select::make('satisfaccion_cliente')
                    ->options([
                        1 => '⭐ Muy Insatisfecho',
                        2 => '⭐⭐ Insatisfecho',
                        3 => '⭐⭐⭐ Neutral',
                        4 => '⭐⭐⭐⭐ Satisfecho',
                        5 => '⭐⭐⭐⭐⭐ Muy Satisfecho',
                    ]),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_ticket')->searchable()->sortable()
                    ->copyable()->copyMessage('Ticket copiado'),
                Tables\Columns\TextColumn::make('asunto')->limit(30)->searchable(),
                Tables\Columns\TextColumn::make('tipo')->badge(),
                Tables\Columns\TextColumn::make('prioridad')->badge()
                    ->colors([
                        'success' => 'baja',
                        'warning' => 'media',
                        'danger' => fn ($state) => in_array($state, ['alta', 'urgente']),
                    ]),
                Tables\Columns\TextColumn::make('estado')->badge()
                    ->colors([
                        'danger' => 'abierto',
                        'warning' => 'en_proceso',
                        'info' => 'esperando_respuesta',
                        'success' => 'resuelto',
                        'gray' => 'cerrado',
                        'primary' => 'escalado',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('estado'),
                Tables\Filters\SelectFilter::make('prioridad'),
                Tables\Filters\SelectFilter::make('tipo'),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuejas::route('/'),
            'create' => Pages\CreateQueja::route('/create'),
            'edit' => Pages\EditQueja::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('estado', ['abierto', 'en_proceso'])->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::where('estado', 'abierto')->where('prioridad', 'urgente')->count();
        return $count > 0 ? 'danger' : 'warning';
    }
}