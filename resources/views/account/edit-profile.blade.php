{{--
 * edit-profile.blade.php
 * หน้าแก้ไขข้อมูลส่วนตัวและเปลี่ยนรหัสผ่าน
 * @Author
 * @Create Date
--}}
@extends('layouts.app')

@section('title', 'แก้ไขข้อมูลส่วนตัว - TEAM 3 Ticket')

@section('content')

  <div class="profile-grid">

    {{-- แก้ไขข้อมูลส่วนตัว --}}
    <section>
      <h1 class="heading-text">แก้ไขข้อมูลส่วนตัว</h1>
      <div class="profile-panel profile-panel--info">

      <form action="{{ route('profile.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row-2">
          <div class="field">
            <label for="first_name">ชื่อ</label>
            <input type="text" id="first_name" name="first_name"
                   value="{{ old('first_name', $user->first_name ?? '') }}"
                   class="{{ $errors->has('first_name') ? 'is-error' : '' }}">
            @error('first_name')<p class="field-error">{{ $message }}</p>@enderror
          </div>
          <div class="field">
            <label for="last_name">นามสกุล</label>
            <input type="text" id="last_name" name="last_name"
                   value="{{ old('last_name', $user->last_name ?? '') }}"
                   class="{{ $errors->has('last_name') ? 'is-error' : '' }}">
            @error('last_name')<p class="field-error">{{ $message }}</p>@enderror
          </div>
        </div>

        <div class="field">
          <label for="phone">เบอร์โทรศัพท์</label>
          <div class="phone-field {{ $errors->has('phone') ? 'is-error' : '' }}">
            <div class="phone-prefix">
              <span class="flag">🇹🇭</span>
              <span>+66</span>
            </div>
            <input type="tel" id="phone" name="phone"
                   value="{{ old('phone', $user->phone ?? '') }}"
                   placeholder="081-234-5678">
          </div>
          @error('phone')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div class="actions">
          <button type="submit" class="btn btn-orange">
            บันทึกการแก้ไขข้อมูลส่วนตัว
          </button>
        </div>
      </form>
      </div>
    </section>

    {{-- เปลี่ยนรหัสผ่าน --}}
    <section>
      <h1 class="heading-text">เปลี่ยนรหัสผ่าน</h1>
      <div class="profile-panel profile-panel--password">

      <form action="{{ route('profile.password.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="field">
          <label for="current_password">รหัสผ่านปัจจุบัน</label>
          <input type="password" id="current_password" name="current_password"
                 placeholder="กรอกรหัสผ่านปัจจุบันของคุณ"
                 class="{{ $errors->has('current_password') ? 'is-error' : '' }}">
          @error('current_password')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
          <label for="new_password">รหัสผ่านใหม่</label>
          <input type="password" id="new_password" name="new_password"
                 placeholder="กรอกรหัสผ่านใหม่"
                 class="{{ $errors->has('new_password') ? 'is-error' : '' }}">
          @error('new_password')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
          <label for="new_password_confirmation">ยืนยันรหัสผ่านใหม่</label>
          <input type="password" id="new_password_confirmation" name="new_password_confirmation"
                 placeholder="กรอกรหัสผ่านใหม่อีกครั้ง">
        </div>

        <div class="actions">
          <button type="submit" class="btn btn-navy">
            บันทึกรหัสผ่านใหม่
          </button>
        </div>
      </form>
      </div>
    </section>

  </div>

@endsection
