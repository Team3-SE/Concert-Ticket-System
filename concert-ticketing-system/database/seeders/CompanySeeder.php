<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement("SET FOREIGN_KEY_CHECKS=0;");
        DB::table("cts_companies")->truncate();
        DB::statement("SET FOREIGN_KEY_CHECKS=1;");

        $companies = [
            [
                "company_id"  => (string) Str::uuid(),
                "name"        => "GMM Grammy",
                "initials"    => "GMM",
                "description" => "บริษัทบันเทิงชั้นนำของไทย ผู้จัดคอนเสิร์ตระดับประเทศ",
                "created_at"  => now(),
                "updated_at"  => now(),
            ],
            [
                "company_id"  => (string) Str::uuid(),
                "name"        => "BEC-Tero Entertainment",
                "initials"    => "BEC",
                "description" => "ผู้นำด้านการจัดคอนเสิร์ตและ entertainment event ระดับนานาชาติ",
                "created_at"  => now(),
                "updated_at"  => now(),
            ],
        ];

        DB::table("cts_companies")->insert($companies);
        $this->command->info("  ✔ CompanySeeder: " . count($companies) . " companies");
    }
}
