<?php

namespace App\Allocator\Stand;

use App\Models\Stand\Stand;
use App\Models\Stand\StandReservation;
use App\Models\Vatsim\NetworkAircraft;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class CallsignDestinationReservedArrivalStandAllocator extends AbstractArrivalStandAllocator
{
    protected function getOrderedStandsQuery(Builder $stands, NetworkAircraft $aircraft): ?Builder
    {
        $reservation = StandReservation::with('stand')
            ->whereHas('stand', function (Builder $standQuery) {
                $standQuery->unoccupied()->unassigned();
            })
            ->where('callsign', $aircraft->callsign)
            ->where('origin', $aircraft->planned_depairport)
            ->where('destination', $aircraft->planned_destairport)
            ->reservedAtBetween(Carbon::now()->subMinutes(30), Carbon::now()->addMinutes(30))
            ->first();

        return $reservation
            ? Stand::where('stands.id', $reservation->stand_id)->select('stands.*')
            : null;
    }
}
