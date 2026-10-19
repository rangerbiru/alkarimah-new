<?php

namespace Tests\Feature;

use App\Helpers\Common;
use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RendersFormValues;

/**
 * Halaman edit yang memakai x-form.select / x-form.radio dengan :old: nilai yang tersimpan harus
 * tampil ter-selected / checked, supaya menyimpan form tidak mengosongkannya.
 */
class EditPagesPreselectTest extends ModuleAccessTestCase
{
    use RendersFormValues;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createUser('admin');
    }

    private function page(string $route, $model): string
    {
        return $this->actingAs($this->admin)->get(route($route, $model->encrypted_id))->assertOk()->getContent();
    }

    public function test_employee_edit_shows_saved_position_and_status(): void
    {
        $position = $this->insertRow('position', ['name' => 'Guru', 'branch_id' => self::BRANCH_ID]);
        $employee = $this->createEmployee($this->createUser('pegawai'));
        DB::table('employee')->where('id', $employee->id)->update([
            'id_position' => $position, 'status_employment' => 3, 'status_teacher' => 0, 'marital_status' => 1, 'gender' => 'female',
        ]);

        $html = $this->page('hr.employee.edit', $employee->fresh());

        $this->assertSame([(string) $position], $this->selectedIn($html, 'id_position'));
        $this->assertSame(['3'], $this->checkedIn($html, 'status_employment'));
        $this->assertSame(['0'], $this->checkedIn($html, 'status_teacher'));
        $this->assertSame(['1'], $this->checkedIn($html, 'marital_status'));
        $this->assertSame(['female'], $this->checkedIn($html, 'gender'));
    }

    public function test_user_edit_shows_saved_gender(): void
    {
        $user = $this->createUser('bendahara');
        DB::table('users')->where('id', $user->id)->update(['gender' => 'female']);

        $html = $this->page('user.edit', User::withoutGlobalScopes()->find($user->id));

        $this->assertSame(['female'], $this->checkedIn($html, 'gender'));
    }

    public function test_parent_edit_shows_saved_gender(): void
    {
        $id = $this->insertRow('parent', ['name' => 'Ortu', 'phone' => '08'.random_int(100000000, 999999999), 'gender' => 'female', 'branch_id' => self::BRANCH_ID]);
        $parent = \App\Models\Parents::withoutGlobalScopes()->find($id);

        $html = $this->page('academic.parent.edit', $parent);

        $this->assertSame(['female'], $this->checkedIn($html, 'gender'));
    }

    public function test_student_edit_shows_saved_dropdowns_and_radios(): void
    {
        $levels = array_keys(Common::option('education_level'));
        $asrama = $this->insertRow('asrama', ['name' => 'Asrama A', 'branch_id' => self::BRANCH_ID]);
        $halaqah = $this->insertRow('halaqah', ['name' => 'Halaqah A', 'branch_id' => self::BRANCH_ID]);
        $class = $this->insertRow('class', ['name' => 'Kelas A', 'level_education' => $levels[0], 'branch_id' => self::BRANCH_ID]);
        $id = $this->insertRow('student', [
            'name' => 'Santri', 'nis' => 'N'.random_int(1000000, 9999999), 'gender' => 'female', 'religion' => 'Kristen',
            'id_class' => $class, 'id_asrama' => $asrama, 'id_halaqah' => $halaqah, 'beasiswa' => 1, 'status' => 1,
            'branch_id' => self::BRANCH_ID,
        ]);

        $html = $this->page('academic.student.edit', Student::withoutGlobalScopes()->find($id));

        $this->assertSame(['female'], $this->checkedIn($html, 'gender'));
        $this->assertSame(['Kristen'], $this->selectedIn($html, 'religion'));
        $this->assertSame([(string) $asrama], $this->selectedIn($html, 'id_asrama'));
        $this->assertSame([(string) $halaqah], $this->selectedIn($html, 'id_halaqah'));
        $this->assertSame([$levels[0]], $this->selectedIn($html, 'level_education'));
    }

    public function test_class_edit_shows_saved_dropdowns(): void
    {
        $levels = array_keys(Common::option('education_level'));
        $teacher = $this->createUser('wali-kelas');
        $id = $this->insertRow('class', ['name' => 'Kelas B', 'id_wali_kelas' => $teacher->id, 'level_education' => $levels[1] ?? $levels[0], 'level_class' => 2, 'branch_id' => self::BRANCH_ID]);

        $html = $this->page('academic.class.edit', \App\Models\Classroom::withoutGlobalScopes()->find($id));

        $this->assertSame([(string) $teacher->id], $this->selectedIn($html, 'id_wali_kelas'));
        $this->assertSame([$levels[1] ?? $levels[0]], $this->selectedIn($html, 'level_education'));
        // level_class (jenjang kelas) diisi lewat AJAX oleh JS halaman, bukan oleh komponen: uji manual
        $this->assertStringContainsString('let class_level = "2"', $html);
    }

    public function test_bill_type_edit_shows_saved_period_and_spp(): void
    {
        $id = $this->insertRow('bill_type', ['name' => 'SPP', 'period' => 3, 'spp' => 1, 'branch_id' => self::BRANCH_ID]);
        $type = \App\Models\BillType::withoutGlobalScopes()->find($id);

        $html = $this->actingAs($this->createUser('kasir'))->get(route('finance.bill.type.edit', $type->encrypted_id))->assertOk()->getContent();

        $this->assertSame(['3'], $this->selectedIn($html, 'period'));
        $this->assertSame(['1'], $this->checkedIn($html, 'spp'));
    }

    public function test_allowance_edit_shows_saved_category(): void
    {
        $id = $this->insertRow('allowance', ['name' => 'Tunjangan', 'category' => 2, 'branch_id' => self::BRANCH_ID]);
        $allowance = \App\Models\Allowance::withoutGlobalScopes()->find($id);

        $html = $this->actingAs($this->createUser('bendahara'))->get(route('hr.allowance.edit', $allowance->encrypted_id))->assertOk()->getContent();

        $this->assertSame(['2'], $this->selectedIn($html, 'category'));
    }

    public function test_master_violation_edit_shows_saved_level_and_status(): void
    {
        $id = $this->insertRow('violation_types', ['code' => 'V'.random_int(100, 999), 'group' => 'A', 'impact_level' => 'sedang', 'description' => 'x', 'points' => 5, 'status' => 'non aktif']);

        $html = $this->actingAs($this->admin)->get(route('hr.violation.edit', $id))->assertOk()->getContent();

        $this->assertSame(['sedang'], $this->selectedIn($html, 'impact_level'));
        $this->assertSame(['non aktif'], $this->selectedIn($html, 'status'));
    }

    public function test_parent_student_edit_shows_saved_values(): void
    {
        $user = $this->createUser('orang-tua');
        $parentId = $this->insertRow('parent', ['id_user' => $user->id, 'name' => 'Ortu', 'phone' => $user->phone, 'gender' => 'male', 'branch_id' => self::BRANCH_ID]);
        $id = $this->insertRow('student', [
            'id_parent' => $parentId, 'name' => 'Santri', 'nis' => 'N'.random_int(1000000, 9999999), 'gender' => 'female',
            'religion' => 'Hindu', 'status' => 1, 'branch_id' => self::BRANCH_ID,
        ]);

        $html = $this->actingAs($user)->get(route('academic.student.edit', Student::withoutGlobalScopes()->find($id)->encrypted_id))->assertOk()->getContent();

        $this->assertSame(['female'], $this->checkedIn($html, 'gender'));
        $this->assertSame(['Hindu'], $this->selectedIn($html, 'religion'));
    }

    public function test_bill_setup_edit_shows_saved_year_and_months(): void
    {
        $year = $this->insertRow('year', ['start_year' => 2026, 'start_month' => 7, 'end_year' => 2027, 'end_month' => 6, 'status' => 1, 'branch_id' => self::BRANCH_ID]);
        $type = $this->insertRow('bill_type', ['name' => 'Gedung', 'period' => 1, 'spp' => 0, 'branch_id' => self::BRANCH_ID]);
        $id = $this->insertRow('bill', [
            'id_year' => $year, 'id_type' => $type, 'name' => 'Tagihan', 'nominal' => 1000, 'billing_date' => '2026-08-05', 'due_date' => '2026-08-20',
            'start_month' => 9, 'start_year' => 2026, 'end_month' => 3, 'end_year' => 2027, 'branch_id' => self::BRANCH_ID,
        ]);
        $bill = \App\Models\Bill::withoutGlobalScopes()->find($id);

        $html = $this->actingAs($this->createUser('kasir'))->get(route('finance.bill.setup.edit', $bill->encrypted_id))->assertOk()->getContent();

        $this->assertSame([(string) $year], $this->selectedIn($html, 'id_year'));
        $this->assertSame(['5'], $this->selectedIn($html, 'billing_date2'));
        $this->assertSame(['20'], $this->selectedIn($html, 'due_date2'));
        $this->assertSame(['9'], $this->selectedIn($html, 'start_month'));
        $this->assertSame(['2026'], $this->selectedIn($html, 'start_year'));
        $this->assertSame(['3'], $this->selectedIn($html, 'end_month'));
        $this->assertSame(['2027'], $this->selectedIn($html, 'end_year'));
    }
}
