@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/report/bill-not-paid"
/>
@endsection

@section('content')
<div class="card custom-card">
    <div class="card-body">
        <div class="alert alert-info">
            {!! __('string.bill_not_paid_list') !!} :
        </div>

        <div class="table-responsive">
            <table class="table" id="table-report">
                <thead>
                    <tr>
                        <th style="width: 50px;">{{ __('label.no') }}</th>
                        <th style="width: 120px;">{{ __('label.school_year') }}</th>
                        <th>{{ __('label.bill_name') }}</th>
                        <th>{{ __('label.nis') }}</th>
                        <th>{{ __('label.student_name') }}</th>
                        <th>{{ __('label.total') }}</th>
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

    window.LaravelDataTables["table-report"] = $("#table-report").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('dashboard.datatable.bill-not-paid') }}",
            type: "POST"
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
                render: (data, type, row, meta) => row.year
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => htmlEntities(row.bill_name)
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
                render: (data, type, row, meta) => `Rp. ${moneyFormat(row.total)}`
            },
        ]
    })

    $($.fn.dataTable.tables(true)).css('width', '100%')
})
</script>
@endpush
