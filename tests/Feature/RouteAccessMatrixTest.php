<?php

namespace Tests\Feature;

use App\Http\Middleware\Access;
use App\Http\Middleware\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Matriks route x role: hasil izinkan/tolak middleware `role:` / `access:` pada setiap route
 * di docs/route-role-inventory.txt (snapshot sebelum migrasi ke `access:`) harus sama dengan
 * perilaku sebelumnya untuk setiap role lama. Pegawai diuji TANPA modul.
 *
 * Satu-satunya perbedaan yang diizinkan: typo `penanggungJawabTabungan` di
 * dashboard.employee.attendanceIn/Out (dulu tidak cocok dengan role mana pun), kini
 * penanggung-jawab-tabungan boleh.
 */
class RouteAccessMatrixTest extends ModuleAccessTestCase
{
    private const ROLES = [
        'admin', 'kasir', 'kasir-unit', 'bendahara', 'penanggung-jawab-tabungan',
        'wali-kelas', 'kepala-sekolah', 'pegawai', 'orang-tua',
    ];

    /** Route yang typo role-nya diperbaiki: role -> boleh sekarang, dulu tidak. */
    private const FIXED_TYPO = [
        'dashboard.employee.attendanceIn' => ['penanggung-jawab-tabungan'],
        'dashboard.employee.attendanceOut' => ['penanggung-jawab-tabungan'],
    ];

    /** Route::resource tanpa nama di snapshot ('?') -> nama route sebenarnya. */
    private const RESOURCES = [
        'routes/finance.php:78' => 'finance.donation.%s',
        'routes/hr.php:23' => 'hr.allowance.%s',
    ];

    private const RESOURCE_ACTIONS = ['index', 'create', 'store', 'edit', 'update', 'destroy'];

    private static int $routes = 0;

    private static int $checks = 0;

    public static function inventory(): array
    {
        $lines = file(dirname(__DIR__, 2).'/docs/route-role-inventory.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $cases = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, '#')) {
                continue;
            }

            [$where, $roles, $name] = array_map('trim', explode('|', $line));

            if ($name === '?') {
                foreach (self::RESOURCE_ACTIONS as $action) {
                    $resolved = sprintf(self::RESOURCES[$where], $action);
                    $cases[$resolved] = [$resolved, explode(',', $roles)];
                }

                continue;
            }

            $cases[$name] = [$name, explode(',', $roles)];
        }

        return $cases;
    }

    private function allowedBy(User $user, string $routeName): bool
    {
        $route = Route::getRoutes()->getByName($routeName);
        $this->assertNotNull($route, "route {$routeName} tidak ditemukan");

        $this->actingAs($user);
        $request = Request::create('/');

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            if (str_starts_with($middleware, 'role:')) {
                $handler = new Role;
            } elseif (str_starts_with($middleware, 'access:')) {
                $handler = new Access;
            } else {
                continue;
            }

            $params = explode(',', substr($middleware, strpos($middleware, ':') + 1));
            $response = $handler->handle($request, fn () => response('ok'), ...$params);

            if ($response->getContent() !== 'ok') {
                return false;
            }
        }

        return true;
    }

    #[DataProvider('inventory')]
    public function test_route_allows_the_same_roles_as_before(string $routeName, array $listedRoles): void
    {
        self::$routes++;

        foreach (self::ROLES as $role) {
            $expected = in_array($role, $listedRoles, true)
                || in_array($role, self::FIXED_TYPO[$routeName] ?? [], true);

            $this->assertSame(
                $expected,
                $this->allowedBy($this->createUser($role), $routeName),
                sprintf('%s untuk role %s: seharusnya %s', $routeName, $role, $expected ? 'diizinkan' : 'ditolak')
            );
            self::$checks++;
        }
    }

    public function test_typo_fix_is_the_only_difference(): void
    {
        // Selain dua route typo, daftar role di snapshot tidak memuat token yang salah eja.
        $this->assertSame(
            ['dashboard.employee.attendanceIn', 'dashboard.employee.attendanceOut'],
            array_keys(array_filter(
                self::inventory(),
                fn ($case) => in_array('penanggungJawabTabungan', $case[1], true)
            ))
        );
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$routes > 0) {
            fwrite(STDERR, sprintf(
                "\n[RouteAccessMatrix] %d route x %d role = %d pengecekan izinkan/tolak\n",
                self::$routes,
                count(self::ROLES),
                self::$checks
            ));
        }

        parent::tearDownAfterClass();
    }
}
