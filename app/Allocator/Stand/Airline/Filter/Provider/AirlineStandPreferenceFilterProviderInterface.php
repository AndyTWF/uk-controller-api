<?php

namespace App\Allocator\Stand\Airline\Filter\Provider;

use App\Models\Stand\AirlineStand;

interface AirlineStandPreferenceFilterProviderInterface
{
    public function preferenceApplicable(AirlineStand $preference): bool;
}
