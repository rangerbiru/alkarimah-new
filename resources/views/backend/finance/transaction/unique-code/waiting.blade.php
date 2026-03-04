@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/transaction/unique-code"
    :breadcrumb-data="$render"
/>
@endsection

@section('content')
<div class="card card-tab mb-3">
    <div class="card-body p-2">
        @include($path . 'menu')

        <div class="p-3">
            @if (Auth::user()->is_kasir)
                <div class="mb-3">
                    <a href="{{ route('finance.transaction.create.unique-code') }}" class="btn btn-primary label-btn">
                        <i class="bx bxs-plus-circle label-btn-icon me-2"></i>
                        {{ __('label.create') }}
                    </a>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table" id="table-deposit">
                    <thead>
                        <tr>
                            <th style="width: 50px;">{{ __('label.no') }}</th>
                            <th>{{ __('label.deposit_number') }}</th>
                            <th>{{ __('label.deposit_date') }}</th>
                            <th>{{ __('label.total') }}</th>
                            <th class="text-center" style="width: {{ (Auth::user()->is_kasir) ? '70px' : '35px' }};">#</th>
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
const user_bendahara = "{{ (Auth::user()->is_bendahara) ? 'true' : 'false' }}"

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
            url: "{{ route('finance.transaction.datatable.unique-code') }}",
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
                class: "align-middle text-center",
                searchable: false,
                render: function(data, type, row) {
                    if (user_bendahara == "true") {
                        let url_verify = "{{ route('finance.transaction.verify.unique-code', 0) }}"
                        url_verify = url_verify.replace("0", row.encrypted_id)

                        return `<a href="${url_verify}" class="btn btn-success btn-xs set-tooltip" title="{{ __('label.verification') }}">
                                <i class="fa-solid fa-check"></i>
                            </a>`
                    } else {
                        let url_edit = "{{ route('finance.transaction.edit.unique-code', 0) }}"
                        let url_destroy = "{{ route('finance.transaction.destroy.unique-code', 0) }}"

                        url_edit = url_edit.replace("0", row.encrypted_id)
                        url_destroy = url_destroy.replace("0", row.encrypted_id)

                        return `<a href="${url_edit}" class="btn btn-dark btn-xs set-tooltip" title="${label_edit}">
                                <i class="bx bx-pencil"></i>
                            </a>
                            <a href="javascript:void(0)" class="btn btn-danger btn-xs set-tooltip" title="${label_delete}" onclick="deleteConfirm('${url_destroy}', false, 'table-type')">
                                <i class="bx bx-trash"></i>
                            </a>`
                    }
                }
            }
        ]
    })

    $($.fn.dataTable.tables(true)).css('width', '100%')
});
</script>
@endpush
