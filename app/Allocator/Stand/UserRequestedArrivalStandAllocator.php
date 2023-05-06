<?php

namespace App\Allocator\Stand;

use App\Models\Stand\StandRequest;
use App\Models\Vatsim\NetworkAircraft;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class UserRequestedArrivalStandAllocator extends AbstractArrivalStandAllocator
{
    protected function getOrderedStandsQuery(Builder $stands, NetworkAircraft $aircraft): ?Builder
    {
        $requestedStands = StandRequest::where('user_id', $aircraft->cid)
            ->whereHas('stand.airfield', function (Builder $airfield) use ($aircraft) {
                $airfield->where('code', $aircraft->planned_destairport);
            })
            ->current()
            ->get();

        if ($requestedStands->isEmpty()) {
            return null;
        }

        return $stands->whereIn('stands.id', $requestedStands->pluck('stand_id'));
    }

    protected function prefersNonRequestedStands(): bool
    {
        return false;
    }
}