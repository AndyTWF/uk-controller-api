<?php

namespace App\Allocator\Stand\Finder;

use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Support\Collection;

/**
 * Finds (and possibly caches) a number of stands.
 */
interface StandFinderInterface
{
    public function findStands(NetworkAircraft $aircraft): Collection;
}
