<?php

namespace App\Http\Livewire;

use App\Allocator\Stand\Finder\AirfieldStandFinder;
use App\Allocator\Stand\Generator\StandOptionsGeneratorInterface;
use App\Allocator\Stand\Prioritiser\PotentialStandPrioritiser;
use App\Allocator\Stand\Rule\AirlineStandRule;
use App\Allocator\Stand\Sorter\StandSorter;
use App\Filament\Helpers\DisplaysStandStatus;
use App\Filament\Helpers\SelectOptions;
use App\Models\Vatsim\NetworkAircraft;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Collection;
use Livewire\Component;

class StandAllocationRuleTesterForm extends Component implements HasForms
{
    use DisplaysStandStatus;
    use InteractsWithForms;

    public string $callsign;
    public string $aircraftType;
    public string $originAirport;
    public string $destinationAirport;
    public Collection $prioritisedStands;

    public function getFormSchema(): array
    {
        return [
            Grid::make(3)
                ->schema([
                    TextInput::make('callsign')
                        ->label('Callsign')
                        ->maxWidth('sm')
                        ->required(),
                    Select::make('aircraftType')
                        ->options(SelectOptions::aircraftTypes())
                        ->searchable()
                        ->label('Aircraft Type')
                        ->maxWidth('sm')
                        ->required(),
                    TextInput::make('originAirport')
                        ->label('Origin Airport')
                        ->maxWidth('sm')
                        ->required(),
                    Select::make('destinationAirport')
                        ->options(SelectOptions::airfields())
                        ->searchable()
                        ->label('Destination Airport')
                        ->maxWidth('sm')
                        ->required(),
                    DateTimePicker::make('arrivalTime')
                        ->label('Arrival Time')
                        ->maxWidth('sm'),
                ]),
        ];
    }

    public function submit(): void
    {
        $prioritiser = new PotentialStandPrioritiser(
            app()->make(AirfieldStandFinder::class),
            app()->make(StandSorter::class),
            new AirlineStandRule()
        );
        $generator = app()->make(StandOptionsGeneratorInterface::class);
        $this->prioritisedStands = $generator
            ->generateStandOptions(new NetworkAircraft([
                'callsign' => $this->callsign,
                'planned_aircraft_short' => SelectOptions::aircraftTypes()[$this->aircraftType],
                'planned_aircraft' => SelectOptions::aircraftTypes()[$this->aircraftType],
                'planned_depairport' => $this->originAirport,
                'planned_destairport' => SelectOptions::airfields()[$this->destinationAirport],
            ]), new AirlineStandRule(), $prioritiser)
            ->filter(fn(Collection $collection) => $collection->isNotEmpty())
            ->values();

        $this->emit('requestAStandFormSubmitted');
    }
}
