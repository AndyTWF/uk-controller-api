<?php

namespace App\Allocator\Stand\Sorter\Rule;

use App\Models\Stand\Stand;
use App\Models\Vatsim\NetworkAircraft;

interface StandSorterRuleInterface
{
    public function sort(NetworkAircraft $aircraft, Stand $stand): mixed;
}
