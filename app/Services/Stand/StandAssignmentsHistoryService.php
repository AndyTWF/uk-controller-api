<?php

namespace App\Services\Stand;

use App\Models\Stand\Stand;
use App\Models\Stand\StandAssignment;
use App\Models\Stand\StandAssignmentsHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StandAssignmentsHistoryService implements RecordsAssignmentHistory
{
    public function deleteHistoryFor(StandAssignment $target): void
    {
        StandAssignmentsHistory::where(
            'callsign',
            $target->callsign
        )->delete();
    }

    public function createHistoryItem(StandAssignmentContext $context): void
    {
        DB::transaction(function () use ($context)
        {
            $assignment = $context->assignment();
            $this->deleteHistoryFor($assignment);
            StandAssignmentsHistory::create(
                [
                    'callsign' => $assignment->callsign,
                    'stand_id' => $assignment->stand_id,
                    'type' => $context->assignmentType(),
                    'user_id' => !is_null(Auth::user()) ? Auth::user()->id : null,
                    'context' => $this->generateContext($context),
                ]
            );
        });
    }

    // TODO: Add aircraft type to context
    // TODO: Add origin airfield to context
    // TODO: Add destination airfield to context
    // TODO: Add user request to context
    // TODO: Add other active requests to context
    // TODO: Add reservations to context
    // TODO: Add fp remarks to context
    private function generateContext(StandAssignmentContext $context): array
    {
        return [
            'removed_assignments' => $context->removedAssignments()->map(
                function (StandAssignment $assignment)
                {
                    return [
                        'callsign' => $assignment->callsign,
                        'stand' => $assignment->stand->identifier,
                    ];
                }
            ),
            'occupied_stands' => Stand::where('airfield_id', $context->assignment()->stand->airfield_id)
                ->where('id', '<>', $context->assignment()->stand_id)
                ->whereHas('occupier')
                ->orderBy('stands.id')
                ->get()
                ->map(fn(Stand $stand) => $stand->identifier),
            'assigned_stands' => Stand::where('airfield_id', $context->assignment()->stand->airfield_id)
                ->where('id', '<>', $context->assignment()->stand_id)
                ->whereHas('assignment')
                ->orderBy('stands.id')
                ->get()
                ->map(fn(Stand $stand) => $stand->identifier),
        ];
    }
}
