<?php

/**
 * WebmailLink
 * ลิงก์เปิดเว็บเมลตามโดเมนของอีเมล (ปุ่ม "เปิด Gmail" ในหน้ายืนยันอีเมลและหน้ากรอกรหัสลืมรหัสผ่าน)
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 29/10/2569 (ย้ายมาจาก EmailVerificationController::webmailFor)
 */

namespace App\Services;

class WebmailLink
{
    /**
     * for(email)
     * param : email
     * return : array|null – ['label' => ชื่อบริการ, 'url' => ลิงก์] หรือ null ถ้าไม่รู้จักโดเมน (ไม่แสดงปุ่ม)
     */
    public static function for(string $email): ?array
    {
        $domain = mb_strtolower(substr((string) strrchr($email, '@'), 1));

        return match ($domain) {
            'gmail.com', 'googlemail.com'                      => ['label' => 'Gmail', 'url' => 'https://mail.google.com'],
            'outlook.com', 'hotmail.com', 'live.com', 'msn.com' => ['label' => 'Outlook', 'url' => 'https://outlook.live.com/mail'],
            'yahoo.com', 'yahoo.co.th'                          => ['label' => 'Yahoo Mail', 'url' => 'https://mail.yahoo.com'],
            'icloud.com', 'me.com'                              => ['label' => 'iCloud Mail', 'url' => 'https://www.icloud.com/mail'],
            default                                             => null,
        };
    }
}
