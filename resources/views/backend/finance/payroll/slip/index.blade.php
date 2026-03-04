@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/report/bill-student"
/>
@endsection

@section('content')
<div class="card custom-card">
    <div class="card-body">
        <form class="form-block">
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <x-form.select
                        name="month"
                        :option="$months"
                        :old="$month"
                    />
                </div>
                <div class="col-sm-6 col-md-2">
                    <x-form.select
                        name="year"
                        :option="$years"
                        :old="$year"
                    />
                </div>
                <div class="col-sm-6 col-md-5">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fa-solid fa-search"></i> &nbsp;{{ __('label.search') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="table-payroll">
                <thead>
                    <tr>
                        <th>{{ __('label.no') }}</th>
                        <th>{{ __('label.month') }}</th>
                        <th>{{ __('label.nip') }}</th>
                        <th>{{ __('label.name') }}</th>
                        <th>{{ __('label.basic_salary') }}</th>
                        <th>{{ __('label.allowance') }}</th>
                        <th>{{ __('label.total') }}</th>
                        <th style="width: 70px;">#</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('vendors/datatables/DataTables-1.13.6/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('vendors/datatables/datatables.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('vendors/datatables/DataTables-1.13.6/js/dataTables.bootstrap5.min.js') }}" type="text/javascript"></script>

<script>
$(document).ready(function() {
    window.LaravelDataTables = window.LaravelDataTables || {}
    window.LaravelDataTables["table-payroll"] = $("#table-payroll").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('finance.payroll.datatable.slip') }}",
            type: "POST",
            data: (d) => {
                d.month = "{{ $month }}"
                d.year = "{{ $year }}"

                return d
            }
        },
        processing: true,
        serverSide: true,
        deferRender: true,
        ordering: false,
        aLengthMenu: [[10, 25, 50, 100],[10, 25, 50, 100]],
        drawCallback: function() {
            $(".set-tooltip").tooltip({
                container: "body"
            })
        },
        columns: [
            {
                class: "align-middle",
                width: "50px",
                searchable: false,
                render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => `${monthFormat(row.months)} ${row.years}`
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => (row.employee.nip == null) ? "-" : htmlEntities(row.employee.nip)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => htmlEntities(row.employee.name)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => `Rp. ${moneyFormat(row.salary)}`
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => `Rp. ${moneyFormat(row.allowance)}`
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => `Rp. ${moneyFormat(row.total)}`
            },
            {
                class: "align-middle text-center",
                searchable: false,
                render: function(data, type, row) {
                    let url_show = "{{ route('finance.payroll.show.slip', 0) }}"
                    let url_download = "{{ route('finance.payroll.download.slip', 0) }}"

                    url_show = url_show.replace("0", row.encrypted_id)
                    url_download = url_download.replace("0", row.encrypted_id)

                    return `<a href="${url_show}" class="btn btn-secondary btn-xs set-tooltip" title="{{ __('label.detail') }}">
                            <i class="fa-solid fa-search"></i>
                        </a>
                        <a href="${url_download}" class="btn btn-success btn-xs set-tooltip" title="{{ __('label.print') . ' ' . __('label.salary_slip') }}">
                            <i class="fa-solid fa-print"></i>
                        </a>`
                }
            }
        ]
    })

    $($.fn.dataTable.tables(true)).css('width', '100%')
});
</script>
@endpush
