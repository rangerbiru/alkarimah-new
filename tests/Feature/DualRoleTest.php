<?php

namespace Tests\Feature;

use App\Models\Parents;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Pegawai yang juga orang tua (model PPIA): satu akun, menu pegawai dan menu orang tua tampil bersamaan,
 * Role middleware meloloskan akun dua peran ke route pegawai dan route orang tua.
 */
class DualRoleTest extends ModuleAccessTestCase
{
    private function linkParent(User $user, bool $withChild = true, ?string $nis = null, ?string $birthdate = null): Parents
    {
        $parentId = DB::table('parent')->insertGetId([
            'id_user' => $user->id, 'name' => $user->name, 'phone' => $user->phone,
            'gender' => 'male', 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);

        if ($withChild) {
            $this->student($parentId, 'Anak Uji', $nis, $birthdate);
        }

        return Parents::withoutGlobalScopes()->findOrFail($parentId);
    }

    private function student(?int $parentId, string $name, ?string $nis = null, ?string $birthdate = null): int
    {
        $classId = DB::table('class')->insertGetId([
            'id_wali_kelas' => 1, 'name' => 'Kelas Uji', 'level_education' => 'sd',
            'level_class' => 1, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);

        return DB::table('student')->insertGetId([
            'id_parent' => $parentId, 'id_class' => $classId, 'nis' => $nis ?? 'T'.random_int(100000, 999999),
            'name' => $name, 'gender' => 'male', 'religion' => 'Islam', 'birthdate' => $birthdate ?? '2015-05-17',
            'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);
    }

    private function dualPegawai(bool $withChild = true, string $role = 'pegawai'): User
    {
        $user = $this->createUser($role);
        $this->createEmployee($user);
        $this->linkParent($user, $withChild);

        return $user;
    }

    // ---------- akun satu peran tidak berubah ----------

    public function test_plain_staff_keeps_working_and_sees_no_parent_menu(): void
    {
        $user = $this->createUser('pegawai');
        $this->createEmployee($user);

        $this->assertFalse($user->has_dual_role);
        $this->actingAs($user)->get(route('academic.student.children'))->assertRedirect(route('base'));
        $this->actingAs($user)->get(route('dashboard.index'))->assertOk()->assertDontSee(__('label.parent_menu'));
    }

    public function test_plain_parent_is_unchanged(): void
    {
        $user = $this->createUser('orang-tua');
        $this->linkParent($user);

        $this->assertFalse($user->has_dual_role);
        $this->assertSame('orang-tua', $user->menu_role->value);
        $this->actingAs($user)->get(route('academic.student.children'))
            ->assertOk()->assertSee('Anak Uji')->assertDontSee('modal-add-child');
        $this->actingAs($user)->post(route('academic.student.store.child'), ['nis' => 'X', 'birthdate' => '17-05-2015'])->assertForbidden();
    }

    // ---------- dua peran: menu gabungan, route kedua kelompok ----------

    public function test_dual_user_sees_both_menus_and_can_open_both_route_groups(): void
    {
        $user = $this->dualPegawai();

        $this->assertTrue($user->has_dual_role);
        $this->assertSame('pegawai', $user->menu_role->value);

        $this->actingAs($user)->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee(__('label.parent_menu'))
            ->assertSee(route('academic.student.children'), false);

        $this->actingAs($user)->get(route('academic.student.children'))
            ->assertOk()->assertSee('Anak Uji')->assertSee('modal-add-child');
    }

    public function test_dual_user_cannot_open_routes_outside_both_groups(): void
    {
        $user = $this->dualPegawai();

        $this->actingAs($user)->get(route('finance.balance.topup'))->assertRedirect(route('base'));
        $this->actingAs($user)->post(route('finance.transaction.store'))->assertRedirect(route('base'));
    }

    public function test_cashier_who_is_also_parent_keeps_cashier_routes_and_gets_parent_routes(): void
    {
        $user = $this->createUser('kasir');
        $this->createEmployee($user);
        $this->linkParent($user);

        $this->assertTrue($user->has_dual_role);
        $this->actingAs($user)->get(route('academic.student.children'))->assertOk();
        $this->actingAs($user)->get(route('finance.balance.topup'))->assertOk();
    }

    public static function staffRoles(): array
    {
        return [['kepala-sekolah'], ['wali-kelas'], ['kasir'], ['kasir-unit'], ['bendahara'], ['penanggung-jawab-tabungan'], ['pegawai']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('staffRoles')]
    public function test_any_staff_role_with_an_employee_row_can_also_be_a_parent(string $role): void
    {
        $user = $this->dualPegawai(true, $role);

        $this->assertTrue($user->has_dual_role);
        $this->assertSame($role, $user->menu_role->value);

        // sidebar (menu peran sendiri + menu orang tua) dirender di setiap halaman backend
        $this->actingAs($user)->get(route('academic.student.children'))
            ->assertOk()->assertSee('Anak Uji')->assertSee(__('label.parent_menu'))->assertSee(route('academic.student.children'), false);
        $this->actingAs($user)->get(route('finance.balance.index'))->assertOk();
    }

    public function test_dual_account_registered_with_the_parent_role_gets_the_employee_menu(): void
    {
        $user = $this->createUser('orang-tua', [\App\Constants\Modules::CreateAbsence]);
        $this->createEmployee($user);
        $this->linkParent($user);

        $this->assertTrue($user->has_dual_role);
        $this->assertSame('pegawai', $user->menu_role->value);
        $this->actingAs($user)->get(route('dashboard.index'))->assertOk()->assertSee(__('label.parent_menu'));
        // employee routes (role:pegawai) are open to the dual-role account
        $this->actingAs($user)->get(route('academic.absence.create'))->assertOk();
    }

    public function test_modules_keep_working_for_a_dual_role_employee(): void
    {
        $user = $this->createUser('pegawai', [\App\Constants\Modules::KasirUnit]);
        $this->createEmployee($user);
        $this->linkParent($user);

        $this->assertTrue($user->hasAccess('kasir-unit'));
        $this->actingAs($user)->get(route('finance.balance.topup'))->assertOk();
        $this->actingAs($user)->get(route('academic.student.children'))->assertOk();
    }

    public function test_role_in_the_database_is_never_touched(): void
    {
        $user = $this->dualPegawai();
        $this->actingAs($user)->get(route('academic.student.children'))->assertOk();
        $user->update(['name' => 'Nama Baru']);

        $this->assertSame('pegawai', DB::table('users')->where('id', $user->id)->value('role'));
    }

    // ---------- Data Anak ----------

    public function test_payment_balance_and_savings_send_a_parent_without_children_to_data_anak(): void
    {
        $user = $this->dualPegawai(false);

        foreach (['finance.payment.index', 'finance.balance.index', 'finance.savings.index'] as $name) {
            $response = $this->actingAs($user)->get(route($name));
            $response->assertRedirect(route('academic.student.children'));
            $response->assertSessionHas('info', __('message.child_required'));
        }
    }

    public function test_dual_user_links_a_child_with_nis_and_birthdate(): void
    {
        $user = $this->dualPegawai(false);
        $free = $this->student(null, 'Santri Bebas', 'NIS-FREE', '2014-03-09');
        $noAccount = $this->student(DB::table('parent')->insertGetId([
            'name' => 'Ortu Tanpa Akun', 'phone' => '081211110000', 'gender' => 'male', 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]), 'Santri Ortu Tanpa Akun', 'NIS-NOACC', '2013-01-02');

        $this->actingAs($user)->post(route('academic.student.store.child'), ['nis' => 'NIS-FREE', 'birthdate' => '09-03-2014'])
            ->assertRedirect(route('academic.student.children'))->assertSessionHas('success');
        $this->assertSame($user->parent->id, (int) DB::table('student')->where('id', $free)->value('id_parent'));

        $this->actingAs($user)->post(route('academic.student.store.child'), ['nis' => 'NIS-NOACC', 'birthdate' => '02-01-2013'])
            ->assertSessionHas('success');
        $this->assertSame($user->parent->id, (int) DB::table('student')->where('id', $noAccount)->value('id_parent'));
    }

    public function test_child_linking_refuses_wrong_birthdate_taken_child_and_other_accounts(): void
    {
        $user = $this->dualPegawai(false);
        $otherUser = $this->createUser('orang-tua');
        $otherParent = $this->linkParent($otherUser, false);
        $taken = $this->student($otherParent->id, 'Anak Orang Lain', 'NIS-TAKEN', '2012-12-12');
        $mine = $this->student($user->parent->id, 'Anak Saya', 'NIS-MINE', '2011-11-11');

        // wrong birthdate and unknown NIS give the same message
        $wrong = $this->actingAs($user)->post(route('academic.student.store.child'), ['nis' => 'NIS-TAKEN', 'birthdate' => '01-01-2000'])->getSession()->get('error');
        $unknown = $this->actingAs($user)->post(route('academic.student.store.child'), ['nis' => 'NOPE', 'birthdate' => '01-01-2000'])->getSession()->get('error');
        $this->assertSame(__('message.child_not_found'), $wrong);
        $this->assertSame($wrong, $unknown);

        // a child whose parent logs in with an account of its own is never taken over
        $this->actingAs($user)->post(route('academic.student.store.child'), ['nis' => 'NIS-TAKEN', 'birthdate' => '12-12-2012'])
            ->assertSessionHas('error', __('message.child_has_other_parent', ['name' => 'Anak Orang Lain']));
        $this->assertSame($otherParent->id, (int) DB::table('student')->where('id', $taken)->value('id_parent'));

        $this->actingAs($user)->post(route('academic.student.store.child'), ['nis' => 'NIS-MINE', 'birthdate' => '11-11-2011'])
            ->assertSessionHas('error', __('message.child_already_linked', ['name' => 'Anak Saya']));
    }

    public function test_parent_cannot_open_another_parents_student(): void
    {
        $mine = $this->createUser('orang-tua');
        $this->linkParent($mine);
        $other = $this->createUser('orang-tua');
        $this->linkParent($other);

        $myStudent = Student::withoutGlobalScopes()->where('id_parent', $mine->parent->id)->first();
        $otherStudent = Student::withoutGlobalScopes()->where('id_parent', $other->parent->id)->first();

        $this->actingAs($mine)->get(route('academic.student.show', $myStudent->encrypted_id))->assertOk();
        $this->actingAs($mine)->get(route('academic.student.show', $otherStudent->encrypted_id))->assertForbidden();
        $this->actingAs($mine)->get(route('academic.student.edit', $otherStudent->encrypted_id))->assertForbidden();
    }

    // ---------- pegawai nonaktif yang juga orang tua ----------

    public function test_inactive_employee_who_is_a_parent_is_treated_as_parent_only(): void
    {
        $user = $this->createUser('pegawai');
        $employee = $this->createEmployee($user);
        DB::table('employee')->where('id', $employee->id)->update(['status' => 0]);
        $this->linkParent($user);

        $this->assertFalse($user->has_dual_role);
        $this->assertTrue($user->acts_as_parent_only);
        $this->assertSame('orang-tua', $user->menu_role->value);

        $this->actingAs($user)->get(route('academic.student.children'))->assertOk();
        $this->actingAs($user)->get(route('academic.absence.create'))->assertRedirect(route('base'));
        $this->actingAs($user)->get(route('dashboard.index'))->assertOk()->assertDontSee(__('label.parent_menu'));
    }

    public function test_inactive_employee_who_is_not_a_parent_is_not_kicked_out(): void
    {
        $user = $this->createUser('pegawai');
        $employee = $this->createEmployee($user);
        DB::table('employee')->where('id', $employee->id)->update(['status' => 0]);

        $this->assertFalse($user->acts_as_parent_only);
        $this->actingAs($user)->get(route('dashboard.index'))->assertOk();
    }

    // ---------- allowlist route orang tua (akun orang tua) ----------

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

    public function test_allowlist_enforce_mode_redirects_parents_but_not_dual_role_users(): void
    {
        config(['parent_access.mode' => 'enforce']);
        $parent = $this->createUser('orang-tua');
        $this->linkParent($parent);

        $this->actingAs($parent)->get(route('employee.lunch-report.index'))->assertRedirect(route('dashboard.index'));
        $this->actingAs($parent)->get(route('academic.student.children'))->assertOk();

        $dual = $this->dualPegawai();
        $this->actingAs($dual)->get(route('employee.lunch-report.index'))->assertOk();
    }
}
