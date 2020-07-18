<?php

namespace App\Allocator\Squawk\General;

use App\Allocator\Squawk\SquawkAssignmentCategories;
use App\BaseFunctionalTestCase;
use App\Models\Squawk\Orcam\OrcamSquawkAssignment;
use App\Models\Squawk\Orcam\OrcamSquawkRange;
use App\Models\Vatsim\NetworkAircraft;
use Carbon\Carbon;

class OrcamSquawkAllocatorTest extends BaseFunctionalTestCase
{
    /**
     * @var OrcamSquawkAllocator
     */
    private $allocator;

    public function setUp(): void
    {
        parent::setUp();
        $this->allocator = new OrcamSquawkAllocator();
    }

    public function testItAllocatesFirstFreeSquawkInRange()
    {
        $this->createSquawkRange('E', '7201', '7210');
        $this->createSquawkAssignment('VIR25F', '7201');
        $this->createSquawkAssignment('BAW92A', '7202');

        $this->assertEquals(
            '7203',
            $this->allocator->allocate('BMI11A', ['origin' => 'EDDF'])->getCode()
        );
        $this->assertDatabaseHas(
            'orcam_squawk_assignments',
            [
                'callsign' => 'BMI11A',
                'code' => '7203',
                'created_at' => Carbon::now(),
            ]
        );
    }

    public function testItAllocatesSingleCharacterRange()
    {
        $this->createSquawkRange('E', '7201', '7210');

        $this->assertEquals(
            '7201',
            $this->allocator->allocate('BMI11A', ['origin' => 'EDDF'])->getCode()
        );
        $this->assertDatabaseHas(
            'orcam_squawk_assignments',
            [
                'callsign' => 'BMI11A',
                'code' => '7201',
                'created_at' => Carbon::now(),
            ]
        );
    }

    public function testItPrefersDoubleCharacterMatchOverSingle()
    {
        $this->createSquawkRange('E', '7201', '7201');
        $this->createSquawkRange('ED', '7202', '7202');

        $this->assertEquals(
            '7202',
            $this->allocator->allocate('BMI11A', ['origin' => 'EDDF'])->getCode()
        );
        $this->assertDatabaseHas(
            'orcam_squawk_assignments',
            [
                'callsign' => 'BMI11A',
                'code' => '7202',
                'created_at' => Carbon::now(),
            ]
        );
    }

    public function testItPrefersTripleCharacterMatchOverDouble()
    {
        $this->createSquawkRange('E', '7201', '7201');
        $this->createSquawkRange('ED', '7202', '7202');
        $this->createSquawkRange('EDD', '7203', '7203');

        $this->assertEquals(
            '7203',
            $this->allocator->allocate('BMI11A', ['origin' => 'EDDF'])->getCode()
        );
        $this->assertDatabaseHas(
            'orcam_squawk_assignments',
            [
                'callsign' => 'BMI11A',
                'code' => '7203',
                'created_at' => Carbon::now(),
            ]
        );
    }

    public function testItPrefersFullMatch()
    {
        $this->createSquawkRange('E', '7201', '7201');
        $this->createSquawkRange('ED', '7202', '7202');
        $this->createSquawkRange('EDD', '7203', '7203');
        $this->createSquawkRange('EDDF', '7204', '7204');

        $this->assertEquals(
            '7204',
            $this->allocator->allocate('BMI11A', ['origin' => 'EDDF'])->getCode()
        );
        $this->assertDatabaseHas(
            'orcam_squawk_assignments',
            [
                'callsign' => 'BMI11A',
                'code' => '7204',
                'created_at' => Carbon::now(),
            ]
        );
    }

    public function testItReturnsNullOnNoApplicableRange()
    {
        $this->assertNull($this->allocator->allocate('BMI11A', ['origin' => 'EGGD']));
    }

    public function testItReturnsNullOnMissingOrigin()
    {
        $this->assertNull($this->allocator->allocate('BMI11A', []));
        $this->assertDatabaseMissing(
            'orcam_squawk_assignments',
            [
                'callsign' => 'BMI11A',
            ]
        );
    }

    public function testItReturnsNullIfAllocationNotFound()
    {
        $this->assertNull($this->allocator->fetch('MMMMM'));
    }

    public function testItReturnsAllocationIfExists()
    {
        $this->createSquawkAssignment('VIR25F', '0001');
        $expected = OrcamSquawkAssignment::find('VIR25F');

        $this->assertEquals($expected, $this->allocator->fetch('VIR25F'));
    }

    public function testItDeletesAllocations()
    {
        $this->createSquawkAssignment('VIR25F', '0001');

        $this->assertTrue($this->allocator->delete('VIR25F'));
        $this->assertDatabaseMissing(
            'orcam_squawk_assignments',
            [
                'callsign' => 'VIR25F'
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
            [SquawkAssignmentCategories::GENERAL, true],
            [SquawkAssignmentCategories::LOCAL, false],
        ];
    }

    private function createSquawkRange(
        string $origin,
        string $first,
        string $last
    ) {
        OrcamSquawkRange::create(
            [
                'origin' => $origin,
                'first' => $first,
                'last' => $last,
            ]
        );
    }

    private function createSquawkAssignment(
        string $callsign,
        string $code
    ) {
        NetworkAircraft::create(
            [
                'callsign' => $callsign
            ]
        );
        OrcamSquawkAssignment::create(
            [
                'callsign' => $callsign,
                'code' => $code,
            ]
        );
    }
}
