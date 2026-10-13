<?php

namespace Tests\Feature;

use App\Constants\Modules;
use App\Models\User;

/**
 * Menu sidebar dan kartu dashboard untuk pegawai pemegang modul TU.
 */
class EmployeeModuleMenuTest extends ModuleAccessTestCase
{
    private function pegawai(array $modules = []): User
    {
        $user = $this->createUser('pegawai', $modules);
        $this->createEmployee($user);

        return $user;
    }

    public function test_kasir_unit_module_sidebar_has_no_pending_menu(): void
    {
        $user = $this->pegawai([Modules::KasirUnit]);

        $html = $this->actingAs($user)->get(route('finance.transaction.history'))->assertOk()->getContent();

        $this->assertStringContainsString(route('finance.transaction.bill.index'), $html);
        $this->assertStringContainsString(route('finance.balance.topup'), $html);
        $this->assertStringNotContainsString(route('finance.transaction.pending'), $html);
    }

    public function test_kasir_module_sidebar_keeps_pending_menu(): void
    {
        $user = $this->pegawai([Modules::Kasir]);

        $html = $this->actingAs($user)->get(route('finance.transaction.history'))->assertOk()->getContent();

        $this->assertStringContainsString(route('finance.transaction.pending'), $html);
    }

    public function test_pegawai_without_module_sees_no_tu_menu(): void
    {
        $user = $this->pegawai();

        $html = $this->actingAs($user)->get(route('employee.tahfidz.index'))->getContent();

        $this->assertStringNotContainsString(route('finance.transaction.bill.index'), $html);
        $this->assertStringNotContainsString(route('finance.transaction.pending'), $html);
    }

    public function test_dashboard_cards_follow_modules(): void
    {
        $unit = $this->pegawai([Modules::KasirUnit]);
        $html = $this->actingAs($unit)->get(route('dashboard.index'))->getContent();
        $this->assertStringContainsString(route('finance.transaction.bill.index'), $html);
        $this->assertStringNotContainsString(route('finance.transaction.pending'), $html);
        $this->assertStringNotContainsString(route('finance.bill.index'), $html);

        $kasir = $this->pegawai([Modules::Kasir]);
        $html = $this->actingAs($kasir)->get(route('dashboard.index'))->getContent();
        $this->assertStringContainsString(route('finance.bill.index'), $html);

        $none = $this->pegawai();
        $html = $this->actingAs($none)->get(route('dashboard.index'))->getContent();
        $this->assertStringNotContainsString(route('finance.transaction.bill.index'), $html);
    }

    public function test_legacy_kasir_unit_sidebar_unchanged(): void
    {
        $html = $this->actingAs($this->createUser('kasir-unit'))->get(route('finance.transaction.history'))->getContent();

        $this->assertStringNotContainsString(route('finance.transaction.pending'), $html);
        $this->assertStringContainsString(route('finance.balance.topup'), $html);
    }

    public function test_general_module_sidebar_sections_follow_modules(): void
    {
        $academic = $this->pegawai([Modules::AdminAcademic]);
        $html = $this->actingAs($academic)->get(route('employee.tahfidz.index'))->getContent();
        $this->assertStringContainsString(route('academic.class.index'), $html);
        $this->assertStringNotContainsString('href="'.route('academic.student.index').'"', $html);
        $this->assertStringNotContainsString(route('hr.violation.index'), $html);
        $this->assertStringNotContainsString(route('hr.employee.index'), $html);
        $this->assertStringNotContainsString(route('hr.item.index'), $html);

        $hr = $this->pegawai([Modules::AdminHumanResource, Modules::AdminInventory]);
        $html = $this->actingAs($hr)->get(route('employee.tahfidz.index'))->getContent();
        $this->assertStringContainsString(route('hr.employee.index'), $html);
        $this->assertStringContainsString(route('hr.item.index'), $html);
        $this->assertStringNotContainsString(route('academic.class.index'), $html);

        $none = $this->pegawai();
        $html = $this->actingAs($none)->get(route('employee.tahfidz.index'))->getContent();
        $this->assertStringNotContainsString(route('academic.class.index'), $html);
        $this->assertStringNotContainsString(route('hr.employee.index'), $html);
    }

    public function test_general_module_dashboard_cards(): void
    {
        $user = $this->pegawai([Modules::AdminAcademic, Modules::AdminInventory]);
        $html = $this->actingAs($user)->get(route('dashboard.index'))->getContent();

        $this->assertStringContainsString(route('academic.class.index'), $html);
        $this->assertStringContainsString(route('hr.inventory-item.index'), $html);
        $this->assertStringNotContainsString(route('hr.employee.index'), $html);
    }

    public function test_admin_sidebar_keeps_general_menus(): void
    {
        $html = $this->actingAs($this->createUser('admin'))->get(route('hr.employee.index'))->getContent();

        foreach (['academic.class.index', 'academic.student.index', 'hr.violation.index', 'hr.employee.index', 'hr.item.index', 'hr.unit.index'] as $name) {
            $this->assertStringContainsString('href="'.route($name).'"', $html, $name);
        }
    }
}
