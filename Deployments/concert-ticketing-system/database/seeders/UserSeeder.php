<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $companies = DB::table('cts_companies')->get()->keyBy('initials');
        $gmmId     = $companies['GMM']->company_id;
        $becId     = $companies['BEC']->company_id;

        foreach ([
            ['สมชาย ดูแลระบบ',  'admin1@concert.local'],
            ['สมหญิง ดูแลระบบ', 'admin2@concert.local'],
        ] as [$name, $email]) {
            $user = $this->makeUser($name, $email, 'admin');
            DB::table('cts_users')->insert($user);
            DB::table('cts_admins')->insert([
                'admin_id'   => $user['user_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ([
            ['ปิยะ จัดคอนเสิร์ต', 'organizer1@gmm.local', $gmmId],
            ['วารี จัดคอนเสิร์ต', 'organizer2@bec.local', $becId],
        ] as [$name, $email, $companyId]) {
            $user = $this->makeUser($name, $email, 'organizer');
            DB::table('cts_users')->insert($user);
            DB::table('cts_event_organizers')->insert([
                'organizer_id' => $user['user_id'],
                'company_id'   => $companyId,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        foreach ([
            ['สมศักดิ์ ตรวจตั๋ว', 'staff1@gmm.local', $gmmId, '0811111101'],
            ['มาลี ตรวจตั๋ว',     'staff2@gmm.local', $gmmId, '0811111102'],
            ['ชัย ตรวจตั๋ว',      'staff3@bec.local', $becId, '0811111103'],
            ['นิตยา ตรวจตั๋ว',    'staff4@bec.local', $becId, '0811111104'],
        ] as [$name, $email, $companyId, $phone]) {
            $user = $this->makeUser($name, $email, 'staff', $phone);
            DB::table('cts_users')->insert($user);
            DB::table('cts_staff')->insert([
                'staff_id'   => $user['user_id'],
                'company_id' => $companyId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ([
            ['สุดา ซื้อตั๋ว',    'customer1@example.com',  '0812345001'],
            ['วิชัย ซื้อตั๋ว',   'customer2@example.com',  '0812345002'],
            ['นภา ซื้อตั๋ว',     'customer3@example.com',  '0812345003'],
            ['กฤตย์ ซื้อตั๋ว',  'customer4@example.com',  '0812345004'],
            ['อรุณ ซื้อตั๋ว',    'customer5@example.com',  '0812345005'],
            ['ลดา ซื้อตั๋ว',     'customer6@example.com',  '0812345006'],
            ['ธนา ซื้อตั๋ว',     'customer7@example.com',  '0812345007'],
            ['พิมพ์ ซื้อตั๋ว',   'customer8@example.com',  '0812345008'],
            ['สรวิศ ซื้อตั๋ว',   'customer9@example.com',  '0812345009'],
            ['อักษลี ซื้อตั๋ว', 'customer10@example.com', '0812345010'],
        ] as [$name, $email, $phone]) {
            $user = $this->makeUser($name, $email, 'customer', $phone);
            DB::table('cts_users')->insert($user);
            DB::table('cts_customers')->insert([
                'customer_id' => $user['user_id'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $this->command->info('  ✔ UserSeeder: 2 admins, 2 organizers, 4 staff, 10 customers');
    }

    private function makeUser(string $name, string $email, string $role, ?string $phone = null): array
    {
        return [
            'user_id'       => (string) Str::uuid(),
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone,
            'password_hash' => Hash::make('Password123!'),
            'role'          => $role,
            'is_banned'     => false,
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
    }
}
