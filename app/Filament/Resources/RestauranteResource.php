<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestauranteResource\Pages;
use App\Models\Restaurante;
use App\Models\Provincia;
use App\Models\Municipio;
use App\Models\Sector;
use App\Models\Usuario;
use App\Models\Plan;
use App\Models\NivelConfianza;
use App\Models\Certificacion;
use App\Models\Personal;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class RestauranteResource extends Resource
{
    protected static ?string $model = Restaurante::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static \UnitEnum|string|null $navigationGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Restaurantes';

    protected static ?string $modelLabel = 'Restaurante';

    protected static ?string $pluralModelLabel = 'Restaurantes';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Schemas\Components\Tabs::make('Restaurante')
                    ->tabs([
                        // ==========================================
                        // TAB: INFORMACIÓN GENERAL
                        // ==========================================
                        Schemas\Components\Tabs\Tab::make('Información General')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Schemas\Components\Section::make('Datos Básicos')
                                    ->schema([
                                        Forms\Components\Select::make('dueno_id')
                                            ->label('Dueño')
                                            ->options(Usuario::where('rol', 'dueno_restaurante')->pluck('nombre', 'id'))
                                            ->searchable()
                                            ->required(),

                                        Forms\Components\TextInput::make('nombre')
                                            ->label('Nombre del Restaurante')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                                                $operation === 'create' ? $set('slug', Str::slug($state)) : null
                                            ),

                                        Forms\Components\TextInput::make('slug')
                                            ->label('URL amigable')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('descripcion')
                                            ->label('Descripción')
                                            ->rows(3)
                                            ->maxLength(1000)
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('descripcion_corta')
                                            ->label('Descripción Corta')
                                            ->maxLength(255),

                                        Forms\Components\TagsInput::make('tipos_cocina')
                                            ->label('Tipos de Cocina')
                                            ->suggestions([
                                                'Dominicana', 'Italiana', 'Mexicana', 'China',
                                                'Japonesa', 'Americana', 'Mariscos', 'Carnes',
                                                'Vegetariana', 'Pizzería', 'Fast Food', 'Sushi',
                                            ]),

                                        Forms\Components\TagsInput::make('etiquetas')
                                            ->label('Etiquetas'),
                                    ])
                                    ->columns(2),
                            ]),

                        // ==========================================
                        // TAB: CONTACTO Y UBICACIÓN
                        // ==========================================
                        Schemas\Components\Tabs\Tab::make('Contacto')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Schemas\Components\Section::make('Datos de Contacto')
                                    ->schema([
                                        Forms\Components\TextInput::make('telefono')
                                            ->label('Teléfono')
                                            ->tel()
                                            ->maxLength(20),

                                        Forms\Components\TextInput::make('whatsapp')
                                            ->label('WhatsApp')
                                            ->tel()
                                            ->maxLength(20),

                                        Forms\Components\TextInput::make('email')
                                            ->label('Correo Electrónico')
                                            ->email()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('sitio_web')
                                            ->label('Sitio Web')
                                            ->url()
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),

                                Schemas\Components\Section::make('Ubicación')
                                    ->schema([
                                        Forms\Components\Textarea::make('direccion')
                                            ->label('Dirección')
                                            ->rows(2)
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('provincia_id')
                                            ->label('Provincia')
                                            ->options(Provincia::pluck('nombre', 'id'))
                                            ->searchable()
                                            ->live(),

                                        Forms\Components\Select::make('municipio_id')
                                            ->label('Municipio')
                                            ->options(fn (Forms\Get $get) =>
                                                Municipio::where('provincia_id', $get('provincia_id'))->pluck('nombre', 'id')
                                            )
                                            ->searchable()
                                            ->live(),

                                        Forms\Components\Select::make('sector_id')
                                            ->label('Sector')
                                            ->options(fn (Forms\Get $get) =>
                                                Sector::where('municipio_id', $get('municipio_id'))->pluck('nombre', 'id')
                                            )
                                            ->searchable(),

                                        Forms\Components\TextInput::make('latitud')
                                            ->label('Latitud')
                                            ->numeric()
                                            ->step(0.00000001),

                                        Forms\Components\TextInput::make('longitud')
                                            ->label('Longitud')
                                            ->numeric()
                                            ->step(0.00000001),
                                    ])
                                    ->columns(2),
                            ]),

                        // ==========================================
                        // TAB: IMÁGENES
                        // ==========================================
                        Schemas\Components\Tabs\Tab::make('Imágenes')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\FileUpload::make('logo')
                                    ->label('Logo')
                                    ->image()
                                    ->directory('restaurantes/logos')
                                    ->imageEditor(),

                                Forms\Components\FileUpload::make('imagen_portada')
                                    ->label('Imagen de Portada')
                                    ->image()
                                    ->directory('restaurantes/portadas')
                                    ->imageEditor(),
                            ])
                            ->columns(2),

                        // ==========================================
                        // TAB: PLAN Y SUSCRIPCIÓN
                        // ==========================================
                        Schemas\Components\Tabs\Tab::make('Plan')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Schemas\Components\Section::make('Plan Actual')
                                    ->schema([
                                        Forms\Components\Select::make('plan_id')
                                            ->label('Plan')
                                            ->options(Plan::activos()->pluck('nombre', 'id'))
                                            ->searchable()
                                            ->helperText('Plan de suscripción del restaurante'),

                                        Schemas\Components\Placeholder::make('suscripcion_info')
                                            ->label('Suscripción Activa')
                                            ->content(fn ($record) => $record?->suscripcionActiva
                                                ? "Estado: {$record->suscripcionActiva->estado} | Próx. facturación: " . $record->suscripcionActiva->proxima_facturacion?->format('d/m/Y')
                                                : 'Sin suscripción activa'
                                            )
                                            ->visible(fn ($record) => $record !== null),
                                    ])
                                    ->columns(2),

                                Schemas\Components\Section::make('Estado de Cuenta')
                                    ->schema([
                                        Forms\Components\Select::make('estado_cuenta')
                                            ->label('Estado de Cuenta')
                                            ->options([
                                                'al_dia' => '🟢 Al Día',
                                                'pendiente' => '🟡 Pendiente',
                                                'moroso' => '🔴 Moroso',
                                            ])
                                            ->default('al_dia'),

                                        Forms\Components\TextInput::make('saldo_pendiente')
                                            ->label('Saldo Pendiente')
                                            ->numeric()
                                            ->prefix('RD$')
                                            ->default(0),

                                        Forms\Components\TextInput::make('dias_mora')
                                            ->label('Días en Mora')
                                            ->numeric()
                                            ->default(0),

                                        Forms\Components\DatePicker::make('ultimo_pago_at')
                                            ->label('Último Pago'),
                                    ])
                                    ->columns(4)
                                    ->collapsible(),
                            ]),

                        // ==========================================
                        // TAB: CONFIANZA (INTERNO)
                        // ==========================================
                        Schemas\Components\Tabs\Tab::make('Confianza')
                            ->icon('heroicon-o-shield-check')
                            ->badge(fn ($record) => $record?->nivelConfianza?->codigo ?? 'nuevo')
                            ->badgeColor(fn ($record) => match ($record?->nivelConfianza?->codigo) {
                                'socio' => 'success',
                                'confiable' => 'info',
                                'observacion' => 'warning',
                                'suspendido' => 'danger',
                                default => 'gray',
                            })
                            ->schema([
                                Schemas\Components\Section::make('Nivel de Confianza (Interno)')
                                    ->description('Este nivel es INTERNO y no se muestra al público. Se usa para toma de decisiones.')
                                    ->schema([
                                        Forms\Components\Select::make('nivel_confianza_id')
                                            ->label('Nivel de Confianza')
                                            ->options(NivelConfianza::activos()->pluck('nombre', 'id'))
                                            ->searchable(),

                                        Forms\Components\TextInput::make('puntuacion_confianza')
                                            ->label('Puntuación')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('/ 100')
                                            ->disabled()
                                            ->dehydrated(false),

                                        Forms\Components\DateTimePicker::make('ultima_evaluacion_confianza_at')
                                            ->label('Última Evaluación')
                                            ->disabled()
                                            ->dehydrated(false),
                                    ])
                                    ->columns(3),

                                Schemas\Components\Section::make('Factores de Confianza')
                                    ->schema([
                                        Schemas\Components\Placeholder::make('factores_info')
                                            ->content('Los factores de confianza se calculan automáticamente basándose en: antigüedad, historial de pagos, pedidos completados, calificaciones de clientes, verificaciones e incidentes.')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                        // ==========================================
                        // TAB: CERTIFICACIÓN (PÚBLICO)
                        // ==========================================
                        Schemas\Components\Tabs\Tab::make('Certificación')
                            ->icon('heroicon-o-academic-cap')
                            ->badge(fn ($record) => $record?->certificacion?->codigo ?? '-')
                            ->badgeColor(fn ($record) => match ($record?->certificacion?->codigo) {
                                'A' => 'success',
                                'B' => 'info',
                                'C' => 'warning',
                                'D', 'E' => 'danger',
                                default => 'gray',
                            })
                            ->schema([
                                Schemas\Components\Section::make('Certificación Pública')
                                    ->description('Esta certificación es VISIBLE al público y aparece en el perfil del restaurante.')
                                    ->schema([
                                        Forms\Components\Select::make('certificacion_id')
                                            ->label('Certificación')
                                            ->options(Certificacion::activas()->pluck('nombre', 'id'))
                                            ->searchable(),

                                        Forms\Components\TextInput::make('puntuacion_certificacion')
                                            ->label('Puntuación')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('/ 100')
                                            ->disabled()
                                            ->dehydrated(false),

                                        Forms\Components\DatePicker::make('certificacion_otorgada_at')
                                            ->label('Otorgada'),

                                        Forms\Components\DatePicker::make('certificacion_vence_at')
                                            ->label('Vence'),
                                    ])
                                    ->columns(4),
                            ]),

                        // ==========================================
                        // TAB: VERIFICACIÓN
                        // ==========================================
                        Schemas\Components\Tabs\Tab::make('Verificación')
                            ->icon('heroicon-o-check-badge')
                            ->schema([
                                Schemas\Components\Section::make('Estado de Verificación')
                                    ->schema([
                                        Forms\Components\Toggle::make('verificado')
                                            ->label('Verificado')
                                            ->helperText('El restaurante ha sido verificado físicamente'),

                                        Forms\Components\DateTimePicker::make('verificado_at')
                                            ->label('Fecha de Verificación'),

                                        Forms\Components\Select::make('verificado_por')
                                            ->label('Verificado Por')
                                            ->options(fn () => \App\Models\User::query()->pluck('name', 'id'))
                                            ->searchable(),

                                        Forms\Components\DatePicker::make('proxima_verificacion')
                                            ->label('Próxima Verificación'),
                                    ])
                                    ->columns(2),

                                Schemas\Components\Section::make('Onboarding')
                                    ->schema([
                                        Forms\Components\Select::make('estado_onboarding')
                                            ->label('Estado de Onboarding')
                                            ->options([
                                                'pendiente' => '⏳ Pendiente',
                                                'en_proceso' => '🔄 En Proceso',
                                                'documentos' => '📄 Documentos',
                                                'verificacion' => '✅ Verificación',
                                                'capacitacion' => '📚 Capacitación',
                                                'completado' => '🎉 Completado',
                                            ])
                                            ->default('pendiente'),
                                    ])
                                    ->collapsible(),
                            ]),

                        // ==========================================
                        // TAB: CONFIGURACIÓN
                        // ==========================================
                        Schemas\Components\Tabs\Tab::make('Configuración')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Schemas\Components\Section::make('Opciones de Servicio')
                                    ->schema([
                                        Forms\Components\TextInput::make('pedido_minimo')
                                            ->label('Pedido Mínimo')
                                            ->numeric()
                                            ->prefix('RD$')
                                            ->default(0),

                                        Forms\Components\TextInput::make('tarifa_delivery')
                                            ->label('Tarifa de Delivery')
                                            ->numeric()
                                            ->prefix('RD$')
                                            ->default(0),

                                        Forms\Components\TextInput::make('radio_delivery_km')
                                            ->label('Radio de Delivery (km)')
                                            ->numeric()
                                            ->suffix('km')
                                            ->default(5),

                                        Forms\Components\TextInput::make('tiempo_preparacion')
                                            ->label('Tiempo de Preparación')
                                            ->numeric()
                                            ->suffix('min')
                                            ->default(30),

                                        Forms\Components\Select::make('rango_precios')
                                            ->label('Rango de Precios')
                                            ->options([
                                                1 => '$ - Económico',
                                                2 => '$$ - Moderado',
                                                3 => '$$$ - Caro',
                                                4 => '$$$$ - Muy Caro',
                                            ]),
                                    ])
                                    ->columns(3),

                                Schemas\Components\Section::make('Capacidades')
                                    ->schema([
                                        Forms\Components\Toggle::make('acepta_delivery')
                                            ->label('Acepta Delivery')
                                            ->default(true),

                                        Forms\Components\Toggle::make('acepta_recogida')
                                            ->label('Acepta Recogida')
                                            ->default(true),

                                        Forms\Components\Toggle::make('acepta_reservas')
                                            ->label('Acepta Reservas')
                                            ->default(false),
                                    ])
                                    ->columns(3),

                                Schemas\Components\Section::make('Funciones Personalizadas')
                                    ->description('Override de funciones específicas para este restaurante')
                                    ->schema([
                                        Forms\Components\KeyValue::make('funciones_habilitadas')
                                            ->label('Funciones')
                                            ->keyLabel('Código de Función')
                                            ->valueLabel('Habilitado (true/false)')
                                            ->columnSpanFull(),

                                        Forms\Components\KeyValue::make('limites_override')
                                            ->label('Límites Personalizados')
                                            ->keyLabel('Código de Límite')
                                            ->valueLabel('Valor')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                        // ==========================================
                        // TAB: FISCAL
                        // ==========================================
                        Schemas\Components\Tabs\Tab::make('Fiscal')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Schemas\Components\Section::make('Datos Fiscales')
                                    ->schema([
                                        Forms\Components\TextInput::make('rnc')
                                            ->label('RNC')
                                            ->maxLength(15),

                                        Forms\Components\TextInput::make('nombre_legal')
                                            ->label('Nombre Legal / Razón Social')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('razon_social')
                                            ->label('Razón Social')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('cuenta_bancaria')
                                            ->label('Cuenta Bancaria')
                                            ->maxLength(30),

                                        Forms\Components\TextInput::make('banco')
                                            ->label('Banco')
                                            ->maxLength(100),

                                        Forms\Components\TextInput::make('comision_porcentaje')
                                            ->label('Comisión')
                                            ->numeric()
                                            ->suffix('%')
                                            ->default(15),
                                    ])
                                    ->columns(2),

                                Schemas\Components\Section::make('Documentos')
                                    ->schema([
                                        Forms\Components\FileUpload::make('documentos')
                                            ->label('Documentos')
                                            ->multiple()
                                            ->directory('restaurantes/documentos')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                        // ==========================================
                        // TAB: ESTADO
                        // ==========================================
                        Schemas\Components\Tabs\Tab::make('Estado')
                            ->icon('heroicon-o-signal')
                            ->schema([
                                Schemas\Components\Section::make('Estado del Restaurante')
                                    ->schema([
                                        Forms\Components\Toggle::make('activo')
                                            ->label('Activo')
                                            ->default(true),

                                        Forms\Components\Toggle::make('destacado')
                                            ->label('Destacado')
                                            ->default(false),

                                        Forms\Components\DateTimePicker::make('aprobado_en')
                                            ->label('Aprobado En'),
                                    ])
                                    ->columns(3),

                                Schemas\Components\Section::make('Notas Internas')
                                    ->schema([
                                        Forms\Components\Textarea::make('notas_internas')
                                            ->label('Notas')
                                            ->rows(3)
                                            ->helperText('Notas visibles solo para el personal de SazónRD')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(asset('images/restaurante-default.png')),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->texto_tipos_cocina),

                Tables\Columns\TextColumn::make('plan.nombre')
                    ->label('Plan')
                    ->badge()
                    ->color(fn ($record) => $record->plan?->color ?? 'gray'),

                Tables\Columns\TextColumn::make('nivelConfianza.codigo')
                    ->label('Confianza')
                    ->badge()
                    ->color(fn ($record) => match ($record->nivelConfianza?->codigo) {
                        'socio' => 'success',
                        'confiable' => 'info',
                        'observacion' => 'warning',
                        'suspendido' => 'danger',
                        default => 'gray',
                    })
                    ->tooltip(fn ($record) => $record->nivelConfianza?->nombre),

                Tables\Columns\TextColumn::make('certificacion.codigo')
                    ->label('Cert.')
                    ->badge()
                    ->size('lg')
                    ->color(fn ($record) => match ($record->certificacion?->codigo) {
                        'A' => 'success',
                        'B' => 'info',
                        'C' => 'warning',
                        'D', 'E' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('calificacion')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format($state, 1) . ' ⭐' : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estado_cuenta')
                    ->label('Cuenta')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'al_dia' => 'success',
                        'pendiente' => 'warning',
                        'moroso' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('verificado')
                    ->label('Verif.')
                    ->boolean(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\IconColumn::make('destacado')
                    ->label('Dest.')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(Plan::pluck('nombre', 'id')),

                Tables\Filters\SelectFilter::make('nivel_confianza_id')
                    ->label('Nivel de Confianza')
                    ->options(NivelConfianza::pluck('nombre', 'id')),

                Tables\Filters\SelectFilter::make('certificacion_id')
                    ->label('Certificación')
                    ->options(Certificacion::pluck('nombre', 'id')),

                Tables\Filters\SelectFilter::make('estado_cuenta')
                    ->label('Estado de Cuenta')
                    ->options([
                        'al_dia' => 'Al Día',
                        'pendiente' => 'Pendiente',
                        'moroso' => 'Moroso',
                    ]),

                Tables\Filters\TernaryFilter::make('verificado')
                    ->label('Verificado'),

                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Activo'),

                Tables\Filters\TernaryFilter::make('destacado')
                    ->label('Destacado'),

                Tables\Filters\SelectFilter::make('provincia_id')
                    ->label('Provincia')
                    ->options(Provincia::pluck('nombre', 'id')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\Action::make('recalcular_confianza')
                        ->label('Recalcular Confianza')
                        ->icon('heroicon-o-calculator')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->actualizarNivelConfianza()),
                    \Filament\Actions\Action::make('generar_qr')
                        ->label('Generar QR')
                        ->icon('heroicon-o-qr-code')
                        ->url(fn ($record) => route('restaurantes.qr', $record)),
                ]),
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
            'index' => Pages\ListRestaurantes::route('/'),
            'create' => Pages\CreateRestaurante::route('/create'),
            'view' => Pages\ViewRestaurante::route('/{record}'),
            'edit' => Pages\EditRestaurante::route('/{record}/edit'),
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

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Plan' => $record->plan?->nombre ?? 'Sin plan',
            'Certificación' => $record->certificacion?->codigo ?? '-',
            'Estado' => $record->activo ? 'Activo' : 'Inactivo',
        ];
    }
}
