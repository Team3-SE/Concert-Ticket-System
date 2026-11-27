<?php

/**
 * app/Http/Controllers/Organizer/ConcertController.php
 * จัดการข้อมูลคอนเสิร์ตฝั่ง Organizer รวมถึงหน้าจอตรวจสอบรายละเอียดคอนเสิร์ต ผังที่นั่ง และรายงานยอดขาย
 * Author : 67160434 จิตฏิยากร โสภา
 * Created : 25/11/2569
 * Updated : 26/11/2569 67160434 จิตฏิยากร โสภา
 */

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Concert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConcertController extends Controller
{
    /**
     * index()
     * เส้นทางสำรองสำหรับเมนูจัดการคอนเสิร์ต พาไปยังหน้าตรวจสอบคอนเสิร์ตแรกที่พบในระบบ
     *
     * @return RedirectResponse เปลี่ยนเส้นทางไปยังหน้าตรวจสอบคอนเสิร์ตหรือแดชบอร์ด
     */
    public function index(): RedirectResponse
    {
        $firstConcert = Concert::query()->orderBy('id')->first();

        if ($firstConcert) {
            return redirect()->route('organizer.concerts.check', ['concertId' => $firstConcert->id]);
        }

        return redirect()->route('organizer.dashboard');
    }

    /**
     * checkConcert($concertId)
     * แสดงหน้าจอตรวจสอบรายละเอียดคอนเสิร์ต ผังที่นั่งจำลองสถานะปัจจุบัน และรายงานจำนวนตั๋วแยกตามโซน
     *
     * @param int $concertId รหัสประจำคอนเสิร์ต
     * @return View หน้าจอตรวจสอบคอนเสิร์ต
     */
    public function checkConcert(int $concertId): View
    {
        $concert = Concert::with(['zones'])->findOrFail($concertId);
        $zoneReports =$this->buildZoneReportData($concert);$totalSeats = $zoneReports->sum('totalSeats');$soldTickets = $zoneReports->sum('soldSeats');$remainingSeats = $totalSeats -$soldTickets;
        $totalRevenue = $zoneReports->sum('zoneRevenue');$seatMapData = $this->buildSeatMapData($concertId);
        $maxSeatsPerRow =$seatMapData['maxSeatsPerRow'];
        $isWideLayout =$maxSeatsPerRow > 12;
        $statusConfig = $this->getConcertStatusConfig((string)$concert->status);

        return view('checkconcert.check-concert', [
            'concert' => $concert,
            'totalRevenue' => $totalRevenue,
            'soldTickets' => $soldTickets,
            'remainingSeats' => $remainingSeats,
            'totalSeats' => $totalSeats,
            'statusConfig' => $statusConfig,
            'zoneReports' => $zoneReports,
            'seatRows' => $seatMapData['rows'],
            'maxSeatsPerRow' => $maxSeatsPerRow,
            'isWideLayout' => $isWideLayout,
        ]);
    }

    /**
     * getRealtimeConcertStatus($concertId)
     * ส่งข้อมูลสรุปยอดขาย ผังที่นั่ง และสถานะคอนเสิร์ตล่าสุดในรูปแบบ JSON สำหรับอัปเดตหน้าจอแบบ Real-time
     *
     * @param int $concertId รหัสประจำคอนเสิร์ต
     * @return JsonResponse ข้อมูลสถานะล่าสุดของคอนเสิร์ต
     */
    public function getRealtimeConcertStatus(int $concertId): JsonResponse
    {
        $concert = Concert::with(['zones'])->findOrFail($concertId);
        $zoneReports =$this->buildZoneReportData($concert);$totalSeats = $zoneReports->sum('totalSeats');$soldTickets = $zoneReports->sum('soldSeats');$remainingSeats = $totalSeats -$soldTickets;
        $totalRevenue =$zoneReports->sum('zoneRevenue');
        $seatMapData =$this->buildSeatMapData($concertId);$statusConfig = $this->getConcertStatusConfig((string)$concert->status);

        return response()->json([
            'totalRevenue' => number_format($totalRevenue) . ' บาท',
            'soldTickets' => number_format($soldTickets) . ' ใบ',
            'remainingSeats' => number_format($remainingSeats) . ' ที่นั่ง',
            'totalSeats' => number_format($totalSeats) . ' ที่นั่ง',
            'statusLabel' => $statusConfig['label'],
            'statusTextClass' => $statusConfig['textClass'],
            'isCheckInMode' => $statusConfig['isCheckInMode'],
            'zoneReports' => $zoneReports->values(),
            'seatRows' => $seatMapData['rows'],
        ]);
    }

    /**
     * buildZoneReportData($concert)
     * คำนวณยอดขาย ที่นั่งคงเหลือ และรายได้รวมแยกตามโซนของคอนเสิร์ต
     *
     * @param Concert $concert ข้อมูลคอนเสิร์ตพร้อมความสัมพันธ์ zones
     * @return Collection รายการข้อมูลสรุปแยกตามโซน
     */
    private function buildZoneReportData(Concert $concert): Collection
    {
        return $concert->zones->map(function ($zone) {$soldSeatsCount = DB::table('cts_seats')
                ->where('zone_id', $zone->id)
                ->where('status', 'SOLD')
                ->count();

            $totalZoneSeats = (int) ($zone->capacity ?? 0);
            $remainingZoneSeats = max(0,$totalZoneSeats - $soldSeatsCount);$zoneRevenue = $soldSeatsCount * (float)$zone->price;

            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'colorHex' => $zone->color_hex ?? '#2F46C4',
                'price' => (float) $zone->price,
                'totalSeats' => $totalZoneSeats,
                'soldSeats' => $soldSeatsCount,
                'remainingSeats' => $remainingZoneSeats,
                'zoneRevenue' => $zoneRevenue,
            ];
        });
    }

    /**
     * buildSeatMapData($concertId)
     * จัดกลุ่มข้อมูลที่นั่งรายแถวและตรวจสอบสถานะการเช็กอินเข้างานของแต่ละที่นั่ง
     *
     * @param int $concertId รหัสประจำคอนเสิร์ต
     * @return array ข้อมูลผังที่นั่งรายแถวและจำนวนที่นั่งสูงสุดต่อแถว
     */
    private function buildSeatMapData(int $concertId): array
    {
        $seats = DB::table('cts_seats')
            ->join('cts_zones', 'cts_seats.zone_id', '=', 'cts_zones.id')
            ->leftJoin('cts_tickets', 'cts_seats.id', '=', 'cts_tickets.seat_id')
            ->where('cts_zones.concert_id', $concertId)
            ->select([
                'cts_seats.id',
                'cts_seats.zone_id as zoneId',
                'cts_seats.row_label as rowLabel',
                'cts_seats.seat_number as seatNumber',
                'cts_seats.status',
                'cts_tickets.checked_in_at as checkedInAt',
            ])
            ->orderBy('cts_seats.row_label')
            ->orderBy('cts_seats.seat_number')
            ->get();

        $rows = [];$maxSeatsPerRow = 0;

        foreach ($seats as$seat) {
            $rowKey = (string)$seat->rowLabel;
            if (!isset($rows[$rowKey])) {
                $rows[$rowKey] = [];
            }

            $rows[$rowKey][] = [
                'id' => $seat->id,
                'zoneId' => $seat->zoneId,
                'number' => $seat->seatNumber,
                'status' => (string) $seat->status,
                'isCheckedIn' => !is_null($seat->checkedInAt),
            ];

            $rowSeatCount = count($rows[$rowKey]);
            if ($rowSeatCount >$maxSeatsPerRow) {
                $maxSeatsPerRow =$rowSeatCount;
            }
        }

        return [
            'rows' => $rows,
            'maxSeatsPerRow' => $maxSeatsPerRow,
        ];
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
            'DRAFT', 'PENDING_APPROVAL', 'UPCOMING', 'ยังไม่เปิดขาย' => [
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
