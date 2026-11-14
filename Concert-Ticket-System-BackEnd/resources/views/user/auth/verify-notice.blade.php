{{--
 * verify-notice.blade.php
 * หน้าแจ้งให้ตรวจอีเมลเพื่อกดยืนยันบัญชี หลังสมัครสมาชิก (การ์ดซองจดหมาย + 3 ขั้นตอน)
 * สไตล์ : resources/css/auth/verify-notice.css (<body class="page-verify">)
 * ข้อมูลจาก EmailVerificationController::notice : $email, $cooldown (วินาทีก่อนส่งซ้ำได้), $ttl (นาที), $webmail (ลิงก์เว็บเมลตามโดเมน หรือ null)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 17/10/2569
 * @Update Date 27/10/2569 67160292 ธีรเมธ พลสาย
--}}
@extends('layouts.user')

@section('title', 'ยืนยันอีเมล - TEAM 3 Ticket')


@section('body_class', 'page-verify min-h-screen flex flex-col bg-[#EBF1F9]')

@section('content')
<div class="vn-wrap">
  <div class="vn-card">
    <div class="vn-icon" aria-hidden="true">
      <span class="vn-icon__bg"></span>
      <span class="vn-icon__ring"></span>
      <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#f7941d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3.5 6.5l8.5 6.5 8.5-6.5"/></svg>
      <span class="vn-icon__dot">1</span>
    </div>

    <h1>ตรวจอีเมลของคุณ</h1>
    <p class="vn-lead">เราส่งลิงก์ยืนยันบัญชีไปที่</p>
    <div class="vn-chip">
      <span class="vn-chip__email" title="{{ $email }}">{{ $email }}</span>
      <button type="button" class="vn-chip__edit" data-open-register>แก้ไข</button>
    </div>

    <ol class="vn-steps">
      <li><b>1</b><span>เปิดอีเมลจาก TEAM 3 Ticket</span></li>
      <li><b>2</b><span>กดปุ่ม "ยืนยันอีเมล"</span></li>
      <li><b>3</b><span>เข้าสู่ระบบให้อัตโนมัติ</span></li>
    </ol>

    @if(session('status'))
      <div class="vn-alert vn-alert--ok" role="status">{{ session('status') }}</div>
    @endif
    @if(session('error'))
      <div class="vn-alert vn-alert--err" role="alert">{{ session('error') }}</div>
    @elseif($errors->any())
      <div class="vn-alert vn-alert--err" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="vn-actions">
      @if($webmail)
        <a class="vn-btn vn-btn--primary" href="{{ $webmail['url'] }}" target="_blank" rel="noopener">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
          เปิด {{ $webmail['label'] }}
        </a>
      @endif
      <form method="POST" action="{{ route('verification.resend') }}">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        <button type="submit" class="vn-btn {{ $webmail ? 'vn-btn--ghost' : 'vn-btn--primary' }}"
                data-resend data-cooldown="{{ $cooldown }}" @disabled($cooldown > 0)>
          ส่งลิงก์อีกครั้ง{{ $cooldown > 0 ? " ({$cooldown} วิ)" : '' }}
        </button>
      </form>
    </div>

    <p class="vn-foot">
      ลิงก์ใช้ได้ {{ $ttl }} นาที · ไม่พบอีเมล? ลองดูในโฟลเดอร์สแปม<br>
      <a href="{{ url('/') }}">กลับหน้าแรก</a>
    </p>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (() => {
    // ปุ่มส่งลิงก์อีกครั้ง: นับถอยหลังจนครบเวลา (กันกดรัว) แล้วจึงกดได้
    const btn = document.querySelector('[data-resend]');
    if (btn) {
      const label = 'ส่งลิงก์อีกครั้ง';
      let left = Number(btn.dataset.cooldown) || 0;
      let timer = null;
      const tick = () => {
        if (left <= 0) {
          clearInterval(timer);
          btn.disabled = false;
          btn.textContent = label;
          return;
        }
        btn.disabled = true;
        btn.textContent = `${label} (${left} วิ)`;
        left--;
      };
      tick();
      if (left > 0) timer = setInterval(tick, 1000);

      btn.form.addEventListener('submit', () => {
        btn.disabled = true;
        btn.textContent = 'กำลังส่ง...';
      });
    }

    // ปุ่ม "แก้ไข": เปิดหน้าต่างสมัครสมาชิก (user/partials/auth-modals) เพื่อสมัครด้วยอีเมลอื่น
    document.querySelector('[data-open-register]')?.addEventListener('click', () => {
      const overlay = document.getElementById('registerOverlay');
      if (!overlay) { window.location.href = '/'; return; }
      document.getElementById('loginOverlay')?.classList.remove('open');
      overlay.classList.add('open');
      overlay.querySelector('input[name="email"]')?.focus();
    });
  })();
</script>
@endpush
