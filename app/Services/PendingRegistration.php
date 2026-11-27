<?php

/**
 * PendingRegistration
 * เก็บข้อมูลสมัครสมาชิกชั่วคราวใน Cache จนกว่าผู้ใช้จะกดลิงก์ยืนยันอีเมล
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 17/10/2569
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

/*
 * (ยังไม่สร้างแถวใน app_user ก่อนยืนยัน)
 */

namespace App\Services;

use App\Notifications\RegistrationVerification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PendingRegistration
{
    /* อายุของลิงก์ยืนยันและข้อมูลชั่วคราว (นาที) */
    public const TTL_MINUTES = 60;

    /**
     * emailKey($email)
     * คีย์ Cache ที่ใช้หา token จากอีเมล
     *
     * @param string $email อีเมล
     * @return string ข้อความตามที่อธิบายข้างต้น
     */
    private static function emailKey(string $email): string
    {
        return 'pending_reg_email:' . sha1(mb_strtolower(trim($email)));
    }

    /**
     * tokenKey($token)
     * คีย์ Cache ที่เก็บข้อมูลสมัครของ token
     *
     * @param string $token โทเค็น
     * @return string ข้อความตามที่อธิบายข้างต้น
     */
    private static function tokenKey(string $token): string
    {
        return 'pending_reg:' . $token;
    }

    /**
     * hasEmail($email)
     * ตรวจว่าอีเมลนี้มีการสมัครที่รอยืนยันอยู่หรือไม่
     *
     * @param string $email อีเมล
     * @return bool true หรือ false ตามที่อธิบายข้างต้น
     */
    public static function hasEmail(string $email): bool
    {
        return Cache::has(self::emailKey($email));
    }

    /**
     * store($data)
     * เก็บข้อมูลสมัคร (รหัสผ่านต้อง hash มาแล้ว) พร้อมสร้าง token ใหม่
     *
     * @param array $data email
     * @return string token
     */
    public static function store(array $data): string
    {
        $token = Str::random(64);
        self::put($token, $data);

        return $token;
    }

    /**
     * find($token)
     * ดึงข้อมูลสมัครจาก token
     *
     * @param string $token โทเค็น
     * @return array|null null ถ้าไม่พบหรือหมดอายุ
     */
    public static function find(string $token): ?array
    {
        return Cache::get(self::tokenKey($token));
    }

    /**
     * tokenForEmail($email)
     * หา token ของการสมัครที่รอยืนยันจากอีเมล
     *
     * @param string $email อีเมล
     * @return string|null ค่าที่ส่งกลับตามที่อธิบายข้างต้น
     */
    public static function tokenForEmail(string $email): ?string
    {
        return Cache::get(self::emailKey($email));
    }

    /**
     * forget($token, $email)
     * ลบข้อมูลสมัครชั่วคราวหลังยืนยันสำเร็จ
     *
     * @param string $token โทเค็น
     * @param string $email อีเมล
     * @return void ไม่มีค่าส่งกลับ
     */
    public static function forget(string $token, string $email): void
    {
        Cache::forget(self::tokenKey($token));
        Cache::forget(self::emailKey($email));
    }

    /**
     * send($token, $data)
     * ต่ออายุข้อมูลชั่วคราว แล้วส่งอีเมลลิงก์ยืนยัน (signed URL)
     *
     * @param string $token โทเค็น
     * @param array $data ข้อมูล
     * @return void ไม่มีค่าส่งกลับ
     */
    public static function send(string $token, array $data): void
    {
        self::put($token, $data);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(self::TTL_MINUTES),
            ['token' => $token]
        );

        Notification::route('mail', $data['email'])
            ->notify(new RegistrationVerification($url, $data['first_name'] ?? ''));
    }

    /**
     * put($token, $data)
     * บันทึกข้อมูลสมัครและตัวชี้จากอีเมลไปยัง token พร้อมอายุ
     *
     * @param string $token โทเค็น
     * @param array $data ข้อมูล
     * @return void ไม่มีค่าส่งกลับ
     */
    private static function put(string $token, array $data): void
    {
        $ttl = now()->addMinutes(self::TTL_MINUTES);
        Cache::put(self::tokenKey($token), $data, $ttl);
        Cache::put(self::emailKey($data['email']), $token, $ttl);
    }
}
