<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiExternaResource\Pages;
use App\Models\ApiExterna;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApiExternaResource extends Resource
{
    protected static ?string $model = ApiExterna::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-globe-alt';
    protected static \UnitEnum|string|null $navigationGroup = 'Integraciones';
    protected static ?string $navigationLabel = 'APIs Externas';
    protected static ?string $modelLabel = 'API Externa';
    protected static ?string $pluralModelLabel = 'APIs Externas';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Schemas\Components\Section::make('Información de la API')->schema([
                Forms\Components\TextInput::make('nombre')->required()->maxLength(255),
                Forms\Components\Textarea::make('descripcion')->rows(2),
                Forms\Components\Select::make('tipo')
                    ->options([
                        'turismo' => '🌴 Turismo',
                        'pagos' => '💳 Pagos',
                        'mapas' => '🗺️ Mapas',
                        'sms' => '📱 SMS',
                        'email' => '📧 Email',
                        'analytics' => '📊 Analytics',
                        'social' => '👥 Social',
                        'otro' => '🔧 Otro',
                    ])->required(),
                Forms\Components\TextInput::make('proveedor')->required(),
            ])->columns(2),

            Schemas\Components\Section::make('Endpoints')->schema([
                Forms\Components\TextInput::make('url_base')->url()->required()->prefix('https://'),
                Forms\Components\TextInput::make('url_sandbox')->url()->prefix('https://'),
                Forms\Components\TextInput::make('version')->placeholder('v1'),
            ])->columns(3),

            Schemas\Components\Section::make('Autenticación')->schema([
                Forms\Components\Select::make('auth_tipo')
                    ->options([
                        'api_key' => 'API Key',
                        'oauth2' => 'OAuth 2.0',
                        'basic' => 'Basic Auth',
                        'bearer' => 'Bearer Token',
                        'custom' => 'Custom',
                    ])->required(),
                Forms\Components\TextInput::make('api_key')->password()->revealable(),
                Forms\Components\TextInput::make('api_secret')->password()->revealable(),
            ])->columns(3),

            Schemas\Components\Section::make('Límites y Estado')->schema([
                Forms\Components\TextInput::make('rate_limit_requests')->numeric()->suffix('requests'),
                Forms\Components\Select::make('rate_limit_periodo')
                    ->options([
                        'segundo' => 'Por segundo',
                        'minuto' => 'Por minuto',
                        'hora' => 'Por hora',
                        'dia' => 'Por día',
                    ]),
                Forms\Components\Select::make('ambiente')
                    ->options([
                        'sandbox' => '🧪 Sandbox',
                        'produccion' => '🚀 Producción',
                    ])->required(),
                Forms\Components\Toggle::make('activo')->default(true),
            ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('proveedor')->searchable(),
                Tables\Columns\TextColumn::make('tipo')->badge(),
                Tables\Columns\TextColumn::make('ambiente')->badge()
                    ->colors([
                        'warning' => 'sandbox',
                        'success' => 'produccion',
                    ]),
                Tables\Columns\IconColumn::make('ultimo_test_exitoso')->boolean()->label('Test OK'),
                Tables\Columns\IconColumn::make('activo')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo'),
                Tables\Filters\SelectFilter::make('ambiente'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('test')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->label('Probar'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiExternas::route('/'),
            'create' => Pages\CreateApiExterna::route('/create'),
            'edit' => Pages\EditApiExterna::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('activo', true)->count();
    }
}