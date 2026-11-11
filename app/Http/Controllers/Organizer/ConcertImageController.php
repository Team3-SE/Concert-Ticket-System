<?php

/**
 * ConcertImageController ไม่ได้ใช้จริง
 * จัดการข้อมูลคอนเสิร์ตทั้งหมดในหน้าเดียว (ข้อมูลทั่วไป ช่วงขายบัตร รูปภาพ โซนและที่นั่ง)
 * และอัปโหลด/ลบรูปภาพรายประเภท (poster, banner, seat_map)
 * Author : 67160292 ธีรเมธ พลสาย
 * Created : 04/09/2569
 * Updated : 11/11/2569 67160292 ธีรเมธ พลสาย
 */

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Concert;
use App\Models\Organization;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\Zone;
use App\Models\ZoneInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ConcertImageController extends Controller
{
    /* คอลัมน์ในตาราง concert ของรูปแต่ละประเภท (ชื่อ key = ชื่อ input ในฟอร์ม) */
    private const IMAGE_COLUMNS = [
        'poster'   => 'poster_path',
        'banner'   => 'banner_path',
        'seat_map' => 'seat_map_image_path',
    ];

    /* ขนาดรูปที่แนะนำ — แก้ค่าตรงนี้แล้ว View จะอัปเดตตาม */
    const IMAGE_SIZES = [
        'poster' => [
            'w' => 500,
            'h' => 750,
            'maxKB' => 2048,   // 2 MB
        ],
        'banner' => [
            'w' => 1200,
            'h' => 300,
            'maxKB' => 3072,   // 3 MB
        ],
        'seat_map' => [
            'w' => 1200,
            'h' => 900,
            'maxKB' => 5120,   // 5 MB
        ],
    ];

    /* สถานะคอนเสิร์ตที่เลือกได้ในหน้าแก้ไข */
    private const STATUS_LABELS = [
        Concert::STATUS_DRAFT     => 'ฉบับร่าง (ไม่แสดงหน้าเว็บ)',
        Concert::STATUS_SCHEDULED => 'เร็ว ๆ นี้ (ยังไม่เปิดขาย)',
        Concert::STATUS_ON_SALE   => 'เปิดขาย',
        Concert::STATUS_SOLD_OUT  => 'ขายหมด',
    ];

    /* ประเภทโซน: SEATED = ระบุที่นั่ง, STANDING = โซนยืน */
    private const ZONE_TYPES = [Zone::TYPE_SEATED, Zone::TYPE_STANDING];

    /* สีเริ่มต้นของโซน เมื่อไม่ได้เลือกสี */
    private const DEFAULT_ZONE_COLOR = '#3B82F6';

    /* จำนวนแถวสูงสุดของโซนที่นั่ง (ชื่อแถว A–Z) */
    private const MAX_ROWS = 26;

    /* จำนวนที่นั่งต่อแถวสูงสุด */
    private const MAX_SEATS_PER_ROW = 200;

    /* จำนวนที่นั่งรวมสูงสุดต่อโซน */
    private const MAX_SEATS_PER_ZONE = 5000;

    /**
     * index(request)
     * แสดงหน้าจัดการคอนเสิร์ต: รายการคอนเสิร์ตทางซ้าย และฟอร์มแก้ไขของคอนเสิร์ตที่เลือกทางขวา
     * param : request (query concert = รหัสคอนเสิร์ตที่เลือก)
     * return : View concerts.manage-images
     */
    public function index(Request $request)
    {
        $concerts = Concert::orderBy('event_datetime', 'desc')->get();
        $selected = $concerts->firstWhere('c_id', (int) $request->query('concert')) ?? $concerts->first();

        return view('organizer.concerts.manage-images', [
            'concerts'      => $concerts,
            'selected'      => $selected,
            'zoneRows'      => $selected ? $this->buildZoneRows($selected, $request->old('zones')) : [],
            'organizations' => Organization::orderBy('name')->get(['org_id', 'name']),
            'statusLabels'  => self::STATUS_LABELS,
            'imageSizes'    => self::IMAGE_SIZES,
        ]);
    }

    /**
     * update(request, concertId)
     * บันทึกข้อมูลคอนเสิร์ตทั้งหมดในครั้งเดียว (ข้อมูลทั่วไป ช่วงขายบัตร รูปภาพ โซนและที่นั่ง)
     * ทำใน transaction เดียว ถ้าขั้นตอนใดล้มเหลวจะไม่มีอะไรถูกบันทึก
     * param : request, concertId
     * return : RedirectResponse กลับหน้าแก้ไขพร้อมข้อความผลลัพธ์
     */
    public function update(Request $request, string $concertId)
    {
        $concert  = Concert::findOrFail($concertId);
        $statuses = array_unique(array_merge(array_keys(self::STATUS_LABELS), [$concert->status]));

        $rules = [
            'c_organization_id'    => ['required', 'integer', 'exists:organization,org_id'],
            // Public Code Format Specification V2.0.0: ตัวอักษร 1 ตัว + ตัวเลข (ระบบเติม 0 ให้ครบ 6 หลักตอนออกรหัส)
            'concert_code'         => ['required', 'string', 'max:100', 'regex:' . Concert::CODE_INPUT_PATTERN, Rule::unique('concert', 'concert_code')->ignore($concert->c_id, 'c_id')],
            'name'                 => ['required', 'string', 'max:255'],
            // schema 3.4.2: artist และ venue เป็น NOT NULL, ตัดคอลัมน์ address, ช่องทางติดต่อย้ายมาอยู่ที่ concert
            'artist'               => ['required', 'string', 'max:255'],
            'venue'                => ['required', 'string', 'max:500'],
            'description'          => ['nullable', 'string', 'max:5000'],
            'phone'                => ['nullable', 'string', 'max:50'],
            'email'                => ['nullable', 'email', 'max:255'],
            'facebook'             => ['nullable', 'string', 'max:255'],
            'line'                 => ['nullable', 'string', 'max:255'],
            'event_datetime'       => ['required', 'date'],
            'sale_start_datetime'  => ['nullable', 'date'],
            'sale_end_datetime'    => ['nullable', 'date'],
            'max_ticket_per_order' => ['nullable', 'integer', 'min:1', 'max:50'],
            'status'               => ['required', Rule::in($statuses)],
            'zones'                => ['nullable', 'array'],
            'zones.*.name'         => ['required', 'string', 'max:255'],
            'zones.*.price'        => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'zones.*.color'        => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'zones.*.zone_type'    => ['nullable', Rule::in(self::ZONE_TYPES)],
            'zones.*.capacity'     => ['nullable', 'integer', 'min:1', 'max:100000'],
            'zones.*.total_rows'   => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_ROWS],
            'zones.*.seats_per_row'     => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_SEATS_PER_ROW],
            'zones.*.seat_number_start' => ['nullable', 'integer', 'min:1', 'max:999'],
            'zones.*._delete'      => ['nullable', 'boolean'],
        ];

        // วันปิดขายต้องไม่ก่อนวันเปิดขาย (เช็กเมื่อกรอกวันเปิดขายด้วยเท่านั้น)
        if ($request->filled('sale_start_datetime')) {
            $rules['sale_end_datetime'][] = 'after_or_equal:sale_start_datetime';
        }

        foreach (array_keys(self::IMAGE_COLUMNS) as $type) {
            $maxKB = self::IMAGE_SIZES[$type]['maxKB'];
            $rules[$type]            = ['nullable', 'image', 'mimes:jpeg,png,webp', "max:{$maxKB}"];
            $rules["remove_{$type}"] = ['nullable', 'boolean'];
        }

        $data = $request->validate($rules, $this->validationMessages(), $this->validationAttributes());

        // รหัสคอนเสิร์ตอยู่ในรหัสคำสั่งซื้อ/บัตรที่ออกไปแล้ว (ห้ามเปลี่ยนตามเอกสาร Public Code) จึงแก้ไม่ได้เมื่อมีการจองแล้ว
        if (strcasecmp((string) $data['concert_code'], (string) $concert->concert_code) !== 0 && $concert->bookings()->exists()) {
            return back()->withInput()->withErrors([
                'concert_code' => 'เปลี่ยนรหัสคอนเสิร์ตไม่ได้ เพราะคอนเสิร์ตนี้มีการจองแล้ว',
            ]);
        }

        // ตรวจเงื่อนไขของโซนก่อนเริ่มบันทึก (โซนที่มีการจองแล้ว แก้โครงสร้าง/ลบไม่ได้)
        [$zoneOps, $zoneErrors] = $this->planZoneChanges($concert, $data['zones'] ?? []);
        if ($zoneErrors) {
            return back()->withInput()->withErrors(['zones' => $zoneErrors]);
        }

        $newPaths    = [];   // ไฟล์ที่เพิ่งอัปโหลด (ลบทิ้งถ้าบันทึกล้มเหลว)
        $oldToDelete = [];   // ไฟล์เก่าที่ถูกแทนที่ (ลบหลังบันทึกสำเร็จ)

        try {
            DB::transaction(function () use ($request, $concert, $data, $zoneOps, &$newPaths, &$oldToDelete) {
                $concert->fill(Arr::only($data, [
                    'c_organization_id', 'concert_code', 'name', 'artist', 'venue', 'description',
                    'phone', 'email', 'facebook', 'line',
                    'event_datetime', 'sale_start_datetime', 'sale_end_datetime', 'max_ticket_per_order', 'status',
                ]));

                foreach (self::IMAGE_COLUMNS as $type => $column) {
                    if ($request->hasFile($type)) {
                        $path = $request->file($type)->store("concerts/{$concert->c_id}", 'public');
                        $newPaths[] = $path;
                        if ($concert->{$column}) {
                            $oldToDelete[] = $concert->{$column};
                        }
                        $concert->{$column} = $path;
                    } elseif ($request->boolean("remove_{$type}") && $concert->{$column}) {
                        $oldToDelete[] = $concert->{$column};
                        $concert->{$column} = null;
                    }
                }

                $concert->save();
                $this->applyZoneChanges($concert, $zoneOps);
            });
        } catch (\Throwable $e) {
            foreach ($newPaths as $path) {
                Storage::disk('public')->delete($path);
            }
            report($e);

            return back()->withInput()->with('error', 'บันทึกไม่สำเร็จ ไม่มีข้อมูลใดถูกเปลี่ยนแปลง กรุณาลองใหม่อีกครั้ง');
        }

        foreach ($oldToDelete as $path) {
            Storage::disk('public')->delete($path);
        }

        return redirect()
            ->route('manage.images', ['concert' => $concert->c_id])
            ->with('success', 'บันทึกข้อมูลคอนเสิร์ตเรียบร้อยแล้ว');
    }

    /**
     * upload(request, concertId, type)
     * อัปโหลดหรือเปลี่ยนรูปภาพของคอนเสิร์ตตามประเภท (poster, banner, seat_map)
     * param : request, concertId, type
     * return : RedirectResponse กลับหน้าเดิมพร้อมข้อความผลลัพธ์
     */
    public function upload(Request $request, string $concertId, string $type)
    {
        $concert = Concert::findOrFail($concertId);

        if (!isset(self::IMAGE_COLUMNS[$type])) {
            return back()->with('error', 'ประเภทรูปไม่ถูกต้อง');
        }

        $column = self::IMAGE_COLUMNS[$type];
        $maxKB  = self::IMAGE_SIZES[$type]['maxKB'];

        $request->validate([
            'image' => "required|image|mimes:jpeg,png,webp|max:{$maxKB}",
        ], [
            'image.required' => 'กรุณาเลือกไฟล์รูปภาพ',
            'image.image'    => 'ไฟล์ต้องเป็นรูปภาพเท่านั้น',
            'image.mimes'    => 'รองรับเฉพาะ JPEG, PNG, WEBP',
            'image.max'      => 'ขนาดไฟล์ต้องไม่เกิน ' . round($maxKB / 1024, 1) . ' MB',
        ]);

        // ลบรูปเก่า (ถ้ามี)
        if ($concert->{$column}) {
            Storage::disk('public')->delete($concert->{$column});
        }

        // บันทึกรูปใหม่
        $path = $request->file('image')->store("concerts/{$concertId}", 'public');
        $concert->{$column} = $path;
        $concert->save();

        return back()->with('success', 'อัปโหลดรูปสำเร็จ!');
    }

    /**
     * destroy(concertId, type)
     * ลบรูปภาพของคอนเสิร์ตตามประเภท (poster, banner, seat_map)
     * param : concertId, type
     * return : RedirectResponse กลับหน้าเดิมพร้อมข้อความผลลัพธ์
     */
    public function destroy(string $concertId, string $type)
    {
        $concert = Concert::findOrFail($concertId);

        if (!isset(self::IMAGE_COLUMNS[$type])) {
            return back()->with('error', 'ประเภทรูปไม่ถูกต้อง');
        }

        $column = self::IMAGE_COLUMNS[$type];

        if ($concert->{$column}) {
            Storage::disk('public')->delete($concert->{$column});
            $concert->{$column} = null;
            $concert->save();
        }

        return back()->with('success', 'ลบรูปสำเร็จ!');
    }

    // ==========================================
    // Zone helpers
    // ==========================================

    /**
     * buildZoneRows(concert, old)
     * สร้างข้อมูลโซนสำหรับฟอร์ม (ค่าจากฐานข้อมูล หรือค่าที่ผู้ใช้กรอกไว้เมื่อบันทึกไม่ผ่าน)
     * param : concert, old (ค่า zones ที่กรอกไว้จาก old input หรือ null)
     * return : array ของโซน (key, zone_type, name, price, color, capacity, ..., locked, sold, remove, is_new)
     */
    private function buildZoneRows(Concert $concert, ?array $old = null): array
    {
        $rows = [];

        foreach ($concert->zones()->orderBy('z_id')->get() as $zone) {
            $key   = (string) $zone->z_id;
            $input = $old[$key] ?? [];

            $rows[] = [
                'key'               => $key,
                'is_new'            => false,
                'zone_type'         => $zone->zone_type,
                'name'              => $input['name']              ?? $zone->name,
                'price'             => $input['price']             ?? (float) $zone->price,
                'color'             => $input['color']             ?? ($zone->color ?: self::DEFAULT_ZONE_COLOR),
                'capacity'          => $input['capacity']          ?? $zone->capacity,
                'total_rows'        => $input['total_rows']        ?? $zone->total_rows,
                'seats_per_row'     => $input['seats_per_row']     ?? $zone->seats_per_row,
                'seat_number_start' => $input['seat_number_start'] ?? $zone->seat_number_start,
                'locked'            => $this->zoneHasActivity($zone),
                'sold'              => $this->soldTickets($zone),
                'remove'            => !empty($input['_delete']),
            ];
        }

        // โซนใหม่ที่ยังไม่ได้บันทึก (กรณีบันทึกไม่ผ่านแล้วกลับมาแก้)
        foreach ($old ?? [] as $key => $input) {
            if (!str_starts_with((string) $key, 'new_') || !empty($input['_delete'])) {
                continue;
            }
            $rows[] = [
                'key'               => (string) $key,
                'is_new'            => true,
                'zone_type'         => $input['zone_type'] ?? Zone::TYPE_SEATED,
                'name'              => $input['name'] ?? '',
                'price'             => $input['price'] ?? '',
                'color'             => $input['color'] ?? self::DEFAULT_ZONE_COLOR,
                'capacity'          => $input['capacity'] ?? '',
                'total_rows'        => $input['total_rows'] ?? '',
                'seats_per_row'     => $input['seats_per_row'] ?? '',
                'seat_number_start' => $input['seat_number_start'] ?? 1,
                'locked'            => false,
                'sold'              => 0,
                'remove'            => false,
            ];
        }

        return $rows;
    }

    /**
     * zoneHasActivity(zone)
     * ตรวจว่าโซนมีการจอง/ขายตั๋ว/ล็อกที่นั่งแล้วหรือไม่ (ถ้ามี ห้ามลบโซนและห้ามแก้โครงสร้างที่นั่ง)
     * schema 3.4.2: booking อ้างถึงโซนโดยตรง (b_zone_id) โซนที่เคยมีการจองจึงลบไม่ได้ ไม่งั้น foreign key จะไม่ยอม
     * param : zone
     * return : bool
     */
    private function zoneHasActivity(Zone $zone): bool
    {
        if ($zone->tickets()->exists() || $zone->bookings()->exists()) {
            return true;
        }
        if ($zone->isStanding()) {
            return false;
        }

        return $zone->seats()->where('seat_booking_status', '!=', Seat::STATUS_AVAILABLE)->exists();
    }

    /**
     * soldTickets(zone)
     * จำนวนตั๋วที่ยังใช้ได้ (status = valid) ของโซน
     * param : zone
     * return : int
     */
    private function soldTickets(Zone $zone): int
    {
        return $zone->tickets()->where('status', Ticket::STATUS_VALID)->count();
    }

    /**
     * planZoneChanges(concert, zonesInput)
     * ตรวจและวางแผนการเปลี่ยนแปลงโซน (สร้าง/แก้/ลบ) ก่อนบันทึกจริง
     * param : concert, zonesInput (zones จากฟอร์ม)
     * return : array [ops, errors] — ops = รายการคำสั่ง, errors = ข้อความผิดพลาด (ว่างถ้าผ่าน)
     */
    private function planZoneChanges(Concert $concert, array $zonesInput): array
    {
        $existing = $concert->zones()->get()->keyBy('z_id');
        $ops      = [];
        $errors   = [];

        foreach ($zonesInput as $key => $z) {
            $key    = (string) $key;
            $label  = 'โซน "' . ($z['name'] ?? '') . '"';
            $delete = !empty($z['_delete']);

            if (str_starts_with($key, 'new_')) {
                if ($delete) {
                    continue;
                }
                $type = $z['zone_type'] ?? null;
                if (!in_array($type, self::ZONE_TYPES, true)) {
                    $errors[] = "{$label}: ประเภทโซนไม่ถูกต้อง";
                    continue;
                }
                $errors = array_merge($errors, $this->checkZoneSize($type, $z, $label));
                $ops[]  = ['op' => 'create', 'zone' => null, 'data' => $z, 'type' => $type, 'locked' => false];
                continue;
            }

            $zone = ctype_digit($key) ? $existing->get((int) $key) : null;
            if (!$zone) {
                continue;   // ไม่ใช่โซนของคอนเสิร์ตนี้ ข้าม
            }

            $locked = $this->zoneHasActivity($zone);

            if ($delete) {
                if ($locked) {
                    $errors[] = "ลบ{$label}ไม่ได้ เพราะมีการจองหรือขายตั๋วแล้ว";
                } else {
                    $ops[] = ['op' => 'delete', 'zone' => $zone, 'data' => $z, 'type' => $zone->zone_type, 'locked' => false];
                }
                continue;
            }

            if ($zone->isStanding()) {
                // จำนวนที่ใช้ไปแล้ว = ที่จองรอชำระ (held) + ที่ขายแล้ว (sold) ใน zone_inventory
                $inventory = $zone->inventory;
                $used = $inventory ? (int) $inventory->held + (int) $inventory->sold : $this->soldTickets($zone);
                $cap  = (int) ($z['capacity'] ?? 0);
                if ($cap < 1) {
                    $errors[] = "{$label}: กรุณากรอกจำนวนที่รองรับ";
                } elseif ($cap < $used) {
                    $errors[] = "{$label}: จำนวนที่รองรับต้องไม่น้อยกว่าตั๋วที่ขายและจองไว้แล้ว ({$used} ใบ)";
                }
            } elseif (!$locked) {
                $errors = array_merge($errors, $this->checkZoneSize(Zone::TYPE_SEATED, $z, $label));
            }

            $ops[] = ['op' => 'update', 'zone' => $zone, 'data' => $z, 'type' => $zone->zone_type, 'locked' => $locked];
        }

        return [$ops, $errors];
    }

    /**
     * checkZoneSize(type, z, label)
     * ตรวจข้อมูลขนาดโซนตามประเภท (ยืน = capacity, ที่นั่ง = จำนวนแถว × ที่นั่งต่อแถว)
     * param : type, z (ข้อมูลโซนจากฟอร์ม), label (ชื่อโซนสำหรับข้อความ)
     * return : array ของข้อความผิดพลาด
     */
    private function checkZoneSize(string $type, array $z, string $label): array
    {
        if ($type === Zone::TYPE_STANDING) {
            return empty($z['capacity']) ? ["{$label}: กรุณากรอกจำนวนที่รองรับ"] : [];
        }

        $rows   = (int) ($z['total_rows'] ?? 0);
        $perRow = (int) ($z['seats_per_row'] ?? 0);
        if ($rows < 1 || $perRow < 1) {
            return ["{$label}: กรุณากรอกจำนวนแถวและจำนวนที่นั่งต่อแถว"];
        }
        if ($rows * $perRow > self::MAX_SEATS_PER_ZONE) {
            return ["{$label}: จำนวนที่นั่งรวมต้องไม่เกิน " . number_format(self::MAX_SEATS_PER_ZONE) . " ที่นั่ง"];
        }

        return [];
    }

    /**
     * applyZoneChanges(concert, ops)
     * บันทึกการเปลี่ยนแปลงโซนตามแผนที่ตรวจแล้ว (เรียกภายใน transaction)
     * param : concert, ops
     * return : void
     */
    private function applyZoneChanges(Concert $concert, array $ops): void
    {
        foreach ($ops as $op) {
            $z    = $op['data'];
            $zone = $op['zone'];

            if ($op['op'] === 'delete') {
                Seat::where('s_zone_id', $zone->z_id)->delete();
                ZoneInventory::whereKey($zone->z_id)->delete();
                $zone->delete();
                continue;
            }

            $attrs = [
                'name'  => trim($z['name']),
                'price' => $z['price'],
                'color' => $z['color'] ?: self::DEFAULT_ZONE_COLOR,
            ];

            if ($op['type'] === Zone::TYPE_STANDING) {
                $attrs['capacity'] = (int) $z['capacity'];
                if ($op['op'] === 'create') {
                    $zone = Zone::create($attrs + [
                        'z_concert_id'      => $concert->c_id,
                        'zone_type'         => Zone::TYPE_STANDING,
                        'seat_number_start' => 1,
                    ]);
                    // โซนยืนต้องมีแถวตัวนับใน zone_inventory (held = จองรอชำระ, sold = ขายแล้ว)
                    ZoneInventory::create(['zi_zone_id' => $zone->z_id, 'held' => 0, 'sold' => 0]);
                } else {
                    $zone->update($attrs);
                }
                continue;
            }

            // โซนที่นั่ง: แก้โครงสร้างได้เฉพาะโซนใหม่ หรือโซนที่ยังไม่มีการจอง
            if ($op['op'] === 'create' || !$op['locked']) {
                $rows   = (int) $z['total_rows'];
                $perRow = (int) $z['seats_per_row'];
                $start  = (int) (($z['seat_number_start'] ?? null) ?: 1);

                $attrs += [
                    'total_rows'        => $rows,
                    'seats_per_row'     => $perRow,
                    'seat_number_start' => $start,
                    'capacity'          => $rows * $perRow,
                ];
            }

            if ($op['op'] === 'create') {
                $zone = Zone::create($attrs + [
                    'z_concert_id' => $concert->c_id,
                    'zone_type'    => Zone::TYPE_SEATED,
                ]);
                $this->generateSeats($zone, $attrs['total_rows'], $attrs['seats_per_row'], $attrs['seat_number_start']);
                continue;
            }

            $layoutChanged = isset($attrs['total_rows']) && (
                (int) $zone->total_rows           !== $attrs['total_rows']
                || (int) $zone->seats_per_row     !== $attrs['seats_per_row']
                || (int) $zone->seat_number_start !== $attrs['seat_number_start']
            );

            $zone->update($attrs);

            if ($layoutChanged) {
                Seat::where('s_zone_id', $zone->z_id)->delete();
                $this->generateSeats($zone, $attrs['total_rows'], $attrs['seats_per_row'], $attrs['seat_number_start']);
            }
        }
    }

    /**
     * generateSeats(zone, rows, perRow, start)
     * สร้างที่นั่งของโซน แถวชื่อ A, B, C… เลขที่นั่งเริ่มจากค่าที่กำหนด
     * param : zone, rows, perRow, start
     * return : void
     */
    private function generateSeats(Zone $zone, int $rows, int $perRow, int $start): void
    {
        $batch = [];

        for ($r = 1; $r <= $rows; $r++) {
            for ($n = 0; $n < $perRow; $n++) {
                $batch[] = [
                    's_zone_id'           => $zone->z_id,
                    'row'                 => chr(64 + $r),
                    'number'              => $start + $n,
                    'seat_booking_status' => Seat::STATUS_AVAILABLE,
                    'seat_status'         => Seat::SEAT_ACTIVE,
                ];
            }
        }

        foreach (array_chunk($batch, 500) as $chunk) {
            Seat::insert($chunk);
        }
    }

    // ==========================================
    // Validation text
    // ==========================================

    /**
     * validationMessages()
     * ข้อความแจ้งเตือนภาษาไทยของฟอร์มแก้ไขคอนเสิร์ต
     * param : -
     * return : array
     */
    private function validationMessages(): array
    {
        return [
            'required'     => 'กรุณากรอก :attribute',
            'string'       => ':attribute ไม่ถูกต้อง',
            'max.string'   => ':attribute ต้องไม่เกิน :max ตัวอักษร',
            'max.numeric'  => ':attribute ต้องไม่เกิน :max',
            'min.numeric'  => ':attribute ต้องไม่น้อยกว่า :min',
            'max.file'     => ':attribute ต้องมีขนาดไม่เกิน :max KB',
            'integer'      => ':attribute ต้องเป็นจำนวนเต็ม',
            'numeric'      => ':attribute ต้องเป็นตัวเลข',
            'date'         => ':attribute รูปแบบวันที่ไม่ถูกต้อง',
            'in'           => ':attribute ไม่ถูกต้อง',
            'exists'       => ':attribute ไม่ถูกต้อง',
            'email'        => ':attribute รูปแบบอีเมลไม่ถูกต้อง',
            'unique'       => ':attribute นี้ถูกใช้แล้ว',
            'regex'        => ':attribute ไม่ถูกต้อง',
            'image'        => ':attribute ต้องเป็นไฟล์รูปภาพ',
            'mimes'        => ':attribute รองรับเฉพาะ JPEG, PNG, WEBP',
            'sale_end_datetime.after_or_equal' => 'วันปิดขายต้องไม่ก่อนวันเปิดขาย',
            'concert_code.regex' => 'รหัสคอนเสิร์ตต้องเป็นตัวอักษรภาษาอังกฤษ 1 ตัวตามด้วยตัวเลข 1–6 หลัก เช่น C000024',
        ];
    }

    /**
     * validationAttributes()
     * ชื่อฟิลด์ภาษาไทยที่ใช้แทน :attribute ในข้อความแจ้งเตือน
     * param : -
     * return : array
     */
    private function validationAttributes(): array
    {
        return [
            'c_organization_id'    => 'ผู้จัด',
            'concert_code'         => 'รหัสคอนเสิร์ต',
            'name'                 => 'ชื่อคอนเสิร์ต',
            'artist'               => 'ศิลปิน',
            'venue'                => 'สถานที่',
            'description'          => 'รายละเอียด',
            'phone'                => 'เบอร์โทรติดต่อ',
            'email'                => 'อีเมลติดต่อ',
            'facebook'             => 'Facebook',
            'line'                 => 'LINE',
            'event_datetime'       => 'วัน-เวลาแสดง',
            'sale_start_datetime'  => 'วันเปิดขาย',
            'sale_end_datetime'    => 'วันปิดขาย',
            'max_ticket_per_order' => 'จำนวนตั๋วสูงสุดต่อคำสั่งซื้อ',
            'status'               => 'สถานะ',
            'poster'               => 'รูปโปสเตอร์',
            'banner'               => 'รูปแบนเนอร์',
            'seat_map'             => 'รูปผังที่นั่ง',
            'zones.*.name'         => 'ชื่อโซน',
            'zones.*.price'        => 'ราคาโซน',
            'zones.*.color'        => 'สีโซน',
            'zones.*.capacity'     => 'จำนวนที่รองรับ',
            'zones.*.total_rows'   => 'จำนวนแถว',
            'zones.*.seats_per_row'     => 'จำนวนที่นั่งต่อแถว',
            'zones.*.seat_number_start' => 'เลขที่นั่งเริ่มต้น',
        ];
    }
}
