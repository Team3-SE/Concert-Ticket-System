{{--
 * booking.blade.php
 * หน้าเลือกโซนและที่นั่งของคอนเสิร์ต
 * @Author 67160434 จิตฏิยากร โสภา
 * @Create Date 2026-10-13
 * @Update Date 23/10/2569 67160292 ธีรเมธ พลสาย
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

        <div class="poster-card">
          @if($concert?->poster_path)
            <img src="{{ asset('storage/' . $concert->poster_path) }}" alt="โปสเตอร์ {{ $concert->name }}">
          @else
            <div class="flex h-full items-center justify-center bg-[#0D2247] px-4 text-center text-sm text-gray-300">
              ไม่มีโปสเตอร์คอนเสิร์ต
            </div>
          @endif
        </div>

        <section class="main-hero-card" aria-labelledby="concert-title">
          <div class="hero-content-left">
            <div>
              <h1 class="hero-title" id="concert-title">{{ $concert ? mb_strtoupper($concert->name) : 'ไม่พบคอนเสิร์ต' }}</h1>
              @if($concert)
                <div class="badge-group">
                  <span class="badge-item">
                    <svg class="badge-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    {{ $concert->event_datetime?->thDateLong() ?? '-' }}
                  </span>
                  <span class="badge-item">
                    <svg class="badge-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 16 14"/></svg>
                    {{ $concert->event_datetime?->format('H:i') ?? '-' }} น.
                  </span>
                  <span class="badge-item">
                    <svg class="badge-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    {{ $concert->venue ?: '-' }}
                  </span>
                </div>
              @endif
            </div>

            @if($concert)
              @php($organization = $concert->organization)
              <div class="contact-info">
                <p>ติดต่อผู้จัดงาน</p>
                <div class="contact-grid">
                  <div>โทร: <span>{{ $organization?->phone ?: '-' }}</span></div>
                  <div>Facebook: <span>{{ $organization?->facebook ?: '-' }}</span></div>
                  <div>LINE: <span>{{ $organization?->line ?: '-' }}</span></div>
                  <div>Email: <span>{{ $organization?->email ?: '-' }}</span></div>
                </div>
              </div>
            @endif
          </div>

          @if($concert)
            <aside class="price-panel" aria-label="ราคาบัตร">
              <span class="price-title">TICKET PRICES</span>
              <div>
                @forelse($zones as $zone)
                  <div class="price-row">
                    <span class="num">{{ number_format($zone['price']) }}</span>
                    <span class="unit">THB</span>
                  </div>
                @empty
                  <p class="text-xs text-gray-300">ยังไม่มีข้อมูลราคาบัตร</p>
                @endforelse
              </div>
            </aside>
          @endif
        </section>

      </div>
    </div>

    {{-- MAIN BODY CONTENT --}}
    <main class="main-body space-y-6">

      {{-- DATE SELECTOR --}}
      <div class="space-y-4">
        <h3 class="section-label">วันเวลาการแสดง</h3>
        <div class="date-pill">
          <span class="dot-orange"></span>
          วันที่ {{ $concert ? $concert->event_datetime->thDateLong() : '-' }}
          เวลา {{ $concert ? $concert->event_datetime->format('H:i') : '-' }} น.
        </div>
      </div>

      {{-- zone-overview start --}}
      {{-- ZONE OVERVIEW MAP & TABLE (ใช้ร่วมกับหน้า concert-detail) --}}
      @include('partials.zone-overview', ['concert' => $concert, 'zones' => $zones, 'selectable' => false])
      {{-- zone-overview end --}}

      {{-- zone-selector start --}}
      {{-- ZONE TAG BUTTONS SELECTOR --}}
      <div class="space-y-4">
        <h3 class="section-label">เลือกโซน</h3>
        <div class="flex flex-wrap gap-2">
          <template x-for="(tag, idx) in zoneTags" :key="idx">
            <button @click="selectZoneByName(tag.name)"
                    :style="{ backgroundColor: tag.color }"
                    :class="selectedZone && selectedZone.name === tag.name ? 'border-[#F47B20]' : 'border-transparent'"
                    class="text-white text-[14px] h-8 px-[10px] rounded-[4px] border-2 font-bold hover:opacity-90 transition"
                    x-text="tag.name">
            </button>
          </template>
        </div>
      </div>
      {{-- zone-selector end --}}

      {{-- seat-map-option-1 start --}}
      {{-- OPTION 1: SEAT MAP <= 20 SEATS --}}
      <template x-if="selectedZone && !isStanding() && totalSeats <= 20">
        <div>
          <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-4">
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
              <div class="seat-stage mx-auto mb-6">STAGE</div>

              <div class="overflow-x-auto seat-scroll pb-2">
                <div class="flex flex-col seat-gap items-center min-w-fit mx-auto">

                  <div class="flex seat-gap items-center mb-1">
                    <span class="seat-row-spacer"></span>
                    <template x-for="col in totalSeats" :key="'hdr-'+col">
                      <span class="seat-col-label text-[14px] seat-label-text text-center inline-flex items-center justify-center font-bold flex-shrink-0" x-text="col"></span>
                    </template>
                    <span class="seat-row-spacer"></span>
                  </div>

                  {{-- แถวเก้าอี้ที่นั่ง --}}
                  <template x-for="row in rows" :key="'r20-'+row">
                    <div class="flex seat-gap items-center">
                      <span class="seat-row-label text-[14px] seat-label-text text-center inline-flex items-center justify-center font-bold select-none flex-shrink-0" x-text="row"></span>
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
                                class="seat-icon-shape transition-all duration-150 flex-shrink-0 border-0 outline-none p-0 flex items-center justify-center">
                          <template x-if="selectedSeats.includes(row + col)">
                            <svg class="seat-check text-white stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                          </template>
                        </button>
                      </template>
                      <span class="seat-row-label text-[14px] seat-label-text text-center inline-flex items-center justify-center font-bold select-none flex-shrink-0" x-text="row"></span>
                    </div>
                  </template>

                </div>
              </div>

              {{-- Legend --}}
              <div class="flex justify-center gap-6 mt-8 text-[16px] text-gray-500">
                <div class="flex items-center gap-2">
                  <span class="seat-legend-icon" :style="{ backgroundColor: selectedZone ? selectedZone.color : '#4D7EE0' }"></span> ว่าง
                </div>
                <div class="flex items-center gap-2">
                  <span class="seat-legend-icon seat-btn-occupied"></span> ขายแล้ว
                </div>
                <div class="flex items-center gap-2">
                  <span class="seat-legend-icon bg-[#F47B1F]"></span> กำลังทำรายการ
                </div>
              </div>
            </div>

            {{-- ฝั่งขวา: ข้อมูลบัตร --}}
            <div class="md:col-span-5 bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between min-h-[300px]">
              <div class="text-[16px] space-y-4">
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
                  <span class="font-bold text-[#F47B1F]">ราคารวม</span>
                  <span class="font-bold text-[#F47B1F]" x-text="calculateTotalPrice()"></span>
                </div>
              </div>

              <div class="flex gap-3 mt-8">
                <button class="flex-1 py-[15px] rounded-lg bg-[#E8EEF7] text-gray-600 text-[16px] font-semibold hover:bg-gray-200 transition" @click="window.location.href='/'">ย้อนกลับ</button>
                <button :disabled="selectedSeats.length === 0 || isSubmitting"
                        :class="selectedSeats.length > 0 ? 'bg-[#F47B1F] hover:bg-orange-600 text-white shadow-md cursor-pointer' : 'bg-[#FBC48E] text-white cursor-not-allowed'"
                        class="flex-1 py-[15px] rounded-lg text-[16px] font-semibold transition"
                        @click="nextPage()">จองบัตร</button>
              </div>
            </div>

          </div>
        </div>
      </template>
      {{-- seat-map-option-1 end --}}

      {{-- seat-map-option-standing start --}}
      {{-- OPTION 3: STANDING ZONE (เลือกจำนวนบัตร ไม่มีผังที่นั่ง) --}}
      <template x-if="selectedZone && isStanding()">
        <div>
          <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-4">
            <div class="md:col-span-7">
              <h3 class="section-label">เลือกจำนวนบัตร</h3>
            </div>
            <div class="md:col-span-5">
              <h3 class="section-label">ข้อมูลบัตร</h3>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-stretch">

            {{-- ฝั่งซ้าย: เลือกจำนวนบัตร (แบบการ์ดตั๋ว: ข้อมูลโซน | รอยปรุ | จำนวนบัตร) --}}
            <div class="md:col-span-7 h-full flex flex-col md:flex-row bg-white rounded-2xl border border-[#DFE6F2] shadow-[0_4px_10px_rgba(11,27,61,0.05)]">

              {{-- ส่วนซ้ายของตั๋ว: ข้อมูลโซนและราคา --}}
              <div class="flex-[3_1_0%] flex flex-col gap-2.5 p-8">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-[3px] flex-shrink-0" :style="{ backgroundColor: selectedZone.color || '#4D7EE0' }"></span>
                  <span class="text-[12px] font-semibold tracking-[2px] text-[#5B6580]">STANDING ZONE</span>
                </div>
                <span class="text-[32px] font-bold leading-tight text-[#0A1F45] break-words" x-text="selectedZone.name"></span>
                <span class="text-[14px] text-[#5B6580]">ไม่ระบุที่นั่ง · บัตร 1 ใบเข้างานได้ 1 คน</span>
                <div class="mt-auto pt-4 flex items-baseline gap-1.5">
                  <span class="text-[28px] font-bold text-[#0A1F45]" x-text="Number(selectedZone.price).toLocaleString() + ' บาท'"></span>
                  <span class="text-[14px] text-[#5B6580]">/ ใบ</span>
                </div>
              </div>

              {{-- ส่วนขวาของตั๋ว: จำนวนบัตร (รอยปรุเส้นประ + รอยเว้าครึ่งวงกลมบน/ล่าง) --}}
              <div class="relative flex-[2_1_0%] flex flex-col items-center justify-center gap-4 px-6 py-8 border-t-2 md:border-t-0 md:border-l-2 border-dashed border-[#DFE6F2]">
                <span class="hidden md:block absolute -left-[13px] -top-[13px] w-6 h-6 rounded-full bg-[#EBF1F9]"></span>
                <span class="hidden md:block absolute -left-[13px] -bottom-[13px] w-6 h-6 rounded-full bg-[#EBF1F9]"></span>

                <template x-if="standingMax() > 0">
                  <div class="flex flex-col items-center gap-4">
                    <span class="text-[14px] text-[#5B6580]">จำนวนบัตร</span>
                    <span class="text-[64px] font-bold leading-none text-[#0A1F45]" x-text="standingQty"></span>
                    <div class="flex gap-4">
                      <button type="button" @click="changeQuantity(-1)" :disabled="standingQty <= 1"
                              class="w-12 h-12 rounded-full border-2 border-[#F47B1F] bg-white text-[#C2570C] text-[22px] font-bold leading-none disabled:opacity-[0.35] disabled:cursor-not-allowed transition"
                              aria-label="ลดจำนวนบัตร">−</button>
                      <button type="button" @click="changeQuantity(1)" :disabled="standingQty >= standingMax()"
                              class="w-12 h-12 rounded-full border-2 border-[#F47B1F] bg-[#F47B1F] text-white text-[22px] font-bold leading-none hover:bg-[#ea580c] disabled:opacity-[0.35] disabled:cursor-not-allowed transition"
                              aria-label="เพิ่มจำนวนบัตร">+</button>
                    </div>
                    <span class="text-[13px] text-[#5B6580]">สูงสุด <span x-text="standingMax()"></span> ใบต่อครั้ง</span>
                  </div>
                </template>

                <template x-if="standingMax() === 0">
                  <div class="flex flex-col items-center gap-2 text-center">
                    <span class="px-3 py-1 rounded-xl bg-[#E6483C] text-white text-[16px] font-bold">Sold out</span>
                    <span class="text-[14px] text-[#5B6580]">บัตรโซนนี้ขายหมดแล้ว</span>
                  </div>
                </template>
              </div>
            </div>

            {{-- ฝั่งขวา: ข้อมูลบัตร --}}
            <div class="md:col-span-5 bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
              <div class="text-[16px] space-y-4">
                <div class="flex justify-between border-b border-gray-100 pb-3">
                  <span class="font-semibold text-gray-700">รอบการแสดง</span>
                  <span class="text-gray-700 font-medium">{{ $concert ? $concert->event_datetime->thDateLong() . ', ' . $concert->event_datetime->format('H:i') . ' น.' : '-' }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-3">
                  <span class="font-semibold text-gray-700">โซน</span>
                  <span class="text-gray-600" x-text="selectedZone.name"></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-3">
                  <span class="font-semibold text-gray-700">เลขที่นั่ง</span>
                  <span class="text-gray-600 font-medium">บัตรยืน</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-3">
                  <span class="font-semibold text-gray-700">จำนวน</span>
                  <span class="text-gray-600" x-text="standingQty"></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-3">
                  <span class="font-semibold text-gray-700">ราคาต่อบัตร</span>
                  <span class="text-gray-600" x-text="Number(selectedZone.price).toLocaleString() + ' บาท'"></span>
                </div>
                <div class="flex justify-between pt-1">
                  <span class="font-bold text-[#F47B1F]">ราคารวม</span>
                  <span class="font-bold text-[#F47B1F]" x-text="calculateTotalPrice()"></span>
                </div>
              </div>

              <div class="flex gap-3 mt-8">
                <button class="flex-1 py-[15px] rounded-lg bg-[#E8EEF7] text-gray-600 text-[16px] font-semibold hover:bg-gray-200 transition" @click="window.location.href='/'">ย้อนกลับ</button>
                <button :disabled="ticketCount() === 0 || isSubmitting"
                        :class="ticketCount() > 0 ? 'bg-[#F47B1F] hover:bg-orange-600 text-white shadow-md cursor-pointer' : 'bg-[#FBC48E] text-white cursor-not-allowed'"
                        class="flex-1 py-[15px] rounded-lg text-[16px] font-semibold transition"
                        @click="nextPage()">จองบัตร</button>
              </div>
            </div>

          </div>
        </div>
      </template>
      {{-- seat-map-option-standing end --}}

      {{-- seat-map-option-2 start --}}
      {{-- OPTION 2: SEAT MAP > 20 SEATS --}}
      <template x-if="selectedZone && !isStanding() && totalSeats > 20">
        <div class="space-y-6">

          <div class="space-y-4">
            <h3 class="section-label">เลือกที่นั่ง</h3>

            <div class="seat-map-transparent-box space-y-6 p-2">
              <div class="seat-stage mx-auto">STAGE</div>

              <div class="overflow-x-auto seat-scroll py-2">
                <div class="flex flex-col seat-gap items-center min-w-fit mx-auto">

                  {{-- Header Numbers --}}
                  <div class="flex seat-gap items-center mb-1">
                    <span class="seat-row-spacer"></span>
                    <template x-for="col in totalSeats" :key="'hdr40-'+col">
                      <span class="seat-col-label text-[14px] seat-label-text text-center font-bold flex-shrink-0" x-text="col"></span>
                    </template>
                    <span class="seat-row-spacer"></span>
                  </div>

                  {{-- Seat Rows --}}
                  <template x-for="row in rows" :key="'r40-'+row">
                    <div class="flex seat-gap items-center">
                      <span class="seat-row-label text-[14px] seat-label-text text-center inline-flex items-center justify-center font-bold select-none flex-shrink-0" x-text="row"></span>
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
                                class="seat-icon-shape transition-all duration-150 flex-shrink-0 border-0 outline-none p-0 flex items-center justify-center">
                          <template x-if="selectedSeats.includes(row + col)">
                            <svg class="seat-check text-white stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                          </template>
                        </button>
                      </template>
                      <span class="seat-row-label text-[14px] seat-label-text text-center inline-flex items-center justify-center font-bold select-none flex-shrink-0" x-text="row"></span>
                    </div>
                  </template>
                </div>
              </div>

              {{-- Seat Legend --}}
              <div class="flex justify-center gap-6 pt-2 text-[16px] text-gray-500">
                <div class="flex items-center gap-2">
                  <span class="seat-legend-icon" :style="{ backgroundColor: selectedZone ? selectedZone.color : '#4D7EE0' }"></span> ว่าง
                </div>
                <div class="flex items-center gap-2">
                  <span class="seat-legend-icon seat-btn-occupied"></span> ขายแล้ว
                </div>
                <div class="flex items-center gap-2">
                  <span class="seat-legend-icon bg-[#F47B1F]"></span> กำลังทำรายการ
                </div>
              </div>
            </div>
          </div>

          {{-- 2. FULL WIDTH TICKET INFO CARD --}}
          <div class="space-y-4">
            <h3 class="section-label">ข้อมูลบัตร</h3>

            <div class="ticket-info-card-full">
              <div class="ticket-summary-grid">
                <div>
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

                <div>
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
                        :disabled="selectedSeats.length === 0 || isSubmitting"
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

      // จำนวนคอลัมน์ของโซน = เลขที่นั่งสูงสุดที่มีจริง (ถ้าไม่มีข้อมูลใช้ 20)
      const colCount = (zone) => {
        const nums = (zone && zone.cols ? zone.cols : []).map(Number).filter(n => !isNaN(n));
        return nums.length ? Math.max(...nums) : 20;
      };

      return {
        totalSeats: dbZones.length > 0 ? colCount(dbZones[0]) : 20,
        selectedSeats: [],
        selectedZone: null,
        isSubmitting: false,
        standingQty: 1,
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
          this.totalSeats = colCount(zone);
          this.rows = (zone.rows && zone.rows.length) ? zone.rows : ['A','B','C','D','E'];
          this.selectedSeats = [];
          this.standingQty = this.standingMax() > 0 ? 1 : 0;
        },

        /**
         * isStanding()
         * โซนที่เลือกเป็นโซนยืน (เลือกจำนวนบัตร ไม่มีผังที่นั่ง)
         * @return {boolean}
         */
        isStanding() {
          return !!this.selectedZone && this.selectedZone.zone_type === 'STANDING';
        },

        /**
         * standingMax()
         * จำนวนบัตรโซนยืนสูงสุดที่เลือกได้ = น้อยกว่าระหว่างจำนวนต่อครั้งกับที่เหลือ
         * @return {number}
         */
        standingMax() {
          if (!this.selectedZone) return 0;
          return Math.max(0, Math.min(maxTickets, parseInt(this.selectedZone.seats) || 0));
        },

        /**
         * changeQuantity()
         * เพิ่ม/ลดจำนวนบัตรโซนยืน
         * @param {number} step
         */
        changeQuantity(step) {
          this.standingQty = Math.min(this.standingMax(), Math.max(1, this.standingQty + step));
        },

        /**
         * ticketCount()
         * จำนวนบัตรที่เลือก (โซนยืน = จำนวนที่เลือก, โซนนั่ง = จำนวนที่นั่ง)
         * @return {number}
         */
        ticketCount() {
          return this.isStanding() ? this.standingQty : this.selectedSeats.length;
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
          // เช็กเฉพาะที่นั่งของโซนที่เลือก (ป้ายที่นั่งอย่าง A1 ซ้ำกันได้ในหลายโซน)
          const list = (this.selectedZone && Array.isArray(this.selectedZone.occupied))
            ? this.selectedZone.occupied
            : this.occupiedSeats;
          return Array.isArray(list) && list.includes(seatId);
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
          if (!this.selectedZone || this.ticketCount() === 0) return '0 บาท';
          const price = parseInt(this.selectedZone.price) || 0;
          return (price * this.ticketCount()).toLocaleString() + ' บาท';
        },

        /**
         * nextPage()
         * สร้างการจองจริง (ล็อกที่นั่ง) แล้วไปหน้าชำระเงินของการจองนั้น
         */
        async nextPage() {
          if (this.isSubmitting || this.ticketCount() === 0 || !this.selectedZone || !concertId) return;
          this.isSubmitting = true;

          try {
            const res = await fetch('/bookings', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
              },
              body: JSON.stringify(this.isStanding()
                ? { concert_id: concertId, zone_id: this.selectedZone.zone_id, quantity: this.standingQty }
                : { concert_id: concertId, zone_id: this.selectedZone.zone_id, seats: this.selectedSeats }),
            });

            if (res.status === 401) {
              alert('กรุณาเข้าสู่ระบบก่อนจองบัตร');
              document.getElementById('openLogin')?.click();
              return;
            }

            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
              alert(data.message || 'จองที่นั่งไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
              return;
            }

            window.location.href = data.redirect;
          } catch (e) {
            alert('เชื่อมต่อระบบไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
          } finally {
            this.isSubmitting = false;
          }
        }
      }
    }
</script>
@endpush
