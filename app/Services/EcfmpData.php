<?php

namespace App\Services;

class EcfmpData
{
    private readonly array $flowMeasures;
    private readonly array $flightInformationRegions;

    public function __construct(array $flowMeasures, array $flightInformationRegions)
    {
        $this->flowMeasures = $flowMeasures;
        $this->flightInformationRegions = $flightInformationRegions;
    }

    public function flowMeasures(): array
    {
        return $this->flowMeasures;
    }

    public function flightInformationRegions(): array
    {
        return $this->flightInformationRegions;
    }
}
