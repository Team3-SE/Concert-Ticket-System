/**
 * auth-modal.js
 * หน้าต่างเข้าสู่ระบบ / สมัครสมาชิก (user/partials/auth-modals) ใช้ได้ทุกหน้าตอนยังไม่ login
 * - ปุ่ม "เข้าสู่ระบบ" ใน navbar (#openLogin) → เปิดหน้าต่าง login, login เสร็จกลับมาหน้าเดิม
 * - ลิงก์/ปุ่มที่มี data-login-required → เปิดหน้าต่าง login แทนการไปหน้านั้น, login เสร็จไปหน้านั้นต่อ
 *   (ใส่ค่าใน data-login-required ได้ ถ้าไม่ใช่ลิงก์ เช่น data-login-required="/booking?concert_id=1")
 * - window.openLoginModal(url) ให้ script อื่นเรียกเปิดได้
 * - ช่องเบอร์โทรในฟอร์มสมัครสมาชิก: ใส่ได้เฉพาะตัวเลข สูงสุด 10 ตัว
 * - ลิงก์ "ลืมรหัสผ่าน?": ส่งอีเมลที่กรอกไว้ในช่องเข้าสู่ระบบไปเติมในหน้าลืมรหัสผ่าน
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 23/10/2569
 * Updated : 29/10/2569 67160292 ธีรเมธ พลสาย
 */

(() => {
  const loginOverlay = document.getElementById('loginOverlay');
  const registerOverlay = document.getElementById('registerOverlay');
  if (!loginOverlay || !registerOverlay) return;

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
  const closeAll = () => { close(loginOverlay); close(registerOverlay); };

  /**
   * openLogin(redirectUrl)
   * เปิดหน้าต่าง login และจำหน้าที่จะพาไปหลัง login สำเร็จ
   * param : redirectUrl (string) ถ้าไม่ส่ง = หน้าปัจจุบัน
   * return : -
   */
  const openLogin = (redirectUrl) => {
    setRedirect(redirectUrl || window.location.href);
    close(registerOverlay);
    open(loginOverlay);
    loginOverlay.querySelector('input[name="email"]')?.focus();
  };
  window.openLoginModal = openLogin;

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

  // ลิงก์ "ลืมรหัสผ่าน?": ถ้ากรอกอีเมลในช่องเข้าสู่ระบบไว้แล้ว ส่งต่อไปเติมในหน้าลืมรหัสผ่าน
  loginOverlay.querySelector('[data-forgot-password]')?.addEventListener('click', (event) => {
    const value = loginOverlay.querySelector('input[name="email"]')?.value.trim() || '';
    if (!value.includes('@')) return;
    event.preventDefault();
    const url = new URL(event.currentTarget.href, window.location.origin);
    url.searchParams.set('email', value);
    window.location.href = url.toString();
  });

  // ช่องเบอร์โทร: ตัดตัวที่ไม่ใช่ตัวเลขออก (รวมถึงตอนวาง) และไม่ให้เกิน 10 ตัว
  const phoneInput = registerOverlay.querySelector('input[name="phone"]');
  phoneInput?.addEventListener('input', () => {
    phoneInput.value = phoneInput.value.replace(/\D/g, '').slice(0, 10);
  });
})();
