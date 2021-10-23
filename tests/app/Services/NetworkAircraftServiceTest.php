<?php

namespace App\Services;

use App\BaseFunctionalTestCase;
use App\Events\NetworkDataUpdatedEvent;
use App\Jobs\Network\AircraftDisconnected;
use App\Models\Vatsim\NetworkAircraft;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use PDOException;

class NetworkAircraftServiceTest extends BaseFunctionalTestCase
{
    /**
     * @var array[]
     */
    private $networkData;

    /**
     * @var NetworkAircraftService
     */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->networkData = [
            'pilots' => [
                $this->getPilotData('VIR25A', true),
                $this->getPilotData('BAW123', false, null, null, '1234'),
                $this->getPilotData('RYR824', true),
                $this->getPilotData('LOT551', true, 44.372, 26.040),
                $this->getPilotData('BMI221', true, null, null, '777'),
                $this->getPilotData('BMI222', true, null, null, '12a4'),
                $this->getPilotData('BMI223', true, null, null, '7778'),
            ]
        ];

        Queue::fake();
        Carbon::setTestNow(Carbon::now()->startOfSecond());
        Date::setTestNow(Carbon::now());
        $this->service = $this->app->make(NetworkAircraftService::class);
    }

    private function fakeNetworkDataReturn(): void
    {
        Http::fake(
            [
                NetworkAircraftService::NETWORK_DATA_URL => Http::response(json_encode($this->networkData))
            ]
        );
    }

    public function testItHandlesErrorCodesFromNetworkDataFeed()
    {
        $this->doesntExpectEvents(NetworkDataUpdatedEvent::class);
        Http::fake(
            [
                NetworkAircraftService::NETWORK_DATA_URL => Http::response('', 500)
            ]
        );
        $this->service->updateNetworkData();
        $this->assertDatabaseMissing(
            'network_aircraft',
            [
                'callsign' => 'VIR25A',
            ]
        );
    }

    public function testItHandlesExceptionsFromNetworkDataFeed()
    {
        $this->doesntExpectEvents(NetworkDataUpdatedEvent::class);
        Http::fake(
            function () {
                throw new Exception('LOL');
            }
        );
        $this->service->updateNetworkData();
        $this->assertDatabaseMissing(
            'network_aircraft',
            [
                'callsign' => 'VIR25A',
            ]
        );
    }

    public function testItHandlesMissingClientData()
    {
        $this->expectsEvents(NetworkDataUpdatedEvent::class);
        Http::fake(
            [
                NetworkAircraftService::NETWORK_DATA_URL => Http::response(json_encode(['not_clients' => '']), 200)
            ]
        );
        $this->service->updateNetworkData();
        $this->assertDatabaseMissing(
            'network_aircraft',
            [
                'callsign' => 'VIR25A',
            ]
        );
    }

    public function testItAddsNewAircraftFromDataFeed()
    {
        $this->withoutEvents();
        $this->fakeNetworkDataReturn();
        $this->service->updateNetworkData();
        $this->assertDatabaseHas(
            'network_aircraft',
            array_merge(
                $this->getTransformedPilotData('VIR25A'),
                [
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'transponder_last_updated_at' => Carbon::now()
                ]
            ),
        );
    }

    public function testItUpdatesExistingAircraftFromDataFeed()
    {
        $this->withoutEvents();
        $this->fakeNetworkDataReturn();
        $this->service->updateNetworkData();
        $this->assertDatabaseHas(
            'network_aircraft',
            array_merge(
                $this->getTransformedPilotData('BAW123', false, '1234'),
                [
                    'created_at' => '2020-05-30 17:30:00',
                    'updated_at' => Carbon::now()
                ]
            ),
        );
    }

    public function testItUpdatesExistingAircraftTransponderChangedAtFromDataFeed()
    {
        $this->withoutEvents();
        $this->fakeNetworkDataReturn();
        $this->service->updateNetworkData();
        $this->assertDatabaseHas(
            'network_aircraft',
            array_merge(
                $this->getTransformedPilotData('RYR824', false),
                [
                    'created_at' => '2020-05-30 17:30:00',
                    'updated_at' => Carbon::now(),
                    'transponder_last_updated_at' => Carbon::now(),
                ]
            ),
        );
    }

    public function testItDoesntUpdateExistingAircraftTransponderChangedAtFromDataFeedIfSame()
    {
        // Update the transponder 15 minutes ago
        $transponderUpdatedAt = Carbon::now()->subMinutes(15);
        DB::table('network_aircraft')->where('callsign', 'BAW123')->update(
            ['transponder_last_updated_at' => $transponderUpdatedAt]
        );

        $this->withoutEvents();
        $this->fakeNetworkDataReturn();
        $this->service->updateNetworkData();
        $this->assertDatabaseHas(
            'network_aircraft',
            array_merge(
                $this->getTransformedPilotData('BAW123', false, '1234'),
                [
                    'created_at' => '2020-05-30 17:30:00',
                    'updated_at' => Carbon::now(),
                    'transponder_last_updated_at' => $transponderUpdatedAt,
                ]
            ),
        );
    }

    public function testItUpdatesExistingAircraftOnTheGroundFromDataFeed()
    {
        $this->withoutEvents();
        $this->fakeNetworkDataReturn();
        $this->service->updateNetworkData();
        $this->assertDatabaseHas(
            'network_aircraft',
            array_merge(
                $this->getTransformedPilotData('RYR824'),
                ['created_at' => '2020-05-30 17:30:00', 'updated_at' => Carbon::now()]
            ),
        );
    }

    public function testItDoesntAddAtcFromDataFeed()
    {
        $this->withoutEvents();
        $this->fakeNetworkDataReturn();
        $this->service->updateNetworkData();
        $this->assertDatabaseMissing(
            'network_aircraft',
            [
                'callsign' => 'LON_S_CTR'
            ]
        );
    }

    public function testItDoesntUpdateAircraftOutOfRangeFromTheDataFeed()
    {
        $this->withoutEvents();
        $this->fakeNetworkDataReturn();
        $this->service->updateNetworkData();
        $this->assertDatabaseMissing(
            'network_aircraft',
            [
                'callsign' => 'LOT551',
            ],
        );
    }

    public function testItDoesntUpdateAircraftWithInvalidTransponderFromDataFeed()
    {
        $this->withoutEvents();
        $this->fakeNetworkDataReturn();
        $this->service->updateNetworkData();
        $this->assertDatabaseMissing(
            'network_aircraft',
            [
                'callsign' => 'BMI221',
            ],
        );
        $this->assertDatabaseMissing(
            'network_aircraft',
            [
                'callsign' => 'BMI222',
            ],
        );
        $this->assertDatabaseMissing(
            'network_aircraft',
            [
                'callsign' => 'BMI223',
            ],
        );
    }

    public function testItTimesOutAircraftFromDataFeed()
    {
        $this->fakeNetworkDataReturn();
        $this->service->updateNetworkData();
        Queue::assertNotPushed(AircraftDisconnected::class, function (AircraftDisconnected $job) {
            return $job->aircraft->callsign === 'BAW123';
        });
        Queue::assertNotPushed(AircraftDisconnected::class, function (AircraftDisconnected $job) {
            return $job->aircraft->callsign === 'BAW456 ';
        });
        Queue::assertPushed(AircraftDisconnected::class, function (AircraftDisconnected $job) {
            return $job->aircraft->callsign === 'BAW789';
        });
    }

    public function testItFiresUpdatedEventsOnDataFeed()
    {
        $this->expectsEvents(NetworkDataUpdatedEvent::class);
        $this->fakeNetworkDataReturn();
        $this->service->updateNetworkData();
    }

    public function testItCreatesNetworkAircraft()
    {
        $expectedData = $this->getTransformedPilotData('AAL123');
        $actual = NetworkAircraftService::createOrUpdateNetworkAircraft('AAL123', $expectedData);
        $actual->refresh();
        $expected = NetworkAircraft::find('AAL123');
        $this->assertEquals($expected->toArray(), $actual->toArray());
        $this->assertDatabaseHas(
            'network_aircraft',
            array_merge(
                $expectedData,
                ['created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
            ),
        );
    }

    public function testItCreatesNetworkAircraftCallsignOnly()
    {
        $actual = NetworkAircraftService::createOrUpdateNetworkAircraft('AAL123');
        $actual->refresh();
        $expected = NetworkAircraft::find('AAL123');
        $this->assertEquals($expected->toArray(), $actual->toArray());
        $this->assertDatabaseHas(
            'network_aircraft',
            [
                'callsign' => 'AAL123',
            ]
        );
    }

    public function testItUpdatesNetworkAircraft()
    {
        $expectedData = $this->getTransformedPilotData('AAL123');
        NetworkAircraft::create($expectedData);
        $actual = NetworkAircraftService::createOrUpdateNetworkAircraft(
            'AAL123',
            ['groundspeed' => '456789']
        );
        $expected = NetworkAircraft::find('AAL123');
        $this->assertEquals($expected->toArray(), $actual->toArray());

        $this->assertDatabaseHas(
            'network_aircraft',
            array_merge(
                $expectedData,
                [
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'groundspeed' => '456789'
                ]
            ),
        );
    }

    public function testItUpdatesNetworkAircraftCallsignOnly()
    {
        $expectedData = $this->getTransformedPilotData('AAL123');
        NetworkAircraft::create($expectedData);
        $actual = NetworkAircraftService::createOrUpdateNetworkAircraft('AAL123');
        $expected = NetworkAircraft::find('AAL123');
        $this->assertEquals($expected->toArray(), $actual->toArray());

        $this->assertDatabaseHas(
            'network_aircraft',
            array_merge(
                $expectedData,
                [
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            ),
        );
    }

    public function testItCreatesPlaceholderAircraft()
    {
        $actual = NetworkAircraftService::createPlaceholderAircraft('AAL123');
        $actual->refresh();
        $expected = NetworkAircraft::find('AAL123');
        $this->assertEquals($expected->toArray(), $actual->toArray());
        $this->assertDatabaseHas(
            'network_aircraft',
            array_merge(
                ['callsign' => 'AAL123'],
                ['created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
            ),
        );
    }

    public function testItFindsNetworkAircraftInCreatePlaceholder()
    {
        Carbon::setTestNow(Carbon::now());
        $expectedData = $this->getTransformedPilotData('AAL123');
        $expected = NetworkAircraft::create($expectedData);
        $expected->created_at = Carbon::now()->subHours(2);
        $expected->updated_at = Carbon::now()->subMinutes(5);
        $expected->save();
        $expected->refresh();
        $this->assertEquals(
            $expected->toArray(),
            NetworkAircraftService::createPlaceholderAircraft('AAL123')->toArray()
        );

        $this->assertDatabaseHas(
            'network_aircraft',
            array_merge(
                $expectedData,
                [
                    'created_at' => Carbon::now()->subHours(2)->toDateTimeString(),
                    'updated_at' => Carbon::now()->subMinutes(5)->toDateTimeString(),
                ]
            ),
        );
    }

    private function getPilotData(
        string $callsign,
        bool $hasFlightplan,
        float $latitude = null,
        float $longitude = null,
        string $transponder = null
    ): array {
        return [
            'callsign' => $callsign,
            'latitude' => $latitude ?? 54.66,
            'longitude' => $longitude ?? -6.21,
            'altitude' => 35123,
            'groundspeed' => 123,
            'transponder' => $transponder ?? '0457',
            'flight_plan' => $hasFlightplan
                ? [
                    'aircraft' => 'B738',
                    'departure' => 'EGKK',
                    'arrival' => 'EGPH',
                    'altitude' => '15001',
                    'flight_rules' => 'I',
                    'route' => 'DIRECT',
                ]
                : null,
        ];
    }

    private function getTransformedPilotData(
        string $callsign,
        bool $hasFlightplan = true,
        string $transponder = null
    ): array
    {
        $pilot = $this->getPilotData($callsign, $hasFlightplan, null, null, $transponder);
        $baseData = [
            'callsign' => $pilot['callsign'],
            'latitude' => $pilot['latitude'],
            'longitude' => $pilot['longitude'],
            'altitude' => $pilot['altitude'],
            'groundspeed' => $pilot['groundspeed'],
            'transponder' => Str::padLeft($pilot['transponder'], '0', 4),
        ];

        if ($hasFlightplan) {
            $baseData = array_merge(
                $baseData,
                [
                    'planned_aircraft' => $pilot['flight_plan']['aircraft'],
                    'planned_depairport' => $pilot['flight_plan']['departure'],
                    'planned_destairport' => $pilot['flight_plan']['arrival'],
                    'planned_altitude' => $pilot['flight_plan']['altitude'],
                    'planned_flighttype' => $pilot['flight_plan']['flight_rules'],
                    'planned_route' => $pilot['flight_plan']['route'],
                ]
            );
        }

        return $baseData;
    }
}
