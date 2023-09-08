<?php

namespace App\Services\Stand;

use App\Allocator\Stand\AirlineAircraftArrivalStandAllocator;
use App\Allocator\Stand\AirlineAircraftTerminalArrivalStandAllocator;
use App\Allocator\Stand\AirlineGeneralArrivalStandAllocator;
use App\Allocator\Stand\AirlineCallsignArrivalStandAllocator;
use App\Allocator\Stand\AirlineCallsignSlugArrivalStandAllocator;
use App\Allocator\Stand\AirlineCallsignSlugTerminalArrivalStandAllocator;
use App\Allocator\Stand\AirlineCallsignTerminalArrivalStandAllocator;
use App\Allocator\Stand\AirlineDestinationArrivalStandAllocator;
use App\Allocator\Stand\AirlineDestinationTerminalArrivalStandAllocator;
use App\Allocator\Stand\AirlineGeneralTerminalArrivalStandAllocator;
use App\Allocator\Stand\ArrivalStandAllocator;
use App\Allocator\Stand\CallsignFlightplanReservedArrivalStandAllocator;
use App\Allocator\Stand\CargoAirlineFallbackStandAllocator;
use App\Allocator\Stand\CargoFlightArrivalStandAllocator;
use App\Allocator\Stand\CargoFlightPreferredArrivalStandAllocator;
use App\Allocator\Stand\CidReservedArrivalStandAllocator;
use App\Allocator\Stand\DomesticInternationalStandAllocator;
use App\Allocator\Stand\FallbackArrivalStandAllocator;
use App\Allocator\Stand\OriginAirfieldStandAllocator;
use App\Allocator\Stand\UserRequestedArrivalStandAllocator;
use App\BaseFunctionalTestCase;
use App\Events\StandAssignedEvent;
use App\Events\StandUnassignedEvent;
use App\Models\Aircraft\Aircraft;
use App\Models\Stand\Stand;
use App\Models\Stand\StandAssignment;
use App\Models\Stand\StandReservation;
use App\Models\Vatsim\NetworkAircraft;
use App\Services\NetworkAircraftService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class ArrivalAllocationServiceTest extends BaseFunctionalTestCase
{
    private readonly ArrivalAllocationService $service;

    public function setUp(): void
    {
        parent::setUp();
        Event::fake();
        $this->service = $this->app->make(ArrivalAllocationService::class);
        DB::table('network_aircraft')->delete();
    }

    public function testItDeallocatesStandForDivertingAircraft()
    {
        $this->addStandAssignment('BMI221', 3);

        NetworkAircraftService::createOrUpdateNetworkAircraft(
            'BMI221',
            [
                'cid' => 1234,
                'planned_aircraft' => 'B738',
                'planned_aircraft_short' => 'B738',
                'planned_depairport' => 'EGKK',
                'planned_destairport' => 'EGXY',
                'groundspeed' => 150,
                // London
                'latitude' => 51.487202,
                'longitude' => -0.466667,
            ]
        );

        $this->service->allocateStandsAtArrivalAirfields();
        $this->assertNull(StandAssignment::find('BMI221'));
        Event::assertDispatched(fn(StandUnassignedEvent $event) => $event->getCallsign() === 'BMI221');
    }

    public function testItAllocatesANewStandForDivertingAircraft()
    {
        $this->addStandAssignment('BMI221', 3);

        NetworkAircraftService::createOrUpdateNetworkAircraft(
            'BMI221',
            [
                'cid' => 1234,
                'planned_aircraft' => 'B738',
                'planned_aircraft_short' => 'B738',
                'planned_depairport' => 'EGKK',
                'planned_destairport' => 'EGLL',
                'groundspeed' => 150,
                // London
                'latitude' => 51.487202,
                'longitude' => -0.466667,
            ]
        );

        $this->service->allocateStandsAtArrivalAirfields();
        $this->assertNotNull(StandAssignment::find('BMI221'));
        $this->assertTrue(in_array(StandAssignment::find('BMI221')->stand_id, [1, 2]));
        Event::assertDispatched(fn(StandUnassignedEvent $event) => $event->getCallsign() === 'BMI221');
        Event::assertDispatched(fn(StandAssignedEvent $event) => $event->getStandAssignment()->callsign === 'BMI221');
    }

    public function testItDoesntDeallocateStandIfAircraftNotDiverting()
    {
        $this->addStandAssignment('BMI221', 1);

        NetworkAircraftService::createOrUpdateNetworkAircraft(
            'BMI221',
            [
                'cid' => 1234,
                'planned_aircraft' => 'B738',
                'planned_aircraft_short' => 'B738',
                'planned_destairport' => 'EGLL',
                'groundspeed' => 150,
                // London
                'latitude' => 51.487202,
                'longitude' => -0.466667,
            ]
        );

        $this->service->allocateStandsAtArrivalAirfields();
        $this->assertEquals(1, StandAssignment::find('BMI221')->stand_id);
        Event::assertNotDispatched(fn(StandUnassignedEvent $event) => $event->getCallsign() === 'BMI221');
    }

    public function testItDoesntDeallocateStandIfForDepartureAirport()
    {
        $this->addStandAssignment('BMI221', 3);

        NetworkAircraftService::createOrUpdateNetworkAircraft(
            'BMI221',
            [
                'cid' => 1234,
                'planned_aircraft' => 'B738',
                'planned_aircraft_short' => 'B738',
                'planned_depairport' => 'EGBB',
                'planned_destairport' => 'EGLL',
                'groundspeed' => 150,
                // London
                'latitude' => 51.487202,
                'longitude' => -0.466667,
            ]
        );

        $this->service->allocateStandsAtArrivalAirfields();
        $this->assertEquals(3, StandAssignment::find('BMI221')->stand_id);
        Event::assertNotDispatched(fn(StandUnassignedEvent $event) => $event->getCallsign() === 'BMI221');
    }

    public function testItDoesntDeallocateStandIfNoStandToDeallocate()
    {
        NetworkAircraftService::createOrUpdateNetworkAircraft(
            'BMI221',
            [
                'cid' => 1234,
                'planned_aircraft' => 'B738',
                'planned_aircraft_short' => 'B738',
                'planned_destairport' => 'EGLL',
                'groundspeed' => 150,
                // London
                'latitude' => 51.487202,
                'longitude' => -0.466667,
            ]
        );

        $this->service->allocateStandsAtArrivalAirfields();
        Event::assertNotDispatched(fn(StandUnassignedEvent $event) => $event->getCallsign() === 'BMI221');
    }

    public function testItHasAllocatorPreference()
    {
        $this->assertEquals(
            [
                CidReservedArrivalStandAllocator::class,
                UserRequestedArrivalStandAllocator::class,
                CallsignFlightplanReservedArrivalStandAllocator::class,
                CargoFlightPreferredArrivalStandAllocator::class,
                CargoFlightArrivalStandAllocator::class,
                AirlineCallsignArrivalStandAllocator::class,
                AirlineCallsignSlugArrivalStandAllocator::class,
                AirlineAircraftArrivalStandAllocator::class,
                AirlineDestinationArrivalStandAllocator::class,
                AirlineGeneralArrivalStandAllocator::class,
                AirlineCallsignTerminalArrivalStandAllocator::class,
                AirlineCallsignSlugTerminalArrivalStandAllocator::class,
                AirlineAircraftTerminalArrivalStandAllocator::class,
                AirlineDestinationTerminalArrivalStandAllocator::class,
                AirlineGeneralTerminalArrivalStandAllocator::class,
                CargoAirlineFallbackStandAllocator::class,
                OriginAirfieldStandAllocator::class,
                DomesticInternationalStandAllocator::class,
                FallbackArrivalStandAllocator::class,
            ],
            array_map(
                fn(ArrivalStandAllocator $allocator) => get_class($allocator),
                $this->service->getAllocators()
            )
        );
    }

    public function testItAllocatesAStandFromAllocator()
    {
        StandReservation::create(
            [
                'callsign' => 'BMI221',
                'stand_id' => 1,
                'start' => Carbon::now()->subMinute(),
                'end' => Carbon::now()->addMinute(),
                'destination' => 'EGLL',
                'origin' => 'EGSS',
            ]
        );

        NetworkAircraftService::createOrUpdateNetworkAircraft(
            'BMI221',
            [
                'cid' => 1234,
                'planned_aircraft' => 'B738',
                'planned_aircraft_short' => 'B738',
                'planned_destairport' => 'EGLL',
                'planned_depairport' => 'EGSS',
                'groundspeed' => 150,
                // London
                'latitude' => 51.487202,
                'longitude' => -0.466667,
            ]
        );

        $this->service->allocateStandsAtArrivalAirfields();
        $this->assertEquals(1, StandAssignment::find('BMI221')->stand_id);
        Event::assertDispatched(StandAssignedEvent::class);
    }

    public function testItDoesntAllocateStandIfTimedOut()
    {
        $aircraft = NetworkAircraftService::createOrUpdateNetworkAircraft(
            'BMI221',
            [
                'cid' => 1234,
                'planned_aircraft' => 'B738',
                'planned_aircraft_short' => 'B738',
                'planned_destairport' => 'EGLL',
                'planned_depairport' => 'EGSS',
                'groundspeed' => 150,
                // London
                'latitude' => 51.487202,
                'longitude' => -0.466667,
            ]
        );
        $aircraft->updated_at = Carbon::now()->subMinutes(30);
        $aircraft->save();

        $this->service->allocateStandsAtArrivalAirfields();
        $this->assertFalse(StandAssignment::where('callsign', 'BMI221')->exists());
        Event::assertNotDispatched(StandAssignedEvent::class);
    }

    public function testItDoesntAllocateStandIfPerformingCircuits()
    {
        NetworkAircraftService::createOrUpdateNetworkAircraft(
            'BMI221',
            [
                'cid' => 1234,
                'planned_aircraft' => 'B738',
                'planned_aircraft_short' => 'B738',
                'planned_destairport' => 'EGLL',
                'planned_depairport' => 'EGLL',
                'groundspeed' => 150,
                // London
                'latitude' => 51.487202,
                'longitude' => -0.466667,
            ]
        );

        $this->service->allocateStandsAtArrivalAirfields();
        $this->assertFalse(StandAssignment::where('callsign', 'BMI221')->exists());
        Event::assertNotDispatched(StandAssignedEvent::class);
    }

    public function testItDoesntPerformAllocationIfStandTooFarFromAirfield()
    {
        NetworkAircraftService::createOrUpdateNetworkAircraft(
            'BMI221',
            [
                'cid' => 1234,
                'planned_aircraft' => 'B738',
                'planned_aircraft_short' => 'B738',
                'planned_destairport' => 'EGLL',
                'planned_depairport' => 'LFPG',
                'groundspeed' => 100,
                // Lambourne
                'latitude' => 51.646099,
                'longitude' => 0.151667,
            ]
        );

        $this->service->allocateStandsAtArrivalAirfields();
        $this->assertFalse(StandAssignment::where('callsign', 'BMI221')->exists());
        Event::assertNotDispatched(StandAssignedEvent::class);
    }

    public function testItDoesntPerformAllocationIfAircraftHasNoGroundspeed()
    {
        NetworkAircraftService::createOrUpdateNetworkAircraft(
            'BMI221',
            [
                'cid' => 1234,
                'planned_aircraft' => 'B738',
                'planned_aircraft_short' => 'B738',
                'planned_destairport' => 'EGLL',
                'planned_depairport' => 'LFPG',
                'groundspeed' => 0,
                // Lambourne
                'latitude' => 51.646099,
                'longitude' => 0.151667,
            ]
        );

        $this->service->allocateStandsAtArrivalAirfields();
        $this->assertFalse(StandAssignment::where('callsign', 'BMI221')->exists());
        Event::assertNotDispatched(StandAssignedEvent::class);
    }

    public function testItDoesntPerformAllocationIfNoStandAllocated()
    {
        // Delete all the stands so there's nothing to allocate
        Stand::all()->each(function (Stand $stand)
        {
            $stand->delete();
        });

        NetworkAircraftService::createOrUpdateNetworkAircraft(
            'BMI221',
            [
                'cid' => 1234,
                'planned_aircraft' => 'B738',
                'planned_aircraft_short' => 'B738',
                'planned_destairport' => 'EGLL',
                'planned_depairport' => 'LFPG',
                'groundspeed' => 150,
                // Lambourne
                'latitude' => 51.646099,
                'longitude' => 0.151667,
            ]
        );

        $this->service->allocateStandsAtArrivalAirfields();
        $this->assertFalse(StandAssignment::where('callsign', 'BMI221')->exists());
        Event::assertNotDispatched(StandAssignedEvent::class);
    }

    public function testItDoesntPerformAllocationIfStandAlreadyAssigned()
    {
        NetworkAircraftService::createOrUpdateNetworkAircraft(
            'BMI221',
            [
                'cid' => 1234,
                'planned_aircraft' => 'B738',
                'planned_aircraft_short' => 'B738',
                'planned_destairport' => 'EGLL',
                'planned_depairport' => 'LFPG',
                'groundspeed' => 150,
                // Lambourne
                'latitude' => 51.646099,
                'longitude' => 0.151667,
            ]
        );
        StandAssignment::create(
            [
                'callsign' => 'BMI221',
                'stand_id' => 1,
            ]
        );

        $this->service->allocateStandsAtArrivalAirfields();
        $this->assertTrue(StandAssignment::where('callsign', 'BMI221')->where('stand_id', 1)->exists());
        Event::assertNotDispatched(StandAssignedEvent::class);
    }

    public function testItDoesntReturnAllocationIfAirfieldNotFound()
    {
        NetworkAircraftService::createOrUpdateNetworkAircraft(
            'BMI221',
            [
                'cid' => 1234,
                'planned_aircraft' => 'B738',
                'planned_aircraft_short' => 'B738',
                'planned_destairport' => 'EGXX',
                'planned_depairport' => 'LFPG',
                'groundspeed' => 150,
                // Lambourne
                'latitude' => 51.646099,
                'longitude' => 0.151667,
            ]
        );

        $this->service->allocateStandsAtArrivalAirfields();
        $this->assertFalse(StandAssignment::where('callsign', 'BMI221')->exists());
        Event::assertNotDispatched(StandAssignedEvent::class);
    }

    public function testItDoesntPerformAllocationIfUnknownAircraftType()
    {
        NetworkAircraftService::createOrUpdateNetworkAircraft(
            'BMI221',
            [
                'cid' => 1234,
                'planned_aircraft' => 'B736',
                'planned_aircraft_short' => 'B736',
                'planned_destairport' => 'EGLL',
                'planned_depairport' => 'LFPG',
                'groundspeed' => 150,
                // Lambourne
                'latitude' => 51.646099,
                'longitude' => 0.151667,
            ]
        );

        $this->service->allocateStandsAtArrivalAirfields();
        $this->assertFalse(StandAssignment::where('callsign', 'BMI221')->exists());
        Event::assertNotDispatched(StandAssignedEvent::class);
    }

    public function testItDoesntPerformAllocationIfAircraftTypeNotStandAssignable()
    {
        Aircraft::where('code', 'B738')->update(['allocate_stands' => false]);

        NetworkAircraftService::createOrUpdateNetworkAircraft(
            'BMI221',
            [
                'cid' => 1234,
                'planned_aircraft' => 'B738',
                'planned_aircraft_short' => 'B738',
                'planned_destairport' => 'EGLL',
                'planned_depairport' => 'LFPG',
                'groundspeed' => 150,
                // Lambourne
                'latitude' => 51.646099,
                'longitude' => 0.151667,
            ]
        );

        $this->service->allocateStandsAtArrivalAirfields();
        $this->assertFalse(StandAssignment::where('callsign', 'BMI221')->exists());
        Event::assertNotDispatched(StandAssignedEvent::class);
    }

    private function addStandAssignment(string $callsign, int $standId): void
    {
        NetworkAircraftService::createPlaceholderAircraft($callsign);
        StandAssignment::create(
            [
                'callsign' => $callsign,
                'stand_id' => $standId,
            ]
        );
    }

    public function testItReturnsRankedStandAllocations()
    {
        // Delete other stand
        DB::table('stands')->delete();

        $aircraft = new NetworkAircraft(
            [
                'callsign' => 'BAW221',
                'cid' => 1234,
                'planned_aircraft' => 'B738',
                'planned_aircraft_short' => 'B738',
                'planned_destairport' => 'EGLL',
                'planned_depairport' => 'LFPG',
                'groundspeed' => 150,
                // Lambourne
                'latitude' => 51.646099,
                'longitude' => 0.151667,
                'airline_id' => 1,
                'aircraft_id' => 1,
            ]
        );

        // Callsign specific
        $stand1 = Stand::factory()->create(['airfield_id' => 1, 'assignment_priority' => 1]);
        $stand1->airlines()->sync([1 => ['full_callsign' => '221']]);
        $stand2 = Stand::factory()->create(['airfield_id' => 1, 'assignment_priority' => 1]);
        $stand2->airlines()->sync([1 => ['full_callsign' => '221']]);

        // Callsign specfic, but with a lower priorityy
        $stand3 = Stand::factory()->create(['airfield_id' => 1, 'assignment_priority' => 2]);
        $stand3->airlines()->sync([1 => ['full_callsign' => '221', 'priority' => 101]]);

        // Generic
        $stand4 = Stand::factory()->create(['airfield_id' => 1, 'assignment_priority' => 3]);
        $stand4->airlines()->sync([1]);

        $expected = [
            AirlineCallsignArrivalStandAllocator::class => [
                0 => [
                    $stand1->id,
                    $stand2->id,
                ],
                1 => [
                    $stand3->id,
                ],
            ],
            AirlineCallsignSlugArrivalStandAllocator::class => [],
            AirlineAircraftArrivalStandAllocator::class => [],
            AirlineDestinationArrivalStandAllocator::class => [],
            AirlineGeneralArrivalStandAllocator::class => [
                0 => [
                    $stand4->id,
                ],
            ],
            AirlineCallsignTerminalArrivalStandAllocator::class => [],
            AirlineCallsignSlugTerminalArrivalStandAllocator::class => [],
            AirlineAircraftTerminalArrivalStandAllocator::class => [],
            AirlineDestinationTerminalArrivalStandAllocator::class => [],
            AirlineGeneralTerminalArrivalStandAllocator::class => [],
            CargoAirlineFallbackStandAllocator::class => [],
            OriginAirfieldStandAllocator::class => [],
            DomesticInternationalStandAllocator::class => [],
            FallbackArrivalStandAllocator::class => [
                0 => [
                    $stand1->id,
                    $stand2->id,
                ],
                1 => [
                    $stand3->id,
                ],
                2 => [
                    $stand4->id,
                ],
            ],
        ];

        $this->assertEquals(
            $expected,
            $this->service->getAllocationRankingForAircraft($aircraft)
                ->map(
                    fn(Collection $stands) =>
                    $stands->map(
                        fn(Collection $standsForRank) =>
                        $standsForRank->sortBy('id')
                            ->map(fn(Stand $stand) => $stand->id)->values()
                    )
                )
                ->toArray()
        );
    }
}
