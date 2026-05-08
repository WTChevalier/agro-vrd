<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Marketing';
    protected static ?string $modelLabel = 'Banner';
    protected static ?string $pluralModelLabel = 'Banners';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count() ?: null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Banner')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Contenido')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Titulo')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('subtitle')
                                    ->label('Subtitulo')
                                    ->maxLength(255),

                                Forms\Components\Textarea::make('description')
                                    ->label('Descripcion')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('button_text')
                                    ->label('Texto del Boton')
                                    ->maxLength(50)
                                    ->placeholder('Ver mas'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Imagenes')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\FileUpload::make('image')
                                    ->label('Imagen Principal')
                                    ->image()
                                    ->required()
                                    ->directory('banners')
                                    ->imageResizeMode('cover')
                                    ->imageCropAspectRatio('16:9')
                                    ->imageResizeTargetWidth('1200')
                                    ->imageResizeTargetHeight('675'),

                                Forms\Components\FileUpload::make('mobile_image')
                                    ->label('Imagen Movil')
                                    ->image()
                                    ->directory('banners/mobile')
                                    ->imageResizeMode('cover')
                                    ->imageCropAspectRatio('1:1')
                                    ->imageResizeTargetWidth('600')
                                    ->imageResizeTargetHeight('600')
                                    ->helperText('Opcional: imagen optimizada para moviles'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Enlace')
                            ->icon('heroicon-o-link')
                            ->schema([
                                Forms\Components\Select::make('link_type')
                                    ->label('Tipo de Enlace')
                                    ->options([
                                        'none' => 'Sin enlace',
                                        'url' => 'URL Externa',
                                        'restaurant' => 'Restaurante',
                                        'promotion' => 'Promocion',
                                        'category' => 'Categoria',
                                    ])
                                    ->default('none')
                                    ->live()
                                    ->required(),

                                Forms\Components\TextInput::make('link_url')
                                    ->label('URL')
                                    ->url()
                                    ->maxLength(500)
                                    ->visible(fn (Forms\Get $get) => $get('link_type') === 'url'),

                                Forms\Components\Select::make('restaurant_id')
                                    ->label('Restaurante')
                                    ->relationship('restaurant', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Forms\Get $get) => $get('link_type') === 'restaurant'),

                                Forms\Components\Select::make('promotion_id')
                                    ->label('Promocion')
                                    ->relationship('promotion', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Forms\Get $get) => $get('link_type') === 'promotion'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Posicion y Estilo')
                            ->icon('heroicon-o-squares-2x2')
                            ->schema([
                                Forms\Components\Select::make('position')
                                    ->label('Posicion')
                                    ->options([
                                        'home_hero' => 'Hero Principal',
                                        'home_secondary' => 'Secundario Home',
                                        'category_top' => 'Top Categoria',
                                        'restaurant_list' => 'Lista Restaurantes',
                                        'checkout' => 'Checkout',
                                        'sidebar' => 'Sidebar',
                                        'popup' => 'Popup',
                                    ])
                                    ->default('home_hero')
                                    ->required(),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Orden')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Menor numero aparece primero'),

                                Forms\Components\ColorPicker::make('background_color')
                                    ->label('Color de Fondo'),

                                Forms\Components\ColorPicker::make('text_color')
                                    ->label('Color del Texto'),

                                Forms\Components\ColorPicker::make('button_color')
                                    ->label('Color del Boton'),
                            ])->columns(3),

                        Forms\Components\Tabs\Tab::make('Validez')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Forms\Components\DateTimePicker::make('starts_at')
                                    ->label('Fecha de Inicio')
                                    ->native(false),

                                Forms\Components\DateTimePicker::make('ends_at')
                                    ->label('Fecha de Fin')
                                    ->native(false),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Activo')
                                    ->default(true),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Estadisticas')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Forms\Components\Placeholder::make('views_count')
                                    ->label('Visualizaciones')
                                    ->content(fn ($record) => number_format($record?->views_count ?? 0)),

                                Forms\Components\Placeholder::make('clicks_count')
                                    ->label('Clicks')
                                    ->content(fn ($record) => number_format($record?->clicks_count ?? 0)),

                                Forms\Components\Placeholder::make('click_rate')
                                    ->label('Tasa de Clicks (CTR)')
                                    ->content(fn ($record) => $record?->views_count > 0
                                        ? number_format(($record->clicks_count / $record->views_count) * 100, 2) . '%'
                                        : '0%'),
                            ])->columns(3)
                            ->visible(fn ($record) => $record !== null),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('')
                    ->square()
                    ->size(60),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titulo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(30),

                Tables\Columns\TextColumn::make('position')
                    ->label('Posicion')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match($state) {
                        'home_hero' => 'Hero',
                        'home_secondary' => 'Secundario',
                        'category_top' => 'Categoria',
                        'restaurant_list' => 'Restaurantes',
                        'checkout' => 'Checkout',
                        'sidebar' => 'Sidebar',
                        'popup' => 'Popup',
                        default => $state,
                    })
                    ->color(fn (string $state) => match($state) {
                        'home_hero' => 'primary',
                        'home_secondary' => 'info',
                        'popup' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('link_type')
                    ->label('Enlace')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match($state) {
                        'none' => 'Sin enlace',
                        'url' => 'URL',
                        'restaurant' => 'Restaurante',
                        'promotion' => 'Promocion',
                        'category' => 'Categoria',
                        default => $state,
                    })
                    ->color(fn (string $state) => match($state) {
                        'none' => 'gray',
                        'url' => 'info',
                        'restaurant' => 'success',
                        'promotion' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('views_count')
                    ->label('Vistas')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('clicks_count')
                    ->label('Clicks')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ctr')
                    ->label('CTR')
                    ->getStateUsing(fn ($record) => $record->views_count > 0
                        ? number_format(($record->clicks_count / $record->views_count) * 100, 2) . '%'
                        : '0%')
                    ->badge()
                    ->color(fn ($record) => match(true) {
                        $record->views_count === 0 => 'gray',
                        ($record->clicks_count / max(1, $record->views_count)) >= 0.05 => 'success',
                        ($record->clicks_count / max(1, $record->views_count)) >= 0.02 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Inicia')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Termina')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->ends_at?->isPast() ? 'danger' : null),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (!$record->is_active) return 'Inactivo';
                        if ($record->ends_at?->isPast()) return 'Expirado';
                        if ($record->starts_at?->isFuture()) return 'Programado';
                        return 'Visible';
                    })
                    ->color(fn ($state) => match($state) {
                        'Visible' => 'success',
                        'Programado' => 'info',
                        'Expirado' => 'danger',
                        'Inactivo' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('position')
                    ->label('Posicion')
                    ->options([
                        'home_hero' => 'Hero Principal',
                        'home_secondary' => 'Secundario Home',
                        'category_top' => 'Top Categoria',
                        'restaurant_list' => 'Lista Restaurantes',
                        'checkout' => 'Checkout',
                        'sidebar' => 'Sidebar',
                        'popup' => 'Popup',
                    ]),

                Tables\Filters\SelectFilter::make('link_type')
                    ->label('Tipo de Enlace')
                    ->options([
                        'none' => 'Sin enlace',
                        'url' => 'URL Externa',
                        'restaurant' => 'Restaurante',
                        'promotion' => 'Promocion',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activo'),

                Tables\Filters\Filter::make('visible')
                    ->label('Visibles Ahora')
                    ->query(fn (Builder $query) => $query->visible()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->action(fn ($record) => $record->update(['is_active' => !$record->is_active]))
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Banner $record) {
                        $newBanner = $record->replicate();
                        $newBanner->title = $record->title . ' (Copia)';
                        $newBanner->views_count = 0;
                        $newBanner->clicks_count = 0;
                        $newBanner->is_active = false;
                        $newBanner->save();
                    }),
                Tables\Actions\Action::make('reset_stats')
                    ->label('Reiniciar Stats')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(fn ($record) => $record->update(['views_count' => 0, 'clicks_count' => 0]))
                    ->requiresConfirmation()
                    ->modalDescription('Esto reiniciara las estadisticas de vistas y clicks a 0.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation()
                        ->color('danger'),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
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
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'view' => Pages\ViewBanner::route('/{record}'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
