<?php

namespace App\Allocator\Stand\Airline\Filter\Provider;

use App\Models\Stand\AirlineStand;

class AirlineWithNoConditions implements AirlineStandPreferenceFilterProviderInterface
{
    public function preferenceApplicable(AirlineStand $preference): bool
    {
        return !isset($preference->destination) &&
            !isset($preference->aircraft_id) &&
            !isset($preference->callsign) &&
            !isset($preference->callsign_slug);
    }
}
