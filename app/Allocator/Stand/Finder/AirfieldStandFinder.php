<?php

namespace App\Allocator\Stand\Finder;

use App\Models\Stand\Stand;
use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AirfieldStandFinder implements StandFinderInterface
{
    public function findStands(NetworkAircraft $aircraft): Collection
    {
        return Cache::rememberForever(
            sprintf('AIRFIELD_%s_OPEN_STANDS', $aircraft->plannedDestinationAirfield()),
            fn() => Stand::whereHas('airfield', function (Builder $airfield) use ($aircraft) {
                $airfield->where('code', $aircraft->plannedDestinationAirfield());
            })
                ->notClosed()
                ->get()
        );
    }
}
