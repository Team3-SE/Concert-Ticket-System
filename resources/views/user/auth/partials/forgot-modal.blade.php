{{--
 * forgot-modal.blade.php
 * หน้าต่างลืมรหัสผ่าน 3 ขั้น (กรอกอีเมล → กรอกรหัส 6 หลักจากอีเมล → ตั้งรหัสผ่านใหม่) แทนหน้าแยก forgot-password, verify-code, reset-password เดิม
 * แต่ละขั้นส่งฟอร์มไป PasswordResetController แล้วกลับหน้าเดิมพร้อมเปิดหน้าต่างนี้ที่ขั้นถัดไป (session open_modal)
 * แสดงทีละขั้น (<section data-forgot-step>) ขั้นที่ 2–3 มีเฉพาะตอนที่ยังทำค้างอยู่ใน session
 * ใช้ใน : user/partials/auth-modals  ข้อมูลจาก View/Composers/AuthModalComposer : $authOpen, $forgot (step, email, code, reset)
 * สไตล์ : resources/css/user/auth/verify-notice.css + password-reset.css  สคริปต์ : resources/js/user/auth-modal.js + auth-flow.js
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 15/11/2569
--}}
@php
  $forgotOpen = $authOpen === 'forgot';
  $errStep    = $forgotOpen ? $forgot['step'] : 0;   // แสดงข้อความ error เฉพาะขั้นที่ controller ส่งกลับมา
  $code       = $forgot['code'];
  $reset      = $forgot['reset'];
