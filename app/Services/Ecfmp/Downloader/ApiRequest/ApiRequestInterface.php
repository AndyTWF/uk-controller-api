<?php

namespace App\Services\Ecfmp\Downloader\ApiRequest;

interface ApiRequestInterface
{
    /**
     * The data itself.
     */
    public function getData(): array;
}
