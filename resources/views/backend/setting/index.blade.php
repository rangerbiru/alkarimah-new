@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="setting"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('setting.update', $setting->encrypted_id) }}" class="form-block">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-sm-5 col-md-4">
                    {{ __('label.savings_withdrawal_limit') }}<br />
                    <small class="text-muted">Batas maksimal pengambilan tabungan per minggu</small>
                </div>
                <div class="col-sm-3 col-md-2" id="form-savings-withdrawal">
                    <div class="d-none d-sm-block mt-1"></div>
                    <x-form.radio
                        name="savings_withdrawal_limit"
                        :option="$on_off"
                        :old="old('savings_withdrawal_limit', $setting->savings_withdrawal_limit)"
                    />
                </div>
                <div class="col-sm-4 col-md-4" id="form-savings-withdrawal-max">
                    <div class="d-none d-sm-block mt-1"></div>
                    <x-form.input-group-mask
                        name="savings_withdrawal_limit_max"
                        mask="nominal"
                        addon="Rp"
                        :old="old('savings_withdrawal_limit_max', $setting->savings_withdrawal_limit_max)"
                    />
                </div>
            </div>
            <hr />

            <x-form.button-submit />
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const error = "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"
const savings_withdrawal_limit = "{{ ($setting->savings_withdrawal_limit) ? '1' : '0' }}"

$(document).ready(function() {
    if (error != "")
        setNotifInfo(error)

    if (savings_withdrawal_limit == "0")
        $("#form-savings-withdrawal-max").hide()

    $(".nominal-mask").inputmask({alias: "nominal"})

    $("#form-savings-withdrawal input").click(function() {
        if ($(this).val() == 0) {
            $("#form-savings-withdrawal-max").hide()
            $("#form-savings-withdrawal-max input").val("0")
        } else
            $("#form-savings-withdrawal-max").show()
    })
})
</script>
@endpush
