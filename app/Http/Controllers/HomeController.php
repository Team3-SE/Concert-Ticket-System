<?php

namespace App\Http\Controllers;

use App\Models\Concert;
use Illuminate\Http\Request;

/**
 * HomeController
 * handle rendering of the public home page
 * Author : AI Agent
 * Created : 31/08/2026
 */
class HomeController extends Controller
{
    /**
     * index()
     * display the home page with concerts from the database.
     * param : none
     * return : \Illuminate\View\View
     */
    public function index()
    {
        // ดึงคอนเสิร์ตที่จะเกิดขึ้น เรียงตามวันที่ใกล้สุดก่อน (สำหรับ Recommend Concerts)
        $concerts = Concert::orderBy('sale_start_datetime', 'asc')->limit(12)->get();

        // ดึงคอนเสิร์ต highlight สำหรับ hero banner (ล่าสุด 3 รายการ)
        $highlights = Concert::orderBy('name', 'desc')->limit(5)->get();

        return view('home', compact('concerts', 'highlights'));
    }
}
