<?php

/**
 * UserController
 * จัดการข้อมูลบัญชีผู้ใช้: ดึงโปรไฟล์ แก้ไขโปรไฟล์ และเปลี่ยนรหัสผ่าน
 * Author :
 * Created :
 */

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * getProfile(userId)
     * ดึงข้อมูลบัญชีผู้ใช้พร้อมองค์กรที่สังกัด
     * param : userId
     * return : AppUser
     */
    public function getProfile(int $userId): AppUser
    {
        return AppUser::with('organization')->findOrFail($userId);
    }

    /**
     * updateProfile(request, userId)
     * ตรวจสอบแล้วบันทึกชื่อ นามสกุล และเบอร์โทรศัพท์ของผู้ใช้
     * param : request, userId
     * return : void
     */
    public function updateProfile(Request $request, int $userId): void
    {
        $user = AppUser::findOrFail($userId);

        $validated = $request->validate([
            'first_name' => ['required', 'regex:/^[\p{Thai}a-zA-Z]{2,30}$/u'],
            'last_name'  => ['required', 'regex:/^[\p{Thai}a-zA-Z]{2,30}$/u'],
            'phone'      => ['required', 'regex:/^0[0-9]{9}$/', "unique:app_user,phone,{$userId},user_id"],
        ], [
            'first_name.required' => 'กรุณากรอกชื่อ',
            'first_name.regex'    => 'ชื่อต้องมี 2–30 ตัวอักษร (ภาษาไทยหรืออังกฤษ)',
            'last_name.required'  => 'กรุณากรอกนามสกุล',
            'last_name.regex'     => 'นามสกุลต้องมี 2–30 ตัวอักษร (ภาษาไทยหรืออังกฤษ)',
            'phone.required'      => 'กรุณากรอกเบอร์โทรศัพท์',
            'phone.regex'         => 'เบอร์โทรต้องเป็นตัวเลข 10 หลัก และขึ้นต้นด้วย 0',
            'phone.unique'        => 'เบอร์โทรศัพท์นี้ถูกใช้งานแล้ว',
        ]);

        $user->update($validated);
    }

    /**
     * changePassword(request, userId)
     * ตรวจรหัสผ่านปัจจุบัน แล้วเปลี่ยนเป็นรหัสผ่านใหม่ (เก็บแบบ hash)
     * param : request, userId
     * return : void
     */
    public function changePassword(Request $request, int $userId): void
    {
        $user = AppUser::findOrFail($userId);

        $request->validate([
            'current_password' => ['required'],
            'new_password'     => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)\S{8,32}$/'],
        ], [
            'current_password.required' => 'กรุณากรอกรหัสผ่านปัจจุบัน',
            'new_password.required'     => 'กรุณากรอกรหัสผ่านใหม่',
            'new_password.regex'        => 'รหัสผ่านต้องมี 8–32 ตัวอักษร และมีตัวอักษรอังกฤษพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข',
            'new_password.confirmed'    => 'รหัสผ่านยืนยันไม่ตรงกัน',
        ]);

        if (!Hash::check($request->current_password, $user->password_hash)) {
            abort(422, 'รหัสผ่านปัจจุบันไม่ถูกต้อง');
        }

        $user->update(['password_hash' => Hash::make($request->new_password)]);
    }
}
