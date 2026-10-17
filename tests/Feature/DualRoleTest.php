<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Parents;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Pegawai yang juga orang tua: satu akun, menu digabung. Request ke route khusus orang tua
 * dijalankan sebagai orang tua (override role hanya di memori).
 */
class DualRoleTest extends ModuleAccessTestCase
{
    private function linkParent(User $user, bool $withChild = true): Parents
    {
        $parentId = DB::table('parent')->insertGetId([
            'id_user' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'gender' => 'male',
            'branch_id' => self::BRANCH_ID,
            'created_by' => 0,
        ]);

        if ($withChild) {
            $classId = DB::table('class')->insertGetId([
                'id_wali_kelas' => $user->id, 'name' => 'Kelas Uji', 'level_education' => 'sd',
                'level_class' => 1, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
            ]);

            DB::table('student')->insert([
                'id_parent' => $parentId,
                'id_class' => $classId,
                'nis' => 'T'.random_int(100000, 999999),
                'name' => 'Anak Uji',
                'gender' => 'male',
                'religion' => 'Islam',
                'branch_id' => self::BRANCH_ID,
                'created_by' => 0,
            ]);
        }

        return Parents::withoutGlobalScopes()->findOrFail($parentId);
    }

    private function dualPegawai(array $modules = []): User
    {
        $user = $this->createUser('pegawai', $modules);
        $this->createEmployee($user);
        $this->linkParent($user);

        return $user;
    }

    // ---------- 1. user satu peran tidak berubah ----------

    public function test_plain_staff_keeps_working_and_sees_no_parent_menu(): void
    {
        $user = $this->createUser('pegawai');
        $this->createEmployee($user);

        $this->actingAs($user)->get(route('academic.student.children'))->assertRedirect(route('base'));
        $this->actingAs($user)->get(route('dashboard.index'))->assertOk()->assertDontSee(__('label.parent_menu'));
        $this->assertFalse($user->isActingAsParent());
        $this->assertFalse($user->hasMultipleRoles());
    }

    public function test_plain_parent_is_unchanged(): void
    {
        $user = $this->createUser('orang-tua');
        $this->linkParent($user);

        $this->actingAs($user)->get(route('academic.student.children'))->assertOk()->assertSee('Anak Uji');
        $this->assertFalse($user->hasMultipleRoles());
        $this->assertTrue($user->isParent());
        $this->assertFalse($user->isEmployee());
    }

    // ---------- 2/3. dua peran: menu gabungan, route sesuai konteks ----------

    public function test_dual_user_sees_both_menus_and_can_open_both_route_groups(): void
    {
        $user = $this->dualPegawai();

        $this->assertTrue($user->hasMultipleRoles());

        $this->actingAs($user)->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee(__('label.parent_menu'))
            ->assertSee(route('academic.student.children'), false);

        $this->actingAs($user)->get(route('academic.student.children'))
            ->assertOk()
            ->assertSee('Anak Uji')
            ->assertSee(__('label.staff_menu'));
    }

    public function test_dual_user_cannot_open_routes_outside_both_groups(): void
    {
        $user = $this->dualPegawai();

        // Route kasir / bendahara tanpa modul: tetap ditolak
        $this->actingAs($user)->get(route('finance.balance.topup'))->assertRedirect(route('base'));
        $this->actingAs($user)->post(route('finance.transaction.store'))->assertRedirect(route('base'));
    }

    public function test_parent_without_children_does_not_get_parent_context(): void
    {
        $user = $this->createUser('pegawai');
        $this->createEmployee($user);
        $this->linkParent($user, false);

        $this->assertFalse($user->hasMultipleRoles());
        $this->actingAs($user)->get(route('academic.student.children'))->assertRedirect(route('base'));
        $this->actingAs($user)->get(route('dashboard.index'))->assertOk()->assertDontSee(__('label.parent_menu'));
    }

    public function test_cashier_who_is_also_parent_keeps_cashier_routes_and_gets_parent_routes(): void
    {
        $user = $this->createUser('kasir');
        $this->linkParent($user);

        $this->actingAs($user)->get(route('academic.student.children'))->assertOk();
        $this->assertTrue($user->isActingAsParent());

        $this->actingAs($user)->get(route('finance.balance.topup'))->assertOk();
        $this->assertFalse($user->isActingAsParent());
        $this->assertTrue($user->hasAccess('kasir'));
    }

    // ---------- modul Hak Akses ----------

    public function test_modules_do_not_apply_while_acting_as_parent_but_work_otherwise(): void
    {
        $user = $this->dualPegawai([\App\Constants\Modules::KasirUnit]);

        $this->assertTrue($user->hasAccess('kasir-unit'));

        $response = $this->actingAs($user)->get(route('finance.balance.index'));
        $response->assertOk()->assertDontSee(route('finance.balance.topup'), false);
        $this->assertTrue($user->isActingAsParent());
        $this->assertFalse($user->hasAccess('kasir-unit'));
        $this->assertSame([], $user->moduleIds());

        // Request berikutnya ke route staf: modul berlaku lagi
        $this->actingAs($user)->get(route('finance.balance.topup'))->assertOk();
        $this->assertFalse($user->isActingAsParent());
        $this->assertTrue($user->hasAccess('kasir-unit'));
    }

    // ---------- 4/5. override tidak pernah tersimpan dan tidak bocor ----------

