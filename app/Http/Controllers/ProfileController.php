<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * ProfileController
 * Maps to UserController spec: getProfile(), updateProfile(), changePassword()
 * Author : AI Agent
 * Updated : 27/09/2026 – delegate to UserController, fixed to use app_user directly
 */
class ProfileController extends Controller
{
    protected UserController $userController;

    public function __construct(UserController $userController)
    {
        $this->userController = $userController;
    }

    /**
     * edit() – displayProfile(): void
     * ขอและแสดงข้อมูลบัญชี APP_USER
     */
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * update() – submitProfileUpdate(data): void
     * บันทึกข้อมูลโปรไฟล์ที่แก้ไข
     */
    public function update(Request $request)
    {
        $this->userController->updateProfile($request, Auth::id());
        return redirect()->route('profile.edit')->with('status', 'บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว ✅');
    }

    /**
     * updatePassword() – submitPasswordChange(password): void
     * เปลี่ยนรหัสผ่าน
     */
    public function updatePassword(Request $request)
    {
        $this->userController->changePassword($request, Auth::id());
        return redirect()->route('profile.edit')->with('status', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว ✅');
    }
}
