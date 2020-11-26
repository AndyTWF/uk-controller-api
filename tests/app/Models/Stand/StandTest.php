<?php

namespace App\Models\Stand;

use App\BaseFunctionalTestCase;
use App\Models\Aircraft\Aircraft;
use App\Models\Airline\Airline;
use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Support\Facades\DB;

class StandTest extends BaseFunctionalTestCase
{
    public function testUnoccupiedOnlyReturnsFreeStands()
    {
        // Create a stand pairing between stand 2 and stand 3, and occupy it.
        Stand::find(2)->pairedStands()->sync([3]);
        Stand::find(3)->pairedStands()->sync([2]);

        NetworkAircraft::find('BAW123')->occupiedStand()->sync([2]);
        $stands = Stand::unoccupied()->get()->pluck('id')->toArray();
        $this->assertEquals([1], $stands);
    }

    public function testUnassignedOnlyReturnsUnassignedStands()
    {
        // Create a stand pairing between stand 2 and stand 3, and assign it.
        Stand::find(2)->pairedStands()->sync([3]);
        Stand::find(3)->pairedStands()->sync([2]);

        StandAssignment::create(['callsign' => 'BAW123', 'stand_id' => 2]);

        $stands = Stand::unassigned()->get()->pluck('id')->toArray();
        $this->assertEquals([1], $stands);
    }

    public function testAvailableOnlyReturnsUnassignedUnoccupiedStands()
    {
        NetworkAircraft::find('BAW123')->occupiedStand()->sync([1]);
        StandAssignment::create(['callsign' => 'BAW123', 'stand_id' => 2]);

        $stands = Stand::available()->get()->pluck('id')->toArray();
        $this->assertEquals([3], $stands);
    }

    public function testAirlineDestinationOnlyReturnsStandsForTheCorrectDestinations()
    {
        DB::table('airline_stand')->insert(
            [
                [
                    'airline_id' => 1,
                    'stand_id' => 1,
                    'destination' => 'EGGD'
                ],
                [
                    'airline_id' => 1,
                    'stand_id' => 2,
                    'destination' => 'EGFF'
                ],
                [
                    'airline_id' => 2,
                    'stand_id' => 1,
                    'destination' => 'EGGD'
                ],
            ]
        );

        $stands = Stand::airlineDestination(
            Airline::find(1),
            ['EGGD']
        )->get()->pluck('id')->toArray();
        $this->assertEquals([1], $stands);
    }

    public function testAppropriateDimensionsOnlyReturnsStandsThatAreTheRightSize()
    {
        $a330 = Aircraft::where('code', 'A333')->first();
        $b738 = Aircraft::where('code', 'B738')->first();
        Stand::find(1)->update(['max_aircraft_id' => $a330->id, 'wake_category_id' => 5]);
        Stand::find(2)->update(['max_aircraft_id' => $b738->id, 'wake_category_id' => 5]);
        Stand::find(3)->update(['max_aircraft_id' => $a330->id, 'wake_category_id' => 5]);

        $stands = Stand::appropriateDimensions($a330)->get()->pluck('id')->toArray();

        $this->assertEquals([1, 3], $stands);
    }

    public function testAppropriateDimensionsReturnsStandsWithNoMaxSize()
    {
        $a330 = Aircraft::where('code', 'A333')->first();
        $stands = Stand::appropriateDimensions($a330)->get()->pluck('id')->toArray();
        $this->assertEquals([1, 2, 3], $stands);
    }

    public function testAppropriateDimensionsRejectsStandsThatArentDeepEnough()
    {
        $a330 = Aircraft::where('code', 'A333')->first();
        $b738 = Aircraft::where('code', 'B738')->first();
        $b738->update(['wingspan' => 999.99]);

        Stand::find(1)->update(['max_aircraft_id' => $a330->id, 'wake_category_id' => 5]);
        Stand::find(2)->update(['max_aircraft_id' => $b738->id, 'wake_category_id' => 5]);
        Stand::find(3)->update(['max_aircraft_id' => $a330->id, 'wake_category_id' => 5]);

        $stands = Stand::appropriateDimensions($a330)->get()->pluck('id')->toArray();

        $this->assertEquals([1, 3], $stands);
    }

    public function testAppropriateDimensionsRejectsStandsThatArentWideEnough()
    {
        $a330 = Aircraft::where('code', 'A333')->first();
        $b738 = Aircraft::where('code', 'B738')->first();
        $b738->update(['length' => 999.99]);

        Stand::find(1)->update(['max_aircraft_id' => $a330->id, 'wake_category_id' => 5]);
        Stand::find(2)->update(['max_aircraft_id' => $b738->id, 'wake_category_id' => 5]);
        Stand::find(3)->update(['max_aircraft_id' => $a330->id, 'wake_category_id' => 5]);

        $stands = Stand::appropriateDimensions($a330)->get()->pluck('id')->toArray();

        $this->assertEquals([1, 3], $stands);
    }

    public function testAppropriateWakeCategoryOnlyReturnsLargeEnoughStands()
    {
        $a330 = Aircraft::where('code', 'A333')->first();
        Stand::find(1)->update(['wake_category_id' => 6]);
        Stand::find(2)->update(['wake_category_id' => 4]);
        Stand::find(3)->update(['wake_category_id' => 5]);

        $stands = Stand::appropriateWakeCategory($a330)->get()->pluck('id')->toArray();

        $this->assertEquals([1, 3], $stands);
    }

    public function testSizeAppropriateOnlyReturnsStandsThatAreBigEnough()
    {
        $a330 = Aircraft::where('code', 'A333')->first();

        // Make this stand too small wake-wise
        Stand::find(2)->update(['wake_category_id' => 4]);

        // Make this stand too small by max aircraft size
        $b738 = Aircraft::where('code', 'B738')->first();
        $b738->update(['length' => 999.99]);

        // Make this stand allowable
        Stand::find(3)->update(['wake_category_id' => 5]);

        $stands = Stand::sizeAppropriate($a330)->get()->pluck('id')->toArray();

        $this->assertEquals([3], $stands);
    }
}
