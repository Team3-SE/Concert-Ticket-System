{{--
 * backoffice.blade.php
 * โครงหน้าหลังบ้านของ Organizer และ Admin (ใช้ร่วมกัน): sidebar ซ้าย + แถบด้านบน + เนื้อหา ตามแบบ Figma
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 27/10/2569
 * @Update Date 27/11/2569 67160292 ธีรเมธ พลสาย
--}}
{{--
 * เมนูใน sidebar เปลี่ยนตาม role อัตโนมัติ (partials/backoffice/sidebar)
 * วิธีใช้ : @extends('layouts.backoffice') แล้วใส่ @section('title'), @section('content')
 *          สไตล์เฉพาะหน้าใส่ใน resources/css/organizer/* หรือ admin/* แล้ว @import ใน backoffice.css
 * มือถือ/แท็บเล็ต (< 1024px): sidebar ซ่อน กดปุ่มเมนูที่แถบด้านบนเพื่อเปิด
--}}
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'หลังบ้าน') - TEAM 3 Ticket</title>

  {{-- Tailwind CSS & Alpine.js (เหมือนฝั่ง User) --}}
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  {{-- IBM Plex Sans Thai Font --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  {{-- CSS/JS หลังบ้าน (Organizer / Admin / Staff): resources/css/backoffice.css, resources/js/backoffice.js --}}
  @vite(['resources/css/backoffice.css', 'resources/js/backoffice.js'])

  @stack('head')
</head>
<body class="bo @yield('body_class')">

  <div class="bo-shell">
    @include('partials.backoffice.sidebar')
    <div class="bo-backdrop" data-bo-close></div>

    <div class="bo-main">
      @include('partials.backoffice.header')

      <main class="bo-content">
        {{-- ข้อความแจ้งผล (สำเร็จ/ผิดพลาด) --}}
        @if(session('status'))
          <div class="bo-alert bo-alert--ok" role="status">{{ session('status') }}</div>
        @endif
        @if(session('error'))
          <div class="bo-alert bo-alert--err" role="alert">{{ session('error') }}</div>
        @endif

        @yield('content')
      </main>
    </div>
  </div>

  @stack('scripts')
</body>
</html>
