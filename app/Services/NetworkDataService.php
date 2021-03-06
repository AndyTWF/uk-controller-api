<?php

namespace App\Services;

use App\Events\NetworkAircraftDisconnectedEvent;
use App\Events\NetworkAircraftUpdatedEvent;
use App\Models\Vatsim\NetworkAircraft;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NetworkDataService
{
    const NETWORK_DATA_URL = "http://cluster.data.vatsim.net/vatsim-data.json";

    public function updateNetworkData(): void
    {

        // Download the network data and check that it was successful
        $networkResponse = null;
        try {
            $networkResponse = Http::get(self::NETWORK_DATA_URL);
        } catch (Exception $exception) {
            Log::warning('Failed to download network data, exception was ' . $exception->getMessage());
            return;
        }

        if (!$networkResponse->successful()) {
            Log::warning('Failed to download network data, response was ' . $networkResponse->status());
            return;
        }

        // Process clients
        $this->processClients($networkResponse->json('clients', []));
        $this->handleTimeouts();
    }

    /**
     * Loop through each client in the clients array from the network data
     *
     * @param array $clients
     */
    private function processClients(array $clients): void
    {
        foreach ($clients as $client) {
            if (!isset($client['clienttype'])) {
                Log::error('Client type missing for aircraft', $client);
                continue;
            }

            if ($client['clienttype'] !== 'PILOT') {
                continue;
            }

            event(
                new NetworkAircraftUpdatedEvent(
                    self::createOrUpdateNetworkAircraft($client['callsign'], $client)
                )
            );
        }
    }

    /**
     * If any aircraft has passed the timeout window, remove it from the list.
     *
     * NOTE: Events should always fire before final deletion because the listeners
     * will use the aircraft data to mark things such as squawk assignments as deleted
     * and send further events. As a last resort calling delete here will delete any
     * foreign key references left over.
     */
    private function handleTimeouts(): void
    {
        NetworkAircraft::where('updated_at', '<', Carbon::now()->subMinutes(20))
            ->get()
            ->each(
                function (NetworkAircraft $aircraft) {
                    $aircraft->getConnection()->transaction(
                        function () use ($aircraft) {
                            event(new NetworkAircraftDisconnectedEvent($aircraft));
                            $aircraft->delete();
                        }
                    );
                }
            );
    }

    public static function createOrUpdateNetworkAircraft(
        string $callsign,
        array $details = []
    ): NetworkAircraft {
        try {
            $aircraft = NetworkAircraft::updateOrCreate(
                ['callsign' => $callsign],
                array_merge(
                    ['callsign' => $callsign],
                    $details
                )
            );
            $aircraft->touch();
        } catch (QueryException $queryException) {
            if ($queryException->errorInfo[1] !== 1062) {
                throw $queryException;
            }
            $aircraft = NetworkAircraft::find($callsign);
        }

        return $aircraft;
    }

    public static function firstOrCreateNetworkAircraft(
        string $callsign,
        array $details = []
    ): NetworkAircraft {
        try {
            $aircraft = NetworkAircraft::firstOrCreate(
                ['callsign' => $callsign],
                array_merge(
                    ['callsign' => $callsign],
                    $details
                )
            );
        } catch (QueryException $queryException) {
            if ($queryException->errorInfo[1] !== 1062) {
                throw $queryException;
            }
            $aircraft = NetworkAircraft::find($callsign);
        }

        return $aircraft;
    }
}
