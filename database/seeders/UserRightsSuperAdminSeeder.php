<?php

namespace Database\Seeders;

use App\Enums\Enums\UserRole;
use App\Enums\UserRole as EnumsUserRole;
use App\Models\User;
use App\Models\UserRights;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserRightsSuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadmin = User::select('id')->whereRole(EnumsUserRole::SuperAdmin)->first();

        UserRights::create([
            'id_user' => $superadmin->id,
            'id_menu' => 1, // Dashboard
        ]);

        UserRights::create([
            'id_user' => $superadmin->id,
            'id_menu' => 2, // Cabang
            'actions' => ['view', 'create', 'edit', 'delete']
        ]);
    }
}
