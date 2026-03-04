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
                    <p class="mb-0 fs-10 op-7 text-muted fw-semibold">{{ __('label.balance_topup') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-2">
            @if (empty($waiting))
                <div class="mb-1">
                    <b><i class='ti ti-receipt-filled text-primary'></i> &nbsp;{{ __('label.topup_balance') }}</b>
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
                <div class="d-flex align-items-center mb-2">
                    <div>{{ __('label.subtotal') }}</div>
                    <div class="ms-auto">
                        <div class="clearfix">
                            <div class="float-start">Rp.</div>
                            <div id="subtotal" class="text-end " style="width: 110px;">0</div>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <div>{{ __('label.unique_code') }}</div>
                    <div class="ms-auto">
                        <div class="clearfix">
                            <div class="float-start">Rp.</div>
                            <div id="unique-code" class="text-end" style="width: 110px;">0</div>
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
                    {!! __('string.you_have_payment_pending') !!}
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
                        <a href="{{ route('finance.balance.waiting', $waiting->encrypted_id) }}" class="btn btn-primary btn-sm btn-payment-detail">{{ __('label.view_payment_details') }}</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const code = parseInt("{{ $payment_code }}")
let method = "{{ $method->bsi }}"

$(document).ready(function() {
    getBalance()

    if ($(".nominal-mask").length)
        $(".nominal-mask").inputmask({ alias: "nominal" })

    $("#student").change(function() {
        student = $(this).val()
        getBalance()
    })

    $("#nominal").keyup(function() {
        setTotal()
    })

    $("#form").on("click", ".form-check-method", function() {
        method = $(this).val()
    })

    $("#btn-process").click(function() {
        const btn = $(this)
        const formData = {
            nominal: $("#nominal").val(),
            payment_method: method,
            unique_code: code
        }

        btn.addClass("btn-loader").html(`<span class="loading"><i class="ri-refresh-line fs-16"></i></span> &nbsp;&nbsp;{{ __('label.processing') }}`).attr("disabled", true)

        $.ajax({
            type: "POST",
            url: "{{ route('finance.balance.store') }}",
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
                ajaxError(xhr.status)
            }
        })
    })
})

function getBalance()
{
    $("#loading").show()
    $("#form").hide()

    $.ajax({
        type: "POST",
        url: "{{ route('finance.balance.get') }}",
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
    let subtotal = $("#nominal").val()
    subtotal = (subtotal == "") ? 0 : parseFloat(subtotal.replace(/\./g, ""))

    const total = subtotal + code

    $("#subtotal").html(moneyFormat(subtotal))
    $("#unique-code").html(moneyFormat(code))
    $("#total").html(moneyFormat(total))
}
</script>
@endpush
