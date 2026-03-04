@extends('layouts.mobile.index')

@section('title', $title)
@section('header')
<x-section-page-mobile
    :label="$title"
    :icon="$icon"
/>
@endsection

{{--
This file is used for :
- Waiting Payment Tagihan (app/Http/Controllers/Finance/PaymentController.php)
- Waiting Payment Setor Tabungan (app/Http/Controllers/Finance/SavingsController.php)
- Waiting Payment Topup Saldo (app/Http/Controllers/Finance/BalanceController.php)
--}}

@section('content')
<div class="card card-tab mb-3">
    <div class="card-body p-0">
        <div class="bg-light p-3">
            {!! __('string.please_make_payment', ['date' => Common::dateFormat($transaction->expired_view_at, 'dd mmm yyyy Pukul hh:ii WIB')]) !!} :
        </div>

        <div class="p-3">
            <div class="d-flex">
                <div>
                    <small class="fw-bold text-grey">{{ __('label.transaction_number') }} : {{ $transaction->number }}</small>
                    <h3 class="text-success mb-0 mt-1">Rp. {{ number_format($transaction->total, 0, '', '.') }}</h3>
                </div>
                <div class="ms-auto">
                    <img src="{{ $transaction->method->image_payment }}" class="img-thumbnail" style="height: 47px;" />
                </div>
            </div>

            <hr class="mt-2" />

            <div class="mb-3">
                <small>{{ __('string.please_transfer_to_rekening') }}</small><br />
                <h5 class="text-grey">
                    {{ $transaction->bank_account->number }}
                    <a href="javascript:void(0)" id="btn-copy" class="text-grey" data-clipboard-text="{{ $transaction->bank_account->number }}"><i class="ti ti-copy"></i></a>
                </h5>
            </div>
            <div class="mb-3">
                <small>{{ __('label.on_behalf_of') }}</small><br />
                <h6 class="text-grey">
                    {{ $transaction->bank_account->name }}
                </h6>
            </div>
            <div class="mb-4 alert alert-info">
                {!! __('string.make_sure_transfer_3_digit') !!}
            </div>

            <div class="d-grid mb-3">
                <button type="button" id="btn-check" class="btn btn-primary">
                    <i class="fa-solid fa-sync"></i> &nbsp;{{ __('label.check_payment_status') }}
                </button>
            </div>
            <div class="text-center">
                <a href="javascript:void(0)" id="btn-cancel" class="text-danger">
                    {{ __('label.cancel_this_payment') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card card-tab mb-3">
    <div class="card-body p-3">
        <div class="d-flex mb-2">
            <div class="fw-bold"><i class="ti ti-receipt-filled text-primary"></i> {{ __('label.transaction_detail') }}</div>
            <div class="ms-auto fw-bold">Rp.</div>
        </div>

        @foreach ($bills_detail as $b)
            <div class="bill d-flex border-bottom pb-2 mb-2">
                <div>{{ $b->name }}</div>
                <div class="ms-auto">
                    {{ number_format($b->total, 0, '', '.') }}
                </div>
            </div>
        @endforeach

        <div class="d-flex border-bottom pb-2 mb-2">
            <div class="fw-bold">{{ __('label.subtotal') }}</div>
            <div class="ms-auto fw-bold">
                {{ number_format($transaction->subtotal, 0, '', '.') }}
            </div>
        </div>

        <div class="d-flex border-bottom pb-2 mb-2">
            <div class="fw-bold">{{ __('label.unique_code') }}</div>
            <div class="ms-auto fw-bold">
                {{ number_format($transaction->unique_code, 0, '', '.') }}
            </div>
        </div>
        <div class="d-flex border-bottom pb-2 mb-2">
            <div class="fw-bold">{{ __('label.total') }}</div>
            <div class="ms-auto fw-bold text-primary">
                {{ number_format($transaction->total, 0, '', '.') }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/clipboard.min.js') }}"></script>

<script>
const clipboard = new ClipboardJS("#btn-copy")

$(document).ready(function() {
    clipboard.on("success", function(e) {
        $(e.trigger).html("<i class='ti ti-copy'></i> <small class='fw-normal' style='font-size: 13px;'>Copied</small>")

        setTimeout(function(){
            $(e.trigger).html("<i class='ti ti-copy'></i>")
        }, 1000)
    })

    $("#btn-check").click(function() {
        const btn = $(this)
        btn.addClass("btn-loader").html(`<span class="loading"><i class="ri-refresh-line fs-16"></i></span> &nbsp;&nbsp;{{ __('label.checking') }}`).attr("disabled", true)

        $.ajax({
            type: "POST",
            url: "{{ route('finance.payment.check', $transaction->encrypted_id) }}",
            dataType: "json",
            success: function (response) {
                btn.removeClass("btn-loader").html("<i class='fa-solid fa-sync'></i> &nbsp;{{ __('label.check_payment_status') }}").removeAttr("disabled")

                if (response.status)
                    setNotifSuccess(response.message, response.data.redirect)
                else
                    setNotifInfo(response.message)
            },
            error: function (xhr, ajaxOptions, thrownError) {
                ajaxError(xhr.status)
            }
        })
    })

    $("#btn-cancel").click(function() {
        Swal.fire({
            icon: "warning",
            title: label_confirmation,
            text: "{{ __('string.confirm_cancel_payment') }}",
            showCancelButton: true,
            confirmButtonText: label_yes,
            cancelButtonText: label_cancel,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('finance.payment.destroy', $transaction->encrypted_id) }}",
                    dataType: "json",
                    success: function (response) {
                        setNotifSuccess(response.message, response.data.redirect)
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        ajaxError(xhr.status)
                    }
                })

                return true
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.close()
            }
        })
    })
})
</script>
@endpush
