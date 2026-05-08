<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LandingBlockResource\Pages;
use App\Models\LandingBlock;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

/**
 * LandingBlockResource — Sprint 1068 (Día 2 — Vive RD).
 *
 * Panel admin para editar bloques estructurados:
 * - testimonials (con nombre, ciudad, texto, rating, avatar)
 * - faq (pregunta + respuesta)
 * - feature (ícono + título + descripción)
 * - rich_text (HTML libre)
 * - cta_secundario, partner_logo, category_highlight
 */
class LandingBlockResource extends Resource
{
    protected static ?string $model = LandingBlock::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static UnitEnum|string|null $navigationGroup = 'Marca';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Bloques de Landing';

    protected static ?string $modelLabel = 'Bloque';

    protected static ?string $pluralModelLabel = 'Bloques de Landing';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificación')->columns(2)->schema([
                Forms\Components\Select::make('tipo')
                    ->label('Tipo de bloque')
                    ->options([
                        'testimonial' => '💬 Testimonial',
                        'faq' => '❓ Pregunta frecuente',
                        'feature' => '✨ Feature / Cómo funciona',
                        'rich_text' => '📝 Texto enriquecido',
                        'category_highlight' => '⭐ Categoría destacada',
                        'cta_secundario' => '🔗 CTA secundario',
                        'partner_logo' => '🏷 Logo de partner',
                    ])
                    ->required()
                    ->reactive()
                    ->native(false),
                Forms\Components\TextInput::make('titulo')
                    ->label('Título / Identificador interno')
                    ->placeholder('Ej: "Testimonio de Ana - Santo Domingo"'),
            ]),

            Section::make('Contenido')->schema([
                // TESTIMONIAL fields
                Forms\Components\Group::make()
                    ->visible(fn (Forms\Get $get) => $get('tipo') === 'testimonial')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('contenido.nombre')->label('Nombre')->required(),
                        Forms\Components\TextInput::make('contenido.ciudad')->label('Ciudad'),
                        Forms\Components\Textarea::make('contenido.texto')
                            ->label('Texto del testimonio')
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('contenido.rating')
                            ->label('Rating (estrellas)')
                            ->options([1=>'⭐',2=>'⭐⭐',3=>'⭐⭐⭐',4=>'⭐⭐⭐⭐',5=>'⭐⭐⭐⭐⭐'])
                            ->default(5)
                            ->native(false),
                        Forms\Components\TextInput::make('contenido.avatar')
                            ->label('Avatar URL')
                            ->placeholder('https://i.pravatar.cc/150?img=1'),
                    ]),

                // FAQ fields
                Forms\Components\Group::make()
                    ->visible(fn (Forms\Get $get) => $get('tipo') === 'faq')
                    ->schema([
                        Forms\Components\TextInput::make('contenido.pregunta')->label('Pregunta')->required()->columnSpanFull(),
                        Forms\Components\Textarea::make('contenido.respuesta')->label('Respuesta')->rows(4)->required()->columnSpanFull(),
                    ]),

                // FEATURE fields
                Forms\Components\Group::make()
                    ->visible(fn (Forms\Get $get) => $get('tipo') === 'feature')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('contenido.icono')
                            ->label('Ícono FontAwesome o emoji')
                            ->placeholder('fa-rocket o 🚀')
                            ->required(),
                        Forms\Components\TextInput::make('contenido.titulo')->label('Título')->required()->columnSpan(2),
                        Forms\Components\Textarea::make('contenido.descripcion')->label('Descripción')->rows(3)->required()->columnSpanFull(),
                    ]),

                // RICH_TEXT fields
                Forms\Components\Group::make()
                    ->visible(fn (Forms\Get $get) => $get('tipo') === 'rich_text')
                    ->schema([
                        Forms\Components\RichEditor::make('contenido.html')
                            ->label('Contenido HTML')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                // Default JSON editor para tipos custom
                Forms\Components\Group::make()
                    ->visible(fn (Forms\Get $get) => in_array($get('tipo'), ['category_highlight', 'cta_secundario', 'partner_logo']))
                    ->schema([
                        Forms\Components\Textarea::make('contenido')
                            ->label('Contenido JSON (custom)')
                            ->rows(8)
                            ->helperText('Estructura libre. Se renderiza según el blade del tipo.'),
                    ]),
            ]),

            Section::make('Configuración')->columns(3)->collapsed()->schema([
                Forms\Components\Toggle::make('activo')->default(true),
                Forms\Components\TextInput::make('orden')->numeric()->default(0)->label('Orden'),
                Forms\Components\Textarea::make('metadata')
                    ->label('Metadata extra (JSON)')
                    ->rows(2)
                    ->helperText('Opcional: tags, source URL, etc.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'testimonial' => 'success',
                        'faq' => 'info',
                        'feature' => 'warning',
                        'rich_text' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => [
                        'testimonial' => '💬 Testimonial',
                        'faq' => '❓ FAQ',
                        'feature' => '✨ Feature',
                        'rich_text' => '📝 Texto',
                        'category_highlight' => '⭐ Cat. destacada',
                        'cta_secundario' => '🔗 CTA',
                        'partner_logo' => '🏷 Partner',
                    ][$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->limit(40)
                    ->wrap(),

                Tables\Columns\TextColumn::make('contenido')
                    ->label('Preview')
                    ->limit(60)
                    ->formatStateUsing(function ($state, $record) {
                        if (is_array($state)) {
                            $preview = $state['texto'] ?? $state['titulo'] ?? $state['pregunta'] ?? $state['nombre'] ?? '';
                            return mb_substr((string) $preview, 0, 60);
                        }
                        return mb_substr((string) $state, 0, 60);
                    })
                    ->wrap(),

                Tables\Columns\IconColumn::make('activo')->boolean(),

                Tables\Columns\TextColumn::make('orden')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')->options([
                    'testimonial' => 'Testimonial',
                    'faq' => 'FAQ',
                    'feature' => 'Feature',
                    'rich_text' => 'Texto',
                    'category_highlight' => 'Cat. destacada',
                    'cta_secundario' => 'CTA',
                    'partner_logo' => 'Partner logo',
                ]),
                Tables\Filters\TernaryFilter::make('activo'),
            ])
            ->defaultSort('orden')
            ->reorderable('orden');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLandingBlocks::route('/'),
            'create' => Pages\CreateLandingBlock::route('/create'),
            'edit' => Pages\EditLandingBlock::route('/{record}/edit'),
        ];
    }
}
