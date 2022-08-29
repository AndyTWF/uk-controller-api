<?php

namespace App\Http\Controllers\Ecfmp;

use App\Services\Ecfmp\EcfmpService;
use Illuminate\Http\JsonResponse;

class EcfmpController
{
    private readonly EcfmpService $ecfmpService;

    public function __construct(EcfmpService $ecfmpService)
    {
        $this->ecfmpService = $ecfmpService;
    }

    public function __invoke(): JsonResponse
    {
        return response()->json($this->ecfmpService->getEcfmpData());
    }
}
