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

<form id="form">
    <div class="row gx-1">
        <div class="col-6">
            <div class="card custom-card mb-3">
                <div class="card-body p-2">
                    <div class="d-flex align-items-center">
                        <div class="me-2">
                            <span class="avatar avatar-md bg-success-transparent">
                                <i class="ti ti-clipboard-check"></i>
                            </span>
                        </div>
                        <div class="flex-fill">
                            <div class="d-flex mb-1 align-items-top justify-content-between">
                                <div id="count-paid" class="fw-semibold mb-0 lh-1">Rp. 0</div>
                            </div>
                            <p class="mb-0 fs-10 op-7 text-muted fw-semibold">Tagihan Terbayar</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card custom-card mb-3">
                <div class="card-body p-2">
                    <div class="d-flex align-items-center">
                        <div class="me-2">
                            <span class="avatar avatar-md p-2 bg-danger-transparent">
                                <i class="ti ti-clipboard-text"></i>
                            </span>
                        </div>
                        <div class="flex-fill">
                            <div class="d-flex mb-1 align-items-top justify-content-between">
                                <div id="count-not-paid" class="fw-semibold mb-0 lh-1">Rp. 0</div>
                            </div>
                            <p class="mb-0 fs-10 op-7 text-muted fw-semibold">Sisa Tagihan</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body p-2">
            <div class="mb-2">
                <b>Tagihan Bulan Ini</b>
            </div>

            <div id="bill"></div>

            <div class="text-center">
                <a href="javascript:void(0)" id="btn-show-bill">
                    <small>Semua Tagihan</small><br />
                    <i class="ti ti-chevron-down"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-2">
            <div id="payment-info" class="text-center text-muted my-3">
                <i class="ti ti-info-circle" style="font-size: 25px"></i>
                <div class="mt-1">
                    {{ __('string.payment_bill_check') }}
                </div>
            </div>

            <div id="payment-pending">
                <div class="alert alert-info text-center">
                    {!! __('string.you_have_payment_pending') !!}
                </div>

                <div class="p-1">
                    <div class="mb-1">
                        <b>{{ __('label.payment_pending') }}</b>
                    </div>
                    <div class="d-flex">
                        <div>
                            <span class="fw-bold text-grey">{{ __('label.transaction_number') }} : <span class="number"></span></span>
                            <h3 class="text-success mb-0 mt-1">Rp. <span class="total"></span></h3>
                        </div>
                        <div class="ms-auto">
                            <img src="{{ asset('images/payment/bsi.png') }}" class="img-thumbnail" style="height: 50px;" />
                        </div>
                    </div>

                    <div class="d-grid mt-2">
                        <a href="" class="btn btn-primary btn-sm btn-payment-detail">{{ __('label.view_payment_details') }}</a>
                    </div>
                </div>
            </div>

            <div id="payment-form">
                <div class="border-bottom" style="margin-bottom: 12px;">
                    <div class="mb-1">
                        <b><i class='bx bxs-credit-card-front text-primary'></i> &nbsp;{{ __('label.payment_method') }}</b>
                    </div>

                    <div id="method-balance" class="d-flex align-items-top mb-2">
                        <div class="me-2">
                            <div class="form-check form-check-md">
                                <input type="radio" name="method" value="{{ $method->balance }}" class="form-check-input form-check-method rb-method-balance" checked />
                            </div>
                        </div>
                        <div class="label">
                            {{ __('label.balance_topup') }}
                        </div>
                        <div class="ms-auto">
                            <div class="clearfix">
                                <div class="float-start">Rp.</div>
                                <div class="text-end" style="width: 110px;">
                                    {{ number_format($user->parent->balance, 0, '', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mb-2">
                        <div class="me-2">
                            <div class="form-check form-check-md">
                                <input type="radio" name="method" value="{{ $method->bsi }}" class="form-check-input form-check-method rb-method-bsi"{{ ($user->parent->balance == 0) ? ' checked' : '' }} />
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
                </div>

                <div class="mb-1">
                    <b><i class='ti ti-receipt-filled text-primary'></i> &nbsp;{{ __('label.bill_detail') }}</b>
                </div>

                <div class="bill-subtotal">
                    <div class="d-flex align-items-center mb-2">
                        <div>{{ __('label.balance_topup') }}</div>
                        <div class="ms-auto">
                            <div class="clearfix">
                                <div class="float-start">Rp.</div>
                                <div class="text-end rp" style="width: 110px;">0</div>
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
                                <div class="text-end rp" style="width: 110px;">0</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-4 bill-total">
                    <div class="fw-bold">{{ __('label.payment_total') }}</div>
                    <div class="ms-auto fw-bold">
                        <div class="clearfix">
                            <div class="float-start">Rp.</div>
                            <div class="text-end rp" style="width: 110px;">0</div>
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="button" id="btn-pay" class="btn btn-primary">
                        {{ __('label.pay') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
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
const student_count = parseInt("{{ $student_count }}")

let bills = {}
let bills_selected = {}
let student = parseInt("{{ $student_first }}")
let bill_hide = true
let bill_checked = 0
let method = ""
let method_balance = "{{ $method->balance }}"
let balance = parseFloat("{{ $user->parent->balance }}")
let subtotal = 0
let form_show = false

$(document).ready(function() {
    getBill()

    $("#student").change(function() {
        student = $(this).val()
        getBill()
    })

    $("#btn-show-bill").click(function() {
        if (bill_hide) {
            bill_hide = false

            $(this).html('<i class="ti ti-chevron-up"></i>')
            $("#bill-hide").slideDown()
        } else {
            bill_hide = true

            $(this).html('<small>Semua Tagihan</small><br /><i class="ti ti-chevron-down"></i>')
            $("#bill-hide").slideUp()
        }
    })

    $("#form").on("click", ".form-check-bill", function() {
        const id = $(this).val()

        if ($(this).is(":checked")) {
            bill_checked++
            subtotal += bills[$(this).val()]
            bills_selected[id] = bills[id]
        } else {
            bill_checked--
            subtotal -= bills[$(this).val()]
            delete bills_selected[id]
        }

        initialize()
        setTotal()
    })

    $("#form").on("click", ".form-check-method", function() {
        method = $(this).val()
        setTotal()
    })

    $("#btn-pay").click(function() {
        const btn = $(this)

        if (method == method_balance) {
            Swal.fire({
                icon: "warning",
                title: label_confirmation,
                text: "{{ __('string.confirm_pay_with_balance') }}",
                showCancelButton: true,
                confirmButtonText: label_yes,
                cancelButtonText: label_cancel,
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    pay(btn)

                    return true
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.close()
                }
            })
        } else
            pay(btn)
    })
})

function getBill()
{
    const formData = {student}

    bills = {}
    bill_checked = 0
    bill_hide = true
    subtotal = 0
    method = ""

    $("#loading").show()
    $("#form, #payment-pending").hide()

    $.ajax({
        type: "POST",
        url: "{{ route('finance.payment.get.bills') }}",
        data: formData,
        dataType: "json",
        success: function (response) {
            $("#count-paid").html(`Rp. ${moneyFormat(response.data.count.paid)}`)
            $("#count-not-paid").html(`Rp. ${moneyFormat(response.data.count.not_paid)}`)
            
            if (response.data.pending) {
                $("#payment-pending .number").html(response.data.number)
                $("#payment-pending .total").html(response.data.total)
                $("#payment-pending img").attr("src", response.data.image)
                $("#payment-pending .btn-payment-detail").attr("href", response.data.url)
                $("#payment-info, #payment-form, #bill-hide, #loading").hide()
                $("#form, #payment-pending").show()
            } else {
                bills = response.data.bills_list

                $("#bill").html(response.data.bills)

                initialize()
                setTotal()

                $("#bill-hide, #loading").hide()
                $("#form").show()
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            $("#loading").hide()
            ajaxError(xhr.status)
        }
    })
}

function initialize()
{
    if (bill_checked == 0) {
        form_show = false

        $("#payment-form").hide()
        $("#payment-info").show()
    } else {
        if (form_show == false) {
            form_show = true

            $("#payment-info").hide()
            $("#payment-form").show()
        }
    }

    if (balance < subtotal) {
        method = "{{ $method->bsi }}"

        $(".rb-method-bsi").prop("checked", true)
        $(".rb-method-balance").attr("disabled", true)
        $("#method-balance").addClass("text-muted")
        $("#method-balance .label").html('Saldo TopUp<br /><span class="text-danger" style="font-size: 10px;">Saldo Anda tidak mencukupi</span>')
    } else {
        method = "{{ $method->balance }}"
        $(".rb-method-balance").removeAttr("disabled").prop("checked", true)
        $("#method-balance").removeClass("text-muted")
        $("#method-balance .label").html('Saldo TopUp')
    }
}

function setTotal()
{
    if (method == method_balance) {
        $(".bill-subtotal, .bill-code").hide()
        $(".bill-total .rp").html(moneyFormat(subtotal))
    } else {
        const total = subtotal + code

        $(".bill-subtotal, .bill-code").show()
        $(".bill-subtotal .rp").html(moneyFormat(subtotal))
        $(".bill-code .rp").html(moneyFormat(code))
        $(".bill-total .rp").html(moneyFormat(total))
    }
}

function pay(btn)
{
    const formData = {
        id_student: student,
        bills: bills_selected,
        payment_method: method,
        unique_code: code
    }

    btn.addClass("btn-loader").html(`<span class="loading"><i class="ri-refresh-line fs-16"></i></span> &nbsp;&nbsp;{{ __('label.processing') }}`).attr("disabled", true)

    $.ajax({
        type: "POST",
        url: "{{ route('finance.payment.store') }}",
        data: formData,
        dataType: "json",
        success: function (response) {
            btn.html("{{ __('label.pay') }}").removeClass("btn-loader").removeAttr("disabled")

            if (response.status)
                window.location = response.data.redirect
            else
                setNotifInfo(response.message)
        },
        error: function (xhr, ajaxOptions, thrownError) {
            btn.html("{{ __('label.pay') }}").removeClass("btn-loader").removeAttr("disabled")
            ajaxError(xhr.status)
        }
    })
}
</script>
@endpush
