/**
 * layout.js (backoffice)
 * มือถือ/แท็บเล็ต (< 1024px): ปุ่มเมนูที่แถบด้านบน (data-bo-open) เปิด sidebar
 * ปิดได้ด้วยการกดพื้นหลังมืด (data-bo-close), ปุ่ม Esc หรือขยายจอกลับเป็นจอใหญ่
 * ใช้กับ : layouts/backoffice.blade.php, partials/backoffice/header.blade.php
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 27/10/2569
 */

(() => {
  const openBtn = document.querySelector('[data-bo-open]');
  if (!openBtn) return;

  const body = document.body;

  /**
   * setOpen(open)
   * เปิดหรือปิด sidebar
   * param : open (boolean)
   * return : -
   */
  const setOpen = (open) => {
    body.classList.toggle('bo-nav-open', open);
    openBtn.setAttribute('aria-expanded', String(open));
  };

  openBtn.addEventListener('click', () => setOpen(!body.classList.contains('bo-nav-open')));
  document.querySelectorAll('[data-bo-close]').forEach((el) => el.addEventListener('click', () => setOpen(false)));
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') setOpen(false); });
  window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => { if (event.matches) setOpen(false); });
})();
