@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="finance/bill/discount"
/>
@endsection

@section('content')
<div class="card custom-card">
    <div class="card-body">
        <x-section-page-action
            :label="$title"
            :icon="$icon"
            :count="$count"
            :create-route="route('finance.bill.discount.create')"
            :filter="true"
            :filter-option="['1' => __('label.active'), '0' => __('label.not_active')]"
            :filter-placeholder="__('label.all_status')"
        />

        <div class="table-responsive">
            <table class="table" id="table-discount">
                <thead>
                    <tr>
                        <th style="width: 50px;">{{ __('label.no') }}</th>
                        <th>{{ __('label.school_year') }}</th>
                        <th>{{ __('label.bill') }}</th>
                        <th>{{ __('label.student') }}</th>
                        <th>{{ __('label.nominal') }}</th>
                        <th>{{ __('label.applies_to') }}</th>
                        <th>{{ __('label.status') }}</th>
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
let status = ""

$(document).ready(function() {
    window.LaravelDataTables = window.LaravelDataTables || {}
    window.LaravelDataTables["table-discount"] = $("#table-discount").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('finance.bill.discount.datatable') }}",
            type: "POST",
            data: (d) => {
                d.status = status
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
                render: (data, discount, row, meta) => meta.row + meta.settings._iDisplayStart + 1
            },
            {
                class: "align-middle",
                render: (data, discount, row, meta) => row.year_name
            },
            {
                class: "align-middle",
                render: (data, discount, row, meta) => htmlEntities(row.bill.name)
            },
            {
                class: "align-middle",
                render: (data, discount, row, meta) => htmlEntities(row.student.name)
            },
            {
                class: "align-middle",
                render: (data, discount, row, meta) => `Rp. ${moneyFormat(row.nominal)}`
            },
            {
                class: "align-middle",
                render: (data, discount, row, meta) => {
                    let result = "-"

                    if (row.applies_to !== null) {
                        result = ""

                        for (const a of Object.keys(row.applies_to)) {
                            const status = (row.applies_to[a] == 0) ? "" : ' <i class="fas fa-check-circle text-success"></i>'
                            result += `<span class="badge bg-outline-grey me-1">${dateFormat(`${a}-01`, "{mmm} {yyyy}")}${status}</span>`
                        }
                    }

                    return result
                }
            },
            {
                class: "align-middle",
                render: (data, discount, row, meta) => row.status_badge
            },
            {
                class: "align-middle text-center",
                searchable: false,
                render: function(data, discount, row) {
                    let url_edit = "{{ route('finance.bill.discount.edit', 0) }}"
                    let url_destroy = "{{ route('finance.bill.discount.destroy', 0) }}"

                    url_edit = url_edit.replace("0", row.encrypted_id)
                    url_destroy = url_destroy.replace("0", row.encrypted_id)

                    return `<a href="${url_edit}" class="btn btn-dark btn-xs set-tooltip" title="${label_edit}">
                            <i class="bx bx-pencil"></i>
                        </a>
                        <a href="javascript:void(0)" class="btn btn-danger btn-xs set-tooltip" title="${label_delete}" onclick="deleteConfirm('${url_destroy}', false, 'table-discount')">
                            <i class="bx bx-trash"></i>
                        </a>`
                }
            }
        ]
    })

    $($.fn.dataTable.tables(true)).css('width', '100%')

    $("#page-filter").change(function() {
        status = $(this).val()
        window.LaravelDataTables["table-discount"].ajax.reload()
    })
});
</script>
@endpush
