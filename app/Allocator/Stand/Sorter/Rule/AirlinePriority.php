<?php

namespace App\Allocator\Stand\Sorter\Rule;

use App\Allocator\Stand\Airline\Filter\AirlineStandPreferenceFilterInterface;
use App\Models\Stand\Stand;
use App\Models\Vatsim\NetworkAircraft;

class AirlinePriority implements StandSorterRuleInterface
{
    private readonly AirlineStandPreferenceFilterInterface $airlinePreferenceFilter;

    public function __construct(AirlineStandPreferenceFilterInterface $airlinePreferenceFilter)
    {
        $this->airlinePreferenceFilter = $airlinePreferenceFilter;
    }

    public function sort(NetworkAircraft $aircraft, Stand $stand): mixed
    {
        $preference = $this->airlinePreferenceFilter->getAirlineStandPreference($aircraft, $stand);
        return $preference?->priority ?? 9999;
    }
}
