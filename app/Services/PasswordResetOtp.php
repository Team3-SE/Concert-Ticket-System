<?php

/**
 * PasswordResetOtp
 * รหัสยืนยัน 6 หลักสำหรับลืมรหัสผ่าน เก็บใน Cache (Redis) แบบ hash ตามอีเมล หมดอายุเองตาม TTL
 * - ขอรหัสใหม่แล้วรหัสเดิมใช้ไม่ได้ทันที (เขียนทับคีย์เดิม)
 * - กรอกผิดครบ MAX_ATTEMPTS ครั้ง รหัสนั้นถูกลบ ต้องขอรหัสใหม่
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 29/10/2569
 */

namespace App\Services;

use App\Models\AppUser;
use App\Notifications\PasswordResetCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class PasswordResetOtp
{
    /* อายุของรหัส (นาที) */
    public const TTL_MINUTES = 10;

    /* กรอกผิดได้กี่ครั้งก่อนรหัสถูกยกเลิก */
    public const MAX_ATTEMPTS = 5;

    /* ต้องรอกี่วินาทีก่อนขอรหัสใหม่ได้ */
    public const RESEND_COOLDOWN_SECONDS = 60;

    /* หลังยืนยันรหัสสำเร็จ มีเวลาตั้งรหัสผ่านใหม่กี่นาที */
    public const RESET_WINDOW_MINUTES = 15;

    /* ผลการตรวจรหัส */
    public const OK = 'ok';
    public const INVALID = 'invalid';
    public const LOCKED = 'locked';
    public const EXPIRED = 'expired';

    /**
     * key(email)
     * คีย์ Cache ของรหัสที่ส่งไปยังอีเมลนี้
     * param : email
     * return : string
     */
    private static function key(string $email): string
    {
        return 'password_otp:' . sha1(mb_strtolower(trim($email)));
    }

    /**
     * send(user)
     * สร้างรหัสใหม่ (แทนรหัสเดิม) แล้วส่งอีเมลไปยังผู้ใช้
     * param : user – บัญชีที่มีอีเมล
     * return : void (ส่งอีเมลไม่สำเร็จจะ throw ให้ผู้เรียกจัดการ และลบรหัสที่เพิ่งสร้าง)
     */
    public static function send(AppUser $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(self::TTL_MINUTES);

        Cache::put(self::key($user->email), [
            'user_id'    => $user->user_id,
            'hash'       => Hash::make($code),
            'attempts'   => 0,
            'sent_at'    => now()->timestamp,
            'expires_at' => $expiresAt->timestamp,
        ], $expiresAt);

        try {
            Notification::route('mail', $user->email)
                ->notify(new PasswordResetCode($code, (string) $user->first_name));
        } catch (\Throwable $e) {
            self::forget($user->email);
            throw $e;
        }
    }

    /**
     * check(email, code)
     * ตรวจรหัสที่ผู้ใช้กรอก (ผิดแล้วนับครั้ง ถูกแล้วลบรหัสทิ้ง ใช้ซ้ำไม่ได้)
     * param : email, code (ตัวเลข 6 หลัก)
     * return : array ['status' => OK|INVALID|LOCKED|EXPIRED, 'user_id' => int|null, 'left' => จำนวนครั้งที่เหลือ]
     */
    public static function check(string $email, string $code): array
    {
        $data = Cache::get(self::key($email));

        if (!$data || $data['expires_at'] <= now()->timestamp) {
            self::forget($email);
            return ['status' => self::EXPIRED, 'user_id' => null, 'left' => 0];
        }

        if (Hash::check($code, $data['hash'])) {
            self::forget($email);
            return ['status' => self::OK, 'user_id' => (int) $data['user_id'], 'left' => 0];
        }

        $data['attempts']++;
        $left = self::MAX_ATTEMPTS - $data['attempts'];

        if ($left <= 0) {
            self::forget($email);
            return ['status' => self::LOCKED, 'user_id' => null, 'left' => 0];
        }

        // เก็บจำนวนครั้งที่ผิด โดยไม่ต่ออายุรหัส
        Cache::put(self::key($email), $data, Carbon::createFromTimestamp($data['expires_at']));

        return ['status' => self::INVALID, 'user_id' => null, 'left' => $left];
    }

    /**
     * secondsLeft(email)
     * เวลาที่รหัสปัจจุบันยังใช้ได้
     * param : email
     * return : int วินาที (0 = ไม่มีรหัสหรือหมดอายุแล้ว)
     */
    public static function secondsLeft(string $email): int
    {
        $data = Cache::get(self::key($email));

        return $data ? max(0, $data['expires_at'] - now()->timestamp) : 0;
    }

    /**
     * cooldownLeft(email)
     * ต้องรออีกกี่วินาทีจึงขอรหัสใหม่ได้ (นับจากครั้งล่าสุดที่ส่ง)
     * param : email
     * return : int วินาที (0 = ขอได้เลย)
     */
    public static function cooldownLeft(string $email): int
    {
        $data = Cache::get(self::key($email));

        return $data ? max(0, self::RESEND_COOLDOWN_SECONDS - (now()->timestamp - $data['sent_at'])) : 0;
    }

    /**
     * forget(email)
     * ลบรหัสของอีเมลนี้
     * param : email
     * return : void
     */
    public static function forget(string $email): void
    {
        Cache::forget(self::key($email));
    }
}
