<?php

namespace App\Services;

use App\Models\Airline\Airline;
use App\Models\Vatsim\NetworkAircraft;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AirlineService
{
    public function getAirlineForAircraft(NetworkAircraft $aircraft): ?Airline
    {
        $airlineIcaoCode = $this->getCallsignAirlinePart($aircraft);
        return Cache::remember(
            sprintf('AIRLINE_%s_AIRLINE_ID', $airlineIcaoCode),
            Carbon::now()->addDay(),
            fn() => Airline::where('icao_code', $airlineIcaoCode)->first()
        );
    }

    private function getCallsignAirlinePart(NetworkAircraft $aircraft): string
    {
        return Str::substr($aircraft->callsign, 0, 3);
    }

    public function getCallsignSlugForAircraft(NetworkAircraft $aircraft): string
    {
        $airline = $this->getAirlineForAircraft($aircraft);
        return $airline
            ? Str::substr($aircraft->callsign, 3)
            : $aircraft->callsign;
    }
}
