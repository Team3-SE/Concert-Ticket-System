{{--
  * my-ticket.blade.php
  * แสดงตั๋วคอนเสิร์ตของผู้ใช้ที่เข้าสู่ระบบ
  * @Author 67160279 กนกพล แซ่ตั๊น
  * @Create Date 19-10-2026
*--}}

@extends('layouts.app')

@section('title', 'ตั๋วของฉัน - TEAM 3 Ticket')

@section('content')
  <h1 class="heading-text">ตั๋วของฉัน</h1>

  @if($tickets->isEmpty())
    <div class="ticket-empty-state">
      <div class="icon" aria-hidden="true">🎫</div>
      <p>คุณยังไม่มีตั๋วคอนเสิร์ต</p>
      <a href="{{ url('/') }}">เลือกชมคอนเสิร์ต</a>
    </div>
  @else
    <div class="my-ticket-grid">
      @foreach($tickets as $ticket)
        <article class="my-ticket-card">
          <div class="my-ticket-date" aria-label="วันที่แสดง {{ $ticket['event_day'] }} {{ $ticket['event_month'] }}">
            <span class="my-ticket-date-day">{{ $ticket['event_day'] }}</span>
            <span class="my-ticket-date-month">{{ $ticket['event_month'] }}</span>
          </div>

          <div class="my-ticket-details">
            <h2 class="my-ticket-concert-name">{{ $ticket['concert_name'] }}</h2>
            <p class="my-ticket-event-meta">
              เวลา {{ $ticket['event_time'] }} น. <span aria-hidden="true">|</span> {{ $ticket['venue'] }}
            </p>
            <div class="my-ticket-tags">
              <span>โซน: {{ $ticket['zone'] }}</span>
              <span>ที่นั่ง: {{ $ticket['seat'] }}</span>
            </div>
          </div>

          <button
            type="button"
            class="my-ticket-action"
            data-ticket-url="{{ route('tickets.show', ['ticketCode' => $ticket['ticket_code']]) }}"
            aria-haspopup="dialog">
            แสดงตั๋ว
          </button>
        </article>
      @endforeach
    </div>

    <div class="table-footer my-ticket-footer">
      @include('partials.pagination', ['paginator' => $tickets, 'unit' => 'ตั๋ว'])
    </div>

    <div class="ticket-modal-overlay" data-ticket-modal hidden>
      <section class="ticket-modal" role="dialog" aria-modal="true" aria-labelledby="ticket-modal-title">
        <header class="ticket-modal-header">

            <p class="ticket-modal-type">TICKET</p>
            <h2 id="ticket-modal-title" data-ticket-name></h2>
        </header>

        <div class="ticket-modal-content">
          <div class="ticket-modal-event">
            <div>
              <span class="ticket-modal-label">วันที่</span>
              <strong data-ticket-date></strong>
            </div>
            <div class="ticket-modal-time">
              <span class="ticket-modal-label">เวลา</span>
              <strong data-ticket-time></strong>
            </div>
          </div>

          <div class="ticket-modal-seats">
            <div class="ticket-modal-seat">
              <span class="ticket-modal-label">โซน</span>
              <strong data-ticket-zone></strong>
            </div>
            <div class="ticket-modal-seat">
              <span class="ticket-modal-label">ที่นั่ง</span>
              <strong data-ticket-seat></strong>
            </div>
          </div>

          <div class="ticket-modal-qr">
            <img data-ticket-qr alt="QR Code สำหรับตรวจสอบตั๋ว">
            <p class="ticket-modal-code">หมายเลขตั๋ว : <span data-ticket-code></span></p>
          </div>

          <div class="ticket-modal-contact">
            <p class="ticket-modal-contact-title">หากมีปัญหา ต้องการติดต่อผู้จัดงาน</p>
            <p>โทร: <span data-ticket-phone></span></p>
            <p>LINE: <span data-ticket-line></span></p>
            <p>Facebook: <span data-ticket-facebook></span></p>
            <p style="margin-bottom: 24px">Email: <span data-ticket-email></span></p>
          </div>
        </div>

        <footer class="ticket-modal-footer">
          <button type="button" data-ticket-close>ย้อนกลับ</button>
        </footer>
      </section>
    </div>
  @endif
@endsection
