{{--
 * user-account.blade.php
 * โครงหน้าแบบมี sidebar ซ้าย (หน้า account) ต่อยอดจาก layouts/user
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 24/10/2569
 * @Update Date 27/11/2569 67160292 ธีรเมธ พลสาย
--}}
@extends('layouts.user')

@section('body_class', 'page-account')

@section('shell')
<div class="shell">

  @include('user.partials.sidebar')

  {{-- Main --}}
  <div class="main">
    @include('user.partials.navbar', ['showLogo' => false])

    <main class="content">
      {{-- ข้อความแจ้งผล (สำเร็จ/ผิดพลาด) หายเองใน 4 วินาที หรือกดปิดได้ --}}
      @if(session('status'))
        <div class="status-msg" role="status">
          <div class="status-msg__icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></div>
          <div class="status-msg__text"><b>บันทึกสำเร็จ</b><span>{{ session('status') }}</span></div>
          <button type="button" class="status-msg__close" aria-label="ปิด">&times;</button>
        </div>
      @endif
      @if(session('error'))
        <div class="status-msg status-msg--error" role="alert">
          <div class="status-msg__icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></div>
          <div class="status-msg__text"><b>เกิดข้อผิดพลาด</b><span>{{ session('error') }}</span></div>
          <button type="button" class="status-msg__close" aria-label="ปิด">&times;</button>
        </div>
      @endif

      @yield('content')
    </main>
  </div>

</div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('.status-msg').forEach(function (box) {
    var hide = function () { box.classList.add('is-hiding'); setTimeout(function () { box.remove(); }, 300); };
    box.querySelector('.status-msg__close').addEventListener('click', hide);
    setTimeout(hide, 4000);
  });
</script>
@endpush
