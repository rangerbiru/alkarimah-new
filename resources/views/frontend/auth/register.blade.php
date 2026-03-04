@extends('layouts.auth.index')

@section('content')
<p class="h4 fw-semibold mb-2">{{ __('label.account_registration') }}</p>
<p class="mb-3 text-muted op-7 fw-normal">{{ __('string.please_fill_your_phone_number') }}</p>

<form id="form-register" method="post" action="{{ route('auth.store.register') }}" class="form-block mb-4">
    @csrf

    @isset($errors->all()[0])
        <div class="alert alert-danger">
            <i class="fa-solid fa-times-circle"></i>&nbsp; {{ $errors->all()[0] }}
        </div>
    @endisset

    <x-form.input-group-mask
        name="phone"
        mask="handphone"
        addon="<i class='fa-solid fa-mobile-screen'></i>"
        :old="old('phone')"
    />

    <div class="mt-3">
        <label>{{ __('label.captcha') }}</label>
        <div class="captcha" style="margin-top: 5px;margin-bottom: 5px;margin-right: 5px;">
            <span></span>
            <a href="javascript:void(0)" class="reload white captcha-refresh">
                <i class="fas fa-sync-alt"></i>
            </a>
        </div>

        <x-form.input-text
            name="captcha"
            class="form-auth captcha"
            :placeholder="__('string.enter_code_above')"
        />

        <div class="d-grid mt-4">
            <button type="button" class="btn btn-lg btn-primary btn-submit w-100" data-loading="{{ strtoupper(__('label.registering')) }}...">
                {{ strtoupper(__('label.register')) }}
            </button>
        </div>
    </div>

    <div class="mt-4">
        Sudah punya akun?
        <a href="{{ route('base') }}" class="text-primary fw-bold">
            Login Sekarang
        </a>
    </div>
</form>
@endsection

@push('scripts')
<script>
const captcha_img = '{!! captcha_img() !!}'

$(document).ready(function(){
    $(".handphone-mask").inputmask({ alias: "handphone" })
    $(".captcha span").html(captcha_img)

    $(".captcha-refresh").click(function () {
        $.ajax({
            type: "GET",
            url: "{{ route('captcha.refresh') }}",
            success: function (data) {
                $(".captcha span").html(data.captcha)
            }
        });
    })

    $("#form-register input").keyup(function(){
        var keyboard = event.which || event.keyCode

        if(keyboard == 13)
            $("#form-register button").click()
    })
})
</script>
@endpush
