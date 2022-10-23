<?php

namespace App\Services\Stand;

use App\Allocator\Stand\AirlineArrivalStandAllocator;
use App\Allocator\Stand\AirlineCallsignSlugArrivalStandAllocator;
use App\Allocator\Stand\AirlineDestinationArrivalStandAllocator;
use App\Allocator\Stand\AirlineTerminalArrivalStandAllocator;
use App\Allocator\Stand\CargoFlightPreferredArrivalStandAllocator;
use App\Allocator\Stand\CargoAirlineFallbackStandAllocator;
use App\Allocator\Stand\CargoFlightArrivalStandAllocator;
use App\Allocator\Stand\CidReservedArrivalStandAllocator;
use App\Allocator\Stand\DomesticInternationalStandAllocator;
use App\Allocator\Stand\FallbackArrivalStandAllocator;
use App\Allocator\Stand\CallsignFlightplanReservedArrivalStandAllocator;
use App\BaseFunctionalTestCase;
use App\Events\StandAssignedEvent;
use App\Events\StandUnassignedEvent;
use App\Exceptions\Stand\StandAlreadyAssignedException;
use App\Exceptions\Stand\StandNotFoundException;
use App\Models\Aircraft\Aircraft;
use App\Models\Dependency\Dependency;
use App\Models\Stand\Stand;
use App\Models\Stand\StandAssignment;
use App\Models\Stand\StandReservation;
use App\Models\Vatsim\NetworkAircraft;
use App\Services\NetworkAircraftService;
use Carbon\Carbon;

class StandServiceTest extends BaseFunctionalTestCase
{
    /**
     * @var StandService
     */
    private $service;

