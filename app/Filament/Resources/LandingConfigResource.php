<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LandingConfigResource\Pages;
use App\Models\LandingConfig;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class LandingConfigResource extends Resource
{
    protected static ?string $model = LandingConfig::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static UnitEnum|string|null $navigationGroup = 'Marca';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Landing Config';

    protected static ?string $modelLabel = 'Configuración de Landing';

    protected static ?string $pluralModelLabel = 'Configuración de Landing';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificación')->columns(2)->schema([
                Forms\Components\TextInput::make('clave')->required()->maxLength(100)->placeholder('hero.title'),
                Forms\Components\Select::make('grupo')->options([
                    'hero' => 'Hero', 'header' => 'Header', 'seo' => 'SEO',
                    'stats' => 'Stats Bar', 'categorias' => 'Categorías',
                    'cta' => 'CTA Final', 'paraguas' => 'Paraguas',
                    'footer' => 'Footer', 'general' => 'General',
                ])->required()->native(false),
            ]),
            Section::make('Contenido')->schema([
                Forms\Components\Textarea::make('valor')->label('Valor (JSON o texto)')->rows(4)->columnSpanFull(),
                Forms\Components\Toggle::make('is_translatable')->label('Traducir auto')->default(true),
                Forms\Components\Toggle::make('activo')->default(true),
                Forms\Components\TextInput::make('orden')->numeric()->default(0),
                Forms\Components\Textarea::make('descripcion')->columnSpanFull()->rows(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('grupo')->badge()->sortable(),
            Tables\Columns\TextColumn::make('clave')->searchable()->copyable()->sortable(),
            Tables\Columns\TextColumn::make('valor')->limit(60)->wrap()
                ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : (string) $state),
            Tables\Columns\IconColumn::make('is_translatable')->boolean()->label('🌍'),
            Tables\Columns\IconColumn::make('activo')->boolean(),
            Tables\Columns\TextColumn::make('updated_at')->since()->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('grupo')->options([
                'hero' => 'Hero', 'seo' => 'SEO', 'stats' => 'Stats',
                'categorias' => 'Categorías', 'cta' => 'CTA',
                'paraguas' => 'Paraguas', 'footer' => 'Footer', 'general' => 'General',
            ]),
            Tables\Filters\TernaryFilter::make('activo'),
        ])
        ->defaultSort('grupo')
        ->reorderable('orden');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLandingConfigs::route('/'),
            'create' => Pages\CreateLandingConfig::route('/create'),
            'edit' => Pages\EditLandingConfig::route('/{record}/edit'),
        ];
    }
}
