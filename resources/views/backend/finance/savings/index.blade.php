@extends('layouts.mobile.index')

@section('title', $title)
@section('header')
<x-section-page-mobile
    :label="$title"
    :icon="$icon"
/>
@endsection

@section('content')
<div class="card card-tab mb-3">
    <div class="card-body p-2">
        @include($path . 'menu')

        @if ($student_count > 1)
            <div class="form-student mt-4 mb-1">
                <x-form.select
                    id="student"
                    :option="$students"
                    :old="$student_first"
                    :data-placeholder="__('label.choose_student')"
                />

                <div style="background: white;padding: 0 5px;position: absolute;margin-left: 10px;margin-top: -46px;">
                    <small class="text-muted">{{ __('label.choose_student') }}</small>
                </div>
            </div>
        @endif
    </div>
</div>

<div id="loading" class="text-center">
    <img src="{{ asset('images/loader.gif') }}" style="width: 50px" />
</div>

<div id="form">
    <div class="card custom-card mb-3">
        <div class="card-body p-2">
            <div class="d-flex align-items-center">
                <div class="me-2">
                    <span class="avatar avatar-md bg-info-transparent">
                        <i class="ti ti-wallet"></i>
                    </span>
                </div>
                <div class="flex-fill">
                    <div class="d-flex mb-1 align-items-top justify-content-between">
                        <div id="balance" class="fw-semibold mb-0 lh-1">Rp. 0</div>
                    </div>
                    <p class="mb-0 fs-10 op-7 text-muted fw-semibold">{{ __('label.savings_balance') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-2">
            @if (empty($waiting))
                <div class="mb-1">
                    <b><i class='ti ti-receipt-filled text-primary'></i> &nbsp;{{ __('label.topup_savings') }}</b>
                </div>

                <x-form.input-group-mask
                    id="nominal"
                    mask="nominal"
                    addon="Rp"
                    :placeholder="__('label.input_nominal')"
                />

                <div class="mt-3 mb-2">{{ __('label.deposit_method') }}</div>

                <div class="d-flex align-items-center mb-2">
                    <div class="me-2">
                        <div class="form-check form-check-md">
                            <input type="radio" name="method" value="{{ $method->bsi }}" class="form-check-input form-check-method rb-method-bsi" checked />
                        </div>
                    </div>
                    <div>
                        {{ __('label.bank_bsi') }}
                    </div>
                    <div class="ms-auto">
                        <img src="{{ asset('images/payments/bsi.png') }}" class="img-thumbnail" style="height: 30px;" />
                    </div>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <div class="me-2">
                        <div class="form-check form-check-md">
                            <input type="radio" name="method" value="{{ $method->bni }}" class="form-check-input form-check-method" />
                        </div>
                    </div>
                    <div>
                        {{ __('label.bank_bni') }}
                    </div>
                    <div class="ms-auto">
                        <img src="{{ asset('images/payments/bni.png') }}" class="img-thumbnail" style="height: 30px;" />
                    </div>
                </div>

                <hr />
                <div class="bill-subtotal">
                    <div class="d-flex align-items-center mb-2">
                        <div>{{ __('label.subtotal') }}</div>
                        <div class="ms-auto">
                            <div class="clearfix">
                                <div class="float-start">Rp.</div>
                                <div id="subtotal" class="text-end " style="width: 110px;">0</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bill-code">
                    <div class="d-flex align-items-center mb-2">
                        <div>{{ __('label.unique_code') }}</div>
                        <div class="ms-auto">
                            <div class="clearfix">
                                <div class="float-start">Rp.</div>
                                <div id="unique-code" class="text-end" style="width: 110px;">0</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-4">
                    <div class="fw-bold">{{ __('label.payment_total') }}</div>
                    <div class="ms-auto fw-bold">
                        <div class="clearfix">
                            <div class="float-start">Rp.</div>
                            <div id="total" class="text-end " style="width: 110px;">0</div>
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-3">
                    <button type="button" id="btn-process" class="btn btn-primary">
                        {{ __('label.process') }}
                    </button>
                </div>
            @else
                <div class="alert alert-info text-center">
                    {!! __('string.you_have_savings_deposit_pending') !!}
                </div>

                <div class="p-1">
                    <div class="mb-1">
                        <b>{{ __('label.payment_pending') }}</b>
                    </div>
                    <div class="d-flex">
                        <div>
                            <span class="fw-bold text-grey">{{ __('label.transaction_number') }} : {{ $waiting->number }}</span>
                            <h3 class="text-success mb-0 mt-1">Rp. {{ number_format($waiting->total, 0, '', '.') }}</h3>
                        </div>
                        <div class="ms-auto">
                            <img src="{{ $waiting->method->image_payment }}" class="img-thumbnail" style="height: 50px;" />
                        </div>
                    </div>

                    <div class="d-grid mt-2">
                        <a href="{{ route('finance.savings.waiting', $waiting->encrypted_id) }}" class="btn btn-primary btn-sm btn-payment-detail">{{ __('label.view_payment_details') }}</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .form-student .select2-container .select2-selection--single .select2-selection__rendered {
        padding-left: 13px;
        padding-top: 2px;
    }
    .form-student .select2-container--default .select2-selection--single .select2-selection__arrow {
        top: 3px;
    }
</style>
@endpush

@push('scripts')
<script>
const code = parseInt("{{ $payment_code }}")

let student = parseInt("{{ $student_first }}")
let method = "{{ $method->bsi }}"

$(document).ready(function() {
    getSavings()

    $(".bill-code").hide()

    if ($(".nominal-mask").length)
        $(".nominal-mask").inputmask({ alias: "nominal" })

    $("#student").change(function() {
        student = $(this).val()
        getSavings()
    })

    $("#nominal").keyup(function() {
        let subtotal = $("#nominal").val()
        subtotal = (subtotal == "") ? 0 : parseFloat(subtotal.replace(/\./g, ""))

        setTotal()
    })

    $("#form").on("click", ".form-check-method", function() {
        method = $(this).val()
        setTotal()
    })

    $("#btn-process").click(function() {
        const btn = $(this)
        const formData = {
            id_student: student,
            nominal: $("#nominal").val(),
            payment_method: method,
            unique_code: code
        }

        btn.addClass("btn-loader").html(`<span class="loading"><i class="ri-refresh-line fs-16"></i></span> &nbsp;&nbsp;{{ __('label.processing') }}`).attr("disabled", true)

        $.ajax({
            type: "POST",
            url: "{{ route('finance.savings.store') }}",
            data: formData,
            dataType: "json",
            success: function (response) {
                btn.html("{{ __('label.process') }}").removeClass("btn-loader").removeAttr("disabled")

                if (response.status)
                    window.location = response.data.redirect
                else
                    setNotifInfo(response.message)
            },
            error: function (xhr, ajaxOptions, thrownError) {
                btn.html("{{ __('label.process') }}").removeClass("btn-loader").removeAttr("disabled")
                ajaxLaravelError(xhr)
            }
        })
    })
})

function getSavings()
{
    const formData = {student}

    $("#loading").show()
    $("#form").hide()

    $.ajax({
        type: "POST",
        url: "{{ route('finance.savings.get') }}",
        data: formData,
        dataType: "json",
        success: function (response) {
            $("#balance").html(`Rp. ${moneyFormat(response.data.balance)}`)
            $("#loading").hide()
            $("#form").show()
        },
        error: function (xhr, ajaxOptions, thrownError) {
            $("#loading").hide()
            ajaxError(xhr.status)
        }
    })
}

function setTotal()
{
    let total = 0
    let subtotal = $("#nominal").val()

    subtotal = (subtotal == "") ? 0 : parseFloat(subtotal.replace(/\./g, ""))
    total = subtotal + code

    $("#subtotal").html(moneyFormat(subtotal))
    $("#unique-code").html(moneyFormat(code))
    $(".bill-subtotal, .bill-code").show()

    $("#total").html(moneyFormat(total))
}
</script>
@endpush
