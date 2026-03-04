@extends('layouts.auth.index')

@section('content')
<p class="h4 fw-semibold mb-2">{{ __('label.whatsapp_verification') }}</p>
<div class="mb-3">
    Kami telah mengirim Kode Verifikasi ke Akun Whatsapp Anda dengan Nomor : <b>{{ Common::phoneFormat($parent->phone) }}</b>.
    Silahkan cek dan masukan Kode yang Anda terima pada form dibawah ini :
</div>

<form id="form-register" method="post" action="{{ route('auth.store.register.verification', $parent->encrypted_id) }}" class="form-block mb-4">
    @csrf

    @isset($errors->all()[0])
        <div class="alert alert-danger">
            <i class="fa-solid fa-times-circle"></i>&nbsp; {{ $errors->all()[0] }}
        </div>
    @endisset

    <x-form.input-text
        name="code"
        :placeholder="__('label.verification_code')"
    />

    <div class="d-grid mt-4">
        <button type="button" class="btn btn-lg btn-primary btn-submit w-100" data-loading="{{ strtoupper(__('label.verifying')) }}...">
            {{ strtoupper(__('label.verify')) }}
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
