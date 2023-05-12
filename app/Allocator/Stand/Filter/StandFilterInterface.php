<?php

namespace App\Allocator\Stand\Filter;

use App\Models\Stand\Stand;
use App\Models\Vatsim\NetworkAircraft;

interface StandFilterInterface
{
    public function filter(NetworkAircraft $aircraft, Stand $stand): bool;
}
