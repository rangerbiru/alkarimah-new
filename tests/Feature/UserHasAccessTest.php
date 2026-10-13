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
}
