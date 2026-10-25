{{--
 * auth-modals.blade.php
 * หน้าต่างเข้าสู่ระบบ / สมัครสมาชิก ใช้ได้ทุกหน้า (layouts/main ใส่ให้เฉพาะตอนยังไม่ login) ตามแบบ Figma
 * เปิด/ปิดด้วย resources/js/auth-modal.js, สไตล์อยู่ที่ resources/css/shared/auth-modal.css
 * ช่อง redirect = หน้าที่จะพาไปหลัง login/สมัครสมาชิกสำเร็จ (ว่าง = หน้า Home)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 23/10/2569
 * @Update Date 23/10/2569 67160292 ธีรเมธ พลสาย
--}}
@php($openModal = session('open_modal') ?: (request()->query('login') === '1' ? 'login' : null))

{{-- Login Modal --}}
<div @class(['overlay', 'auth-overlay', 'open' => $openModal === 'login']) id="loginOverlay">
  <div class="modal modal-login" role="dialog" aria-modal="true" aria-labelledby="loginTitle">
    <button type="button" class="modal-close" data-close aria-label="ปิด">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
    <h2 id="loginTitle">เข้าสู่ระบบ</h2>
    <p class="sub">กรุณากรอกข้อมูลเพื่อใช้งานต่อ</p>

    {{-- แสดง error --}}
    @if(session('open_modal') === 'login' && $errors->any())
      <div class="modal-error">{{ $errors->first() }}</div>
    @endif

    <form id="loginForm" method="POST" action="{{ route('auth.login') }}">
      @csrf
      <input type="hidden" name="redirect" value="{{ old('redirect') }}">
      <div class="field">
        <label>อีเมล / เบอร์โทรศัพท์</label>
        <input type="text" name="email" placeholder="example@email.com" autocomplete="username"
               value="{{ old('email') }}" required>
      </div>
      <div class="field">
        <label>รหัสผ่าน</label>
        <div class="pw-wrap">
          <input type="password" name="password" placeholder="••••••••" required>
          <button type="button" class="pw-toggle" data-toggle-password aria-label="แสดง/ซ่อนรหัสผ่าน"><svg class="eye-open" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-6.5 10-6.5c1.6 0 3 .4 4.2 1M22 12s-3.5 6.5-10 6.5c-1.6 0-3-.4-4.2-1M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18"/></svg></button>
        </div>
      </div>
      <div class="options-row">
        <label><input type="checkbox" name="remember"> จำฉันไว้</label>
        <a href="#">ลืมรหัสผ่าน?</a>
      </div>
      <button type="submit" class="btn-orange">เข้าสู่ระบบ</button>
    </form>
    <p class="switch-text">ยังไม่มีบัญชี? <a href="#" id="toRegister">สมัครสมาชิก</a></p>
  </div>
</div>

{{-- Register Modal --}}
<div @class(['overlay', 'auth-overlay', 'open' => $openModal === 'register']) id="registerOverlay">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="registerTitle">
    <button type="button" class="modal-close" data-close aria-label="ปิด">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
    <h2 id="registerTitle">สมัครสมาชิก</h2>
    <p class="sub">เป็นสมาชิกอยู่แล้วใช่ไหม? <a href="#" id="toLoginFromSub" class="sub-link">เข้าสู่ระบบ</a></p>

    {{-- แสดง error --}}
    @if(session('open_modal') === 'register' && $errors->any())
      <div class="modal-error">{{ $errors->first() }}</div>
    @endif

    <form id="registerForm" method="POST" action="{{ route('auth.register') }}">
      @csrf
      <input type="hidden" name="redirect" value="{{ old('redirect') }}">
      <div class="field">
        <label>อีเมล <span class="req">*</span></label>
        <input type="email" name="email" placeholder="example@email.com"
               value="{{ old('email') }}" required>
      </div>
      <div class="field">
        <label>รหัสผ่าน <span class="req">*</span></label>
        <div class="pw-wrap">
          <input type="password" name="password" placeholder="••••••••" minlength="8" required>
          <button type="button" class="pw-toggle" data-toggle-password aria-label="แสดง/ซ่อนรหัสผ่าน"><svg class="eye-open" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-6.5 10-6.5c1.6 0 3 .4 4.2 1M22 12s-3.5 6.5-10 6.5c-1.6 0-3-.4-4.2-1M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18"/></svg></button>
        </div>
      </div>
      <div class="field">
        <label>ยืนยันรหัสผ่าน <span class="req">*</span></label>
        <div class="pw-wrap">
          <input type="password" name="password_confirmation" placeholder="••••••••" required>
          <button type="button" class="pw-toggle" data-toggle-password aria-label="แสดง/ซ่อนรหัสผ่าน"><svg class="eye-open" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-6.5 10-6.5c1.6 0 3 .4 4.2 1M22 12s-3.5 6.5-10 6.5c-1.6 0-3-.4-4.2-1M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18"/></svg></button>
        </div>
        <ul class="pw-hint">
          <li>รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร,</li>
          <li>รหัสผ่านต้องมีตัวอักษรอังกฤษพิมพ์เล็ก, พิมพ์ใหญ่ และตัวเลข</li>
        </ul>
      </div>

      <h3 class="section-sub">ข้อมูลส่วนตัว (PERSONAL INFORMATION)</h3>

      <div class="field">
        <label>ชื่อจริง <span class="req">*</span></label>
        <input type="text" name="first_name" placeholder="กรุณากรอกเป็นภาษาไทยหรือภาษาอังกฤษเท่านั้น"
               value="{{ old('first_name') }}" maxlength="30" required>
      </div>
      <div class="field">
        <label>นามสกุล <span class="req">*</span></label>
        <input type="text" name="last_name" placeholder="กรุณากรอกเป็นภาษาไทยหรือภาษาอังกฤษเท่านั้น"
               value="{{ old('last_name') }}" maxlength="30" required>
      </div>
      <div class="field">
        <label>หมายเลขโทรศัพท์มือถือ <span class="req">*</span></label>
        <div class="phone-field">
          <span class="phone-prefix" aria-label="ประเทศไทย"><svg viewBox="0 0 24 16" width="24" height="16" aria-hidden="true"><rect width="24" height="16" fill="#A51931"/><rect y="2.67" width="24" height="10.67" fill="#F4F5F8"/><rect y="5.33" width="24" height="5.33" fill="#2D2A4A"/></svg></span>
          <input type="tel" name="phone" placeholder="0XXXXXXXXX" maxlength="10" inputmode="numeric"
                 pattern="0[0-9]{9}" title="เบอร์โทรต้องเป็นตัวเลข 10 หลัก และขึ้นต้นด้วย 0"
                 value="{{ old('phone') }}" required>
        </div>
      </div>
      <button type="submit" class="btn-orange">สมัครสมาชิก</button>
    </form>
  </div>
</div>
