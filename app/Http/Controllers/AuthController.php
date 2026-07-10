<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Helpers\Whatsapp;
use App\Http\Requests\AuthForgotPasswordRequest;
use App\Http\Requests\AuthPasswordRequest;
use App\Http\Requests\AuthRegisterRequest;
use App\Http\Requests\AuthRequest;
use App\Http\Requests\AuthVerificationRequest;
use App\Models\Parents;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;

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

    public function forgotPasswordVerification(User $user)
    {
        return view($this->path.'forgot-password-verification', [
            'user' => $user,
        ]);
    }

    public function resetPassword($token)
    {
        $user = User::select('id')->whereToken(Crypt::decrypt($token))->firstOrFail();

        return view($this->path.'reset-password', [
            'user' => $user,
        ]);
    }

    public function authenticate(AuthRequest $request)
    {
        if ($request->validated()) {
            $remember = ($request->remember) ? true : false;
            $auth_email = ['email' => $request->username, 'password' => $request->password];
            $auth_phone = ['phone' => str_replace('-', '', $request->username), 'password' => $request->password];

            if (Auth::attempt($auth_email, $remember) or Auth::attempt($auth_phone, $remember)) {
                if (Auth::user()->is_orang_tua) {
                    $student = Student::whereIdParent(Auth::user()->parent->id)->count();

                    if ($student == 0) {
                        Auth::logout();

                        return back()->withErrors([
                            'useraccount' => 'Data Siswa Anda belum ada, harap hubungi Admin untuk mendaftarkan data siswa Anda',
                        ]);
                    }
                }

                User::whereId(Auth::id())->update(['lastlogin_at' => date('Y-m-d H:i:s')]);
                $request->session()->regenerate();

                return Redirect::intended(route('dashboard.index'));
            }

            return back()->withErrors([
                'useraccount' => 'Email / No. HP atau Password Salah',
            ]);
        } else {
            return back()->withInput($request->all);
        }
    }

    public function storeRegister(AuthRegisterRequest $request)
    {
        $error = false;
        $parent = Parents::select('id', 'phone')->wherePhone($request->phone)->first();

        if (empty($parent)) {
            $error = __('string.you_not_registered_as_parent');
        }

        if ($error == false) {
            $user = User::wherePhone($parent->phone)->whereRole(UserRole::OrangTua->value)->count();

            if ($user > 0) {
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

        if ($parent->token != $request->code) {
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

        return Redirect::route('auth.register.password', $parent->encrypted_id);
    }

    public function storeRegisterPassword(AuthPasswordRequest $request, Parents $parent)
    {
        $user = User::create([
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

        return Redirect::route('base')->with('success', __('message.registration_success'));
    }

    public function storeForgotPassword(AuthForgotPasswordRequest $request)
    {
        $error = false;
        $user = User::select('id', 'phone')->wherePhone($request->phone)->first();

        if (empty($user)) {
            $error = __('string.account_not_found');
        }

        if ($error == false) {
            $user->token = rand(111111, 999999);
            $user->token_expired_at = date('Y-m-d H:i:s', strtotime('+10 minute'));
            $user->save();

            $message = "*Pondok Pesantren*\n";
            $message .= "*Al-Karimah*\n\n";
            $message .= 'Berikut ini adalah Kode Verifikasi Anda : *'.$user->token."*\n\n";
            $message .= "Kode ini hanya berlaku 10 menit. JANGAN BAGIKAN kode verifikasi ini kepada siapapun.\n\n";
            $message .= '```app.alkarimah.org```';

            Whatsapp::send($user->phone, $message);
        }

        if ($error !== false) {
            return Redirect::route('auth.forgot-password')->withErrors($error)->withInput();
        }

        return Redirect::route('auth.forgot-password.verification', $user->encrypted_id);
    }

    public function storeForgotPasswordVerification(AuthVerificationRequest $request, User $user)
    {
        $error = false;

        if ($user->token != $request->code) {
            $error = __('validation.captcha', ['attribute' => __('label.verification_code')]);
        }

        if ($error == false && strtotime($user->token_expired_at) < strtotime(date('Y-m-d H:i:s'))) {
            $error = __('string.verification_code_expired');

            $user->token = rand(111111, 999999);
            $user->token_expired_at = date('Y-m-d H:i:s', strtotime('+10 minute'));
            $user->save();

            $message = "*Pondok Pesantren*\n";
            $message .= "*Al-Karimah*\n\n";
            $message .= 'Berikut ini adalah Kode Verifikasi Anda : *'.$user->token."*\n\n";
            $message .= "Kode ini hanya berlaku 10 menit. JANGAN BAGIKAN kode verifikasi ini kepada siapapun.\n\n";
            $message .= '```app.alkarimah.org```';

            Whatsapp::send($user->phone, $message);
        }

        if ($error !== false) {
            return Redirect::route('auth.forgot-password.verification', $user->encrypted_id)->withErrors($error)->withInput();
        }

        return Redirect::route('auth.reset-password', Crypt::encrypt($user->token));
    }

    public function storeResetPassword(AuthPasswordRequest $request, User $user)
    {
        $user->update([
            'password' => $request->password,
            'token' => null,
            'token_expired_at' => null,
        ]);

        return Redirect::route('base')->with('success', __('message.reset_password_success'));
    }

    public function logout()
    {
        Auth::logout();

        return Redirect::route('base');
    }
}
