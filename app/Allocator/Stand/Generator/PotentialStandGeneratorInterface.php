<?php

namespace App\Allocator\Stand\Generator;

use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Support\Collection;

interface PotentialStandGeneratorInterface
{
    public function generatePotentialStands(NetworkAircraft $aircraft): Collection;
}
