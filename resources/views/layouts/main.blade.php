{{--
 * main.blade.php
 * โครงหน้าหลักของทุกหน้า: head, navbar, เนื้อหา และ footer
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 07-10-2569
 * @Update Date 23/10/2569 67160292 ธีรเมธ พลสาย
--}}
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'TEAM 3 Ticket')</title>

  {{-- Tailwind CSS & Alpine.js --}}
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  {{-- IBM Plex Sans Thai Font --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  {{-- CSS/JS ของทั้งเว็บ: เขียนใน resources/css, resources/js แล้ว Vite build เป็นไฟล์เดียว (ดู resources/css/README.md) --}}
  @vite(['resources/css/site.css', 'resources/js/site.js'])

  {{-- Page-specific styles --}}
  @stack('head')
</head>
<body class="@yield('body_class', 'min-h-screen flex flex-col bg-[#EBF1F9]')" @stack('body_attrs')>

  @hasSection('shell')
    {{-- Custom shell (e.g. layouts.app with sidebar) --}}
    @yield('shell')
  @else
    @include('partials.navbar')

    <div class="site-main">
      @yield('content')
    </div>

    @include('partials.footer')
  @endif

  {{-- หน้าต่างเข้าสู่ระบบ/สมัครสมาชิก: ใช้ได้ทุกหน้าตอนยังไม่ login (partials/auth-modals + resources/js/auth-modal.js) --}}
  @guest
    @include('partials.auth-modals')
  @endguest

  @stack('scripts')
</body>
</html>
