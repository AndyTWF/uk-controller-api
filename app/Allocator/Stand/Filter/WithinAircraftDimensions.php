<?php

namespace App\Allocator\Stand\Filter;

use App\Models\Aircraft\Aircraft;
use App\Models\Stand\Stand;
use App\Models\Vatsim\NetworkAircraft;

class WithinAircraftDimensions implements StandFilterInterface
{
    public function filter(NetworkAircraft $aircraft, Stand $stand): bool
    {
        $aircraftType = Aircraft::where('code', $aircraft->planned_aircraft_short)->firstOrFail();
        $maxAircraftForStand = $stand->maxAircraft;
        return !$maxAircraftForStand || ($aircraftType->wingspan <= $maxAircraftForStand->wingspan && $aircraftType->length <= $maxAircraftForStand->length);
    }
}
