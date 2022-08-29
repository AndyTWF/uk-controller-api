<?php

namespace App\Services\Ecfmp;

use Illuminate\Support\Facades\Cache;

class EcfmpService
{
    public const ECFMP_CACHE_KEY = 'ECFMP_DATA';

    public function getEcfmpData(): array
    {
        return Cache::get(self::ECFMP_CACHE_KEY, []);
    }
}
