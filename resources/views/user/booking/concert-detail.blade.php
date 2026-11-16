{{--
  * concert-detail.blade.php
  * หน้ารายละเอียดคอนเสิร์ตและข้อมูลบัตร
  * @Author 67160279 กนกพล แซ่ตั๊น
  * @Create Date 18-10-2026
  * @Update Date 11/11/2569 67160292 ธีรเมธ พลสาย
*@--}}

@extends('layouts.user')

@section('title', $concert ? $concert->name . ' - TEAM 3 Ticket' : 'รายละเอียดคอนเสิร์ต - TEAM 3 Ticket')
@section('body_class', 'min-h-screen flex flex-col bg-[#EBF1F9]')

@section('content')
  <div class="flex-1">
    <div class="hero-banner"></div>

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
              {{-- schema 3.4.2: ช่องทางติดต่อผู้จัดเก็บไว้ที่คอนเสิร์ต (organizer กรอกในหน้าจัดการคอนเสิร์ต) --}}
              <div class="contact-info">
                <p>ติดต่อผู้จัดงาน</p>
                <div class="contact-grid">
                  <div>โทร: <span>{{ $concert->phone ?: '-' }}</span></div>
                  <div>Facebook: <span>{{ $concert->facebook ?: '-' }}</span></div>
                  <div>LINE: <span>{{ $concert->line ?: '-' }}</span></div>
                  <div>Email: <span>{{ $concert->email ?: '-' }}</span></div>
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

    <main class="main-body space-y-6">
      @if(!$concert)
        <section class="ticket-info-full-card text-center">
          <h2 class="section-label inline-block">ไม่พบข้อมูลคอนเสิร์ต</h2>
          <p class="mt-4 text-sm text-gray-600">คอนเสิร์ตนี้อาจถูกยกเลิกหรือนำออกจากระบบแล้ว</p>
          <a href="{{ url('/concerts') }}" class="btn-clean-submit mt-5 inline-block">กลับไปหน้าคอนเสิร์ต</a>
        </section>
      @else
        <section aria-label="โซนทั้งหมด">
          {{-- ใช้ส่วนเดียวกับหน้า booking (user/partials/zone-overview) --}}
          @include('user.partials.zone-overview', ['concert' => $concert, 'zones' => $zones])
        </section>

        <section class="ticket-info-full-card">
          <div class="flex flex-col gap-5">
            <div class="flex flex-col gap-4">
              <h2 class="heading-label">ซื้อบัตรการแสดง</h2>
              <p class="body-text text-gray-600">
                @if($concert->sale_start_datetime && $concert->sale_end_datetime)
                  @php($thaiWeekdays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'])
                  รอบการแสดง วัน{{ $thaiWeekdays[$concert->event_datetime->dayOfWeek] }}ที่ {{ $concert->event_datetime->thDateLong() }} ({{ $concert->event_datetime->format('H:i') }} น.)<br>
                @else
                  เลือกโซนที่นั่งและตรวจสอบจำนวนบัตรว่างก่อนเริ่มทำรายการ
                @endif
              </p>
            </div>
            <div class="mt-2 flex justify-center">
              @if($concert->canBook())
                {{-- ยังไม่ login: เปิดหน้าต่างเข้าสู่ระบบแทน แล้ว login เสร็จพาไปหน้าจองบัตรต่อ (resources/js/user/auth-modal.js) --}}
                <a href="{{ url('/booking?concert_id=' . $concert->c_id) }}" @guest data-login-required @endguest class="btn-clean-submit !text-[16px] block w-full max-w-[660px] text-center">
                  ซื้อบัตรการแสดง
                </a>
              @else
                {{-- จองไม่ได้: บอกเหตุผลตามสถานะ (บัตรหมด / ยกเลิก / การแสดงจบแล้ว / ปิดการขาย / ยังไม่เปิดขาย) --}}
                <button type="button" disabled class="btn-clean-submit !text-[16px] block w-full max-w-[660px] text-center">
                  {{ $concert->bookingBlockedMessage() }}
                </button>
              @endif
            </div>
          </div>
        </section>

        <section class="ticket-info-full-card">
          <div class="flex flex-col gap-4">
            <h2 class="heading-label">รายละเอียดงาน</h2>
            <div class="body-text text-gray-600">
              {{ $concert->description ?: 'ยังไม่มีรายละเอียดเพิ่มเติมสำหรับคอนเสิร์ตนี้' }}
            </div>
          </div>
        </section>

        <section class="ticket-info-full-card">
          <div class="flex flex-col gap-4">
            <h2 class="heading-label">ข้อกำหนดและเงื่อนไขการใช้บริการจองบัตร</h2>

                <div class="body-text text-base font-normal leading-7 text-gray-600">
                การใช้บริการจองบัตรผ่านระบบนี้ถือว่าผู้ใช้บริการได้อ่านและยอมรับข้อกำหนดและเงื่อนไขดังต่อไปนี้โดยครบถ้วน<br><br>

                ที่นั่งแต่ละตำแหน่งสามารถดำเนินการจองและออกบัตรได้เพียง 1 รายการต่อผู้ซื้อ 1 ท่านเท่านั้น ทางผู้ให้บริการขอสงวนสิทธิ์ในการจำหน่ายตามลำดับก่อน-หลังของการทำรายการ
                (First-Come, First-Served) โดยระบบจะทำการปิดสิทธิ์การจองที่นั่งดังกล่าวโดยทันทีเมื่อรายการเสร็จสมบูรณ์ <br><br>

                  เมื่อผู้ใช้บริการดำเนินการเลือกที่นั่งเรียบร้อยแล้ว ระบบจะทำการสำรองที่นั่ง (Lock) ไว้เป็นการชั่วคราวเป็นระยะเวลา 10 นาที เพื่อให้ผู้ใช้บริการดำเนินการชำระเงินให้แล้วเสร็จ
                  ภายในระยะเวลาดังกล่าว หากผู้ใช้บริการไม่สามารถดำเนินการชำระเงินให้แล้วเสร็จภายในเวลาที่กำหนด ระบบจะทำการยกเลิกรายการโดยอัตโนมัติ และคืนสิทธิ์การจองที่นั่งดังกล่าว
                  กลับเข้าสู่ระบบ เพื่อให้ผู้ใช้บริการรายอื่นสามารถดำเนินการจองต่อไปได้<br><br>

                  คำสั่งซื้อที่ได้รับการยืนยันการชำระเงินเรียบร้อยแล้ว ให้ถือเป็นที่สิ้นสุดและไม่สามารถเปลี่ยนแปลงแก้ไขได้ไม่ว่ากรณีใด ๆ ผู้ใช้บริการไม่มีสิทธิ์ขอยกเลิกรายการ ขอเปลี่ยนแปลงที่นั่ง
                  หรือรอบการแสดง หรือขอคืนเงินไม่ว่าด้วยเหตุผลใดทั้งสิ้น (Non-Refundable)<br><br>

                  บัตรเข้าชมงานที่ออกให้แก่ผู้ใช้บริการอยู่ในรูปแบบ QR Code ซึ่งสามารถใช้เพื่อสแกนผ่านประตูเข้าสู่พื้นที่จัดงานได้เพียงครั้งเดียวเท่านั้น (One-Time Access)
                  เมื่อมีการสแกนใช้งานบัตรดังกล่าวแล้ว บัตรจะถูกยกเลิกการใช้งานโดยทันที ทั้งนี้เพื่อป้องกันมิให้มีการนำ QR Code ไปใช้งานซ้ำหรือส่งต่อให้บุคคลอื่น<br><br>

                  ทั้งนี้ ผู้ให้บริการขอสงวนสิทธิ์ในการเปลี่ยนแปลงข้อกำหนดและเงื่อนไขดังกล่าวได้โดยไม่ต้องแจ้งให้ทราบล่วงหน้า
                </div>
          </div>

        </section>
      @endif
    </main>
    {{-- @* nav-bar end *@ --}}
  </div>
@endsection
