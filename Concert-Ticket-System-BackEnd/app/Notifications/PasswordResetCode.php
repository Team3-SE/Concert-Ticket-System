<?php

/**
 * PasswordResetCode
 * อีเมลรหัสยืนยัน 6 หลักสำหรับตั้งรหัสผ่านใหม่ (ลืมรหัสผ่าน)
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 29/10/2569
 */

namespace App\Notifications;

use App\Services\PasswordResetOtp;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCode extends Notification
{
    /**
     * __construct(code, firstName)
     * param : code – รหัส 6 หลัก, firstName – ชื่อผู้ใช้ (ว่างได้ เช่นบัญชี Organizer/Admin ที่ไม่มีชื่อ)
     */
    public function __construct(private string $code, private string $firstName = '')
    {
    }

    /**
     * via(notifiable)
     * param : notifiable
     * return : array ช่องทางส่ง
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * toMail(notifiable)
     * สร้างอีเมลภาษาไทยจาก template ของเราเอง
     * (HTML: emails/password-reset-code, ข้อความล้วน: emails/password-reset-code-text)
     * param : notifiable
     * return : MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('รหัสยืนยันสำหรับตั้งรหัสผ่านใหม่ - TEAM 3 Ticket')
            ->view(
                [
                    'html' => 'emails.password-reset-code',
                    'text' => 'emails.password-reset-code-text',
                ],
                [
                    'code'        => $this->code,
                    'firstName'   => trim($this->firstName),
                    'ttl'         => PasswordResetOtp::TTL_MINUTES,
                    'maxAttempts' => PasswordResetOtp::MAX_ATTEMPTS,
                ]
            );
    }
}
