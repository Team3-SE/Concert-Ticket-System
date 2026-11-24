/**
 * auth-flow.js
 * การทำงานภายในหน้าต่างลืมรหัสผ่าน (#forgotOverlay) และหน้าต่างตรวจอีเมลหลังสมัครสมาชิก (#verifyEmailOverlay)
 * ของ user/partials/auth-modals (ย้ายมาจาก script ในหน้าแยก forgot-password, verify-code, reset-password, verify-notice เดิม)
 * การเปิด/ปิดหน้าต่างอยู่ที่ resources/js/user/auth-modal.js
 * - ปุ่ม "เปลี่ยน" อีเมลในขั้นกรอกรหัส (data-forgot-step-to): กลับขั้นที่ 1 ในหน้าต่างเดิมโดยไม่โหลดหน้าใหม่
 * - ช่องรหัส 6 ช่อง (data-otp-form): รับเฉพาะตัวเลข ไปช่องถัดไปเอง วางรหัสทั้งชุดได้ ครบ 6 หลักแล้วส่งเอง
 *   และนับเวลาที่รหัสยังใช้ได้ หมดแล้วปิดช่องกรอก
 * - ปุ่มส่งรหัส/ลิงก์อีกครั้ง (data-resend): นับถอยหลังจนครบเวลา (กันกดรัว) แล้วจึงกดได้
 * - ฟอร์มตั้งรหัสผ่านใหม่ (data-reset-form): ติ๊กเงื่อนไขรหัสผ่านขณะพิมพ์
 * - ฟอร์มที่มี data-submit-once: กดส่งแล้วปิดปุ่ม (ข้อความระหว่างส่งจาก data-loading) กันส่งซ้ำ
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 15/11/2569
 */

