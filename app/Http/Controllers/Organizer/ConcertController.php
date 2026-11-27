<?php

/**
 * app/Http/Controllers/Organizer/ConcertController.php
 * จัดการข้อมูลคอนเสิร์ตฝั่ง Organizer รวมถึงหน้าจอตรวจสอบรายละเอียดคอนเสิร์ต ผังที่นั่ง และรายงานยอดขาย
 * Author : 67160434 จิตฏิยากร โสภา
 * Created : 25/11/2569
 * Updated : 27/11/2569 67160292 ธีรเมธ พลสาย (แก้ให้ตรง schema 3.4.2)
 */

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Concert;
use App\Models\Seat;
use App\Models\Zone;
use App\Models\ZoneInventory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConcertController extends Controller
{
    /**
     * index()
     * เส้นทางสำรองสำหรับเมนูจัดการคอนเสิร์ต พาไปยังหน้าตรวจสอบคอนเสิร์ตแรกขององค์กรผู้จัด
     *
     * @return RedirectResponse เปลี่ยนเส้นทางไปยังหน้าตรวจสอบคอนเสิร์ตหรือแดชบอร์ด
     */
    public function index(): RedirectResponse
    {
        $firstConcert = $this->buildOwnedConcertQuery()->orderBy('c_id')->first();

        if ($firstConcert) {
            return redirect()->route('organizer.concerts.check', ['concertId' => $firstConcert->c_id]);
        }

        return redirect()->route('organizer.dashboard');
    }

    /**
     * checkConcert(concertId)
     * แสดงหน้าจอตรวจสอบรายละเอียดคอนเสิร์ต ผังที่นั่งสถานะปัจจุบัน และรายงานจำนวนตั๋วแยกตามโซน
     *
     * @param  int  $concertId  รหัสประจำคอนเสิร์ต (c_id)
     * @return View หน้าจอตรวจสอบคอนเสิร์ต
     */
    public function checkConcert(int $concertId): View
    {
        $concert = $this->buildOwnedConcertQuery()->with('zones')->findOrFail($concertId);
        $summary = $this->buildSummary($concert);
        $seatMapData = $this->buildSeatMapData($concert->c_id);
        $maxSeatsPerRow = $seatMapData['maxSeatsPerRow'];

        return view('organizer.checkconcert.check-concert', [
            'concert' => $concert,
            'totalRevenue' => $summary['totalRevenue'],
            'soldTickets' => $summary['soldTickets'],
            'remainingSeats' => $summary['remainingSeats'],
            'totalSeats' => $summary['totalSeats'],
            'statusConfig' => $this->getConcertStatusConfig($concert->displayStatus()),
            'zoneReports' => $summary['zoneReports'],
            'seatRows' => $seatMapData['rows'],
            'maxSeatsPerRow' => $maxSeatsPerRow,
            'isWideLayout' => $maxSeatsPerRow > 12,
        ]);
    }

    /**
     * getRealtimeConcertStatus(concertId)
     * ส่งข้อมูลสรุปยอดขาย ผังที่นั่ง และสถานะคอนเสิร์ตล่าสุดเป็น JSON สำหรับอัปเดตหน้าจอแบบ Real-time
     *
     * @param  int  $concertId  รหัสประจำคอนเสิร์ต (c_id)
     * @return JsonResponse ข้อมูลสถานะล่าสุดของคอนเสิร์ต
     */
    public function getRealtimeConcertStatus(int $concertId): JsonResponse
    {
        $concert = $this->buildOwnedConcertQuery()->with('zones')->findOrFail($concertId);
        $summary = $this->buildSummary($concert);
        $seatMapData = $this->buildSeatMapData($concert->c_id);
        $statusConfig = $this->getConcertStatusConfig($concert->displayStatus());

        return response()->json([
            'totalRevenue' => number_format($summary['totalRevenue']).' บาท',
            'soldTickets' => number_format($summary['soldTickets']).' ใบ',
            'remainingSeats' => number_format($summary['remainingSeats']).' ที่นั่ง',
            'totalSeats' => number_format($summary['totalSeats']).' ที่นั่ง',
            'statusLabel' => $statusConfig['label'],
            'statusTextClass' => $statusConfig['textClass'],
            'isCheckInMode' => $statusConfig['isCheckInMode'],
            'zoneReports' => $summary['zoneReports']->values(),
            'seatRows' => $seatMapData['rows'],
        ]);
    }

    /**
     * buildOwnedConcertQuery()
     * สร้าง query คอนเสิร์ตที่เป็นขององค์กรผู้ใช้ปัจจุบัน (กันดูคอนเสิร์ตขององค์กรอื่น)
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
     * buildSummary(concert)
     * รวมยอดขาย ที่นั่งคงเหลือ และรายได้รวมของคอนเสิร์ตจากรายงานรายโซน
     *
     * @param  Concert  $concert  คอนเสิร์ตพร้อมความสัมพันธ์ zones
     * @return array ตัวเลขสรุปและรายงานรายโซน
     */
    private function buildSummary(Concert $concert): array
    {
        $zoneReports = $this->buildZoneReportData($concert);
        $totalSeats = (int) $zoneReports->sum('totalSeats');
        $soldTickets = (int) $zoneReports->sum('soldSeats');

        return [
            'zoneReports' => $zoneReports,
            'totalSeats' => $totalSeats,
            'soldTickets' => $soldTickets,
            'remainingSeats' => max(0, $totalSeats - $soldTickets),
            'totalRevenue' => (float) $zoneReports->sum('zoneRevenue'),
        ];
    }

    /**
     * buildZoneReportData(concert)
     * คำนวณยอดขาย ที่นั่งคงเหลือ และรายได้รวมแยกตามโซนของคอนเสิร์ต
     * โซนนั่ง (SEATED) นับจาก seat.seat_booking_status = sold, โซนยืน (STANDING) นับจาก zone_inventory.sold
     *
     * @param  Concert  $concert  คอนเสิร์ตพร้อมความสัมพันธ์ zones
     * @return Collection รายการข้อมูลสรุปแยกตามโซน
     */
    private function buildZoneReportData(Concert $concert): Collection
    {
        $zoneIds = $concert->zones->pluck('z_id');

        $soldSeatedByZone = DB::table('seat')
            ->whereIn('s_zone_id', $zoneIds)
            ->where('seat_booking_status', Seat::STATUS_SOLD)
            ->select('s_zone_id', DB::raw('COUNT(*) AS total'))
            ->groupBy('s_zone_id')
            ->pluck('total', 's_zone_id');

        $soldInventoryByZone = ZoneInventory::query()
            ->whereIn('zi_zone_id', $zoneIds)
            ->pluck('sold', 'zi_zone_id');

        return $concert->zones->map(function (Zone $zone) use ($soldSeatedByZone, $soldInventoryByZone) {
            $soldSeats = $zone->zone_type === Zone::TYPE_STANDING
                ? (int) ($soldInventoryByZone[$zone->z_id] ?? 0)
                : (int) ($soldSeatedByZone[$zone->z_id] ?? 0);

            $totalZoneSeats = (int) ($zone->capacity ?? 0);

            return [
                'id' => $zone->z_id,
                'name' => $zone->name,
                'colorHex' => $zone->color ?: '#2F46C4',
                'price' => (float) $zone->price,
                'totalSeats' => $totalZoneSeats,
                'soldSeats' => $soldSeats,
                'remainingSeats' => max(0, $totalZoneSeats - $soldSeats),
                'zoneRevenue' => $soldSeats * (float) $zone->price,
            ];
        });
    }

    /**
     * buildSeatMapData(concertId)
     * จัดกลุ่มข้อมูลที่นั่งรายแถว พร้อมสถานะการเช็กอิน (ticket.is_used) ของแต่ละที่นั่ง
     * สถานะที่ส่งให้ view ใช้ตัวพิมพ์ใหญ่: AVAILABLE / LOCKED / SOLD / DISABLED
     *
     * @param  int  $concertId  รหัสประจำคอนเสิร์ต (c_id)
     * @return array ข้อมูลผังที่นั่งรายแถวและจำนวนที่นั่งสูงสุดต่อแถว
     */
    private function buildSeatMapData(int $concertId): array
    {
        $seats = DB::table('seat')
            ->join('zone', 'seat.s_zone_id', '=', 'zone.z_id')
            ->leftJoin('ticket', function ($join) {
                $join->on('ticket.t_seat_id', '=', 'seat.s_id')
                    ->where('ticket.status', '=', 'valid');
            })
            ->where('zone.z_concert_id', $concertId)
            ->select([
                'seat.s_id as id',
                'seat.s_zone_id as zoneId',
                'seat.row as rowLabel',
                'seat.number as seatNumber',
                'seat.seat_booking_status as bookingStatus',
                'seat.seat_status as seatStatus',
                'ticket.is_used as isUsed',
            ])
            ->orderBy('zone.z_id')
            ->orderBy('seat.row')
            ->orderBy('seat.number')
            ->get();

        $rows = [];
        $maxSeatsPerRow = 0;

        foreach ($seats as $seat) {
            $rowKey = (string) $seat->rowLabel;
            $rows[$rowKey][] = [
                'id' => $seat->id,
                'zoneId' => $seat->zoneId,
                'number' => $seat->seatNumber,
                'status' => $this->mapSeatStatus($seat->bookingStatus, $seat->seatStatus),
                'isCheckedIn' => (bool) $seat->isUsed,
            ];

            $maxSeatsPerRow = max($maxSeatsPerRow, count($rows[$rowKey]));
        }

        return [
            'rows' => $rows,
            'maxSeatsPerRow' => $maxSeatsPerRow,
        ];
    }

    /**
     * mapSeatStatus(bookingStatus, seatStatus)
     * แปลงสถานะที่นั่งใน schema (ตัวพิมพ์เล็ก) เป็นรหัสที่หน้า view ใช้
     *
     * @param  string|null  $bookingStatus  seat.seat_booking_status
     * @param  string|null  $seatStatus  seat.seat_status
     * @return string AVAILABLE, LOCKED, SOLD หรือ DISABLED
     */
    private function mapSeatStatus(?string $bookingStatus, ?string $seatStatus): string
    {
        if ($seatStatus !== null && $seatStatus !== Seat::SEAT_ACTIVE) {
            return 'DISABLED';
        }

        return match ($bookingStatus) {
            Seat::STATUS_SOLD => 'SOLD',
            Seat::STATUS_LOCKED, Seat::STATUS_RESERVED => 'LOCKED',
            default => 'AVAILABLE',
        };
    }

    /**
     * getConcertStatusConfig($status)
     * แปลงรหัสสถานะคอนเสิร์ตเป็นข้อความภาษาไทย คลาสสี และโหมดการแสดงผลผังที่นั่ง
     *
     * @param string $status สถานะจากฐานข้อมูล
     * @return array ค่าตั้งต้นสำหรับแสดงผลสถานะ
     */
    private function getConcertStatusConfig(string $status): array
    {
        $normalizedStatus = strtoupper(trim($status));

        return match ($normalizedStatus) {
            'DRAFT', 'SCHEDULED', 'PENDING_APPROVAL', 'UPCOMING', 'ยังไม่เปิดขาย' => [
                'label' => 'ยังไม่เปิดขาย',
                'textClass' => 'status-text--pending',
                'isCheckInMode' => false,
            ],
            'ON_SALE', 'PUBLISHED', 'กำลังเปิดขาย' => [
                'label' => 'กำลังเปิดขาย',
                'textClass' => 'status-text--on-sale',
                'isCheckInMode' => false,
            ],
            'SOLD_OUT', 'บัตรหมด' => [
                'label' => 'บัตรหมด',
                'textClass' => 'status-text--sold-out',
                'isCheckInMode' => false,
            ],
            'SALE_CLOSED' => [
                'label' => 'ปิดการจำหน่าย',
                'textClass' => 'status-text--sold-out',
                'isCheckInMode' => false,
            ],
            'CANCELLED' => [
                'label' => 'ยกเลิกการแสดง',
                'textClass' => 'status-text--ended',
                'isCheckInMode' => false,
            ],
            'EVENT_DAY', 'CHECK_IN', 'วันแสดง' => [
                'label' => 'วันแสดง',
                'textClass' => 'status-text--event-day',
                'isCheckInMode' => true,
            ],
            'ENDED', 'COMPLETED', 'สิ้นสุดการแสดง' => [
                'label' => 'สิ้นสุดการแสดง',
                'textClass' => 'status-text--ended',
                'isCheckInMode' => true,
            ],
            default => [
                'label' => 'กำลังเปิดขาย',
                'textClass' => 'status-text--on-sale',
                'isCheckInMode' => false,
            ],
        };
    }
}
