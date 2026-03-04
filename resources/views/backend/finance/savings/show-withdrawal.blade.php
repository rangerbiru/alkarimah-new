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
                <small class="fw-bold text-grey">{{ __('label.withdrawal_number') }} : {{ $withdrawal->number }}</small>
                <h3 class="text-success mb-0 mt-1">Rp. {{ number_format($withdrawal->total, 0, '', '.') }}</h3>
            </div>
            <div class="ms-auto">
                <img src="{{ asset('images/icons/savings.png') }}" class="img-thumbnail" style="height: 47px;" />
            </div>
        </div>

        <hr class="mt-2" />

        <div class="d-flex mb-2">
            <div>{{ __('label.status') }}</div>
            <div class="ms-auto">{!! $withdrawal->status_badge !!}</div>
        </div>
        <div class="d-flex mb-2">
            <div>{{ __('label.penanggung_jawab_tabungan') }}</div>
            <div class="ms-auto text-grey">{{ $withdrawal->creator->name }}</div>
        </div>
        <div class="d-flex mb-2">
            @if ($withdrawal->is_processed)
                <div>{{ __('label.payment_date') }}</div>
                <div class="ms-auto text-grey">{{ Common::dateFormat($withdrawal->processed_at, 'dd mmm yyyy, hh:ii WIB') }}</div>
            @else
                <div>{{ __('label.request_date') }}</div>
                <div class="ms-auto text-grey">{{ Common::dateFormat($withdrawal->created_at, 'dd mmm yyyy, hh:ii WIB') }}</div>
            @endif
        </div>
        <div class="d-flex mb-4">
            <div>{{ __('label.student_name') }}</div>
            <div class="ms-auto text-grey">{{ $withdrawal->student->name }}</div>
        </div>

        <hr />
        <div class="d-flex mb-2">
            <div class="fw-bold">{{ __('label.savings_balance') }}</div>
            <div class="ms-auto fw-bold text-success">{{ number_format($withdrawal->student->balance_savings, 0, '', '.') }}</div>
        </div>
    </div>
</div>
@endsection
