<?php

namespace App\Allocator\Stand\Prioritiser;

use App\Allocator\Stand\Finder\StandFinderInterface;
use App\Allocator\Stand\Rule\CacheableStandRuleInterface;
use App\Allocator\Stand\Rule\StandRuleInterface;
use App\Allocator\Stand\Sorter\StandSorterInterface;
use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PotentialStandPrioritiser implements PotentialStandPrioritiserInterface
{
    private readonly StandFinderInterface $finder;
    private readonly StandSorterInterface $sorter;
    private readonly StandRuleInterface $rule;

    public function __construct(
        StandFinderInterface $finder,
        StandSorterInterface $sorter,
        StandRuleInterface $rule
    ) {
        $this->finder = $finder;
        $this->sorter = $sorter;
        $this->rule = $rule;
    }

    public function prioritisePotentialStands(NetworkAircraft $aircraft): Collection
    {
        return $this->rule instanceof CacheableStandRuleInterface
            ? $this->getCachedStands($aircraft, $this->rule)
            : $this->generateStands($aircraft);
    }

    private function getCachedStands(NetworkAircraft $aircraft, CacheableStandRuleInterface $rule): Collection
    {
        return Cache::remember(
            $rule->cacheProperties()->cacheKey($aircraft),
            $rule->cacheProperties()->cacheDuration(),
            fn() => $this->generateStands($aircraft)
        );
    }

    /**
     * Find any stands, reject any that don't meet the filter conditions and then sort them.
     */
    private function generateStands(NetworkAircraft $aircraft): Collection
    {
        return $this->sorter->sort(
            $this->rule,
            $aircraft,
            $this->finder->findStands($aircraft, $this->rule->queryFilters())
        );
    }
}
