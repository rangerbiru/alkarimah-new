@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/transaction/bill/show"
    :breadcrumb-data="$transaction->encrypted_id"
/>
@endsection

@section('content')
<div class="card custom-card">
    <div class="card-body">
        <div class="clearfix mb-4">
            <div class="float-start me-2">
                <i class="fa-solid fa-file-invoice text-warning" style="font-size: 43px;"></i>
            </div>

            <h5 class="mb-0 text-grey">{{ $transaction->number }}</h5>
            <small>&nbsp;{{ __('label.transaction_date') . ' : ' . Common::dateFormat($transaction->dates) }}</small>
        </div>

        <div class="card mb-3">
            <div class="card-body p-3 bg-light">
                <div class="row">
                    <div class="col-sm-6">
                        <table class="table-padding">
                            @if ($transaction->is_topup_saldo)
                                <tr>
                                    <td class="fw-bold" style="width: 120px;">{{ __('label.parent_name') }}</td>
                                    <td class="divide">:</td>
                                    <td>{{ $transaction->parent->name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">{{ __('label.phone_number') }}</td>
                                    <td class="divide">:</td>
                                    <td>{{ Common::phoneFormat($transaction->parent->phone) }}</td>
                                </tr>
                            @elseif ($transaction->is_pengambilan_tabungan)
                                <tr>
                                    <td class="fw-bold" style="width: 145px;">{{ __('label.penanggung_jawab_tabungan') }}</td>
                                    <td class="divide">:</td>
                                    <td>{{ $transaction->personResponsible->name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">{{ __('label.phone_number') }}</td>
                                    <td class="divide">:</td>
                                    <td>{{ Common::phoneFormat($transaction->personResponsible->phone) }}</td>
                                </tr>
                            @else
                                <tr>
                                    <td class="fw-bold" style="width: 100px;">{{ __('label.nis') }}</td>
                                    <td class="divide">:</td>
                                    <td>{{ $transaction->student->nis }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">{{ __('label.student_name') }}</td>
                                    <td class="divide">:</td>
                                    <td>{{ $transaction->student->name }}</td>
                                </tr>

                                @if ($transaction->is_tagihan)
                                    <tr>
                                        <td class="fw-bold">{{ __('label.scholarship') }}</td>
                                        <td class="divide">:</td>
                                        <td>{{ $transaction->get_scholarship }}</td>
                                    </tr>
                                @endif
                            @endif


                            @if ($transaction->is_get_scholarship)
                                <tr>
                                    <td class="fw-bold">{{ __('label.donatur') }}</td>
                                    <td class="divide">:</td>
                                    <td>{{ $transaction->donatur->name }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                    <div class="col-sm-6">
                        <table class="table-padding">
                            <tr>
                                <td class="fw-bold" style="width: 145px;">{{ __('label.payment_method') }}</td>
                                <td class="divide">:</td>
                                <td>{{ $transaction->method->name }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">{{ __('label.payment_date') }}</td>
                                <td class="divide">:</td>
                                <td>{{ Common::dateFormat($transaction->paid_at, 'dd mmm yyyy, hh:ii WIB') }}</td>
                            </tr>

                            @if ($transaction->payment_method == $method_cash)
                                <tr>
                                    <td class="fw-bold">{{ __('label.cashier') }}</td>
                                    <td class="divide">:</td>
                                    <td>{{ $transaction->cashier->name }}</td>
                                </tr>
                            @endif

                            <tr>
                                <td class="fw-bold">{{ __('label.status') }}</td>
                                <td class="divide">:</td>
                                <td>{!! $transaction->status_badge !!}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">{{ __('label.no') }}</th>

                        @if ($transaction->is_tagihan)
                            @php
                            $colspan = 4;
                            @endphp

                            <th style="width: 125px;">{{ __('label.school_year') }}</th>
                            <th style="width: 170px;">{{ __('label.type') }}</th>
                            <th>{{ __('label.bill_name') }}</th>

                            @if ($transaction->discount > 0)
                                @php
                                $colspan = 6;
                                @endphp

                                <th class="text-end">{{ __('label.cost') }}</th>
                                <th class="text-end">{{ __('label.discount') }}</th>
                            @endif
                        @elseif ($transaction->is_pengambilan_tabungan)
                            @php
                            $colspan = 5;
                            @endphp

                            <th style="width: 160px;">{{ __('label.withdrawal_number') }}</th>
                            <th style="width: 175px;">{{ __('label.nis') }}</th>
                            <th>{{ __('label.student_name') }}</th>
                            <th>{{ __('label.class') }}</th>
                        @else
                            @php
                            $colspan = 2;
                            @endphp
                            <th>{{ __('label.bill_name') }}</th>
                        @endif

                        <th class="text-end" colspan="2">{{ __('label.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($transaction->is_tagihan)
                        @foreach ($bills as $index => $b)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $b->bill->year->year_name }}</td>
                                <td>{{ $b->bill->type->name }}</td>
                                <td>
                                    {{ $b->bill->name }}

                                    @if ($b->bill->type->period->value == $period->monthly)
                                        - Bulan {{ Common::monthFormat($b->months) . ' ' . $b->years }}
                                    @elseif ($b->bill->type->period->value == $period->semester)
                                        - Semester {{ $b->semester }}
                                    @endif
                                </td>

                                @if ($transaction->discount > 0)
                                    <td class="text-end">{{ number_format($b->subtotal, 0, '', '.') }}</td>
                                    <td class="text-end">{{ number_format($b->discount, 0, '', '.') }}</td>
                                @endif

                                <td>Rp.</td>
                                <td class="text-end">{{ number_format($b->total, 0, '', '.') }}</td>
                            </tr>
                        @endforeach
                    @elseif ($transaction->is_pengambilan_tabungan)
                        @foreach ($withdrawals as $index => $w)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $w->number }}</td>
                                <td>{{ $w->nis }}</td>
                                <td>{{ $w->name }}</td>
                                <td>{{ $w->class_name }}</td>
                                <td>Rp.</td>
                                <td class="text-end">{{ number_format($w->total, 0, '', '.') }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="text-center">1</td>
                            <td>{{ $transaction->flag_detail->name }}</td>
                            <td>Rp.</td>
                            <td class="text-end">{{ number_format($transaction->subtotal, 0, '', '.') }}</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="{{ $colspan }}" class="text-end pb-1 border-bottom-0">{{ __('label.subtotal') }}</th>
                        <th class="text-primary pb-1 border-bottom-0" style="width: 20px;">Rp.</th>
                        <th class="text-end text-primary pb-1 border-bottom-0" style="width: 100px;">{{ number_format($transaction->subtotal, 0, '', '.') }}</th>
                    </tr>

                    @if ($transaction->discount > 0)
                        <tr>
                            <th colspan="{{ $colspan }}" class="text-end pb-1 border-bottom-0">{{ __('label.discount') }}</th>
                            <th class="text-primary pb-1 border-bottom-0" style="width: 20px;">Rp.</th>
                            <th class="text-end text-primary pb-1 border-bottom-0" style="width: 100px;">{{ number_format($transaction->discount, 0, '', '.') }}</th>
                        </tr>
                    @endif

                    @if ($transaction->is_get_scholarship)
                        <tr>
                            <td colspan="{{ $colspan }}" class="text-end pb-1 border-bottom-0">{{ __('label.scholarship') }}</td>
                            <td class="pb-1 border-bottom-0">Rp.</td>
                            <td class="text-end pb-1 border-bottom-0">{{ number_format($transaction->donation, 0, '', '.') }}</td>
                        </tr>
                    @endif

                    @if ($transaction->unique_code > 0)
                        <tr>
                            <td colspan="{{ $colspan }}" class="text-end pb-1 border-bottom-0">{{ __('label.unique_code') }}</td>
                            <td class="pb-1 border-bottom-0">Rp.</td>
                            <td class="text-end pb-1 border-bottom-0">{{ number_format($transaction->unique_code, 0, '', '.') }}</td>
                        </tr>
                    @endif

                    <tr>
                        <td colspan="{{ $colspan }}" class="fw-bold text-end pb-1 border-bottom-0">{{ __('label.total') }}</td>
                        <td class="text-success fw-bold pb-1 border-bottom-0">Rp.</td>
                        <td class="text-end text-success fw-bold pb-1 border-bottom-0">{{ number_format($transaction->total, 0, '', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <hr />
        @if ($transaction->is_pengambilan_tabungan)
            <a href="{{ route('finance.savings.download.excel.withdrawal', $transaction->encrypted_id) }}" class="btn btn-success label-btn set-tooltip" title="Download {{ __('label.proof_savings_withdrawal') }}">
                <i class="fa-solid fa-file-excel label-btn-icon me-2"></i>
                {{ __('label.proof_withdrawal') }}
            </a>
        @endif

        <a href="{{ route('finance.transaction.history') }}" class="btn btn-secondary">
            {{ strtoupper(__('label.close')) }}
        </a>
    </div>
</div>
@endsection
