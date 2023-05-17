<?php

namespace App\Allocator\Stand\Selector;

use App\Allocator\Stand\Prioritiser\PotentialStandPrioritiserInterface;
use App\Allocator\Stand\Randomiser\StandRandomiserInterface;
use App\Allocator\Stand\Rule\StandRuleInterface;
use App\Models\Stand\Stand;
use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Support\Collection;

class StandSelector implements StandSelectorInterface
{
    private readonly StandRandomiserInterface $randomiser;

    private readonly Collection $commonFilters;

    public function __construct(
        StandRandomiserInterface $randomiser,
        Collection $commonFilters,
    ) {
        $this->randomiser = $randomiser;
        $this->commonFilters = $commonFilters;
    }

    /**
     * @inheritDoc
     */
    public function selectStand(
        NetworkAircraft $aircraft,
        StandRuleInterface $rule,
        PotentialStandPrioritiserInterface $generator
    ): ?Stand {
        // The potential stands is a collection of collections of stands.
        foreach ($generator->prioritisePotentialStands($aircraft) as $potentialStands) {
            // Randomise the subset of stands.
            $randomisedStands = $this->randomiser->randomise($potentialStands);

            // Pick the first one that is applicable
            foreach ($randomisedStands as $stand) {
                if ($this->commonFilters->contains(fn($filter) => !$filter->filter($aircraft, $stand))) {
                    continue;
                }

                if ($rule->filters()->contains(fn($filter) => !$filter->filter($aircraft, $stand))) {
                    continue;
                }

                return $stand;
            }
        }

        return null;
    }
}
