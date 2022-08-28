<?php

namespace App\Services\Ecfmp\Downloader;

use App\Services\Ecfmp\Downloader\ApiRequest\ApiRequestInterface;

class EcfmpDataDownloader
{
    private readonly ApiRequestInterface $flowMeasuresRequest;
    private readonly ApiRequestInterface $flightInformationRegionsRequest;

    public function __construct(
        ApiRequestInterface $flowMeasuresRequest,
        ApiRequestInterface $flightInformationRegionsRequest
    ) {
        $this->flowMeasuresRequest = $flowMeasuresRequest;
        $this->flightInformationRegionsRequest = $flightInformationRegionsRequest;
    }

    public function downloadData(): EcfmpData
    {
        return new EcfmpData(
            $this->flowMeasuresRequest->getData(),
            $this->flightInformationRegionsRequest->getData()
        );
    }
}
