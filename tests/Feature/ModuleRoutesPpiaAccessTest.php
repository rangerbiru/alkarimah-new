<?php

namespace Tests\Feature;

use App\Constants\Modules;
use App\Http\Middleware\Access;
use App\Http\Middleware\AccessRights;
use App\Http\Middleware\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Route akademik / kepegawaian / pengadaan barang kini boleh dibuka pegawai pemegang modulnya
 * (Kelola Akademik, Kelola Kepegawaian, Kelola Pengadaan Barang). Dibandingkan dengan snapshot
 * middleware sebelum porting (docs/route-middleware-pre-ppia.json), itu satu-satunya
 * penambahan akses yang diizinkan.
 */
class ModuleRoutesPpiaAccessTest extends ModuleAccessTestCase
{
    private const ROLES = [
        'admin', 'kasir', 'kasir-unit', 'bendahara', 'penanggung-jawab-tabungan',
        'wali-kelas', 'kepala-sekolah', 'pegawai', 'orang-tua',
    ];

    private const GRANTING = [Modules::AdminAcademic, Modules::AdminHumanResource, Modules::AdminInventory];

    /** @return array<string, User> persona => user */
    private function personas(): array
    {
        $personas = [];

        foreach (self::ROLES as $role) {
            $personas[$role] = $this->createUser($role);
        }

        foreach (array_merge(Modules::Groups['general'], Modules::Groups['tu']) as $module) {
            $personas['pegawai+modul-'.$module] = $this->createUser('pegawai', [$module]);
        }

        return $personas;
    }

    /** @param array<int, string> $middlewares */
    private function allows(User $user, array $middlewares): bool
    {
        $this->actingAs($user);
        $request = Request::create('/');

        foreach ($middlewares as $middleware) {
            [$name, $params] = explode(':', $middleware, 2) + [1 => ''];
            $handler = match ($name) {
                'role' => new Role,
                'access' => new Access,
                'accessRights' => new AccessRights,
                default => null,
            };

            if (! $handler) {
                continue;
            }

            $response = $handler->handle($request, fn () => response('ok'), ...explode(',', $params));

            if ($response->getContent() !== 'ok') {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, array<int, string>> */
    private function currentMiddleware(): array
    {
        $current = [];

        foreach (Route::getRoutes() as $route) {
            if ($route->getName()) {
                $current[$route->getName()] = array_values(array_filter(
                    $route->gatherMiddleware(),
                    fn ($m) => is_string($m) && preg_match('/^(role|access|accessRights):/', $m)
                ));
            }
        }

        return $current;
    }

    private function snapshot(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__, 2).'/docs/route-middleware-pre-ppia.json'), true);
    }

    /** Modul umum yang disyaratkan route (accessRights:N), atau null. */
    private function requiredModule(array $middlewares): ?int
    {
        foreach ($middlewares as $m) {
            if (str_starts_with($m, 'accessRights:') && in_array((int) substr($m, 13), self::GRANTING, true)) {
                return (int) substr($m, 13);
            }
        }

        return null;
    }

    public function test_only_change_is_pegawai_with_the_module(): void
    {
        $personas = $this->personas();
        $snapshot = $this->snapshot();
        $current = $this->currentMiddleware();
        $changes = [];
        $checks = 0;

        foreach ($snapshot as $name => $before) {
            $this->assertArrayHasKey($name, $current, "route {$name} hilang");
            $module = $this->requiredModule($current[$name]);

            foreach ($personas as $persona => $user) {
                $was = $this->allows($user, $before);
                $now = $this->allows($user, $current[$name]);
                $checks++;

                if ($was === $now) {
                    continue;
                }

                $grantedByModule = $module !== null && $persona === 'pegawai+modul-'.$module && ! $was && $now;
                $this->assertTrue($grantedByModule, sprintf(
                    '%s untuk %s berubah dari %s menjadi %s di luar yang diizinkan',
                    $name, $persona, $was ? 'izin' : 'tolak', $now ? 'izin' : 'tolak'
                ));
                $changes[$name] = $persona;
            }
        }

        $this->assertNotEmpty($changes);
        fwrite(STDERR, sprintf("\n[PpiaAccess] %d route x %d persona = %d pengecekan; %d route berubah:\n", count($snapshot), count($personas), $checks, count($changes)));
        foreach ($changes as $name => $persona) {
            fwrite(STDERR, "  {$name} + {$persona}\n");
        }
    }

    public function test_module_routes_are_gated_by_the_right_module_only(): void
    {
        $personas = $this->personas();
        $current = $this->currentMiddleware();
        $gated = 0;

        foreach ($current as $name => $middlewares) {
            $module = $this->requiredModule($middlewares);

            if (! $module) {
                continue;
            }

            $gated++;

            foreach ($personas as $persona => $user) {
                $expected = $persona === 'admin' || $persona === 'pegawai+modul-'.$module;

                $this->assertSame($expected, $this->allows($user, $middlewares), "{$name} (modul {$module}) untuk {$persona}");
            }
        }

        $this->assertGreaterThan(0, $gated);
    }

    public function test_pegawai_without_module_is_denied_and_academic_module_cannot_open_hr(): void
    {
        $pegawai = $this->createUser('pegawai');
        $academic = $this->createUser('pegawai', [Modules::AdminAcademic]);
        $current = $this->currentMiddleware();

        foreach (['academic.class.index', 'academic.parent.index', 'hr.employee.index', 'hr.item.index'] as $name) {
            $this->assertFalse($this->allows($pegawai, $current[$name]), $name);
        }

        $this->assertTrue($this->allows($academic, $current['academic.class.index']));
        $this->assertFalse($this->allows($academic, $current['hr.employee.index']));
        $this->assertFalse($this->allows($academic, $current['hr.item.index']));
    }

    public function test_employee_rights_page_stays_admin_only(): void
    {
        $current = $this->currentMiddleware();
        $hr = $this->createUser('pegawai', [Modules::AdminHumanResource]);
        $admin = $this->createUser('admin');

        foreach (['hr.employee.rights', 'hr.employee.store.rights'] as $name) {
            $this->assertFalse($this->allows($hr, $current[$name]), $name);
            $this->assertTrue($this->allows($admin, $current[$name]), $name);
        }
    }

    public function test_admin_bypass_does_not_open_absence_create(): void
    {
        $admin = $this->createUser('admin');
        $withModule = $this->createUser('pegawai', [Modules::CreateAbsence]);
        $without = $this->createUser('pegawai');
        $current = $this->currentMiddleware();

        foreach (['academic.absence.create', 'academic.absence.store'] as $name) {
            $this->assertContains('accessRights:'.Modules::CreateAbsence, $current[$name]);
            $this->assertFalse($this->allows($admin, $current[$name]), $name);
            $this->assertTrue($this->allows($withModule, $current[$name]), $name);
            $this->assertFalse($this->allows($without, $current[$name]), $name);
        }
    }
}