    public function test_role_in_db_is_never_changed_by_saving_while_overridden(): void
    {
        $user = $this->dualPegawai();
        $user->actAs('parent');

        $this->assertSame(UserRole::OrangTua, $user->role);

        $user->name = 'Nama Setelah Override';
        $user->save();
        $user->update(['gender' => 'female']);

        $this->assertSame('pegawai', DB::table('users')->where('id', $user->id)->value('role'));
        $this->assertSame('Nama Setelah Override', DB::table('users')->where('id', $user->id)->value('name'));
        // override dipasang lagi untuk sisa request
        $this->assertSame(UserRole::OrangTua, $user->role);

        $user->clearActAs();
        $this->assertSame(UserRole::Pegawai, $user->role);
        $this->assertFalse($user->isDirty('role'));
    }

    public function test_override_does_not_leak_into_the_next_request(): void
    {
        $user = $this->dualPegawai();

        $this->actingAs($user)->get(route('academic.student.children'))->assertOk();
        $this->assertTrue($user->isActingAsParent());

        $this->actingAs($user)->get(route('dashboard.index'))->assertOk();
        $this->assertFalse($user->isActingAsParent());
        $this->assertSame(UserRole::Pegawai, $user->role);
    }

    // ---------- pegawai nonaktif yang juga orang tua ----------

    public function test_inactive_employee_who_is_a_parent_is_treated_as_parent_only(): void
    {
        $user = $this->createUser('pegawai');
        $employee = $this->createEmployee($user);
        DB::table('employee')->where('id', $employee->id)->update(['status' => 0]);
        $this->linkParent($user);

        $this->assertFalse($user->isEmployee());
        $this->assertTrue($user->isParentOnlyAccount());
        $this->assertFalse($user->hasMultipleRoles());

        $this->actingAs($user)->get(route('academic.student.children'))->assertOk();
        $this->actingAs($user)->get(route('academic.absence.create'))->assertRedirect(route('base'));
        $this->actingAs($user)->get(route('dashboard.index'))->assertOk()->assertDontSee(__('label.parent_menu'));
        $this->assertSame('pegawai', DB::table('users')->where('id', $user->id)->value('role'));
    }

    public function test_inactive_employee_who_is_not_a_parent_is_not_kicked_out(): void
    {
        $user = $this->createUser('pegawai');
        $employee = $this->createEmployee($user);
        DB::table('employee')->where('id', $employee->id)->update(['status' => 0]);

        $this->assertTrue($user->isEmployee());
        $this->assertFalse($user->isParentOnlyAccount());
        $this->actingAs($user)->get(route('dashboard.index'))->assertOk();
    }

    // ---------- allowlist route orang tua ----------

    public function test_every_parent_route_is_in_the_allowlist_config(): void
    {
        $missing = [];

        foreach (Route::getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $middleware) {
                if (is_string($middleware) && preg_match('/^(role|access):(.*)$/', $middleware, $m)
                    && in_array('orang-tua', explode(',', $m[2]), true)) {
                    $name = $route->getName();

                    if ($name === null || ! Str::is(config('parent_access.routes'), $name)) {
                        $missing[] = $name ?? $route->uri();
                    }
                }
            }
        }

        $this->assertSame([], array_unique($missing), 'Route orang tua belum ada di config/parent_access.php');
    }

    public function test_allowlist_log_mode_logs_but_does_not_block(): void
    {
        config(['parent_access.mode' => 'log']);
        Log::spy();
        $user = $this->createUser('orang-tua');
        $this->linkParent($user);

        $this->actingAs($user)->get(route('setting.index'))->assertRedirect(route('base'));
        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message, $context = []) => $message === 'parent-route-outside-allowlist' && ($context['route'] ?? null) === 'setting.index')
            ->once();
    }

    public function test_allowlist_log_mode_stays_quiet_for_allowed_routes(): void
    {
        config(['parent_access.mode' => 'log']);
        Log::spy();
        $user = $this->createUser('orang-tua');
        $this->linkParent($user);

        $this->actingAs($user)->get(route('academic.student.children'))->assertOk();
        Log::shouldNotHaveReceived('warning', ['parent-route-outside-allowlist', \Mockery::any()]);
    }

    public function test_allowlist_enforce_mode_redirects_outside_routes_for_parents(): void
    {
        config(['parent_access.mode' => 'enforce']);
        $parent = $this->createUser('orang-tua');
        $this->linkParent($parent);

        $this->actingAs($parent)->get(route('employee.lunch-report.index'))->assertRedirect(route('dashboard.index'));
        $this->actingAs($parent)->get(route('academic.student.children'))->assertOk();

        // Konteks staf tidak dibatasi allowlist
        $dual = $this->dualPegawai();
        $this->actingAs($dual)->get(route('employee.lunch-report.index'))->assertOk();
    }

    public function test_parent_cannot_open_another_parents_student(): void
    {
        $mine = $this->createUser('orang-tua');
        $this->linkParent($mine);
        $other = $this->createUser('orang-tua');
        $this->linkParent($other);

        $myStudent = \App\Models\Student::withoutGlobalScopes()->where('id_parent', $mine->parent->id)->first();
        $otherStudent = \App\Models\Student::withoutGlobalScopes()->where('id_parent', $other->parent->id)->first();

        $this->actingAs($mine)->get(route('academic.student.show', $myStudent->encrypted_id))->assertOk();
        $this->actingAs($mine)->get(route('academic.student.show', $otherStudent->encrypted_id))->assertNotFound();
        $this->actingAs($mine)->get(route('academic.student.edit', $otherStudent->encrypted_id))->assertNotFound();
    }
}
