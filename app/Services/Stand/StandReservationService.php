<?php

namespace App\Services\Stand;

use App\Exceptions\Stand\StandNotFoundException;
use App\Exceptions\Stand\StandReservationAirfieldsInvalidException;
use App\Exceptions\Stand\StandReservationCallsignNotValidException;
use App\Models\Stand\Stand;
use App\Models\Stand\StandReservation;
use App\Rules\Airfield\AirfieldIcao;
use App\Rules\VatsimCallsign;
use Carbon\CarbonInterface;

class StandReservationService
{
    public static function createStandReservation(
        string $callsign,
        int $standId,
        CarbonInterface $reservationTime,
        ?string $origin,
        ?string $destination
    ): void {
        if (!self::callsignValid($callsign)) {
            throw StandReservationCallsignNotValidException::forCallsign($callsign);
        }

        if (!Stand::where('id', $standId)->exists()) {
            throw StandNotFoundException::forId($standId);
        }

        if (!self::airfieldsSet($origin, $destination)) {
            throw StandReservationAirfieldsInvalidException::forBoth();
        }

        if (!self::airfieldValid($origin)) {
            throw StandReservationAirfieldsInvalidException::forOrigin($origin);
        }

        if (!self::airfieldValid($destination)) {
            throw StandReservationAirfieldsInvalidException::forDestination($destination);
        }

        StandReservation::create(
            [
                'stand_id' => $standId,
                'callsign' => $callsign,
                'origin' => $origin,
                'destination' => $destination,
                'reserved_at' => $reservationTime,
            ]
        );
    }

    private static function airfieldsSet(?string $origin, ?string $destination): bool
    {
        return !($origin xor $destination);
    }

    private static function airfieldValid(?string $airfield): bool
    {
        return is_null($airfield) || (new AirfieldIcao())->passes('', $airfield);
    }

    private static function callsignValid(string $callsign): bool
    {
        return (new VatsimCallsign())->passes('', $callsign);
    }
}
