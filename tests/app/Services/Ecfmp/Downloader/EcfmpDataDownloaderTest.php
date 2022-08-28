<?php

namespace App\Services\Ecfmp\Downloader;

use App\BaseUnitTestCase;
use App\Exceptions\Ecfmp\EcfmpDataDownloadFailedException;
use App\Services\Ecfmp\Downloader\ApiRequest\ApiRequestInterface;
use Mockery;

class EcfmpDataDownloaderTest extends BaseUnitTestCase
{
    public function testItDownloadsData()
    {
        $mockFlowMeasureRequest = Mockery::mock(ApiRequestInterface::class)
            ->shouldReceive('getData')
            ->once()
            ->andReturn(['foo' => 'bar'])
            ->getMock();

        $mockFlightInformationRegionRequest = Mockery::mock(ApiRequestInterface::class)
            ->shouldReceive('getData')
            ->once()
            ->andReturn(['bar' => 'baz'])
            ->getMock();

        $ecfmpData = (new EcfmpDataDownloader($mockFlowMeasureRequest, $mockFlightInformationRegionRequest))
            ->downloadData();

        $this->assertEquals(['foo' => 'bar'], $ecfmpData->flowMeasures());
        $this->assertEquals(['bar' => 'baz'], $ecfmpData->flightInformationRegions());
    }

    public function testItThrowsExceptionIfFlowMeasureRequestThrowsException()
    {
        $mockFlowMeasureRequest = Mockery::mock(ApiRequestInterface::class)
            ->shouldReceive('getData')
            ->once()
            ->andThrow(new EcfmpDataDownloadFailedException())
            ->getMock();

        $mockFlightInformationRegionRequest = Mockery::mock(ApiRequestInterface::class)
            ->shouldReceive('getData')
            ->never()
            ->getMock();

        $this->expectException(EcfmpDataDownloadFailedException::class);
        (new EcfmpDataDownloader($mockFlowMeasureRequest, $mockFlightInformationRegionRequest))
            ->downloadData();
    }

    public function testItThrowsExceptionIfFlightInformationRegionRequestThrowsException()
    {
        $mockFlowMeasureRequest = Mockery::mock(ApiRequestInterface::class)
            ->shouldReceive('getData')
            ->once()
            ->andReturn(['foo' => 'bar'])
            ->getMock();

        $mockFlightInformationRegionRequest = Mockery::mock(ApiRequestInterface::class)
            ->shouldReceive('getData')
            ->once()
            ->andThrow(new EcfmpDataDownloadFailedException())
            ->getMock();

        $this->expectException(EcfmpDataDownloadFailedException::class);
        (new EcfmpDataDownloader($mockFlowMeasureRequest, $mockFlightInformationRegionRequest))
            ->downloadData();
    }
}