    /**
     * @var Dependency
     */
    private $dependency;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(StandService::class);
        $this->dependency = Dependency::create(
            [
                'key' => StandService::STAND_DEPENDENCY_KEY,
                'action' => 'foo',
                'local_file' => 'stands.json'
            ]
        );
        $this->dependency->updated_at = null;
        $this->dependency->save();
    }

    public function testItReturnsStandDependency()
    {
        $expected = collect(
            [
                'EGLL' => collect(
                    [
                        [
                            'id' => 1,
                            'identifier' => '1L',
                        ],
                        [
                            'id' => 2,
                            'identifier' => '251',
                        ],
                    ]
                ),
                'EGBB' => collect(
                    [
                        [
                            'id' => 3,
                            'identifier' => '32',
                        ]
                    ]
                ),
            ]
        );

        $this->assertEquals($expected, $this->service->getStandsDependency());
    }

    public function testStandDependencyIgnoresClosedStands()
    {
        Stand::where('identifier', '1L')
            ->airfield('EGLL')
            ->firstOrFail()
            ->close();

        $expected = collect(
            [
                'EGLL' => collect(
                    [
                        [
                            'id' => 2,
                            'identifier' => '251',
                        ],
                    ]
                ),
                'EGBB' => collect(
                    [
                        [
                            'id' => 3,
                            'identifier' => '32',
                        ]
                    ]
                ),
            ]
        );

        $this->assertEquals($expected, $this->service->getStandsDependency());
    }

    public function testItReturnsAllStandAssignments()
    {
        StandAssignment::insert(
            [
                [
                    'callsign' => 'BAW123',
                    'stand_id' => 1,
                ],
                [
                    'callsign' => 'BAW456',
                    'stand_id' => 2,
                ],
            ]
        );

        $expected = collect(
            [
                [
                    'callsign' => 'BAW123',
                    'stand_id' => 1,
                ],
                [
                    'callsign' => 'BAW456',
                    'stand_id' => 2,
                ],
            ]
        );

        $this->assertEquals($expected, $this->service->getStandAssignments());
    }

    public function testAssignStandToAircraftThrowsExceptionIfStandNotFound()
    {
        $this->doesntExpectEvents(StandAssignedEvent::class);
        $this->expectException(StandNotFoundException::class);
        $this->expectExceptionMessage('Stand with id 55 not found');
        $this->service->assignStandToAircraft('RYR7234', 55);
    }

    public function testAssignStandToAircraftAddsNewStandAssignment()
    {
        $this->expectsEvents(StandAssignedEvent::class);
        $this->doesntExpectEvents(StandUnassignedEvent::class);
        $this->service->assignStandToAircraft('RYR7234', 1);

        $this->assertDatabaseHas(
            'stand_assignments',
            [
                'callsign' => 'RYR7234',
                'stand_id' => 1,
            ]
        );
    }

    public function testAssignStandToAircraftUpdatesExistingStandAssignment()
    {
        $this->expectsEvents(StandAssignedEvent::class);
        $this->doesntExpectEvents(StandUnassignedEvent::class);
        $this->addStandAssignment('RYR7234', 1);
        $this->service->assignStandToAircraft('RYR7234', 2);

        $this->assertDatabaseHas(
            'stand_assignments',
            [
                'callsign' => 'RYR7234',
                'stand_id' => 2,
            ]
        );
    }

    public function testAssignStandToAircraftUnassignsExistingAssignment()
    {
        $this->addStandAssignment('BAW123', 1);
        $this->expectsEvents(StandAssignedEvent::class);
        $this->expectsEvents(StandUnassignedEvent::class);
        $this->service->assignStandToAircraft('RYR7234', 1);

        $this->assertDatabaseHas(
            'stand_assignments',
            [
                'callsign' => 'RYR7234',
                'stand_id' => 1,
            ]
        );
        $this->assertDatabaseMissing(
            'stand_assignments',
            [
                'callsign' => 'BAW123'
            ]
        );
    }

    public function testAssignStandToAircraftUnassignsExistingAssignmentToPairedStand()
    {
        Stand::find(1)->pairedStands()->sync([2]);
        $this->addStandAssignment('BAW123', 2);
        $this->expectsEvents(StandAssignedEvent::class);
        $this->expectsEvents(StandUnassignedEvent::class);

        $this->service->assignStandToAircraft('RYR7234', 1);

        $this->assertDatabaseHas(
            'stand_assignments',
            [
                'callsign' => 'RYR7234',
                'stand_id' => 1,
            ]
        );
        $this->assertDatabaseMissing(
            'stand_assignments',
            [
                'callsign' => 'BAW123'
            ]
        );
    }

    public function testItDoesntTriggerUnassignmentIfMovingWithinPair()
    {
        Stand::find(1)->pairedStands()->sync([2]);
        $this->addStandAssignment('BAW123', 2);
        $this->expectsEvents(StandAssignedEvent::class);
        $this->doesntExpectEvents(StandUnassignedEvent::class);

        $this->service->assignStandToAircraft('BAW123', 1);

        $this->assertDatabaseHas(
            'stand_assignments',
            [
                'callsign' => 'BAW123',
                'stand_id' => 1,
            ]
        );
    }

    public function testAssignStandToAircraftAllowsAssignmentToSameStand()
    {
        $this->expectsEvents(StandAssignedEvent::class);
        $this->doesntExpectEvents(StandUnassignedEvent::class);
        $this->addStandAssignment('RYR7234', 1);
        $this->service->assignStandToAircraft('RYR7234', 1);

        $this->assertDatabaseHas(
            'stand_assignments',
            [
                'callsign' => 'RYR7234',
                'stand_id' => 1,
            ]
        );
    }

    public function testItDeletesStandAssignments()
    {
        $this->expectsEvents(StandUnassignedEvent::class);
        $this->addStandAssignment('RYR7234', 1);
        $this->service->deleteStandAssignmentByCallsign('RYR7234');

        $this->assertDatabaseMissing(
            'stand_assignments',
            [
                'callsign' => 'RYR7234',
            ]
        );
    }

    public function testItDoesntTriggerEventIfNoAssignmentDelete()
    {
        $this->doesntExpectEvents(StandUnassignedEvent::class);
        $this->service->deleteStandAssignmentByCallsign('RYR7234');

        $this->assertDatabaseMissing(
            'stand_assignments',
            [
                'callsign' => 'RYR7234',
            ]
        );
    }

    public function testItGetsDepartureStandAssignmentForAircraft()
    {
        $this->addStandAssignment('BAW123', 1);
        NetworkAircraft::where('callsign', 'BAW123')->update(['planned_depairport' => 'EGLL']);

        $this->assertEquals(
            StandAssignment::find('BAW123'),
            $this->service->getDepartureStandAssignmentForAircraft(NetworkAircraft::find('BAW123'))
        );
    }

    public function testItDoesntGetDepartureStandIfAssignmentNotForDepartureAirport()
    {
        $this->addStandAssignment('BAW123', 1);
        NetworkAircraft::where('callsign', 'BAW123')->update(['planned_depairport' => 'EGBB']);
        $this->assertNull($this->service->getDepartureStandAssignmentForAircraft(NetworkAircraft::find('BAW123')));
    }

    public function testItDoesntGetDepartureStandIfNoAssignment()
    {
        $this->assertNull($this->service->getDepartureStandAssignmentForAircraft(NetworkAircraft::find('BAW123')));
    }

    public function testItDeletesAStand()
    {
        $this->service->deleteStand('EGLL', '1L');
        $this->assertDatabaseMissing(
            'stands',
            [
                'airfield_id' => 1,
                'identifier' => '1L'
            ]
        );
    }

    public function testDeletingAStandUpdatesStandDependency()
    {
        $this->assertNull($this->dependency->updated_at);
        $this->service->deleteStand('EGLL', '1L');
        $this->dependency->refresh();
        $this->assertNotNull($this->dependency->updated_at);
    }

    public function testItDoesNotUpdateDependencyOnDeletingNonExistentStand()
    {
        $this->assertNull($this->dependency->updated_at);
        $this->service->deleteStand('EGLL', 'ABCD');
        $this->dependency->refresh();
        $this->assertNull($this->dependency->updated_at);
    }

    public function testItChangesAStandIdentifier()
    {
        $this->service->changeStandIdentifier('EGLL', '1L', '1R');
        $this->assertDatabaseMissing(
            'stands',
            [
                'airfield_id' => 1,
                'identifier' => '1L'
            ]
        );
        $this->assertDatabaseHas(
            'stands',
            [
                'airfield_id' => 1,
                'identifier' => '1R'
            ]
        );
    }

    public function testChangingAStandIdentifierUpdatesStandDependency()
    {
        $this->assertNull($this->dependency->updated_at);
        $this->service->changeStandIdentifier('EGLL', '1L', '1R');
        $this->dependency->refresh();
        $this->assertNotNull($this->dependency->updated_at);
    }

    public function testItDoesNotUpdateDependencyOnChangingIdentifierOfNonExistentStand()
    {
        $this->assertNull($this->dependency->updated_at);
        $this->service->changeStandIdentifier('EGLL', 'ABCD', '1R');
        $this->dependency->refresh();
        $this->assertNull($this->dependency->updated_at);
    }

    public function testItReturnsAnAircraftsStandAssignment()
    {
        $this->addStandAssignment('BAW959', 1);
        $this->assertEquals(1, $this->service->getAssignedStandForAircraft('BAW959')->id);
    }

    public function testItReturnsNullIfNoStandAssignmentForAircraft()
    {
        $this->assertNull($this->service->getAssignedStandForAircraft('BAW959'));
    }

    public function testItReturnsStandStatuses()
    {
        Carbon::setTestNow(Carbon::now());

        // Clear out all the stands so its easier to follow the test data.
        Stand::all()->each(function (Stand $stand) {
            $stand->delete();
        });

        // Stand 1 is free but has a reservation starting in a few hours, it also has an airline with some destinations
        $stand1 = Stand::create(
            [
                'airfield_id' => 1,
                'type_id' => 3,
                'identifier' => 'TEST1',
                'latitude' => 54.658828,
                'longitude' => -6.222070,
            ]
        );
        $this->addStandReservation('FUTURE-RESERVATION', $stand1->id, false);
        $stand1->airlines()->attach([1 => ['destination' => 'EDDM']]);
        $stand1->airlines()->attach([1 => ['destination' => 'EDDF']]);

        // Stand 2 is assigned, it has a max aircraft type
        $stand2 = Stand::create(
            [
                'airfield_id' => 1,
                'identifier' => 'TEST2',
                'latitude' => 54.658828,
                'longitude' => -6.222070,
                'max_aircraft_id' => 1,
            ]
        );
        $this->addStandAssignment('ASSIGNMENT', $stand2->id);

        // Stand 3 is reserved
        $stand3 = Stand::create(
            [
                'airfield_id' => 1,
                'identifier' => 'TEST3',
                'latitude' => 54.658828,
                'longitude' => -6.222070,
            ]
        );
        $this->addStandReservation('RESERVATION', $stand3->id, true);

        // Stand 4 is occupied
        $stand4 = Stand::create(
            [
                'airfield_id' => 1,
                'identifier' => 'TEST4',
                'latitude' => 54.658828,
                'longitude' => -6.222070,
            ]
        );
        $occupier = NetworkAircraftService::createPlaceholderAircraft('OCCUPIED');
        $occupier->occupiedStand()->sync($stand4);

        // Stand 5 is paired with stand 2 which is assigned
        $stand5 = Stand::create(
            [
                'airfield_id' => 1,
                'identifier' => 'TEST5',
                'latitude' => 54.658828,
                'longitude' => -6.222070,
            ]
        );
        $stand2->pairedStands()->sync($stand5);
        $stand5->pairedStands()->sync($stand2);

        // Stand 6 is paired with stand 3 which is reserved
        $stand6 = Stand::create(
            [
                'airfield_id' => 1,
                'identifier' => 'TEST6',
                'latitude' => 54.658828,
                'longitude' => -6.222070,
            ]
        );
        $stand3->pairedStands()->sync([$stand6->id]);
        $stand6->pairedStands()->sync([$stand3->id]);

        // Stand 7 is paired with stand 4 which is occupied
        $stand7 = Stand::create(
            [
                'airfield_id' => 1,
                'identifier' => 'TEST7',
                'latitude' => 54.658828,
                'longitude' => -6.222070,
            ]
        );
        $stand4->pairedStands()->sync([$stand7->id]);
        $stand7->pairedStands()->sync([$stand4->id]);

        // Stand 8 is paired with stand 1 which is free
        $stand8 = Stand::create(
            [
                'airfield_id' => 1,
                'identifier' => 'TEST8',
                'latitude' => 54.658828,
                'longitude' => -6.222070,
            ]
        );
        $stand1->pairedStands()->sync([$stand8->id]);
        $stand8->pairedStands()->sync([$stand1->id]);

        // Stand 9 is reserved in half an hour
        $stand9 = Stand::create(
            [
                'airfield_id' => 1,
                'identifier' => 'TEST9',
                'latitude' => 54.658828,
                'longitude' => -6.222070,
            ]
        );
        StandReservation::create(
            [
                'callsign' => null,
                'stand_id' => $stand9->id,
                'start' => Carbon::now()->addMinutes(59)->startOfSecond(),
                'end' => Carbon::now()->addHours(2),
            ]
        );

        // Stand 10 is closed
        $stand10 = Stand::create(
            [
                'airfield_id' => 1,
                'identifier' => 'TEST10',
                'latitude' => 54.658828,
                'longitude' => -6.222070,
            ]
        );
        $stand10->close();

        $this->assertEquals(
            [
                [
                    'identifier' => 'TEST1',
                    'type' => 'CARGO',
                    'status' => 'available',
                    'airlines' => [
                        'BAW' => ['EDDM', 'EDDF']
                    ],
                    'max_wake_category' => 'LM',
                    'max_aircraft_type' => null,
                ],
                [
                    'identifier' => 'TEST2',
                    'type' => null,
                    'status' => 'assigned',
                    'callsign' => 'ASSIGNMENT',
                    'airlines' => [],
                    'max_wake_category' => 'LM',
                    'max_aircraft_type' => 'B738',
                ],
                [
                    'identifier' => 'TEST3',
                    'type' => null,
                    'status' => 'reserved',
                    'callsign' => 'RESERVATION',
                    'airlines' => [],
                    'max_wake_category' => 'LM',
                    'max_aircraft_type' => null,
                ],
                [
                    'identifier' => 'TEST4',
                    'type' => null,
                    'status' => 'occupied',
                    'callsign' => 'OCCUPIED',
                    'airlines' => [],
                    'max_wake_category' => 'LM',
                    'max_aircraft_type' => null,
                ],
                [
                    'identifier' => 'TEST5',
                    'type' => null,
                    'status' => 'unavailable',
                    'airlines' => [],
                    'max_wake_category' => 'LM',
                    'max_aircraft_type' => null,
                ],
                [
                    'identifier' => 'TEST6',
                    'type' => null,
                    'status' => 'unavailable',
                    'airlines' => [],
                    'max_wake_category' => 'LM',
                    'max_aircraft_type' => null,
                ],
                [
                    'identifier' => 'TEST7',
                    'type' => null,
                    'status' => 'unavailable',
                    'airlines' => [],
                    'max_wake_category' => 'LM',
                    'max_aircraft_type' => null,
                ],
                [
                    'identifier' => 'TEST8',
                    'type' => null,
                    'status' => 'available',
                    'airlines' => [],
                    'max_wake_category' => 'LM',
                    'max_aircraft_type' => null,
                ],
                [
                    'identifier' => 'TEST9',
                    'type' => null,
                    'status' => 'reserved_soon',
                    'callsign' => null,
                    'reserved_at' => Carbon::now()->addMinutes(59)->startOfSecond(),
                    'airlines' => [],
                    'max_wake_category' => 'LM',
                    'max_aircraft_type' => null,
                ],
                [
                    'identifier' => 'TEST10',
                    'type' => null,
                    'status' => 'closed',
                    'airlines' => [],
                    'max_wake_category' => 'LM',
                    'max_aircraft_type' => null,
                ],
            ],
            $this->service->getAirfieldStandStatus('EGLL')
        );
    }

    public function testItReturnsAircraftWhoAreEligibleForArrivalStandAllocation()
    {
        $this->addStandAssignment('BAW456', 2);
        $this->assertEquals(
            collect(
                [
                    NetworkAircraft::find('BAW123')
                ]
            ),
            $this->service->getAircraftEligibleForArrivalStandAllocation()->toBase()
        );
    }

    private function addStandAssignment(string $callsign, int $standId): StandAssignment
    {
        NetworkAircraftService::createPlaceholderAircraft($callsign);
        return StandAssignment::create(
            [
                'callsign' => $callsign,
                'stand_id' => $standId,
            ]
        );
    }

    private function addStandReservation(string $callsign, int $standId, bool $active): StandReservation
    {
        NetworkAircraftService::createPlaceholderAircraft($callsign);
        return StandReservation::create(
            [
                'callsign' => $callsign,
                'stand_id' => $standId,
                'start' => $active ? Carbon::now() : Carbon::now()->addHours(2),
                'end' => Carbon::now()->addHours(2)->addMinutes(10),
                'destination' => 'EGLL',
                'origin' => 'EGSS',
            ]
        );
    }
}
