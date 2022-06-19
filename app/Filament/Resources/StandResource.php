<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StandResource\Pages;
use App\Filament\Resources\StandResource\RelationManagers;
use App\Models\Aircraft\Aircraft;
use App\Models\Aircraft\WakeCategory;
use App\Models\Aircraft\WakeCategoryScheme;
use App\Models\Airfield\Airfield;
use App\Models\Airfield\Terminal;
use App\Models\Stand\Stand;
use App\Models\Stand\StandType;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Form;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class StandResource extends Resource
{
    protected static ?string $model = Stand::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $recordTitleAttribute = 'airfieldIdentifier';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Identifiers')->schema(
                    [
                        Select::make('airfield_id')
                            ->label('Airfield')
                            ->helperText(__('Required'))
                            ->hintIcon('heroicon-o-folder')
                            ->options(
                                fn() => Airfield::all()->mapWithKeys(
                                    fn(Airfield $airfield) => [$airfield->id => $airfield->code]
                                )
                            )
                            ->searchable()
                            ->disabled(fn (Page $livewire) => !$livewire instanceof CreateRecord)
                            ->dehydrated(fn (Page $livewire) => $livewire instanceof CreateRecord)
                            ->required(),
                        Select::make('terminal_id')
                            ->label('Terminal')
                            ->helperText(__('Required'))
                            ->hintIcon('heroicon-o-folder')
                            ->options(
                                fn() => Terminal::all()->mapWithKeys(
                                    fn(Terminal $terminal) => [$terminal->id => $terminal->description]
                                )
                            )
                            ->searchable()
                            ->disabled(fn (Page $livewire) => !$livewire instanceof CreateRecord)
                            ->dehydrated(fn (Page $livewire) => $livewire instanceof CreateRecord),
                        TextInput::make('identifier')
                            ->label(__('Identifier'))
                            ->maxLength(255)
                            ->required(),
                        Select::make('type_id')
                            ->label(__('Type'))
                            ->hintIcon('heroicon-o-folder')
                            ->options(
                                fn() => StandType::all()->mapWithKeys(
                                    fn(StandType $type) => [$type->id => $type->key]
                                )
                            )
                            ->searchable(),
                        TextInput::make('latitude')
                            ->label(__('Latitude'))
                            ->numeric('decimal')
                            ->helperText('The decimal latitude of the stand')
                            ->required(),
                        TextInput::make('longitude')
                            ->label(__('Longitude'))
                            ->numeric('decimal')
                            ->helperText('The decimal longitude of the stand')
                            ->required(),
                    ]
                ),
                Fieldset::make('Allocation')->schema(
                    [
                        Select::make('wake_category_id')
                            ->label(__('Maximum UK Wake Category'))
                            ->hintIcon('heroicon-o-scale')
                            ->options(
                                fn() => WakeCategoryScheme::with('categories')
                                    ->uk()
                                    ->firstOrFail()
                                    ->categories
                                    ->mapWithKeys(
                                        fn(WakeCategory $category) => [$category->id => sprintf('%s (%s)', $category->description, $category->code)]
                                    )
                            )
                            ->helperText('Maximum UK WTC that can be assigned to this stand. Used as a fallback if no specific aircraft type if specified.')
                            ->searchable()
                            ->required(),
                        Select::make('max_aircraft_id')
                            ->label(__('Maximum Aircraft Type'))
                            ->hintIcon('heroicon-o-paper-airplane')
                            ->options(
                                fn() => Aircraft::all()->mapWithKeys(fn (Aircraft $aircraft) => [$aircraft->id => $aircraft->code])
                            )
                            ->helperText('Maximum aircraft size that can be assigned to the stand. Overrides Max WTC.')
                            ->searchable(),
                        Toggle::make('isOpen')
                            ->label(__('Available for Allocation'))
                            ->helperText('Stands not open for allocation will not be allocated by the automatic allocator or be available for controllers to assign.')
                            ->default(true)
                            ->required(),
                        TextInput::make('assignment_priority')
                            ->label(__('Allocation Priority'))
                            ->helperText('Global priority when assigning. Lower value is higher priority.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(9999)
                            ->default(100)
                            ->required(),
                    ]
                ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('Id'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('airfield.code')
                    ->label(__('Airfield'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('terminal.description')
                    ->label(__('Terminal'))
                    ->default('--')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('identifier')
                    ->label(__('Identifier'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TagsColumn::make('uniqueAirlines.icao_code')
                    ->label(__('Airlines'))
                    ->default(['--'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignment_priority')
                    ->label(__('Assignment priority (lower is higher)'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BooleanColumn::make('isOpen')
                    ->label(__('Available for Allocation'))
            ])->defaultSort('airfield.code')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListStands::route('/'),
            'create' => Pages\CreateStand::route('/create'),
            'edit' => Pages\EditStand::route('/{record}/edit'),
            'view' => Pages\ViewStand::route('/{record}')
        ];
    }
}
