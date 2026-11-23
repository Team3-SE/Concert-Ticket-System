{{--
 * edit-profile.blade.php
 * หน้าแก้ไขข้อมูลส่วนตัวและเปลี่ยนรหัสผ่าน
 * @Author
 * @Create Date
 * @Update Date 24/10/2569 67160292 ธีรเมธ พลสาย
--}}
@extends('layouts.user-account')

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
                   value="{{ old('first_name', $user->first_name ?? '') }}" maxlength="30"
                   class="{{ $errors->has('first_name') ? 'is-error' : '' }}">
            @error('first_name')<p class="field-error">{{ $message }}</p>@enderror
          </div>
          <div class="field">
            <label for="last_name">นามสกุล</label>
            <input type="text" id="last_name" name="last_name"
                   value="{{ old('last_name', $user->last_name ?? '') }}" maxlength="30"
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
                   placeholder="0812345678" maxlength="10" inputmode="numeric" pattern="0[0-9]{9}"
                   oninput="this.value = this.value.replace(/\D/g, '').slice(0, 10)">
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
          <div class="pw-wrap">
            <input type="password" id="current_password" name="current_password"
                   placeholder="กรอกรหัสผ่านปัจจุบันของคุณ"
                   class="{{ $errors->has('current_password') ? 'is-error' : '' }}">
            <button type="button" class="pw-toggle" data-toggle-password aria-label="แสดง/ซ่อนรหัสผ่าน"><svg class="eye-open" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-6.5 10-6.5c1.6 0 3 .4 4.2 1M22 12s-3.5 6.5-10 6.5c-1.6 0-3-.4-4.2-1M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18"/></svg></button>
          </div>
          @error('current_password')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
          <label for="new_password">รหัสผ่านใหม่</label>
          <div class="pw-wrap">
            <input type="password" id="new_password" name="new_password"
                   placeholder="กรอกรหัสผ่านใหม่" maxlength="32"
                   class="{{ $errors->has('new_password') ? 'is-error' : '' }}">
            <button type="button" class="pw-toggle" data-toggle-password aria-label="แสดง/ซ่อนรหัสผ่าน"><svg class="eye-open" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-6.5 10-6.5c1.6 0 3 .4 4.2 1M22 12s-3.5 6.5-10 6.5c-1.6 0-3-.4-4.2-1M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18"/></svg></button>
          </div>
          @error('new_password')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
          <label for="new_password_confirmation">ยืนยันรหัสผ่านใหม่</label>
          <div class="pw-wrap">
            <input type="password" id="new_password_confirmation" name="new_password_confirmation"
                   placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" maxlength="32">
            <button type="button" class="pw-toggle" data-toggle-password aria-label="แสดง/ซ่อนรหัสผ่าน"><svg class="eye-open" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-6.5 10-6.5c1.6 0 3 .4 4.2 1M22 12s-3.5 6.5-10 6.5c-1.6 0-3-.4-4.2-1M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18"/></svg></button>
          </div>
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
