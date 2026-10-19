<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RendersFormValues;

/**
 * Form pegawai (create / edit): semua input form tersimpan dan tampil kembali di halaman edit,
 * dan kolom sensitif (id_user, branch_id, role, salary, status) tidak bisa diisi dari request.
 */
class EmployeeFormTest extends ModuleAccessTestCase
{
    use RendersFormValues;

    private function position(string $name, int $branch = self::BRANCH_ID): int
    {
        return $this->insertRow('position', ['name' => $name, 'branch_id' => $branch]);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'nip' => 'NIP'.random_int(100000, 999999),
            'nik' => (string) random_int(1000000000000000, 9999999999999999),
            'name' => 'Pegawai Uji',
            'phone' => '08'.random_int(100000000, 999999999),
            'email' => 'uji'.random_int(100000, 999999).'@example.test',
            'gender' => 'female',
            'marital_status' => '1',
            'education' => 'S1',
            'address' => 'Jl. Uji 1',
            'id_position' => '',
            'placement' => 'Gedung A',
            'status_employment' => '2',
            'status_teacher' => '0',
            'task_main' => 'Mengajar',
            'task_additional' => 'Piket',
            'is_parent' => '0',
            'password' => 'password123',
            'password_confirm' => 'password123',
        ], $override);
    }

    private function createEmployeeViaForm(array $override = []): Employee
    {
        $this->actingAs($this->admin)->post(route('hr.employee.store'), $this->payload($override))
            ->assertRedirect(route('hr.employee.index'));

        return Employee::withoutGlobalScopes()->latest('id')->firstOrFail();
    }

    /** Payload update yang menjaga identitas unik pegawai */
    private function updatePayload(Employee $employee, array $override = []): array
    {
        return $this->payload([
            'nip' => $employee->nip, 'nik' => $employee->nik, 'phone' => $employee->phone, 'email' => $employee->email,
            'password' => '', 'password_confirm' => '',
        ] + $override);
    }

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createUser('admin');
    }

    public function test_create_saves_every_form_field(): void
    {
        $position = $this->position('Guru');

        $employee = $this->createEmployeeViaForm(['id_position' => (string) $position, 'name' => 'Siti Aminah']);

        $this->assertSame($position, (int) $employee->id_position);
        $this->assertSame('2', $employee->status_employment->value);
        $this->assertFalse((bool) $employee->status_teacher);
        $this->assertTrue((bool) $employee->marital_status);
        $this->assertSame('Gedung A', $employee->placement);
        $this->assertSame('Mengajar', $employee->task_main);
        $this->assertSame('Piket', $employee->task_additional);
        $this->assertSame('S1', $employee->education);
        $this->assertSame('Jl. Uji 1', $employee->address);
        $this->assertSame('Siti Aminah', $employee->name);
        $this->assertSame('female', $employee->gender->value);
        $this->assertNotNull($employee->nik);
        $this->assertSame(self::BRANCH_ID, (int) $employee->branch_id);
    }

    public function test_update_saves_every_form_field_and_edit_page_shows_them_back(): void
    {
        $first = $this->position('Guru');
        $second = $this->position('Kepala Madrasah');
        $employee = $this->createEmployeeViaForm(['id_position' => (string) $first]);

        $this->actingAs($this->admin)->put(route('hr.employee.update', $employee->encrypted_id), $this->updatePayload($employee, [
            'name' => 'Nama Baru', 'gender' => 'male', 'marital_status' => '0', 'education' => 'S2', 'address' => 'Alamat baru',
            'id_position' => (string) $second, 'placement' => 'Gedung B', 'status_employment' => '3', 'status_teacher' => '1',
            'task_main' => 'Tugas baru', 'task_additional' => 'Tambahan baru',
        ]))->assertRedirect(route('hr.employee.index'));

        $row = DB::table('employee')->where('id', $employee->id)->first();
        $this->assertSame($second, (int) $row->id_position);
        $this->assertSame(3, (int) $row->status_employment);
        $this->assertSame(1, (int) $row->status_teacher);
        $this->assertSame('Gedung B', $row->placement);
        $this->assertSame('Tugas baru', $row->task_main);
        $this->assertSame('Tambahan baru', $row->task_additional);
        $this->assertSame('S2', $row->education);

        $html = $this->actingAs($this->admin)->get(route('hr.employee.edit', $employee->encrypted_id))->assertOk()->getContent();
        $this->assertSame([(string) $second], $this->selectedIn($html, 'id_position'));
        $this->assertSame(['3'], $this->checkedIn($html, 'status_employment'));
        $this->assertSame(['1'], $this->checkedIn($html, 'status_teacher'));
        $this->assertSame(['0'], $this->checkedIn($html, 'marital_status'));
        $this->assertSame(['male'], $this->checkedIn($html, 'gender'));
        $this->assertStringContainsString('value="S2"', $html);
        $this->assertStringContainsString('Gedung B', $html);
    }

    public function test_saving_the_edit_form_unchanged_keeps_position_and_status(): void
    {
        $position = $this->position('Guru');
        $employee = $this->createEmployeeViaForm(['id_position' => (string) $position, 'status_employment' => '3']);

        // Kirim ulang persis nilai yang ditampilkan form edit
        $html = $this->actingAs($this->admin)->get(route('hr.employee.edit', $employee->encrypted_id))->getContent();
        $this->actingAs($this->admin)->put(route('hr.employee.update', $employee->encrypted_id), $this->updatePayload($employee, [
            'id_position' => $this->selectedIn($html, 'id_position')[0] ?? '',
            'status_employment' => $this->checkedIn($html, 'status_employment')[0] ?? '',
        ]))->assertRedirect(route('hr.employee.index'));

        $row = DB::table('employee')->where('id', $employee->id)->first();
        $this->assertSame($position, (int) $row->id_position);
        $this->assertSame(3, (int) $row->status_employment);
    }

    public function test_update_does_not_blank_fields_the_form_did_not_send(): void
    {
        $position = $this->position('Guru');
        $employee = $this->createEmployeeViaForm(['id_position' => (string) $position]);

        $payload = $this->updatePayload($employee);
        unset($payload['id_position'], $payload['education'], $payload['task_additional']);

        $this->actingAs($this->admin)->put(route('hr.employee.update', $employee->encrypted_id), $payload)
            ->assertRedirect(route('hr.employee.index'));

        $row = DB::table('employee')->where('id', $employee->id)->first();
        $this->assertSame($position, (int) $row->id_position);
        $this->assertSame('S1', $row->education);
        $this->assertSame('Piket', $row->task_additional);
    }

    public function test_sensitive_columns_cannot_be_set_from_the_request(): void
    {
        $employee = $this->createEmployeeViaForm();
        $other = $this->createUser('pegawai');
        $before = DB::table('employee')->where('id', $employee->id)->first();

        $this->actingAs($this->admin)->put(route('hr.employee.update', $employee->encrypted_id), $this->updatePayload($employee, [
            'branch_id' => 1, 'id_user' => $other->id, 'role' => 'admin', 'salary' => 99999999, 'status' => 0,
            'created_by' => 5, 'deleted_at' => now()->toDateTimeString(),
        ]))->assertRedirect(route('hr.employee.index'));

        $after = DB::table('employee')->where('id', $employee->id)->first();
        $this->assertSame($before->branch_id, $after->branch_id);
        $this->assertSame($before->id_user, $after->id_user);
        $this->assertSame($before->salary, $after->salary);
        $this->assertSame($before->status, $after->status);
        $this->assertSame($before->created_by, $after->created_by);
        $this->assertNull($after->deleted_at);
        $this->assertSame('pegawai', DB::table('users')->where('id', $employee->id_user)->value('role'));
    }

    public function test_create_ignores_sensitive_columns_from_the_request(): void
    {
        $other = $this->createUser('pegawai');

        $employee = $this->createEmployeeViaForm(['branch_id' => 1, 'id_user' => $other->id, 'role' => 'admin', 'salary' => 123456, 'status' => 0]);

        $this->assertSame(self::BRANCH_ID, (int) $employee->branch_id);
        $this->assertNotSame($other->id, (int) $employee->id_user);
        $this->assertEquals(0, $employee->salary);
        $this->assertSame('pegawai', DB::table('users')->where('id', $employee->id_user)->value('role'));
    }

    public function test_position_must_exist_in_the_admin_branch(): void
    {
        $employee = $this->createEmployeeViaForm();
        $foreign = $this->position('Cabang lain', 1);
        $deleted = $this->position('Dihapus');
        DB::table('position')->where('id', $deleted)->update(['deleted_at' => now()]);

        foreach ([999999, $foreign, $deleted] as $bad) {
            $this->actingAs($this->admin)->put(route('hr.employee.update', $employee->encrypted_id), $this->updatePayload($employee, ['id_position' => (string) $bad]))
                ->assertSessionHasErrors('id_position');
        }

        $this->assertNull(DB::table('employee')->where('id', $employee->id)->value('id_position'));
    }

    public function test_employment_status_must_be_a_known_value(): void
    {
        $employee = $this->createEmployeeViaForm();

        foreach (['0', '4', 'x'] as $bad) {
            $this->actingAs($this->admin)->put(route('hr.employee.update', $employee->encrypted_id), $this->updatePayload($employee, ['status_employment' => $bad]))
                ->assertSessionHasErrors('status_employment');
        }

        $this->assertSame(2, (int) DB::table('employee')->where('id', $employee->id)->value('status_employment'));
    }
}
