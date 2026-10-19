<?php

/**
 * AuthController
 * จัดการการสมัครสมาชิก เข้าสู่ระบบ และออกจากระบบของผู้ใช้
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 04/09/2569
 */

namespace App\Http\Controllers;

use App\Models\AppUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * register(request)
     * ตรวจสอบข้อมูลสมัครสมาชิก สร้างบัญชีผู้ใช้ (role = USER ตรงกับ app_user.role ใน database) แล้วเข้าสู่ระบบให้ทันที
     * param : request
     * return : RedirectResponse ไปหน้าแรก (ถ้าข้อมูลไม่ถูกต้องจะกลับหน้าเดิมพร้อม error)
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'      => ['required', 'email', 'unique:app_user,email'],
            'password'   => ['required', 'confirmed', 'regex:/^(?=.*[A-Za-z])(?=.*\d)\S{6,}$/'],
            'first_name' => ['required', 'regex:/^[\p{Thai}a-zA-Z]{2,50}$/u'],
            'last_name'  => ['required', 'regex:/^[\p{Thai}a-zA-Z]{2,50}$/u'],
            'phone'      => ['required', 'regex:/^0[0-9]{9}$/', 'unique:app_user,phone'],
        ], [
            'email.required'      => 'กรุณากรอกอีเมล',
            'email.email'         => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique'        => 'อีเมลนี้ถูกใช้งานแล้ว',
            'password.required'   => 'กรุณากรอกรหัสผ่าน',
            'password.regex'      => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร มีตัวอักษรและตัวเลขอย่างน้อยอย่างละ 1 ตัว',
            'password.confirmed'  => 'รหัสผ่านยืนยันไม่ตรงกัน',
            'first_name.required' => 'กรุณากรอกชื่อ',
            'first_name.regex'    => 'ชื่อต้องมี 2–50 ตัวอักษร (ภาษาไทยหรืออังกฤษ)',
            'last_name.required'  => 'กรุณากรอกนามสกุล',
            'last_name.regex'     => 'นามสกุลต้องมี 2–50 ตัวอักษร (ภาษาไทยหรืออังกฤษ)',
            'phone.required'      => 'กรุณากรอกเบอร์โทรศัพท์',
            'phone.regex'         => 'เบอร์โทรต้องเป็นตัวเลข 10 หลัก และขึ้นต้นด้วย 0',
            'phone.unique'        => 'เบอร์โทรศัพท์นี้ถูกใช้งานแล้ว',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('open_modal', 'register');
        }

        // สร้าง Customer ใน app_user เดียว (single-table design per spec)
        $user = AppUser::create([
            'email'          => $request->email,
            'password_hash'  => Hash::make($request->password),
            'role'           => AppUser::ROLE_CUSTOMER,
            'account_status' => AppUser::STATUS_ACTIVE,
            'first_name'     => $request->first_name,
            'last_name'      => $request->last_name,
            'phone'          => $request->phone,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/')->with('status', 'ยินดีต้อนรับ! สมัครสมาชิกสำเร็จแล้ว 🎉');
    }

    /**
     * login(request)
     * ตรวจสอบอีเมล รหัสผ่าน และสถานะบัญชี (ถูกระงับหรือไม่) แล้วเข้าสู่ระบบ
     * param : request
     * return : RedirectResponse ไปหน้าแรก (ถ้าไม่ผ่านจะกลับหน้าเดิมพร้อม error)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required'    => 'กรุณากรอกอีเมล',
            'email.email'       => 'รูปแบบอีเมลไม่ถูกต้อง',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
        ]);

        $user = AppUser::where('email', $request->email)->first();

        if (!$user) {
            return back()
                ->withErrors(['email' => 'ไม่พบบัญชีนี้ในระบบ'])
                ->withInput($request->only('email'))
                ->with('open_modal', 'login');
        }

        if ($user->isBanned()) {
            return back()
                ->withErrors(['email' => 'บัญชีนี้ถูกระงับการใช้งาน'])
                ->withInput($request->only('email'))
                ->with('open_modal', 'login');
        }

        if (!Hash::check($request->password, $user->password_hash)) {
            return back()
                ->withErrors(['password' => 'รหัสผ่านไม่ถูกต้อง'])
                ->withInput($request->only('email'))
                ->with('open_modal', 'login');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect('/');
    }

    /**
     * logout(request)
     * ออกจากระบบ ยกเลิก session และสร้าง CSRF token ใหม่
     * param : request
     * return : RedirectResponse ไปหน้าแรก
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
