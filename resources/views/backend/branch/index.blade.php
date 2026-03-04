@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="branch"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <x-section-page-action
            :label="$title"
            :icon="$icon"
            :count="$count"
            :create-route="route('branch.create')"
        />

        <div class="table-responsive">
            <table class="table" id="table-branch">
                <thead>
                    <tr>
                        <th>{{ __('label.no') }}</th>
                        <th>{{ __('label.name') }}</th>
                        <th>{{ __('label.phone_number') }}</th>
                        <th>{{ __('label.email') }}</th>
                        <th>{{ __('label.address') }}</th>
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
    window.LaravelDataTables["table-branch"] = $("#table-branch").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('branch.datatable') }}",
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
                render: (data, type, row, meta) => htmlEntities(row.name)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => phoneFormat(row.phone)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => htmlEntities(row.email)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => htmlEntities(row.address)
            },
            {
                class: "align-middle text-center",
                searchable: false,
                render: function(data, type, row) {
                    if (row.id == 1)
                        return ""
                    else {
                        let url_edit = "{{ route('branch.edit', 0) }}"
                        let url_destroy = "{{ route('branch.destroy', 0) }}"

                        url_edit = url_edit.replace("0", row.encrypted_id)
                        url_destroy = url_destroy.replace("0", row.encrypted_id)

                        return `<a href="${url_edit}" class="btn btn-dark btn-xs set-tooltip" title="${label_edit}">
                                <i class="bx bx-pencil"></i>
                            </a>
                            <a href="javascript:void(0)" class="btn btn-danger btn-xs set-tooltip" title="${label_delete}" onclick="deleteConfirm('${url_destroy}', false, 'table-branch')">
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
