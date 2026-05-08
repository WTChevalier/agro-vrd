<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PersonalResource\Pages;
use App\Models\Personal;
use App\Models\Rol;
use App\Models\Municipio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PersonalResource extends Resource
{
    protected static ?string $model = Personal::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Personal';

    protected static ?string $navigationLabel = 'Personal';

    protected static ?string $modelLabel = 'Personal';

    protected static ?string $pluralModelLabel = 'Personal';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Personal')
                    ->schema([
                        Forms\Components\TextInput::make('nombre_completo')
                            ->label('Nombre Completo')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('cedula')
                            ->label('Cédula')
                            ->unique(ignoreRecord: true)
                            ->maxLength(15),

                        Forms\Components\TextInput::make('codigo_empleado')
                            ->label('Código de Empleado')
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->helperText('Se genera automáticamente si se deja vacío'),

                        Forms\Components\Select::make('tipo')
                            ->label('Tipo de Contrato')
                            ->options([
                                Personal::TIPO_INTERNO => 'Interno',
                                Personal::TIPO_EXTERNO => 'Externo',
                                Personal::TIPO_TEMPORAL => 'Temporal',
                            ])
                            ->required()
                            ->default(Personal::TIPO_INTERNO),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contacto')
                    ->schema([
                        Forms\Components\TextInput::make('telefono')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Textarea::make('direccion')
                            ->label('Dirección')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Rol y Permisos')
                    ->schema([
                        Forms\Components\Select::make('rol_id')
                            ->label('Rol')
                            ->options(Rol::activos()->ordenadoPorNivel()->pluck('nombre', 'id'))
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('usuario_id')
                            ->label('Usuario del Sistema')
                            ->relationship('usuario', 'nombre')
                            ->searchable()
                            ->helperText('Vincular con una cuenta de usuario existente'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Zonas Asignadas')
                    ->schema([
                        Forms\Components\Select::make('zonas_asignadas')
                            ->label('Municipios/Zonas')
                            ->multiple()
                            ->options(Municipio::with('provincia')->get()->mapWithKeys(function ($m) {
                                return [$m->id => $m->provincia->nombre . ' - ' . $m->nombre];
                            }))
                            ->searchable()
                            ->columnSpanFull()
                            ->helperText('Dejar vacío para acceso a todas las zonas (según rol)'),
                    ]),

                Forms\Components\Section::make('Horario de Trabajo')
                    ->schema([
                        Forms\Components\KeyValue::make('horario_trabajo')
                            ->label('Horario')
                            ->keyLabel('Día')
                            ->valueLabel('Horario')
                            ->default([
                                'lunes' => '08:00-17:00',
                                'martes' => '08:00-17:00',
                                'miercoles' => '08:00-17:00',
                                'jueves' => '08:00-17:00',
                                'viernes' => '08:00-17:00',
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Fechas y Estado')
                    ->schema([
                        Forms\Components\DatePicker::make('fecha_ingreso')
                            ->label('Fecha de Ingreso')
                            ->default(now()),

                        Forms\Components\DatePicker::make('fecha_salida')
                            ->label('Fecha de Salida'),

                        Forms\Components\Select::make('estado')
                            ->label('Estado')
                            ->options([
                                Personal::ESTADO_ACTIVO => '🟢 Activo',
                                Personal::ESTADO_INACTIVO => '⚫ Inactivo',
                                Personal::ESTADO_VACACIONES => '🏖️ Vacaciones',
                                Personal::ESTADO_SUSPENDIDO => '🔴 Suspendido',
                            ])
                            ->required()
                            ->default(Personal::ESTADO_ACTIVO),

                        Forms\Components\FileUpload::make('foto')
                            ->label('Foto')
                            ->image()
                            ->directory('personal/fotos')
                            ->imageEditor(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Notas')
                    ->schema([
                        Forms\Components\Textarea::make('notas')
                            ->label('Notas Internas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('foto')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(asset('images/user-default.png')),

                Tables\Columns\TextColumn::make('codigo_empleado')
                    ->label('Código')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rol.nombre')
                    ->label('Rol')
                    ->badge()
                    ->color(fn (Personal $record): string => $record->rol?->color ?? 'gray'),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'interno' => 'success',
                        'externo' => 'warning',
                        'temporal' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'activo' => 'success',
                        'inactivo' => 'gray',
                        'vacaciones' => 'info',
                        'suspendido' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('tareas_pendientes_count')
                    ->label('Tareas Pendientes')
                    ->counts([
                        'tareasAsignadas' => fn (Builder $query) => $query->whereIn('estado', ['pendiente', 'en_progreso']),
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_ingreso')
                    ->label('Ingreso')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rol_id')
                    ->label('Rol')
                    ->options(Rol::pluck('nombre', 'id')),

                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        Personal::TIPO_INTERNO => 'Interno',
                        Personal::TIPO_EXTERNO => 'Externo',
                        Personal::TIPO_TEMPORAL => 'Temporal',
                    ]),

                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        Personal::ESTADO_ACTIVO => 'Activo',
                        Personal::ESTADO_INACTIVO => 'Inactivo',
                        Personal::ESTADO_VACACIONES => 'Vacaciones',
                        Personal::ESTADO_SUSPENDIDO => 'Suspendido',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPersonal::route('/'),
            'create' => Pages\CreatePersonal::route('/create'),
            'view' => Pages\ViewPersonal::route('/{record}'),
            'edit' => Pages\EditPersonal::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('estado', Personal::ESTADO_ACTIVO)->count();
    }
}
