<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Role Kasir Unit tidak boleh mengakses menu Pending (finance.transaction.pending).
 *
 * Memakai DB app_alkarimah_testing dengan DatabaseTransactions.
 */
class KasirUnitPendingAccessTest extends TestCase
{
    use DatabaseTransactions;

    private const BRANCH_ID = 990002;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('app_alkarimah_testing', DB::connection()->getDatabaseName());
    }

    private function createUser(string $role): User
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

        return User::withoutGlobalScopes()->findOrFail($id);
    }

    public function test_kasir_unit_is_redirected_from_pending_page(): void
    {
        $user = $this->createUser('kasir-unit');

        $this->actingAs($user)->get(route('finance.transaction.pending'))
            ->assertRedirect(route('base'));

        $this->actingAs($user)->post(route('finance.transaction.datatable.pending'))
            ->assertRedirect(route('base'));
    }

    public function test_kasir_unit_still_reaches_other_transaction_pages(): void
    {
        $user = $this->createUser('kasir-unit');

        $response = $this->actingAs($user)->get(route('finance.transaction.history'));

        $this->assertNotSame(route('base'), $response->headers->get('Location'));
    }

    public function test_kasir_can_still_open_pending_page(): void
    {
        $user = $this->createUser('kasir');

        $response = $this->actingAs($user)->get(route('finance.transaction.pending'));

        $this->assertNotSame(route('base'), $response->headers->get('Location'));
    }

    public function test_migration_removes_pending_rights_only_for_kasir_unit(): void
    {
        $menuId = DB::table('menu')->insertGetId([
            'name' => 'Pending', 'route' => json_encode(['name' => 'finance.transaction.pending']),
            'group' => 'keuangan', 'sort' => 1, 'is_parent' => 0, 'is_sidebar' => 1,
        ]);
        $otherMenuId = DB::table('menu')->insertGetId([
            'name' => 'Riwayat', 'route' => json_encode(['name' => 'finance.transaction.history']),
            'group' => 'keuangan', 'sort' => 2, 'is_parent' => 0, 'is_sidebar' => 1,
        ]);

        $unit = $this->createUser('kasir-unit');
        $kasir = $this->createUser('kasir');

        foreach ([$unit, $kasir] as $u) {
            foreach ([$menuId, $otherMenuId] as $m) {
                DB::table('user_rights')->insert(['id_user' => $u->id, 'id_menu' => $m, 'is_parent' => 0, 'created_by' => 0]);
            }
        }

        (require database_path('migrations/2026_10_13_000100_remove_pending_menu_rights_from_kasir_unit.php'))->up();

        $this->assertDatabaseMissing('user_rights', ['id_user' => $unit->id, 'id_menu' => $menuId]);
        $this->assertDatabaseHas('user_rights', ['id_user' => $unit->id, 'id_menu' => $otherMenuId]);
        $this->assertDatabaseHas('user_rights', ['id_user' => $kasir->id, 'id_menu' => $menuId]);
    }
}
