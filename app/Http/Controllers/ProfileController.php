<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        $customer = Auth::user()->customer;

        return view('profile.edit', compact('customer'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'regex:/^[\p{Thai}a-zA-Z]{2,50}$/u'],
            'last_name'  => ['required', 'regex:/^[\p{Thai}a-zA-Z]{2,50}$/u'],
            'phone'      => ['required', 'regex:/^0[0-9]{9}$/'],
        ], [
            'first_name.required' => 'กรุณากรอกชื่อ',
            'first_name.regex'    => 'ชื่อต้องมี 2–50 ตัวอักษร และใช้ได้เฉพาะภาษาไทยหรืออังกฤษเท่านั้น',
            'last_name.required'  => 'กรุณากรอกนามสกุล',
            'last_name.regex'     => 'นามสกุลต้องมี 2–50 ตัวอักษร และใช้ได้เฉพาะภาษาไทยหรืออังกฤษเท่านั้น',
            'phone.required'      => 'กรุณากรอกเบอร์โทรศัพท์',
            'phone.regex'         => 'เบอร์โทรต้องเป็นตัวเลข 10 หลัก และขึ้นต้นด้วย 0 (ตัวอย่าง: 0812345678)',
        ]);

        Auth::user()->customer->update([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'phone'      => $request->phone,
        ]);

        return redirect()->route('profile.edit')->with('status', 'บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว ✅');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'new_password'     => ['required', 'confirmed', 'regex:/^(?=.*[A-Za-z])(?=.*\d)\S{6,}$/'],
        ], [
            'current_password.required' => 'กรุณากรอกรหัสผ่านปัจจุบัน',
            'new_password.required'     => 'กรุณากรอกรหัสผ่านใหม่',
            'new_password.regex'        => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร มีตัวอักษรภาษาอังกฤษและตัวเลขอย่างน้อยอย่างละ 1 ตัว และไม่มีช่องว่าง',
            'new_password.confirmed'    => 'รหัสผ่านยืนยันไม่ตรงกัน',
        ]);

        $appUser = Auth::user();

        if (! Hash::check($request->current_password, $appUser->password_hash)) {
            return back()->withErrors(['current_password' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง'])->withInput();
        }

        $appUser->update([
            'password_hash' => Hash::make($request->new_password),
        ]);

        return redirect()->route('profile.edit')->with('status', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว ✅');
    }
}
