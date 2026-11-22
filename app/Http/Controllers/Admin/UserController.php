<?php

/**
 * UserController (Admin)
 * จัดการผู้ใช้ของ Admin: รายชื่อผู้ใช้ทุก role เพิ่มผู้ใช้ ระงับ/ปลดระงับบัญชี (หน้าแรกหลัง login)
 * ตอนนี้ยังเป็นหน้าเปล่า รอทำตาม Figma (Admin → Manage users)
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 27/10/2569
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * index()
     * แสดงหน้าจัดการผู้ใช้
     * param : -
     * return : View admin.users.index
     */
    public function index()
    {
        return view('admin.users.index');
    }
}
