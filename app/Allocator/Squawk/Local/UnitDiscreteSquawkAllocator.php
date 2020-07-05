<?php

namespace App\Allocator\Squawk\Local;

use App\Allocator\Squawk\SquawkAllocatorInterface;
use App\Allocator\Squawk\SquawkAssignmentInterface;
use App\Models\Squawk\UnitDiscrete\UnitDiscreteSquawkAssignment;
use App\Models\Squawk\UnitDiscrete\UnitDiscreteSquawkRange;
use App\Models\Squawk\UnitDiscrete\UnitDiscreteSquawkRangeGuest;
use App\Models\Vatsim\NetworkAircraft;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class UnitDiscreteSquawkAllocator implements SquawkAllocatorInterface
{
    private function getApplicableRanges(string $unit): Collection
    {
        return UnitDiscreteSquawkRange::whereIn('unit', $this->getApplicableUnits($unit))
            ->get();
    }

    private function getApplicableUnits(string $unit): array
    {
        return array_merge(
            UnitDiscreteSquawkRangeGuest::where('guest_unit', $unit)
                ->pluck('primary_unit')
                ->all(),
            [$unit]
        );
    }

    private function getUnitString(string $unit): string
    {
        return substr($unit, 0, 4);
    }

    public function allocate(string $callsign, array $details): ?SquawkAssignmentInterface
    {
        $unit = isset($details['unit']) ? $this->getUnitString($details['unit']) : null;
        if (!$unit) {
            Log::error('Unit not provided for local squawk assignment');
            return null;
        }

        $assignment = null;
        $this->getApplicableRanges($unit)->each(function (UnitDiscreteSquawkRange $range) use (
            &$assignment,
            $callsign
        ) {
            $allSquawks = $range->getAllSquawksInRange();
            $possibleSquawks = $allSquawks->diff(
                UnitDiscreteSquawkAssignment::whereIn('code', $allSquawks)
                    ->where('unit', $range->unit)
                    ->pluck('code')
                    ->all()
            );

            if ($possibleSquawks->isEmpty()) {
                return true;
            }

            NetworkAircraft::firstOrCreate(
                [
                    'callsign' => $callsign,
                ]
            );

            $assignment = UnitDiscreteSquawkAssignment::create(
                [
                    'callsign' => $callsign,
                    'unit' => $range->unit,
                    'code' => $possibleSquawks->first(),
                ]
            );
            return false;
        });

        return $assignment;
    }

    public function delete(string $callsign): void
    {
        UnitDiscreteSquawkAssignment::find($callsign)->delete();
    }

    public function fetch(string $callsign): ?SquawkAssignmentInterface
    {
        return UnitDiscreteSquawkAssignment::find($callsign);
    }
}
