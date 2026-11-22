<?php

/**
 * AuthModalComposer
 * ส่งข้อมูลให้หน้าต่างของ user/partials/auth-modals (เข้าสู่ระบบ / สมัครสมาชิก / ลืมรหัสผ่าน / ตรวจอีเมล) ทุกครั้งที่แสดง
 * - authOpen : หน้าต่างที่ต้องเปิดตอนโหลดหน้า (login, register, forgot, verify-email) หรือ null
 *              มาจาก session open_modal ที่ controller ส่งมากับ redirect หรือ ?login=1
 * - forgot   : ขั้นที่จะแสดงในหน้าต่างลืมรหัสผ่าน (1–3) อีเมลในขั้นที่ 1 และข้อมูลขั้นที่ 2–3 ถ้ายังทำค้างอยู่ใน session
 * - verify   : ข้อมูลหน้าต่างตรวจอีเมลหลังสมัครสมาชิก หรือ null ถ้าไม่มีอีเมลรอยืนยัน
 * ลงทะเบียนที่ AppServiceProvider::boot
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 15/11/2569
 */

namespace App\View\Composers;

use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\User\EmailVerificationController;
use App\Services\PasswordResetOtp;
use App\Services\PendingRegistration;
use App\Services\WebmailLink;
use Illuminate\View\View;

class AuthModalComposer
{
    /* ค่า open_modal ของหน้าต่างลืมรหัสผ่าน => ขั้นที่ต้องแสดง */
    private const FORGOT_STEPS = ['forgot' => 1, 'forgot-code' => 2, 'forgot-reset' => 3];

    /**
     * compose(view)
     * ใส่ตัวแปร authOpen, forgot, verify ให้ view
     * param : view
     * return : -
     */
    public function compose(View $view): void
    {
        $open   = session('open_modal') ?: (request()->query('login') === '1' ? 'login' : null);
        $code   = $this->codeStep();
        $reset  = $this->resetStep();
        $verify = $this->verifyData();

        // ขั้นที่จะแสดง: ตามที่ controller สั่งมา ถ้าไม่ได้สั่ง (กดลิงก์ "ลืมรหัสผ่าน?" เอง) = ขั้นที่ทำค้างไว้ล่าสุด
        // ข้อมูลขั้นนั้นไม่มีแล้ว (session หมดอายุ / หมดเวลา) = กลับไปขั้นที่ 1
        $step = self::FORGOT_STEPS[$open] ?? ($reset ? 3 : ($code ? 2 : 1));
        if (($step === 2 && !$code) || ($step === 3 && !$reset)) {
            $step = 1;
        }

        if (isset(self::FORGOT_STEPS[$open])) {
            $open = 'forgot';
        }
        if ($open === 'verify-email' && !$verify) {
            $open = 'register';
        }

        $view->with([
            'authOpen' => $open,
            'forgot'   => [
                'step'  => $step,
                'email' => (string) old('email', session(PasswordResetController::SESSION_EMAIL, '')),
                'code'  => $code,
                'reset' => $reset,
            ],
            'verify'   => $verify,
        ]);
    }

    /**
     * codeStep()
     * ข้อมูลขั้นที่ 2 (กรอกรหัส 6 หลัก) เมื่อขอรหัสไปแล้ว
     * param : -
     * return : array (email, secondsLeft, cooldown, ttl, maxAttempts, webmail) หรือ null ถ้ายังไม่ได้ขอรหัส
     */
    private function codeStep(): ?array
    {
        $email = session(PasswordResetController::SESSION_EMAIL);
        if (!$email) {
            return null;
        }

        return [
            'email'       => $email,
            'secondsLeft' => PasswordResetOtp::secondsLeft($email),
            'cooldown'    => PasswordResetOtp::cooldownLeft($email),
            'ttl'         => PasswordResetOtp::TTL_MINUTES,
            'maxAttempts' => PasswordResetOtp::MAX_ATTEMPTS,
            'webmail'     => WebmailLink::for($email),
        ];
    }

    /**
     * resetStep()
     * ข้อมูลขั้นที่ 3 (ตั้งรหัสผ่านใหม่) เมื่อยืนยันรหัสแล้วและยังไม่หมดเวลา
     * param : -
     * return : array (email, minutesLeft) หรือ null
     */
    private function resetStep(): ?array
    {
        $state = session(PasswordResetController::SESSION_VERIFIED);
        if (!is_array($state) || ($state['expires_at'] ?? 0) <= now()->timestamp) {
            return null;
        }

        return [
            'email'       => $state['email'],
            'minutesLeft' => (int) ceil(($state['expires_at'] - now()->timestamp) / 60),
        ];
    }

    /**
     * verifyData()
     * ข้อมูลหน้าต่างตรวจอีเมลหลังสมัครสมาชิก
     * param : -
     * return : array (email, cooldown, ttl, webmail) หรือ null ถ้าไม่มีอีเมลรอยืนยันใน session
     */
    private function verifyData(): ?array
    {
        $email = session('verify_email');
        if (!$email) {
            return null;
        }

        $sentAt = (int) session('verify_sent_at', 0);

        return [
            'email'    => $email,
            'cooldown' => max(0, EmailVerificationController::RESEND_COOLDOWN_SECONDS - (now()->timestamp - $sentAt)),
            'ttl'      => PendingRegistration::TTL_MINUTES,
            'webmail'  => WebmailLink::for($email),
        ];
    }
}
