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
}
