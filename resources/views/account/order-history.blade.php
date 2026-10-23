{{--
 * order-history.blade.php
 * หน้าประวัติการสั่งซื้อของผู้ใช้
 * @Author
 * @Create Date
--}}
@extends('layouts.app')

@section('title', 'ประวัติการสั่งซื้อ - TEAM 3 Ticket')

@section('content')

  <div class="heading-text">ประวัติการสั่งซื้อ</div>

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
          @php
            // รูปแบบวันเวลา: ว/ด/ป(พ.ศ.) เวลา 24 ชม. เช่น 18/07/2569 13:37
            $thDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->thDateTime() : '–';
          @endphp
          @foreach($orders as $order)
          @php
            $booking     = $order->booking;
            $concert     = $booking?->concert;
            $seats       = $booking?->bookingSeats ?? collect();
            $ticketCount = $order->tickets?->count() ?? $seats->count();

            // โซนและที่นั่ง: จัดกลุ่มตามโซนจาก BookingSeat snapshots เช่น "โซน B4 : F05, F06"
            $seatGroups = $seats->groupBy(fn($s) => $s->zone_name_snapshot ?? '–');

            // โซนยืน: ไม่มีเลขที่นั่ง แสดงเป็น "โซน Standing : บัตรยืน x 2"
            $standingItems = $booking?->bookingStandingItems ?? collect();

            // ยอดรวม: จาก payment ถ้ามี ไม่งั้น booking
            $totalAmount = $order->payment?->amount ?? $booking?->total_amount ?? 0;

            // วันแสดง: ใช้ snapshot ใน CustomerOrder ก่อน fallback concert
            $eventDate = $order->event_datetime_snapshot ?? $concert?->date_time;
          @endphp
          <tr>
            <td><span class="order-id">{{ $order->order_code ?? substr($order->order_id, 0, 8) }}</span></td>
            <td>
              <div class="concert-name">{{ $order->concert_name_snapshot ?? $concert?->name ?? '–' }}</div>
              <div class="concert-venue">{{ $order->venue_snapshot ?? $concert?->venue ?? '–' }}</div>
            </td>
            <td>{{ $thDate($eventDate) }}</td>
            <td>
              @foreach($seatGroups as $zoneName => $zoneSeats)
                <div>
                  {{ \Illuminate\Support\Str::startsWith($zoneName, 'โซน') ? $zoneName : 'โซน ' . $zoneName }}
                  : <strong>{{ $zoneSeats->pluck('seat_label_snapshot')->filter()->join(', ') ?: '–' }}</strong>
                </div>
              @endforeach
              @foreach($standingItems as $standingItem)
                @php $zoneName = $standingItem->zone_name_snapshot ?? '–'; @endphp
                <div>
                  {{ \Illuminate\Support\Str::startsWith($zoneName, 'โซน') ? $zoneName : 'โซน ' . $zoneName }}
                  : <strong>บัตรยืน x {{ $standingItem->quantity }}</strong>
                </div>
              @endforeach
              @if($seatGroups->isEmpty() && $standingItems->isEmpty())
                –
              @endif
            </td>
            <td><span class="qty-badge">{{ $ticketCount }} ใบ</span></td>
            <td>{{ $thDate($order->order_date) }}</td>
            <td class="amount">
              {{ number_format((float)$totalAmount, 0) }} บาท
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
