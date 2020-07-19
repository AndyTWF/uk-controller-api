<?php

namespace App\Listeners\GroundStatus;

use App\BaseFunctionalTestCase;
use App\Events\GroundStatusUnassignedEvent;
use App\Events\NetworkAircraftDisconnectedEvent;
use App\Models\Vatsim\NetworkAircraft;

class UnassignGroundStatusOnDisconnectTest extends BaseFunctionalTestCase
{
    const CALLSIGN = 'BAW123';

    /**
     * @var UnassignGroundStatusOnDisconnect
     */
    private $listener;

    public function setUp(): void
    {
        parent::setUp();
        $this->listener = new UnassignGroundStatusOnDisconnect();
    }

    public function testItFiresEventIfGroundStatusSet()
    {
        NetworkAircraft::find(self::CALLSIGN)->groundStatus()->sync([1]);
        $this->expectsEvents(GroundStatusUnassignedEvent::class);
        $this->assertTrue(
            $this->listener->handle(
                new NetworkAircraftDisconnectedEvent(NetworkAircraft::find(self::CALLSIGN))
            )
        );
    }

    public function testItDoesntFireEventIfNoGroundStatusSet()
    {
        $this->doesntExpectEvents(GroundStatusUnassignedEvent::class);
        $this->assertTrue(
            $this->listener->handle(
                new NetworkAircraftDisconnectedEvent(NetworkAircraft::find(self::CALLSIGN))
            )
        );
    }
}
