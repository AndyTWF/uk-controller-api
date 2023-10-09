<?php

namespace App\Allocator\Stand;

use App\BaseFunctionalTestCase;
use App\Models\Aircraft\Aircraft;
use App\Models\Airfield\Airfield;
use App\Models\Airline\Airline;
use App\Models\Stand\Stand;
use App\Models\Stand\StandRequest;
use App\Models\Stand\StandReservation;
use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AirlineAircraftArrivalStandAllocatorTest extends BaseFunctionalTestCase
{
    private readonly AirlineAircraftArrivalStandAllocator $allocator;

    public function setUp(): void
    {
        parent::setUp();
        $this->allocator = $this->app->make(AirlineAircraftArrivalStandAllocator::class);
        Airline::factory()->create(['icao_code' => 'EZY']);
    }

    public function testItAllocatesAStandWithAnAircraftType()
    {
        DB::table('airline_stand')->insert(
            [
                [
                    'airline_id' => 1,
                    'stand_id' => 1,
                    'aircraft_id' => null
                ],
                [
                    'airline_id' => 1,
                    'stand_id' => 2,
                    'aircraft_id' => 1
                ],
                [
                    'airline_id' => 1,
                    'stand_id' => 3,
                    'aircraft_id' => null
                ],
                [
                    'airline_id' => 2,
                    'stand_id' => 1,
                    'aircraft_id' => 2
                ],
            ]
        );
        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertEquals(2, $this->allocator->allocate($aircraft));
    }

    public function testItConsidersAirlinePreferences()
    {
        DB::table('airline_stand')->insert(
            [
                [
                    'airline_id' => 1,
                    'stand_id' => 1,
                    'aircraft_id' => 1,
                    'priority' => 100,
                ],
                [
                    'airline_id' => 1,
                    'stand_id' => 2,
                    'aircraft_id' => 1,
                    'priority' => 3,
                ],
                [
                    'airline_id' => 1,
                    'stand_id' => 3,
                    'aircraft_id' => 1,
                    'priority' => 2,
                ],
                [
                    'airline_id' => 2,
                    'stand_id' => 1,
                    'aircraft_id' => 1,
                    'priority' => 1,
                ],
            ]
        );
        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertEquals(2, $this->allocator->allocate($aircraft));
    }

    public function testItAllocatesAStandWithAnAppropriateSize()
    {
        Aircraft::where('code', 'B738')->update(['aerodrome_reference_code' => 'E']);
        $weightAppropriateStand = Stand::create(
            [
                'airfield_id' => 1,
                'identifier' => '502',
                'latitude' => 54.65875500,
                'longitude' => -6.22258694,
                'aerodrome_reference_code' => 'E',
            ]
        );
        DB::table('airline_stand')->insert(
            [
                [
                    'airline_id' => 1,
                    'stand_id' => 1,
                    'aircraft_id' => null
                ],
                [
                    'airline_id' => 1,
                    'stand_id' => 2,
                    'aircraft_id' => 1
                ],
                [
                    'airline_id' => 1,
                    'stand_id' => 3,
                    'aircraft_id' => null
                ],
                [
                    'airline_id' => 2,
                    'stand_id' => 1,
                    'aircraft_id' => 1
                ],
                [
                    'airline_id' => 1,
                    'stand_id' => $weightAppropriateStand->id,
                    'aircraft_id' => 1
                ],
            ]
        );
        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertEquals($weightAppropriateStand->id, $this->allocator->allocate($aircraft));
    }

    public function testItAllocatesAStandInAerodromeReferenceAscendingOrder()
    {
        Aircraft::where('code', 'B738')->update(['aerodrome_reference_code' => 'B']);
        $weightAppropriateStand = Stand::create(
            [
                'airfield_id' => 1,
                'identifier' => '502',
                'latitude' => 54.65875500,
                'longitude' => -6.22258694,
                'aerodrome_reference_code' => 'B',
            ]
        );
        DB::table('airline_stand')->insert(
            [
                [
                    'airline_id' => 1,
                    'stand_id' => 1,
                    'aircraft_id' => null
                ],
                [
                    'airline_id' => 1,
                    'stand_id' => 2,
                    'aircraft_id' => 1
                ],
                [
                    'airline_id' => 1,
                    'stand_id' => 3,
                    'aircraft_id' => null
                ],
                [
                    'airline_id' => 2,
                    'stand_id' => 1,
                    'aircraft_id' => 1
                ],
                [
                    'airline_id' => 1,
                    'stand_id' => $weightAppropriateStand->id,
                    'aircraft_id' => 1
                ],
            ]
        );
        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertEquals($weightAppropriateStand->id, $this->allocator->allocate($aircraft));
    }

    public function testItDoesntAllocateOccupiedStands()
    {
        DB::table('airline_stand')->insert(
            [
                [
                    'airline_id' => 1,
                    'stand_id' => 1,
                    'aircraft_id' => 1
                ],
                [
                    'airline_id' => 1,
                    'stand_id' => 2,
                    'aircraft_id' => 1
                ],
                [
                    'airline_id' => 1,
                    'stand_id' => 3,
                    'aircraft_id' => null
                ],
                [
                    'airline_id' => 2,
                    'stand_id' => 1,
                    'aircraft_id' => 1
                ],
            ]
        );

        $occupier = $this->createAircraft('EZY7823', 'EGLL', 'EGGD');
        $occupier->occupiedStand()->sync([1]);
        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertEquals(2, $this->allocator->allocate($aircraft));
    }

    public function testItDoesntAllocateAStandWithNoAircraftType()
    {
        DB::table('airline_stand')->insert(
            [
                [
                    'airline_id' => 1,
                    'stand_id' => 2,
                    'aircraft_id' => null
                ],
                [
                    'airline_id' => 1,
                    'stand_id' => 1,
                    'aircraft_id' => null
                ],
                [
                    'airline_id' => 2,
                    'stand_id' => 1,
                    'aircraft_id' => 1
                ],
            ]
        );
        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertNull($this->allocator->allocate($aircraft));
    }

    public function testItDoesntAllocateAtTheWrongAirfield()
    {
        DB::table('airline_stand')->insert(
            [
                [
                    'airline_id' => 1,
                    'stand_id' => 3,
                    'aircraft_id' => 1
                ],
                [
                    'airline_id' => 2,
                    'stand_id' => 1,
                    'aircraft_id' => 1,
                ],
            ]
        );
        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertNull($this->allocator->allocate($aircraft));
    }

    public function testItDoesntAllocateForTheWrongAircraftType()
    {
        DB::table('airline_stand')->insert(
            [
                [
                    'airline_id' => 2,
                    'stand_id' => 1,
                    'aircraft_id' => 2,
                ],
            ]
        );
        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertNull($this->allocator->allocate($aircraft));
    }

    public function testItDoesntAllocateUnavailableStands()
    {
        DB::table('airline_stand')->insert(
            [
                [
                    'airline_id' => 1,
                    'stand_id' => 1,
                    'aircraft_id' => 1
                ],
                [
                    'airline_id' => 1,
                    'stand_id' => 2,
                    'aircraft_id' => 1
                ],
            ]
        );
        NetworkAircraft::find('BAW123')->occupiedStand()->sync([1]);

        $aircraft = $this->createAircraft('BAW23451', 'EGLL', 'EGGD');
        $this->assertEquals(2, $this->allocator->allocate($aircraft));
    }

    public function testItDoesntAllocateNonExistentAirlines()
    {
        DB::table('airline_stand')->insert(
            [
                [
                    'airline_id' => 1,
                    'stand_id' => 3,
                    'aircraft_id' => 1
                ],
                [
                    'airline_id' => 2,
                    'stand_id' => 1,
                    'aircraft_id' => 1
                ],
            ]
        );
        $aircraft = $this->createAircraft('***1234', 'EGLL', 'EGGD');
        $this->assertNull($this->allocator->allocate($aircraft));
    }

    public function testItDoesntAllocateUnknownAircraftTypes()
    {
        DB::table('airline_stand')->insert(
            [
                [
                    'airline_id' => 1,
                    'stand_id' => 3,
                    'aircraft_id' => 1
                ],
                [
                    'airline_id' => 2,
                    'stand_id' => 1,
                    'aircraft_id' => 1
                ],
            ]
        );
        $aircraft = $this->createAircraft('***1234', 'EGLL', 'EGGD', aircraftType: 'XXX');
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

        // Should be ranked first - it has the highest priority. It gets a stand reservation to make
        // sure it is ranked first even if it is occupied.
        $standA1 = Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'identifier' => 'A1',
            ]
        );
        $standA1->airlines()->sync([1 => ['aircraft_id' => 1, 'priority' => 100]]);
        StandReservation::create(
            [
                'stand_id' => $standA1->id,
                'start' => Carbon::now()->subMinutes(1),
                'end' => Carbon::now()->addMinutes(1),
            ]
        );

        // Should be ranked joint second, lower priority than A1. B1 has a request, to show that requests arent considered
        // when ranking.
        $standB1 = Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'identifier' => 'B1',
                'aerodrome_reference_code' => 'C'
            ]
        );
        StandRequest::factory()->create(['requested_time' => Carbon::now(), 'stand_id' => $standB1->id]);
        $standB2 = Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'identifier' => 'B2',
                'aerodrome_reference_code' => 'C'
            ]
        );
        $standB1->airlines()->sync([1 => ['aircraft_id' => 1, 'priority' => 101]]);
        $standB2->airlines()->sync([1 => ['aircraft_id' => 1, 'priority' => 101]]);

        // Should be ranked joint third, same priority as B1 and B2 but smaller stands
        $standC1 = Stand::factory()->create(['airfield_id' => $airfieldId, 'identifier' => 'C1']);
        $standC1->airlines()->sync([1 => ['aircraft_id' => 1, 'priority' => 101]]);
        $standC2 = Stand::factory()->create(['airfield_id' => $airfieldId, 'identifier' => 'C2']);
        $standC2->airlines()->sync([1 => ['aircraft_id' => 1, 'priority' => 101]]);

        // Should not appear in rankings - wrong airfield
        $standD1 = Stand::factory()->create(['airfield_id' => 2, 'identifier' => 'D1']);
        $standD1->airlines()->sync([1 => ['aircraft_id' => 1]]);

        // Should not appear in rankings - wrong aircraft type
        $standE1 = Stand::factory()->create(['airfield_id' => $airfieldId, 'identifier' => 'E1']);
        $standE1->airlines()->sync([1 => ['aircraft_id' => $cessna->id]]);

        // Should not appear in rankings - too small ARC
        $standF1 = Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'identifier' => 'F1',
                'aerodrome_reference_code' => 'A'
            ]
        );
        $standF1->airlines()->sync([1 => ['aircraft_id' => 1]]);

        // Should not appear in rankings - too small max aircraft size
        $standG1 = Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'identifier' => 'G1',
                'max_aircraft_id_length' => $cessna->id,
                'max_aircraft_id_wingspan' => $cessna->id
            ]
        );
        $standG1->airlines()->sync([1 => ['aircraft_id' => 1]]);

        // Should not appear in rankings - closed
        $standH1 = Stand::factory()->create(
            [
                'airfield_id' => $airfieldId,
                'identifier' => 'H1',
                'aerodrome_reference_code' => 'C',
                'closed_at' => Carbon::now(),
            ]
        );
        $standH1->airlines()->sync([1 => ['aircraft_id' => 1]]);

        $expectedRanks = [
            $standA1->id => 1,
            $standB1->id => 2,
            $standB2->id => 2,
            $standC1->id => 3,
            $standC2->id => 3,
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
