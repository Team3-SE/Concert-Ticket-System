<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SeatLockService
{
    private const LOCK_KEY_PREFIX = 'concert:seat-lock:';

    public function acquireSeats(array $seatIds, string $owner): bool
    {
        $seatIds = collect($seatIds)->map(fn ($seatId) => (int) $seatId)->unique()->sort()->values();
        $ttlSeconds = (Booking::SEAT_LOCK_MINUTES + Booking::PAYMENT_GRACE_MINUTES) * 60;
        $acquiredSeatIds = [];
        $store = Cache::store('redis');

        try {
            foreach ($seatIds as $seatId) {
                $lock = $store->lock(self::LOCK_KEY_PREFIX.$seatId, $ttlSeconds, $owner);

                if (! $lock->get()) {
                    $this->releaseSeats($acquiredSeatIds, $owner);

                    return false;
                }

                $acquiredSeatIds[] = $seatId;
            }
        } catch (Throwable $exception) {
            try {
                $this->releaseSeats($acquiredSeatIds, $owner);
            } catch (Throwable $releaseException) {
                report($releaseException);
            }

            throw $exception;
        }

        return true;
    }

    public function releaseSeats(array $seatIds, string $owner): void
    {
        $store = Cache::store('redis');

        foreach (array_unique(array_map('intval', $seatIds)) as $seatId) {
            $store->restoreLock(self::LOCK_KEY_PREFIX.$seatId, $owner)->release();
        }
    }
}
