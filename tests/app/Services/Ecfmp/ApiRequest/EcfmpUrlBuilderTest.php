<?php

namespace App\Services\Ecfmp\ApiRequest;

use App\BaseUnitTestCase;

class EcfmpUrlBuilderTest extends BaseUnitTestCase
{
    /**
     * @dataProvider urlPathProvider
     */
    public function testItBuildsTheUrl(string $path, string $expected)
    {
        $this->assertEquals(
            $expected,
            (new EcfmpUrlBuilder())->buildUrl($path)
        );
    }

    public function urlPathProvider(): array
    {
        return [
            'Flow measures' => [
                'api/v1/flow-measure',
                'https://ecfmp.vatsim.net/api/v1/flow-measure',
            ],
            'Flow measures with preceding slash' => [
                '/api/v1/flow-measure',
                'https://ecfmp.vatsim.net/api/v1/flow-measure',
            ],
            'Flow measures with following slash' => [
                'api/v1/flow-measure/',
                'https://ecfmp.vatsim.net/api/v1/flow-measure',
            ],
            'Flow measures with both slashes' => [
                '/api/v1/flow-measure/',
                'https://ecfmp.vatsim.net/api/v1/flow-measure',
            ],
        ];
    }
}
