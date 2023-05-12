<?php

namespace App\Allocator\Stand\Airline\Filter;

use App\Allocator\Stand\Airline\AirlineStandPreferencesInterface;
use App\Allocator\Stand\Airline\Filter\Provider\AirlineStandPreferenceFilterProviderInterface;
use App\Models\Stand\AirlineStand;
use App\Models\Stand\Stand;
use App\Models\Vatsim\NetworkAircraft;

class AirlineStandPreferenceFilter implements AirlineStandPreferenceFilterInterface
{
    private readonly AirlineStandPreferencesInterface $airlineStandPreferences;
    private readonly AirlineStandPreferenceFilterProviderInterface $filterProvider;

    public function __construct(
        AirlineStandPreferencesInterface $airlineStandPreferences,
        AirlineStandPreferenceFilterProviderInterface $filterProvider
    ) {
        $this->airlineStandPreferences = $airlineStandPreferences;
        $this->filterProvider = $filterProvider;
    }

    /**
     * Given all the airline stand preferences, find the first that matches the condition.
     */
    public function getAirlineStandPreference(NetworkAircraft $aircraft, Stand $stand): ?AirlineStand
    {
        return $this->airlineStandPreferences->getAirlineStandPreferences($aircraft)
            ->get($stand->id, collect())
            ->first(fn (AirlineStand $preference) => $this->filterProvider->preferenceApplicable($preference));
    }
}
