<?php

namespace App\Allocator\Stand\Finder\Filter;

use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Database\Eloquent\Builder;

interface StandFinderFilterInterface
{
    public function applyFilterToQuery(NetworkAircraft $aircraft, Builder $query): Builder;
}
