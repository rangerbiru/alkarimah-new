<?php

namespace Tests\Feature;

use App\Constants\Modules;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Command access:restore-pegawai-from-kepala-sekolah. Berjalan di app_alkarimah_testing
 * (DatabaseTransactions), tidak menyentuh data dev/produksi.
 */
class RestorePegawaiFromKepalaSekolahCommandTest extends ModuleAccessTestCase
{
    private function ksWithEmployee(array $levels, ?string $name = null): User
    {
        $user = $this->createUser('kepala-sekolah', [], $levels);

        if ($name !== null) {
            DB::table('users')->where('id', $user->id)->update(['name' => $name]);
        }

        $this->createEmployee($user);

        return User::withoutGlobalScopes()->find($user->id);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // fixture: modul KS tersedia (seed migration sudah dijalankan di DB testing)
        $this->assertTrue(DB::table('module')->where('id', Modules::KepalaSekolah)->exists());
    }

    public function test_dry_run_changes_nothing_and_lists_users(): void
    {
        $user = $this->ksWithEmployee(['sd', 'smp']);
        $before = DB::table('users')->where('id', $user->id)->first();

        $this->artisan('access:restore-pegawai-from-kepala-sekolah', ['--dry-run' => true])
            ->expectsOutputToContain('DRY-RUN')
            ->expectsOutputToContain((string) $user->id)
            ->assertSuccessful();

        $this->assertEquals($before, DB::table('users')->where('id', $user->id)->first());
        $this->assertSame(0, DB::table('module_rights')->where('id_user', $user->id)->count());
        $this->assertSame(2, DB::table('user_education_levels')->where('user_id', $user->id)->whereNull('module_id')->count());
    }

    public function test_without_flags_behaves_as_dry_run(): void
    {
        $user = $this->ksWithEmployee(['sd']);

        $this->artisan('access:restore-pegawai-from-kepala-sekolah')->assertSuccessful();

        $this->assertSame('kepala-sekolah', DB::table('users')->where('id', $user->id)->value('role'));
    }

    public function test_dry_run_wins_over_force(): void
    {
        $user = $this->ksWithEmployee(['sd']);

        $this->artisan('access:restore-pegawai-from-kepala-sekolah', ['--dry-run' => true, '--force' => true])->assertSuccessful();

        $this->assertSame('kepala-sekolah', DB::table('users')->where('id', $user->id)->value('role'));
    }

    public function test_phone_like_name_is_rejected_and_reported_never_fixed(): void
    {
        $bad = $this->ksWithEmployee(['sd'], '081317435630');
        $badPlus = $this->ksWithEmployee(['sd'], '+62 813-1743-5630');
        $good = $this->ksWithEmployee(['smp'], 'Ustadz Ahmad 2');

        foreach ([['--dry-run' => true], ['--force' => true]] as $options) {
            $this->artisan('access:restore-pegawai-from-kepala-sekolah', $options)
                ->expectsOutputToContain('DITOLAK')
                ->expectsOutputToContain('081317435630')
                ->assertSuccessful();

            foreach ([$bad, $badPlus] as $user) {
                $this->assertSame('kepala-sekolah', DB::table('users')->where('id', $user->id)->value('role'));
                $this->assertSame(0, DB::table('module_rights')->where('id_user', $user->id)->count());
                $this->assertSame('081317435630' === $user->name ? '081317435630' : $user->name, DB::table('users')->where('id', $user->id)->value('name'), 'nama tidak diperbaiki otomatis');
                $this->assertSame(1, DB::table('user_education_levels')->where('user_id', $user->id)->whereNull('module_id')->count());
            }
        }

        // nama dengan angka tapi bukan nomor HP tetap diproses
        $this->assertSame('pegawai', DB::table('users')->where('id', $good->id)->value('role'));
    }

    public function test_force_restores_pegawai_with_same_effective_access(): void
    {
        $user = $this->ksWithEmployee(['sd', 'smp']);
        $classSd = DB::table('class')->insertGetId(['id_wali_kelas' => 0, 'name' => 'KS SD', 'level_education' => 'sd', 'level_class' => 1, 'branch_id' => self::BRANCH_ID, 'created_by' => 0]);
        $classSma = DB::table('class')->insertGetId(['id_wali_kelas' => 0, 'name' => 'KS SMA', 'level_education' => 'sma', 'level_class' => 1, 'branch_id' => self::BRANCH_ID, 'created_by' => 0]);

        $before = $user->educationLevelClassIds();
        $this->assertContains($classSd, $before);
        $this->assertNotContains($classSma, $before);

        $this->artisan('access:restore-pegawai-from-kepala-sekolah', ['--force' => true])->assertSuccessful();

        $after = User::withoutGlobalScopes()->find($user->id);

        $this->assertSame('pegawai', $after->role->value);
        $this->assertTrue($after->hasAccess('kepala-sekolah'));
        $this->assertSame([Modules::KepalaSekolah], $after->moduleIds());
        $this->assertEqualsCanonicalizing(['sd', 'smp'], $after->educationLevelValues(Modules::KepalaSekolah));
        $this->assertSame([], $after->educationLevelValues(), 'tidak ada jenjang akun yang tersisa');
        $this->assertEqualsCanonicalizing($before, $after->educationLevelClassIds(), 'jenjang efektif sama');
    }

    public function test_force_is_idempotent_for_existing_module_right(): void
    {
        $user = $this->ksWithEmployee(['sd']);
        DB::table('module_rights')->insert(['id_module' => Modules::KepalaSekolah, 'id_user' => $user->id]);
        DB::table('user_education_levels')->insert(['user_id' => $user->id, 'module_id' => Modules::KepalaSekolah, 'level_education' => 'sd']);

        $this->artisan('access:restore-pegawai-from-kepala-sekolah', ['--force' => true])->assertSuccessful();

        $this->assertSame(1, DB::table('module_rights')->where('id_user', $user->id)->where('id_module', Modules::KepalaSekolah)->count());
        $this->assertSame(1, DB::table('user_education_levels')->where('user_id', $user->id)->count());
        $this->assertSame('pegawai', DB::table('users')->where('id', $user->id)->value('role'));
    }

    public function test_user_without_employee_is_skipped(): void
    {
        $user = $this->createUser('kepala-sekolah', [], ['sd']);

        $this->artisan('access:restore-pegawai-from-kepala-sekolah', ['--force' => true])
            ->expectsOutputToContain('tidak terhubung ke pegawai')
            ->assertSuccessful();

        $this->assertSame('kepala-sekolah', DB::table('users')->where('id', $user->id)->value('role'));
    }

    public function test_aborts_when_module_is_missing(): void
    {
        DB::table('module')->where('id', Modules::KepalaSekolah)->update(['name' => 'Lain']);

        $user = $this->ksWithEmployee(['sd']);

        $this->artisan('access:restore-pegawai-from-kepala-sekolah', ['--force' => true])->assertFailed();
        $this->assertSame('kepala-sekolah', DB::table('users')->where('id', $user->id)->value('role'));

        // dry-run tetap menampilkan laporan, hanya memberi peringatan
        $this->artisan('access:restore-pegawai-from-kepala-sekolah', ['--dry-run' => true])
            ->expectsOutputToContain('belum ada di tabel module')
            ->assertSuccessful();
    }
}
