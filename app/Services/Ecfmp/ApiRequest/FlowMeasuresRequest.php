<?php

namespace App\Services\Ecfmp\ApiRequest;

use App\Exceptions\Ecfmp\EcfmpDataDownloadFailedException;
use Illuminate\Support\Facades\Http;

class FlowMeasuresRequest implements ApiRequestInterface
{
    private readonly EcfmpUrlBuilder $urlBuilder;

    public function __construct(EcfmpUrlBuilder $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }

    public function getData(): array
    {
        $response = Http::get(
            $this->urlBuilder->buildUrl('/api/v1/flow-measure'),
            [
                'deleted' => 1,
            ]
        );

        if (!$response->successful()) {
            throw new EcfmpDataDownloadFailedException(
                sprintf('Failed to download Flow Measure data, response code was %d', $response->status())
            );
        }

        return $response->json();
    }
}
