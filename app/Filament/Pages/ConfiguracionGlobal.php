<?php

namespace App\Filament\Pages;

use App\Models\ConfiguracionGlobal as ConfiguracionModel;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class ConfiguracionGlobal extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static \UnitEnum|string|null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Configuración Global';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.configuracion-global';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getConfiguracion());
    }

    protected function getConfiguracion(): array
    {
        $configuracion = [];

        $items = ConfiguracionModel::all();
        foreach ($items as $item) {
            $configuracion[$item->clave] = $item->valor;
        }

        return $configuracion;
    }

    public function form(Schema $schema): Schema
    {
        return $form
            ->schema([
                Schemas\Components\Tabs::make('Configuración')
                    ->tabs([
                        // ==========================================
                        // TAB: PLATAFORMA
                        // ==========================================
                        Schemas\Components\Tabs\Tab::make('Plataforma')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Schemas\Components\Section::make('Información General')
                                    ->schema([
                                        Forms\Components\TextInput::make('plataforma.nombre')
                                            ->label('Nombre de la Plataforma')
                                            ->default('SazónRD'),

                                        Forms\Components\TextInput::make('plataforma.slogan')
                                            ->label('Slogan')
                                            ->default('El sabor de República Dominicana'),

                                        Forms\Components\TextInput::make('plataforma.email_contacto')
                                            ->label('Email de Contacto')
                                            ->email(),

                                        Forms\Components\TextInput::make('plataforma.telefono')
                                            ->label('Teléfono'),

                                        Forms\Components\TextInput::make('plataforma.whatsapp')
                                            ->label('WhatsApp'),
                                    ])
                                    ->columns(2),

                                Schemas\Components\Section::make('Modo de Operación')
                                    ->schema([
                                        Forms\Components\Toggle::make('plataforma.modo_mantenimiento')
                                            ->label('Modo Mantenimiento')
                                            ->helperText('Desactiva el acceso público a la plataforma'),

                                        Forms\Components\Toggle::make('plataforma.registro_abierto')
                                            ->label('Registro Abierto')
                                            ->default(true)
                                            ->helperText('Permitir registro de nuevos restaurantes'),
                                    ])
                                    ->columns(2),
                            ]),

                        // ==========================================
                        // TAB: FUNCIONES GLOBALES
                        // ==========================================
                        Schemas\Components\Tabs\Tab::make('Funciones')
                            ->icon('heroicon-o-puzzle-piece')
                            ->schema([
                                Schemas\Components\Section::make('Módulos del Sistema')
                                    ->description('Active o desactive funciones a nivel global')
                                    ->schema([
                                        Forms\Components\Toggle::make('funciones.delivery_habilitado')
                                            ->label('Delivery')
                                            ->helperText('Permitir pedidos con delivery')
                                            ->default(true),

                                        Forms\Components\Toggle::make('funciones.recogida_habilitado')
                                            ->label('Recogida')
                                            ->helperText('Permitir pedidos para recoger')
                                            ->default(true),

                                        Forms\Components\Toggle::make('funciones.reservas_habilitado')
                                            ->label('Reservas')
                                            ->helperText('Permitir reservas de mesa')
                                            ->default(false),

                                        Forms\Components\Toggle::make('funciones.pagos_online_habilitado')
                                            ->label('Pagos Online')
                                            ->helperText('Permitir pagos con tarjeta')
                                            ->default(false),

                                        Forms\Components\Toggle::make('funciones.promociones_habilitado')
                                            ->label('Promociones')
                                            ->helperText('Sistema de promociones y cupones')
                                            ->default(true),

                                        Forms\Components\Toggle::make('funciones.calificaciones_habilitado')
                                            ->label('Calificaciones')
                                            ->helperText('Permitir calificaciones de clientes')
                                            ->default(true),

                                        Forms\Components\Toggle::make('funciones.chat_habilitado')
                                            ->label('Chat')
                                            ->helperText('Chat entre cliente y restaurante')
                                            ->default(false),

                                        Forms\Components\Toggle::make('funciones.notificaciones_push_habilitado')
                                            ->label('Push Notifications')
                                            ->helperText('Notificaciones push')
                                            ->default(true),
                                    ])
                                    ->columns(4),
                            ]),

                        // ==========================================
                        // TAB: SUSCRIPCIONES
                        // ==========================================
                        Schemas\Components\Tabs\Tab::make('Suscripciones')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Schemas\Components\Section::make('Configuración de Pagos')
                                    ->schema([
                                        Forms\Components\TextInput::make('suscripciones.dias_gracia')
                                            ->label('Días de Gracia')
                                            ->numeric()
                                            ->default(7)
                                            ->helperText('Días después del vencimiento antes de suspender'),

                                        Forms\Components\TextInput::make('suscripciones.dias_aviso_renovacion')
                                            ->label('Días de Aviso')
                                            ->numeric()
                                            ->default(7)
                                            ->helperText('Días antes del vencimiento para avisar'),

                                        Forms\Components\TextInput::make('suscripciones.intentos_cobro')
                                            ->label('Intentos de Cobro')
                                            ->numeric()
                                            ->default(3),
                                    ])
                                    ->columns(3),

                                Schemas\Components\Section::make('Restricciones por Mora')
                                    ->schema([
                                        Forms\Components\Toggle::make('suscripciones.bloquear_pedidos_mora')
                                            ->label('Bloquear Pedidos')
                                            ->helperText('Bloquear recepción de pedidos si hay mora'),

                                        Forms\Components\Toggle::make('suscripciones.ocultar_listado_mora')
                                            ->label('Ocultar del Listado')
                                            ->helperText('Ocultar restaurante del listado público si hay mora'),
                                    ])
                                    ->columns(2),
                            ]),

                        // ==========================================
                        // TAB: CALIDAD
                        // ==========================================
                        Schemas\Components\Tabs\Tab::make('Calidad')
                            ->icon('heroicon-o-star')
                            ->schema([
                                Schemas\Components\Section::make('Confianza')
                                    ->schema([
                                        Forms\Components\TextInput::make('calidad.dias_recalculo_confianza')
                                            ->label('Días entre Recálculos')
                                            ->numeric()
                                            ->default(7)
                                            ->helperText('Frecuencia de recálculo automático de confianza'),

                                        Forms\Components\Toggle::make('calidad.recalculo_automatico')
                                            ->label('Recálculo Automático')
                                            ->default(true),
                                    ])
                                    ->columns(2),

                                Schemas\Components\Section::make('Certificaciones')
                                    ->schema([
                                        Forms\Components\TextInput::make('calidad.vigencia_certificacion_meses')
                                            ->label('Vigencia (meses)')
                                            ->numeric()
                                            ->default(12),

                                        Forms\Components\Toggle::make('calidad.mostrar_certificacion_publica')
                                            ->label('Mostrar Públicamente')
                                            ->default(true)
                                            ->helperText('Mostrar certificación en perfil público'),
                                    ])
                                    ->columns(2),
                            ]),

                        // ==========================================
                        // TAB: NOTIFICACIONES
                        // ==========================================
                        Schemas\Components\Tabs\Tab::make('Notificaciones')
                            ->icon('heroicon-o-bell')
                            ->schema([
                                Schemas\Components\Section::make('Canales de Notificación')
                                    ->schema([
                                        Forms\Components\Toggle::make('notificaciones.email_habilitado')
                                            ->label('Email')
                                            ->default(true),

                                        Forms\Components\Toggle::make('notificaciones.sms_habilitado')
                                            ->label('SMS')
                                            ->default(false),

                                        Forms\Components\Toggle::make('notificaciones.whatsapp_habilitado')
                                            ->label('WhatsApp')
                                            ->default(true),

                                        Forms\Components\Toggle::make('notificaciones.push_habilitado')
                                            ->label('Push')
                                            ->default(true),
                                    ])
                                    ->columns(4),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('guardar')
                ->label('Guardar Configuración')
                ->icon('heroicon-o-check')
                ->action(function () {
                    $this->guardarConfiguracion();
                }),

            Action::make('limpiar_cache')
                ->label('Limpiar Caché')
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->action(function () {
                    Cache::flush();
                    Notification::make()
                        ->title('Caché limpiado')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function guardarConfiguracion(): void
    {
        $data = $this->form->getState();

        foreach ($this->flattenArray($data) as $clave => $valor) {
            ConfiguracionModel::updateOrCreate(
                ['clave' => $clave],
                [
                    'valor' => $valor,
                    'tipo' => is_bool($valor) ? 'boolean' : (is_numeric($valor) ? 'integer' : 'string'),
                ]
            );
        }

        // Limpiar caché de configuración
        Cache::forget('configuracion_global');

        Notification::make()
            ->title('Configuración guardada')
            ->success()
            ->send();
    }

    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
