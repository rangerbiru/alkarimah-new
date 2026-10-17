<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * users.role (dan branch_id, token, ...) tidak pernah bisa diisi lewat mass assignment.
 * Port dari PPIA (role-mass-assignment), disesuaikan dengan DB testing Al-Karimah.
 */
class UserRoleMassAssignmentTest extends ModuleAccessTestCase
{
    public function test_model_ignores_role_and_sensitive_fields_in_mass_assignment(): void
    {
        $user = User::create([
            'name' => 'Mass Assign', 'email' => 'mass'.random_int(1, 999999).'@example.test', 'phone' => '08'.random_int(100000000, 999999999),
            'gender' => 'male', 'password' => 'secret123',
            'role' => UserRole::Admin, 'branch_id' => 77, 'token' => '123456', 'lastlogin_at' => now(),
        ]);

        $fresh = DB::table('users')->where('id', $user->id)->first();
        $this->assertNotSame('admin', $fresh->role);
        $this->assertNull($fresh->token);
        $this->assertNull($fresh->lastlogin_at);
        $this->assertNotSame(77, (int) $fresh->branch_id);
    }

    public function test_force_create_and_force_fill_still_set_role_explicitly(): void
    {
        $user = User::forceCreate([
            'name' => 'Explicit', 'email' => 'explicit'.random_int(1, 999999).'@example.test', 'phone' => '08'.random_int(100000000, 999999999),
            'gender' => 'male', 'password' => 'secret123', 'role' => UserRole::Bendahara, 'branch_id' => self::BRANCH_ID,
        ]);
        $this->assertSame('bendahara', DB::table('users')->where('id', $user->id)->value('role'));

        $user->forceFill(['role' => UserRole::Kasir])->save();
        $this->assertSame('kasir', DB::table('users')->where('id', $user->id)->value('role'));

        $user->update(['role' => UserRole::Admin]);
        $this->assertSame('kasir', DB::table('users')->where('id', $user->id)->value('role'));
    }

    public function test_profile_update_ignores_role_and_sensitive_fields(): void
    {
        $user = $this->createUser('orang-tua');

        $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => 'Nama Baru',
            'email' => 'baru'.$user->id.'@example.test',
            'phone' => $user->phone,
            'gender' => 'male',
            'role' => UserRole::Admin->value,
            'branch_id' => 99,
            'token' => '123456',
        ])->assertRedirect(route('user.profile'));

        $row = DB::table('users')->where('id', $user->id)->first();
        $this->assertSame('Nama Baru', $row->name);
        $this->assertSame('orang-tua', $row->role);
        $this->assertSame(self::BRANCH_ID, (int) $row->branch_id);
        $this->assertNull($row->token);
    }

    public function test_non_admin_cannot_create_user(): void
    {
        $parent = $this->createUser('orang-tua');

        $this->actingAs($parent)->post(route('user.store'), [
            'name' => 'Penyusup', 'phone' => '081200000001', 'email' => 'penyusup@example.com', 'gender' => 'male',
            'password' => 'password123', 'password_confirm' => 'password123', 'role' => UserRole::Admin->value,
        ])->assertRedirect(route('base'));

        $this->assertDatabaseMissing('users', ['email' => 'penyusup@example.com']);
    }

    public function test_non_admin_cannot_update_other_user(): void
    {
        $parent = $this->createUser('orang-tua');
        $admin = $this->createUser('admin');
        $password = $admin->password;

        $this->actingAs($parent)->put(route('user.update', Crypt::encrypt($admin->id)), [
            'name' => $admin->name, 'phone' => $admin->phone, 'email' => 'x@example.test', 'gender' => 'male',
            'password' => 'password123', 'password_confirm' => 'password123',
        ])->assertRedirect(route('base'));

        $this->assertSame($password, DB::table('users')->where('id', $admin->id)->value('password'));
    }

    public function test_admin_cannot_create_user_with_role_outside_allowed_list(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)->post(route('user.store'), [
            'name' => 'Admin Baru', 'phone' => '081200000002', 'email' => 'admin-baru@example.com', 'gender' => 'male',
            'password' => 'password123', 'password_confirm' => 'password123', 'role' => UserRole::Admin->value,
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'admin-baru@example.com']);
    }

    public function test_admin_creates_allowed_role_through_force_create(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)->post(route('user.store'), [
            'name' => 'Bendahara Baru', 'phone' => '081200000003', 'email' => 'bendahara-baru@example.com', 'gender' => 'male',
            'password' => 'password123', 'password_confirm' => 'password123', 'role' => UserRole::Bendahara->value,
        ]);

        $this->assertSame('bendahara', DB::table('users')->where('email', 'bendahara-baru@example.com')->value('role'));
    }
}
