<?php

namespace Tests\Feature;

use App\Constants\Modules;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Menu modul Hak Akses untuk pegawai di kedua layout: backend (halaman TU) dan
 * mobile (dashboard pegawai, lewat menu hamburger).
 */
class PegawaiModuleSidebarTest extends ModuleAccessTestCase
{
    private function pegawai(array $modules, array $levels = []): User
    {
        $user = $this->createUser('pegawai', $modules);
        $this->createEmployee($user);

        foreach ($levels as $module => $list) {
            foreach ($list as $level) {
                DB::table('user_education_levels')->insert(['user_id' => $user->id, 'module_id' => $module, 'level_education' => $level]);
            }
        }

        return User::withoutGlobalScopes()->find($user->id);
    }

    /** Link ada sebagai item sidebar (bukan sekadar kartu dashboard). */
    private function hasMenu(string $html, string $url): bool
    {
        return (bool) preg_match('/<a href="'.preg_quote($url, '/').'"[^>]*side-menu__item/', $html);
    }

    /** @return array<string, array{0: string}> halaman backend dan halaman mobile */
    public static function pages(): array
    {
        return ['layout backend' => ['employee.tahfidz.index'], 'layout mobile' => ['dashboard.index']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_kasir_unit_smp_menu_without_pending(string $page): void
    {
        $user = $this->pegawai([Modules::KasirUnit], [Modules::KasirUnit => ['smp']]);

        $html = $this->actingAs($user)->get(route($page))->assertOk()->getContent();

        $this->assertTrue($this->hasMenu($html, route('finance.transaction.bill.index')), 'Transaksi');
        $this->assertTrue($this->hasMenu($html, route('finance.savings.deposit')), 'Tabungan');
        $this->assertTrue($this->hasMenu($html, route('finance.balance.topup')), 'Saldo/Uang saku');
        $this->assertFalse($this->hasMenu($html, route('finance.transaction.pending')), 'Pending');
    }

    public function test_kasir_unit_levels_are_read_from_module_and_limited_to_smp(): void
    {
        $wali = $this->createUser('wali-kelas');
        $ids = [];
        foreach (['sd', 'smp'] as $level) {
            $ids[$level] = DB::table('class')->insertGetId([
                'id_wali_kelas' => $wali->id, 'name' => 'Kelas '.$level, 'level_education' => $level,
                'level_class' => 1, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
            ]);
        }

        $user = $this->pegawai([Modules::KasirUnit], [Modules::KasirUnit => ['smp']]);

        $this->assertSame(['smp'], $user->educationLevelValues(Modules::KasirUnit));
        $classIds = $user->kasirUnitClassIds();
        $this->assertContains($ids['smp'], $classIds);
        $this->assertNotContains($ids['sd'], $classIds);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_bendahara_menu(string $page): void
    {
        $html = $this->actingAs($this->pegawai([Modules::Bendahara]))->get(route($page))->assertOk()->getContent();

        $this->assertTrue($this->hasMenu($html, route('finance.payroll.setup')));
        $this->assertTrue($this->hasMenu($html, route('hr.allowance.index')));
        $this->assertFalse($this->hasMenu($html, route('finance.transaction.bill.index')));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_kelola_kepegawaian_shows_hr_menu_not_academic(string $page): void
    {
        $html = $this->actingAs($this->pegawai([Modules::AdminHumanResource]))->get(route($page))->assertOk()->getContent();

        $this->assertTrue($this->hasMenu($html, route('hr.employee.index')));
        $this->assertTrue($this->hasMenu($html, route('hr.position.index')));
        $this->assertFalse($this->hasMenu($html, route('academic.class.index')));
        $this->assertFalse($this->hasMenu($html, route('hr.item.index')));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_pegawai_without_module_has_no_module_menu(string $page): void
    {
        $html = $this->actingAs($this->pegawai([]))->get(route($page))->assertOk()->getContent();

        foreach (['hr.employee.index', 'academic.class.index', 'finance.transaction.bill.index', 'finance.payroll.setup'] as $name) {
            $this->assertFalse($this->hasMenu($html, route($name)), $name);
        }
    }

    public function test_legacy_role_with_module_rights_gets_no_module_menu(): void
    {
        $user = $this->createUser('kepala-sekolah', [Modules::Kasir, Modules::AdminHumanResource]);
        $this->createEmployee($user);

        $html = $this->actingAs($user)->get(route('employee.tahfidz.index'))->getContent();

        $this->assertFalse($this->hasMenu($html, route('finance.transaction.bill.index')));
        $this->assertFalse($this->hasMenu($html, route('hr.employee.index')));
    }
}
