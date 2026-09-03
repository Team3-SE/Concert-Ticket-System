@extends('layouts.app')

@section('title', 'แก้ไขข้อมูลส่วนตัว - TEAM 3 Ticket')

@section('content')

  <div class="grid-2">

    <!-- ===== แก้ไขข้อมูลส่วนตัว ===== -->
    <section class="card">
      <h2><span class="bar"></span> แก้ไขข้อมูลส่วนตัว</h2>

      <form action="{{ route('profile.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row-2">
          <div class="field">
            <label for="first_name">ชื่อ</label>
            <input type="text" id="first_name" name="first_name"
                   value="{{ old('first_name', $user->first_name ?? 'อนิกุน') }}">
          </div>
          <div class="field">
            <label for="last_name">นามสกุล</label>
            <input type="text" id="last_name" name="last_name"
                   value="{{ old('last_name', $user->last_name ?? 'ชุนวิรกาล') }}">
          </div>
        </div>

        <div class="field">
          <label for="phone">เบอร์โทรศัพท์</label>
          <div class="phone-field">
            <span class="phone-prefix">🇹🇭 +66</span>
            <input type="tel" id="phone" name="phone"
                   value="{{ old('phone', $user->phone ?? '') }}"
                   placeholder="081-234-5678">
          </div>
        </div>

        <div class="actions">
          <button type="submit" class="btn btn-orange">บันทึกการแก้ไขข้อมูลส่วนตัว</button>
        </div>
      </form>
    </section>

    <!-- ===== เปลี่ยนรหัสผ่าน ===== -->
    <section class="card">
      <h2><span class="bar"></span> เปลี่ยนรหัสผ่าน</h2>

      <form action="{{ route('profile.password.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="field">
          <label for="current_password">รหัสผ่านปัจจุบัน</label>
          <input type="password" id="current_password" name="current_password"
                 placeholder="กรอกรหัสผ่านปัจจุบันของคุณ">
        </div>

        <div class="field">
          <label for="new_password">รหัสผ่านใหม่</label>
          <input type="password" id="new_password" name="new_password"
                 placeholder="กรอกรหัสผ่านใหม่">
        </div>

        <div class="field">
          <label for="new_password_confirmation">ยืนยันรหัสผ่านใหม่</label>
          <input type="password" id="new_password_confirmation" name="new_password_confirmation"
                 placeholder="กรอกรหัสผ่านใหม่อีกครั้ง">
        </div>

        <div class="actions">
          <button type="submit" class="btn btn-navy">บันทึกรหัสผ่านใหม่</button>
        </div>
      </form>
    </section>

  </div>

@endsection
