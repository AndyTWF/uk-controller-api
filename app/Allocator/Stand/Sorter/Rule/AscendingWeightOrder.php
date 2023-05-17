<?php

namespace App\Allocator\Stand\Sorter\Rule;

use App\Models\Stand\Stand;
use App\Models\Vatsim\NetworkAircraft;

class AscendingWeightOrder implements StandSorterRuleInterface
{
    public function sort(NetworkAircraft $aircraft, Stand $stand): mixed
    {
        return $stand->wakeCategory->relative_weighting;
    }
}
