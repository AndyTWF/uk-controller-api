<?php

namespace App\Allocator\Stand\Finder\Filter;

use App\Models\Vatsim\NetworkAircraft;
use App\Services\AirlineService;
use Illuminate\Database\Eloquent\Builder;

class NoAirlinePreferences implements StandFinderFilterInterface
{
    private readonly AirlineService $airlineService;

    public function __construct(AirlineService $airlineService)
    {
        $this->airlineService = $airlineService;
    }

    public function applyFilterToQuery(NetworkAircraft $aircraft, Builder $query): Builder
    {
        $aircraftAirline = $this->airlineService->getAirlineForAircraft($aircraft);

        return $query->whereHas('airlines', function (Builder $airline) use ($aircraftAirline) {
            $airline->where('airline_stand.airline_id', '=', $aircraftAirline->id)
                ->whereNull('airline_stand.callsign')
                ->whereNull('airline_stand.callsign_slug')
                ->whereNull('airline_stand.aircraft_id')
                ->whereNull('airline_stand.destination');
        });
    }
}
