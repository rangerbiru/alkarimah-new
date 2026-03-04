@extends('layouts.auth.index')

@section('content')
<p class="h4 fw-semibold mb-2">Ahlan Wa Sahlan</p>
<p class="mb-3 text-muted op-7 fw-normal">{{ __('string.please_login_first') }}</p>

<form id="form-login" method="post" action="{{ route('auth.authenticate') }}" class="form-block mb-4">
    @csrf

    <input id="username" style="display:none" type="text" name="fakeusernameremembered">
    <input id="password" style="display:none" type="password" name="fakepasswordremembered">

    <div class="row gy-3 mt-3">
        <div class="col-xl-12 mt-0">
            <label for="signin-username" class="form-label text-default">
                {{ __('label.email') . ' / ' . __('label.phone_number') }}
            </label>

            <x-form.input-text
                id="signin-username"
                name="username"
                class="form-control-lg"
                :old="old('username')"
            />
        </div>
        <div class="col-xl-12">
            <label for="signin-password" class="form-label text-default d-block">
                {{ __('label.password') }}
            </label>
            <div class="input-group mb-3">
                <x-form.input-text
                    type="password"
                    id="signin-password"
                    name="password"
                    class="form-control-lg"
                    autocomplete="new-password"
                />

                <button class="btn btn-light bg-transparent" type="button" onclick="createpassword('signin-password',this)" id="button-addon2"><i class="ri-eye-off-line align-middle"></i></button>
            </div>

            <div class="d-flex">
                <div>
                    <x-form.checkbox
                        id="remember"
                        name="remember"
                        :label="__('label.remember_me')"
                        value="1"
                        :old="old('remember')"
                        checked
                    />
                </div>
                <div class="ms-auto">
                    <a href="{{ route('auth.forgot-password') }}" class="text-primary">
                        {{ __('label.forgot_password') }}?
                    </a>
                </div>
            </div>
        </div>
        <div class="col-xl-12 d-grid">
            <button type="button" id="btn-login" class="btn btn-lg btn-primary btn-submit w-100" data-loading="PLEASE WAIT...">
                {{ __('label.login') }}
            </button>
        </div>
    </div>

    <div class="mt-4">
        Tidak punya akun?
        <a href="{{ route('auth.register') }}" class="text-primary fw-bold">
            Daftar Sekarang
        </a>
    </div>
</form>
@endsection

@push('scripts')
<script>
const error = "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"

$(document).ready(function(){
    if (error != "")
        setNotifInfo(error)

    $("#form-login input").keyup(function(){
        var keyboard = event.which || event.keyCode

        if(keyboard == 13)
            $("#btn-login").click()
    })
})
</script>
@endpush
