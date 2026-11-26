<?php

/**
 * ConcertManagementController
 * จัดการรายการคอนเสิร์ตของ Organizer
 * Author : 67160279 กนกพล แซ่ตั๊น
 * Created : 26/11/2569
 * Updated : 26/11/2569 67160279 กนกพล แซ่ตั๊น
 */

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Concert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConcertManagementController extends Controller
{
    private const STATUS_PAUSED = 'PAUSED';

    private const PER_PAGE = 10;

    private const SUGGEST_LIMIT = 6;

    private const MAX_SEARCH_LENGTH = 120;

    private const MAX_SEARCH_TERMS = 5;

    private const BUDDHIST_ERA_OFFSET = 543;

    private const STATUS_FILTERS = [
        'SCHEDULED' => 'รอเปิดจำหน่าย',
        'ON_SALE' => 'กำลังเปิดจำหน่าย',
        'CLOSED' => 'ปิดการจำหน่าย',
        'DRAFT' => 'ยังไม่เผยแพร่',
        self::STATUS_PAUSED => 'ปิดการจำหน่ายชั่วคราว',
        'COMPLETED' => 'เสร็จสิ้น',
    ];

    /**
     * index(request)
     * แสดงรายการคอนเสิร์ตที่ผู้จัดมีสิทธิ์จัดการ
     *
     * @param  Request  $request  คำค้นหาและตัวกรองสถานะ
     * @return View หน้าแสดงรายการคอนเสิร์ต
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:'.self::MAX_SEARCH_LENGTH],
            'status' => ['nullable', Rule::in(array_merge(['ALL'], array_keys(self::STATUS_FILTERS)))],
        ]);
        $organizationId = Auth::user()->u_organization_id;

        abort_if(! $organizationId, 403, 'บัญชีผู้จัดยังไม่ได้ผูกกับองค์กร');

        $search = trim($filters['q'] ?? '');
        $status = $filters['status'] ?? 'ALL';

        $query = Concert::query()
            ->where('c_organization_id', $organizationId)
            ->withAvailability();

        if ($search !== '') {
            $query->where(function ($concerts) use ($search) {
                $concerts->where('name', 'like', '%'.$search.'%')
                    ->orWhere('artist', 'like', '%'.$search.'%');
            });
        }

        $this->applyStatusFilter($query, $status);

        $concerts = $query
            ->orderByDesc('sale_start_datetime')
            ->orderByDesc('sale_end_datetime')
            ->orderBy('event_datetime')
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Concert $concert) => $this->presentConcert($concert));

        return view('organizer.concerts.concert-management', [
            'concerts' => $concerts,
            'search' => $search,
            'selectedStatus' => $status,
            'statusFilters' => self::STATUS_FILTERS,
        ]);
    }

    /**
     * suggest(request)
     * ส่งรายการคอนเสิร์ตขององค์กรที่ตรงกับชื่อหรือศิลปิน
     *
     * @param  Request  $request  คำค้นหาสำหรับรายการแนะนำ
     * @return JsonResponse รายการคอนเสิร์ตที่ตรงกับคำค้น
     */
    public function suggest(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:'.self::MAX_SEARCH_LENGTH],
        ]);
        $query = trim(preg_replace('/\s+/u', ' ', $filters['q'] ?? ''));

        if ($query === '') {
            return response()->json(['query' => $query, 'results' => []]);
        }

        $terms = array_slice(array_values(array_unique(explode(' ', $query))), 0, self::MAX_SEARCH_TERMS);
        $prefix = addcslashes($query, '\\%_').'%';
        $concerts = $this->buildOwnedConcertQuery()
            ->withAvailability()
            ->where(function (Builder $matches) use ($terms) {
                foreach ($terms as $term) {
                    $like = '%'.addcslashes($term, '\\%_').'%';
                    $matches->where(function (Builder $fields) use ($like) {
                        $fields->where('name', 'like', $like)
                            ->orWhere('artist', 'like', $like);
                    });
                }
            })
            ->orderByRaw(
                'CASE WHEN concert.name LIKE ? THEN 0 WHEN concert.artist LIKE ? THEN 1 ELSE 2 END',
                [$prefix, $prefix]
            )
            ->orderByDesc('sale_start_datetime')
            ->orderBy('event_datetime')
            ->limit(self::SUGGEST_LIMIT)
            ->get()
            ->map(function (Concert $concert) {
                $item = $this->presentConcert($concert);

                return [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'artist' => $item['artist'],
                    'venue' => $item['venue'],
                    'date' => $concert->event_datetime
                        ? $this->formatDateTime($concert->event_datetime)
                        : null,
                    'status' => $item['status'],
                    'status_label' => $item['status_label'],
                    'poster' => $item['poster'],
                    'url' => $item['editable']
                        ? route('organizer.concerts.edit', $item['id'])
                        : url('/organizer/check-concert?concert_id='.$item['id']),
                ];
            });

        return response()->json([
            'query' => $query,
            'results' => $concerts->values(),
        ]);
    }

    /**
     * edit(concertId)
     * ส่งต่อไปยังหน้าแก้ไขคอนเสิร์ตเดิม
     *
     * @param  int  $concertId  รหัสคอนเสิร์ต
     * @return RedirectResponse เปลี่ยนเส้นทางไปหน้าจัดการรูปคอนเสิร์ต
     */
    public function edit(int $concertId): RedirectResponse
    {
        $concert = $this->buildOwnedConcertQuery()->findOrFail($concertId);

        return redirect()->route('manage.images', ['concert' => $concert->c_id]);
    }

    /**
     * destroy(request, concertId)
     * ลบคอนเสิร์ตแบบ soft delete เมื่ออยู่ในสถานะที่อนุญาต
     *
     * @param  Request  $request  คำค้นหาและตัวกรองเพื่อคงค่าหลังลบ
     * @param  int  $concertId  รหัสคอนเสิร์ต
     * @return RedirectResponse เปลี่ยนเส้นทางกลับหน้าจัดการคอนเสิร์ต
     */
    public function destroy(Request $request, int $concertId): RedirectResponse
    {
        $concert = $this->buildOwnedConcertQuery()->withAvailability()->findOrFail($concertId);
        abort_unless(
            in_array($concert->displayStatus(), [Concert::STATUS_SCHEDULED, Concert::STATUS_DRAFT], true),
            403,
            'ไม่สามารถลบคอนเสิร์ตที่เริ่มจำหน่ายหรือสิ้นสุดแล้วได้'
        );

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:'.self::MAX_SEARCH_LENGTH],
            'status' => ['nullable', Rule::in(array_merge(['ALL'], array_keys(self::STATUS_FILTERS)))],
        ]);

        $concert->delete();

        return redirect()
            ->route('organizer.concerts.index', array_filter($filters, fn ($value) => $value !== null))
            ->with('status', 'ลบคอนเสิร์ตเรียบร้อยแล้ว');
    }

    /**
     * buildOwnedConcertQuery()
     * สร้าง query คอนเสิร์ตที่เป็นขององค์กรผู้ใช้ปัจจุบัน
     *
     * @return Builder Query ของคอนเสิร์ตในองค์กรผู้ใช้
     */
    private function buildOwnedConcertQuery(): Builder
    {
        $organizationId = Auth::user()->u_organization_id;

        abort_if(! $organizationId, 403, 'บัญชีผู้จัดยังไม่ได้ผูกกับองค์กร');

        return Concert::query()->where('c_organization_id', $organizationId);
    }

    /**
     * applyStatusFilter(query, status)
     * กรองรายการคอนเสิร์ตตามสถานะที่เลือก
     *
     * @param  Builder  $query  Query รายการคอนเสิร์ต
     * @param  string  $status  สถานะที่เลือก
     */
    private function applyStatusFilter(Builder $query, string $status): void
    {
        match ($status) {
            'SCHEDULED' => $query->whereDisplayStatus(Concert::STATUS_SCHEDULED),
            'ON_SALE' => $query->whereDisplayStatus(Concert::STATUS_ON_SALE),
            'CLOSED' => $query->where(function ($closed) {
                $closed->whereDisplayStatus(Concert::STATUS_SOLD_OUT)
                    ->orWhere(function ($saleClosed) {
                        $saleClosed->where('status', Concert::STATUS_ON_SALE)
                            ->where(fn ($event) => $event->whereNull('event_datetime')->orWhere('event_datetime', '>', now()))
                            ->whereNotNull('sale_end_datetime')
                            ->where('sale_end_datetime', '<', now());
                    });
            }),
            'DRAFT' => $query->where('status', Concert::STATUS_DRAFT),
            self::STATUS_PAUSED => $query->where('status', self::STATUS_PAUSED)
                ->where(fn ($event) => $event->whereNull('event_datetime')->orWhere('event_datetime', '>', now())),
            'COMPLETED' => $query->whereNotIn('status', [Concert::STATUS_DRAFT, Concert::STATUS_CANCELLED])
                ->whereNotNull('event_datetime')
                ->where('event_datetime', '<=', now()),
            default => null,
        };
    }

    /**
     * presentConcert(concert)
     * จัดรูปแบบข้อมูลคอนเสิร์ตก่อนส่งให้ view
     *
     * @param  Concert  $concert  คอนเสิร์ตที่ต้องการจัดรูปแบบ
     * @return array ข้อมูลคอนเสิร์ตสำหรับหน้า Organizer
     */
    private function presentConcert(Concert $concert): array
    {
        $displayStatus = $concert->status === self::STATUS_PAUSED && ! $concert->hasEnded()
            ? self::STATUS_PAUSED
            : $concert->displayStatus();
        $statusLabels = self::STATUS_FILTERS + [
            Concert::STATUS_SOLD_OUT => 'ปิดการจำหน่าย',
            Concert::STATUS_CANCELLED => 'ยกเลิกการแสดง',
            Concert::DISPLAY_SALE_CLOSED => 'ปิดการจำหน่าย',
        ];

        return [
            'id' => $concert->c_id,
            'name' => $concert->name,
            'artist' => $concert->artist,
            'poster' => $concert->poster_path ? asset('storage/'.$concert->poster_path) : null,
            'venue' => $concert->venue,
            'description' => $concert->description,
            'sale_start' => $this->formatDateTime($concert->sale_start_datetime),
            'sale_end' => $this->formatDateTime($concert->sale_end_datetime),
            'event_date' => $this->formatDate($concert->event_datetime),
            'status' => $displayStatus,
            'status_label' => $statusLabels[$displayStatus] ?? 'ไม่ทราบสถานะ',
            'editable' => in_array($displayStatus, [Concert::STATUS_SCHEDULED, Concert::STATUS_DRAFT], true),
        ];
    }

    /**
     * formatDateTime(value)
     * แปลงวันและเวลาเป็นรูปแบบภาษาไทย
     *
     * @param  mixed  $value  ค่าวันและเวลาต้นทาง
     * @return string วันและเวลาในรูปแบบภาษาไทย
     */
    private function formatDateTime(mixed $value): string
    {
        if (! $value) {
            return '–';
        }

        $date = Carbon::parse($value)->timezone(config('app.timezone'));

        return $this->formatThaiDate($date).' ('.$date->format('H:i').')';
    }

    /**
     * formatDate(value)
     * แปลงวันที่เป็นรูปแบบภาษาไทย
     *
     * @param  mixed  $value  ค่าวันที่ต้นทาง
     * @return string วันที่ในรูปแบบภาษาไทย
     */
    private function formatDate(mixed $value): string
    {
        return $value
            ? $this->formatThaiDate(Carbon::parse($value)->timezone(config('app.timezone')))
            : '–';
    }

    /**
     * formatThaiDate(date)
     * แสดงวันที่ด้วยชื่อเดือนย่อภาษาไทยและปีพุทธศักราช
     *
     * @param  Carbon  $date  วันที่ที่แปลง timezone แล้ว
     * @return string วันที่ภาษาไทยแบบย่อ
     */
    private function formatThaiDate(Carbon $date): string
    {
        $months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
            'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $day = str_pad((string) $date->day, 2, '0', STR_PAD_LEFT);

        return $day.' '.$months[$date->month - 1].' '.($date->year + self::BUDDHIST_ERA_OFFSET);
    }
}
