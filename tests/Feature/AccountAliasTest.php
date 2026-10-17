<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Parents;
use App\Models\User;
use App\Services\AccountLinkException;
use App\Services\AccountLinker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Akun alias: akun orang tua lama yang digabung ke akun pegawai tetap bisa dipakai login
 * (users.linked_user_id), tetapi sesinya selalu milik akun pegawai.
 */
class AccountAliasTest extends ModuleAccessTestCase
{
    private AccountLinker $linker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->linker = app(AccountLinker::class);
    }

    private function phone(): string
    {
        return '08'.random_int(100000000, 999999999);
    }

    private function setPassword(User $user, string $password): void
    {
        DB::table('users')->where('id', $user->id)->update(['password' => Hash::make($password)]);
    }

    /** @return array{0: User, 1: Employee, 2: User, 3: Parents, 4: int} X pegawai, Y orang tua berakun (belum digabung) */
    private function pair(): array
    {
        $x = $this->createUser('pegawai');
        $employee = $this->createEmployee($x);
        $this->setPassword($x, 'pw-pegawai');
        $y = $this->createUser('orang-tua');
        $this->setPassword($y, 'pw-ortu');
        $parentId = DB::table('parent')->insertGetId([
            'id_user' => $y->id, 'name' => 'Ortu Y', 'phone' => $y->phone, 'gender' => 'male', 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);
        $studentId = DB::table('student')->insertGetId([
            'id_parent' => $parentId, 'id_class' => 1, 'nis' => 'N'.random_int(1000000, 9999999), 'name' => 'Anak Y',
            'gender' => 'male', 'religion' => 'Islam', 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);

        return [$x, $employee, $y, Parents::withoutGlobalScopes()->findOrFail($parentId), $studentId];
    }

    /** Login lewat endpoint; mengembalikan id user di sesi (null bila ditolak). */
    private function loginAs(string $username, string $password): ?int
    {
        Auth::forgetGuards();
        $this->flushSession();
        $this->post(route('auth.authenticate'), ['username' => $username, 'password' => $password]);

        return Auth::id();
    }

    public function test_after_merge_both_passwords_log_in_as_the_employee_with_the_dual_role_menu(): void
    {
        [$x, $employee, $y, $parent] = $this->pair();
        $this->linker->link($employee->id, $parent->id);

        $this->assertSame($x->id, (int) User::withoutGlobalScopes()->find($y->id)->linked_user_id);

        foreach ([[$x->phone, 'pw-pegawai'], [$x->email ?: $x->phone, 'pw-pegawai'], [$y->phone, 'pw-ortu']] as [$username, $password]) {
            $this->assertSame($x->id, $this->loginAs($username, $password), "login {$username}");
            $this->assertTrue(Auth::user()->has_dual_role);
            $this->get(route('dashboard.index'))->assertOk()->assertSee(__('label.my_children'));
        }
    }

    public function test_wrong_password_is_rejected_in_both_directions(): void
    {
        [, $employee, $y, $parent] = $this->pair();
        $x = User::withoutGlobalScopes()->find($employee->id_user);
        $this->linker->link($employee->id, $parent->id);

        // password akun pegawai tidak membuka akun orang tua lama, dan sebaliknya
        $this->assertNull($this->loginAs($y->phone, 'pw-pegawai'));
        $this->assertNull($this->loginAs($x->phone, 'pw-ortu'));
        $this->assertNull($this->loginAs($y->phone, 'salah'));
        $this->assertSame($x->id, $this->loginAs($y->phone, 'pw-ortu'));
    }

    public function test_same_phone_on_both_accounts_resolves_by_password(): void
    {
        [$x, $employee, $y, $parent] = $this->pair();
        DB::table('users')->where('id', $y->id)->update(['phone' => $x->phone]);
        $this->linker->link($employee->id, $parent->id);

        $this->assertSame($x->id, $this->loginAs($x->phone, 'pw-pegawai'));
        $this->assertSame($x->id, $this->loginAs($x->phone, 'pw-ortu'));
        $this->assertNull($this->loginAs($x->phone, 'salah'));
    }

    public function test_unlink_returns_both_accounts_to_themselves(): void
    {
        [$x, $employee, $y, $parent] = $this->pair();
        $this->linker->link($employee->id, $parent->id);
        $this->linker->unlink($parent->id);

        $this->assertNull(User::withoutGlobalScopes()->find($y->id)->linked_user_id);
        $this->assertSame($y->id, (int) DB::table('parent')->where('id', $parent->id)->value('id_user'));

        $this->assertSame($x->id, $this->loginAs($x->phone, 'pw-pegawai'));
        $this->assertFalse(Auth::user()->has_dual_role);

        $this->assertSame($y->id, $this->loginAs($y->phone, 'pw-ortu'));
        $this->assertSame('orang-tua', Auth::user()->menu_role->value);
        $this->assertNull($this->loginAs($y->phone, 'pw-pegawai'));
    }

    public function test_accounts_that_were_never_merged_log_in_as_before(): void
    {
        [$x, , $y] = $this->pair();

        $this->assertSame($x->id, $this->loginAs($x->phone, 'pw-pegawai'));
        $this->assertSame($y->id, $this->loginAs($y->phone, 'pw-ortu'));
        $this->assertNull($this->loginAs($y->phone, 'pw-pegawai'));
        $this->assertNull($this->loginAs('nomor-tidak-ada', 'pw-ortu'));
    }

    public function test_different_target_accounts_with_the_same_password_are_not_decided_automatically(): void
    {
        $phone = $this->phone();
        $a = $this->createUser('kasir');
        $b = $this->createUser('orang-tua');
        DB::table('users')->whereIn('id', [$a->id, $b->id])->update(['phone' => $phone]);

        $this->assertNull($this->loginAs($phone, 'secret'));
        $this->assertStringContainsString(__('string.login_ambiguous_accounts'), session('errors')->first());
    }

    public function test_empty_and_zero_phone_are_never_used_to_find_accounts(): void
    {
        $zero = $this->createUser('kasir');
        DB::table('users')->where('id', $zero->id)->update(['phone' => '0']);
        $blank = $this->createUser('kasir');
        DB::table('users')->where('id', $blank->id)->update(['phone' => '']);

        $this->assertNull($this->loginAs('0', 'secret'));
        $this->assertNull($this->loginAs('-', 'secret'));
    }

    public function test_more_than_ten_candidates_checks_ten_and_logs_the_phone_and_count(): void
    {
        $phone = $this->phone();
        foreach (range(1, 12) as $i) {
            $user = $this->createUser('orang-tua');
            DB::table('users')->where('id', $user->id)->update(['phone' => $phone]);
        }

        Log::spy();
        $this->loginAs($phone, 'salah');

        Log::shouldHaveReceived('warning')->withArgs(
            fn ($message, $context = []) => $message === 'login-too-many-candidates'
                && $context['username'] === $phone && $context['candidates'] === 12 && $context['checked'] === 10
        )->once();
    }

    public function test_login_route_is_throttled(): void
    {
        foreach (range(1, 20) as $i) {
            $this->post(route('auth.authenticate'), ['username' => 'x'.$i, 'password' => 'y'])->assertStatus(302);
        }

        $this->post(route('auth.authenticate'), ['username' => 'x', 'password' => 'y'])->assertStatus(429);
    }

    public function test_an_old_session_of_the_alias_is_redirected_to_the_employee_account(): void
    {
        [$x, $employee, $y, $parent] = $this->pair();
        $this->linker->link($employee->id, $parent->id);

        $this->actingAs(User::withoutGlobalScopes()->find($y->id))->get(route('dashboard.index'))->assertOk();

        $this->assertSame($x->id, Auth::id());
    }

    public function test_an_alias_session_with_an_invalid_target_is_logged_out(): void
    {
        [$x, $employee, $y, $parent] = $this->pair();
        $this->linker->link($employee->id, $parent->id);
        DB::table('users')->where('id', $x->id)->update(['deleted_at' => now()]);

        $this->actingAs(User::withoutGlobalScopes()->find($y->id))->get(route('dashboard.index'))->assertRedirect(route('base'));

        $this->assertNull(Auth::id());
        // login lewat alias yang tujuannya hilang juga ditolak
        $this->assertNull($this->loginAs($y->phone, 'pw-ortu'));
    }

    public function test_deleting_the_employee_releases_the_alias(): void
    {
        [$x, $employee, $y, $parent] = $this->pair();
        $this->linker->link($employee->id, $parent->id);
        $admin = $this->createUser('admin');

        $this->actingAs($admin)->deleteJson(route('hr.employee.destroy', $employee->encrypted_id))->assertOk();

        $this->assertNull(User::withoutGlobalScopes()->find($y->id)->linked_user_id);
        $this->assertSame($y->id, (int) DB::table('parent')->where('id', $parent->id)->value('id_user'));
        $this->assertSame(1, DB::table('activity_log')->where('log_name', 'account-link')->where('subject_id', $parent->id)
            ->where('properties->event', 'unlink')->where('properties->reason', 'employee-deleted')->count());
        $this->assertSame($y->id, $this->loginAs($y->phone, 'pw-ortu'));
    }

    public function test_deleting_the_employee_user_releases_the_alias(): void
    {
        [$x, $employee, $y, $parent] = $this->pair();
        $this->linker->link($employee->id, $parent->id);
        $kasir = $this->createUser('admin');
        $this->actingAs($kasir);
        $this->assertSame(1, $this->linker->unlinkAliasesOf($x->id, 'user-deleted'));
        $this->assertNull(User::withoutGlobalScopes()->find($y->id)->linked_user_id);
        $this->assertSame($y->id, (int) DB::table('parent')->where('id', $parent->id)->value('id_user'));
    }

    public function test_alias_without_log_entry_is_released_too(): void
    {
        [$x, , $y] = $this->pair();
        DB::table('users')->where('id', $y->id)->update(['linked_user_id' => $x->id]);

        $this->assertSame(1, $this->linker->unlinkAliasesOf($x->id, 'employee-deleted'));
        $this->assertNull(User::withoutGlobalScopes()->find($y->id)->linked_user_id);
    }

    public function test_blockers_for_aliases(): void
    {
        [$x, $employee, $y, $parent] = $this->pair();
        $other = $this->createUser('pegawai');
        $otherEmployee = $this->createEmployee($other);

        DB::table('users')->where('id', $y->id)->update(['linked_user_id' => $other->id]);
        $codes = collect($this->linker->preview($employee->id, $parent->id)['blockers'])->pluck('code');
        $this->assertContains('old_user_is_alias', $codes);

        DB::table('users')->where('id', $y->id)->update(['linked_user_id' => null]);
        DB::table('users')->where('id', $x->id)->update(['linked_user_id' => $other->id]);
        $codes = collect($this->linker->preview($employee->id, $parent->id)['blockers'])->pluck('code');
        $this->assertContains('employee_user_is_alias', $codes);
    }

    public function test_the_alias_column_is_not_mass_assignable(): void
    {
        [$x, , $y] = $this->pair();
        User::withoutGlobalScopes()->find($y->id)->fill(['linked_user_id' => $x->id])->save();

        $this->assertNull(User::withoutGlobalScopes()->find($y->id)->linked_user_id);
    }

    public function test_legacy_log_entry_with_a_soft_deleted_account_can_still_be_unlinked(): void
    {
        [$x, $employee, $y, $parent] = $this->pair();
        // keadaan hasil versi lama: akun orang tua di-soft-delete, tanpa linked_user_id
        DB::table('parent')->where('id', $parent->id)->update(['id_user' => $x->id]);
        DB::table('users')->where('id', $y->id)->update(['deleted_at' => now(), 'deleted_by' => 1]);
        DB::table('account_link_log')->insert([
            'action' => 'link', 'employee_id' => $employee->id, 'parent_id' => $parent->id, 'employee_user_id' => $x->id,
            'old_user_id' => $y->id, 'before' => json_encode(['parent_id_user' => $y->id, 'parent_phone' => $y->phone, 'old_user_deleted' => true]),
            'moved' => json_encode([]),
        ]);

        $this->linker->unlink($parent->id);

        $this->assertNull(User::withoutGlobalScopes()->find($y->id)->deleted_at);
        $this->assertSame($y->id, (int) DB::table('parent')->where('id', $parent->id)->value('id_user'));
    }

    public function test_registration_with_an_alias_phone_is_refused_with_login_hint(): void
    {
        [, $employee, $y, $parent] = $this->pair();
        $this->linker->link($employee->id, $parent->id);

        \Illuminate\Support\Facades\Validator::extend('captcha', fn () => true);

        $this->post(route('auth.store.register'), ['phone' => $y->phone, 'captcha' => 'x'])
            ->assertRedirect(route('auth.register'));
        $this->assertStringContainsString(__('string.you_has_been_registered'), session('errors')->first());
    }

    public function test_forgot_password_on_the_alias_phone_resets_only_the_alias_password(): void
    {
        [$x, $employee, $y, $parent] = $this->pair();
        $this->linker->link($employee->id, $parent->id);
        $xHash = User::withoutGlobalScopes()->find($x->id)->password;

        $this->withSession(['forgot_password' => [
            'phone' => $y->phone, 'code' => '424242', 'expires_at' => now()->addMinutes(10)->getTimestamp(), 'attempts' => 0,
        ]])->post(route('auth.store.forgot-password.verification'), ['code' => '424242'])
            ->assertRedirect(route('auth.reset-password'));
        $this->post(route('auth.store.reset-password'), ['password' => 'baru-12345', 'password_confirm' => 'baru-12345'])
            ->assertRedirect(route('base'));

        $this->assertSame($xHash, User::withoutGlobalScopes()->find($x->id)->password);
        $this->assertTrue(Hash::check('baru-12345', User::withoutGlobalScopes()->find($y->id)->password));
        $this->assertSame($x->id, $this->loginAs($y->phone, 'baru-12345'));
        $this->assertSame($x->id, $this->loginAs($x->phone, 'pw-pegawai'));
        $this->assertNull($this->loginAs($y->phone, 'pw-ortu'));
    }

    public function test_parent_edit_page_shows_the_linked_state_and_old_account(): void
    {
        [, $employee, $y, $parent] = $this->pair();
        $this->linker->link($employee->id, $parent->id);
        $admin = $this->createUser('admin');

        $this->actingAs($admin)->get(route('academic.parent.edit', $parent->encrypted_id))
            ->assertOk()
            ->assertSee(__('string.parent_linked_to_employee'))
            ->assertSee(__('string.account_link_alias_info', ['account' => $y->email ?: $y->phone]), false);
    }

    private function linker(): AccountLinker
    {
        return $this->linker;
    }
}
