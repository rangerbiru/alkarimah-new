<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Dasar test hak akses modul pegawai. Memakai DB app_alkarimah_testing
 * dengan DatabaseTransactions.
 */
abstract class ModuleAccessTestCase extends TestCase
{
    use DatabaseTransactions;

    protected const BRANCH_ID = 990003;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('app_alkarimah_testing', DB::connection()->getDatabaseName());
    }

    /**
     * @param  array<int, int>  $modules  ID modul yang dipegang
     * @param  array<int, string>  $levels  jenjang (tanpa modul)
     */
    protected function createUser(string $role, array $modules = [], array $levels = []): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Test '.$role,
            'password' => bcrypt('secret'),
            'role' => $role,
            'phone' => '08'.random_int(100000000, 999999999),
            'gender' => 'male',
            'branch_id' => self::BRANCH_ID,
            'created_by' => 0,
        ]);

        foreach ($modules as $module) {
            DB::table('module_rights')->insert(['id_module' => $module, 'id_user' => $id]);
        }

        foreach ($levels as $level) {
            DB::table('user_education_levels')->insert(['user_id' => $id, 'level_education' => $level]);
        }

        return User::withoutGlobalScopes()->findOrFail($id);
    }
}
