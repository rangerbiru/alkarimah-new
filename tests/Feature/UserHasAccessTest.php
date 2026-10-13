<?php

namespace Tests\Feature;

use App\Constants\Modules;

class UserHasAccessTest extends ModuleAccessTestCase
{
    public function test_role_matches_itself_only(): void
    {
        $kasir = $this->createUser('kasir');

        $this->assertTrue($kasir->hasAccess('kasir'));
        $this->assertFalse($kasir->hasAccess('kasir-unit'));
        $this->assertFalse($kasir->hasAccess('admin'));
    }

    public function test_pegawai_without_module_has_no_tu_access(): void
    {
        $pegawai = $this->createUser('pegawai');

        foreach (array_keys(Modules::ByAccess) as $access) {
            $this->assertFalse($pegawai->hasAccess($access), $access);
        }

        $this->assertTrue($pegawai->hasAccess('pegawai'));
    }

    public function test_pegawai_with_module_gets_only_that_access(): void
    {
        $pegawai = $this->createUser('pegawai', [Modules::KasirUnit]);

        $this->assertTrue($pegawai->hasAccess('kasir-unit'));
        $this->assertFalse($pegawai->hasAccess('kasir'));
        $this->assertFalse($pegawai->hasAccess('bendahara'));
        $this->assertFalse($pegawai->hasAccess('admin'));
    }

    public function test_module_does_not_grant_access_to_non_pegawai_roles(): void
    {
        $orangTua = $this->createUser('orang-tua', [Modules::Kasir]);

        $this->assertFalse($orangTua->hasAccess('kasir'));
    }

    public function test_admin_only_passes_for_admin(): void
    {
        $admin = $this->createUser('admin');

        $this->assertTrue($admin->hasAccess('admin'));
        $this->assertFalse($admin->hasAccess('kasir'));
        $this->assertFalse($admin->hasAccess('pegawai'));
    }

    public function test_education_levels_are_kept_per_module(): void
    {
        $pegawai = $this->createUser('pegawai', [Modules::KepalaSekolah, Modules::KasirUnit]);
        $pegawai->syncEducationLevels(['sd'], Modules::KepalaSekolah);
        $pegawai->syncEducationLevels(['smp', 'sma'], Modules::KasirUnit);

        $pegawai = $pegawai->fresh();

        $this->assertSame(['sd'], $pegawai->educationLevelValues(Modules::KepalaSekolah));
        $this->assertEqualsCanonicalizing(['smp', 'sma'], $pegawai->educationLevelValues(Modules::KasirUnit));
        $this->assertSame([], $pegawai->educationLevelValues());
    }

    public function test_syncing_one_module_does_not_touch_other_levels(): void
    {
        $pegawai = $this->createUser('pegawai', [Modules::KepalaSekolah, Modules::KasirUnit]);
        $pegawai->syncEducationLevels(['sd'], Modules::KepalaSekolah);
        $pegawai->syncEducationLevels(['sma'], Modules::KasirUnit);

        $pegawai->syncEducationLevels(['smp'], Modules::KasirUnit);
        $pegawai = $pegawai->fresh();

        $this->assertSame(['sd'], $pegawai->educationLevelValues(Modules::KepalaSekolah));
        $this->assertSame(['smp'], $pegawai->educationLevelValues(Modules::KasirUnit));
    }

    public function test_legacy_kepala_sekolah_keeps_account_level_scope(): void
    {
        $ks = $this->createUser('kepala-sekolah', [], ['sd']);

        $this->assertSame(['sd'], $ks->educationLevelValues());
        $this->assertNotNull($ks->educationLevelClassIds());
    }

    public function test_pegawai_without_kepala_sekolah_module_is_not_scoped(): void
    {
        $pegawai = $this->createUser('pegawai', [Modules::Kasir]);

        $this->assertNull($pegawai->educationLevelClassIds());
        $this->assertNull($pegawai->allowedClassIds());
    }
}
