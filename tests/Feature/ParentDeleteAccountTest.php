<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Parents;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Hapus data orang tua: akun login ber-role orang-tua yang tidak dipakai data orang tua lain ikut dinonaktifkan,
 * kecuali admin MEMILIH menjadikannya alias dari pegawai bernomor HP sama. Akun staf tidak pernah disentuh.
 * Akun orang tua yatim yang sudah ada mendapat pesan login khusus.
 */
class ParentDeleteAccountTest extends ModuleAccessTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createUser('admin');
    }

    private function phone(): string
    {
        return '08'.random_int(1000000000, 9999999999);
    }

    private function setPhone(User $user, string $phone): void
    {
        DB::table('users')->where('id', $user->id)->update(['phone' => $phone]);
    }

    private function parentRow(?int $userId, string $phone, int $children = 1): Parents
    {
        $id = DB::table('parent')->insertGetId([
            'id_user' => $userId, 'name' => 'Ortu Uji', 'phone' => $phone, 'gender' => 'male', 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);

        for ($i = 0; $i < $children; $i++) {
            DB::table('student')->insert([
                'id_parent' => $id, 'id_class' => 1, 'nis' => 'N'.random_int(1000000, 9999999), 'name' => 'Anak '.$i,
                'gender' => 'male', 'religion' => 'Islam', 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
            ]);
        }

        return Parents::withoutGlobalScopes()->findOrFail($id);
    }

    /** @return array{0: User, 1: Parents} akun orang tua + baris orang tuanya */
    private function parentWithAccount(?string $phone = null): array
    {
        $user = $this->createUser('orang-tua');
        $this->setPhone($user, $phone ?? $this->phone());
        DB::table('users')->where('id', $user->id)->update(['password' => Hash::make('pw-ortu')]);
        $user = User::withoutGlobalScopes()->find($user->id);

        return [$user, $this->parentRow($user->id, $user->phone)];
    }

    /** @return array{0: User, 1: Employee} */
    private function employeeWithPhone(string $phone): array
    {
        $user = $this->createUser('pegawai');
        $this->setPhone($user, $phone);
        DB::table('users')->where('id', $user->id)->update(['password' => Hash::make('pw-pegawai')]);

        return [User::withoutGlobalScopes()->find($user->id), $this->createEmployee($user)];
    }

    private function preview(Parents $parent): array
    {
        return $this->actingAs($this->admin)->getJson(route('academic.parent.delete-preview', $parent->encrypted_id))
            ->assertOk()->json('data');
    }

    private function destroy(Parents $parent, array $data = [])
    {
        return $this->actingAs($this->admin)->deleteJson(route('academic.parent.destroy', $parent->encrypted_id), $data)->assertOk();
    }

    private function trashed(string $table, int $id): bool
    {
        return DB::table($table)->where('id', $id)->whereNotNull('deleted_at')->exists();
    }

    public function test_deleting_a_parent_deactivates_its_own_parent_account_in_the_same_transaction(): void
    {
        [$user, $parent] = $this->parentWithAccount();

        $this->assertSame($user->name, $this->preview($parent)['account']['name']);

        $this->destroy($parent)->assertJson(['status' => true, 'message' => __('message.parent_deleted_account_deactivated')]);

        $this->assertTrue($this->trashed('parent', $parent->id));
        $this->assertTrue($this->trashed('users', $user->id));
        $this->assertTrue(DB::table('activity_log')->where('log_name', 'parent-account')
            ->where('subject_id', $user->id)->where('causer_id', $this->admin->id)->exists());
    }

    public function test_default_never_makes_an_alias_even_when_an_employee_shares_the_phone(): void
    {
        [$user, $parent] = $this->parentWithAccount();
        [$staff] = $this->employeeWithPhone($user->phone);

        $this->assertCount(1, $this->preview($parent)['candidates']);

        $this->destroy($parent)->assertJson(['status' => true]);

        $this->assertTrue($this->trashed('users', $user->id));
        $this->assertNull(DB::table('users')->where('id', $user->id)->value('linked_user_id'));
        $this->assertFalse(DB::table('account_link_log')->where('old_user_id', $user->id)->exists());
        $this->assertFalse($this->trashed('users', $staff->id));
    }

    public function test_candidates_match_on_the_normalised_phone_only(): void
    {
        [$user, $parent] = $this->parentWithAccount('081234567890');
        [, $dashed] = $this->employeeWithPhone('0812-3456-7890');
        [, $international] = $this->employeeWithPhone('+62 812.3456.7890');
        $this->employeeWithPhone('081234567899');

        $names = collect($this->preview($parent)['candidates'])->pluck('name')->sort()->values()->all();

        $this->assertSame(collect([$dashed->name, $international->name])->sort()->values()->all(), $names);
    }

    public function test_inactive_employee_or_alias_account_is_not_a_candidate(): void
    {
        [$user, $parent] = $this->parentWithAccount();
        [, $inactive] = $this->employeeWithPhone($user->phone);
        DB::table('employee')->where('id', $inactive->id)->update(['status' => 0]);

        $this->assertSame([], $this->preview($parent)['candidates']);
    }

    public function test_admin_can_choose_to_make_the_account_an_alias_through_account_linker(): void
    {
        [$user, $parent] = $this->parentWithAccount();
        [$staff, $employee] = $this->employeeWithPhone('+62'.substr($user->phone, 1));

        $this->destroy($parent, ['alias_employee' => $employee->encrypted_id])
            ->assertJson(['status' => true, 'message' => __('message.parent_deleted_account_aliased', ['name' => $employee->name])]);

        $this->assertTrue($this->trashed('parent', $parent->id));
        $this->assertFalse($this->trashed('users', $user->id));
        $this->assertSame($staff->id, (int) DB::table('users')->where('id', $user->id)->value('linked_user_id'));

        $log = DB::table('account_link_log')->where('old_user_id', $user->id)->first();
        $this->assertSame('alias', $log->action);
        $this->assertSame($employee->id, (int) $log->employee_id);
        $this->assertSame($parent->id, (int) $log->parent_id);
        $this->assertSame($this->admin->id, (int) $log->actor_id);
        $this->assertTrue(DB::table('activity_log')->where('log_name', 'account-link')->where('properties', 'like', '%"event":"alias"%')->exists());

        // Password akun orang tua lama sekarang masuk ke akun pegawai
        Auth::forgetGuards();
        $this->flushSession();
        $this->post(route('auth.authenticate'), ['username' => $user->phone, 'password' => 'pw-ortu']);
        $this->assertSame($staff->id, Auth::id());
    }

    public function test_alias_to_an_employee_that_was_not_suggested_is_refused_and_nothing_is_deleted(): void
    {
        [$user, $parent] = $this->parentWithAccount();
        [, $other] = $this->employeeWithPhone($this->phone());

        $this->destroy($parent, ['alias_employee' => $other->encrypted_id])
            ->assertJson(['status' => false, 'message' => __('message.parent_delete_alias_invalid')]);

        $this->assertFalse($this->trashed('parent', $parent->id));
        $this->assertFalse($this->trashed('users', $user->id));
        $this->assertNull(DB::table('users')->where('id', $user->id)->value('linked_user_id'));

        $this->destroy($parent, ['alias_employee' => 'bukan-id-terenkripsi'])->assertJson(['status' => false]);
        $this->assertFalse($this->trashed('parent', $parent->id));
    }

    public function test_staff_accounts_are_never_deactivated(): void
    {
        foreach (['pegawai', 'kepala-sekolah', 'kasir'] as $role) {
            $staff = $this->createUser($role);
            $this->createEmployee($staff);
            $parent = $this->parentRow($staff->id, $staff->phone);

            $this->assertNull($this->preview($parent)['account'], $role);
            $this->destroy($parent)->assertJson(['status' => true, 'message' => __('message.delete_success', ['label' => __('label.parent')])]);

            $this->assertTrue($this->trashed('parent', $parent->id), $role);
            $this->assertFalse($this->trashed('users', $staff->id), $role);
        }
    }

    public function test_parent_account_used_by_another_parent_row_is_kept(): void
    {
        [$user, $parent] = $this->parentWithAccount();
        $this->parentRow($user->id, $user->phone);

        $this->assertNull($this->preview($parent)['account']);
        $this->destroy($parent)->assertJson(['status' => true]);

        $this->assertTrue($this->trashed('parent', $parent->id));
        $this->assertFalse($this->trashed('users', $user->id));
    }

    public function test_parent_without_account_is_just_deleted(): void
    {
        $parent = $this->parentRow(null, $this->phone());

        $this->assertNull($this->preview($parent)['account']);
        $this->destroy($parent)->assertJson(['status' => true]);
        $this->assertTrue($this->trashed('parent', $parent->id));
    }

    public function test_existing_orphan_parent_account_gets_a_specific_login_message(): void
    {
        // Akun yatim lama: data orang tuanya dihapus sebelum fitur ini ada, akunnya masih aktif
        [$user, $parent] = $this->parentWithAccount();
        DB::table('parent')->where('id', $parent->id)->update(['deleted_at' => now()]);

        Auth::forgetGuards();
        $this->post(route('auth.authenticate'), ['username' => $user->phone, 'password' => 'pw-ortu'])
            ->assertSessionHasErrors(['useraccount' => __('string.login_parent_account_orphan')]);
        $this->assertNull(Auth::id());
    }

    public function test_parent_with_record_but_no_active_students_keeps_the_old_message(): void
    {
        $user = $this->createUser('orang-tua');
        DB::table('users')->where('id', $user->id)->update(['password' => Hash::make('pw-ortu')]);
        $this->parentRow($user->id, $user->phone, 0);

        Auth::forgetGuards();
        $this->post(route('auth.authenticate'), ['username' => $user->phone, 'password' => 'pw-ortu'])
            ->assertSessionHasErrors(['useraccount' => 'Data Siswa Anda belum ada, harap hubungi Admin untuk mendaftarkan data siswa Anda']);
        $this->assertNull(Auth::id());
    }

    public function test_parent_index_page_script_is_valid_javascript(): void
    {
        // Regresi: @json di akhir baris memakan baris baru dan merusak seluruh blok script (DataTable tidak tampil)
        $node = trim((string) shell_exec('command -v node'));

        if ($node === '') {
            $this->markTestSkipped('node tidak tersedia untuk memeriksa sintaks JS');
        }

        $html = $this->actingAs($this->admin)->get(route('academic.parent.index'))->assertOk()->getContent();
        preg_match_all('/<script>(.*?)<\/script>/s', $html, $blocks);
        $script = collect($blocks[1])->first(fn ($block) => str_contains($block, 'table-parent'));
        $this->assertNotNull($script);

        $file = tempnam(sys_get_temp_dir(), 'parent-index').'.js';
        file_put_contents($file, $script);
        exec(escapeshellarg($node).' --check '.escapeshellarg($file).' 2>&1', $output, $code);
        @unlink($file);

        $this->assertSame(0, $code, implode("\n", $output));
    }
}
