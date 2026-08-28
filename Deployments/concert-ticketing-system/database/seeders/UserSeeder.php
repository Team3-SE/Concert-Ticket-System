<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * UserSeeder
 * seed users of all roles: admin, organizer, staff, customer
 * Author : Squad A (Auth)
 * Created : 28-08-2569
 */
class UserSeeder extends Seeder
{
    /**
     * run()
     * create sample users with their corresponding role-specific records
     * param : none
     * return : void
     */
    public function run(): void
    {
        $companies = DB::table('cts_companies')->get()->keyBy('initials');
        $gmmId     = $companies['GMM']->company_id;
        $becId     = $companies['BEC']->company_id;

        // --- Admins ---
        foreach ([
            ['à¸ªà¸¡à¸Šà¸²à¸¢ à¸”à¸¹à¹à¸¥à¸£à¸°à¸šà¸š',  'admin1@concert.local'],
            ['à¸ªà¸¡à¸«à¸à¸´à¸‡ à¸”à¸¹à¹à¸¥à¸£à¸°à¸šà¸š', 'admin2@concert.local'],
        ] as [$name, $email]) {
            $user = $this->makeUser($name, $email, 'admin');
            DB::table('cts_users')->insert($user);
            DB::table('cts_admins')->insert([
                'admin_id'   => $user['user_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // --- Organizers ---
        foreach ([
            ['à¸›à¸´à¸¢à¸° à¸ˆà¸±à¸”à¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸•', 'organizer1@gmm.local', $gmmId],
            ['à¸§à¸²à¸£à¸µ à¸ˆà¸±à¸”à¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸•', 'organizer2@bec.local', $becId],
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

        // --- Staff ---
        foreach ([
            ['à¸ªà¸¡à¸¨à¸±à¸à¸”à¸´à¹Œ à¸•à¸£à¸§à¸ˆà¸•à¸±à¹‹à¸§', 'staff1@gmm.local', $gmmId, '0811111101'],
            ['à¸¡à¸²à¸¥à¸µ à¸•à¸£à¸§à¸ˆà¸•à¸±à¹‹à¸§',     'staff2@gmm.local', $gmmId, '0811111102'],
            ['à¸Šà¸±à¸¢ à¸•à¸£à¸§à¸ˆà¸•à¸±à¹‹à¸§',      'staff3@bec.local', $becId, '0811111103'],
            ['à¸™à¸´à¸•à¸¢à¸² à¸•à¸£à¸§à¸ˆà¸•à¸±à¹‹à¸§',    'staff4@bec.local', $becId, '0811111104'],
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

        // --- Customers ---
        foreach ([
            ['à¸ªà¸¸à¸”à¸² à¸‹à¸·à¹‰à¸­à¸•à¸±à¹‹à¸§',    'customer1@example.com',  '0812345001'],
            ['à¸§à¸´à¸Šà¸±à¸¢ à¸‹à¸·à¹‰à¸­à¸•à¸±à¹‹à¸§',   'customer2@example.com',  '0812345002'],
            ['à¸™à¸ à¸² à¸‹à¸·à¹‰à¸­à¸•à¸±à¹‹à¸§',     'customer3@example.com',  '0812345003'],
            ['à¸à¸¤à¸•à¸¢à¹Œ à¸‹à¸·à¹‰à¸­à¸•à¸±à¹‹à¸§',  'customer4@example.com',  '0812345004'],
            ['à¸­à¸£à¸¸à¸“ à¸‹à¸·à¹‰à¸­à¸•à¸±à¹‹à¸§',    'customer5@example.com',  '0812345005'],
            ['à¸¥à¸”à¸² à¸‹à¸·à¹‰à¸­à¸•à¸±à¹‹à¸§',     'customer6@example.com',  '0812345006'],
            ['à¸˜à¸™à¸² à¸‹à¸·à¹‰à¸­à¸•à¸±à¹‹à¸§',     'customer7@example.com',  '0812345007'],
            ['à¸žà¸´à¸¡à¸žà¹Œ à¸‹à¸·à¹‰à¸­à¸•à¸±à¹‹à¸§',   'customer8@example.com',  '0812345008'],
            ['à¸ªà¸£à¸§à¸´à¸¨ à¸‹à¸·à¹‰à¸­à¸•à¸±à¹‹à¸§',   'customer9@example.com',  '0812345009'],
            ['à¸­à¸±à¸à¸Šà¸¥à¸µ à¸‹à¸·à¹‰à¸­à¸•à¸±à¹‹à¸§', 'customer10@example.com', '0812345010'],
        ] as [$name, $email, $phone]) {
            $user = $this->makeUser($name, $email, 'customer', $phone);
            DB::table('cts_users')->insert($user);
            DB::table('cts_customers')->insert([
                'customer_id' => $user['user_id'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $this->command->info('  âœ“ UserSeeder: 2 admins, 2 organizers, 4 staff, 10 customers');
        $this->command->info('    Default password: Password123!');
    }

    /**
     * makeUser(name, email, role, phone)
     * build a user data array with bcrypt-hashed password
     * param : name, email, role, phone
     * return : array
     */
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
