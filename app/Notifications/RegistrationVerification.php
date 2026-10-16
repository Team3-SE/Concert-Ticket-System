<?php

/**
 * RegistrationVerification
 * อีเมลลิงก์ยืนยันการสมัครสมาชิก (ส่งไปยังอีเมลที่ยังไม่มีบัญชี)
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 17/10/2569
 */

namespace App\Notifications;

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
     * สร้างอีเมลภาษาไทยพร้อมปุ่มยืนยัน
     * param : notifiable
     * return : MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('ยืนยันอีเมลเพื่อสมัครสมาชิก - TEAM 3 Ticket')
            ->greeting('สวัสดี ' . $this->firstName)
            ->line('กรุณากดปุ่มด้านล่างเพื่อยืนยันอีเมลและสร้างบัญชีของคุณ')
            ->action('ยืนยันอีเมล', $this->url)
            ->line('ลิงก์นี้ใช้ได้ ' . \App\Services\PendingRegistration::TTL_MINUTES . ' นาที ถ้าคุณไม่ได้สมัครสมาชิก ไม่ต้องทำอะไร')
            ->salutation('TEAM 3 Ticket');
    }
}
