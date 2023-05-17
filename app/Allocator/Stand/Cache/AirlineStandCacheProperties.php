<?php

namespace App\Allocator\Stand\Cache;

use App\Models\Aircraft\Aircraft;
use App\Models\Vatsim\NetworkAircraft;
use App\Services\AirlineService;
use Carbon\CarbonImmutable;

class AirlineStandCacheProperties implements StandPrioritisationCachePropertiesInterface
{
    private readonly AirlineService $airlineService;

    public function __construct(AirlineService $airlineService)
    {
        $this->airlineService = $airlineService;
    }

    public function cacheKey(NetworkAircraft $aircraft): string|null
    {
        $airline = $this->airlineService->getAirlineForAircraft($aircraft);
        $aircraftType = Aircraft::where('code', $aircraft->planned_aircraft_short)->firstOrFail();

        return $airline
            ? sprintf(
                'AIRLINE_%d_WAKE_CATEGORY_%d_STAND_PRIORITISATION',
                $airline->id,
                $aircraftType->id,
            )
            : null;
    }

    public function cacheDuration(): CarbonImmutable
    {
        return CarbonImmutable::now()->addHours(12);
    }
}
