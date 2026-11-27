/**
 * my-ticket.js
 * เปิดและปิด popup แสดงรายละเอียด e-ticket
 * Author : 67160279 กนกพล แซ่ตั๊น
 * Created : 19/10/2569
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย
 */

(() => {
  const modal = document.querySelector('[data-ticket-modal]');
  if (!modal) return;

  const closeButton = modal.querySelector('[data-ticket-close]');
  let activeTrigger = null;

  /**
   * closeTicketModal()
   * ปิด popup และคืน focus ไปยังปุ่มที่ใช้เปิด
   * param : -
   * return : -
   */
  const closeTicketModal = () => {
    modal.hidden = true;
    document.body.classList.remove('ticket-modal-open');
    if (activeTrigger) activeTrigger.focus();
    activeTrigger = null;
  };

  /**
   * showTicketDetails(ticketDetails)
   * แสดงข้อมูลตั๋วที่ TicketController ส่งกลับมาใน popup
   * param : ticketDetails
   * return : -
   */
  const showTicketDetails = (ticketDetails) => {
    modal.querySelector('[data-ticket-name]').textContent = ticketDetails.concert_name;
    modal.querySelector('[data-ticket-date]').textContent = ticketDetails.event_date;
    modal.querySelector('[data-ticket-time]').textContent = `${ticketDetails.event_time} น.`;
    modal.querySelector('[data-ticket-zone]').textContent = ticketDetails.zone;
    modal.querySelector('[data-ticket-seat]').textContent = ticketDetails.seat;
    modal.querySelector('[data-ticket-code]').textContent = ticketDetails.ticket_code;
    modal.querySelector('[data-ticket-phone]').textContent = ticketDetails.contact.phone;
    modal.querySelector('[data-ticket-line]').textContent = ticketDetails.contact.line;
    modal.querySelector('[data-ticket-facebook]').textContent = ticketDetails.contact.facebook;
    modal.querySelector('[data-ticket-email]').textContent = ticketDetails.contact.email;

    const qrImage = modal.querySelector('[data-ticket-qr]');
    const qrError = modal.querySelector('[data-ticket-qr-error]');
    qrImage.hidden = !ticketDetails.qr_code;
    qrError.hidden = Boolean(ticketDetails.qr_code);

    if (ticketDetails.qr_code) {
      qrImage.onerror = () => {
        qrImage.hidden = true;
        qrError.hidden = false;
      };
      qrImage.src = ticketDetails.qr_code;
    } else {
      qrImage.removeAttribute('src');
    }
  };

  document.addEventListener('click', async (event) => {
    const clickTarget = event.target instanceof Element ? event.target : null;
    if (!clickTarget) return;

    const trigger = clickTarget.closest('[data-ticket-url]');
    if (trigger) {
      activeTrigger = trigger;
      trigger.disabled = true;

      try {
        const response = await fetch(trigger.dataset.ticketUrl, {
          headers: { Accept: 'application/json' },
          credentials: 'same-origin',
        });

        if (!response.ok) {
          throw new Error(`Ticket request failed with status ${response.status}`);
        }

        showTicketDetails(await response.json());
        modal.hidden = false;
        document.body.classList.add('ticket-modal-open');
        closeButton.focus();
      } catch (error) {
        console.error('Unable to load ticket details.', error);
        window.alert('ไม่สามารถแสดงตั๋วได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง');
        activeTrigger = null;
      } finally {
        trigger.disabled = false;
      }

      return;
    }

    if (clickTarget === modal || clickTarget.closest('[data-ticket-close]')) {
      closeTicketModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) {
      closeTicketModal();
    }
  });
})();
