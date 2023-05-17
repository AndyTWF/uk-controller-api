<?php

namespace App\Allocator\Stand\Prioritiser;

use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Support\Collection;

interface PotentialStandPrioritiserInterface
{
    /**
     * Generate potential stands for a given aircraft in a base prioritised order. The return should be a collection,
     * containing a number of sub-collections, each containing a number of stands.
     *
     * The aim of these classes is to narrow down the number of stands that need to be considered for a given aircraft,
     * whilst keeping the cache hit rate high.
     *
     * Rules that are less likely to have a cache hit are dealt in the Selector process (as they are difficult to cache against).
     */
    public function prioritisePotentialStands(NetworkAircraft $aircraft): Collection;
}
