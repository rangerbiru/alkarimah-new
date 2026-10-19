<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Pengecekan "wajib punya santri" saat login hanya untuk akun orang tua dan pegawai yang EKSPLISIT nonaktif
 * (employee.status = 0) yang juga orang tua. Nilai kosong / tidak dikenal tidak dianggap nonaktif.
 */
class LoginEmployeeStatusTest extends ModuleAccessTestCase
{
    private const NO_STUDENTS = 'Data Siswa Anda belum ada, harap hubungi Admin untuk mendaftarkan data siswa Anda';

    private function password(User $user, string $password): void
    {
        DB::table('users')->where('id', $user->id)->update(['password' => Hash::make($password)]);
    }

    private function parentRow(User $user, int $children): int
    {
        $parentId = DB::table('parent')->insertGetId([
            'id_user' => $user->id, 'name' => 'Ortu '.$user->id, 'phone' => $user->phone, 'gender' => 'male', 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);

        for ($i = 0; $i < $children; $i++) {
            DB::table('student')->insert([
                'id_parent' => $parentId, 'id_class' => 1, 'nis' => 'N'.random_int(1000000, 9999999), 'name' => 'Anak '.$i,
                'gender' => 'male', 'religion' => 'Islam', 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
            ]);
        }

        return $parentId;
    }

    /** @return array{0: User, 1: Employee} */
    private function staff(string $role = 'pegawai', array $employee = []): array
    {
        $user = $this->createUser($role);
        $this->password($user, 'pw-staf');
        $row = $this->createEmployee($user);
        if ($employee !== []) {
            DB::table('employee')->where('id', $row->id)->update($employee);
        }

        return [$user, $row->fresh()];
    }

    private function loginAs(string $username, string $password): ?int
    {
        Auth::forgetGuards();
        $this->flushSession();
        $this->post(route('auth.authenticate'), ['username' => $username, 'password' => $password]);

        return Auth::id();
    }

    public function test_server_case_orphan_parent_account_is_refused_but_employee_password_works(): void
    {
        // Akun 1213: kepala sekolah aktif, punya data orang tua dengan 1 anak. Akun 762: akun orang tua lama
        // yang data orang tuanya sudah dihapus (soft delete), nomor HP sama, password berbeda
        [$staff] = $this->staff('kepala-sekolah');
        $this->parentRow($staff, 1);

        $orphan = $this->createUser('orang-tua');
        DB::table('users')->where('id', $orphan->id)->update(['phone' => $staff->phone]);
        $this->password($orphan, 'pw-ortu-lama');
        $deleted = $this->parentRow($orphan, 0);
        DB::table('parent')->where('id', $deleted)->update(['deleted_at' => now()]);

        // Password pegawai: masuk sebagai kepala sekolah, tanpa pengecekan santri
        $this->assertSame($staff->id, $this->loginAs($staff->phone, 'pw-staf'));

        // Password akun orang tua lama: akun itu sendiri yang cocok, dan data orang tuanya sudah dihapus
        Auth::forgetGuards();
        $this->flushSession();
        $this->post(route('auth.authenticate'), ['username' => $staff->phone, 'password' => 'pw-ortu-lama'])
            ->assertSessionHasErrors(['useraccount' => __('string.login_parent_account_orphan')]);
        $this->assertNull(Auth::id());
    }

    public function test_active_staff_with_unknown_employment_status_still_logs_in_as_staff(): void
    {
        // status_employment di luar 1/2/3 (tidak dikenal) tidak mengubah apa pun
        [$user] = $this->staff('pegawai', ['status_employment' => 0]);
        $this->parentRow($user, 0);

        $this->assertSame($user->id, $this->loginAs($user->phone, 'pw-staf'));
        $fresh = User::withoutGlobalScopes()->find($user->id);
        $this->assertFalse($fresh->acts_as_parent_only);
        $this->assertTrue($fresh->has_dual_role);
    }

    public function test_active_staff_who_is_a_parent_without_children_is_not_checked_for_students(): void
    {
        foreach (['pegawai', 'kepala-sekolah', 'kasir'] as $role) {
            [$user] = $this->staff($role);
            $this->parentRow($user, 0);

            $this->assertSame($user->id, $this->loginAs($user->phone, 'pw-staf'), $role);
        }
    }

    public function test_status_that_is_empty_or_unknown_is_not_inactive(): void
    {
        [$user, $employee] = $this->staff();
        $this->parentRow($user, 1);

        foreach ([null, 1, 2] as $status) {
            $model = User::withoutGlobalScopes()->find($user->id);
            $model->setRelation('employee', (new Employee)->forceFill(['status' => $status]));

            $this->assertFalse($model->is_inactive_employee, 'status='.var_export($status, true));
            $this->assertFalse($model->acts_as_parent_only);
        }

        $model = User::withoutGlobalScopes()->find($user->id);
        $model->setRelation('employee', (new Employee)->forceFill(['status' => 0]));
        $this->assertTrue($model->is_inactive_employee);
        $this->assertTrue($model->acts_as_parent_only);
    }

    public function test_explicitly_inactive_employee_who_is_a_parent_with_children_enters_as_parent(): void
    {
        [$user] = $this->staff('pegawai', ['status' => 0]);
        $this->parentRow($user, 1);

        $this->assertSame($user->id, $this->loginAs($user->phone, 'pw-staf'));
        $fresh = User::withoutGlobalScopes()->find($user->id);
        $this->assertTrue($fresh->acts_as_parent_only);
        $this->assertSame('orang-tua', $fresh->menu_role->value);
        $this->assertFalse($fresh->has_dual_role);
    }

    public function test_explicitly_inactive_employee_who_is_a_parent_without_children_is_refused(): void
    {
        [$user] = $this->staff('pegawai', ['status' => 0]);
        $this->parentRow($user, 0);

        Auth::forgetGuards();
        $this->post(route('auth.authenticate'), ['username' => $user->phone, 'password' => 'pw-staf'])
            ->assertSessionHasErrors(['useraccount' => self::NO_STUDENTS]);
        $this->assertNull(Auth::id());
    }

    public function test_parent_without_students_is_still_refused(): void
    {
        $user = $this->createUser('orang-tua');
        $this->password($user, 'pw-ortu');
        $this->parentRow($user, 0);

        Auth::forgetGuards();
        $this->post(route('auth.authenticate'), ['username' => $user->phone, 'password' => 'pw-ortu'])
            ->assertSessionHasErrors(['useraccount' => self::NO_STUDENTS]);
        $this->assertNull(Auth::id());

        // Dengan satu santri aktif: masuk
        $user2 = $this->createUser('orang-tua');
        $this->password($user2, 'pw-ortu');
        $this->parentRow($user2, 1);
        $this->assertSame($user2->id, $this->loginAs($user2->phone, 'pw-ortu'));
    }

    public function test_edit_employee_form_cannot_change_the_employee_status_column(): void
    {
        $admin = $this->createUser('admin');
        [$user, $employee] = $this->staff();
        $this->parentRow($user, 1);

        $this->actingAs($admin)->put(route('hr.employee.update', $employee->encrypted_id), [
            'nip' => 'NIP'.random_int(100000, 999999), 'name' => 'Nama', 'phone' => $user->phone, 'email' => 'x'.random_int(100000, 999999).'@example.test',
            'gender' => 'male', 'marital_status' => '0', 'address' => 'A', 'placement' => 'P', 'status_employment' => '1',
            'status_teacher' => '0', 'task_main' => 'T', 'is_parent' => '1', 'status' => '0',
        ])->assertRedirect(route('hr.employee.index'));

        $this->assertSame(1, (int) DB::table('employee')->where('id', $employee->id)->value('status'));
    }
}
