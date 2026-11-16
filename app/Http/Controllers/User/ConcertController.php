<?php

/**
 * ConcertController
 * จัดการข้อมูลคอนเสิร์ตฝั่งลูกค้า: รายการ ค้นหา (ชื่อ ศิลปิน สถานที่ ผู้จัด วันที่) รายการแนะนำขณะพิมพ์ และรายละเอียดคอนเสิร์ต
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 16/10/2569
 * Updated : 16/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Concert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ConcertController extends Controller
{
    /* จำนวนคอนเสิร์ตต่อหน้าในหน้าคอนเสิร์ตทั้งหมด */
    private const PER_PAGE = 12;

    /* จำนวนรายการแนะนำในช่องค้นหา */
    private const SUGGEST_LIMIT = 6;

    /* ความยาวคำค้นสูงสุด (ตัวอักษร) */
    private const MAX_KEYWORD_LENGTH = 100;

    /* จำนวนคำค้นสูงสุดที่นำไปค้นหา */
    private const MAX_SEARCH_TERMS = 5;

    /* ชื่อเดือนที่ใช้ค้นหาด้วยวันที่ (ไทยเต็ม/ย่อ และอังกฤษ) => เลขเดือน */
    private const MONTH_NAMES = [
        'มกราคม' => 1, 'กุมภาพันธ์' => 2, 'มีนาคม' => 3, 'เมษายน' => 4, 'พฤษภาคม' => 5, 'มิถุนายน' => 6,
        'กรกฎาคม' => 7, 'สิงหาคม' => 8, 'กันยายน' => 9, 'ตุลาคม' => 10, 'พฤศจิกายน' => 11, 'ธันวาคม' => 12,
        'ม.ค.' => 1, 'ก.พ.' => 2, 'มี.ค.' => 3, 'เม.ย.' => 4, 'พ.ค.' => 5, 'มิ.ย.' => 6,
        'ก.ค.' => 7, 'ส.ค.' => 8, 'ก.ย.' => 9, 'ต.ค.' => 10, 'พ.ย.' => 11, 'ธ.ค.' => 12,
        'ม.ค' => 1, 'ก.พ' => 2, 'มี.ค' => 3, 'เม.ย' => 4, 'พ.ค' => 5, 'มิ.ย' => 6,
        'ก.ค' => 7, 'ส.ค' => 8, 'ก.ย' => 9, 'ต.ค' => 10, 'พ.ย' => 11, 'ธ.ค' => 12,
        'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4, 'may' => 5, 'june' => 6,
        'july' => 7, 'august' => 8, 'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'jun' => 6, 'jul' => 7,
        'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
    ];

    /* ตัวกรองสถานะในหน้าคอนเสิร์ตทั้งหมด */
    private const STATUS_FILTERS = [
        Concert::STATUS_ON_SALE   => 'เปิดขาย',
        Concert::STATUS_SCHEDULED => 'เร็ว ๆ นี้',
        Concert::STATUS_SOLD_OUT  => 'ขายหมด',
    ];

    /**
     * getConcertList()
     * ดึงรายการคอนเสิร์ตที่ลูกค้าเห็นได้ (ไม่ถูกลบ ไม่ใช่ DRAFT) เรียงตามวันเปิดขาย พร้อมจำนวนบัตรคงเหลือสำหรับป้ายสถานะ
     * param : -
     * return : Collection ของ Concert
     */
    public function getConcertList()
    {
        return $this->publicConcerts()
            ->orderBy('sale_start_datetime', 'asc')
            ->get();
    }

    /**
     * index(request)
     * แสดงหน้าคอนเสิร์ตทั้งหมด (ปุ่ม Show All) พร้อมค้นหา กรองสถานะ และแบ่งหน้า (เรียงตามวันแสดงที่ใกล้ที่สุด)
     * param : request
     * return : View concerts.index
     */
    public function index(Request $request)
    {
        $search = mb_substr($this->normalizeKeyword((string) $request->query('search', '')), 0, self::MAX_KEYWORD_LENGTH);

        $status = strtoupper((string) $request->query('status', ''));
        $status = array_key_exists($status, self::STATUS_FILTERS) ? $status : '';

        // ค้นหา: ทุกคำต้องเจอ ถ้าไม่เจอเลยค่อยหาแบบเจอคำใดคำหนึ่ง (เหมือน searchConcert)
        $base = $this->publicConcerts();
        if ($search !== '') {
            $terms = $this->searchTerms($search);
            $matchAll = count($terms) === 1
                || $this->applyKeyword($this->publicConcerts(), $terms, true)->exists();
            $base = $this->applyKeyword($base, $terms, $matchAll);
        }

        $query = $base->withMin('zones', 'price');   // zones_min_price = ราคาเริ่มต้น
        if ($status !== '') {
            // กรองตามสถานะที่แสดงผล (เช่น ขายหมด = ตั้งสถานะ SOLD_OUT หรือที่นั่งถูกจองหมดแล้ว)
            $query->whereDisplayStatus($status);
        }

        // คอนเสิร์ตที่ยังไม่จัดขึ้นก่อน แล้วเรียงตามวันแสดงที่ใกล้ที่สุด
        $query->orderByRaw('event_datetime < NOW()')->orderBy('event_datetime');

        $concerts = $query->paginate(self::PER_PAGE)->withQueryString();

        return view('user.concerts.index', [
            'concerts'      => $concerts,
            'search'        => $search,
            'status'        => $status,
            'statusFilters' => self::STATUS_FILTERS,
        ]);
    }

    /**
     * searchConcert(keyword, limit)
     * ค้นหาคอนเสิร์ตจากชื่อ ศิลปิน สถานที่ หรือผู้จัด (ทุกคำต้องเจอ ถ้าไม่เจอเลยจะหาแบบเจอคำใดคำหนึ่ง)
     * param : keyword, limit
     * return : Collection ของ Concert เรียงจากใกล้เคียงที่สุด
     */
    public function searchConcert(string $keyword, ?int $limit = null): Collection
    {
        $keyword = $this->normalizeKeyword($keyword);
        if ($keyword === '') {
            return collect();
        }

        $terms = $this->searchTerms($keyword);

        $results = $this->searchQuery($keyword, $terms, true)
            ->when($limit, fn (Builder $q) => $q->limit($limit))
            ->get();

        if ($results->isEmpty() && count($terms) > 1) {
            $results = $this->searchQuery($keyword, $terms, false)
                ->when($limit, fn (Builder $q) => $q->limit($limit))
                ->get();
        }

        return $results;
    }

    /**
     * suggest(request)
     * ส่งรายการคอนเสิร์ตที่ใกล้เคียงกับคำที่กำลังพิมพ์ (ใช้กับช่องค้นหา)
     * param : request
     * return : JsonResponse { query, results }
     */
    public function suggest(Request $request): JsonResponse
    {
        $query = mb_substr(trim((string) $request->query('q', '')), 0, self::MAX_KEYWORD_LENGTH);

        $results = $query === '' ? collect() : $this->searchConcert($query, self::SUGGEST_LIMIT);

        return response()->json([
            'query'   => $query,
            'results' => $results->map(fn (Concert $concert) => [
                'id'           => $concert->c_id,
                'name'         => $concert->name,
                'artist'       => $concert->artist,
                'venue'        => $concert->venue,
                'organization' => $concert->organization?->name,
                'date'         => $concert->event_datetime
                    ? Carbon::parse($concert->event_datetime)->thDateLong() . ' ' . Carbon::parse($concert->event_datetime)->format('H:i') . ' น.'
                    : null,
                'status'       => $concert->displayStatus(),
                'poster'       => $concert->poster_path ? asset('storage/' . $concert->poster_path) : null,
                'url'          => url('/concert-detail?concert_id=' . $concert->c_id),
            ])->values(),
        ]);
    }

    /**
     * getConcertDetail(concertId)
     * ดึงรายละเอียดคอนเสิร์ต พร้อมผู้จัดและโซน (คอนเสิร์ตฉบับร่าง DRAFT ลูกค้าเปิดไม่ได้ ตอบ 404)
     * param : concertId
     * return : Concert (ถ้าไม่พบจะตอบ 404)
     */
    public function getConcertDetail(int $concertId)
    {
        return Concert::with(['organization', 'zones'])
            ->whereKey($concertId)
            ->where('status', '!=', Concert::STATUS_DRAFT)
            ->firstOrFail();
    }

    /* Start Helpers (ค้นหา) */

    /**
     * publicConcerts()
     * สร้าง query ของคอนเสิร์ตที่ลูกค้าเห็นได้ (ไม่ถูกลบ และไม่ใช่ DRAFT) พร้อมจำนวนบัตรคงเหลือ (available_tickets)
     * param : -
     * return : Builder
     */
    private function publicConcerts(): Builder
    {
        return Concert::with('organization')
            ->withAvailability()
            ->where('concert.status', '!=', Concert::STATUS_DRAFT);
    }

    /**
     * normalizeKeyword(keyword)
     * ตัดช่องว่างหัวท้าย และรวมช่องว่างที่ติดกันให้เหลือช่องเดียว
     * param : keyword
     * return : string คำค้นที่จัดรูปแล้ว
     */
    private function normalizeKeyword(string $keyword): string
    {
        return trim(preg_replace('/\s+/u', ' ', $keyword));
    }

    /**
     * searchTerms(keyword)
     * แยกคำค้นด้วยช่องว่าง ตัดคำซ้ำ และจำกัดไม่เกิน MAX_SEARCH_TERMS คำ
     * param : keyword
     * return : array ของคำค้น
     */
    private function searchTerms(string $keyword): array
    {
        return array_slice(array_values(array_unique(explode(' ', $keyword))), 0, self::MAX_SEARCH_TERMS);
    }

    /**
     * likeValue(text)
     * สร้างค่าสำหรับเงื่อนไข LIKE แบบ %คำค้น% โดย escape ตัว % และ _ ที่ผู้ใช้พิมพ์
     * param : text
     * return : string
     */
    private function likeValue(string $text): string
    {
        return '%' . addcslashes($text, '\\%_') . '%';
    }

    /**
     * applyKeyword(query, terms, matchAll)
     * เพิ่มเงื่อนไขค้นหาลงใน query (matchAll = true ทุกคำต้องเจอ, false เจอคำใดคำหนึ่ง)
     * param : query, terms, matchAll
     * return : Builder
     */
    private function applyKeyword(Builder $query, array $terms, bool $matchAll): Builder
    {
        // ถ้าคำค้นเป็นวันที่ (เช่น 16/11/2569, 16 พ.ย. 2569, พฤศจิกายน) ให้ค้นจากวันแสดง (event_datetime) ด้วย
        $date = $this->parseDateKeyword(implode(' ', $terms));

        return $query->where(function (Builder $outer) use ($terms, $matchAll, $date) {
            $outer->where(function (Builder $textMatch) use ($terms, $matchAll) {
                foreach ($terms as $term) {
                    $like = $this->likeValue($term);
                    $matchTerm = function (Builder $q) use ($like) {
                        $q->where('name', 'like', $like)
                          ->orWhere('artist', 'like', $like)
                          ->orWhere('venue', 'like', $like)
                          ->orWhereHas('organization', fn (Builder $org) => $org->where('name', 'like', $like));
                    };
                    $matchAll ? $textMatch->where($matchTerm) : $textMatch->orWhere($matchTerm);
                }
            });

            if ($date !== null) {
                $outer->orWhere(function (Builder $q) use ($date) {
                    $q->whereNotNull('event_datetime');
                    if ($date['year'] !== null)  { $q->whereYear('event_datetime', $date['year']); }
                    if ($date['month'] !== null) { $q->whereMonth('event_datetime', $date['month']); }
                    if ($date['day'] !== null)   { $q->whereDay('event_datetime', $date['day']); }
                });
            }
        });
    }

    /**
     * parseDateKeyword(keyword)
     * แปลงคำค้นที่เป็นวันที่ให้เป็นส่วนของวันที่ (รองรับปี พ.ศ./ค.ศ.)
     * รูปแบบ: 16/11/2569, 16-11-2025, 2025-11-16, 11/2569, 16/11, 16 พฤศจิกายน 2569, 16 พ.ย., พฤศจิกายน 2569, พฤศจิกายน
     * param : keyword
     * return : array ['day' => ?int, 'month' => ?int, 'year' => ?int] หรือ null ถ้าไม่ใช่วันที่
     */
    private function parseDateKeyword(string $keyword): ?array
    {
        $text = mb_strtolower(trim($keyword));
        $day = $month = $year = null;

        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $text, $m)) {
            [$day, $month, $year] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $text, $m)) {
            [$year, $month, $day] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{1,2})[\/\-.](\d{4})$/', $text, $m)) {
            [$month, $year] = [(int) $m[1], (int) $m[2]];
        } elseif (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})$/', $text, $m)) {
            [$day, $month] = [(int) $m[1], (int) $m[2]];
        } else {
            $names = array_keys(self::MONTH_NAMES);
            usort($names, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
            $pattern = implode('|', array_map(fn ($n) => preg_quote($n, '/'), $names));

            if (!preg_match('/^(?:(\d{1,2})\s*)?(' . $pattern . ')(?:\s*(\d{4}))?$/u', $text, $m)) {
                return null;
            }
            $day   = $m[1] !== '' ? (int) $m[1] : null;
            $month = self::MONTH_NAMES[$m[2]];
            $year  = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : null;
        }

        if ($year !== null && $year > 2400) {
            $year -= 543;   // พ.ศ. → ค.ศ.
        }
        if ($month !== null && ($month < 1 || $month > 12)) {
            return null;
        }
        if ($day !== null && ($day < 1 || $day > 31)) {
            return null;
        }
        if ($day !== null && $month !== null && $year !== null && !checkdate($month, $day, $year)) {
            return null;
        }
        if ($year !== null && ($year < 1900 || $year > 2200)) {
            return null;
        }

        return ['day' => $day, 'month' => $month, 'year' => $year];
    }

    /**
     * searchQuery(keyword, terms, matchAll)
     * สร้าง query ค้นหาที่เรียงตามความใกล้เคียง และคอนเสิร์ตที่ยังไม่จัดขึ้นก่อน
     * param : keyword, terms, matchAll
     * return : Builder
     */
    private function searchQuery(string $keyword, array $terms, bool $matchAll): Builder
    {
        $contains = $this->likeValue($keyword);

        return $this->applyKeyword($this->publicConcerts(), $terms, $matchAll)
            ->orderByRaw(
                'CASE WHEN name LIKE ? THEN 0 WHEN name LIKE ? THEN 1 WHEN artist LIKE ? THEN 2 ELSE 3 END',
                [addcslashes($keyword, '\\%_') . '%', $contains, $contains]
            )
            ->orderByRaw('event_datetime < NOW()')   // คอนเสิร์ตที่ยังไม่จัดขึ้นก่อน
            ->orderBy('event_datetime');
    }

    /* End Helpers (ค้นหา) */
}
