/**
 * concert-management.js
 * จัดการตัวกรองสถานะและ popup ยืนยันลบในหน้าจัดการคอนเสิร์ต
 * Author : 67160279 กนกพล แซ่ตั๊น
 * Created : 26/11/2569
 * Updated : 26/11/2569 67160279 กนกพล แซ่ตั๊น
 */

(() => {
  const searchForm = document.querySelector('[data-concert-search]');
  if (searchForm) {
    const statusInput = searchForm.querySelector('[data-status-input]');

    searchForm.querySelectorAll('[data-status-value]').forEach((option) => {
      option.addEventListener('click', () => {
        statusInput.value = option.dataset.statusValue;
        searchForm.requestSubmit();
      });
    });
  }

  const dialog = document.querySelector('[data-delete-dialog]');
  if (!dialog) return;

  let pendingForm = null;
  document.querySelectorAll('[data-delete-trigger]').forEach((button) => {
    button.addEventListener('click', () => {
      pendingForm = button.closest('[data-delete-form]');
      dialog.querySelector('[data-delete-name]').textContent = button.dataset.concertName;
      dialog.showModal();
    });
  });

  dialog.querySelector('[data-delete-cancel]').addEventListener('click', () => dialog.close());
  dialog.querySelector('[data-delete-confirm]').addEventListener('click', () => {
    if (pendingForm) pendingForm.requestSubmit();
  });
  dialog.addEventListener('close', () => {
    pendingForm = null;
  });
})();
