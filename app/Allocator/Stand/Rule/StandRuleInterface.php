<?php

namespace App\Allocator\Stand\Rule;

use Illuminate\Support\Collection;

interface StandRuleInterface
{
    /**
     * Returns filters that are always applicable to this rule.
     */
    public function filters(): Collection;

    /**
     * Returns the sorters for this rule.
     */
    public function sorters(): Collection;

    /**
     * Returns filters that are applied "pre-selection", for example a time-based filter.
     */
    public function preSelectionFilters(): Collection;
}
