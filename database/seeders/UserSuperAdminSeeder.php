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
        User::create([
            'name' => 'Ibnu Abbas',
            'email' => 'office@binabbas.org',
            'phone' => '085212223424',
            'password' => '12345678',
            'role' => EnumsUserRole::SuperAdmin,
        ]);
    }
}
