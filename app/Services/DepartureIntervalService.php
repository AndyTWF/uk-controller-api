<?php

namespace App\Services;

use App\Models\Departure\DepartureInterval;
use App\Models\Departure\DepartureIntervalType;
use App\Models\Sid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DepartureIntervalService
{
    /**
     * Create an MDI
     */
    public function createMinimumDepartureInterval(
        int $interval,
        string $airfield,
        array $sids,
        Carbon $expiresAt
    ) : DepartureInterval {
        return $this->createDepartureInterval($interval, 'mdi', $airfield, $sids, $expiresAt);
    }

    /**
     * Create an MDI
     */
    public function createAverageDepartureInterval(
        int $interval,
        string $airfield,
        array $sids,
        Carbon $expiresAt
    ) : DepartureInterval {
        return $this->createDepartureInterval($interval, 'adi', $airfield, $sids, $expiresAt);
    }

    public function updateDepartureInterval(
        int $id,
        int $interval,
        string $airfield,
        array $sids,
        Carbon $expiresAt
    ) {
        $model = DepartureInterval::findOrFail($id);
        $model->update(['expires_at' => $expiresAt, 'interval' => $interval]);
        $this->addSidToDepartureInterval($model, $airfield, $sids);
    }

    public function expireDepartureInterval(int $id): void
    {
        DepartureInterval::findOrFail($id)->expire();
    }

    /**
     * Create a specified type of departure interval
     */
    private function createDepartureInterval(
        int $interval,
        string $type,
        string $airfield,
        array $sids,
        Carbon $expiresAt
    ): DepartureInterval {
        $interval = DepartureInterval::create(
            [
                'interval' => $interval,
                'type_id' => DepartureIntervalType::where('key', $type)->first()->id,
                'expires_at' => $expiresAt
            ]
        );
        $this->addSidToDepartureInterval($interval, $airfield, $sids);

        return $interval;
    }

    /**
     * Associate sids with a newly created departure interval
     */
    private function addSidToDepartureInterval(DepartureInterval $interval, string $airfield, array $identifiers): void
    {
        $sids = Sid::whereHas('airfield', function (Builder $airfieldQuery) use ($airfield) {
            $airfieldQuery->where('code', $airfield);
        })
            ->whereIn('identifier', $identifiers)
            ->pluck('id');

        $interval->sids()->sync($sids);
    }
}
