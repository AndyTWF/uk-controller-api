<?php

namespace App\Services\Stand;

use App\Exceptions\Stand\CallsignHasClashingReservationException;
use App\Exceptions\Stand\StandNotFoundException;
use App\Exceptions\Stand\StandReservationAirfieldsInvalidException;
use App\Exceptions\Stand\StandReservationCallsignNotValidException;
use App\Exceptions\Stand\StandReservationTimeInvalidException;
use App\Models\Stand\Stand;
use App\Models\Stand\StandReservation;
use App\Rules\Airfield\AirfieldIcao;
use App\Rules\VatsimCallsign;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class StandReservationService
{
    public static function createStandReservation(
        string $callsign,
        int $standId,
        CarbonInterface $startTime,
        CarbonInterface $endTime,
        ?string $origin,
        ?string $destination
    ): void {
        if (!VatsimCallsign::callsignValid($callsign)) {
            throw StandReservationCallsignNotValidException::forCallsign($callsign);
        }

        if (!Stand::where('id', $standId)->exists()) {
            throw StandNotFoundException::forId($standId);
        }

        if (!$endTime->isAfter($startTime)) {
            throw new StandReservationTimeInvalidException();
        }

        if (self::callsignHasClashingReservation($callsign, $startTime, $endTime)) {
            throw CallsignHasClashingReservationException::forCallsign($callsign);
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
                'start' => $startTime,
                'end' => $endTime,
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

    private static function applyTimePeriodToQuery(
        Builder $query,
        CarbonInterface $startTime,
        CarbonInterface $endTime
    ): Builder {
        $startsDuringPeriod = $query->clone()->where('start', '>', $startTime)
            ->where('start', '<', $endTime);

        $endsDuringPeriod = $query->clone()->where('end', '>', $startTime)
            ->where('end', '<', $endTime);

        $coversPeriod = $query->clone()->where('start', '<=', $startTime)
            ->where('end', '>=', $endTime);

        return $startsDuringPeriod->union($endsDuringPeriod)->union($coversPeriod);
    }

    private static function callsignHasClashingReservation(
        string $callsign,
        CarbonInterface $startTime,
        CarbonInterface $endTime
    ): bool {
        return self::applyTimePeriodToQuery(StandReservation::callsign($callsign), $startTime, $endTime)->exists();
    }
}
