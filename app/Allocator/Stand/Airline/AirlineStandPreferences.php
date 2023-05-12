<?php

namespace App\Allocator\Stand\Airline;

use App\Models\Airline\Airline;
use App\Models\Stand\AirlineStand;
use App\Models\Vatsim\NetworkAircraft;
use App\Services\AirlineService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AirlineStandPreferences implements AirlineStandPreferencesInterface
{
    private readonly AirlineService $airlineService;

    public function __construct(AirlineService $airlineService)
    {
        $this->airlineService = $airlineService;
    }

    public function getAirlineStandPreferences(NetworkAircraft $aircraft): Collection
    {
        $airline = $this->airlineService->getAirlineForAircraft($aircraft);
        if (!$airline) {
            return collect();
        }

        return Cache::remember(
            $this->getCacheKey($airline),
            Carbon::now()->addHours(12),
            fn() => AirlineStand::where('airline_id', $airline->id)
                ->get()
                ->groupBy('stand_id')
        );
    }

    private function getCacheKey(Airline $airline): string
    {
        return sprintf('AIRLINE_%d_STAND_PREFERENCES',  $airline->id);
    }
}
