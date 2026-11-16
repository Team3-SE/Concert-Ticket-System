{{--
 * verify-confirm.blade.php
 * หน้ากดปุ่มยืนยันอีเมล (เปิดจากลิงก์ในอีเมลสมัครสมาชิก) กดแล้วจึงสร้างบัญชีและเข้าสู่ระบบ (POST verification.confirm)
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 16/11/2569
 * @Update Date 16/11/2569 67160292 ธีรเมธ พลสาย
--}}
@extends('layouts.user')

@section('title', 'ยืนยันอีเมล - TEAM 3 Ticket')

@section('content')
<div class="mx-auto my-16 w-full max-w-md rounded-2xl bg-white p-8 text-center shadow">
  <h1 class="text-xl font-bold text-[#1a2b8f]">ยืนยันอีเมลเพื่อสมัครสมาชิก</h1>
  <p class="mt-3 text-sm text-gray-600">
    @if($firstName !== '') สวัสดีคุณ {{ $firstName }}<br>@endif
    กดปุ่มด้านล่างเพื่อยืนยันอีเมล <strong class="break-all">{{ $email }}</strong><br>
    แล้วเปิดใช้งานบัญชีของคุณ
  </p>

  <form method="POST" action="{{ $action }}" class="mt-6">
    @csrf
    <button type="submit" class="w-full rounded-lg bg-[#1a2b8f] px-4 py-3 font-semibold text-white hover:opacity-90">
      ยืนยันอีเมล
    </button>
  </form>
</div>
@endsection
