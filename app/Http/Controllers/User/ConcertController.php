<?php

/**
 * ConcertController
 * จัดการข้อมูลคอนเสิร์ตฝั่งลูกค้า: รายการ ค้นหา รายการแนะนำขณะพิมพ์ และรายละเอียดคอนเสิร์ต
 * Author :
 * Created :
 * Updated : 11/11/2569 67160292 ธีรเมธ พลสาย
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
        return $query->where(function (Builder $outer) use ($terms, $matchAll) {
            foreach ($terms as $term) {
                $like = $this->likeValue($term);
                $matchTerm = function (Builder $q) use ($like) {
                    $q->where('name', 'like', $like)
                      ->orWhere('artist', 'like', $like)
                      ->orWhere('venue', 'like', $like)
                      ->orWhereHas('organization', fn (Builder $org) => $org->where('name', 'like', $like));
                };
                $matchAll ? $outer->where($matchTerm) : $outer->orWhere($matchTerm);
            }
        });
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
