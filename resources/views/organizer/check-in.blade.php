{{--
 * check-in.blade.php (Organizer)
 * ตรวจสอบสิทธิ์บัตรเข้างาน (Gate Check-in) แบบจอคอม: เลือกคอนเสิร์ต สถิติ กล้องสแกน QR และประวัติการสแกน
 * ใช้ระบบตรวจตั๋วเดียวกับหน้า Staff (CheckInController)
 * ตอนนี้เป็นหน้าเปล่า รอทำตาม Figma (หน้า Organizer → Check Event)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 27/10/2569
--}}
@extends('layouts.backoffice')

@section('title', 'ตรวจสอบสิทธิ์เข้างาน')
@section('body_class', 'page-organizer-check-in')

@section('content')
  <div data-check-in>
  <h1 class="bo-title">ตรวจสอบสิทธิ์บัตรเข้างาน (Gate Check-in)</h1>

  <div class="bo-empty">
    <b>หน้านี้กำลังพัฒนา</b>
    <span>เลือกคอนเสิร์ต, จำนวนเข้างานแล้ว/ยังไม่เข้างาน/ตั๋วที่ขายได้, สแกน QR Code หรือกรอกรหัสตั๋ว และประวัติการสแกนล่าสุด (ตาม Figma)</span>
  </div>
  </div>
@endsection
