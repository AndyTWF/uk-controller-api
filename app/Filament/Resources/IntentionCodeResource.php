<?php

namespace App\Filament\Resources;

use App\Filament\Helpers\SelectOptions;
use App\Filament\Resources\IntentionCodeResource\Pages;
use App\Models\IntentionCode\ConditionType;
use App\Models\IntentionCode\FirExitPoint;
use App\Models\IntentionCode\IntentionCode;
use App\Rules\Airfield\AirfieldIcao;
use App\Rules\Airfield\PartialAirfieldIcao;
use App\Rules\Controller\ControllerPositionPartialCallsign;
use Closure;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

class IntentionCodeResource extends Resource
{
    use TranslatesStrings;

    protected static ?string $model = IntentionCode::class;
    protected static ?string $navigationIcon = 'heroicon-o-code';
    protected static ?string $navigationGroup = 'Intention Codes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('description')
                    ->required()
                    ->maxLength(255)
                    ->label(self::translateFormPath('description.label'))
                    ->helperText(self::translateFormPath('description.helper')),
                Fieldset::make('code_spec')
                    ->label(self::translateFormPath('code_spec.label'))
                    ->schema([
                        Select::make('code_type')
                            ->required()
                            ->reactive()
                            ->options([
                                'airfield_identifier' => 'Airfield Identifier',
                                'single_code' => 'Single Code',
                            ])
                            ->label(self::translateFormPath('code_type.label'))
                            ->helperText(self::translateFormPath('code_type.helper')),
                        TextInput::make('single_code')
                            ->required(fn(Closure $get) => $get('code_type') === 'single_code')
                            ->maxLength(2)
                            ->hidden(fn(Closure $get) => $get('code_type') !== 'single_code')
                            ->label(self::translateFormPath('single_code.label'))
                            ->helperText(self::translateFormPath('single_code.helper'))
                    ]),
                Fieldset::make('priority')
                    ->label(self::translateFormPath('priority.label'))
                    ->schema([
                        Select::make('order_type')
                            ->reactive()
                            ->required()
                            ->label(self::translateFormPath('order_type.label'))
                            ->helperText(self::translateFormPath('order_type.helper'))
                            ->options([
                                'at_position' => 'At Position',
                                'before' => 'Insert Before',
                                'after' => 'Insert After',
                            ]),
                        TextInput::make('position')
                            ->numeric()
                            ->minValue(1)
                            ->label(self::translateFormPath('position.label'))
                            ->helperText(self::translateFormPath('position.helper'))
                            ->hidden(fn(Closure $get) => $get('order_type') !== 'at_position')
                            ->required(fn(Closure $get) => $get('order_type') === 'at_position'),
                        Select::make('insert_position')
                            ->label(self::translateFormPath('before_after_position.label'))
                            ->helperText(self::translateFormPath('before_after_position.helper'))
                            ->hidden(fn(Closure $get) => !in_array($get('order_type'), ['before', 'after']))
                            ->required(fn(Closure $get) => in_array($get('order_type'), ['before', 'after']))
                            ->options(fn() => IntentionCode::all()->mapWithKeys(fn(IntentionCode $code) => [$code->id => self::formatCodeColumn($code)]
                            )
                            ),
                    ]),
                Section::make(self::translateFormPath('conditions.conditions.label'))->schema([self::conditions()]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('priority')
                    ->label(self::translateTablePath('columns.priority')),
                TextColumn::make('description')
                    ->label(self::translateTablePath('columns.description')),
                TextColumn::make('code')
                    ->formatStateUsing(fn(IntentionCode $record) => self::formatCodeColumn($record))
                    ->label(self::translateTablePath('columns.code')),

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIntentionCodes::route('/'),
            'create' => Pages\CreateIntentionCode::route('/create'),
            'view' => Pages\ViewIntentionCode::route('/{record}'),
            'edit' => Pages\EditIntentionCode::route('/{record}/edit'),
        ];
    }

    private static function formatCodeColumn(IntentionCode $record): string
    {
        return match ($record->code['type']) {
            'airfield_identifier' => 'Airfield Identifier',
            'single_code' => $record->code['code'],
        };
    }

    private static function conditions(): Builder
    {
        return Builder::make('conditions')
            ->label(self::translateFormPath('conditions.conditions.label'))
            ->helperText(self::translateFormPath('conditions.conditions.helper'))
            ->required()
            ->collapsible()
            ->blocks([
                Block::make(ConditionType::ArrivalAirfields->value)
                    ->label(self::translateFormPath('conditions.arrival_airfields.menu_item'))
                    ->schema([
                        Repeater::make('airfields')
                            ->label(self::translateFormPath('conditions.arrival_airfields.repeater_label'))
                            ->schema([
                                TextInput::make('airfield')
                                    ->label(self::translateFormPath('conditions.arrival_airfields.label'))
                                    ->helperText(self::translateFormPath('conditions.arrival_airfields.helper'))
                                    ->required()
                                    ->rule(new AirfieldIcao())
                            ])
                            ->required(),
                    ]),
                Block::make(ConditionType::ArrivalAirfieldPattern->value)
                    ->label(self::translateFormPath('conditions.arrival_airfield_pattern.menu_item'))
                    ->schema([
                        TextInput::make('pattern')
                            ->label(self::translateFormPath('conditions.arrival_airfield_pattern.label'))
                            ->helperText(self::translateFormPath('conditions.arrival_airfield_pattern.helper'))
                            ->required()
                            ->rule(new PartialAirfieldIcao()),
                    ]),
                Block::make(ConditionType::ExitPoint->value)
                    ->label(self::translateFormPath('conditions.exit_point.menu_item'))
                    ->reactive()
                    ->schema([
                        Select::make('exit_point')
                            ->label(self::translateFormPath('conditions.exit_point.label'))
                            ->helperText(self::translateFormPath('conditions.exit_point.helper'))
                            ->required()
                            ->searchable()
                            ->options(
                                function (Select $component)
                                {
                                    $data = $component->getLivewire()->data;
                                    return self::getFirExitPointOptions(
                                        isset($data, $data['conditions']) ? $data['conditions'] : null
                                    );
                                }
                            )
                    ]),
                Block::make(ConditionType::MaximumCruisingLevel->value)
                    ->label(self::translateFormPath('conditions.maximum_cruising_level.menu_item'))
                    ->schema([
                        TextInput::make('maximum_cruising_level')
                            ->label(self::translateFormPath('conditions.maximum_cruising_level.label'))
                            ->helperText(self::translateFormPath('conditions.maximum_cruising_level.helper'))
                            ->required()
                            ->integer()
                            ->minValue(1000)
                            ->maxValue(60000)
                    ]),
                Block::make(ConditionType::CruisingLevelAbove->value)
                    ->label(self::translateFormPath('conditions.cruising_level_above.menu_item'))
                    ->schema([
                        TextInput::make('cruising_level_above')
                            ->label(self::translateFormPath('conditions.cruising_level_above.label'))
                            ->helperText(self::translateFormPath('conditions.cruising_level_above.helper'))
                            ->required()
                            ->integer()
                            ->minValue(1000)
                            ->maxValue(60000),
                    ]),
                Block::make(ConditionType::RoutingVia->value)
                    ->label(self::translateFormPath('conditions.routing_via.menu_item'))
                    ->schema([
                        TextInput::make('routing_via')
                            ->label(self::translateFormPath('conditions.routing_via.label'))
                            ->helperText(self::translateFormPath('conditions.routing_via.helper'))
                            ->required()
                            ->maxLength(5)
                    ]),
                Block::make(ConditionType::ControllerPositionStartsWith->value)
                    ->label(self::translateFormPath('conditions.controller_position_starts_with.menu_item'))
                    ->schema([
                        TextInput::make('controller_position_starts_with')
                            ->label(self::translateFormPath('conditions.controller_position_starts_with.label'))
                            ->helperText(self::translateFormPath('conditions.controller_position_starts_with.helper'))
                            ->required()
                            ->rule(new ControllerPositionPartialCallsign()),
                    ]),
                Block::make(ConditionType::Not->value)
                    ->label(self::translateFormPath('conditions.not.menu_item'))
                    ->schema(fn() => [self::conditions()]),
                Block::make(ConditionType::AnyOf->value)
                    ->label(self::translateFormPath('conditions.any_of.menu_item'))
                    ->schema(fn() => [self::conditions()]),
                Block::make(ConditionType::AllOf->value)
                    ->label(self::translateFormPath('conditions.all_of.menu_item'))
                    ->schema(fn() => [self::conditions()])
            ]);
    }

    protected static function translationPathRoot(): string
    {
        return 'intention';
    }

    /**
     * We only want to allow a single FIR exit point per intention code. This is necessary to make things simpler in 
     * the plugin. TLDR:
     * 
     * The plugin sends messages to its integrations, e.g. vStrips, these messages contain the FIR Exit Point that
     * relates to a resolved intention code. Due to the way this solution is architected, it's a bit of a pain to have
     * multiple FIR exit points per intention code.
     * 
     * This method (and its sub-methods) therefore only allows users to select a single FIR exit per intention code.
     */
    protected static function getFirExitPointOptions(?array $conditions): Collection
    {
        $selectOptions = SelectOptions::firExitPoints();
        if (is_null($conditions) || self::countExitPointConditions($conditions) <= 1) {
            return $selectOptions;
        }

        $foundExitPoint = self::getSelectedFirExitPoint($conditions);
        return $foundExitPoint
            ? $selectOptions->only($foundExitPoint)
            : $selectOptions;
    }

    protected static function getSelectedFirExitPoint(array $conditions): ?int
    {
        foreach ($conditions as $condition) {
            $parsedConditionType = ConditionType::from($condition['type']);

            if ($parsedConditionType === ConditionType::ExitPoint) {
                return $condition['data']['exit_point'];
            }

            if (in_array($parsedConditionType, [ConditionType::Not, ConditionType::AllOf, ConditionType::AnyOf])) {
                $foundExitPoint = self::getSelectedFirExitPoint($condition['data']['conditions']);
                if ($foundExitPoint) {
                    return $foundExitPoint;
                }
            }
        }

        return null;
    }

    protected static function countExitPointConditions(array $conditions): int
    {
        $foundConditions = 0;

        foreach ($conditions as $condition) {
            $parsedConditionType = ConditionType::from($condition['type']);

            if ($parsedConditionType === ConditionType::ExitPoint) {
                $foundConditions++;
            }

            if (in_array($parsedConditionType, [ConditionType::Not, ConditionType::AllOf, ConditionType::AnyOf])) {
                $foundConditions += self::countExitPointConditions($condition['data']['conditions']);
            }
        }

        return $foundConditions;
    }
}
