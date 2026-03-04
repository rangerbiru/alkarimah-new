@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/payroll/show/slip"
    :breadcrumb-data="$payroll->encrypted_id"
/>
@endsection

@section('content')
<div class="card custom-card">
    <div class="card-body">
        <div class="clearfix mb-4">
            <div class="float-start me-2">
                <i class="fa-solid fa-file-invoice text-warning" style="font-size: 43px;"></i>
            </div>

            <h5 class="mb-0 text-grey">RINCIAN MUKAFAAH PEGAWAI</h5>
            <span class="text-muted">Bulan {{ Common::monthFormat($payroll->months) . ' ' . $payroll->years }}</span>
        </div>

        <div class="card mb-3">
            <div class="card-body p-3 bg-light">
                <div class="row">
                    <div class="col-sm-6">
                        <table class="table-padding">
                            <tr>
                                <td class="fw-bold" style="width: 80px;">{{ __('label.name') }}</td>
                                <td class="divide">:</td>
                                <td>{{ $payroll->employee->name }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">{{ __('label.nip') }}</td>
                                <td class="divide">:</td>
                                <td>{{ (empty($payroll->employee->nip)) ? '-' : $payroll->employee->nip }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">{{ __('label.position') }}</td>
                                <td class="divide">:</td>
                                <td>{{ (empty($payroll->employee->id_position)) ? '-' : $payroll->employee->position->name }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-sm-6">
                        <table class="table-padding">
                            <tr>
                                <td class="fw-bold" style="width: 80px;">{{ __('label.nik') }}</td>
                                <td class="divide">:</td>
                                <td>{{ (empty($payroll->employee->nik)) ? '-' : $payroll->employee->nik }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">{{ __('label.status') }}</td>
                                <td class="divide">:</td>
                                <td>{{ $payroll->employee->status_employment_name }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <td class="fw-bold">{{ __('label.basic_salary') }}</td>
                            <td style="width: 30px;">Rp.</td>
                            <td class="text-end" style="width: 120px;">{{ number_format($payroll->salary, 0, '', '.') }}</td>
                        </tr>

                        @if (!empty($payroll->allowance_details->structural))
                            <tr>
                                <td class="fw-bold">{{ __('label.structural_allowance') }}</td>
                                <td></td>
                                <td></td>
                            </tr>

                            @foreach ($payroll->allowance_details->structural as $s)
                                <tr>
                                    <td class="ps-4">{{ $s->name }}</td>
                                    <td>Rp.</td>
                                    <td class="text-end">{{ number_format($s->nominal, 0, '', '.') }}</td>
                                </tr>
                            @endforeach
                        @endif

                        @if (!empty($payroll->allowance_details->liability))
                            <tr>
                                <td class="fw-bold">{{ __('label.liability_allowance') }}</td>
                                <td></td>
                                <td></td>
                            </tr>

                            @foreach ($payroll->allowance_details->liability as $l)
                                <tr>
                                    <td class="ps-4">{{ $l->name }}</td>
                                    <td>Rp.</td>
                                    <td class="text-end">{{ number_format($l->nominal, 0, '', '.') }}</td>
                                </tr>
                            @endforeach
                        @endif

                        @if (!empty($payroll->allowance_details->performance))
                            <tr>
                                <td class="fw-bold">{{ __('label.performance_allowance') }}</td>
                                <td></td>
                                <td></td>
                            </tr>

                            @foreach ($payroll->allowance_details->performance as $p)
                                <tr>
                                    <td class="ps-4">{{ $p->name }}</td>
                                    <td>Rp.</td>
                                    <td class="text-end">{{ number_format($p->nominal, 0, '', '.') }}</td>
                                </tr>
                            @endforeach
                        @endif

                        <tr class="table-success">
                            <td class="fw-bold">{{ __('label.total') }}</td>
                            <td class="fw-bold">Rp.</td>
                            <td class="fw-bold text-end">{{ number_format($payroll->total, 0, '', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <hr />
        <a href="{{ route('finance.payroll.slip') }}" class="btn btn-secondary">
            {{ strtoupper(__('label.close')) }}
        </a>
    </div>
</div>
@endsection
