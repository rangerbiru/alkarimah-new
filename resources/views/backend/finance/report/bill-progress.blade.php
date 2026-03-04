@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/report/bill-progress"
/>
@endsection

@section('content')
<div class="card custom-card">
    <div class="card-body">
        <form class="form-block">
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        name="year"
                        :option="$years"
                        :data-placeholder="__('label.choose_school_year')"
                        :old="$year"
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
        <div id="button-download" class="mb-3">
            <a href="{{ route('finance.report.download.excel.bill-progress', ['year' => $year]) }}" class="btn btn-success label-btn">
                <i class="fa-solid fa-file-excel label-btn-icon me-2"></i>
                DOWNLOAD EXCEL
            </a>
            <a href="{{ route('finance.report.download.pdf.bill-progress', ['year' => $year]) }}" class="btn btn-danger label-btn">
                <i class="fa-solid fa-file-pdf label-btn-icon me-2"></i>
                DOWNLOAD PDF
            </a>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="ps-0" style="width: 40px;">{{ __('label.no') }}</th>
                        <th>{{ __('label.payment_type') }}</th>
                        <th class="text-end">{{ __('label.liability') }}</th>
                        <th class="text-end">{{ __('label.paid_off2') }}</th>
                        <th class="text-end">{{ __('label.less') }}</th>
                        <th colspan="2">{{ __('label.progress') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $no = 1;
                    @endphp

                    @foreach ($bill_progress as $index => $progress)
                        <tr>
                            <td class="fw-bold">{{ $no }}</td>
                            <td class="fw-bold" colspan="6">{{ __('label.level_class') . ' ' . $index }}</td>
                        </tr>

                        @foreach ($progress['data'] as $index_p => $p)
                            @php
                            $no_p = $index_p + 1;
                            @endphp

                            <tr>
                                <td></td>
                                <td>{{ $no . '.' . $no_p . ' : ' . $p->type }}</td>
                                <td class="text-end">{{ number_format($p->total, 0, '', '.') }}</td>
                                <td class="text-end">{{ number_format($p->paid, 0, '', '.') }}</td>
                                <td class="text-end">{{ number_format($p->remaining, 0, '', '.') }}</td>
                                <td style="width: 100px;">
                                    <div class="progress" role="progressbar" aria-label="Animated striped example" aria-valuenow="{{ $p->progress }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar {{ $p->progress_color }} progress-bar-striped progress-bar-animated" style="width: {{ $p->progress }}%"></div>
                                    </div>
                                </td>
                                <td style="width: 80px;">
                                    {{ Common::decimalFormat($p->progress) . '%' }}
                                </td>
                            </tr>
                        @endforeach

                        <tr>
                            <td></td>
                            <td><b>{{ __('label.total') . ' ' . __('label.level_class') . ' ' . $index }}</b></td>
                            <td style="text-align: right;"><b>{{ number_format($progress['total'], 0, '', '.') }}</b></td>
                            <td style="text-align: right;"><b>{{ number_format($progress['paid'], 0, '', '.') }}</b></td>
                            <td style="text-align: right;"><b>{{ number_format($progress['remaining'], 0, '', '.') }}</b></td>
                            <td style="width: 100px;">
                                <div class="progress" role="progressbar" aria-label="Animated striped example" aria-valuenow="{{ $progress['progress'] }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar {{ $progress['progress_color'] }} progress-bar-striped progress-bar-animated" style="width: {{ $progress['progress'] }}%"></div>
                                </div>
                            </td>
                            <td style="width: 80px;">
                                {{ Common::decimalFormat($progress['progress']) . '%' }}
                            </td>
                        </tr>

                        @php
                        $no++;
                        @endphp
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"><b>{{ __('label.total') }}</b></td>
                        <td style="text-align: right;"><b>{{ number_format($sum->total, 0, '', '.') }}</b></td>
                        <td style="text-align: right;"><b>{{ number_format($sum->paid, 0, '', '.') }}</b></td>
                        <td style="text-align: right;"><b>{{ number_format($sum->remaining, 0, '', '.') }}</b></td>
                        <td style="width: 100px;">
                            <div class="progress" role="progressbar" aria-label="Animated striped example" aria-valuenow="{{ $sum->progress }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar {{ $sum->progress_color }} progress-bar-striped progress-bar-animated" style="width: {{ $sum->progress }}%"></div>
                            </div>
                        </td>
                        <td style="width: 80px;">
                            {{ Common::decimalFormat($sum->progress) . '%' }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
