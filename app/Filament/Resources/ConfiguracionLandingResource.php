<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfiguracionLandingResource\Pages;
use App\Models\ConfiguracionLanding;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConfiguracionLandingResource extends Resource
{
    protected static ?string $model = ConfiguracionLanding::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static \UnitEnum|string|null $navigationGroup = 'Landing Page';
    protected static ?string $navigationLabel = 'Configuración';
    protected static ?string $modelLabel = 'Configuración Landing';
    protected static ?string $pluralModelLabel = 'Configuraciones Landing';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Schemas\Components\Section::make('Configuración')
                ->schema([
                    Forms\Components\Select::make('seccion')
                        ->label('Sección')
                        ->options(ConfiguracionLanding::getSecciones())
                        ->required()
                        ->searchable(),
                    Forms\Components\TextInput::make('clave')
                        ->label('Clave')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\Select::make('tipo')
                        ->label('Tipo de Campo')
                        ->options(ConfiguracionLanding::getTipos())
                        ->default('text')
                        ->required(),
                    Forms\Components\Textarea::make('valor')
                        ->label('Valor')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('orden')
                        ->label('Orden')
                        ->numeric()
                        ->default(0),
                    Forms\Components\Toggle::make('activo')
                        ->label('Activo')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('seccion')
                    ->label('Sección')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('clave')
                    ->label('Clave')
                    ->searchable(),
                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->limit(50),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('seccion')
                    ->options(ConfiguracionLanding::getSecciones()),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('seccion')
            ->groups(['seccion']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConfiguracionLandings::route('/'),
            'create' => Pages\CreateConfiguracionLanding::route('/create'),
            'edit' => Pages\EditConfiguracionLanding::route('/{record}/edit'),
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