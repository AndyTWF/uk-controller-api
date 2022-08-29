<?php

namespace App\Http\Controllers\Ecfmp;

use App\BaseApiTestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class EcfmpControllerTest extends BaseApiTestCase
{
    public function testItReturnsEcfmpData()
    {
        Cache::put('ECFMP_DATA', ['foo' => 'bar'], Carbon::now()->addMinute());

        $this->makeUnauthenticatedApiRequest(self::METHOD_GET, 'api/ecfmp')
            ->assertStatus(200)
            ->assertExactJson(['foo' => 'bar']);
    }
}
