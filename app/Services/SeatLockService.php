<?php

/**
 * SeatLockService
 * ล็อกที่นั่งชั่วคราวด้วย Redis ระหว่างรอชำระเงิน เพื่อกันการจองที่นั่งซ้ำ
 * Author :
 * Created :
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SeatLockService
{
    private const LOCK_KEY_PREFIX = 'concert:seat-lock:';

    /**
     * acquireSeats($seatIds, $owner)
     * ล็อกที่นั่งทั้งหมดที่ระบุ ถ้าล็อกไม่ได้ครบจะปล่อยที่ล็อกไปแล้วคืน
     *
     * @param array $seatIds รหัสที่นั่ง
     * @param string $owner ผู้ถือการล็อกที่นั่ง
     * @return bool true ถ้าล็อกได้ครบทุกที่นั่ง
     */
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

    /**
     * releaseSeats($seatIds, $owner)
     * ปล่อยการล็อกที่นั่งตามรหัสที่นั่งและผู้ถือล็อก
     *
     * @param array $seatIds รหัสที่นั่ง
     * @param string $owner ผู้ถือการล็อกที่นั่ง
     * @return void ไม่มีค่าส่งกลับ
     */
    public function releaseSeats(array $seatIds, string $owner): void
    {
        $store = Cache::store('redis');

        foreach (array_unique(array_map('intval', $seatIds)) as $seatId) {
            $store->restoreLock(self::LOCK_KEY_PREFIX.$seatId, $owner)->release();
        }
    }
}
