/**
 * auth-modal.js
 * หน้าต่างเข้าสู่ระบบ / สมัครสมาชิก (user/partials/auth-modals) ใช้ได้ทุกหน้าตอนยังไม่ login
 * - ปุ่ม "เข้าสู่ระบบ" ใน navbar (#openLogin) → เปิดหน้าต่าง login, login เสร็จกลับมาหน้าเดิม
 * - ลิงก์/ปุ่มที่มี data-login-required → เปิดหน้าต่าง login แทนการไปหน้านั้น, login เสร็จไปหน้านั้นต่อ
 *   (ใส่ค่าใน data-login-required ได้ ถ้าไม่ใช่ลิงก์ เช่น data-login-required="/booking?concert_id=1")
 * - window.openLoginModal(url) ให้ script อื่นเรียกเปิดได้
 * - ช่องเบอร์โทรในฟอร์มสมัครสมาชิก: ใส่ได้เฉพาะตัวเลข สูงสุด 10 ตัว
 * - ลิงก์ "ลืมรหัสผ่าน?": เปิดหน้าต่างลืมรหัสผ่าน (user/auth/partials/forgot-modal) แทนการไปหน้าใหม่
 *   ถ้าอยู่ขั้นที่ 1 เติมอีเมลที่กรอกไว้ในช่องเข้าสู่ระบบให้, ถ้ายังทำค้างอยู่ (ขอรหัสแล้ว) จะเปิดขั้นที่ทำค้างไว้
 * - ปุ่ม/ลิงก์ data-open-login, data-open-register: สลับไปหน้าต่างเข้าสู่ระบบ / สมัครสมาชิก
 *   (เช่น "เข้าสู่ระบบ" ในหน้าต่างลืมรหัสผ่าน, "แก้ไข" อีเมลในหน้าต่างตรวจอีเมล user/auth/partials/verify-email-modal)
 * - หน้าต่างลืมรหัสผ่านที่เปิดมาตอนโหลดหน้า: วางเคอร์เซอร์ที่ช่องแรกของขั้นนั้นให้
 * การทำงานภายในหน้าต่างลืมรหัสผ่าน/ตรวจอีเมล (ช่องรหัส 6 หลัก นับถอยหลัง ฯลฯ) อยู่ที่ resources/js/user/auth-flow.js
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 23/10/2569
 * Updated : 15/11/2569 67160292 ธีรเมธ พลสาย
 */

