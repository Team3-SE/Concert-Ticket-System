{{--
 * verify-notice.blade.php
 * หน้าแจ้งให้ตรวจอีเมลเพื่อกดยืนยันบัญชี หลังสมัครสมาชิก
 * @Author 67160292 ธีรเมธ พลสาย
 * @Create Date 17/10/2569
--}}
@extends('layouts.main')

@section('title', 'ยืนยันอีเมล - TEAM 3 Ticket')
@section('body_class', 'page-home')

@section('content')
<div class="verify-wrap">
  <div class="verify-card">
    <h1>ตรวจอีเมลของคุณ</h1>
    <p class="verify-text">
      เราได้ส่งลิงก์ยืนยันบัญชีไปที่<br>
      <strong>{{ $email }}</strong>
    </p>
    <p class="verify-hint">กดลิงก์ในอีเมลภายใน 60 นาทีเพื่อเปิดใช้งานบัญชี ถ้าไม่พบ ให้ตรวจโฟลเดอร์สแปมด้วย</p>

    @if(session('status'))
      <div class="modal-success">{{ session('status') }}</div>
    @endif
    @if(session('error'))
      <div class="modal-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
      <div class="modal-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('verification.resend') }}">
      @csrf
      <input type="hidden" name="email" value="{{ $email }}">
      <button type="submit" class="btn-orange">ส่งลิงก์ยืนยันอีกครั้ง</button>
    </form>

    <a class="verify-back" href="{{ url('/') }}">กลับหน้าแรก</a>
  </div>
</div>
@endsection
