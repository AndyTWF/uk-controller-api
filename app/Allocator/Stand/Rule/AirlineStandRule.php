<?php

namespace App\Allocator\Stand\Rule;

use App\Allocator\Stand\Airline\Filter\Provider\AirlineWithNoConditions;
use App\Allocator\Stand\Cache\AirlineStandCacheProperties;
use App\Allocator\Stand\Cache\StandPrioritisationCachePropertiesInterface;
use App\Allocator\Stand\Filter\NotBeforeTime;
use App\Allocator\Stand\Sorter\Rule\AirlinePrioritySorterRule;
use Illuminate\Support\Collection;

class AirlineStandRule implements StandRuleInterface, CacheableStandRuleInterface
{
    use UsesAirlinePreferences;

    public function filters(): Collection
    {
        return collect([
            $this->getAirlineStandPreferenceMatchesFilter(),
        ]);
    }

    public function sorters(): Collection
    {
        return collect([
            app()->make(
                AirlinePrioritySorterRule::class,
                $this->getAirlineStandPreferencesFilter()
            ),
        ]);
    }

    public function cacheProperties(): StandPrioritisationCachePropertiesInterface
    {
        return app()->make(AirlineStandCacheProperties::class);
    }

    public function preSelectionFilters(): Collection
    {
        return collect([app()->make(NotBeforeTime::class)]);
    }

    private function getAirlineStandPreferenceFilterType(): string
    {
        return AirlineWithNoConditions::class;
    }
}