(() => {
  const loginOverlay = document.getElementById('loginOverlay');
  const registerOverlay = document.getElementById('registerOverlay');
  if (!loginOverlay || !registerOverlay) return;
  const forgotOverlay = document.getElementById('forgotOverlay');
  const overlays = document.querySelectorAll('.auth-overlay');

  const redirectInputs = document.querySelectorAll('.auth-overlay input[name="redirect"]');

  /**
   * toLocalPath(url)
   * แปลง URL เป็น path ภายในเว็บ (/booking?concert_id=1) ให้ตรงกับที่ AuthController ยอมรับ
   * param : url (string)
   * return : path (string) หรือ '' ถ้าเป็นเว็บอื่น
   */
  const toLocalPath = (url) => {
    try {
      const target = new URL(url, window.location.origin);
      if (target.origin !== window.location.origin) return '';
      return target.pathname + target.search + target.hash;
    } catch {
      return '';
    }
  };

  const setRedirect = (url) => {
    const path = toLocalPath(url);
    redirectInputs.forEach((input) => { input.value = path; });
  };

  const open = (el) => el.classList.add('open');
  const close = (el) => el.classList.remove('open');
  const closeAll = () => overlays.forEach(close);

  /**
   * focusFirstField(overlay)
   * วางเคอร์เซอร์ที่ช่องกรอกแรกที่มองเห็นของหน้าต่าง (หน้าต่างลืมรหัสผ่าน = ขั้นที่แสดงอยู่)
   * param : overlay (element)
   * return : -
   */
  const focusFirstField = (overlay) => {
    overlay.querySelector('section:not([hidden]) input:not([type="hidden"]):not([hidden]):not(:disabled)')?.focus();
  };

  /**
   * openLogin(redirectUrl)
   * เปิดหน้าต่าง login และจำหน้าที่จะพาไปหลัง login สำเร็จ
   * param : redirectUrl (string) ถ้าไม่ส่ง = หน้าปัจจุบัน
   * return : -
   */
  const openLogin = (redirectUrl) => {
    setRedirect(redirectUrl || window.location.href);
    closeAll();
    open(loginOverlay);
    loginOverlay.querySelector('input[name="email"]')?.focus();
  };
  window.openLoginModal = openLogin;

  /**
   * openRegister()
   * เปิดหน้าต่างสมัครสมาชิก (สมัครเสร็จ/ยืนยันอีเมลแล้วกลับมาหน้าปัจจุบัน)
   * param : -
   * return : -
   */
  const openRegister = () => {
    setRedirect(window.location.href);
    closeAll();
    open(registerOverlay);
    registerOverlay.querySelector('input[name="email"]')?.focus();
  };

  /**
   * openForgot()
   * เปิดหน้าต่างลืมรหัสผ่านที่ขั้นที่แสดงอยู่ (ขั้นที่ 1 = เติมอีเมลจากช่องเข้าสู่ระบบถ้ากรอกเป็นอีเมลไว้)
   * param : -
   * return : -
   */
  const openForgot = () => {
    const typed = loginOverlay.querySelector('input[name="email"]')?.value.trim() || '';
    const step1 = forgotOverlay.querySelector('[data-forgot-step="1"]');
    if (step1 && !step1.hidden && typed.includes('@')) {
      step1.querySelector('input[name="email"]').value = typed;
    }
    closeAll();
    open(forgotOverlay);
    focusFirstField(forgotOverlay);
  };

  document.getElementById('openLogin')?.addEventListener('click', () => openLogin());

  // ลิงก์/ปุ่มที่ต้อง login ก่อน (เช่นปุ่ม "ซื้อบัตรการแสดง" หน้ารายละเอียดคอนเสิร์ต)
  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-login-required]');
    if (!trigger) return;
    event.preventDefault();
    openLogin(trigger.dataset.loginRequired || trigger.getAttribute('href'));
  });

  document.getElementById('toRegister')?.addEventListener('click', (event) => {
    event.preventDefault(); close(loginOverlay); open(registerOverlay);
  });
  document.getElementById('toLoginFromSub')?.addEventListener('click', (event) => {
    event.preventDefault(); close(registerOverlay); open(loginOverlay);
  });

  document.querySelectorAll('.auth-overlay [data-close]').forEach((btn) => {
    btn.addEventListener('click', closeAll);
  });
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeAll(); });

  // ลิงก์ "ลืมรหัสผ่าน?": เปิดหน้าต่างลืมรหัสผ่าน (ไม่มีหน้าต่าง = ไปตามลิงก์ ซึ่งพาไปหน้าแรกพร้อมเปิดหน้าต่าง)
  loginOverlay.querySelector('[data-forgot-password]')?.addEventListener('click', (event) => {
    if (!forgotOverlay) return;
    event.preventDefault();
    openForgot();
  });

  // สลับไปหน้าต่างเข้าสู่ระบบ / สมัครสมาชิก จากหน้าต่างลืมรหัสผ่านหรือหน้าต่างตรวจอีเมล
  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-open-login]')) {
      event.preventDefault();
      openLogin();
    } else if (event.target.closest('[data-open-register]')) {
      event.preventDefault();
      openRegister();
    }
  });

  // หน้าต่างลืมรหัสผ่านที่ controller สั่งเปิดมากับหน้า (กลับมาพร้อมขั้นถัดไป/ข้อความผิดพลาด)
  if (forgotOverlay?.classList.contains('open')) focusFirstField(forgotOverlay);

  // ช่องเบอร์โทร: ตัดตัวที่ไม่ใช่ตัวเลขออก (รวมถึงตอนวาง) และไม่ให้เกิน 10 ตัว
  const phoneInput = registerOverlay.querySelector('input[name="phone"]');
  phoneInput?.addEventListener('input', () => {
    phoneInput.value = phoneInput.value.replace(/\D/g, '').slice(0, 10);
  });
})();
