{{--
 * verify-email-modal.blade.php
 * หน้าต่าง "ตรวจอีเมลของคุณ" หลังสมัครสมาชิก (แทนหน้าแยก verify-notice เดิม): อีเมลที่ส่งลิงก์ไป + ปุ่มแก้ไข (กลับหน้าต่างสมัครสมาชิก)
 * 3 ขั้นตอน ปุ่มเปิดเว็บเมล และปุ่มส่งลิงก์อีกครั้ง (นับถอยหลัง) ส่งไป EmailVerificationController::resend แล้วกลับหน้าเดิมพร้อมเปิดหน้าต่างนี้
 * มีเฉพาะตอนที่มีอีเมลรอยืนยันใน session
 * ใช้ใน : user/partials/auth-modals  ข้อมูลจาก View/Composers/AuthModalComposer : $authOpen, $verify (email, cooldown, ttl, webmail)
 * สไตล์ : resources/css/user/auth/verify-notice.css  สคริปต์ : resources/js/user/auth-modal.js + auth-flow.js
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 15/11/2569
--}}
@if($verify)
@php($verifyOpen = $authOpen === 'verify-email')
<div @class(['overlay', 'auth-overlay', 'open' => $verifyOpen]) id="verifyEmailOverlay">
  <div class="modal auth-flow" role="dialog" aria-modal="true" aria-labelledby="verifyEmailTitle">
    <button type="button" class="modal-close" data-close aria-label="ปิด">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </button>

    <div class="vn-icon" aria-hidden="true">
      <span class="vn-icon__bg"></span>
      <span class="vn-icon__ring"></span>
      <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#f7941d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3.5 6.5l8.5 6.5 8.5-6.5"/></svg>
      <span class="vn-icon__dot">1</span>
    </div>

    <h2 id="verifyEmailTitle">ตรวจอีเมลของคุณ</h2>
    <p class="vn-lead">เราส่งลิงก์ยืนยันบัญชีไปที่</p>
    <div class="vn-chip">
      <span class="vn-chip__email" title="{{ $verify['email'] }}">{{ $verify['email'] }}</span>
      <button type="button" class="vn-chip__edit" data-open-register>แก้ไข</button>
    </div>

    <ol class="vn-steps">
      <li><b>1</b><span>เปิดอีเมลจาก TEAM 3 Ticket</span></li>
      <li><b>2</b><span>กดปุ่ม "ยืนยันอีเมล"</span></li>
      <li><b>3</b><span>เข้าสู่ระบบให้อัตโนมัติ</span></li>
    </ol>

    @if($verifyOpen && session('status'))
      <div class="vn-alert vn-alert--ok" role="status">{{ session('status') }}</div>
    @endif
    @if($verifyOpen && $errors->any())
      <div class="vn-alert vn-alert--err" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="vn-actions">
      @if($verify['webmail'])
        <a class="vn-btn vn-btn--primary" href="{{ $verify['webmail']['url'] }}" target="_blank" rel="noopener">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
          เปิด {{ $verify['webmail']['label'] }}
        </a>
      @endif
      <form method="POST" action="{{ route('verification.resend') }}" data-submit-once>
        @csrf
        <input type="hidden" name="email" value="{{ $verify['email'] }}">
        <button type="submit" class="vn-btn {{ $verify['webmail'] ? 'vn-btn--ghost' : 'vn-btn--primary' }}"
                data-resend data-label="ส่งลิงก์อีกครั้ง" data-loading="กำลังส่ง..." data-cooldown="{{ $verify['cooldown'] }}" @disabled($verify['cooldown'] > 0)>
          ส่งลิงก์อีกครั้ง{{ $verify['cooldown'] > 0 ? " ({$verify['cooldown']} วิ)" : '' }}
        </button>
      </form>
    </div>

    <p class="vn-foot">ลิงก์ใช้ได้ {{ $verify['ttl'] }} นาที · ไม่พบอีเมล? ลองดูในโฟลเดอร์สแปม</p>
  </div>
</div>
@endif
