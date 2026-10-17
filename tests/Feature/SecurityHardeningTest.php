<?php

namespace Tests\Feature;

use App\Helpers\Whatsapp;
use App\Http\Middleware\Access;
use App\Http\Middleware\AccessRights;
use App\Http\Middleware\Role;
use App\Models\Parents;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Perbaikan darurat: mass assignment, route sensitif, OTP registrasi dan reset password,
 * guard WhatsApp. Memakai DB app_alkarimah_testing dengan DatabaseTransactions.
 */
class SecurityHardeningTest extends ModuleAccessTestCase
{
    private function createParent(string $phone, ?int $userId = null): Parents
    {
        $id = DB::table('parent')->insertGetId([
            'id_user' => $userId,
            'name' => 'Ortu Test',
            'phone' => $phone,
            'gender' => 'male',
            'branch_id' => self::BRANCH_ID,
            'created_by' => 0,
        ]);

        return Parents::withoutGlobalScopes()->findOrFail($id);
    }

    private function uniquePhone(): string
    {
        return '08'.random_int(100000000, 999999999);
    }

    // ---------- A. mass assignment profil ----------

    public function test_profile_update_cannot_change_own_role(): void
    {
        $user = $this->createUser('orang-tua');

        $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => 'Nama Baru',
            'email' => 'baru'.$user->id.'@example.test',
            'phone' => $this->uniquePhone(),
            'role' => 'admin',
            'branch_id' => 1,
        ]);

        $fresh = User::withoutGlobalScopes()->find($user->id);
        $this->assertSame('Nama Baru', $fresh->name);
        $this->assertSame('orang-tua', $fresh->role->value);
        $this->assertSame(self::BRANCH_ID, (int) $fresh->branch_id);
    }

    // ---------- B/C. UserController ----------

    public function test_orang_tua_cannot_create_users(): void
    {
        $actor = $this->createUser('orang-tua');
        $before = User::withoutGlobalScopes()->count();

        $response = $this->actingAs($actor)->post(route('user.store'), [
            'name' => 'Penyusup',
            'phone' => $this->uniquePhone(),
            'email' => 'penyusup@example.test',
            'gender' => 'male',
            'role' => 'admin',
            'password' => 'password123',
            'password_confirm' => 'password123',
        ]);

        $response->assertRedirect(route('base'));
        $this->assertSame($before, User::withoutGlobalScopes()->count());
    }

    public function test_admin_can_create_managed_role_but_not_admin_role(): void
    {
        $admin = $this->createUser('admin');
        $payload = fn (string $role) => [
            'name' => 'Kasir Baru',
            'phone' => $this->uniquePhone(),
            'email' => 'kasir'.random_int(1, 999999).'@example.test',
            'gender' => 'male',
            'role' => $role,
            'password' => 'password123',
            'password_confirm' => 'password123',
        ];

        $this->actingAs($admin)->post(route('user.store'), $payload('admin'))->assertSessionHasErrors('role');
        $this->assertSame(0, User::withoutGlobalScopes()->where('name', 'Kasir Baru')->count());

        $this->actingAs($admin)->post(route('user.store'), $payload('kasir'));
        $created = User::withoutGlobalScopes()->where('name', 'Kasir Baru')->first();
        $this->assertNotNull($created);
        $this->assertSame('kasir', $created->role->value);
    }

    public function test_user_request_validates_uniqueness_against_users_table(): void
    {
        $admin = $this->createUser('admin');
        $existing = $this->createUser('kasir');

        $this->actingAs($admin)->post(route('user.store'), [
            'name' => 'Duplikat',
            'phone' => $existing->phone,
            'email' => 'dup'.random_int(1, 999999).'@example.test',
            'gender' => 'male',
            'role' => 'kasir',
            'password' => 'password123',
            'password_confirm' => 'password123',
        ])->assertSessionHasErrors('phone');
    }

    public function test_admin_cannot_edit_update_or_delete_non_managed_accounts(): void
    {
        $admin = $this->createUser('admin');
        $pegawai = $this->createUser('pegawai');
        $otherAdmin = $this->createUser('admin');

        foreach ([$pegawai, $otherAdmin] as $target) {
            $this->actingAs($admin)->get(route('user.edit', $target->encrypted_id))->assertNotFound();
            $this->actingAs($admin)->put(route('user.update', $target->encrypted_id), [
                'name' => 'X', 'phone' => $this->uniquePhone(), 'email' => 'x@example.test', 'gender' => 'male',
            ])->assertNotFound();
            $this->actingAs($admin)->delete(route('user.destroy', $target->encrypted_id))->assertNotFound();
        }
    }

    public function test_admin_update_never_changes_role_or_branch(): void
    {
        $admin = $this->createUser('admin');
        $kasir = $this->createUser('kasir');

        $response = $this->actingAs($admin)->put(route('user.update', $kasir->encrypted_id), [
            'name' => 'Kasir Diubah',
            'phone' => $kasir->phone,
            'email' => 'kasir'.$kasir->id.'@example.test',
            'gender' => 'female',
            'role' => 'admin',
            'branch_id' => 1,
        ]);
        $response->assertSessionHasNoErrors();

        $fresh = User::withoutGlobalScopes()->find($kasir->id);
        $this->assertSame('Kasir Diubah', $fresh->name);
        $this->assertSame('kasir', $fresh->role->value);
        $this->assertSame(self::BRANCH_ID, (int) $fresh->branch_id);
    }

    // ---------- F. route sensitif ----------

    public static function lockedRoutes(): array
    {
        $adminSuper = ['admin', 'super-admin'];

        return [
            'branch.index' => ['branch.index', $adminSuper],
            'branch.store' => ['branch.store', $adminSuper],
            'branch.datatable' => ['branch.datatable', $adminSuper],
            'setting.index' => ['setting.index', $adminSuper],
            'setting.update' => ['setting.update', $adminSuper],
            'setting.year.index' => ['setting.year.index', $adminSuper],
            'setting.year.store' => ['setting.year.store', $adminSuper],
            'user.index' => ['user.index', $adminSuper],
            'user.store' => ['user.store', $adminSuper],
            'user.update' => ['user.update', $adminSuper],
            'user.destroy' => ['user.destroy', $adminSuper],
            'user.datatable' => ['user.datatable', $adminSuper],
            'whatsapp.send.bill-due-date' => ['whatsapp.send.bill-due-date', ['kasir']],
            'student.import' => ['student.import', ['admin']],
            'employee.import' => ['employee.import', ['admin']],
        ];
    }

    #[DataProvider('lockedRoutes')]
    public function test_sensitive_routes_are_locked(string $routeName, array $allowedRoles): void
    {
        $roles = ['super-admin', 'admin', 'kasir', 'kasir-unit', 'bendahara', 'penanggung-jawab-tabungan',
            'wali-kelas', 'kepala-sekolah', 'pegawai', 'orang-tua'];

        foreach ($roles as $role) {
            $this->assertSame(
                in_array($role, $allowedRoles, true),
                $this->passesMiddleware($this->createUser($role), $routeName),
                "{$routeName} untuk role {$role}"
            );
        }
    }

    public function test_pegawai_with_module_can_still_use_imports(): void
    {
        $this->assertTrue($this->passesMiddleware($this->createUser('pegawai', [2]), 'student.import'));
        $this->assertTrue($this->passesMiddleware($this->createUser('pegawai', [3]), 'employee.import'));
        $this->assertFalse($this->passesMiddleware($this->createUser('pegawai', [3]), 'student.import'));
    }

    private function passesMiddleware(User $user, string $routeName): bool
    {
        $route = Route::getRoutes()->getByName($routeName);
        $this->assertNotNull($route, "route {$routeName} tidak ditemukan");

        $this->actingAs($user);
        $request = Request::create('/');

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            $params = explode(',', substr($middleware, strpos($middleware, ':') + 1));

            if (str_starts_with($middleware, 'role:')) {
                $handler = new Role;
            } elseif (str_starts_with($middleware, 'access:')) {
                $handler = new Access;
            } elseif (str_starts_with($middleware, 'accessRights:')) {
                $handler = new AccessRights;
                $params = [$params[0]];
            } else {
                continue;
            }

            if ($handler->handle($request, fn () => response('ok'), ...$params)->getContent() !== 'ok') {
                return false;
            }
        }

        return true;
    }

    // ---------- D. registrasi ortu butuh OTP terverifikasi ----------

    public function test_register_password_requires_verified_otp(): void
    {
        $parent = $this->createParent($this->uniquePhone());
        $payload = ['password' => 'password123', 'password_confirm' => 'password123'];

        $this->post(route('auth.store.register-password', $parent->encrypted_id), $payload)
            ->assertRedirect(route('auth.register'));
        $this->get(route('auth.register.password', $parent->encrypted_id))
            ->assertRedirect(route('auth.register'));

        $this->assertNull(Parents::withoutGlobalScopes()->find($parent->id)->id_user);
    }

    public function test_register_flow_with_otp_creates_account_once(): void
    {
        $parent = $this->createParent($this->uniquePhone());
        DB::table('parent')->where('id', $parent->id)->update([
            'token' => '123456',
            'token_expired_at' => now()->addMinutes(10),
        ]);

        $this->post(route('auth.store.register.verification', $parent->encrypted_id), ['code' => '000000'])
            ->assertSessionHasErrors();
        $this->post(route('auth.store.register-password', $parent->encrypted_id), [
            'password' => 'password123', 'password_confirm' => 'password123',
        ])->assertRedirect(route('auth.register'));

        $this->post(route('auth.store.register.verification', $parent->encrypted_id), ['code' => '123456'])
            ->assertRedirectContains('register/password/');

        $this->post(route('auth.store.register-password', $parent->encrypted_id), [
            'password' => 'password123', 'password_confirm' => 'password123',
        ])->assertRedirect(route('base'));

        $parent = Parents::withoutGlobalScopes()->find($parent->id);
        $user = User::withoutGlobalScopes()->find($parent->id_user);
        $this->assertSame('orang-tua', $user->role->value);
        $this->assertNull($parent->token);

        // Flag habis dipakai; mengulang tidak boleh membuat akun / mengganti password lagi
        $this->post(route('auth.store.register-password', $parent->encrypted_id), [
            'password' => 'password999', 'password_confirm' => 'password999',
        ])->assertRedirect(route('auth.register'));
        $this->assertTrue(Hash::check('password123', User::withoutGlobalScopes()->find($user->id)->password));
    }

    public function test_register_otp_flag_is_bound_to_the_parent(): void
    {
        $parentA = $this->createParent($this->uniquePhone());
        $parentB = $this->createParent($this->uniquePhone());
        DB::table('parent')->where('id', $parentA->id)->update(['token' => '654321', 'token_expired_at' => now()->addMinutes(10)]);

        $this->post(route('auth.store.register.verification', $parentA->encrypted_id), ['code' => '654321']);

        $this->post(route('auth.store.register-password', $parentB->encrypted_id), [
            'password' => 'password123', 'password_confirm' => 'password123',
        ])->assertRedirect(route('auth.register'));
        $this->assertNull(Parents::withoutGlobalScopes()->find($parentB->id)->id_user);
    }

    public function test_register_otp_flag_expires(): void
    {
        $parent = $this->createParent($this->uniquePhone());
        DB::table('parent')->where('id', $parent->id)->update(['token' => '111222', 'token_expired_at' => now()->addMinutes(10)]);
        $this->post(route('auth.store.register.verification', $parent->encrypted_id), ['code' => '111222']);

        $this->travel(16)->minutes();

        $this->post(route('auth.store.register-password', $parent->encrypted_id), [
            'password' => 'password123', 'password_confirm' => 'password123',
        ])->assertRedirect(route('auth.register'));
        $this->assertNull(Parents::withoutGlobalScopes()->find($parent->id)->id_user);
    }

    // ---------- E/G. reset password ----------

    private function forgotSession(string $phone, string $code = '424242'): array
    {
        return ['forgot_password' => [
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(10)->getTimestamp(),
            'attempts' => 0,
        ]];
    }

    public function test_reset_password_without_otp_is_rejected(): void
    {
        $user = $this->createUser('admin');
        $old = $user->password;

        $this->get(route('auth.reset-password'))->assertRedirect(route('auth.forgot-password'));
        $this->post(route('auth.store.reset-password'), [
            'password' => 'password123', 'password_confirm' => 'password123',
        ])->assertRedirect(route('auth.forgot-password'));

        $this->assertSame($old, User::withoutGlobalScopes()->find($user->id)->password);
    }

    public function test_old_route_shapes_that_leaked_user_ids_are_gone(): void
    {
        $user = $this->createUser('admin');

        $this->post('reset-password/'.$user->encrypted_id, ['password' => 'password123', 'password_confirm' => 'password123'])
            ->assertNotFound();
        $this->post('forgot-password/verification/'.$user->encrypted_id, ['code' => '123456'])->assertNotFound();
    }

    public function test_reset_password_single_account_flow(): void
    {
        $user = $this->createUser('kasir');

        $this->withSession($this->forgotSession($user->phone))
            ->post(route('auth.store.forgot-password.verification'), ['code' => '000000'])
            ->assertSessionHasErrors();

        $this->post(route('auth.store.forgot-password.verification'), ['code' => '424242'])
            ->assertRedirect(route('auth.reset-password'));

        $this->post(route('auth.store.reset-password'), [
            'password' => 'password123', 'password_confirm' => 'password123',
        ])->assertRedirect(route('base'));

        $this->assertTrue(Hash::check('password123', User::withoutGlobalScopes()->find($user->id)->password));

        // Flag dibuang setelah dipakai
        $this->post(route('auth.store.reset-password'), [
            'password' => 'password999', 'password_confirm' => 'password999',
        ])->assertRedirect(route('auth.forgot-password'));
        $this->assertTrue(Hash::check('password123', User::withoutGlobalScopes()->find($user->id)->password));
    }

    public function test_reset_password_shared_phone_lets_user_choose_account(): void
    {
        $phone = $this->uniquePhone();
        $a = $this->createUser('kasir');
        $b = $this->createUser('orang-tua');
        DB::table('users')->whereIn('id', [$a->id, $b->id])->update(['phone' => $phone]);
        $other = $this->createUser('admin');
        $otherHash = $other->password;
        $bHash = User::withoutGlobalScopes()->find($b->id)->password;

        $this->withSession($this->forgotSession($phone))
            ->post(route('auth.store.forgot-password.verification'), ['code' => '424242'])
            ->assertRedirect(route('auth.forgot-password.accounts'));

        // Tanpa memilih akun tidak bisa reset
        $this->post(route('auth.store.reset-password'), [
            'password' => 'password123', 'password_confirm' => 'password123',
        ])->assertRedirect(route('auth.forgot-password'));

        $this->get(route('auth.forgot-password.accounts'))
            ->assertOk()
            ->assertSee($a->name)
            ->assertSee($b->name);

        // Akun yang nomornya berbeda tidak boleh dipilih
        $this->post(route('auth.store.forgot-password.accounts'), ['account' => $other->encrypted_id])
            ->assertRedirect(route('auth.forgot-password.accounts'));
        $this->post(route('auth.store.reset-password'), [
            'password' => 'password123', 'password_confirm' => 'password123',
        ])->assertRedirect(route('auth.forgot-password'));
        $this->assertSame($otherHash, User::withoutGlobalScopes()->find($other->id)->password);

        $this->post(route('auth.store.forgot-password.accounts'), ['account' => $a->encrypted_id])
            ->assertRedirect(route('auth.reset-password'));
        $this->post(route('auth.store.reset-password'), [
            'password' => 'password123', 'password_confirm' => 'password123',
        ])->assertRedirect(route('base'));

        $this->assertTrue(Hash::check('password123', User::withoutGlobalScopes()->find($a->id)->password));
        $this->assertSame($bHash, User::withoutGlobalScopes()->find($b->id)->password);
    }

    public function test_forgot_otp_locks_after_too_many_attempts(): void
    {
        $user = $this->createUser('kasir');
        $this->withSession($this->forgotSession($user->phone));

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('auth.store.forgot-password.verification'), ['code' => '000000'])->assertSessionHasErrors();
        }

        $this->post(route('auth.store.forgot-password.verification'), ['code' => '424242'])
            ->assertRedirect(route('auth.forgot-password'));
        $this->post(route('auth.store.reset-password'), [
            'password' => 'password123', 'password_confirm' => 'password123',
        ])->assertRedirect(route('auth.forgot-password'));
    }

    public function test_forgot_otp_expired_sends_new_code_and_rejects_old(): void
    {
        $user = $this->createUser('kasir');
        $session = $this->forgotSession($user->phone);
        $session['forgot_password']['expires_at'] = now()->subMinute()->getTimestamp();

        $this->withSession($session)
            ->post(route('auth.store.forgot-password.verification'), ['code' => '424242'])
            ->assertSessionHasErrors();

        $this->assertNotSame('424242', session('forgot_password.code'));
        $this->assertGreaterThan(now()->getTimestamp(), session('forgot_password.expires_at'));
        $this->post(route('auth.store.reset-password'), [
            'password' => 'password123', 'password_confirm' => 'password123',
        ])->assertRedirect(route('auth.forgot-password'));
    }

    // ---------- guard WhatsApp ----------

    public function test_whatsapp_is_disabled_outside_production(): void
    {
        $this->assertFalse(Whatsapp::enabled());

        Log::spy();
        $this->assertTrue(Whatsapp::send('08123456789', 'halo'));
        Log::shouldHaveReceived('info')->withArgs(fn ($message) => str_contains($message, 'skipped'))->once();
    }

    public function test_whatsapp_switch_can_disable_even_in_production(): void
    {
        $this->app['env'] = 'production';

        config(['services.whatsapp.enabled' => false]);
        $this->assertFalse(Whatsapp::enabled());

        config(['services.whatsapp.enabled' => 'false']);
        $this->assertFalse(Whatsapp::enabled());

        config(['services.whatsapp.enabled' => true]);
        $this->assertTrue(Whatsapp::enabled());
    }
}
