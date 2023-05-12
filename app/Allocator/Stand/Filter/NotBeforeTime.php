<?php

namespace App\Allocator\Stand\Filter;

use App\Allocator\Stand\Airline\Filter\AirlineStandPreferenceFilterInterface;
use App\Models\Stand\Stand;
use App\Models\Vatsim\NetworkAircraft;
use Carbon\Carbon;

class NotBeforeTime implements StandFilterInterface
{
    private readonly AirlineStandPreferenceFilterInterface $airlinePreferenceFilter;

    public function __construct(AirlineStandPreferenceFilterInterface $airlinePreferenceFilter)
    {
        $this->airlinePreferenceFilter = $airlinePreferenceFilter;
    }

    public function filter(NetworkAircraft $aircraft, Stand $stand): bool
    {
        $airlinePreference = $this->airlinePreferenceFilter->getAirlineStandPreference($aircraft, $stand);
        if (!$airlinePreference) {
            return false;
        }

        return Carbon::now() > Carbon::now()->setTimeFrom($airlinePreference->not_before)
            || Carbon::now()->lessThan(Carbon::now()->setTime(2, 0));
    }
}
