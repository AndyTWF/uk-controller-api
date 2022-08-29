<?php

namespace App\Services\Ecfmp;

use App\BaseUnitTestCase;
use Illuminate\Support\Facades\Cache;

class EcfmpServiceTest extends BaseUnitTestCase
{
    public function testItReturnsEcfmpDataFromCache()
    {
        Cache::shouldReceive('get')->with('ECFMP_DATA', [])
            ->andReturn(['foo' => 'bar']);

        $this->assertEquals(
            ['foo' => 'bar'],
            (new EcfmpService())->getEcfmpData()
        );
    }
}
