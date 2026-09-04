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
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        // ค้นหาจากชื่อคอนเสิร์ต ศิลปิน หรือองค์กรเจ้าของคอนเสิร์ต
        $concertQuery = Concert::query()
            ->with('company')
            ->orderBy('sale_start_datetime', 'asc');

        if ($search !== '') {
            $concertQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('artist', 'like', "%{$search}%")
                    ->orWhereHas('company', function ($companyQuery) use ($search) {
                        $companyQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $concerts = $concertQuery->limit(12)->get();

        // ดึงคอนเสิร์ต highlight สำหรับ hero banner (ล่าสุด 3 รายการ)
        $highlights = Concert::orderBy('name', 'desc')->limit(5)->get();

        return view('home', compact('concerts', 'highlights', 'search'));
    }
}
