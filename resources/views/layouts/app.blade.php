{{--
 * app.blade.php
 * โครงหน้าแบบมี sidebar ซ้าย (หน้า account) ต่อยอดจาก layouts/main
 * @Author
 * @Create Date
--}}
@extends('layouts.main')

@section('body_class', 'page-account')

@section('shell')
<div class="shell">

  @include('partials.sidebar')

  {{-- Main --}}
  <div class="main">
    @include('partials.navbar', ['showLogo' => false])

    <main class="content">
      @if(session('status'))
        <div class="status-msg">{{ session('status') }}</div>
      @endif
      @if(session('error'))
        <div class="status-msg status-msg--error">{{ session('error') }}</div>
      @endif

      @yield('content')
    </main>
  </div>

</div>
@endsection
