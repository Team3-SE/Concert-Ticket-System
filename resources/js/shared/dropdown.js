/**
 * dropdown.js
 * เมนู dropdown ทั่วไป: กดปุ่มเพื่อเปิด/ปิด, คลิกที่อื่นหรือกด Esc เพื่อปิด, ใช้ปุ่มลูกศรเลื่อนเลือกรายการได้
 * วิธีใช้ : <div data-dropdown> <button data-dropdown-toggle> ... <div data-dropdown-menu hidden> ลิงก์/ปุ่ม </div> </div>
 * Author : ธีรเมธ พลสาย
 * Created : 10/10/2569
 */

(() => {
  const dropdowns = document.querySelectorAll('[data-dropdown]');
  if (!dropdowns.length) return;

  /**
   * setOpen(dropdown, open)
   * เปิดหรือปิดเมนูของ dropdown ที่ระบุ
   * param : dropdown, open
   * return : -
   */
  const setOpen = (dropdown, open) => {
    dropdown.querySelector('[data-dropdown-menu]').hidden = !open;
    dropdown.querySelector('[data-dropdown-toggle]').setAttribute('aria-expanded', String(open));
    dropdown.classList.toggle('is-open', open);
  };

  /**
   * closeAll(except)
   * ปิดทุก dropdown ยกเว้นตัวที่ระบุ
   * param : except
   * return : -
   */
  const closeAll = (except = null) => {
    dropdowns.forEach((dropdown) => {
      if (dropdown !== except) setOpen(dropdown, false);
    });
  };

  /**
   * moveFocus(dropdown, step)
   * เลื่อนโฟกัสไปยังรายการก่อนหน้า/ถัดไปในเมนู (step = -1 หรือ 1)
   * param : dropdown, step
   * return : -
   */
  const moveFocus = (dropdown, step) => {
    const items = [...dropdown.querySelectorAll('[data-dropdown-menu] a, [data-dropdown-menu] button')];
    if (!items.length) return;
    const current = items.indexOf(document.activeElement);
    const next = current === -1
      ? (step > 0 ? 0 : items.length - 1)
      : (current + step + items.length) % items.length;
    items[next].focus();
  };

  dropdowns.forEach((dropdown) => {
    const toggle = dropdown.querySelector('[data-dropdown-toggle]');
    const menu = dropdown.querySelector('[data-dropdown-menu]');
    if (!toggle || !menu) return;

    toggle.addEventListener('click', () => {
      const open = menu.hidden;
      closeAll(dropdown);
      setOpen(dropdown, open);
    });

    dropdown.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !menu.hidden) {
        setOpen(dropdown, false);
        toggle.focus();
      } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        if (menu.hidden) setOpen(dropdown, true);
        moveFocus(dropdown, event.key === 'ArrowDown' ? 1 : -1);
      }
    });

    // กด Tab ออกจาก dropdown แล้วปิดเมนู
    dropdown.addEventListener('focusout', (event) => {
      if (event.relatedTarget && !dropdown.contains(event.relatedTarget)) setOpen(dropdown, false);
    });
  });

  document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-dropdown]')) closeAll();
  });
})();
