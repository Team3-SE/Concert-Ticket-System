<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * UserController
 * Spec: getProfile(), updateProfile(), changePassword()
 * Author : AI Agent
 * Created : 27/09/2026
 */
class UserController extends Controller
{
    /**
     * getProfile(userID): AppUser
     * ดึงข้อมูล APP_USER ซึ่งรวมข้อมูลตาม Role
     */
    public function getProfile(int $userId): AppUser
    {
        return AppUser::with('organization')->findOrFail($userId);
    }

    /**
     * updateProfile(userID, data): void
     * แก้ไขข้อมูลโปรไฟล์ใน APP_USER ตาม Role
     */
    public function updateProfile(Request $request, int $userId): void
    {
        $user = AppUser::findOrFail($userId);

        $validated = $request->validate([
            'first_name' => ['required', 'regex:/^[\p{Thai}a-zA-Z]{2,50}$/u'],
            'last_name'  => ['required', 'regex:/^[\p{Thai}a-zA-Z]{2,50}$/u'],
            'phone'      => ['required', 'regex:/^0[0-9]{9}$/', "unique:app_user,phone,{$userId},user_id"],
        ], [
            'first_name.required' => 'กรุณากรอกชื่อ',
            'first_name.regex'    => 'ชื่อต้องมี 2–50 ตัวอักษร (ภาษาไทยหรืออังกฤษ)',
            'last_name.required'  => 'กรุณากรอกนามสกุล',
            'last_name.regex'     => 'นามสกุลต้องมี 2–50 ตัวอักษร (ภาษาไทยหรืออังกฤษ)',
            'phone.required'      => 'กรุณากรอกเบอร์โทรศัพท์',
            'phone.regex'         => 'เบอร์โทรต้องเป็นตัวเลข 10 หลัก และขึ้นต้นด้วย 0',
            'phone.unique'        => 'เบอร์โทรศัพท์นี้ถูกใช้งานแล้ว',
        ]);

        $user->update($validated);
    }

    /**
     * changePassword(userID, password): void
     * Hash และเปลี่ยน passwordHash ของ APP_USER
     */
    public function changePassword(Request $request, int $userId): void
    {
        $user = AppUser::findOrFail($userId);

        $request->validate([
            'current_password' => ['required'],
            'new_password'     => ['required', 'confirmed', 'regex:/^(?=.*[A-Za-z])(?=.*\d)\S{6,}$/'],
        ], [
            'current_password.required' => 'กรุณากรอกรหัสผ่านปัจจุบัน',
            'new_password.required'     => 'กรุณากรอกรหัสผ่านใหม่',
            'new_password.regex'        => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร มีตัวอักษรและตัวเลขอย่างน้อยอย่างละ 1 ตัว',
            'new_password.confirmed'    => 'รหัสผ่านยืนยันไม่ตรงกัน',
        ]);

        if (!Hash::check($request->current_password, $user->password_hash)) {
            abort(422, 'รหัสผ่านปัจจุบันไม่ถูกต้อง');
        }

        $user->update(['password_hash' => Hash::make($request->new_password)]);
    }
}
