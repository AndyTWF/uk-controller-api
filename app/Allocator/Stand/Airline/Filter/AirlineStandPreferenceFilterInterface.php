<?php

namespace App\Allocator\Stand\Airline\Filter;

use App\Models\Stand\AirlineStand;
use App\Models\Stand\Stand;
use App\Models\Vatsim\NetworkAircraft;

interface AirlineStandPreferenceFilterInterface
{
    /**
     * Returns the relevant airline stand preference.
     */
    public function getAirlineStandPreference(NetworkAircraft $aircraft, Stand $stand): ?AirlineStand;
}
