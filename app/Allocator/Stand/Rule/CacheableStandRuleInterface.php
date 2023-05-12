<?php

namespace App\Allocator\Stand\Rule;

use App\Allocator\Stand\Cache\StandPrioritisationCachePropertiesInterface;
use App\Models\Vatsim\NetworkAircraft;
use Carbon\CarbonImmutable;

interface CacheableStandRuleInterface
{
    public function cacheProperties(): StandPrioritisationCachePropertiesInterface;
}
