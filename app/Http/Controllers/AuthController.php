<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * AuthController
 * handle user registration, login, and logout
 * Author : AI Agent
 * Created : 04/09/2026
 */
class AuthController extends Controller
{
    /**
     * register()
     * สมัครสมาชิกใหม่ (role = CUSTOMER)
     * สร้าง AppUser + Customer record พร้อมกัน แล้ว login ทันที
     */
    public function register(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email'      => ['required', 'email', 'unique:customer,email'],
            'password'   => ['required', 'confirmed', 'regex:/^(?=.*[A-Za-z])(?=.*\d)\S{6,}$/'],
            'first_name' => ['required', 'regex:/^[\p{Thai}a-zA-Z]{2,50}$/u'],
            'last_name'  => ['required', 'regex:/^[\p{Thai}a-zA-Z]{2,50}$/u'],
            'phone'      => ['required', 'regex:/^0[0-9]{9}$/', 'unique:customer,phone'],
        ], [
            'email.required'      => 'กรุณากรอกอีเมล',
            'email.email'         => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique'        => 'อีเมลนี้ถูกใช้งานแล้ว',
            'password.required'   => 'กรุณากรอกรหัสผ่าน',
            'password.regex'      => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร มีตัวอักษรภาษาอังกฤษและตัวเลขอย่างน้อยอย่างละ 1 ตัว และไม่มีช่องว่าง',
            'password.confirmed'  => 'รหัสผ่านยืนยันไม่ตรงกัน',
            'first_name.required' => 'กรุณากรอกชื่อ',
            'first_name.regex'    => 'ชื่อต้องมี 2–50 ตัวอักษร และใช้ได้เฉพาะภาษาไทยหรืออังกฤษเท่านั้น',
            'last_name.required'  => 'กรุณากรอกนามสกุล',
            'last_name.regex'     => 'นามสกุลต้องมี 2–50 ตัวอักษร และใช้ได้เฉพาะภาษาไทยหรืออังกฤษเท่านั้น',
            'phone.required'      => 'กรุณากรอกเบอร์โทรศัพท์',
            'phone.regex'         => 'เบอร์โทรต้องเป็นตัวเลข 10 หลัก และขึ้นต้นด้วย 0 (ตัวอย่าง: 0812345678)',
            'phone.unique'        => 'เบอร์โทรศัพท์นี้ถูกใช้งานแล้ว',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('open_modal', 'register');
        }

        // สร้าง AppUser (supertype) ก่อน — ULID จะถูกสร้างอัตโนมัติโดย HasUlids
        $appUser = AppUser::create([
            'password_hash'  => Hash::make($request->password),
            'role'           => AppUser::ROLE_CUSTOMER,
            'account_status' => AppUser::STATUS_ACTIVE,
        ]);

        // สร้าง Customer (subtype) โดยใช้ user_id เดียวกัน
        Customer::create([
            'customer_id' => $appUser->user_id,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'first_name'  => $request->first_name,
            'last_name'   => $request->last_name,
        ]);

        // Login ทันทีหลังสมัคร
        Auth::login($appUser);

        return redirect('/')->with('status', 'ยินดีต้อนรับ! สมัครสมาชิกสำเร็จแล้ว 🎉');
    }

    /**
     * login()
     * เข้าสู่ระบบด้วย email (จาก customer table) + password (จาก app_user table)
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

        // หา AppUser ผ่าน Customer.email
        $customer = Customer::where('email', $request->email)->first();

        if (! $customer) {
            return back()
                ->withErrors(['email' => 'ไม่พบบัญชีนี้ในระบบ'])
                ->withInput($request->only('email'))
                ->with('open_modal', 'login');
        }

        $appUser = $customer->appUser;

        if (! $appUser || ! $appUser->isActive()) {
            return back()
                ->withErrors(['email' => 'บัญชีนี้ถูกระงับการใช้งาน'])
                ->withInput($request->only('email'))
                ->with('open_modal', 'login');
        }

        if (! Hash::check($request->password, $appUser->password_hash)) {
            return back()
                ->withErrors(['password' => 'รหัสผ่านไม่ถูกต้อง'])
                ->withInput($request->only('email'))
                ->with('open_modal', 'login');
        }

        Auth::login($appUser, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect('/');
    }

    /**
     * logout()
     * ออกจากระบบและล้าง session
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

