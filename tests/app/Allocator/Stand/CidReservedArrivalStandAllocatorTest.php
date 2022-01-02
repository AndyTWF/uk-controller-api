<?php

namespace App\Allocator\Stand;

use App\BaseFunctionalTestCase;
use App\Models\Stand\StandAssignment;
use App\Models\Stand\StandReservation;
use App\Models\Vatsim\NetworkAircraft;
use Carbon\Carbon;

class CidReservedArrivalStandAllocatorTest extends BaseFunctionalTestCase
{
    private CidReservedArrivalStandAllocator $allocator;

    public function setUp(): void
    {
        parent::setUp();
        $this->allocator = $this->app->make(CidReservedArrivalStandAllocator::class);
        NetworkAircraft::find('BAW123')->update(['cid' => self::ACTIVE_USER_CID]);
    }

    public function testItAllocatesReservedStandIfActive()
    {
        StandReservation::create(
            [
                'callsign' => 'BAW123',
                'cid' => self::ACTIVE_USER_CID,
                'stand_id' => 1,
                'start' => Carbon::now()->subMinute(),
                'end' => Carbon::now()->addHour(),
                'origin' => 'EGSS',
                'destination' => 'EGLL',
            ]
        );

        $actual = $this->allocator->allocate(NetworkAircraft::find('BAW123'));
        $expected = StandAssignment::where('callsign', 'BAW123')->first();

        $this->assertEquals($actual->stand_id, 1);
        $this->assertEquals($actual->stand_id, $expected->stand_id);
    }

    public function testItAllocatesReservedStandIfActiveOnDifferentCallsign()
    {
        StandReservation::create(
            [
                'callsign' => 'BAW123A',
                'cid' => self::ACTIVE_USER_CID,
                'stand_id' => 1,
                'start' => Carbon::now()->subMinute(),
                'end' => Carbon::now()->addHour(),
                'origin' => 'EGSS',
                'destination' => 'EGLL',
            ]
        );

        $actual = $this->allocator->allocate(NetworkAircraft::find('BAW123'));
        $expected = StandAssignment::where('callsign', 'BAW123')->first();

        $this->assertEquals($actual->stand_id, 1);
        $this->assertEquals($actual->stand_id, $expected->stand_id);
    }

    public function testItDoesntAllocateReservedStandIfNotActive()
    {
        StandReservation::create(
            [
                'callsign' => 'BAW123',
                'cid' => self::ACTIVE_USER_CID,
                'stand_id' => 1,
                'start' => Carbon::now()->addMinute(),
                'end' => Carbon::now()->addHour(),
                'origin' => 'EGSS',
                'destination' => 'EGLL',
            ]
        );

        $this->assertNull($this->allocator->allocate(NetworkAircraft::find('BAW123')));
    }

    public function testItDoesntAllocateReservedStandIfNotRightCid()
    {
        StandReservation::create(
            [
                'callsign' => 'BAW123',
                'cid' => self::BANNED_USER_CID,
                'stand_id' => 1,
                'start' => Carbon::now()->subMinute(),
                'end' => Carbon::now()->addHour(),
                'origin' => 'EGSS',
                'destination' => 'EGLL',
            ]
        );

        $this->assertNull($this->allocator->allocate(NetworkAircraft::find('BAW123')));
    }

    public function testItDoesntAllocateReservedStandIfNotFree()
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
                'start' => Carbon::now()->subMinute(),
                'end' => Carbon::now()->addHour(),
                'origin' => 'EGSS',
                'destination' => 'EGLL',
            ]
        );

        $this->assertNull($this->allocator->allocate(NetworkAircraft::find('BAW123')));
    }
}
