{{--
 * payment-popups.blade.php
 * popup หน้าชำระเงิน: ยืนยันการชำระเงิน, แจ้งชำระเงินสำเร็จ และแจ้งชำระเงินไม่สำเร็จ (ใช้ใน user/booking/payment ภายใต้ Alpine paymentApp)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 24/10/2569
 * @Update Date 24/10/2569 67160292 ธีรเมธ พลสาย
--}}
{{-- CONFIRM PAY POPUP: กดชำระเงินแล้วต้องกดยืนยันก่อนสร้างรายการชำระเงิน --}}
<div x-show="showConfirmModal" x-transition.opacity x-cloak class="pay-popup-overlay"
     @keydown.escape.window="showConfirmModal = false">
  <div class="pay-popup-card" role="dialog" aria-modal="true" aria-labelledby="confirmPayTitle"
       @click.away="showConfirmModal = false">
    <div class="pay-popup-icon is-question" aria-hidden="true">?</div>
    <h3 id="confirmPayTitle" class="pay-popup-title">ยืนยันการชำระเงินด้วย <span x-text="methodLabel"></span></h3>
    <p class="pay-popup-total">ยอดรวม : <span x-text="calculateTotal()"></span> ฿</p>
    <div class="pay-popup-actions">
      <button type="button" class="pay-popup-btn is-cancel" @click="showConfirmModal = false">ยกเลิก</button>
      <button type="button" class="pay-popup-btn is-primary" :disabled="isSubmitting" @click="confirmPay()"
              x-text="paymentMethod === 'credit' ? 'ยืนยัน' : 'ตกลง'">ตกลง</button>
    </div>
  </div>
</div>

{{-- PAYMENT SUCCESS POPUP: ชำระเงินสำเร็จแล้วแจ้งผล กดตกลงไปหน้าตั๋วของฉัน --}}
<div x-show="showSuccessModal" x-transition.opacity x-cloak class="pay-popup-overlay">
  <div class="pay-popup-card" role="dialog" aria-modal="true" aria-labelledby="successPayTitle">
    <div class="pay-popup-icon is-success" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
    </div>
    <h3 id="successPayTitle" class="pay-popup-title">ชำระเงินสำเร็จ</h3>
    <p class="pay-popup-sub">ระบบได้รับการชำระเงินของคุณเรียบร้อยแล้ว</p>
    <div class="pay-popup-summary">
      <div class="pay-popup-row">
        <span>ช่องทางชำระเงิน</span>
        <strong x-text="methodLabel"></strong>
      </div>
      <div class="pay-popup-row">
        <span>ยอดเงินทั้งหมด</span>
        <strong class="is-amount"><span x-text="paidTotal"></span> ฿</strong>
      </div>
    </div>
    <div class="pay-popup-actions is-single">
      <button type="button" class="pay-popup-btn is-primary" @click="finishPayment()">ตกลง</button>
    </div>
  </div>
</div>

{{-- PAYMENT FAIL POPUP: ชำระเงินไม่สำเร็จ (หมดเวลา 10 นาที หรือระบบทำรายการไม่ได้) --}}
<div x-show="showFailModal" x-transition.opacity x-cloak class="pay-popup-overlay">
  <div class="pay-popup-card is-wide" role="dialog" aria-modal="true" aria-labelledby="failPayTitle">
    <div class="pay-popup-icon is-fail" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </div>
    <h3 id="failPayTitle" class="pay-popup-title">ชำระเงินไม่สำเร็จ</h3>
    <p class="pay-popup-sub" x-text="failMessage || (failReason === 'timeout' ? 'หมดเวลาชำระเงิน การจองนี้ถูกยกเลิกแล้ว กรุณาเลือกที่นั่งใหม่' : 'ขออภัย ระบบไม่สามารถทำรายการของคุณได้ในขณะนี้')"></p>
    <div class="pay-popup-summary">
      <div class="pay-popup-row">
        <span>ช่องทางชำระเงิน</span>
        <strong x-text="methodLabel"></strong>
      </div>
      <div class="pay-popup-row">
        <span>ยอดเงินทั้งหมด</span>
        <strong class="is-amount is-fail"><span x-text="calculateTotal()"></span> ฿</strong>
      </div>
    </div>
    <div class="pay-popup-actions" :class="{ 'is-single': failReason === 'timeout' }">
      <button type="button" class="pay-popup-btn is-cancel" x-show="failReason !== 'timeout'" @click="goBack()">ย้อนกลับ</button>
      <button type="button" class="pay-popup-btn is-primary" @click="retryPayment()"
              x-text="failReason === 'timeout' ? 'เลือกที่นั่งใหม่' : 'ลองใหม่อีกครั้ง'">ลองใหม่อีกครั้ง</button>
    </div>
  </div>
</div>
