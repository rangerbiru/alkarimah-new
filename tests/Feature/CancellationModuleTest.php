<?php

namespace Tests\Feature;

use App\Constants\Modules;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionMethod;
use RuntimeException;

/**
 * Bagian A fitur Pembatalan Transaksi: migration modul dan halaman Hak Akses pegawai.
 */
class CancellationModuleTest extends ModuleAccessTestCase
{
    private const NAME = 'Pembatalan Transaksi';

    /** Instance migration anonim, diambil lewat resolvePath supaya file tidak di-require dua kali. */
    private function migration(string $file): object
    {
        $migrator = app('migrator');
        $path = database_path('migrations/'.$file.'.php');

        return (new ReflectionMethod($migrator, 'resolvePath'))->invoke($migrator, $path);
    }

    private function moduleMigration(): object
    {
        return $this->migration('2026_10_22_000003_add_cancel_transaction_module');
    }

    private function module(): ?object
    {
        return DB::table('module')->where('id', Modules::CancelTransaction)->first();
    }

    private function snapshotOtherModules(): array
    {
        return DB::table('module')->where('id', '<>', Modules::CancelTransaction)->orderBy('id')->get()->all();
    }

    public function test_up_inserts_the_module_when_id_is_free(): void
    {
        DB::table('module')->where('id', Modules::CancelTransaction)->delete();
        $others = $this->snapshotOtherModules();

        $this->moduleMigration()->up();

        $module = $this->module();
        $this->assertSame(self::NAME, $module->name);
        $this->assertSame('Membatalkan transaksi pembayaran masuk yang dibuat orang tua', $module->description);
        $this->assertSame(0, DB::table('module_rights')->where('id_module', Modules::CancelTransaction)->count());
        $this->assertEquals($others, $this->snapshotOtherModules());
    }

    public function test_up_skips_when_the_module_is_already_there_under_the_same_name(): void
    {
        DB::table('module')->where('id', Modules::CancelTransaction)->update(['name' => '  pembatalan TRANSAKSI ', 'description' => 'Diubah manual']);

        $this->moduleMigration()->up();

        $module = $this->module();
        $this->assertSame('  pembatalan TRANSAKSI ', $module->name);
        $this->assertSame('Diubah manual', $module->description);
    }

    public function test_up_throws_when_the_id_belongs_to_another_module_and_changes_nothing(): void
    {
        DB::table('module')->where('id', Modules::CancelTransaction)->update(['name' => 'Modul Lain', 'description' => 'Bukan milik fitur ini']);
        DB::table('module_rights')->insert(['id_module' => Modules::CancelTransaction, 'id_user' => $this->createUser('pegawai')->id]);
        $before = $this->module();
        $rights = DB::table('module_rights')->where('id_module', Modules::CancelTransaction)->count();

        try {
            $this->moduleMigration()->up();
            $this->fail('RuntimeException diharapkan');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Modul Lain', $e->getMessage());
            $this->assertStringContainsString('Modules::CancelTransaction', $e->getMessage());
        }

        $this->assertEquals($before, $this->module());
        $this->assertSame($rights, DB::table('module_rights')->where('id_module', Modules::CancelTransaction)->count());
    }

    public function test_down_removes_its_own_module_together_with_its_rights(): void
    {
        $user = $this->createUser('pegawai', [Modules::CancelTransaction]);
        $other = $this->createUser('pegawai', [Modules::Kasir]);
        $others = $this->snapshotOtherModules();

        $this->moduleMigration()->down();

        $this->assertNull($this->module());
        $this->assertDatabaseMissing('module_rights', ['id_user' => $user->id, 'id_module' => Modules::CancelTransaction]);
        $this->assertDatabaseHas('module_rights', ['id_user' => $other->id, 'id_module' => Modules::Kasir]);
        $this->assertEquals($others, $this->snapshotOtherModules());
    }

    public function test_down_leaves_a_module_with_another_name_alone(): void
    {
        DB::table('module')->where('id', Modules::CancelTransaction)->update(['name' => 'Modul Lain']);
        $user = $this->createUser('pegawai', [Modules::CancelTransaction]);

        $this->moduleMigration()->down();

        $this->assertSame('Modul Lain', $this->module()->name);
        $this->assertDatabaseHas('module_rights', ['id_user' => $user->id, 'id_module' => Modules::CancelTransaction]);
    }

    #[RunInSeparateProcess]
    public function test_rights_page_shows_the_module_unchecked_then_checked_after_it_is_granted(): void
    {
        $admin = $this->createUser('admin');
        $employee = $this->createEmployee($this->createUser('pegawai'));
        $page = route('hr.employee.rights', $employee->encrypted_id);
        $checkbox = '/<td>'.self::NAME.'<\/td>/';
        $input = 'name="module[]" value="'.Modules::CancelTransaction.'"';

        $html = $this->actingAs($admin)->get($page)->assertOk()->getContent();
        $this->assertMatchesRegularExpression($checkbox, $html);
        $this->assertStringContainsString($input.' />', $html);
        $this->assertStringNotContainsString($input.' checked', $html);

        $this->actingAs($admin)->post(route('hr.employee.store.rights', $employee->encrypted_id), ['module' => [Modules::CancelTransaction]])
            ->assertSessionHasNoErrors();

        $html = $this->actingAs($admin)->get($page)->assertOk()->getContent();
        $this->assertStringContainsString($input.' checked', $html);
    }

    #[RunInSeparateProcess]
    public function test_rights_page_lists_the_module_in_the_administration_group(): void
    {
        $employee = $this->createEmployee($this->createUser('pegawai'));

        $html = $this->actingAs($this->createUser('admin'))->get(route('hr.employee.rights', $employee->encrypted_id))->assertOk()->getContent();

        $this->assertGreaterThan(strpos($html, '>Tata Usaha</th>'), strpos($html, '<td>'.self::NAME.'</td>'));
    }
}
