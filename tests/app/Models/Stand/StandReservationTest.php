<?php

namespace App\Models\Stand;

use App\BaseFunctionalTestCase;
use Carbon\Carbon;

class StandReservationTest extends BaseFunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        StandReservation::all()->each(function (StandReservation $standReservation) {
            $standReservation->delete();
        });
        Carbon::setTestNow(Carbon::now());
    }

    public function testItReturnsActiveReservations()
    {
        // Already ended
        StandReservation::create(
            [
                'stand_id' => 1,
                'reserved_at' => Carbon::now()->addMinutes(32),
            ]
        );

        // Just ended
        StandReservation::create(
            [
                'stand_id' => 1,
                'reserved_at' => Carbon::now()->addMinutes(31),
            ]
        );

        $aboutToEnd = StandReservation::create(
            [
                'stand_id' => 1,
                'reserved_at' => Carbon::now()->addMinutes(30),
            ]
        );
        $veryActive = StandReservation::create(
            [
                'stand_id' => 1,
                'reserved_at' => Carbon::now()->addMinutes(2),
            ]
        );
        $onlyJustStarted = StandReservation::create(
            [
                'stand_id' => 1,
                'reserved_at' => Carbon::now()->subMinutes(30),
            ]
        );

        // Not started
        StandReservation::create(
            [
                'stand_id' => 1,
                'reserved_at' => Carbon::now()->addMinutes(40),
            ]
        );

        $activeReservations = StandReservation::active()->orderBy('id')->pluck('id')->toArray();
        $this->assertEquals([$aboutToEnd->id, $veryActive->id, $onlyJustStarted->id], $activeReservations);
    }

    public function testItReturnsUpcomingReservations()
    {
        // Already started
        StandReservation::create(
            [
                'stand_id' => 1,
                'reserved_at' => Carbon::now()->subMinutes(10),
            ]
        );

        // Just started
        StandReservation::create(
            [
                'stand_id' => 1,
                'reserved_at' => Carbon::now(),
            ]
        );

        $aboutToStart = StandReservation::create(
            [
                'stand_id' => 1,
                'reserved_at' => Carbon::now()->addMinutes(2),
            ]
        );

        $littleWhileOff = StandReservation::create(
            [
                'stand_id' => 1,
                'reserved_at' => Carbon::now()->addMinutes(5),
            ]
        );

        $justInRange = StandReservation::create(
            [
                'stand_id' => 1,
                'reserved_at' => Carbon::now()->addMinutes(10),
            ]
        );

        // Too far off
        StandReservation::create(
            [
                'stand_id' => 1,
                'reserved_at' => Carbon::now()->addMinutes(10)->addSecond(),
            ]
        );

        $upcomingReservations = StandReservation::upcoming(Carbon::now()->addMinutes(10))->pluck('id')->toArray();
        $this->assertEquals([$aboutToStart->id, $littleWhileOff->id, $justInRange->id], $upcomingReservations);
    }
}
