<?php

namespace Tests\Feature;

use App\Constants\Modules;
use Illuminate\Support\Facades\DB;

/**
 * Halaman Hak Akses pegawai: modul dan jenjang per modul.
 */
class EmployeeRightsPageTest extends ModuleAccessTestCase
{
    private function rightsUrl($employee): string
    {
        return route('hr.employee.store.rights', $employee->encrypted_id);
    }

    private function seedModules(): void
    {
        foreach ([Modules::KepalaSekolah => 'Kepala Sekolah', Modules::KasirUnit => 'Kasir Unit', Modules::Kasir => 'Kasir'] as $id => $name) {
            DB::table('module')->updateOrInsert(['id' => $id], ['name' => $name, 'description' => $name]);
        }
    }

    public function test_kasir_unit_module_requires_at_least_one_level(): void
    {
        $this->seedModules();
        $admin = $this->createUser('admin');
        $employee = $this->createEmployee($this->createUser('pegawai'));

        $this->actingAs($admin)->post($this->rightsUrl($employee), ['module' => [Modules::KasirUnit]])
            ->assertSessionHasErrors('level_education.'.Modules::KasirUnit);

        $this->assertDatabaseMissing('module_rights', ['id_user' => $employee->id_user, 'id_module' => Modules::KasirUnit]);
    }

    public function test_levels_are_saved_per_module_and_cleared_when_module_removed(): void
    {
        $this->seedModules();
        $admin = $this->createUser('admin');
        $employee = $this->createEmployee($this->createUser('pegawai'));

        $this->actingAs($admin)->post($this->rightsUrl($employee), [
            'module' => [Modules::KepalaSekolah, Modules::KasirUnit],
            'level_education' => [Modules::KepalaSekolah => ['sd'], Modules::KasirUnit => ['smp', 'sma']],
        ])->assertSessionHasNoErrors();

        $user = $employee->user()->first();
        $this->assertSame(['sd'], $user->educationLevelValues(Modules::KepalaSekolah));
        $this->assertEqualsCanonicalizing(['smp', 'sma'], $user->educationLevelValues(Modules::KasirUnit));

        $this->actingAs($admin)->post($this->rightsUrl($employee), [
            'module' => [Modules::KepalaSekolah],
            'level_education' => [Modules::KepalaSekolah => ['sd']],
        ])->assertSessionHasNoErrors();

        $user = $employee->user()->first();
        $this->assertSame(['sd'], $user->educationLevelValues(Modules::KepalaSekolah));
        $this->assertSame([], $user->educationLevelValues(Modules::KasirUnit));
        $this->assertDatabaseMissing('module_rights', ['id_user' => $user->id, 'id_module' => Modules::KasirUnit]);
    }

    public function test_saving_without_any_module_is_allowed(): void
    {
        $this->seedModules();
        $admin = $this->createUser('admin');
        $employee = $this->createEmployee($this->createUser('pegawai', [Modules::Kasir]));

        $this->actingAs($admin)->post($this->rightsUrl($employee))->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('module_rights', ['id_user' => $employee->id_user]);
    }

    public function test_invalid_level_is_rejected(): void
    {
        $this->seedModules();
        $admin = $this->createUser('admin');
        $employee = $this->createEmployee($this->createUser('pegawai'));

        $this->actingAs($admin)->post($this->rightsUrl($employee), [
            'module' => [Modules::KasirUnit],
            'level_education' => [Modules::KasirUnit => ['smk']],
        ])->assertSessionHasErrors();
    }

    public function test_only_admin_can_open_rights_page(): void
    {
        $this->seedModules();
        $employee = $this->createEmployee($this->createUser('pegawai'));

        $this->actingAs($this->createUser('pegawai', [Modules::Kasir]))
            ->post($this->rightsUrl($employee), ['module' => [Modules::Kasir]])
            ->assertRedirect(route('base'));

        $this->actingAs($this->createUser('admin'))
            ->get(route('hr.employee.rights', $employee->encrypted_id))
            ->assertOk()
            ->assertSee('level_education['.Modules::KasirUnit.'][]', false);
    }
}
