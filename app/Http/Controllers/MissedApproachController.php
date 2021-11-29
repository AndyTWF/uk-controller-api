<?php

namespace App\Http\Controllers;

use App\Exceptions\MissedApproach\MissedApproachAlreadyActiveException;
use App\Http\Requests\MissedApproach\CreateMissedApproachNotification;
use App\Services\MissedApproachService;
use Illuminate\Http\JsonResponse;

class MissedApproachController
{
    private MissedApproachService $service;

    public function __construct(MissedApproachService $service)
    {
        $this->service = $service;
    }

    public function create(CreateMissedApproachNotification $request): JsonResponse
    {
        try {
            $notification = $this->service->sendMissedApproachNotification($request->validated()['callsign']);
            return response()->json(
                ['id' => $notification['id'], 'expires_at' => $notification->expires_at->toDateTimeString()],
                201
            );
        } catch (MissedApproachAlreadyActiveException $alreadyActiveException) {
            return response()->json(['message' => $alreadyActiveException->getMessage()], 409);
        }
    }
}
