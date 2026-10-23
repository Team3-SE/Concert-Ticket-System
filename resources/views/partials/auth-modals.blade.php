{{--
 * auth-modals.blade.php
 * หน้าต่างเข้าสู่ระบบ / สมัครสมาชิก ใช้ได้ทุกหน้า (layouts/main ใส่ให้เฉพาะตอนยังไม่ login)
 * เปิด/ปิดด้วย resources/js/auth-modal.js, สไตล์อยู่ที่ resources/css/shared/auth-modal.css
 * ช่อง redirect = หน้าที่จะพาไปหลัง login/สมัครสมาชิกสำเร็จ (ว่าง = หน้า Home)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 23/10/2569
--}}
@php($openModal = session('open_modal') ?: (request()->query('login') === '1' ? 'login' : null))

{{-- Login Modal --}}
<div @class(['overlay', 'auth-overlay', 'open' => $openModal === 'login']) id="loginOverlay">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="loginTitle">
    <button type="button" class="modal-close" data-close aria-label="ปิด">✕</button>
    <h2 id="loginTitle">เข้าสู่ระบบ</h2>
    <p class="sub">ยินดีต้อนรับกลับมาอีกครั้ง</p>

    {{-- แสดง error --}}
    @if(session('open_modal') === 'login' && $errors->any())
      <div class="modal-error">{{ $errors->first() }}</div>
    @endif

    <form id="loginForm" method="POST" action="{{ route('auth.login') }}">
      @csrf
      <input type="hidden" name="redirect" value="{{ old('redirect') }}">
      <div class="field">
        <label>อีเมล</label>
        <input type="email" name="email" placeholder="example@email.com"
               value="{{ old('email') }}" required>
      </div>
      <div class="field">
        <label>รหัสผ่าน</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <div class="options-row">
        <label><input type="checkbox" name="remember" style="width:auto;"> จดจำฉัน</label>
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
    <button type="button" class="modal-close" data-close aria-label="ปิด">✕</button>
    <h2 id="registerTitle">สมัครสมาชิก</h2>
    <p class="sub">เป็นสมาชิกอยู่แล้ว? <a href="#" id="toLoginFromSub" class="sub-link">เข้าสู่ระบบ</a></p>

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
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <div class="field">
        <label>ยืนยันรหัสผ่าน <span class="req">*</span></label>
        <input type="password" name="password_confirmation" placeholder="••••••••" required>
      </div>
      <div class="row-2">
        <div class="field">
          <label>ชื่อ <span class="req">*</span></label>
          <input type="text" name="first_name" placeholder="ชื่อจริงตามบัตรประชาชน"
                 value="{{ old('first_name') }}" required>
        </div>
        <div class="field">
          <label>นามสกุล <span class="req">*</span></label>
          <input type="text" name="last_name" placeholder="นามสกุลตามบัตรประชาชน"
                 value="{{ old('last_name') }}" required>
        </div>
      </div>
      <div class="field">
        <label>เบอร์โทรศัพท์ <span class="req">*</span></label>
        <div class="phone-field">
          <span class="phone-prefix">🇹🇭 +66</span>
          <input type="tel" name="phone" placeholder="0XXXXXXXX"
                 value="{{ old('phone') }}" required>
        </div>
      </div>
      <button type="submit" class="btn-orange" style="margin-top:6px;">สมัครสมาชิก</button>
    </form>
  </div>
</div>
