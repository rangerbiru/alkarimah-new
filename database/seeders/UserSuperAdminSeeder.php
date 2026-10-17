<?php

namespace Database\Seeders;

use App\Enums\Enums\UserRole;
use App\Enums\UserRole as EnumsUserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::forceCreate([
            'name' => 'Al-Karimah',
            'email' => 'office@alkarimah.org',
            'phone' => '085212223424',
            'password' => bcrypt('12345678'),
            'role' => EnumsUserRole::SuperAdmin,
        ]);

        User::forceCreate([
            'name' => 'Admin Al-Karimah',
            'email' => 'admin@alkarimah.org',
            'phone' => '085212223424',
            'password' => bcrypt('12345678'),
            'role' => EnumsUserRole::Admin,
        ]);

        User::forceCreate([
            'name' => 'Ponpes Al-Karimah',
            'email' => 'alkarimahponpes@gmail.com',
            'phone' => '085212223424',
            'password' => bcrypt('12345678'),
            'role' => EnumsUserRole::Admin,
        ]);
    }
}
