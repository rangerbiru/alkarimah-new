@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/payroll/setup"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="table-employee">
                <thead>
                    <tr>
                        <th>{{ __('label.no') }}</th>
                        <th>{{ __('label.nip') }}</th>
                        <th>{{ __('label.nik') }}</th>
                        <th>{{ __('label.name') }}</th>
                        <th>{{ __('label.phone_number') }}</th>
                        <th>{{ __('label.salary') }}</th>
                        <th>{{ __('label.setup') }}</th>
                        <th style="width: 35px;">#</th>
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
    window.LaravelDataTables["table-employee"] = $("#table-employee").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('finance.payroll.datatable.setup') }}",
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
                class: "align-middle",
                width: "50px",
                searchable: false,
                render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => (row.nip == null) ? "-" : htmlEntities(row.nip)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => (row.nik == null) ? "-" : htmlEntities(row.nik)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => htmlEntities(row.name)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => phoneFormat(row.phone)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => `Rp. ${moneyFormat(row.salary)}`
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => row.status
            },
            {
                class: "align-middle text-center",
                searchable: false,
                render: function(data, type, row) {
                    let url_edit = "{{ route('finance.payroll.edit.setup', 0) }}"
                    url_edit = url_edit.replace("0", row.encrypted_id)

                    return `<a href="${url_edit}" class="btn btn-dark btn-xs set-tooltip" title="${label_edit}">
                            <i class="bx bx-cog"></i>
                        </a>`
                }
            }
        ]
    })

    $($.fn.dataTable.tables(true)).css('width', '100%')
});
</script>
@endpush
