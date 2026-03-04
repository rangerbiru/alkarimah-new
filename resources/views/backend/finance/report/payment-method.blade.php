@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/report/payment-method"
/>
@endsection

@section('content')
<div class="card custom-card">
    <div class="card-body">
        <form method="get" class="form-block">
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        name="year"
                        :option="$years"
                        :data-placeholder="__('label.choose_school_year')"
                        :old="$year"
                    />
                </div>
                <div class="col-sm-6 col-md-4">
                    <x-form.date-picker
                        id-start="start"
                        id-end="end"
                        name-start="start"
                        name-end="end"
                        picker-type="date-range"
                        :old="$filter->start"
                        :old-end="$filter->end"
                    />
                </div>
                <div class="col-sm-6 col-md-5">
                    <button type="button" class="btn btn-secondary btn-submit" data-loading="{{ strtoupper(__('label.searching')) }}">
                        <i class="fa-solid fa-search"></i> &nbsp;{{ __('label.search') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="card custom-card">
    <div class="card-body">
        <div class="mb-3">
            <button type="button" id="btn-download-excel" class="btn btn-success label-btn">
                <i class="fa-solid fa-file-excel label-btn-icon me-2"></i>
                DOWNLOAD EXCEL
            </button>
            <button type="button" id="btn-download-pdf" class="btn btn-danger label-btn">
                <i class="fa-solid fa-file-pdf label-btn-icon me-2"></i>
                DOWNLOAD PDF
            </button>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="ps-0" style="width: 50px;">{{ __('label.no') }}</th>
                                <th>{{ __('label.payment_method') }}</th>
                                <th class="text-end">{{ __('label.total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-0">1</td>
                                <td>{{ __('label.cash') }}</td>
                                <td class="text-end">{{ number_format($cash, 0, '', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="ps-0">2</td>
                                <td>{{ __('label.bank_bni') }}</td>
                                <td class="text-end">{{ number_format($bni, 0, '', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="ps-0">3</td>
                                <td>{{ __('label.bank_bsi') }}</td>
                                <td class="text-end">{{ number_format($bsi, 0, '', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="ps-0">4</td>
                                <td>{{ __('label.balance_topup') }}</td>
                                <td class="text-end">{{ number_format($topup, 0, '', '.') }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="ps-0 fw-bold" colspan="2">{{ __('label.total') }}</th>
                                <th class="text-end fw-bold">{{ number_format($cash + $bni + $bsi + $topup, 0, '', '.') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let start_date = "{{ $filter->start }}"
let end_date = "{{ $filter->end }}"

$(document).ready(function() {
    $("#btn-download-excel").click(function() {
        window.location = `{{ route('finance.report.download.excel.payment-method') }}?start_date=${start_date}&end_date=${end_date}`
    })

    $("#btn-download-pdf").click(function() {
        window.location = `{{ route('finance.report.download.pdf.payment-method') }}?start_date=${start_date}&end_date=${end_date}`
    })
})
</script>
@endpush
