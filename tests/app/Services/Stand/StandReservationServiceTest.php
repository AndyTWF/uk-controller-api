<?php

namespace App\Services\Stand;

use App\BaseFunctionalTestCase;
use App\Exceptions\Stand\StandNotFoundException;
use App\Exceptions\Stand\StandReservationAirfieldsInvalidException;
use App\Exceptions\Stand\StandReservationCallsignNotValidException;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;

class StandReservationServiceTest extends BaseFunctionalTestCase
{
    /**
     * @dataProvider goodDataProvider
     */
    public function testItCreatesStandReservations(
        string $callsign,
        CarbonInterface $reservationTime,
        ?string $origin,
        ?string $destination
    ) {
        StandReservationService::createStandReservation(
            $callsign,
            1,
            $reservationTime,
            $origin,
            $destination
        );


        $this->assertDatabaseHas(
            'stand_reservations',
            [
                'callsign' => $callsign,
                'stand_id' => 1,
                'reserved_at' => $reservationTime->toDateTimeString(),
                'origin' => $origin,
                'destination' => $destination
            ]
        );
    }

    public function goodDataProvider(): array
    {
        return [
            'Starts before existing, ends before existing' => [
                'BAW123',
                Carbon::parse('2022-01-01 18:00:00'),
                'EGKK',
                'EGLL',
            ],
            'No origin or destination' => [
                'BAW123',
                Carbon::parse('2022-01-01 18:00:00'),
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider badDataProvider
     */
    public function testItThrowsExceptionsForBadReservationData(
        string $callsign,
        int $standId,
        CarbonInterface $reservationTime,
        ?string $origin,
        ?string $destination,
        string $expectedExceptionType,
        string $expectedExceptionMessage
    ) {
        try {
            StandReservationService::createStandReservation(
                $callsign,
                $standId,
                $reservationTime,
                $origin,
                $destination
            );
        } catch (Exception $exception) {
            $this->assertEquals($expectedExceptionType, get_class($exception));
            $this->assertEquals($expectedExceptionMessage, $exception->getMessage());
            $this->assertDatabaseCount(
                'stand_reservations',
                0
            );
            return;
        }

        $this->fail('Expected exception but none thrown');
    }

    private function badDataProvider(): array
    {
        return [
            'Invalid callsign' => [
                '###',
                1,
                Carbon::parse('2022-01-01 18:00:00'),
                'EGKK',
                'EGLL',
                StandReservationCallsignNotValidException::class,
                'Callsign ### is not valid for stand reservation'
            ],
            'Stand doesnt exist' => [
                'BAW123',
                5,
                Carbon::parse('2022-01-01 18:00:00'),
                'EGKK',
                'EGLL',
                StandNotFoundException::class,
                'Stand with id 5 not found'
            ],
            'Only origin airfield set' => [
                'BAW123',
                1,
                Carbon::parse('2022-01-01 18:00:00'),
                'EGKK',
                null,
                StandReservationAirfieldsInvalidException::class,
                'Stand reservations require both or neither airfield to be set'
            ],
            'Only destination airfield set' => [
                'BAW123',
                1,
                Carbon::parse('2022-01-01 18:00:00'),
                null,
                'EGLL',
                StandReservationAirfieldsInvalidException::class,
                'Stand reservations require both or neither airfield to be set'
            ],
            'Origin airfield not valid' => [
                'BAW123',
                1,
                Carbon::parse('2022-01-01 18:00:00'),
                'EGKKS',
                'EGLL',
                StandReservationAirfieldsInvalidException::class,
                'Stand reservation origin airfield EGKKS is invalid'
            ],
            'Destination airfield not valid' => [
                'BAW123',
                1,
                Carbon::parse('2022-01-01 18:00:00'),
                'EGKK',
                'EGLLS',
                StandReservationAirfieldsInvalidException::class,
                'Stand reservation destination airfield EGLLS is invalid'
            ],
        ];
    }
}
