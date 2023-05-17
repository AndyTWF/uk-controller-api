<?php

namespace App\Allocator\Stand\Generator;

use App\Allocator\Stand\Prioritiser\PotentialStandPrioritiserInterface;
use App\Allocator\Stand\Rule\StandRuleInterface;
use App\Models\Stand\Stand;
use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Support\Collection;

class StandOptionsGenerator implements StandOptionsGeneratorInterface
{
    public function generateStandOptions(
        NetworkAircraft $aircraft,
        StandRuleInterface $rule,
        PotentialStandPrioritiserInterface $generator
    ): Collection {
        return $generator->prioritisePotentialStands($aircraft)
            ->map(
                fn(Collection $potentialStandGroup) => $potentialStandGroup->reject(
                    fn(Stand $stand) => $rule->filters()->contains(fn($filter) => !$filter->filter($aircraft, $stand))
                )
            );
    }
}
