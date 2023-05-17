<?php

namespace App\Allocator\Stand\Finder\Filter;

use App\Models\Aircraft\Aircraft;
use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Database\Eloquent\Builder;

class WakeAppropriate implements StandFinderFilterInterface
{
    public function applyFilterToQuery(NetworkAircraft $aircraft, Builder $query): Builder
    {
        return $query->appropriateWakeCategory(
            Aircraft::where('code', $aircraft->planned_aircraft_short)->firstOrFail()
        );
    }
}
