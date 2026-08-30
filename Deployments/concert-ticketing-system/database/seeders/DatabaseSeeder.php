<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder
 * main seeder orchestrating all table seeders in dependency order
 * Author : Squad F (Infra)
 * Created : 28-08-2569
 */
class DatabaseSeeder extends Seeder
{
    /**
     * run()
     * call all seeders in order: companies -> users -> concerts -> bookings
     * param : none
     * return : void
     */
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            UserSeeder::class,
            ConcertSeeder::class,
            BookingSeeder::class,
        ]);
    }
}
