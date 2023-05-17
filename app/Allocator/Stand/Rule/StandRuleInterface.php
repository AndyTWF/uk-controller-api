<?php

namespace App\Allocator\Stand\Rule;

use Illuminate\Support\Collection;

interface StandRuleInterface
{
    /**
     * Returns filters that should be applied to the query.
     */
    public function queryFilters(): Collection;

    /**
     * Returns filters that need to be applied after the query has been executed and are contextual (e.g. not after).
     */
    public function filters(): Collection;

    /**
     * Returns the sorters for this rule.
     */
    public function sorters(): Collection;
}
