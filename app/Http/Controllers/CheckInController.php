<?php

/**
 * CheckInController
 * ตรวจสอบสิทธิ์เข้างาน ใช้ร่วมกันระหว่าง Organizer (หน้าจอคอม ใน sidebar) และ Staff (หน้ามือถือ)
 * ตอนนี้ยังมีแค่หน้า รอทำส่วนตรวจตั๋ว (สแกน QR / กรอกรหัสตั๋ว → บันทึก ticket_validation_log)
 * สิทธิ์ที่ต้องตรวจตอนทำ: Staff ตรวจได้เฉพาะคอนเสิร์ตที่ถูกมอบหมาย (staff_concert),
 *                    Organizer ตรวจได้เฉพาะคอนเสิร์ตขององค์กรตัวเอง
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 27/10/2569
 */

namespace App\Http\Controllers;

class CheckInController extends Controller
{
    /**
     * organizer()
     * หน้าตรวจสอบสิทธิ์เข้างานของ Organizer (route organizer.check-in)
     * param : -
     * return : View organizer.check-in
     */
    public function organizer()
    {
        return view('organizer.check-in');
    }

    /**
     * staff()
     * หน้าตรวจสอบสิทธิ์เข้างานของ Staff (route staff.check-in)
     * param : -
     * return : View staff.check-in
     */
    public function staff()
    {
        return view('staff.check-in');
    }
}
