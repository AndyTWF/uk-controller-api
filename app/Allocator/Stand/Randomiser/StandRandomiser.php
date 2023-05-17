<?php

namespace App\Allocator\Stand\Randomiser;

use Illuminate\Support\Collection;

class StandRandomiser implements StandRandomiserInterface
{
    public function randomise(Collection $stands): Collection
    {
        return $stands->shuffle();
    }
}
