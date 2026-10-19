<?php

/**
 * HomeController
 * แสดงหน้า Home: คอนเสิร์ตแนะนำ ผลการค้นหา และคอนเสิร์ตไฮไลต์
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 16/10/2569
 */

namespace App\Http\Controllers;

use App\Models\Concert;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /* จำนวนคอนเสิร์ตที่แสดงในหน้า Home */
    private const CONCERT_LIMIT = 12;

    /* จำนวนคอนเสิร์ตไฮไลต์ในแบนเนอร์ */
    private const HIGHLIGHT_LIMIT = 5;

    protected ConcertController $concertController;

    /**
     * __construct(concertController)
     * รับ ConcertController ผ่าน dependency injection
     * param : concertController
     * return : -
     */
    public function __construct(ConcertController $concertController)
    {
        $this->concertController = $concertController;
    }

    /**
     * index(request)
     * แสดงหน้า Home พร้อมรายการคอนเสิร์ต (หรือผลการค้นหา) และคอนเสิร์ตไฮไลต์
     * param : request
     * return : View concerts.home
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $concerts = $this->concertController->searchConcert($search)->take(self::CONCERT_LIMIT);
        } else {
            $concerts = $this->concertController->getConcertList()->take(self::CONCERT_LIMIT);
        }

        // เลือกคอนเสิร์ตที่มีแบนเนอร์ก่อน แล้วเรียงตามวันแสดง
        $highlights = Concert::where('is_deleted', 0)
            ->orderByRaw('banner_path IS NULL')
            ->orderBy('event_datetime', 'asc')
            ->limit(self::HIGHLIGHT_LIMIT)
            ->get();

        return view('concerts.home', compact('concerts', 'highlights', 'search'));
    }
}
