<?php

namespace App\Allocator\Stand\Finder;

use App\Allocator\Stand\Finder\Filter\StandFinderFilterInterface;
use App\Models\Stand\Stand;
use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AirfieldStandFinder implements StandFinderInterface
{
    public function findStands(NetworkAircraft $aircraft, Collection $filters): Collection
    {
        return tap(
            Stand::whereHas('airfield', function (Builder $airfield) use ($aircraft) {
                $airfield->where('code', $aircraft->plannedDestinationAirfield());
            })
                ->notClosed(),
            function (Builder $query) use ($filters, $aircraft) {
                $filters->each(
                    fn(StandFinderFilterInterface $filter) => $filter->applyFilterToQuery($aircraft, $query)
                );
            }
        )->get();
    }
}
