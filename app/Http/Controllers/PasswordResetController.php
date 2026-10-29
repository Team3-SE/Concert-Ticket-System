<?php

/**
 * PasswordResetController
 * ลืมรหัสผ่าน 3 ขั้น: กรอกอีเมล → กรอกรหัส 6 หลักที่ส่งไปทางอีเมล → ตั้งรหัสผ่านใหม่
 * ใช้ได้ทุกบัญชีที่มีอีเมล (User / Organizer / Admin) ยกเว้น Staff ซึ่งให้ Organizer รีเซ็ตรหัสผ่านให้
 * เปิดได้เฉพาะคนที่ยังไม่ได้เข้าสู่ระบบ (routes/web.php กลุ่ม role:GUEST)
 * รหัสเก็บใน Cache ผ่าน Services/PasswordResetOtp
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 29/10/2569
 */

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Services\PasswordResetOtp;
use App\Services\WebmailLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /* session: อีเมลที่กำลังขอรีเซ็ต (ใช้ในขั้นกรอกรหัส) */
    private const SESSION_EMAIL = 'password_reset_email';

    /* session: สิทธิ์ตั้งรหัสผ่านใหม่หลังยืนยันรหัสแล้ว ['user_id', 'email', 'expires_at'] */
    private const SESSION_VERIFIED = 'password_reset_verified';

    /* กฎรหัสผ่านเดียวกับตอนสมัครสมาชิก (AuthController::register) */
    private const PASSWORD_REGEX = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)\S{8,32}$/';

    /**
     * showForgot(request)
     * ขั้นที่ 1: หน้ากรอกอีเมลเพื่อขอรหัส (เติมอีเมลให้ถ้ามาจากหน้าต่างเข้าสู่ระบบ ?email=)
     * param : request
     * return : View user.auth.forgot-password (email)
     */
    public function showForgot(Request $request)
    {
        $email = (string) ($request->query('email') ?: $request->session()->get(self::SESSION_EMAIL, ''));

        return view('user.auth.forgot-password', ['email' => old('email', $email)]);
    }

    /**
     * sendCode(request)
     * ตรวจอีเมลแล้วส่งรหัส 6 หลัก (ถ้าเพิ่งส่งไปไม่ถึง 60 วินาที จะไม่ส่งซ้ำ พาไปหน้ากรอกรหัสเลย)
     * param : request – email
     * return : RedirectResponse ไปหน้ากรอกรหัส หรือกลับหน้าเดิมพร้อม error
     */
    public function sendCode(Request $request)
    {
        $request->merge(['email' => trim((string) $request->input('email'))]);
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email'    => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.max'      => 'อีเมลยาวเกินไป',
        ]);

        $user = AppUser::where('email', $request->email)->first();
        if ($error = $this->cannotReset($user)) {
            return back()->withErrors(['email' => $error])->withInput();
        }

        $request->session()->forget(self::SESSION_VERIFIED);
        $request->session()->put(self::SESSION_EMAIL, $user->email);

        if (PasswordResetOtp::cooldownLeft($user->email) > 0) {
            return redirect()->route('password.verify')
                ->with('status', 'เราเพิ่งส่งรหัสไปที่อีเมลนี้ กรุณาตรวจอีเมล');
        }

        try {
            PasswordResetOtp::send($user);
        } catch (\Throwable $e) {
            report($e);
            $request->session()->forget(self::SESSION_EMAIL);

            return back()->withErrors(['email' => 'ส่งอีเมลไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'])->withInput();
        }

        return redirect()->route('password.verify');
    }

    /**
     * showVerify(request)
     * ขั้นที่ 2: หน้ากรอกรหัส 6 หลัก พร้อมเวลาที่รหัสยังใช้ได้ และปุ่มส่งรหัสอีกครั้ง (นับถอยหลัง)
     * param : request
     * return : View user.auth.verify-code หรือกลับขั้นที่ 1 ถ้ายังไม่ได้ขอรหัส
     */
    public function showVerify(Request $request)
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        if (!$email) {
            return redirect()->route('password.forgot');
        }

        return view('user.auth.verify-code', [
            'email'       => $email,
            'secondsLeft' => PasswordResetOtp::secondsLeft($email),
            'cooldown'    => PasswordResetOtp::cooldownLeft($email),
            'ttl'         => PasswordResetOtp::TTL_MINUTES,
            'maxAttempts' => PasswordResetOtp::MAX_ATTEMPTS,
            'webmail'     => WebmailLink::for($email),
        ]);
    }

    /**
     * verifyCode(request)
     * ตรวจรหัสที่กรอก (ช่อง digits[] 6 ช่อง หรือ code) ถูกแล้วให้สิทธิ์ตั้งรหัสผ่านใหม่ภายใน RESET_WINDOW_MINUTES นาที
     * param : request
     * return : RedirectResponse ไปขั้นที่ 3 หรือกลับหน้ากรอกรหัสพร้อม error
     */
    public function verifyCode(Request $request)
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        if (!$email) {
            return redirect()->route('password.forgot');
        }

        $digits = $request->input('digits');
        $code = is_array($digits) ? implode('', $digits) : (string) $request->input('code', '');
        $code = preg_replace('/\s+/', '', $code);

        if (!preg_match('/^\d{6}$/', $code)) {
            return back()->withErrors(['code' => 'กรุณากรอกรหัสตัวเลขให้ครบ 6 หลัก']);
        }

        $result = PasswordResetOtp::check($email, $code);

        if ($result['status'] === PasswordResetOtp::INVALID) {
            return back()->withErrors(['code' => "รหัสไม่ถูกต้อง กรอกได้อีก {$result['left']} ครั้ง"]);
        }
        if ($result['status'] === PasswordResetOtp::LOCKED) {
            return back()->withErrors(['code' => 'กรอกรหัสผิดครบ ' . PasswordResetOtp::MAX_ATTEMPTS . ' ครั้ง รหัสนี้ใช้ไม่ได้แล้ว กรุณากดส่งรหัสอีกครั้ง']);
        }
        if ($result['status'] === PasswordResetOtp::EXPIRED) {
            return back()->withErrors(['code' => 'รหัสหมดอายุหรือถูกยกเลิกแล้ว กรุณากดส่งรหัสอีกครั้ง']);
        }

        $user = AppUser::find($result['user_id']);
        if ($error = $this->cannotReset($user)) {
            return $this->restart($request, $error);
        }

        $request->session()->put(self::SESSION_VERIFIED, [
            'user_id'    => $user->user_id,
            'email'      => $user->email,
            'expires_at' => now()->addMinutes(PasswordResetOtp::RESET_WINDOW_MINUTES)->timestamp,
        ]);
        $request->session()->regenerate();

        return redirect()->route('password.reset');
    }

    /**
     * resend(request)
     * ส่งรหัสใหม่ไปที่อีเมลเดิม (รหัสเดิมใช้ไม่ได้ทันที) จำกัดให้ห่างกันอย่างน้อย 60 วินาที
     * param : request
     * return : RedirectResponse กลับหน้ากรอกรหัส
     */
    public function resend(Request $request)
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        if (!$email) {
            return redirect()->route('password.forgot');
        }

        // หน้าเว็บนับถอยหลังให้แล้ว อันนี้กันกรณีส่งฟอร์มเอง
        if (PasswordResetOtp::cooldownLeft($email) > 0) {
            return redirect()->route('password.verify')
                ->withErrors(['code' => 'กรุณารอสักครู่ก่อนขอรหัสใหม่']);
        }

        $user = AppUser::where('email', $email)->first();
        if ($error = $this->cannotReset($user)) {
            return $this->restart($request, $error);
        }

        try {
            PasswordResetOtp::send($user);
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('password.verify')
                ->withErrors(['code' => 'ส่งอีเมลไม่สำเร็จ กรุณาลองใหม่อีกครั้งภายหลัง']);
        }

        return redirect()->route('password.verify')
            ->with('status', 'ส่งรหัสใหม่แล้ว รหัสเดิมใช้ไม่ได้แล้ว กรุณาตรวจอีเมล');
    }

    /**
     * showReset(request)
     * ขั้นที่ 3: หน้าตั้งรหัสผ่านใหม่ (ต้องยืนยันรหัสในขั้นที่ 2 ก่อน และยังไม่หมดเวลา)
     * param : request
     * return : View user.auth.reset-password (email, minutesLeft) หรือกลับขั้นที่ 1
     */
    public function showReset(Request $request)
    {
        $state = $this->verifiedState($request);
        if (!is_array($state)) {
            return $state;
        }

        return view('user.auth.reset-password', [
            'email'       => $state['email'],
            'minutesLeft' => (int) ceil(($state['expires_at'] - now()->timestamp) / 60),
        ]);
    }

    /**
     * reset(request)
     * บันทึกรหัสผ่านใหม่ (hash) และสุ่ม remember_token ใหม่ ให้เครื่องอื่นที่เลือก "จำฉันไว้" ต้องเข้าสู่ระบบใหม่
     * param : request – password, password_confirmation
     * return : RedirectResponse หน้าแรกพร้อมหน้าต่างเข้าสู่ระบบ (เติมอีเมลให้)
     */
    public function reset(Request $request)
    {
        $state = $this->verifiedState($request);
        if (!is_array($state)) {
            return $state;
        }

        $request->validate([
            'password' => ['required', 'confirmed', 'regex:' . self::PASSWORD_REGEX],
        ], [
            'password.required'  => 'กรุณากรอกรหัสผ่านใหม่',
            'password.regex'     => 'รหัสผ่านต้องมี 8–32 ตัวอักษร และมีตัวอักษรอังกฤษพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข',
            'password.confirmed' => 'รหัสผ่านยืนยันไม่ตรงกัน',
        ]);

        $user = AppUser::find($state['user_id']);
        if ($error = $this->cannotReset($user)) {
            return $this->restart($request, $error);
        }

        $user->password_hash = Hash::make($request->password);
        $user->setRememberToken(Str::random(60));
        $user->save();

        PasswordResetOtp::forget($user->email);
        $request->session()->forget([self::SESSION_EMAIL, self::SESSION_VERIFIED]);

        return redirect('/')
            ->with('open_modal', 'login')
            ->with('login_notice', 'ตั้งรหัสผ่านใหม่สำเร็จ กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่')
            ->withInput(['email' => $user->email]);
    }

    /**
     * verifiedState(request)
     * อ่านสิทธิ์ตั้งรหัสผ่านใหม่จาก session (ได้หลังยืนยันรหัสสำเร็จ)
     * param : request
     * return : array ['user_id', 'email', 'expires_at'] หรือ RedirectResponse กลับขั้นที่ 1 ถ้าไม่มีสิทธิ์/หมดเวลา
     */
    private function verifiedState(Request $request)
    {
        $state = $request->session()->get(self::SESSION_VERIFIED);

        if (!is_array($state)) {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'กรุณายืนยันรหัสจากอีเมลก่อนตั้งรหัสผ่านใหม่']);
        }
        if ($state['expires_at'] <= now()->timestamp) {
            return $this->restart($request, 'หมดเวลาตั้งรหัสผ่านใหม่ กรุณาขอรหัสใหม่อีกครั้ง', $state['email']);
        }

        return $state;
    }

    /**
     * cannotReset(user)
     * เหตุผลที่บัญชีนี้ขอรีเซ็ตรหัสผ่านเองไม่ได้
     * param : user (null = ไม่พบบัญชี)
     * return : string|null ข้อความแจ้ง หรือ null ถ้ารีเซ็ตได้
     */
    private function cannotReset(?AppUser $user): ?string
    {
        if (!$user || !$user->email) {
            return 'ไม่พบบัญชีนี้ในระบบ';
        }
        if ($user->isStaff()) {
            return 'บัญชี Staff รีเซ็ตรหัสผ่านเองไม่ได้ กรุณาติดต่อผู้จัดงาน (Organizer)';
        }
        if ($user->isBanned()) {
            return 'บัญชีนี้ถูกระงับการใช้งาน';
        }

        return null;
    }

    /**
     * restart(request, message, email)
     * ล้างขั้นตอนที่ค้างอยู่ แล้วกลับไปขั้นที่ 1 พร้อมข้อความ
     * param : request, message, email (เติมให้ในช่องอีเมล)
     * return : RedirectResponse
     */
    private function restart(Request $request, string $message, ?string $email = null)
    {
        $email ??= $request->session()->get(self::SESSION_EMAIL);
        $request->session()->forget([self::SESSION_EMAIL, self::SESSION_VERIFIED]);

        return redirect()->route('password.forgot')
            ->withErrors(['email' => $message])
            ->withInput(['email' => (string) $email]);
    }
}
