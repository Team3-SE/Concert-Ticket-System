<?php

/**
 * EmailVerificationController
 * ยืนยันอีเมลก่อนสร้างบัญชี: หน้าแจ้งให้ตรวจอีเมล, ลิงก์ยืนยัน (สร้างบัญชีจริง) และส่งลิงก์ซ้ำ
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 17/10/2569
 * Updated : 29/10/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Services\PendingRegistration;
use App\Services\WebmailLink;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    /* ต้องรอกี่วินาทีหลังส่งลิงก์ ก่อนกดส่งซ้ำได้ (นับจาก verify_sent_at ใน session) */
    public const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * notice(request)
     * แสดงหน้า "กรุณาตรวจอีเมลเพื่อยืนยัน" พร้อมปุ่มเปิดเว็บเมลและปุ่มส่งลิงก์ซ้ำ (นับถอยหลัง)
     * param : request
     * return : View auth.verify-notice (email, cooldown, ttl, webmail) หรือ redirect หน้าแรกถ้าไม่มีอีเมลรอยืนยัน
     */
    public function notice(Request $request)
    {
        $email = $request->session()->get('verify_email');

        if (!$email) {
            return redirect('/')->with('open_modal', 'register');
        }

        $sentAt   = (int) $request->session()->get('verify_sent_at', 0);
        $cooldown = max(0, self::RESEND_COOLDOWN_SECONDS - (now()->timestamp - $sentAt));

        return view('user.auth.verify-notice', [
            'email'    => $email,
            'cooldown' => $cooldown,
            'ttl'      => PendingRegistration::TTL_MINUTES,
            'webmail'  => WebmailLink::for($email),
        ]);
    }

    /**
     * verify(request, token)
     * ตรวจลิงก์ยืนยัน (signed URL) แล้วสร้างบัญชีจากข้อมูลที่เก็บไว้ชั่วคราว และเข้าสู่ระบบให้
     * param : request, token
     * return : RedirectResponse กลับหน้าที่ผู้ใช้อยู่ตอนสมัคร (ไม่มี = หน้าแรกพร้อมหน้าต่างยืนยันสำเร็จ)
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

        // กลับหน้าเดิมที่เก็บไว้ตอนสมัคร (รับเฉพาะ path ภายในเว็บ) ไม่มีหรือเป็นหน้าแรกให้แสดงหน้าต่างยืนยันสำเร็จ
        $target = $data['redirect'] ?? '/';
        $isLocalPath = is_string($target)
            && str_starts_with($target, '/')
            && !str_starts_with($target, '//')
            && !str_contains($target, '\\');

        if (!$isLocalPath || $target === '/') {
            return redirect('/')->with('verified', true);
        }

        return redirect($target)->with('status', 'ยืนยันอีเมลสำเร็จ ยินดีต้อนรับสู่ TEAM 3 Ticket');
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

        // กันกดส่งซ้ำถี่เกินไป (หน้าเว็บนับถอยหลังให้แล้ว อันนี้กันกรณีส่งฟอร์มเอง)
        $sentAt = (int) $request->session()->get('verify_sent_at', 0);
        if (now()->timestamp - $sentAt < self::RESEND_COOLDOWN_SECONDS) {
            return redirect()->route('verification.notice')
                ->with('error', 'กรุณารอสักครู่ก่อนส่งลิงก์อีกครั้ง');
        }

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
        $request->session()->put('verify_sent_at', now()->timestamp);

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
