<?php

/**
 * AuthController
 * จัดการการสมัครสมาชิก เข้าสู่ระบบ และออกจากระบบของผู้ใช้
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 04/09/2569
 * Updated : 15/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Services\PendingRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * register(request)
     * ตรวจสอบข้อมูลสมัครสมาชิก แล้วเก็บข้อมูลชั่วคราวพร้อมส่งลิงก์ยืนยันอีเมล
     * (ยังไม่สร้างบัญชี บัญชี role = USER จะถูกสร้างและเข้าสู่ระบบเมื่อกดลิงก์ที่ EmailVerificationController::verify)
     * เก็บค่า redirect (หน้าที่ผู้ใช้อยู่ตอนกดสมัคร) ไปด้วย เพื่อพากลับหลังยืนยันอีเมลสำเร็จ
     * param : request
     * return : RedirectResponse กลับหน้าเดิมพร้อมเปิดหน้าต่างตรวจอีเมล ถ้าข้อมูลไม่ถูกต้องจะเปิดหน้าต่างสมัครสมาชิกพร้อม error
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'      => ['required', 'email', 'unique:app_user,email'],
            'password'   => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)\S{8,32}$/'],
            'first_name' => ['required', 'regex:/^[\p{Thai}a-zA-Z]{2,30}$/u'],
            'last_name'  => ['required', 'regex:/^[\p{Thai}a-zA-Z]{2,30}$/u'],
            'phone'      => ['required', 'regex:/^0[0-9]{9}$/', 'unique:app_user,phone'],
        ], [
            'email.required'      => 'กรุณากรอกอีเมล',
            'email.email'         => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique'        => 'อีเมลนี้ถูกใช้งานแล้ว',
            'password.required'   => 'กรุณากรอกรหัสผ่าน',
            'password.regex'      => 'รหัสผ่านต้องมี 8–32 ตัวอักษร และมีตัวอักษรอังกฤษพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข',
            'password.confirmed'  => 'รหัสผ่านยืนยันไม่ตรงกัน',
            'first_name.required' => 'กรุณากรอกชื่อ',
            'first_name.regex'    => 'ชื่อต้องมี 2–30 ตัวอักษร (ภาษาไทยหรืออังกฤษ)',
            'last_name.required'  => 'กรุณากรอกนามสกุล',
            'last_name.regex'     => 'นามสกุลต้องมี 2–30 ตัวอักษร (ภาษาไทยหรืออังกฤษ)',
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

        // อีเมลนี้มีการสมัครที่รอยืนยันอยู่แล้ว: ไม่ให้สมัครซ้ำจนกว่าลิงก์เดิมจะหมดอายุ
        if (PendingRegistration::hasEmail($request->email)) {
            $request->session()->put('verify_email', $request->email);

            return back()
                ->withErrors(['email' => 'อีเมลนี้ส่งลิงก์ยืนยันไปแล้ว กรุณาตรวจอีเมลหรือกดส่งลิงก์อีกครั้ง'])
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('open_modal', 'register')
                ->with('pending_verify', true);
        }

        // ยังไม่สร้างบัญชี: เก็บข้อมูลชั่วคราวและส่งลิงก์ยืนยัน บัญชีจะถูกสร้างเมื่อกดลิงก์
        $data = [
            'email'         => $request->email,
            'password_hash' => Hash::make($request->password),
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'phone'         => $request->phone,
            'redirect'      => $this->redirectTarget($request),
        ];
        $token = PendingRegistration::store($data);
        $request->session()->put('verify_email', $request->email);

        try {
            PendingRegistration::send($token, $data);
        } catch (\Throwable $e) {
            report($e);
            PendingRegistration::forget($token, $request->email);

            return back()
                ->withErrors(['email' => 'ส่งอีเมลยืนยันไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'])
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('open_modal', 'register');
        }

        $request->session()->put('verify_sent_at', now()->timestamp);

        // กลับหน้าเดิมแล้วเปิดหน้าต่างตรวจอีเมล (user/auth/partials/verify-email-modal)
        return back()->with('open_modal', 'verify-email');
    }

    /**
     * login(request)
     * ตรวจสอบอีเมล เบอร์โทร หรือชื่อผู้ใช้ (ช่อง email ใส่ได้ทั้งสามแบบ) รหัสผ่าน และสถานะบัญชี (ถูกระงับหรือไม่) แล้วเข้าสู่ระบบ
     * ใช้หน้าต่างเข้าสู่ระบบเดียวกันทุก role แล้วพาไปหน้าแรกของ role นั้น (ดู redirectAfterLogin)
     * param : request
     * return : RedirectResponse ไปหน้าตาม role ถ้าไม่ผ่านจะกลับหน้าเดิมพร้อม error
     */
    public function login(Request $request)
    {
        // ช่อง email รับได้ 3 แบบ: มี @ = อีเมล, ตัวเลขล้วน = เบอร์โทร 10 หลัก,
        // นอกนั้น = ชื่อผู้ใช้ (บัญชี Organizer/Staff ที่ไม่มีอีเมล เช่น gmm-gate-1)
        $identifier = trim((string) $request->input('email'));
        if (str_contains($identifier, '@')) {
            [$field, $rule, $message] = ['email', 'email', 'รูปแบบอีเมลไม่ถูกต้อง'];
        } elseif (ctype_digit($identifier)) {
            [$field, $rule, $message] = ['phone', 'regex:/^0[0-9]{9}$/', 'เบอร์โทรต้องเป็นตัวเลข 10 หลัก และขึ้นต้นด้วย 0'];
        } else {
            [$field, $rule, $message] = ['username', 'regex:/^[A-Za-z0-9._-]{3,50}$/', 'ชื่อผู้ใช้ใช้ได้เฉพาะตัวอักษรอังกฤษ ตัวเลข และ . _ -'];
        }

        $request->merge(['email' => $identifier]);
        $validator = Validator::make($request->all(), [
            'email'    => ['required', 'string', 'max:255', $rule],
            'password' => ['required'],
        ], [
            'email.required'    => 'กรุณากรอกอีเมล เบอร์โทรศัพท์ หรือชื่อผู้ใช้',
            'email.email'       => $message,
            'email.regex'       => $message,
            'password.required' => 'กรุณากรอกรหัสผ่าน',
        ]);

        // ข้อมูลไม่ครบ/รูปแบบผิด: กลับมาเปิดหน้าต่างเข้าสู่ระบบพร้อมข้อความ
        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('email', 'redirect'))
                ->with('open_modal', 'login');
        }

        $user = AppUser::where($field, $identifier)->first();

        if (!$user) {
            return back()
                ->withErrors(['email' => 'ไม่พบบัญชีนี้ในระบบ'])
                ->withInput($request->only('email', 'redirect'))
                ->with('open_modal', 'login');
        }

        if ($user->isBanned()) {
            return back()
                ->withErrors(['email' => 'บัญชีนี้ถูกระงับการใช้งาน'])
                ->withInput($request->only('email', 'redirect'))
                ->with('open_modal', 'login');
        }

        if (!Hash::check($request->password, $user->password_hash)) {
            return back()
                ->withErrors(['password' => 'รหัสผ่านไม่ถูกต้อง'])
                ->withInput($request->only('email', 'redirect'))
                ->with('open_modal', 'login');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect($this->redirectAfterLogin($request, $user));
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

    /**
     * redirectAfterLogin(request, user)
     * หน้าที่จะพาไปหลังเข้าสู่ระบบ
     * - ลูกค้า (USER): หน้าที่ส่งมาในช่อง redirect เหมือนเดิม (เช่นกลับหน้าจองบัตร) ไม่มี = หน้าแรก
     * - Organizer / Admin / Staff: หน้าแรกของ role (AppUser::homeUrl) ยกเว้น redirect ชี้ไปหน้าในส่วนของตัวเอง
     *   (เช่นเปิด /organizer/check-in ไว้ก่อน login) เพราะเปิดหน้าฝั่งลูกค้าไม่ได้
     * param : request, user
     * return : path (string)
     */
    private function redirectAfterLogin(Request $request, AppUser $user): string
    {
        $target = $this->redirectTarget($request);
        $prefix = $user->areaPrefix();

        if ($prefix === null) {
            return $target;
        }

        $path = parse_url($target, PHP_URL_PATH) ?: '/';

        return ($path === $prefix || str_starts_with($path, $prefix . '/')) ? $target : $user->homeUrl();
    }

    /**
     * redirectTarget(request)
     * หน้าที่จะพาไปหลัง login หรือหลังยืนยันอีเมลตอนสมัครสมาชิก จากช่อง redirect ในหน้าต่าง (เช่น /booking?concert_id=1)
     * รับเฉพาะ path ภายในเว็บ (ขึ้นต้นด้วย / ตัวเดียว) กันการพาออกไปเว็บอื่น (open redirect)
     * param : request
     * return : path (string) ถ้าไม่มีหรือไม่ถูกต้อง = '/'
     */
    private function redirectTarget(Request $request): string
    {
        $target = $request->input('redirect');

        $isLocalPath = is_string($target)
            && $target !== ''
            && strlen($target) <= 2048
            && str_starts_with($target, '/')
            && !str_starts_with($target, '//')
            && !str_contains($target, '\\')
            && !preg_match('/[\x00-\x1F\x7F]/', $target);

        return $isLocalPath ? $target : '/';
    }
}
