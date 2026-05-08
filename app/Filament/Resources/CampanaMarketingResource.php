<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampanaMarketingResource\Pages;
use App\Models\CampanaMarketing;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CampanaMarketingResource extends Resource
{
    protected static ?string $model = CampanaMarketing::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-megaphone';
    protected static \UnitEnum|string|null $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Campañas';
    protected static ?string $modelLabel = 'Campaña';
    protected static ?string $pluralModelLabel = 'Campañas';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Schemas\Components\Section::make('Información de la Campaña')->schema([
                Forms\Components\TextInput::make('nombre')->required()->maxLength(255),
                Forms\Components\Textarea::make('descripcion')->rows(2),
                Forms\Components\Select::make('tipo')
                    ->options([
                        'email' => '📧 Email',
                        'push' => '🔔 Push',
                        'sms' => '📱 SMS',
                        'in_app' => '📲 In-App',
                        'multi_canal' => '📡 Multi-Canal',
                    ])->required(),
                Forms\Components\Select::make('objetivo')
                    ->options([
                        'adquisicion' => '🎯 Adquisición',
                        'retencion' => '🔄 Retención',
                        'reactivacion' => '⏰ Reactivación',
                        'promocion' => '🎁 Promoción',
                        'informativo' => '📢 Informativo',
                    ])->required(),
            ])->columns(2),

            Schemas\Components\Section::make('Contenido')->schema([
                Forms\Components\TextInput::make('asunto')->maxLength(255)
                    ->helperText('Asunto del email o título de la notificación'),
                Forms\Components\RichEditor::make('contenido_html')
                    ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList']),
                Forms\Components\Textarea::make('contenido_texto')->rows(3)
                    ->helperText('Versión de texto plano'),
                Forms\Components\FileUpload::make('imagen_url')->image()->directory('campanas'),
            ]),

            Schemas\Components\Section::make('Programación')->schema([
                Forms\Components\DateTimePicker::make('fecha_inicio'),
                Forms\Components\DateTimePicker::make('fecha_fin'),
                Forms\Components\Select::make('frecuencia')
                    ->options([
                        'unica' => 'Una vez',
                        'diaria' => 'Diaria',
                        'semanal' => 'Semanal',
                        'mensual' => 'Mensual',
                    ])->default('unica'),
                Forms\Components\Select::make('estado')
                    ->options([
                        'borrador' => '📝 Borrador',
                        'programada' => '📅 Programada',
                        'activa' => '✅ Activa',
                        'pausada' => '⏸️ Pausada',
                        'completada' => '🏁 Completada',
                        'cancelada' => '❌ Cancelada',
                    ])->default('borrador'),
            ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tipo')->badge(),
                Tables\Columns\TextColumn::make('objetivo')->badge()->color('info'),
                Tables\Columns\TextColumn::make('estado')->badge()
                    ->colors([
                        'gray' => 'borrador',
                        'warning' => 'programada',
                        'success' => 'activa',
                        'info' => 'pausada',
                        'primary' => 'completada',
                        'danger' => 'cancelada',
                    ]),
                Tables\Columns\TextColumn::make('enviados')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('abiertos')->numeric(),
                Tables\Columns\TextColumn::make('fecha_inicio')->dateTime('d/m/Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado'),
                Tables\Filters\SelectFilter::make('tipo'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('enviar')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->estado === 'borrador'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampanaMarketings::route('/'),
            'create' => Pages\CreateCampanaMarketing::route('/create'),
            'edit' => Pages\EditCampanaMarketing::route('/edit/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('estado', 'activa')->count();
    }
}