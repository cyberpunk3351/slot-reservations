<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HoldController extends Controller
{
    public function __construct(private SlotService $service) {}

    public function store(Request $request, int $slotId): JsonResponse
    {
        $key = $request->header('Idempotency-Key');
        if (!$key) {
            return response()->json(['error' => 'Idempotency-Key header is required'], 422);
        }

        $result = $this->service->createHold(
            slotId: $slotId,
            idempotencyKey: $key,
            endpoint: $request->path(),
            method: $request->method()
        );

        return response()->json($result['body'], $result['code']);
    }

    public function confirm(int $holdId): JsonResponse
    {
        $result = $this->service->confirmHold($holdId);
        return response()->json($result['body'], $result['code']);
    }

    public function destroy(int $holdId): JsonResponse
    {
        $result = $this->service->cancelHold($holdId);
        return response()->json($result['body'], $result['code']);
    }
}
