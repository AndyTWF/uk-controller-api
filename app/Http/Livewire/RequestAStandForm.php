<?php

namespace App\Http\Livewire;

use App\Filament\Helpers\DisplaysStandStatus;
use App\Filament\Helpers\SelectOptions;
use App\Models\Aircraft\Aircraft;
use App\Models\Airfield\Airfield;
use App\Models\Stand\Stand;
use App\Models\Stand\StandRequest;
use App\Models\Stand\StandRequestHistory;
use App\Models\Vatsim\NetworkAircraft;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class RequestAStandForm extends Component implements HasForms
{
    use DisplaysStandStatus;
    use InteractsWithForms;
    use ShowsRequestTimeInformation;

    public ?NetworkAircraft $userAircraft;
    public ?Aircraft $userAircraftType;
    public array $stands = [];
    public ?int $requestedStand = null;
    public ?string $requestedTime = null;

    protected $messages = [
        'requestedStand' => 'You must select a valid stand.',
        'requestedTime' => 'You must select a valid time.',
    ];

    public function mount(): void
    {
        $this->userAircraft = $this->getUserAircraft();
        $this->userAircraftType = $this->userAircraft
            ? Aircraft::where('code', $this->userAircraft->planned_aircraft_short)->first()
            : null;
        $userDestinationAirfield = $this->userAircraft ? Airfield::where(
            'code',
            $this->userAircraft->planned_destairport
        )
            ->first()
            : null;

        $this->stands = $userDestinationAirfield && $this->userAircraftType
            ? Stand::where('airfield_id', $userDestinationAirfield->id)
                ->notClosed()
                ->sizeAppropriate($this->userAircraftType)
                ->get()
                ->mapWithKeys(fn (Stand $stand): array => [$stand->id => $stand->airfieldIdentifier])
                ->toArray()
            : [];
    }

    public function getFormSchema(): array
    {
        return [
            Placeholder::make('')
                ->content($this->getFirstPlaceholderText()),
            Placeholder::make('')
                ->content($this->getSecondPlaceholderText()),
            Placeholder::make('Stand request for')
                ->maxWidth('sm')
                ->content(sprintf('%s at %s', $this->userAircraft->callsign, $this->userAircraft->planned_destairport))
                ->disabled(),
            Select::make('requestedStand')
                ->label('Stand')
                ->helperText('Only stands that can accommodate your aircraft type are shown.')
                ->maxWidth('sm')
                ->columnSpan(0.25)
                ->options($this->stands)
                ->reactive()
                ->searchable()
                ->required(),
            View::make('livewire.stand-status')
                ->hidden(fn () => $this->requestedStand === null)
                ->viewData(
                    [
                        'standStatus' => $this->requestedStand ? $this->getStandStatus(
                            Stand::findOrFail($this->requestedStand),
                            $this->userAircraft
                        ) : null,
                    ]
                ),
            DateTimePicker::make('requestedTime')
                ->label('Arrival time (Zulu)')
                ->maxWidth('sm')
                ->withoutSeconds()
                ->minDate(Carbon::now())
                ->maxDate(Carbon::now()->addHours(12))
                ->placeholder(Carbon::now()->addMinutes(5)->toDateTimeString('minute'))
                ->helperText('Can be up to 12 hours in advance.')
                ->reactive()
                ->required(),
            View::make('livewire.stand-booking-applicability')
                ->hidden(fn () => $this->requestedTime === null)
                ->viewData($this->getRequestTimeViewData(Carbon::parse($this->requestedTime))),
        ];
    }

    public function submit(): void
    {
        $this->form->validate();

        DB::transaction(function () {
            $userAircraft = $this->getUserAircraft();
            $requestData = [
                'stand_id' => $this->requestedStand,
                'requested_time' => $this->requestedTime,
                'user_id' => $userAircraft->cid,
                'callsign' => $userAircraft->callsign,
            ];

            $request = StandRequest::create($requestData);
            $historyItem = new StandRequestHistory($requestData);
            $historyItem->id = $request->id;
            $historyItem->save();
        });

        $this->emit('requestAStandFormSubmitted');
    }

    public function updatedRequestedStand(): void
    {
        if (($stand = $this->getStandRequested())) {
            $this->emitTo('stand-status', 'updateStandStatus', ['stand' => $stand]);
        }
    }

    private function getStandRequested(): ?Stand
    {
        return Stand::find($this->requestedStand);
    }

    private function getFirstPlaceholderText(): string
    {
        return 'Please note, requesting a stand does not guarantee that it will be assigned to you. Stands are assigned
                on a first-come first-served basis. Other pilots may still connect up on the stand,
                and the stand allocator will allocate it to another aircraft if it is the only realistic stand for their
                flight.';
    }

    private function getSecondPlaceholderText(): string
    {
        return 'Disconnecting from the VATSIM network for an extended period of time will cause your stand request to be
                automatically relinquished.';
    }
}
