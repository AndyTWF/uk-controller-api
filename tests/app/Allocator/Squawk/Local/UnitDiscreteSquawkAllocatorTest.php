<?php

namespace App\Allocator\Squawk\Local;

use App\Allocator\Squawk\SquawkAssignmentCategories;
use App\BaseFunctionalTestCase;
use App\Models\Squawk\UnitDiscrete\UnitDiscreteSquawkAssignment;
use App\Models\Squawk\UnitDiscrete\UnitDiscreteSquawkRange;
use App\Models\Squawk\UnitDiscrete\UnitDiscreteSquawkRangeGuest;
use App\Models\Squawk\UnitDiscrete\UnitDiscreteSquawkRangeRule;
use App\Models\Vatsim\NetworkAircraft;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UnitDiscreteSquawkAllocatorTest extends BaseFunctionalTestCase
{
    /**
     * @var UnitDiscreteSquawkAllocator
     */
    private $allocator;

    public function setUp(): void
    {
        parent::setUp();
        $this->allocator = new UnitDiscreteSquawkAllocator();

        $this->createSquawkRange('EGGD', '7201', '7210');
    }

    public function testItAllocatesFirstFreeSquawkInRange()
    {
        $this->createSquawkAssignment('VIR25F', 'EGGD', '7201');
        $this->createSquawkAssignment('BAW92A', 'EGGD', '7202');

        $this->assertEquals('7203', $this->allocator->allocate('BMI11A', ['unit' => 'EGGD'])->getCode());
        $this->assertDatabaseHas(
            'unit_discrete_squawk_assignments',
            [
                'callsign' => 'BMI11A',
                'unit' => 'EGGD',
                'code' => '7203',
                'created_at' => Carbon::now(),
            ]
        );
    }

    public function testItReducesUnitToBaseForm()
    {
        $this->createSquawkAssignment('VIR25F', 'EGGD', '7201');
        $this->createSquawkAssignment('BAW92A', 'EGGD', '7202');

        $this->assertEquals('7203', $this->allocator->allocate('BMI11A', ['unit' => 'EGGD_APP'])->getCode());
        $this->assertDatabaseHas(
            'unit_discrete_squawk_assignments',
            [
                'callsign' => 'BMI11A',
                'unit' => 'EGGD',
                'code' => '7203',
                'created_at' => Carbon::now(),
            ]
        );
    }

    public function testItIncludesGuestRanges()
    {
        UnitDiscreteSquawkRangeGuest::create(
            [
                'primary_unit' => 'EGGD',
                'guest_unit' => 'EGFF',
            ]
        );
        $this->assertEquals('7201', $this->allocator->allocate('BMI11A', ['unit' => 'EGFF'])->getCode());
        $this->assertDatabaseHas(
            'unit_discrete_squawk_assignments',
            [
                'callsign' => 'BMI11A',
                'unit' => 'EGGD',
                'code' => '7201',
                'created_at' => Carbon::now(),
            ]
        );
    }

    public function testItIgnoresOverlappingIfDifferentUnits()
    {
        $this->createSquawkRange('EGFF', '7201', '7210');
        $this->createSquawkAssignment('VIR25F', 'EGFF', '7201');
        $this->createSquawkAssignment('BAW92A', 'EGFF', '7202');

        $this->assertEquals('7201', $this->allocator->allocate('BMI11A', ['unit' => 'EGGD'])->getCode());
        $this->assertDatabaseHas(
            'unit_discrete_squawk_assignments',
            [
                'callsign' => 'BMI11A',
                'unit' => 'EGGD',
                'code' => '7201',
                'created_at' => Carbon::now(),
            ]
        );
    }

    public function testItFiltersRangesWhereRulesDoNotPass()
    {
        $range = $this->createSquawkRange('EGFF', '7201', '7210');
        UnitDiscreteSquawkRangeRule::create(
            [
                'unit_discrete_squawk_range_id' => $range->id,
                'rule' => [
                    'rule' => 'TWR',
                    'type' => 'UNIT_TYPE',
                ],
            ]
        );
        $this->assertNull($this->allocator->allocate('BMI11A', ['unit' => 'EGFF_APP']));
    }

    public function testItReturnsNullNoUnitProvided()
    {
        $this->assertNull($this->allocator->allocate('BMI11A', []));
    }

    public function testItReturnsNullOnNoApplicableRange()
    {
        $this->assertNull($this->allocator->allocate('BMI11A', ['unit' => 'EGHH']));
    }

    public function testItReturnsNullIfAllocationNotFound()
    {
        $this->assertNull($this->allocator->fetch('MMMMM'));
    }

    public function testItReturnsAllocationIfExists()
    {
        $this->createSquawkAssignment('VIR25F', 'EGGD', '0001');
        $expected = UnitDiscreteSquawkAssignment::find('VIR25F');

        $this->assertEquals($expected, $this->allocator->fetch('VIR25F'));
    }

    public function testItDeletesAllocations()
    {
        $this->createSquawkAssignment('VIR25F', 'EGGD', '0001');

        $this->assertTrue($this->allocator->delete('VIR25F'));
        $this->assertDatabaseMissing(
            'unit_discrete_squawk_assignments',
            [
                'callsign' => 'VIR25F',
            ]
        );
    }

    public function testItReturnsFalseForNonDeletedAllocations()
    {
        $this->assertFalse($this->allocator->delete('LALALA'));
    }


    /**
     * @dataProvider categoryProvider
     */
    public function testItAllocatesCategories(string $category, bool $expected)
    {
        $this->assertEquals($expected, $this->allocator->canAllocateForCategory($category));
    }

    public function categoryProvider(): array
    {
        return [
            [SquawkAssignmentCategories::GENERAL, false],
            [SquawkAssignmentCategories::LOCAL, true],
        ];
    }

    private function createSquawkRange(
        string $unit,
        string $first,
        string $last
    ): UnitDiscreteSquawkRange {
        return UnitDiscreteSquawkRange::create(
            [
                'unit' => $unit,
                'first' => $first,
                'last' => $last,
            ]
        );
    }

    private function createSquawkAssignment(
        string $callsign,
        string $unit,
        string $code
    ) {
        NetworkAircraft::create(
            [
                'callsign' => $callsign
            ]
        );
        UnitDiscreteSquawkAssignment::create(
            [
                'callsign' => $callsign,
                'unit' => $unit,
                'code' => $code,
            ]
        );
    }
}
