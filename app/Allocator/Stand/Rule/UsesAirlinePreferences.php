<?php

namespace App\Allocator\Stand\Rule;

use App\Allocator\Stand\Airline\Filter\AirlineStandPreferenceFilter;
use App\Allocator\Stand\Filter\AirlinePreferenceMatches;

trait UsesAirlinePreferences
{
    private function getAirlineStandPreferenceMatchesFilter(): AirlinePreferenceMatches
    {
        return app()->make(
            AirlinePreferenceMatches::class,
            $this->getAirlineStandPreferencesFilter()
        );
    }

    private function getAirlineStandPreferencesFilter(): array
    {
        return [
            'airlinePreferenceFilter' => app()->make(
                AirlineStandPreferenceFilter::class,
                ['filterProvider' => app()->make($this->getAirlineStandPreferenceFilterType())]
            ),
        ];
    }

    private abstract function getAirlineStandPreferenceFilterType(): string;
}
