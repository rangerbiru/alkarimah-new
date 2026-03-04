@extends('layouts.backend.index')

@section('title', $title)
@section('header')
<x-section-page
    :label="$title"
    :icon="$icon"
    breadcrumb="academic/class"
/>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <x-section-page-action
            :label="$title"
            :icon="$icon"
            :count="$count"
            :create-route="route('academic.class.create')"
        />

        <div class="table-responsive">
            <table class="table" id="table-class">
                <thead>
                    <tr>
                        <th>{{ __('label.no') }}</th>
                        <th>{{ __('label.wali_kelas') }}</th>
                        <th>{{ __('label.name') }}</th>
                        <th>{{ __('label.level_education') }}</th>
                        <th>{{ __('label.level_class') }}</th>
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
    window.LaravelDataTables["table-class"] = $("#table-class").DataTable({
        language: {
            search: "",
            searchPlaceholder: `${label_search}...`,
            lengthMenu: "_MENU_ Data",
            emptyTable: label_nodata
        },
        ajax:
        {
            url: "{{ route('academic.class.datatable') }}",
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
                render: (data, type, row, meta) => htmlEntities(row.wali_kelas.name)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => htmlEntities(row.name)
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => row.level_education
            },
            {
                class: "align-middle",
                render: (data, type, row, meta) => row.level_class
            },
            {
                class: "align-middle text-center",
                searchable: false,
                render: function(data, type, row) {
                    let url_edit = "{{ route('academic.class.edit', 0) }}"
                    let url_destroy = "{{ route('academic.class.destroy', 0) }}"

                    url_edit = url_edit.replace("0", row.encrypted_id)
                    url_destroy = url_destroy.replace("0", row.encrypted_id)

                    return `<a href="${url_edit}" class="btn btn-dark btn-xs set-tooltip" title="${label_edit}">
                            <i class="bx bx-pencil"></i>
                        </a>
                        <a href="javascript:void(0)" class="btn btn-danger btn-xs set-tooltip" title="${label_delete}" onclick="deleteConfirm('${url_destroy}', false, 'table-class')">
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
