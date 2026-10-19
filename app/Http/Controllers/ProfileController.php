<?php

/**
 * ProfileController
 * หน้าแก้ไขข้อมูลส่วนตัวและเปลี่ยนรหัสผ่าน (ส่งต่องานบันทึกให้ UserController)
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 04/09/2569
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    protected UserController $userController;

    /**
     * __construct(userController)
     * รับ UserController ผ่าน dependency injection
     * param : userController
     * return : -
     */
    public function __construct(UserController $userController)
    {
        $this->userController = $userController;
    }

    /**
     * edit()
     * แสดงหน้าแก้ไขข้อมูลส่วนตัวของผู้ใช้ที่เข้าสู่ระบบ
     * param : -
     * return : View account.edit-profile
     */
    public function edit()
    {
        $user = Auth::user();
        return view('account.edit-profile', compact('user'));
    }

    /**
     * update(request)
     * บันทึกข้อมูลส่วนตัวที่แก้ไข
     * param : request
     * return : RedirectResponse กลับหน้าแก้ไขข้อมูลพร้อมข้อความสำเร็จ
     */
    public function update(Request $request)
    {
        $this->userController->updateProfile($request, Auth::id());
        return redirect()->route('profile.edit')->with('status', 'บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว');
    }

    /**
     * updatePassword(request)
     * เปลี่ยนรหัสผ่านของผู้ใช้ที่เข้าสู่ระบบ
     * param : request
     * return : RedirectResponse กลับหน้าแก้ไขข้อมูลพร้อมข้อความสำเร็จ
     */
    public function updatePassword(Request $request)
    {
        $this->userController->changePassword($request, Auth::id());
        return redirect()->route('profile.edit')->with('status', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว ✅');
    }
}
