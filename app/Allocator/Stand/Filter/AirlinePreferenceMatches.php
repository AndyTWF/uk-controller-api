<?php

namespace App\Allocator\Stand\Filter;

use App\Allocator\Stand\Airline\Filter\AirlineStandPreferenceFilterInterface;
use App\Models\Stand\Stand;
use App\Models\Vatsim\NetworkAircraft;

class AirlinePreferenceMatches implements StandFilterInterface
{
    private readonly AirlineStandPreferenceFilterInterface $airlinePreferenceFilter;

    public function __construct(AirlineStandPreferenceFilterInterface $airlinePreferenceFilter)
    {
        $this->airlinePreferenceFilter = $airlinePreferenceFilter;
    }

    public function filter(NetworkAircraft $aircraft, Stand $stand): bool
    {
        return $this->airlinePreferenceFilter->getAirlineStandPreference($aircraft, $stand) !== null;
    }
}
