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
    <div class="card-body p-3">
        <div class="d-flex">
            <div>
                <small class="fw-bold text-grey">{{ __('label.transaction_number') }} : {{ $history->transaction->number }}</small>
                <h3 class="text-success mb-0 mt-1">Rp. {{ number_format($history->transaction->total, 0, '', '.') }}</h3>
            </div>
            <div class="ms-auto">
                <img src="{{ ($history->debit > 0) ? $history->transaction->method->image_payment : asset('images/icons/min.png') }}" class="img-thumbnail" style="height: 47px;" />
            </div>
        </div>

        <hr class="mt-2" />

        <div class="d-flex mb-2">
            <div>{{ __('label.status') }}</div>
            <div class="ms-auto">{!! $history->transaction->status_badge !!}</div>
        </div>
        <div class="d-flex mb-2">
            <div>{{ __('label.payment_method') }}</div>
            <div class="ms-auto text-grey">{{ $history->transaction->method->name }}</div>
        </div>
        <div class="d-flex mb-4">
            <div>{{ __('label.payment_date') }}</div>
            <div class="ms-auto text-grey">{{ Common::dateFormat($history->transaction->paid_at, 'dd mmm yyyy, hh:ii WIB') }}</div>
        </div>

        <div class="d-flex mb-2">
            <div><b><i class="fa-solid fa-clipboard-list text-grey"></i> &nbsp;{{ __('label.transaction_detail') }}</b></div>
            <div class="ms-auto"><b>Rp</b></div>
        </div>
        <div class="d-flex mb-2">
            <div>{{ __('label.topup_balance_nominal') }}</div>
            <div class="ms-auto">{{ number_format($history->debit, 0, '', '.') }}</div>
        </div>
        <div class="d-flex mb-2">
            <div class="fw-bold">{{ __('label.unique_code') }}</div>
            <div class="ms-auto fw-bold text-grey">{{ number_format($history->transaction->unique_code, 0, '', '.') }}</div>
        </div>
        <div class="d-flex mb-2">
            <div class="fw-bold">{{ __('label.total') }}</div>
            <div class="ms-auto fw-bold text-primary">{{ number_format($history->transaction->total, 0, '', '.') }}</div>
        </div>
        <hr />
        <div class="d-flex mb-2">
            <div class="fw-bold">{{ __('label.balance_topup') }}</div>
            <div class="ms-auto fw-bold text-success">{{ number_format($history->balance, 0, '', '.') }}</div>
        </div>
    </div>
</div>
@endsection
