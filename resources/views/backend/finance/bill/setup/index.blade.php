@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/bill/setup"
/>
@endsection

@section('content')
<div class="card card-nav">
    <div class="card-body">
        @include($path . 'menu')

        <div class="card-content">
            <div class="row mb-3">
                <div class="col-sm-7 col-md-3">
                    <x-form.select
                        id="year"
                        name="year"
                        :option="$years"
                        :old="$year->id"
                    />
                </div>
                <div class="col-sm-5 col-md-9">
                    <a href="{{ route('finance.bill.setup.create') }}" class="btn btn-primary label-btn">
                        <i class="bx bxs-plus-circle label-btn-icon me-2"></i>
                        {{ __('label.create') }}
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table" id="table-bill">
                    <thead>
                        <tr>
                            <th>{{ __('label.no') }}</th>
                            <th>{{ __('label.type') }}</th>
                            <th>{{ __('label.name') }}</th>
                            <th>{{ __('label.nominal') }}</th>
                            <th>{{ __('label.billing_date') }}</th>
                            <th>{{ __('label.due_date') }}</th>
                            <th style="max-width: 150px;">{{ __('label.information') }}</th>
                            <th style="width: 70px;">#</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
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
let id_year = "{{ $year->id }}"

$(document).ready(function() {
    window.LaravelDataTables = window.LaravelDataTables || {}
    window.LaravelDataTables["table-bill"] = $("#table-bill").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('finance.bill.setup.datatable') }}",
            type: "POST",
            data: (d) => {
                d.year = id_year

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
                width: "50px",
                searchable: false,
                render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => `${htmlEntities(row.type.name)}<br />
                    <small class="text-muted">${row.period_name}</small>`
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => (row.period_monthly) ? `${htmlEntities(row.name)}<br />
                    <small class="text-muted">{{ __('label.validity_period') }} : ${row.period}</small>` : htmlEntities(row.name)
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => `Rp. ${moneyFormat(row.nominal)}`
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => (row.period == "{{ $period_onetime }}") ? dateFormat(row.billing_date, "{dd} {mmm} {yyyy}") : row.billing_date_day
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => (row.period == "{{ $period_onetime }}") ? dateFormat(row.due_date, "{dd} {mmm} {yyyy}") : row.due_date_day
            },
            {
                class: "align-top",
                render: (data, type, row, meta) => (row.description == null) ? "-" : htmlEntities(row.description)
            },
            {
                class: "align-top text-center",
                searchable: false,
                render: function(data, type, row) {
                    let url_edit = "{{ route('finance.bill.setup.edit', 0) }}"
                    let url_destroy = "{{ route('finance.bill.setup.destroy', 0) }}"

                    url_edit = url_edit.replace("0", row.encrypted_id)
                    url_destroy = url_destroy.replace("0", row.encrypted_id)

                    return `<a href="${url_edit}" class="btn btn-dark btn-xs set-tooltip" title="${label_edit}">
                            <i class="bx bx-pencil"></i>
                        </a>
                        <a href="javascript:void(0)" class="btn btn-danger btn-xs set-tooltip" title="${label_delete}" onclick="deleteConfirm('${url_destroy}', false, 'table-bill')">
                            <i class="bx bx-trash"></i>
                        </a>`
                }
            }
        ]
    })

    $($.fn.dataTable.tables(true)).css('width', '100%')
});
</script>
@endpush
