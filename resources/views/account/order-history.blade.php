{{--
 * order-history.blade.php
 * หน้าประวัติการสั่งซื้อของผู้ใช้
 * @Author
 * @Create Date
--}}
@extends('layouts.app')

@section('title', 'ประวัติการสั่งซื้อ - TEAM 3 Ticket')

@section('content')

  <div class="page-title">ประวัติการสั่งซื้อ</div>

  <div class="table-card">

    @if($orders->isEmpty())
      {{-- Empty state --}}
      <div class="empty-state">
        <div class="icon">🎫</div>
        <p>คุณยังไม่มีประวัติการสั่งซื้อ<br>ลองดูคอนเสิร์ตที่น่าสนใจแล้วจองตั๋วได้เลย!</p>
        <a href="{{ url('/') }}">ดูคอนเสิร์ตทั้งหมด</a>
      </div>

    @else
      <table class="orders">
        <thead>
          <tr>
            <th>หมายเลขคำสั่งซื้อ</th>
            <th>คอนเสิร์ต / สถานที่</th>
            <th>วัน-เวลา แสดง</th>
            <th>โซน และ เลขที่นั่ง</th>
            <th>จำนวนตั๋ว</th>
            <th>วันที่ทำรายการ</th>
            <th>จำนวนเงินรวม</th>
          </tr>
        </thead>
        <tbody>
          @foreach($orders as $order)
          @php
            $booking     = $order->booking;
            $concert     = $booking?->concert;
            $seats       = $booking?->bookingSeats ?? collect();
            $ticketCount = $order->tickets?->count() ?? $seats->count();

            // โซนและที่นั่ง: รวมจาก BookingSeat snapshots
            $seatLabels = $seats->map(fn($s) => ($s->zone_name_snapshot ?? '–') . ' : ' . ($s->seat_label_snapshot ?? '–'))
                               ->join(', ');
            if ($seatLabels === '') $seatLabels = '–';

            // ยอดรวม: จาก payment ถ้ามี ไม่งั้น booking
            $totalAmount = $order->payment?->amount ?? $booking?->total_amount ?? 0;

            // วันแสดง: ใช้ snapshot ใน CustomerOrder ก่อน fallback concert
            $eventDate = $order->event_datetime_snapshot ?? $concert?->date_time;
          @endphp
          <tr>
            <td><span class="order-id">#{{ $order->order_code ?? substr($order->order_id, 0, 8) }}</span></td>
            <td>
              <div class="concert-name">{{ $order->concert_name_snapshot ?? $concert?->name ?? '–' }}</div>
              <div class="concert-venue">{{ $order->venue_snapshot ?? $concert?->venue ?? '–' }}</div>
            </td>
            <td>
              {{ $eventDate ? \Carbon\Carbon::parse($eventDate)->translatedFormat('j M Y · H:i') : '–' }}
            </td>
            <td>{{ $seatLabels }}</td>
            <td><span class="qty-badge">{{ $ticketCount }} ใบ</span></td>
            <td>{{ \Carbon\Carbon::parse($order->order_date)->translatedFormat('j M Y') }}</td>
            <td class="amount">
              {{ number_format((float)$totalAmount, 0) }} ฿
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>

      <div class="table-footer">
        @include('partials.pagination', ['paginator' => $orders, 'unit' => 'คำสั่งซื้อ'])
      </div>

    @endif

  </div>

@endsection
