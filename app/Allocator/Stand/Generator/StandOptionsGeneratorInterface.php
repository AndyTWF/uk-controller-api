<?php

namespace App\Allocator\Stand\Generator;

use App\Allocator\Stand\Prioritiser\PotentialStandPrioritiserInterface;
use App\Allocator\Stand\Rule\StandRuleInterface;
use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Support\Collection;

interface StandOptionsGeneratorInterface
{
    /**
     * Generate the potential stands for a given aircraft, based on the rule and the prioritiser.
     *
     * This applies the rules non-query based filters (the ones that are difficult to cache against) and then
     * returns the entire set of potential stands.
     *
     * This allows us to display to users the potential stands that could be assigned, given the aircraft.
     */
    public function generateStandOptions(
        NetworkAircraft $aircraft,
        StandRuleInterface $rule,
        PotentialStandPrioritiserInterface $generator
    ): Collection;
}
