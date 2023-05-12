<?php

namespace App\Allocator\Stand\Airline;

use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Support\Collection;

interface AirlineStandPreferencesInterface
{
    public function getAirlineStandPreferences(NetworkAircraft $aircraft): Collection;
}
