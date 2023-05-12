<?php

namespace App\Allocator\Stand\Cache;

use App\Models\Vatsim\NetworkAircraft;
use Carbon\CarbonImmutable;

interface StandPrioritisationCachePropertiesInterface
{
    /**
     * The cache key. Returns null if no cache key is available.
     */
    public function cacheKey(NetworkAircraft $aircraft): string|null;

    /**
     * How long to cache for.
     */
    public function cacheDuration(): CarbonImmutable;
}
