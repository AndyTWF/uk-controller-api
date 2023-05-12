<?php

namespace App\Allocator\Stand\Sorter;

use App\Allocator\Stand\Rule\StandRuleInterface;
use App\Allocator\Stand\Sorter\Rule\StandSorterRuleInterface;
use App\Models\Stand\Stand;
use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Support\Collection;

class StandSorter implements StandSorterInterface
{
    public function sort(StandRuleInterface $rule, NetworkAircraft $aircraft, Collection $stands): Collection
    {
        return $rule->sorters()->reduce(
            fn(Collection $sortedStands, StandSorterRuleInterface $sorter) => $sortedStands
                ->map(
                    fn(Collection $standCollection) => $standCollection
                        ->groupBy(fn(Stand $stand) => $sorter->sort($aircraft, $stand))
                        ->sortBy(fn(Collection $groupedStands) => $sorter->sort($aircraft, $groupedStands->first()))
                )->flatten(1),
            collect([$stands])
        );
    }
}
