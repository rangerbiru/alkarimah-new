@extends('layouts.auth.index')

@section('content')
<p class="h4 fw-semibold mb-2">{{ __('label.reset_password') }}</p>
<p class="mb-3 text-muted op-7 fw-normal">{{ __('string.please_fill_your_password') }}</p>

<form id="form-register" method="post" action="{{ route('auth.store.reset-password', $user->encrypted_id) }}" class="form-block mb-4">
    @csrf

    @isset($errors->all()[0])
        <div class="alert alert-danger">
            <i class="fa-solid fa-times-circle"></i>&nbsp; {{ $errors->all()[0] }}
        </div>
    @endisset

    <x-form.input-group
        type="password"
        name="password"
        :label="__('label.password')"
        addon="<i class='fa-solid fa-lock'></i>"
        :info="__('string.minimal_8_character')"
        autocomplete="new-password"
    />
    <x-form.input-group
        type="password"
        name="password_confirm"
        :label="__('label.confirm_password')"
        addon="<i class='fa-solid fa-lock'></i>"
    />

    <div class="d-grid mt-4">
        <button type="button" class="btn btn-lg btn-primary btn-submit w-100" data-loading="PLEASE WAIT...">
            {{ strtoupper(__('label.submit')) }}
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function(){
    $("#form-register input").keyup(function(){
        var keyboard = event.which || event.keyCode

        if(keyboard == 13)
            $("#form-register button").click()
    })
})
</script>
@endpush
