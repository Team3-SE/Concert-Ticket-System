{{--
 * order-history.blade.php
 * หน้าประวัติการสั่งซื้อของผู้ใช้
 * @Author
 * @Create Date
 * @Update Date 27/11/2569 67160292 ธีรเมธ พลสาย
--}}
@extends('layouts.user-account')

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
          @foreach($orders as $order)
          <tr>
            <td><span class="order-id">{{ $order['order_code'] }}</span></td>
            <td>
              <div class="concert-name">{{ $order['concert_name'] }}</div>
              <div class="concert-venue">{{ $order['venue'] }}</div>
            </td>
            <td>{{ $order['event_datetime'] }}</td>
            <td>
              @foreach($order['zones'] as $zone)
                <div>
                  {{ $zone['name'] }} : <strong>{{ $zone['details'] }}</strong>
                </div>
              @endforeach
              @if($order['zones']->isEmpty())
                –
              @endif
            </td>
            <td><span class="qty-badge">{{ $order['ticket_count'] }} ใบ</span></td>
            <td>{{ $order['order_date'] }}</td>
            <td class="amount">
              {{ $order['total_amount'] }} บาท
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
