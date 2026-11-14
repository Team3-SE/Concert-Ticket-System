/**
 * navbar.js
 * เมนูโปรไฟล์ จัดตัวอักษรในวงกลมโปรไฟล์ให้อยู่กลาง และปุ่มเข้าสู่ระบบ (user/partials/navbar)
 * Author : ธีรเมธ พลสาย
 * Created : 07/10/2569
 * Updated : 23/10/2569 67160292 ธีรเมธ พลสาย
 */

// เปิด/ปิดเมนูโปรไฟล์เมื่อกดที่ชื่อผู้ใช้ (user/partials/navbar)
(() => {
  const trigger = document.querySelector('[data-profile-toggle]');
  const menu = document.querySelector('[data-profile-menu]');
  if (!trigger || !menu) return;
  trigger.addEventListener('click', () => {
    const isOpen = !menu.hidden;
    menu.hidden = isOpen;
    trigger.setAttribute('aria-expanded', String(!isOpen));
  });
  document.addEventListener('click', (event) => {
    if (!event.target.closest('.profile-area')) {
      menu.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
    }
  });
})();

/*
 * จัดตัวอักษรในวงกลมโปรไฟล์ให้อยู่กึ่งกลางจริง ๆ โดยวัดขอบของตัวอักษรจากฟอนต์ที่ใช้อยู่
 * (ฟอนต์ไทยมีที่ว่างด้านบนสำหรับสระ/วรรณยุกต์ การจัดกึ่งกลางด้วย CSS อย่างเดียวจะดูสูงไป)
 */
(() => {
  const initial = document.querySelector('.profile-initial');
  if (!initial) return;

  /**
   * center()
   * จัดตัวอักษรในวงกลมโปรไฟล์ให้อยู่กึ่งกลาง โดยวัดขอบตัวอักษรจริงของฟอนต์
   * param : -
   * return : -
   */
  const center = () => {
    const text = initial.textContent.trim();
    if (!text) return;
    const ctx = document.createElement('canvas').getContext('2d');
    const cs = getComputedStyle(initial);
    ctx.font = `${cs.fontWeight} ${cs.fontSize} ${cs.fontFamily}`;
    const m = ctx.measureText(text);
    if (m.fontBoundingBoxAscent === undefined || m.actualBoundingBoxAscent === undefined) return;
    // ระยะที่ต้องเลื่อน = (กึ่งกลางกล่องข้อความ) - (กึ่งกลางตัวอักษรจริง)
    const dy = (m.fontBoundingBoxDescent - m.fontBoundingBoxAscent) / 2
             + (m.actualBoundingBoxAscent - m.actualBoundingBoxDescent) / 2;
    initial.style.transform = `translateY(${dy.toFixed(2)}px)`;
  };

  center();
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(center);
})();

/*
 * ปุ่ม "เข้าสู่ระบบ": ปกติเปิดหน้าต่าง login ในหน้านั้นเลย (user/partials/auth-modals + auth-modal.js)
 * สำรอง: หน้าไหนไม่มีหน้าต่างนี้ → พาไปหน้า Home แล้วเปิดหน้าต่าง login ให้
 */
(() => {
  const loginBtn = document.getElementById('openLogin');
  if (!loginBtn || document.getElementById('loginOverlay')) return;
  loginBtn.addEventListener('click', () => { window.location.href = '/?login=1'; });
})();
