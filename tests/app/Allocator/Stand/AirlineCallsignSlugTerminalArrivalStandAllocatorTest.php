<?php

namespace App\Allocator\Stand;

use App\BaseFunctionalTestCase;
use App\Models\Aircraft\Aircraft;
use App\Models\Airfield\Airfield;
use App\Models\Airfield\Terminal;
use App\Models\Airline\Airline;
use App\Models\Stand\Stand;
use App\Models\Stand\StandRequest;
use App\Models\Stand\StandReservation;
use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AirlineCallsignSlugTerminalArrivalStandAllocatorTest extends BaseFunctionalTestCase
{
    private readonly AirlineCallsignSlugTerminalArrivalStandAllocator $allocator;

    public function setUp(): void
    {
        parent::setUp();
        $this->allocator = $this->app->make(AirlineCallsignSlugTerminalArrivalStandAllocator::class);
        Airline::factory()->create(['icao_code' => 'EZY']);
    }

    public function testItAllocatesAStandWithAFixedCallsignSlug()
    {
        $terminal1 = Terminal::factory()->create(['airfield_id' => 1]);
        $stand1 = Stand::factory()->withTerminal($terminal1)->create(['airfield_id' => 1, 'identifier' => '1A']);

        // Wrong airfield
        $terminal2 = Terminal::factory()->create(['airfield_id' => 2]);
        Stand::factory()->withTerminal($terminal2)->create(['airfield_id' => 2, 'identifier' => '1A']);

        // This shouldn't get picked, it's not terminal this airline has
        $terminal3 = Terminal::factory()->create(['airfield_id' => 1]);
        Stand::factory()->withTerminal($terminal3)->create(['airfield_id' => 1, 'identifier' => '1B']);

        // This shouldn't get picked, not at a terminal!
        Stand::factory()->create(['airfield_id' => 1, 'identifier' => '1C']);

        DB::table('airline_terminal')->insert(
            [
                // Not picked, null callsign slug
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal1->id,
                    'callsign_slug' => null
                ],
                // Will be picked, matches callsign slug
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal1->id,
                    'callsign_slug' => '23451'
                ],
                // Not picked, wrong airport
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal2->id,
                    'callsign_slug' => '23451'
                ],
                // Not picked, wrong airline
                [
                    'airline_id' => 2,
                    'terminal_id' => $terminal1->id,
                    'callsign_slug' => '23451'
                ],
            ]
        );

        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertEquals($stand1->id, $this->allocator->allocate($aircraft));
    }

    public function testItConsidersAirlinePreferences()
    {
        // Not highest priority
        $terminal1 = Terminal::factory()->create(['airfield_id' => 1]);
        Stand::factory()->withTerminal($terminal1)->create(['airfield_id' => 1, 'identifier' => '1A']);

        // Not highest priority
        $terminal2 = Terminal::factory()->create(['airfield_id' => 1]);
        Stand::factory()->withTerminal($terminal2)->create(['airfield_id' => 1, 'identifier' => '1B']);

        // Right airfield, highest priority, should be picked
        $terminal3 = Terminal::factory()->create(['airfield_id' => 1]);
        $stand3 = Stand::factory()->withTerminal($terminal3)->create(['airfield_id' => 1, 'identifier' => '1C']);

        // Wrong airfield, should not be picked
        $terminal4 = Terminal::factory()->create(['airfield_id' => 2]);
        Stand::factory()->withTerminal($terminal4)->create(['airfield_id' => 2, 'identifier' => '1A']);

        DB::table('airline_terminal')->insert(
            [
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal1->id,
                    'callsign_slug' => '23451',
                    'priority' => 100,
                ],
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal2->id,
                    'callsign_slug' => '23451',
                    'priority' => 3,
                ],
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal3->id,
                    'callsign_slug' => '23451',
                    'priority' => 2,
                ],
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal3->id,
                    'callsign_slug' => '23451',
                    'priority' => 2,
                ],
            ]
        );

        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertEquals($stand3->id, $this->allocator->allocate($aircraft));
    }

    public function testItAllocatesAStandWithAnAppropriateAerodromeReferenceCode()
    {
        $terminal = Terminal::factory()->create(['airfield_id' => 1]);
        Aircraft::where('code', 'B738')->update(['aerodrome_reference_code' => 'E']);
        $weightAppropriateStand = Stand::create(
            [
                'airfield_id' => 1,
                'terminal_id' => $terminal->id,
                'identifier' => '502',
                'latitude' => 54.65875500,
                'longitude' => -6.22258694,
                'aerodrome_reference_code' => 'E',
            ]
        );

        // Too small, should not get picked
        Stand::create(
            [
                'airfield_id' => 1,
                'terminal_id' => $terminal->id,
                'identifier' => '503',
                'latitude' => 54.65875500,
                'longitude' => -6.22258694,
                'aerodrome_reference_code' => 'B',
            ]
        );

        DB::table('airline_terminal')->insert(
            [
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal->id,
                    'callsign_slug' => '23451'
                ],
            ]
        );
        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertEquals($weightAppropriateStand->id, $this->allocator->allocate($aircraft));
    }

    public function testItAllocatesAStandInAerodromeReferenceAscendingOrder()
    {
        $terminal = Terminal::factory()->create(['airfield_id' => 1]);
        Aircraft::where('code', 'B738')->update(['aerodrome_reference_code' => 'B']);
        $weightAppropriateStand = Stand::create(
            [
                'airfield_id' => 1,
                'terminal_id' => $terminal->id,
                'identifier' => '502',
                'latitude' => 54.65875500,
                'longitude' => -6.22258694,
                'aerodrome_reference_code' => 'B',
            ]
        );

        // Larger stand, should be ignored
        Stand::create(
            [
                'airfield_id' => 1,
                'terminal_id' => $terminal->id,
                'identifier' => '503',
                'latitude' => 54.65875500,
                'longitude' => -6.22258694,
                'aerodrome_reference_code' => 'E',
            ]
        );

        DB::table('airline_terminal')->insert(
            [
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal->id,
                    'callsign_slug' => '23451'
                ],
            ]
        );

        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertEquals($weightAppropriateStand->id, $this->allocator->allocate($aircraft));
    }

    public function testItAllocatesSingleCharacterMatches()
    {
        $terminal1 = Terminal::factory()->create(['airfield_id' => 1]);
        $stand1 = Stand::factory()->withTerminal($terminal1)->create();

        // No slug
        $terminal2 = Terminal::factory()->create(['airfield_id' => 1]);
        Stand::factory()->withTerminal($terminal2)->create();

        DB::table('airline_terminal')->insert(
            [
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal2->id,
                    'callsign_slug' => null
                ],
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal1->id,
                    'callsign_slug' => '2'
                ],
            ]
        );

        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertEquals($stand1->id, $this->allocator->allocate($aircraft));
    }

    public function testItPrefersDoubleCharacterMatches()
    {
        $terminal1 = Terminal::factory()->create(['airfield_id' => 1]);
        $stand1 = Stand::factory()->withTerminal($terminal1)->create();

        // No slug
        $terminal2 = Terminal::factory()->create(['airfield_id' => 1]);
        Stand::factory()->withTerminal($terminal2)->create();

        // Only single character
        $terminal3 = Terminal::factory()->create(['airfield_id' => 1]);
        Stand::factory()->withTerminal($terminal3)->create();

        DB::table('airline_terminal')->insert(
            [
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal2->id,
                    'callsign_slug' => null
                ],
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal3->id,
                    'callsign_slug' => '2'
                ],
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal1->id,
                    'callsign_slug' => '23'
                ],
            ]
        );

        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertEquals($stand1->id, $this->allocator->allocate($aircraft));
    }

    public function testItPrefersTripleCharacterMatches()
    {
        $terminal1 = Terminal::factory()->create(['airfield_id' => 1]);
        $stand1 = Stand::factory()->withTerminal($terminal1)->create();

        // No slug
        $terminal2 = Terminal::factory()->create(['airfield_id' => 1]);
        Stand::factory()->withTerminal($terminal2)->create();

        // Only single character
        $terminal3 = Terminal::factory()->create(['airfield_id' => 1]);
        Stand::factory()->withTerminal($terminal3)->create();

        // Only double character
        $terminal4 = Terminal::factory()->create(['airfield_id' => 1]);
        Stand::factory()->withTerminal($terminal4)->create();

        DB::table('airline_terminal')->insert(
            [
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal2->id,
                    'callsign_slug' => null
                ],
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal3->id,
                    'callsign_slug' => '2'
                ],
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal4->id,
                    'callsign_slug' => '23'
                ],
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal1->id,
                    'callsign_slug' => '234'
                ],
            ]
        );

        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertEquals($stand1->id, $this->allocator->allocate($aircraft));
    }

    public function testItPrefersFullMatches()
    {
        $terminal1 = Terminal::factory()->create(['airfield_id' => 1]);
        $stand1 = Stand::factory()->withTerminal($terminal1)->create();

        // No slug
        $terminal2 = Terminal::factory()->create(['airfield_id' => 1]);
        Stand::factory()->withTerminal($terminal2)->create();

        // Only single character
        $terminal3 = Terminal::factory()->create(['airfield_id' => 1]);
        Stand::factory()->withTerminal($terminal3)->create();

        // Only double character
        $terminal4 = Terminal::factory()->create(['airfield_id' => 1]);
        Stand::factory()->withTerminal($terminal4)->create();

        // Only 4 chars
        $terminal5 = Terminal::factory()->create(['airfield_id' => 1]);
        Stand::factory()->withTerminal($terminal5)->create();

        DB::table('airline_terminal')->insert(
            [
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal2->id,
                    'callsign_slug' => null
                ],
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal3->id,
                    'callsign_slug' => '2'
                ],
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal4->id,
                    'callsign_slug' => '23'
                ],
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal5->id,
                    'callsign_slug' => '2345'
                ],
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal1->id,
                    'callsign_slug' => '23451'
                ],
            ]
        );

        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertEquals($stand1->id, $this->allocator->allocate($aircraft));
    }

    public function testItDoesntAllocateOccupiedStands()
    {
        $terminal1 = Terminal::factory()->create(['airfield_id' => 1]);
        $stand1 = Stand::factory()->withTerminal($terminal1)->create();

        // Occupied
        $stand2 = Stand::factory()->withTerminal($terminal1)->create();
        $occupier = $this->createAircraft('EZY7823', 'EGLL', 'EGGD');
        $occupier->occupiedStand()->sync([$stand2->id]);

        DB::table('airline_terminal')->insert(
            [
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal1->id,
                    'callsign_slug' => '23451'
                ],
            ]
        );

        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertEquals($stand1->id, $this->allocator->allocate($aircraft));
    }

    public function testItDoesntAllocateAStandWithNoSlugh()
    {
        $terminal1 = Terminal::factory()->create(['airfield_id' => 1]);
        Stand::factory()->withTerminal($terminal1)->create();

        // Occupied
        $stand2 = Stand::factory()->withTerminal($terminal1)->create();
        $occupier = $this->createAircraft('EZY7823', 'EGLL', 'EGGD');
        $occupier->occupiedStand()->sync([$stand2->id]);

        DB::table('airline_terminal')->insert(
            [
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal1->id,
                    'callsign_slug' => null
                ],
                [
                    'airline_id' => 2,
                    'terminal_id' => $terminal1->id,
                    'callsign_slug' => '23451'
                ],
            ]
        );

        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertNull($this->allocator->allocate($aircraft));
    }

    public function testItDoesntAllocateAtTheWrongAirfield()
    {
        $terminal1 = Terminal::factory()->create(['airfield_id' => 2]);
        Stand::factory()->withTerminal($terminal1)->create();

        DB::table('airline_terminal')->insert(
            [
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal1->id,
                    'callsign_slug' => '23451'
                ],
            ]
        );

        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertNull($this->allocator->allocate($aircraft));
    }

    public function testItDoesntAllocateForTheWrongCallsign()
    {
        $terminal1 = Terminal::factory()->create(['airfield_id' => 1]);
        Stand::factory()->withTerminal($terminal1)->create();

        DB::table('airline_terminal')->insert(
            [
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal1->id,
                    'callsign_slug' => '5'
                ],
            ]
        );

        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertNull($this->allocator->allocate($aircraft));
    }

    public function testItDoesntAllocateUnavailableStands()
    {
        $terminal1 = Terminal::factory()->create(['airfield_id' => 1]);
        $stand1 = Stand::factory()->withTerminal($terminal1)->create();
        $stand2 = Stand::factory()->withTerminal($terminal1)->create();
        NetworkAircraft::find('BAW123')->occupiedStand()->sync([$stand1->id]);

        DB::table('airline_terminal')->insert(
            [
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal1->id,
                    'callsign_slug' => '23'
                ],
            ]
        );

        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertEquals($stand2->id, $this->allocator->allocate($aircraft));
    }

    public function testItDoesntAllocateNonExistentAirlines()
    {
        $terminal1 = Terminal::factory()->create(['airfield_id' => 1]);
        Stand::factory()->withTerminal($terminal1)->create();

        DB::table('airline_terminal')->insert(
            [
                [
                    'airline_id' => 1,
                    'terminal_id' => $terminal1->id,
                    'callsign_slug' => '23451'
                ],
                [
                    'airline_id' => 2,
                    'terminal_id' => $terminal1->id,
                    'callsign_slug' => '23451'
                ],
            ]
        );
        $aircraft = $this->createAircraft('***1234', 'EGLL', 'EGGD');
        $this->assertNull($this->allocator->allocate($aircraft));
    }

    public function testItDoesntRankStandsIfUnknownAircraftType()
    {
        $aircraft = $this->newAircraft('BAW23451', 'EGLL', 'EGGD', aircraftType: 'XXX');
        $this->assertEquals(collect(), $this->allocator->getRankedStandAllocation($aircraft));
    }

    public function testItDoesntRankStandsIfUnknownAirline()
    {
        $aircraft = $this->newAircraft('***1234', 'EGLL', 'EGGD');
        $this->assertEquals(collect(), $this->allocator->getRankedStandAllocation($aircraft));
    }

    public function testItGetsRankedStandAllocation()
    {
        // Create an airfield that we dont have so we know its a clean test
        $airfield = Airfield::factory()->create(['code' => 'EXXX']);
        $airfieldId = $airfield->id;

        // Create a small aircraft type to test stand size ranking
        $cessna = Aircraft::create(
            [
                'code' => 'C172',
                'allocate_stands' => true,
                'aerodrome_reference_code' => 'A',
                'wingspan' => 1,
                'length' => 12,
            ]
        );

        // Should be ranked first - it has the highest priority. Both stands on the terminal should be
        // included. Stand A1 gets a reservation and a request so that we show its not considered.
        $terminalA1 = Terminal::factory()->create(['airfield_id' => $airfieldId]);
        $terminalA1->airlines()->sync([1 => ['callsign_slug' => '23451', 'priority' => 100]]);
        $standA1 = Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'terminal_id' => $terminalA1->id,
                'identifier' => 'A1',
            ]
        );
        $standA2 = Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'terminal_id' => $terminalA1->id,
                'identifier' => 'A2',
            ]
        );
        StandReservation::create(
            [
                'stand_id' => $standA1->id,
                'start' => Carbon::now()->subMinutes(1),
                'end' => Carbon::now()->addMinutes(1),
            ]
        );
        StandRequest::factory()->create(['requested_time' => Carbon::now(), 'stand_id' => $standA1->id]);

        // Should be ranked joint second, lower priority than A1.
        $terminalB1 = Terminal::factory()->create(['airfield_id' => $airfieldId]);
        $terminalB1->airlines()->sync([1 => ['callsign_slug' => '23451', 'priority' => 101]]);
        $standB1 = Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'terminal_id' => $terminalB1->id,
                'identifier' => 'B1',
                'aerodrome_reference_code' => 'C'
            ]
        );

        $terminalB2 = Terminal::factory()->create(['airfield_id' => $airfieldId]);
        $terminalB2->airlines()->sync([1 => ['callsign_slug' => '23451', 'priority' => 101]]);
        $standB2 = Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'terminal_id' => $terminalB2->id,
                'identifier' => 'B2',
                'aerodrome_reference_code' => 'C'
            ]
        );

        // Should be ranked joint third, same priority as B1 and B2 but smaller stands
        $terminalC1 = Terminal::factory()->create(['airfield_id' => $airfieldId]);
        $terminalC1->airlines()->sync([1 => ['callsign_slug' => '23451', 'priority' => 101]]);
        $standC1 = Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'identifier' => 'C1',
                'terminal_id' => $terminalC1->id,
            ]
        );

        $terminalC2 = Terminal::factory()->create(['airfield_id' => $airfieldId]);
        $terminalC2->airlines()->sync([1 => ['callsign_slug' => '23451', 'priority' => 101]]);
        $standC2 = Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'identifier' => 'C2',
                'terminal_id' => $terminalC2->id,
            ]
        );

        // Should be ranked 4th, 5th, 6th, 7th, less specific callsign slugs
        $terminalC3 = Terminal::factory()->create(['airfield_id' => $airfieldId]);
        $terminalC3->airlines()->sync([1 => ['callsign_slug' => '2345', 'priority' => 101]]);
        $standC3 = Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'identifier' => 'C3',
                'terminal_id' => $terminalC3->id,
            ]
        );

        $terminalC4 = Terminal::factory()->create(['airfield_id' => $airfieldId]);
        $terminalC4->airlines()->sync([1 => ['callsign_slug' => '234', 'priority' => 101]]);
        $standC4 = Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'identifier' => 'C4',
                'terminal_id' => $terminalC4->id,
            ]
        );

        $terminalC5 = Terminal::factory()->create(['airfield_id' => $airfieldId]);
        $terminalC5->airlines()->sync([1 => ['callsign_slug' => '23', 'priority' => 101]]);
        $standC5 = Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'identifier' => 'C5',
                'terminal_id' => $terminalC5->id,
            ]
        );

        $terminalC6 = Terminal::factory()->create(['airfield_id' => $airfieldId]);
        $terminalC6->airlines()->sync([1 => ['callsign_slug' => '2', 'priority' => 101]]);
        $standC6 = Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'identifier' => 'C6',
                'terminal_id' => $terminalC6->id,
            ]
        );

        // Should not appear in rankings - wrong airfield
        Terminal::find(1)->airlines()->sync([1 => ['callsign_slug' => '23451', 'priority' => 101]]);
        Stand::factory()->create(
            [
                'airfield_id' => 1,
                'identifier' => 'D1',
                'terminal_id' => 1
            ]
        );

        // Should not appear in rankings - wrong terminal
        $terminalD2 = Terminal::factory()->create(['airfield_id' => $airfieldId]);
        Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'identifier' => 'D1',
                'terminal_id' => $terminalD2->id
            ]
        );

        // Should not appear in rankings - wrong callsign_slug
        $terminalE1 = Terminal::factory()->create(['airfield_id' => $airfieldId]);
        $terminalE1->airlines()->sync([1 => ['callsign_slug' => 'xxxx']]);
        Stand::factory()->create(['airfield_id' => $airfieldId, 'identifier' => 'E1']);

        // Should not appear in rankings - no wrong callsign_slug
        $terminalE2 = Terminal::factory()->create(['airfield_id' => $airfieldId]);
        $terminalE2->airlines()->sync([1]);
        Stand::factory()->create(['airfield_id' => $airfieldId, 'identifier' => 'E2']);

        // Should not appear in rankings - too small ARC
        $terminalF1 = Terminal::factory()->create(['airfield_id' => $airfieldId]);
        $terminalF1->airlines()->sync([1 => ['callsign_slug' => '23451']]);
        Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'identifier' => 'F1',
                'aerodrome_reference_code' => 'A'
            ]
        );

        // Should not appear in rankings - too small max aircraft size
        $terminalG1 = Terminal::factory()->create(['airfield_id' => $airfieldId]);
        $terminalG1->airlines()->sync([1 => ['callsign_slug' => '23451']]);
        Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'identifier' => 'G1',
                'max_aircraft_id_length' => $cessna->id,
                'max_aircraft_id_wingspan' => $cessna->id
            ]
        );


        // Should not appear in rankings - closed
        $terminalH1 = Terminal::factory()->create(['airfield_id' => $airfieldId]);
        $terminalH1->airlines()->sync([1 => ['callsign_slug' => '23451']]);
        Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'identifier' => 'H1',
                'closed_at' => Carbon::now()
            ]
        );


        $expectedRanks = [
            $standA1->id => 1,
            $standA2->id => 1,
            $standB1->id => 2,
            $standB2->id => 2,
            $standC1->id => 3,
            $standC2->id => 3,
            $standC3->id => 4,
            $standC4->id => 5,
            $standC5->id => 6,
            $standC6->id => 7,
        ];

        $actualRanks = $this->allocator->getRankedStandAllocation(
            $this->newAircraft('BAW23451', $airfield->code, 'EGGD')
        )->mapWithKeys(
                fn($stand) => [$stand->id => $stand->rank]
            )
            ->toArray();

        $this->assertEquals($expectedRanks, $actualRanks);
    }

    private function createAircraft(
        string $callsign,
        string $arrivalAirport,
        string $departureAirport,
        string $aircraftType = 'B738'
    ): NetworkAircraft {
        return tap(
            $this->newAircraft($callsign, $arrivalAirport, $departureAirport, $aircraftType),
            fn(NetworkAircraft $aircraft) => $aircraft->save()
        );
    }

    private function newAircraft(
        string $callsign,
        string $arrivalAirport,
        string $departureAirport,
        string $aircraftType = 'B738'
    ): NetworkAircraft {
        return new NetworkAircraft(
            [
                'callsign' => $callsign,
                'cid' => 1234,
                'planned_aircraft' => $aircraftType,
                'planned_aircraft_short' => $aircraftType,
                'planned_destairport' => $arrivalAirport,
                'planned_depairport' => $departureAirport,
                'aircraft_id' => $aircraftType === 'B738' ? 1 : null,
                'airline_id' => match ($callsign) {
                    'BAW23451' => 1,
                    'EZY7823' => Airline::where('icao_code', 'EZY')->first()->id,
                    default => null,
                },
            ]
        );
    }
}
