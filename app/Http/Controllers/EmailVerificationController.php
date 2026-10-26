<?php

/**
 * EmailVerificationController
 * ยืนยันอีเมลก่อนสร้างบัญชี: หน้าแจ้งให้ตรวจอีเมล, ลิงก์ยืนยัน (สร้างบัญชีจริง) และส่งลิงก์ซ้ำ
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 17/10/2569
 */

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Services\PendingRegistration;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    /**
     * notice(request)
     * แสดงหน้า "กรุณาตรวจอีเมลเพื่อยืนยัน" พร้อมปุ่มส่งลิงก์ซ้ำ
     * param : request
     * return : View auth.verify-notice หรือ redirect หน้าแรกถ้าไม่มีอีเมลรอยืนยัน
     */
    public function notice(Request $request)
    {
        $email = $request->session()->get('verify_email');

        if (!$email) {
            return redirect('/')->with('open_modal', 'register');
        }

        return view('auth.verify-notice', ['email' => $email]);
    }

    /**
     * verify(request, token)
     * ตรวจลิงก์ยืนยัน (signed URL) แล้วสร้างบัญชีจากข้อมูลที่เก็บไว้ชั่วคราว และเข้าสู่ระบบให้
     * param : request, token
     * return : RedirectResponse ไปหน้าแรก
     */
    public function verify(Request $request, string $token)
    {
        $data = PendingRegistration::find($token);

        if (!$data) {
            return $this->backToRegister('ลิงก์ยืนยันหมดอายุหรือไม่ถูกต้อง กรุณาสมัครสมาชิกใหม่');
        }

        // ตรวจซ้ำอีกครั้ง เผื่อมีคนสมัครด้วยอีเมล/เบอร์เดียวกันสำเร็จไปก่อน
        if (AppUser::where('email', $data['email'])->exists()) {
            PendingRegistration::forget($token, $data['email']);
            return redirect('/')->with('open_modal', 'login')
                ->withErrors(['email' => 'อีเมลนี้มีบัญชีอยู่แล้ว กรุณาเข้าสู่ระบบ']);
        }
        if (AppUser::where('phone', $data['phone'])->exists()) {
            PendingRegistration::forget($token, $data['email']);
            return $this->backToRegister('เบอร์โทรศัพท์นี้ถูกใช้งานแล้ว กรุณาสมัครสมาชิกใหม่ด้วยเบอร์อื่น');
        }

        try {
            $user = AppUser::create([
                'email'          => $data['email'],
                'password_hash'  => $data['password_hash'],
                'role'           => AppUser::ROLE_CUSTOMER,
                'account_status' => AppUser::STATUS_ACTIVE,
                'first_name'     => $data['first_name'],
                'last_name'      => $data['last_name'],
                'phone'          => $data['phone'],
            ]);
        } catch (UniqueConstraintViolationException $e) {
            PendingRegistration::forget($token, $data['email']);
            return $this->backToRegister('อีเมลหรือเบอร์โทรนี้ถูกใช้งานแล้ว กรุณาสมัครสมาชิกใหม่');
        }

        PendingRegistration::forget($token, $data['email']);
        $request->session()->forget('verify_email');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/')->with('verified', true);
    }

    /**
     * resend(request)
     * ส่งลิงก์ยืนยันอีกครั้งจากข้อมูลสมัครที่ยังรอยืนยัน (จำกัดความถี่ที่ route)
     * param : request
     * return : RedirectResponse กลับหน้าแจ้งตรวจอีเมล
     */
    public function resend(Request $request)
    {
        $request->validate(['email' => ['required', 'email']], [
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email'    => 'รูปแบบอีเมลไม่ถูกต้อง',
        ]);

        $token = PendingRegistration::tokenForEmail($request->email);
        $data  = $token ? PendingRegistration::find($token) : null;

        if (!$data) {
            return $this->backToRegister('ไม่พบข้อมูลที่รอยืนยันหรือหมดอายุแล้ว กรุณาสมัครสมาชิกใหม่');
        }

        try {
            PendingRegistration::send($token, $data);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'ส่งอีเมลไม่สำเร็จ กรุณาลองใหม่อีกครั้งภายหลัง');
        }

        $request->session()->put('verify_email', $request->email);

        return redirect()->route('verification.notice')
            ->with('status', 'ส่งลิงก์ยืนยันอีกครั้งแล้ว กรุณาตรวจอีเมล');
    }

    /**
     * backToRegister(message)
     * กลับหน้าแรกพร้อมเปิดโมดัลสมัครสมาชิกและแสดงข้อความผิดพลาด
     * param : message
     * return : RedirectResponse
     */
    private function backToRegister(string $message)
    {
        return redirect('/')->with('open_modal', 'register')
            ->withErrors(['email' => $message]);
    }
}
