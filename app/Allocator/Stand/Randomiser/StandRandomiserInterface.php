<?php

namespace App\Allocator\Stand\Randomiser;

use Illuminate\Support\Collection;

interface StandRandomiserInterface
{
    public function randomise(Collection $stands): Collection;
}
