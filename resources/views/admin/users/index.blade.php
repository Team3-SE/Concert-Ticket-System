{{--
 * index.blade.php (Admin / users)
 * จัดการผู้ใช้: รายชื่อผู้ใช้ทุก role ค้นหา กรองบทบาท เพิ่มผู้ใช้ และระงับ/ปลดระงับบัญชี – หน้าแรกของ Admin หลัง login
 * ตอนนี้เป็นหน้าเปล่า รอทำตาม Figma (หน้า Admin → Manage users)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 27/10/2569
--}}
@extends('layouts.backoffice')

@section('title', 'จัดการผู้ใช้')
@section('body_class', 'page-admin-users')

@section('content')
  <div data-admin-users>
  <h1 class="bo-title">จัดการผู้ใช้</h1>

  <div class="bo-empty">
    <b>หน้านี้กำลังพัฒนา</b>
    <span>ตารางผู้ใช้ (ชื่อ อีเมล/เบอร์โทร บทบาท องค์กร สถานะบัญชี) ช่องค้นหา ตัวกรองบทบาท ปุ่มเพิ่มผู้ใช้ และจัดการบัญชี (ตาม Figma)</span>
  </div>
  </div>
@endsection
