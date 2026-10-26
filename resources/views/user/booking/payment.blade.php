{{--
 * payment.blade.php
 * หน้าชำระเงิน
 * @Author 67160434 จิตฏิยากร โสภา
 * @Create Date 2026-10-13
 * @Update Date 24/10/2569 67160292 ธีรเมธ พลสาย
--}}
@extends('layouts.user')

@section('title', 'TEAM 3 Ticket - Payment')
@section('body_class', 'page-payment min-h-screen flex flex-col bg-[#EBF1F9]')

@push('body_attrs')x-data="paymentApp()"@endpush

@section('content')
  {{-- MAIN BODY CONTENT --}}
  <main class="flex-1 max-w-[1320px] mx-auto w-full px-4 md:px-8 pt-10 pb-8 space-y-6">

    {{-- TOP SECTION: RESERVE SUMMARY & PAYMENT SUMMARY SIDE BY SIDE (Figma: 2 คอลัมน์เท่ากัน ห่าง 40px) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-start">

      {{-- LEFT COLUMN: SELECTION CARDS --}}
      <div class="space-y-6">
        <h2 class="page-title">RESERVE SUMMARY</h2>

        <div class="space-y-4">
          {{-- Protection Selection --}}
          <h3 class="section-title">ความคุ้มครอง/Ticket Protection</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            {{-- ประกัน: ไม่บังคับ เลือกได้ทั้งสองอย่างหรือไม่เลือกเลยก็ได้ (ค่าประกัน 5% ของราคาบัตรต่อหนึ่งประกัน) --}}
            {{-- Option 1: Show Protection --}}
            <label class="card-option" :class="{ 'active': protection.includes('show') }">
              <input type="checkbox" name="protection" value="show" x-model="protection" class="radio-btn">
              <span class="radio-label">ซื้อประกันบัตรการแสดง</span>
            </label>

            {{-- Option 2: Travel Protection --}}
            <label class="card-option" :class="{ 'active': protection.includes('travel') }">
              <input type="checkbox" name="protection" value="travel" x-model="protection" class="radio-btn">
              <span class="radio-label">ซื้อประกันการเดินทาง</span>
            </label>

          </div>

          {{-- Payment Method Selection --}}
          <div class="space-y-3">
            <h3 class="section-title">เลือกช่องทางชำระเงิน / Choose Payment Methods</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

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
      </div>

      {{-- RIGHT COLUMN: DARK PRICE SUMMARY CARD --}}
      <div class="summary-card">
        {{-- Table Header --}}
        <div class="summary-header">
          <span>ประเภท/Type</span>
          <span>ราคา/Price</span>
        </div>

        {{-- Price Breakdowns --}}
        <div class="summary-row pt-0.5">
          <span x-text="quantity + ' x ' + zoneName"></span>
          <span x-text="calculateSubtotal().toLocaleString() + ' บาท'"></span>
        </div>
        <div class="summary-row">
          <span>ค่าจัดส่ง/Shipping Fee</span>
          <span>0 บาท</span>
        </div>
        <div class="summary-row">
          <span>ค่าธรรมเนียมบริการ/Service Fee</span>
          <span x-text="serviceFee.toLocaleString() + ' บาท'"></span>
        </div>
        <div class="summary-row" x-show="protection.includes('show')">
          <span>ค่าประกันบัตรการแสดง</span>
          <span x-text="protectionFeeEach().toLocaleString() + ' บาท'"></span>
        </div>
        <div class="summary-row" x-show="protection.includes('travel')">
          <span>ค่าประกันการเดินทาง</span>
          <span x-text="protectionFeeEach().toLocaleString() + ' บาท'"></span>
        </div>

        {{-- Total Price Row --}}
        <div class="summary-total-row">
          <span>ยอดที่ต้องชำระ/Total Amount</span>
          <span x-text="calculateTotal() + ' บาท'"></span>
        </div>

        {{-- Timer (Figma: กล่องส้มจางอยู่ในการ์ด) --}}
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
         class="info-card !pb-8 space-y-4">
      <h3 class="detail-heading">รายละเอียดบัตรเครดิต / เดบิต</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

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
    <div class="info-card space-y-2">

      {{-- Event Header --}}
      <div class="event-header">
        <h2 class="event-title">{{ $concert ? strtoupper($concert->name) : '-' }}</h2>
        <p class="event-meta">
          วันและเวลา : Show Date :
          {{ $concert ? $concert->event_datetime->thDateTime() : '-' }}
          | สถานที่/Venue : {{ $concert ? $concert->venue : '-' }}
        </p>
      </div>

      {{-- Details Grid (Figma: 2 คอลัมน์ ห่าง 40px) --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-4 pt-2">

        {{-- Left: Order Details --}}
        <div>
          <h3 class="detail-heading">รายการซื้อ/Order Details</h3>
          <div class="detail-row">
            <span class="detail-label">จำนวน/Quantity</span>
            <span class="detail-value" x-text="quantity"></span>
          </div>
          <div class="detail-row">
            <span class="detail-label">เลขที่นั่ง/Seat No.</span>
            <span class="detail-value" x-text="seatNo"></span>
          </div>
          <div class="detail-row">
            <span class="detail-label">โซน/Zone</span>
            <span class="detail-value" x-text="zoneName"></span>
          </div>
        </div>

        {{-- Right: Buyer Details --}}
        <div>
          <h3 class="detail-heading">ข้อมูลผู้ซื้อ/Buyer Details</h3>
          <div class="detail-row">
            <span class="detail-label">ชื่อ/Name</span>
            <span class="detail-value" x-text="buyer.name || '-'">-</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">อีเมล/Email - Address</span>
            <span class="detail-value" x-text="buyer.email || '-'">-</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">เบอร์โทรศัพท์/Mobile No</span>
            <span class="detail-value" x-text="buyer.phone || '-'">-</span>
          </div>
        </div>

      </div>

      {{-- Action Footer (Figma: กล่องติดต่อผู้จัด + ปุ่มชิดขวา ห่าง 16px) --}}
      <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4">

        {{-- Organizer Support Contact --}}
        <div class="contact-box">
          <span class="contact-title">มีปัญหา ต้องการติดต่อผู้จัดงาน</span>
          {{-- schema v3.1.0: ช่องทางติดต่ออยู่ในตาราง organization --}}
          <span class="contact-item">โทร : {{ $organization?->phone ?: '-' }}</span>
          <span class="contact-item">LINE : {{ $organization?->line ?: '-' }}</span>
          <span class="contact-item">Facebook : {{ $organization?->facebook ?: '-' }}</span>
          <span class="contact-item">Email : {{ $organization?->email ?: '-' }}</span>
        </div>

        {{-- Back & Payment Action Buttons --}}
        <div class="flex items-center gap-4 w-full lg:w-auto">
          <button @click="goBack()" class="btn-back flex-1 lg:flex-initial">
            ย้อนกลับ
          </button>
          <button @click="requestPayment()" :disabled="isSubmitting" class="btn-submit flex-1 lg:flex-initial disabled:opacity-60">
            ชำระเงิน
          </button>
        </div>

      </div>

    </div>

  </main>

  {{-- INCLUDE POP-UP FROM DEV FOLDER --}}
  @include('user.booking.qr-modal')

  {{-- POP-UP ยืนยันการชำระเงิน / ชำระเงินสำเร็จ --}}
  @include('user.booking.payment-popups')
@endsection

@push('scripts')
<script>
    /**
     * paymentApp()
     * Alpine.js component หน้าชำระเงิน
     * ถ้ามี checkout (มาจากการจองจริง) ใช้ข้อมูลจาก server, ถ้าไม่มีใช้ค่าจาก query string แบบเดิม
     */
    function paymentApp() {
      const checkout = @json($checkout ?? null);
      const concertId = @json($concert?->concert_id);
      const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

      return {
        protection: [],   // ไม่เลือกประกันไว้ก่อน: 'show' (ประกันบัตรการแสดง), 'travel' (ประกันการเดินทาง) เลือกได้ทั้งสอง
        paymentMethod: 'promptpay',

        bookingId: checkout?.bookingId ?? null,
        zoneName: checkout?.zoneName ?? 'ELF-2',
        seatNo: checkout?.seatNo ?? 'A9',
        quantity: checkout?.quantity ?? 1,
        ticketPrice: checkout?.ticketPrice ?? 4800,
        subtotal: checkout?.subtotal ?? null,
        buyer: checkout?.buyer ?? { name: '', email: '', phone: '' },

        serviceFee: checkout?.serviceFee ?? 30,
        protectionRate: checkout?.protectionRate ?? 0.05,   // ค่าประกันต่อหนึ่งประกัน = 5% ของราคาบัตรรวม

        // Modal & Timer State
        showQrModal: false,
        showConfirmModal: false,   // popup ยืนยันการชำระเงิน
        showSuccessModal: false,   // popup ชำระเงินสำเร็จ
        showFailModal: false,      // popup ชำระเงินไม่สำเร็จ
        failReason: 'error',       // 'timeout' = หมดเวลา/การจองใช้ไม่ได้แล้ว, 'error' = ระบบทำรายการไม่ได้ (ลองใหม่ได้)
        failMessage: '',
        expiresAt: 0,              // เวลาหมดอายุจริง (ms) ใช้คำนวณเวลาที่เหลือ ไม่ลบทีละวินาที กันตัวนับเพี้ยนตอนแท็บอยู่เบื้องหลัง
        paidTotal: '',             // ยอดที่ชำระแล้ว (แสดงใน popup สำเร็จ)
        redirectUrl: '/orders/history',
        isSubmitting: false,
        countdownTimer: null,
        timeLeft: checkout?.secondsLeft ?? 597,
        orderCode: checkout?.orderCode ?? null,
        paymentId: checkout?.paymentId ?? null,
        currentDate: '',

        init() {
          if (!checkout) {
            const params = new URLSearchParams(window.location.search);

            if (params.has('zone')) this.zoneName = params.get('zone');
            if (params.has('seat')) this.seatNo = params.get('seat');
            if (params.has('quantity')) this.quantity = parseInt(params.get('quantity')) || 1;
            if (params.has('price')) this.ticketPrice = parseFloat(params.get('price')) || 4800;
          }

          // สร้างวันที่ทำรายการอัตโนมัติ (วว/ดด/ปปปป ชช:นน)
          const now = new Date();
          const day = String(now.getDate()).padStart(2, '0');
          const month = String(now.getMonth() + 1).padStart(2, '0');
          const year = now.getFullYear() + 543;
          const hours = String(now.getHours()).padStart(2, '0');
          const minutes = String(now.getMinutes()).padStart(2, '0');
          this.currentDate = `${day}/${month}/${year} ${hours}:${minutes}`;

          // เริ่มนับเวลาถอยหลัง (การจองจริงนับจาก expiry_time ของ booking)
          this.expiresAt = Date.now() + this.timeLeft * 1000;
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
            this.timeLeft = Math.max(0, Math.ceil((this.expiresAt - Date.now()) / 1000));
            if (this.timeLeft <= 0) {
              clearInterval(this.countdownTimer);
              this.handleTimeout();
            }
          }, 1000);
        },

        /**
         * handleTimeout()
         * ตัวนับเวลาถึง 0: ถาม server ก่อนว่าจ่ายสำเร็จไปแล้วหรือยัง ถ้ายังจึงแจ้งว่าไม่สำเร็จ
         */
        async handleTimeout() {
          if (!this.bookingId) {
            this.showFail('timeout');
            return;
          }
          try {
            const res = await fetch(`/bookings/${this.bookingId}/status`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json().catch(() => ({}));

            if (data.status === 'CONFIRMED') {
              // จ่ายสำเร็จก่อนหมดเวลา (ผลมาถึงหลังตัวนับ 0) แจ้งว่าสำเร็จ
              this.paidTotal = this.calculateTotal();
              this.showQrModal = false;
              this.showConfirmModal = false;
              this.showSuccessModal = true;
              return;
            }
            if (data.status === 'PENDING_PAYMENT' && data.seconds_left > 2) {
              // นาฬิกาเครื่องเพี้ยน: server ยังมีเวลาเหลือ นับต่อ
              this.expiresAt = Date.now() + data.seconds_left * 1000;
              this.startCountdown();
              return;
            }
          } catch (e) {
            // ตรวจกับ server ไม่ได้ ถือว่าหมดเวลา
          }
          this.showFail('timeout');
        },

        /**
         * showFail(reason, message)
         * เปิด popup ชำระเงินไม่สำเร็จ (ปิด popup อื่นที่เปิดอยู่ก่อน)
         */
        showFail(reason, message = '') {
          clearInterval(this.countdownTimer);
          this.failReason = reason;
          this.failMessage = message;
          this.showQrModal = false;
          this.showConfirmModal = false;
          this.showFailModal = true;
        },

        /**
         * retryPayment()
         * หมดเวลา: ไปเลือกที่นั่งใหม่ / ระบบขัดข้อง: ลองสร้างรายการชำระเงินอีกครั้ง
         */
        retryPayment() {
          if (this.failReason === 'timeout') {
            if (this.bookingId && concertId) {
              window.location.href = `/booking?concert_id=${concertId}`;
            } else {
              window.history.back();
            }
            return;
          }
          this.showFailModal = false;
          this.processPayment();
        },

        calculateSubtotal() {
          return this.subtotal ?? this.ticketPrice * this.quantity;
        },

        // ชื่อช่องทางชำระเงินที่แสดงใน popup
        get methodLabel() {
          return this.paymentMethod === 'credit' ? 'Credit Card' : 'Thai QR Payment';
        },

        // ค่าประกันต่อหนึ่งประกัน (ปัดเป็นสตางค์ ให้ตรงกับที่ server คำนวณ)
        protectionFeeEach() {
          return Math.round(this.calculateSubtotal() * this.protectionRate * 100) / 100;
        },

        calculateTotal() {
          let total = this.calculateSubtotal() + this.serviceFee + this.protectionFeeEach() * this.protection.length;
          return total.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        /**
         * goBack()
         * ย้อนกลับ: ถ้ามาจากการจองจริงและยังไม่ได้เปิด QR ให้ยกเลิกการจองก่อน (ปลดล็อกที่นั่ง) แล้วกลับหน้าเลือกที่นั่ง
         */
        async goBack() {
          if (this.bookingId && concertId) {
            try {
              await fetch(`/bookings/${this.bookingId}/cancel`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
              });
            } catch (e) {
              // ยกเลิกไม่สำเร็จก็ยังกลับได้ ที่นั่งจะถูกปลดเมื่อหมดเวลา
            }
            window.location.href = `/booking?concert_id=${concertId}`;
            return;
          }
          window.history.back();
        },

        closeModal() {
          this.showQrModal = false;
        },

        /**
         * requestPayment()
         * กดปุ่มชำระเงิน: เปิด popup ให้ยืนยันก่อน (ยังไม่สร้างรายการชำระเงิน)
         */
        requestPayment() {
          if (this.paymentMethod !== 'promptpay') {
            alert('ระบบชำระด้วยบัตรเครดิตยังไม่เชื่อมต่อ กรุณาเลือกพร้อมเพย์');
            return;
          }
          this.showConfirmModal = true;
        },

        /**
         * confirmPay()
         * กดตกลง/ยืนยันใน popup: ปิด popup แล้วสร้างรายการชำระเงินต่อ
         */
        async confirmPay() {
          this.showConfirmModal = false;
          await this.processPayment();
        },

        /**
         * finishPayment()
         * กดตกลงใน popup ชำระเงินสำเร็จ: ไปหน้าประวัติคำสั่งซื้อ
         */
        finishPayment() {
          window.location.href = this.redirectUrl;
        },

        /**
         * processPayment()
         * การจองจริง: สร้างรายการชำระเงิน (ได้หมายเลขคำสั่งซื้อ) แล้วเปิดหน้าต่าง QR
         */
        async processPayment() {
          if (!this.bookingId) {
            this.showQrModal = true;   // หน้าตัวอย่าง (ไม่มีการจองจริง)
            return;
          }
          if (this.isSubmitting) return;
          this.isSubmitting = true;

          try {
            const res = await fetch('/payments', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
              body: JSON.stringify({ booking_id: this.bookingId, method: this.paymentMethod, protection: this.protection }),
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
              // 410/409 = การจองหมดเวลาหรือถูกยกเลิกแล้ว ใช้ต่อไม่ได้, อย่างอื่น = ระบบทำรายการไม่ได้ ลองใหม่ได้
              const unusable = res.status === 410 || res.status === 409;
              this.showFail(unusable ? 'timeout' : 'error', unusable ? '' : (data.message || ''));
              return;
            }

            this.paymentId = data.payment_id;
            this.orderCode = data.order_code;
            this.showQrModal = true;
          } catch (e) {
            this.showFail('error', 'เชื่อมต่อระบบไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
          } finally {
            this.isSubmitting = false;
          }
        },

        /**
         * confirmModalPayment()
         * จำลองการชำระเงินสำเร็จ (ปุ่มนี้แสดงเฉพาะ APP_ENV=local) แล้วไปหน้าประวัติคำสั่งซื้อ
         */
        async confirmModalPayment() {
          if (!this.paymentId) return;

          const res = await fetch(`/payments/${this.paymentId}/mock-complete`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
          });
          const data = await res.json().catch(() => ({}));

          if (!res.ok) {
            // 409 = การจองหมดเวลา/ถูกยกเลิกไปแล้ว
            this.showFail(res.status === 409 ? 'timeout' : 'error', res.status === 409 ? '' : (data.message || ''));
            return;
          }
          // ชำระเงินสำเร็จ: ปิดหน้าต่าง QR แล้วแจ้งผลด้วย popup (กดตกลงแล้วไปหน้าประวัติคำสั่งซื้อ)
          this.redirectUrl = data.redirect || this.redirectUrl;
          this.paidTotal = this.calculateTotal();
          clearInterval(this.countdownTimer);
          this.showQrModal = false;
          this.showSuccessModal = true;
        }
      }
    }
  </script>
@endpush
