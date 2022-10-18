<?php

namespace App\Filament\Pages;

use App\Rules\Airfield\AirfieldIcao;
use App\Rules\VatsimCallsign;
use App\Services\NetworkAircraftService;
use App\Services\SquawkService;
use Filament\Pages\Page;

class SquawkAllocator extends Page
{
    public $callsign;
    public $origin;
    public $destination;

    public $assignedSquawk;

    protected static ?string $navigationIcon = 'heroicon-o-wifi';
    protected static string $view = 'filament.pages.squawk-allocator';

    public function allocateSquawk(SquawkService $squawkService)
    {
        $validated = $this->validate(
            [
                'callsign' => new VatsimCallsign,
                'origin' => new AirfieldIcao,
                'destination' => new AirfieldIcao,
            ]
        );
        NetworkAircraftService::createOrUpdateNetworkAircraft($validated['callsign']);
        $this->assignedSquawk = $squawkService->assignGeneralSquawk($validated['callsign'], $validated['origin'], $validated['destination']);
    }

    public function getViewData(): array
    {
        return [
            'assignedSquawk' => $this->assignedSquawk,
        ];
    }
}
