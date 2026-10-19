{{--
 * booking.blade.php
 * หน้าเลือกโซนและที่นั่งของคอนเสิร์ต
 * @Author 67160434 จิตฏิยากร โสภา
 * @Create Date 2026-10-13
--}}
@extends('layouts.main')

@section('title', 'TEAM 3 Ticket - Concert Booking')
@section('body_class', "min-h-screen flex flex-col bg-[#EBF1F9]")

@push('body_attrs')x-data="bookingApp()"@endpush

@section('content')
  <div class="flex-1">
    {{-- HERO BANNER WRAPPER --}}
    <div class="hero-banner">
    </div>

    {{-- MAIN HERO CARD OVERLAY --}}
    <div class="hero-overlay-wrap">
      <div class="hero-flex-container">

        {{-- Concert Poster --}}
        <div class="poster-card">
          @if($concert && $concert->poster_path)
            <img src="{{ asset('storage/' . $concert->poster_path) }}" alt="{{ $concert->name }}" onerror="this.remove()">
          @endif
        </div>

        {{-- Main Dark Info Card --}}
        <div class="main-hero-card">
          <div class="hero-content-left">
            <div>
              <h1 class="hero-title">{{ $concert ? strtoupper($concert->name) : 'ไม่พบคอนเสิร์ต' }}</h1>

              <div class="badge-group">
                <span class="badge-item">
                  <svg class="badge-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                  {{ $concert ? $concert->event_datetime->thDateLong() : '-' }}
                </span>
                <span class="badge-item">
                  <svg class="badge-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 16 14"/></svg>
                  {{ $concert ? $concert->event_datetime->format('H:i') : '-' }} น.
                </span>
                <span class="badge-item">
                  <svg class="badge-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                  {{ $concert ? $concert->venue : '-' }}
                </span>
              </div>
            </div>

            <div class="contact-info">
              <p>ติดต่อผู้จัดงาน</p>
              <div class="contact-grid">
                @php $org = $concert?->organization; @endphp
                <div>โทร: <span>{{ $org?->phone ?: '-' }}</span></div>
                <div>Facebook: <span>{{ $org?->facebook ?: '-' }}</span></div>
                <div>LINE: <span>{{ $org?->line ?: '-' }}</span></div>
                <div>Email: <span>{{ $org?->email ?: '-' }}</span></div>
              </div>
            </div>
          </div>

          <div class="price-panel">
            <span class="price-title">TICKET PRICES</span>
            <div>
              @foreach($zones as $zone)
              <div class="price-row" title="{{ $zone['name'] }}">
                <span class="w-2 h-2 rounded-sm inline-block mr-2" style="background-color: {{ $zone['color'] ?: '#4D7EE0' }}"></span>
                <span class="num">{{ number_format($zone['price']) }}</span>
                <span class="unit">THB</span>
              </div>
              @endforeach
            </div>
          </div>
        </div>

      </div>
    </div>

    {{-- MAIN BODY CONTENT --}}
    <main class="main-body space-y-6">

      {{-- DATE SELECTOR --}}
      <div class="space-y-2">
        <h3 class="section-label">วันเวลาการแสดง</h3>
        <div class="date-pill">
          <span class="dot-orange"></span>
          วันที่ {{ $concert ? $concert->event_datetime->thDateLong() : '-' }}
          เวลา {{ $concert ? $concert->event_datetime->format('H:i') : '-' }} น.
        </div>
      </div>

      {{-- zone-overview start --}}
      {{-- ZONE OVERVIEW MAP & TABLE --}}
      <div class="space-y-2.5">
        <h3 class="section-label">โซนทั้งหมด</h3>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-stretch">

          {{-- ฝั่งซ้าย: รูปผังโซน --}}
          <div class="md:col-span-4 bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-center">
            <img src="{{ asset('images/zone-map.png') }}" alt="Venue Zone Map" class="w-full h-auto max-h-[360px] object-contain rounded-lg">
          </div>

          {{-- ฝั่งขวา: การ์ดตารางโซน --}}
          <div class="md:col-span-8 bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-start">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="text-gray-400 border-b border-gray-100">
                  <th class="pb-3.5 pt-1 font-medium w-2/5 pl-4">โซน</th>
                  <th class="pb-3.5 pt-1 font-medium text-center w-1/5">ราคา</th>
                  <th class="pb-3.5 pt-1 font-medium text-right w-2/5 pr-4">ที่นั่งว่าง</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <template x-for="zone in zones" :key="zone.name">
                  <tr class="hover:bg-blue-50/50 cursor-pointer transition-colors" @click="selectZone(zone)">
                    <td class="py-3.5 pl-4">
                      <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-sm inline-block flex-shrink-0" :style="{ backgroundColor: zone.color }"></span>
                        <span class="font-semibold text-gray-700 text-xs" x-text="zone.name"></span>
                      </div>
                    </td>
                    <td class="py-3.5 text-gray-600 text-xs text-center" x-text="zone.price + ' บาท'"></td>
                    <td class="py-3.5 pr-4 text-right">
                      <span class="px-3.5 py-1 text-[10px] text-white font-bold rounded-full inline-block min-w-[65px] text-center shadow-sm"
                            :class="zone.seats === 0 ? 'bg-[#E6483C]' : ((zone.seats / (zone.capacity || zone.totalSeats || 100)) <= 0.3 ? 'bg-[#E79B1F]' : 'bg-[#1FA855]')"
                            x-text="zone.seats === 0 ? 'Sold out' : zone.seats"></span>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>

        </div>
      </div>
      {{-- zone-overview end --}}

      {{-- zone-selector start --}}
      {{-- ZONE TAG BUTTONS SELECTOR --}}
      <div class="space-y-2">
        <h3 class="section-label">เลือกโซน</h3>
        <div class="flex flex-wrap gap-1.5">
          <template x-for="(tag, idx) in zoneTags" :key="idx">
            <button @click="selectZoneByName(tag.name)"
                    :style="{ backgroundColor: tag.color }"
                    class="text-white text-[11px] px-3 py-1 rounded-[3px] font-semibold hover:opacity-90 transition shadow-sm"
                    x-text="tag.name">
            </button>
          </template>
        </div>
      </div>
      {{-- zone-selector end --}}

      {{-- seat-map-option-1 start --}}
      {{-- OPTION 1: SEAT MAP <= 20 SEATS --}}
      <template x-if="selectedZone && totalSeats <= 20">
        <div>
          <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-2">
            <div class="md:col-span-7">
              <h3 class="section-label">เลือกที่นั่ง</h3>
            </div>
            <div class="md:col-span-5">
              <h3 class="section-label">ข้อมูลบัตร</h3>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">

            {{-- ฝั่งซ้าย: ผังที่นั่ง --}}
            <div class="md:col-span-7 seat-map-transparent-box p-2">
              <div class="bg-[#061838] text-white text-center py-1.5 text-[10px] tracking-[0.2em] font-bold rounded max-w-[280px] mx-auto mb-4">STAGE</div>

              <div class="overflow-x-auto seat-scroll pb-2">
                <div class="flex flex-col gap-1 items-center min-w-fit mx-auto">

                  <div class="flex gap-1 items-center mb-1">
                    <span class="min-w-[28px] inline-block flex-shrink-0"></span>
                    <template x-for="col in totalSeats" :key="'hdr-'+col">
                      <span class="w-[14px] h-[14px] text-[9px] seat-label-text text-center inline-flex items-center justify-center font-semibold flex-shrink-0" x-text="col"></span>
                    </template>
                    <span class="min-w-[28px] inline-block flex-shrink-0"></span>
                  </div>

                  {{-- แถวเก้าอี้ที่นั่ง --}}
                  <template x-for="row in rows" :key="'r20-'+row">
                    <div class="flex gap-1 items-center">
                      <span class="min-w-[28px] px-1 h-[14px] text-[9px] seat-label-text text-center inline-flex items-center justify-center font-semibold select-none flex-shrink-0" x-text="row"></span>
                      <template x-for="col in totalSeats" :key="'s20-'+row+col">
                        <button @click="toggleSeat(row + col)"
                                :disabled="isOccupied(row + col) || isPending(row + col)"
                                :class="{
                                  '!rounded-full !bg-black text-white scale-105 shadow-md z-10 flex items-center justify-center': selectedSeats.includes(row + col),
                                  '!bg-[#F47B1F] scale-105 shadow-md z-10 cursor-not-allowed opacity-90': isPending(row + col),
                                  'seat-btn-occupied': isOccupied(row + col),
                                  'cursor-pointer': !isOccupied(row + col) && !isPending(row + col) && !selectedSeats.includes(row + col)
                                }"
                                :style="!isOccupied(row + col) && !isPending(row + col) && !selectedSeats.includes(row + col) ? { backgroundColor: getZoneColorForRow(row) } : {}"
                                class="!w-[14px] !h-[14px] seat-icon-shape transition-all duration-150 flex-shrink-0 border-0 outline-none p-0 flex items-center justify-center">
                          <template x-if="selectedSeats.includes(row + col)">
                            <svg class="w-2.5 h-2.5 text-white stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                          </template>
                        </button>
                      </template>
                      <span class="min-w-[28px] px-1 h-[14px] text-[9px] seat-label-text text-center inline-flex items-center justify-center font-semibold select-none flex-shrink-0" x-text="row"></span>
                    </div>
                  </template>

                </div>
              </div>

              {{-- Legend --}}
              <div class="flex justify-center gap-6 mt-5 text-[11px] text-gray-500">
                <div class="flex items-center gap-1.5">
                  <span class="seat-legend-icon" :style="{ backgroundColor: selectedZone ? selectedZone.color : '#4D7EE0' }"></span> ว่าง
                </div>
                <div class="flex items-center gap-1.5">
                  <span class="seat-legend-icon seat-btn-occupied"></span> ขายแล้ว
                </div>
                <div class="flex items-center gap-1.5">
                  <span class="seat-legend-icon bg-[#F47B1F]"></span> กำลังทำรายการ
                </div>
              </div>
            </div>

            {{-- ฝั่งขวา: ข้อมูลบัตร --}}
            <div class="md:col-span-5 bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between min-h-[300px]">
              <div class="text-xs space-y-4">
                <div class="flex justify-between border-b border-gray-100 pb-3">
                  <span class="font-semibold text-gray-700">รอบการแสดง</span>
                  <span class="text-gray-700 font-medium">{{ $concert ? $concert->event_datetime->thDateLong() . ', ' . $concert->event_datetime->format('H:i') . ' น.' : '-' }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-3">
                  <span class="font-semibold text-gray-700">โซน</span>
                  <span class="text-gray-600" x-text="selectedZone ? selectedZone.name : '-'"></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-3">
                  <span class="font-semibold text-gray-700">เลขที่นั่ง</span>
                  <span class="text-gray-600 font-medium" x-text="selectedSeats.length > 0 ? selectedSeats.join(', ') : '-'"></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-3">
                  <span class="font-semibold text-gray-700">จำนวน</span>
                  <span class="text-gray-600" x-text="selectedSeats.length > 0 ? selectedSeats.length : '0'"></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-3">
                  <span class="font-semibold text-gray-700">ราคาต่อบัตร</span>
                  <span class="text-gray-600" x-text="selectedZone ? selectedZone.price + ' บาท' : '0 บาท'"></span>
                </div>
                <div class="flex justify-between pt-1">
                  <span class="font-bold text-[#F47B1F] text-sm">ราคารวม</span>
                  <span class="font-bold text-[#F47B1F] text-sm" x-text="calculateTotalPrice()"></span>
                </div>
              </div>

              <div class="flex gap-3 mt-8">
                <button class="flex-1 py-2.5 rounded-lg bg-[#E8EEF7] text-gray-600 text-xs font-semibold hover:bg-gray-200 transition" @click="window.location.href='/'">ย้อนกลับ</button>
                <button :disabled="selectedSeats.length === 0"
                        :class="selectedSeats.length > 0 ? 'bg-[#F47B1F] hover:bg-orange-600 text-white shadow-md cursor-pointer' : 'bg-[#FBC48E] text-white cursor-not-allowed'"
                        class="flex-1 py-2.5 rounded-lg text-xs font-semibold transition"
                        @click="nextPage()">จองบัตร</button>
              </div>
            </div>

          </div>
        </div>
      </template>
      {{-- seat-map-option-1 end --}}

      {{-- seat-map-option-2 start --}}
      {{-- OPTION 2: SEAT MAP > 20 SEATS --}}
      <template x-if="selectedZone && totalSeats > 20">
        <div class="space-y-6">

          <div class="space-y-2">
            <h3 class="section-label">เลือกที่นั่ง</h3>

            <div class="seat-map-transparent-box space-y-4 p-2">
              <div class="bg-[#061838] text-white text-center py-1.5 text-[10px] tracking-[0.2em] font-bold rounded max-w-[300px] mx-auto">STAGE</div>

              <div class="overflow-x-auto seat-scroll py-2">
                <div class="flex flex-col gap-1 items-center min-w-fit mx-auto">

                  {{-- Header Numbers --}}
                  <div class="flex gap-1 items-center mb-1">
                    <span class="min-w-[28px] inline-block flex-shrink-0"></span>
                    <template x-for="col in totalSeats" :key="'hdr40-'+col">
                      <span class="w-[14px] h-[14px] text-[9px] seat-label-text text-center font-semibold flex-shrink-0" x-text="col"></span>
                    </template>
                    <span class="min-w-[28px] inline-block flex-shrink-0"></span>
                  </div>

                  {{-- Seat Rows --}}
                  <template x-for="row in rows" :key="'r40-'+row">
                    <div class="flex gap-1 items-center">
                      <span class="min-w-[28px] px-1 h-[14px] text-[9px] seat-label-text text-center inline-flex items-center justify-center font-semibold select-none flex-shrink-0" x-text="row"></span>
                      <template x-for="col in totalSeats" :key="'s40-'+row+col">
                        <button @click="toggleSeat(row + col)"
                                :disabled="isOccupied(row + col) || isPending(row + col)"
                                :class="{
                                  '!rounded-full !bg-black text-white scale-105 shadow-md z-10 flex items-center justify-center': selectedSeats.includes(row + col),
                                  '!bg-[#F47B1F] scale-105 shadow-md z-10 cursor-not-allowed opacity-90': isPending(row + col),
                                  'seat-btn-occupied': isOccupied(row + col),
                                  'cursor-pointer': !isOccupied(row + col) && !isPending(row + col) && !selectedSeats.includes(row + col)
                                }"
                                :style="!isOccupied(row + col) && !isPending(row + col) && !selectedSeats.includes(row + col) ? { backgroundColor: getZoneColorForRow(row) } : {}"
                                class="!w-[14px] !h-[14px] seat-icon-shape transition-all duration-150 flex-shrink-0 border-0 outline-none p-0 flex items-center justify-center">
                          <template x-if="selectedSeats.includes(row + col)">
                            <svg class="w-2.5 h-2.5 text-white stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                          </template>
                        </button>
                      </template>
                      <span class="min-w-[28px] px-1 h-[14px] text-[9px] seat-label-text text-center inline-flex items-center justify-center font-semibold select-none flex-shrink-0" x-text="row"></span>
                    </div>
                  </template>
                </div>
              </div>

              {{-- Seat Legend --}}
              <div class="flex justify-center gap-6 pt-2 text-[11px] text-gray-500">
                <div class="flex items-center gap-1.5">
                  <span class="seat-legend-icon" :style="{ backgroundColor: selectedZone ? selectedZone.color : '#4D7EE0' }"></span> ว่าง
                </div>
                <div class="flex items-center gap-1.5">
                  <span class="seat-legend-icon seat-btn-occupied"></span> ขายแล้ว
                </div>
                <div class="flex items-center gap-1.5">
                  <span class="seat-legend-icon bg-[#F47B1F]"></span> กำลังทำรายการ
                </div>
              </div>
            </div>
          </div>

          {{-- 2. FULL WIDTH TICKET INFO CARD --}}
          <div class="space-y-2">
            <h3 class="section-label">ข้อมูลบัตร</h3>

            <div class="ticket-info-card-full space-y-6">
              <div class="ticket-summary-grid">
                <div class="space-y-3">
                  <div class="ticket-summary-row">
                    <span class="font-semibold text-gray-700">รอบการแสดง</span>
                    <span class="text-gray-700 font-medium">{{ $concert ? $concert->event_datetime->locale('th')->isoFormat('D MMMM YYYY') . ', ' . $concert->event_datetime->format('H:i') . ' น.' : '-' }}</span>
                  </div>
                  <div class="ticket-summary-row">
                    <span class="font-semibold text-gray-700">โซน</span>
                    <span class="text-gray-600" x-text="selectedZone ? selectedZone.name : '-'"></span>
                  </div>
                  <div class="ticket-summary-row">
                    <span class="font-semibold text-gray-700">เลขที่นั่ง</span>
                    <span class="text-gray-600 font-medium" x-text="selectedSeats.length > 0 ? selectedSeats.join(', ') : '-'"></span>
                  </div>
                </div>

                <div class="space-y-3">
                  <div class="ticket-summary-row">
                    <span class="font-semibold text-gray-700">จำนวน</span>
                    <span class="text-gray-600" x-text="selectedSeats.length > 0 ? selectedSeats.length : '0'"></span>
                  </div>
                  <div class="ticket-summary-row">
                    <span class="font-semibold text-gray-700">ราคาต่อบัตร</span>
                    <span class="text-gray-600" x-text="selectedZone ? selectedZone.price + ' บาท' : '0 บาท'"></span>
                  </div>
                  <div class="ticket-summary-row">
                    <span class="font-bold text-[#F47B1F]">ราคารวม</span>
                    <span class="font-bold text-[#F47B1F]" x-text="calculateTotalPrice()"></span>
                  </div>
                </div>
              </div>

              <div class="action-buttons-center">
                <button class="btn-clean-back" @click="window.location.href='/'">
                  ย้อนกลับ
                </button>
                <button class="btn-clean-submit"
                        :disabled="selectedSeats.length === 0"
                        @click="nextPage()">
                  จองบัตร
                </button>
              </div>
            </div>
          </div>

        </div>
      </template>
      {{-- seat-map-option-2 end --}}

    </main>
  </div>