(() => {
  const flows = document.querySelectorAll('.auth-flow');
  if (!flows.length) return;

  // กดส่งแล้วปิดปุ่มกันกดซ้ำ (เช่น ส่งอีเมลซ้ำ)
  document.querySelectorAll('.auth-flow form[data-submit-once]').forEach((form) => {
    form.addEventListener('submit', () => {
      const btn = form.querySelector('[data-loading]');
      if (btn) { btn.disabled = true; btn.textContent = btn.dataset.loading; }
    });
  });

  const forgotOverlay = document.getElementById('forgotOverlay');

  /**
   * showForgotStep(step)
   * แสดงขั้นที่เลือกในหน้าต่างลืมรหัสผ่าน (ซ่อนขั้นอื่น) แล้ววางเคอร์เซอร์ที่ช่องแรกของขั้นนั้น
   * param : step (1–3)
   * return : -
   */
  const showForgotStep = (step) => {
    if (!forgotOverlay) return;
    forgotOverlay.querySelectorAll('[data-forgot-step]').forEach((section) => {
      section.hidden = section.dataset.forgotStep !== String(step);
    });
    forgotOverlay.querySelector('[role="dialog"]')?.setAttribute('aria-labelledby', `forgotTitle${step}`);
    forgotOverlay.querySelector(`[data-forgot-step="${step}"] input:not([type="hidden"]):not([hidden]):not(:disabled)`)?.focus();
  };

  forgotOverlay?.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-forgot-step-to]');
    if (btn) showForgotStep(btn.dataset.forgotStepTo);
  });

  // ช่องรหัส 6 ช่อง + เวลาที่รหัสยังใช้ได้
  const otpForm = document.querySelector('.auth-flow [data-otp-form]');
  if (otpForm) {
    const section = otpForm.closest('[data-forgot-step]') || otpForm.parentElement;
    const boxes = [...otpForm.querySelectorAll('.pr-otp__box')];
    const group = otpForm.querySelector('.pr-otp');
    const verifyBtn = otpForm.querySelector('[data-verify]');
    let submitted = false;

    const fillFrom = (start, text) => {
      const digits = text.replace(/\D/g, '').slice(0, boxes.length - start).split('');
      digits.forEach((d, i) => { boxes[start + i].value = d; });
      return start + digits.length;
    };
    const refresh = () => {
      boxes.forEach((b) => b.classList.toggle('is-filled', b.value !== ''));
      group.classList.remove('is-error');
    };
    const lockAndShowLoading = () => {
      submitted = true;
      verifyBtn.disabled = true;
      verifyBtn.textContent = 'กำลังตรวจสอบ...';
    };
    const submitIfComplete = () => {
      if (submitted || verifyBtn.disabled) return;
      if (boxes.every((b) => /^\d$/.test(b.value))) {
        lockAndShowLoading();
        otpForm.submit();
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

    otpForm.addEventListener('submit', lockAndShowLoading);

    // นับเวลาที่รหัสยังใช้ได้ หมดแล้วปิดช่องกรอกและปุ่มยืนยัน ให้ปุ่มส่งรหัสอีกครั้งเป็นปุ่มหลัก
    const timer = otpForm.querySelector('[data-expire]');
    let expireLeft = Number(timer.dataset.expire) || 0;
    let expireTimer = null;
    const expireTick = () => {
      if (expireLeft <= 0) {
        clearInterval(expireTimer);
        timer.classList.add('is-expired');
        timer.textContent = 'รหัสหมดอายุแล้ว กรุณากดส่งรหัสอีกครั้ง';
        boxes.forEach((b) => { b.disabled = true; });
        verifyBtn.disabled = true;
        section.querySelector('[data-resend]')?.classList.replace('vn-btn--ghost', 'vn-btn--primary');
        return;
      }
      const m = Math.floor(expireLeft / 60);
      const s = String(expireLeft % 60).padStart(2, '0');
      timer.innerHTML = `รหัสหมดอายุใน <b>${m}:${s}</b>`;
      expireLeft--;
    };
    if (expireLeft > 0) expireTimer = setInterval(expireTick, 1000);
    expireTick();
  }

  // ปุ่มส่งรหัส/ลิงก์อีกครั้ง: นับถอยหลังจนครบเวลา (กันกดรัว) แล้วจึงกดได้
  document.querySelectorAll('.auth-flow [data-resend]').forEach((btn) => {
    const label = btn.dataset.label;
    let left = Number(btn.dataset.cooldown) || 0;
    let cooldownTimer = null;
    const tick = () => {
      if (left <= 0) {
        clearInterval(cooldownTimer);
        btn.disabled = false;
        btn.textContent = label;
        return;
      }
      btn.disabled = true;
      btn.textContent = `${label} (${left} วิ)`;
      left--;
    };
    tick();
    if (left > 0) cooldownTimer = setInterval(tick, 1000);
  });

  // ติ๊กเงื่อนไขรหัสผ่านขณะพิมพ์ (กฎเดียวกับฝั่งเซิร์ฟเวอร์: 8–32 ตัว มีพิมพ์เล็ก พิมพ์ใหญ่ ตัวเลข ไม่มีช่องว่าง)
  const resetForm = document.querySelector('.auth-flow [data-reset-form]');
  if (resetForm) {
    const pw = resetForm.querySelector('input[name="password"]');
    const confirmPw = resetForm.querySelector('input[name="password_confirmation"]');
    const rules = {
      length: (v) => /^\S{8,32}$/.test(v),
      lower: (v) => /[a-z]/.test(v),
      upper: (v) => /[A-Z]/.test(v),
      digit: (v) => /\d/.test(v),
      match: (v) => v !== '' && v === confirmPw.value,
    };
    const check = () => {
      resetForm.querySelectorAll('[data-rule]').forEach((li) => {
        li.classList.toggle('is-ok', rules[li.dataset.rule](pw.value));
      });
    };
    pw.addEventListener('input', check);
    confirmPw.addEventListener('input', check);
  }
})();
