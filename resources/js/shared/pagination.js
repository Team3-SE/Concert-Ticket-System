/**
 * pagination.js
 * ปุ่ม "…" ในตัวแบ่งหน้า: พิมพ์เลขหน้าเพื่อกระโดดไปหน้านั้น (partials/pagination)
 * Enter หรือปุ่ม "ไป" = ไปหน้านั้น, Esc หรือคลิกที่อื่น = ปิด
 * Author : ธีรเมธ พลสาย
 * Created : 09/10/2569
 */

(() => {
  document.querySelectorAll('[data-pager]').forEach((pager) => {
    const dots = pager.querySelector('.pg-dots');
    if (!dots) return;

    const lastPage = parseInt(pager.dataset.lastPage, 10);
    const pageName = pager.dataset.pageName || 'page';
    let popover = null;

    /**
     * goTo(page)
     * ไปยังหน้าที่ระบุ (เปลี่ยนค่า page ใน URL)
     * param : page
     * return : -
     */
    const goTo = (page) => {
      const url = new URL(pager.dataset.url || window.location.href, window.location.href);
      url.searchParams.set(pageName, page);
      window.location.href = url.toString();
    };

    /**
     * close(focusDots)
     * ปิดกล่องพิมพ์เลขหน้า
     * param : focusDots
     * return : -
     */
    const close = (focusDots = false) => {
      if (!popover) return;
      popover.remove();
      popover = null;
      dots.classList.remove('open');
      dots.setAttribute('aria-expanded', 'false');
      if (focusDots) dots.focus();
    };

    /**
     * open()
     * เปิดกล่องพิมพ์เลขหน้าเหนือปุ่ม "…"
     * param : -
     * return : -
     */
    const open = () => {
      popover = document.createElement('div');
      popover.className = 'pg-popover';
      popover.addEventListener('click', (e) => e.stopPropagation());

      const input = document.createElement('input');
      input.type = 'number';
      input.min = 1;
      input.max = lastPage;
      input.placeholder = 'หน้า';
      input.setAttribute('aria-label', `เลขหน้า (1–${lastPage})`);

      const go = document.createElement('button');
      go.type = 'button';
      go.className = 'pg-popover-go';
      go.textContent = 'ไป';

      /**
       * commit()
       * ไปหน้าที่พิมพ์ถ้าเลขหน้าถูกต้อง ไม่งั้นปิดกล่อง
       * param : -
       * return : -
       */
      const commit = () => {
        const page = parseInt(input.value, 10);
        if (!Number.isNaN(page) && page >= 1 && page <= lastPage) {
          goTo(page);
        } else {
          close(true);
        }
      };

      input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); commit(); }
        if (e.key === 'Escape') { e.preventDefault(); close(true); }
      });
      go.addEventListener('click', commit);

      popover.append(input, go);
      dots.parentElement.appendChild(popover);
      dots.classList.add('open');
      dots.setAttribute('aria-expanded', 'true');
      setTimeout(() => input.focus(), 0);
    };

    dots.addEventListener('click', (e) => {
      e.stopPropagation();
      popover ? close() : open();
    });

    document.addEventListener('click', () => close());
  });
})();
