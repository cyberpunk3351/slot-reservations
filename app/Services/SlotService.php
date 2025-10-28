<?php
namespace App\Services;

use App\Models\Hold;
use App\Models\Slot;
use App\Models\IdempotencyKey;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

readonly class SlotService
{
    public function __construct(private CacheRepository $cache) {}

    public function getAvailability(): array
    {
        $key = 'slots:availability';
        if ($data = $this->cache->get($key)) {
            return $data;
        }
        $lock = Cache::lock('slots:availability:lock', 10);
        try {
            if ($lock->get()) {
                if ($data = $this->cache->get($key)) {
                    return $data;
                }
                $fresh = Slot::query()
                    ->select('id', 'capacity', 'remaining')
                    ->orderBy('id')
                    ->get()
                    ->toArray();
                $this->cache->put($key, $fresh, now()->addSeconds(10));
                return $fresh;
            }
            $lock->block(3);
            return $this->cache->get($key) ?? [];
        } finally {
            optional($lock)->release();
        }
    }

    public function createHold(int $slotId, string $idempotencyKey, string $endpoint, string $method = 'POST'): array
    {
        if ($existing = IdempotencyKey::where('key', $idempotencyKey)->first()) {
            return [
                'code' => $existing->response_code,
                'body' => $existing->response_body ?? ($existing->hold_id ? ['hold_id' => $existing->hold_id] : []),
            ];
        }

        return DB::transaction(function () use ($slotId, $idempotencyKey, $endpoint, $method) {
            if ($existing = IdempotencyKey::where('key', $idempotencyKey)->lockForUpdate()->first()) {
                return [
                    'code' => $existing->response_code,
                    'body' => $existing->response_body ?? ($existing->hold_id ? ['hold_id' => $existing->hold_id] : []),
                ];
            }

            $slot = Slot::lockForUpdate()->findOrFail($slotId);

            if ($slot->remaining <= 0) {
                $payload = ['error' => 'Capacity exhausted'];
                IdempotencyKey::create([
                    'key' => $idempotencyKey,
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'response_code' => 409,
                    'response_body' => $payload,
                ]);
                return ['code' => 409, 'body' => $payload];
            }

            $hold = Hold::create([
                'slot_id'         => $slot->id,
                'status'          => 'held',
                'idempotency_key' => $idempotencyKey,
                'expires_at'      => now()->addMinutes(5),
            ]);

            $payload = [
                'hold_id' => $hold->id,
                'status' => $hold->status,
                'expires_at' => $hold->expires_at->toIso8601String()
            ];

            IdempotencyKey::create([
                'key'           => $idempotencyKey,
                'method'        => $method,
                'endpoint'      => $endpoint,
                'response_code' => 201,
                'response_body' => $payload,
                'hold_id'       => $hold->id,
            ]);

            return ['code' => 201, 'body' => $payload];
        }, 3);
    }

    public function confirmHold(int $holdId): array
    {
        return DB::transaction(function () use ($holdId) {
            /** @var Hold $hold */
            $hold = Hold::lockForUpdate()->findOrFail($holdId);

            if ($hold->status !== 'held') {
                return ['code' => 409, 'body' => ['error' => 'Hold is not in held state']];
            }

            if ($hold->isExpired()) {
                $hold->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
                return ['code' => 409, 'body' => ['error' => 'Hold expired']];
            }

            $affected = Slot::where('id', $hold->slot_id)
                ->where('remaining', '>', 0)
                ->decrement('remaining');

            if ($affected === 0) {
                return ['code' => 409, 'body' => ['error' => 'No remaining capacity']];
            }

            $hold->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            Cache::forget('slots:availability');

            return ['code' => 200, 'body' => ['hold_id' => $hold->id, 'status' => $hold->status]];
        }, 3);
    }

    /** Отмена холда; если был confirmed — возвращаем место */
    public function cancelHold(int $holdId): array
    {
        return DB::transaction(function () use ($holdId) {
            /** @var Hold $hold */
            $hold = Hold::lockForUpdate()->findOrFail($holdId);

            if ($hold->status === 'cancelled') {
                return ['code' => 200, 'body' => ['hold_id' => $hold->id, 'status' => 'cancelled']];
            }

            if ($hold->status === 'confirmed') {
                Slot::where('id', $hold->slot_id)
                    ->increment('remaining');
                Cache::forget('slots:availability');
            }

            $hold->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            return ['code' => 200, 'body' => ['hold_id' => $hold->id, 'status' => 'cancelled']];
        }, 3);
    }
}
