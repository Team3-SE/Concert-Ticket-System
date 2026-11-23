<?php

/**
 * RegistrationVerification
 * อีเมลลิงก์ยืนยันการสมัครสมาชิก (ส่งไปยังอีเมลที่ยังไม่มีบัญชี)
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 17/10/2569
 * Updated : 27/10/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Notifications;

use App\Services\PendingRegistration;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationVerification extends Notification
{
    /**
     * __construct(url, firstName)
     * param : url – ลิงก์ยืนยัน (signed), firstName – ชื่อผู้สมัคร
     */
    public function __construct(private string $url, private string $firstName = '')
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
     * สร้างอีเมลภาษาไทยพร้อมปุ่มยืนยัน จาก template ของเราเอง
     * (HTML: emails/registration-verification, ข้อความล้วน: emails/registration-verification-text)
     * param : notifiable
     * return : MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('ยืนยันอีเมลเพื่อสมัครสมาชิก - TEAM 3 Ticket')
            ->view(
                [
                    'html' => 'emails.registration-verification',
                    'text' => 'emails.registration-verification-text',
                ],
                [
                    'url'       => $this->url,
                    'firstName' => trim($this->firstName),
                    'ttl'       => PendingRegistration::TTL_MINUTES,
                ]
            );
    }
}
