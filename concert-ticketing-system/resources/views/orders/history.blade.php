@extends('layouts.app')

@section('title', 'ประวัติการสั่งซื้อ - TEAM 3 Ticket')

@section('styles')
  .table-card{
    background:#fff;border-radius:12px;border:1px solid var(--gray-300);
    padding:8px 0 4px;
  }
  table.orders{
    width:100%;border-collapse:collapse;font-size:13px;
  }
  table.orders thead th{
    text-align:left;font-weight:600;color:var(--gray-700);
    padding:14px 24px;border-bottom:1px solid var(--gray-300);
    white-space:nowrap;
  }
  table.orders tbody td{
    padding:16px 24px;border-bottom:1px solid var(--gray-100);
    vertical-align:top;
  }
  table.orders tbody tr:last-child td{border-bottom:none;}
  .order-id{color:var(--navy-700);font-weight:700;}
  .concert-name{font-weight:700;color:var(--text);margin-bottom:2px;}
  .concert-venue{font-size:12px;color:var(--gray-500);}
  .qty-badge{
    display:inline-block;background:var(--gray-100);color:var(--gray-700);
    font-size:12px;font-weight:600;padding:3px 10px;border-radius:12px;
  }
  .amount{font-weight:700;color:var(--text);white-space:nowrap;}

  .table-footer{
    display:flex;align-items:center;justify-content:space-between;
    padding:16px 24px 18px;font-size:12px;color:var(--gray-700);
  }
  .table-footer b{color:var(--text);}
  .pagination{display:flex;align-items:center;gap:6px;}
  .pagination a, .pagination span{
    display:inline-flex;align-items:center;justify-content:center;
    min-width:30px;height:30px;padding:0 8px;
    border-radius:6px;font-size:12px;text-decoration:none;
    color:var(--gray-700);border:1px solid transparent;
  }
  .pagination a:hover{background:var(--gray-100);}
  .pagination .active{
    background:var(--navy-800);color:#fff;font-weight:600;
  }
  .pagination .disabled{color:var(--gray-500);}
@endsection

@section('content')

  <div class="page-title">ประวัติการสั่งซื้อ</div>

  {{--
    ยังไม่เชื่อมข้อมูลจริง — ตอนนี้ ทำแถวตัวอย่างไว้คร่าว ๆ เพื่อดูหน้าตา
    ทีหลังค่อยเปลี่ยนเป็น @forelse($orders as $order) แล้ว loop จากข้อมูลจริง
  --}}
  <div class="table-card">
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
        <tr>
          <td><span class="order-id">#T3-XXXXX</span></td>
          <td>
            <div class="concert-name">ชื่อคอนเสิร์ตตัวอย่าง</div>
            <div class="concert-venue">ชื่อสถานที่</div>
          </td>
          <td>00 ม.ค. 0000</td>
          <td>โซน XX : XXX</td>
          <td><span class="qty-badge">1 ใบ</span></td>
          <td>00 ม.ค. 0000</td>
          <td class="amount">0,000 ฿</td>
        </tr>
        <tr>
          <td><span class="order-id">#T3-XXXXX</span></td>
          <td>
            <div class="concert-name">ชื่อคอนเสิร์ตตัวอย่าง</div>
            <div class="concert-venue">ชื่อสถานที่</div>
          </td>
          <td>00 ม.ค. 0000</td>
          <td>โซน XX : XXX</td>
          <td><span class="qty-badge">1 ใบ</span></td>
          <td>00 ม.ค. 0000</td>
          <td class="amount">0,000 ฿</td>
        </tr>
        <tr>
          <td><span class="order-id">#T3-XXXXX</span></td>
          <td>
            <div class="concert-name">ชื่อคอนเสิร์ตตัวอย่าง</div>
            <div class="concert-venue">ชื่อสถานที่</div>
          </td>
          <td>00 ม.ค. 0000</td>
          <td>โซน XX : XXX</td>
          <td><span class="qty-badge">1 ใบ</span></td>
          <td>00 ม.ค. 0000</td>
          <td class="amount">0,000 ฿</td>
        </tr>
      </tbody>
    </table>

    <div class="table-footer">
      <div>แสดง <b>1 - 3</b> จากทั้งหมด <b>0</b> รายการ</div>
      <div class="pagination">
        <span class="disabled">« ก่อนหน้า</span>
        <a href="#" class="active">1</a>
        <a href="#">2</a>
        <a href="#">3</a>
        <span>...</span>
        <a href="#">10</a>
        <a href="#">ถัดไป »</a>
      </div>
    </div>
  </div>

@endsection