<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * ConcertSeeder
 * seed sample concerts with zones, seats, and staff assignments
 * Author : Squad B (Concert)
 * Created : 28-08-2569
 */
class ConcertSeeder extends Seeder
{
    /**
     * run()
     * create 3 concerts with zones, seats, and staff assignments
     * param : none
     * return : void
     */
    public function run(): void
    {
        $companies  = DB::table('cts_companies')->get()->keyBy('initials');
        $organizers = DB::table('cts_event_organizers')->get();
        $allStaff   = DB::table('cts_staff')->get();

        $gmmId       = $companies['GMM']->company_id;
        $becId       = $companies['BEC']->company_id;
        $gmmOrg      = $organizers->where('company_id', $gmmId)->first();
        $becOrg      = $organizers->where('company_id', $becId)->first();
        $gmmStaffIds = $allStaff->where('company_id', $gmmId)->pluck('staff_id')->toArray();
        $becStaffIds = $allStaff->where('company_id', $becId)->pluck('staff_id')->toArray();

        $definitions = [
            [
                'concert_id'   => (string) Str::uuid(),
                'company_id'   => $gmmId,
                'organizer_id' => $gmmOrg->organizer_id,
                'name'         => 'Rock Night 2026',
                'artist'       => 'Bodyslam',
                'date_time'    => '2026-11-01 18:00:00',
                'venue'        => 'BCC Hall, Bangkok',
                '_staff'       => $gmmStaffIds,
                '_zones'       => [
                    ['name' => 'VIP',    'price' => 3000.00, 'rows' => 3, 'cols' => 5],
                    ['name' => 'Zone A', 'price' => 1500.00, 'rows' => 5, 'cols' => 10],
                    ['name' => 'Zone B', 'price' => 800.00,  'rows' => 5, 'cols' => 20],
                ],
            ],
            [
                'concert_id'   => (string) Str::uuid(),
                'company_id'   => $becId,
                'organizer_id' => $becOrg->organizer_id,
                'name'         => 'Pop Star Live 2026',
                'artist'       => 'Bird Thongchai',
                'date_time'    => '2026-12-15 19:00:00',
                'venue'        => 'Impact Arena, Muang Thong Thani',
                '_staff'       => $becStaffIds,
                '_zones'       => [
                    ['name' => 'VIP',    'price' => 5000.00, 'rows' => 3, 'cols' => 5],
                    ['name' => 'Zone A', 'price' => 2500.00, 'rows' => 5, 'cols' => 10],
                    ['name' => 'Zone B', 'price' => 1200.00, 'rows' => 5, 'cols' => 20],
                ],
            ],
            [
                'concert_id'   => (string) Str::uuid(),
                'company_id'   => $gmmId,
                'organizer_id' => $gmmOrg->organizer_id,
                'name'         => 'Jazz Festival 2026',
                'artist'       => 'Various Artists',
                'date_time'    => '2027-01-20 17:00:00',
                'venue'        => 'Lumpini Park Amphitheatre, Bangkok',
                '_staff'       => $gmmStaffIds,
                '_zones'       => [
                    ['name' => 'Premium', 'price' => 2000.00, 'rows' => 4, 'cols' => 8],
                    ['name' => 'General', 'price' => 1000.00, 'rows' => 6, 'cols' => 15],
                ],
            ],
        ];

        $totalSeats = 0;

        foreach ($definitions as $def) {
            $zonesConfig = $def['_zones'];
            $staffIds    = $def['_staff'];
            unset($def['_zones'], $def['_staff']);

            DB::table('cts_concerts')->insert([...$def, 'created_at' => now(), 'updated_at' => now()]);

            foreach ($staffIds as $staffId) {
                DB::table('cts_staff_concerts')->insert([
                    'staff_id' => $staffId, 'concert_id' => $def['concert_id'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            foreach ($zonesConfig as $z) {
                $capacity = $z['rows'] * $z['cols'];
                $zoneId   = (string) Str::uuid();
                DB::table('cts_zones')->insert([
                    'zone_id'    => $zoneId, 'concert_id' => $def['concert_id'],
                    'name'       => $z['name'], 'price' => $z['price'], 'capacity' => $capacity,
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                $seats = [];
                for ($r = 0; $r < $z['rows']; $r++) {
                    $row = chr(ord('A') + $r);
                    for ($n = 1; $n <= $z['cols']; $n++) {
                        $seats[] = [
                            'seat_id' => (string) Str::uuid(), 'zone_id' => $zoneId,
                            'row' => $row, 'number' => $n, 'status' => 'available',
                            'created_at' => now(), 'updated_at' => now(),
                        ];
                    }
                }
                DB::table('cts_seats')->insert($seats);
                $totalSeats += count($seats);
            }
        }

        $this->command->info('  âœ“ ConcertSeeder: ' . count($definitions) . " concerts, {$totalSeats} seats");
    }
}
