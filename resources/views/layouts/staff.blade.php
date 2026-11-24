{{--
 * staff.blade.php
 * โครงหน้าของ Staff (เจ้าหน้าที่หน้างาน): แถบด้านบน + เนื้อหา ไม่มี sidebar (Staff ใช้ได้แค่ตรวจสอบสิทธิ์เข้างาน)
 * ออกแบบให้มือถือก่อน (ตามแบบ Figma) แต่ใช้ได้ทุกขนาดจอ: จอกว้างจะจัดกลางและแบ่งสองคอลัมน์ได้
 * ออกจากระบบ: กดชื่อ/รูปโปรไฟล์มุมขวาบน
 * วิธีใช้ : @extends('layouts.staff') แล้วใส่ @section('title'), @section('content')
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 27/10/2569
--}}
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="theme-color" content="#0b2f61">
  <title>@yield('title', 'ตรวจสอบสิทธิ์เข้างาน') - TEAM 3 Ticket</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  @vite(['resources/css/backoffice.css', 'resources/js/backoffice.js'])

  @stack('head')
</head>
<body class="st @yield('body_class')">
  @php($stName = auth()->user()->displayName())

  <header class="st-header">
    <span class="st-logo">
      <img src="{{ asset('images/logo.png') }}" alt="" width="28" height="28">
      <span>TEAM <b>3</b> Ticket</span>
    </span>

    <div class="st-user" data-dropdown>
      <button type="button" class="st-user__btn" data-dropdown-toggle aria-haspopup="true" aria-expanded="false">
        <span class="st-user__name">{{ $stName }}</span>
        <span class="st-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($stName, 0, 1)) }}</span>
      </button>
      <div class="st-user__menu" data-dropdown-menu hidden>
        <form method="POST" action="{{ route('auth.logout') }}">
          @csrf
          <button type="submit">ออกจากระบบ</button>
        </form>
      </div>
    </div>
  </header>

  <main class="st-content">
    @yield('content')
  </main>

  @stack('scripts')
</body>
</html>
