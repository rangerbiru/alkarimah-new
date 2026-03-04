@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/transaction/cash"
    :breadcrumb-data="$render"
/>
@endsection

@section('content')
<div class="card card-tab mb-3">
    <div class="card-body p-2">
        @include($path . 'menu')

        <div class="p-3">
            <div class="table-responsive">
                <table class="table" id="table-deposit">
                    <thead>
                        <tr>
                            <th style="width: 50px;">{{ __('label.no') }}</th>
                            <th>{{ __('label.deposit_number') }}</th>
                            <th>{{ __('label.deposit_date') }}</th>
                            <th>{{ __('label.total') }}</th>
                            <th>{{ __('label.bendahara') }}</th>
                            <th>{{ __('label.verification_date') }}</th>
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
const render = "{{ $render }}"

$(document).ready(function() {
    window.LaravelDataTables = window.LaravelDataTables || {}
    window.LaravelDataTables["table-deposit"] = $("#table-deposit").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('finance.transaction.datatable.cash') }}",
            type: "POST",
            data: (d) => {
                d.render = render

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
                searchable: false,
                render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => row.number
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => dateFormat(row.dates)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => `Rp. ${moneyFormat(row.total)}`
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => htmlEntities(row.verificator.name)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => dateFormat(row.verified_at, "{dd} {mmm} {yyyy}, {hh}:{ii} WIB")
            }
        ]
    })

    $($.fn.dataTable.tables(true)).css('width', '100%')
});
</script>
@endpush
