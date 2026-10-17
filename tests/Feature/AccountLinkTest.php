<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Parents;
use App\Models\User;
use App\Services\AccountLinkException;
use App\Services\AccountLinker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Penggabungan akun pegawai - orang tua (AccountLinker + endpoint admin + form pegawai/orang tua
 * + registrasi orang tua dengan nomor pegawai).
 */
class AccountLinkTest extends ModuleAccessTestCase
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

    private function parentRow(?int $userId = null, ?string $phone = null, int $branch = self::BRANCH_ID, string $name = 'Ortu Uji'): Parents
    {
        $id = DB::table('parent')->insertGetId([
            'id_user' => $userId, 'name' => $name, 'phone' => $phone ?? $this->phone(),
            'gender' => 'male', 'branch_id' => $branch, 'created_by' => 0,
        ]);

        return Parents::withoutGlobalScopes()->findOrFail($id);
    }

    private function studentRow(?int $parentId, string $name = 'Anak Uji', int $branch = self::BRANCH_ID): int
    {
        return DB::table('student')->insertGetId([
            'id_parent' => $parentId, 'id_class' => 1, 'nis' => 'N'.random_int(1000000, 9999999), 'name' => $name,
            'gender' => 'male', 'religion' => 'Islam', 'branch_id' => $branch, 'created_by' => 0,
        ]);
    }

    /** @return array{0: User, 1: Employee} */
    private function employeeAccount(string $role = 'pegawai'): array
    {
        $user = $this->createUser($role);

        return [$user, $this->createEmployee($user)];
    }

    /** Orang tua dengan akun sendiri dan riwayat (activity log + kode pembayaran). */
    private function parentWithAccount(): array
    {
        $oldUser = $this->createUser('orang-tua');
        $parent = $this->parentRow($oldUser->id, $oldUser->phone);
        $this->studentRow($parent->id);

        $activity = [];
        foreach ([1, 2] as $i) {
            $activity[] = DB::table('activity_log')->insertGetId([
                'log_name' => 'default', 'description' => 'x'.$i,
                'causer_type' => User::class, 'causer_id' => $oldUser->id,
            ]);
        }
        $foreign = DB::table('activity_log')->insertGetId([
            'log_name' => 'default', 'description' => 'other-model', 'causer_type' => 'App\\Models\\Other', 'causer_id' => $oldUser->id,
        ]);
        $code = DB::table('transaction_payment_code')->insertGetId([
            'code' => 1, 'expired_at' => now()->addDay(), 'flag' => 1, 'created_by' => $oldUser->id,
        ]);

        return [$oldUser, $parent, ['activity' => $activity, 'foreign' => $foreign, 'code' => $code]];
    }

    private function codes(array $blockers): array
    {
        return array_column($blockers, 'code');
    }

    // ---------- preview ----------

    public function test_preview_is_read_only_and_lists_children_old_user_and_moved_rows(): void
    {
        [, $employee] = $this->employeeAccount();
        [$oldUser, $parent, $rows] = $this->parentWithAccount();
        $parentBefore = DB::table('parent')->where('id', $parent->id)->first();

        $preview = $this->linker->preview($employee->id, $parent->id);

        $this->assertTrue($preview['can_link']);
        $this->assertSame([], $preview['blockers']);
        $this->assertSame('Anak Uji', $preview['children'][0]['name']);
        $this->assertSame($oldUser->id, $preview['old_user']['id']);
        $this->assertSame(3, $preview['moved_rows']); // 2 activity_log + 1 transaction_payment_code
        $this->assertEquals($parentBefore, DB::table('parent')->where('id', $parent->id)->first());
        $this->assertNull(User::withoutGlobalScopes()->find($oldUser->id)->deleted_at);
        $this->assertSame(0, DB::table('account_link_log')->where('parent_id', $parent->id)->count());
    }

    // ---------- link ----------

    public function test_link_parent_without_account(): void
    {
        [$user, $employee] = $this->employeeAccount();
        $parent = $this->parentRow(null, '081200000001');
        $this->studentRow($parent->id);

        $result = $this->linker->link($employee->id, $parent->id);

        $fresh = Parents::withoutGlobalScopes()->find($parent->id);
        $this->assertSame($user->id, (int) $fresh->id_user);
        $this->assertSame($user->phone, $fresh->phone);
        $this->assertNull($result['old_user_id']);
        $this->assertSame(1, DB::table('account_link_log')->where('parent_id', $parent->id)->where('action', 'link')->count());
        $this->assertSame(1, DB::table('activity_log')->where('log_name', 'account-link')->where('subject_id', $parent->id)->count());
        $this->assertTrue(User::withoutGlobalScopes()->find($user->id)->hasMultipleRoles());
    }

    public function test_link_moves_allowlisted_rows_and_disables_old_account(): void
    {
        [$user, $employee] = $this->employeeAccount();
        [$oldUser, $parent, $rows] = $this->parentWithAccount();
        DB::table('sessions')->insert(['id' => 'sess'.random_int(1, 999999), 'user_id' => $oldUser->id, 'payload' => '', 'last_activity' => time()]);

        $result = $this->linker->link($employee->id, $parent->id);

        $this->assertSame(3, $result['moved_rows']);
        $this->assertSame($user->id, (int) DB::table('parent')->where('id', $parent->id)->value('id_user'));
        $this->assertSame($user->id, (int) DB::table('transaction_payment_code')->where('id', $rows['code'])->value('created_by'));
        foreach ($rows['activity'] as $id) {
            $this->assertSame($user->id, (int) DB::table('activity_log')->where('id', $id)->value('causer_id'));
        }
        // causer_type lain tidak disentuh
        $this->assertSame($oldUser->id, (int) DB::table('activity_log')->where('id', $rows['foreign'])->value('causer_id'));

        $old = User::withoutGlobalScopes()->withTrashed()->find($oldUser->id);
        $this->assertNotNull($old->deleted_at);
        $this->assertNull(User::find($oldUser->id));
        $this->assertSame(0, DB::table('sessions')->where('user_id', $oldUser->id)->count());
        // akun lama tidak bisa login
        $this->assertFalse(Auth::attempt(['phone' => $oldUser->phone, 'password' => 'secret']));
        // password akun pegawai tidak berubah
        $this->assertTrue(password_verify('secret', User::withoutGlobalScopes()->find($user->id)->password));
    }

    // ---------- unlink ----------

    public function test_unlink_restores_previous_state(): void
    {
        [$user, $employee] = $this->employeeAccount();
        [$oldUser, $parent, $rows] = $this->parentWithAccount();
        $phoneBefore = $parent->phone;

        $this->linker->link($employee->id, $parent->id);

        // baris yang dibuat setelah penggabungan tetap milik user pegawai
        $newActivity = DB::table('activity_log')->insertGetId([
            'log_name' => 'default', 'description' => 'after', 'causer_type' => User::class, 'causer_id' => $user->id,
        ]);

        $result = $this->linker->unlink($parent->id);

        $this->assertTrue($result['had_log']);
        $this->assertSame(3, $result['moved_rows']);
        $fresh = Parents::withoutGlobalScopes()->find($parent->id);
        $this->assertSame($oldUser->id, (int) $fresh->id_user);
        $this->assertSame($phoneBefore, $fresh->phone);

        $restored = User::withoutGlobalScopes()->find($oldUser->id);
        $this->assertNotNull($restored);
        $this->assertNull($restored->deleted_at);
        $this->assertNull(DB::table('users')->where('id', $oldUser->id)->value('deleted_by'));
        foreach ($rows['activity'] as $id) {
            $this->assertSame($oldUser->id, (int) DB::table('activity_log')->where('id', $id)->value('causer_id'));
        }
        $this->assertSame($oldUser->id, (int) DB::table('transaction_payment_code')->where('id', $rows['code'])->value('created_by'));
        $this->assertSame($user->id, (int) DB::table('activity_log')->where('id', $newActivity)->value('causer_id'));
        $this->assertNotNull(DB::table('account_link_log')->where('parent_id', $parent->id)->where('action', 'link')->value('rolled_back_at'));
        $this->assertSame(1, DB::table('account_link_log')->where('parent_id', $parent->id)->where('action', 'unlink')->count());

        // dapat digabung lagi setelah diputus
        $this->assertTrue($this->linker->preview($employee->id, $parent->id)['can_link']);
    }

    public function test_unlink_without_log_just_detaches_the_parent(): void
    {
        [$user, $employee] = $this->employeeAccount();
        $parent = $this->parentRow($user->id, $user->phone);

        $result = $this->linker->unlink($parent->id);

        $this->assertFalse($result['had_log']);
        $this->assertNull(Parents::withoutGlobalScopes()->find($parent->id)->id_user);
        $this->assertNotNull(User::withoutGlobalScopes()->find($user->id));
    }

    // ---------- pemblokir ----------

    public function test_blockers(): void
    {
        [$user, $employee] = $this->employeeAccount();
        $parent = $this->parentRow();

        // pegawai nonaktif
        DB::table('employee')->where('id', $employee->id)->update(['status' => 0]);
        $this->assertContains('employee_inactive', $this->codes($this->linker->preview($employee->id, $parent->id)['blockers']));
        DB::table('employee')->where('id', $employee->id)->update(['status' => 1]);

        // pegawai dihapus
        DB::table('employee')->where('id', $employee->id)->update(['deleted_at' => now()]);
        $this->assertContains('employee_inactive', $this->codes($this->linker->preview($employee->id, $parent->id)['blockers']));
        DB::table('employee')->where('id', $employee->id)->update(['deleted_at' => null]);

        // orang tua dihapus
        DB::table('parent')->where('id', $parent->id)->update(['deleted_at' => now()]);
        $this->assertContains('parent_deleted', $this->codes($this->linker->preview($employee->id, $parent->id)['blockers']));
        DB::table('parent')->where('id', $parent->id)->update(['deleted_at' => null]);

        // user pegawai sudah terhubung ke orang tua lain
        $other = $this->parentRow($user->id, $user->phone);
        $this->assertContains('user_has_other_parent', $this->codes($this->linker->preview($employee->id, $parent->id)['blockers']));
        DB::table('parent')->where('id', $other->id)->update(['id_user' => null]);

        // nomor pegawai sudah dipakai data orang tua lain
        $this->parentRow(null, $user->phone);
        $this->assertContains('phone_used_by_other_parent', $this->codes($this->linker->preview($employee->id, $parent->id)['blockers']));
    }

    public function test_blocker_parent_account_is_a_staff_account(): void
    {
        [, $employee] = $this->employeeAccount();
        $staff = $this->createUser('kasir');
        $parent = $this->parentRow($staff->id, $staff->phone);

        $this->assertContains('old_user_not_parent', $this->codes($this->linker->preview($employee->id, $parent->id)['blockers']));
        $this->expectException(AccountLinkException::class);
        $this->linker->link($employee->id, $parent->id);
    }

    public function test_blocker_already_linked(): void
    {
        [$user, $employee] = $this->employeeAccount();
        $parent = $this->parentRow($user->id, $user->phone);

        $this->assertContains('already_linked', $this->codes($this->linker->preview($employee->id, $parent->id)['blockers']));
    }

    public function test_blocker_branch_mismatch_where_branch_scope_hides_children(): void
    {
        [, $employee] = $this->employeeAccount();
        $parent = $this->parentRow(null, null, self::BRANCH_ID);
        $this->studentRow($parent->id, 'Anak Cabang Lain', self::BRANCH_ID + 1);

        $this->assertContains('branch_mismatch', $this->codes($this->linker->preview($employee->id, $parent->id)['blockers']));

        $parent2 = $this->parentRow(null, null, self::BRANCH_ID + 1);
        $this->assertContains('branch_mismatch', $this->codes($this->linker->preview($employee->id, $parent2->id)['blockers']));
    }

    public function test_link_is_rejected_atomically_when_blocked(): void
    {
        [$user, $employee] = $this->employeeAccount();
        $parent = $this->parentRow(null, '081299990000');
        $this->studentRow($parent->id, 'Anak', self::BRANCH_ID + 1);

        try {
            $this->linker->link($employee->id, $parent->id);
            $this->fail('seharusnya ditolak');
        } catch (AccountLinkException) {
        }

        $fresh = Parents::withoutGlobalScopes()->find($parent->id);
        $this->assertNull($fresh->id_user);
        $this->assertSame('081299990000', $fresh->phone);
        $this->assertSame(0, DB::table('account_link_log')->where('parent_id', $parent->id)->count());
    }

    // ---------- endpoint admin ----------

    public function test_endpoints_are_admin_only(): void
    {
        $parent = $this->parentRow();

        foreach (['pegawai', 'orang-tua', 'kasir'] as $role) {
            $this->actingAs($this->createUser($role))
                ->post(route('hr.account-link.unlink'), ['parent' => $parent->encrypted_id])
                ->assertRedirect(route('base'));
        }
    }

    public function test_preview_from_selected_students_requires_a_single_parent(): void
    {
        $admin = $this->createUser('admin');
        [, $employee] = $this->employeeAccount();
        $parentA = $this->parentRow(null, null, self::BRANCH_ID, 'Ortu A');
        $parentB = $this->parentRow(null, null, self::BRANCH_ID, 'Ortu B');
        $s1 = $this->studentRow($parentA->id, 'Anak 1');
        $s2 = $this->studentRow($parentA->id, 'Anak 2');
        $s3 = $this->studentRow($parentB->id, 'Anak 3');
        $enc = fn ($id) => \Illuminate\Support\Facades\Crypt::encrypt($id);

        $this->actingAs($admin)->postJson(route('hr.account-link.preview'), [
            'employee' => $employee->encrypted_id, 'students' => [$enc($s1), $enc($s3)],
        ])->assertStatus(422)->assertJsonPath('status', false);

        $this->actingAs($admin)->postJson(route('hr.account-link.preview'), [
            'employee' => $employee->encrypted_id, 'students' => [$enc($s1), $enc($s2)],
        ])->assertOk()->assertJsonPath('data.parent.name', 'Ortu A')->assertJsonPath('data.can_link', true)
            ->assertJsonCount(2, 'data.children');
    }

    public function test_student_datatable_lists_students_with_their_parent(): void
    {
        $admin = $this->createUser('admin');
        $parent = $this->parentRow(null, '081211112222', self::BRANCH_ID, 'Ortu Cari');
        $this->studentRow($parent->id, 'Santri Cari Saya');

        $this->actingAs($admin)->postJson(route('hr.account-link.students'), [
            'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => 'Santri Cari Saya'],
        ])->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.parent_name', 'Ortu Cari');
    }

    public function test_employee_search_suggests_same_phone_but_never_links(): void
    {
        $admin = $this->createUser('admin');
        [$user, $employee] = $this->employeeAccount();
        $parent = $this->parentRow(null, '+62'.substr($user->phone, 1));

        $response = $this->actingAs($admin)->getJson(route('hr.account-link.employees', ['parent' => $parent->encrypted_id]));
        $response->assertOk()->assertJsonPath('data.0.suggested', true);
        $this->assertNull(Parents::withoutGlobalScopes()->find($parent->id)->id_user);
    }

    public function test_link_and_unlink_endpoints(): void
    {
        $admin = $this->createUser('admin');
        [$user, $employee] = $this->employeeAccount();
        $parent = $this->parentRow();
        $this->studentRow($parent->id);

        $this->actingAs($admin)->postJson(route('hr.account-link.link'), [
            'employee' => $employee->encrypted_id, 'parent' => $parent->encrypted_id,
        ])->assertOk()->assertJsonPath('status', true);
        $this->assertSame($user->id, (int) Parents::withoutGlobalScopes()->find($parent->id)->id_user);

        // server mengecek ulang pemblokir (sudah terhubung)
        $this->actingAs($admin)->postJson(route('hr.account-link.link'), [
            'employee' => $employee->encrypted_id, 'parent' => $parent->encrypted_id,
        ])->assertStatus(422)->assertJsonPath('blockers.0.code', 'already_linked');

        $this->actingAs($admin)->postJson(route('hr.account-link.unlink'), ['parent' => $parent->encrypted_id])
            ->assertOk()->assertJsonPath('status', true);
        $this->assertNull(Parents::withoutGlobalScopes()->find($parent->id)->id_user);
    }

    // ---------- form pegawai ----------

    private function employeePayload(array $overrides = []): array
    {
        return array_merge([
            'nip' => 'NIP'.random_int(100000, 999999), 'name' => 'Pegawai Baru', 'phone' => $this->phone(),
            'email' => 'pegawai'.random_int(100000, 999999).'@example.test', 'gender' => 'male', 'marital_status' => 1,
            'address' => 'Alamat', 'placement' => 'Pusat', 'status_employment' => 1, 'status_teacher' => 0,
            'task_main' => 'Mengajar', 'password' => 'password123', 'password_confirm' => 'password123',
        ], $overrides);
    }

    public function test_employee_form_creates_employee_and_links_selected_parent(): void
    {
        $admin = $this->createUser('admin');
        $parent = $this->parentRow(null, '081300000009');
        $student = $this->studentRow($parent->id);
        $payload = $this->employeePayload(['also_parent' => 1, 'link_students' => [\Illuminate\Support\Facades\Crypt::encrypt($student)]]);

        $this->actingAs($admin)->post(route('hr.employee.store'), $payload)->assertRedirect(route('hr.employee.index'));

        $employeeUser = User::withoutGlobalScopes()->where('email', $payload['email'])->first();
        $this->assertNotNull($employeeUser);
        $this->assertSame('pegawai', $employeeUser->role->value);
        $this->assertSame($employeeUser->id, (int) Parents::withoutGlobalScopes()->find($parent->id)->id_user);
        $this->assertSame($payload['phone'], Parents::withoutGlobalScopes()->find($parent->id)->phone);
    }

    public function test_employee_form_rolls_back_everything_when_link_is_blocked(): void
    {
        $admin = $this->createUser('admin');
        $parent = $this->parentRow();
        $student = $this->studentRow($parent->id, 'Anak Beda Cabang', self::BRANCH_ID + 1);
        $payload = $this->employeePayload(['also_parent' => 1, 'link_students' => [\Illuminate\Support\Facades\Crypt::encrypt($student)]]);

        $this->actingAs($admin)->post(route('hr.employee.store'), $payload)->assertSessionHasErrors();

        $this->assertNull(User::withoutGlobalScopes()->where('email', $payload['email'])->first());
        $this->assertSame(0, DB::table('employee')->where('nip', $payload['nip'])->count());
        $this->assertNull(Parents::withoutGlobalScopes()->find($parent->id)->id_user);
    }

    public function test_employee_form_rejects_children_of_different_parents_and_keeps_input(): void
    {
        $admin = $this->createUser('admin');
        $a = $this->studentRow($this->parentRow()->id, 'Anak A');
        $b = $this->studentRow($this->parentRow()->id, 'Anak B');
        $enc = fn ($id) => \Illuminate\Support\Facades\Crypt::encrypt($id);
        $payload = $this->employeePayload(['also_parent' => 1, 'link_students' => [$enc($a), $enc($b)]]);

        $this->actingAs($admin)->post(route('hr.employee.store'), $payload)->assertSessionHasErrors('link_students');

        $this->assertNull(User::withoutGlobalScopes()->where('email', $payload['email'])->first());
        $this->assertCount(2, session()->getOldInput('link_students'));
    }

    public function test_student_datatable_marks_parents_that_already_have_an_account(): void
    {
        $admin = $this->createUser('admin');
        [$user] = $this->employeeAccount();
        $linked = $this->parentRow($user->id, $user->phone, self::BRANCH_ID, 'Ortu Tergabung');
        $this->studentRow($linked->id, 'Santri Tergabung');
        $free = $this->parentRow(null, null, self::BRANCH_ID, 'Ortu Bebas');
        $this->studentRow($free->id, 'Santri Bebas');
        $withAccount = $this->parentRow($this->createUser('orang-tua')->id, null, self::BRANCH_ID, 'Ortu Punya Akun');
        $this->studentRow($withAccount->id, 'Santri Punya Akun');

        $rows = collect($this->actingAs($admin)->postJson(route('hr.account-link.students'), [
            'draw' => 1, 'start' => 0, 'length' => 100, 'search' => ['value' => 'Santri '],
        ])->assertOk()->json('data'))->keyBy('name');

        $this->assertTrue($rows['Santri Tergabung']['parent_has_account']);
        $this->assertTrue($rows['Santri Punya Akun']['parent_has_account']);
        $this->assertFalse($rows['Santri Bebas']['parent_has_account']);
        $this->assertNotSame($rows['Santri Bebas']['parent_key'], $rows['Santri Punya Akun']['parent_key']);
    }

    public function test_children_endpoint_returns_all_siblings_of_the_same_parent(): void
    {
        $admin = $this->createUser('admin');
        $parent = $this->parentRow();
        $first = $this->studentRow($parent->id, 'Anak Pertama');
        $this->studentRow($parent->id, 'Anak Kedua');
        $this->studentRow($parent->id, 'Anak Ketiga');
        $this->studentRow($this->parentRow()->id, 'Anak Orang Lain');
        $enc = \Illuminate\Support\Facades\Crypt::encrypt($first);

        $response = $this->actingAs($admin)->postJson(route('hr.account-link.children'), ['student' => $enc])->assertOk();

        $this->assertSame(['Anak Kedua', 'Anak Ketiga', 'Anak Pertama'], collect($response->json('data.children'))->pluck('name')->sort()->values()->all());
    }

    public function test_employee_form_links_the_parent_with_all_its_children_when_one_child_is_sent(): void
    {
        $admin = $this->createUser('admin');
        $parent = $this->parentRow(null, '081300000010');
        $one = $this->studentRow($parent->id, 'Anak Satu');
        $this->studentRow($parent->id, 'Anak Dua');
        $payload = $this->employeePayload(['also_parent' => 1, 'link_students' => [\Illuminate\Support\Facades\Crypt::encrypt($one)]]);

        $this->actingAs($admin)->post(route('hr.employee.store'), $payload)->assertRedirect(route('hr.employee.index'));

        $user = User::withoutGlobalScopes()->where('email', $payload['email'])->first();
        $this->assertSame($user->id, (int) Parents::withoutGlobalScopes()->find($parent->id)->id_user);
        $this->assertSame(2, DB::table('student')->where('id_parent', $parent->id)->count());
        $this->assertTrue(User::withoutGlobalScopes()->find($user->id)->hasMultipleRoles());
    }

    // ---------- santri tanpa data orang tua (id_parent kosong) ----------

    public function test_student_datatable_lists_students_without_a_parent_record(): void
    {
        $admin = $this->createUser('admin');
        $this->studentRow(null, 'Santri Tanpa Ortu');
        $deleted = $this->parentRow(null, null, self::BRANCH_ID, 'Ortu Dihapus');
        $this->studentRow($deleted->id, 'Santri Ortu Dihapus');
        DB::table('parent')->where('id', $deleted->id)->update(['deleted_at' => now()]);

        $rows = collect($this->actingAs($admin)->postJson(route('hr.account-link.students'), [
            'draw' => 1, 'start' => 0, 'length' => 100, 'search' => ['value' => 'Santri '],
        ])->assertOk()->json('data'))->keyBy('name');

        $this->assertSame('none', $rows['Santri Tanpa Ortu']['parent_key']);
        $this->assertFalse($rows['Santri Tanpa Ortu']['parent_has_account']);
        $this->assertNull($rows['Santri Tanpa Ortu']['parent_name']);
        // data orang tuanya sudah dihapus: dianggap belum punya data orang tua (bisa dipilih)
        $this->assertSame('none', $rows['Santri Ortu Dihapus']['parent_key']);
        $this->assertFalse($rows['Santri Ortu Dihapus']['parent_has_account']);
    }

    public function test_children_endpoint_returns_only_the_student_when_there_is_no_parent_record(): void
    {
        $admin = $this->createUser('admin');
        $one = $this->studentRow(null, 'Yatim A');
        $this->studentRow(null, 'Yatim B');

        $response = $this->actingAs($admin)->postJson(route('hr.account-link.children'), [
            'student' => \Illuminate\Support\Facades\Crypt::encrypt($one),
        ])->assertOk()->assertJsonPath('data.parent_key', 'none');

        $this->assertSame(['Yatim A'], collect($response->json('data.children'))->pluck('name')->all());
    }

    public function test_preview_for_students_without_parent_describes_a_new_parent_record(): void
    {
        $admin = $this->createUser('admin');
        [$user, $employee] = $this->employeeAccount();
        $a = $this->studentRow(null, 'Anak Baru 1');
        $b = $this->studentRow(null, 'Anak Baru 2');
        $enc = fn ($id) => \Illuminate\Support\Facades\Crypt::encrypt($id);

        $this->actingAs($admin)->postJson(route('hr.account-link.preview'), [
            'employee' => $employee->encrypted_id, 'students' => [$enc($a), $enc($b)],
        ])->assertOk()
            ->assertJsonPath('data.new_parent', true)
            ->assertJsonPath('data.can_link', true)
            ->assertJsonPath('data.parent.phone', $user->phone)
            ->assertJsonCount(2, 'data.children');

        $this->assertSame(0, DB::table('parent')->where('id_user', $user->id)->count());
        $this->assertNull(DB::table('student')->where('id', $a)->value('id_parent'));
    }

    public function test_mixed_selection_of_students_with_and_without_parent_is_rejected(): void
    {
        $admin = $this->createUser('admin');
        [, $employee] = $this->employeeAccount();
        $withParent = $this->studentRow($this->parentRow()->id, 'Punya Ortu');
        $without = $this->studentRow(null, 'Tanpa Ortu');
        $enc = fn ($id) => \Illuminate\Support\Facades\Crypt::encrypt($id);

        $this->actingAs($admin)->postJson(route('hr.account-link.preview'), [
            'employee' => $employee->encrypted_id, 'students' => [$enc($withParent), $enc($without)],
        ])->assertStatus(422);

        $payload = $this->employeePayload(['also_parent' => 1, 'link_students' => [$enc($withParent), $enc($without)]]);
        $this->actingAs($admin)->post(route('hr.employee.store'), $payload)->assertSessionHasErrors('link_students');
        $this->assertNull(User::withoutGlobalScopes()->where('email', $payload['email'])->first());
    }

    public function test_employee_form_creates_a_parent_record_for_students_without_parent(): void
    {
        $admin = $this->createUser('admin');
        $a = $this->studentRow(null, 'Anak Tanpa Ortu 1');
        $b = $this->studentRow(null, 'Anak Tanpa Ortu 2');
        $enc = fn ($id) => \Illuminate\Support\Facades\Crypt::encrypt($id);
        $payload = $this->employeePayload(['name' => 'Pegawai Ortu Baru', 'also_parent' => 1, 'link_students' => [$enc($a), $enc($b)]]);

        $this->actingAs($admin)->post(route('hr.employee.store'), $payload)->assertRedirect(route('hr.employee.index'));

        $user = User::withoutGlobalScopes()->where('email', $payload['email'])->first();
        $parent = Parents::withoutGlobalScopes()->where('id_user', $user->id)->first();
        $this->assertNotNull($parent);
        $this->assertSame('Pegawai Ortu Baru', $parent->name);
        $this->assertSame($payload['phone'], $parent->phone);
        $this->assertSame(self::BRANCH_ID, (int) $parent->branch_id);
        $this->assertSame($parent->id, (int) DB::table('student')->where('id', $a)->value('id_parent'));
        $this->assertSame($parent->id, (int) DB::table('student')->where('id', $b)->value('id_parent'));
        $this->assertTrue(User::withoutGlobalScopes()->find($user->id)->hasMultipleRoles());
    }

    public function test_unlink_removes_the_parent_record_created_from_the_employee(): void
    {
        [$user, $employee] = $this->employeeAccount();
        $a = $this->studentRow(null, 'Anak X');
        $result = $this->linker->linkNew($employee->id, [$a]);

        $unlink = $this->linker->unlink($result['parent_id']);

        $this->assertTrue($unlink['removed_parent']);
        $this->assertNull(DB::table('student')->where('id', $a)->value('id_parent'));
        $this->assertNotNull(DB::table('parent')->where('id', $result['parent_id'])->value('deleted_at'));
        $this->assertNull(Parents::withoutGlobalScopes()->where('id_user', $user->id)->first());
        $this->assertFalse(User::withoutGlobalScopes()->find($user->id)->isParent());
    }

    public function test_unlink_keeps_a_created_parent_record_that_is_already_in_use(): void
    {
        [, $employee] = $this->employeeAccount();
        $a = $this->studentRow(null, 'Anak Y');
        $result = $this->linker->linkNew($employee->id, [$a]);
        DB::table('parent')->where('id', $result['parent_id'])->update(['balance' => 50000]);

        $unlink = $this->linker->unlink($result['parent_id']);

        $this->assertFalse($unlink['removed_parent']);
        $parent = Parents::withoutGlobalScopes()->find($result['parent_id']);
        $this->assertNotNull($parent);
        $this->assertNull($parent->id_user);
        $this->assertSame($result['parent_id'], (int) DB::table('student')->where('id', $a)->value('id_parent'));
    }

    public function test_students_whose_parent_row_is_missing_or_deleted_get_a_new_parent_and_are_restored_on_unlink(): void
    {
        [, $employee] = $this->employeeAccount();
        $deleted = $this->parentRow(null, null, self::BRANCH_ID, 'Ortu Dihapus');
        $a = $this->studentRow($deleted->id, 'Anak Ortu Dihapus');
        DB::table('parent')->where('id', $deleted->id)->update(['deleted_at' => now()]);
        $dangling = $this->studentRow(999999991, 'Anak Ortu Hilang');

        $result = $this->linker->linkNew($employee->id, [$a, $dangling]);

        $this->assertSame($result['parent_id'], (int) DB::table('student')->where('id', $a)->value('id_parent'));
        $this->assertSame($result['parent_id'], (int) DB::table('student')->where('id', $dangling)->value('id_parent'));

        $this->linker->unlink($result['parent_id']);

        $this->assertSame($deleted->id, (int) DB::table('student')->where('id', $a)->value('id_parent'));
        $this->assertSame(999999991, (int) DB::table('student')->where('id', $dangling)->value('id_parent'));
    }

    public function test_new_parent_record_blockers(): void
    {
        [$user, $employee] = $this->employeeAccount();
        $orphan = $this->studentRow(null, 'Yatim');

        // santri yang sudah punya orang tua
        $owned = $this->studentRow($this->parentRow()->id, 'Sudah Punya');
        $this->assertContains('students_have_parent', $this->codes($this->linker->previewNew($employee->id, [$owned])['blockers']));

        // cabang berbeda
        $otherBranch = $this->studentRow(null, 'Cabang Lain', self::BRANCH_ID + 1);
        $this->assertContains('branch_mismatch', $this->codes($this->linker->previewNew($employee->id, [$otherBranch])['blockers']));

        // pegawai nonaktif
        DB::table('employee')->where('id', $employee->id)->update(['status' => 0]);
        $this->assertContains('employee_inactive', $this->codes($this->linker->previewNew($employee->id, [$orphan])['blockers']));
        DB::table('employee')->where('id', $employee->id)->update(['status' => 1]);

        // akun pegawai sudah punya data orang tua
        $this->parentRow($user->id, $user->phone);
        $this->assertContains('user_has_other_parent', $this->codes($this->linker->previewNew($employee->id, [$orphan])['blockers']));

        $this->expectException(AccountLinkException::class);
        $this->linker->linkNew($employee->id, [$orphan]);
    }

    public function test_employee_form_refuses_a_parent_that_already_has_an_account(): void
    {
        $admin = $this->createUser('admin');
        $parent = $this->parentRow($this->createUser('orang-tua')->id);
        $student = $this->studentRow($parent->id);
        $payload = $this->employeePayload(['also_parent' => 1, 'link_students' => [\Illuminate\Support\Facades\Crypt::encrypt($student)]]);

        $this->actingAs($admin)->post(route('hr.employee.store'), $payload)->assertSessionHasErrors('link_students');

        $this->assertNull(User::withoutGlobalScopes()->where('email', $payload['email'])->first());
    }

    public function test_employee_form_without_also_parent_does_not_link(): void
    {
        $admin = $this->createUser('admin');
        $parent = $this->parentRow();
        $student = $this->studentRow($parent->id);
        $payload = $this->employeePayload(['also_parent' => 0, 'link_students' => [\Illuminate\Support\Facades\Crypt::encrypt($student)]]);

        $this->actingAs($admin)->post(route('hr.employee.store'), $payload)->assertRedirect(route('hr.employee.index'));
        $this->assertNull(Parents::withoutGlobalScopes()->find($parent->id)->id_user);
    }

    public function test_only_admin_can_link_from_the_employee_form(): void
    {
        $hrStaff = $this->createUser('pegawai', [\App\Constants\Modules::AdminHumanResource]);
        $parent = $this->parentRow();
        $student = $this->studentRow($parent->id);
        $payload = $this->employeePayload(['also_parent' => 1, 'link_students' => [\Illuminate\Support\Facades\Crypt::encrypt($student)]]);

        $this->actingAs($hrStaff)->post(route('hr.employee.store'), $payload)->assertForbidden();
        $this->assertNull(Parents::withoutGlobalScopes()->find($parent->id)->id_user);
        $this->assertNull(User::withoutGlobalScopes()->where('email', $payload['email'])->first());
    }

    public function test_employee_update_keeps_linked_parent_phone_in_sync(): void
    {
        $admin = $this->createUser('admin');
        [$user, $employee] = $this->employeeAccount();
        $parent = $this->parentRow();
        $this->linker->link($employee->id, $parent->id);
        $newPhone = $this->phone();

        $this->actingAs($admin)->put(route('hr.employee.update', $employee->encrypted_id), $this->employeePayload([
            'nip' => $employee->nip ?: 'NIPX'.random_int(1000, 9999), 'name' => $user->name,
            'email' => 'baru'.random_int(1000, 99999).'@example.test', 'phone' => $newPhone, 'password' => '', 'password_confirm' => '',
        ]))->assertRedirect(route('hr.employee.index'));

        $this->assertSame($newPhone, User::withoutGlobalScopes()->find($user->id)->phone);
        $this->assertSame($newPhone, Parents::withoutGlobalScopes()->find($parent->id)->phone);
    }

    public function test_employee_form_pages_render_the_also_parent_section_for_admin_only(): void
    {
        $admin = $this->createUser('admin');
        [, $employee] = $this->employeeAccount();

        $this->actingAs($admin)->get(route('hr.employee.create'))->assertOk()->assertSee('also_parent', false)->assertSee(__('label.pick_children'));
        $this->actingAs($admin)->get(route('hr.employee.edit', $employee->encrypted_id))->assertOk()->assertSee('also_parent', false);

        $hrStaff = $this->createUser('pegawai', [\App\Constants\Modules::AdminHumanResource]);
        $this->actingAs($hrStaff)->get(route('hr.employee.create'))->assertOk()->assertDontSee('also_parent', false);
    }

    // ---------- form orang tua ----------

    public function test_parent_update_with_phone_of_an_employee_redirects_to_edit_with_merge_hint(): void
    {
        $admin = $this->createUser('admin');
        [$staff] = $this->employeeAccount();
        $parentUser = $this->createUser('orang-tua');
        $parent = $this->parentRow($parentUser->id, $parentUser->phone);

        $response = $this->actingAs($admin)->put(route('academic.parent.update', $parent->encrypted_id), [
            'name' => 'Nama Diubah', 'phone' => $staff->phone, 'gender' => 'male',
        ]);

        $response->assertRedirectContains('academic/parent/');
        $this->assertStringEndsWith('/edit', $response->headers->get('Location'));
        $response->assertSessionHasErrors();
        $this->assertStringContainsString('Gabungkan', session('errors')->first());
        $this->assertSame('Nama Diubah', session()->getOldInput('name'));
        $this->assertNotSame($staff->phone, Parents::withoutGlobalScopes()->find($parent->id)->phone);
    }

    public function test_parent_edit_page_shows_merge_section_for_admin_and_linked_state(): void
    {
        $admin = $this->createUser('admin');
        [, $employee] = $this->employeeAccount();
        $parent = $this->parentRow();

        $this->actingAs($admin)->get(route('academic.parent.edit', $parent->encrypted_id))
            ->assertOk()->assertSee(__('label.link_to_employee'))->assertSee('btn-link-employee', false);

        $this->linker->link($employee->id, $parent->id);
        $this->actingAs($admin)->get(route('academic.parent.edit', $parent->encrypted_id))
            ->assertOk()->assertSee('btn-unlink-employee', false)->assertSee($employee->name);
    }

    public function test_editing_a_merged_parent_never_changes_the_employee_account(): void
    {
        $admin = $this->createUser('admin');
        [$user, $employee] = $this->employeeAccount();
        $parent = $this->parentRow();
        $this->linker->link($employee->id, $parent->id);
        $before = DB::table('users')->where('id', $user->id)->first();

        $this->actingAs($admin)->put(route('academic.parent.update', $parent->encrypted_id), [
            'name' => 'Nama Ortu Baru', 'phone' => '081377770000', 'gender' => 'female', 'password' => 'newpassword1', 'password_confirm' => 'newpassword1',
        ])->assertRedirect(route('academic.parent.index'));

        $after = DB::table('users')->where('id', $user->id)->first();
        $this->assertSame($before->name, $after->name);
        $this->assertSame($before->phone, $after->phone);
        $this->assertSame($before->password, $after->password);
        $fresh = Parents::withoutGlobalScopes()->find($parent->id);
        $this->assertSame('Nama Ortu Baru', $fresh->name);
        $this->assertSame($user->phone, $fresh->phone); // nomor tetap mengikuti akun pegawai
    }

    // ---------- registrasi orang tua ----------

    public function test_registration_with_an_employee_phone_links_instead_of_creating_a_user(): void
    {
        [$user, $employee] = $this->employeeAccount();
        $parent = $this->parentRow(null, $user->phone);
        $this->studentRow($parent->id);
        DB::table('parent')->where('id', $parent->id)->update(['token' => '135790', 'token_expired_at' => now()->addMinutes(10)]);
        $usersBefore = User::withoutGlobalScopes()->count();

        $this->post(route('auth.store.register.verification', $parent->encrypted_id), ['code' => '135790'])
            ->assertRedirect(route('base'))
            ->assertSessionHas('success');

        $this->assertSame($usersBefore, User::withoutGlobalScopes()->count());
        $fresh = Parents::withoutGlobalScopes()->find($parent->id);
        $this->assertSame($user->id, (int) $fresh->id_user);
        $this->assertNull($fresh->token);
        $this->assertStringContainsString('akun pegawai', session('success'));
    }

    public function test_registration_with_an_inactive_employee_phone_creates_no_user_and_links_nothing(): void
    {
        [$user, $employee] = $this->employeeAccount();
        DB::table('employee')->where('id', $employee->id)->update(['status' => 0]);
        $parent = $this->parentRow(null, $user->phone);
        DB::table('parent')->where('id', $parent->id)->update(['token' => '246810', 'token_expired_at' => now()->addMinutes(10)]);
        $usersBefore = User::withoutGlobalScopes()->count();

        $this->post(route('auth.store.register.verification', $parent->encrypted_id), ['code' => '246810'])
            ->assertRedirect(route('auth.register'))
            ->assertSessionHasErrors();

        $this->assertNull(Parents::withoutGlobalScopes()->find($parent->id)->id_user);
        $this->assertSame($usersBefore, User::withoutGlobalScopes()->count());
        // tidak ada tanda OTP: halaman set password tetap tertutup
        $this->post(route('auth.store.register-password', $parent->encrypted_id), ['password' => 'password123', 'password_confirm' => 'password123'])
            ->assertRedirect(route('auth.register'));
        $this->assertSame($usersBefore, User::withoutGlobalScopes()->count());
    }

    public function test_registration_with_a_staff_phone_without_employee_row_is_not_registered_as_new_user(): void
    {
        $kasir = $this->createUser('kasir');
        $parent = $this->parentRow(null, $kasir->phone);
        DB::table('parent')->where('id', $parent->id)->update(['token' => '112233', 'token_expired_at' => now()->addMinutes(10)]);
        $usersBefore = User::withoutGlobalScopes()->count();

        $this->post(route('auth.store.register.verification', $parent->encrypted_id), ['code' => '112233'])
            ->assertRedirect(route('auth.register'));

        $this->assertSame($usersBefore, User::withoutGlobalScopes()->count());
        $this->assertNull(Parents::withoutGlobalScopes()->find($parent->id)->id_user);
    }
}
