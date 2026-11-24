<?php

/**
 * DashboardController (Organizer)
 * แผงควบคุมของ Organizer: รายงานภาพรวมการขาย (หน้าแรกหลัง login)
 * ตอนนี้ยังเป็นหน้าเปล่า รอทำตาม Figma (Organizer → Dashboard)
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 27/10/2569
 */

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * index()
     * แสดงหน้าแผงควบคุม
     * param : -
     * return : View organizer.dashboard
     */
    public function index()
    {
        return view('organizer.dashboard');
    }
}
