<?php

namespace App\Http\Controllers;

use App\Models\Concert;
use Illuminate\Http\Request;

/**
 * HomeController
 * ใช้ ConcertController เป็น dependency แทนการ query ตรง
 * Author : AI Agent
 * Updated : 27/09/2026 – fixed to use organization relationship instead of company
 */
class HomeController extends Controller
{
    protected ConcertController $concertController;

    public function __construct(ConcertController $concertController)
    {
        $this->concertController = $concertController;
    }

    /**
     * index()
     * แสดงหน้า Home พร้อมรายการ Concert (ใช้ ConcertController.getConcertList / searchConcert)
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $concerts = $this->concertController->searchConcert($search)->take(12);
        } else {
            $concerts = $this->concertController->getConcertList()->take(12);
        }

        $highlights = Concert::where('is_deleted', 0)
            ->orderBy('event_datetime', 'asc')
            ->limit(5)
            ->get();

        return view('home', compact('concerts', 'highlights', 'search'));
    }
}
