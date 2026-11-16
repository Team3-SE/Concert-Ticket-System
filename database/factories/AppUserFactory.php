<?php

namespace Database\Factories;

use App\Models\AppUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<AppUser>
 */
class AppUserFactory extends Factory
{
    protected $model = AppUser::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // schema 3.4.2: u_id เป็น AUTO_INCREMENT ไม่ต้องกำหนดเอง, ลูกค้าทั่วไปใช้ role 'USER'
        return [
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => static::$password ??= Hash::make('password'),
            'role' => AppUser::ROLE_CUSTOMER,
            'account_status' => AppUser::STATUS_ACTIVE,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'created_at' => now(),
        ];
    }
}
