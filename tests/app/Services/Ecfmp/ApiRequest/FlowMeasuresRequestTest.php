<?php

namespace App\Services\Ecfmp\ApiRequest;

use App\BaseUnitTestCase;
use App\Exceptions\Ecfmp\EcfmpDataDownloadFailedException;
use Illuminate\Support\Facades\Http;

class FlowMeasuresRequestTest extends BaseUnitTestCase
{
    public function testItReturnsFlowMeasures()
    {
        Http::fake(
            [
                'https://ecfmp.vatsim.net/api/v1/flow-measure?deleted=1' => Http::response(['foo' => 'bar']),
            ]
        );

        $requester = $this->app->make(FlowMeasuresRequest::class);
        $this->assertEquals(['foo' => 'bar'], $requester->getData());
    }

    public function testItThrowsExceptionOnFlowMeasuresNotBeingRetrieved()
    {
        Http::fake(
            [
                'https://ecfmp.vatsim.net/api/v1/flow-measure?deleted=1' => Http::response(['foo' => 'bar'], 500),
            ]
        );

        $requester = $this->app->make(FlowMeasuresRequest::class);
        $this->expectException(EcfmpDataDownloadFailedException::class);
        $this->expectExceptionMessage('Failed to download Flow Measure data, response code was 500');
        $requester->getData();
    }
}
