{{--
 * verify-code.blade.php
 * ลืมรหัสผ่าน ขั้นที่ 2: กรอกรหัส 6 หลักที่ส่งไปทางอีเมล (ช่องละ 1 หลัก วางรหัสทั้งชุดได้ กรอกครบแล้วส่งเอง)
 * มีตัวนับเวลาที่รหัสยังใช้ได้ และปุ่มส่งรหัสอีกครั้งที่นับถอยหลังก่อนกดได้
 * สไตล์ : resources/css/user/auth/verify-notice.css + password-reset.css (<body class="page-verify page-reset">)
 * ข้อมูลจาก PasswordResetController::showVerify :
 *   $email, $secondsLeft (วินาทีที่รหัสยังใช้ได้ 0 = หมดอายุ), $cooldown (วินาทีก่อนส่งซ้ำได้),
 *   $ttl (อายุรหัส นาที), $maxAttempts (กรอกผิดได้กี่ครั้ง), $webmail (ลิงก์เว็บเมล หรือ null)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 29/10/2569
--}}
@extends('layouts.user')

@section('title', 'กรอกรหัสยืนยัน - TEAM 3 Ticket')
@section('body_class', 'page-verify page-reset min-h-screen flex flex-col bg-[#EBF1F9]')

@section('content')
<div class="vn-wrap">
  <div class="vn-card">
    <div class="vn-icon" aria-hidden="true">
      <span class="vn-icon__bg"></span>
      <span class="vn-icon__ring"></span>
      <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#f7941d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3.5 6.5l8.5 6.5 8.5-6.5"/></svg>
      <span class="vn-icon__dot">6</span>
    </div>

    <h1>กรอกรหัสยืนยัน</h1>
    <p class="vn-lead">เราส่งรหัส 6 หลักไปที่</p>
    <div class="vn-chip">
      <span class="vn-chip__email" title="{{ $email }}">{{ $email }}</span>
      <a class="vn-chip__edit" href="{{ route('password.forgot', ['email' => $email]) }}">เปลี่ยน</a>
    </div>

    @include('user.auth.partials.reset-steps', ['current' => 2])

    @if(session('status'))
      <div class="vn-alert vn-alert--ok" role="status">{{ session('status') }}</div>
    @endif
    @if($errors->any())
      <div class="vn-alert vn-alert--err" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.check') }}" class="pr-form" id="otpForm">
      @csrf
      <div @class(['pr-otp', 'is-error' => $errors->has('code')]) role="group" aria-label="รหัสยืนยัน 6 หลัก">
        @for($i = 0; $i < 6; $i++)
          {{-- ไม่ใส่ maxlength: ให้รหัสที่มือถือเติมให้อัตโนมัติ (one-time-code) ลงช่องแรกครบ 6 หลัก แล้ว JS กระจายไปช่องอื่น --}}
          <input class="pr-otp__box" type="text" name="digits[]" inputmode="numeric" pattern="[0-9]"
                 autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}" aria-label="หลักที่ {{ $i + 1 }}"
                 required @disabled($secondsLeft <= 0) @if($i === 0) autofocus @endif>
        @endfor
      </div>

      <p @class(['pr-timer', 'is-expired' => $secondsLeft <= 0]) data-expire="{{ $secondsLeft }}" aria-live="polite">
        @if($secondsLeft > 0)
          รหัสหมดอายุใน <b>{{ intdiv($secondsLeft, 60) }}:{{ str_pad($secondsLeft % 60, 2, '0', STR_PAD_LEFT) }}</b>
        @else
          รหัสหมดอายุแล้ว กรุณากดส่งรหัสอีกครั้ง
        @endif
      </p>

      <button type="submit" class="vn-btn vn-btn--primary" data-verify @disabled($secondsLeft <= 0)>ยืนยันรหัส</button>
    </form>

    <div class="vn-actions">
      @if($webmail)
        <a class="vn-btn vn-btn--ghost" href="{{ $webmail['url'] }}" target="_blank" rel="noopener">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
          เปิด {{ $webmail['label'] }}
        </a>
      @endif
      <form method="POST" action="{{ route('password.resend') }}">
        @csrf
        {{-- รหัสหมดอายุแล้ว: ปุ่มส่งรหัสอีกครั้งกลายเป็นปุ่มหลัก --}}
        <button type="submit" class="vn-btn {{ $secondsLeft > 0 ? 'vn-btn--ghost' : 'vn-btn--primary' }}" data-resend data-cooldown="{{ $cooldown }}" @disabled($cooldown > 0)>
          ส่งรหัสอีกครั้ง{{ $cooldown > 0 ? " ({$cooldown} วิ)" : '' }}
        </button>
      </form>
    </div>

    <p class="vn-foot">
      รหัสใช้ได้ {{ $ttl }} นาที กรอกผิดได้ไม่เกิน {{ $maxAttempts }} ครั้ง · ไม่พบอีเมล? ลองดูในโฟลเดอร์สแปม<br>
      <a href="{{ url('/') }}">กลับหน้าแรก</a>
    </p>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (() => {
    const form = document.getElementById('otpForm');
    const boxes = [...form.querySelectorAll('.pr-otp__box')];
    const group = form.querySelector('.pr-otp');
    const verifyBtn = form.querySelector('[data-verify]');
    let submitted = false;

    // ช่องรหัส 6 ช่อง: รับเฉพาะตัวเลข พิมพ์แล้วไปช่องถัดไป ลบแล้วถอยกลับ วางรหัสทั้งชุดได้ ครบ 6 หลักแล้วส่งเอง
    const fillFrom = (start, text) => {
      const digits = text.replace(/\D/g, '').slice(0, boxes.length - start).split('');
      digits.forEach((d, i) => { boxes[start + i].value = d; });
      return start + digits.length;
    };
    const refresh = () => {
      boxes.forEach((b) => b.classList.toggle('is-filled', b.value !== ''));
      group.classList.remove('is-error');
    };
    const submitIfComplete = () => {
      if (submitted || verifyBtn.disabled) return;
      if (boxes.every((b) => /^\d$/.test(b.value))) {
        submitted = true;
        verifyBtn.disabled = true;
        verifyBtn.textContent = 'กำลังตรวจสอบ...';
        form.submit();
      }
    };

    boxes.forEach((box, index) => {
      box.addEventListener('input', () => {
        const next = fillFrom(index, box.value);
        if (!/^\d?$/.test(box.value)) box.value = '';
        refresh();
        if (box.value !== '') boxes[Math.min(next, boxes.length - 1)].focus();
        submitIfComplete();
      });
      box.addEventListener('keydown', (event) => {
        // พิมพ์ทับช่องที่มีตัวเลขอยู่แล้ว = แทนที่ ไม่ใช่ต่อท้าย
        if (/^\d$/.test(event.key) && box.value !== '' && box.selectionStart === box.selectionEnd) {
          box.value = '';
        }
        if (event.key === 'Backspace' && box.value === '' && index > 0) {
          boxes[index - 1].value = '';
          boxes[index - 1].focus();
          refresh();
          event.preventDefault();
        } else if (event.key === 'ArrowLeft' && index > 0) {
          boxes[index - 1].focus();
        } else if (event.key === 'ArrowRight' && index < boxes.length - 1) {
          boxes[index + 1].focus();
        }
      });
      box.addEventListener('paste', (event) => {
        event.preventDefault();
        const next = fillFrom(index, (event.clipboardData || window.clipboardData).getData('text'));
        refresh();
        boxes[Math.min(next, boxes.length - 1)].focus();
        submitIfComplete();
      });
      box.addEventListener('focus', () => box.select());
    });

    form.addEventListener('submit', () => {
      submitted = true;
      verifyBtn.disabled = true;
      verifyBtn.textContent = 'กำลังตรวจสอบ...';
    });

    // นับเวลาที่รหัสยังใช้ได้ หมดแล้วปิดช่องกรอกและปุ่มยืนยัน
    const timer = form.querySelector('[data-expire]');
    let expireLeft = Number(timer.dataset.expire) || 0;
    const expireTick = () => {
      if (expireLeft <= 0) {
        clearInterval(expireTimer);
        timer.classList.add('is-expired');
        timer.textContent = 'รหัสหมดอายุแล้ว กรุณากดส่งรหัสอีกครั้ง';
        boxes.forEach((b) => { b.disabled = true; });
        verifyBtn.disabled = true;
        document.querySelector('[data-resend]')?.classList.replace('vn-btn--ghost', 'vn-btn--primary');
        return;
      }
      const m = Math.floor(expireLeft / 60);
      const s = String(expireLeft % 60).padStart(2, '0');
      timer.innerHTML = `รหัสหมดอายุใน <b>${m}:${s}</b>`;
      expireLeft--;
    };
    const expireTimer = expireLeft > 0 ? setInterval(expireTick, 1000) : null;
    expireTick();

    // ปุ่มส่งรหัสอีกครั้ง: นับถอยหลังจนครบเวลา (กันกดรัว) แล้วจึงกดได้
    const resend = document.querySelector('[data-resend]');
    if (resend) {
      const label = 'ส่งรหัสอีกครั้ง';
      let left = Number(resend.dataset.cooldown) || 0;
      let cooldownTimer = null;
      const tick = () => {
        if (left <= 0) {
          clearInterval(cooldownTimer);
          resend.disabled = false;
          resend.textContent = label;
          return;
        }
        resend.disabled = true;
        resend.textContent = `${label} (${left} วิ)`;
        left--;
      };
      tick();
      if (left > 0) cooldownTimer = setInterval(tick, 1000);

      resend.form.addEventListener('submit', () => {
        resend.disabled = true;
        resend.textContent = 'กำลังส่ง...';
      });
    }
  })();
</script>
@endpush