@endsection

@push('scripts')
<script>
    /**
     * bookingApp()
     * Alpine.js App Component สำหรับหน้าเลือกโซนและที่นั่งของคอนเสิร์ต
     * รองรับการอัปเดตสถานะที่นั่งกำลังทำรายการแบบ Real-time
     * @Author 67160434 จิตฏิยากร โสภา
     * @Create Date 2026-10-13
     */
    function bookingApp() {
      // ข้อมูลจากฐานข้อมูล (inject โดย PHP/Blade)
      const dbZones = @json($zones ?? []);
      const dbOccupied = @json($occupiedSeats ?? []);
      const dbPending = @json($pendingSeats ?? []);
      const concertId = @json($concert ? $concert->concert_id : null);
      const maxTickets = @json($concert ? $concert->max_ticket_per_order : 4);

      const defaultCols = Array.from({ length: 20 }, (_, i) => i + 1);

      return {
        totalSeats: dbZones.length > 0 ? (dbZones[0].totalCols || 20) : 20,
        selectedSeats: [],
        selectedZone: null,
        rows: dbZones.length > 0 ? (dbZones[0].rows || ['A','B','C','D','E']) : ['A','B','C','D','E'],
        zones: dbZones,
        zoneTags: dbZones,
        occupiedSeats: dbOccupied,
        pendingSeats: dbPending,

        /**
         * init()
         * เริ่มต้นระบบดักฟัง Event Real-time จาก WebSocket (Laravel Echo)
         */
        init() {
          if (typeof window.Echo !== 'undefined' && concertId) {
            window.Echo.channel(`concert.${concertId}`)
              .listen('.SeatStatusUpdated', (e) => {
                if (e.occupiedSeats) this.occupiedSeats = e.occupiedSeats;
                if (e.pendingSeats) this.pendingSeats = e.pendingSeats;
              });
          }
        },

        /**
         * selectZone()
         * เลือกโซนการแสดง
         * @param {Object} zone
         */
        selectZone(zone) {
          this.selectedZone = zone;
          this.cols = (zone.cols && zone.cols.length) ? zone.cols : defaultCols;
          this.rows = (zone.rows && zone.rows.length) ? zone.rows : ['A','B','C','D','E'];
          this.selectedSeats = [];
        },

        /**
         * selectZoneByName()
         * เลือกโซนตามชื่อ
         * @param {string} name
         */
        selectZoneByName(name) {
          const zone = this.zones.find(z => z.name === name);
          if (zone) this.selectZone(zone);
        },

        /**
         * toggleSeat()
         * เลือกหรือยกเลิกการเลือกที่นั่ง
         * @param {string} seatId
         */
        toggleSeat(seatId) {
          if (this.isOccupied(seatId) || this.isPending(seatId)) return;

          if (this.selectedSeats.includes(seatId)) {
            this.selectedSeats = this.selectedSeats.filter(s => s !== seatId);
          } else {
            if (this.selectedSeats.length < maxTickets) {
              this.selectedSeats.push(seatId);
              if (!this.selectedZone && this.zones.length > 0) {
                this.selectedZone = this.zones[0];
              }
            }
          }

          // ยิง Broadcast บอก Backend ว่าเรากำลังทำรายการที่นั่งนี้
          this.broadcastSeatSelection();
        },

        /**
         * broadcastSeatSelection()
         * ส่งข้อมูลที่นั่งที่กำลังเลือกไปยัง Backend แบบ Real-time
         */
        broadcastSeatSelection() {
          if (!concertId) return;
          fetch(`/api/concerts/${concertId}/hold-seats`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
              seats: this.selectedSeats,
              zone: this.selectedZone?.name
            })
          }).catch(() => {});
        },

        /**
         * getZoneColorForRow()
         * คืนค่าสีประจำโซนตามแถวของที่นั่ง
         * @param {string} row
         * @return {string}
         */
        getZoneColorForRow(row) {
          if (this.selectedZone && this.selectedZone.color) {
            return this.selectedZone.color;
          }
          for (const z of this.zones) {
            if (z.rows && z.rows.includes(row)) {
              return z.color || '#4D7EE0';
            }
          }
          return '#4D7EE0';
        },

        /**
         * isOccupied()
         * ตรวจสอบว่าที่นั่งถูกขายไปแล้วหรือไม่
         * @param {string} seatId
         * @return {boolean}
         */
        isOccupied(seatId) {
          return Array.isArray(this.occupiedSeats) && this.occupiedSeats.includes(seatId);
        },

        /**
         * isPending()
         * ตรวจสอบว่ามีลูกค้ารายอื่นกำลังทำรายการอยู่หรือไม่
         * @param {string} seatId
         * @return {boolean}
         */
        isPending(seatId) {
          return Array.isArray(this.pendingSeats) && this.pendingSeats.includes(seatId) && !this.selectedSeats.includes(seatId);
        },

        /**
         * calculateTotalPrice()
         * คำนวณราคารวมของที่นั่งที่เลือก
         * @return {string}
         */
        calculateTotalPrice() {
          if (!this.selectedZone || this.selectedSeats.length === 0) return '0 บาท';
          const price = parseInt(this.selectedZone.price) || 0;
          return (price * this.selectedSeats.length).toLocaleString() + ' บาท';
        },

        /**
         * nextPage()
         * นำทางไปยังหน้าชำระเงินพร้อมแนบข้อมูลตั๋ว
         */
        nextPage() {
          if (this.selectedSeats.length > 0 && this.selectedZone) {
            const params = new URLSearchParams({
              zone: this.selectedZone.name,
              seat: this.selectedSeats.join(','),
              price: this.selectedZone.price,
              quantity: this.selectedSeats.length,
            });
            if (concertId) params.set('concert_id', concertId);

            window.location.href = `/payment?${params.toString()}`;
          }
        }
      }
    }
</script>
@endpush
