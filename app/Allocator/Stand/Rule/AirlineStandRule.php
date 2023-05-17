<?php

namespace App\Allocator\Stand\Rule;

use App\Allocator\Stand\Airline\Filter\Provider\AirlineWithNoConditions;
use App\Allocator\Stand\Cache\AirlineStandCacheProperties;
use App\Allocator\Stand\Cache\StandPrioritisationCachePropertiesInterface;
use App\Allocator\Stand\Filter\NotBeforeTime;
use App\Allocator\Stand\Filter\WithinAircraftDimensions;
use App\Allocator\Stand\Finder\Filter\WakeAppropriate;
use App\Allocator\Stand\Finder\Filter\NoAirlinePreferences;
use App\Allocator\Stand\Sorter\Rule\AirlinePriority;
use App\Allocator\Stand\Sorter\Rule\AscendingWeightOrder;
use Illuminate\Support\Collection;

class AirlineStandRule implements StandRuleInterface, CacheableStandRuleInterface
{
    use UsesAirlinePreferences;

    public function queryFilters(): Collection
    {
        return collect([
            app()->make(WakeAppropriate::class),
            app()->make(NoAirlinePreferences::class),
        ]);
    }

    public function filters(): Collection
    {
        return collect(
            [
                app()->make(NotBeforeTime::class, $this->getAirlineStandPreferencesFilter()),
                app()->make(WithinAircraftDimensions::class),
            ]
        );
    }

    public function sorters(): Collection
    {
        return collect([
            app()->make(
                AirlinePriority::class,
                $this->getAirlineStandPreferencesFilter()
            ),
            app()->make(AscendingWeightOrder::class)
        ]);
    }

    public function cacheProperties(): StandPrioritisationCachePropertiesInterface
    {
        return app()->make(AirlineStandCacheProperties::class);
    }

    private function getAirlineStandPreferenceFilterType(): string
    {
        return AirlineWithNoConditions::class;
    }
}
