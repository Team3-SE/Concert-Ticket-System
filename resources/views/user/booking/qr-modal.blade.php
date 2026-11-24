{{--
 * qr-modal.blade.php
 * หน้าเลือกโซนและที่นั่งของคอนเสิร์ต
 * @Author 67160434 จิตฏิยากร โสภา
 * @Create Date 2026-10-13
 * @Update Date 11/11/2569 67160292 ธีรเมธ พลสาย
--}}
{{-- THAI QR PAYMENT MODAL COMPONENT --}}
<div x-show="showQrModal"
     x-transition.opacity
     class="qr-modal-overlay"
     x-cloak>

  <div class="qr-modal-card" @click.away="closeModal()">

    {{-- Modal Dark Blue Header --}}
    <div class="qr-modal-header">
      <button @click="closeModal()" class="qr-modal-close-btn" aria-label="ปิด">&times;</button>
      <h3 class="qr-modal-title">ชำระเงินด้วย THAI QR PAYMENT</h3>
      <p class="qr-modal-subtitle">Team 3 Ticket | หมายเลขคำสั่งซื้อ : <span x-text="orderCode || '-'">-</span></p>
    </div>

    {{-- Modal Body (Figma: 2 คอลัมน์เท่ากัน) --}}
    <div class="qr-modal-body">

      {{-- Left Side: QR Container Placeholder & Timer --}}
      <div class="qr-modal-left">

        {{-- Blank QR Code Container --}}
        {{-- APP_ENV=local: กดที่ QR เพื่อจำลองว่าชำระเงินสำเร็จ (ยังไม่มี payment provider จริง) --}}
        <div class="qr-placeholder-box"
             @env('local')
               :class="{ 'is-mock-pay': paymentId && !gateway }"
               :title="paymentId && !gateway ? 'ทดสอบ: กดเพื่อจำลองว่าชำระเงินสำเร็จ' : ''"
               @click="confirmModalPayment()"
             @endenv
        >
          {{-- QR จริงจาก PayMock --}}
          {{-- กดที่ QR เพื่อเปิดหน้าจำลองแอปธนาคาร (มี bank_simulator_url เฉพาะ APP_ENV=local) --}}
          <a x-show="gateway && (gateway.qr_image || gateway.qrcode_url)"
             :href="gateway && gateway.bank_simulator_url ? gateway.bank_simulator_url : null"
             :title="gateway && gateway.bank_simulator_url ? 'ทดสอบ: กดที่ QR เพื่อเปิดหน้าจำลองแอปธนาคาร' : ''"
             :style="gateway && gateway.bank_simulator_url ? 'cursor:pointer' : 'cursor:default'"
             target="_blank" rel="noopener" @click.stop>
            <img :src="gateway ? (gateway.qr_image || gateway.qrcode_url) : ''" alt="QR พร้อมเพย์" width="240" height="240">
          </a>
          <svg x-show="!gateway" class="w-16 h-16 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-4v-3m-4 3h-2m-2 0H4m0 0v-4m0 4h2m6-11V4m0 0h2m-2 0H8m4 0v3m-4-3H4m0 0v4m0-4h2m0 4v3m0-3h2m4 0h2m-2 0v3m0-3V9m-4 3h2m-2 0H8m4 0v3" />
          </svg>
          <span x-show="!gateway" class="text-xs font-semibold text-gray-500">QR CODE PLACEHOLDER</span>
          <span x-show="!gateway" class="text-[10px] text-gray-400 mt-1">(รอเชื่อมต่อระบบชำระเงิน)</span>
        </div>

        {{-- Countdown Timer Box --}}
        <div class="qr-timer-box">
          ระยะเวลาที่เหลือในการชำระเงิน <span x-text="formattedTime">09:01</span> นาที
        </div>

      </div>

      {{-- Right Side: Details List --}}
      <div class="qr-modal-right">

        {{-- Amount Section --}}
        <div class="qr-amount-label">จำนวนเงินที่ต้องชำระ</div>
        <div class="qr-amount">
          <span x-text="calculateTotal()">4,974.00</span> บาท
        </div>

        {{-- Details Rows --}}
        <div>
          <div class="qr-detail-row">
            <span class="qr-detail-label">หมายเลขคำสั่งซื้อ</span>
            <span class="qr-detail-value" x-text="orderCode || '-'">-</span>
          </div>

          <div class="qr-detail-row">
            <span class="qr-detail-label">ชื่อผู้ชำระเงิน</span>
            <span class="qr-detail-value" x-text="buyer.name || '-'">-</span>
          </div>

          <div class="qr-detail-row">
            <span class="qr-detail-label">วันที่ทำรายการ</span>
            <span class="qr-detail-value is-small" x-text="currentDate">09/07/2569 13:37</span>
          </div>

          <div class="qr-detail-row">
            <span class="qr-detail-label">สินค้า / บริการ</span>
            <span class="qr-detail-value is-small">{{ $concert?->name ?? '-' }}</span>
          </div>
        </div>

      </div>

    </div>

    {{-- Modal Footer --}}
    <div class="qr-modal-footer">
      <span class="qr-contact-title">มีปัญหา ต้องการติดต่อผู้จัดงาน</span>
      {{-- schema 3.4.2: ช่องทางติดต่อผู้จัดเก็บไว้ที่คอนเสิร์ต --}}
      <span class="qr-contact-item">โทร : {{ $concert?->phone ?: '-' }}</span>
      <span class="qr-contact-item">LINE : {{ $concert?->line ?: '-' }}</span>
      <span class="qr-contact-item">Facebook : {{ $concert?->facebook ?: '-' }}</span>
      <span class="qr-contact-item">Email : {{ $concert?->email ?: '-' }}</span>
    </div>

  </div>
</div>
