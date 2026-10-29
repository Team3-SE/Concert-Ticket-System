{{--
 * reset-password.blade.php
 * ลืมรหัสผ่าน ขั้นที่ 3: ตั้งรหัสผ่านใหม่ (เปิดได้หลังยืนยันรหัสจากอีเมลแล้วเท่านั้น)
 * มีรายการเงื่อนไขรหัสผ่านที่ติ๊กถูกให้ทันทีขณะพิมพ์ และปุ่มรูปตา (resources/js/shared/password-toggle.js)
 * สไตล์ : resources/css/user/auth/verify-notice.css + password-reset.css + shared/password-toggle.css
 * ข้อมูลจาก PasswordResetController::showReset : $email, $minutesLeft (เวลาที่เหลือให้ตั้งรหัส นาที)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 29/10/2569
--}}
@extends('layouts.user')

@section('title', 'ตั้งรหัสผ่านใหม่ - TEAM 3 Ticket')
@section('body_class', 'page-verify page-reset min-h-screen flex flex-col bg-[#EBF1F9]')

@section('content')
<div class="vn-wrap">
  <div class="vn-card">
    <div class="vn-icon" aria-hidden="true">
      <span class="vn-icon__bg"></span>
      <span class="vn-icon__ring"></span>
      <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#f7941d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7.5 3v5.5c0 4.6-3.2 8.3-7.5 9.5-4.3-1.2-7.5-4.9-7.5-9.5V6L12 3z"/><path d="M8.5 12.2l2.4 2.4 4.6-4.8"/></svg>
    </div>

    <h1>ตั้งรหัสผ่านใหม่</h1>
    <p class="vn-lead">ยืนยันรหัสสำเร็จ ตั้งรหัสผ่านใหม่สำหรับบัญชี</p>
    <div class="vn-chip">
      <span class="vn-chip__email" title="{{ $email }}">{{ $email }}</span>
    </div>

    @include('user.auth.partials.reset-steps', ['current' => 3])

    @if($errors->any())
      <div class="vn-alert vn-alert--err" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="pr-form" id="resetForm">
      @csrf
      {{-- ให้โปรแกรมจัดการรหัสผ่านของเบราว์เซอร์รู้ว่าเป็นบัญชีไหน --}}
      <input type="text" name="username" value="{{ $email }}" autocomplete="username" hidden>

      <label class="pr-label" for="prPassword">รหัสผ่านใหม่</label>
      <div class="pw-wrap">
        <input id="prPassword" @class(['pr-input', 'is-error' => $errors->has('password')]) type="password" name="password"
               placeholder="••••••••" autocomplete="new-password" minlength="8" maxlength="32" required autofocus>
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

      <button type="submit" class="vn-btn vn-btn--primary" data-save>บันทึกรหัสผ่านใหม่</button>
    </form>

    <p class="pr-note">กรุณาตั้งรหัสผ่านภายใน {{ $minutesLeft }} นาที หลังบันทึกแล้ว อุปกรณ์อื่นที่เลือก “จำฉันไว้” ไว้ จะต้องเข้าสู่ระบบใหม่</p>

    <p class="vn-foot"><a href="{{ url('/') }}">ยกเลิก กลับหน้าแรก</a></p>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (() => {
    // ติ๊กเงื่อนไขรหัสผ่านขณะพิมพ์ (กฎเดียวกับฝั่งเซิร์ฟเวอร์: 8–32 ตัว มีพิมพ์เล็ก พิมพ์ใหญ่ ตัวเลข ไม่มีช่องว่าง)
    const form = document.getElementById('resetForm');
    const pw = form.querySelector('input[name="password"]');
    const confirmPw = form.querySelector('input[name="password_confirmation"]');
    const rules = {
      length: (v) => /^\S{8,32}$/.test(v),
      lower: (v) => /[a-z]/.test(v),
      upper: (v) => /[A-Z]/.test(v),
      digit: (v) => /\d/.test(v),
      match: (v) => v !== '' && v === confirmPw.value,
    };
    const check = () => {
      form.querySelectorAll('[data-rule]').forEach((li) => {
        li.classList.toggle('is-ok', rules[li.dataset.rule](pw.value));
      });
    };
    pw.addEventListener('input', check);
    confirmPw.addEventListener('input', check);

    form.addEventListener('submit', () => {
      const btn = form.querySelector('[data-save]');
      btn.disabled = true;
      btn.textContent = 'กำลังบันทึก...';
    });
  })();
</script>
@endpush
