@extends('layouts.app')

@section('title', 'ประวัติการสั่งซื้อ - TEAM 3 Ticket')

@section('styles')
  /* ===== Table card ===== */
  .table-card{
    background:#fff;
    border-radius:8px;
    border:1px solid var(--gray-border);
    overflow:hidden;
    max-width:1100px;
  }

  /* ===== Table ===== */
  table.orders{
    width:100%;border-collapse:collapse;font-size:12.5px;
  }
  table.orders thead th{
    text-align:left;
    font-size:11.5px;font-weight:600;color:#8b95b0;
    padding:13px 20px;
    border-bottom:1px solid var(--gray-border);
    white-space:nowrap;
    background:#fff;
  }
  table.orders tbody td{
    padding:14px 20px;
    border-bottom:1px solid #f0f1f7;
    vertical-align:middle;
    color:var(--text);
  }
  table.orders tbody tr:last-child td{border-bottom:none;}
  table.orders tbody tr:hover{background:#fafbff;}

  /* Cell variants */
  .order-id{color:#122070;font-weight:700;font-size:12.5px;}
  .concert-name{font-weight:700;color:var(--text);margin-bottom:2px;font-size:12.5px;}
  .concert-venue{font-size:11px;color:#8b95b0;}

  .qty-badge{
    display:inline-block;
    background:#e8eaf6;color:#3a50b0;
    font-size:11.5px;font-weight:700;
    padding:3px 12px;border-radius:20px;
    white-space:nowrap;
  }

  .amount{font-weight:700;color:var(--text);white-space:nowrap;font-size:13px;}

  /* ===== Footer / Pagination ===== */
  .table-footer{
    display:flex;align-items:center;justify-content:space-between;
    padding:14px 20px;font-size:11.5px;color:#8b95b0;
    border-top:1px solid #f0f1f7;
  }
  .table-footer b{color:var(--text);}

  .pagination{display:flex;align-items:center;gap:4px;}
  .pagination a, .pagination span{
    display:inline-flex;align-items:center;justify-content:center;
    min-width:28px;height:28px;padding:0 8px;
    border-radius:6px;font-size:11.5px;text-decoration:none;
    color:#4b5280;border:1px solid var(--gray-border);
    white-space:nowrap;
  }
  .pagination a:hover{background:#f0f2fb;border-color:#c5cadf;}
  .pagination .active{
    background:#122070;color:#fff;font-weight:700;border-color:#122070;
  }
  .pagination .disabled{color:#b0b8d0;border-color:#edeef5;cursor:default;}
  .pagination .ellipsis{border:none;color:#b0b8d0;min-width:auto;padding:0 2px;}

  /* ===== Empty state ===== */
  .empty-state{
    text-align:center;padding:60px 24px;color:#8b95b0;
  }
  .empty-state .icon{font-size:48px;margin-bottom:12px;}
  .empty-state p{font-size:13px;line-height:1.8;}
  .empty-state a{
    display:inline-block;margin-top:18px;
    background:var(--orange);color:#fff;font-weight:600;font-size:13px;
    padding:10px 26px;border-radius:20px;text-decoration:none;
    transition:background .15s;
  }
  .empty-state a:hover{background:var(--orange-dark);}
@endsection


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
        <div>
          แสดง <b>{{ $orders->firstItem() }}–{{ $orders->lastItem() }}</b>
          จากทั้งหมด <b>{{ $orders->total() }}</b> รายการ
        </div>
        <div class="pagination">
          {{-- < ก่อนหน้า --}}
          @if($orders->onFirstPage())
            <span class="disabled">‹ ก่อนหน้า</span>
          @else
            <a href="{{ $orders->previousPageUrl() }}">‹ ก่อนหน้า</a>
          @endif

          {{-- หน้าตัวเลข แบบ smart: 1 … current-1 current current+1 … last --}}
          @php
            $current  = $orders->currentPage();
            $last     = $orders->lastPage();
            $window   = 1; // จำนวนหน้าซ้าย/ขวาของหน้าปัจจุบัน
            $pages    = collect();
            // เสมอแสดงหน้าแรก
            $pages->push(1);
            // ช่องว่างหน้า current - window
            if ($current - $window > 2) $pages->push('...');
            // window รอบ current
            for ($p = max(2, $current - $window); $p <= min($last - 1, $current + $window); $p++) {
                $pages->push($p);
            }
            // ช่องว่างหลัง current + window
            if ($current + $window < $last - 1) $pages->push('...');
            // เสมอแสดงหน้าสุดท้าย (ถ้ามีมากกว่า 1 หน้า)
            if ($last > 1) $pages->push($last);
          @endphp

          @foreach($pages as $page)
            @if($page === '...')
              <span class="ellipsis">…</span>
            @elseif($page == $current)
              <span class="active">{{ $page }}</span>
            @else
              <a href="{{ $orders->url($page) }}">{{ $page }}</a>
            @endif
          @endforeach

          {{-- ถัดไป › --}}
          @if($orders->hasMorePages())
            <a href="{{ $orders->nextPageUrl() }}">ถัดไป ›</a>
          @else
            <span class="disabled">ถัดไป ›</span>
          @endif
        </div>
      </div>

    @endif

  </div>

@endsection

