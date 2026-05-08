<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RolResource\Pages;
use App\Models\Rol;
use App\Models\Permiso;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RolResource extends Resource
{
    protected static ?string $model = Rol::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Personal';

    protected static ?string $navigationLabel = 'Roles';

    protected static ?string $modelLabel = 'Rol';

    protected static ?string $pluralModelLabel = 'Roles';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Rol')
                    ->schema([
                        Forms\Components\TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->disabled(fn ($record) => $record?->es_sistema)
                            ->helperText('Identificador único del rol'),

                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nivel')
                            ->label('Nivel de Jerarquía')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Mayor número = más privilegios'),

                        Forms\Components\ColorPicker::make('color')
                            ->label('Color')
                            ->default('#6b7280'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Permisos')
                    ->schema([
                        Forms\Components\Placeholder::make('permisos_info')
                            ->label('')
                            ->content('Seleccione los permisos que tendrá este rol. Use "*" para todos los permisos.'),

                        Forms\Components\TagsInput::make('permisos')
                            ->label('Códigos de Permisos')
                            ->suggestions(
                                array_merge(
                                    ['*'],
                                    Permiso::pluck('codigo')->toArray(),
                                    ['restaurantes.*', 'pedidos.*', 'finanzas.*', 'personal.*', 'configuracion.*']
                                )
                            )
                            ->helperText('Escriba el código del permiso o seleccione de las sugerencias')
                            ->columnSpanFull(),

                        Forms\Components\CheckboxList::make('permisosRelacion')
                            ->label('Permisos Disponibles')
                            ->relationship('permisosRelacion', 'nombre')
                            ->columns(3)
                            ->searchable()
                            ->bulkToggleable()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Configuración')
                    ->schema([
                        Forms\Components\Toggle::make('es_sistema')
                            ->label('Rol de Sistema')
                            ->disabled()
                            ->helperText('Los roles de sistema no pueden modificarse'),

                        Forms\Components\Toggle::make('puede_eliminarse')
                            ->label('Puede Eliminarse')
                            ->disabled(fn ($record) => $record?->es_sistema)
                            ->default(true),

                        Forms\Components\Toggle::make('activo')
                            ->label('Activo')
                            ->default(true),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')
                    ->label(''),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('nivel')
                    ->label('Nivel')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 90 => 'danger',
                        $state >= 70 => 'warning',
                        $state >= 50 => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('personal_count')
                    ->label('Personal')
                    ->counts('personal')
                    ->sortable(),

                Tables\Columns\IconColumn::make('es_sistema')
                    ->label('Sistema')
                    ->boolean(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('nivel', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Activo'),

                Tables\Filters\TernaryFilter::make('es_sistema')
                    ->label('Rol de Sistema'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->es_sistema || auth()->user()?->hasRole('super_admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            $records = $records->filter(fn ($record) => $record->puede_eliminarse);
                        }),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRol::route('/create'),
            'view' => Pages\ViewRol::route('/{record}'),
            'edit' => Pages\EditRol::route('/{record}/edit'),
        ];
    }
}
