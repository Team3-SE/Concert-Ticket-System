{{--
 * payment.blade.php
 * หน้าชำระเงิน
 * @Author
 * @Create Date
--}}
@extends('layouts.main')

@section('title', 'TEAM 3 Ticket - Payment')
@section('body_class', 'page-payment min-h-screen flex flex-col bg-[#EBF1F9]')

@push('body_attrs')x-data="paymentApp()"@endpush

@section('content')
  {{-- MAIN BODY CONTENT --}}
  <main class="flex-1 max-w-6xl mx-auto w-full px-4 md:px-8 py-8 space-y-6">

    {{-- TOP SECTION: RESERVE SUMMARY & PAYMENT SUMMARY SIDE BY SIDE --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

      {{-- LEFT COLUMN: SELECTION CARDS --}}
      <div class="lg:col-span-7 space-y-6">
        <h2 class="page-title">RESERVE SUMMARY</h2>

        {{-- Protection Selection --}}
        <div class="space-y-2.5">
          <h3 class="section-title">ความคุ้มครอง/Ticket Protection</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

            {{-- Option 1: Show Protection --}}
            <label class="card-option" :class="{ 'active': protection === 'show' }">
              <input type="radio" name="protection" value="show" x-model="protection" class="radio-btn">
              <span class="radio-label">ซื้อประกันบัตรการแสดง</span>
            </label>

            {{-- Option 2: Travel Protection --}}
            <label class="card-option" :class="{ 'active': protection === 'travel' }">
              <input type="radio" name="protection" value="travel" x-model="protection" class="radio-btn">
              <span class="radio-label">ซื้อประกันการเดินทาง</span>
            </label>

          </div>
        </div>

        {{-- Payment Method Selection --}}
        <div class="space-y-2.5">
          <h3 class="section-title">เลือกช่องทางชำระเงิน / Choose Payment Methods</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

            {{-- PromptPay --}}
            <label class="card-option" :class="{ 'active': paymentMethod === 'promptpay' }">
              <input type="radio" name="paymentMethod" value="promptpay" x-model="paymentMethod" class="radio-btn">
              <span class="radio-label">พร้อมเพย์ / Thai QR Payment</span>
            </label>

            {{-- Credit Card --}}
            <label class="card-option" :class="{ 'active': paymentMethod === 'credit' }">
              <input type="radio" name="paymentMethod" value="credit" x-model="paymentMethod" class="radio-btn">
              <span class="radio-label">ชำระด้วยบัตรเครดิต / Credit Card</span>
            </label>

          </div>
        </div>
      </div>

      {{-- RIGHT COLUMN: DARK PRICE SUMMARY CARD --}}
      <div class="lg:col-span-5 summary-card">
        <div class="summary-card-body space-y-4">
          {{-- Table Header --}}
          <div class="summary-header">
            <span>ประเภท/Type</span>
            <span>ราคา/Price</span>
          </div>

          {{-- Price Breakdowns --}}
          <div class="space-y-2.5">
            <div class="summary-row flex justify-between">
              <span x-text="quantity + ' x ' + zoneName"></span>
              <span x-text="calculateSubtotal().toLocaleString() + ' บ.'"></span>
            </div>
            <div class="summary-row flex justify-between">
              <span>ค่าจัดส่ง/Shipping Fee</span>
              <span>0 บ.</span>
            </div>
            <div class="summary-row flex justify-between">
              <span>ค่าธรรมเนียมบริการ/Service Fee</span>
              <span x-text="serviceFee + ' บ.'"></span>
            </div>
            <div class="summary-row flex justify-between" x-show="protection !== 'none'">
              <span>ค่าประกันความคุ้มครอง</span>
              <span x-text="protectionFee + ' บ.'"></span>
            </div>
          </div>

          {{-- Total Price Row --}}
          <div class="summary-total-row">
            <span class="text-[#F47B1F]">ยอดที่ต้องชำระ/Total Amount</span>
            <span class="text-[#F47B1F] text-sm" x-text="calculateTotal() + ' บ.'"></span>
          </div>
        </div>

        {{-- Timer Bar at Bottom --}}
        <div class="summary-timer-bar">
          ระยะเวลาที่เหลือในการชำระเงิน <span x-text="formattedTime">10:00</span> นาที
        </div>
      </div>

    </div>

    {{-- DYNAMIC SECTION: CREDIT CARD DETAILS FORM --}}
    <div x-show="paymentMethod === 'credit'"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="info-card space-y-4">
      <h3 class="text-xs font-bold text-gray-800">รายละเอียดบัตรเครดิต / เดบิต</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">

        {{-- Card Number --}}
        <div class="space-y-1.5">
          <label class="input-label">หมายเลขบัตร (Card Number)</label>
          <input type="text" placeholder="XXXX XXXX XXXX XXXX" class="input-control">
        </div>

        {{-- Cardholder Name --}}
        <div class="space-y-1.5">
          <label class="input-label">ชื่อบนบัตร (Cardholder Name)</label>
          <input type="text" placeholder="SOMCHAI JAIDEE" class="input-control">
        </div>

        {{-- Expiry Date --}}
        <div class="space-y-1.5">
          <label class="input-label">วันหมดอายุ (MM/YY)</label>
          <input type="text" placeholder="MM/YY" class="input-control">
        </div>

        {{-- CVC / CVV --}}
        <div class="space-y-1.5">
          <label class="input-label">CVC / CVV</label>
          <input type="text" placeholder="123" class="input-control">
        </div>

      </div>
    </div>

    {{-- BOTTOM SECTION: TICKET DETAILS & BUYER INFO CARD --}}
    <div class="info-card space-y-6">

      {{-- Event Header --}}
      <div class="space-y-1 border-b border-gray-100 pb-4">
        <h2 class="text-base font-bold text-gray-900">{{ $concert ? strtoupper($concert->name) : '-' }}</h2>
        <p class="text-[11px] text-gray-400">
          วันและเวลา : Show Date :
          {{ $concert ? $concert->event_datetime->locale('en')->isoFormat('D MMMM YYYY HH:mm') : '-' }}
          | สถานที่/Venue : {{ $concert ? $concert->venue : '-' }}
        </p>
      </div>

      {{-- Details Grid --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-xs">

        {{-- Left: Order Details --}}
        <div class="space-y-3">
          <h3 class="font-bold text-gray-800 text-xs">รายการซื้อ/Order Details</h3>
          <div class="space-y-2.5 divide-y divide-gray-50">
            <div class="detail-row pt-1">
              <span class="detail-label">จำนวน/Quantity</span>
              <span class="detail-value" x-text="quantity"></span>
            </div>
            <div class="detail-row pt-2">
              <span class="detail-label">เลขที่นั่ง/Seat No.</span>
              <span class="detail-value" x-text="seatNo"></span>
            </div>
            <div class="detail-row pt-2">
              <span class="detail-label">โซน/Zone</span>
              <span class="detail-value" x-text="zoneName"></span>
            </div>
          </div>
        </div>

        {{-- Right: Buyer Details --}}
        <div class="space-y-3">
          <h3 class="font-bold text-gray-800 text-xs">ข้อมูลผู้ซื้อ/Buyer Details</h3>
          <div class="space-y-2.5 divide-y divide-gray-50">
            <div class="detail-row pt-1">
              <span class="detail-label">ชื่อ/Name</span>
              <span class="detail-value">อนิรุธ สุขวิรกาล</span>
            </div>
            <div class="detail-row pt-2">
              <span class="detail-label">อีเมล/Email - Address</span>
              <span class="detail-value">anitun007@gmail.com</span>
            </div>
            <div class="detail-row pt-2">
              <span class="detail-label">เบอร์โทรศัพท์/Mobile No</span>
              <span class="detail-value">0867676767</span>
            </div>
          </div>
        </div>

      </div>

      {{-- Action Footer --}}
      <div class="pt-4 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">

        {{-- Organizer Support Contact --}}
        <div class="text-[11px] text-gray-400 space-x-2">
          <span class="text-[#F47B1F] font-semibold">มีปัญหา ต้องการติดต่อผู้จัดงาน</span>
          {{-- schema v3.1.0: ช่องทางติดต่ออยู่ในตาราง organization --}}
          <span>โทร: {{ $organization?->phone ?: '-' }}</span>
          <span>LINE: {{ $organization?->line ?: '-' }}</span>
          <span>Facebook: {{ $organization?->facebook ?: '-' }}</span>
          <span>Email: {{ $organization?->email ?: '-' }}</span>
        </div>

        {{-- Back & Payment Action Buttons --}}
        <div class="flex items-center gap-3 w-full md:w-auto">
          <button @click="goBack()" class="btn-back flex-1 md:flex-initial">
            ย้อนกลับ
          </button>
          <button @click="processPayment()" class="btn-submit flex-1 md:flex-initial">
            ชำระเงิน
          </button>
        </div>

      </div>

    </div>

  </main>

  {{-- INCLUDE POP-UP FROM DEV FOLDER --}}
  @include('booking.qr-modal')
@endsection

@push('scripts')
<script>
    function paymentApp() {
      return {
        protection: 'show',
        paymentMethod: 'promptpay',

        zoneName: 'ELF-2',
        seatNo: 'A9',
        quantity: 1,
        ticketPrice: 4800,

        serviceFee: 30,
        protectionFee: 144,

        // Modal & Timer State
        showQrModal: false,
        countdownTimer: null,
        timeLeft: 597,
        invoiceNo: '81001269340014456372',
        currentDate: '',

        init() {
          const params = new URLSearchParams(window.location.search);

          if (params.has('zone')) this.zoneName = params.get('zone');
          if (params.has('seat')) this.seatNo = params.get('seat');
          if (params.has('quantity')) this.quantity = parseInt(params.get('quantity')) || 1;
          if (params.has('price')) this.ticketPrice = parseFloat(params.get('price')) || 4800;

          // สร้างวันที่ทำรายการอัตโนมัติ (วว/ดด/ปปปป ชช:นน)
          const now = new Date();
          const day = String(now.getDate()).padStart(2, '0');
          const month = String(now.getMonth() + 1).padStart(2, '0');
          const year = now.getFullYear() + 543;
          const hours = String(now.getHours()).padStart(2, '0');
          const minutes = String(now.getMinutes()).padStart(2, '0');
          this.currentDate = `${day}/${month}/${year} ${hours}:${minutes}`;

          // เริ่มนับเวลาถอยหลัง
          this.startCountdown();
        },

        // แปลงเวลาให้เป็นนาที:วินาที (MM:SS)
        get formattedTime() {
          const minutes = Math.floor(this.timeLeft / 60);
          const seconds = this.timeLeft % 60;
          return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        },

        startCountdown() {
          clearInterval(this.countdownTimer);
          this.countdownTimer = setInterval(() => {
            if (this.timeLeft > 0) {
              this.timeLeft--;
            } else {
              clearInterval(this.countdownTimer);
              alert('หมดเวลาทำรายการชำระเงิน ระบบจะนำท่านกลับไปเลือกที่นั่งใหม่');
              this.goBack();
            }
          }, 1000);
        },

        calculateSubtotal() {
          return this.ticketPrice * this.quantity;
        },

        calculateTotal() {
          let total = this.calculateSubtotal() + this.serviceFee;
          if (this.protection !== 'none') {
            total += this.protectionFee;
          }
          return total.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        goBack() {
          window.history.back();
        },

        closeModal() {
          this.showQrModal = false;
        },

        processPayment() {
          if (this.paymentMethod === 'promptpay') {
            this.showQrModal = true;
          } else {
            alert('กำลังดำเนินการชำระเงินผ่านบัตรเครดิต...');
          }
        },

        confirmModalPayment() {
          alert('ชำระเงินสำเร็จแล้ว!');
          // window.location.href = '/booking/success';
        }
      }
    }
  </script>
@endpush
