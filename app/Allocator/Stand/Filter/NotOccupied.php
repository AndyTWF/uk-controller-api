<?php

namespace App\Allocator\Stand\Filter;

use App\Models\Stand\Stand;
use App\Models\Vatsim\NetworkAircraft;

class NotOccupied implements StandFilterInterface
{
    public function filter(NetworkAircraft $aircraft, Stand $stand): bool
    {
        return $stand->occupier()->exists() === false && $stand->assignment()->exists() === false;
    }
}
