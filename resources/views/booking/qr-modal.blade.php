{{--
 * qr-modal.blade.php
 * popup ชำระเงินด้วย Thai QR (ใช้ในหน้า payment)
 * @Author
 * @Create Date
--}}
{{-- THAI QR PAYMENT MODAL COMPONENT --}}
<div x-show="showQrModal"
     x-transition.opacity
     class="qr-modal-overlay"
     x-cloak>

  <div class="qr-modal-card" @click.away="closeModal()">

    {{-- Modal Dark Blue Header --}}
    <div class="qr-modal-header">
      <button @click="closeModal()" class="qr-modal-close-btn">&times;</button>
      <h3 class="text-xl font-bold tracking-wide mb-1">ชำระเงินด้วย THAI QR PAYMENT</h3>
      <p class="text-[11px] text-gray-300">ALL Ticket | หมายเลขใบแจ้งหนี้: <span x-text="invoiceNo">81001269340014456372</span></p>
    </div>

    {{-- Modal Body --}}
    <div class="p-6 md:p-8 grid grid-cols-1 md:grid-cols-12 gap-6 items-center">

      {{-- Left Side: QR Container Placeholder & Timer --}}
      <div class="md:col-span-5 flex flex-col items-center space-y-4">

        {{-- Blank QR Code Container --}}
        <div class="qr-placeholder-box">
          <svg class="w-16 h-16 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-4v-3m-4 3h-2m-2 0H4m0 0v-4m0 4h2m6-11V4m0 0h2m-2 0H8m4 0v3m-4-3H4m0 0v4m0-4h2m0 4v3m0-3h2m4 0h2m-2 0v3m0-3V9m-4 3h2m-2 0H8m4 0v3" />
          </svg>
          <span class="text-xs font-semibold text-gray-500">QR CODE PLACEHOLDER</span>
          <span class="text-[10px] text-gray-400 mt-1">(รอเชื่อมต่อระบบชำระเงิน)</span>
        </div>

        {{-- Countdown Timer Box --}}
        <div class="qr-timer-box">
          <span>ระยะเวลาที่เหลือในการชำระเงิน </span>
          <span class="font-bold" x-text="formattedTime + ' นาที'">09:01 นาที</span>
        </div>

      </div>

      {{-- Right Side: Details List --}}
      <div class="md:col-span-7 space-y-5">

        {{-- Amount Section --}}
        <div>
          <div class="text-[11px] text-gray-400 mb-1">จำนวนเงินที่ต้องชำระ</div>
          <div class="text-3xl font-bold text-[#18355C]">
            <span x-text="calculateTotal()">4,974.00</span> <span class="text-2xl">บาท</span>
          </div>
          <div class="border-b border-gray-100 mt-4"></div>
        </div>

        {{-- Details Rows --}}
        <div class="space-y-3.5 text-xs">
          <div class="flex justify-between items-center border-b border-gray-100 pb-3">
            <span class="font-bold text-[#18355C]">หมายเลขใบแจ้งหนี้</span>
            <span class="text-gray-600 font-medium" x-text="invoiceNo">81001269340014456372</span>
          </div>

          <div class="flex justify-between items-center border-b border-gray-100 pb-3">
            <span class="font-bold text-[#18355C]">ชื่อผู้ชำระเงิน</span>
            <span class="text-gray-600 font-medium">อนิรุธ สุขวิรกาล</span>
          </div>

          <div class="flex justify-between items-center border-b border-gray-100 pb-3">
            <span class="font-bold text-[#18355C]">วันที่ทำรายการ</span>
            <span class="text-gray-600 font-medium" x-text="currentDate">09/07/2569 13:37</span>
          </div>

          <div class="flex justify-between items-start pt-1">
            <span class="font-bold text-[#18355C] flex-shrink-0">สินค้า / บริการ</span>
            <span class="text-gray-600 font-medium text-right ml-4">
              2026 SUPER JUNIOR-D&E<br>FANCONTOUR [1983] in BANGKOK
            </span>
          </div>
        </div>

      </div>

    </div>

    {{-- Modal Footer --}}
    <div class="bg-gray-50 border-t border-gray-100 px-6 py-3.5 text-center text-[10px] text-gray-500">
      <span class="text-[#F47B1F] font-bold mr-1">มีปัญหา ต้องการติดต่อผู้จัดงาน</span>
      <span>โทร: 081-234-5678</span>
      <span class="mx-1">|</span>
      <span>LINE: @musicfest</span>
      <span class="mx-1">|</span>
      <span>Facebook: Music Fest Thailand</span>
      <span class="mx-1">|</span>
      <span>Email: support@musicfest.com</span>
    </div>

  </div>
</div>