@endphp
<div @class(['overlay', 'auth-overlay', 'open' => $forgotOpen]) id="forgotOverlay">
  <div class="modal auth-flow" role="dialog" aria-modal="true" aria-labelledby="forgotTitle{{ $forgot['step'] }}">
    <button type="button" class="modal-close" data-close aria-label="ปิด">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </button>

    {{-- ขั้นที่ 1: กรอกอีเมลเพื่อขอรหัส --}}
    <section data-forgot-step="1" @if($forgot['step'] !== 1) hidden @endif>
      <div class="vn-icon" aria-hidden="true">
        <span class="vn-icon__bg"></span>
        <span class="vn-icon__ring"></span>
        <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#f7941d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V7.5a4 4 0 0 1 7.6-1.7"/><circle cx="12" cy="15.5" r="1.4"/><path d="M12 16.9v1.6"/></svg>
      </div>

      <h2 id="forgotTitle1">ลืมรหัสผ่าน</h2>
      <p class="vn-lead">กรอกอีเมลที่ใช้กับบัญชี เราจะส่งรหัสยืนยัน 6 หลักไปให้</p>

      @include('user.auth.partials.reset-steps', ['current' => 1])

      @if($errStep === 1 && $errors->any())
        <div class="vn-alert vn-alert--err" role="alert">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('password.send') }}" class="pr-form" data-submit-once>
        @csrf
        <label class="pr-label" for="prEmail">อีเมล</label>
        <input id="prEmail" @class(['pr-input', 'is-error' => $errStep === 1 && $errors->has('email')]) type="email" name="email" value="{{ $forgot['email'] }}"
               placeholder="example@email.com" autocomplete="email" maxlength="255" required>
        <button type="submit" class="vn-btn vn-btn--primary" data-loading="กำลังส่งรหัส...">ส่งรหัสยืนยัน</button>
      </form>

      <p class="pr-note">บัญชี Staff ที่เข้าสู่ระบบด้วยชื่อผู้ใช้ ไม่มีอีเมลสำหรับรับรหัส กรุณาติดต่อผู้จัดงาน (Organizer) เพื่อรีเซ็ตรหัสผ่าน</p>

      <p class="vn-foot">จำรหัสผ่านได้แล้ว? <a href="{{ url('/?login=1') }}" data-open-login>เข้าสู่ระบบ</a></p>
    </section>

    {{-- ขั้นที่ 2: กรอกรหัส 6 หลักที่ส่งไปทางอีเมล (มีเมื่อขอรหัสแล้ว) --}}
    @if($code)
    <section data-forgot-step="2" @if($forgot['step'] !== 2) hidden @endif>
      <div class="vn-icon" aria-hidden="true">
        <span class="vn-icon__bg"></span>
        <span class="vn-icon__ring"></span>
        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#f7941d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3.5 6.5l8.5 6.5 8.5-6.5"/></svg>
        <span class="vn-icon__dot">6</span>
      </div>

      <h2 id="forgotTitle2">กรอกรหัสยืนยัน</h2>
      <p class="vn-lead">เราส่งรหัส 6 หลักไปที่</p>
      <div class="vn-chip">
        <span class="vn-chip__email" title="{{ $code['email'] }}">{{ $code['email'] }}</span>
        <button type="button" class="vn-chip__edit" data-forgot-step-to="1">เปลี่ยน</button>
      </div>

      @include('user.auth.partials.reset-steps', ['current' => 2])

      @if($errStep === 2 && session('status'))
        <div class="vn-alert vn-alert--ok" role="status">{{ session('status') }}</div>
      @endif
      @if($errStep === 2 && $errors->any())
        <div class="vn-alert vn-alert--err" role="alert">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('password.check') }}" class="pr-form" data-otp-form>
        @csrf
        <div @class(['pr-otp', 'is-error' => $errStep === 2 && $errors->has('code')]) role="group" aria-label="รหัสยืนยัน 6 หลัก">
          @for($i = 0; $i < 6; $i++)
            {{-- ไม่ใส่ maxlength: ให้รหัสที่มือถือเติมให้อัตโนมัติ (one-time-code) ลงช่องแรกครบ 6 หลัก แล้ว JS กระจายไปช่องอื่น --}}
            <input class="pr-otp__box" type="text" name="digits[]" inputmode="numeric" pattern="[0-9]"
                   autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}" aria-label="หลักที่ {{ $i + 1 }}"
                   required @disabled($code['secondsLeft'] <= 0)>
          @endfor
        </div>

        <p @class(['pr-timer', 'is-expired' => $code['secondsLeft'] <= 0]) data-expire="{{ $code['secondsLeft'] }}" aria-live="polite">
          @if($code['secondsLeft'] > 0)
            รหัสหมดอายุใน <b>{{ intdiv($code['secondsLeft'], 60) }}:{{ str_pad($code['secondsLeft'] % 60, 2, '0', STR_PAD_LEFT) }}</b>
          @else
            รหัสหมดอายุแล้ว กรุณากดส่งรหัสอีกครั้ง
          @endif
        </p>

        <button type="submit" class="vn-btn vn-btn--primary" data-verify @disabled($code['secondsLeft'] <= 0)>ยืนยันรหัส</button>
      </form>

      <div class="vn-actions">
        @if($code['webmail'])
          <a class="vn-btn vn-btn--ghost" href="{{ $code['webmail']['url'] }}" target="_blank" rel="noopener">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
            เปิด {{ $code['webmail']['label'] }}
          </a>
        @endif
        <form method="POST" action="{{ route('password.resend') }}" data-submit-once>
          @csrf
          {{-- รหัสหมดอายุแล้ว: ปุ่มส่งรหัสอีกครั้งกลายเป็นปุ่มหลัก --}}
          <button type="submit" class="vn-btn {{ $code['secondsLeft'] > 0 ? 'vn-btn--ghost' : 'vn-btn--primary' }}" data-resend data-label="ส่งรหัสอีกครั้ง" data-loading="กำลังส่ง..." data-cooldown="{{ $code['cooldown'] }}" @disabled($code['cooldown'] > 0)>
            ส่งรหัสอีกครั้ง{{ $code['cooldown'] > 0 ? " ({$code['cooldown']} วิ)" : '' }}
          </button>
        </form>
      </div>

      <p class="vn-foot">รหัสใช้ได้ {{ $code['ttl'] }} นาที กรอกผิดได้ไม่เกิน {{ $code['maxAttempts'] }} ครั้ง · ไม่พบอีเมล? ลองดูในโฟลเดอร์สแปม</p>
    </section>
    @endif

    {{-- ขั้นที่ 3: ตั้งรหัสผ่านใหม่ (มีเมื่อยืนยันรหัสแล้วและยังไม่หมดเวลา) --}}
    @if($reset)
    <section data-forgot-step="3" @if($forgot['step'] !== 3) hidden @endif>
      <div class="vn-icon" aria-hidden="true">
        <span class="vn-icon__bg"></span>
        <span class="vn-icon__ring"></span>
        <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#f7941d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7.5 3v5.5c0 4.6-3.2 8.3-7.5 9.5-4.3-1.2-7.5-4.9-7.5-9.5V6L12 3z"/><path d="M8.5 12.2l2.4 2.4 4.6-4.8"/></svg>
      </div>

      <h2 id="forgotTitle3">ตั้งรหัสผ่านใหม่</h2>
      <p class="vn-lead">ยืนยันรหัสสำเร็จ ตั้งรหัสผ่านใหม่สำหรับบัญชี</p>
      <div class="vn-chip">
        <span class="vn-chip__email" title="{{ $reset['email'] }}">{{ $reset['email'] }}</span>
      </div>

      @include('user.auth.partials.reset-steps', ['current' => 3])

      @if($errStep === 3 && $errors->any())
        <div class="vn-alert vn-alert--err" role="alert">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('password.update') }}" class="pr-form" data-reset-form data-submit-once>
        @csrf
        {{-- ให้โปรแกรมจัดการรหัสผ่านของเบราว์เซอร์รู้ว่าเป็นบัญชีไหน --}}
        <input type="text" name="username" value="{{ $reset['email'] }}" autocomplete="username" hidden>

        <label class="pr-label" for="prPassword">รหัสผ่านใหม่</label>
        <div class="pw-wrap">
          <input id="prPassword" @class(['pr-input', 'is-error' => $errStep === 3 && $errors->has('password')]) type="password" name="password"
                 placeholder="••••••••" autocomplete="new-password" minlength="8" maxlength="32" required>
          <button type="button" class="pw-toggle" data-toggle-password aria-label="แสดง/ซ่อนรหัสผ่าน"><svg class="eye-open" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-6.5 10-6.5c1.6 0 3 .4 4.2 1M22 12s-3.5 6.5-10 6.5c-1.6 0-3-.4-4.2-1M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18"/></svg></button>
        </div>

        <label class="pr-label" for="prPasswordConfirm">ยืนยันรหัสผ่านใหม่</label>
        <div class="pw-wrap">
          <input id="prPasswordConfirm" class="pr-input" type="password" name="password_confirmation"
                 placeholder="••••••••" autocomplete="new-password" maxlength="32" required>
          <button type="button" class="pw-toggle" data-toggle-password aria-label="แสดง/ซ่อนรหัสผ่าน"><svg class="eye-open" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-6.5 10-6.5c1.6 0 3 .4 4.2 1M22 12s-3.5 6.5-10 6.5c-1.6 0-3-.4-4.2-1M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18"/></svg></button>
        </div>

        <ul class="pr-rules" aria-label="เงื่อนไขรหัสผ่าน">
          <li data-rule="length">ยาว 8–32 ตัวอักษร ไม่มีช่องว่าง</li>
          <li data-rule="lower">มีตัวอักษรอังกฤษพิมพ์เล็ก (a–z)</li>
          <li data-rule="upper">มีตัวอักษรอังกฤษพิมพ์ใหญ่ (A–Z)</li>
          <li data-rule="digit">มีตัวเลข (0–9)</li>
          <li data-rule="match">ยืนยันรหัสผ่านตรงกัน</li>
        </ul>

        <button type="submit" class="vn-btn vn-btn--primary" data-loading="กำลังบันทึก...">บันทึกรหัสผ่านใหม่</button>
      </form>

      <p class="pr-note">กรุณาตั้งรหัสผ่านภายใน {{ $reset['minutesLeft'] }} นาที หลังบันทึกแล้ว อุปกรณ์อื่นที่เลือก “จำฉันไว้” ไว้ จะต้องเข้าสู่ระบบใหม่</p>
    </section>
    @endif
  </div>
</div>
