<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AvailabilityController extends Controller
{
    public function __construct(private SlotService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->getAvailability());
    }
}
