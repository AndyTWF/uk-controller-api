<?php

namespace App\Services\Ecfmp\Downloader\ApiRequest;

class EcfmpUrlBuilder
{
    private const URL_SEPARATOR = '/';

    public function buildUrl(string $path): string
    {
        return sprintf(
            '%s%s%s',
            config('services.ecfmp.base_url'),
            self::URL_SEPARATOR,
            trim($path, self::URL_SEPARATOR)
        );
    }
}
