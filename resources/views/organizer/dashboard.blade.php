{{--
 * dashboard.blade.php (Organizer)
 * แผงควบคุม: รายงานภาพรวมการขาย – หน้าแรกของ Organizer หลัง login
 * ตอนนี้เป็นหน้าเปล่า รอทำตาม Figma (หน้า Organizer → Dashboard)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 27/10/2569
--}}
@extends('layouts.backoffice')

@section('title', 'แผงควบคุม')
@section('body_class', 'page-organizer-dashboard')

@section('content')
  <div data-organizer-dashboard>
  <h1 class="bo-title">รายงานภาพรวมการขาย</h1>

  <div class="bo-empty">
    <b>หน้านี้กำลังพัฒนา</b>
    <span>ยอดขายรวม ตั๋วที่ขายได้ การจองหลุด จำนวนคอนเสิร์ต กราฟรายได้รายเดือน และสัดส่วนช่องทางชำระเงิน (ตาม Figma)</span>
  </div>
  </div>
@endsection
