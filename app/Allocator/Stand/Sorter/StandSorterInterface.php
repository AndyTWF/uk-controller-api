<?php

namespace App\Allocator\Stand\Sorter;

use App\Allocator\Stand\Rule\StandRuleInterface;
use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Support\Collection;

interface StandSorterInterface
{
    public function sort(
        StandRuleInterface $rule,
        NetworkAircraft $aircraft,
        Collection $stands
    ): Collection;
}
