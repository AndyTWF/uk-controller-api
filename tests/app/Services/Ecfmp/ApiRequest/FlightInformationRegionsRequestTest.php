<?php

namespace App\Services\Ecfmp\ApiRequest;

use App\BaseUnitTestCase;
use App\Exceptions\Ecfmp\EcfmpDataDownloadFailedException;
use Illuminate\Support\Facades\Http;

class FlightInformationRegionsRequestTest extends BaseUnitTestCase
{
    public function testItReturnsFlightInformationRegions()
    {
        Http::fake(
            [
                'https://ecfmp.vatsim.net/api/v1/flight-information-region' => Http::response(['foo' => 'bar']),
            ]
        );

        $requester = $this->app->make(FlightInformationRegionsRequest::class);
        $this->assertEquals(['foo' => 'bar'], $requester->getData());
    }

    public function testItThrowsExceptionOnFlightInformationRegionsNotBeingRetrieved()
    {
        Http::fake(
            [
                'https://ecfmp.vatsim.net/api/v1/flight-information-region' => Http::response(['foo' => 'bar'], 500),
            ]
        );

        $requester = $this->app->make(FlightInformationRegionsRequest::class);
        $this->expectException(EcfmpDataDownloadFailedException::class);
        $this->expectExceptionMessage('Failed to download FIR data, response code was 500');
        $requester->getData();
    }
}
