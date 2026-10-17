<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Helpers\Whatsapp;
use App\Http\Requests\AuthForgotPasswordRequest;
use App\Http\Requests\AuthPasswordRequest;
use App\Http\Requests\AuthRegisterRequest;
use App\Http\Requests\AuthRequest;
use App\Http\Requests\AuthVerificationRequest;
use App\Models\Employee;
use App\Models\Parents;
use App\Services\AccountLinkException;
use App\Services\AccountLinker;
use App\Models\Scopes\BranchScope;
use App\Models\Student;
use App\Models\User;
use App\Support\OtpFlag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private $path = 'frontend.auth.';

    public function index()
    {
        if (Auth::check()) {
            return Redirect::route('dashboard.index');
        }

        return view($this->path.'login');
    }

    public function register()
    {
        if (Auth::check()) {
            return Redirect::route('dashboard.index');
        }

        return view($this->path.'register');
    }

    public function registerVerification(Parents $parent)
    {
        return view($this->path.'register-verification', [
            'parent' => $parent,
        ]);
    }

    public function registerPassword(Parents $parent)
    {
        if (! $this->registerOtpVerified($parent)) {
            return Redirect::route('auth.register')->withErrors(__('string.otp_session_invalid'));
        }

        return view($this->path.'register-password', [
            'parent' => $parent,
        ]);
    }

    public function forgotPassword()
    {
        if (Auth::check()) {
            return Redirect::route('dashboard.index');
        }

        return view($this->path.'forgot-password');
    }

    public function forgotPasswordVerification()
    {
        $context = session('forgot_password');

        if (! is_array($context)) {
            return Redirect::route('auth.forgot-password')->withErrors(__('string.otp_session_invalid'));
        }

        return view($this->path.'forgot-password-verification', [
            'phone' => $context['phone'],
        ]);
    }

    public function forgotPasswordAccounts()
    {
        $phone = $this->forgotVerifiedPhone();

        if ($phone === null) {
            return Redirect::route('auth.forgot-password')->withErrors(__('string.otp_session_invalid'));
        }

        $accounts = User::wherePhone($phone)->orderBy('id')->get()->map(fn (User $u) => (object) [
            'id' => $u->encrypted_id,
            'name' => $u->name,
            'role' => $this->roleLabel($u->role->value),
        ]);

        return view($this->path.'forgot-password-accounts', ['accounts' => $accounts]);
    }

    public function resetPassword()
    {
        $user = $this->forgotVerifiedUser();

        if ($user === null) {
            return Redirect::route('auth.forgot-password')->withErrors(__('string.otp_session_invalid'));
        }

        return view($this->path.'reset-password', [
            'user' => $user,
        ]);
    }

    // Maksimum kandidat akun (email / nomor HP sama) yang password-nya dicek per percobaan login
    private const LOGIN_MAX_CANDIDATES = 10;

    // Percobaan gagal per menit untuk satu kombinasi identifier + IP
    private const LOGIN_MAX_ATTEMPTS = 10;

    public function authenticate(AuthRequest $request)
    {
        if (! $request->validated()) {
            return back()->withInput($request->all);
        }

        $username = trim((string) $request->username);
        $password = (string) $request->password;
        $remember = (bool) $request->remember;

        // Pembatasan per kombinasi identifier + IP (pola login bawaan Laravel): satu akun yang diserang
        // tidak mengunci staf lain di jaringan pondok yang sama
        $throttleKey = Str::lower(str_replace('-', '', $username)).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::LOGIN_MAX_ATTEMPTS)) {
            return back()->withErrors([
                'useraccount' => __('string.login_too_many_attempts', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ]);
        }

        $result = $this->resolveLoginAccount($username, $password);

        if (is_string($result)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors(['useraccount' => $result]);
        }

        $user = $result;

        // A dual-role user can still log in as an employee without active students. An inactive
        // employee who is also a parent counts as a parent only
        if (($user->role == UserRole::OrangTua || $user->acts_as_parent_only) && ! $user->has_dual_role) {
            $parent = $user->parent;

            if (! $parent || Student::withoutGlobalScope(BranchScope::class)->whereIdParent($parent->id)->count() == 0) {
                return back()->withErrors([
                    'useraccount' => 'Data Siswa Anda belum ada, harap hubungi Admin untuk mendaftarkan data siswa Anda',
                ]);
            }
        }

        RateLimiter::clear($throttleKey);
        Auth::login($user, $remember);
        User::whereId($user->id)->update(['lastlogin_at' => date('Y-m-d H:i:s')]);
        $request->session()->regenerate();

        return Redirect::intended(route('dashboard.index'));
    }

    /**
     * Cari SEMUA akun dengan email / nomor HP ini, cek password ke masing-masing, lalu selesaikan ke akun
     * tujuan (akun alias -> akun pegawainya). Tepat satu akun tujuan -> User. Selain itu -> pesan error.
     */
    private function resolveLoginAccount(string $username, string $password): User|string
    {
        $failed = 'Email / No. HP atau Password Salah';
        $phone = str_replace('-', '', $username);

        $candidates = User::query()
            ->where(function ($query) use ($username, $phone) {
                $query->where('email', $username);

                // Nomor kosong atau "0" tidak pernah dipakai mencari akun
                if ($phone !== '' && $phone !== '0') {
                    $query->orWhere('phone', $phone);
                }
            })
            ->orderBy('id')
            ->limit(self::LOGIN_MAX_CANDIDATES + 1)
            ->get();

        if ($candidates->count() > self::LOGIN_MAX_CANDIDATES) {
            Log::warning('login-too-many-candidates', [
                'username' => $username,
                'candidates' => User::where('email', $username)->orWhere('phone', $phone)->count(),
                'checked' => self::LOGIN_MAX_CANDIDATES,
            ]);
            $candidates = $candidates->take(self::LOGIN_MAX_CANDIDATES);
        }

        if ($candidates->isEmpty()) {
            // Waktu respons sama dengan akun yang ada: tidak membocorkan keberadaan akun
            Hash::check($password, '$2y$12$.bWkvtr5/Bsd7xlIMTZCZesLfvWBPfLQ/JyQBuaLN6Dk2zmCpaU6O');

            return $failed;
        }

        $targets = collect();

        foreach ($candidates as $candidate) {
            if (! Hash::check($password, $candidate->password)) {
                continue;
            }

            if (! $candidate->linked_user_id) {
                $targets->put($candidate->id, $candidate);

                continue;
            }

            $target = User::find($candidate->linked_user_id);

            // Tujuan alias harus akun pegawai yang masih ada dan bukan alias juga
            if (! $target || $target->linked_user_id) {
                Log::warning('login-alias-target-invalid', ['alias_user_id' => $candidate->id, 'target' => $candidate->linked_user_id]);

                continue;
            }

            $targets->put($target->id, $target);
        }

        if ($targets->isEmpty()) {
            return $failed;
        }

        if ($targets->count() > 1) {
            Log::warning('login-ambiguous-accounts', [
                'username' => $username,
                'target_user_ids' => $targets->keys()->all(),
            ]);

            return __('string.login_ambiguous_accounts');
        }

        return $targets->first();
    }

    public function storeRegister(AuthRegisterRequest $request)
    {
        $error = false;
        $parent = Parents::select('id', 'phone')->wherePhone($request->phone)->first();

        // Nomor akun alias (akun orang tua lama yang digabung ke akun pegawai) sudah punya akun
        if (User::wherePhone($request->phone)->whereNotNull('linked_user_id')->exists()) {
            return Redirect::route('auth.register')->withErrors(__('string.you_has_been_registered'))->withInput();
        }

        if (empty($parent)) {
            $error = __('string.you_not_registered_as_parent');
        }

        if ($error == false) {
            // Cek semua user dengan nomor ini tanpa melihat role. Nomor yang dipakai akun pegawai
            // tidak ditolak di sini: setelah OTP terverifikasi, data orang tua dihubungkan ke akun
            // pegawai tsb (bukan dibuatkan akun baru).
            $registered = User::wherePhone($parent->phone)->where('role', UserRole::OrangTua->value)->exists()
                || ($parent->id_user && User::whereKey($parent->id_user)->exists());

            if ($registered) {
                $error = __('string.you_has_been_registered');
            }
        }

        if ($error == false) {
            $parent->token = rand(111111, 999999);
            $parent->token_expired_at = date('Y-m-d H:i:s', strtotime('+10 minute'));
            $parent->save();

            $message = "*Pondok Pesantren*\n";
            $message .= "*Al-Karimah*\n\n";
            $message .= 'Berikut ini adalah Kode Verifikasi Anda : *'.$parent->token."*\n\n";
            $message .= "Kode ini hanya berlaku 10 menit. JANGAN BAGIKAN kode verifikasi ini kepada siapapun.\n\n";
            $message .= '```app.alkarimah.org```';

            Whatsapp::send($parent->phone, $message);
        }

        if ($error !== false) {
            return Redirect::route('auth.register')->withErrors($error)->withInput();
        }

        return Redirect::route('auth.register.verification', $parent->encrypted_id);
    }

    public function storeRegisterVerification(AuthVerificationRequest $request, Parents $parent)
    {
        $error = false;

        if (empty($parent->token) || ! hash_equals((string) $parent->token, (string) $request->code)) {
            $error = __('validation.captcha', ['attribute' => __('label.verification_code')]);
        }

        if ($error == false && strtotime($parent->token_expired_at) < strtotime(date('Y-m-d H:i:s'))) {
            $error = __('string.verification_code_expired');

            $parent->token = rand(111111, 999999);
            $parent->token_expired_at = date('Y-m-d H:i:s', strtotime('+10 minute'));
            $parent->save();

            $message = "*Pondok Pesantren*\n";
            $message .= "*Al-Karimah*\n\n";
            $message .= 'Berikut ini adalah Kode Verifikasi Anda : *'.$parent->token."*\n\n";
            $message .= "Kode ini hanya berlaku 10 menit. JANGAN BAGIKAN kode verifikasi ini kepada siapapun.\n\n";
            $message .= '```app.alkarimah.org```';

            Whatsapp::send($parent->phone, $message);
        }

        if ($error !== false) {
            return Redirect::route('auth.register.verification', $parent->encrypted_id)->withErrors($error)->withInput();
        }

        $parent->token = null;
        $parent->token_expired_at = null;
        $parent->save();

        // Nomor ini milik akun staf: hubungkan ke akun pegawai, jangan buat user baru
        $staffUsers = $this->staffUsersForPhone($parent->phone);

        if ($staffUsers->isNotEmpty()) {
            $employee = $staffUsers->count() === 1
                ? Employee::withoutGlobalScopes()->where('id_user', $staffUsers->first()->id)->first()
                : null;

            try {
                if (! $employee) {
                    throw new AccountLinkException('no employee');
                }

                app(AccountLinker::class)->link($employee->id, $parent->id);
            } catch (AccountLinkException) {
                return Redirect::route('auth.register')->withErrors(__('string.phone_registered_as_employee_contact_admin'));
            }

            return Redirect::route('base')->with('success', __('message.registration_linked_to_employee'));
        }

        OtpFlag::grant('register', ['parent' => $parent->id, 'phone' => $parent->phone]);

        return Redirect::route('auth.register.password', $parent->encrypted_id);
    }

    public function storeRegisterPassword(AuthPasswordRequest $request, Parents $parent)
    {
        if (! $this->registerOtpVerified($parent)) {
            return Redirect::route('auth.register')->withErrors(__('string.otp_session_invalid'));
        }

        $user = User::forceCreate([
            'name' => $parent->name,
            'email' => strtolower(explode(' ', $parent->name)[0]).'_'.$parent->id.'@binabbas.org',
            'phone' => $parent->phone,
            'gender' => $parent->gender->value,
            'password' => $request->password,
            'role' => UserRole::OrangTua,
            'branch_id' => $parent->branch_id,
        ]);

        $parent->update([
            'id_user' => $user->id,
            'token' => null,
            'token_expired_at' => null,
        ]);
        $user->update([
            'email' => strtolower(explode(' ', $parent->name)[0]).'_'.$user->id.'@binabbas.org',
        ]);

        OtpFlag::forget('register');

        return Redirect::route('base')->with('success', __('message.registration_success'));
    }

    public function storeForgotPassword(AuthForgotPasswordRequest $request)
    {
        $phone = $request->phone;
        $digits = preg_replace('/\D/', '', (string) $phone);

        // Nomor kosong / "0" / terlalu pendek tidak pernah dianggap akun yang valid
        $found = strlen($digits) >= 8 && User::wherePhone($phone)->exists();

        if (! $found) {
            return Redirect::route('auth.forgot-password')->withErrors(__('string.account_not_found'))->withInput();
        }

        $this->startForgotOtp($phone);

        return Redirect::route('auth.forgot-password.verification');
    }

    public function storeForgotPasswordVerification(AuthVerificationRequest $request)
    {
        $context = session('forgot_password');

        if (! is_array($context)) {
            return Redirect::route('auth.forgot-password')->withErrors(__('string.otp_session_invalid'));
        }

        $error = false;
        $context['attempts'] = ($context['attempts'] ?? 0) + 1;

        if ($context['attempts'] > 5) {
            session()->forget('forgot_password');

            return Redirect::route('auth.forgot-password')->withErrors(__('string.otp_too_many_attempts'));
        }

        session()->put('forgot_password', $context);

        if ($context['expires_at'] < now()->getTimestamp()) {
            $error = __('string.verification_code_expired');
            $this->startForgotOtp($context['phone']);
        } elseif (! hash_equals((string) $context['code'], (string) $request->code)) {
            $error = __('validation.captcha', ['attribute' => __('label.verification_code')]);
        }

        if ($error !== false) {
            return Redirect::route('auth.forgot-password.verification')->withErrors($error)->withInput();
        }

        session()->forget('forgot_password');
        session()->put('forgot_password_phone', $context['phone']);
        OtpFlag::grant('forgot_phone', ['phone' => $context['phone']]);

        $users = User::wherePhone($context['phone'])->get();

        if ($users->count() === 1) {
            $this->grantForgotUser($context['phone'], $users->first());

            return Redirect::route('auth.reset-password');
        }

        return Redirect::route('auth.forgot-password.accounts');
    }

    public function storeForgotPasswordAccounts(Request $request)
    {
        $phone = $this->forgotVerifiedPhone();

        if ($phone === null) {
            return Redirect::route('auth.forgot-password')->withErrors(__('string.otp_session_invalid'));
        }

        $request->validate(['account' => 'required|string']);

        try {
            $id = Crypt::decrypt($request->account);
        } catch (\Throwable) {
            return Redirect::route('auth.forgot-password.accounts')->withErrors(__('string.account_not_found'));
        }

        // Hanya akun yang memakai nomor yang sudah terverifikasi OTP
        $user = User::wherePhone($phone)->whereKey($id)->first();

        if (! $user) {
            return Redirect::route('auth.forgot-password.accounts')->withErrors(__('string.account_not_found'));
        }

        $this->grantForgotUser($phone, $user);

        return Redirect::route('auth.reset-password');
    }

    public function storeResetPassword(AuthPasswordRequest $request)
    {
        $user = $this->forgotVerifiedUser();

        if ($user === null) {
            return Redirect::route('auth.forgot-password')->withErrors(__('string.otp_session_invalid'));
        }

        $user->forceFill([
            'password' => $request->password,
            'token' => null,
            'token_expired_at' => null,
        ])->save();

        OtpFlag::forget('forgot_phone');
        OtpFlag::forget('forgot_user');
        session()->forget(['forgot_password_phone', 'forgot_password_user']);

        return Redirect::route('base')->with('success', __('message.reset_password_success'));
    }

    /**
     * Akun staf (role bukan orang-tua) yang memakai nomor HP ini.
     */
    private function staffUsersForPhone(?string $phone)
    {
        if (empty($phone)) {
            return collect();
        }

        return User::wherePhone($phone)->where('role', '!=', UserRole::OrangTua->value)->get();
    }

    private function registerOtpVerified(Parents $parent): bool
    {
        if (! OtpFlag::check('register', ['parent' => $parent->id, 'phone' => $parent->phone])) {
            return false;
        }

        // Parent yang sudah punya akun tidak boleh dibuatkan akun baru / diganti passwordnya lewat registrasi
        return empty($parent->id_user) || ! User::whereKey($parent->id_user)->exists();
    }

    private function startForgotOtp(string $phone): void
    {
        $code = rand(111111, 999999);

        session()->put('forgot_password', [
            'phone' => $phone,
            'code' => (string) $code,
            'expires_at' => now()->addMinutes(10)->getTimestamp(),
            'attempts' => 0,
        ]);

        $message = "*Pondok Pesantren*\n";
        $message .= "*Al-Karimah*\n\n";
        $message .= 'Berikut ini adalah Kode Verifikasi Anda : *'.$code."*\n\n";
        $message .= "Kode ini hanya berlaku 10 menit. JANGAN BAGIKAN kode verifikasi ini kepada siapapun.\n\n";
        $message .= '```app.alkarimah.org```';

        Whatsapp::send($phone, $message);
    }

    /** Nomor HP yang OTP-nya sudah terverifikasi (masih berlaku), atau null. */
    private function forgotVerifiedPhone(): ?string
    {
        $phone = session('forgot_password_phone');

        return $phone && OtpFlag::check('forgot_phone', ['phone' => $phone]) ? $phone : null;
    }

    private function grantForgotUser(string $phone, User $user): void
    {
        session()->put('forgot_password_user', $user->id);
        OtpFlag::grant('forgot_user', ['phone' => $phone, 'user' => $user->id]);
    }

    /** Akun yang dipilih (OTP nomornya terverifikasi), atau null. */
    private function forgotVerifiedUser(): ?User
    {
        $phone = $this->forgotVerifiedPhone();
        $userId = session('forgot_password_user');

        if ($phone === null || ! $userId || ! OtpFlag::check('forgot_user', ['phone' => $phone, 'user' => $userId])) {
            return null;
        }

        // Nomor akun harus masih sama dengan nomor yang diverifikasi
        return User::whereKey($userId)->wherePhone($phone)->first();
    }

    private function roleLabel(string $role): string
    {
        $key = 'label.'.str_replace('-', '_', $role);

        return Lang::has($key) ? __($key) : ucwords(str_replace('-', ' ', $role));
    }

    public function logout()
    {
        Auth::logout();

        return Redirect::route('base');
    }
}
