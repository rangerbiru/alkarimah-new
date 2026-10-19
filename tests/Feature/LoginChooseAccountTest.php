<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccountLinker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Halaman pilih akun: beberapa akun tujuan dengan nomor dan password sama. Hanya akun yang password-nya
 * cocok yang ditampilkan; daftar id di session (5 menit, dipakai sekali); server memvalidasi pilihan.
 */
class LoginChooseAccountTest extends ModuleAccessTestCase
{
    private function phone(): string
    {
        return '08'.random_int(1000000000, 9999999999);
    }

    private function account(string $role, string $phone, string $password, string $name): User
    {
        $user = $this->createUser($role);
        DB::table('users')->where('id', $user->id)->update(['phone' => $phone, 'password' => Hash::make($password), 'name' => $name]);

        return User::withoutGlobalScopes()->find($user->id);
    }

    private function parentRow(User $user, array $children): int
    {
        $parentId = DB::table('parent')->insertGetId([
            'id_user' => $user->id, 'name' => $user->name, 'phone' => $user->phone, 'gender' => 'male', 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);

        foreach ($children as $child) {
            DB::table('student')->insert([
                'id_parent' => $parentId, 'id_class' => 1, 'nis' => 'N'.random_int(1000000, 9999999), 'name' => $child,
                'gender' => 'male', 'religion' => 'Islam', 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
            ]);
        }

        return $parentId;
    }

    /** Dua akun dengan nomor dan password sama: kasir dan orang tua (dengan anak). */
    private function twins(): array
    {
        $phone = $this->phone();
        $kasir = $this->account('kasir', $phone, 'sama', 'Kasir Kembar');
        $ortu = $this->account('orang-tua', $phone, 'sama', 'Ortu Kembar');
        $this->parentRow($ortu, ['Ahmad Santri', 'Budi Santri']);

        return [$phone, $kasir, $ortu];
    }

    private function login(string $username, string $password, bool $remember = false)
    {
        Auth::forgetGuards();

        return $this->post(route('auth.authenticate'), ['username' => $username, 'password' => $password] + ($remember ? ['remember' => '1'] : []));
    }

    private function choose(User $user)
    {
        return $this->post(route('auth.store.choose-account'), ['account' => $user->encrypted_id]);
    }

    public function test_same_phone_and_password_shows_the_choice_and_logs_into_the_chosen_account(): void
    {
        [$phone, $kasir, $ortu] = $this->twins();

        $this->login($phone, 'sama')->assertRedirect(route('auth.choose-account'));
        $this->assertFalse(Auth::check());
        $this->assertSame([$kasir->id, $ortu->id], session('login_choice.ids'));

        $this->get(route('auth.choose-account'))->assertOk()
            ->assertSee('Kasir Kembar')->assertSee('Ortu Kembar')
            ->assertSee(__('label.kasir'))->assertSee('Orang Tua')
            ->assertSee('Ahmad Santri, Budi Santri')
            ->assertDontSee((string) $kasir->id.'"', false);

        $this->choose($ortu)->assertRedirect(route('dashboard.index'));
        $this->assertSame($ortu->id, Auth::id());
        $this->assertNull(session('login_choice'));

        $presented = DB::table('activity_log')->where('log_name', 'login-ambiguous-accounts')->where('properties', 'like', '%"presented"%')->latest('id')->first();
        $chosen = DB::table('activity_log')->where('log_name', 'login-ambiguous-accounts')->where('properties', 'like', '%"chosen"%')->latest('id')->first();
        $this->assertSame([$kasir->id, $ortu->id], json_decode($presented->properties, true)['candidate_user_ids']);
        $this->assertSame($phone, json_decode($presented->properties, true)['username']);
        $this->assertSame($ortu->id, json_decode($chosen->properties, true)['chosen_user_id']);
        $this->assertSame([$kasir->id, $ortu->id], json_decode($chosen->properties, true)['candidate_user_ids']);
        $this->assertSame($ortu->id, (int) $chosen->causer_id);
    }

    public function test_accounts_whose_password_does_not_match_are_not_shown(): void
    {
        [$phone, $kasir, $ortu] = $this->twins();
        $other = $this->account('bendahara', $phone, 'lain', 'Bendahara Beda Password');

        $this->login($phone, 'sama')->assertRedirect(route('auth.choose-account'));
        $this->assertSame([$kasir->id, $ortu->id], session('login_choice.ids'));
        $this->get(route('auth.choose-account'))->assertDontSee('Bendahara Beda Password');

        // Akun yang tidak ada di daftar ditolak walau id-nya valid
        $this->choose($other)->assertRedirect(route('base'))->assertSessionHasErrors(['useraccount' => __('string.login_choice_invalid')]);
        $this->assertFalse(Auth::check());
    }

    public function test_choice_outside_the_session_list_clears_the_list(): void
    {
        [$phone, $kasir] = $this->twins();
        $this->login($phone, 'sama');

        $this->post(route('auth.store.choose-account'), ['account' => 'bukan-terenkripsi'])
            ->assertRedirect(route('base'))->assertSessionHasErrors(['useraccount' => __('string.login_choice_invalid')]);

        // Daftar sudah dihapus: pilihan yang sah sekalipun harus login ulang
        $this->choose($kasir)->assertRedirect(route('base'))->assertSessionHasErrors(['useraccount' => __('string.login_choice_expired')]);
        $this->assertFalse(Auth::check());
    }

    public function test_expired_choice_returns_to_login(): void
    {
        [$phone, $kasir] = $this->twins();
        $this->login($phone, 'sama');

        $this->travel(301)->seconds();

        $this->get(route('auth.choose-account'))->assertRedirect(route('base'))
            ->assertSessionHasErrors(['useraccount' => __('string.login_choice_expired')]);
        $this->choose($kasir)->assertRedirect(route('base'));
        $this->assertFalse(Auth::check());
    }

    public function test_choice_can_be_used_only_once(): void
    {
        [$phone, $kasir, $ortu] = $this->twins();
        $this->login($phone, 'sama');
        $this->choose($kasir);
        $this->assertSame($kasir->id, Auth::id());

        Auth::logout();
        Auth::forgetGuards();
        $this->choose($ortu)->assertRedirect(route('base'))->assertSessionHasErrors(['useraccount' => __('string.login_choice_expired')]);
        $this->assertFalse(Auth::check());
    }

    public function test_remember_me_follows_the_original_login_input(): void
    {
        [$phone, $kasir] = $this->twins();
        $recaller = Auth::guard()->getRecallerName();

        $this->login($phone, 'sama', true);
        $this->choose($kasir)->assertCookie($recaller);

        Auth::logout();
        $this->login($phone, 'sama', false);
        $this->choose($kasir)->assertCookieMissing($recaller);
    }

    public function test_chosen_parent_account_still_goes_through_the_normal_checks(): void
    {
        $phone = $this->phone();
        $this->account('kasir', $phone, 'sama', 'Kasir');
        $ortu = $this->account('orang-tua', $phone, 'sama', 'Ortu Tanpa Santri');
        $this->parentRow($ortu, []);

        $this->login($phone, 'sama');
        $this->choose($ortu)->assertRedirect(route('base'))
            ->assertSessionHasErrors(['useraccount' => 'Data Siswa Anda belum ada, harap hubungi Admin untuk mendaftarkan data siswa Anda']);
        $this->assertFalse(Auth::check());
    }

    public function test_alias_account_still_logs_straight_into_the_employee_account(): void
    {
        $phone = $this->phone();
        $staff = $this->account('pegawai', $phone, 'sama', 'Pegawai');
        $employee = $this->createEmployee($staff);
        $ortu = $this->account('orang-tua', $phone, 'sama', 'Ortu Lama');
        $parentId = $this->parentRow($ortu, ['Anak']);
        app(AccountLinker::class)->link($employee->id, $parentId);

        // Alias dan akun pegawainya = satu akun tujuan: tidak ada halaman pilih akun
        $this->login($phone, 'sama')->assertRedirect(route('dashboard.index'));
        $this->assertSame($staff->id, Auth::id());
        $this->assertNull(session('login_choice'));
    }

    public function test_single_account_logs_in_directly(): void
    {
        $phone = $this->phone();
        $kasir = $this->account('kasir', $phone, 'sama', 'Kasir');
        $this->account('orang-tua', $phone, 'beda', 'Ortu');

        $this->login($phone, 'sama')->assertRedirect(route('dashboard.index'));
        $this->assertSame($kasir->id, Auth::id());
    }
}
