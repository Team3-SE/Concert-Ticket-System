<?php

/**
 * ConcertController
 * จัดการข้อมูลคอนเสิร์ตฝั่ง Organizer รวมถึงหน้าจอตรวจสอบรายละเอียดคอนเสิร์ต ผังที่นั่ง และรายงานยอดขาย
 * Author : 67160434 จิตฏิยากร โสภา
 * Created : 25/11/2569
 * Updated : 28/11/2569 67160434 จิตฏิยากร โสภา
 */

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Concert;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\Zone;
use App\Models\ZoneInventory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ConcertController extends Controller
{
    /**
     * จำนวนที่นั่งสูงสุดต่อแถวก่อนสลับการแสดงผลเป็นแบบเรียงซ้อน (Stacked Layout)
     */
    private const WIDE_LAYOUT_SEAT_THRESHOLD = 20;

    /**
     * รหัสสีเริ่มต้นของโซนกรณีไม่ได้ระบุสีในฐานข้อมูล
     */
    private const DEFAULT_ZONE_COLOR = '#3b82f6';

    private const SEAT_DISPLAY_AVAILABLE = 'AVAILABLE';
    private const SEAT_DISPLAY_LOCKED = 'LOCKED';
    private const SEAT_DISPLAY_SOLD = 'SOLD';
    private const SEAT_DISPLAY_DISABLED = 'DISABLED';

    private const COLOR_RED_WEIGHT = 299;
    private const COLOR_GREEN_WEIGHT = 587;
    private const COLOR_BLUE_WEIGHT = 114;
    private const COLOR_WEIGHT_TOTAL = 1000;
    private const LIGHT_COLOR_THRESHOLD = 150;

    /**
     * checkConcert($concertId)
     * แสดงหน้าจอตรวจสอบรายละเอียดคอนเสิร์ต ผังที่นั่งสถานะปัจจุบัน และรายงานจำนวนตั๋วแยกตามโซน
     *
     * @param int $concertId รหัสประจำคอนเสิร์ต
     * @return View หน้าจอตรวจสอบคอนเสิร์ต
     */
    public function checkConcert(int $concertId): View
    {
        $concert = $this->buildOwnedConcertQuery()->with('zones')->findOrFail($concertId);
        $summary = $this->buildSummary($concert);
        $seatMapData = $this->buildSeatMapData($concert);
        $maxSeatsPerRow = $seatMapData['maxSeatsPerRow'];
        $displayStatus = $concert->displayStatus();

        return view('organizer.checkconcert.check-concert', [
            'concert' => $concert,
            'totalRevenue' => $summary['totalRevenue'],
            'soldTickets' => $summary['soldTickets'],
            'remainingSeats' => $summary['remainingSeats'],
            'totalSeats' => $summary['totalSeats'],
            'statusConfig' => $this->getConcertStatusConfig(
                $displayStatus,
                $concert->event_datetime?->isToday() ?? false
            ),
            'zoneReports' => $summary['zoneReports'],
            'seatRows' => $seatMapData['rows'],
            'seatMapsByZone' => $seatMapData['seatMapsByZone'],
            'maxSeatsPerRow' => $maxSeatsPerRow,
            'isWideLayout' => $maxSeatsPerRow > self::WIDE_LAYOUT_SEAT_THRESHOLD,
        ]);
    }

    /**
     * updateStatus($request, $concertId)
     * อัปเดตสถานะการจำหน่ายคอนเสิร์ตโดยยืนยันว่าเป็นขององค์กรผู้จัด
     *
     * @param Request $request ข้อมูลสถานะเป้าหมาย
     * @param int $concertId รหัสประจำคอนเสิร์ต
     * @return RedirectResponse กลับไปยังหน้ารายละเอียดคอนเสิร์ต
     */
    public function updateStatus(Request $request, int $concertId): RedirectResponse
    {
        $concert = $this->buildOwnedConcertQuery()->findOrFail($concertId);
        $targetStatus = strtoupper((string) $request->input('target_status', ''));
        $statusConfig = $this->getConcertStatusConfig(
            $concert->displayStatus(),
            $concert->event_datetime?->isToday() ?? false
        );
        abort_unless(
            in_array($targetStatus, [
                Concert::STATUS_ON_SALE,
                Concert::STATUS_SCHEDULED,
                Concert::STATUS_SOLD_OUT,
            ], true)
            && $statusConfig['targetStatus'] === $targetStatus,
            422,
            'สถานะเป้าหมายไม่ถูกต้อง'
        );

        $concertAttributes = ['status' => $targetStatus];
        if (
            $targetStatus === Concert::STATUS_ON_SALE
            && $concert->sale_end_datetime !== null
            && $concert->sale_end_datetime->isPast()
            && $concert->event_datetime !== null
            && $concert->event_datetime->isFuture()
        ) {
            $concertAttributes['sale_end_datetime'] = $concert->event_datetime;
        }

        $concert->update($concertAttributes);

        return redirect()
            ->route('organizer.concerts.check', ['concertId' => $concert->c_id])
            ->with('status', 'อัปเดตสถานะคอนเสิร์ตเรียบร้อยแล้ว');
    }

    /**
     * getRealtimeConcertStatus($concertId)
     * ส่งข้อมูลสรุปยอดขาย ผังที่นั่ง และสถานะคอนเสิร์ตล่าสุดเป็น JSON สำหรับอัปเดตหน้าจอแบบ Real-time
     *
     * @param int $concertId รหัสประจำคอนเสิร์ต
     * @return JsonResponse ข้อมูลสถานะล่าสุดของคอนเสิร์ต
     */
    public function getRealtimeConcertStatus(int $concertId): JsonResponse
    {
        $concert = $this->buildOwnedConcertQuery()->with('zones')->findOrFail($concertId);
        $summary = $this->buildSummary($concert);
        $seatMapData = $this->buildSeatMapData($concert);
        $statusConfig = $this->getConcertStatusConfig($concert->displayStatus());

        return response()->json([
            'totalRevenue' => number_format($summary['totalRevenue']) . ' บาท',
            'soldTickets' => number_format($summary['soldTickets']) . ' ใบ',
            'remainingSeats' => number_format($summary['remainingSeats']) . ' ที่นั่ง',
            'totalSeats' => number_format($summary['totalSeats']) . ' ที่นั่ง',
            'statusLabel' => $statusConfig['label'],
            'statusTextClass' => $statusConfig['textClass'],
            'isCheckInMode' => $statusConfig['isCheckInMode'],
            'actionTargetStatus' => $statusConfig['targetStatus'],
            'actionButtonLabel' => $statusConfig['actionButtonLabel'],
            'actionButtonClass' => $statusConfig['actionButtonClass'],
            'zoneReports' => $summary['zoneReports']->values(),
            'seatRows' => $seatMapData['rows'],
            'seatMapsByZone' => $seatMapData['seatMapsByZone'],
        ]);
    }

    /**
     * buildOwnedConcertQuery()
     * สร้างชุดคำสั่งค้นหาคอนเสิร์ตที่เป็นขององค์กรผู้ใช้ปัจจุบัน
     *
     * @return Builder ชุดคำสั่งค้นหาคอนเสิร์ตในองค์กรผู้ใช้
     */
    private function buildOwnedConcertQuery(): Builder
    {
        $organizationId = Auth::user()->u_organization_id;

        abort_if(! $organizationId, 403, 'บัญชีผู้จัดยังไม่ได้ผูกกับองค์กร');

        return Concert::query()->where('c_organization_id', $organizationId);
    }

    /**
     * buildSummary($concert)
     * รวมยอดขาย ที่นั่งคงเหลือ และรายได้รวมของคอนเสิร์ตจากรายงานรายโซน
     *
     * @param Concert $concert ข้อมูลคอนเสิร์ตพร้อมความสัมพันธ์โซน
     * @return array ข้อมูลตัวเลขสรุปและรายงานรายโซน
     */
    private function buildSummary(Concert $concert): array
    {
        $zoneReports = $this->buildZoneReportData($concert);
        $totalSeats = (int) $zoneReports->sum('totalSeats');
        $soldTickets = (int) $zoneReports->sum('soldTickets');
        $remainingSeats = (int) $zoneReports->sum('remainingSeats');
        $totalRevenue = (float) $zoneReports->sum('revenue');

        return [
            'totalSeats' => $totalSeats,
            'soldTickets' => $soldTickets,
            'remainingSeats' => $remainingSeats,
            'totalRevenue' => $totalRevenue,
            'zoneReports' => $zoneReports,
        ];
    }

    /**
     * buildZoneReportData($concert)
     * คำนวณข้อมูลสรุปรายโซนทั้งโซนนั่งและโซนยืนตามโครงสร้างฐานข้อมูล Schema 3.4.2
     *
     * @param Concert $concert ข้อมูลคอนเสิร์ตพร้อมความสัมพันธ์โซน
     * @return Collection รายการสรุปแยกตามโซน
     */
    private function buildZoneReportData(Concert $concert): Collection
    {
        $zoneIds = $concert->zones->pluck('z_id');

        if ($zoneIds->isEmpty()) {
            return collect();
        }

        /* Start: ดึงข้อมูลสถิติที่นั่งจากตาราง seat และตัวนับโซนยืนจาก zone_inventory */
        $seatStatistics = Seat::query()
            ->whereIn('s_zone_id', $zoneIds)
            ->where('seat_status', Seat::SEAT_ACTIVE)
            ->select('s_zone_id')
            ->selectRaw('COUNT(*) AS total_seats')
            ->selectRaw(
                'SUM(CASE WHEN seat_booking_status = ? THEN 1 ELSE 0 END) AS sold_tickets',
                [Seat::STATUS_SOLD]
            )
            ->selectRaw(
                'SUM(CASE WHEN seat_booking_status = ? THEN 1 ELSE 0 END) AS available_seats',
                [Seat::STATUS_AVAILABLE]
            )
            ->groupBy('s_zone_id')
            ->get()
            ->keyBy('s_zone_id');

        $standingInventories = ZoneInventory::query()
            ->whereIn('zi_zone_id', $zoneIds)
            ->get()
            ->keyBy('zi_zone_id');
        $ticketsByZone = Ticket::query()
            ->whereIn('t_zone_id', $zoneIds)
            ->where('status', Ticket::STATUS_VALID)
            ->with('order')
            ->get()
            ->groupBy('t_zone_id');

        return $concert->zones->map(function (Zone $zone) use ($seatStatistics, $standingInventories, $ticketsByZone) {
            $isStandingZone = $zone->isStanding();
            $zoneTickets = $ticketsByZone->get($zone->z_id, collect());

            if ($isStandingZone) {
                $inventory = $standingInventories->get($zone->z_id);
                $totalSeats = (int) ($zone->capacity ?? 0);
                $soldTickets = (int) ($inventory->sold ?? 0);
                $heldTickets = (int) ($inventory->held ?? 0);
                $remainingSeats = max(0, $totalSeats - $soldTickets - $heldTickets);
            } else {
                $seatStatistic = $seatStatistics->get($zone->z_id);
                $totalSeats = (int) ($seatStatistic->total_seats ?? $zone->capacity ?? 0);
                $soldTickets = (int) ($seatStatistic->sold_tickets ?? 0);
                $remainingSeats = (int) ($seatStatistic->available_seats ?? 0);
            }

            $zoneColor = $zone->color ?: self::DEFAULT_ZONE_COLOR;
            $zonePrice = (float) $zone->price;
            $revenue = (float) $zoneTickets->sum(
                fn (Ticket $ticket) => $ticket->unitPrice()
            );
            $checkedInTickets = $zoneTickets->filter(
                fn (Ticket $ticket) => $ticket->is_used
            )->count();

            return [
                'id' => $zone->z_id,
                'z_id' => $zone->z_id,
                'name' => $zone->name,
                'colorHex' => $zoneColor,
                'color' => $zoneColor,
                'textColor' => $this->getContrastingTextColor($zoneColor),
                'zoneType' => $isStandingZone ? Zone::TYPE_STANDING : Zone::TYPE_SEATED,
                'price' => $zonePrice,
                'totalSeats' => $totalSeats,
                'soldTickets' => $soldTickets,
                'soldSeats' => $soldTickets,
                'remainingSeats' => $remainingSeats,
                'checkedInTickets' => $checkedInTickets,
                'revenue' => $revenue,
                'zoneRevenue' => $revenue,
            ];
        });
    }

    /**
     * buildSeatMapData($concert)
     * จัดกลุ่มที่นั่งรายแถวและรายโซนสำหรับแสดงผลบนผังที่นั่งจำลอง
     *
     * @param Concert $concert ข้อมูลคอนเสิร์ตพร้อมความสัมพันธ์โซน
     * @return array ข้อมูลแถวที่นั่งและจำนวนที่นั่งสูงสุดต่อแถว
     */
    private function buildSeatMapData(Concert $concert): array
    {
        $zonesById = $concert->zones->keyBy('z_id');
        $zoneIds = $zonesById->keys();

        if ($zoneIds->isEmpty()) {
            return [
                'rows' => [],
                'seatMapsByZone' => [],
                'maxSeatsPerRow' => 0,
            ];
        }

        $seats = Seat::query()
            ->with('ticket')
            ->whereIn('s_zone_id', $zoneIds)
            ->orderBy('row')
            ->orderBy('number')
            ->get();

        /* Start: จัดกลุ่มรายการที่นั่งตามชื่อแถวให้ตรงกับโครงสร้างหน้าผังที่นั่ง */
        $groupedRows = [];
        $seatMapsByZone = [];
        $maxSeatsPerRow = 0;

        foreach ($seats as $seat) {
            $zone = $zonesById->get($seat->s_zone_id);
            $rowLabel = (string) ($seat->row ?? '-');
            $seatNumber = (string) ($seat->number ?? '');
            $zoneColor = $zone?->color ?: self::DEFAULT_ZONE_COLOR;
            $seatDisplayInfo = $this->resolveSeatDisplayInfo($seat);

            $zoneKey = (string) $seat->s_zone_id;
            if (! isset($seatMapsByZone[$zoneKey])) {
                $seatMapsByZone[$zoneKey] = [];
            }

            if (! isset($seatMapsByZone[$zoneKey][$rowLabel])) {
                $seatMapsByZone[$zoneKey][$rowLabel] = [];
            }

            $seatData = [
                'id' => $seat->s_id,
                's_id' => $seat->s_id,
                'zoneId' => $seat->s_zone_id,
                'zoneName' => $zone?->name ?? '-',
                'zoneColor' => $zoneColor,
                'rowLabel' => $rowLabel,
                'number' => $seatNumber,
                'seatNumber' => $seatNumber,
                'label' => $rowLabel . $seatNumber,
                'status' => $seatDisplayInfo['status'],
                'isCheckedIn' => (bool) ($seat->ticket?->is_used ?? false),
            ];

            $seatMapsByZone[$zoneKey][$rowLabel][] = $seatData;
            $groupedRows[$zoneKey] = $seatMapsByZone[$zoneKey];
        }

        foreach ($seatMapsByZone as $zoneSeatRows) {
            foreach ($zoneSeatRows as $seatRow) {
                $seatCountInRow = count($seatRow);
                if ($seatCountInRow > $maxSeatsPerRow) {
                    $maxSeatsPerRow = $seatCountInRow;
                }
            }
        }
        /* End: จัดกลุ่มรายการที่นั่งตามชื่อแถวให้ตรงกับโครงสร้างหน้าผังที่นั่ง */

        return [
            'rows' => $groupedRows,
            'seatMapsByZone' => $seatMapsByZone,
            'maxSeatsPerRow' => $maxSeatsPerRow,
        ];
    }

    /**
     * resolveSeatDisplayInfo($seat)
     * แปลงค่า seat_status และ seat_booking_status จากฐานข้อมูลให้เป็นสถานะมาตรฐานสำหรับหน้าผังที่นั่ง
     *
     * @param Seat $seat ข้อมูลที่นั่งจากฐานข้อมูล
     * @return array สถานะที่นั่งสำหรับแสดงผลบนผังที่นั่ง
     */
    private function resolveSeatDisplayInfo(Seat $seat): array
    {
        if (strtolower((string) $seat->seat_status) !== Seat::SEAT_ACTIVE) {
            return [
                'status' => self::SEAT_DISPLAY_DISABLED,
            ];
        }

        $bookingStatus = strtolower((string) $seat->seat_booking_status);

        return match ($bookingStatus) {
            Seat::STATUS_SOLD => ['status' => self::SEAT_DISPLAY_SOLD],
            Seat::STATUS_LOCKED, Seat::STATUS_RESERVED => ['status' => self::SEAT_DISPLAY_LOCKED],
            Seat::STATUS_AVAILABLE => ['status' => self::SEAT_DISPLAY_AVAILABLE],
            default => ['status' => self::SEAT_DISPLAY_DISABLED],
        };
    }

    /**
     * getConcertStatusConfig($displayStatus)
     * แปลงสถานะของคอนเสิร์ตเป็นข้อความ สีตัวอักษร และโหมดแสดงผลผังที่นั่ง
     *
     * @param string $displayStatus สถานะการแสดงผลของคอนเสิร์ต
     * @return array ค่าคอนฟิกสำหรับแสดงผลในหน้าตรวจสอบคอนเสิร์ต
     */
    private function getConcertStatusConfig(string $displayStatus, bool $isEventDay = false): array
    {
        $normalizedStatus = strtoupper($displayStatus);
        $statusConfig = match ($normalizedStatus) {
            Concert::STATUS_ON_SALE => [
                'label' => 'กำลังเปิดขาย',
                'textClass' => 'status-text--on-sale',
                'isCheckInMode' => false,
                'targetStatus' => Concert::STATUS_SCHEDULED,
                'actionButtonLabel' => 'ปิดการจำหน่ายชั่วคราว',
                'actionButtonClass' => 'btn-status-action--pause',
            ],
            Concert::STATUS_SCHEDULED => [
                'label' => 'ปิดการจำหน่ายชั่วคราว',
                'textClass' => 'status-text--paused',
                'isCheckInMode' => false,
                'targetStatus' => Concert::STATUS_ON_SALE,
                'actionButtonLabel' => 'ดำเนินการจำหน่าย',
                'actionButtonClass' => 'btn-status-action--resume',
            ],
            Concert::STATUS_SOLD_OUT => [
                'label' => 'ปิดการจำหน่าย',
                'textClass' => 'status-text--closed',
                'isCheckInMode' => true,
                'targetStatus' => Concert::STATUS_ON_SALE,
                'actionButtonLabel' => 'เปิดคอนเสิร์ต',
                'actionButtonClass' => 'btn-status-action--open-concert',
            ],
            Concert::DISPLAY_COMPLETED => [
                'label' => 'เสร็จสิ้น',
                'textClass' => 'status-text--ended',
                'isCheckInMode' => true,
                'targetStatus' => null,
                'actionButtonLabel' => null,
                'actionButtonClass' => null,
            ],
            Concert::STATUS_DRAFT => [
                'label' => 'ฉบับร่าง',
                'textClass' => 'status-text--pending',
                'isCheckInMode' => false,
                'targetStatus' => null,
                'actionButtonLabel' => null,
                'actionButtonClass' => null,
            ],
            Concert::DISPLAY_SALE_CLOSED => [
                'label' => 'ปิดการจำหน่าย',
                'textClass' => 'status-text--closed',
                'isCheckInMode' => true,
                'targetStatus' => Concert::STATUS_SOLD_OUT,
                'actionButtonLabel' => 'ปิดคอนเสิร์ต',
                'actionButtonClass' => 'btn-status-action--open-concert',
            ],
            Concert::STATUS_CANCELLED => [
                'label' => 'ยกเลิกการแสดง',
                'textClass' => 'status-text--sold-out',
                'isCheckInMode' => false,
                'targetStatus' => null,
                'actionButtonLabel' => null,
                'actionButtonClass' => null,
            ],
            default => [
                'label' => 'รอเปิดจำหน่าย',
                'textClass' => 'status-text--pending',
                'isCheckInMode' => false,
                'targetStatus' => null,
                'actionButtonLabel' => null,
                'actionButtonClass' => null,
            ],
        };

        if (
            $isEventDay
            && in_array($normalizedStatus, [
                Concert::STATUS_ON_SALE,
                Concert::STATUS_SCHEDULED,
                Concert::STATUS_SOLD_OUT,
                Concert::DISPLAY_SALE_CLOSED,
            ], true)
        ) {
            $statusConfig['isCheckInMode'] = true;
        }

        return $statusConfig;
    }

    /**
     * getContrastingTextColor($hexColor)
     * เลือกสีข้อความปุ่มโซนให้ตัดกับสีพื้นหลังจากฐานข้อมูล
     *
     * @param string $hexColor สีพื้นหลังแบบ hexadecimal
     * @return string สีข้อความ
     */
    private function getContrastingTextColor(string $hexColor): string
    {
        $normalizedColor = ltrim($hexColor, '#');
        if (strlen($normalizedColor) === 3) {
            $normalizedColor = implode('', array_map(
                static fn (string $digit): string => $digit . $digit,
                str_split($normalizedColor)
            ));
        }

        if (! preg_match('/^[a-f0-9]{6}$/i', $normalizedColor)) {
            return '#ffffff';
        }

        $red = hexdec(substr($normalizedColor, 0, 2));
        $green = hexdec(substr($normalizedColor, 2, 2));
        $blue = hexdec(substr($normalizedColor, 4, 2));
        $luminance = (
            $red * self::COLOR_RED_WEIGHT
            + $green * self::COLOR_GREEN_WEIGHT
            + $blue * self::COLOR_BLUE_WEIGHT
        ) / self::COLOR_WEIGHT_TOTAL;

        return $luminance >= self::LIGHT_COLOR_THRESHOLD ? '#0f172a' : '#ffffff';
    }
}
