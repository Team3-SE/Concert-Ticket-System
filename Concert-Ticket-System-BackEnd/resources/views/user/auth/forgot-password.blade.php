{{--
 * forgot-password.blade.php
 * ลืมรหัสผ่าน ขั้นที่ 1: กรอกอีเมลเพื่อขอรหัสยืนยัน 6 หลัก
 * ใช้การ์ดเดียวกับหน้ายืนยันอีเมล (<body class="page-verify page-reset">)
 * สไตล์ : resources/css/user/auth/verify-notice.css + password-reset.css
 * ข้อมูลจาก PasswordResetController::showForgot : $email (เติมให้ถ้ามาจากหน้าต่างเข้าสู่ระบบ)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 29/10/2569
--}}
@extends('layouts.user')

@section('title', 'ลืมรหัสผ่าน - TEAM 3 Ticket')
@section('body_class', 'page-verify page-reset min-h-screen flex flex-col bg-[#EBF1F9]')

@section('content')
<div class="vn-wrap">
  <div class="vn-card">
    <div class="vn-icon" aria-hidden="true">
      <span class="vn-icon__bg"></span>
      <span class="vn-icon__ring"></span>
      <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#f7941d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V7.5a4 4 0 0 1 7.6-1.7"/><circle cx="12" cy="15.5" r="1.4"/><path d="M12 16.9v1.6"/></svg>
    </div>

    <h1>ลืมรหัสผ่าน</h1>
    <p class="vn-lead">กรอกอีเมลที่ใช้กับบัญชี เราจะส่งรหัสยืนยัน 6 หลักไปให้</p>

    @include('user.auth.partials.reset-steps', ['current' => 1])

    @if($errors->any())
      <div class="vn-alert vn-alert--err" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.send') }}" class="pr-form" data-submit-once>
      @csrf
      <label class="pr-label" for="prEmail">อีเมล</label>
      <input id="prEmail" @class(['pr-input', 'is-error' => $errors->has('email')]) type="email" name="email" value="{{ $email }}"
             placeholder="example@email.com" autocomplete="email" maxlength="255" required autofocus>
      <button type="submit" class="vn-btn vn-btn--primary" data-loading="กำลังส่งรหัส...">ส่งรหัสยืนยัน</button>
    </form>

    <p class="pr-note">บัญชี Staff ที่เข้าสู่ระบบด้วยชื่อผู้ใช้ ไม่มีอีเมลสำหรับรับรหัส กรุณาติดต่อผู้จัดงาน (Organizer) เพื่อรีเซ็ตรหัสผ่าน</p>

    <p class="vn-foot">
      จำรหัสผ่านได้แล้ว? <a href="{{ url('/?login=1') }}">เข้าสู่ระบบ</a><br>
      <a href="{{ url('/') }}">กลับหน้าแรก</a>
    </p>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // กดส่งแล้วปิดปุ่มกันกดซ้ำ (ส่งอีเมลซ้ำ)
  document.querySelectorAll('form[data-submit-once]').forEach((form) => {
    form.addEventListener('submit', () => {
      const btn = form.querySelector('[data-loading]');
      if (btn) { btn.disabled = true; btn.textContent = btn.dataset.loading; }
    });
  });
</script>
@endpush
