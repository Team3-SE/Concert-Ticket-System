{{--
 * check-in.blade.php (Staff)
 * ตรวจสอบสิทธิ์เข้างาน: สถิติเข้างาน กล้องสแกน QR Code และช่องกรอกรหัสตั๋ว – หน้าเดียวของ Staff
 * มือถือ: เรียงคอลัมน์เดียว / จอกว้าง (≥ 900px): กล้องซ้าย ประวัติการสแกนขวา
 * ใช้ระบบตรวจตั๋วเดียวกับหน้า Organizer (CheckInController) ตอนนี้ยังไม่เชื่อมต่อ (ช่องกรอกปิดไว้)
 * หมายเหตุ: เบราว์เซอร์เปิดกล้องได้เฉพาะเว็บที่เป็น HTTPS หรือ localhost
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 27/10/2569
--}}
@extends('layouts.staff')

@section('title', 'ตรวจสอบสิทธิ์เข้างาน')

@section('content')
  <section class="st-stats" aria-label="สถิติการเข้างาน">
    <div><span>เข้างานแล้ว</span><b class="is-in">–</b></div>
    <div><span>ยังไม่เข้างาน</span><b class="is-wait">–</b></div>
    <div><span>ตั๋วทั้งหมด</span><b>–</b></div>
  </section>

  <div class="st-grid" data-check-in>
    <section class="st-scan">
      <div class="st-scanner" aria-label="พื้นที่สแกน QR Code">
        <div class="st-scanner__frame">
          <span class="st-scanner__line"></span>
          <p>วาง QR Code ในกรอบ</p>
        </div>
      </div>

      <form class="st-manual" onsubmit="return false">
        <label for="ticketCode" class="sr-only">รหัสตั๋ว</label>
        <input id="ticketCode" type="text" inputmode="text" autocomplete="off" placeholder="รหัสตั๋ว เช่น TKT-C000001-000001" disabled>
        <button type="submit" disabled>ตรวจ</button>
      </form>
      <p class="st-note">ระบบตรวจตั๋วกำลังพัฒนา</p>
    </section>

    <section class="st-feed" aria-label="ประวัติการสแกนล่าสุด">
      <h2>ประวัติการสแกนล่าสุด</h2>
      <p class="st-feed__empty">ยังไม่มีการสแกน</p>
    </section>
  </div>
@endsection
