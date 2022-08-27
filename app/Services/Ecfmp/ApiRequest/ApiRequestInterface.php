<?php

namespace App\Services\Ecfmp\ApiRequest;

interface ApiRequestInterface
{
    /**
     * The data itself.
     */
    public function getData(): array;
}
