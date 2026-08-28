<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * CompanySeeder
 * seed initial concert organizer companies for development
 * Author : Squad F (Infra)
 * Created : 28-08-2569
 */
class CompanySeeder extends Seeder
{
    /**
     * run()
     * insert sample company records into cts_companies
     * param : none
     * return : void
     */
    public function run(): void
    {
        $companies = [
            [
                'company_id'  => (string) Str::uuid(),
                'name'        => 'GMM Grammy',
                'initials'    => 'GMM',
                'description' => 'à¸šà¸£à¸´à¸©à¸±à¸—à¸šà¸±à¸™à¹€à¸—à¸´à¸‡à¸Šà¸±à¹‰à¸™à¸™à¸³à¸‚à¸­à¸‡à¹„à¸—à¸¢ à¸œà¸¹à¹‰à¸ˆà¸±à¸”à¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸•à¸£à¸°à¸”à¸±à¸šà¸›à¸£à¸°à¹€à¸—à¸¨',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'company_id'  => (string) Str::uuid(),
                'name'        => 'BEC-Tero Entertainment',
                'initials'    => 'BEC',
                'description' => 'à¸œà¸¹à¹‰à¸™à¸³à¸”à¹‰à¸²à¸™à¸à¸²à¸£à¸ˆà¸±à¸”à¸„à¸­à¸™à¹€à¸ªà¸´à¸£à¹Œà¸•à¹à¸¥à¸° entertainment event à¸£à¸°à¸”à¸±à¸šà¸™à¸²à¸™à¸²à¸Šà¸²à¸•à¸´',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        DB::table('cts_companies')->insert($companies);
        $this->command->info('  âœ“ CompanySeeder: ' . count($companies) . ' companies');
    }
}
