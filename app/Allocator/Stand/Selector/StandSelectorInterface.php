<?php

namespace App\Allocator\Stand\Selector;

use App\Allocator\Stand\Prioritiser\PotentialStandPrioritiserInterface;
use App\Allocator\Stand\Rule\StandRuleInterface;
use App\Models\Stand\Stand;
use App\Models\Vatsim\NetworkAircraft;

interface StandSelectorInterface
{
    /**
     * Should select a single stand to be assigned to a given aircraft.
     *
     * This should shuffle the potential stands, and then apply the common filters to each stand, followed by the
     * rule specific filters.
     *
     * It should then return the first stand that is applicable, or null if none are.
     */
    public function selectStand(
        NetworkAircraft $aircraft,
        StandRuleInterface $rule,
        PotentialStandPrioritiserInterface $generator
    ): ?Stand;
}
