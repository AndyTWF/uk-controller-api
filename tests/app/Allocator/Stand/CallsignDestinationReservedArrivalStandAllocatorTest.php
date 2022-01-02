<?php

namespace App\Allocator\Stand;

use App\BaseFunctionalTestCase;
use App\Models\Stand\StandAssignment;
use App\Models\Stand\StandReservation;
use App\Models\Vatsim\NetworkAircraft;
use Carbon\Carbon;

class CallsignDestinationReservedArrivalStandAllocatorTest extends BaseFunctionalTestCase
{
    private CallsignDestinationReservedArrivalStandAllocator $allocator;

    public function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::now());
        $this->allocator = $this->app->make(CallsignDestinationReservedArrivalStandAllocator::class);
        NetworkAircraft::find('BAW123')->update(['planned_destairport' => 'EGLL', 'planned_depairport' => 'EGCC']);
    }

    public function testItAllocatesReservedStandIfReservedAtWithinThirtyMinutesEitherSideOfNow()
    {
        StandReservation::create(
            [
                'callsign' => 'BAW123',
                'stand_id' => 1,
                'reserved_at' => Carbon::now(),
                'origin' => 'EGCC',
                'destination' => 'EGLL',
            ]
        );

        $actual = $this->allocator->allocate(NetworkAircraft::find('BAW123'));
        $expected = StandAssignment::where('callsign', 'BAW123')->first();

        $this->assertEquals($actual->stand_id, 1);
        $this->assertEquals($actual->stand_id, $expected->stand_id);
    }

    public function testItAllocatesReservedStandIfReservedThirtyMinutesBeforeNow()
    {
        StandReservation::create(
            [
                'callsign' => 'BAW123',
                'stand_id' => 1,
                'reserved_at' => Carbon::now()->subMinutes(30),
                'origin' => 'EGCC',
                'destination' => 'EGLL',
            ]
        );

        $actual = $this->allocator->allocate(NetworkAircraft::find('BAW123'));
        $expected = StandAssignment::where('callsign', 'BAW123')->first();

        $this->assertEquals($actual->stand_id, 1);
        $this->assertEquals($actual->stand_id, $expected->stand_id);
    }

    public function testItAllocatesReservedStandIfReservedThirtyMinutesAfterNow()
    {
        StandReservation::create(
            [
                'callsign' => 'BAW123',
                'stand_id' => 1,
                'reserved_at' => Carbon::now()->addMinutes(30),
                'origin' => 'EGCC',
                'destination' => 'EGLL',
            ]
        );

        $actual = $this->allocator->allocate(NetworkAircraft::find('BAW123'));
        $expected = StandAssignment::where('callsign', 'BAW123')->first();

        $this->assertEquals($actual->stand_id, 1);
        $this->assertEquals($actual->stand_id, $expected->stand_id);
    }

    public function testItDoesntAllocateReservedStandIfReservedAtTimeMoreThanThirtyMinutesAgo()
    {
        StandReservation::create(
            [
                'callsign' => 'BAW123',
                'stand_id' => 1,
                'reserved_at' => Carbon::now()->subMinutes(31),
                'origin' => 'EGCC',
                'destination' => 'EGLL',
            ]
        );

        $this->assertNull($this->allocator->allocate(NetworkAircraft::find('BAW123')));
    }

    public function testItDoesntAllocateReservedStandIfReservedAtTimeMoreThanThirtyMinutesAway()
    {
        StandReservation::create(
            [
                'callsign' => 'BAW123',
                'stand_id' => 1,
                'reserved_at' => Carbon::now()->addMinutes(31),
                'origin' => 'EGCC',
                'destination' => 'EGLL',
            ]
        );

        $this->assertNull($this->allocator->allocate(NetworkAircraft::find('BAW123')));
    }

    public function testItDoesntAllocateReservedStandIfNotRightCallsign()
    {
        StandReservation::create(
            [
                'callsign' => 'BAW124',
                'stand_id' => 1,
                'reserved_at' => Carbon::now(),
                'origin' => 'EGCC',
                'destination' => 'EGLL',
            ]
        );

        $this->assertNull($this->allocator->allocate(NetworkAircraft::find('BAW123')));
    }

    public function testItDoesntAllocateReservedStandIfNotRightDestination()
    {
        StandReservation::create(
            [
                'callsign' => 'BAW123',
                'stand_id' => 1,
                'reserved_at' => Carbon::now(),
                'origin' => 'EGCC',
                'destination' => 'EGSS',
            ]
        );

        $this->assertNull($this->allocator->allocate(NetworkAircraft::find('BAW123')));
    }

    public function testItDoesntAllocateReservedStandIfNotRightOrigin()
    {
        StandReservation::create(
            [
                'callsign' => 'BAW123',
                'stand_id' => 1,
                'reserved_at' => Carbon::now(),
                'origin' => 'EGGP',
                'destination' => 'EGLL',
            ]
        );

        $this->assertNull($this->allocator->allocate(NetworkAircraft::find('BAW123')));
    }

    public function testItDoesntAllocateReservedStandIfAlreadyAssigned()
    {
        StandAssignment::create(
            [
                'callsign' => 'BAW456',
                'stand_id' => 1,
            ]
        );

        StandReservation::create(
            [
                'callsign' => 'BAW123',
                'stand_id' => 1,
                'reserved_at' => Carbon::now(),
                'origin' => 'EGCC',
                'destination' => 'EGLL',
            ]
        );

        $this->assertNull($this->allocator->allocate(NetworkAircraft::find('BAW123')));
    }
}
