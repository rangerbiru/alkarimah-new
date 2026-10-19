<?php

namespace Tests\Feature;

use App\Helpers\Phone;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Nomor HP dibandingkan dalam bentuk ternormalisasi (tanpa -, spasi, titik; +62 / 62 menjadi 0) di login,
 * lupa password, dan registrasi, tanpa mengubah data di DB.
 */
class LoginPhoneNormalizationTest extends ModuleAccessTestCase
{
    private function digits(): string
    {
        return '8'.random_int(100000000, 999999999); // 10 digit setelah 0
    }

    private function account(string $storedPhone, string $role = 'kasir'): User
    {
        $user = $this->createUser($role);
        DB::table('users')->where('id', $user->id)->update(['phone' => $storedPhone, 'password' => Hash::make('rahasia')]);

        return User::withoutGlobalScopes()->find($user->id);
    }

    private function loginAs(string $username): ?int
    {
        Auth::forgetGuards();
        $this->flushSession();
        $this->post(route('auth.authenticate'), ['username' => $username, 'password' => 'rahasia']);

        return Auth::id();
    }

    public function test_php_and_sql_normalisation_agree(): void
    {
        $cases = [
            '081234567890' => '081234567890',
            '0812-3456-7890' => '081234567890',
            '0812 3456 7890' => '081234567890',
            '0812.3456.7890' => '081234567890',
            '+62 812-3456-7890' => '081234567890',
            '+6281234567890' => '081234567890',
            '6281234567890' => '081234567890',
            '' => '',
            '0' => '0',
            '+62' => '0',
        ];

        foreach ($cases as $input => $expected) {
            $this->assertSame($expected, Phone::normalize($input), "PHP [$input]");
            $sql = DB::selectOne('SELECT '.Phone::sql('x.v').' AS n FROM (SELECT ? AS v) x', [$input])->n;
            $this->assertSame($expected, (string) $sql, "SQL [$input]");
        }
    }

    public function test_stored_phone_with_dashes_logs_in_with_plain_digits(): void
    {
        $d = $this->digits();
        $user = $this->account('0'.substr($d, 0, 3).'-'.substr($d, 3, 4).'-'.substr($d, 7));

        $this->assertSame($user->id, $this->loginAs('0'.$d));
        $this->assertSame($user->id, $this->loginAs('0'.substr($d, 0, 3).' '.substr($d, 3)));
    }

    public function test_stored_international_format_logs_in_with_local_format_and_back(): void
    {
        $d = $this->digits();
        $plus = $this->account('+62 '.$d);
        $this->assertSame($plus->id, $this->loginAs('0'.$d));

        $d2 = $this->digits();
        $local = $this->account('0'.$d2);
        $this->assertSame($local->id, $this->loginAs('+62'.$d2));
        $this->assertSame($local->id, $this->loginAs('62'.$d2));
    }

    public function test_blank_numbers_never_match(): void
    {
        $zero = $this->account('0');
        $blank = $this->account('');

        foreach (['0', '+62', '62', '-', ' '] as $input) {
            $this->assertNull($this->loginAs($input), "input [$input]");
        }

        $this->assertNotNull($zero);
        $this->assertNotNull($blank);
    }

    public function test_forgot_password_finds_accounts_stored_in_another_format(): void
    {
        Validator::extend('captcha', fn () => true);
        $d = $this->digits();
        $this->account('0'.substr($d, 0, 3).'-'.substr($d, 3, 4).'-'.substr($d, 7));

        $this->post(route('auth.store.forgot-password'), ['phone' => '+62'.$d, 'captcha' => 'x'])
            ->assertRedirect(route('auth.forgot-password.verification'));
        $this->assertSame('0'.$d, session('forgot_password.phone'));

        $this->post(route('auth.store.forgot-password'), ['phone' => '0899'.random_int(1000000, 9999999), 'captcha' => 'x'])
            ->assertRedirect(route('auth.forgot-password'));
    }

    public function test_registration_finds_the_parent_stored_in_another_format(): void
    {
        Validator::extend('captcha', fn () => true);
        $d = $this->digits();
        $parentId = DB::table('parent')->insertGetId([
            'name' => 'Ortu Daftar', 'phone' => '+62 '.$d, 'gender' => 'male', 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);
        $parent = \App\Models\Parents::withoutGlobalScopes()->find($parentId);

        $location = $this->post(route('auth.store.register'), ['phone' => '0'.substr($d, 0, 3).'-'.substr($d, 3), 'captcha' => 'x'])
            ->assertRedirectContains('register/verification/')->headers->get('Location');

        $this->assertSame($parent->id, (int) \Illuminate\Support\Facades\Crypt::decrypt(basename($location)));
    }

    public function test_registration_refuses_a_number_already_used_by_a_parent_account_in_another_format(): void
    {
        Validator::extend('captcha', fn () => true);
        $d = $this->digits();
        $user = $this->account('0'.$d, 'orang-tua');
        DB::table('parent')->insert([
            'id_user' => $user->id, 'name' => 'Ortu Terdaftar', 'phone' => '0'.$d, 'gender' => 'male', 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);

        $this->post(route('auth.store.register'), ['phone' => '+62'.$d, 'captcha' => 'x'])
            ->assertRedirect(route('auth.register'))
            ->assertSessionHasErrors();
    }
}
