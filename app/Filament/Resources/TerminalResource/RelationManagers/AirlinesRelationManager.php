<?php

namespace App\Filament\Resources\TerminalResource\RelationManagers;

use App\Filament\Resources\Pages\LimitsTableRecordListingOptions;
use App\Filament\Resources\TranslatesStrings;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Facades\DB;

class AirlinesRelationManager extends RelationManager
{
    use LimitsTableRecordListingOptions;
    use TranslatesStrings;

    private const DEFAULT_COLUMN_VALUE = '--';
    protected bool $allowsDuplicates = true;
    protected static string $relationship = 'airlines';
    protected static ?string $inverseRelationship = 'terminals';
    protected static ?string $recordTitleAttribute = 'icao_code';

    protected function getTableDescription(): ?string
    {
        return self::translateTablePath('description');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('icao_code')
                    ->label(self::translateTablePath('columns.icao'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('destination')
                    ->label(self::translateTablePath('columns.destination'))
                    ->default(self::DEFAULT_COLUMN_VALUE)
                    ->sortable(),
                Tables\Columns\TextColumn::make('callsign_slug')
                    ->default(self::DEFAULT_COLUMN_VALUE)
                    ->label(self::translateTablePath('columns.callsign')),
                Tables\Columns\TextColumn::make('priority')
                    ->default(self::DEFAULT_COLUMN_VALUE)
                    ->label(self::translateTablePath('columns.priority'))
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make('pair-airline')
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label(self::translateFormPath('icao.label'))
                            ->required(),
                        TextInput::make('destination')
                            ->label(self::translateFormPath('destination.label'))
                            ->helperText(self::translateFormPath('destination.helper'))
                            ->maxLength(4),
                        TextInput::make('callsign_slug')
                            ->label(self::translateFormPath('callsign.label'))
                            ->helperText(self::translateFormPath('callsign.helper'))
                            ->maxLength(4),
                        TextInput::make('priority')
                            ->label(self::translateFormPath('priority.label'))
                            ->helperText(self::translateFormPath('priority.helper'))
                            ->default(100)
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(9999)
                            ->required(),
                    ])
            ])
            ->actions([
                Tables\Actions\DetachAction::make('unpair-airline')
                    ->label(self::translateFormPath('remove.label'))
                    ->using(function (Tables\Actions\DetachAction $action) {
                        DB::table('airline_terminal')
                            ->where('id', $action->getRecord()->pivot_id)
                            ->delete();
                    })
            ]);
    }

    protected static function translationPathRoot(): string
    {
        return 'terminals.airlines';
    }
}