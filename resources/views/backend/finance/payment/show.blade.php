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
                <small class="fw-bold text-grey">{{ __('label.transaction_number') }} : {{ $transaction->number }}</small>
                <h3 class="text-success mb-0 mt-1">Rp. {{ number_format($transaction->total, 0, '', '.') }}</h3>
            </div>
            <div class="ms-auto">
                <img src="{{ $transaction->method->image_payment }}" class="img-thumbnail" style="height: 47px;" />
            </div>
        </div>

        <hr class="mt-2" />

        <div class="d-flex mb-2">
            <div>{{ __('label.status') }}</div>
            <div class="ms-auto">{!! $transaction->status_badge !!}</div>
        </div>
        <div class="d-flex mb-2">
            <div>{{ __('label.payment_method') }}</div>
            <div class="ms-auto text-grey">{{ $transaction->method->name }}</div>
        </div>
        <div class="d-flex mb-2">
            <div>{{ __('label.payment_date') }}</div>
            <div class="ms-auto text-grey">{{ Common::dateFormat($transaction->paid_at, 'dd mmm yyyy, hh:ii WIB') }}</div>
        </div>
        <div class="d-flex mb-2">
            <div>{{ __('label.student_name') }}</div>
            <div class="ms-auto text-grey">{{ $transaction->student->name }}</div>
        </div>
        <div class="d-flex mb-4">
            <div>{{ __('label.scholarship') }}</div>
            <div class="ms-auto text-grey">{{ $transaction->get_scholarship }}</div>
        </div>

        <div class="d-flex mb-2">
            <div><b><i class="fa-solid fa-clipboard-list text-grey"></i> &nbsp;{{ __('label.bill_detail') }}</b></div>
            <div class="ms-auto"><b>Rp</b></div>
        </div>

        @foreach ($transaction->bills_detail as $b)
            <div class="bill d-flex border-bottom pb-2 mb-2">
                <div>{{ $b->name }}</div>
                <div class="ms-auto text-grey">
                    {{ number_format($b->total, 0, '', '.') }}
                </div>
            </div>
        @endforeach

        <div class="d-flex mb-2">
            <div class="fw-bold">{{ __('label.subtotal') }}</div>
            <div class="ms-auto fw-bold text-grey">{{ number_format($transaction->subtotal, 0, '', '.') }}</div>
        </div>

        @if ($transaction->discount > 0)
            <div class="d-flex mb-2">
                <div>{{ __('label.discount') }}</div>
                <div class="ms-auto text-grey">{{ number_format($transaction->discount, 0, '', '.') }}</div>
            </div>
        @endif

        @if ($transaction->donation > 0)
            <div class="d-flex mb-2">
                <div class="fw-bold">{{ __('label.scholarship_total') }}</div>
                <div class="ms-auto fw-bold text-grey">{{ number_format($transaction->donation, 0, '', '.') }}</div>
            </div>
        @endif

        @if ($transaction->unique_code > 0)
            <div class="d-flex mb-2">
                <div class="fw-bold">{{ __('label.unique_code') }}</div>
                <div class="ms-auto fw-bold text-grey">{{ number_format($transaction->unique_code, 0, '', '.') }}</div>
            </div>
        @endif

        <div class="d-flex mb-2">
            <div class="fw-bold">{{ __('label.total') }}</div>
            <div class="ms-auto fw-bold text-primary">{{ number_format($transaction->total, 0, '', '.') }}</div>
        </div>
    </div>
</div>
@endsection
