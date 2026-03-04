@extends('layouts.mobile.index')

@section('title', $title)
@section('content')
<div class="card custom-card">
    <div class="card-body">
        <div class="clearfix mb-2">
            <div class="float-start me-2">
                <i class="fa-solid fa-file-invoice text-warning" style="font-size: 35px;"></i>
            </div>

            <h6 class="mb-0 text-grey">RINCIAN MUKAFAAH PEGAWAI</h6>
            <span class="text-muted">Bulan {{ Common::monthFormat($payroll->months) . ' ' . $payroll->years }}</span>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="table-responsive">
                    <table class="table table-payroll">
                        <tr>
                            <td class="fw-bold">{{ __('label.basic_salary') }}</td>
                            <td style="width: 30px;">Rp.</td>
                            <td class="text-end" style="width: 100px;">{{ number_format($payroll->salary, 0, '', '.') }}</td>
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
                            <td class="fw-bold ps-3">{{ __('label.total') }}</td>
                            <td class="fw-bold">Rp.</td>
                            <td class="fw-bold text-end">{{ number_format($payroll->total, 0, '', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="{{ route('finance.payroll.download.slip', $payroll->encrypted_id) }}" class="btn btn-danger">
                <i class="fa-solid fa-file-pdf"></i> &nbsp;{{ __('label.download') }}
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table-payroll td {
        padding-left: 0;
        padding-right: 0;
    }
</style>
@endpush
