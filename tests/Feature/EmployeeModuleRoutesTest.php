<?php

namespace Tests\Feature;

use App\Constants\Modules;
use App\Models\User;

/**
 * Route TU (middleware access:) untuk akun role lama dan pegawai pemegang modul.
 */
class EmployeeModuleRoutesTest extends ModuleAccessTestCase
{
    private function assertDenied(User $user, string $method, string $route): void
    {
        $response = $this->actingAs($user)->{$method}(route($route));

        $response->assertRedirect(route('base'));
    }

    private function assertAllowed(User $user, string $method, string $route): void
    {
        $response = $this->actingAs($user)->{$method}(route($route));

        $this->assertNotSame(route('base'), $response->headers->get('Location'), "{$user->role->value} ditolak di {$route}");
    }

    public function test_pegawai_without_module_is_denied_on_tu_routes(): void
    {
        $pegawai = $this->createUser('pegawai');

        foreach (['finance.transaction.history', 'finance.balance.topup', 'finance.transaction.pending', 'finance.bill.index', 'finance.transaction.bill.index'] as $route) {
            $this->assertDenied($pegawai, 'get', $route);
        }

        $this->assertDenied($pegawai, 'post', 'finance.transaction.datatable.pending');
    }

    public function test_pegawai_with_kasir_unit_module_reaches_kasir_unit_routes_only(): void
    {
        $pegawai = $this->createUser('pegawai', [Modules::KasirUnit]);

        $this->assertAllowed($pegawai, 'get', 'finance.transaction.history');
        $this->assertAllowed($pegawai, 'get', 'finance.balance.topup');

        // Pending dan setup tagihan bukan untuk Kasir Unit
        $this->assertDenied($pegawai, 'get', 'finance.transaction.pending');
        $this->assertDenied($pegawai, 'post', 'finance.transaction.datatable.pending');
        $this->assertDenied($pegawai, 'get', 'finance.bill.index');
    }

    public function test_pegawai_with_kasir_module_reaches_pending(): void
    {
        $pegawai = $this->createUser('pegawai', [Modules::Kasir]);

        $this->assertAllowed($pegawai, 'get', 'finance.transaction.pending');
        $this->assertAllowed($pegawai, 'get', 'finance.transaction.history');
    }

    public function test_legacy_roles_keep_their_access(): void
    {
        $this->assertAllowed($this->createUser('kasir'), 'get', 'finance.transaction.pending');
        $this->assertAllowed($this->createUser('kasir'), 'get', 'finance.bill.index');
        $this->assertAllowed($this->createUser('kasir-unit'), 'get', 'finance.transaction.history');
        $this->assertDenied($this->createUser('kasir-unit'), 'get', 'finance.transaction.pending');
        $this->assertAllowed($this->createUser('bendahara'), 'get', 'finance.payroll.setup');
    }

    public function test_admin_only_passes_where_admin_is_listed(): void
    {
        $admin = $this->createUser('admin');

        $this->assertDenied($admin, 'get', 'finance.transaction.history');
        $this->assertDenied($admin, 'get', 'finance.transaction.pending');
        $this->assertDenied($admin, 'get', 'finance.balance.topup');
    }

    public function test_module_of_one_role_does_not_open_another_roles_routes(): void
    {
        $pegawai = $this->createUser('pegawai', [Modules::PenanggungJawabTabungan]);

        $this->assertDenied($pegawai, 'get', 'finance.transaction.history');
        $this->assertDenied($pegawai, 'get', 'finance.payroll.setup');
    }

    public function test_cashier_accessors_follow_modules(): void
    {
        $unit = $this->createUser('pegawai', [Modules::KasirUnit]);

        $this->assertTrue($unit->is_kasir_unit);
        $this->assertFalse($unit->is_kasir);
        $this->assertFalse($unit->is_bendahara);
    }

    public function test_wali_kelas_candidates_include_pegawai_with_module(): void
    {
        $legacy = $this->createUser('wali-kelas');
        $withModule = $this->createUser('pegawai', [Modules::WaliKelas]);
        $without = $this->createUser('pegawai');

        $ids = User::withoutGlobalScopes()->waliKelasCandidates()->pluck('id')->all();

        $this->assertContains($legacy->id, $ids);
        $this->assertContains($withModule->id, $ids);
        $this->assertNotContains($without->id, $ids);
    }
}
