@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/savings/withdrawal/history"
/>
@endsection

@section('content')
<div class="card custom-card">
    <div class="card-body">
        <div class="row">
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
            <div class="col-sm-6 col-md-8">
                <button type="button" id="btn-search" class="btn btn-secondary">
                    <i class="fa-solid fa-search"></i> &nbsp;{{ __('label.search') }}
                </button>
            </div>
        </div>
    </div>
</div>
<div class="card custom-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="table-history">
                <thead>
                    <tr>
                        <th style="width: 50px;">{{ __('label.no') }}</th>
                        <th>{{ __('label.request_number') }}</th>
                        <th>{{ __('label.request_date') }}</th>
                        <th>{{ __('label.nis') }}</th>
                        <th>{{ __('label.student_name') }}</th>
                        <th>{{ __('label.class') }}</th>
                        <th>{{ __('label.total') }}</th>
                        <th>{{ __('label.withdrawal_date') }}</th>
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
let start_date = "{{ $filter->start }}"
let end_date = "{{ $filter->end }}"

$(document).ready(function() {
    window.LaravelDataTables = window.LaravelDataTables || {}
    window.LaravelDataTables["table-history"] = $("#table-history").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('finance.savings.datatable.history-withdrawal') }}",
            type: "POST",
            data: (d) => {
                d.start_date = start_date
                d.end_date = end_date

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
                class: "align-top",
                searchable: false,
                render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => `<span class="text-info fw-bold">${row.number}</span>`
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => dateFormat(row.dates, "{dd} {mmm} {yyyy}")
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => htmlEntities(row.student.nis)
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => htmlEntities(row.student.name)
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => `${row.student.class.level_education.toUpperCase()} ${htmlEntities(row.student.class.name)}`
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => `Rp. ${moneyFormat(row.total)}`
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => dateFormat(row.processed_at, "{dd} {mmm} {yyyy}, {hh}:{ii} WIB")
            },
        ]
    })

    $($.fn.dataTable.tables(true)).css('width', '100%')

    $("#btn-search").click(function() {
        start_date = $("#start").val()
        end_date = $("#end").val()

        const formData = {start: start_date, end: end_date}
        window.history.pushState("", "", `{{ route('finance.savings.history.withdrawal') }}?start=${start_date}&end=${end_date}`)
        window.LaravelDataTables["table-history"].ajax.reload()
    })
});
</script>
@endpush
