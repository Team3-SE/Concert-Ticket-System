<?php

/**
 * PasswordResetController
 * ลืมรหัสผ่าน 3 ขั้น: กรอกอีเมล → กรอกรหัส 6 หลักที่ส่งไปทางอีเมล → ตั้งรหัสผ่านใหม่
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 29/10/2569
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

/*
 * ใช้ได้ทุกบัญชีที่มีอีเมล (User / Organizer / Admin) ยกเว้น Staff ซึ่งให้ Organizer รีเซ็ตรหัสผ่านให้
 * เปิดได้เฉพาะคนที่ยังไม่ได้เข้าสู่ระบบ (routes/web.php กลุ่ม role:GUEST)
 * รหัสเก็บใน Cache ผ่าน Services/PasswordResetOtp
 * ทุกขั้นเป็นหน้าต่าง (user/auth/partials/forgot-modal ใน user/partials/auth-modals): ส่งฟอร์มแล้วกลับหน้าเดิม
 * พร้อมเปิดหน้าต่างที่ขั้นถัดไปผ่าน session open_modal (forgot / forgot-code / forgot-reset)
 * ข้อมูลที่แสดงในหน้าต่างมาจาก View/Composers/AuthModalComposer
 */

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Services\PasswordResetOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /* session: อีเมลที่กำลังขอรีเซ็ต (ใช้ในขั้นกรอกรหัส, AuthModalComposer อ่านไปแสดงในหน้าต่าง) */
    public const SESSION_EMAIL = 'password_reset_email';

    /* session: สิทธิ์ตั้งรหัสผ่านใหม่หลังยืนยันรหัสแล้ว ['user_id', 'email', 'expires_at'] */
    public const SESSION_VERIFIED = 'password_reset_verified';

    /* กฎรหัสผ่านเดียวกับตอนสมัครสมาชิก (AuthController::register) */
    private const PASSWORD_REGEX = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)\S{8,32}$/';

    /**
     * showForgot($request)
     * ลิงก์เดิมของหน้าลืมรหัสผ่าน: พาไปหน้าแรกแล้วเปิดหน้าต่างลืมรหัสผ่านขั้นที่ 1 (เติมอีเมลให้ถ้ามี ?email=)
     *
     * @param Request $request คำขอ HTTP ที่ส่งมา
     * @return RedirectResponse เปลี่ยนเส้นทางไปหน้าที่เกี่ยวข้อง
     */
    public function showForgot(Request $request)
    {
        $email = trim((string) $request->query('email', ''));

        return $this->openOnHome($request, 'forgot', $email !== '' ? ['email' => $email] : []);
    }

    /**
     * sendCode($request)
     * ตรวจอีเมลแล้วส่งรหัส 6 หลัก (ถ้าเพิ่งส่งไปไม่ถึง 60 วินาที จะไม่ส่งซ้ำ พาไปขั้นกรอกรหัสเลย)
     *
     * @param Request $request email
     * @return RedirectResponse กลับหน้าเดิมพร้อมเปิดขั้นกรอกรหัส หรือขั้นที่ 1 พร้อม error
     */
    public function sendCode(Request $request)
    {
        $request->merge(['email' => trim((string) $request->input('email'))]);
        $validator = Validator::make($request->only('email'), [
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email'    => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.max'      => 'อีเมลยาวเกินไป',
        ]);
        if ($validator->fails()) {
            return $this->toStep('forgot')->withErrors($validator)->withInput($request->only('email'));
        }

        $user = AppUser::where('email', $request->email)->first();
        if ($error = $this->cannotReset($user)) {
            return $this->toStep('forgot')->withErrors(['email' => $error])->withInput($request->only('email'));
        }

        $request->session()->forget(self::SESSION_VERIFIED);
        $request->session()->put(self::SESSION_EMAIL, $user->email);

        if (PasswordResetOtp::cooldownLeft($user->email) > 0) {
            return $this->toStep('forgot-code')
                ->with('status', 'เราเพิ่งส่งรหัสไปที่อีเมลนี้ กรุณาตรวจอีเมล');
        }

        try {
            PasswordResetOtp::send($user);
        } catch (\Throwable $e) {
            report($e);
            $request->session()->forget(self::SESSION_EMAIL);

            return $this->toStep('forgot')
                ->withErrors(['email' => 'ส่งอีเมลไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'])
                ->withInput($request->only('email'));
        }

        return $this->toStep('forgot-code');
    }

    /**
     * showVerify($request)
     * ลิงก์เดิมของหน้ากรอกรหัส: พาไปหน้าแรกแล้วเปิดหน้าต่างลืมรหัสผ่านขั้นที่ 2 (ยังไม่ได้ขอรหัส = ขั้นที่ 1)
     *
     * @param Request $request คำขอ HTTP ที่ส่งมา
     * @return RedirectResponse เปลี่ยนเส้นทางไปหน้าที่เกี่ยวข้อง
     */
    public function showVerify(Request $request)
    {
        return $this->openOnHome($request, 'forgot-code');
    }

    /**
     * verifyCode($request)
     * ตรวจรหัสที่กรอก (ช่อง digits[] 6 ช่อง หรือ code) ถูกแล้วให้สิทธิ์ตั้งรหัสผ่านใหม่ภายใน RESET_WINDOW_MINUTES นาที
     *
     * @param Request $request คำขอ HTTP ที่ส่งมา
     * @return RedirectResponse กลับหน้าเดิมพร้อมเปิดขั้นที่ 3 หรือขั้นกรอกรหัสพร้อม error
     */
    public function verifyCode(Request $request)
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        if (!$email) {
            return $this->toStep('forgot');
        }

        $digits = $request->input('digits');
        $code = is_array($digits) ? implode('', $digits) : (string) $request->input('code', '');
        $code = preg_replace('/\s+/', '', $code);

        if (!preg_match('/^\d{6}$/', $code)) {
            return $this->toStep('forgot-code')->withErrors(['code' => 'กรุณากรอกรหัสตัวเลขให้ครบ 6 หลัก']);
        }

        $result = PasswordResetOtp::check($email, $code);

        if ($result['status'] === PasswordResetOtp::INVALID) {
            return $this->toStep('forgot-code')->withErrors(['code' => "รหัสไม่ถูกต้อง กรอกได้อีก {$result['left']} ครั้ง"]);
        }
        if ($result['status'] === PasswordResetOtp::LOCKED) {
            return $this->toStep('forgot-code')->withErrors(['code' => 'กรอกรหัสผิดครบ ' . PasswordResetOtp::MAX_ATTEMPTS . ' ครั้ง รหัสนี้ใช้ไม่ได้แล้ว กรุณากดส่งรหัสอีกครั้ง']);
        }
        if ($result['status'] === PasswordResetOtp::EXPIRED) {
            return $this->toStep('forgot-code')->withErrors(['code' => 'รหัสหมดอายุหรือถูกยกเลิกแล้ว กรุณากดส่งรหัสอีกครั้ง']);
        }

        $user = AppUser::find($result['user_id']);
        if ($error = $this->cannotReset($user)) {
            return $this->restart($request, $error);
        }

        $request->session()->put(self::SESSION_VERIFIED, [
            'user_id'    => $user->u_id,
            'email'      => $user->email,
            'expires_at' => now()->addMinutes(PasswordResetOtp::RESET_WINDOW_MINUTES)->timestamp,
        ]);
        $request->session()->regenerate();

        return $this->toStep('forgot-reset');
    }

    /**
     * resend($request)
     * ส่งรหัสใหม่ไปที่อีเมลเดิม (รหัสเดิมใช้ไม่ได้ทันที) จำกัดให้ห่างกันอย่างน้อย 60 วินาที
     *
     * @param Request $request คำขอ HTTP ที่ส่งมา
     * @return RedirectResponse กลับหน้าเดิมพร้อมเปิดขั้นกรอกรหัส
     */
    public function resend(Request $request)
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        if (!$email) {
            return $this->toStep('forgot');
        }

        // หน้าเว็บนับถอยหลังให้แล้ว อันนี้กันกรณีส่งฟอร์มเอง
        if (PasswordResetOtp::cooldownLeft($email) > 0) {
            return $this->toStep('forgot-code')
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
            return $this->toStep('forgot-code')
                ->withErrors(['code' => 'ส่งอีเมลไม่สำเร็จ กรุณาลองใหม่อีกครั้งภายหลัง']);
        }

        return $this->toStep('forgot-code')
            ->with('status', 'ส่งรหัสใหม่แล้ว รหัสเดิมใช้ไม่ได้แล้ว กรุณาตรวจอีเมล');
    }

    /**
     * showReset($request)
     * ลิงก์เดิมของหน้าตั้งรหัสผ่านใหม่: พาไปหน้าแรกแล้วเปิดหน้าต่างลืมรหัสผ่านขั้นที่ 3 (ยังไม่ได้ยืนยันรหัสหรือหมดเวลา = ขั้นที่ 1)
     *
     * @param Request $request คำขอ HTTP ที่ส่งมา
     * @return RedirectResponse เปลี่ยนเส้นทางไปหน้าที่เกี่ยวข้อง
     */
    public function showReset(Request $request)
    {
        return $this->openOnHome($request, 'forgot-reset');
    }

    /**
     * reset($request)
     * บันทึกรหัสผ่านใหม่ (hash) และสุ่ม remember_token ใหม่ ให้เครื่องอื่นที่เลือก "จำฉันไว้" ต้องเข้าสู่ระบบใหม่
     *
     * @param Request $request password
     * @return RedirectResponse กลับหน้าเดิมพร้อมหน้าต่างเข้าสู่ระบบ (เติมอีเมลให้) หรือขั้นตั้งรหัสใหม่พร้อม error
     */
    public function reset(Request $request)
    {
        $state = $this->verifiedState($request);
        if (!is_array($state)) {
            return $state;
        }

        $validator = Validator::make($request->only('password', 'password_confirmation'), [
            'password' => ['required', 'confirmed', 'regex:' . self::PASSWORD_REGEX],
        ], [
            'password.required'  => 'กรุณากรอกรหัสผ่านใหม่',
            'password.regex'     => 'รหัสผ่านต้องมี 8–32 ตัวอักษร และมีตัวอักษรอังกฤษพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข',
            'password.confirmed' => 'รหัสผ่านยืนยันไม่ตรงกัน',
        ]);
        if ($validator->fails()) {
            return $this->toStep('forgot-reset')->withErrors($validator);
        }

        $user = AppUser::find($state['user_id']);
        if ($error = $this->cannotReset($user)) {
            return $this->restart($request, $error);
        }

        $user->password_hash = Hash::make($request->password);
        $user->setRememberToken(Str::random(60));
        $user->save();

        PasswordResetOtp::forget($user->email);
        $request->session()->forget([self::SESSION_EMAIL, self::SESSION_VERIFIED]);

        // เปิดหน้าต่างเข้าสู่ระบบในหน้าเดิม เข้าสู่ระบบแล้วอยู่หน้าเดิมต่อ (ช่อง redirect)
        return back()
            ->with('open_modal', 'login')
            ->with('login_notice', 'ตั้งรหัสผ่านใหม่สำเร็จ กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่')
            ->withInput(['email' => $user->email, 'redirect' => $this->previousPath()]);
    }

    /**
     * verifiedState($request)
     * อ่านสิทธิ์ตั้งรหัสผ่านใหม่จาก session (ได้หลังยืนยันรหัสสำเร็จ)
     *
     * @param Request $request คำขอ HTTP ที่ส่งมา
     * @return array ['user_id', 'email', 'expires_at'] หรือ RedirectResponse กลับขั้นที่ 1 ถ้าไม่มีสิทธิ์/หมดเวลา
     */
    private function verifiedState(Request $request)
    {
        $state = $request->session()->get(self::SESSION_VERIFIED);

        if (!is_array($state)) {
            return $this->toStep('forgot')
                ->withErrors(['email' => 'กรุณายืนยันรหัสจากอีเมลก่อนตั้งรหัสผ่านใหม่']);
        }
        if ($state['expires_at'] <= now()->timestamp) {
            return $this->restart($request, 'หมดเวลาตั้งรหัสผ่านใหม่ กรุณาขอรหัสใหม่อีกครั้ง', $state['email']);
        }

        return $state;
    }

    /**
     * cannotReset($user)
     * เหตุผลที่บัญชีนี้ขอรีเซ็ตรหัสผ่านเองไม่ได้
     *
     * @param AppUser|null $user null = ไม่พบบัญชี
     * @return string|null ข้อความแจ้ง หรือ null ถ้ารีเซ็ตได้
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
     * restart($request, $message, $email)
     * ล้างขั้นตอนที่ค้างอยู่ แล้วกลับไปขั้นที่ 1 พร้อมข้อความ
     *
     * @param Request $request คำขอ HTTP ที่ส่งมา
     * @param string $message ข้อความ
     * @param string|null $email เติมให้ในช่องอีเมล
     * @return RedirectResponse เปลี่ยนเส้นทางไปหน้าที่เกี่ยวข้อง
     */
    private function restart(Request $request, string $message, ?string $email = null)
    {
        $email ??= $request->session()->get(self::SESSION_EMAIL);
        $request->session()->forget([self::SESSION_EMAIL, self::SESSION_VERIFIED]);

        return $this->toStep('forgot')
            ->withErrors(['email' => $message])
            ->withInput(['email' => (string) $email]);
    }

    /**
     * toStep($modal)
     * กลับหน้าที่ผู้ใช้อยู่ แล้วเปิดหน้าต่างลืมรหัสผ่านที่ขั้นนั้น
     *
     * @param string $modal forgot (ขั้นที่ 1 กรอกอีเมล
     * @return RedirectResponse เปลี่ยนเส้นทางไปหน้าที่เกี่ยวข้อง
     */
    private function toStep(string $modal)
    {
        return back()->with('open_modal', $modal);
    }

    /**
     * openOnHome($request, $modal, $input)
     * ลิงก์เดิมของหน้าแยก (ตอนนี้เป็นหน้าต่างแล้ว): พาไปหน้าแรกพร้อมเปิดหน้าต่างลืมรหัสผ่าน
     * เก็บข้อความ/error ที่ส่งมากับ redirect ก่อนหน้าไว้ด้วย (reflash) เผื่อมาจากหน้าเก่าที่เปิดค้างไว้
     *
     * @param Request $request คำขอ HTTP ที่ส่งมา
     * @param string $modal ใช้เมื่อไม่มี open_modal ส่งมาก่อน
     * @param array $input ค่าเริ่มต้นในช่องกรอก
     * @return RedirectResponse เปลี่ยนเส้นทางไปหน้าที่เกี่ยวข้อง
     */
    private function openOnHome(Request $request, string $modal, array $input = [])
    {
        $request->session()->reflash();
        $response = redirect('/')->with('open_modal', $request->session()->get('open_modal') ?: $modal);

        return $input ? $response->withInput($input) : $response;
    }

    /**
     * previousPath()
     * path ของหน้าที่ผู้ใช้อยู่ (เช่น /concert-detail?concert_id=1) ใช้เป็นช่อง redirect ของหน้าต่างเข้าสู่ระบบ
     *
     * @return string ข้อความตามที่อธิบายข้างต้น
     */
    private function previousPath(): string
    {
        $url = parse_url(url()->previous());

        return ($url['path'] ?? '/') . (isset($url['query']) ? '?' . $url['query'] : '');
    }
}
