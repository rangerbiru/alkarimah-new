@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/transaction/unique-code/create"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form id="form" method="post" action="{{ route('finance.transaction.store.verify-unique-code', $deposit->encrypted_id) }}" class="form-block">
            @csrf

            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.input-group
                        addon="<i class='fa-regular fa-calendar'></i>"
                        class="bg-light"
                        :label="__('label.deposit_date')"
                        :old="old('dates', date('d-m-Y', strtotime($deposit->dates)))"
                        readonly
                    />
                </div>
            </div>

            <div class="table-responsive">
                <table class="table" id="table-transaction">
                    <thead>
                        <tr>
                            <th style="width: 50px;">{{ __('label.no') }}</th>
                            <th>{{ __('label.transaction_number') }}</th>
                            <th>{{ __('label.transaction_type') }}</th>
                            <th>{{ __('label.student_or_parent_name') }}</th>
                            <th>{{ __('label.payment') }}</th>
                            <th>{{ __('label.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($deposit->transaction_detail as $index => $t)
                            <tr>
                                <td class="align-top text-center">{{ $index + 1 }}</td>
                                <td class="align-top">
                                    <span class="fw-bold text-{{ $t->flag_detail->color }}">{{ $t->number }}</span>

                                    <div class="mt-3">
                                        <small><b>{{ __('label.transaction_date') }}</b></small><br />
                                        {{ Common::dateFormat($t->dates) }}
                                    </div>
                                </td>
                                <td class="align-top">
                                    {{ $t->flag_detail->name }}
                                </td>
                                <td class="align-top">
                                    @if (empty($t->student->nis))
                                        {{ $t->parent->name }}

                                        <div class="mt-3">
                                            <small><b>{{ __('label.phone_number') }}</b></small><br />
                                            {{ Common::phoneFormat($t->parent->phone) }}
                                        </div>
                                    @else
                                        {{ $t->student->name }}

                                        <div class="mt-3">
                                            <small><b>{{ __('label.nis') }}</b></small><br />
                                            {{ $t->student->nis }}
                                        </div>
                                    @endif
                                </td>
                                <td class="align-top">
                                    {{ $t->method }}

                                    <div class="mt-3">
                                        <small><b>{{ __('label.payment_date') }}</b></small><br />
                                        {{ Common::dateFormat($t->paid_at, 'dd mmm yyyy, hh:ii WIB') }}
                                    </div>
                                </td>
                                <td class="align-top">
                                    @if ($t->donation == 0 && $t->unique_code == 0)
                                        {{ 'Rp. ' . number_format($t->total, 0, '', '.') }}
                                    @else
                                        <table>
                                            @if ($t->donation > 0 or $t->unique_code > 0)
                                                <tr>
                                                    <td class="ps-0 pb-0 pt-0">{{ __('label.subtotal') }}</td>
                                                    <td class="ps-0 pb-0 pt-0">Rp.</td>
                                                    <td class="ps-0 pb-0 pt-0 text-end">{{ number_format($t->subtotal, 0, '', '.') }}</td>
                                                </tr>
                                            @endif

                                            @if ($t->donation > 0)
                                                <tr>
                                                    <td class="ps-0 pb-0 pt-0">{{ __('label.scholarship') }}</td>
                                                    <td class="ps-0 pb-0 pt-0">Rp.</td>
                                                    <td class="ps-0 pb-0 pt-0 text-end">{{ number_format($t->donation, 0, '', '.') }}</td>
                                                </tr>
                                            @endif

                                            @if ($t->unique_code > 0)
                                                <tr>
                                                    <td class="ps-0 pb-0 pt-0">{{ __('label.unique_code') }}</td>
                                                    <td class="ps-0 pb-0 pt-0">Rp.</td>
                                                    <td class="ps-0 pb-0 pt-0 text-end">{{ number_format($t->unique_code, 0, '', '.') }}</td>
                                                </tr>
                                            @endif

                                            <tr>
                                                <td class="ps-0 pb-0 fw-bold">{{ __('label.total') }}</td>
                                                <td class="ps-0 pb-0 fw-bold">Rp.</td>
                                                <td class="ps-0 pb-0 fw-bold text-end">{{ number_format($t->total, 0, '', '.') }}</td>
                                            </tr>
                                        </table>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="fw-bold text-end" colspan="5">{{ __('label.total') }}</th>
                            <th class="fw-bold">{{ 'Rp. ' . number_format($deposit->total, 0, '', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row mt-4">
                <div class="col-sm-6 col-md-3" id="form-status">
                    <x-form.radio
                        name="status"
                        :label="__('label.status')"
                        :option="$status"
                    />
                </div>
            </div>
            <div class="row" id="form-reason">
                <div class="col-sm-6">
                    <x-form.text-area
                        name="reason"
                        :label="__('label.reason')"
                    />
                </div>
            </div>

            <x-form.button-submit
                :label="__('label.verify')"
                :loading="__('label.verifying')"
                :cancel-route="route('finance.transaction.unique-code', 'waiting')"
            />
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const error = "@isset($errors->all()[0]) {{ $errors->all()[0] }} @endisset"
const rejected = "{{ $status_rejected }}"

$(document).ready(function() {
    if (error != "")
        setNotifInfo(error)

    $("#form-reason").hide()

    $("#form-status input").click(function() {
        if ($(this).val() == rejected)
            $("#form-reason").show()
        else
            $("#form-reason").hide()
    })
})
</script>
@endpush
